<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->enum('type', [
                'booking_payment_hold',
                'booking_payment_release',
                'platform_fee_deduction',
                'booking_refund_reversal',
                'booking_refund_credit',
                'withdrawal_hold',
                'withdrawal_release',
                'withdrawal_debit',
                'adjustment',
            ]);
            $table->enum('direction', ['credit', 'debit']);
            $table->enum('balance_type', ['available', 'held']);
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_before', 18, 2)->default(0.00);
            $table->decimal('balance_after', 18, 2)->default(0.00);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['wallet_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
