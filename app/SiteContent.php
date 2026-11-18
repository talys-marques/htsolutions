<?php
/**
 * Conteúdo do site: páginas com campos fixos, que o time de negócio edita num formulário
 * (Painel → Conteúdo do site) sem mexer na montagem da página.
 *
 * Cada página é uma página do blog (type page) com dois campos a mais:
 *   template   nome do modelo
 *   fields     os valores, na estrutura dos campos do modelo (app/Field.php)
 * Assim ela ganha do blog o endereço, o SEO, o sitemap, a busca, a lixeira e as versões.
 *
 * Os modelos vêm do tema atual e, no que faltar, do tema padrão (theme/):
 *   fields/{modelo}.php            campos e opções do modelo (formato abaixo)
 *   templates/{modelo}.php         HTML da página; recebe $fields completos (Site::renderPost)
 *   content/{modelo}.json          conteúdo inicial, opcional (install());
 *   content/{modelo}/{slug}.json   o de cada página de um modelo repetido
 *
 *   return [
 *       'label' => 'ESG',           nome no painel
 *       'description' => '...',     texto acima do formulário (opcional)
 *       'order' => 4,               ordem na lista do painel (padrão 50)
 *       'repeatable' => true,       várias páginas com este modelo (ex.: uma por marca)
 *       'shared' => true,           conteúdo de todas as páginas (topo, rodapé): fica numa página
 *                                   em rascunho e o tema lê com SiteContent::shared('nome')
 *       'fields' => ['titulo' => Field::text('Título'), ...],
 *   ];
 *
 * Guia para quem cria modelos: themes/README.md ("Páginas com campos").
 */
final class SiteContent
{
    /** Modelos de cada tema (pelo nome do tema), lidos uma vez por requisição. */
    private static array $templates = [];
    private static array $pagesOf = [];
    private static array $shared = [];

    /* ============================================================ Modelos */

    /** Modelos do tema atual, na ordem do painel: [nome => modelo]. */
    public static function templates(): array
    {
        $theme = Themes::current();
        $slug = $theme['slug'];
        if (!isset(self::$templates[$slug])) {
            self::$templates[$slug] = []; // um modelo que lê páginas enquanto carrega não entra em laço
            Themes::config(); // o functions.php do tema pode ter funções usadas nos modelos
            $files = [];
            foreach (array_unique([THEME_DIR, $theme['dir']]) as $dir) {
                foreach (glob($dir . '/fields/*.php') ?: [] as $file) {
                    $files[basename($file, '.php')] = $file; // o do tema vale mais que o do padrão
                }
            }
            $templates = [];
            foreach ($files as $name => $file) {
                if (preg_match('/^[a-z0-9-]+$/', $name) && ($template = self::load($name, $file))) {
                    $templates[$name] = $template;
                }
            }
            uasort($templates, fn($a, $b) => $a['order'] <=> $b['order'] ?: strnatcasecmp($a['label'], $b['label']));
            self::$templates[$slug] = $templates;
        }
        return self::$templates[$slug];
    }

    public static function template(string $name): ?array
    {
        return $name === '' ? null : (self::templates()[$name] ?? null);
    }

    /** Modelo de uma página (post completo ou item do índice), se ela tiver um que o tema atual conheça. */
    public static function forPost(array $post): ?array
    {
        return ($post['type'] ?? '') === 'page' ? self::template((string) ($post['template'] ?? '')) : null;
    }

    /* ======================================================= Para os templates */

    /** Campos de uma página prontos para o template (completos). */
    public static function fill(array $post): array
    {
        $template = self::forPost($post);
        return $template ? Field::fill($template['fields'], $post['fields'] ?? []) : [];
    }

    /** Páginas publicadas de um modelo (ex.: as marcas), em ordem alfabética, com os campos completos. */
    public static function pagesOf(string $name): array
    {
        $template = self::template($name);
        if (!$template) {
            return [];
        }
        if (!isset(self::$pagesOf[$name])) {
            $pages = [];
            foreach (self::pages() as ['item' => $item]) {
                if ($item['template'] === $name && Posts::isPublic($item) && ($post = Posts::find($item['id']))) {
                    $post['fields'] = Field::fill($template['fields'], $post['fields']);
                    $pages[] = $post;
                }
            }
            self::$pagesOf[$name] = $pages;
        }
        return self::$pagesOf[$name];
    }

    /** Campos de um modelo 'shared' (topo, rodapé, contatos), completos; vazios se a página ainda não existe. */
    public static function shared(string $name): array
    {
        $template = self::template($name);
        if (!$template) {
            return [];
        }
        if (!isset(self::$shared[$name])) {
            $item = self::pageOf($name);
            $post = $item ? Posts::find($item['id']) : null;
            self::$shared[$name] = Field::fill($template['fields'], $post['fields'] ?? []);
        }
        return self::$shared[$name];
    }

    /* ============================================================ Páginas */

    /**
     * Páginas com um modelo do tema atual (fora da lixeira), na ordem dos modelos e depois
     * do título: [['item' => item do índice, 'template' => modelo], ...].
     */
    public static function pages(): array
    {
        $templates = self::templates();
        $order = array_flip(array_keys($templates));
        $pages = [];
        foreach (Posts::items() as $item) {
            $template = $templates[$item['template'] ?? ''] ?? null;
            if ($template && $item['type'] === 'page' && $item['status'] !== 'trash') {
                $pages[] = ['item' => $item, 'template' => $template];
            }
        }
        usort($pages, fn($a, $b) => $order[$a['template']['name']] <=> $order[$b['template']['name']]
            ?: strcmp(normalize_text($a['item']['title']), normalize_text($b['item']['title'])));
        return $pages;
    }

    /** A primeira página de um modelo (fora da lixeira), ou null. Item do índice. */
    public static function pageOf(string $name): ?array
    {
        foreach (Posts::items() as $item) {
            if (($item['template'] ?? '') === $name && $item['type'] === 'page' && $item['status'] !== 'trash') {
                return $item;
            }
        }
        return null;
    }

    /**
     * Cria uma página do modelo, em rascunho (a não ser que $data diga outra coisa, como na
     * instalação). Páginas 'shared' ficam sempre em rascunho e fora do Google.
     */
    public static function create(array $template, string $author, array $data = []): array
    {
        $post = array_merge([
            'type' => 'page',
            'status' => 'draft',
            'title' => $template['label'],
            'slug' => $template['shared'] ? 'site-' . $template['name'] : '',
            'author' => $author,
        ], $data, [
            'template' => $template['name'],
            'fields' => Field::clean($template['fields'], $data['fields'] ?? []),
        ]);
        if ($template['shared']) {
            $post['status'] = 'draft';
            $post['seo_noindex'] = true;
        }
        return Posts::save($post);
    }

    /* ======================================================= Conteúdo inicial */

    /**
     * Arquivos de content/ do tema atual: [['template' => modelo, 'file' => caminho,
     * 'data' => JSON, 'page' => item do índice, se a página já existe], ...].
     * JSON: { "title", "slug", "status", "seo_description", "home" (true = página inicial), "fields" }.
     */
    public static function contentFiles(): array
    {
        $dir = Themes::current()['dir'] . '/content';
        $out = [];
        foreach (self::templates() as $name => $template) {
            $files = $template['repeatable'] ? glob("$dir/$name/*.json") : glob("$dir/$name.json");
            foreach ($files ?: [] as $file) {
                $data = json_decode((string) file_get_contents($file), true);
                if (!is_array($data)) {
                    continue;
                }
                $slug = slugify((string) ($data['slug'] ?? ''));
                $page = $template['repeatable'] ? Posts::findBySlug($slug) : self::pageOf($name);
                $out[] = ['template' => $template, 'file' => $file, 'data' => $data,
                    'page' => $page && ($page['template'] ?? '') === $name ? $page : null];
            }
        }
        return $out;
    }

    /**
     * Cria as páginas que faltam a partir de content/ do tema atual. $update = true também
     * sobrescreve as que já existem (os campos, o título e a descrição). Devolve
     * ['created' => [...], 'updated' => [...], 'skipped' => [...]] com os endereços.
     */
    public static function install(string $author, bool $update = false): array
    {
        $log = ['created' => [], 'updated' => [], 'skipped' => []];
        foreach (self::contentFiles() as ['template' => $template, 'data' => $data, 'page' => $page]) {
            $label = $page ? post_path($page) : '/' . ($data['slug'] ?? $template['name']) . '/';
            if ($page && !$update) {
                $log['skipped'][] = $label;
                continue;
            }
            $values = [
                'title' => (string) ($data['title'] ?? $template['label']),
                'seo_description' => (string) ($data['seo_description'] ?? ''),
                'fields' => is_array($data['fields'] ?? null) ? $data['fields'] : [],
            ];
            if (!$page) {
                $values += ['status' => ($data['status'] ?? 'published') === 'draft' ? 'draft' : 'published'];
                if (!empty($data['slug'])) {
                    $values['slug'] = (string) $data['slug'];
                }
                $post = self::create($template, $author, $values);
            } else {
                $values['fields'] = Field::clean($template['fields'], $values['fields']);
                $post = Posts::save(['id' => $page['id']] + $values);
            }
            if (!empty($data['home']) && !$template['shared'] && $post['status'] === 'published' && (!setting('home_page') || $update)) {
                Settings::save(['home_page' => $post['id']]);
            }
            $log[$page ? 'updated' : 'created'][] = post_path($post);
        }
        return $log;
    }

    /**
     * Grava em content/ do tema atual o que está nas páginas (para versionar no Git ou levar a
     * outro site junto com o tema). Devolve os arquivos gravados.
     */
    public static function export(): array
    {
        $theme = Themes::current();
        if ($theme['builtin']) {
            throw new RuntimeException('O tema padrão não guarda conteúdo. Ative o tema do site antes de exportar.');
        }
        $written = [];
        foreach (self::pages() as ['item' => $item, 'template' => $template]) {
            $post = Posts::find($item['id']);
            if (!$post) {
                continue;
            }
            $name = $template['name'];
            $file = $theme['dir'] . '/content/' . ($template['repeatable'] ? "$name/{$post['slug']}.json" : "$name.json");
            $data = array_filter([
                'title' => $post['title'],
                'slug' => $template['shared'] ? null : $post['slug'],
                'status' => $template['shared'] ? null : ($post['status'] === 'published' ? 'published' : 'draft'),
                'seo_description' => $post['seo_description'],
                'home' => post_path($post) === '/' ? true : null,
            ], fn($v) => $v !== null && $v !== '') + ['fields' => Field::paths($template['fields'], $post['fields'], 'strip_base')];
            if (!is_dir(dirname($file)) && !@mkdir(dirname($file), 0775, true) && !is_dir(dirname($file))) {
                throw new RuntimeException('Não foi possível criar ' . dirname($file) . '.');
            }
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
            $written[] = substr($file, strlen($theme['dir']) + 1);
        }
        return $written;
    }

    /* ============================================================ Interno */

    private static function load(string $name, string $file): ?array
    {
        try {
            $data = (static fn() => require $file)();
        } catch (Throwable $e) {
            // Erro num modelo não derruba o site nem o painel: ele some da lista e o erro vai para o log.
            if (App::$config['debug']) {
                throw $e;
            }
            error_log("Modelo $name: $e");
            return null;
        }
        if (!is_array($data) || !is_array($data['fields'] ?? null)) {
            return null;
        }
        return [
            'name' => $name,
            'label' => is_string($data['label'] ?? null) && $data['label'] !== '' ? $data['label'] : ucfirst(str_replace('-', ' ', $name)),
            'description' => is_string($data['description'] ?? null) ? $data['description'] : '',
            'order' => (int) ($data['order'] ?? 50),
            'repeatable' => !empty($data['repeatable']),
            'shared' => !empty($data['shared']),
            'fields' => $data['fields'],
        ];
    }
}
