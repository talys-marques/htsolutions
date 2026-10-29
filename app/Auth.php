<?php
/**
 * Usuários e login.
 *
 * data/users.json guarda os usuários (senhas com password_hash).
 * Existe exatamente um "admin" (o dono do blog). Os demais são "author":
 * cuidam de todo o conteúdo (posts, páginas, mídia, SEO de categorias e tags),
 * mas não da parte técnica (usuários, configurações, SEO geral,
 * redirecionamentos, backup), que usa requireAdmin().
 *
 * Visitantes do site nunca recebem cookie: a sessão só começa no painel.
 */
final class Auth
{
    public const ROLES = ['admin' => 'Administrador', 'author' => 'Autor'];

    private const COOKIE = 'blog_admin';
    private const SESSION_DAYS = 14;
    private const MAX_ATTEMPTS = 5; // por IP
    private const MAX_USER_ATTEMPTS = 20; // por usuário, somando todos os IPs (ataque distribuído)
    private const LOCKOUT_SECONDS = 900;

    private static ?array $current = null;
    private static bool $resolved = false;

    /* ------------------------------------------------------------ Usuários */

    public static function users(): array
    {
        $users = Storage::read('users.json', []);
        return is_array($users) ? $users : [];
    }

    public static function find(string $username): ?array
    {
        return self::users()[strtolower(trim($username))] ?? null;
    }

    public static function admin(): ?array
    {
        foreach (self::users() as $user) {
            if (($user['role'] ?? '') === 'admin') {
                return $user;
            }
        }
        return null;
    }

    public static function hasAdmin(): bool
    {
        return self::admin() !== null;
    }

    /** Valida os dados de um usuário. Devolve a mensagem de erro ou null. */
    public static function validate(string $username, string $name, string $password, string $confirm, bool $isNew): ?string
    {
        if ($isNew) {
            $error = self::validateUsername($username);
            if ($error) {
                return $error;
            }
        }
        if ($name === '') {
            return 'Informe o nome que aparece nos posts.';
        }
        if ($isNew || $password !== '') {
            if (mb_strlen($password) < 8) {
                return 'A senha precisa ter pelo menos 8 caracteres.';
            }
            if ($password !== $confirm) {
                return 'As senhas não conferem.';
            }
        }
        return null;
    }

    /** Valida um login novo (criação ou troca). Devolve a mensagem de erro ou null. */
    public static function validateUsername(string $username): ?string
    {
        if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,31}$/', $username) || $username === 'new') {
            return 'O usuário deve ter de 3 a 32 caracteres: letras minúsculas, números, ponto, hífen ou sublinhado.';
        }
        if (self::find($username)) {
            return 'Já existe um usuário com esse nome.';
        }
        return null;
    }

    public static function create(string $username, string $name, string $password, string $role = 'author', string $email = ''): array
    {
        return Storage::lock(function () use ($username, $name, $password, $role, $email) {
            $users = self::users();
            if (isset($users[$username])) {
                throw new RuntimeException('Esse usuário já existe.');
            }
            if ($role === 'admin' && self::hasAdmin()) {
                throw new RuntimeException('O blog já tem um administrador.');
            }
            $user = [
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'role' => isset(self::ROLES[$role]) ? $role : 'author',
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date('c'),
            ];
            $users[$username] = $user;
            ksort($users);
            Storage::write('users.json', $users);
            return $user;
        });
    }

    /** Altera name, email e/ou password (texto puro). O papel nunca muda. */
    public static function update(string $username, array $changes): array
    {
        return Storage::lock(function () use ($username, $changes) {
            $users = self::users();
            if (!isset($users[$username])) {
                throw new RuntimeException('Usuário não encontrado.');
            }
            foreach (['name', 'email'] as $field) {
                if (isset($changes[$field])) {
                    $users[$username][$field] = (string) $changes[$field];
                }
            }
            if (isset($changes['password']) && $changes['password'] !== '') {
                $users[$username]['password'] = password_hash((string) $changes['password'], PASSWORD_DEFAULT);
            }
            if (isset($changes['last_login'])) {
                $users[$username]['last_login'] = $changes['last_login'];
            }
            Storage::write('users.json', $users);
            return $users[$username];
        });
    }

    /**
     * Troca o login (valide antes com validateUsername()). Senha e papel não
     * mudam, os posts vão junto e /author/antigo/ redireciona para o novo.
     * Devolve quantos posts mudaram de dono.
     */
    public static function rename(string $from, string $to): int
    {
        return Storage::lock(function () use ($from, $to) {
            $users = self::users();
            if (!isset($users[$from])) {
                throw new RuntimeException('Usuário não encontrado.');
            }
            if (isset($users[$to])) {
                throw new RuntimeException('Já existe um usuário com esse nome.');
            }
            // Posts antes do usuário: se algo falhar no meio, repetir a troca completa o serviço.
            $moved = Posts::reassign($from, $to);
            $users[$to] = array_merge($users[$from], ['username' => $to]);
            unset($users[$from]);
            ksort($users);
            Storage::write('users.json', $users);
            Redirects::add('/author/' . $from . '/', '/author/' . $to . '/');
            return $moved;
        });
    }

    public static function remove(string $username): void
    {
        Storage::lock(function () use ($username) {
            $users = self::users();
            if (($users[$username]['role'] ?? '') === 'admin') {
                throw new RuntimeException('O administrador não pode ser excluído.');
            }
            unset($users[$username]);
            Storage::write('users.json', $users);
        });
    }

    /* -------------------------------------------------------------- Sessão */

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        self::configureSession();
        session_start();
    }

    /** Nas páginas públicas: só lê a sessão se o navegador já tiver o cookie do painel. */
    public static function resumeSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || !isset($_COOKIE[self::COOKIE])) {
            return;
        }
        self::configureSession();
        session_start(['read_and_close' => true]);
    }

    private static function configureSession(): void
    {
        $lifetime = self::SESSION_DAYS * 86400;
        // Sessões ficam em data/sessions para não serem apagadas pela limpeza
        // automática de outros sites do mesmo servidor.
        session_save_path(Storage::path('sessions'));
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.gc_probability', '1'); // algumas hospedagens desligam a limpeza automática
        ini_set('session.gc_divisor', '100');
        ini_set('session.use_strict_mode', '1');
        session_name(self::COOKIE);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => App::$basePath . '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** Usuário logado ou null. */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$current;
        }
        self::$resolved = true;
        $username = $_SESSION['user'] ?? null;
        if (!is_string($username) || $username === '') {
            return self::$current = null;
        }
        $user = self::find($username);
        if (!$user || !hash_equals((string) ($_SESSION['sig'] ?? ''), self::signature($user))) {
            return self::$current = null;
        }
        return self::$current = $user;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    /** Tenta logar. Devolve a mensagem de erro ou null em caso de sucesso. */
    public static function attempt(string $username, string $password): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $account = 'user:' . substr(strtolower(trim($username)), 0, 64);
        if (self::isLockedOut($ip, self::MAX_ATTEMPTS) || self::isLockedOut($account, self::MAX_USER_ATTEMPTS)) {
            return 'Muitas tentativas erradas. Aguarde 15 minutos e tente de novo.';
        }
        $user = self::find($username);
        if (!$user) {
            password_hash($password, PASSWORD_DEFAULT); // mesmo tempo de resposta para usuário inexistente
        }
        if (!$user || !password_verify($password, $user['password'])) {
            self::registerFailure($ip, $account);
            return 'Usuário ou senha incorretos.';
        }
        self::clearFailures($ip, $account);
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $user = self::update($user['username'], ['password' => $password]);
        }
        $user = self::update($user['username'], ['last_login' => date('c')]);
        self::login($user);
        return null;
    }

    public static function login(array $user): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['user'] = $user['username'];
        $_SESSION['sig'] = self::signature($user);
        self::$current = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
        setcookie(self::COOKIE, '', ['expires' => time() - 3600, 'path' => App::$basePath . '/']);
        self::$current = null;
    }

    /** Muda quando a senha muda: trocar a senha desloga as outras sessões. */
    private static function signature(array $user): string
    {
        return hash('sha256', $user['password'] . '|' . $user['role']);
    }

    /* ---------------------------------------------------------- Permissões */

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user) {
            return $user;
        }
        if (wants_json()) {
            json_response(['error' => 'Sua sessão expirou. Entre novamente.'], 401);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            self::startSession();
            $query = $_SERVER['QUERY_STRING'] ?? '';
            $_SESSION['intended'] = App::$path . ($query !== '' ? '?' . $query : '');
        }
        redirect('/admin/login');
    }

    public static function requireAdmin(): array
    {
        $user = self::requireLogin();
        if ($user['role'] !== 'admin') {
            self::forbidden();
        }
        return $user;
    }

    /** Admin e autores editam qualquer post ou página. */
    public static function canEdit(array $post): bool
    {
        return self::user() !== null;
    }

    public static function requireCanEdit(array $post): void
    {
        if (!self::canEdit($post)) {
            self::forbidden();
        }
    }

    public static function forbidden(): never
    {
        if (wants_json()) {
            json_response(['error' => 'Você não tem permissão para isso.'], 403);
        }
        http_response_code(403);
        admin_view('message', ['title' => 'Acesso negado', 'message' => 'Você não tem permissão para acessar esta área.']);
        exit;
    }

    /* ------------------------------------------- Limite de tentativas de login */

    private static function attempts(): array
    {
        $data = Storage::read('cache/login-attempts.json', []);
        $now = time();
        return array_filter(is_array($data) ? $data : [], fn($a) => $now - ($a[1] ?? 0) < self::LOCKOUT_SECONDS);
    }

    /** $key: o IP ou "user:login". */
    private static function isLockedOut(string $key, int $max): bool
    {
        return (self::attempts()[$key][0] ?? 0) >= $max;
    }

    private static function registerFailure(string ...$keys): void
    {
        Storage::lock(function () use ($keys) {
            $data = self::attempts();
            foreach ($keys as $key) {
                $data[$key] = [($data[$key][0] ?? 0) + 1, $data[$key][1] ?? time()];
            }
            Storage::write('cache/login-attempts.json', $data, false);
        });
    }

    private static function clearFailures(string ...$keys): void
    {
        Storage::lock(function () use ($keys) {
            $data = self::attempts();
            foreach ($keys as $key) {
                unset($data[$key]);
            }
            Storage::write('cache/login-attempts.json', $data, false);
        });
    }
}
