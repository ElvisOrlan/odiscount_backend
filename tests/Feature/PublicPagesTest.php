<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Partenaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_displays_active_categories_with_their_images(): void
    {
        Category::create([
            'categorie' => 'Restaurants',
            'slug' => 'restaurants',
            'image_categorie' => 'restos.jpg',
            'status' => 'active',
        ]);
        Category::create([
            'categorie' => 'Archives',
            'slug' => 'archives',
            'status' => 'inactive',
        ]);

        $this->get(route('front.home'))
            ->assertOk()
            ->assertSee('Restaurants')
            ->assertSee(asset('images/categories/restos.jpg'))
            ->assertSee(route('front.categories.show', 'restaurants'))
            ->assertDontSee('Archives');
    }

    public function test_category_page_displays_partner_details_and_shared_image_url(): void
    {
        $category = Category::create([
            'categorie' => 'Restaurants',
            'slug' => 'restaurants',
            'status' => 'active',
        ]);
        Partenaire::create([
            'categorie_id' => $category->id,
            'image_partenaire' => 'chez-awa.jpg',
            'nom_partenaire' => 'Chez Awa',
            'taux_reduction' => 12.5,
            'info_reduction' => 'Sur les repas',
            'localisation' => 'Cocody',
        ]);

        $this->get(route('front.categories.show', 'restaurants'))
            ->assertOk()
            ->assertSee('Chez Awa')
            ->assertSee('12,5%')
            ->assertSee('Sur les repas')
            ->assertSee('Cocody')
            ->assertSee(asset('images/partenaires/chez-awa.jpg'));
    }

    public function test_partner_created_in_admin_is_visible_on_public_category_page(): void
    {
        Category::create([
            'categorie' => 'Restaurants',
            'slug' => 'restaurants',
            'status' => 'active',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));

        $this->actingAs($admin)
            ->post(route('admin.partners.store'), [
                'categorie_id' => 1,
                'nom_partenaire' => 'Le Nouveau Partenaire',
                'taux_reduction' => 15,
                'info_reduction' => 'Offre nouvelle',
                'localisation' => 'Cocody',
            ])
            ->assertRedirect(route('admin.partners'));

        $this->get(route('front.categories.show', 'restaurants'))
            ->assertOk()
            ->assertSee('Le Nouveau Partenaire')
            ->assertSee('Offre nouvelle')
            ->assertSee('Cocody');
    }
}
