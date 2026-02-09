<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use RuntimeException;

class PuppeteerChartRenderer
{
    /**
     * @param  array<string, mixed>  $chartConfig
     */
    public function renderChartToDataUri(array $chartConfig): string
    {
        $tmpDir = storage_path('app/tmp/reports-pdf');
        File::ensureDirectoryExists($tmpDir);

        $id = (string) Str::uuid();
        $htmlPath = $tmpDir.DIRECTORY_SEPARATOR."chart-{$id}.html";
        $pngPath = $tmpDir.DIRECTORY_SEPARATOR."chart-{$id}.png";

        $html = view('pdf.reports.chart-render', [
            'chartConfig' => $chartConfig,
        ])->render();

        File::put($htmlPath, $html);

        $node = 'node';
        $script = base_path('scripts/render-chart.mjs');

        $process = new Process([
            $node,
            $script,
            '--html', $htmlPath,
            '--out', $pngPath,
            '--selector', '#capture',
            '--width', '1200',
            '--height', '650',
            '--timeoutMs', '45000',
        ], base_path(), null, null, 60);

        $process->run();

        if (! $process->isSuccessful() || ! File::exists($pngPath)) {
            $output = trim($process->getErrorOutput()."\n".$process->getOutput());

            File::delete([$htmlPath, $pngPath]);

            throw new RuntimeException('No se pudo generar el gráfico para PDF. '.$output);
        }

        $base64 = base64_encode((string) File::get($pngPath));

        File::delete([$htmlPath, $pngPath]);

        return 'data:image/png;base64,'.$base64;
    }
}
