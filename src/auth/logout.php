<?php
/**
 * Logout Handler
 * 
 * This script destroys the user's session and redirects to the login page.
 */

require_once __DIR__ . '/../common/auth.php';

// Call the logout function from auth.php
logout();

// Redirect to login page
header('Location: /src/auth/login.html');
exit;
