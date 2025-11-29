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
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('cleaner_id')->nullable()->default(NULL);
            $table->dateTime('booking_date_time');
            // $table->time('booking_time');
            $table->enum('status', ['pending','ongoing','completed','cancelled','rejected'])->default('pending');
            $table->enum('customer_status', ['pending','accepted','rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('address');
            $table->decimal('total_price', 10, 2);
            $table->enum('payment_status', ['pending','paid','failed'])->default('pending');
            $table->timestamps();
            
            // We'll add the foreign key constraints in a separate migration after all tables are created
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