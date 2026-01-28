<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <header class="header">
        <nav class="header-container">
            <div class="header-left">
                <div class="logo" role="img" aria-label="Money icon">💰</div>
                <div class="site-title">
                    <h1>Fund Management System</h1>
                    <p>Track your finances</p>
                </div>
            </div>
            
            <div class="header-right">
                <!-- Search Box with Autocomplete -->
                <div class="search-box">
                    <label for="searchInput" class="sr-only">Search transactions</label>
                    <input type="search" id="searchInput" placeholder="Search transactions..." autocomplete="off" aria-label="Search transactions">
                    <div id="searchDropdown" class="autocomplete-dropdown" role="listbox"></div>
                </div>
                
                <!-- User Info with Logout Icon -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="logout.php" class="user-avatar" title="Logout" aria-label="Logout" style="text-decoration: none; cursor: pointer;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </a>
                    <span style="font-size: 14px; font-weight: 500;"><?php echo clean($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </nav>
    </header>
    <?php endif; ?>
    
    <main>