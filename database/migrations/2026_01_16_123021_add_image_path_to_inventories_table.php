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
        Schema::table('inventories', function (Blueprint $table) {
            if (!Schema::hasColumn('inventories', 'image_path')) {
                $table->string('image_path')->nullable();
            }
            if (!Schema::hasColumn('inventories', 'stock_status')) {
                $table->string('stock_status')->default('Out of stock');
            }
            if (!Schema::hasColumn('inventories', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->foreign('vendor_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
            if (Schema::hasColumn('inventories', 'stock_status')) {
                $table->dropColumn('stock_status');
            }
            if (Schema::hasColumn('inventories', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
