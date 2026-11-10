<?php
// admin/database.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - BakeTrack Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-gray-900">Database Users Management (CRUD)</h1>
            <button class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 px-4 py-2 rounded-lg font-bold text-sm shadow-sm transition">
                <i class="fa-solid fa-user-plus mr-1"></i> Add New User
            </button>
        </header>

        <!-- Users Table -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                        <th class="p-4">ID</th>
                        <th class="p-4">Name</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Role</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <tr>
                        <td class="p-4 font-semibold">1</td>
                        <td class="p-4">Maria Beatrice Santos</td>
                        <td class="p-4 text-gray-600">admin@baketrack.test</td>
                        <td class="p-4"><span class="bg-pink-100 text-pink-700 px-2.5 py-1 rounded-full text-xs font-bold">Admin</span></td>
                        <td class="p-4 text-center space-x-2">
                            <button class="text-blue-600 hover:text-blue-800 font-medium"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                            <button class="text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td class="p-4 font-semibold">2</td>
                        <td class="p-4">Juan Dela Cruz</td>
                        <td class="p-4 text-gray-600">juan@baketrack.test</td>
                        <td class="p-4"><span class="bg-yellow-100 text-yellow-800 px-2.5 py-1 rounded-full text-xs font-bold">Customer</span></td>
                        <td class="p-4 text-center space-x-2">
                            <button class="text-blue-600 hover:text-blue-800 font-medium"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                            <button class="text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>