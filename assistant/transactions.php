<?php
// assistant/transactions.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - BakeTrack Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="mb-8">
            <h1 class="text-2xl font-black text-gray-900">Uploaded Reports & Transactions Record</h1>
        </header>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                        <th class="p-4">Reference ID</th>
                        <th class="p-4">Report Type</th>
                        <th class="p-4">Uploaded By</th>
                        <th class="p-4">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <tr>
                        <td class="p-4 font-semibold">REP-501</td>
                        <td class="p-4">Daily Sales Summary</td>
                        <td class="p-4 text-gray-600">Assistant</td>
                        <td class="p-4 text-gray-500 text-xs">October 6, 2026</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>