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
        Schema::create('cleaners', function (Blueprint $table) {
            $table->id();
            // Use unsignedBigInteger instead of foreignId to avoid automatic constraint creation
            $table->unsignedBigInteger('vendor_id');
            $table->string('name');
            $table->string('phone');
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'assigned', 'completed'])->default('active')->nullable();
            $table->float('ratings')->default(0)->nullable();
            $table->timestamps();
        });
        
        // Add the foreign key constraint only if the vendors table exists
        Schema::table('cleaners', function (Blueprint $table) {
            if (Schema::hasTable('vendors')) {
                $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Simply drop the table - Laravel will handle foreign key constraints automatically
        Schema::dropIfExists('cleaners');
    }
};