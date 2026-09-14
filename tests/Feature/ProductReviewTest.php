<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function completedOrderWithItem(User $user, Product $product, string $number): OrderItem
    {
        $order = Order::create([
            'user_id' => $user->id, 'order_number' => $number, 'status' => 'completed',
            'subtotal' => 100000, 'shipping_cost' => 0, 'discount' => 0, 'total' => 100000,
            'payment_method' => 'cod', 'payment_status' => 'paid', 'recipient_name' => 'Test',
            'phone' => '081234567890', 'address_line' => 'Test', 'city' => 'Depok', 'state' => 'Jawa Barat', 'postal_code' => '17531',
        ]);

        return OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'sku' => $product->sku, 'price' => 100000, 'quantity' => 1, 'line_total' => 100000,
        ]);
    }

    public function test_customer_can_review_product_after_completed_purchase(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);
        $item = $this->completedOrderWithItem($user, $product, 'REV-1');

        $this->actingAs($user)->post(route('products.reviews.store', $product), [
            'rating' => 5, 'title' => 'Excellent', 'body' => 'Very good product.',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $user->id, 'product_id' => $product->id, 'order_item_id' => $item->id,
            'rating' => 5, 'status' => 'pending', 'is_verified_purchase' => 1,
        ]);
    }

    public function test_customer_cannot_review_without_completed_purchase(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('products.reviews.store', $product), [
            'rating' => 5,
        ])->assertRedirect();

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_admin_can_approve_review(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $item = $this->completedOrderWithItem($user, $product, 'REV-2');
        $review = ProductReview::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $item->order_id,
            'order_item_id' => $item->id, 'rating' => 4, 'body' => 'Good', 'status' => 'pending',
            'is_verified_purchase' => true,
        ]);

        $this->actingAs($admin)->patch(route('admin.reviews.update', $review), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('product_reviews', ['id' => $review->id, 'status' => 'approved']);
    }

    public function test_customer_cannot_moderate_reviews(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $item = $this->completedOrderWithItem($user, $product, 'REV-3');
        $review = ProductReview::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $item->order_id,
            'order_item_id' => $item->id, 'rating' => 5, 'status' => 'pending', 'is_verified_purchase' => true,
        ]);

        $this->actingAs($user)->patch(route('admin.reviews.update', $review), ['status' => 'approved'])
            ->assertForbidden();
    }
}
