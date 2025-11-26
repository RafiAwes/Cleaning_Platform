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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            
            $table->string('payment_intent_id');
            $table->string('charge_id')->nullable();
            $table->string('transfer_id')->nullable();

            $table->integer('total_amount');      
            $table->integer('platform_fee');      
            $table->integer('vendor_amount');     
            $table->string('status', ['paid', 'refunded', 'failed'])->default('paid'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['vendor_id']);
        });
        
        Schema::dropIfExists('transactions');
    }
};