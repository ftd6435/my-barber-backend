<?php

use App\Models\Currency;
use App\Models\User;
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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Currency::class)->constrained()->restrictOnDelete();
            $table->boolean('is_locked')->default(false)->comment('Si le wallet est verrouillé');
            $table->decimal('available_balance', 18, 2)->default(0.00)->comment('Solde disponible du wallet');
            $table->decimal('held_balance', 18, 2)->default(0.00)->comment('Solde bloqué ou en séquestre');
            $table->timestamps();

            $table->unique(['user_id', 'currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
