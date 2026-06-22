<?php

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
        Schema::create('bookin_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete(); // Author
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('professionel_id')->constrained('users')->cascadeOnDelete(); // Booked Professional
            $table->text('review')->nullable(); // review text
            $table->integer('rating')->nullable(); // 1-5
            $table->boolean('is_visible')->default(true); // Is visible to the public
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookin_reviews');
    }
};
