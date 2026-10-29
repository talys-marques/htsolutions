<?php
/**
 * Tela /admin/import da extensão "Importar do WordPress".
 */
final class WordPressImportPage
{
    public static function handle(): void
    {
        $user = Auth::requireAdmin();
        $error = null;
        $report = null;
        $progress = null;
        if (is_post()) {
            verify_csrf();
            @set_time_limit(900);
            try {
                if (input('step') === 'images') {
                    $progress = self::downloadImages();
                } else {
                    $importer = new WordPressImporter($user['username'], isset($_POST['drafts']));
                    $report = $importer->import(self::importFile(), input('old_url'));
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
        $files = array_map('basename', glob(Storage::path('import') . '/*.xml') ?: []);
        rsort($files);
        $running = $progress && !$progress['finished'];
        admin_view(__DIR__ . '/views/import.php', [
            'title' => 'Importar do WordPress',
            'active' => 'import',
            'error' => $error,
            'report' => $report,
            'files' => $files,
            'maxUpload' => Media::serverMaxBytes(),
            'progress' => $progress,
            'missing' => $running ? [] : WordPressImages::missing(),
            'oldUrl' => $report['old_url'] ?? (input('old_url')
                ?: ($files ? WordPressImporter::siteUrl(Storage::path('import/' . $files[0])) : '')),
        ]);
    }

    /**
     * Uma parte do download das imagens (uns 15 segundos, para não estourar o
     * tempo limite do servidor). A página envia a parte seguinte sozinha.
     */
    private static function downloadImages(): array
    {
        $oldUrl = rtrim(input('old_url'), '/');
        if (!preg_match('#^https?://[^/\s]+#i', $oldUrl)) {
            throw new RuntimeException('Informe o endereço do site antigo, ex.: https://meublog.com.br');
        }
        $after = (string) ($_POST['after'] ?? '');
        $missing = WordPressImages::missing();
        $todo = array_values(array_filter($missing, fn($path) => strcmp($path, $after) > 0));
        [$ok, $failed, $last] = WordPressImages::download($oldUrl, $todo, 15);

        $progress = [
            'old_url' => $oldUrl,
            'total' => (int) input('total') ?: count($missing),
            'ok' => (int) input('ok') + $ok,
            'failed' => (int) input('failed') + $failed,
            'after' => $last,
            'finished' => $last === ($todo ? end($todo) : ''),
        ];
        if ($progress['ok'] === 0 && $failed > 0) {
            throw new RuntimeException("Nenhuma imagem pôde ser baixada de $oldUrl. Confira o endereço e se o site antigo ainda está no ar.");
        }
        return $progress;
    }

    /** Arquivo XML da importação: enviado agora ou já colocado em data/import/. */
    private static function importFile(): string
    {
        $dir = Storage::path('import');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $upload = $_FILES['xml'] ?? null;
        if (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($upload['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('O envio do arquivo falhou (talvez seja maior que o limite do servidor). '
                    . 'Alternativa: coloque o .xml na pasta data/import/ por FTP e escolha na lista.');
            }
            if (strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION)) !== 'xml') {
                throw new RuntimeException('Envie o arquivo .xml exportado pelo WordPress.');
            }
            $target = $dir . '/' . date('Y-m-d-His') . '-' . (slugify(pathinfo((string) $upload['name'], PATHINFO_FILENAME)) ?: 'wordpress') . '.xml';
            if (!move_uploaded_file($upload['tmp_name'], $target)) {
                throw new RuntimeException('Não foi possível salvar o arquivo enviado em data/import/.');
            }
            return $target;
        }
        $existing = basename(input('existing'));
        if ($existing !== '' && is_file($dir . '/' . $existing)) {
            return $dir . '/' . $existing;
        }
        throw new RuntimeException('Escolha o arquivo .xml exportado pelo WordPress.');
    }
}
