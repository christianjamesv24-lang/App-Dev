<?php
// assistant/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$sub = $_GET['sub'] ?? '';
?>
<aside class="w-64 bg-pink-300 text-gray-900 flex flex-col justify-between min-h-screen shadow-md p-4">
    <div>
        <!-- Profile Section -->
        <div class="flex flex-col items-center text-center pb-4">
            <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150" alt="Assistant Profile" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm mb-2">
            <span class="font-bold text-sm text-gray-900">assistant@baketrack.test</span>
            <span class="text-xs text-gray-700 bg-white/50 px-2 py-0.5 rounded-full mt-1">Store Assistant</span>
        </div>
        
        <hr class="border-white/40 my-3">

        <!-- Navigation Buttons -->
        <nav class="space-y-1.5 text-sm font-medium">
            <a href="shop.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'shop.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-store w-5"></i> Shop
            </a>
            <a href="orders.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'orders.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-box-archive w-5"></i> Orders
            </a>
            <a href="transactions.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'transactions.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-receipt w-5"></i> Transactions
            </a>

            <!-- Report Menu with Sub-buttons -->
            <div class="space-y-1">
                <a href="report.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'report.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                    <i class="fa-solid fa-file-lines w-5"></i> Report
                </a>
                <div class="pl-8 space-y-1 text-xs">
                    <a href="report.php?sub=add" class="block py-1 hover:text-white transition">• Add Report</a>
                    <a href="report.php?sub=today" class="block py-1 hover:text-white transition">• Today's Report</a>
                </div>
            </div>

            <a href="messages.php" class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $current_page == 'messages.php' ? 'bg-yellow-400 font-bold shadow-sm' : 'hover:bg-pink-200' ?>">
                <i class="fa-solid fa-messages w-5"></i> Messages
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