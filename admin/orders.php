<?php
// admin/orders.php
$tab = $_GET['tab'] ?? 'requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - BakeTrack Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-gray-900">Orders Management</h1>
            <div class="space-x-2">
                <a href="orders.php?tab=requests" class="px-4 py-2 rounded-lg font-bold text-sm <?= $tab == 'requests' ? 'bg-yellow-400 text-gray-900' : 'bg-gray-100 text-gray-600' ?>">Requests</a>
                <a href="orders.php?tab=successful" class="px-4 py-2 rounded-lg font-bold text-sm <?= $tab == 'successful' ? 'bg-yellow-400 text-gray-900' : 'bg-gray-100 text-gray-600' ?>">Successful</a>
                <a href="orders.php?tab=cancelled" class="px-4 py-2 rounded-lg font-bold text-sm <?= $tab == 'cancelled' ? 'bg-yellow-400 text-gray-900' : 'bg-gray-100 text-gray-600' ?>">Cancelled</a>
            </div>
        </header>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                        <th class="p-4">Order ID</th>
                        <th class="p-4">Customer</th>
                        <th class="p-4">Total Amount</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <tr>
                        <td class="p-4 font-semibold">#1024</td>
                        <td class="p-4">Juan Dela Cruz</td>
                        <td class="p-4 font-bold text-pink-600">₱360.00</td>
                        <td class="p-4"><span class="bg-yellow-100 text-yellow-800 px-2.5 py-1 rounded-full text-xs font-bold"><?= ucfirst($tab) ?></span></td>
                        <td class="p-4 text-center">
                            <button class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm">Update Status</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>