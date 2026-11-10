<?php
// admin/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-pink-300 text-gray-900 flex flex-col justify-between min-h-screen shadow-md p-4">
    <div>
        <!-- Profile Section -->
        <div class="flex flex-col items-center text-center pb-4">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150" alt="Admin Profile" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm mb-2">
            <span class="font-bold text-sm text-gray-900">admin@baketrack.test</span>
            <span class="text-xs text-gray-700 bg-white/50 px-2 py-0.5 rounded-full mt-1">Administrator</span>
        </div>
        
        <hr class="border-white/40 my-3">

        <!-- Navigation Buttons -->
        <nav class="space-y-1.5 text-sm font-medium">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'dashboard.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-chart-pie w-5"></i> Dashboard
            </a>
            <a href="shop.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'shop.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-store w-5"></i> Shop
            </a>

            <!-- Upload Menu with Sub-buttons -->
            <div class="space-y-1">
                <a href="upload.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= in_array($current_page, ['upload.php', 'products_uploader.php']) ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                    <i class="fa-solid fa-cloud-arrow-up w-5"></i> Upload
                </a>
                <div class="pl-8 space-y-1 text-xs">
                    <a href="upload.php?sub=add" class="block py-1 hover:text-white transition">• Add Product</a>
                    <a href="upload.php?sub=list" class="block py-1 hover:text-white transition">• Added Products</a>
                </div>
            </div>

            <!-- Orders Menu with Sub-buttons -->
            <div class="space-y-1 pt-1">
                <a href="orders.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'orders.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                    <i class="fa-solid fa-box-archive w-5"></i> Orders
                </a>
                <div class="pl-8 space-y-1 text-xs">
                    <a href="orders.php?tab=requests" class="block py-1 hover:text-white transition">• Requests</a>
                    <a href="orders.php?tab=successful" class="block py-1 hover:text-white transition">• Successful</a>
                    <a href="orders.php?tab=cancelled" class="block py-1 hover:text-white transition">• Cancelled</a>
                </div>
            </div>

            <a href="transactions.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'transactions.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-receipt w-5"></i> Transactions
            </a>
            <a href="messages.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'messages.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-messages w-5"></i> Messages
            </a>
            <a href="inventory.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'inventory.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-warehouse w-5"></i> Inventory
            </a>
            <a href="database.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'database.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-database w-5"></i> Database (CRUD)
            </a>
            <a href="settings.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'settings.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-gear w-5"></i> Settings
            </a>
        </nav>
    </div>

    <!-- Logout Button -->
    <div class="pt-4">
        <hr class="border-white/40 mb-3">
        <a href="../index.php" class="flex items-center justify-center gap-2 w-full bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold py-2 px-4 rounded-lg shadow-sm transition text-sm">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</aside>