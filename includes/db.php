<?php
// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'learning_platform';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure optional columns exist for editor mode features
    $pdo->exec("ALTER TABLE courses ADD COLUMN IF NOT EXISTS editor_mode ENUM('rich','markdown') DEFAULT 'rich'");
    $pdo->exec("ALTER TABLE lessons ADD COLUMN IF NOT EXISTS editor_mode ENUM('inherit','rich','markdown') DEFAULT 'inherit'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>