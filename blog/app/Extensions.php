<?php
/**
 * Extensões opcionais: cada uma é uma pasta em extensions/{nome}/.
 * Para tirar um recurso do blog, apague a pasta dele. O núcleo não depende
 * de nenhuma extensão.
 *
 * extensions/{nome}/extension.php devolve um array:
 *
 *   return [
 *       'name'   => 'Nome que aparece no painel',
 *       'routes' => function (Router $router) { $router->get('/admin/x', fn() => MinhaClasse::page()); },
 *       'menu'   => [['key' => 'x', 'url' => '/admin/x', 'label' => 'Rótulo', 'role' => 'admin']],
 *   ];
 *
 * 'routes' e 'menu' são opcionais. 'role' do menu: 'admin' (padrão) ou 'author'.
 * Classes em extensions/{nome}/NomeDaClasse.php são carregadas automaticamente.
 */
final class Extensions
{
    private static ?array $loaded = null;

    /** Extensões instaladas, indexadas pelo nome da pasta. */
    public static function all(): array
    {
        if (self::$loaded === null) {
            self::$loaded = [];
            foreach (glob(EXTENSIONS_DIR . '/*/extension.php') ?: [] as $file) {
                $config = require $file;
                if (is_array($config)) {
                    $folder = basename(dirname($file));
                    self::$loaded[$folder] = $config + ['name' => $folder];
                }
            }
        }
        return self::$loaded;
    }

    public static function routes(Router $router): void
    {
        foreach (self::all() as $extension) {
            if (isset($extension['routes']) && is_callable($extension['routes'])) {
                $extension['routes']($router);
            }
        }
    }

    /** Itens que as extensões adicionam ao menu do painel. */
    public static function menu(): array
    {
        $items = [];
        foreach (self::all() as $extension) {
            foreach ($extension['menu'] ?? [] as $item) {
                $items[] = $item + ['role' => 'admin'];
            }
        }
        return $items;
    }
}
