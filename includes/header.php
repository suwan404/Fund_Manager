<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suwan's Fund Management System</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💰</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Top Header -->
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="text-2xl">💰</div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Suwan's Fund Management</h1>
                        <p class="text-sm text-gray-600">Track your finances</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="hidden md:block">
                        <input type="text" id="searchInput" placeholder="Search transactions..." 
                               class="px-4 py-2 border border-gray-300 rounded-lg w-64" autocomplete="off">
                    </div>
                    
                    <!-- User Menu -->
                    <div class="relative">
                        <button onclick="toggleUserMenu()" 
                                class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100">
                            <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                                <?php echo escape(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?>
                            </div>
                            <span class="hidden md:block font-medium"><?php echo escape($_SESSION['full_name'] ?? 'User'); ?></span>
                        </button>
                        
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 border z-50">
                            <button onclick="openModal('profileModal')" class="w-full text-left px-4 py-2 hover:bg-gray-50">
                                Edit Profile
                            </button>
                            <a href="logout.php" class="block px-4 py-2 hover:bg-gray-50 text-red-600">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <main>
