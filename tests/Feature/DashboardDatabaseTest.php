<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('categorie');
            $table->string('slug')->unique();
            $table->string('image_categorie')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('villes', function (Blueprint $table): void {
            $table->id();
            $table->string('ville');
            $table->timestamps();
        });

        Schema::create('communes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ville_id');
            $table->string('commune');
            $table->timestamps();
        });

        Schema::create('partenaires', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('categorie_id');
            $table->string('image_partenaire')->nullable();
            $table->string('nom_partenaire');
            $table->decimal('taux_reduction', 5, 2);
            $table->text('info_reduction')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->unsignedBigInteger('ville_id')->nullable();
            $table->unsignedBigInteger('commune_id')->nullable();
            $table->string('localisation')->nullable();
            $table->string('nom_prenom_gerant')->nullable();
            $table->string('mail_gerant')->nullable();
            $table->string('phone_gerant', 30)->nullable();
            $table->string('activite_principale')->nullable();
            $table->boolean('boutique_physique')->default(false);
            $table->timestamps();
        });
    }

    public function test_dashboard_calculates_statistics_from_the_existing_schema(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'categorie' => 'Restaurants', 'slug' => 'restaurants', 'status' => 'active'],
            ['id' => 2, 'categorie' => 'Santé', 'slug' => 'sante', 'status' => 'active'],
            ['id' => 3, 'categorie' => 'Archives', 'slug' => 'archives', 'status' => 'inactive'],
        ]);

        DB::table('partenaires')->insert([
            ['categorie_id' => 1, 'nom_partenaire' => 'Partenaire A', 'taux_reduction' => 10],
            ['categorie_id' => 1, 'nom_partenaire' => 'Partenaire B', 'taux_reduction' => 20],
            ['categorie_id' => 3, 'nom_partenaire' => 'Partenaire C', 'taux_reduction' => 40],
        ]);

        $this->actingAs($this->makeUserWithRole('Admin'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('<b>2</b><span>partenaires visibles</span>', false)
            ->assertSee('<b>2</b><span>catégories visibles</span>', false)
            ->assertSee('<b>15 %</b><span>réduction moyenne</span>', false)
            ->assertSee('<b>1</b><span>catégories sans partenaire</span>', false)
            ->assertSee('Restaurants')
            ->assertSee('Archives');
    }

    public function test_categories_page_lists_database_categories_and_partner_counts(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'categorie' => 'Restaurants', 'slug' => 'restaurants', 'status' => 'active'],
            ['id' => 2, 'categorie' => 'Santé', 'slug' => 'sante', 'status' => 'inactive'],
        ]);

        DB::table('partenaires')->insert([
            ['categorie_id' => 1, 'nom_partenaire' => 'Partenaire A', 'taux_reduction' => 10],
            ['categorie_id' => 1, 'nom_partenaire' => 'Partenaire B', 'taux_reduction' => 20],
            ['categorie_id' => 2, 'nom_partenaire' => 'Partenaire C', 'taux_reduction' => 40],
        ]);

        $this->actingAs($this->makeUserWithRole('Admin'))
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee('Restaurants')
            ->assertSee('/categories/restaurants')
            ->assertSee('Santé')
            ->assertSee('/categories/sante')
            ->assertSee('Active')
            ->assertSee('Masquée')
            ->assertSee('<td>2</td>', false)
            ->assertSee('<td>1</td>', false)
            ->assertDontSee('Nouvelle catégorie')
            ->assertDontSee('Supprimer');
    }

    public function test_category_switch_updates_the_existing_status_field(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'categorie' => 'Restaurants', 'slug' => 'restaurants', 'status' => 'active'],
        ]);

        $this->patchJson('/admin/categories/1/status', ['active' => false])
            ->assertUnauthorized();

        $this->assertSame(
            'active',
            DB::table('categories')->where('id', 1)->value('status')
        );

        $admin = $this->makeUserWithRole('Admin');

        $this->actingAs($admin)
            ->patchJson('/admin/categories/1/status', ['active' => false])
            ->assertOk()
            ->assertJson(['status' => 'inactive']);

        $this->assertSame(
            'inactive',
            DB::table('categories')->where('id', 1)->value('status')
        );

        $this->actingAs($admin)
            ->patchJson('/admin/categories/1/status', ['active' => true])
            ->assertOk()
            ->assertJson(['status' => 'active']);

        $this->assertSame(
            'active',
            DB::table('categories')->where('id', 1)->value('status')
        );
    }

    public function test_partners_page_reads_real_partner_fields_and_relations(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'categorie' => 'Restaurants', 'slug' => 'restaurants', 'status' => 'active'],
            ['id' => 2, 'categorie' => 'Archives', 'slug' => 'archives', 'status' => 'inactive'],
        ]);
        DB::table('villes')->insert([
            ['id' => 1, 'ville' => 'Abidjan'],
        ]);
        DB::table('communes')->insert([
            ['id' => 1, 'ville_id' => 1, 'commune' => 'Cocody'],
        ]);
        DB::table('partenaires')->insert([
            [
                'categorie_id' => 1,
                'nom_partenaire' => 'Chez Awa',
                'taux_reduction' => 12.5,
                'info_reduction' => 'Sur les repas',
                'telephone' => '0700000000',
                'ville_id' => 1,
                'commune_id' => 1,
                'localisation' => null,
                'nom_prenom_gerant' => 'Awa Koné',
                'phone_gerant' => '0500000000',
                'activite_principale' => 'Restaurant',
                'mail_gerant' => null,
            ],
            [
                'categorie_id' => 2,
                'nom_partenaire' => 'Garage du Nord',
                'taux_reduction' => 5,
                'info_reduction' => null,
                'telephone' => null,
                'ville_id' => null,
                'commune_id' => null,
                'localisation' => 'Yopougon',
                'nom_prenom_gerant' => 'Moussa Diabaté',
                'phone_gerant' => null,
                'activite_principale' => null,
                'mail_gerant' => null,
            ],
        ]);

        $this->actingAs($this->makeUserWithRole('Admin'))
            ->get('/admin/partners')
            ->assertOk()
            ->assertSee('Chez Awa')
            ->assertSee('Garage du Nord')
            ->assertSee('Restaurants')
            ->assertSee('Archives')
            ->assertSee('Catégorie masquée')
            ->assertSee('Sur les repas')
            ->assertSee('Cocody')
            ->assertSee('Yopougon')
            ->assertSee('Awa Koné')
            ->assertSee('0700000000')
            ->assertSee('12,5%')
            ->assertSee('category-filter')
            ->assertDontSee('visibility-filter')
            ->assertDontSee('Nouvelle catégorie')
            ->assertDontSee('Modifier le partenaire');
    }

    public function test_admin_can_create_a_partner_from_the_partners_page(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'categorie' => 'Restaurants', 'slug' => 'restaurants', 'status' => 'active'],
        ]);

        $admin = $this->makeUserWithRole('Admin');

        $this->actingAs($admin)
            ->get('/admin/partners')
            ->assertOk()
            ->assertSee('Créer un partenaire')
            ->assertSee(route('admin.partners.create'));

        $this->actingAs($admin)
            ->get(route('admin.partners.create'))
            ->assertOk()
            ->assertSee('name="nom_partenaire"', false)
            ->assertSee('Restaurants');

        $this->actingAs($admin)
            ->post(route('admin.partners.store'), [
                'categorie_id' => 1,
                'nom_partenaire' => 'Le Nouveau Partenaire',
                'taux_reduction' => 12.5,
                'info_reduction' => 'Réduction sur les repas',
                'telephone' => '0700000000',
                'localisation' => 'Cocody',
                'nom_prenom_gerant' => 'Awa Koné',
                'mail_gerant' => 'awa@example.com',
                'phone_gerant' => '0500000000',
                'activite_principale' => 'Restaurant',
                'boutique_physique' => 1,
            ])
            ->assertRedirect(route('admin.partners'))
            ->assertSessionHas('status', 'Le partenaire « Le Nouveau Partenaire » a été créé.');

        $this->assertDatabaseHas('partenaires', [
            'categorie_id' => 1,
            'nom_partenaire' => 'Le Nouveau Partenaire',
            'taux_reduction' => 12.5,
            'info_reduction' => 'Réduction sur les repas',
            'telephone' => '0700000000',
            'localisation' => 'Cocody',
            'nom_prenom_gerant' => 'Awa Koné',
            'mail_gerant' => 'awa@example.com',
            'phone_gerant' => '0500000000',
            'activite_principale' => 'Restaurant',
            'boutique_physique' => true,
        ]);
    }

    public function test_user_role_cannot_create_a_partner(): void
    {
        $user = $this->makeUserWithRole('User');

        $this->actingAs($user)
            ->get(route('admin.partners.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.partners.store'), [])
            ->assertForbidden();
    }

    public function test_partners_page_paginates_and_preserves_search_and_category_filters(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'categorie' => 'Restaurants', 'slug' => 'restaurants', 'status' => 'active'],
            ['id' => 2, 'categorie' => 'Archives', 'slug' => 'archives', 'status' => 'inactive'],
        ]);

        for ($number = 1; $number <= 17; $number++) {
            DB::table('partenaires')->insert([
                'categorie_id' => 1,
                'nom_partenaire' => sprintf('Promo partenaire %02d', $number),
                'taux_reduction' => 10,
            ]);
        }

        DB::table('partenaires')->insert([
            'categorie_id' => 2,
            'nom_partenaire' => 'Promo archive',
            'taux_reduction' => 5,
        ]);

        $this->actingAs($this->makeUserWithRole('Admin'))
            ->get('/admin/partners?q=Promo&category=1')
            ->assertOk()
            ->assertSee('Affichage de 1 à 15 sur 17 partenaires')
            ->assertSee('Promo partenaire 01')
            ->assertDontSee('Promo archive')
            ->assertSee('q=Promo')
            ->assertSee('category=1')
            ->assertSee('page=2');

        $this->actingAs($this->makeUserWithRole('Admin'))
            ->get('/admin/partners?q=Promo&category=1&page=2')
            ->assertOk()
            ->assertSee('Affichage de 16 à 17 sur 17 partenaires')
            ->assertSee('Promo partenaire 16')
            ->assertSee('Promo partenaire 17')
            ->assertDontSee('Promo archive')
            ->assertSee('name="q"', false)
            ->assertSee('value="Promo"', false);

        $this->actingAs($this->makeUserWithRole('Admin'))
            ->get('/admin/partners?q=Promo&category=1&page=3')
            ->assertRedirect(route('admin.partners', ['q' => 'Promo', 'category' => 1, 'page' => 2]));
    }

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
