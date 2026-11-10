<?php
// assistant/shop.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - BakeTrack Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="mb-8">
            <h1 class="text-2xl font-black text-gray-900">Shop Catalog Overview</h1>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <img src="https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=500" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h3 class="font-bold text-lg">Artisan Sourdough</h3>
                    <p class="text-pink-600 font-bold mt-1">₱180.00</p>
                    <span class="inline-block mt-3 bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold">In Stock</span>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h3 class="font-bold text-lg">Classic Butter Croissant</h3>
                    <p class="text-pink-600 font-bold mt-1">₱95.00</p>
                    <span class="inline-block mt-3 bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold">In Stock</span>
                </div>
            </div>
        </div>
    </main>

</body>
</html>