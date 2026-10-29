<?php
/**
 * Limpeza de HTML enviado por autores (usuários que não são o admin).
 * O admin pode usar qualquer HTML.
 *
 * Funciona por lista branca: só ficam as tags e atributos listados abaixo.
 * Tags desconhecidas somem mas o texto delas fica; as de DROP somem com
 * todo o conteúdo. Links só com http(s), mailto, tel ou caminho relativo;
 * iframes só do YouTube/Vimeo; em style, só propriedades de formatação.
 */
final class Html
{
    /** Tag => atributos próprios permitidos (além de GLOBAL_ATTRIBUTES). */
    private const TAGS = [
        'p' => [], 'br' => [], 'hr' => [], 'div' => [], 'span' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'del' => [], 'ins' => [],
        'sub' => [], 'sup' => [], 'small' => [], 'mark' => [], 'abbr' => [], 'cite' => [], 'q' => ['cite'],
        'code' => [], 'pre' => [], 'kbd' => [], 'samp' => [], 'blockquote' => ['cite'],
        'ul' => [], 'ol' => ['start', 'reversed', 'type'], 'li' => ['value'], 'dl' => [], 'dt' => [], 'dd' => [],
        'a' => ['href', 'target', 'rel', 'name'],
        'img' => ['src', 'srcset', 'sizes', 'alt', 'width', 'height', 'loading'],
        'figure' => [], 'figcaption' => [], 'picture' => [], 'source' => ['src', 'srcset', 'sizes', 'type', 'media'],
        'video' => ['src', 'poster', 'controls', 'width', 'height', 'preload', 'loop', 'muted', 'playsinline'],
        'audio' => ['src', 'controls', 'preload', 'loop'],
        'iframe' => ['src', 'width', 'height', 'allow', 'allowfullscreen', 'loading', 'frameborder'],
        'table' => [], 'caption' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan', 'scope'], 'td' => ['colspan', 'rowspan'], 'colgroup' => ['span'], 'col' => ['span'],
        'details' => ['open'], 'summary' => [], 'time' => ['datetime'],
        'section' => [], 'article' => [], 'aside' => [], 'header' => [], 'footer' => [],
    ];
    private const GLOBAL_ATTRIBUTES = ['class', 'id', 'title', 'lang', 'dir', 'style'];
    private const URL_ATTRIBUTES = ['href', 'src', 'poster', 'cite'];
    private const URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];
    private const DROP = ['script', 'style', 'template', 'noscript', 'xmp', 'noembed', 'noframes', 'plaintext', 'title',
        'textarea', 'select', 'option', 'input', 'button', 'object', 'embed', 'applet', 'svg', 'math',
        'iframe', 'frame', 'frameset', 'head', 'meta', 'link', 'base'];
    private const IFRAME_HOSTS = ['www.youtube-nocookie.com', 'www.youtube.com', 'youtube.com', 'player.vimeo.com'];
    private const STYLE_PROPERTIES = '/^(text-align|color|background-color|font-(weight|style|size)|text-decoration|vertical-align|float|(max-)?width|height|(margin|padding)(-(top|right|bottom|left))?|border(-[a-z-]+)?|list-style-type)$/';

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        // Um </body> perdido no meio faria a libxml jogar o resto do texto fora.
        $html = (string) preg_replace('#</?(html|head|body)\b[^>]*>#i', '', $html);
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><html><body>' . $html . '</body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }
        self::cleanChildren($body);
        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return $out;
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                continue;
            }
            // Comentários, CDATA e instruções de processamento: o navegador e a
            // libxml nem sempre leem igual, então saem.
            if (!$node instanceof DOMElement) {
                $parent->removeChild($node);
                continue;
            }
            $tag = strtolower($node->nodeName);
            if ($tag === 'iframe' && self::allowedIframe($node->getAttribute('src'))) {
                self::cleanAttributes($node, $tag);
                while ($node->firstChild) {
                    $node->removeChild($node->firstChild);
                }
                continue;
            }
            if (in_array($tag, self::DROP, true)) {
                $parent->removeChild($node);
                continue;
            }
            self::cleanChildren($node);
            if (isset(self::TAGS[$tag])) {
                self::cleanAttributes($node, $tag);
                continue;
            }
            // Tag desconhecida (font, center, o:p do Word...): fica só o conteúdo.
            while ($node->firstChild) {
                $parent->insertBefore($node->firstChild, $node);
            }
            $parent->removeChild($node);
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = array_merge(self::GLOBAL_ATTRIBUTES, self::TAGS[$tag]);
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = $attr->value;
            if ($name === 'style') {
                $value = self::cleanStyle($value);
            }
            $keep = in_array($name, $allowed, true) && match (true) {
                in_array($name, self::URL_ATTRIBUTES, true) => self::safeUrl($value, $tag === 'img' && $name === 'src'),
                $name === 'srcset' => self::safeSrcset($value),
                default => $value !== '' || $name !== 'style',
            };
            if (!$keep) {
                $el->removeAttribute($attr->nodeName);
            } elseif ($value !== $attr->value) {
                $el->setAttribute($attr->nodeName, $value);
            }
        }
        if ($tag === 'a' && $el->hasAttribute('target') && !preg_match('/\bnoopener\b/i', $el->getAttribute('rel'))) {
            $el->setAttribute('rel', trim($el->getAttribute('rel') . ' noopener'));
        }
    }

    /** Relativo, http(s), mailto ou tel. data: só para imagens comuns. */
    private static function safeUrl(string $url, bool $allowDataImage = false): bool
    {
        // O navegador ignora espaços e caracteres de controle ("java\tscript:").
        $url = (string) preg_replace('/[\x00-\x20\x7f]+/', '', $url);
        if (!preg_match('/^([a-z][a-z0-9+.-]*):/i', $url, $m)) {
            return true;
        }
        return in_array(strtolower($m[1]), self::URL_SCHEMES, true)
            || ($allowDataImage && preg_match('#^data:image/(png|jpe?g|gif|webp|avif)[;,]#i', $url));
    }

    private static function safeSrcset(string $srcset): bool
    {
        foreach (explode(',', $srcset) as $candidate) {
            $url = preg_split('/\s+/', trim($candidate))[0];
            if ($url !== '' && !self::safeUrl($url)) {
                return false;
            }
        }
        return true;
    }

    /** Mantém só propriedades de formatação (nada de position, url() etc.). */
    private static function cleanStyle(string $style): string
    {
        $out = [];
        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_map('trim', explode(':', $declaration, 2)) + [1 => ''];
            $property = strtolower($property);
            if ($value !== '' && preg_match(self::STYLE_PROPERTIES, $property)
                && !preg_match('/url\s*\(|expression|image-set|[\\\\<>@"\'`]|\/\*/i', $value)) {
                $out[] = "$property: $value";
            }
        }
        return implode('; ', $out);
    }

    private static function allowedIframe(string $src): bool
    {
        $host = strtolower((string) parse_url($src, PHP_URL_HOST));
        return str_starts_with(strtolower($src), 'https://') && in_array($host, self::IFRAME_HOSTS, true);
    }
}
