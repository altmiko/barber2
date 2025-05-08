<?php
/**
 * Collection of helper functions for the Barberbook application
 */

/**
 * Sanitize user input
 * 
 * @param string $data The data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate a secure password hash
 * 
 * @param string $password The password to hash
 * @return string The hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * 
 * @param string $password The password to verify
 * @param string $hash The hash to verify against
 * @return bool True if password matches hash, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is a barber
 * 
 * @return bool True if user is a barber, false otherwise
 */
function isBarber() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'barber';
}

/**
 * Check if user is a customer
 * 
 * @return bool True if user is a customer, false otherwise
 */
function isCustomer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer';
}

/**
 * Redirect to a specific page
 * 
 * @param string $location The location to redirect to
 * @return void
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Display flash message
 * 
 * @return string HTML for flash message if it exists
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return "<div class='alert alert-{$type}'>{$message}</div>";
    }
    return '';
}

/**
 * Set flash message
 * 
 * @param string $message The message to flash
 * @param string $type The type of message (success, error, info, warning)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Format date and time for display
 * 
 * @param string $datetime The datetime to format
 * @param string $format The format to use
 * @return string Formatted datetime
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    $date = new DateTime($datetime);
    return $date->format($format);
}

/**
 * Format price for display
 * 
 * @param int $price The price to format
 * @return string Formatted price
 */
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

/**
 * Get user name
 * 
 * @param int $userID The user ID
 * @param string $userType The user type (customer or barber)
 * @param mysqli $conn The database connection
 * @return string The user's full name
 */
function getUserName($userID, $userType, $conn) {
    $table = ($userType === 'customer') ? 'Customers' : 'Barbers';
    $sql = "SELECT FirstName, LastName FROM {$table} WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['FirstName'] . ' ' . $row['LastName'];
    }
    
    return 'Unknown User';
}

/**
 * Send a notification email
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param int $customerID Customer ID
 * @param mysqli $conn Database connection
 * @return bool True if email sent successfully, false otherwise
 */
function sendNotification($to, $subject, $message, $customerID, $conn) {
    // In a real application, you would use a library like PHPMailer
    // For now, we'll just insert into the Notifications table
    $sql = "INSERT INTO Notifications (RecipientEmail, Subject, Body, Status, CustomerID) 
            VALUES (?, ?, ?, 'pending', ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $to, $subject, $message, $customerID);
    
    if ($stmt->execute()) {
        // In a real app, you would actually send the email here
        return true;
    }
    
    return false;
}