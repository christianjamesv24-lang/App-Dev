<?php
// admin/upload.php
$sub = $_GET['sub'] ?? 'list';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Management - BakeTrack Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-gray-900">Upload & Product Uploader</h1>
            <div class="space-x-2">
                <a href="upload.php?sub=add" class="px-4 py-2 rounded-lg font-bold text-sm <?= $sub == 'add' ? 'bg-yellow-400 text-gray-900' : 'bg-gray-100 text-gray-600' ?>">Add Product</a>
                <a href="upload.php?sub=list" class="px-4 py-2 rounded-lg font-bold text-sm <?= $sub == 'list' ? 'bg-yellow-400 text-gray-900' : 'bg-gray-100 text-gray-600' ?>">Products Uploader</a>
            </div>
        </header>

        <?php if ($sub == 'add'): ?>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 max-w-xl">
                <h3 class="font-bold text-lg mb-4">Add New Product Form</h3>
                <form action="#" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Product Name</label>
                        <input type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Price (₱)</label>
                        <input type="number" step="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Product Image</label>
                        <input type="file" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-pink-100 file:text-pink-700 hover:file:bg-pink-200">
                    </div>
                    <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold px-6 py-2.5 rounded-lg shadow-sm transition text-sm">Save Product</button>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                            <th class="p-4">Image</th>
                            <th class="p-4">Product Name</th>
                            <th class="p-4">Price</th>
                            <th class="p-4">Date Uploaded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <tr>
                            <td class="p-4"><img src="https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=100" class="w-12 h-12 rounded-lg object-cover"></td>
                            <td class="p-4 font-semibold">Artisan Sourdough</td>
                            <td class="p-4 text-pink-600 font-bold">₱180.00</td>
                            <td class="p-4 text-gray-500 text-xs">October 6, 2026</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>