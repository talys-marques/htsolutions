<?php
/**
 * Tipos de campo das páginas com modelo (Painel → Conteúdo do site). Os modelos listam os
 * campos em fields/{modelo}.php do tema (veja app/SiteContent.php), usando estes métodos:
 *
 *   text      uma linha, sem HTML                   textarea  várias linhas, sem HTML
 *   heading   uma linha com <strong>, <em> e <br>   html      editor visual (passa por Html::clean)
 *   image     imagem da Mídia                       file      qualquer arquivo da Mídia (PDF...)
 *   link      /pagina/, https://..., mailto:, tel:, #ancora
 *   select    uma das opções                        post      um post publicado (guarda o slug)
 *   group     campos juntos                         list      itens repetidos (adicionar, mover, remover)
 *
 * Os valores ficam em $post['fields'], na mesma estrutura dos campos. Formulário:
 * app/views/admin/_field.php e admin.js (seção "Conteúdo do site").
 */
final class Field
{
    /** Itens que uma lista aceita. */
    private const MAX_ITEMS = 100;

    public static function text(string $label, string $hint = ''): array
    {
        return ['type' => 'text', 'label' => $label, 'hint' => $hint];
    }

    public static function textarea(string $label, string $hint = ''): array
    {
        return ['type' => 'textarea', 'label' => $label, 'hint' => $hint];
    }

    /** Título com destaque: no template, imprima sem e() (já foi limpo ao salvar). */
    public static function heading(string $label, string $hint = 'Use <strong>palavra</strong> para destacar e <br> para quebrar a linha.'): array
    {
        return ['type' => 'heading', 'label' => $label, 'hint' => $hint];
    }

    /** Texto com formatação: no template, imprima sem e() (já passou por Html::clean ao salvar). */
    public static function html(string $label, string $hint = ''): array
    {
        return ['type' => 'html', 'label' => $label, 'hint' => $hint];
    }

    public static function image(string $label, string $hint = ''): array
    {
        return ['type' => 'image', 'label' => $label, 'hint' => $hint];
    }

    public static function file(string $label, string $hint = ''): array
    {
        return ['type' => 'file', 'label' => $label, 'hint' => $hint];
    }

    public static function link(string $label, string $hint = 'Página do site (ex.: /contato/) ou endereço completo (https://...).'): array
    {
        return ['type' => 'link', 'label' => $label, 'hint' => $hint];
    }

    /** $choices: valor => texto. Sem escolha válida, vale a primeira. */
    public static function select(string $label, array $choices, string $hint = ''): array
    {
        return ['type' => 'select', 'label' => $label, 'choices' => $choices, 'hint' => $hint];
    }

    public static function post(string $label, string $hint = ''): array
    {
        return ['type' => 'post', 'label' => $label, 'hint' => $hint];
    }

    public static function group(string $label, array $fields, string $hint = ''): array
    {
        return ['type' => 'group', 'label' => $label, 'fields' => $fields, 'hint' => $hint];
    }

    /** $item: nome de cada item ("Pergunta"); $summary: campo cujo valor aparece no item fechado. */
    public static function list(string $label, string $item, array $fields, string $summary = '', string $hint = ''): array
    {
        return ['type' => 'list', 'label' => $label, 'item' => $item, 'fields' => $fields, 'summary' => $summary, 'hint' => $hint];
    }

    /** Botão com texto e link: ['text' => ..., 'link' => ...]. */
    public static function button(string $label = 'Botão'): array
    {
        return self::group($label, ['text' => self::text('Texto do botão'), 'link' => self::link('Link')]);
    }

    /* ============================================================ Valores */

    /** Deixa só os campos do modelo, cada valor no formato do seu tipo. Use antes de gravar. */
    public static function clean(array $fields, $values): array
    {
        $values = is_array($values) ? $values : [];
        $out = [];
        foreach ($fields as $key => $field) {
            $out[$key] = self::cleanValue($field, $values[$key] ?? null);
        }
        return $out;
    }

    /**
     * Valores prontos para o template: o que faltar (campo novo no modelo, página antiga) vem
     * vazio, então o template não precisa testar cada chave. Não limpa de novo.
     */
    public static function fill(array $fields, $values): array
    {
        $values = is_array($values) ? $values : [];
        $out = [];
        foreach ($fields as $key => $field) {
            $value = $values[$key] ?? null;
            $out[$key] = match ($field['type'] ?? 'text') {
                'group' => self::fill($field['fields'], $value),
                'list' => array_map(fn($item) => self::fill($field['fields'], $item), array_values(array_filter((array) $value, 'is_array'))),
                'select' => is_scalar($value) && isset($field['choices'][(string) $value]) ? (string) $value : (string) array_key_first($field['choices']),
                default => is_string($value) ? $value : '',
            };
        }
        return $out;
    }

    /** add_base() ou strip_base() nas imagens, arquivos, links e HTML (usado por Posts::paths()). */
    public static function paths(array $fields, $values, callable $fn)
    {
        if (!is_array($values)) {
            return $values;
        }
        foreach ($fields as $key => $field) {
            $value = $values[$key] ?? null;
            if ($value === null) {
                continue;
            }
            switch ($field['type'] ?? 'text') {
                case 'group':
                    $values[$key] = self::paths($field['fields'], $value, $fn);
                    break;
                case 'list':
                    foreach (is_array($value) ? $value : [] as $i => $item) {
                        $values[$key][$i] = self::paths($field['fields'], $item, $fn);
                    }
                    break;
                case 'image':
                case 'file':
                case 'link':
                    $values[$key] = is_string($value) ? $fn($value) : $value;
                    break;
                case 'html':
                    $values[$key] = is_string($value) ? html_base($value, $fn) : $value;
                    break;
            }
        }
        return $values;
    }

    /** Texto puro dos campos de texto (busca e resumo da página). */
    public static function plainText(array $fields, $values): string
    {
        $parts = [];
        foreach ($fields as $key => $field) {
            $value = is_array($values) ? ($values[$key] ?? null) : null;
            switch ($field['type'] ?? 'text') {
                case 'group':
                    $parts[] = self::plainText($field['fields'], $value);
                    break;
                case 'list':
                    foreach (is_array($value) ? $value : [] as $item) {
                        $parts[] = self::plainText($field['fields'], $item);
                    }
                    break;
                case 'text':
                case 'textarea':
                case 'heading':
                case 'html':
                    $parts[] = is_string($value) ? plain_text($value) : '';
                    break;
            }
        }
        return implode(' ', array_filter($parts, fn($p) => $p !== ''));
    }

    private static function cleanValue(array $field, $value)
    {
        $text = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
        switch ($field['type'] ?? 'text') {
            case 'group':
                return self::clean($field['fields'], $value);
            case 'list':
                $items = is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
                return array_map(fn($item) => self::clean($field['fields'], $item), array_slice($items, 0, self::MAX_ITEMS));
            case 'html':
                $html = trim(Html::clean($text));
                // Só parágrafos vazios (o editor deixa um <p><br></p>) = vazio, e o template pode testar com === ''.
                return trim(strip_tags($html, '<img><iframe><hr><video><audio>')) === '' ? '' : $html;
            case 'heading':
                // Só as três tags, e sem atributos (onclick, style...).
                return trim((string) preg_replace('#<(/?)(strong|em|br)\b[^>]*>#i', '<$1$2>', strip_tags(str_replace("\n", ' ', $text), '<strong><em><br>')));
            case 'textarea':
                return trim(strip_tags($text));
            case 'image':
            case 'file':
                // Arquivos da Mídia (ou importados do WordPress). Gravado sem a pasta do blog (Posts::paths).
                $path = strip_base($text);
                $ext = strtolower(pathinfo((string) strtok($path, '?#'), PATHINFO_EXTENSION));
                $ok = preg_match('#^/(uploads|wp-content/uploads)/[^"\'<>\s]+$#', $path) && !str_contains($path, '..')
                    && ($field['type'] === 'file' || in_array($ext, [...Media::IMAGES, 'svg'], true));
                return $ok ? add_base($path) : '';
            case 'link':
                $ok = preg_match('#^(/|\#|https?://|mailto:|tel:)#i', $text) && !preg_match('/["\'<>\s]/', $text);
                return $ok ? add_base(strip_base($text)) : '';
            case 'select':
                return isset($field['choices'][$text]) ? $text : (string) array_key_first($field['choices']);
            case 'post':
                return $text !== '' && Posts::findBySlug($text) ? $text : '';
            default: // text
                return trim(strip_tags(str_replace("\n", ' ', $text)));
        }
    }
}
