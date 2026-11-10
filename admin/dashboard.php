<?php
// admin/dashboard.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BakeTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-gray-900">Admin Dashboard</h1>
            <span class="text-sm text-gray-500 font-medium">Welcome back, Admin</span>
        </header>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-50 border border-gray-100 p-6 rounded-2xl shadow-sm">
                <p class="text-xs text-gray-500 font-bold uppercase">Total Sales Today</p>
                <h3 class="text-2xl font-black text-pink-600 mt-2">₱14,850.00</h3>
            </div>
            <div class="bg-gray-50 border border-gray-100 p-6 rounded-2xl shadow-sm">
                <p class="text-xs text-gray-500 font-bold uppercase">Pending Requests</p>
                <h3 class="text-2xl font-black text-yellow-500 mt-2">8 Orders</h3>
            </div>
            <div class="bg-gray-50 border border-gray-100 p-6 rounded-2xl shadow-sm">
                <p class="text-xs text-gray-500 font-bold uppercase">Active Users</p>
                <h3 class="text-2xl font-black text-gray-900 mt-2">1,240</h3>
            </div>
        </div>

        <!-- Overview Section -->
        <div class="bg-gray-50 border border-gray-100 rounded-2xl p-6">
            <h3 class="font-bold text-lg mb-4">System Activity Summary</h3>
            <p class="text-sm text-gray-600">Here you can monitor live overview data, quick management metrics, and daily production stats for BakeTrack.</p>
        </div>
    </main>

</body>
</html>