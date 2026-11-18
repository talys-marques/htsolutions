<?php
/**
 * Temas: mudam o visual do site e podem acrescentar blocos, páginas e opções,
 * sem mexer no conteúdo. O tema padrão é a pasta theme/. Cada tema instalado
 * é uma pasta em themes/{nome}/ que só traz o que muda; o que faltar vem do padrão.
 *
 *   themes/{nome}/
 *     theme.json          nome, descrição, versão, autor e opções (obrigatório)
 *     functions.php       opcional: return ['routes' => function (Router $router) { ... }];
 *     home.php, post.php, partials/card.php...   substituem os de theme/
 *     slots/{lugar}.php   blocos extras nos lugares marcados com slot() nos templates
 *     assets/style.css    carregado depois do site.css (opcional)
 *     assets/script.js    carregado com defer (opcional)
 *     assets/screenshot.(png|jpg|webp|svg)   miniatura no painel (opcional)
 *
 * O tema escolhido fica em setting('theme') ('default' = theme/); as opções de
 * cada tema, em data/theme-options.json. Guia de quem cria temas: themes/README.md.
 */
final class Themes
{
    /** Tipos de opção aceitos no theme.json (formulário em app/views/admin/theme-options.php). */
    public const OPTION_TYPES = ['text', 'textarea', 'html', 'url', 'image', 'color', 'checkbox', 'select', 'number', 'category', 'datetime'];

    /** Arquivos que podem vir no .zip de um tema. */
    private const EXTENSIONS = ['php', 'json', 'css', 'js', 'mjs', 'map', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg', 'ico',
        'woff', 'woff2', 'ttf', 'otf', 'mp4', 'webm', 'md', 'txt'];

    /** Arquivos de assets/ que a rota /themes/{nome}/assets/... entrega, e o tipo de cada um. */
    private const ASSET_TYPES = [
        'css' => 'text/css; charset=UTF-8', 'js' => 'text/javascript; charset=UTF-8', 'mjs' => 'text/javascript; charset=UTF-8',
        'json' => 'application/json', 'map' => 'application/json',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp',
        'avif' => 'image/avif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
        'mp4' => 'video/mp4', 'webm' => 'video/webm',
    ];

    private const MAX_FILES = 1000;
    private const MAX_BYTES = 50 * 1024 * 1024; // descompactado

    /** 'default' é o tema padrão e 'preview' é endereço do painel (/admin/themes/preview). */
    private const RESERVED = ['default', 'preview'];

    private static ?array $all = null;
    private static array $options = [];
    private static array $configs = [];
    private static ?string $forced = null;

    /* ======================================================= Temas instalados */

    /** Temas disponíveis, o padrão primeiro: [nome da pasta => dados]. Os com theme.json inválido vêm com 'error'. */
    public static function all(): array
    {
        if (self::$all === null) {
            $themes = [];
            foreach (glob(THEMES_DIR . '/*/theme.json') ?: [] as $file) {
                $slug = basename(dirname($file));
                if (self::validSlug($slug)) {
                    $themes[$slug] = self::load($slug, dirname($file));
                }
            }
            uasort($themes, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
            self::$all = ['default' => self::builtin()] + $themes;
        }
        return self::$all;
    }

    /** Um tema que pode ser usado (existe e o theme.json é válido), ou null. */
    public static function get(string $slug): ?array
    {
        $theme = self::all()[$slug] ?? null;
        return $theme && !$theme['error'] ? $theme : null;
    }

    /** Tema escolhido em Painel → Temas: o que os visitantes veem. */
    public static function activeSlug(): string
    {
        $slug = (string) setting('theme', 'default');
        return self::get($slug) ? $slug : 'default';
    }

    /** Tema usado para montar esta página: o escolhido ou, para o admin, o que ele está pré-visualizando. */
    public static function current(): array
    {
        return (self::$forced ? self::get(self::$forced) : null) ?? self::previewing() ?? self::get(self::activeSlug());
    }

    /** Tema que o admin está pré-visualizando no site (Temas → Pré-visualizar), ou null. */
    public static function previewing(): ?array
    {
        $slug = $_SESSION['theme_preview'] ?? null;
        return is_string($slug) && Auth::isAdmin() ? self::get($slug) : null;
    }

    /** Usa um tema (e, se dadas, estas opções) só nesta requisição, sem gravar: prévia ao vivo do painel. */
    public static function force(string $slug, ?array $options = null): void
    {
        self::$forced = $slug;
        if ($options !== null) {
            self::$options[$slug] = $options;
        }
    }

    /* ============================================================ Templates */

    /** Arquivo de um template ('home', 'partials/card'...): o do tema atual ou, se ele não tiver, o de theme/. */
    public static function file(string $template): string
    {
        $file = self::current()['dir'] . '/' . $template . '.php';
        return is_file($file) ? $file : THEME_DIR . '/' . $template . '.php';
    }

    /** O tema (o atual, se não disser qual) traz o próprio template? Ex.: has('home') = tela inicial própria. */
    public static function has(string $template, ?array $theme = null): bool
    {
        $theme ??= self::current();
        return !$theme['builtin'] && is_file($theme['dir'] . '/' . $template . '.php');
    }

    /** Bloco do tema atual para um lugar marcado com slot(), ou null. */
    public static function slotFile(string $name): ?string
    {
        $file = self::current()['dir'] . '/slots/' . $name . '.php';
        return is_file($file) ? $file : null;
    }

    /** O que o functions.php do tema atual devolve (lido uma vez por requisição). */
    public static function config(): array
    {
        $theme = self::current();
        if (!array_key_exists($theme['slug'], self::$configs)) {
            $file = $theme['dir'] . '/functions.php';
            $config = [];
            try {
                $config = !$theme['builtin'] && is_file($file) ? (static fn() => require $file)() : [];
            } catch (Throwable $e) {
                // Erro no tema não derruba o site: fica sem o functions.php e o erro vai para o log.
                if (App::$config['debug']) {
                    throw $e;
                }
                error_log("Tema {$theme['slug']}: $e");
            }
            self::$configs[$theme['slug']] = is_array($config) ? $config : [];
        }
        return self::$configs[$theme['slug']];
    }

    /**
     * Classes que o tema atual acrescenta ao <body> (functions.php → 'body_class', que recebe
     * as variáveis da página e devolve uma lista ou um texto). Assim o CSS do tema pode
     * depender das opções: ex. 'ps-header-dark' quando o cabeçalho escuro foi escolhido.
     */
    public static function bodyClass(array $vars = []): string
    {
        $fn = self::config()['body_class'] ?? null;
        $classes = is_callable($fn) ? $fn($vars) : [];
        $classes = is_array($classes) ? $classes : explode(' ', (string) $classes);
        return implode(' ', array_filter(array_map(fn($c) => preg_replace('/[^A-Za-z0-9_-]/', '', (string) $c), $classes)));
    }

    /** Páginas novas que o tema atual cria (functions.php → 'routes'). Chamado antes da rota genérica /{slug}. */
    public static function routes(Router $router): void
    {
        $routes = self::config()['routes'] ?? null;
        if (is_callable($routes)) {
            $routes($router);
        }
    }

    /* =============================================================== Assets */

    /** URL de um arquivo de assets/ do tema (o atual, se não disser qual), com versão para o cache; '' se não existir. */
    public static function assetUrl(string $file, ?array $theme = null): string
    {
        $theme ??= self::current();
        $path = $theme['dir'] . '/assets/' . $file;
        if ($theme['builtin'] || !is_file($path)) {
            return '';
        }
        $encoded = implode('/', array_map('rawurlencode', explode('/', $file)));
        return url('/themes/' . $theme['slug'] . '/assets/' . $encoded) . '?v=' . filemtime($path);
    }

    /** Miniatura do tema no painel: assets/screenshot.(png|jpg|jpeg|webp|svg), ou ''. O padrão usa public/assets/theme-default.svg. */
    public static function screenshot(array $theme): string
    {
        if ($theme['builtin']) {
            return asset('theme-default.svg');
        }
        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {
            if ($url = self::assetUrl("screenshot.$ext", $theme)) {
                return $url;
            }
        }
        return '';
    }

    /**
     * Rota /themes/{nome}/assets/{arquivo}: os temas ficam fora de public/ (têm PHP),
     * então CSS, JS, imagens e fontes passam por aqui. Com ?v= (assetUrl), o navegador guarda por um ano.
     */
    public static function serveAsset(string $slug, string $path)
    {
        $theme = self::get($slug);
        $parts = explode('/', $path);
        $type = self::ASSET_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        if (!$theme || !$type || array_intersect($parts, ['', '.', '..'])) {
            return false;
        }
        $file = $theme['dir'] . '/assets/' . $path;
        if (!is_file($file)) {
            return false;
        }
        $etag = '"' . dechex((int) filemtime($file)) . '-' . dechex((int) filesize($file)) . '"';
        header('Content-Type: ' . $type);
        header('Cache-Control: public, max-age=' . (isset($_GET['v']) ? '31536000, immutable' : '3600'));
        header('ETag: ' . $etag);
        if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            return true;
        }
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }

    /* ============================================================== Opções */

    /** Opções de um tema (o atual, se não disser qual): o que foi salvo e, no resto, os padrões do theme.json. */
    public static function options(?string $slug = null): array
    {
        $theme = $slug === null ? self::current() : self::get($slug);
        if (!$theme) {
            return [];
        }
        if (!isset(self::$options[$theme['slug']])) {
            $all = Storage::read('theme-options.json', []);
            $saved = is_array($all) && is_array($all[$theme['slug']] ?? null) ? $all[$theme['slug']] : [];
            $values = [];
            foreach ($theme['options'] as $key => $field) {
                $value = self::cast($field, array_key_exists($key, $saved) ? $saved[$key] : $field['default']);
                // "assets/banner.jpg" = arquivo que vem dentro do tema (padrões do theme.json e imagens de temas copiados).
                if (in_array($field['type'], ['image', 'url'], true) && str_starts_with($value, 'assets/')) {
                    $value = self::assetUrl(substr($value, 7), $theme);
                }
                $values[$key] = self::paths($field['type'], $value, 'add_base');
            }
            self::$options[$theme['slug']] = $values;
        }
        return self::$options[$theme['slug']];
    }

    /** Valores enviados pelo formulário de opções (Temas → Personalizar), já limpos. */
    public static function input(array $theme, array $input): array
    {
        $values = [];
        foreach ($theme['options'] as $key => $field) {
            $raw = $input[$key] ?? null;
            $text = is_string($raw) ? trim(str_replace("\r\n", "\n", $raw)) : '';
            $values[$key] = match ($field['type']) {
                'checkbox' => $raw !== null,
                'number' => self::cast($field, (int) $text),
                'textarea', 'html', 'category' => $text,
                'url' => preg_match('#^(https?://|/|mailto:|tel:)[^\s"\'<>]*$#i', $text) ? $text : '',
                'image' => preg_match('#^(https?://|/)[^\s"\'<>]*$#i', $text) ? $text : '',
                'color' => preg_match('/^#[0-9a-f]{6}$/i', $text) ? strtolower($text) : $field['default'],
                'select' => isset($field['choices'][$text]) ? $text : $field['default'],
                'datetime' => preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $text) ? $text : '', // data e hora do site, como 2026-11-30T23:59
                default => str_replace("\n", ' ', $text),
            };
        }
        return $values;
    }

    public static function saveOptions(string $slug, array $values): void
    {
        $theme = self::get($slug) ?? throw new RuntimeException('Tema não encontrado.');
        $clean = [];
        $own = '/themes/' . $slug . '/assets/';
        foreach ($theme['options'] as $key => $field) {
            if (array_key_exists($key, $values)) {
                $value = self::paths($field['type'], $values[$key], 'strip_base'); // em data/, sem a pasta do blog
                // Arquivo do próprio tema volta a ser "assets/...", que continua valendo se o tema for copiado.
                if (is_string($value) && str_starts_with($value, $own)) {
                    $value = 'assets/' . rawurldecode((string) strtok(substr($value, strlen($own)), '?'));
                }
                $clean[$key] = $value;
            }
        }
        self::writeOptions($slug, $clean);
        unset(self::$options[$slug]);
    }

    /** Grava (ou apaga, com null) as opções salvas de um tema em data/theme-options.json. */
    private static function writeOptions(string $slug, ?array $values): void
    {
        Storage::lock(function () use ($slug, $values) {
            $all = Storage::read('theme-options.json', []);
            $all = is_array($all) ? $all : [];
            if ($values === null) {
                if (!isset($all[$slug])) {
                    return;
                }
                unset($all[$slug]);
            } else {
                $all[$slug] = $values;
            }
            Storage::write('theme-options.json', $all);
        });
    }

    /** As opções como estão gravadas (sem a pasta do blog), ou null se o tema nunca foi personalizado. */
    private static function savedOptions(string $slug): ?array
    {
        $all = Storage::read('theme-options.json', []);
        return is_array($all) && is_array($all[$slug] ?? null) ? $all[$slug] : null;
    }

    /* ============================================================ Cópia */

    /**
     * .zip do tema para levar a outro site: a pasta do tema e, em options.json, as opções
     * preenchidas aqui. Imagens das opções que estão em uploads/ vão junto, em assets/media/.
     * No outro site, Temas → Enviar tema instala tudo. Devolve o caminho do .zip (em data/cache/).
     */
    public static function export(string $slug): string
    {
        $theme = self::get($slug) ?? throw new RuntimeException('Tema não encontrado ou com theme.json inválido.');
        if ($theme['builtin']) {
            throw new RuntimeException('O tema padrão faz parte do blog e já existe em todos os sites.');
        }
        $files = [];
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme['dir'], FilesystemIterator::SKIP_DOTS));
        foreach ($items as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($theme['dir']) + 1));
            if ($item->isFile() && $relative !== 'options.json' && !preg_match('#(^|/)\.#', $relative)) {
                $files["$slug/$relative"] = $item->getPathname();
            }
        }
        $contents = [];
        if (($saved = self::savedOptions($slug)) !== null) {
            foreach ($saved as $key => $value) {
                $type = $theme['options'][$key]['type'] ?? '';
                $local = is_string($value) && $type === 'image' && str_starts_with($value, '/') && !str_starts_with($value, '//')
                    ? PUBLIC_DIR . rawurldecode((string) strtok($value, '?')) : '';
                if ($local !== '' && !str_contains($value, '..') && is_file($local)) {
                    $name = 'media/' . preg_replace('/[^A-Za-z0-9._-]+/', '-', basename($local));
                    $files["$slug/assets/$name"] = $local;
                    $saved[$key] = "assets/$name";
                }
            }
            $contents["$slug/options.json"] = json_encode($saved, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $target = Storage::path('cache') . '/theme-' . $slug . '-' . bin2hex(random_bytes(4)) . '.zip';
        Zip::create($target, $files, $contents);
        return $target;
    }

    /* ======================================================== Gerenciamento */

    public static function activate(string $slug): array
    {
        $theme = self::get($slug) ?? throw new RuntimeException('Tema não encontrado ou com theme.json inválido.');
        Settings::save(['theme' => $slug]);
        return $theme;
    }

    public static function delete(string $slug): void
    {
        $theme = self::all()[$slug] ?? null; // temas com erro também podem ser excluídos
        if (!$theme || $theme['builtin']) {
            throw new RuntimeException('Tema não encontrado.');
        }
        if ($slug === self::activeSlug()) {
            throw new RuntimeException('Este é o tema ativo. Ative outro antes de excluir.');
        }
        self::removeDir($theme['dir']);
        if (is_dir($theme['dir'])) {
            throw new RuntimeException("Não foi possível apagar a pasta themes/$slug. Verifique as permissões.");
        }
        self::writeOptions($slug, null);
        self::$all = null;
        unset(self::$options[$slug]);
    }

    /** Instala o .zip enviado pelo painel. Veja installZip(). */
    public static function install(?array $upload): array
    {
        $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Escolha o arquivo .zip do tema.');
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('O arquivo passou do limite de envio do servidor (' . ini_get('upload_max_filesize') . ').');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) $upload['tmp_name'])) {
            throw new RuntimeException('O envio falhou. Tente de novo.');
        }
        if (strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION)) !== 'zip') {
            throw new RuntimeException('Envie o tema num arquivo .zip.');
        }
        return self::installZip((string) $upload['tmp_name']);
    }

    /**
     * Instala (ou atualiza, se já existir) um tema a partir de um .zip. O .zip pode ter
     * os arquivos dentro de uma pasta, cujo nome vira o nome da pasta em themes/, ou
     * soltos na raiz, e aí o nome sai do "name" do theme.json. As opções salvas continuam.
     * Devolve ['theme' => dados do tema, 'updated' => bool].
     */
    public static function installZip(string $file): array
    {
        $zip = new Zip($file);
        // Lixo que o sistema operacional põe nos .zip (e arquivos ocultos, como .htaccess) fica de fora.
        $files = array_filter($zip->files(), fn($name) => !preg_match('#(^|/)(__MACOSX/|\.|thumbs\.db$|desktop\.ini$)#i', $name), ARRAY_FILTER_USE_KEY);
        if (count($files) > self::MAX_FILES) {
            throw new RuntimeException('O tema tem mais de ' . self::MAX_FILES . ' arquivos.');
        }
        if (array_sum($files) > self::MAX_BYTES) {
            throw new RuntimeException('O tema passa de ' . format_bytes(self::MAX_BYTES) . ' descompactado.');
        }

        // theme.json na raiz ou dentro de uma única pasta.
        $prefix = isset($files['theme.json']) ? '' : null;
        if ($prefix === null) {
            $tops = array_unique(array_map(fn($name) => explode('/', $name)[0], array_keys($files)));
            if (count($tops) === 1 && isset($files[reset($tops) . '/theme.json'])) {
                $prefix = reset($tops) . '/';
            }
        }
        if ($prefix === null) {
            throw new RuntimeException('Não encontrei o theme.json. Ele deve ficar na raiz do .zip ou dentro de uma única pasta (a pasta do tema).');
        }
        $json = json_decode($zip->read($prefix . 'theme.json'), true);
        if (!is_array($json) || !is_string($json['name'] ?? null) || trim($json['name']) === '') {
            throw new RuntimeException('O theme.json precisa ser um JSON válido com pelo menos o "name" do tema.');
        }
        $slug = trim(substr(slugify($prefix !== '' ? rtrim($prefix, '/') : $json['name']), 0, 60), '-');
        if (!self::validSlug($slug)) {
            throw new RuntimeException('“' . ($slug ?: $prefix) . '” não pode ser o nome da pasta do tema. Renomeie a pasta antes de compactar.');
        }

        // Confere todos os nomes antes de gravar qualquer coisa.
        $paths = [];
        foreach (array_keys($files) as $name) {
            $relative = (string) substr($name, strlen($prefix));
            if (!preg_match('#^[A-Za-z0-9 ._-]+(/[A-Za-z0-9 ._-]+)*$#', $relative) || array_intersect(explode('/', $relative), ['.', '..'])) {
                throw new RuntimeException("Nome de arquivo não aceito: $name. Use letras sem acento, números, espaço, ponto, - e _.");
            }
            if (!in_array(strtolower(pathinfo($relative, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
                throw new RuntimeException("Tipo de arquivo não aceito num tema: $name. Aceitos: " . implode(', ', self::EXTENSIONS) . '.');
            }
            $paths[$name] = $relative;
        }
        // options.json (veio de "Baixar .zip" em outro site) não fica na pasta: vira as opções do tema aqui.
        $imported = isset($paths[$prefix . 'options.json']) ? json_decode($zip->read($prefix . 'options.json'), true) : null;
        unset($paths[$prefix . 'options.json']);

        if (!is_dir(THEMES_DIR) && !@mkdir(THEMES_DIR, 0775, true) && !is_dir(THEMES_DIR)) {
            throw new RuntimeException('Não foi possível criar a pasta themes/. Verifique as permissões.');
        }
        // Grava numa pasta temporária e só no fim troca pela definitiva: um envio que
        // falha no meio não deixa o tema pela metade nem estraga a versão instalada.
        $dir = THEMES_DIR . '/' . $slug;
        $tmp = THEMES_DIR . '/.tmp-' . bin2hex(random_bytes(4));
        $old = null;
        $updated = is_dir($dir);
        try {
            foreach ($paths as $name => $relative) {
                $target = "$tmp/$relative";
                $parent = dirname($target);
                if ((!is_dir($parent) && !@mkdir($parent, 0775, true) && !is_dir($parent)) || @file_put_contents($target, $zip->read($name)) === false) {
                    throw new RuntimeException("Não foi possível gravar themes/$slug/$relative. Verifique as permissões de themes/.");
                }
            }
            if ($updated) {
                $old = THEMES_DIR . '/.old-' . bin2hex(random_bytes(4));
                if (!@rename($dir, $old)) {
                    $old = null;
                    throw new RuntimeException('Não foi possível substituir a versão instalada. Verifique as permissões de themes/.');
                }
            }
            if (!@rename($tmp, $dir)) {
                if ($old && @rename($old, $dir)) {
                    $old = null; // a versão anterior voltou para o lugar
                }
                throw new RuntimeException('Não foi possível instalar o tema. Verifique as permissões de themes/.');
            }
        } finally {
            self::removeDir($tmp);
            if ($old) {
                self::removeDir($old);
            }
        }

        // Hospedagens com cache de PHP (opcache) poderiam continuar usando os arquivos antigos.
        if (function_exists('opcache_invalidate')) {
            foreach ($paths as $relative) {
                if (str_ends_with($relative, '.php')) {
                    @opcache_invalidate("$dir/$relative", true);
                }
            }
        }
        self::$all = null;
        unset(self::$options[$slug]);
        $theme = self::all()[$slug];

        // Opções que vieram no .zip: valem quando o tema é novo neste site (ou nunca foi personalizado).
        // Numa atualização, as opções já preenchidas aqui continuam.
        $applied = false;
        if (is_array($imported) && !$theme['error'] && self::savedOptions($slug) === null) {
            $values = [];
            foreach ($theme['options'] as $key => $field) {
                if (array_key_exists($key, $imported)) {
                    $values[$key] = self::cast($field, $imported[$key]);
                }
            }
            self::writeOptions($slug, $values);
            $applied = true;
        }
        return ['theme' => $theme, 'updated' => $updated, 'options' => $applied];
    }

    /* =========================================================== Internos */

    private static function builtin(): array
    {
        return [
            'slug' => 'default', 'dir' => THEME_DIR, 'name' => 'Padrão', 'version' => '', 'author' => '',
            'description' => 'O visual que vem com o blog. Os outros temas partem dele e trocam só o que precisam.',
            'options' => [], 'builtin' => true, 'error' => null,
        ];
    }

    private static function load(string $slug, string $dir): array
    {
        $theme = ['slug' => $slug, 'dir' => $dir, 'name' => $slug, 'version' => '', 'author' => '', 'description' => '',
            'options' => [], 'builtin' => false, 'error' => null];
        $json = json_decode((string) @file_get_contents($dir . '/theme.json'), true);
        if (!is_array($json)) {
            return ['error' => 'O theme.json deste tema não é um JSON válido.'] + $theme;
        }
        foreach (['name', 'description', 'version', 'author'] as $key) {
            if (is_scalar($json[$key] ?? null) && trim((string) $json[$key]) !== '') {
                $theme[$key] = trim((string) $json[$key]);
            }
        }
        foreach (is_array($json['options'] ?? null) ? $json['options'] : [] as $key => $option) {
            if (is_string($key) && preg_match('/^[a-z0-9_]+$/', $key) && is_array($option)) {
                $theme['options'][$key] = self::field($key, $option);
            }
        }
        return $theme;
    }

    /** Uma opção do theme.json com todos os campos preenchidos. */
    private static function field(string $key, array $option): array
    {
        $text = fn($value) => is_scalar($value) ? (string) $value : '';
        $type = in_array($option['type'] ?? '', self::OPTION_TYPES, true) ? $option['type'] : 'text';
        $choices = [];
        foreach (is_array($option['choices'] ?? null) ? $option['choices'] : [] as $value => $label) {
            $choices[(string) $value] = $text($label);
        }
        $field = [
            'type' => $type,
            'label' => $text($option['label'] ?? '') ?: $key,
            'hint' => $text($option['hint'] ?? ''),
            'placeholder' => $text($option['placeholder'] ?? ''),
            'group' => $text($option['group'] ?? ''),
            'choices' => $choices,
            'min' => is_numeric($option['min'] ?? null) ? (int) $option['min'] : null,
            'max' => is_numeric($option['max'] ?? null) ? (int) $option['max'] : null,
            'default' => $option['default'] ?? null,
        ];
        $field['default'] = self::cast($field, $field['default'] ?? ($type === 'select' ? (string) array_key_first($choices) : null));
        return $field;
    }

    /** Valor no tipo certo para a opção (salvo por uma versão anterior do tema, por exemplo). */
    private static function cast(array $field, $value)
    {
        return match ($field['type']) {
            'checkbox' => (bool) $value,
            'number' => max($field['min'] ?? PHP_INT_MIN, min($field['max'] ?? PHP_INT_MAX, (int) $value)),
            'select' => isset($field['choices'][(string) $value]) ? (string) $value : (string) array_key_first($field['choices']),
            default => is_scalar($value) ? (string) $value : '',
        };
    }

    /** add_base() ou strip_base() nos endereços e no HTML: em data/ ficam sem a pasta do blog. */
    private static function paths(string $type, $value, callable $fn)
    {
        return match ($type) {
            'url', 'image' => $fn((string) $value),
            'html' => html_base((string) $value, $fn),
            default => $value,
        };
    }

    private static function validSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9-]{0,59}$/', $slug) && !in_array($slug, self::RESERVED, true);
    }

    /** Apaga uma pasta e tudo dentro dela. Só age dentro de themes/. */
    private static function removeDir(string $dir): void
    {
        $root = str_replace('\\', '/', THEMES_DIR) . '/';
        if (!is_dir($dir) || !str_starts_with(str_replace('\\', '/', $dir), $root)) {
            return;
        }
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() && !$item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
