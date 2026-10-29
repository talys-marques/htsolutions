<?php
/**
 * Conteúdo do site institucional da HT Solutions.
 *
 * Cada página do site é uma página do blog (type page) com dois campos a mais:
 *   modelo   nome do arquivo em modelos/ (campos do formulário) e em theme/site/ (template)
 *   campos   os valores, no formato do modelo
 * Assim cada página tem endereço, SEO, sitemap, lixeira e histórico de versões do próprio blog.
 * O topo, o rodapé e os parceiros ficam numa página em rascunho com modelo "geral" (slug site-geral),
 * que o tema lê em todas as páginas.
 */
final class SiteHT
{
    public const SLUG_GERAL = 'site-geral';

    /** Esquema de um modelo: ['titulo' => ..., 'ordem' => n, 'campos' => [...]], ou null. */
    public static function modelo(string $nome): ?array
    {
        $file = __DIR__ . '/modelos/' . $nome . '.php';
        return preg_match('/^[a-z0-9-]+$/', $nome) && is_file($file) ? require $file : null;
    }

    /** Páginas que têm modelo, na ordem dos modelos (o geral por último). */
    public static function paginas(): array
    {
        $paginas = [];
        foreach (Posts::items() as $item) {
            if ($item['type'] !== 'page' || $item['status'] === 'trash') {
                continue;
            }
            $post = Posts::find($item['id']);
            $modelo = $post ? self::modelo((string) ($post['modelo'] ?? '')) : null;
            if ($modelo) {
                $paginas[] = ['post' => $post, 'modelo' => $modelo];
            }
        }
        usort($paginas, fn($a, $b) => ($a['modelo']['ordem'] ?? 99) <=> ($b['modelo']['ordem'] ?? 99));
        return $paginas;
    }

    /**
     * Campos de uma página prontos para o template: o que faltar (campo novo no modelo, página
     * antiga) vem vazio, então o template não precisa testar cada chave. Não limpa de novo.
     */
    public static function completar(string $modelo, $valores): array
    {
        $esquema = self::modelo($modelo);
        return $esquema ? self::preencher($esquema['campos'], $valores) : (array) $valores;
    }

    private static function preencher(array $campos, $valores): array
    {
        $valores = is_array($valores) ? $valores : [];
        $saida = [];
        foreach ($campos as $chave => $def) {
            $v = $valores[$chave] ?? null;
            $saida[$chave] = match ($def['tipo']) {
                'grupo' => self::preencher($def['campos'], $v),
                'lista' => array_map(fn($item) => self::preencher($def['campos'], $item), array_values(array_filter((array) $v, 'is_array'))),
                default => is_string($v) ? $v : '',
            };
        }
        return $saida;
    }

    /* ------------------------------------------------------------ Limpeza */

    /** Deixa só os campos do esquema, cada um no formato do seu tipo. */
    public static function limpar(array $campos, $valores): array
    {
        $valores = is_array($valores) ? $valores : [];
        $saida = [];
        foreach ($campos as $chave => $def) {
            $saida[$chave] = self::limparCampo($def, $valores[$chave] ?? null);
        }
        return $saida;
    }

    private static function limparCampo(array $def, $valor)
    {
        $texto = is_string($valor) ? str_replace("\r\n", "\n", $valor) : '';
        switch ($def['tipo']) {
            case 'grupo':
                return self::limpar($def['campos'], $valor);
            case 'lista':
                $itens = is_array($valor) ? array_values(array_filter($valor, 'is_array')) : [];
                return array_map(fn($item) => self::limpar($def['campos'], $item), array_slice($itens, 0, 60));
            case 'html':
                return trim(Html::clean($texto));
            case 'titulo':
                return trim(strip_tags($texto, '<strong><em><br>'));
            case 'longo':
                return trim(strip_tags($texto));
            case 'imagem':
                $url = strip_base(trim($texto));
                return preg_match('#^/(uploads|wp-content/uploads)/[^"<>]+$#', $url) ? $url : '';
            case 'link':
                $url = trim($texto);
                return preg_match('#^(/|\#|https?://|mailto:|tel:)#i', $url) && !preg_match('/["<>\s]/', $url) ? $url : '';
            case 'selecao':
                return isset($def['opcoes'][$texto]) ? $texto : (string) array_key_first($def['opcoes']);
            case 'post':
                return $texto !== '' && Posts::findBySlug($texto) ? $texto : '';
            default:
                return trim(strip_tags(str_replace("\n", ' ', $texto)));
        }
    }

    /* ------------------------------------------------------------ Painel */

    public static function lista(): void
    {
        Auth::requireLogin();
        admin_view(__DIR__ . '/views/lista.php', [
            'title' => 'Conteúdo do site',
            'active' => 'site',
            'paginas' => self::paginas(),
        ]);
    }

    public static function editar(int $id)
    {
        Auth::requireLogin();
        $post = Posts::find($id);
        $modelo = $post ? self::modelo((string) ($post['modelo'] ?? '')) : null;
        if (!$modelo || $post['status'] === 'trash') {
            return false;
        }
        Auth::requireCanEdit($post);

        if (is_post()) {
            verify_csrf();
            $dados = json_decode(input_raw('dados'), true);
            if (!is_array($dados)) {
                flash('error', 'Os campos não chegaram completos. Nada foi salvo; tente de novo.');
                redirect('/admin/site/' . $id);
            }
            $alteracoes = ['id' => $id, 'campos' => self::limpar($modelo['campos'], $dados)];
            if (empty($modelo['geral'])) {
                $alteracoes['title'] = input('title') !== '' ? input('title') : $post['title'];
                $alteracoes['seo_description'] = mb_substr(input('seo_description'), 0, 300);
            }
            Posts::save($alteracoes);
            flash('success', 'Salvo. As mudanças já estão no site.');
            redirect('/admin/site/' . $id);
        }

        admin_view(__DIR__ . '/views/editar.php', [
            'title' => $modelo['titulo'],
            'active' => 'site',
            'post' => $post,
            'modelo' => $modelo,
            'campos' => self::limpar($modelo['campos'], $post['campos'] ?? []),
            'versoes' => array_slice(Posts::revisions($id), 0, 20),
        ]);
        return true;
    }

    /**
     * Volta os campos (e o título e a descrição) de uma versão anterior. O histórico do blog
     * (Posts::restoreRevision) só conhece os campos comuns de post, não os do modelo.
     */
    public static function restaurar(int $id)
    {
        Auth::requireLogin();
        verify_csrf();
        $post = Posts::find($id);
        $modelo = $post ? self::modelo((string) ($post['modelo'] ?? '')) : null;
        if (!$modelo) {
            return false;
        }
        Auth::requireCanEdit($post);
        $nome = input('versao');
        $versao = preg_match('/^[\w-]+\.json$/', $nome) ? Storage::read("revisions/$id/$nome") : null;
        if (!is_array($versao) || !isset($versao['campos'])) {
            flash('error', 'Versão não encontrada.');
            redirect('/admin/site/' . $id);
        }
        Posts::save([
            'id' => $id,
            'campos' => self::limpar($modelo['campos'], $versao['campos']),
            'title' => (string) ($versao['title'] ?? $post['title']),
            'seo_description' => (string) ($versao['seo_description'] ?? $post['seo_description']),
        ]);
        flash('success', 'Versão restaurada. A versão que estava no ar continua na lista.');
        redirect('/admin/site/' . $id);
    }

    /* ------------------------------------------------------------ Formulário */

    /** HTML de um campo do formulário. $valor já passou por limpar(). */
    public static function campo(string $chave, array $def, $valor): string
    {
        $rotulo = '<span class="sh-rotulo">' . e($def['rotulo']) . '</span>';
        $dica = !empty($def['dica']) ? '<span class="hint">' . e($def['dica']) . '</span>' : '';
        $attrs = 'data-campo="' . e($chave) . '" data-tipo="' . e($def['tipo']) . '"';

        switch ($def['tipo']) {
            case 'grupo':
                $html = '<fieldset class="sh-grupo" ' . $attrs . '><legend>' . e($def['rotulo']) . '</legend>' . $dica;
                foreach ($def['campos'] as $k => $d) {
                    $html .= self::campo($k, $d, $valor[$k] ?? null);
                }
                return $html . '</fieldset>';

            case 'lista':
                $html = '<div class="sh-lista" ' . $attrs . ' data-resumo="' . e($def['resumo']) . '">'
                    . '<div class="sh-lista-topo">' . $rotulo . $dica . '</div><div class="sh-itens">';
                foreach ((array) $valor as $item) {
                    $html .= self::item($def, $item);
                }
                return $html . '</div><template>' . self::item($def, self::limpar($def['campos'], [])) . '</template>'
                    . '<button type="button" class="btn btn-sm" data-sh-adicionar>+ Adicionar ' . e(mb_strtolower($def['item'])) . '</button></div>';

            case 'html':
                return '<div class="field sh-campo" ' . $attrs . '>' . $rotulo . $dica
                    . render_file(APP_DIR . '/views/admin/_editor.php', ['name' => '', 'value' => (string) $valor, 'placeholder' => '', 'compact' => true])
                    . '</div>';

            case 'imagem':
                return '<div class="field sh-campo sh-imagem" ' . $attrs . ' data-image-field>' . $rotulo . $dica
                    . '<div class="image-preview"></div><input type="hidden" value="' . e((string) $valor) . '">'
                    . '<div class="row"><button type="button" class="btn btn-sm" data-image-pick>Escolher imagem</button>'
                    . '<button type="button" class="btn btn-sm btn-ghost" data-image-remove>Remover</button></div></div>';

            case 'longo':
                return '<label class="field sh-campo" ' . $attrs . '>' . $rotulo . $dica
                    . '<textarea rows="3">' . e((string) $valor) . '</textarea></label>';

            case 'selecao':
                $opcoes = '';
                foreach ($def['opcoes'] as $v => $texto) {
                    $opcoes .= '<option value="' . e($v) . '"' . ($v === $valor ? ' selected' : '') . '>' . e($texto) . '</option>';
                }
                return '<label class="field sh-campo" ' . $attrs . '>' . $rotulo . $dica . '<select>' . $opcoes . '</select></label>';

            case 'post':
                $opcoes = '<option value="">(nenhum)</option>';
                foreach (Posts::query(['type' => 'post', 'public' => true]) as $p) {
                    $opcoes .= '<option value="' . e($p['slug']) . '"' . ($p['slug'] === $valor ? ' selected' : '') . '>'
                        . e($p['title']) . ' · ' . e(format_date($p['published_at'])) . '</option>';
                }
                return '<label class="field sh-campo" ' . $attrs . '>' . $rotulo . $dica . '<select>' . $opcoes . '</select></label>';

            default: // texto, titulo, link
                return '<label class="field sh-campo" ' . $attrs . '>' . $rotulo . $dica
                    . '<input value="' . e((string) $valor) . '" autocomplete="off"' . ($def['tipo'] === 'link' ? ' inputmode="url"' : '') . '></label>';
        }
    }

    private static function item(array $def, $valor): string
    {
        $html = '<details class="sh-item"><summary><span class="sh-item-nome">' . e($def['item']) . '</span>'
            . '<span class="sh-item-acoes">'
            . '<button type="button" class="btn btn-sm btn-ghost" data-sh-subir title="Mover para cima" aria-label="Mover para cima">↑</button>'
            . '<button type="button" class="btn btn-sm btn-ghost" data-sh-descer title="Mover para baixo" aria-label="Mover para baixo">↓</button>'
            . '<button type="button" class="btn btn-sm btn-ghost" data-sh-remover>Remover</button>'
            . '</span></summary><div class="sh-item-campos">';
        foreach ($def['campos'] as $k => $d) {
            $html .= self::campo($k, $d, $valor[$k] ?? null);
        }
        return $html . '</div></details>';
    }
}
