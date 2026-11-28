<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_are_available_with_separate_navigation(): void
    {
        $this->actingAs($this->makeUserWithRole('Admin'))
            ->get('/office')
            ->assertOk()
            ->assertSee(asset('images/icone.png'))
            ->assertSee('Tableau de bord')
            ->assertSee('Partenaires par catégorie');
    }

    public function test_admin_pages_require_an_authenticated_session(): void
    {
        $this->get('/office')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_log_in_with_configured_credentials(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee(asset('images/icone.png'));

        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'correct-password',
        ]);

        $this->post('/office/login', [
            'email' => 'admin@example.test',
            'password' => 'correct-password',
        ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_disabled_user_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'disabled@example.test',
            'password' => 'correct-password',
            'is_active' => false,
        ]);

        $this->post('/office/login', [
            'email' => 'disabled@example.test',
            'password' => 'correct-password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_disabled_user_is_logged_out_of_admin_pages(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get('/office')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_old_admin_path_is_no_longer_registered(): void
    {
        $this->get('/admin')->assertNotFound();
    }

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
