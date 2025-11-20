<?php

/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    header('HTTP/1.1 200 OK');
    exit();
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once __DIR__ . '/../../common/db.php';

// TODO: Get the PDO database connection
$pdo = getDatabaseConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true);
// TODO: Parse query parameters for filtering and searching
// $filterParams = $body['filterParams'];
// $searchParams = $body['searchParams'];
/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields
    // We'll accept search from either query string or parsed JSON body
    global $searchParams, $filterParams;

    $search = '';
    if (!empty($_GET['search'])) {
        $search = trim($_GET['search']);
    } elseif (!empty($searchParams)) {
        if (is_array($searchParams) && isset($searchParams['search'])) {
            $search = trim($searchParams['search']);
        } elseif (is_string($searchParams)) {
            $search = trim($searchParams);
        }
    }

    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
    $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';
    $allowedSortFields = ['name', 'student_id', 'email'];
    if (!in_array($sort, $allowedSortFields, true)) {
        $sort = 'name';
    }

    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    $sql = 'SELECT id, name, email, created_at FROM users';
    if ($search !== '') {
        $sql .= ' WHERE name LIKE :search1 OR id LIKE :search2 OR email LIKE :search3';
    }
    $sql .= " ORDER BY $sort $order";

    try {
        $stmt = $db->prepare($sql);

        // TODO: Bind parameters if using search
        if ($search !== '') {
            $term = "%$search%";
            $stmt->bindValue(':search1', $term, PDO::PARAM_STR);
            $stmt->bindValue(':search2', $term, PDO::PARAM_STR);
            $stmt->bindValue(':search3', $term, PDO::PARAM_STR);
        }

        // TODO: Execute the query
        $stmt->execute();

        // TODO: Fetch all results as an associative array
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // TODO: Return JSON response with success status and data
        http_response_code(200);
        return json_encode([
            'success' => true,
            'data' => $students
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while fetching students.',
            'error' => $e->getMessage()
        ]);
        return;
    }
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    $sql = "SELECT id, name, email, created_at FROM users WHERE id = ?";
    // TODO: Bind the student_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $studentId, PDO::PARAM_INT);
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch the result
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status
    if ($student) {
        http_response_code(200);
        return json_encode([
            "success"=> true,
            "data"=> $student
            ]);
    } else {
        http_response_code(404);
        return json_encode([
            "success"=> false,
            "message" => "Student not found"
            ]);
    }
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    if (!isset($data['student_id'], $data['name'], $data['email'], $data['password'])) {
        $response = array(
            'status' => 'error',
            'message' => 'Missing required fields'
        );
        return json_encode($response);
        exit();
    }
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
    $email = sanitizeInput($data['email']);
    $password = sanitizeInput($data['password']);
    $student_id = sanitizeInput($data['student_id']);
    $student_name = sanitizeInput($data['name']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return json_encode([
            'status'=> 'error',
            'message'=> 'invalid email format'
            ]);
    }

    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $sql = 'SELECT id,email FROM users WHERE id LIKE ? OR email LIKE ?';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $student_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $email, PDO::PARAM_STR);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student) {
        http_response_code(409);
        return json_encode([
            'status'=> 'error',
            'message'=> 'Student ID or email already exists'
        ]);
    }   
    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // TODO: Prepare INSERT query
    $sql = 'INSERT INTO users (id, name, email, password, created_at) VALUES (?, ?, ?, ?, NOW())';
    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $student_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $student_name, PDO::PARAM_STR);
    $stmt->bindParam(3, $email, PDO::PARAM_STR);
    $stmt->bindParam(4, $hashed_password, PDO::PARAM_STR);
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status
    $newId = $db->lastInsertId();
    if ($newId) {
        http_response_code(201);
        return json_encode([
            'status'=> 'success',
            'message'=> 'Student created successfully',
            'student_id'=> $newId
        ]);
    } else {
        http_response_code(500);
        return json_encode([
            'status'=> 'error',
            'message'=> 'Failed to create student'
        ]);
    }
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (!isset($data['student_id'])) {
        http_response_code(400);
        return json_encode([
            'status'=> 'error',
            'message'=> 'student_id is required'
            ]);
    }
    $student_id = $data['student_id'];

    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    $sql = 'SELECT id FROM users WHERE id LIKE ?';
    $stmt = $db->prepare($sql);
    $stmt->bindParam('', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!isset($student)) {
        http_response_code(404);
        return json_encode([
            'status'=> 'error',
            'message'=> 'Student not found'
            ]);
    }
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $fields = [];
    $params = [];
     if (isset($data['name'])) {
        $name = trim($data['name']);
        $fields[] = 'name = ?';
        $params[] = $name;
    }


    // TODO: If email is being updated, check if new email already exists
    // Prepare and execute a SELECT query
    // Exclude the current student from the check
    // If duplicate found, return error response with 409 status
    if (isset($data['email'])) {
        $email = trim($data['email']);
        // validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return json_encode([
                'status' => 'error',
                'message' => 'Invalid email format'
            ]);
        }

        // check if email already exists for another user
        $checkSql = 'SELECT id FROM users WHERE email = ? AND id != ?';
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$email, $student_id]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            http_response_code(409);
            return json_encode([
                'status' => 'error',
                'message' => 'Email already in use by another student'
            ]);
        }

        $fields[] = 'email = ?';
        $params[] = $email;
    }
     if (count($fields) === 0) {
        http_response_code(400);
        return json_encode([
            'status' => 'error',
            'message' => 'No fields provided to update'
        ]);
    }

    // TODO: Bind parameters dynamically
    // Bind only the parameters that are being updated
    $sqlUpdate = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $params[] = $student_id; // WHERE param
    // TODO: Execute the query
    try {
        $updateStmt = $db->prepare($sqlUpdate);
        $success = $updateStmt->execute($params);
        // TODO: Check if update was successful

        if ($success) { 
            // If yes, return success response
            // You may want to fetch the updated row to return it
            $fetchSql = 'SELECT id, name, email, created_at FROM users WHERE id = ?';
            $fetchStmt = $db->prepare($fetchSql);
            $fetchStmt->execute([$student_id]);
            $updatedStudent = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            http_response_code(200);
            return json_encode([
                'status' => 'success',
                'message' => 'Student updated successfully',
                'data' => $updatedStudent
            ]);
        } else {
            // If no, return error response with 500 status
            http_response_code(500);
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update student'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        return json_encode([
            'status' => 'error',
            'message' => 'Database error during update',
            'error' => $e->getMessage()
        ]);
    }
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (!isset($studentId) || empty($studentId)) {
        http_response_code(400);
        return json_encode([
            'status'=> 'error',
            'message'=> 'student_id is required'
            ]);
    }
    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $sql = 'SELECT id FROM users WHERE id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $studentId, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        http_response_code(404);
        return json_encode([
            'status'=> 'error',
            'message'=> 'Student not found'
            ]);
    }
    // TODO: Prepare DELETE query
    $sql = 'DELETE FROM users WHERE id = ?';
    $stmt = $db->prepare($sql);
    // TODO: Bind the student_id parameter
    $stmt->bindParam(1, $studentId, PDO::PARAM_INT);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        return json_encode([
            'status'=> 'success',
            'message'=> 'Student deleted successfully'
            ]);
    } else {
        http_response_code(500);
        return json_encode([
            'status'=> 'error',
            'message'=> 'Failed to delete student'
            ]); 
    }
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
    if (!isset($data['student_id']) || !isset($data['current_password']) || !isset($data['new_password'])) {
        http_response_code(400);
        return json_encode([
            'status'=> 'error',
            'message'=> 'Missing required fields'
            ]);
    }
    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    if (strlen($data['new_password']) < 8) {
        http_response_code(400);
        return json_encode([
            'status'=> 'error',
            'message'=> 'New password must be at least 8 characters long'
            ]);
    }
    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    $sql = "SELECT password FROM users WHERE id LIKE ?";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $data['student_id'], PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!isset($student)) {
        http_response_code(404);
        return json_encode([
            'status'=> 'error',
            'message'=> 'Student not found'
            ]);
    }
    $currentPasswordHash = $student['password'];
    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
    if (!password_verify($data['current_password'], $currentPasswordHash)) {
        http_response_code(401);
        return json_encode([
            'status'=> 'error',
            'message'=> 'Current password is incorrect'
            ]);
    }
    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    $newHashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
    // TODO: Update password in database
    // Prepare UPDATE query
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    // TODO: Bind parameters and execute
    $stmt->bindParam(1, $newHashedPassword, PDO::PARAM_STR);
    $stmt->bindParam(2, $data['student_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        return json_encode([
            'status'=> 'success',
            'message'=> 'Password changed successfully'
            ]);
    } else {
        http_response_code(500);
        return json_encode([
            'status'=> 'error',
            'message'=> 'Failed to change password'
            ]); 
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
        if (isset($_GET['student_id'])) {
            echo getStudentById($pdo, $_GET['student_id']);
        } else {
            echo getStudents($pdo);
        }

        
    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
        if (isset($body['action']) && $body['action'] === 'change_password') {
            echo changePassword($pdo, $body);
        } else {
            echo createStudent($pdo, $body);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
        echo updateStudent($pdo, $body);
        
    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        if (isset($_GET['student_id'])) {
            echo deleteStudent($pdo, $_GET['student_id']);
        } elseif (isset($body['student_id'])) {
            echo deleteStudent($pdo, $body['student_id']);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'student_id is required'
            ], 400);
        }
        
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        sendResponse([
        'success' => false,
        'message' => 'Method Not Allowed'
            ], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred.',
        'error' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    sendResponse([
        'success' => false,
        'message' => 'An unexpected error occurred.',
        'error' => $e->getMessage()
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    // TODO: Echo JSON encoded data
    echo json_encode($data);
    // TODO: Exit to prevent further execution
    exit();
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data);
    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);
    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data);
    // Return sanitized data
    return $data;
}

?>
