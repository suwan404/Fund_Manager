<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/security.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    return $_SESSION['user_id'];
}

// Get total income and expense
function getTotals($user_id) {
    global $db;
    
    $query = "SELECT 
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
              FROM transactions 
              WHERE user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get chart data
function getChartData($user_id, $period = 'monthly') {
    global $db;
    
        $format = "%Y-%m";
        $days = "12 MONTH";
    
    $query = "SELECT 
                DATE_FORMAT(transaction_date, '$format') as period,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
              FROM transactions 
              WHERE user_id = :user_id 
                AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL $days)
              GROUP BY period 
              ORDER BY period";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get transactions with filters
function getTransactions($user_id, $filters = []) {
    global $db;
    
    $query = "SELECT * FROM transactions WHERE user_id = :user_id";
    $params = [':user_id' => $user_id];
    
    // Add filters
    if (!empty($filters['type'])) {
        $query .= " AND type = :type";
        $params[':type'] = $filters['type'];
    }
    
    if (!empty($filters['start_date'])) {
        $query .= " AND transaction_date >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    
    if (!empty($filters['end_date'])) {
        $query .= " AND transaction_date <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    
    if (!empty($filters['search'])) {
        $query .= " AND remarks LIKE :search";
        $params[':search'] = "%" . $filters['search'] . "%";
    }
    
    $query .= " ORDER BY transaction_date DESC";
    
    if (!empty($filters['limit'])) {
        $query .= " LIMIT " . (int)$filters['limit'];
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get single transaction by ID
function getTransaction($user_id, $id) {
    global $db;
    
    $query = "SELECT * FROM transactions WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get autocomplete suggestions
function getAutocomplete($user_id, $term) {
    global $db;
    
    $query = "SELECT DISTINCT remarks 
              FROM transactions 
              WHERE user_id = :user_id AND remarks LIKE :term 
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $user_id, ':term' => "%$term%"]);
    
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = $row['remarks'];
    }
    
    return $results;
}
?>