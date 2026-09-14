<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sales_report(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'report-admin@example.com', 'password' => 'password', 'role' => 'admin']);
        Order::create([
            'user_id' => $admin->id, 'order_number' => 'REPORT-1', 'status' => 'completed',
            'subtotal' => 100000, 'shipping_cost' => 10000, 'discount' => 0, 'total' => 110000,
            'payment_method' => 'cod', 'payment_status' => 'paid', 'recipient_name' => 'Test',
            'phone' => '081234567890', 'address_line' => 'Test', 'city' => 'Depok', 'state' => 'Jawa Barat', 'postal_code' => '17531',
        ]);

        $this->actingAs($admin)->get(route('admin.reports.index'))
            ->assertOk()->assertSee('Reports &amp; Analytics', false)->assertSee('110.000');
    }

    public function test_customer_cannot_access_sales_report(): void
    {
        $customer = User::create(['name' => 'Customer', 'email' => 'report-customer@example.com', 'password' => 'password', 'role' => 'customer']);
        $this->actingAs($customer)->get(route('admin.reports.index'))->assertForbidden();
    }
}
