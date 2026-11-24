<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

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
