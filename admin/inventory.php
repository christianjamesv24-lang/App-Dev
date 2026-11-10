<?php
// admin/inventory.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Reports - BakeTrack Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="mb-8">
            <h1 class="text-2xl font-black text-gray-900">Inventory & Daily Reports</h1>
        </header>

        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="font-bold text-lg mb-2">Daily Stock Status</h3>
            <p class="text-sm text-gray-600 mb-6">Summary records of ingredient usage, baked stock output, and daily inventory reports.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase">Flour Stock</span>
                    <h4 class="text-xl font-black text-gray-900 mt-1">120 Bags</h4>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <span class="text-xs font-bold text-gray-500 uppercase">Butter Stock</span>
                    <h4 class="text-xl font-black text-gray-900 mt-1">45 Blocks</h4>
                </div>
            </div>
        </div>
    </main>

</body>
</html>