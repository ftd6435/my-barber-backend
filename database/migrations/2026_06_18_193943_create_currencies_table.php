<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->string('code', 3)->unique();
            $table->string('symbol', 10);
            $table->timestamps();
        });

        // Auto create default currency
        $currency = [
            'name' => 'Franc Guinéen',
            'code' => 'GNF',
            'symbol' => 'FG',
        ];

        // Check if currency already exists
        if (Currency::where('code', $currency['code'])->first()) {
            return;
        }

        Currency::create($currency);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
