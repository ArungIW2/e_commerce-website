<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status');

        $reviews = ProductReview::query()
            ->with(['user', 'product', 'order'])
            ->when(in_array($status, ProductReview::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'status'));
    }

    public function update(Request $request, ProductReview $review): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $review->update(['status' => $data['status']]);

        return back()->with('success', 'Review moderation status updated.');
    }
}
