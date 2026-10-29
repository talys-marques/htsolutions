<?php
/**
 * Troca a senha de um usuário (não existe "esqueci a senha" por e-mail).
 *
 *   php tools/reset-password.php usuario              gera uma senha aleatória
 *   php tools/reset-password.php usuario nova-senha   usa a senha informada
 *
 * Também libera o login se ele estiver bloqueado por tentativas erradas.
 */
if (PHP_SAPI !== 'cli') {
    exit("Use pela linha de comando.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';

$username = strtolower($argv[1] ?? '');
if ($username === '' || !Auth::find($username)) {
    $users = array_keys(Auth::users());
    fwrite(STDERR, "Uso: php tools/reset-password.php usuario [nova-senha]\n");
    fwrite(STDERR, $users ? 'Usuários: ' . implode(', ', $users) . "\n" : "Nenhum usuário ainda: abra /admin no navegador para criar o administrador.\n");
    exit(1);
}

$password = $argv[2] ?? substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 14);
if (mb_strlen($password) < 8) {
    fwrite(STDERR, "A senha precisa ter pelo menos 8 caracteres.\n");
    exit(1);
}

Auth::update($username, ['password' => $password]);
Storage::delete('cache/login-attempts.json');
echo "Senha de '$username' alterada para: $password\n";
