<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Services\BiteshipShippingService;
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

    public function notification(Request $request, PaymentGateway $gateway, BiteshipShippingService $shipping): JsonResponse
    {
        $gateway->handleNotification($request->all());

        $midtransOrderNumber = $request->input('order_id');
        if ($midtransOrderNumber) {
            $order = Order::where('order_number', $midtransOrderNumber)->first();
            if ($order?->payment_status === 'paid' && str_starts_with($order->shipping_method, 'biteship:') && !$order->biteship_order_id) {
                try {
                    $shipping->syncOrderResponse($order, $shipping->createOrder($order));
                } catch (Throwable $e) {
                    Log::error('Automatic Biteship shipment creation after Midtrans payment failed.', [
                        'order_id' => $order->id,
                        'exception' => $e,
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
