<?php
/**
 * Funções dos templates do site (theme/site/*.php). Os campos vêm de $post['campos'],
 * no formato de extensions/site-ht/modelos/{modelo}.php.
 */
if (function_exists('ht_link')) {
    return;
}

/** Link guardado no conteúdo: "/servicos/" ganha a pasta do site; externos e "#contato" ficam. */
function ht_link(string $href): string
{
    return str_starts_with($href, '/') && !str_starts_with($href, '//') ? url($href) : $href;
}

/** target/rel para links que saem do site. */
function ht_alvo(string $href): string
{
    return preg_match('#^https?://#i', $href) ? ' target="_blank" rel="noopener"' : '';
}

/** <img> com versões menores (srcset) para imagens da biblioteca. */
function ht_img(string $src, string $sizes, array $attrs = []): string
{
    return $src === '' ? '' : image_tag(url($src), $sizes, $attrs + ['alt' => '', 'loading' => 'lazy', 'decoding' => 'async']);
}

/** Títulos com <strong> e <br> (campo "titulo"): já limpos ao salvar. */
function ht_titulo(string $html): string
{
    return strip_tags($html, '<strong><em><br>');
}

/** Ícones de linha (Lucide, licença ISC), na cor do texto. */
function ht_icone(string $nome, int $tamanho = 20): string
{
    static $icones = [
        'telefone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'email' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'local' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'relogio' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'seta' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'externo' => '<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'play' => '<path d="m7 4 13 8-13 8Z"/>',
        'busca' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'ok' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'carrinho' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
        'documento' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        'capelo' => '<path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
        'parceria' => '<path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/>',
        'predio' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
    ];
    return '<svg class="icone" width="' . $tamanho . '" height="' . $tamanho . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($icones[$nome] ?? $icones['seta']) . '</svg>';
}

/** Botão com texto e link (campo Campo::botao). Vazio = nada. */
function ht_botao(array $botao, string $classe = 'botao', string $icone = ''): string
{
    if (($botao['texto'] ?? '') === '' || ($botao['link'] ?? '') === '') {
        return '';
    }
    $icone = $icone !== '' ? ' ' . ht_icone($icone, 18) : '';
    return '<a href="' . e(ht_link($botao['link'])) . '" class="' . e($classe) . '"' . ht_alvo($botao['link']) . '>' . e($botao['texto']) . $icone . '</a>';
}

/** Título de seção com o texto pequeno acima (sobretítulo). */
function ht_secao_titulo(string $titulo, string $sobretitulo = '', int $nivel = 2, bool $centro = false): string
{
    return '<div class="titulo' . ($centro ? ' centro' : '') . '">'
        . ($sobretitulo !== '' ? '<p class="sobretitulo">' . e($sobretitulo) . '</p>' : '')
        . "<h$nivel>" . ht_titulo($titulo) . "</h$nivel></div>";
}

/** Conteúdo de "Topo, rodapé e contatos" (página em rascunho site-geral). */
function ht_geral(): array
{
    static $geral = null;
    if ($geral === null) {
        $item = Posts::findBySlug('site-geral');
        $geral = $item ? (array) (Posts::find($item['id'])['campos'] ?? []) : [];
    }
    return $geral;
}

/** "(51) 4020 1585" → "tel:+555140201585"; números 0800 ficam sem o +55. */
function ht_tel(string $telefone): string
{
    $digitos = preg_replace('/\D/', '', $telefone);
    return 'tel:' . (str_starts_with($digitos, '0') ? $digitos : '+55' . $digitos);
}

/** Posts mais recentes (opcionalmente de uma categoria, pelo slug). */
function ht_ultimos(int $quantidade = 3, string $categoria = ''): array
{
    $filtros = ['type' => 'post', 'public' => true] + ($categoria !== '' ? ['category' => $categoria] : []);
    return array_slice(Posts::query($filtros), 0, $quantidade);
}

/** Cartão de post (item do índice ou post completo). */
function ht_cartao(array $post): string
{
    $categoria = $post['categories'][0] ?? '';
    return '<li class="cartao"><a href="' . e(post_url($post)) . '">'
        . ($post['cover'] ? image_tag($post['cover'], '(max-width: 767px) 100vw, 400px', ['alt' => '', 'loading' => 'lazy', 'decoding' => 'async']) : '')
        . '<div class="cartao-corpo">'
        . ($categoria !== '' ? '<span class="cartao-categoria">' . e($categoria) . '</span>' : '')
        . '<h3>' . e($post['title']) . '</h3>'
        . '<p>' . e($post['excerpt'] ?? excerpt_from($post['content'] ?? '')) . '</p>'
        . '<span class="cartao-data">' . e(format_date($post['published_at'])) . '</span>'
        . '</div></a></li>';
}

/** Endereço de vídeo do YouTube → embed sem cookies até dar play. */
function ht_youtube(string $url): string
{
    return preg_match('/(?:youtu\.be\/|v=|embed\/)([\w-]{11})/', $url, $m) ? 'https://www.youtube-nocookie.com/embed/' . $m[1] : '';
}

/** Bloco com os últimos posts e a newsletter (início e Google para Empresas). */
function ht_blog_newsletter(array $posts, array $botao): string
{
    $news = ht_geral()['newsletter'] ?? [];
    ob_start(); ?>
    <div class="blog-h">
      <div class="ultimos">
        <ul class="cartoes"><?php foreach ($posts as $p): ?><?= ht_cartao($p) ?><?php endforeach ?></ul>
        <?= ht_botao($botao, 'botao botao-contorno', 'seta') ?>
      </div>
      <div class="newsletter">
        <h3><?= e($news['titulo'] ?? 'Newsletter') ?></h3>
        <p><?= e($news['texto'] ?? '') ?></p>
        <form action="https://link.htsolutions.com.br/u/register.php" method="get" target="emarsys-newsletter" data-ht-newsletter>
          <input type="hidden" name="CID" value="1090780807">
          <input type="hidden" name="f" value="1404">
          <input type="hidden" name="p" value="2">
          <input type="hidden" name="a" value="r">
          <input type="hidden" name="optin" value="y">
          <label class="sr-only" for="newsletter-email">Seu e-mail</label>
          <input id="newsletter-email" name="inp_3" type="email" placeholder="Seu e-mail" autocomplete="email" required>
          <button type="submit" class="botao">Assinar</button>
        </form>
        <p class="formulario-ok" role="status" hidden><?= ht_icone('ok') ?> Pronto! Você vai receber nossas novidades.</p>
        <iframe name="emarsys-newsletter" title="Cadastro na newsletter" hidden></iframe>
      </div>
    </div>
    <?php return (string) ob_get_clean();
}
