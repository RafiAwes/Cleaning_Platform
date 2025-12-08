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
            // The customer_id foreign key is already defined in the bookings table creation
            // Adding the package_id foreign key constraint now that the packages table exists
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            // Only adding cleaner_id foreign key here as it wasn't constrained before
            $table->foreign('cleaner_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'package_id')) {
                $table->dropForeign(['package_id']);
            }
            if (Schema::hasColumn('bookings', 'cleaner_id')) {
                $table->dropForeign(['cleaner_id']);
            }
        });
    }
};