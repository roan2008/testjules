<?php
require_once 'src/Database.php';
try {
    $pdo = Database::connect();
    echo "Database connection: SUCCESS\n";
    echo "PHP version: " . phpversion() . "\n";
    echo "Current time: " . date('Y-m-d H:i:s') . "\n";
} catch(Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "\n";
}
?>
