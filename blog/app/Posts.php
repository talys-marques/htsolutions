<?php
/**
 * Posts e páginas.
 *
 *   data/posts/{id}.json   um arquivo por post, com todos os campos
 *   data/index.json        metadados de todos os posts (para listar sem abrir cada arquivo)
 *   data/search.json       texto normalizado usado na busca
 *   data/revisions/{id}/   versões anteriores de cada post
 *
 * index.json e search.json são derivados: se sumirem ou ficarem
 * desatualizados (ex.: você editou um post na mão), Posts::rebuildIndex()
 * refaz tudo a partir de data/posts/.
 *
 * Capa e endereços do conteúdo são gravados sem a pasta do blog e ganham
 * a pasta atual na leitura (veja add_base()): quem usa Posts não precisa saber.
 */
final class Posts
{
    public const TYPES = ['post' => 'Post', 'page' => 'Página'];
    public const STATUSES = ['draft' => 'Rascunho', 'published' => 'Publicado', 'trash' => 'Lixeira'];

    /** Slugs que conflitam com rotas do sistema. */
    private const RESERVED = ['admin', 'page', 'category', 'tag', 'author', 'assets', 'uploads', 'wp-content', 'wp-admin', 'wp-login-php', 'sitemap-xml', 'robots-txt', 'thumbs'];

    private static ?array $index = null;
    private static ?array $search = null;
    private static bool $batch = false;

    /** Resultados de query() nesta requisição. Zerado sempre que o índice muda. */
    private static array $queries = [];

    /** Campos de um post (com valores vazios). */
    public static function blank(string $type = 'post'): array
    {
        return [
            'id' => 0,
            'type' => $type,
            'status' => 'draft',
            'title' => '',
            'slug' => '',
            'content' => '',
            'excerpt' => '',
            'cover' => '',
            'cover_alt' => '', // texto alternativo da capa (Google Imagens e leitores de tela)
            'categories' => [], // a primeira é a principal (aparece nos cards e na trilha do Google)
            'tags' => [],
            'author' => '',
            'published_at' => '',
            'created_at' => '',
            'updated_at' => '',
            'seo_title' => '',
            'seo_description' => '',
            'seo_keyword' => '', // palavra-chave principal, usada só na análise do editor
            'seo_noindex' => false, // true = fora do Google e do sitemap
        ];
    }

    /* ------------------------------------------------------------- Leitura */

    public static function index(): array
    {
        if (self::$index === null) {
            $index = Storage::read('index.json');
            self::$index = is_array($index) ? self::indexPaths($index + ['next_id' => 1, 'items' => []], 'add_base') : self::rebuildIndex();
            self::$queries = [];
        }
        return self::$index;
    }

    /** Metadados de todos os posts, indexados pelo id. */
    public static function items(): array
    {
        return self::index()['items'];
    }

    public static function item(int $id): ?array
    {
        return self::items()[$id] ?? null;
    }

    /** Post completo (com conteúdo). */
    public static function find(int $id): ?array
    {
        $post = Storage::read("posts/$id.json");
        return is_array($post) ? self::paths(array_merge(self::blank(), $post), 'add_base') : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        foreach (self::items() as $item) {
            if ($item['slug'] === $slug && $item['status'] !== 'trash') {
                return $item;
            }
        }
        return null;
    }

    /** Busca pelo id do WordPress (links ?p=123) ou pelo id daqui. */
    public static function findByLegacyId(int $id): ?array
    {
        foreach (self::items() as $item) {
            if (($item['wp_id'] ?? null) === $id) {
                return $item;
            }
        }
        return self::item($id);
    }

    public static function isPublic(array $item): bool
    {
        return $item['status'] === 'published' && $item['ts'] <= time();
    }

    public static function isScheduled(array $item): bool
    {
        return $item['status'] === 'published' && $item['ts'] > time();
    }

    /**
     * Página fixa mostrada no início do blog (Configurações → Página inicial), como no WordPress.
     * null = o início mostra os posts mais recentes (nenhuma escolhida, ou ela não está no ar).
     */
    public static function homePage(): ?array
    {
        return self::settingPage('home_page');
    }

    /** Com página fixa no início, a página onde fica a lista de posts (/blog/, por exemplo). */
    public static function blogPage(): ?array
    {
        $home = self::homePage();
        $blog = $home ? self::settingPage('blog_page') : null;
        return $blog && $blog['id'] !== $home['id'] ? $blog : null;
    }

    /**
     * Lista posts, do mais novo para o mais antigo. Filtros:
     *   type      'post' | 'page'
     *   public    true = só publicados com data já passada
     *   status    'draft' | 'published' | 'scheduled' | 'trash' (sem ele, a lixeira fica de fora)
     *   author    nome de usuário
     *   category  slug da categoria
     *   tag       slug da tag
     *   search    texto livre
     */
    public static function query(array $filters = []): array
    {
        // A página inicial e o sitemap repetem as mesmas consultas várias vezes.
        $key = serialize($filters);
        if (isset(self::$queries[$key])) {
            return self::$queries[$key];
        }
        $status = $filters['status'] ?? null;
        $out = [];
        foreach (self::items() as $item) {
            if (isset($filters['type']) && $item['type'] !== $filters['type']) {
                continue;
            }
            if ($status === 'trash' ? $item['status'] !== 'trash' : $item['status'] === 'trash') {
                continue;
            }
            if ($status === 'draft' && $item['status'] !== 'draft') {
                continue;
            }
            if ($status === 'scheduled' && !self::isScheduled($item)) {
                continue;
            }
            if (($status === 'published' || !empty($filters['public'])) && !self::isPublic($item)) {
                continue;
            }
            if (isset($filters['author']) && $item['author'] !== $filters['author']) {
                continue;
            }
            if (isset($filters['category']) && !self::hasTerm($item['categories'], $filters['category'])) {
                continue;
            }
            if (isset($filters['tag']) && !self::hasTerm($item['tags'], $filters['tag'])) {
                continue;
            }
            $out[] = $item;
        }
        usort($out, fn($a, $b) => [$b['ts'], $b['id']] <=> [$a['ts'], $a['id']]);

        if (isset($filters['search']) && trim($filters['search']) !== '') {
            $out = self::search($out, $filters['search']);
        }
        return self::$queries[$key] = $out;
    }

    /**
     * Posts parecidos para o "Leia também": pontos por categoria em comum (a principal vale mais)
     * e por tag em comum; no empate, o mais recente. Sem nada em comum, vêm os mais recentes.
     * Ficam de fora o próprio post, os ids em $exclude e os escondidos dos buscadores.
     */
    public static function related(array $post, int $limit = 3, array $exclude = []): array
    {
        $slugs = fn(array $names) => array_map([self::class, 'termSlug'], $names);
        $categories = $slugs($post['categories']);
        $tags = $slugs($post['tags']);
        $exclude[] = $post['id'];
        $scored = [];
        foreach (self::query(['type' => 'post', 'public' => true]) as $position => $item) {
            if (in_array($item['id'], $exclude, true) || !empty($item['noindex'])) {
                continue;
            }
            $itemCategories = $slugs($item['categories']);
            $score = 3 * count(array_intersect($categories, $itemCategories))
                + 2 * count(array_intersect($tags, $slugs($item['tags'])))
                + ($categories && ($itemCategories[0] ?? null) === $categories[0] ? 2 : 0);
            $scored[] = [$score, $position, $item];
        }
        usort($scored, fn($a, $b) => $b[0] <=> $a[0] ?: $a[1] <=> $b[1]); // query() já vem do mais novo ao mais antigo
        return array_column(array_slice($scored, 0, max(0, $limit)), 2);
    }

    /** [anterior (mais antigo), próximo (mais novo)] entre os posts publicados. */
    public static function adjacent(int $id): array
    {
        $list = self::query(['type' => 'post', 'public' => true]);
        foreach ($list as $i => $item) {
            if ($item['id'] === $id) {
                return [$list[$i + 1] ?? null, $list[$i - 1] ?? null];
            }
        }
        return [null, null];
    }

    /**
     * Categorias ou tags em uso, das mais usadas para as menos usadas.
     * $field: 'categories' | 'tags'.
     */
    public static function terms(string $field, bool $publicOnly = true): array
    {
        $terms = [];
        foreach (self::query($publicOnly ? ['type' => 'post', 'public' => true] : ['type' => 'post']) as $item) {
            foreach ($item[$field] as $name) {
                $slug = self::termSlug($name);
                $terms[$slug] ??= ['name' => $name, 'slug' => $slug, 'count' => 0];
                $terms[$slug]['count']++;
            }
        }
        uasort($terms, fn($a, $b) => $b['count'] <=> $a['count'] ?: strcmp($a['slug'], $b['slug']));
        return $terms;
    }

    public static function revisions(int $id): array
    {
        $files = glob(Storage::path("revisions/$id") . '/*.json') ?: [];
        rsort($files);
        return array_map(function ($file) {
            $name = basename($file);
            $date = DateTime::createFromFormat('Ymd-His', substr($name, 0, 15));
            return ['name' => $name, 'date' => $date ? $date->format('c') : date('c', (int) filemtime($file))];
        }, $files);
    }

    /* ------------------------------------------------------------- Gravação */

    /**
     * Cria ou atualiza um post. Sem 'id' (ou com um id livre), cria.
     * Só os campos enviados em $data mudam. Devolve o post salvo.
     * $touch = false mantém o updated_at informado (usado na importação).
     */
    public static function save(array $data, bool $touch = true): array
    {
        return Storage::lock(function () use ($data, $touch) {
            if (!self::$batch) {
                self::$index = null;
                self::$search = null;
            }
            self::index();

            $id = (int) ($data['id'] ?? 0);
            $old = $id > 0 ? self::find($id) : null;
            if ($id <= 0) {
                $id = (int) self::$index['next_id'];
            }

            $now = date('c');
            $post = array_merge(self::blank(), $old ?? [], $data);
            $post['id'] = $id;
            if (!isset(self::TYPES[$post['type']])) {
                $post['type'] = 'post';
            }
            if (!isset(self::STATUSES[$post['status']])) {
                $post['status'] = 'draft';
            }
            $post['title'] = trim((string) $post['title']);
            $post['content'] = (string) $post['content'];
            $post['excerpt'] = trim((string) $post['excerpt']);
            $post['categories'] = clean_list($post['categories']);
            $post['tags'] = clean_list($post['tags']);
            $slug = slugify((string) $post['slug']) ?: slugify($post['title']) ?: 'post-' . $id;
            $post['slug'] = self::uniqueSlug($slug, $id);
            if ($post['status'] === 'published' && $post['published_at'] === '') {
                $post['published_at'] = $now;
            }
            if ($post['created_at'] === '') {
                $post['created_at'] = $post['published_at'] ?: $now;
            }
            if ($touch || $post['updated_at'] === '') {
                $post['updated_at'] = $now;
            }

            if ($old) {
                self::storeRevision($old);
            }
            Storage::write("posts/$id.json", self::paths($post, 'strip_base'));

            self::$index['items'][$id] = self::indexItem($post);
            self::$index['next_id'] = max((int) self::$index['next_id'], $id + 1);
            self::$queries = [];
            $search = self::searchData();
            if ($post['status'] === 'trash') {
                unset($search[$id]);
            } else {
                $search[$id] = self::searchText($post);
            }
            self::$search = $search;
            if (!self::$batch) {
                self::flush();
            }

            // Mudou o endereço de algo publicado: o endereço antigo passa a redirecionar.
            if ($old && $old['slug'] !== $post['slug'] && $old['status'] === 'published') {
                Redirects::add('/' . $old['slug'] . '/', '/' . $post['slug'] . '/');
            }
            return $post;
        });
    }

    public static function trash(int $id): void
    {
        self::save(['id' => $id, 'status' => 'trash']);
    }

    public static function restore(int $id): void
    {
        self::save(['id' => $id, 'status' => 'draft']);
    }

    /** Exclui de vez (arquivo, índice e revisões). */
    public static function delete(int $id): void
    {
        Storage::lock(function () use ($id) {
            self::$index = null;
            self::$search = null;
            self::index();
            $search = self::searchData();
            unset(self::$index['items'][$id], $search[$id]);
            self::$queries = [];
            self::$search = $search;
            Storage::delete("posts/$id.json");
            foreach (glob(Storage::path("revisions/$id") . '/*.json') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir(Storage::path("revisions/$id"));
            self::flush();
        });
    }

    /**
     * Volta o conteúdo de uma revisão (status, endereço e data atuais são mantidos).
     * $cleanHtml = true passa o conteúdo por Html::clean() (restauração feita por autor).
     */
    public static function restoreRevision(int $id, string $name, bool $cleanHtml = false): ?array
    {
        if (!preg_match('/^[\w-]+\.json$/', $name)) {
            return null;
        }
        $revision = Storage::read("revisions/$id/$name");
        $current = self::find($id);
        if (!is_array($revision) || !$current) {
            return null;
        }
        $fields = ['title', 'content', 'excerpt', 'cover', 'cover_alt', 'categories', 'tags', 'seo_title', 'seo_description', 'seo_keyword', 'seo_noindex'];
        $data = self::paths(array_intersect_key($revision, array_flip($fields)), 'add_base');
        if ($cleanHtml && isset($data['content'])) {
            $data['content'] = Html::clean((string) $data['content']);
        }
        return self::save(['id' => $id] + $data);
    }

    /** Passa todos os posts de um autor para outro. Devolve quantos mudaram. */
    public static function reassign(string $from, string $to): int
    {
        return self::batch(function () use ($from, $to) {
            $count = 0;
            foreach (self::items() as $item) {
                if ($item['author'] === $from) {
                    self::save(['id' => $item['id'], 'author' => $to], false);
                    $count++;
                }
            }
            return $count;
        });
    }

    /**
     * Executa várias gravações de uma vez, salvando índice e busca só no final.
     * Usado na importação: milhares de posts em segundos.
     */
    public static function batch(callable $fn)
    {
        return Storage::lock(function () use ($fn) {
            self::$index = null;
            self::$search = null;
            self::index();
            self::searchData();
            self::$batch = true;
            try {
                return $fn();
            } finally {
                self::$batch = false;
                self::flush();
            }
        });
    }

    /** Refaz index.json e search.json a partir de data/posts/. Devolve o total. */
    public static function rebuildIndex(): array
    {
        return Storage::lock(function () {
            $previous = Storage::read('index.json');
            $index = ['next_id' => max(1, (int) ($previous['next_id'] ?? 1)), 'items' => []];
            $search = [];
            foreach (glob(Storage::path('posts') . '/*.json') ?: [] as $file) {
                $post = json_decode((string) file_get_contents($file), true);
                if (!is_array($post) || empty($post['id'])) {
                    continue;
                }
                $post = self::paths(array_merge(self::blank(), $post), 'add_base');
                $id = (int) $post['id'];
                $index['items'][$id] = self::indexItem($post);
                $index['next_id'] = max($index['next_id'], $id + 1);
                if ($post['status'] !== 'trash') {
                    $search[$id] = self::searchText($post);
                }
            }
            ksort($index['items']);
            self::$index = $index;
            self::$queries = [];
            self::$search = $search;
            self::flush();
            return $index;
        });
    }

    public static function isFreeId(int $id): bool
    {
        return $id > 0 && !isset(self::items()[$id]) && !is_file(Storage::path("posts/$id.json"));
    }

    /* -------------------------------------------------------------- Interno */

    /** Página escolhida numa configuração ('home_page' ou 'blog_page'), se for uma página no ar. */
    private static function settingPage(string $key): ?array
    {
        $item = self::item((int) setting($key));
        return $item && $item['type'] === 'page' && self::isPublic($item) ? $item : null;
    }

    private static function flush(): void
    {
        Storage::write('index.json', self::indexPaths(self::$index, 'strip_base'), false);
        Storage::write('search.json', self::searchData(), false);
    }

    /** add_base() ou strip_base() nos endereços de um post: em data/ eles ficam sem a pasta do blog. */
    private static function paths(array $post, callable $fn): array
    {
        if (isset($post['content'])) {
            $post['content'] = html_base((string) $post['content'], $fn);
        }
        if (isset($post['cover'])) {
            $post['cover'] = $fn((string) $post['cover']);
        }
        return $post;
    }

    /** O mesmo que paths(), na capa de cada item do índice. */
    private static function indexPaths(array $index, callable $fn): array
    {
        if (App::$basePath !== '') {
            foreach ($index['items'] as $id => $item) {
                $index['items'][$id]['cover'] = $fn((string) ($item['cover'] ?? ''));
            }
        }
        return $index;
    }

    private static function searchData(): array
    {
        if (self::$search === null) {
            $data = Storage::read('search.json', []);
            self::$search = is_array($data) ? $data : [];
        }
        return self::$search;
    }

    private static function indexItem(array $post): array
    {
        $item = [
            'id' => (int) $post['id'],
            'type' => $post['type'],
            'status' => $post['status'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'excerpt' => $post['excerpt'] !== '' ? $post['excerpt'] : excerpt_from($post['content']),
            'cover' => $post['cover'],
            'categories' => $post['categories'],
            'tags' => $post['tags'],
            'author' => $post['author'],
            'published_at' => $post['published_at'],
            'updated_at' => $post['updated_at'],
            'ts' => (int) strtotime($post['published_at'] ?: ($post['updated_at'] ?: 'now')),
            'reading_time' => reading_time($post['content']),
        ];
        if (isset($post['wp_id'])) {
            $item['wp_id'] = (int) $post['wp_id'];
        }
        if (!empty($post['seo_noindex'])) {
            $item['noindex'] = true; // o sitemap deixa de fora
        }
        return $item;
    }

    /** Palavras sem acento, minúsculas e sem pontuação. */
    private static function words(string $text): array
    {
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', normalize_text($text));
        return array_values(array_filter(explode(' ', $text), fn($w) => $w !== ''));
    }

    /** Cada palavra do post uma única vez: o arquivo de busca fica bem menor. */
    private static function searchText(array $post): string
    {
        return implode(' ', array_unique(self::words(implode(' ', [
            $post['title'],
            $post['excerpt'],
            plain_text($post['content']),
            implode(' ', $post['categories']),
            implode(' ', $post['tags']),
        ]))));
    }

    private static function search(array $items, string $query): array
    {
        $terms = array_unique(array_filter(self::words($query), fn($t) => mb_strlen($t) >= 2));
        if (!$terms) {
            return [];
        }
        $texts = self::searchData();
        $scored = [];
        foreach ($items as $item) {
            $title = implode(' ', self::words($item['title']));
            $body = $texts[$item['id']] ?? '';
            $score = 0;
            foreach ($terms as $term) {
                $inTitle = str_contains($title, $term);
                if (!$inTitle && !str_contains($body, $term)) {
                    continue 2; // todos os termos precisam aparecer
                }
                $score += $inTitle ? 10 : 1;
            }
            $scored[] = [$score, $item];
        }
        usort($scored, fn($a, $b) => $b[0] <=> $a[0] ?: $b[1]['ts'] <=> $a[1]['ts']);
        return array_column($scored, 1);
    }

    private static function hasTerm(array $names, string $slug): bool
    {
        foreach ($names as $name) {
            if (self::termSlug($name) === $slug) {
                return true;
            }
        }
        return false;
    }

    /** slugify() com memória: os mesmos nomes de categoria e tag se repetem em todos os posts. */
    private static function termSlug(string $name): string
    {
        static $slugs = [];
        return $slugs[$name] ??= slugify($name);
    }

    private static function uniqueSlug(string $slug, int $id): string
    {
        $taken = array_fill_keys(self::RESERVED, true);
        foreach (self::$index['items'] as $item) {
            if ($item['id'] !== $id && $item['status'] !== 'trash') {
                $taken[$item['slug']] = true;
            }
        }
        $candidate = $slug;
        for ($n = 2; isset($taken[$candidate]); $n++) {
            $candidate = $slug . '-' . $n;
        }
        return $candidate;
    }

    private static function storeRevision(array $post): void
    {
        if (self::$batch) {
            return;
        }
        $dir = 'revisions/' . $post['id'];
        Storage::write($dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.json', self::paths($post, 'strip_base'));
        $files = glob(Storage::path($dir) . '/*.json') ?: [];
        rsort($files);
        foreach (array_slice($files, max(1, (int) config('revisions', 20))) as $file) {
            @unlink($file);
        }
    }
}
