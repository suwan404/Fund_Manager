// Global variables
let chart = null;

// Open modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    const form = document.getElementById(modalId).querySelector('form');
    if (form) form.reset();
}

// Update chart with total income and expense
function updateChart(totalIncome, totalExpense) {
    const canvas = document.getElementById('chart');
    if (!canvas) {
        console.error('Chart canvas not found');
        return;
    }
    
    // Destroy old chart
    if (chart) {
        chart.destroy();
    }
    
    // Create new bar chart
    chart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: ['Total Income', 'Total Expense'],
            datasets: [{
                label: 'Amount (Rs)',
                data: [parseFloat(totalIncome) || 0, parseFloat(totalExpense) || 0],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderColor: [
                    '#10b981',
                    '#ef4444'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rs ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rs ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
}

// Edit transaction
function editTransaction(id) {
    fetch('index.php?ajax=1&action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const t = data.transaction;
                document.getElementById('editId').value = t.id;
                document.getElementById('editType').value = t.type;
                document.getElementById('editAmount').value = t.amount;
                document.getElementById('editRemarks').value = t.remarks;
                document.getElementById('editDate').value = t.transaction_date;
                openModal('editModal');
            }
        });
}

// Delete transaction
function deleteTransaction(id) {
    document.getElementById('deleteId').value = id;
    openModal('deleteModal');
}

// Load statement
function loadStatement() {
    const type = document.getElementById('filterType').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const search = document.getElementById('filterSearch').value;
    
    let url = 'index.php?ajax=1&action=transactions';
    if (type) url += '&type=' + type;
    if (startDate) url += '&start_date=' + startDate;
    if (endDate) url += '&end_date=' + endDate;
    if (search) url += '&search=' + encodeURIComponent(search);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStatement(data.transactions);
            }
        });
}

// Display statement
function displayStatement(transactions) {
    const container = document.getElementById('statementTable');
    
    if (transactions.length === 0) {
        container.innerHTML = '<div class="empty-state">No transactions found</div>';
        return;
    }
    
    let totalAmount = 0;
    
    let html = '<table><thead><tr>';
    html += '<th>Date</th>';
    html += '<th>Type</th>';
    html += '<th>Remarks</th>';
    html += '<th class="text-right">Amount</th>';
    html += '<th class="text-center">Actions</th>';
    html += '</tr></thead><tbody>';
    
    transactions.forEach(t => {
        const amount = parseFloat(t.amount);
        if (t.type === 'income') {
            totalAmount += amount;
        } else {
            totalAmount -= amount;
        }
        
        html += '<tr>';
        html += '<td>' + t.transaction_date + '</td>';
        html += '<td><span class="type-badge ' + t.type + '">' + t.type + '</span></td>';
        html += '<td>' + t.remarks + '</td>';
        html += '<td class="text-right ' + t.type + '">';
        html += (t.type === 'income' ? '+' : '-') + 'Rs ' + amount.toFixed(2);
        html += '</td>';
        html += '<td class="text-center">';
        html += '<button onclick="editTransaction(' + t.id + ')" class="action-btn edit">Edit</button> ';
        html += '<button onclick="deleteTransaction(' + t.id + ')" class="action-btn delete">Delete</button>';
        html += '</td>';
        html += '</tr>';
    });
    
    html += '</tbody><tfoot><tr>';
    html += '<td colspan="4" class="text-right"><strong>Total Amount:</strong></td>';
    html += '<td class="text-right">';
    html += '<div><strong>Rs ' + totalAmount.toFixed(2) + '</strong></div>';
    html += '</td>';
    html += '</tr></tfoot></table>';
    
    container.innerHTML = html;
}

// Clear filters
function clearFilters() {
    document.getElementById('filterType').value = '';
    document.getElementById('filterSearch').value = '';
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('startDate').value = today;
    document.getElementById('endDate').value = today;
    
    // Reload with today's date
    loadStatement();
}

// Autocomplete for header search
const searchInput = document.getElementById('searchInput');
const searchDropdown = document.getElementById('searchDropdown');
let searchTimeout;

if (searchInput) {
    searchInput.addEventListener('input', function() {
        const term = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (term.length < 2) {
            searchDropdown.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetch('index.php?ajax=1&action=autocomplete&term=' + encodeURIComponent(term))
                .then(response => response.json())
                .then(results => {
                    if (results.length > 0) {
                        searchDropdown.innerHTML = results.map(r => 
                            '<div class="autocomplete-item" onclick="selectSearch(\'' + r.replace(/'/g, "\\'") + '\')">' + r + '</div>'
                        ).join('');
                        searchDropdown.style.display = 'block';
                    } else {
                        searchDropdown.style.display = 'none';
                    }
                });
        }, 300);
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!searchInput.contains(event.target) && !searchDropdown.contains(event.target)) {
            searchDropdown.style.display = 'none';
        }
    });
}

// Select from search autocomplete
function selectSearch(value) {
    document.getElementById('searchInput').value = value;
    searchDropdown.style.display = 'none';
    document.getElementById('filterSearch').value = value;
    loadStatement();
}

// Autocomplete for filter search
const filterInput = document.getElementById('filterSearch');
const filterDropdown = document.getElementById('filterDropdown');
let filterTimeout;

if (filterInput) {
    filterInput.addEventListener('input', function() {
        const term = this.value.trim();
        
        clearTimeout(filterTimeout);
        
        if (term.length < 2) {
            filterDropdown.style.display = 'none';
            return;
        }
        
        filterTimeout = setTimeout(() => {
            fetch('index.php?ajax=1&action=autocomplete&term=' + encodeURIComponent(term))
                .then(response => response.json())
                .then(results => {
                    if (results.length > 0) {
                        filterDropdown.innerHTML = results.map(r => 
                            '<div class="autocomplete-item" onclick="selectFilter(\'' + r + '\')">' + r + '</div>'
                        ).join('');
                        filterDropdown.style.display = 'block';
                    } else {
                        filterDropdown.style.display = 'none';
                    }
                });
        }, 300);
    });
}

// Select from filter autocomplete
function selectFilter(value) {
    document.getElementById('filterSearch').value = value;
    filterDropdown.style.display = 'none';
}