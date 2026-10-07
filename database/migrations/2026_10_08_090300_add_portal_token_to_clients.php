<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal login moves to a long random token stored only as a SHA-256 hash.
 * The old plaintext portal_access_code keeps working (exact match, rate-limited) until the
 * admin rotates it; rotating clears the old code.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('portal_token_hash', 64)->nullable()->unique();
            $table->timestamp('portal_token_rotated_at')->nullable();
            $table->timestamp('portal_last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['portal_token_hash']);
            $table->dropColumn(['portal_token_hash', 'portal_token_rotated_at', 'portal_last_login_at']);
        });
    }
};
