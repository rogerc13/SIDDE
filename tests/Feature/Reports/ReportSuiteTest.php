<?php

namespace Tests\Feature\Reports;

use App\Models\Capacity;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Modality;
use App\Models\Participant;
use App\Models\Person;
use App\Models\Role;
use App\Models\Scheduled;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the restructured reporting suite: the unified envelope, the executive
 * summary endpoint, exports and the frontend options registry.
 */
class ReportSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
        $this->seed(\Database\Seeders\TypeSeeder::class);
        $this->seed(\Database\Seeders\ParticipantStatusSeeder::class);

        CourseStatus::create(['name' => 'Por Dictar']);
        CourseStatus::create(['name' => 'En Curso']);
        CourseStatus::create(['name' => 'Culminado']);
        CourseStatus::create(['name' => 'Cancelado']);
    }

    private function admin(): User
    {
        return User::factory()->administrador()->create();
    }

    private function makeScheduled(int $statusId, string $startDate, string $sex = 'M'): Scheduled
    {
        $category = Category::factory()->create();
        $modality = Modality::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'duration' => 8,
        ]);
        Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);

        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => Facilitator::create(['person_id' => Person::factory()->create()->id])->id,
            'course_status_id' => $statusId,
            'start_date' => $startDate,
            'end_date' => Carbon::parse($startDate)->addDays(2)->toDateString(),
        ]);

        Participant::create([
            'person_id' => Person::factory()->create(['sex' => $sex])->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::APROBADO,
        ]);

        return $scheduled;
    }

    private function range(): array
    {
        return [
            'start_date' => Carbon::now()->subDays(20)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ];
    }

    public function test_report_returns_unified_envelope(): void
    {
        $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(3)->toDateString());

        $response = $this->actingAs($this->admin())->post('/reports/date', $this->range());

        $response->assertOk();
        $data = $response->json();

        foreach (['type', 'meta', 'summary', 'insight', 'charts', 'tables', 'raw'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }

        $this->assertSame('date', $data['type']);
        $this->assertSame('Cantidad de Acciones de Formación', $data['meta']['title']);
        $this->assertNotEmpty($data['insight']);
        $this->assertArrayHasKey('total', $data['raw']);
    }

    public function test_every_report_type_returns_an_insight(): void
    {
        $this->makeScheduled(CourseStatus::CULMINADO, now()->subDays(3)->toDateString(), 'F');

        $types = [
            'date', 'category', 'status', 'duration', 'canceled',
            'participant-by-quantity', 'participant-by-status', 'participant-average',
            'gender', 'most-scheduled', 'least-scheduled', 'not-scheduled',
        ];

        foreach ($types as $type) {
            $response = $this->actingAs($this->admin())->post("/reports/{$type}", $this->range());
            $response->assertOk();
            $this->assertNotEmpty($response->json('insight'), "Report {$type} should produce an insight");
        }
    }

    public function test_summary_endpoint_returns_documented_kpis(): void
    {
        $this->makeScheduled(CourseStatus::CULMINADO, now()->subDays(3)->toDateString());

        $response = $this->actingAs($this->admin())->post('/reports/summary', $this->range());

        $response->assertOk();
        $kpis = $response->json('kpis');

        $this->assertNotEmpty($kpis);
        $keys = array_column($kpis, 'key');
        foreach (['actions', 'completion', 'cancellation', 'hours', 'participants', 'approval'] as $expected) {
            $this->assertContains($expected, $keys);
        }

        foreach ($kpis as $kpi) {
            $this->assertArrayHasKey('formula', $kpi);
            $this->assertNotSame('', $kpi['formula']);
            $this->assertArrayHasKey('display', $kpi);
        }
    }

    public function test_summary_kpis_are_grouped_and_percentages_are_not_duplicated(): void
    {
        $this->makeScheduled(CourseStatus::CULMINADO, now()->subDays(3)->toDateString());

        $kpis = collect(
            $this->actingAs($this->admin())->post('/reports/summary', $this->range())->json('kpis')
        );

        // Volume metrics first, then rates.
        $this->assertSame(
            ['actions', 'hours', 'participants', 'completion', 'cancellation', 'approval'],
            $kpis->pluck('key')->all(),
        );

        foreach (['completion', 'cancellation', 'approval'] as $key) {
            $kpi = $kpis->firstWhere('key', $key);
            $this->assertSame('%', $kpi['unit']);
            $this->assertStringNotContainsString('%', (string) $kpi['display']);
        }

        $actions = $kpis->firstWhere('key', 'actions');
        $this->assertSame('acciones', $actions['unit']);
        $this->assertStringNotContainsString('%', (string) $actions['display']);
    }

    public function test_summary_actions_kpi_tracks_the_selected_range(): void
    {
        // Two actions inside the recent window, three in the older one.
        $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(3)->toDateString());
        $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(4)->toDateString());
        $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(60)->toDateString());
        $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(61)->toDateString());
        $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(62)->toDateString());

        $actions = fn (array $payload) => (int) collect(
            $this->actingAs($this->admin())->post('/reports/summary', $payload)->json('kpis')
        )->firstWhere('key', 'actions')['value'];

        $recent = $actions($this->range());
        $older = $actions([
            'start_date' => now()->subDays(75)->toDateString(),
            'end_date' => now()->subDays(45)->toDateString(),
        ]);

        $this->assertSame(2, $recent);
        $this->assertSame(3, $older);
    }

    public function test_export_csv_downloads_a_file(): void
    {
        $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(3)->toDateString());

        $this->actingAs($this->admin())
            ->post('/reports/export', $this->range() + ['report-type' => 'date', 'format' => 'csv'])
            ->assertOk()
            ->assertDownload();
    }

    public function test_export_xlsx_downloads_a_file(): void
    {
        $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(3)->toDateString());

        $this->actingAs($this->admin())
            ->post('/reports/export', $this->range() + ['report-type' => 'date', 'format' => 'xlsx'])
            ->assertOk()
            ->assertDownload();
    }

    public function test_export_rejects_unknown_report_type(): void
    {
        $this->actingAs($this->admin())
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/reports/export', ['report-type' => 'nope'])
            ->assertStatus(422);
    }

    public function test_least_scheduled_ranks_ascending_and_ignores_range(): void
    {
        // Two courses scheduled different numbers of times.
        $rare = $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(5)->toDateString());
        $common = $this->makeScheduled(CourseStatus::EN_CURSO, now()->subDays(4)->toDateString());
        Scheduled::create([
            'course_id' => $common->course_id,
            'facilitator_id' => $common->facilitator_id,
            'course_status_id' => CourseStatus::EN_CURSO,
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->subDays(1)->toDateString(),
        ]);

        $post = fn (string $start, string $end) => $this->actingAs($this->admin())
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/reports/least-scheduled', ['start_date' => $start, 'end_date' => $end, 'step' => '1 month'])
            ->json('raw');

        $rangeA = $post(now()->subDays(20)->toDateString(), now()->toDateString());
        $rangeB = $post(now()->subYear()->toDateString(), now()->subYear()->addDays(10)->toDateString());

        // Historical report must be identical regardless of the chosen range.
        $this->assertSame(json_encode($rangeA['amountData']), json_encode($rangeB['amountData']));

        // Ascending ordering: the rarest course (1 run) comes before the common one (2 runs).
        $amounts = $rangeA['amountData'];
        $this->assertLessThan((int) $amounts[$common->course_id], (int) $amounts[$rare->course_id]);
    }

    public function test_pdf_download_returns_a_pdf_for_representative_report_types(): void
    {
        $this->makeScheduled(CourseStatus::CULMINADO, now()->subDays(3)->toDateString());

        // A representative mix: chart + table, ranged + historical.
        $types = ['date', 'category', 'gender', 'least-scheduled'];

        foreach ($types as $type) {
            $this->actingAs($this->admin())
                ->post('/reports/pdf', $this->range() + ['report-type' => $type])
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf')
                ->assertDownload();
        }
    }

    public function test_pdf_accepts_browser_supplied_charts(): void
    {
        $this->makeScheduled(CourseStatus::CULMINADO, now()->subDays(3)->toDateString());

        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $this->actingAs($this->admin())
            ->post('/reports/pdf', $this->range() + [
                'report-type' => 'category',
                'charts' => [
                    'category_bar' => ['title' => 'Gráfico', 'image' => $png],
                    'category_doughnut' => ['title' => 'Dona', 'image' => $png],
                ],
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload();
    }

    public function test_options_endpoint_exposes_registry_and_palette(): void
    {
        $response = $this->actingAs($this->admin())->get('/reports/options');

        $response->assertOk();
        $types = array_column($response->json('types'), 'value');
        $this->assertContains('date', $types);
        $this->assertContains('gender', $types);
        $this->assertNotEmpty($response->json('palette.categorical'));
    }
}
