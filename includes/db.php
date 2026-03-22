<?php
// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'learning_platform';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure optional columns exist for editor mode features (compatible fallback)
    $columnQuery = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?");

    $columnQuery->execute([$dbname, 'courses', 'editor_mode']);
    if (((int)$columnQuery->fetchColumn()) === 0) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN editor_mode ENUM('rich','markdown') NOT NULL DEFAULT 'rich'");
    }

    $columnQuery->execute([$dbname, 'lessons', 'editor_mode']);
    if (((int)$columnQuery->fetchColumn()) === 0) {
        $pdo->exec("ALTER TABLE lessons ADD COLUMN editor_mode ENUM('inherit','rich','markdown') NOT NULL DEFAULT 'inherit'");
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>