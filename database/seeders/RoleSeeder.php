<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $accounts = [
            ['name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'Admin'],
            ['name' => 'User', 'email' => 'user@example.com', 'role' => 'User'],
            ['name' => 'Master', 'email' => 'master@example.com', 'role' => 'Master'],
        ];

        foreach ($accounts as $account) {
            $role = Role::findOrCreate($account['role'], 'web');
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$role]);
        }
    }
}
