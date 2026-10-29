<?php
/**
 * /ultimos.json?n=3&categoria=cases
 * n: quantos posts (1 a 12, padrão 3). categoria: slug da categoria (opcional).
 * Endereços completos, para funcionar em qualquer página do domínio.
 */
final class LatestPosts
{
    public static function json(): void
    {
        $limit = max(1, min(12, (int) (get_param('n') ?: 3)));
        $filters = ['type' => 'post', 'public' => true];
        $category = slugify(get_param('categoria'));
        if ($category !== '') {
            $filters['category'] = $category;
        }
        $posts = array_map(fn($p) => [
            'title' => $p['title'],
            'url' => absolute_url(post_url($p)),
            'excerpt' => $p['excerpt'],
            'cover' => $p['cover'] !== '' ? absolute_url($p['cover']) : '',
            'category' => $p['categories'][0] ?? '',
            'date' => format_date($p['published_at']),
        ], array_slice(Posts::query($filters), 0, $limit));

        // O site institucional pode estar em outro endereço durante os testes.
        header('Access-Control-Allow-Origin: *');
        json_response($posts);
    }
}
