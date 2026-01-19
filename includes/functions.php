<?php
/**
 * Core Application Functions
 * Contains all business logic for the fund management system
 */

// Start session
session_start();

// Include dependencies
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/security.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// ========================================
// Authentication Functions
// ========================================

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require user to be logged in
 * Redirects to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    return $_SESSION['user_id'];
}

/**
 * Wrapper function for sanitizeInput
 * Makes code easier to read
 */
function sanitize($input) {
    return sanitizeInput($input);
}

// ========================================
// Dashboard Functions
// ========================================

/**
 * Get financial totals for user
 * Returns total income and total expense
 */
function getFinancialTotals($user_id) {
    global $db;
    
    // SQL query to calculate totals
    $query = "SELECT 
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense
              FROM transactions 
              WHERE user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get chart data for specified period
 * Periods: daily, weekly, monthly, yearly
 */
function getChartData($user_id, $period = 'monthly') {
    global $db;
    
    // Set date format and interval based on period
    $dateFormat = "";
    $interval = "";
    
    switch($period) {
        case 'daily':
            $dateFormat = "%Y-%m-%d";  // Format: 2025-01-15
            $interval = "30 DAY";       // Last 30 days
            break;
        case 'weekly':
            $dateFormat = "%x-W%v";     // Format: 2025-W03
            $interval = "12 WEEK";      // Last 12 weeks
            break;
        case 'monthly':
            $dateFormat = "%Y-%m";      // Format: 2025-01
            $interval = "12 MONTH";     // Last 12 months
            break;
        case 'yearly':
            $dateFormat = "%Y";         // Format: 2025
            $interval = "5 YEAR";       // Last 5 years
            break;
        default:
            $dateFormat = "%Y-%m";
            $interval = "12 MONTH";
    }
    
    // Query to get income and expense grouped by period
    $query = "SELECT 
                DATE_FORMAT(transaction_date, :date_format) as period,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense
              FROM transactions 
              WHERE user_id = :user_id 
                AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL $interval)
              GROUP BY period 
              ORDER BY period ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":date_format", $dateFormat);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ========================================
// Transaction CRUD Functions
// ========================================

/**
 * Get transactions with optional filters
 * Supports multiple simultaneous filters (advanced search)
 */
function getTransactions($user_id, $filters = []) {
    global $db;
    
    // Base query
    $query = "SELECT * FROM transactions WHERE user_id = :user_id";
    $params = [':user_id' => $user_id];
    
    // Add type filter (income/expense)
    if (!empty($filters['type'])) {
        $query .= " AND type = :type";
        $params[':type'] = $filters['type'];
    }
    
    // Add start date filter
    if (!empty($filters['start_date'])) {
        $query .= " AND transaction_date >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    
    // Add end date filter
    if (!empty($filters['end_date'])) {
        $query .= " AND transaction_date <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    
    // Add text search filter
    if (!empty($filters['search'])) {
        $query .= " AND remarks LIKE :search";
        $params[':search'] = "%" . $filters['search'] . "%";
    }
    
    // Add minimum amount filter
    if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
        $query .= " AND amount >= :min_amount";
        $params[':min_amount'] = floatval($filters['min_amount']);
    }
    
    // Add maximum amount filter
    if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
        $query .= " AND amount <= :max_amount";
        $params[':max_amount'] = floatval($filters['max_amount']);
    }
    
    // Order by date (newest first)
    $query .= " ORDER BY transaction_date DESC, created_at DESC";
    
    // Add limit if specified
    if (!empty($filters['limit'])) {
        $query .= " LIMIT :limit";
        $params[':limit'] = (int)$filters['limit'];
    }
    
    // Execute query
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        if ($key === ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get single transaction by ID
 * Used for edit operation
 */
function getTransactionById($user_id, $transaction_id) {
    global $db;
    
    $query = "SELECT * FROM transactions WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $transaction_id, ':user_id' => $user_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Update existing transaction
 * CRUD: Update operation
 */
function updateTransaction($user_id, $transaction_id, $data) {
    global $db;
    
    $query = "UPDATE transactions 
              SET type = :type, 
                  amount = :amount, 
                  remarks = :remarks, 
                  transaction_date = :date,
                  updated_at = NOW()
              WHERE id = :id AND user_id = :user_id";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([
        ':type' => $data['type'],
        ':amount' => $data['amount'],
        ':remarks' => $data['remarks'],
        ':date' => $data['date'],
        ':id' => $transaction_id,
        ':user_id' => $user_id
    ]);
}

// ========================================
// Search Functions
// ========================================

/**
 * Autocomplete search for transaction remarks
 * Returns suggestions as user types
 */
function autocompleteSearch($user_id, $term) {
    global $db;
    
    $query = "SELECT DISTINCT remarks 
              FROM transactions 
              WHERE user_id = :user_id 
                AND remarks LIKE :term 
              ORDER BY created_at DESC 
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':term' => "%$term%"
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>