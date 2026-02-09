<?php

/**
 * Simple script to create academy_settings table
 * Run this with: php create-academy-settings-table.php
 */

// Database configuration - reads from .env if available
$envFile = __DIR__.'/../../.env';
$envVars = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
}

$host = $envVars['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$port = $envVars['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$database = $envVars['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'itqan_platform';
$username = $envVars['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
$password = $envVars['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully\n";

    // Check if academy_settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'academy_settings'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "ℹ️ academy_settings table already exists\n";
        exit(0);
    }

    // Create academy_settings table
    $sql = '
    CREATE TABLE academy_settings (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        academy_id BIGINT UNSIGNED NOT NULL,
        default_preparation_minutes TINYINT UNSIGNED NOT NULL DEFAULT 10,
        default_late_join_minutes TINYINT UNSIGNED NOT NULL DEFAULT 15,
        default_session_end_buffer_minutes TINYINT UNSIGNED NOT NULL DEFAULT 5,
        requires_session_approval TINYINT(1) NOT NULL DEFAULT 0,
        allows_teacher_creation TINYINT(1) NOT NULL DEFAULT 1,
        allows_student_enrollment TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL DEFAULT NULL,
        updated_at TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY academy_settings_academy_id_unique (academy_id),
        CONSTRAINT academy_settings_academy_id_foreign FOREIGN KEY (academy_id) REFERENCES academies (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ';

    $pdo->exec($sql);
    echo "✅ academy_settings table created successfully\n";

    // Insert default settings for existing academies
    $stmt = $pdo->query('SELECT id FROM academies');
    $academies = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($academies)) {
        echo "ℹ️ No academies found to insert default settings\n";
    } else {
        $insertSql = 'INSERT INTO academy_settings (academy_id) VALUES (?)';
        $stmt = $pdo->prepare($insertSql);

        foreach ($academies as $academyId) {
            $stmt->execute([$academyId]);
        }

        echo '✅ Inserted default settings for '.count($academies)." academies\n";
    }

    echo "🎉 Migration completed successfully!\n";

} catch (PDOException $e) {
    echo '❌ Database error: '.$e->getMessage()."\n";
    echo "\nIf you have phpMyAdmin, run this SQL directly:\n";
    echo "\nCREATE TABLE academy_settings (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        academy_id BIGINT UNSIGNED NOT NULL,
        default_preparation_minutes TINYINT UNSIGNED NOT NULL DEFAULT 10,
        default_late_join_minutes TINYINT UNSIGNED NOT NULL DEFAULT 15,
        default_session_end_buffer_minutes TINYINT UNSIGNED NOT NULL DEFAULT 5,
        requires_session_approval TINYINT(1) NOT NULL DEFAULT 0,
        allows_teacher_creation TINYINT(1) NOT NULL DEFAULT 1,
        allows_student_enrollment TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL DEFAULT NULL,
        updated_at TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY academy_settings_academy_id_unique (academy_id),
        CONSTRAINT academy_settings_academy_id_foreign FOREIGN KEY (academy_id) REFERENCES academies (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
    exit(1);
}
