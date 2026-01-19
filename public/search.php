<?php
require_once '../includes/functions.php';
$user_id = requireLogin();

// Handle search
$search_results = [];
if (isset($_GET['q'])) {
    $search_results = getTransactions($user_id, ['search' => $_GET['q']]);
}

include '../includes/header.php';
?>
<div class="max-w-7xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">Search Transactions</h1>
    
    <form method="GET" class="mb-6">
        <input type="text" name="q" value="<?php echo escape($_GET['q'] ?? ''); ?>"
               placeholder="Search by remarks..." 
               class="w-full max-w-md px-4 py-2 border rounded-lg">
        <button type="submit" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded-lg">
            Search
        </button>
    </form>
    
    <?php if (!empty($_GET['q'])): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Search Results</h2>
        <?php if (empty($search_results)): ?>
            <p class="text-gray-500">No results found.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($search_results as $t): ?>
                <div class="border-b pb-3">
                    <div class="flex justify-between">
                        <div>
                            <span class="<?php echo $t['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo ucfirst($t['type']); ?>
                            </span>
                            <span class="ml-3">Rs <?php echo number_format($t['amount'], 2); ?></span>
                        </div>
                        <div class="text-gray-600"><?php echo escape($t['transaction_date']); ?></div>
                    </div>
                    <div class="text-gray-800 mt-1"><?php echo escape($t['remarks']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
