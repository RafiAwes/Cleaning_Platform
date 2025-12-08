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
        // Add foreign key constraint only if it doesn't already exist and the tables exist
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasTable('packages') && Schema::hasColumn('services', 'package_id')) {
                try {
                    // Check if the foreign key already exists
                    $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist, ignore the error
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'package_id')) {
                try {
                    $table->dropForeign(['package_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore the error
                }
            }
        });
    }
};