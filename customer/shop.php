<?php
// customer/shop.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - BakeTrack Customer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-black text-gray-900">BakeTrack Bakery Shop</h1>
                <p class="text-sm text-gray-500 mt-1">Order your favorite freshly baked goods right away.</p>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Product Card 1 -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col justify-between">
                <div>
                    <img src="https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=500" class="w-full h-48 object-cover">
                    <div class="p-5">
                        <h3 class="font-bold text-lg">Artisan Sourdough</h3>
                        <p class="text-pink-600 font-bold mt-1">₱180.00</p>
                        <p class="text-xs text-gray-500 mt-2">Crispy crust with a soft open crumb.</p>
                    </div>
                </div>
                <div class="p-5 pt-0">
                    <button class="w-full bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold py-2 rounded-lg shadow-sm transition text-sm">
                        <i class="fa-solid fa-cart-shopping mr-1"></i> Order Now
                    </button>
                </div>
            </div>

            <!-- Product Card 2 -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col justify-between">
                <div>
                    <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500" class="w-full h-48 object-cover">
                    <div class="p-5">
                        <h3 class="font-bold text-lg">Classic Butter Croissant</h3>
                        <p class="text-pink-600 font-bold mt-1">₱95.00</p>
                        <p class="text-xs text-gray-500 mt-2">Laminated French pastry with rich European butter.</p>
                    </div>
                </div>
                <div class="p-5 pt-0">
                    <button class="w-full bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold py-2 rounded-lg shadow-sm transition text-sm">
                        <i class="fa-solid fa-cart-shopping mr-1"></i> Order Now
                    </button>
                </div>
            </div>
        </div>
    </main>

</body>
</html>