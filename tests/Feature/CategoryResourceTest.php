<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Helpers\FormatHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_creation()
    {
        $category = Category::create([
            'name' => 'Electronics'
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'parent_id' => null
        ]);

        $this->assertTrue($category->isParent());
        $this->assertFalse($category->isSubcategory());
    }

    public function test_subcategory_creation()
    {
        $parent = Category::create(['name' => 'Electronics']);
        $child = Category::create([
            'name' => 'Smartphones',
            'parent_id' => $parent->id
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Smartphones',
            'parent_id' => $parent->id
        ]);

        $this->assertFalse($child->isParent());
        $this->assertTrue($child->isSubcategory());
        $this->assertEquals('Electronics > Smartphones', $child->full_path);
    }

    public function test_category_relationships()
    {
        $parent = Category::create(['name' => 'Electronics']);
        $child1 = Category::create([
            'name' => 'Smartphones',
            'parent_id' => $parent->id
        ]);
        $child2 = Category::create([
            'name' => 'Laptops',
            'parent_id' => $parent->id
        ]);

        // Test parent relationship
        $this->assertEquals($parent->id, $child1->parent->id);
        $this->assertEquals($parent->id, $child2->parent->id);

        // Test children relationship
        $this->assertEquals(2, $parent->children()->count());
        $this->assertTrue($parent->hasChildren());
        $this->assertFalse($child1->hasChildren());
    }

    public function test_category_scopes()
    {
        $parent = Category::create(['name' => 'Electronics']);
        $child = Category::create([
            'name' => 'Smartphones',
            'parent_id' => $parent->id
        ]);

        // Test top level scope
        $topLevel = Category::topLevel()->get();
        $this->assertEquals(1, $topLevel->count());
        $this->assertEquals('Electronics', $topLevel->first()->name);

        // Test has children scope
        $withChildren = Category::query()->hasChildren()->get();
        $this->assertEquals(1, $withChildren->count());
        $this->assertEquals('Electronics', $withChildren->first()->name);
    }

    public function test_category_with_products()
    {
        $category = Category::create(['name' => 'Electronics']);
        
        $product = Product::create([
            'name' => 'Test Product',
            'barcode' => '123456789',
            'category_id' => $category->id,
            'purchase_price_per_unit' => 100.00,
            'sell_price_per_unit' => 150.00,
            'stock' => 10,
            'is_active' => true
        ]);

        $this->assertTrue($category->hasProducts());
        $this->assertEquals(1, $category->products()->count());
        $this->assertEquals(1, $category->total_products_count);
    }

    public function test_category_deletion_validation()
    {
        $category = Category::create(['name' => 'Electronics']);
        $child = Category::create([
            'name' => 'Smartphones',
            'parent_id' => $category->id
        ]);

        // Test deletion with children
        $this->assertFalse($category->canBeDeleted());
        $this->assertNotNull($category->getDeletionErrorMessage());

        // Test deletion with products
        $product = Product::create([
            'name' => 'Test Product',
            'barcode' => '123456789',
            'category_id' => $child->id,
            'purchase_price_per_unit' => 100.00,
            'sell_price_per_unit' => 150.00,
            'stock' => 10,
            'is_active' => true
        ]);

        $this->assertFalse($child->canBeDeleted());
        $this->assertNotNull($child->getDeletionErrorMessage());
    }

    public function test_format_helper_integration()
    {
        $number = 1234.5678;
        $currency = 99.99;
        $date = now();

        $this->assertEquals('1,234.57', FormatHelper::formatNumber($number));
        $this->assertEquals('$99.99', FormatHelper::formatCurrency($currency));
        $this->assertIsString(FormatHelper::formatDateTime($date));
    }

    public function test_category_hierarchy_methods()
    {
        $grandparent = Category::create(['name' => 'Electronics']);
        $parent = Category::create([
            'name' => 'Computers',
            'parent_id' => $grandparent->id
        ]);
        $child = Category::create([
            'name' => 'Laptops',
            'parent_id' => $parent->id
        ]);

        // Test descendants
        $descendants = $grandparent->getAllDescendants();
        $this->assertEquals(2, $descendants->count());

        // Test ancestors
        $ancestors = $child->getAllAncestors();
        $this->assertEquals(2, $ancestors->count());
    }

    public function test_category_resource_attributes()
    {
        $category = Category::create(['name' => 'Electronics']);
        
        // Test that full_path is appended
        $this->assertArrayHasKey('full_path', $category->toArray());
        $this->assertEquals('Electronics', $category->full_path);
    }
}
