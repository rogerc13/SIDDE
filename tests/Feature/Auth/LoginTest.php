<?php

namespace Tests\Feature\Auth;

use App\Models\{User, Role, Person};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
    }

    protected function createUser(array $overrides = []): User
    {
        $person = Person::factory()->create();
        return User::create(array_merge([
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'role_id' => Role::ADMINISTRADOR,
            'person_id' => $person->id,
        ], $overrides));
    }

    public function test_login_page_is_displayed(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_already_authenticated_user_redirects_to_home(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect(route('home'));
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createUser(['password' => bcrypt('secret123')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = $this->createUser(['password' => bcrypt('secret123')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_cannot_login_with_missing_email(): void
    {
        $response = $this->post('/login', [
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_cannot_login_with_missing_password(): void
    {
        $user = $this->createUser();

        $response = $this->post('/login', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_is_logged_out_after_logout(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->post('/logout');
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_invalidates_session(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $token = session()->token();
        $this->post('/logout');
        $this->assertNotEquals($token, session()->token());
    }

    public function test_guest_is_redirected_to_login_when_accessing_protected_route(): void
    {
        $response = $this->get('/home');
        $response->assertRedirect(route('login'));
    }

    public function test_user_is_redirected_to_home_after_login(): void
    {
        $user = $this->createUser(['password' => bcrypt('secret123')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
    }
}
