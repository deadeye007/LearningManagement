<?php
// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'learning_platform';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure optional columns exist for editor mode and quiz features (compatible fallback)
    $columnQuery = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $tableQuery = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");

    $columnQuery->execute([$dbname, 'courses', 'editor_mode']);
    if (((int)$columnQuery->fetchColumn()) === 0) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN editor_mode ENUM('rich','markdown') NOT NULL DEFAULT 'rich'");
    }

    $columnQuery->execute([$dbname, 'lessons', 'editor_mode']);
    if (((int)$columnQuery->fetchColumn()) === 0) {
        $pdo->exec("ALTER TABLE lessons ADD COLUMN editor_mode ENUM('inherit','rich','markdown') NOT NULL DEFAULT 'inherit'");
    }

    $tableQuery->execute([$dbname, 'quizzes']);
    if (((int)$tableQuery->fetchColumn()) === 0) {
        $pdo->exec("
            CREATE TABLE quizzes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lesson_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                passing_score INT NOT NULL DEFAULT 70,
                time_limit_seconds INT NULL,
                max_attempts INT NULL,
                is_published BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_quiz_lesson (lesson_id),
                FOREIGN KEY (lesson_id) REFERENCES lessons(id)
            )
        ");
    } else {
        $quizColumns = [
            'description' => "ALTER TABLE quizzes ADD COLUMN description TEXT NULL AFTER title",
            'passing_score' => "ALTER TABLE quizzes ADD COLUMN passing_score INT NOT NULL DEFAULT 70 AFTER description",
            'time_limit_seconds' => "ALTER TABLE quizzes ADD COLUMN time_limit_seconds INT NULL AFTER passing_score",
            'max_attempts' => "ALTER TABLE quizzes ADD COLUMN max_attempts INT NULL AFTER time_limit_seconds",
            'is_published' => "ALTER TABLE quizzes ADD COLUMN is_published BOOLEAN NOT NULL DEFAULT FALSE AFTER max_attempts",
            'created_at' => "ALTER TABLE quizzes ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_published",
            'updated_at' => "ALTER TABLE quizzes ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
        ];

        foreach ($quizColumns as $columnName => $sql) {
            $columnQuery->execute([$dbname, 'quizzes', $columnName]);
            if (((int)$columnQuery->fetchColumn()) === 0) {
                $pdo->exec($sql);
            }
        }

        $indexQuery = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?");
        $indexQuery->execute([$dbname, 'quizzes', 'unique_quiz_lesson']);
        if (((int)$indexQuery->fetchColumn()) === 0) {
            $pdo->exec("ALTER TABLE quizzes ADD UNIQUE KEY unique_quiz_lesson (lesson_id)");
        }
    }

    $tableQuery->execute([$dbname, 'quiz_questions']);
    if (((int)$tableQuery->fetchColumn()) === 0) {
        $pdo->exec("
            CREATE TABLE quiz_questions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                quiz_id INT NOT NULL,
                question_text TEXT NOT NULL,
                question_type ENUM('multiple_choice') NOT NULL DEFAULT 'multiple_choice',
                points INT NOT NULL DEFAULT 1,
                order_num INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
            )
        ");
    } else {
        $columnQuery->execute([$dbname, 'quiz_questions', 'question_text']);
        if (((int)$columnQuery->fetchColumn()) === 0) {
            $columnQuery->execute([$dbname, 'quiz_questions', 'question']);
            if (((int)$columnQuery->fetchColumn()) > 0) {
                $pdo->exec("ALTER TABLE quiz_questions CHANGE COLUMN question question_text TEXT NOT NULL");
            } else {
                $pdo->exec("ALTER TABLE quiz_questions ADD COLUMN question_text TEXT NOT NULL");
            }
        }

        $questionColumns = [
            'question_type' => "ALTER TABLE quiz_questions ADD COLUMN question_type ENUM('multiple_choice') NOT NULL DEFAULT 'multiple_choice' AFTER question_text",
            'points' => "ALTER TABLE quiz_questions ADD COLUMN points INT NOT NULL DEFAULT 1 AFTER question_type",
            'order_num' => "ALTER TABLE quiz_questions ADD COLUMN order_num INT NOT NULL DEFAULT 1 AFTER points",
            'created_at' => "ALTER TABLE quiz_questions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER order_num"
        ];

        foreach ($questionColumns as $columnName => $sql) {
            $columnQuery->execute([$dbname, 'quiz_questions', $columnName]);
            if (((int)$columnQuery->fetchColumn()) === 0) {
                $pdo->exec($sql);
            }
        }
    }

    $tableQuery->execute([$dbname, 'quiz_answers']);
    if (((int)$tableQuery->fetchColumn()) === 0) {
        $pdo->exec("
            CREATE TABLE quiz_answers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                question_id INT NOT NULL,
                answer_text TEXT NOT NULL,
                is_correct BOOLEAN NOT NULL DEFAULT FALSE,
                order_num INT NOT NULL DEFAULT 1,
                FOREIGN KEY (question_id) REFERENCES quiz_questions(id)
            )
        ");
    }

    $tableQuery->execute([$dbname, 'quiz_attempts']);
    if (((int)$tableQuery->fetchColumn()) === 0) {
        $pdo->exec("
            CREATE TABLE quiz_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                quiz_id INT NOT NULL,
                score INT NOT NULL DEFAULT 0,
                max_score INT NOT NULL DEFAULT 0,
                percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                passed BOOLEAN NOT NULL DEFAULT FALSE,
                started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                submitted_at TIMESTAMP NULL DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
            )
        ");
    } else {
        $attemptColumns = [
            'max_score' => "ALTER TABLE quiz_attempts ADD COLUMN max_score INT NOT NULL DEFAULT 0 AFTER score",
            'percentage' => "ALTER TABLE quiz_attempts ADD COLUMN percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER max_score",
            'passed' => "ALTER TABLE quiz_attempts ADD COLUMN passed BOOLEAN NOT NULL DEFAULT FALSE AFTER percentage",
            'started_at' => "ALTER TABLE quiz_attempts ADD COLUMN started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER passed",
            'submitted_at' => "ALTER TABLE quiz_attempts ADD COLUMN submitted_at TIMESTAMP NULL DEFAULT NULL AFTER started_at"
        ];

        foreach ($attemptColumns as $columnName => $sql) {
            $columnQuery->execute([$dbname, 'quiz_attempts', $columnName]);
            if (((int)$columnQuery->fetchColumn()) === 0) {
                $pdo->exec($sql);
            }
        }

        $columnQuery->execute([$dbname, 'quiz_attempts', 'attempted_at']);
        if (((int)$columnQuery->fetchColumn()) > 0) {
            $pdo->exec("UPDATE quiz_attempts SET submitted_at = COALESCE(submitted_at, attempted_at), started_at = COALESCE(started_at, attempted_at)");
        }
    }

    $tableQuery->execute([$dbname, 'quiz_attempt_responses']);
    if (((int)$tableQuery->fetchColumn()) === 0) {
        $pdo->exec("
            CREATE TABLE quiz_attempt_responses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attempt_id INT NOT NULL,
                question_id INT NOT NULL,
                selected_answer_id INT NULL,
                is_correct BOOLEAN NOT NULL DEFAULT FALSE,
                points_awarded INT NOT NULL DEFAULT 0,
                FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id),
                FOREIGN KEY (question_id) REFERENCES quiz_questions(id),
                FOREIGN KEY (selected_answer_id) REFERENCES quiz_answers(id)
            )
        ");
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
