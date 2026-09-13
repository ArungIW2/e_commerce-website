<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGateway
{
    public function createPayment(Order $order): array;

    public function handleNotification(array $payload): void;
}
