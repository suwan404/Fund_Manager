/**
 * Main JavaScript File
 * Handles all AJAX operations, charts, and UI interactions
 */

// Global variables
let chart = null;
let currentPeriod = 'monthly';
let autocompleteTimeout = null;

// ========================================
// Modal Functions
// ========================================

/**
 * Open modal by ID
 */
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

/**
 * Close modal by ID
 */
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    // Reset form if exists
    const form = document.getElementById(modalId).querySelector('form');
    if (form) form.reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// ========================================
// User Menu Functions
// ========================================

/**
 * Toggle user dropdown menu
 */
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('hidden');
}

// Close menu when clicking outside
document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('userMenu');
    if (userMenu && !e.target.closest('.relative')) {
        userMenu.classList.add('hidden');
    }
});

// ========================================
// Transaction CRUD Operations
// ========================================

/**
 * Add new transaction (income or expense)
 * CRUD: Create operation
 */
async function addTransaction(event, type) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('ajax', '1');
    formData.append('action', 'add_transaction');
    formData.append('type', type);
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            closeModal(type + 'Modal');
            form.reset();
            await refreshDashboard();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error adding transaction');
        console.error(error);
    }
}

/**
 * Load transaction data for editing
 * CRUD: Read operation (single record)
 */
async function editTransaction(id) {
    try {
        const response = await fetch(`index.php?ajax=1&action=get_transaction&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const t = data.transaction;
            // Populate edit form
            document.getElementById('editId').value = t.id;
            document.getElementById('editType').value = t.type;
            document.getElementById('editAmount').value = t.amount;
            document.getElementById('editRemarks').value = t.remarks;
            document.getElementById('editDate').value = t.transaction_date;
            openModal('editModal');
        } else {
            alert('Error loading transaction');
        }
    } catch (error) {
        alert('Error loading transaction');
        console.error(error);
    }
}

/**
 * Update existing transaction
 * CRUD: Update operation
 */
async function updateTransaction(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('ajax', '1');
    formData.append('action', 'update_transaction');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            closeModal('editModal');
            await refreshDashboard();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error updating transaction');
        console.error(error);
    }
}

/**
 * Delete transaction with confirmation
 * CRUD: Delete operation
 */
async function deleteTransaction(id) {
    if (!confirm('Are you sure you want to delete this transaction?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'delete_transaction');
    formData.append('id', id);
    formData.append('csrf_token', CSRF_TOKEN);
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            await refreshDashboard();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error deleting transaction');
        console.error(error);
    }
}

// ========================================
// Profile Management
// ========================================

/**
 * Update user profile
 */
async function updateProfile(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('ajax', '1');
    formData.append('action', 'update_profile');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            closeModal('profileModal');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error updating profile');
        console.error(error);
    }
}

// ========================================
// Dashboard Refresh Functions
// ========================================

/**
 * Refresh all dashboard components
 */
async function refreshDashboard() {
    await Promise.all([
        loadSummary(),
        loadRecent(),
        loadChart(currentPeriod)
    ]);
}

/**
 * Load financial summary
 */
async function loadSummary() {
    try {
        const response = await fetch('index.php?ajax=1&action=summary');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('balance').textContent = 'Rs ' + data.balance.toFixed(2);
            document.getElementById('totalIncome').textContent = 'Rs ' + data.total_income.toFixed(2);
            document.getElementById('totalExpense').textContent = 'Rs ' + data.total_expense.toFixed(2);
        }
    } catch (error) {
        console.error('Error loading summary:', error);
    }
}

/**
 * Load recent transactions
 * CRUD: Read operation
 */
async function loadRecent() {
    try {
        const response = await fetch('index.php?ajax=1&action=transactions&limit=5');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('recentList');
            if (data.transactions.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No transactions yet</div>';
            } else {
                container.innerHTML = data.transactions.map(t => `
                    <div class="transaction-item">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="${t.type === 'income' ? 'text-green-600' : 'text-red-600'}">
                                        ${t.type.charAt(0).toUpperCase() + t.type.slice(1)}
                                    </span>
                                    <span class="text-sm text-gray-500">${t.transaction_date}</span>
                                </div>
                                <div class="text-gray-800">${escapeHtml(t.remarks || 'No remarks')}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold ${t.type === 'income' ? 'text-green-600' : 'text-red-600'}">
                                    ${t.type === 'income' ? '+' : '-'}Rs ${parseFloat(t.amount).toFixed(2)}
                                </div>
                                <div class="flex space-x-2 mt-1">
                                    <button onclick="editTransaction(${t.id})" class="text-blue-500 hover:text-blue-700 text-sm">Edit</button>
                                    <button onclick="deleteTransaction(${t.id})" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading recent transactions:', error);
    }
}

// ========================================
// Chart Functions (ENHANCED)
// ========================================

/**
 * Load chart data for specific period
 */
async function loadChart(period) {
    try {
        const response = await fetch(`index.php?ajax=1&action=chart&period=${period}`);
        const data = await response.json();
        
        if (data.success) {
            updateChart(data.chart_data);
        }
    } catch (error) {
        console.error('Error loading chart:', error);
    }
}

/**
 * Change chart period (daily/weekly/monthly/yearly)
 */
function changeChart(period) {
    currentPeriod = period;
    // Update button styles
    document.querySelectorAll('[onclick^="changeChart"]').forEach(btn => {
        btn.classList.remove('bg-blue-500', 'text-white');
        btn.classList.add('hover:bg-gray-100');
    });
    event.target.classList.remove('hover:bg-gray-100');
    event.target.classList.add('bg-blue-500', 'text-white');
    loadChart(period);
}

/**
 * Update chart with new data (ENHANCED)
 */
function updateChart(chartData) {
    const ctx = document.getElementById('chart');
    if (!ctx) return;
    
    // Prepare data for chart
    const labels = chartData.map(d => formatPeriodLabel(d.period, currentPeriod));
    const incomeData = chartData.map(d => parseFloat(d.income));
    const expenseData = chartData.map(d => parseFloat(d.expense));
    
    // Destroy existing chart
    if (chart) {
        chart.destroy();
    }
    
    // Create new enhanced chart
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Income',
                    data: incomeData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Expense',
                    data: expenseData,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12,
                            weight: 'bold'
                        },
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': Rs ';
                            }
                            label += context.parsed.y.toFixed(2);
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'Rs ' + value.toFixed(0);
                        },
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            }
        }
    });
}

/**
 * Format period labels for better readability
 */
function formatPeriodLabel(period, periodType) {
    if (periodType === 'daily') {
        // Format: 2025-01-15 → Jan 15
        const date = new Date(period);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    } else if (periodType === 'weekly') {
        // Format: 2025-W03 → Week 3
        const week = period.split('-W')[1];
        return 'Week ' + week;
    } else if (periodType === 'monthly') {
        // Format: 2025-01 → Jan 2025
        const [year, month] = period.split('-');
        const date = new Date(year, month - 1);
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    } else if (periodType === 'yearly') {
        // Format: 2025 → 2025
        return period;
    }
    return period;
}

// ========================================
// Statement Functions
// ========================================

/**
 * Show statement section
 */
function showStatement() {
    document.getElementById('statementSection').classList.remove('hidden');
    loadStatement();
}

/**
 * Hide statement section
 */
function hideStatement() {
    document.getElementById('statementSection').classList.add('hidden');
}

/**
 * Load statement with filters
 */
async function loadStatement() {
    const filters = {
        type: document.getElementById('filterType').value,
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        search: document.getElementById('filterSearch').value,
        min_amount: document.getElementById('minAmount').value,
        max_amount: document.getElementById('maxAmount').value
    };
    
    const params = new URLSearchParams({ajax: '1', action: 'transactions'});
    Object.keys(filters).forEach(key => {
        if (filters[key]) params.append(key, filters[key]);
    });
    
    try {
        const response = await fetch(`index.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayStatement(data.transactions);
        }
    } catch (error) {
        console.error('Error loading statement:', error);
    }
}

/**
 * Display statement table
 */
function displayStatement(transactions) {
    const container = document.getElementById('statementTable');
    
    if (transactions.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500">No transactions found</div>';
        return;
    }
    
    let totalIncome = 0;
    let totalExpense = 0;
    
    const html = `
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Date</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Type</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Remarks</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Amount</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    ${transactions.map(t => {
                        if (t.type === 'income') totalIncome += parseFloat(t.amount);
                        else totalExpense += parseFloat(t.amount);
                        
                        return `
                            <tr>
                                <td class="px-4 py-3 text-sm">${t.transaction_date}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded ${t.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                        ${t.type.charAt(0).toUpperCase() + t.type.slice(1)}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">${escapeHtml(t.remarks || 'No remarks')}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold ${t.type === 'income' ? 'text-green-600' : 'text-red-600'}">
                                    ${t.type === 'income' ? '+' : '-'}Rs ${parseFloat(t.amount).toFixed(2)}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button onclick="editTransaction(${t.id})" class="text-blue-500 hover:text-blue-700 text-sm mr-2">Edit</button>
                                    <button onclick="deleteTransaction(${t.id})" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                                </td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right">Totals:</td>
                        <td class="px-4 py-3 text-right">
                            <div class="text-green-600">Income: Rs ${totalIncome.toFixed(2)}</div>
                            <div class="text-red-600">Expense: Rs ${totalExpense.toFixed(2)}</div>
                            <div class="text-gray-800 border-t pt-2 mt-2">Balance: Rs ${(totalIncome - totalExpense).toFixed(2)}</div>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

/**
 * Clear all filters
 */
function clearFilters() {
    document.getElementById('filterType').value = '';
    document.getElementById('startDate').value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    document.getElementById('endDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('filterSearch').value = '';
    document.getElementById('minAmount').value = '';
    document.getElementById('maxAmount').value = '';
    loadStatement();
}

/**
 * Download statement as CSV
 */
function downloadCSV() {
    const table = document.querySelector('#statementTable table');
    if (!table) {
        alert('No data to download');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length - 1; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length - 1; j++) {
            let text = cols[j].textContent.trim().replace(/\s+/g, ' ');
            row.push('"' + text + '"');
        }
        csv.push(row.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'transactions_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// ========================================
// Header Search with Autocomplete
// ========================================

/**
 * Setup header search autocomplete
 * Shows dropdown with suggestions (NOT statement)
 */
document.addEventListener('DOMContentLoaded', function() {
    const headerSearch = document.getElementById('searchInput');
    
    if (headerSearch) {
        // Create autocomplete dropdown
        const dropdown = document.createElement('div');
        dropdown.id = 'headerAutocomplete';
        dropdown.className = 'hidden absolute z-50 bg-white border border-gray-300 rounded-lg mt-1 w-64 max-h-60 overflow-y-auto shadow-lg';
        headerSearch.parentElement.style.position = 'relative';
        headerSearch.parentElement.appendChild(dropdown);
        
        // Handle input
        headerSearch.addEventListener('input', function() {
            clearTimeout(autocompleteTimeout);
            const term = this.value.trim();
            
            if (term.length < 2) {
                dropdown.classList.add('hidden');
                return;
            }
            
            autocompleteTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`index.php?ajax=1&action=autocomplete&term=${encodeURIComponent(term)}`);
                    const results = await response.json();
                    
                    if (results.length > 0) {
                        dropdown.innerHTML = results.map(r => 
                            `<div class="px-4 py-2 hover:bg-gray-100 cursor-pointer" onclick="selectHeaderSearch('${escapeHtml(r)}')">${escapeHtml(r)}</div>`
                        ).join('');
                        dropdown.classList.remove('hidden');
                    } else {
                        dropdown.classList.add('hidden');
                    }
                } catch (error) {
                    console.error('Autocomplete error:', error);
                }
            }, 300);
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!headerSearch.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }
    
    // Setup statement search autocomplete
    const statementSearch = document.getElementById('filterSearch');
    const statementDropdown = document.getElementById('autocomplete');
    
    if (statementSearch && statementDropdown) {
        statementSearch.addEventListener('input', function() {
            clearTimeout(autocompleteTimeout);
            const term = this.value.trim();
            
            if (term.length < 2) {
                statementDropdown.classList.add('hidden');
                return;
            }
            
            autocompleteTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`index.php?ajax=1&action=autocomplete&term=${encodeURIComponent(term)}`);
                    const results = await response.json();
                    
                    if (results.length > 0) {
                        statementDropdown.innerHTML = results.map(r => 
                            `<div class="px-4 py-2 hover:bg-gray-100 cursor-pointer" onclick="selectAutocomplete('${escapeHtml(r)}')">${escapeHtml(r)}</div>`
                        ).join('');
                        statementDropdown.classList.remove('hidden');
                    } else {
                        statementDropdown.classList.add('hidden');
                    }
                } catch (error) {
                    console.error('Autocomplete error:', error);
                }
            }, 300);
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!statementSearch.contains(e.target) && !statementDropdown.contains(e.target)) {
                statementDropdown.classList.add('hidden');
            }
        });
    }
});

/**
 * Select search result from header autocomplete
 */
function selectHeaderSearch(value) {
    document.getElementById('searchInput').value = value;
    document.getElementById('headerAutocomplete').classList.add('hidden');
    
    // Show statement with this search
    document.getElementById('filterSearch').value = value;
    showStatement();
    loadStatement();
}

/**
 * Select search result from statement autocomplete
 */
function selectAutocomplete(value) {
    document.getElementById('filterSearch').value = value;
    document.getElementById('autocomplete').classList.add('hidden');
    loadStatement();
}

// ========================================
// Password Validation Functions
// ========================================

/**
 * Check password strength with visual feedback
 */
function checkPasswordStrength() {
    const password = document.getElementById('password');
    if (!password) return;
    
    const strengthBar = document.getElementById('passwordStrength');
    const hint = document.getElementById('passwordHint');
    if (!strengthBar) return;
    
    const value = password.value;
    let strength = 0;
    let hints = [];
    
    if (value.length >= 8) strength += 25;
    else hints.push('8+ characters');
    
    if (/[A-Z]/.test(value)) strength += 25;
    else hints.push('uppercase letter');
    
    if (/[a-z]/.test(value)) strength += 25;
    else hints.push('lowercase letter');
    
    if (/[0-9]/.test(value)) strength += 25;
    else hints.push('number');
    
    strengthBar.style.width = strength + '%';
    
    if (strength < 50) {
        strengthBar.style.backgroundColor = '#ef4444';
    } else if (strength < 75) {
        strengthBar.style.backgroundColor = '#f59e0b';
    } else {
        strengthBar.style.backgroundColor = '#10b981';
    }
    
    if (hint) {
        hint.textContent = hints.length > 0 ? 'Need: ' + hints.join(', ') : 'Strong password!';
        hint.style.color = hints.length > 0 ? '#ef4444' : '#10b981';
    }
}

/**
 * Check if passwords match
 */
function checkPasswordMatch() {
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const matchDiv = document.getElementById('passwordMatch');
    
    if (!password || !confirm || !matchDiv) return;
    
    if (confirm.value === '') {
        matchDiv.textContent = '';
        return;
    }
    
    if (password.value === confirm.value) {
        matchDiv.textContent = '✓ Passwords match';
        matchDiv.style.color = '#10b981';
    } else {
        matchDiv.textContent = '✗ Passwords do not match';
        matchDiv.style.color = '#ef4444';
    }
}

// ========================================
// Utility Functions
// ========================================

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}