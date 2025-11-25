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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('about')->nullable();
            $table->string('badge')->nullable();
            $table->integer('ratings')->nullable()->default(0);
            $table->time('from_time')->nullable();
            $table->time('to_time')->nullable();
            $table->integer('bookings_target')->nullable()->default(0);
            $table->integer('revenue_target')->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
