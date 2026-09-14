<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('last_used_at')->index();
            $table->timestamp('revoked_at')->nullable()->after('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['revoked_at']);
            $table->dropColumn(['expires_at', 'revoked_at']);
        });
    }
};
