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
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('service_id');
            $table->foreignId('cleaner_id')->nullable();
            $table->dateTime('booking_date');
            $table->enum('status', ['pending','assigned','completed','cancelled'])->default('pending');
            $table->string('address');
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
            
            // We'll add the foreign key constraints after the referenced tables are created
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