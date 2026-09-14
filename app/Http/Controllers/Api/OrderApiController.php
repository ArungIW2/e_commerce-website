<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderApiController extends ApiController
{
    public function index(Request $request)
    {
        return $this->success($request->user()->orders()->with('items')->latest()->paginate(15));
    }

    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) return $this->error('Order not found.', 404);
        return $this->success($order->load('items'));
    }
}
