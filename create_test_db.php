<?php
try {
    $pdo = new PDO('mysql:host=156.67.27.156;port=3306', 'nex_user', 'Hh102030@@@');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS nex_db_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "Test DB created OK\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
