<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('biteship_order_id')->nullable()->unique()->after('payment_token');
            $table->string('biteship_tracking_id')->nullable()->index()->after('biteship_order_id');
            $table->string('shipping_status')->nullable()->index()->after('biteship_tracking_id');
            $table->text('shipping_tracking_url')->nullable()->after('shipping_status');
            $table->timestamp('shipped_at')->nullable()->after('shipping_tracking_url');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'biteship_order_id',
                'biteship_tracking_id',
                'shipping_status',
                'shipping_tracking_url',
                'shipped_at',
            ]);
        });
    }
};
