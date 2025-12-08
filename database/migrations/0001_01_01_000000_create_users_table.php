<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('role',['admin','customer','vendor']);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints from other tables before dropping users table
        // Bookings table
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'customer_id')) {
                    $table->dropForeign(['customer_id']);
                }
                if (Schema::hasColumn('bookings', 'vendor_id')) {
                    $table->dropForeign(['vendor_id']);
                }
                if (Schema::hasColumn('bookings', 'cleaner_id')) {
                    $table->dropForeign(['cleaner_id']);
                }
            });
        }
        
        // Packages table
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                if (Schema::hasColumn('packages', 'vendor_id')) {
                    $table->dropForeign(['vendor_id']);
                }
            });
        }
        
        // Cleaner availabilities table
        if (Schema::hasTable('cleaner_availabilities')) {
            Schema::table('cleaner_availabilities', function (Blueprint $table) {
                if (Schema::hasColumn('cleaner_availabilities', 'cleaner_id')) {
                    $table->dropForeign(['cleaner_id']);
                }
            });
        }
        
        // Vendors table
        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                if (Schema::hasColumn('vendors', 'user_id')) {
                    $table->dropForeign(['user_id']);
                }
            });
        }
        
        // Messages table
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (Schema::hasColumn('messages', 'sender_id')) {
                    $table->dropForeign(['sender_id']);
                }
                if (Schema::hasColumn('messages', 'receiver_id')) {
                    $table->dropForeign(['receiver_id']);
                }
            });
        }
        
        // Inventories table
        if (Schema::hasTable('inventories')) {
            Schema::table('inventories', function (Blueprint $table) {
                if (Schema::hasColumn('inventories', 'vendor_id')) {
                    $table->dropForeign(['vendor_id']);
                }
            });
        }
        
        // Customers table
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'user_id')) {
                    $table->dropForeign(['user_id']);
                }
            });
        }
        
        // Custom prices table
        if (Schema::hasTable('custom_prices')) {
            Schema::table('custom_prices', function (Blueprint $table) {
                if (Schema::hasColumn('custom_prices', 'vendor_id')) {
                    $table->dropForeign(['vendor_id']);
                }
            });
        }
        
        // Custom bookings table
        if (Schema::hasTable('custom_bookings')) {
            Schema::table('custom_bookings', function (Blueprint $table) {
                if (Schema::hasColumn('custom_bookings', 'customer_id')) {
                    $table->dropForeign(['customer_id']);
                }
                if (Schema::hasColumn('custom_bookings', 'vendor_id')) {
                    $table->dropForeign(['vendor_id']);
                }
            });
        }
        
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};