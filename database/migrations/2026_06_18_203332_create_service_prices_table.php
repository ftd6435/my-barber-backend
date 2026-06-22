<?php

use App\Models\Activities\AgeRange;
use App\Models\Activities\Service;
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
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Service::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AgeRange::class)->constrained()->cascadeOnDelete();
            $table->decimal('price', 8, 2)->default(0.00);
            $table->boolean('is_approved')->default(false); // Price must be reviewed by super_admin and admin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
