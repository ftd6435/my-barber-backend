<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Direct payments (no redirect)
        Schema::create('djomy_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('merchant_reference')->unique();      // your order ref (TXN-XXXX)
            $table->string('djomy_transaction_id')->nullable();  // returned by Djomy
            $table->string('payment_method');                    // OM, MOMO, KULU, etc.
            $table->string('payer_identifier');                  // 00224XXXXXXXXX
            $table->decimal('amount', 15, 2);
            $table->string('country_code', 3)->default('GN');
            $table->string('status')->default('PENDING');        // PENDING, SUCCESS, FAILED
            $table->string('description')->nullable();
            $table->string('redirect_url')->nullable();          // for KULU
            $table->json('djomy_response')->nullable();          // full raw API response
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('djomy_transaction_id');
        });

        // Payment links (hosted Djomy portal)
        Schema::create('djomy_payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('djomy_reference')->unique();         // reference returned by Djomy
            $table->string('merchant_reference')->nullable();    // your own ref
            $table->string('link_name')->nullable();
            $table->string('link_url')->nullable();              // the shareable URL
            $table->decimal('amount_to_pay', 15, 2)->nullable();
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->string('country_code', 3)->default('GN');
            $table->string('usage_type')->default('UNIQUE');     // UNIQUE | MULTIPLE
            $table->integer('usage_limit')->nullable();
            $table->string('status')->default('ACTIVE');         // ACTIVE | INACTIVE | EXPIRED
            $table->timestamp('expires_at')->nullable();
            $table->string('description')->nullable();
            $table->json('allowed_payment_methods')->nullable();
            $table->json('djomy_response')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('merchant_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('djomy_payments');
        Schema::dropIfExists('djomy_payment_links');
    }
};
