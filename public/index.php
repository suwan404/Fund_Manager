<?php
/**
 * Main Dashboard Page
 * Handles all CRUD operations and displays dashboard
 */

require_once '../includes/functions.php';
$user_id = requireLogin();

// ========================================
// AJAX Request Handler
// ========================================
if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    // CSRF validation for POST requests (security)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit();
        }
    }
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch($action) {
        // Get financial summary
        case 'summary':
            $totals = getFinancialTotals($user_id);
            $balance = $totals['total_income'] - $totals['total_expense'];
            
            echo json_encode([
                'success' => true,
                'total_income' => (float)$totals['total_income'],
                'total_expense' => (float)$totals['total_expense'],
                'balance' => (float)$balance
            ]);
            exit();
            
        // Get chart data for specific period
        case 'chart':
            $period = $_GET['period'] ?? 'monthly';
            $chart_data = getChartData($user_id, $period);
            echo json_encode(['success' => true, 'chart_data' => $chart_data]);
            exit();
            
        // Get transactions with filters (advanced search)
        case 'transactions':
            $filters = [
                'type' => $_GET['type'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null,
                'search' => $_GET['search'] ?? null,
                'min_amount' => $_GET['min_amount'] ?? null,
                'max_amount' => $_GET['max_amount'] ?? null,
                'limit' => $_GET['limit'] ?? null
            ];
            $transactions = getTransactions($user_id, $filters);
            echo json_encode(['success' => true, 'transactions' => $transactions]);
            exit();
            
        // Autocomplete search suggestions
        case 'autocomplete':
            $term = $_GET['term'] ?? '';
            $results = autocompleteSearch($user_id, $term);
            echo json_encode($results);
            exit();
            
        // CRUD: CREATE - Add new transaction
        case 'add_transaction':
            $type = $_POST['type'];
            $amount = floatval($_POST['amount']);
            $remarks = sanitize($_POST['remarks']);
            $date = $_POST['date'];
            
            // Validate amount
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Amount must be positive']);
                exit();
            }
            
            // Insert into database
            $query = "INSERT INTO transactions (user_id, type, amount, remarks, transaction_date) 
                      VALUES (:user_id, :type, :amount, :remarks, :date)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':type' => $type,
                ':amount' => $amount,
                ':remarks' => $remarks,
                ':date' => $date
            ]);
            
            echo json_encode(['success' => true, 'message' => ucfirst($type) . ' added successfully']);
            exit();
            
        // CRUD: READ - Get single transaction for editing
        case 'get_transaction':
            $id = intval($_GET['id']);
            $transaction = getTransactionById($user_id, $id);
            
            if ($transaction) {
                echo json_encode(['success' => true, 'transaction' => $transaction]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            }
            exit();
            
        // CRUD: UPDATE - Update existing transaction
        case 'update_transaction':
            $id = intval($_POST['id']);
            $data = [
                'type' => $_POST['type'],
                'amount' => floatval($_POST['amount']),
                'remarks' => sanitize($_POST['remarks']),
                'date' => $_POST['date']
            ];
            
            // Validate amount
            if ($data['amount'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Amount must be positive']);
                exit();
            }
            
            if (updateTransaction($user_id, $id, $data)) {
                echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
            exit();
            
        // CRUD: DELETE - Delete transaction
        case 'delete_transaction':
            $id = intval($_POST['id']);
            
            $query = "DELETE FROM transactions WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id, ':user_id' => $user_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Transaction deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            }
            exit();
            
        // Update user profile
        case 'update_profile':
            $full_name = sanitize($_POST['full_name']);
            $email = sanitize($_POST['email']);
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'] ?? '';
            
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch();
            
            if (!password_verify($current_password, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit();
            }
            
            // Update profile
            $query = "UPDATE users SET full_name = :full_name, email = :email";
            $params = [':full_name' => $full_name, ':email' => $email, ':id' => $user_id];
            
            if (!empty($new_password)) {
                $passwordErrors = validatePasswordStrength($new_password);
                if (!empty($passwordErrors)) {
                    echo json_encode(['success' => false, 'message' => implode(", ", $passwordErrors)]);
                    exit();
                }
                $query .= ", password = :password";
                $params[':password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            $query .= " WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            // Update session
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            exit();
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}

// ========================================
// Page Load - Get Initial Data
// ========================================
$totals = getFinancialTotals($user_id);
$balance = $totals['total_income'] - $totals['total_expense'];
$chart_data = getChartData($user_id, 'monthly');
$recent = getTransactions($user_id, ['limit' => 5]);
?>

<?php include '../includes/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <!-- Financial Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-6 rounded-lg border">
            <div class="text-gray-600 text-sm">Current Balance</div>
            <div id="balance" class="text-2xl font-bold text-gray-800">Rs <?php echo number_format($balance, 2); ?></div>
        </div>
        <div class="bg-white p-6 rounded-lg border">
            <div class="text-gray-600 text-sm">Total Income</div>
            <div id="totalIncome" class="text-2xl font-bold text-green-600">Rs <?php echo number_format($totals['total_income'], 2); ?></div>
        </div>
        <div class="bg-white p-6 rounded-lg border">
            <div class="text-gray-600 text-sm">Total Expense</div>
            <div id="totalExpense" class="text-2xl font-bold text-red-600">Rs <?php echo number_format($totals['total_expense'], 2); ?></div>
        </div>
    </div>

    <!-- Quick Action Buttons -->
    <div class="flex flex-wrap gap-3 mb-6">
        <button onclick="openModal('incomeModal')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
            + Add Income
        </button>
        <button onclick="openModal('expenseModal')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
            - Add Expense
        </button>
        <button onclick="showStatement()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            View Statement
        </button>
    </div>

    <!-- Chart & Recent Transactions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Enhanced Chart Section -->
        <div class="bg-white p-6 rounded-lg border">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Financial Overview</h2>
                <div class="flex space-x-2">
                    <button onclick="changeChart('daily')" class="px-3 py-1 rounded text-sm hover:bg-gray-100">Daily</button>
                    <button onclick="changeChart('weekly')" class="px-3 py-1 rounded text-sm hover:bg-gray-100">Weekly</button>
                    <button onclick="changeChart('monthly')" class="px-3 py-1 rounded text-sm bg-blue-500 text-white">Monthly</button>
                    <button onclick="changeChart('yearly')" class="px-3 py-1 rounded text-sm hover:bg-gray-100">Yearly</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="chart"></canvas>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white p-6 rounded-lg border">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Recent Transactions</h2>
                <button onclick="loadRecent()" class="text-blue-500 hover:text-blue-700">Refresh</button>
            </div>
            <div id="recentList" class="space-y-4">
                <?php if (empty($recent)): ?>
                    <div class="text-center py-8 text-gray-500">No transactions yet</div>
                <?php else: ?>
                    <?php foreach ($recent as $t): ?>
                    <div class="transaction-item">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="<?php echo $t['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo ucfirst($t['type']); ?>
                                    </span>
                                    <span class="text-sm text-gray-500"><?php echo escape($t['transaction_date']); ?></span>
                                </div>
                                <div class="text-gray-800"><?php echo escape($t['remarks'] ?: 'No remarks'); ?></div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold <?php echo $t['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $t['type'] === 'income' ? '+' : '-'; ?>Rs <?php echo number_format($t['amount'], 2); ?>
                                </div>
                                <div class="flex space-x-2 mt-1">
                                    <button onclick="editTransaction(<?php echo $t['id']; ?>)" 
                                            class="text-blue-500 hover:text-blue-700 text-sm">
                                        Edit
                                    </button>
                                    <button onclick="deleteTransaction(<?php echo $t['id']; ?>)" 
                                            class="text-red-500 hover:text-red-700 text-sm">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statement Section (Hidden by default) -->
    <div id="statementSection" class="bg-white p-6 rounded-lg border hidden">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold">Transaction Statement</h2>
            <div class="flex space-x-3">
                <button onclick="downloadCSV()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                    Download CSV
                </button>
                <button onclick="hideStatement()" class="border border-gray-300 px-4 py-2 rounded-lg">
                    Close
                </button>
            </div>
        </div>

        <!-- Advanced Search Filters (Multiple Criteria) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <select id="filterType" class="border border-gray-300 rounded px-3 py-2">
                <option value="">All Types</option>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>
            <input type="date" id="startDate" class="border border-gray-300 rounded px-3 py-2" 
                   value="<?php echo date('Y-m-01'); ?>">
            <input type="date" id="endDate" class="border border-gray-300 rounded px-3 py-2" 
                   value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <input type="number" id="minAmount" placeholder="Min Amount" 
                   class="border border-gray-300 rounded px-3 py-2" step="0.01">
            <input type="number" id="maxAmount" placeholder="Max Amount" 
                   class="border border-gray-300 rounded px-3 py-2" step="0.01">
            <div class="relative">
                <input type="text" id="filterSearch" placeholder="Search remarks..." 
                       class="border border-gray-300 rounded px-3 py-2 w-full" autocomplete="off">
                <div id="autocomplete" class="hidden absolute z-10 bg-white border border-gray-300 rounded mt-1 w-full max-h-48 overflow-y-auto shadow-lg"></div>
            </div>
        </div>
        <div class="flex space-x-3 mb-6">
            <button onclick="loadStatement()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                Apply Filters
            </button>
            <button onclick="clearFilters()" class="border border-gray-300 px-4 py-2 rounded-lg">
                Clear Filters
            </button>
        </div>

        <!-- Statement Table -->
        <div id="statementTable">
            <div class="text-center py-8 text-gray-500">Apply filters to view statement</div>
        </div>
    </div>
</div>

<!-- Include Modal Forms -->
<?php include '../includes/modals.php'; ?>

<script>
// Pass CSRF token and initial chart data to JavaScript
const CSRF_TOKEN = '<?php echo CSRF::generateToken(); ?>';

// Initialize chart when page loads
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?php echo json_encode($chart_data); ?>;
    if (typeof updateChart === 'function') {
        updateChart(chartData);
    }
});
</script>

<?php include '../includes/footer.php'; ?>