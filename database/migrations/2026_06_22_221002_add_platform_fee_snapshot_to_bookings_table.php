<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('platform_fee_percentage', 5, 2)
                ->default(0.00)
                ->after('client_refunded_amount');
            $table->decimal('platform_fee_amount', 18, 2)
                ->default(0.00)
                ->after('platform_fee_percentage');
            $table->decimal('professionel_net_amount', 18, 2)
                ->default(0.00)
                ->after('platform_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'platform_fee_percentage',
                'platform_fee_amount',
                'professionel_net_amount',
            ]);
        });
    }
};
