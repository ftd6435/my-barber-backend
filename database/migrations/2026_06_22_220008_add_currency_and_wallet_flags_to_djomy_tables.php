<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('djomy_payments', function (Blueprint $table) {
            $table->foreignId('currency_id')
                ->nullable()
                ->after('booking_id')
                ->constrained('currencies')
                ->restrictOnDelete();
            $table->boolean('is_wallet_applied')->default(false)->after('status');
            $table->timestamp('wallet_applied_at')->nullable()->after('is_wallet_applied');
        });

        Schema::table('djomy_payment_links', function (Blueprint $table) {
            $table->foreignId('currency_id')
                ->nullable()
                ->after('booking_id')
                ->constrained('currencies')
                ->restrictOnDelete();
            $table->boolean('is_wallet_applied')->default(false)->after('status');
            $table->timestamp('wallet_applied_at')->nullable()->after('is_wallet_applied');
        });
    }

    public function down(): void
    {
        Schema::table('djomy_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
            $table->dropColumn(['is_wallet_applied', 'wallet_applied_at']);
        });

        Schema::table('djomy_payment_links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
            $table->dropColumn(['is_wallet_applied', 'wallet_applied_at']);
        });
    }
};
