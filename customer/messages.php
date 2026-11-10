<?php
// customer/messages.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - BakeTrack Customer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800 font-sans flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex bg-white">
        <!-- Message List (Left) -->
        <div class="w-1/3 border-r border-gray-100 flex flex-col">
            <div class="p-4 border-b border-gray-100 font-bold text-lg">Chats</div>
            <div class="overflow-y-auto flex-1 divide-y divide-gray-50">
                <div class="p-4 hover:bg-gray-50 cursor-pointer flex items-center gap-3 bg-gray-50">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100" class="w-12 h-12 rounded-full object-cover">
                    <div>
                        <h4 class="font-bold text-sm">BakeTrack Admin / Assistant</h4>
                        <p class="text-xs text-gray-500 truncate">Yes po! Meron pa kaming bagong luto...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Conversation (Right) -->
        <div class="flex-1 flex flex-col">
            <div class="p-4 border-b border-gray-100 flex items-center gap-3">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100" class="w-10 h-10 rounded-full object-cover">
                <div>
                    <h4 class="font-bold text-sm">BakeTrack Support</h4>
                    <span class="text-xs text-green-500 font-medium">● Online</span>
                </div>
            </div>

            <div class="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50/50">
                <div class="flex items-end gap-2">
                    <div class="bg-white p-3 rounded-2xl shadow-sm text-sm max-w-xs border border-gray-100">
                        Hello po! May available pa bang Artisan Sourdough ngayong hapon?
                    </div>
                </div>
                <div class="flex items-end justify-end gap-2">
                    <div class="bg-yellow-400 text-gray-900 p-3 rounded-2xl shadow-sm text-sm max-w-xs font-medium">
                        Yes po! Meron pa kaming bagong luto na batch. Gusto niyo po bang i-reserve ko?
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-100 flex items-center gap-2 bg-white">
                <input type="text" placeholder="Type a message..." class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-300 text-sm">
                <button class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 px-5 py-2 rounded-lg font-bold shadow-sm transition text-sm">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </main>

</body>
</html>