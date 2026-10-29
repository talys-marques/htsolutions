<?php
/**
 * Menu do topo do site (Configurações → Menu). Fica em setting('menu'):
 *
 *   [{ type, label, id | slug | url, children: [...] }, ...]
 *
 *   type      um de TYPES
 *   label     nome no menu; vazio = título da página, nome da categoria etc.
 *   id        page e post: id do post (o link acompanha se o endereço mudar)
 *   slug      category e tag
 *   url       link: endereço livre; vazio = só um título que abre os subitens
 *   new_tab   true = abre em nova aba (só aparece quando ligado)
 *   children  subitens (um nível só), apenas nos itens do primeiro nível
 *
 * "categories" abre a lista de todas as categorias com posts publicados,
 * montada na hora: categorias novas entram sozinhas.
 * "blog" leva à lista de posts: a página dos posts escolhida em Configurações →
 * Página inicial ou, sem página fixa no início, o próprio início.
 * Item cujo destino não está no ar (rascunho, lixeira, categoria sem posts
 * publicados) some do site e volta quando ele for publicado.
 *
 * Para criar um tipo: inclua em TYPES e trate em normalize(), resolve() e
 * describe(); para ele aparecer no seletor do painel, em search().
 */
final class Menu
{
    public const TYPES = [
        'home' => 'Início',
        'blog' => 'Posts',
        'page' => 'Página',
        'post' => 'Post',
        'category' => 'Categoria',
        'tag' => 'Tag',
        'categories' => 'Categorias',
        'link' => 'Link',
    ];

    /** Itens salvos (ou os de $menu), limpos. Aceita o formato antigo [{ label, url }]. */
    public static function items(?array $menu = null): array
    {
        return array_values(array_filter(array_map(fn($item) => self::normalize($item), $menu ?? (array) setting('menu', []))));
    }

    /**
     * Menu pronto para theme/layout.php:
     * [{ label, url, new_tab, current, active, children: [{ label, url, new_tab, current }] }]
     * url vazio = item que só abre os subitens. active = ele ou um subitem é a página atual.
     */
    public static function links(): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $links = [];
        foreach (self::items() as $item) {
            $children = $item['type'] === 'categories' ? self::allCategories() : [];
            foreach ($item['children'] as $child) {
                $link = self::resolve($child);
                if ($link && $link['url'] !== '') {
                    $children[] = $link;
                }
            }
            $link = self::resolve($item);
            if (!$link) {
                // Destino fora do ar: os subitens continuam no menu, no primeiro nível.
                foreach ($children as $child) {
                    $links[] = $child + ['children' => []];
                }
            } elseif ($link['url'] !== '' || $children) {
                $links[] = $link + ['children' => $children];
            }
        }
        foreach ($links as &$link) {
            foreach ($link['children'] as &$child) {
                $child['current'] = $child['url'] === $path;
            }
            unset($child);
            $link['current'] = $link['url'] === $path;
            $link['active'] = $link['current'] || in_array(true, array_column($link['children'], 'current'), true);
        }
        return $links;
    }

    /** Linhas do formulário de Configurações (menu[n][type|target|label|sub]), na ordem da tela. */
    public static function fromInput(array $rows): array
    {
        $get = fn($row, string $key) => is_array($row) && is_string($row[$key] ?? null) ? trim($row[$key]) : '';
        $menu = [];
        foreach ($rows as $row) {
            $target = $get($row, 'target');
            $item = ['type' => $get($row, 'type'), 'label' => mb_substr($get($row, 'label'), 0, 80), 'id' => (int) $target, 'slug' => $target, 'url' => $target,
                'new_tab' => $get($row, 'new_tab') !== ''];
            // Subitem entra no último item do primeiro nível. "categories" já tem os seus, então não vira subitem.
            if ($get($row, 'sub') !== '' && $menu && $item['type'] !== 'categories') {
                if ($child = self::normalize($item, false)) {
                    $menu[array_key_last($menu)]['children'][] = $child;
                }
            } elseif ($item = self::normalize($item)) {
                $menu[] = $item;
            }
        }
        return $menu;
    }

    /** Itens para a tela de Configurações, numa lista só (cada subitem logo depois do pai). */
    public static function rows(array $menu): array
    {
        $rows = [];
        foreach (self::items($menu) as $item) {
            $rows[] = self::describe($item) + ['sub' => false];
            foreach ($item['children'] as $child) {
                $rows[] = self::describe($child) + ['sub' => true];
            }
        }
        return $rows;
    }

    /**
     * Um item como o painel mostra: type, type_label, target (id, slug ou endereço), label, new_tab,
     * title (nome do destino), auto (nome usado com label vazio), note (aviso na lista)
     * e hint (detalhe no seletor).
     */
    public static function describe(array $item): array
    {
        $row = ['type' => $item['type'], 'type_label' => self::TYPES[$item['type']], 'target' => '', 'label' => $item['label'] ?? '',
            'new_tab' => !empty($item['new_tab']), 'title' => '', 'auto' => '', 'note' => '', 'hint' => ''];
        switch ($item['type']) {
            case 'home':
                return ['title' => 'Página inicial', 'auto' => 'Início'] + $row;
            case 'blog':
                $blog = Posts::blogPage();
                return [
                    'title' => 'Página dos posts' . ($blog ? ' (' . $blog['title'] . ')' : ''),
                    'auto' => $blog['title'] ?? 'Blog',
                    'note' => match (true) {
                        $blog !== null => '',
                        Posts::homePage() === null => 'Os posts estão na página inicial: o item leva para o início.',
                        default => 'Sem página dos posts em Configurações → Página inicial: não aparece no site.',
                    },
                    'hint' => $blog ? '/' . $blog['slug'] . '/' : 'Lista de posts',
                ] + $row;
            case 'page':
            case 'post':
                $row['target'] = (string) $item['id'];
                $post = Posts::item($item['id']);
                if (!$post || $post['status'] === 'trash') {
                    return ['title' => $post['title'] ?? 'Item excluído', 'note' => 'Está na lixeira ou foi excluído: não aparece no site.'] + $row;
                }
                $scheduled = Posts::isScheduled($post);
                return [
                    'title' => $post['title'],
                    'auto' => $post['title'],
                    'note' => match (true) {
                        $post['status'] === 'draft' => 'Rascunho: aparece no menu quando for publicado.',
                        $scheduled => 'Agendado: aparece no menu a partir de ' . format_date($post['published_at'], true) . '.',
                        default => '',
                    },
                    'hint' => match (true) {
                        $post['status'] === 'draft' => 'Rascunho',
                        $scheduled => 'Agendado',
                        default => $item['type'] === 'post' ? format_date($post['published_at']) : '',
                    },
                ] + $row;
            case 'category':
            case 'tag':
                $name = self::terms($item['type'], false)[$item['slug']]['name'] ?? $item['slug'];
                $count = self::terms($item['type'])[$item['slug']]['count'] ?? 0;
                return [
                    'target' => $item['slug'],
                    'title' => $name,
                    'auto' => $name,
                    'note' => $count ? '' : 'Sem posts publicados: não aparece no site.',
                    'hint' => $count === 1 ? '1 post' : "$count posts",
                ] + $row;
            case 'categories':
                $count = count(self::terms('category'));
                return [
                    'title' => 'Todas as categorias',
                    'auto' => 'Categorias',
                    'note' => $count
                        ? "Abre a lista das $count categorias com posts publicados, em ordem alfabética. As novas entram sozinhas."
                        : 'Ainda não há categorias com posts publicados: não aparece no site.',
                    'hint' => 'Lista automática',
                ] + $row;
            default:
                return ['target' => $item['url'], 'title' => $item['url'], 'note' => $item['url'] === '' ? 'Sem endereço: é só um título que abre os subitens.' : ''] + $row;
        }
    }

    /**
     * Opções do seletor do painel. $type: page | post | category | tag.
     * Sem busca, os mais recentes (páginas e posts) ou os mais usados (categorias e tags).
     */
    public static function search(string $type, string $query, int $limit = 30): array
    {
        $query = normalize_text($query);
        $matches = fn(string $text) => $query === '' || str_contains(normalize_text($text), $query);
        $found = [];
        if ($type === 'page' && $matches('Página inicial Início')) {
            $found[] = ['type' => 'home', 'label' => ''];
        }
        if ($type === 'page' && $matches('Página dos posts Blog')) {
            $found[] = ['type' => 'blog', 'label' => ''];
        }
        if ($type === 'category' && $matches('Todas as categorias')) {
            $found[] = ['type' => 'categories', 'label' => ''];
        }
        if ($type === 'page' || $type === 'post') {
            foreach (Posts::query(['type' => $type]) as $item) {
                if (count($found) > $limit) {
                    break;
                }
                if ($matches($item['title'])) {
                    $found[] = ['type' => $type, 'id' => $item['id'], 'label' => ''];
                }
            }
        } elseif ($type === 'category' || $type === 'tag') {
            foreach (self::terms($type, false) as $term) {
                if ($matches($term['name'])) {
                    $found[] = ['type' => $type, 'slug' => $term['slug'], 'label' => ''];
                }
            }
        }
        return [
            'items' => array_map(fn($item) => self::describe($item), array_slice($found, 0, $limit)),
            'more' => count($found) > $limit,
        ];
    }

    /* -------------------------------------------------------------- Interno */

    /** Item limpo, ou null se não serve. $top = item do primeiro nível (pode ter subitens). */
    private static function normalize($item, bool $top = true): ?array
    {
        if (!is_array($item)) {
            return null;
        }
        if (!isset($item['type'])) {
            $item = self::fromUrl((string) ($item['url'] ?? '')) + $item; // formato antigo
        }
        $clean = ['type' => (string) $item['type'], 'label' => trim((string) ($item['label'] ?? ''))];
        switch ($clean['type']) {
            case 'page':
            case 'post':
                $clean['id'] = (int) ($item['id'] ?? 0);
                if ($clean['id'] <= 0) {
                    return null;
                }
                break;
            case 'category':
            case 'tag':
                $clean['slug'] = slugify((string) ($item['slug'] ?? ''));
                if ($clean['slug'] === '') {
                    return null;
                }
                break;
            case 'link':
                $url = trim((string) ($item['url'] ?? ''));
                $clean['url'] = self::cleanUrl($url);
                // Endereço recusado (javascript: etc.) descarta o item; vazio vira um título que só abre os subitens.
                if (($url !== '' && $clean['url'] === '') || ($clean['url'] === '' && $clean['label'] === '')) {
                    return null;
                }
                break;
            case 'categories':
                if (!$top) {
                    return null;
                }
                break;
            case 'home':
            case 'blog':
                break;
            default:
                return null;
        }
        // Nova aba só vale para itens com link ("categories" e link sem endereço só abrem a lista).
        if (!empty($item['new_tab']) && $clean['type'] !== 'categories' && ($clean['url'] ?? '-') !== '') {
            $clean['new_tab'] = true;
        }
        if ($top) {
            $clean['children'] = array_values(array_filter(array_map(fn($child) => self::normalize($child, false), (array) ($item['children'] ?? []))));
        }
        return $clean;
    }

    /** Formato antigo: adivinha o tipo pelo endereço ("/sobre/" vira a página Sobre). */
    private static function fromUrl(string $url): array
    {
        if ($url === '/') {
            return ['type' => 'home'];
        }
        if (preg_match('#^/(category|tag)/([^/?\#]+)/?$#', $url, $m)) {
            return ['type' => $m[1], 'slug' => rawurldecode($m[2])];
        }
        if (preg_match('#^/([^/?\#]+)/?$#', $url, $m) && ($post = Posts::findBySlug(rawurldecode($m[1])))) {
            return ['type' => $post['type'], 'id' => $post['id']];
        }
        return ['type' => 'link', 'url' => $url];
    }

    /** Endereço de um link: http(s), caminho do site, âncora, mailto: ou tel:. "site.com/x" ganha https://. */
    private static function cleanUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\s"\'<>]/', $url)) {
            return '';
        }
        if (preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $url)) {
            return $url;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return ''; // outros esquemas (javascript: etc.)
        }
        return preg_match('#^[\w-]+(\.[\w-]+)+(/|$)#', $url) ? 'https://' . $url : '/' . $url;
    }

    /** Item → ['label', 'url', 'new_tab'] para o site, ou null se o destino não está no ar. */
    private static function resolve(array $item): ?array
    {
        switch ($item['type']) {
            case 'home':
                $link = ['label' => $item['label'] ?: 'Início', 'url' => url('/')];
                break;
            case 'blog':
                $blog = Posts::blogPage();
                $link = $blog || !Posts::homePage() ? ['label' => $item['label'] ?: ($blog['title'] ?? 'Blog'), 'url' => $blog ? post_url($blog) : url('/')] : null;
                break;
            case 'page':
            case 'post':
                $post = Posts::item($item['id']);
                $link = $post && Posts::isPublic($post) ? ['label' => $item['label'] ?: $post['title'], 'url' => post_url($post)] : null;
                break;
            case 'category':
            case 'tag':
                $term = self::terms($item['type'])[$item['slug']] ?? null;
                $link = $term ? ['label' => $item['label'] ?: $term['name'], 'url' => term_url($item['type'], $term['name'])] : null;
                break;
            case 'categories':
                $link = self::terms('category') ? ['label' => $item['label'] ?: 'Categorias', 'url' => ''] : null;
                break;
            default:
                $link = ['label' => $item['label'] ?: $item['url'], 'url' => $item['url'] === '' ? '' : url($item['url'])];
        }
        return $link ? $link + ['new_tab' => !empty($item['new_tab'])] : null;
    }

    /** Todas as categorias com posts publicados, em ordem alfabética (item "categories"). */
    private static function allCategories(): array
    {
        $terms = self::terms('category');
        usort($terms, fn($a, $b) => strnatcmp(normalize_text($a['name']), normalize_text($b['name'])));
        return array_map(fn($term) => ['label' => $term['name'], 'url' => term_url('category', $term['name']), 'new_tab' => false], $terms);
    }

    /** Categorias ou tags ($type: category | tag) indexadas pelo slug. $public = false inclui as que só estão em rascunhos. */
    private static function terms(string $type, bool $public = true): array
    {
        static $cache = [];
        return $cache[$type][(int) $public] ??= Posts::terms($type === 'tag' ? 'tags' : 'categories', $public);
    }
}
