<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function show(Order $order, PaymentGateway $gateway): View
    {
        abort_unless($order->user_id === Auth::id(), 403);
        abort_unless($order->payment_method === 'midtrans' && $order->payment_status === 'unpaid' && $order->status !== 'cancelled', 422);

        $order->load('items');
        $token = $order->payment_token;
        if (!$token) {
            $payment = $gateway->createPayment($order);
            $token = $payment['token'];
            $order->update(['payment_token' => $token]);
        }

        return view('store.payment', ['order' => $order, 'payment' => ['token' => $token]]);
    }

    public function notification(Request $request, PaymentGateway $gateway): void
    {
        $gateway->handleNotification($request->all());
    }
}
