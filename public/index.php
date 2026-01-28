<?php
require_once '../includes/functions.php';
$user_id = requireLogin();

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';
    
    // Get summary
    if ($action == 'summary') {
        $totals = getTotals($user_id);
        $balance = $totals['total_income'] - $totals['total_expense'];
        echo json_encode([
            'success' => true,
            'total_income' => (float)$totals['total_income'],
            'total_expense' => (float)$totals['total_expense'],
            'balance' => (float)$balance
        ]);
        exit();
    }
    
    // Get transactions
    if ($action == 'transactions') {
        $filters = [
            'type' => $_GET['type'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'search' => $_GET['search'] ?? null,
            'limit' => $_GET['limit'] ?? null
        ];
        $transactions = getTransactions($user_id, $filters);
        echo json_encode(['success' => true, 'transactions' => $transactions]);
        exit();
    }
    
    // Get single transaction
    if ($action == 'get') {
        $id = $_GET['id'] ?? 0;
        $transaction = getTransaction($user_id, $id);
        echo json_encode(['success' => true, 'transaction' => $transaction]);
        exit();
    }
    
    // Autocomplete
    if ($action == 'autocomplete') {
        $term = $_GET['term'] ?? '';
        $results = getAutocomplete($user_id, $term);
        echo json_encode($results);
        exit();
    }
    
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Check CSRF token
    if (!CSRF::check($_POST['token'] ?? '')) {
        die("Invalid security token");
    }
    
    // Add transaction
    if (isset($_POST['add'])) {
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $remarks = clean($_POST['remarks']);
        $date = $_POST['date'];
        
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
        
        header("Location: index.php?success=added");
        exit();
    }
    
    // Update transaction
    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $remarks = clean($_POST['remarks']);
        $date = $_POST['date'];
        
        $query = "UPDATE transactions 
                  SET type = :type, amount = :amount, remarks = :remarks, transaction_date = :date
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':type' => $type,
            ':amount' => $amount,
            ':remarks' => $remarks,
            ':date' => $date,
            ':id' => $id,
            ':user_id' => $user_id
        ]);
        
        header("Location: index.php?success=updated");
        exit();
    }
    
    // Delete transaction
    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        
        $query = "DELETE FROM transactions WHERE id = :id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id, ':user_id' => $user_id]);
        
        header("Location: index.php?success=deleted");
        exit();
    }
}

// Get data for page
$totals = getTotals($user_id);
$balance = $totals['total_income'] - $totals['total_expense'];
$recent = getTransactions($user_id, ['limit' => 5]);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    
    <!-- Success Message -->
    <?php if (isset($_GET['success'])): ?>
    <aside class="success-message" role="alert">
        <?php 
        if ($_GET['success'] == 'added') echo "Transaction added successfully!";
        if ($_GET['success'] == 'updated') echo "Transaction updated successfully!";
        if ($_GET['success'] == 'deleted') echo "Transaction deleted successfully!";
        ?>
    </aside>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <section aria-label="Financial Summary">
        <div class="grid grid-cols-3">
            <article class="card">
                <div class="card-title">Current Balance</div>
                <div id="balance" class="card-value balance">Rs <?php echo number_format($balance, 2); ?></div>
            </article>
            <article class="card">
                <div class="card-title">Total Income</div>
                <div id="totalIncome" class="card-value income">Rs <?php echo number_format($totals['total_income'], 2); ?></div>
            </article>
            <article class="card">
                <div class="card-title">Total Expense</div>
                <div id="totalExpense" class="card-value expense">Rs <?php echo number_format($totals['total_expense'], 2); ?></div>
            </article>
        </div>
    </section>

    <!-- Buttons -->
    <nav class="button-group" aria-label="Transaction actions">
        <button onclick="openModal('incomeModal')" class="btn btn-green" aria-label="Add income transaction">+ Add Income</button>
        <button onclick="openModal('expenseModal')" class="btn btn-red" aria-label="Add expense transaction">- Add Expense</button>
    </nav>

    <!-- Chart & Recent Transactions -->
    <div class="grid grid-cols-2">
        <!-- Chart -->
        <section class="card" aria-labelledby="chart-heading">
            <header class="chart-header">
                <h2 id="chart-heading">Financial Overview</h2>
            </header>
            <div class="chart-container">
                <canvas id="chart" role="img" aria-label="Bar chart showing total income and expense"></canvas>
            </div>
        </section>

        <!-- Recent Transactions -->
        <section class="card" aria-labelledby="recent-heading">
            <header class="transaction-header">
                <h2 id="recent-heading">Recent Transactions</h2>
            </header>
            <div id="recentList" class="transactions-list">
                <?php if (empty($recent)): ?>
                    <p class="empty-state">No transactions yet</p>
                <?php else: ?>
                    <?php foreach ($recent as $t): ?>
                    <article class="transaction-item">
                        <div class="transaction-row">
                            <div class="transaction-left">
                                <div class="transaction-meta">
                                    <span class="transaction-type <?php echo $t['type']; ?>">
                                        <?php echo ucfirst($t['type']); ?>
                                    </span>
                                    <time class="transaction-date" datetime="<?php echo $t['transaction_date']; ?>">
                                        <?php echo $t['transaction_date']; ?>
                                    </time>
                                </div>
                                <p class="transaction-remarks"><?php echo clean($t['remarks']); ?></p>
                            </div>
                            <div class="transaction-right">
                                <div class="transaction-amount <?php echo $t['type']; ?>">
                                    <?php echo $t['type'] === 'income' ? '+' : '-'; ?>Rs <?php echo number_format($t['amount'], 2); ?>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Statement Section -->
    <section id="statementSection" class="card" aria-labelledby="statement-heading">
        <header class="statement-header">
            <h2 id="statement-heading">Transaction Statement</h2>
        </header>

        <!-- Filters in Single Row -->
        <form class="filter-grid" onsubmit="event.preventDefault(); loadStatement();" aria-label="Transaction filters">
            <label for="filterType" class="sr-only">Filter by type</label>
            <select id="filterType" class="filter-select" aria-label="Transaction type filter">
                <option value="">All Types</option>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>
            
            <label for="startDate" class="sr-only">Start date</label>
            <input type="date" id="startDate" class="filter-input" value="<?php echo date('Y-m-d'); ?>" aria-label="Start date">
            
            <label for="endDate" class="sr-only">End date</label>
            <input type="date" id="endDate" class="filter-input" value="<?php echo date('Y-m-d'); ?>" aria-label="End date">
            
            <!-- Search with Autocomplete -->
            <div style="position: relative;">
                <label for="filterSearch" class="sr-only">Search remarks</label>
                <input type="search" id="filterSearch" placeholder="Search remarks..." class="filter-input" autocomplete="off" aria-label="Search remarks">
                <div id="filterDropdown" class="autocomplete-dropdown" role="listbox"></div>
            </div>
            
            <button type="submit" class="filter-btn filter-btn-apply" aria-label="Apply filters">✓ Apply</button>
            <button type="button" onclick="clearFilters()" class="filter-btn filter-btn-clear" aria-label="Clear filters">Clear</button>
        </form>

        <div id="statementTable" class="statement-table" role="region" aria-live="polite" aria-label="Transaction statement table"></div>
    </section>
</div>

<!-- Modals -->
<?php include '../includes/modals.php'; ?>

<script>
// Initialize bar chart with total income and expense
window.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing dashboard...');
    
    const totalIncome = parseFloat(<?php echo $totals['total_income']; ?>) || 0;
    const totalExpense = parseFloat(<?php echo $totals['total_expense']; ?>) || 0;
    
    console.log('Total Income:', totalIncome);
    console.log('Total Expense:', totalExpense);
    
    // Initialize chart
    updateChart(totalIncome, totalExpense);
    
    // Load today's transactions by default
    loadStatement();
});
</script>

<?php include '../includes/footer.php'; ?>