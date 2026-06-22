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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // Auto generated reference number. BK-2026001, BK-2026002, ...
            $table->foreignId('professionel_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->enum('location', ['home', 'salon'])->default('home');
            $table->string('client_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 10, 8)->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'partial', 'completed'])->default('pending');
            $table->json('booking_details')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('professionel_comment')->nullable(); // Professionel must leave a comment for reason why added extra fees or rejected the booking.
            $table->decimal('extra_fees', 10, 2)->default(0.00); // Base on the details, the booking may require extra fees fixed by the professionel.
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
