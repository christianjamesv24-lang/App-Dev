<?php
// customer/reviews.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Reviews - BakeTrack Customer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-gray-900">Product Ratings & Reviews</h1>
            <button class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 px-4 py-2 rounded-lg font-bold text-sm shadow-sm transition">
                <i class="fa-solid fa-star mr-1"></i> Add Review
            </button>
        </header>

        <div class="space-y-4 max-w-3xl">
            <!-- Review Item 1 -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex items-start gap-4">
                <img src="https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=100" class="w-16 h-16 rounded-xl object-cover">
                <div class="flex-1">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-lg">Artisan Sourdough</h3>
                        <div class="text-yellow-400 text-sm">
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">Reviewed on October 5, 2026</p>
                    <p class="text-sm text-gray-600 mt-2">"Super crisp ng crust at ang lambot sa loob! Napakasarap ipares sa kape tuwing umaga."</p>
                </div>
            </div>

            <!-- Review Item 2 -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex items-start gap-4">
                <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=100" class="w-16 h-16 rounded-xl object-cover">
                <div class="flex-1">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-lg">Classic Butter Croissant</h3>
                        <div class="text-yellow-400 text-sm">
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-regular fa-star"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">Reviewed on October 3, 2026</p>
                    <p class="text-sm text-gray-600 mt-2">"Flaky at napakabango ng butter. Sobrang sarap lalo na kapag medyo mainit pa."</p>
                </div>
            </div>
        </div>
    </main>

</body>
</html>