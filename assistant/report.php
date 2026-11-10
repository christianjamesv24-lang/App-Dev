<?php
// assistant/report.php
$sub = $_GET['sub'] ?? 'today';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - BakeTrack Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-gray-900">Reports Management</h1>
            <div class="space-x-2">
                <a href="report.php?sub=add" class="px-4 py-2 rounded-lg font-bold text-sm <?= $sub == 'add' ? 'bg-yellow-400 text-gray-900' : 'bg-gray-100 text-gray-600' ?>">Add Report</a>
                <a href="report.php?sub=today" class="px-4 py-2 rounded-lg font-bold text-sm <?= $sub == 'today' ? 'bg-yellow-400 text-gray-900' : 'bg-gray-100 text-gray-600' ?>">Today's Report</a>
            </div>
        </header>

        <?php if ($sub == 'add'): ?>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 max-w-xl">
                <h3 class="font-bold text-lg mb-4">Submit New Daily Report</h3>
                <form action="#" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Report Title</label>
                        <input type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-300 text-sm" placeholder="e.g., Afternoon Inventory & Sales">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Report Details</label>
                        <textarea rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-300 text-sm" placeholder="Ilagay ang detalye ng report..."></textarea>
                    </div>
                    <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold px-6 py-2.5 rounded-lg shadow-sm transition text-sm">Upload Report</button>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <h3 class="font-bold text-lg mb-2">Today's Summary Report (October 6, 2026)</h3>
                <p class="text-sm text-gray-600 mb-4">Lahat ng nakatalang transaksyon at report para sa araw na ito ay maayos na nai-log.</p>
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 space-y-2">
                    <p class="text-sm font-semibold">Total Bakes Sold: <span class="text-pink-600">85 items</span></p>
                    <p class="text-sm font-semibold">Total Cash Collected: <span class="text-pink-600">₱9,450.00</span></p>
                </div>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>