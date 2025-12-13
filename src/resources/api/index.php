<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../common/db.php';
require_once __DIR__ . '/../../common/auth.php';

// Protect all resource routes - require authentication
requireApiAuthentication();

$db = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawInput = file_get_contents('php://input');
$requestData = json_decode($rawInput, true);

if (!is_array($requestData)) {
    $requestData = [];
}

$action = $_GET['action'] ?? null;
$resourceId = $_GET['id'] ?? $_GET['resource_id'] ?? null;
$commentId = $_GET['comment_id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'comments') {
                $targetResourceId = $_GET['resource_id'] ?? null;
                getCommentsByResourceId($db, $targetResourceId);
            }

            if (isset($_GET['id'])) {
                getResourceById($db, $_GET['id']);
            }

            getAllResources($db);
            break;

        case 'POST':
            if ($action === 'comment') {
                createComment($db, $requestData);
                break;
            }

            // Creating resources requires admin
            requireApiAdmin();
            createResource($db, $requestData);
            break;

        case 'PUT':
            // Updating resources requires admin
            requireApiAdmin();
            updateResource($db, $requestData);
            break;

        case 'DELETE':
            if ($action === 'delete_comment') {
                $targetCommentId = $commentId ?? ($requestData['comment_id'] ?? null);
                deleteComment($db, $targetCommentId);
                break;
            }

            // Deleting resources requires admin
            requireApiAdmin();
            $targetResourceId = $_GET['id'] ?? ($requestData['id'] ?? null);
            deleteResource($db, $targetResourceId);
            break;

        default:
            sendResponse([
                'success' => false,
                'message' => 'Method not allowed.',
            ], 405);
    }
} catch (PDOException $exception) {
    error_log('Resources API database error: ' . $exception->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'A database error occurred.',
    ], 500);
} catch (Exception $exception) {
    error_log('Resources API error: ' . $exception->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'An unexpected error occurred.',
    ], 500);
}

function getAllResources(PDO $db): void
{
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'desc';

    $allowedSortFields = ['title', 'created_at'];
    $allowedOrder = ['asc', 'desc'];

    if (!in_array($sort, $allowedSortFields, true)) {
        $sort = 'created_at';
    }

    if (!in_array(strtolower($order), $allowedOrder, true)) {
        $order = 'desc';
    }

    $query = 'SELECT id, title, description, link, created_at FROM resources';
    $params = [];

    if ($search !== '') {
        $query .= ' WHERE title LIKE :search OR description LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    $query .= " ORDER BY {$sort} " . strtoupper($order);

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data' => $results,
    ]);
}

function getResourceById(PDO $db, $resourceId): void
{
    if (!is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'A valid resource ID is required.',
        ], 400);
    }

    $stmt = $db->prepare('SELECT id, title, description, link, created_at FROM resources WHERE id = :id');
    $stmt->bindValue(':id', (int) $resourceId, PDO::PARAM_INT);
    $stmt->execute();
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resource) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.',
        ], 404);
    }

    sendResponse([
        'success' => true,
        'data' => $resource,
    ]);
}

function createResource(PDO $db, array $data): void
{
    $required = validateRequiredFields($data, ['title', 'link']);
    if (!$required['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $required['missing'],
        ], 400);
    }

    $title = sanitizeInput($data['title']);
    $link = trim($data['link']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';

    if (!validateUrl($link)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid URL format.',
        ], 400);
    }

    $stmt = $db->prepare('INSERT INTO resources (title, description, link) VALUES (:title, :description, :link)');
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':description', $description);
    $stmt->bindValue(':link', $link);
    $stmt->execute();

    $newId = (int) $db->lastInsertId();

    sendResponse([
        'success' => true,
        'message' => 'Resource created successfully.',
        'id' => $newId,
    ], 201);
}

function updateResource(PDO $db, array $data): void
{
    if (empty($data['id']) || !is_numeric($data['id'])) {
        sendResponse([
            'success' => false,
            'message' => 'A valid resource ID is required.',
        ], 400);
    }

    $resourceId = (int) $data['id'];
    $stmt = $db->prepare('SELECT id FROM resources WHERE id = :id');
    $stmt->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $stmt->execute();

    if (!$stmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.',
        ], 404);
    }

    $fields = [];
    $params = [':id' => $resourceId];

    if (array_key_exists('title', $data)) {
        $fields[] = 'title = :title';
        $params[':title'] = sanitizeInput($data['title']);
    }

    if (array_key_exists('description', $data)) {
        $fields[] = 'description = :description';
        $params[':description'] = sanitizeInput($data['description'] ?? '');
    }

    if (array_key_exists('link', $data)) {
        if (!validateUrl($data['link'])) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid URL format.',
            ], 400);
        }

        $fields[] = 'link = :link';
        $params[':link'] = trim($data['link']);
    }

    if (!$fields) {
        sendResponse([
            'success' => false,
            'message' => 'No fields provided for update.',
        ], 400);
    }

    $fields[] = 'updated_at = CURRENT_TIMESTAMP';

    $query = 'UPDATE resources SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $db->prepare($query);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();

    sendResponse([
        'success' => true,
        'message' => 'Resource updated successfully.',
    ]);
}

function deleteResource(PDO $db, $resourceId): void
{
    if (!is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'A valid resource ID is required.',
        ], 400);
    }

    $resourceId = (int) $resourceId;

    $stmt = $db->prepare('SELECT id FROM resources WHERE id = :id');
    $stmt->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $stmt->execute();

    if (!$stmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.',
        ], 404);
    }

    $db->beginTransaction();

    try {
        $deleteComments = $db->prepare('DELETE FROM comments_resource WHERE resource_id = :id');
        $deleteComments->bindValue(':id', $resourceId, PDO::PARAM_INT);
        $deleteComments->execute();

        $deleteResource = $db->prepare('DELETE FROM resources WHERE id = :id');
        $deleteResource->bindValue(':id', $resourceId, PDO::PARAM_INT);
        $deleteResource->execute();

        $db->commit();

        sendResponse([
            'success' => true,
            'message' => 'Resource deleted successfully.',
        ]);
    } catch (Exception $exception) {
        $db->rollBack();
        throw $exception;
    }
}

function getCommentsByResourceId(PDO $db, $resourceId): void
{
    if (!is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'A valid resource ID is required.',
        ], 400);
    }

    $stmt = $db->prepare('SELECT id, resource_id, author, text, created_at FROM comments_resource WHERE resource_id = :id ORDER BY created_at ASC');
    $stmt->bindValue(':id', (int) $resourceId, PDO::PARAM_INT);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data' => $comments,
    ]);
}

function createComment(PDO $db, array $data): void
{
    $required = validateRequiredFields($data, ['resource_id', 'author', 'text']);
    if (!$required['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $required['missing'],
        ], 400);
    }

    if (!is_numeric($data['resource_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'A valid resource ID is required.',
        ], 400);
    }

    $resourceId = (int) $data['resource_id'];
    $stmt = $db->prepare('SELECT id FROM resources WHERE id = :id');
    $stmt->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $stmt->execute();

    if (!$stmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.',
        ], 404);
    }

    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    $stmt = $db->prepare('INSERT INTO comments_resource (resource_id, author, text) VALUES (:resource_id, :author, :text)');
    $stmt->bindValue(':resource_id', $resourceId, PDO::PARAM_INT);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', $text);
    $stmt->execute();

    sendResponse([
        'success' => true,
        'message' => 'Comment added successfully.',
        'id' => (int) $db->lastInsertId(),
    ], 201);
}

function deleteComment(PDO $db, $commentId): void
{
    if (!is_numeric($commentId)) {
        sendResponse([
            'success' => false,
            'message' => 'A valid comment ID is required.',
        ], 400);
    }

    $stmt = $db->prepare('SELECT id FROM comments_resource WHERE id = :id');
    $stmt->bindValue(':id', (int) $commentId, PDO::PARAM_INT);
    $stmt->execute();

    if (!$stmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Comment not found.',
        ], 404);
    }

    $delete = $db->prepare('DELETE FROM comments_resource WHERE id = :id');
    $delete->bindValue(':id', (int) $commentId, PDO::PARAM_INT);
    $delete->execute();

    sendResponse([
        'success' => true,
        'message' => 'Comment deleted successfully.',
    ]);
}

function sendResponse(array $data, int $statusCode = 200): void
{
    if (!array_key_exists('success', $data)) {
        $data['success'] = $statusCode >= 200 && $statusCode < 300;
    }

    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function validateUrl(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput(?string $value): string
{
    if ($value === null) {
        return '';
    }

    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function validateRequiredFields(array $data, array $requiredFields): array
{
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            $missing[] = $field;
        }
    }

    return [
        'valid' => count($missing) === 0,
        'missing' => $missing,
    ];
}

