<?php

// scripts/seed.php

require_once __DIR__ . '/../vendor/autoload.php';

// --- Helper Functions ---

function print_message($message, $type = 'info') {
    $color_codes = [
        'info'    => "\033[0;32m", // Green
        'error'   => "\033[0;31m", // Red
        'warning' => "\033[0;33m", // Yellow
        'reset'   => "\033[0m"
    ];
    echo $color_codes[$type] . $message . $color_codes['reset'] . PHP_EOL;
}

function execute_sql_file(PDO $pdo, $filepath) {
    if (!file_exists($filepath)) {
        print_message("Error: File not found at {$filepath}", 'error');
        return;
    }
    try {
        $sql = file_get_contents($filepath);
        $pdo->exec($sql);
        print_message("Successfully executed: " . basename($filepath));
    } catch (PDOException $e) {
        print_message("Error executing {$filepath}: " . $e->getMessage(), 'error');
        exit(1);
    }
}

// --- Main Script ---

print_message("Starting database setup...", 'info');

// 1. Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_DATABASE'];
$db_user = $_ENV['DB_USERNAME'];
$db_pass = $_ENV['DB_PASSWORD'];

// 2. Establish PDO Connection
try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    print_message("Database connection successful.");
} catch (PDOException $e) {
    print_message("Database connection failed: " . $e->getMessage(), 'error');
    exit(1);
}

// 3. Execute Schema
$schema_path = __DIR__ . '/../database/schema.sql';
print_message("\nExecuting schema file...", 'info');
execute_sql_file($pdo, $schema_path);

// 4. Execute Seeds
$seeds_dir = __DIR__ . '/../database/seeds/';
$seed_files = glob($seeds_dir . '*.sql');
sort($seed_files); // Sort files alphabetically to ensure correct order

if (empty($seed_files)) {
    print_message("\nNo seed files found.", 'warning');
} else {
    print_message("\nExecuting seed files...", 'info');
    foreach ($seed_files as $file) {
        execute_sql_file($pdo, $file);
    }
}

print_message("\nDatabase setup completed successfully!", 'info');
