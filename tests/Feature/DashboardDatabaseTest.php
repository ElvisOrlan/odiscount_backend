<?php

namespace Tests\Feature;

use App\Models\Partenaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardDatabaseTest extends TestCase
{
    use RefreshDatabase;

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
            ->get('/office')
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
            ->get('/office/categories')
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

        $this->patchJson('/office/categories/1/status', ['active' => false])
            ->assertUnauthorized();

        $this->assertSame(
            'active',
            DB::table('categories')->where('id', 1)->value('status')
        );

        $admin = $this->makeUserWithRole('Admin');

        $this->actingAs($admin)
            ->patchJson('/office/categories/1/status', ['active' => false])
            ->assertOk()
            ->assertJson(['status' => 'inactive']);

        $this->assertSame(
            'inactive',
            DB::table('categories')->where('id', 1)->value('status')
        );

        $this->actingAs($admin)
            ->patchJson('/office/categories/1/status', ['active' => true])
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
            ->get('/office/partners')
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
            ->assertSee('Modifier')
            ->assertSee('Désactiver')
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
            ->get('/office/partners')
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

    public function test_admin_can_edit_a_partner_and_keep_its_existing_image(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'categorie' => 'Restaurants', 'slug' => 'restaurants', 'status' => 'active'],
            ['id' => 2, 'categorie' => 'Santé', 'slug' => 'sante', 'status' => 'active'],
        ]);
        DB::table('villes')->insert([
            ['id' => 1, 'ville' => 'Abidjan'],
        ]);
        DB::table('communes')->insert([
            ['id' => 1, 'ville_id' => 1, 'commune' => 'Cocody'],
        ]);
        $partner = Partenaire::create([
            'categorie_id' => 1,
            'nom_partenaire' => 'Ancien nom',
            'taux_reduction' => 10,
            'image_partenaire' => 'image-existante.jpg',
            'is_active' => true,
        ]);
        $admin = $this->makeUserWithRole('Admin');

        $this->actingAs($admin)
            ->get(route('admin.partners.edit', $partner))
            ->assertOk()
            ->assertSee('value="Ancien nom"', false)
            ->assertSee('src="'.asset('images/partenaires/image-existante.jpg').'"', false)
            ->assertSee('id="new-partner-image-preview"', false)
            ->assertSee('Nouvelle image sélectionnée')
            ->assertSee('image-existante.jpg');

        $this->actingAs($admin)
            ->put(route('admin.partners.update', $partner), [
                'categorie_id' => 2,
                'nom_partenaire' => 'Nouveau nom',
                'taux_reduction' => 15,
                'ville_id' => 1,
                'commune_id' => 1,
                'localisation' => 'Cocody centre',
            ])
            ->assertRedirect(route('admin.partners'))
            ->assertSessionHas('status', 'Le partenaire « Nouveau nom » a été modifié.');

        $this->assertDatabaseHas('partenaires', [
            'id' => $partner->id,
            'categorie_id' => 2,
            'nom_partenaire' => 'Nouveau nom',
            'taux_reduction' => 15,
            'image_partenaire' => 'image-existante.jpg',
            'ville_id' => 1,
            'commune_id' => 1,
            'localisation' => 'Cocody centre',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_deactivate_and_reactivate_a_partner(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'categorie' => 'Restaurants', 'slug' => 'restaurants', 'status' => 'active'],
        ]);
        $partner = Partenaire::create([
            'categorie_id' => 1,
            'nom_partenaire' => 'Partenaire contrôlé',
            'taux_reduction' => 10,
            'is_active' => true,
        ]);
        $admin = $this->makeUserWithRole('Admin');

        $this->actingAs($admin)
            ->get('/office/partners')
            ->assertOk()
            ->assertSee('Actif')
            ->assertSee(route('admin.partners.status', $partner));

        $this->actingAs($admin)
            ->from(route('admin.partners'))
            ->patch(route('admin.partners.status', $partner), ['active' => false])
            ->assertRedirect(route('admin.partners'))
            ->assertSessionHas('status', 'Le partenaire « Partenaire contrôlé » a été désactivé.');

        $this->assertDatabaseHas('partenaires', [
            'id' => $partner->id,
            'is_active' => false,
        ]);
        $this->get(route('front.categories.show', 'restaurants'))
            ->assertOk()
            ->assertDontSee('Partenaire contrôlé');
        $this->get('/office/partners')
            ->assertOk()
            ->assertSee('Désactivé')
            ->assertSee('Réactiver');

        $this->actingAs($admin)
            ->from(route('admin.partners'))
            ->patch(route('admin.partners.status', $partner), ['active' => true])
            ->assertRedirect(route('admin.partners'))
            ->assertSessionHas('status', 'Le partenaire « Partenaire contrôlé » a été réactivé.');

        $this->assertDatabaseHas('partenaires', [
            'id' => $partner->id,
            'is_active' => true,
        ]);
        $this->get(route('front.categories.show', 'restaurants'))
            ->assertOk()
            ->assertSee('Partenaire contrôlé');
    }

    public function test_admin_can_upload_a_partner_image_to_the_shared_public_directory(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'categorie' => 'Restaurants', 'slug' => 'restaurants', 'status' => 'active'],
        ]);
        $admin = $this->makeUserWithRole('Admin');
        $storedImageName = null;

        try {
            $this->actingAs($admin)
                ->get(route('admin.partners.create'))
                ->assertOk()
                ->assertSee('enctype="multipart/form-data"', false)
                ->assertSee('name="image_partenaire"', false);

            $this->actingAs($admin)
                ->post(route('admin.partners.store'), [
                    'categorie_id' => 1,
                    'nom_partenaire' => 'Partenaire avec image',
                    'taux_reduction' => 10,
                    'image_partenaire' => UploadedFile::fake()->image('logo.png'),
                ])
                ->assertRedirect(route('admin.partners'));

            $storedImageName = DB::table('partenaires')
                ->where('nom_partenaire', 'Partenaire avec image')
                ->value('image_partenaire');

            $this->assertNotEmpty($storedImageName);
            $this->assertSame(basename($storedImageName), $storedImageName);
            $this->assertFileExists(public_path('images/partenaires/'.$storedImageName));

            $partner = Partenaire::where('nom_partenaire', 'Partenaire avec image')->firstOrFail();
            $this->assertSame(asset('images/partenaires/'.$storedImageName), $partner->image_url);
            $this->get(route('front.categories.show', 'restaurants'))
                ->assertOk()
                ->assertSee($partner->image_url);
        } finally {
            if ($storedImageName !== null) {
                $imagePath = public_path('images/partenaires/'.$storedImageName);
                if (is_file($imagePath)) {
                    unlink($imagePath);
                }
            }
        }
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
            ->get('/office/partners?q=Promo&category=1')
            ->assertOk()
            ->assertSee('Affichage de 1 à 15 sur 17 partenaires')
            ->assertSee('Promo partenaire 01')
            ->assertDontSee('Promo archive')
            ->assertSee('q=Promo')
            ->assertSee('category=1')
            ->assertSee('page=2');

        $this->actingAs($this->makeUserWithRole('Admin'))
            ->get('/office/partners?q=Promo&category=1&page=2')
            ->assertOk()
            ->assertSee('Affichage de 16 à 17 sur 17 partenaires')
            ->assertSee('Promo partenaire 16')
            ->assertSee('Promo partenaire 17')
            ->assertDontSee('Promo archive')
            ->assertSee('name="q"', false)
            ->assertSee('value="Promo"', false);

        $this->actingAs($this->makeUserWithRole('Admin'))
            ->get('/office/partners?q=Promo&category=1&page=3')
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
