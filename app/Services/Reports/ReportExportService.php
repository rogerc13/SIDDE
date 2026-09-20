<?php

namespace App\Services\Reports;

use App\Enums\ReportType;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Exports a {@see ReportResult} to CSV or XLSX.
 *
 * Tables are derived from the normalized result so every report gains export
 * support without per-report writer code. CSV uses a UTF-8 BOM and a semicolon
 * delimiter for Excel compatibility.
 */
final class ReportExportService
{
    /**
     * @return array{0: string, 1: string} [path, filename]
     */
    public function write(ReportResult $result, string $format): array
    {
        $format = $format === 'xlsx' ? 'xlsx' : 'csv';
        $filename = Str::slug($result->type->label(), '-') . '-' . Carbon::now()->format('Ymd-His') . '.' . $format;
        $path = $this->tempPath($filename);

        $writer = $format === 'xlsx'
            ? new XlsxWriter()
            : new CsvWriter(new CsvOptions(FIELD_DELIMITER: ';', SHOULD_ADD_BOM: true));

        $writer->openToFile($path);

        $headerStyle = new Style(fontBold: true);
        $tables = $this->tablesFor($result);

        foreach ($tables as $index => $table) {
            if ($index > 0) {
                $writer->addRow(Row::fromValues(['']));
            }

            $writer->addRow(Row::fromValuesWithStyle([$table['title']], $headerStyle));
            $writer->addRow(Row::fromValuesWithStyle(array_map('strval', $table['columns']), $headerStyle));

            foreach ($table['rows'] as $row) {
                $writer->addRow(Row::fromValues(array_map(
                    fn ($cell) => is_array($cell) ? ($cell['text'] ?? '') : (string) $cell,
                    $row,
                )));
            }
        }

        $writer->close();

        return [$path, $filename];
    }

    public function download(ReportResult $result, string $format): BinaryFileResponse
    {
        [$path, $filename] = $this->write($result, $format);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function tempPath(string $filename): string
    {
        $dir = storage_path('app/tmp/reports-export');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @return array<int, array{title: string, columns: array<int, string>, rows: array<int, array<int, mixed>>}>
     */
    public function tablesFor(ReportResult $result): array
    {
        $raw = $result->raw;

        return match ($result->type) {
            ReportType::Date => [[
                'title' => 'Cantidad de Acciones de Formación',
                'columns' => ['Período', 'Cantidad'],
                'rows' => $this->zipRows($raw['x'] ?? [], $raw['y'] ?? []),
            ]],
            ReportType::Category => [
                [
                    'title' => 'Acciones por Área de Conocimiento',
                    'columns' => ['Área de Conocimiento', 'Cantidad'],
                    'rows' => array_map(fn ($row) => [$row['categoryName'] ?? '', $row['amount'] ?? 0], (array) Arr::get($raw, 'courseData', [])),
                ],
                [
                    'title' => 'Áreas de Conocimiento por Período',
                    'columns' => ['Área', 'Período', 'Cantidad'],
                    'rows' => array_map(fn ($row) => [$row['category'] ?? '', $this->displayDate($row['x'] ?? ''), $row['y'] ?? 0], (array) Arr::get($raw, 'y', [])),
                ],
            ],
            ReportType::Status => [
                [
                    'title' => 'Acciones por Estatus',
                    'columns' => ['Estatus', 'Cantidad'],
                    'rows' => array_map(fn ($row) => [$row['statusName'] ?? '', $row['amount'] ?? 0], (array) Arr::get($raw, 'courseData', [])),
                ],
                [
                    'title' => 'Estatus por Período',
                    'columns' => ['Estatus', 'Período', 'Cantidad'],
                    'rows' => array_map(fn ($row) => [$row['status'] ?? '', $this->displayDate($row['x'] ?? ''), $row['y'] ?? 0], (array) Arr::get($raw, 'y', [])),
                ],
            ],
            ReportType::Duration => [[
                'title' => 'Duración de Acciones de Formación',
                'columns' => ['Título', 'Inicio', 'Fin', 'Duración (horas)', 'Duración (días)'],
                'rows' => array_map(fn ($row) => [
                    $row['course_title'] ?? '',
                    $this->displayDate($row['start_date'] ?? ''),
                    $this->displayDate($row['end_date'] ?? ''),
                    $row['duration'] ?? 0,
                    $row['duration_days'] ?? '',
                ], (array) Arr::get($raw, 'byDateRange', [])),
            ]],
            ReportType::Canceled => [[
                'title' => 'Acciones de Formación Canceladas',
                'columns' => ['Código', 'Título', 'Área de Conocimiento', 'Inicio', 'Fin'],
                'rows' => array_map(fn ($row) => [
                    $row['code'] ?? '',
                    $row['title'] ?? '',
                    $row['category'] ?? '',
                    $this->displayDate($row['start_date'] ?? ''),
                    $this->displayDate($row['end_date'] ?? ''),
                ], (array) Arr::get($raw, 'data', [])),
            ]],
            ReportType::ParticipantByQuantity => [[
                'title' => 'Acciones por Cantidad de Participantes',
                'columns' => ['Título', 'Inicio', 'Fin', 'Participantes', 'Capacidad', 'Ocupación (%)'],
                'rows' => array_map(fn ($row) => [
                    $row['course'] ?? '',
                    $this->displayDate($row['start_date'] ?? ''),
                    $this->displayDate($row['end_date'] ?? ''),
                    $row['count'] ?? 0,
                    $row['capacity'] ?? '',
                    $row['fillRate'] ?? '',
                ], is_array(Arr::get($raw, 'dateRangeAmountPerCourse', [])) ? Arr::get($raw, 'dateRangeAmountPerCourse', []) : []),
            ]],
            ReportType::ParticipantByStatus => [
                [
                    'title' => 'Participantes por Estatus',
                    'columns' => ['Período', 'Estatus', 'Cantidad'],
                    'rows' => array_map(fn ($row) => [
                        $this->displayDate($row['date'] ?? ''),
                        $row['status'] ?? '',
                        $row['countByStatus'] ?? 0,
                    ], (array) Arr::get($raw, 'allStatusbyDateRange', [])),
                ],
                [
                    'title' => 'Lista de Participantes',
                    'columns' => ['Nombres', 'Apellidos', 'Cédula', 'Estatus', 'Acción de Formación', 'Inicio'],
                    'rows' => array_map(function ($p) {
                        $person = $p['person'] ?? [];
                        $scheduled = $p['scheduled'] ?? [];
                        $course = $scheduled['course'] ?? [];
                        $status = $p['participant_status'] ?? ($p['participantStatus'] ?? []);

                        return [
                            $person['name'] ?? '',
                            $person['last_name'] ?? '',
                            $person['id_number'] ?? '',
                            $status['name'] ?? '',
                            $course['title'] ?? '',
                            $this->displayDate($scheduled['start_date'] ?? ''),
                        ];
                    }, (array) Arr::get($raw, 'byAllTime', [])),
                ],
            ],
            ReportType::ParticipantAverage => [[
                'title' => 'Promedio de Participantes',
                'columns' => ['Período', 'Aprobados', 'Reprobados', 'Total'],
                'rows' => array_map(fn ($row) => [
                    $this->displayDate($row['date'] ?? ''),
                    $row['approved'] ?? 0,
                    $row['failed'] ?? 0,
                    $row['total'] ?? 0,
                ], (array) Arr::get($raw, 'data', [])),
            ]],
            ReportType::Gender => [[
                'title' => 'Participantes por Género',
                'columns' => ['Género', 'Cantidad', 'Porcentaje (%)'],
                'rows' => array_map(fn ($row) => [
                    $row['label'] ?? '',
                    $row['amount'] ?? 0,
                    $row['share'] ?? '',
                ], (array) Arr::get($raw, 'rows', [])),
            ]],
            ReportType::MostScheduled => [[
                'title' => 'Acciones más Programadas',
                'columns' => ['Código', 'Título', 'Programaciones'],
                'rows' => array_map(fn ($course) => [
                    $course['code'] ?? '',
                    $course['title'] ?? '',
                    (int) (Arr::get($raw, 'amountData.' . ($course['id'] ?? ''), 0)),
                ], (array) Arr::get($raw, 'courseData', [])),
            ]],
            ReportType::LeastScheduled => [[
                'title' => 'Acciones menos Programadas',
                'columns' => ['Código', 'Título', 'Programaciones'],
                'rows' => collect((array) Arr::get($raw, 'courseData', []))
                    ->sortBy(fn ($course) => (int) Arr::get($raw, 'amountData.' . ($course['id'] ?? ''), 0))
                    ->map(fn ($course) => [
                        $course['code'] ?? '',
                        $course['title'] ?? '',
                        (int) (Arr::get($raw, 'amountData.' . ($course['id'] ?? ''), 0)),
                    ])
                    ->values()
                    ->all(),
            ]],
            ReportType::NotScheduled => [[
                'title' => 'Acciones No Programadas',
                'columns' => ['Código', 'Título', 'Área de Conocimiento'],
                'rows' => array_map(fn ($course) => [
                    $course['code'] ?? '',
                    $course['title'] ?? '',
                    Arr::get($course, 'category.name', ''),
                ], (array) Arr::get($raw, 'data', [])),
            ]],
        };
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function zipRows(array $x, array $y): array
    {
        $rows = [];
        foreach ($x as $index => $label) {
            $rows[] = [$label, $y[$index] ?? 0];
        }

        return $rows;
    }

    private function displayDate(mixed $date): string
    {
        if (! is_string($date) || trim($date) === '') {
            return '';
        }

        try {
            return Carbon::parse($date)->format('d-m-Y');
        } catch (\Throwable) {
            return $date;
        }
    }
}
