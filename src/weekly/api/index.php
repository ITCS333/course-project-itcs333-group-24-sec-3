<?php
/**
 * Weekly Course Breakdown API
 * RESTful API for managing weeks and comments.
 */

 /* ============================================================
    BASIC SETUP AND CONFIGURATION
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load database class (adjust path if needed)
require_once _DIR_ . '/../config/Database.php';

// Create DB connection
$database = new Database();
$db = $database->getConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Read request body for POST/PUT
$rawInput = file_get_contents('php://input');
$requestData = json_decode($rawInput, true);
if (!is_array($requestData)) {
    $requestData = [];
}

// Determine resource type: weeks or comments
$resource = isset($_GET['resource']) ? $_GET['resource'] : 'weeks';


/* ============================================================
   WEEKS CRUD OPERATIONS
   ============================================================ */

function getAllWeeks($db)
{
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'start_date';
    $order = isset($_GET['order']) ? strtolower($_GET['order']) : 'asc';

    $allowedSort = ['title', 'start_date', 'created_at'];
    if (!in_array($sort, $allowedSort)) {
        $sort = 'start_date';
    }

    $order = $order === 'desc' ? 'DESC' : 'ASC';

    $sql = "SELECT week_id, title, start_date, description, links, created_at 
            FROM weeks";
    $params = [];

    if ($search !== '') {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = "%{$search}%";
    }

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }

    $stmt->execute();
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($weeks as &$w) {
        $w['links'] = $w['links'] ? json_decode($w['links'], true) : [];
    }

    sendResponse(['success' => true, 'data' => $weeks]);
}

function getWeekById($db, $weekId)
{
    if (!$weekId) return sendError("week_id is required", 400);

    $sql = "SELECT week_id, title, start_date, description, links, created_at
            FROM weeks WHERE week_id = ? LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $weekId);
    $stmt->execute();

    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$week) return sendError("Week not found", 404);

    $week['links'] = $week['links'] ? json_decode($week['links'], true) : [];

    sendResponse(['success' => true, 'data' => $week]);
}

function createWeek($db, $data)
{
    $required = ['week_id', 'title', 'start_date', 'description'];
    foreach ($required as $r) {
        if (empty($data[$r])) return sendError("Missing field: $r", 400);
    }

    $week_id = sanitizeInput($data['week_id']);
    $title = sanitizeInput($data['title']);
    $start_date = sanitizeInput($data['start_date']);
    $description = sanitizeInput($data['description']);

    if (!validateDate($start_date))
        return sendError("Invalid date format (use YYYY-MM-DD)", 400);

    // Check duplicate
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id = ? LIMIT 1");
    $check->bindValue(1, $week_id);
    $check->execute();
    if ($check->fetch()) return sendError("week_id already exists", 409);

    $links = isset($data['links']) && is_array($data['links']) ?
             json_encode($data['links']) : "[]";

    $sql = "INSERT INTO weeks (week_id, title, start_date, description, links)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $week_id);
    $stmt->bindValue(2, $title);
    $stmt->bindValue(3, $start_date);
    $stmt->bindValue(4, $description);
    $stmt->bindValue(5, $links);

    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'data' => [
                'week_id' => $week_id,
                'title' => $title,
                'start_date' => $start_date,
                'description' => $description,
                'links' => json_decode($links, true)
            ]
        ], 201);
    } else {
        sendError("Failed to create week", 500);
    }
}

function updateWeek($db, $data)
{
    if (empty($data['week_id'])) return sendError("week_id is required", 400);

    $weekId = sanitizeInput($data['week_id']);

    // Check existence
    $check = $db->prepare("SELECT * FROM weeks WHERE week_id = ? LIMIT 1");
    $check->bindValue(1, $weekId);
    $check->execute();

    if (!$check->fetch()) return sendError("Week not found", 404);

    $fields = [];
    $values = [];

    if (isset($data['title'])) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }

    if (isset($data['start_date'])) {
        if (!validateDate($data['start_date']))
            return sendError("Invalid date format", 400);
        $fields[] = "start_date = ?";
        $values[] = sanitizeInput($data['start_date']);
    }

    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data['description']);
    }

    if (isset($data['links'])) {
        $fields[] = "links = ?";
        $values[] = json_encode($data['links']);
    }

    if (empty($fields)) return sendError("No fields to update", 400);

    $fields[] = "updated_at = CURRENT_TIMESTAMP";

    $sql = "UPDATE weeks SET " . implode(", ", $fields) . " WHERE week_id = ?";
    $stmt = $db->prepare($sql);

    $i = 1;
    foreach ($values as $v) {
        $stmt->bindValue($i++, $v);
    }
    $stmt->bindValue($i, $weekId);

    if ($stmt->execute()) {
        getWeekById($db, $weekId);
    } else {
        sendError("Failed to update week", 500);
    }
}

function deleteWeek($db, $weekId)
{
    if (!$weekId) return sendError("week_id is required", 400);

    $check = $db->prepare("SELECT id FROM weeks WHERE week_id = ? LIMIT 1");
    $check->bindValue(1, $weekId);
    $check->execute();

    if (!$check->fetch()) return sendError("Week not found", 404);

    // Delete comments first
    $delC = $db->prepare("DELETE FROM comments WHERE week_id = ?");
    $delC->bindValue(1, $weekId);
    $delC->execute();

    // Delete week
    $del = $db->prepare("DELETE FROM weeks WHERE week_id = ?");
    $del->bindValue(1, $weekId);

    if ($del->execute()) {
        sendResponse([
            'success' => true,
            'message' => "Week and related comments deleted"
        ]);
    } else {
        sendError("Failed to delete week", 500);
    }
}


/* ============================================================
   COMMENTS CRUD OPERATIONS
   ============================================================ */

function getCommentsByWeek($db, $weekId)
{
    if (!$weekId) return sendError("week_id is required", 400);

    $sql = "SELECT id, week_id, author, text, created_at
            FROM comments WHERE week_id = ?
            ORDER BY created_at ASC";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $weekId);
    $stmt->execute();

    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $comments]);
}

function createComment($db, $data)
{
    $required = ['week_id', 'author', 'text'];
    foreach ($required as $r) {
        if (empty($data[$r])) return sendError("Missing field: $r", 400);
    }

    $week_id = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    if ($text === '') return sendError("Comment text cannot be empty", 400);

    // Check week exists
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id = ? LIMIT 1");
    $check->bindValue(1, $week_id);
    $check->execute();

    if (!$check->fetch()) return sendError("Week does not exist", 404);

    $sql = "INSERT INTO comments (week_id, author, text)
            VALUES (?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $week_id);
    $stmt->bindValue(2, $author);
    $stmt->bindValue(3, $text);

    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'data' => [
                'id' => $db->lastInsertId(),
                'week_id' => $week_id,
                'author' => $author,
                'text' => $text
            ]
        ], 201);
    } else {
        sendError("Failed to create comment", 500);
    }
}

function deleteComment($db, $commentId)
{
    if (!$commentId) return sendError("id is required", 400);

    $check = $db->prepare("SELECT id FROM comments WHERE id = ? LIMIT 1");
    $check->bindValue(1, $commentId);
    $check->execute();

    if (!$check->fetch()) return sendError("Comment not found", 404);

    $del = $db->prepare("DELETE FROM comments WHERE id = ?");
    $del->bindValue(1, $commentId);

    if ($del->execute()) {
        sendResponse(['success' => true, 'message' => "Comment deleted"]);
    } else {
        sendError("Failed to delete comment", 500);
    }
}


/* ============================================================
   REQUEST ROUTER
   ============================================================ */

try {

    if ($resource === 'weeks') {

        if ($method === 'GET') {
            $weekId = isset($_GET['week_id']) ? $_GET['week_id'] : null;
            if ($weekId) getWeekById($db, $weekId);
            else getAllWeeks($db);

        } elseif ($method === 'POST') {
            createWeek($db, $requestData);

        } elseif ($method === 'PUT') {
            updateWeek($db, $requestData);

        } elseif ($method === 'DELETE') {
            $weekId = isset($_GET['week_id']) ? $_GET['week_id'] :
                      ($requestData['week_id'] ?? null);
            deleteWeek($db, $weekId);

        } else {
            sendError("Method Not Allowed", 405);
        }

    } elseif ($resource === 'comments') {

        if ($method === 'GET') {
            $weekId = $_GET['week_id'] ?? null;
            getCommentsByWeek($db, $weekId);

        } elseif ($method === 'POST') {
            createComment($db, $requestData);

        } elseif ($method === 'DELETE') {
            $commentId = $_GET['id'] ?? ($requestData['id'] ?? null);
            deleteComment($db, $commentId);

        } else {
            sendError("Method Not Allowed", 405);
        }

    } else {
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }

} catch (Exception $e) {
    sendError("Server error", 500);
}


/* ============================================================
   HELPER FUNCTIONS
   ============================================================ */

function sendResponse($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function sendError($message, $status = 400)
{
    sendResponse([
        'success' => false,
        'error' => $message
    ], $status);
}

function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
