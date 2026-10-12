<?php

namespace Tests\Unit\Reports;

use App\Enums\ReportType;
use App\Services\Reports\ProvidedCharts;
use App\Services\Reports\ReportPdfPageBuilder;
use Tests\TestCase;

class ReportPdfPageBuilderTest extends TestCase
{
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function builder(array $charts = []): ReportPdfPageBuilder
    {
        return new ReportPdfPageBuilder(new ProvidedCharts($charts));
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     * @return array<int, array<string, mixed>>
     */
    private function tables(array $pages): array
    {
        return array_values(array_filter($pages, fn ($page) => ($page['kind'] ?? '') === 'table'));
    }

    public function test_duration_tables_are_populated_from_collections(): void
    {
        $pages = $this->builder()->build(ReportType::Duration, [
            'dateRange' => ['startDate' => '2026-09-01', 'endDate' => '2026-09-30'],
            'finishedByDateRange' => 8,
            'byDateRange' => collect([
                ['course_title' => 'Curso A', 'start_date' => '2026-09-10', 'end_date' => '2026-09-12', 'duration' => 8, 'duration_days' => 3],
            ]),
            'mostDuration' => collect([
                ['code' => 'C-1', 'title' => 'Curso A', 'duration' => 8],
            ]),
        ]);

        $tables = $this->tables($pages);
        $this->assertCount(2, $tables);
        $this->assertSame('Curso A', $tables[0]['rows'][0][0]);
        $this->assertSame('C-1', $tables[1]['rows'][0][0]);
    }

    public function test_most_scheduled_sums_collection_amounts(): void
    {
        $pages = $this->builder()->build(ReportType::MostScheduled, [
            'amountData' => collect([5 => 3, 7 => 2]),
            'courseData' => collect([
                ['id' => 5, 'code' => 'A', 'title' => 'Curso A'],
                ['id' => 7, 'code' => 'B', 'title' => 'Curso B'],
            ]),
        ]);

        $table = $pages[0];
        $this->assertSame(['A', 'Curso A', '3'], $table['rows'][0]);
        $this->assertStringContainsString('Total: 5', $table['title']);
    }

    public function test_not_scheduled_and_canceled_are_populated(): void
    {
        $notScheduled = $this->builder()->build(ReportType::NotScheduled, [
            'data' => collect([
                ['code' => 'N-1', 'title' => 'Sin programar', 'category' => ['name' => 'Área X']],
            ]),
        ]);
        $this->assertSame(['N-1', 'Sin programar', 'Área X'], $notScheduled[0]['rows'][0]);

        $canceled = $this->builder()->build(ReportType::Canceled, [
            'data' => collect([
                ['code' => 'C-9', 'title' => 'Cancelado', 'category' => 'Área Y', 'start_date' => '2026-09-05', 'end_date' => '2026-09-06'],
            ]),
        ]);
        $this->assertSame('C-9', $canceled[0]['rows'][0][0]);
        $this->assertStringContainsString('Total: 1', $canceled[0]['title']);
    }

    public function test_nested_course_collections_build_detail_rows(): void
    {
        $nested = collect([
            [
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-12',
                'course' => ['title' => 'Curso Nested', 'category' => ['name' => 'Área Z']],
            ],
        ]);

        $category = $this->builder()->build(ReportType::Category, [
            'dateRange' => ['startDate' => '2026-09-01', 'endDate' => '2026-09-30'],
            'courseData' => [
                ['categoryName' => 'Área Z', 'amount' => 1, 'courseData' => $nested],
            ],
        ]);
        $this->assertSame(['Curso Nested', '2026-09-10', '2026-09-12', 'Área Z'], $this->tables($category)[1]['rows'][0]);

        $status = $this->builder()->build(ReportType::Status, [
            'dateRange' => ['startDate' => '2026-09-01', 'endDate' => '2026-09-30'],
            'courseData' => [
                ['statusName' => 'Culminado', 'amount' => 1, 'courseData' => collect([
                    [
                        'start_date' => '2026-09-10',
                        'end_date' => '2026-09-12',
                        'course' => ['title' => 'Curso Nested'],
                        'course_status' => ['name' => 'Culminado'],
                    ],
                ])],
            ],
        ]);
        $this->assertSame(['Curso Nested', '2026-09-10', '2026-09-12', 'Culminado'], $this->tables($status)[1]['rows'][0]);
    }

    public function test_gender_participant_status_and_quantity_collections(): void
    {
        $gender = $this->builder()->build(ReportType::Gender, [
            'dateRange' => ['startDate' => '2026-09-01', 'endDate' => '2026-09-30'],
            'total' => 3,
            'rows' => collect([
                ['label' => 'Masculino', 'amount' => 2],
                ['label' => 'Femenino', 'amount' => 1],
            ]),
        ]);
        $this->assertSame(['2', '1', '3'], $this->tables($gender)[0]['rows'][0]);

        $participantStatus = $this->builder()->build(ReportType::ParticipantByStatus, [
            'dateRange' => ['startDate' => '2026-09-01', 'endDate' => '2026-09-30'],
            'labels' => [['label' => 'Aprobado']],
            'allStatusbyDateRange' => [['status' => 'Aprobado', 'countByStatus' => 2]],
            'byAllTime' => collect([
                ['person' => ['name' => 'Ana', 'last_name' => 'Pérez', 'id_number' => '123'], 'scheduled' => ['course' => ['title' => 'Curso'], 'start_date' => '2026-09-10'], 'participant_status' => ['name' => 'Aprobado']],
            ]),
        ]);
        $this->assertSame(['Ana', 'Pérez', '123', 'Aprobado', 'Curso', '2026-09-10'], $this->tables($participantStatus)[1]['rows'][0]);

        $quantity = $this->builder()->build(ReportType::ParticipantByQuantity, [
            'dateRangeAmountPerCourse' => collect([
                ['course' => 'Curso', 'start_date' => '2026-09-10', 'end_date' => '2026-09-12', 'count' => 4],
            ]),
        ]);
        $this->assertSame(['Curso', '2026-09-10', '2026-09-12', '4'], $quantity[0]['rows'][0]);
    }

    public function test_chart_pages_come_from_provided_charts(): void
    {
        $pages = $this->builder(['date_line' => ['image' => self::PNG]])
            ->build(ReportType::Date, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'total' => 5,
            ]);

        $this->assertCount(1, $pages);
        $this->assertSame('chart', $pages[0]['kind']);
        $this->assertSame(self::PNG, $pages[0]['image']);
        $this->assertStringContainsString(': 5', $pages[0]['title']);
    }
}
