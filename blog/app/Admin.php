<?php
/**
 * Telas do painel (/admin).
 * Rotas em app/routes/admin.php, templates em app/views/admin/.
 */
final class Admin
{
    /* ============================================================== Acesso */

    public static function setup(): void
    {
        if (Auth::hasAdmin()) {
            redirect('/admin/login');
        }
        $error = null;
        $old = ['site_title' => '', 'username' => '', 'name' => '', 'email' => ''];
        if (is_post()) {
            verify_csrf();
            $old = ['site_title' => input('site_title'), 'username' => strtolower(input('username')), 'name' => input('name'), 'email' => input('email')];
            $password = (string) ($_POST['password'] ?? '');
            $error = Auth::validate($old['username'], $old['name'], $password, (string) ($_POST['password_confirm'] ?? ''), true);
            if (!$error) {
                $user = Auth::create($old['username'], $old['name'], $password, 'admin', $old['email']);
                Settings::save(['title' => $old['site_title'] ?: 'Meu Blog']);
                Auth::login($user);
                flash('success', 'Tudo pronto! Que tal escrever o primeiro post?');
                redirect('/admin/posts/new');
            }
        }
        admin_view('setup', ['title' => 'Configuração inicial', 'error' => $error, 'old' => $old], 'auth');
    }

    public static function login(): void
    {
        if (!Auth::hasAdmin()) {
            redirect('/admin/setup');
        }
        if (Auth::user()) {
            redirect('/admin');
        }
        $error = null;
        $username = '';
        if (is_post()) {
            verify_csrf();
            $username = strtolower(input('username'));
            $error = Auth::attempt($username, (string) ($_POST['password'] ?? ''));
            if ($error === null) {
                $to = $_SESSION['intended'] ?? '/admin';
                unset($_SESSION['intended']);
                redirect(is_string($to) && str_starts_with($to, '/admin') ? $to : '/admin');
            }
        }
        admin_view('login', ['title' => 'Entrar', 'error' => $error, 'username' => $username], 'auth');
    }

    public static function logout(): void
    {
        verify_csrf();
        Auth::logout();
        redirect('/admin/login');
    }

    /* =============================================================== Posts */

    public static function posts(): void
    {
        Auth::requireLogin();
        $type = get_param('type') === 'page' ? 'page' : 'post';
        $base = ['type' => $type];
        $counts = [];
        foreach (['', 'published', 'scheduled', 'draft', 'trash'] as $status) {
            $counts[$status] = count(Posts::query($status === '' ? $base : $base + ['status' => $status]));
        }
        $status = get_param('status');
        if (!isset($counts[$status])) {
            $status = '';
        }
        $filters = $status === '' ? $base : $base + ['status' => $status];
        $q = get_param('q');
        if ($q !== '') {
            $filters['search'] = $q;
        }
        admin_view('posts', [
            'title' => $type === 'page' ? 'Páginas' : 'Posts',
            'active' => $type === 'page' ? 'pages' : 'posts',
            'type' => $type,
            'status' => $status,
            'q' => $q,
            'counts' => $counts,
            'pagination' => paginate(Posts::query($filters), (int) get_param('page', '1'), 30),
        ]);
    }

    public static function newPost(): void
    {
        $user = Auth::requireLogin();
        $type = get_param('type') === 'page' ? 'page' : 'post';
        $post = Posts::blank($type);
        $post['author'] = $user['username'];
        self::editor($post);
    }

    public static function editPost(int $id): void
    {
        Auth::requireLogin();
        $post = Posts::find($id);
        if (!$post) {
            self::notFound();
            return;
        }
        Auth::requireCanEdit($post);
        self::editor($post);
    }

    public static function savePost(?int $id): void
    {
        $user = Auth::requireLogin();
        verify_csrf();
        $old = $id ? Posts::find($id) : null;
        if ($id && !$old) {
            self::notFound();
            return;
        }
        $type = $old['type'] ?? (input('type') === 'page' ? 'page' : 'post');
        if ($old) {
            Auth::requireCanEdit($old);
        }

        $content = input_raw('content');
        if (!Auth::isAdmin()) {
            $content = Html::clean($content);
        }
        if (trim(strip_tags($content, '<img><iframe><hr><video><audio>')) === '') {
            $content = '';
        }

        $data = [
            'type' => $type,
            'status' => input('status') === 'published' ? 'published' : 'draft',
            'title' => input('title'),
            'slug' => input('slug'),
            'content' => $content,
            'excerpt' => input('excerpt'),
            'cover' => self::cleanUrl(input('cover')),
            'cover_alt' => input('cover_alt'),
            'categories' => $type === 'post' ? clean_list(input('categories')) : [],
            'tags' => $type === 'post' ? clean_list(input('tags')) : [],
            'seo_title' => input('seo_title'),
            'seo_description' => input('seo_description'),
            'seo_keyword' => input('seo_keyword'),
            'seo_noindex' => isset($_POST['seo_noindex']),
            'published_at' => input_to_iso(input('published_at')),
        ];
        if ($id) {
            $data['id'] = $id;
        } else {
            $data['author'] = $user['username'];
        }
        $author = strtolower(input('author'));
        if ($author !== '' && Auth::find($author)) {
            $data['author'] = $author;
        }

        if ($data['title'] === '') {
            self::editor(array_merge($old ?? Posts::blank($type), $data), 'Dê um título antes de salvar.');
            return;
        }

        $post = Posts::save($data);
        $item = Posts::item($post['id']);
        flash('success', match (true) {
            $post['status'] === 'draft' => 'Rascunho salvo.',
            Posts::isScheduled($item) => 'Agendado para ' . format_date($post['published_at'], true) . '.',
            default => $old && $old['status'] === 'published' ? 'Alterações publicadas.' : 'Publicado!',
        });
        redirect('/admin/posts/' . $post['id'] . ($id ? '' : '?created=1'));
    }

    public static function postAction(int $id, string $action): void
    {
        Auth::requireLogin();
        verify_csrf();
        $post = Posts::find($id);
        if (!$post) {
            self::notFound();
            return;
        }
        Auth::requireCanEdit($post);
        $back = '/admin/posts' . ($post['type'] === 'page' ? '?type=page' : '');
        if ($action === 'trash') {
            Posts::trash($id);
            flash('success', '“' . $post['title'] . '” foi para a lixeira.');
        } elseif ($action === 'restore') {
            Posts::restore($id);
            flash('success', '“' . $post['title'] . '” foi restaurado como rascunho.');
        } elseif ($action === 'delete' && $post['status'] === 'trash') {
            Posts::delete($id);
            flash('success', '“' . $post['title'] . '” foi excluído definitivamente.');
            $back .= (str_contains($back, '?') ? '&' : '?') . 'status=trash';
        }
        redirect($back);
    }

    public static function restoreRevision(int $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        $post = Posts::find($id);
        if (!$post) {
            self::notFound();
            return;
        }
        Auth::requireCanEdit($post);
        $restored = Posts::restoreRevision($id, input('revision'), !Auth::isAdmin());
        flash($restored ? 'success' : 'error', $restored ? 'Versão restaurada. A versão anterior continua nas revisões.' : 'Revisão não encontrada.');
        redirect('/admin/posts/' . $id);
    }

    public static function preview(int $id): void
    {
        Auth::requireLogin();
        $post = Posts::find($id);
        if (!$post) {
            self::notFound();
            return;
        }
        Auth::requireCanEdit($post);
        Site::renderPost($post, true);
    }

    private static function editor(array $post, ?string $error = null): void
    {
        $isPage = $post['type'] === 'page';
        admin_view('edit', [
            'title' => $post['id'] ? ($isPage ? 'Editar página' : 'Editar post') : ($isPage ? 'Nova página' : 'Novo post'),
            'active' => $isPage ? 'pages' : 'posts',
            'post' => $post,
            'item' => $post['id'] ? Posts::item($post['id']) : null,
            'error' => $error,
            'categories' => array_slice(array_column(Posts::terms('categories', false), 'name'), 0, 40),
            'tags' => array_slice(array_column(Posts::terms('tags', false), 'name'), 0, 40),
            'revisions' => $post['id'] ? Posts::revisions($post['id']) : [],
            'authors' => Auth::users(),
        ]);
    }

    /* =============================================================== Mídia */

    public static function media(): void
    {
        Auth::requireLogin();
        $page = max(1, (int) get_param('page', '1'));
        $q = get_param('q');
        [$files, $total] = Media::list($page, 48, $q);
        admin_view('media', [
            'title' => 'Mídia',
            'active' => 'media',
            'files' => $files,
            'q' => $q,
            'pagination' => ['page' => $page, 'pages' => max(1, (int) ceil($total / 48)), 'total' => $total],
        ]);
    }

    public static function mediaJson(): void
    {
        Auth::requireLogin();
        [$files, $total] = Media::list(max(1, (int) get_param('page', '1')), 40, get_param('q'), get_param('images') === '1');
        json_response(['files' => $files, 'total' => $total, 'per_page' => 40]);
    }

    public static function upload(): void
    {
        Auth::requireLogin();
        verify_csrf();
        $saved = [];
        $errors = [];
        foreach (normalize_files($_FILES['files'] ?? ($_FILES['file'] ?? null)) as $file) {
            try {
                $saved[] = Media::upload($file);
            } catch (RuntimeException $e) {
                $errors[] = $file['name'] . ': ' . $e->getMessage();
            }
        }
        if (!$saved && !$errors) {
            $errors[] = 'Nenhum arquivo recebido.';
        }
        if (wants_json()) {
            json_response(['files' => $saved, 'error' => $errors ? implode("\n", $errors) : null], $saved ? 200 : 422);
        }
        if ($saved) {
            flash('success', count($saved) === 1 ? 'Arquivo enviado.' : count($saved) . ' arquivos enviados.');
        }
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('/admin/media');
    }

    public static function deleteMedia(): void
    {
        Auth::requireLogin();
        verify_csrf();
        $ok = Media::delete(input('path'));
        flash($ok ? 'success' : 'error', $ok ? 'Arquivo excluído.' : 'Não foi possível excluir o arquivo.');
        redirect('/admin/media');
    }

    /* ============================================================ Usuários */

    public static function users(): void
    {
        Auth::requireAdmin();
        $counts = [];
        foreach (Posts::query(['type' => 'post']) as $item) {
            $counts[$item['author']] = ($counts[$item['author']] ?? 0) + 1;
        }
        admin_view('users', ['title' => 'Usuários', 'active' => 'users', 'users' => Auth::users(), 'counts' => $counts]);
    }

    public static function userForm(?string $username): void
    {
        $me = Auth::requireAdmin();
        $editing = $username !== null ? Auth::find($username) : null;
        if ($username !== null && !$editing) {
            self::notFound();
            return;
        }
        $error = null;
        $values = $editing ?? ['username' => '', 'name' => '', 'email' => '', 'role' => 'author'];
        if (is_post()) {
            verify_csrf();
            $values = [
                'username' => strtolower(input('username')),
                'name' => input('name'),
                'email' => input('email'),
                'role' => $editing['role'] ?? 'author',
            ];
            $renamed = $editing && $values['username'] !== $editing['username'];
            $password = (string) ($_POST['password'] ?? '');
            $error = Auth::validate($values['username'], $values['name'], $password, (string) ($_POST['password_confirm'] ?? ''), !$editing);
            if (!$error && $renamed) {
                $error = Auth::validateUsername($values['username']);
            }
            if (!$error && $values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'E-mail inválido.';
            }
            if (!$error) {
                if ($editing) {
                    $message = 'Usuário atualizado.';
                    if ($renamed) {
                        $moved = Auth::rename($editing['username'], $values['username']);
                        $message .= ' O login agora é "' . $values['username'] . '"' . ($moved ? " e $moved post(s) foram junto." : '.');
                    }
                    $updated = Auth::update($values['username'], ['name' => $values['name'], 'email' => $values['email'], 'password' => $password]);
                    if ($editing['username'] === $me['username']) {
                        Auth::login($updated); // continua logado depois de trocar a própria senha ou o login
                    }
                    flash('success', $message);
                } else {
                    Auth::create($values['username'], $values['name'], $password, 'author', $values['email']);
                    flash('success', 'Autor criado. Passe o usuário e a senha para a pessoa entrar em ' . absolute_url('/admin') . '.');
                }
                redirect('/admin/users');
            }
        }
        admin_view('user-form', [
            'title' => $editing ? 'Editar usuário' : 'Novo autor',
            'active' => 'users',
            'values' => $values,
            'editing' => (bool) $editing,
            'error' => $error,
        ]);
    }

    public static function deleteUser(string $username): void
    {
        $me = Auth::requireAdmin();
        verify_csrf();
        $user = Auth::find($username);
        if (!$user || $user['role'] === 'admin') {
            flash('error', 'Esse usuário não pode ser excluído.');
            redirect('/admin/users');
        }
        $moved = Posts::reassign($user['username'], $me['username']);
        Auth::remove($user['username']);
        flash('success', 'Usuário excluído.' . ($moved ? " $moved post(s) passaram para você." : ''));
        redirect('/admin/users');
    }

    public static function account(): void
    {
        $user = Auth::requireLogin();
        $error = null;
        if (is_post()) {
            verify_csrf();
            $name = input('name');
            $email = input('email');
            $new = (string) ($_POST['password'] ?? '');
            $error = Auth::validate($user['username'], $name, $new, (string) ($_POST['password_confirm'] ?? ''), false);
            if (!$error && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'E-mail inválido.';
            }
            if (!$error && $new !== '' && !password_verify((string) ($_POST['current_password'] ?? ''), $user['password'])) {
                $error = 'A senha atual está incorreta.';
            }
            if (!$error) {
                Auth::login(Auth::update($user['username'], ['name' => $name, 'email' => $email, 'password' => $new]));
                flash('success', $new !== '' ? 'Dados e senha atualizados.' : 'Dados atualizados.');
                redirect('/admin/account');
            }
            $user = array_merge($user, ['name' => $name, 'email' => $email]);
        }
        admin_view('account', ['title' => 'Minha conta', 'active' => 'account', 'me' => $user, 'error' => $error]);
    }

    /* ======================================================= Configurações */

    public static function settings(): void
    {
        Auth::requireAdmin();
        $error = null;
        $values = Settings::all();
        if (is_post()) {
            verify_csrf();
            $values = self::settingsInput();
            if ($values['url'] !== '' && !preg_match('#^https?://[^\s/]+(/[^\s]*)?$#i', $values['url'])) {
                $error = 'O endereço do site deve ser uma URL completa, como https://meublog.com.br';
            } elseif (!in_array($values['timezone'], timezone_identifiers_list(), true)) {
                $error = 'Fuso horário inválido.';
            } elseif (!preg_match('/^#[0-9a-f]{6}$/', $values['accent_color'])) {
                $error = 'Cor inválida. Use o formato #2f5bea.';
            } elseif (array_filter($values['social'], fn($i) => $i['network'] === 'email' && !filter_var($i['user'], FILTER_VALIDATE_EMAIL))) {
                $error = 'O e-mail em Redes sociais não é válido.';
            }
            if (!$error) {
                Settings::save($values);
                flash('success', 'Configurações salvas.');
                redirect('/admin/settings');
            }
        }
        admin_view('settings', [
            'title' => 'Configurações',
            'active' => 'settings',
            'error' => $error,
            's' => $values,
            'menuRows' => Menu::rows((array) $values['menu']),
            'fonts' => Settings::fonts(),
            'networks' => Social::networks(),
            'layouts' => Settings::homeLayouts(),
            'pages' => self::pageChoices(),
            'titlePlaces' => Settings::titlePlaces(),
            'tokens' => Settings::tokens(),
            'tokenChoices' => Settings::tokenChoices((array) $values['tokens']),
            'previewItem' => self::previewItem(),
        ]);
    }

    /**
     * Página inicial, ou um post com ?page=post, com o que está no formulário
     * de Configurações, sem salvar (prévia ao vivo).
     */
    public static function settingsPreview(): void
    {
        Auth::requireAdmin();
        verify_csrf();
        $values = self::settingsInput();
        if (!preg_match('/^#[0-9a-f]{6}$/', $values['accent_color'])) {
            $values['accent_color'] = setting('accent_color'); // cor ainda sendo digitada
        }
        Settings::override(array_replace($values, [
            // Prévia só para olhar: links desligados, sem a barra do painel e sem analytics.
            'head_code' => '<style>a, input, button { pointer-events: none; } .admin-bar { display: none; }</style>',
            'body_code' => '',
        ]));
        $item = get_param('page') === 'post' ? self::previewItem() : null;
        $post = $item ? Posts::find($item['id']) : null;
        if ($post) {
            $_SERVER['REQUEST_URI'] = post_url($post);
            Site::renderPost($post);
            return;
        }
        // A prévia mostra a lista de posts (onde a estrutura escolhida vale), mesmo com página fixa no início.
        $blog = Posts::blogPage();
        $_SERVER['REQUEST_URI'] = $blog ? post_url($blog) : url('/'); // para o menu marcar a página atual
        Site::blog(1, $blog['slug'] ?? '');
    }

    /** Opções do seletor de itens do menu (Configurações → Menu), em JSON. */
    public static function menuItems(): void
    {
        Auth::requireAdmin();
        json_response(Menu::search(get_param('type'), get_param('q')));
    }

    /** Post da prévia de Estilo: o mais recente com capa (mostra o arredondamento das imagens) ou o mais recente. */
    private static function previewItem(): ?array
    {
        $items = Posts::query(['type' => 'post', 'public' => true]);
        return current(array_filter($items, fn($item) => (bool) $item['cover'])) ?: ($items[0] ?? null);
    }

    /** Páginas que podem ser a inicial ou a dos posts (Configurações → Página inicial), em ordem alfabética. */
    private static function pageChoices(): array
    {
        $pages = Posts::query(['type' => 'page']);
        usort($pages, fn($a, $b) => strnatcmp(normalize_text($a['title']), normalize_text($b['title'])));
        return $pages;
    }

    /** Id de uma página que não está na lixeira, ou 0. */
    private static function pageId(string $value): int
    {
        $item = Posts::item((int) $value);
        return $item && $item['type'] === 'page' && $item['status'] !== 'trash' ? $item['id'] : 0;
    }

    /** Valores enviados pelo formulário de Configurações, já limpos (ainda sem validar). */
    private static function settingsInput(): array
    {
        // Página dos posts só existe com página fixa no início; sem ela, os posts já ficam no início.
        $homePage = self::pageId(input('home_page'));
        $blogPage = $homePage ? self::pageId(input('blog_page')) : 0;
        return [
            'title' => input('title') ?: 'Meu Blog',
            'tagline' => input('tagline'),
            // A descrição para o Google fica em Painel → SEO (Admin::seo()).
            'url' => rtrim(input('url'), '/'),
            'language' => input('language') ?: 'pt-BR',
            'timezone' => input('timezone'),
            'posts_per_page' => max(1, min(100, (int) input('posts_per_page'))),
            'accent_color' => self::cleanColor(input('accent_color')),
            'font_text' => array_key_exists(input('font_text'), Settings::fonts()) ? input('font_text') : 'system',
            'font_heading' => array_key_exists(input('font_heading'), Settings::fonts()) ? input('font_heading') : '',
            'browser_accent' => isset($_POST['browser_accent']),
            'tokens' => Settings::tokenChoices((array) ($_POST['tokens'] ?? [])),
            'logo' => self::cleanUrl(input('logo')),
            'title_place' => array_key_exists(input('title_place'), Settings::titlePlaces()) ? input('title_place') : 'both',
            'favicon' => self::cleanUrl(input('favicon')),
            'default_image' => self::cleanUrl(input('default_image')),
            'menu' => Menu::fromInput((array) ($_POST['menu'] ?? [])),
            'social' => Social::fromInput((array) ($_POST['social_network'] ?? []), (array) ($_POST['social_user'] ?? [])),
            'footer_text' => trim(input_raw('footer_text')),
            'head_code' => trim(input_raw('head_code')),
            'body_code' => trim(input_raw('body_code')),
            'show_author' => isset($_POST['show_author']),
            'related_posts' => in_array((int) input('related_posts'), [0, 3, 6], true) ? (int) input('related_posts') : 3,
            'home_page' => $homePage,
            'blog_page' => $blogPage === $homePage ? 0 : $blogPage,
            'home_layout' => array_key_exists(input('home_layout'), Settings::homeLayouts()) ? input('home_layout') : 'classic',
            'home_featured' => isset($_POST['home_featured']),
            'home_columns' => max(2, min(4, (int) input('home_columns'))),
            'home_intro' => isset($_POST['home_intro']),
        ];
    }

    public static function redirects(): void
    {
        Auth::requireAdmin();
        $error = null;
        $text = Redirects::toText();
        if (is_post()) {
            verify_csrf();
            $action = input('action');
            if ($action === 'miss') {
                // Um endereço da lista de não encontrados (404) ganha destino.
                $to = input('to');
                if (!preg_match('#^(/|https?://)\S*$#i', $to)) {
                    $error = 'Informe o destino como /endereco-novo/ ou um endereço completo (https://...).';
                } else {
                    Redirects::add(input('from'), $to);
                    flash('success', input('from') . ' agora leva para ' . $to . '.');
                    redirect('/admin/redirects');
                }
            } elseif ($action === 'forget') {
                Redirects::forgetMisses(input('from') !== '' ? [input('from')] : []);
                flash('success', input('from') !== '' ? 'Endereço tirado da lista.' : 'Lista de não encontrados limpa.');
                redirect('/admin/redirects');
            } else {
                $text = input_raw('redirects');
                [$map, $invalid] = Redirects::parse($text);
                if ($invalid) {
                    $error = 'Linha(s) ' . implode(', ', $invalid) . ' fora do formato. Use: /endereco-antigo /endereco-novo/';
                } else {
                    Redirects::replaceAll($map);
                    flash('success', count($map) . ' redirecionamento(s) salvos.');
                    redirect('/admin/redirects');
                }
            }
        }
        // Sugestão de destino: um post com o mesmo slug do fim do endereço (ex.: /2019/05/meu-post → /meu-post/).
        $misses = [];
        foreach (array_slice(Redirects::misses(), 0, 100, true) as $path => $miss) {
            $item = Posts::findBySlug(slugify(basename($path)));
            $misses[$path] = $miss + ['suggest' => $item && Posts::isPublic($item) ? '/' . $item['slug'] . '/' : ''];
        }
        admin_view('redirects', ['title' => 'Redirecionamentos', 'active' => 'redirects', 'text' => $text, 'error' => $error, 'misses' => $misses]);
    }

    /* ================================================================= SEO */

    public static function seo(): void
    {
        Auth::requireAdmin();
        if (is_post()) {
            verify_csrf();
            Settings::save([
                'seo_home_title' => input('seo_home_title'),
                'description' => input('description'),
                'seo_separator' => in_array(input('seo_separator'), Seo::SEPARATORS, true) ? input('seo_separator') : '–',
                'seo_hide_site' => isset($_POST['seo_hide_site']),
                'seo_index_tags' => isset($_POST['seo_index_tags']),
                'seo_index_authors' => isset($_POST['seo_index_authors']),
                'seo_entity' => input('seo_entity') === 'person' ? 'person' : 'organization',
                'seo_entity_name' => input('seo_entity_name'),
                'seo_entity_logo' => self::cleanUrl(input('seo_entity_logo')),
                'seo_google' => self::verificationCode(input_raw('seo_google')),
                'seo_bing' => self::verificationCode(input_raw('seo_bing')),
                'seo_robots' => trim(input_raw('seo_robots')),
            ]);
            flash('success', 'Configurações de SEO salvas.');
            redirect('/admin/seo');
        }
        $items = Posts::query(['public' => true]);
        admin_view('seo', [
            'title' => 'SEO',
            'active' => 'seo',
            's' => Settings::all(),
            'missing' => count(Redirects::misses()),
            'hidden' => count(array_filter($items, fn($item) => !empty($item['noindex']))),
            'total' => count($items),
        ]);
    }

    public static function seoTerms(): void
    {
        Auth::requireLogin();
        $lists = [];
        foreach (['category' => 'categories', 'tag' => 'tags'] as $kind => $field) {
            foreach (Posts::terms($field, false) as $slug => $term) {
                $lists[$kind][] = $term + ['seo' => Terms::get($kind, (string) $slug)];
            }
        }
        admin_view('seo-terms', ['title' => 'SEO · Categorias e tags', 'active' => 'seo', 'lists' => $lists, 'missing' => count(Redirects::misses())]);
    }

    /** $kind: 'category' ou 'tag'. */
    public static function seoTerm(string $kind, string $slug): void
    {
        Auth::requireLogin();
        $term = Posts::terms($kind === 'tag' ? 'tags' : 'categories', false)[$slug] ?? null;
        if (!$term) {
            self::notFound();
            return;
        }
        if (is_post()) {
            verify_csrf();
            $description = trim(input_raw('description'));
            if (!Auth::isAdmin()) {
                $description = Html::clean($description);
            }
            Terms::save($kind, $slug, [
                'description' => $description,
                'seo_title' => input('seo_title'),
                'seo_description' => input('seo_description'),
                'noindex' => isset($_POST['noindex']),
            ]);
            flash('success', 'SEO de “' . $term['name'] . '” salvo.');
            redirect('/admin/seo/terms');
        }
        $posts = Posts::query(['type' => 'post', 'public' => true, $kind => $slug]);
        admin_view('seo-term', [
            'title' => Terms::KINDS[$kind] . ': ' . $term['name'],
            'active' => 'seo',
            'kind' => $kind,
            'term' => $term,
            'seo' => Terms::get($kind, $slug),
            'auto' => Seo::listDescription('Posts sobre ' . $term['name'], array_slice($posts, 0, 3)),
        ]);
    }

    /** Código de verificação colado sozinho ou dentro da tag <meta> inteira → só o código. */
    private static function verificationCode(string $value): string
    {
        if (preg_match('/content=["\']([^"\']+)/i', $value, $m)) {
            $value = $m[1];
        }
        return (string) preg_replace('/[^\w.\-]/', '', $value);
    }

    /* =============================================== Backup e ferramentas */

    public static function tools(): void
    {
        Auth::requireAdmin();
        $counts = ['post' => 0, 'page' => 0, 'trash' => 0];
        foreach (Posts::items() as $item) {
            $item['status'] === 'trash' ? $counts['trash']++ : $counts[$item['type']]++;
        }
        admin_view('tools', [
            'title' => 'Backup e ferramentas',
            'active' => 'tools',
            'counts' => $counts,
            'checks' => [
                'Versão do PHP' => [PHP_VERSION, true],
                'Pasta de dados' => [DATA_DIR, is_writable(DATA_DIR)],
                'Pasta de uploads' => [Media::dir(), is_dir(Media::dir()) && is_writable(Media::dir())],
                'Redimensionar imagens (GD)' => [extension_loaded('gd') ? 'disponível' : 'indisponível', extension_loaded('gd')],
                'Limite de upload' => [format_bytes(Media::maxBytes()), true],
                'Formato do backup' => [class_exists('ZipArchive') ? '.zip' : '.tar.gz', true],
                'Extensões (pasta extensions/)' => [implode(', ', array_column(Extensions::all(), 'name')) ?: 'nenhuma', true],
            ],
        ]);
    }

    public static function backup(): void
    {
        Auth::requireAdmin();
        verify_csrf();
        @set_time_limit(600);
        Backup::send(Backup::create(isset($_POST['uploads'])));
    }

    /** Arquivo .xml no formato do WordPress (WXR). */
    public static function export(): void
    {
        Auth::requireAdmin();
        verify_csrf();
        @set_time_limit(600);
        Export::send(input('content'));
    }

    public static function reindex(): void
    {
        Auth::requireAdmin();
        verify_csrf();
        $index = Posts::rebuildIndex();
        flash('success', 'Índice reconstruído com ' . count($index['items']) . ' item(ns).');
        redirect('/admin/tools');
    }
    /* =========================================================== Utilidades */

    public static function notFound(): void
    {
        http_response_code(404);
        admin_view('message', ['title' => 'Não encontrado', 'message' => 'O item que você procura não existe (ou foi excluído).']);
    }

    /** Selo colorido de status para as listagens. */
    public static function badge(array $item): string
    {
        [$class, $label] = match (true) {
            $item['status'] === 'trash' => ['trash', 'Lixeira'],
            $item['status'] === 'draft' => ['draft', 'Rascunho'],
            Posts::isScheduled($item) => ['scheduled', 'Agendado'],
            default => ['published', 'Publicado'],
        };
        return '<span class="badge badge-' . $class . '">' . $label . '</span>';
    }

    /** "Página inicial" ou "Página dos posts" quando a página foi escolhida em Configurações → Página inicial. */
    public static function pageRole(array $item): string
    {
        return match ($item['id']) {
            (int) setting('home_page') => 'Página inicial',
            (int) setting('blog_page') => 'Página dos posts',
            default => '',
        };
    }

    /** Aceita só URLs http(s) ou caminhos internos (/uploads/...). */
    private static function cleanUrl(string $value): string
    {
        return preg_match('#^(https?://|/)[^\s"\'<>]*$#i', $value) ? $value : '';
    }

    /** "#2F5BEA", "2f5bea" ou "#25b" → "#2f5bea". O que não for cor volta como veio, para a validação recusar. */
    private static function cleanColor(string $value): string
    {
        $hex = strtolower(ltrim($value, '#'));
        if (preg_match('/^[0-9a-f]{3}$/', $hex)) {
            $hex = preg_replace('/./', '$0$0', $hex);
        }
        return '#' . $hex;
    }
}
