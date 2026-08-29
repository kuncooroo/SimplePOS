<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\CategoryForm;
use App\Livewire\Catalog\CategoryIndex;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_table_matches_catalog_schema(): void
    {
        $this->assertTrue(Schema::hasColumns('categories', [
            'id',
            'name',
            'active',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_owner_can_create_a_category(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(CategoryForm::class)
            ->set('name', 'Minuman')
            ->set('active', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Minuman',
            'active' => 1,
        ]);
    }

    public function test_administrator_can_edit_a_category(): void
    {
        $admin = User::factory()->administrator()->create();
        $category = Category::factory()->create([
            'name' => 'Makanan',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoryForm::class, ['category' => $category])
            ->set('name', 'Makanan Ringan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Makanan Ringan', $category->fresh()->name);
    }

    public function test_name_is_required(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(CategoryForm::class)
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_search_finds_categories_by_name_and_shows_empty_state(): void
    {
        $owner = User::factory()->owner()->create();
        Category::factory()->create(['name' => 'Sembako']);
        Category::factory()->create(['name' => 'ATK']);

        Livewire::actingAs($owner)
            ->test(CategoryIndex::class)
            ->set('search', 'Sem')
            ->assertSee('Sembako', false)
            ->assertDontSee('ATK', false);

        Livewire::actingAs($owner)
            ->test(CategoryIndex::class)
            ->set('search', 'zzzz-no-category')
            ->assertSee('No matching categories', false);
    }

    public function test_status_filter_shows_inactive_categories(): void
    {
        $owner = User::factory()->owner()->create();
        Category::factory()->create(['name' => 'Active Group']);
        Category::factory()->inactive()->create(['name' => 'Archived Group']);

        Livewire::actingAs($owner)
            ->test(CategoryIndex::class)
            ->set('status', 'inactive')
            ->assertSee('Archived Group', false)
            ->assertDontSee('Active Group', false);
    }

    public function test_category_can_be_deactivated_and_reactivated_without_deleting(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create(['name' => 'Minuman']);

        Livewire::actingAs($owner)
            ->test(CategoryIndex::class)
            ->call('confirmDeactivate', $category->id)
            ->call('deactivate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Minuman',
            'active' => 0,
        ]);

        Livewire::actingAs($owner)
            ->test(CategoryIndex::class)
            ->call('activate', $category->id)
            ->assertHasNoErrors();

        $this->assertTrue($category->fresh()->active);
    }

    public function test_cashier_cannot_access_category_management(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();

        $this->actingAs($cashier)->get(route('categories.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('categories.create'))->assertForbidden();
        $this->actingAs($cashier)->get(route('categories.edit', $category))->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(CategoryIndex::class)
            ->assertForbidden();

        $this->assertFalse($cashier->can('create', Category::class));
        $this->assertFalse($cashier->can('update', $category));
        $this->assertFalse($cashier->can('delete', $category));
    }

    public function test_nobody_can_delete_a_category(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();

        $this->assertFalse($owner->can('delete', $category));
        $this->assertFalse($owner->can('forceDelete', $category));
    }

    public function test_category_names_are_escaped_in_the_list(): void
    {
        $owner = User::factory()->owner()->create();
        Category::factory()->create([
            'name' => '<script>alert("xss")</script>',
        ]);

        $this->actingAs($owner)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
    }
}
