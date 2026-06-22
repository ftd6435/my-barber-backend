<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('service_currency_id')
                ->nullable()
                ->after('service_id')
                ->constrained('currencies')
                ->restrictOnDelete();
            $table->foreignId('client_currency_id')
                ->nullable()
                ->after('service_currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();
            $table->foreignId('settlement_currency_id')
                ->nullable()
                ->after('client_currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();
            $table->decimal('service_to_client_exchange_rate', 20, 8)
                ->default(1)
                ->after('payment_status');
            $table->decimal('service_subtotal_amount', 18, 2)
                ->default(0.00)
                ->after('service_to_client_exchange_rate');
            $table->decimal('service_total_amount', 18, 2)
                ->default(0.00)
                ->after('service_subtotal_amount');
            $table->decimal('client_total_amount', 18, 2)
                ->default(0.00)
                ->after('service_total_amount');
            $table->decimal('settlement_total_amount', 18, 2)
                ->default(0.00)
                ->after('client_total_amount');
            $table->decimal('client_refunded_amount', 18, 2)
                ->default(0.00)
                ->after('settlement_total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_currency_id');
            $table->dropConstrainedForeignId('client_currency_id');
            $table->dropConstrainedForeignId('settlement_currency_id');
            $table->dropColumn([
                'service_to_client_exchange_rate',
                'service_subtotal_amount',
                'service_total_amount',
                'client_total_amount',
                'settlement_total_amount',
                'client_refunded_amount',
            ]);
        });
    }
};
