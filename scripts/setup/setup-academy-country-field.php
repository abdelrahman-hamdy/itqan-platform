<?php

/**
 * Script to add country field to academies table
 * Run this if you get an error about missing country column
 * 
 * Usage: php setup-academy-country-field.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $pdo = new PDO("mysql:host=localhost;dbname=test", "test", "test");
    
    // Check if academies table exists and has country column
    $stmt = $pdo->query("SHOW TABLES LIKE 'academies'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Check if country column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM academies LIKE 'country'");
        $columnExists = $stmt->rowCount() > 0;
        
        if (!$columnExists) {
            echo "Adding country column to academies table...\n";
            $pdo->exec("ALTER TABLE academies ADD COLUMN country VARCHAR(2) DEFAULT 'SA' AFTER theme");
            echo "Country column added successfully!\n";
        } else {
            echo "Country column already exists.\n";
        }
    } else {
        echo "Academies table does not exist.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please run the migration manually:\n";
    echo "php artisan migrate --path=database/migrations/2025_11_10_add_country_to_academies_table.php\n";
}
