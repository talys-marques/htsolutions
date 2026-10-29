<?php
/**
 * Configurações do blog editáveis no painel, salvas em data/settings.json.
 *
 * Para criar uma configuração nova: adicione o valor padrão em defaults(),
 * o campo em app/views/admin/settings.php e a leitura em Admin::settings().
 */
final class Settings
{
    private static ?array $cache = null;

    public static function defaults(): array
    {
        return [
            'title' => 'Meu Blog',
            'tagline' => '',
            'description' => '',
            'url' => '',
            'language' => 'pt-BR',
            'timezone' => 'America/Sao_Paulo',
            'posts_per_page' => 10,
            'accent_color' => '#2f5bea',
            'font_text' => 'system',
            'font_heading' => '', // vazio = igual ao texto
            'browser_accent' => false, // cor de destaque na barra do navegador do celular
            'tokens' => [], // opção escolhida de cada item de tokens(), ex.: { "radius": "square" }; faltando = padrão
            'logo' => '',
            'title_place' => 'both', // onde aparece o nome do blog, uma das titlePlaces()
            'favicon' => '', // vazio = public/assets/favicon.svg
            'default_image' => '',
            'menu' => [['type' => 'home', 'label' => '', 'children' => []]], // veja Menu
            'social' => [], // [{ network, user }], veja Social::networks()
            'footer_text' => '',
            'head_code' => '',
            'body_code' => '',
            'show_author' => true,
            'related_posts' => 3, // "Leia também" no fim dos posts: quantos (0 = não mostrar)
            'home_page' => 0, // id da página fixa mostrada no início; 0 = os posts mais recentes (veja Posts::homePage())
            'blog_page' => 0, // com página fixa no início, id da página onde fica a lista de posts; 0 = nenhuma
            'home_layout' => 'classic', // estrutura da lista de posts (no início ou na página dos posts), uma das homeLayouts()
            'home_featured' => true, // post mais recente em destaque
            'home_columns' => 3, // colunas das estruturas em grade (2 a 4)
            'home_intro' => true, // frase de apresentação no topo da página inicial
            // Painel → SEO (veja app/Seo.php)
            'seo_home_title' => '', // título da página inicial no Google; vazio = nome do blog + frase de apresentação
            'seo_separator' => '–', // entre o título da página e o nome do blog, um de Seo::SEPARATORS
            'seo_hide_site' => false, // pede aos buscadores para não indexar nada (site em testes)
            'seo_index_tags' => true, // páginas de tag no Google e no sitemap
            'seo_index_authors' => true, // páginas de autor no Google e no sitemap
            'seo_entity' => 'organization', // quem publica o site: organization | person
            'seo_entity_name' => '', // vazio = nome do blog
            'seo_entity_logo' => '', // vazio = logo do blog
            'seo_google' => '', // código de verificação do Google Search Console
            'seo_bing' => '', // código de verificação do Bing Webmaster Tools
            'seo_robots' => '', // linhas extras do robots.txt
        ];
    }

    /**
     * Onde o nome do blog aparece: no cabeçalho (ao lado do logo), no topo da
     * página inicial, nos dois ou em nenhum. Sem logo, o cabeçalho mostra o nome sempre.
     */
    public static function titlePlaces(): array
    {
        return [
            'header' => 'No cabeçalho, ao lado do logo',
            'home' => 'No topo da página inicial',
            'both' => 'Nos dois lugares',
            'none' => 'Só no logo (o nome já está escrito nele)',
        ];
    }

    /**
     * Estruturas da página inicial. 'options' diz quais ajustes valem para cada
     * uma (o painel esconde os outros). Para criar uma nova: inclua aqui, monte
     * em theme/home.php e desenhe a miniatura em app/views/admin/settings.php.
     */
    public static function homeLayouts(): array
    {
        return [
            'classic' => ['label' => 'Clássica', 'options' => ['featured'], 'description' => 'Um post por linha, com a imagem ao lado do texto.'],
            'grid' => ['label' => 'Grade', 'options' => ['featured', 'columns'], 'description' => 'Posts lado a lado em colunas, com a imagem em cima.'],
            'magazine' => ['label' => 'Revista', 'options' => ['columns'], 'description' => 'O post mais recente em destaque, os dois seguintes ao lado e o restante em grade.'],
            'compact' => ['label' => 'Compacta', 'options' => ['featured'], 'description' => 'Só a data e o título, separados por ano. Boa para blogs com muitos textos curtos.'],
            'categories' => ['label' => 'Por categoria', 'options' => ['featured', 'columns'], 'description' => 'Uma seção para cada categoria (as 6 com mais posts), com os posts mais recentes de cada uma.'],
        ];
    }

    /**
     * Fontes que o dono pode escolher. Só fontes que já vêm instaladas nos
     * aparelhos (nada é baixado), por isso cada opção é uma lista de alternativas.
     */
    public static function fonts(): array
    {
        return [
            'system' => ['label' => 'Padrão do sistema', 'stack' => 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'],
            'neutral' => ['label' => 'Neutra (Inter, Helvetica, Arial)', 'stack' => 'Inter, Roboto, "Helvetica Neue", "Arial Nova", "Nimbus Sans", Arial, sans-serif'],
            'humanist' => ['label' => 'Humanista (Calibri, Seravek, Ubuntu)', 'stack' => 'Seravek, "Gill Sans Nova", Ubuntu, Calibri, "DejaVu Sans", source-sans-pro, sans-serif'],
            'geometric' => ['label' => 'Geométrica (Avenir, Corbel, Montserrat)', 'stack' => 'Avenir, Montserrat, Corbel, "URW Gothic", source-sans-pro, sans-serif'],
            'rounded' => ['label' => 'Arredondada', 'stack' => 'ui-rounded, "Hiragino Maru Gothic ProN", Quicksand, Comfortaa, Manjari, "Arial Rounded MT", "Arial Rounded MT Bold", Calibri, source-sans-pro, sans-serif'],
            'condensed' => ['label' => 'Condensada (Bahnschrift, DIN)', 'stack' => 'Bahnschrift, "DIN Alternate", "Franklin Gothic Medium", "Nimbus Sans Narrow", sans-serif-condensed, sans-serif'],
            'serif' => ['label' => 'Serifada moderna (Charter, Cambria)', 'stack' => 'Charter, "Bitstream Charter", "Sitka Text", Cambria, serif'],
            'oldstyle' => ['label' => 'Serifada clássica (Palatino, Iowan)', 'stack' => '"Iowan Old Style", "Palatino Linotype", "URW Palladio L", P052, serif'],
            'bookman' => ['label' => 'Serifada robusta (Bookman, Georgia)', 'stack' => 'Superclarendon, "Bookman Old Style", "URW Bookman", "URW Bookman L", "Georgia Pro", Georgia, serif'],
            'slab' => ['label' => 'Serifa quadrada (Rockwell)', 'stack' => 'Rockwell, "Rockwell Nova", "Roboto Slab", "DejaVu Serif", "Sitka Small", serif'],
            'didone' => ['label' => 'Alto contraste (Didot, Bodoni)', 'stack' => 'Didot, "Bodoni MT", "Noto Serif Display", "URW Palladio L", P052, Sylfaen, serif'],
        ];
    }

    /** Lista de fontes CSS de uma opção de fonts(). Opção desconhecida = padrão. */
    public static function fontStack(string $key): string
    {
        $fonts = self::fonts();
        return ($fonts[$key] ?? $fonts['system'])['stack'];
    }

    /**
     * Tokens de estilo (Configurações → Estilo). Cada opção é um conjunto de
     * variáveis CSS que public/assets/site.css usa; o padrão de cada item
     * reproduz os valores do próprio site.css. Para criar um item: inclua aqui,
     * use as variáveis em site.css e desenhe a miniatura em admin.css
     * (.swatch[data-swatch=chave]).
     */
    public static function tokens(): array
    {
        return [
            'radius' => [
                'label' => 'Cantos',
                'hint' => 'Imagens, cards, botões, etiquetas e o campo de busca.',
                'default' => 'rounded',
                'options' => [
                    'square' => ['label' => 'Retos', 'css' => ['--radius' => '0px', '--radius-sm' => '0px', '--radius-pill' => '0px']],
                    'soft' => ['label' => 'Suaves', 'css' => ['--radius' => '6px', '--radius-sm' => '4px', '--radius-pill' => '4px']],
                    'rounded' => ['label' => 'Médios', 'css' => ['--radius' => '14px', '--radius-sm' => '8px', '--radius-pill' => '999px']],
                    'round' => ['label' => 'Redondos', 'css' => ['--radius' => '26px', '--radius-sm' => '12px', '--radius-pill' => '999px']],
                ],
            ],
            'spacing' => [
                'label' => 'Espaçamento',
                'hint' => 'Espaço entre os posts, as seções e as partes da página.',
                'default' => 'normal',
                'options' => [
                    'compact' => ['label' => 'Compacto', 'css' => ['--space' => '.7']],
                    'normal' => ['label' => 'Normal', 'css' => ['--space' => '1']],
                    'airy' => ['label' => 'Amplo', 'css' => ['--space' => '1.35']],
                ],
            ],
            'text_size' => [
                'label' => 'Tamanho do texto',
                'hint' => 'Títulos, espaços e larguras acompanham, para manter as proporções.',
                'default' => 'medium',
                'options' => [
                    'small' => ['label' => 'Pequeno', 'css' => ['--scale' => '93.75%']],
                    'medium' => ['label' => 'Médio', 'css' => ['--scale' => '100%']],
                    'large' => ['label' => 'Grande', 'css' => ['--scale' => '112.5%']],
                ],
            ],
            'width' => [
                'label' => 'Largura',
                'hint' => 'Da coluna de texto dos posts e da área das listas.',
                'default' => 'medium',
                'options' => [
                    'narrow' => ['label' => 'Estreita', 'css' => ['--measure' => '38rem', '--wide' => '62rem']],
                    'medium' => ['label' => 'Média', 'css' => ['--measure' => '44rem', '--wide' => '70rem']],
                    'wide' => ['label' => 'Larga', 'css' => ['--measure' => '50rem', '--wide' => '80rem']],
                ],
            ],
            'heading_weight' => [
                'label' => 'Peso dos títulos',
                'hint' => 'Fontes serifadas e de alto contraste costumam ficar mais elegantes em Normal.',
                'default' => 'bold',
                'options' => [
                    'normal' => ['label' => 'Normal', 'css' => ['--heading-weight' => '400']],
                    'semibold' => ['label' => 'Seminegrito', 'css' => ['--heading-weight' => '600']],
                    'bold' => ['label' => 'Negrito', 'css' => ['--heading-weight' => '700']],
                ],
            ],
        ];
    }

    /** Opção de cada item de tokens(), a partir do que foi salvo ou enviado. Faltando ou desconhecida = padrão. */
    public static function tokenChoices(array $chosen): array
    {
        $choices = [];
        foreach (self::tokens() as $key => $token) {
            $value = $chosen[$key] ?? null;
            $choices[$key] = is_string($value) && isset($token['options'][$value]) ? $value : $token['default'];
        }
        return $choices;
    }

    /** Variáveis CSS do tema (fontes e tokens), para o <style> de theme/layout.php. */
    public static function themeVars(): array
    {
        $vars = ['--font' => self::fontStack((string) self::get('font_text'))];
        if (self::get('font_heading')) {
            $vars['--font-heading'] = self::fontStack((string) self::get('font_heading'));
        }
        foreach (self::tokenChoices((array) self::get('tokens')) as $key => $value) {
            $vars += self::tokens()[$key]['options'][$value]['css'];
        }
        return $vars;
    }

    /** ['--a' => '1px'] → "--a: 1px;" */
    public static function css(array $vars): string
    {
        return implode(' ', array_map(fn($name, $value) => "$name: $value;", array_keys($vars), $vars));
    }

    public static function all(): array
    {
        if (self::$cache === null) {
            $saved = Storage::read('settings.json', []);
            $saved = is_array($saved) ? $saved : [];
            // Antes eram duas caixas: show_title (nome no cabeçalho) e home_intro (nome e frase na página inicial).
            if (!isset($saved['title_place']) && isset($saved['show_title'])) {
                $home = $saved['home_intro'] ?? true;
                $saved['title_place'] = $saved['show_title'] ? ($home ? 'both' : 'header') : ($home ? 'home' : 'none');
            }
            unset($saved['show_title']);
            self::$cache = self::paths(array_replace(self::defaults(), $saved), 'add_base');
        }
        return self::$cache;
    }

    public static function get(string $key, $default = null)
    {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /** Usa estes valores só nesta requisição, sem gravar (prévia do painel). */
    public static function override(array $values): void
    {
        self::$cache = array_replace(self::all(), $values);
    }

    public static function save(array $values): void
    {
        Storage::lock(function () use ($values) {
            self::$cache = null;
            $data = array_replace(self::all(), $values);
            Storage::write('settings.json', self::paths($data, 'strip_base'));
            self::$cache = $data;
        });
    }

    /** add_base() ou strip_base() nas imagens e no HTML do rodapé: em data/ ficam sem a pasta do blog. */
    private static function paths(array $values, callable $fn): array
    {
        foreach (['logo', 'favicon', 'default_image', 'seo_entity_logo'] as $key) {
            $values[$key] = $fn((string) $values[$key]);
        }
        $values['footer_text'] = html_base((string) $values['footer_text'], $fn);
        return $values;
    }
}
