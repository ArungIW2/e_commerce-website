<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PaymentController extends Controller
{
    public function show(Order $order, PaymentGateway $gateway): View|\Illuminate\Http\RedirectResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);
        abort_unless($order->payment_method === 'midtrans' && $order->status !== 'cancelled', 422);

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order);
        }

        abort_unless($order->payment_status === 'unpaid', 422);

        $order->load('items');
        $token = $order->payment_token;

        if (!$token) {
            try {
                $payment = $gateway->createPayment($order);
                $token = $payment['token'];
                $order->update(['payment_token' => $token]);
            } catch (Throwable $e) {
                Log::error('Midtrans payment creation failed.', [
                    'order_id' => $order->id,
                    'exception' => $e,
                ]);

                return redirect()
                    ->route('orders.show', $order)
                    ->with('error', 'Pembayaran sedang tidak tersedia. Silakan coba lagi beberapa saat lagi.');
            }
        }

        return view('store.payment', [
            'order' => $order,
            'payment' => ['token' => $token],
        ]);
    }

    public function notification(Request $request, PaymentGateway $gateway): JsonResponse
    {
        $gateway->handleNotification($request->all());

        return response()->json(['status' => 'ok']);
    }
}
