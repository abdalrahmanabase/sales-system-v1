<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Provider;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\CompanyName;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BonusItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_bonus_item_stores_purchase_price_but_has_zero_cost()
    {
        // Create necessary models
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $provider = Provider::factory()->create([
            'company_name_id' => $company->id,
            'name' => 'Test Provider'
        ]);
        
        $branch = Branch::factory()->create(['name' => 'Test Branch']);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'purchase_price_per_unit' => 10.00,
            'sell_price_per_unit' => 15.00,
            'provider_id' => $provider->id,
            'stock' => 0
        ]);

        // Create purchase invoice with bonus item
        $invoice = PurchaseInvoice::create([
            'provider_id' => $provider->id,
            'branch_id' => $branch->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now(),
            'total_amount' => 0, // Will be calculated
            'notes' => 'Test invoice'
        ]);

        // Create bonus item
        $bonusItem = PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'purchase_price' => 10.00, // Should store actual purchase price
            'sell_price' => 15.00,
            'is_bonus' => true
        ]);

        // Refresh models
        $bonusItem->refresh();
        $product->refresh();

        // Assertions
        $this->assertEquals(10.00, $bonusItem->purchase_price, 'Purchase price should be stored correctly');
        $this->assertEquals(0, $bonusItem->actual_cost, 'Actual cost should be zero for bonus items');
        $this->assertEquals(0, $bonusItem->total_cost, 'Total cost should be zero for bonus items');
        $this->assertEquals(50.00, $bonusItem->total_purchase_value, 'Total purchase value should be calculated correctly');
        $this->assertTrue($bonusItem->is_bonus, 'Item should be marked as bonus');
        
        // Check that stock was incremented (bonus items also add to stock)
        $this->assertEquals(5, $product->stock, 'Stock should be incremented for bonus items');
    }

    public function test_regular_item_has_normal_cost()
    {
        // Create necessary models
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $provider = Provider::factory()->create([
            'company_name_id' => $company->id,
            'name' => 'Test Provider'
        ]);
        
        $branch = Branch::factory()->create(['name' => 'Test Branch']);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'purchase_price_per_unit' => 10.00,
            'sell_price_per_unit' => 15.00,
            'provider_id' => $provider->id,
            'stock' => 0
        ]);

        // Create purchase invoice with regular item
        $invoice = PurchaseInvoice::create([
            'provider_id' => $provider->id,
            'branch_id' => $branch->id,
            'invoice_number' => 'INV-002',
            'invoice_date' => now(),
            'total_amount' => 0,
            'notes' => 'Test invoice'
        ]);

        // Create regular item
        $regularItem = PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'purchase_price' => 10.00,
            'sell_price' => 15.00,
            'is_bonus' => false
        ]);

        // Refresh models
        $regularItem->refresh();
        $product->refresh();

        // Assertions
        $this->assertEquals(10.00, $regularItem->purchase_price, 'Purchase price should be stored correctly');
        $this->assertEquals(10.00, $regularItem->actual_cost, 'Actual cost should equal purchase price for regular items');
        $this->assertEquals(30.00, $regularItem->total_cost, 'Total cost should be calculated correctly');
        $this->assertEquals(30.00, $regularItem->total_purchase_value, 'Total purchase value should equal total cost for regular items');
        $this->assertFalse($regularItem->is_bonus, 'Item should not be marked as bonus');
        
        // Check that stock was incremented
        $this->assertEquals(3, $product->stock, 'Stock should be incremented for regular items');
    }
} 