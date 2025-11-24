<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Check what migrations are already recorded
    $existingMigrations = DB::table('migrations')->pluck('migration')->toArray();
    
    echo "Existing migrations in table:\n";
    foreach ($existingMigrations as $migration) {
        echo "- $migration\n";
    }
    
    // Migrations that appear to already be in the database
    $alreadyRunMigrations = [
        '2025_11_22_065158_create_cleaners_table',
        '2025_11_22_065256_create_bookings_table'
    ];
    
    echo "\nChecking for missing migrations...\n";
    
    foreach ($alreadyRunMigrations as $migration) {
        if (!in_array($migration, $existingMigrations)) {
            echo "Adding $migration to migrations table...\n";
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => 2
            ]);
        } else {
            echo "$migration already recorded\n";
        }
    }
    
    echo "Migration fix completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}