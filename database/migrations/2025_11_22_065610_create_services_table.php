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
        Schema::create('services', function (Blueprint $table) {
            $table->id();            
            $table->string('name');
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('status')->default('active'); // active, inactive, discontinued
            $table->string('image_path')->nullable(); // Store multiple image paths
            // $table->foreignId('category_id')->nullable()->constrained('service_categories')->onDelete('set null');
            // $table->unsignedInteger('sort_order')->default(0);
            // $table->boolean('is_featured')->default(false);
            $table->text('description');
            $table->decimal('price', 10, 2);
            // $table->integer('duration_minutes');
            // $table->foreignId('category_id')->nullable()->constrained('service_categories')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
