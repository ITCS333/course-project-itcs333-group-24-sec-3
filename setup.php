<?php
$host = 'localhost';
$dbname = 'crudusers';
$user = 'root';
$pass = 'password123';

try {
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $db = new PDO($dsn, $user, $pass, $options);

    $db->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE `$dbname`");

    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            student_id VARCHAR(20) NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            role VARCHAR(20) NOT NULL DEFAULT 'student',
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY users_student_id_unique (student_id)
        ) ENGINE=InnoDB
    SQL);

    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS resources (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            link TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    SQL);

    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resource_id INT UNSIGNED NOT NULL,
            author VARCHAR(150) NOT NULL,
            text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_comments_resource FOREIGN KEY (resource_id)
                REFERENCES resources(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    SQL);

    $adminEmail = 'admin@example.com';
    $adminExists = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $adminExists->execute([$adminEmail]);

    if (!$adminExists->fetch()) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = $db->prepare('INSERT INTO users (name, student_id, email, role, password_hash) VALUES (?, NULL, ?, ?, ?)');
        $insertAdmin->execute(['Course Administrator', $adminEmail, 'admin', $adminPassword]);
        echo "Seeded default admin user (admin@example.com / admin123)." . PHP_EOL;
    }

    $resourcesCount = (int) $db->query('SELECT COUNT(*) FROM resources')->fetchColumn();

    if ($resourcesCount === 0) {
        $seedResources = $db->prepare('INSERT INTO resources (title, description, link) VALUES (?, ?, ?)');
        $sampleResources = [
            [
                'Chapter 1 - Introduction to Systems Analysis',
                'Slides, summary notes, and worked examples that cover the first week of lecture content.',
                'https://example.com/resources/chapter-1.pdf',
            ],
            [
                'Git Branching Playground',
                'Practice branching and merging safely in your browser before trying commands locally.',
                'https://learngitbranching.js.org/',
            ],
            [
                'Accessibility Checklist',
                'A concise checklist to ensure your web pages meet WCAG AA guidelines.',
                'https://www.w3.org/WAI/test-evaluate/preliminary/',
            ],
        ];

        foreach ($sampleResources as $resource) {
            $seedResources->execute($resource);
        }

        $firstResourceId = (int) $db->lastInsertId() - 2;

        $seedComments = $db->prepare('INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)');
        $seedComments->execute([$firstResourceId, 'Course Instructor', 'Feel free to post your questions about week one here.']);
        $seedComments->execute([$firstResourceId, 'Student A', 'Does this include the optional reading for next week?']);
    }

    echo "Database and tables are ready." . PHP_EOL;
} catch (PDOException $e) {
    echo "Setup failed: " . $e->getMessage();
}
?>