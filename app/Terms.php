<?php
/**
 * Textos de SEO das categorias e tags (Painel → SEO → Categorias e tags).
 *
 * Categorias e tags são só nomes dentro dos posts. O que for escrito para
 * elas fica em data/terms.json, pelo slug:
 *   { "category": { "casa-e-construcao": { description, seo_title, seo_description, noindex } }, "tag": {...} }
 */
final class Terms
{
    public const KINDS = ['category' => 'Categoria', 'tag' => 'Tag'];

    private static ?array $data = null;

    public static function blank(): array
    {
        return [
            'description' => '', // HTML que aparece no topo da página da categoria (de autor passa por Html::clean())
            'seo_title' => '', // vazio = nome da categoria
            'seo_description' => '', // vazio = descrição acima ou uma montada com os títulos dos posts
            'noindex' => false,
        ];
    }

    public static function all(): array
    {
        if (self::$data === null) {
            $data = Storage::read('terms.json', []);
            self::$data = self::paths(is_array($data) ? $data : [], 'add_base');
        }
        return self::$data;
    }

    /** $kind: 'category' ou 'tag'. */
    public static function get(string $kind, string $slug): array
    {
        return array_replace(self::blank(), (array) (self::all()[$kind][$slug] ?? []));
    }

    /** Grava os textos de uma categoria ou tag. Tudo vazio = apaga o registro. */
    public static function save(string $kind, string $slug, array $data): void
    {
        Storage::lock(function () use ($kind, $slug, $data) {
            self::$data = null;
            $all = self::all();
            $data = array_replace(self::blank(), array_intersect_key($data, self::blank()));
            if (array_filter($data)) {
                $all[$kind][$slug] = $data;
            } else {
                unset($all[$kind][$slug]);
            }
            Storage::write('terms.json', self::paths($all, 'strip_base'));
            self::$data = $all;
        });
    }

    /** add_base() ou strip_base() no HTML das descrições: em data/ fica sem a pasta do blog. */
    private static function paths(array $all, callable $fn): array
    {
        foreach ($all as $kind => $terms) {
            foreach ((array) $terms as $slug => $term) {
                if (isset($term['description'])) {
                    $all[$kind][$slug]['description'] = html_base((string) $term['description'], $fn);
                }
            }
        }
        return $all;
    }
}
