<?php
/**
 * Discussion Board API
 * 
 * This is a RESTful API that handles all CRUD operations for the discussion board.
 * It manages both discussion topics and their replies.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: topics
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - topic_id (VARCHAR(50), UNIQUE) - The topic's unique identifier (e.g., "topic_1234567890")
 *   - subject (VARCHAR(255)) - The topic subject/title
 *   - message (TEXT) - The main topic message
 *   - author (VARCHAR(100)) - The author's name
 *   - created_at (TIMESTAMP) - When the topic was created
 * 
 * Table: replies
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - reply_id (VARCHAR(50), UNIQUE) - The reply's unique identifier (e.g., "reply_1234567890")
 *   - topic_id (VARCHAR(50)) - Foreign key to topics.topic_id
 *   - text (TEXT) - The reply message
 *   - author (VARCHAR(100)) - The reply author's name
 *   - created_at (TIMESTAMP) - When the reply was created
 * 
 * API Endpoints:
 * 
 * Topics:
 *   GET    /api/discussion.php?resource=topics              - Get all topics (with optional search)
 *   GET    /api/discussion.php?resource=topics&id={id}      - Get single topic
 *   POST   /api/discussion.php?resource=topics              - Create new topic
 *   PUT    /api/discussion.php?resource=topics              - Update a topic
 *   DELETE /api/discussion.php?resource=topics&id={id}      - Delete a topic
 * 
 * Replies:
 *   GET    /api/discussion.php?resource=replies&topic_id={id} - Get all replies for a topic
 *   POST   /api/discussion.php?resource=replies              - Create new reply
 *   DELETE /api/discussion.php?resource=replies&id={id}      - Delete a reply
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once __DIR__ . '/../../common/db.php';


// TODO: Get the PDO database connection
// $db = $database->getConnection();
$db = getDatabaseConnection();


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$requestBody = '';
$data = null;
if ($method === 'POST' || $method === 'PUT') {
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
}


// TODO: Parse query parameters for filtering and searching
$resource = $_GET['resource'] ?? null;


// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

/**
 * Function: Get all topics or search for specific topics
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by subject, message, or author
 *   - sort: Optional field to sort by (subject, author, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 */
function getAllTopics($db) {
    // TODO: Initialize base SQL query
    // Select topic_id, subject, message, author, and created_at (formatted as date)
    $query = "SELECT topic_id, subject, message, author, DATE(created_at) as created_at FROM topics";
    
    // TODO: Initialize an array to hold bound parameters
    $params = [];
    
    // TODO: Check if search parameter exists in $_GET
    // If yes, add WHERE clause using LIKE for subject, message, OR author
    // Add the search term to the params array
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $query .= " WHERE subject LIKE ? OR message LIKE ? OR author LIKE ?";
        $searchTerm = "%{$_GET['search']}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // TODO: Add ORDER BY clause
    // Check for sort and order parameters in $_GET
    // Validate the sort field (only allow: subject, author, created_at)
    // Validate order (only allow: asc, desc)
    // Default to ordering by created_at DESC
    $allowedSortFields = ['subject', 'author', 'created_at'];
    $sort = $_GET['sort'] ?? 'created_at';
    if (!in_array($sort, $allowedSortFields)) {
        $sort = 'created_at';
    }
    
    $order = $_GET['order'] ?? 'desc';
    if ($order !== 'asc' && $order !== 'desc') {
        $order = 'desc';
    }
    
    $query .= " ORDER BY $sort $order";
    
    // TODO: Prepare the SQL statement
    $stmt = $db->prepare($query);
    
    // TODO: Bind parameters if search was used
    // Loop through $params array and bind each parameter
    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i], PDO::PARAM_STR);
        }
    }
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response with success status and data
    // Call sendResponse() helper function or echo json_encode directly
    sendResponse(['success' => true, 'data' => $topics]);
}


/**
 * Function: Get a single topic by topic_id
 * Method: GET
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function getTopicById($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If empty, return error with 400 status
    if (empty($topicId)) {
        sendError('Topic id is required', 400);
        return;
    }
    
    // TODO: Prepare SQL query to select topic by topic_id
    // Select topic_id, subject, message, author, and created_at
    $query = "SELECT topic_id, subject, message, author, DATE(created_at) as created_at FROM topics WHERE topic_id = ?";
    $stmt = $db->prepare($query);
    
    // TODO: Prepare and bind the topic_id parameter
    $stmt->bindValue(1, $topicId, PDO::PARAM_STR);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch the result
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if topic exists
    // If topic found, return success response with topic data
    // If not found, return error with 404 status
    if ($topic) {
        sendResponse(['success' => true, 'data' => $topic]);
    } else {
        sendError('Topic not found', 404);
    }
}


/**
 * Function: Create a new topic
 * Method: POST
 * 
 * Required JSON Body:
 *   - topic_id: Unique identifier (e.g., "topic_1234567890")
 *   - subject: Topic subject/title
 *   - message: Main topic message
 *   - author: Author's name
 */
function createTopic($db, $data) {
    // TODO: Validate required fields
    // Check if topic_id, subject, message, and author are provided
    // If any required field is missing, return error with 400 status
    if (!isset($data['topic_id']) || !isset($data['subject']) || !isset($data['message']) || !isset($data['author'])) {
        sendError('Missing required fields: topic_id, subject, message, and author are required', 400);
        return;
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all string fields
    // Use the sanitizeInput() helper function
    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);
    
    // TODO: Check if topic_id already exists
    // Prepare and execute a SELECT query to check for duplicate
    // If duplicate found, return error with 409 status (Conflict)
    $checkStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = ?");
    $checkStmt->execute([$topicId]);
    if ($checkStmt->fetch()) {
        sendError('Topic with this topic_id already exists', 409);
        return;
    }
    
    // TODO: Prepare INSERT query
    // Insert topic_id, subject, message, and author
    // The created_at field should auto-populate with CURRENT_TIMESTAMP
    $query = "INSERT INTO topics (topic_id, subject, message, author) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    // TODO: Prepare the statement and bind parameters
    // Bind all the sanitized values
    $stmt->bindValue(1, $topicId, PDO::PARAM_STR);
    $stmt->bindValue(2, $subject, PDO::PARAM_STR);
    $stmt->bindValue(3, $message, PDO::PARAM_STR);
    $stmt->bindValue(4, $author, PDO::PARAM_STR);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // Include the topic_id in the response
    // If no, return error with 500 status
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'data' => ['topic_id' => $topicId]], 201);
    } else {
        sendError('Failed to create topic', 500);
    }
}


/**
 * Function: Update an existing topic
 * Method: PUT
 * 
 * Required JSON Body:
 *   - topic_id: The topic's unique identifier
 *   - subject: Updated subject (optional)
 *   - message: Updated message (optional)
 */
function updateTopic($db, $data) {
    // TODO: Validate that topic_id is provided
    // If not provided, return error with 400 status
    if (!isset($data['topic_id'])) {
        sendError('topic_id is required', 400);
        return;
    }
    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    $checkStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = ?");
    $checkStmt->execute([$data['topic_id']]);
    if (!$checkStmt->fetch()) {
        sendError('Topic not found', 404);
        return;
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $updates = [];
    $params = [];
    
    if (isset($data['subject'])) {
        $updates[] = "subject = ?";
        $params[] = sanitizeInput($data['subject']);
    }
    
    if (isset($data['message'])) {
        $updates[] = "message = ?";
        $params[] = sanitizeInput($data['message']);
    }
    
    // TODO: Check if there are any fields to update
    // If $updates array is empty, return error
    if (empty($updates)) {
        sendError('No fields to update', 400);
        return;
    }
    
    // TODO: Complete the UPDATE query
    $query = "UPDATE topics SET " . implode(', ', $updates) . " WHERE topic_id = ?";
    $params[] = $data['topic_id'];
    
    // TODO: Prepare statement and bind parameters
    // Bind all parameters from the $params array
    $stmt = $db->prepare($query);
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i], PDO::PARAM_STR);
    }
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if update was successful
    // If yes, return success response
    // If no rows affected, return appropriate message
    // If error, return error with 500 status
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Topic updated successfully']);
    } else {
        sendError('No changes made to topic', 400);
    }
}


/**
 * Function: Delete a topic
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function deleteTopic($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not, return error with 400 status
    if (empty($topicId)) {
        sendError('Topic id is required', 400);
        return;
    }
    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    $checkStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = ?");
    $checkStmt->execute([$topicId]);
    if (!$checkStmt->fetch()) {
        sendError('Topic not found', 404);
        return;
    }
    
    // TODO: Delete associated replies first (foreign key constraint)
    // Prepare DELETE query for replies table
    $deleteRepliesStmt = $db->prepare("DELETE FROM replies WHERE topic_id = ?");
    $deleteRepliesStmt->execute([$topicId]);
    
    // TODO: Prepare DELETE query for the topic
    $deleteTopicStmt = $db->prepare("DELETE FROM topics WHERE topic_id = ?");
    
    // TODO: Prepare, bind, and execute
    $deleteTopicStmt->bindValue(1, $topicId, PDO::PARAM_STR);
    $deleteTopicStmt->execute();
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if ($deleteTopicStmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Topic and associated replies deleted successfully']);
    } else {
        sendError('Failed to delete topic', 500);
    }
}


// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

/**
 * Function: Get all replies for a specific topic
 * Method: GET
 * 
 * Query Parameters:
 *   - topic_id: The topic's unique identifier
 */
function getRepliesByTopicId($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not provided, return error with 400 status
    if (empty($topicId)) {
        sendError('topic_id is required', 400);
        return;
    }
    
    // TODO: Prepare SQL query to select all replies for the topic
    // Select reply_id, topic_id, text, author, and created_at (formatted as date)
    // Order by created_at ASC (oldest first)
    $query = "SELECT reply_id, topic_id, text, author, DATE(created_at) as created_at FROM replies WHERE topic_id = ? ORDER BY created_at ASC";
    $stmt = $db->prepare($query);
    
    // TODO: Prepare and bind the topic_id parameter
    $stmt->bindValue(1, $topicId, PDO::PARAM_STR);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response
    // Even if no replies found, return empty array (not an error)
    sendResponse(['success' => true, 'data' => $replies]);
}


/**
 * Function: Create a new reply
 * Method: POST
 * 
 * Required JSON Body:
 *   - reply_id: Unique identifier (e.g., "reply_1234567890")
 *   - topic_id: The parent topic's identifier
 *   - text: Reply message text
 *   - author: Author's name
 */
function createReply($db, $data) {
    // TODO: Validate required fields
    // Check if reply_id, topic_id, text, and author are provided
    // If any field is missing, return error with 400 status
    if (!isset($data['reply_id']) || !isset($data['topic_id']) || !isset($data['text']) || !isset($data['author'])) {
        sendError('Missing required fields: reply_id, topic_id, text, and author are required', 400);
        return;
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $replyId = sanitizeInput($data['reply_id']);
    $topicId = sanitizeInput($data['topic_id']);
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author']);
    
    // TODO: Verify that the parent topic exists
    // Prepare and execute SELECT query on topics table
    // If topic doesn't exist, return error with 404 status (can't reply to non-existent topic)
    $checkTopicStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = ?");
    $checkTopicStmt->execute([$topicId]);
    if (!$checkTopicStmt->fetch()) {
        sendError('Topic not found', 404);
        return;
    }
    
    // TODO: Check if reply_id already exists
    // Prepare and execute SELECT query to check for duplicate
    // If duplicate found, return error with 409 status
    $checkReplyStmt = $db->prepare("SELECT reply_id FROM replies WHERE reply_id = ?");
    $checkReplyStmt->execute([$replyId]);
    if ($checkReplyStmt->fetch()) {
        sendError('Reply with this reply_id already exists', 409);
        return;
    }
    
    // TODO: Prepare INSERT query
    // Insert reply_id, topic_id, text, and author
    $query = "INSERT INTO replies (reply_id, topic_id, text, author) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    // TODO: Prepare statement and bind parameters
    $stmt->bindValue(1, $replyId, PDO::PARAM_STR);
    $stmt->bindValue(2, $topicId, PDO::PARAM_STR);
    $stmt->bindValue(3, $text, PDO::PARAM_STR);
    $stmt->bindValue(4, $author, PDO::PARAM_STR);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status
    // Include the reply_id in the response
    // If no, return error with 500 status
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'data' => ['reply_id' => $replyId]], 201);
    } else {
        sendError('Failed to create reply', 500);
    }
}


/**
 * Function: Delete a reply
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The reply's unique identifier
 */
function deleteReply($db, $replyId) {
    // TODO: Validate that replyId is provided
    // If not, return error with 400 status
    if (empty($replyId)) {
        sendError('Reply id is required', 400);
        return;
    }
    
    // TODO: Check if reply exists
    // Prepare and execute SELECT query
    // If not found, return error with 404 status
    $checkStmt = $db->prepare("SELECT reply_id FROM replies WHERE reply_id = ?");
    $checkStmt->execute([$replyId]);
    if (!$checkStmt->fetch()) {
        sendError('Reply not found', 404);
        return;
    }
    
    // TODO: Prepare DELETE query
    $query = "DELETE FROM replies WHERE reply_id = ?";
    $stmt = $db->prepare($query);
    
    // TODO: Prepare, bind, and execute
    $stmt->bindValue(1, $replyId, PDO::PARAM_STR);
    $stmt->execute();
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Reply deleted successfully']);
    } else {
        sendError('Failed to delete reply', 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on resource and HTTP method
    if (!isValidResource($resource)) {
        sendError("Invalid resource. Use 'topics' or 'replies'", 400);
        exit;
    }
    
    // TODO: For GET requests, check for 'id' parameter in $_GET
    if ($resource === 'topics') {
        if ($method === 'GET') {
            if (isset($_GET['id'])) {
                getTopicById($db, $_GET['id']);
            } else {
                getAllTopics($db);
            }
        } elseif ($method === 'POST') {
            createTopic($db, $data);
        } elseif ($method === 'PUT') {
            updateTopic($db, $data);
        } elseif ($method === 'DELETE') {
            // TODO: For DELETE requests, get id from query parameter or request body
            $topicId = $_GET['id'] ?? ($data['id'] ?? null);
            deleteTopic($db, $topicId);
        } else {
            // TODO: For unsupported methods, return 405 Method Not Allowed
            sendError('Method not allowed', 405);
        }
    } elseif ($resource === 'replies') {
        if ($method === 'GET') {
            $topicId = $_GET['topic_id'] ?? null;
            getRepliesByTopicId($db, $topicId);
        } elseif ($method === 'POST') {
            createReply($db, $data);
        } elseif ($method === 'DELETE') {
            // TODO: For DELETE requests, get id from query parameter or request body
            $replyId = $_GET['id'] ?? ($data['id'] ?? null);
            deleteReply($db, $replyId);
        } else {
            // TODO: For unsupported methods, return 405 Method Not Allowed
            sendError('Method not allowed', 405);
        }
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // DO NOT expose the actual error message to the client (security risk)
    // Log the error for debugging (optional)
    // Return generic error response with 500 status
    sendError('Database error occurred', 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error for debugging
    // Return error response with 500 status
    sendError('An error occurred', 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    // TODO: Echo JSON encoded data
    // Make sure to handle JSON encoding errors
    $json = json_encode($data);
    if ($json === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'JSON encoding error']);
        exit;
    }
    echo $json;
    
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    sendResponse(['success' => false, 'error' => $message], $statusCode);
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Check if data is a string
    // If not, return as is or convert to string
    if (!is_string($data)) {
        $data = (string)$data;
    }
    
    // TODO: Trim whitespace from both ends
    $data = trim($data);
    
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);
    
    // TODO: Convert special characters to HTML entities (prevents XSS)
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate resource name
 * 
 * @param string $resource - Resource name to validate
 * @return bool - True if valid, false otherwise
 */
function isValidResource($resource) {
    // TODO: Define allowed resources
    $allowedResources = ['topics', 'replies'];
    
    // TODO: Check if resource is in the allowed list
    return in_array($resource, $allowedResources);
}

?>
