import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import { loginAs } from './helpers/auth';

type Fixtures = {
    courseId: number;
    capacityId: number;
    scheduledId: number;
    participantIds: number[];
    personIds: number[];
    secondFacilitadorEmail: string;
};

let fixtures: Fixtures;

const SECOND_FACILITADOR_EMAIL = `e2e-facilitador-${Date.now()}@pdvsa.com`;

function tinker(code: string): string {
    return execSync(`php artisan tinker --execute '${code}'`, {
        cwd: process.cwd(),
        encoding: 'utf8',
        timeout: 20000,
    });
}

function tinkerJson<T>(code: string): T {
    const out = tinker(code);
    const m = out.match(/E2EJSON:(\{[\s\S]*\})/);
    if (!m) throw new Error(`Fixture setup failed. Output was:\n${out}`);
    return JSON.parse(m[1]) as T;
}

test.describe.serial('Participant evaluation', () => {
    test.beforeAll(async () => {
        fixtures = tinkerJson<Fixtures>([
            '$fu = App\\Models\\User::where("email", "facilitador@pdvsa.com")->first();',
            '$facilitator = App\\Models\\Facilitator::firstOrCreate(["person_id" => $fu->person_id]);',
            '$statusEnCurso = DB::table("course_status")->where("name", "En Curso")->value("id");',
            '$category = App\\Models\\Category::factory()->create();',
            '$modality = App\\Models\\Modality::factory()->create();',
            '$course = App\\Models\\Course::create(["code" => "E2EEV-" . time(), "title" => "Curso Evaluacion E2E", "objective" => "E2E", "addressed" => "E2E", "duration" => 8, "category_id" => $category->id, "modality_id" => $modality->id]);',
            '$capacity = App\\Models\\Capacity::create(["min" => 1, "max" => 30, "course_id" => $course->id]);',
            '$scheduled = App\\Models\\Scheduled::create(["course_id" => $course->id, "facilitator_id" => $facilitator->id, "course_status_id" => $statusEnCurso, "start_date" => now()->subDay()->toDateString(), "end_date" => now()->addDays(5)->toDateString()]);',
            '$personA = Person::factory()->create();',
            '$personB = Person::factory()->create();',
            '$pa = App\\Models\\Participant::create(["person_id" => $personA->id, "scheduled_id" => $scheduled->id, "participant_status_id" => App\\Models\\Participant::ENCURSO]);',
            '$pb = App\\Models\\Participant::create(["person_id" => $personB->id, "scheduled_id" => $scheduled->id, "participant_status_id" => App\\Models\\Participant::ENCURSO]);',
            '$sp = Person::factory()->create();',
            '$su = new App\\Models\\User(["role_id" => App\\Models\\Role::FACILITADOR, "email" => "' + SECOND_FACILITADOR_EMAIL + '", "password" => bcrypt("password")]);',
            '$sp->user()->save($su);',
            'App\\Models\\Facilitator::create(["person_id" => $sp->id]);',
            'echo "E2EJSON:" . json_encode(["courseId" => $course->id, "capacityId" => $capacity->id, "scheduledId" => $scheduled->id, "participantIds" => [$pa->id, $pb->id], "personIds" => [$personA->id, $personB->id, $sp->id]]);',
        ].join(' '));
    });

    test.afterAll(async () => {
        if (!fixtures) return;
        const ids = Buffer.from(JSON.stringify(fixtures)).toString('base64');
        try {
            tinker([
                '$f = json_decode(base64_decode("' + ids + '"), true);',
                'App\\Models\\Participant::withTrashed()->whereIn("id", $f["participantIds"])->forceDelete();',
                'App\\Models\\Person::withTrashed()->whereIn("id", $f["personIds"])->each(function ($p) { $p->forceDelete(); });',
                'App\\Models\\Scheduled::withTrashed()->where("id", $f["scheduledId"])->forceDelete();',
                'DB::table("capacities")->where("id", $f["capacityId"])->delete();',
                'App\\Models\\Course::withTrashed()->where("id", $f["courseId"])->forceDelete();',
                '$du = App\\Models\\User::where("email", "' + SECOND_FACILITADOR_EMAIL + '")->first();',
                'if ($du) { App\\Models\\Facilitator::where("person_id", $du->person_id)->forceDelete(); $du->forceDelete(); }',
            ].join(' '));
        } catch (error) {
            console.log('[teardown] participant-evaluation fixtures:', error);
        }
    });

    function participantsUrl(): string {
        return `/u/af_programadas/${fixtures.scheduledId}/participantes`;
    }

    async function manualLogin(page: import('@playwright/test').Page, email: string): Promise<void> {
        await page.goto('/login');
        await page.fill('#email', email);
        await page.fill('#password', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/home');
    }

    test('owner facilitador begins evaluation, saves, and change persists', async ({ page }) => {
        await loginAs(page, 'facilitador');
        await page.goto(participantsUrl());

        const participantId = fixtures.participantIds[0];
        const firstSelect = page.locator(`.participant_status[data-participant-id="${participantId}"]`);
        const firstBadge = page.locator(`tr:has(.participant_status[data-participant-id="${participantId}"]) .participant_status_helper`);
        const evaluateButton = page.locator('.evaluate-button');

        await expect(evaluateButton).toBeVisible();
        await expect(evaluateButton).toContainText('Evaluar Participantes');
        await expect(firstSelect).toBeHidden();
        await expect(firstSelect).toBeDisabled();
        await expect(firstBadge).toBeVisible();

        await evaluateButton.click();
        await expect(evaluateButton).toContainText('Finalizar Evaluación');
        await expect(firstSelect).toBeVisible();
        await expect(firstSelect).toBeEnabled();

        await firstSelect.selectOption({ label: 'Aprobado' });

        await expect(page.locator('#evaluation-feedback')).toContainText('guardada');

        await evaluateButton.click();
        await expect(firstSelect).toBeHidden();
        await expect(firstBadge).toHaveText('Aprobado');

        await page.reload();
        await expect(firstSelect).toHaveValue(String(3));
        await expect(firstBadge).toHaveText('Aprobado');
    });

    test('non-owner facilitador sees read-only badges', async ({ page }) => {
        await manualLogin(page, SECOND_FACILITADOR_EMAIL);
        await page.goto(participantsUrl());

        await expect(page.locator('.evaluate-button')).toHaveCount(0);

        const participantId = fixtures.participantIds[0];
        const firstSelect = page.locator(`.participant_status[data-participant-id="${participantId}"]`);
        await expect(firstSelect).toBeHidden();
        await expect(firstSelect).toBeDisabled();
        await expect(page.locator(`tr:has(.participant_status[data-participant-id="${participantId}"]) .participant_status_helper`)).toBeVisible();
    });

    test('admin can begin evaluation', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto(participantsUrl());

        const firstSelect = page.locator('.participant_status').first();
        const evaluateButton = page.locator('.evaluate-button');

        await expect(evaluateButton).toBeVisible();
        await expect(evaluateButton).not.toHaveClass(/is-blocked/);
        await expect(firstSelect).toBeHidden();

        await evaluateButton.click();
        await expect(firstSelect).toBeVisible();
        await expect(firstSelect).toBeEnabled();

        await expect(page.locator('.participant_status')).toHaveCount(2);
    });
});
