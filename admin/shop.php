<?php
// admin/shop.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Management - BakeTrack Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-gray-900">Shop Products Catalog</h1>
            <a href="upload.php?sub=add" class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 px-4 py-2 rounded-lg font-bold text-sm shadow-sm transition">
                <i class="fa-solid fa-plus mr-1"></i> Add Product
            </a>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <img src="https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=500" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h3 class="font-bold text-lg">Artisan Sourdough</h3>
                    <p class="text-pink-600 font-bold mt-1">₱180.00</p>
                    <p class="text-xs text-gray-500 mt-2">Category: Breads</p>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h3 class="font-bold text-lg">Classic Butter Croissant</h3>
                    <p class="text-pink-600 font-bold mt-1">₱95.00</p>
                    <p class="text-xs text-gray-500 mt-2">Category: Pastries</p>
                </div>
            </div>
        </div>
    </main>

</body>
</html>