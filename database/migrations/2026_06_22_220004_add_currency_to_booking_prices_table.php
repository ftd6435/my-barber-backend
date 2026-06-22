<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_prices', function (Blueprint $table) {
            $table->foreignId('currency_id')
                ->nullable()
                ->after('age_range_id')
                ->constrained('currencies')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_prices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
        });
    }
};
