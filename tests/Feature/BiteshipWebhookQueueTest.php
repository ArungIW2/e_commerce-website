<?php

namespace Tests\Feature;

use App\Jobs\ProcessBiteshipWebhook;
use App\Models\BiteshipWebhookEvent;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BiteshipWebhookQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_payloads_share_one_idempotency_key(): void
    {
        config()->set('services.biteship.webhook_signature_key', 'X-Biteship-Signature');
        config()->set('services.biteship.webhook_signature_secret', 'secret-value');
        Queue::fake();

        $payload = ['event' => 'order.status', 'order_id' => 'bite-order-1', 'status' => 'confirmed'];

        $this->withHeader('X-Biteship-Signature', 'secret-value')->postJson(route('shipping.webhook'), $payload)->assertOk();
        $this->withHeader('X-Biteship-Signature', 'secret-value')->postJson(route('shipping.webhook'), $payload)->assertOk();

        $this->assertCount(1, BiteshipWebhookEvent::all());
        Queue::assertPushed(ProcessBiteshipWebhook::class, 1);
    }
}
