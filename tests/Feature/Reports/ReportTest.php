<?php

namespace Tests\Feature\Reports;

use App\Models\Capacity;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseStatus;
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
 * Characterization tests: pin the CURRENT response contracts of the reports
 * endpoints so later hardening/rewrites cannot silently drift the shapes the
 * frontend (reports.js) and ReportPdfController depend on.
 */
class ReportTest extends TestCase
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

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        return User::factory()->administrador()->create();
    }

    private function makeCategory(string $name): Category
    {
        return Category::factory()->create(['name' => $name]);
    }

    private function makeScheduled(Category $category, int $statusId, string $startDate, ?string $endDate = null): Scheduled
    {
        $modality = Modality::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'duration' => 8,
        ]);
        Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);

        return Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => \App\Models\Facilitator::create(['person_id' => Person::factory()->create()->id])->id,
            'course_status_id' => $statusId,
            'start_date' => $startDate,
            'end_date' => $endDate ?? Carbon::parse($startDate)->addDays(5)->toDateString(),
        ]);
    }

    private function addParticipant(Scheduled $scheduled, ?int $statusId = null, string $sex = 'M'): Participant
    {
        return Participant::create([
            'person_id' => Person::factory()->create(['sex' => $sex])->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => $statusId ?? Participant::PORINICIAR,
        ]);
    }

    private function inRangeDates(): array
    {
        return [
            'start_date' => Carbon::now()->subDays(20)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ];
    }

    private function decodeAsCurrentContract($response): array
    {
        // Several endpoints currently return json_encode() strings instead of
        // JsonResponse. This helper decodes both styles.
        return json_decode($response->getContent(), true);
    }

    // ------------------------------------------------------------ auth matrix

    public function test_reports_page_denies_facilitador_and_participante(): void
    {
        foreach ([Role::FACILITADOR, Role::PARTICIPANTE] as $roleId) {
            $user = User::factory()->create(['role_id' => $roleId]);

            $this->actingAs($user)
                ->from('/')
                ->post('/reports/date', $this->inRangeDates())
                ->assertRedirect(route('home'));

            $this->actingAs($user)
                ->get('/reports')
                ->assertRedirect(route('home'));
        }
    }

    public function test_admin_can_access_reports_endpoints(): void
    {
        $admin = $this->admin();

        foreach (['date', 'status', 'gender', 'most-scheduled', 'not-scheduled'] as $type) {
            $this->actingAs($admin)
                ->post("/reports/{$type}", $type === 'date' ? $this->inRangeDates() : [])
                ->assertOk();
        }
    }

    // ------------------------------------------------- per-endpoint contracts

    public function test_by_date_shape_and_counts(): void
    {
        $cat = $this->makeCategory('Cat A');
        $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDays(5)->toDateString());
        // outside the requested range: must not be counted
        $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->addDays(40)->toDateString());

        $response = $this->actingAs($this->admin())->post('/reports/date', $this->inRangeDates());

        $response->assertOk();
        $data = $response->json();

        foreach (['x', 'y', 'total', 'yData', 'start_date', 'end_date'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
        $this->assertSame(1, $data['total']);
        $this->assertSame(array_sum($data['y']), $data['total']);
        $this->assertCount(count($data['x']), $data['y']);
    }

    public function test_by_category_shape(): void
    {
        $catA = $this->makeCategory('Cat Alpha');
        $catB = $this->makeCategory('Cat Beta');
        $this->makeScheduled($catA, CourseStatus::EN_CURSO, now()->subDays(5)->toDateString());
        $this->makeScheduled($catB, CourseStatus::CULMINADO, now()->subDays(3)->toDateString());

        $response = $this->actingAs($this->admin())->post('/reports/category', $this->inRangeDates());

        $response->assertOk();
        $data = $this->decodeAsCurrentContract($response);

        $this->assertIsArray($data);
        foreach (['x', 'graphData', 'categories', 'courseData', 'dateRange', 'y'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
        $this->assertEqualsCanonicalizing(['Cat Alpha', 'Cat Beta'], $data['categories']);
        $this->assertSame('Cat Alpha', $data['dateRange']['startDate'] ? $data['courseData'][0]['categoryName'] : null);
        $this->assertSame(1, $data['courseData'][0]['amount']);
    }

    public function test_by_status_shape(): void
    {
        $cat = $this->makeCategory('Cat S');
        $this->makeScheduled($cat, CourseStatus::CULMINADO, now()->subDays(4)->toDateString());
        $this->makeScheduled($cat, CourseStatus::CANCELADO, now()->subDays(2)->toDateString());

        $response = $this->actingAs($this->admin())->post('/reports/status', $this->inRangeDates());

        $response->assertOk();
        $data = $this->decodeAsCurrentContract($response);

        foreach (['statuses', 'x', 'y', 'byAllTime', 'courseData', 'dateRange'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
        $this->assertContains('Culminado', $data['statuses']);
        $culminatedRow = collect($data['courseData'])->firstWhere('statusName', 'Culminado');
        $this->assertSame(1, $culminatedRow['amount']);
    }

    public function test_duration_shape_and_counts(): void
    {
        $cat = $this->makeCategory('Cat D');
        $finished = $this->makeScheduled($cat, CourseStatus::CULMINADO, now()->subDays(4)->toDateString());
        $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDays(2)->toDateString());

        $response = $this->actingAs($this->admin())->post('/reports/duration', $this->inRangeDates());

        $response->assertOk();
        $data = $this->decodeAsCurrentContract($response);

        foreach (['byAllTime', 'finishedTotal', 'mostDuration', 'byDateRange', 'finishedByDateRange', 'spansMostDays', 'dateRange'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
        // only CULMINADO runs count towards finished hours
        $this->assertSame(8, $data['finishedByDateRange']);
        $this->assertCount(1, $data['byDateRange']);
        $this->assertSame($finished->id, $data['byDateRange'][0]['scheduled_id']);
        $this->assertNotEmpty($data['spansMostDays']);
    }

    public function test_invalid_dates_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/reports/date', ['start_date' => 'not-a-date', 'end_date' => 'also-bad'])
            ->assertSessionHasErrors(['start_date', 'end_date']);

        $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/reports/date', ['start_date' => 'not-a-date'])
            ->assertStatus(422);
    }

    public function test_oversized_range_is_rejected(): void
    {
        $admin = $this->admin();

        // 10 years at daily step would generate ~3650 buckets; cap is ~400.
        $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/reports/date', [
                'start_date' => now()->subYears(10)->toDateString(),
                'end_date' => now()->toDateString(),
                'step' => '1 day',
            ])
            ->assertStatus(422);
    }

    public function test_participants_by_status_defaults_to_last_month_when_dates_missing(): void
    {
        $cat = $this->makeCategory('Cat PD');
        // started 20 days ago: inside the default last-month window
        $scheduled = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDays(20)->toDateString());
        $this->addParticipant($scheduled);

        $response = $this->actingAs($this->admin())->post('/reports/participant-by-status');

        $response->assertOk();
        $data = $response->json();

        $this->assertCount(1, $data['byAllTime']);
        $this->assertNotSame(now()->toDateString(), $data['dateRange']['startDate']);
    }

    public function test_participants_by_status_shape_with_explicit_dates(): void
    {
        $cat = $this->makeCategory('Cat P');
        $scheduled = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->addParticipant($scheduled, Participant::APROBADO);
        $this->addParticipant($scheduled, Participant::REPROBADO);

        $response = $this->actingAs($this->admin())->post('/reports/participant-by-status', [
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $response->assertOk();
        $data = $response->json();

        foreach (['dateRange', 'byAllTime', 'byStatusByDateRange', 'allStatusbyDateRange', 'labels'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
        $this->assertCount(2, $data['byAllTime']);
        $labels = array_column($data['labels'], 'label');
        $this->assertContains('Aprobado', $labels);
    }

    public function test_gender_shape_groups_labels(): void
    {
        $cat = $this->makeCategory('Cat G');
        $scheduled = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->addParticipant($scheduled, Participant::APROBADO, 'F');
        $this->addParticipant($scheduled, Participant::APROBADO, 'F');
        $this->addParticipant($scheduled, Participant::PORINICIAR, 'M');

        $response = $this->actingAs($this->admin())->post('/reports/gender', $this->inRangeDates());

        $response->assertOk();
        $data = $response->json();

        $this->assertSame(3, $data['total']);
        $rows = array_column($data['rows'], 'amount', 'label');
        $this->assertSame(2, $rows['Femenino']);
        $this->assertSame(1, $rows['Masculino']);
    }

    public function test_quantity_returns_zero_when_no_scheduled_in_range(): void
    {
        $response = $this->actingAs($this->admin())->post('/reports/participant-by-quantity', $this->inRangeDates());

        $response->assertOk();
        $data = $this->decodeAsCurrentContract($response);

        $this->assertArrayHasKey('amountAllTimePerCourse', $data);
        $this->assertSame(0, $data['dateRangeAmountPerCourse']);
    }

    public function test_quantity_lists_courses_with_counts_when_present(): void
    {
        $cat = $this->makeCategory('Cat Q');
        $s1 = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $s2 = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->addParticipant($s1);
        $this->addParticipant($s1);
        $this->addParticipant($s2);

        $response = $this->actingAs($this->admin())->post('/reports/participant-by-quantity', $this->inRangeDates());

        $data = $this->decodeAsCurrentContract($response);
        $this->assertIsArray($data['dateRangeAmountPerCourse']);

        $counts = array_column($data['dateRangeAmountPerCourse'], 'count', 'scheduled_id');
        $this->assertSame(2, $counts[$s1->id]);
        $this->assertSame(1, $counts[$s2->id]);

        // sorted descending by count
        $sortedCounts = array_column($data['dateRangeAmountPerCourse'], 'count');
        $this->assertSame($sortedCounts, array_reverse(array_values(array_reverse($sortedCounts))));
        $this->assertGreaterThanOrEqual(end($sortedCounts), $sortedCounts[0]);
    }

    public function test_most_scheduled_shape(): void
    {
        $cat = $this->makeCategory('Cat M');
        $modality = Modality::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $cat->id,
            'modality_id' => $modality->id,
        ]);
        Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);
        $facilitatorId = \App\Models\Facilitator::create(['person_id' => Person::factory()->create()->id])->id;

        foreach ([3, 6] as $offset) {
            Scheduled::create([
                'course_id' => $course->id,
                'facilitator_id' => $facilitatorId,
                'course_status_id' => CourseStatus::EN_CURSO,
                'start_date' => now()->subDays($offset)->toDateString(),
                'end_date' => now()->subDays($offset - 2)->toDateString(),
            ]);
        }
        $other = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());

        $response = $this->actingAs($this->admin())->post('/reports/most-scheduled');

        $data = $this->decodeAsCurrentContract($response);
        $this->assertSame(2, $data['amountData'][$course->id]);
        $codes = array_column($data['courseData'], 'code');
        $this->assertContains($course->code, $codes);
        $this->assertContains($other->course->code, $codes);
    }

    public function test_not_scheduled_lists_unscheduled_courses_only(): void
    {
        $cat = $this->makeCategory('Cat N');
        $modality = Modality::factory()->create();
        $lonely = Course::factory()->create([
            'category_id' => $cat->id,
            'modality_id' => $modality->id,
        ]);
        $scheduled = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());

        $response = $this->actingAs($this->admin())->post('/reports/not-scheduled');

        $data = $this->decodeAsCurrentContract($response);
        $ids = array_column($data['data'], 'id');
        $this->assertContains($lonely->id, $ids);
        $this->assertNotContains($scheduled->course->id, $ids);
    }

    public function test_canceled_endpoint_returns_normalized_rows_in_range(): void
    {
        $cat = $this->makeCategory('Cat C');
        $canceled = $this->makeScheduled($cat, CourseStatus::CANCELADO, now()->subDay()->toDateString());
        $active = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());

        $response = $this->actingAs($this->admin())->post('/reports/canceled');

        $response->assertOk();
        $data = $response->json();

        foreach (['dateRange', 'total', 'data'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
        $ids = array_column($data['data'], 'id');
        $this->assertContains($canceled->id, $ids);
        $this->assertNotContains($active->id, $ids);

        $row = collect($data['data'])->firstWhere('id', $canceled->id);
        $this->assertSame('Cat C', $row['category']);
        $this->assertSame(1, $data['total']);
    }

    public function test_participant_status_select_returns_statuses(): void
    {
        $response = $this->actingAs($this->admin())->get('/reports/participant-status-select');

        $response->assertOk();
        $names = array_column($response->json('statuses'), 'name');
        $this->assertContains('Aprobado', $names);
    }

    public function test_participant_average_shape_and_values(): void
    {
        $cat = $this->makeCategory('Cat Avg');
        $s1 = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->addParticipant($s1, Participant::APROBADO);
        $this->addParticipant($s1, Participant::REPROBADO);
        $this->addParticipant($s1);
        $s2 = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->addParticipant($s2, Participant::APROBADO);

        $response = $this->actingAs($this->admin())->post('/reports/participant-average', $this->inRangeDates());

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertCount(2, $data['data']);

        $row1 = collect($data['data'])->firstWhere('scheduled_id', $s1->id);
        $this->assertSame(1, $row1['approved']);
        $this->assertSame(1, $row1['failed']);
        $this->assertSame(3, $row1['total']);

        $row2 = collect($data['data'])->firstWhere('scheduled_id', $s2->id);
        $this->assertSame(1, $row2['approved']);
        $this->assertSame(0, $row2['failed']);
    }

    public function test_participant_average_returns_empty_data_when_no_runs(): void
    {
        $response = $this->actingAs($this->admin())->post('/reports/participant-average', $this->inRangeDates());

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
    }

    public function test_participants_by_status_filters_by_status_param(): void
    {
        $cat = $this->makeCategory('Cat F');
        $scheduled = $this->makeScheduled($cat, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->addParticipant($scheduled, Participant::APROBADO);
        $this->addParticipant($scheduled, Participant::REPROBADO);

        $payload = [
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->toDateString(),
            'status_id' => Participant::APROBADO,
        ];

        $filtered = $this->actingAs($this->admin())
            ->post('/reports/participant-by-status', $payload)
            ->assertOk()
            ->json();

        $this->assertCount(1, $filtered['byAllTime']);
        $this->assertSame(Participant::APROBADO, $filtered['byAllTime'][0]['participant_status_id']);

        // invalid status id is rejected
        $this->actingAs($this->admin())
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/reports/participant-by-status', array_merge($payload, ['status_id' => 999]))
            ->assertStatus(422);
    }
}
