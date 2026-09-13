<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_method')->default('standard')->after('shipping_cost');
            $table->string('payment_method')->default('manual')->after('shipping_method');
            $table->string('payment_status')->default('unpaid')->after('payment_method');
            $table->string('coupon_code')->nullable()->after('discount');
            $table->string('tracking_number')->nullable()->after('postal_code');
            $table->timestamp('cancelled_at')->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_method', 'payment_method', 'payment_status',
                'coupon_code', 'tracking_number', 'cancelled_at',
            ]);
        });
    }
};
