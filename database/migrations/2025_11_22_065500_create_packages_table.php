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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            // Use unsignedBigInteger instead of foreignId to avoid automatic constraint creation
            $table->unsignedBigInteger('vendor_id');
            // Define the category_id column first
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->decimal('ratings', 10, 2)->nullable();
            $table->timestamps();
        });
        
        // Add the foreign key constraints only if the referenced tables exist
        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasTable('users')) {
                $table->foreign('vendor_id')->references('id')->on('users')->onDelete('cascade');
            }
            if (Schema::hasTable('categories')) {
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Simply drop the table - Laravel will handle foreign key constraints automatically
        Schema::dropIfExists('packages');
    }
};