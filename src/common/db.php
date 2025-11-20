<?php
// Provides a reusable PDO connection factory for the application.
function getDatabaseConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $databasePath = __DIR__ . '/../../database.db';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $connection = new PDO('sqlite:' . $databasePath, null, null, $options);
    $connection->exec('PRAGMA foreign_keys = ON');

    initializeResourcesSchema($connection);

    return $connection;
}

function initializeResourcesSchema(PDO $connection): void
{
    $connection->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NULL,
            link TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $connection->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            resource_id INTEGER NOT NULL,
            author TEXT NOT NULL,
            text TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
        )
    SQL);

    $count = (int) $connection->query('SELECT COUNT(*) as count FROM resources')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $seedResources = [
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

    $insert = $connection->prepare('INSERT INTO resources (title, description, link) VALUES (?, ?, ?)');
    foreach ($seedResources as $resource) {
        $insert->execute($resource);
    }

    $firstResourceId = (int) $connection->lastInsertId() - 2;
    $commentInsert = $connection->prepare('INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)');
    $commentInsert->execute([$firstResourceId, 'Course Instructor', 'Feel free to post your questions about week one here.']);
    $commentInsert->execute([$firstResourceId, 'Student A', 'Does this include the optional reading for next week?']);
}
