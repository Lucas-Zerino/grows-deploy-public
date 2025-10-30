<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Running migration: 004_add_instagram_support.sql\n";

try {
    $db = Database::getInstance();
    
    // Read migration file
    $migrationFile = __DIR__ . '/../database/migrations/004_add_instagram_support.sql';
    $sql = file_get_contents($migrationFile);
    
    if (!$sql) {
        throw new Exception("Could not read migration file: $migrationFile");
    }
    
    // Execute the entire SQL at once
    echo "Executing migration SQL...\n";
    $db->exec($sql);
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
