<?php

use App\Models\Activities\AgeRange;
use App\Models\Activities\Booking;
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
        Schema::create('booking_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Booking::class)->constrained('bookings')->cascadeOnDelete();
            $table->foreignIdFor(AgeRange::class)->constrained('age_ranges')->cascadeOnDelete();
            $table->integer('number')->comment('Number of people in the age range');
            $table->decimal('price', 10, 2)->default(0.00)->comment('Price per person in the age range from service_prices');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_prices');
    }
};
