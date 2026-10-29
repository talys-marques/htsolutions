<?php
/**
 * Editor visual (admin.js, seção "Editor visual"). O HTML vai no campo $name.
 * @var string $name  @var string $value  HTML atual  @var string $placeholder  @var bool $compact  altura menor
 */
$icon = function (string $name): string {
    $paths = [
        'link' => '<path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 0 0-7.07-7.07L11.5 4.4"/><path d="M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 0 0 7.07 7.07l1.5-1.5"/>',
        'ul' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1.2" fill="currentColor"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor"/>',
        'ol' => '<path d="M10 6h10M10 12h10M10 18h10"/><path d="M4 4.5h1.2V9M3.6 9h3M3.5 14.2a1.4 1.4 0 0 1 2.6.6c0 .9-2.6 2.2-2.6 3.2h2.8" stroke-width="1.4"/>',
        'quote' => '<path d="M4 17c2.5-1 4-3.2 4-6.5V7H4v5h3.6M14 17c2.5-1 4-3.2 4-6.5V7h-4v5h3.6"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-9 9"/>',
        'video' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m10 9 5 3-5 3z" fill="currentColor"/>',
        'hr' => '<path d="M4 12h16"/>',
        'undo' => '<path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/>',
        'redo' => '<path d="m15 14 5-5-5-5"/><path d="M20 9H9a5 5 0 0 0 0 10h3"/>',
        'code' => '<path d="m8 7-5 5 5 5M16 7l5 5-5 5"/>',
        'clear' => '<path d="M5 6h12M11 6l-3 13M4 20h8M15 14l5 5M20 14l-5 5"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
};
?>
<div class="editor<?= !empty($compact) ? ' editor-compact' : '' ?>" data-editor>
  <div class="editor-toolbar" role="toolbar" aria-label="Formatação">
    <select data-block aria-label="Estilo do parágrafo">
      <option value="p">Parágrafo</option>
      <option value="h2">Título 2</option>
      <option value="h3">Título 3</option>
      <option value="h4">Título 4</option>
      <option value="pre">Código</option>
    </select>
    <span class="sep"></span>
    <button type="button" data-cmd="bold" title="Negrito (Ctrl+B)"><b>B</b></button>
    <button type="button" data-cmd="italic" title="Itálico (Ctrl+I)"><i>I</i></button>
    <button type="button" data-cmd="strike" title="Riscado"><s>S</s></button>
    <button type="button" data-cmd="link" title="Link (Ctrl+K)"><?= $icon('link') ?></button>
    <span class="sep"></span>
    <button type="button" data-cmd="ul" title="Lista"><?= $icon('ul') ?></button>
    <button type="button" data-cmd="ol" title="Lista numerada"><?= $icon('ol') ?></button>
    <button type="button" data-cmd="quote" title="Citação"><?= $icon('quote') ?></button>
    <button type="button" data-cmd="hr" title="Linha divisória"><?= $icon('hr') ?></button>
    <span class="sep"></span>
    <button type="button" data-cmd="image" title="Imagem (ou arraste/cole no texto)"><?= $icon('image') ?></button>
    <button type="button" data-cmd="video" title="Vídeo do YouTube ou Vimeo"><?= $icon('video') ?></button>
    <span class="sep"></span>
    <button type="button" data-cmd="clear" title="Limpar formatação"><?= $icon('clear') ?></button>
    <button type="button" data-cmd="undo" title="Desfazer (Ctrl+Z)"><?= $icon('undo') ?></button>
    <button type="button" data-cmd="redo" title="Refazer (Ctrl+Y)"><?= $icon('redo') ?></button>
    <span class="toolbar-status" aria-live="polite"></span>
    <button type="button" data-cmd="source" class="toolbar-source" title="Editar o HTML"><?= $icon('code') ?> HTML</button>
  </div>
  <div class="editor-content prose" contenteditable="true" data-placeholder="<?= e($placeholder) ?>"></div>
  <textarea name="<?= e($name) ?>" class="editor-source" spellcheck="false" hidden><?= e($value) ?></textarea>
</div>
