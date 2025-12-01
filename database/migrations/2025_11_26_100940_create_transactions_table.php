<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');

            $table->string('payment_intent_id')->nullable();
            $table->string('charge_id')->nullable();
            $table->string('transfer_id')->nullable();

            $table->bigInteger('total_amount');       // total in cents
            $table->bigInteger('platform_fee');       // platform fee
            $table->bigInteger('vendor_amount');      // amount to vendor

            // IMPORTANT: include "released"
            $table->enum('status', ['paid', 'released', 'refunded', 'failed'])
                  ->default('paid');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['vendor_id']);
        });

        Schema::dropIfExists('transactions');
    }
};
