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
        Schema::table('bookings', function (Blueprint $table) {
            // Add foreign key constraints after all referenced tables are created
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('cleaner_id')->references('id')->on('cleaners')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropForeign(['cleaner_id']);
        });
    }
};