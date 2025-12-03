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
            $table->string('phone')->nullable();
            $table->text('about')->nullable();
            $table->string('address')->nullable();
            $table->string('business_name')->nullable();
            $table->json('service_category')->nullable();
            $table->string('image_path')->nullable();
            $table->string('badge')->nullable();
            $table->boolean('is_custom_pricing')->default(false);
            $table->integer('ratings')->nullable()->default(0);
            $table->time('from_time')->nullable();
            $table->time('to_time')->nullable();
            $table->integer('bookings_target')->nullable()->default(0);
            $table->integer('revenue_target')->nullable()->default(0);
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_stripe_connected')->default(false);
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
