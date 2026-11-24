<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_master_can_manage_users_and_roles(): void
    {
        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertForbidden();
        $this->actingAs($admin)
            ->get(route('admin.roles'))
            ->assertForbidden();

        $master = $this->userWithRole('Master');

        $this->actingAs($master)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Utilisateurs et rôles')
            ->assertSee('Créer un utilisateur');
        $this->actingAs($master)
            ->get(route('admin.roles'))
            ->assertOk()
            ->assertSee('Admin')
            ->assertSee('Master')
            ->assertSee('Créer un rôle');
    }

    public function test_user_role_cannot_access_administration_pages(): void
    {
        $user = $this->userWithRole('User');

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.categories'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.partners'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.partners.create'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.roles'))->assertForbidden();
    }

    public function test_master_can_create_edit_and_assign_user_roles(): void
    {
        $master = $this->userWithRole('Master');
        $adminRole = Role::findByName('Admin', 'web');
        $userRole = Role::findByName('User', 'web');

        $this->actingAs($master)
            ->post(route('admin.users.store'), [
                'name' => 'Nouveau compte',
                'email' => 'new-user@example.test',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'roles' => [$userRole->id],
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('status', 'Le compte « Nouveau compte » a été créé.');

        $user = User::where('email', 'new-user@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertSame(['User'], $user->getRoleNames()->all());

        $this->actingAs($master)
            ->put(route('admin.users.update', $user), [
                'name' => 'Compte mis à jour',
                'email' => 'updated-user@example.test',
                'roles' => [$adminRole->id, $userRole->id],
            ])
            ->assertRedirect(route('admin.users'));

        $user->refresh();
        $this->assertSame('Compte mis à jour', $user->name);
        $this->assertSame('updated-user@example.test', $user->email);
        $this->assertSame(['Admin', 'User'], $user->getRoleNames()->sort()->values()->all());
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_master_can_manage_custom_roles_but_system_roles_are_protected(): void
    {
        $master = $this->userWithRole('Master');

        $this->actingAs($master)
            ->post(route('admin.roles.store'), ['name' => ' Content Editor '])
            ->assertRedirect(route('admin.roles'))
            ->assertSessionHas('status', 'Le rôle a été créé.');

        $role = Role::findByName('Content Editor', 'web');
        $this->actingAs($master)
            ->put(route('admin.roles.update', $role), ['name' => 'Catalog Editor'])
            ->assertRedirect(route('admin.roles'))
            ->assertSessionHas('status', 'Le rôle a été renommé.');
        $role->refresh();
        $this->assertSame('Catalog Editor', $role->name);

        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($master)
            ->delete(route('admin.roles.destroy', $role))
            ->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);

        $user->removeRole($role);
        $this->actingAs($master)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles'))
            ->assertSessionHas('status', 'Le rôle a été supprimé.');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);

        $systemRole = Role::findByName('Admin', 'web');
        $this->actingAs($master)
            ->put(route('admin.roles.update', $systemRole), ['name' => 'Renamed Admin'])
            ->assertSessionHasErrors('name');
        $this->actingAs($master)
            ->delete(route('admin.roles.destroy', $systemRole))
            ->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id, 'name' => 'Admin']);
    }

    public function test_master_cannot_remove_or_disable_the_last_active_master(): void
    {
        $master = $this->userWithRole('Master');
        $userRole = Role::findByName('User', 'web');

        $this->actingAs($master)
            ->patch(route('admin.users.status', $master), ['active' => false])
            ->assertSessionHasErrors('active');

        $this->actingAs($master)
            ->put(route('admin.users.update', $master), [
                'name' => $master->name,
                'email' => $master->email,
                'roles' => [$userRole->id],
            ])
            ->assertSessionHasErrors('roles');

        $this->assertTrue($master->fresh()->is_active);
        $this->assertTrue($master->fresh()->hasRole('Master'));
    }

    private function userWithRole(string $roleName): User
    {
        foreach (['Admin', 'User', 'Master'] as $systemRole) {
            Role::findOrCreate($systemRole, 'web');
        }
        $role = Role::findByName($roleName, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
