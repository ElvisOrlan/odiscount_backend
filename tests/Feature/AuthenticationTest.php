<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_issues_a_sanctum_token_and_logout_revokes_it(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.test',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'device_name' => 'test-device',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('token_type', 'Bearer');

        $token = $response->json('token');

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_inactive_accounts_cannot_authenticate_or_use_existing_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'disabled@example.test',
            'password' => 'secret-password',
        ]);
        $token = $user->createToken('test-device')->plainTextToken;

        $user->is_active = false;
        $user->save();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertUnprocessable();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertForbidden();
    }

    public function test_admin_or_master_can_deactivate_a_user_and_revoke_their_tokens(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $target = User::factory()->create();
        $targetToken = $target->createToken('target-device')->plainTextToken;
        $adminToken = $admin->createToken('admin-device')->plainTextToken;

        $this->withToken($adminToken)
            ->patchJson('/api/users/'.$target->id.'/status', ['active' => false])
            ->assertOk()
            ->assertJson([
                'id' => $target->id,
                'is_active' => false,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $target->id,
            'tokenable_type' => User::class,
        ]);

        Auth::forgetGuards();
        $this->withToken($targetToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_user_role_cannot_deactivate_another_user(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('User');
        $token = $user->createToken('user-device')->plainTextToken;
        $target = User::factory()->create();

        $this->withToken($token)
            ->patchJson('/api/users/'.$target->id.'/status', ['active' => false])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => true,
        ]);
    }

    public function test_role_seeder_creates_the_three_requested_roles_without_permissions(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'Admin', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'User', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'Master', 'guard_name' => 'web']);
        $this->assertDatabaseCount('permissions', 0);

        foreach ([
            'admin@example.com' => 'Admin',
            'user@example.com' => 'User',
            'master@example.com' => 'Master',
        ] as $email => $roleName) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertSame($roleName, $user->getRoleNames()->sole());
            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertTrue($user->isActive());
        }
    }

    public function test_role_seeder_is_idempotent_and_syncs_test_account_roles(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $admin->assignRole('User');

        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('users', 3);
        $this->assertSame(['Admin'], User::query()
            ->where('email', 'admin@example.com')
            ->firstOrFail()
            ->getRoleNames()
            ->all());
    }
}
