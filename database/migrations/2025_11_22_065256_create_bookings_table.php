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
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('vendor_id')->nullable()->default(NULL)->constrained('users');
            // Use foreignId but without constrained() to avoid creating the foreign key before the packages table exists
            $table->foreignId('package_id')->nullable()->default(NULL);
            $table->foreignId('cleaner_id')->nullable()->default(NULL);
            $table->boolean('is_custom')->default(false);
            $table->dateTime('booking_date_time');
            $table->enum('status', ['pending', 'new', 'ongoing','completed','cancelled','rejected'])->default('pending');
            $table->enum('customer_status', ['pending','accepted','rejected',])->default('pending');
            $table->text('notes')->nullable();
            $table->string('address');
            $table->decimal('total_price', 10, 2);
            $table->enum('payment_status', ['pending','paid','failed'])->default('pending');
            $table->decimal('ratingS', 10, 2)->default(0.00)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints before dropping the table
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'customer_id')) {
                $table->dropForeign(['customer_id']);
            }
            if (Schema::hasColumn('bookings', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
            }
        });
        
        Schema::dropIfExists('bookings');
    }
};