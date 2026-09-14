<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', Auth::id())
                ->where('status', 'completed'))
            ->latest('order_items.id')
            ->first();

        if (! $item) {
            return back()->with('error', 'You can review a product only after completing a purchase.');
        }

        $review = ProductReview::updateOrCreate(
            ['user_id' => Auth::id(), 'product_id' => $product->id],
            [
                'order_id' => $item->order_id,
                'order_item_id' => $item->id,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => 'pending',
                'is_verified_purchase' => true,
            ],
        );

        return back()->with('success', $review->wasRecentlyCreated
            ? 'Review submitted and awaiting moderation.'
            : 'Review updated and sent back for moderation.');
    }
}
