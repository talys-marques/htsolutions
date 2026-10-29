<?php
/**
 * Tipos de campo dos modelos de página (extensions/site-ht/modelos/*.php).
 * Cada método devolve a definição usada para montar o formulário e limpar o que chega.
 *
 *   texto    uma linha, sem HTML           longo    várias linhas, sem HTML
 *   titulo   uma linha com <strong> e <br>  html     editor visual (Html::clean)
 *   imagem   escolhida na biblioteca       link     /pagina/, https://..., mailto:, tel:
 *   selecao  uma das opções               post     um post publicado do blog
 *   grupo    campos juntos                 lista    itens repetidos (adicionar, mover, remover)
 */
final class Campo
{
    public static function texto(string $rotulo, string $dica = ''): array
    {
        return ['tipo' => 'texto', 'rotulo' => $rotulo, 'dica' => $dica];
    }

    public static function longo(string $rotulo, string $dica = ''): array
    {
        return ['tipo' => 'longo', 'rotulo' => $rotulo, 'dica' => $dica];
    }

    public static function titulo(string $rotulo, string $dica = 'Use <strong>palavra</strong> para destacar em azul e <br> para quebrar a linha.'): array
    {
        return ['tipo' => 'titulo', 'rotulo' => $rotulo, 'dica' => $dica];
    }

    public static function html(string $rotulo, string $dica = ''): array
    {
        return ['tipo' => 'html', 'rotulo' => $rotulo, 'dica' => $dica];
    }

    public static function imagem(string $rotulo, string $dica = ''): array
    {
        return ['tipo' => 'imagem', 'rotulo' => $rotulo, 'dica' => $dica];
    }

    public static function link(string $rotulo, string $dica = 'Página do site (ex.: /servicos/) ou endereço completo (https://...).'): array
    {
        return ['tipo' => 'link', 'rotulo' => $rotulo, 'dica' => $dica];
    }

    public static function selecao(string $rotulo, array $opcoes, string $dica = ''): array
    {
        return ['tipo' => 'selecao', 'rotulo' => $rotulo, 'opcoes' => $opcoes, 'dica' => $dica];
    }

    public static function post(string $rotulo, string $dica = ''): array
    {
        return ['tipo' => 'post', 'rotulo' => $rotulo, 'dica' => $dica];
    }

    public static function grupo(string $rotulo, array $campos, string $dica = ''): array
    {
        return ['tipo' => 'grupo', 'rotulo' => $rotulo, 'campos' => $campos, 'dica' => $dica];
    }

    /** $item: nome de cada item ("Parceiro"); $resumo: campo cujo valor aparece no item fechado. */
    public static function lista(string $rotulo, string $item, array $campos, string $resumo = '', string $dica = ''): array
    {
        return ['tipo' => 'lista', 'rotulo' => $rotulo, 'item' => $item, 'campos' => $campos, 'resumo' => $resumo, 'dica' => $dica];
    }

    /** Botão com texto e link (aparece em quase todas as páginas). */
    public static function botao(string $rotulo = 'Botão'): array
    {
        return self::grupo($rotulo, ['texto' => self::texto('Texto do botão'), 'link' => self::link('Link')]);
    }
}
