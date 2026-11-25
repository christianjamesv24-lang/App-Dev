<?php
require_once 'includes/functions.php';
requireValidCsrfToken();

requireAssistant();

$user_id = $_SESSION['user_id'];
$allowed_pages = ['bakery', 'orders', 'transactions', 'report', 'messages', 'settings'];
$requested_page = $_GET['page'] ?? 'bakery';
if (!is_string($requested_page) || !in_array($requested_page, $allowed_pages, true)) {
    header('Location: assistant.php?page=bakery');
    exit();
}
$page = $requested_page;
$orderPages = [];
foreach (['waiting', 'preparing', 'ready'] as $orderStatus) {
    $orderPage = filter_var($_GET[$orderStatus . '_page'] ?? 1, FILTER_VALIDATE_INT);
    $orderPages[$orderStatus] = $orderPage && $orderPage > 0 ? $orderPage : 1;
}
if ($page === 'report') {
    ensureOrderCustomerNameColumn();
}

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
if ($page === 'messages') {
    $read_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
    $read_stmt->bind_param('i', $user_id);
    $read_stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = filter_var($_POST['receiver_id'] ?? null, FILTER_VALIDATE_INT);
    $message = trim($_POST['message'] ?? '');
    if ($receiver_id && $receiver_id !== (int) $user_id && $message !== '') {
        $message_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $message_stmt->bind_param('iis', $user_id, $receiver_id, $message);
        if ($message_stmt->execute()) {
            header('Location: assistant.php?page=messages&sent=1');
            exit();
        }
    }
    header('Location: assistant.php?page=messages&error=message');
    exit();
}

// Handle direct sales using inventory prices and locked stock rows.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $items = json_decode($_POST['products'] ?? '', true);
    $submissionKey = $_POST['submission_key'] ?? '';
    $expectedKey = $_SESSION['order_submission_key'] ?? '';
    if (
        !is_string($submissionKey)
        || !preg_match('/\A[a-f0-9]{64}\z/', $submissionKey)
        || !is_string($expectedKey)
        || !hash_equals($expectedKey, $submissionKey)
    ) {
        header('Location: assistant.php?page=report&error=invalid');
        exit();
    }
    try {
        createCashSale($user_id, $items, $_POST['payment'] ?? null, $_POST['customer_name'] ?? '', $submissionKey);
        unset($_SESSION['order_submission_key']);
        header('Location: assistant.php?page=report&saved=1');
    } catch (InvalidArgumentException $error) {
        header('Location: assistant.php?page=report&error=invalid');
    } catch (Throwable $error) {
        error_log('Direct sale failed: ' . $error->getMessage());
        header('Location: assistant.php?page=report&error=save');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $status = isset($_POST['status']) && is_string($_POST['status']) ? $_POST['status'] : '';
    $reason = isset($_POST['reason']) && is_string($_POST['reason']) ? trim($_POST['reason']) : '';
    if ($order_id && in_array($status, ['waiting', 'preparing', 'ready', 'completed'], true)) {
        try {
            transitionOrder($order_id, $status, $reason, getCurrentUserRole(), (int) $user_id);
            header('Location: assistant.php?page=orders&updated=1');
        } catch (Throwable $error) {
            error_log('Assistant order update failed: ' . $error->getMessage());
            header('Location: assistant.php?page=orders&error=update');
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BakeTrack - Assistant</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="<?= !empty($user['darkmode']) ? 'dark-mode' : '' ?>" data-live-orders="<?= $page === 'orders' ? 'true' : 'false' ?>" data-csrf-token="<?= htmlspecialchars(csrfToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="dashboard-sidebar">
            <div class="sidebar-brand">
                <h2><span class="brand-mark" aria-hidden="true">BT</span> BakeTrack</h2>
                <p>Assistant Panel</p>
            </div>
            <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success" role="status">Sale saved and inventory updated.</div>
            <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-error" role="alert">Unable to save the sale. Check the products, stock, and payment, then try again.</div>
            <?php endif; ?>
            <nav class="sidebar-nav" aria-label="Assistant navigation">
                <a href="?page=bakery" class="<?= $page === 'bakery' ? 'active' : '' ?>" <?= $page === 'bakery' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-store"></i> Bakery
                </a>
                <a href="?page=orders" class="<?= $page === 'orders' ? 'active' : '' ?>" <?= $page === 'orders' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-clipboard-list"></i> Orders
                </a>
                <a href="?page=transactions" class="<?= $page === 'transactions' ? 'active' : '' ?>" <?= $page === 'transactions' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-credit-card"></i> Transactions
                </a>
                <a href="?page=report" class="<?= $page === 'report' ? 'active' : '' ?>" <?= $page === 'report' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-file-alt"></i> Report
                </a>
                <a href="?page=messages" class="<?= $page === 'messages' ? 'active' : '' ?>" <?= $page === 'messages' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>" <?= $page === 'settings' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-cog"></i> Settings
                </a>
                <form method="POST" action="logout.php" class="logout-form">
                    <?= csrfField() ?>
                    <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-mobile-header">
                <button class="dashboard-menu-toggle" type="button" aria-expanded="false" aria-controls="dashboard-sidebar" aria-label="Open navigation menu">
                    <span></span><span></span><span></span>
                </button>
                <span class="dashboard-mobile-brand"><span class="brand-mark" aria-hidden="true">BT</span> BakeTrack</span>
            </div>
            <!-- BAKERY -->
            <?php if ($page === 'bakery'): ?>
            <div class="page-header">
                <h1>Bakery Shop</h1>
                <div class="search-box">
                    <input type="text" id="searchProduct" placeholder="Search products..." oninput="filterProducts()">
                    <button type="button" onclick="filterProducts()"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="products-grid" id="productsGrid">
                <?php 
                $products = $conn->query("SELECT * FROM products ORDER BY date_added DESC");
                while ($product = $products->fetch_assoc()): 
                ?>
                <div class="product-card" data-name="<?= htmlspecialchars(mb_strtolower($product['name']), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(getProductImageUrl($product['image']), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='uploads/products/default.svg'">
                    <h4><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <p class="price">₱<?= number_format($product['price'], 2) ?></p>
                    <span class="status <?= $product['status'] === 'available' ? 'available' : 'unavailable' ?>">
                        <?= ucfirst($product['status']) ?>
                    </span>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>

            <!-- ORDERS -->
            <?php if ($page === 'orders'): ?>
            <div class="page-header">
                <h1>Orders Management</h1>
            </div>
            <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success" role="status">Order updated.</div>
            <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-error" role="alert">Unable to update that order.</div>
            <?php endif; ?>
            <div class="order-tabs">
                <button class="tab-btn active" onclick="showTab(event, 'waiting')">Waiting</button>
                <button class="tab-btn" onclick="showTab(event, 'preparing')">To Prepare</button>
                <button class="tab-btn" onclick="showTab(event, 'ready')">Ready to Grab</button>
            </div>
            <div class="order-content">
                <?php 
                $statuses = ['waiting', 'preparing', 'ready'];
                foreach ($statuses as $status):
                $orderPage = $orderPages[$status];
                $orderOffset = ($orderPage - 1) * 100;
                $orders = $conn->query("SELECT o.*, u.fullname as customer FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.status = '$status' 
                    ORDER BY o.order_date DESC, o.id DESC
                    LIMIT 101 OFFSET $orderOffset");
                $orderHasMore = $orders->num_rows > 100;
                ?>
                <div class="order-list" id="tab-<?= $status ?>" style="<?= $status === 'waiting' ? 'display:block' : 'display:none' ?>">
                    <?php if ($orders->num_rows > 0): ?>
                        <?php $orderShown = 0; while ($orderShown < 100 && ($order = $orders->fetch_assoc())): $orderShown++; ?>
                        <div class="order-card">
                            <div class="order-header">
                                <span class="order-id">#<?= $order['id'] ?></span>
                                <span class="order-customer">Customer: <?= htmlspecialchars($order['customer']) ?></span>
                                <span class="order-date"><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></span>
                            </div>
                            <div class="order-body">
                                <p><strong>Total:</strong> ₱<?= number_format($order['total'], 2) ?></p>
                                <div class="order-actions">
                                    <?php if ($status === 'waiting'): ?>
                                    <button class="btn btn-success" onclick="updateOrder(<?= $order['id'] ?>, 'preparing')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($status === 'preparing'): ?>
                                    <button class="btn btn-success" onclick="updateOrder(<?= $order['id'] ?>, 'ready')">
                                        <i class="fas fa-utensils"></i> Done Preparing
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($status === 'ready'): ?>
                                    <button class="btn btn-success" onclick="updateOrder(<?= $order['id'] ?>, 'completed')">
                                        <i class="fas fa-check"></i> Mark Completed
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="empty-state">No orders in <?= $status ?></p>
                    <?php endif; ?>
                    <?php if ($orderPage > 1 || $orderHasMore): ?>
                    <nav class="pagination-controls" aria-label="<?= htmlspecialchars(ucfirst($status), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> order pages">
                        <?php if ($orderPage > 1): ?>
                        <a href="?page=orders&<?= $status ?>_page=<?= $orderPage - 1 ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($orderHasMore): ?>
                        <a href="?page=orders&<?= $status ?>_page=<?= $orderPage + 1 ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- TRANSACTIONS -->
            <?php if ($page === 'transactions'): ?>
            <div class="page-header">
                <h1>Transactions</h1>
            </div>
            <div class="transactions-list">
                <?php 
                $transactionPage = filter_var($_GET['transactions_page'] ?? 1, FILTER_VALIDATE_INT);
                $transactionPage = $transactionPage && $transactionPage > 0 ? $transactionPage : 1;
                $transactionOffset = ($transactionPage - 1) * 100;
                $transactionLimit = 101;
                $transactions_stmt = $conn->prepare(
                    'SELECT t.*, u.fullname as user FROM transactions t
                     JOIN users u ON t.user_id = u.id
                     WHERE t.user_id = ?
                     ORDER BY t.transaction_date DESC, t.id DESC
                     LIMIT ? OFFSET ?'
                );
                $transactions_stmt->bind_param('iii', $user_id, $transactionLimit, $transactionOffset);
                $transactions_stmt->execute();
                $transactions = $transactions_stmt->get_result();
                $transactionsHaveMore = $transactions->num_rows > 100;
                $transactionsShown = 0;
                if ($transactions->num_rows > 0):
                while ($transactionsShown < 100 && ($trans = $transactions->fetch_assoc())):
                $transactionsShown++;
                ?>
                <div class="transaction-item">
                    <div class="trans-info">
                        <span class="trans-id">#<?= $trans['id'] ?></span>
                        <span class="trans-user">👤 <?= htmlspecialchars($trans['user']) ?></span>
                        <span class="trans-amount">₱<?= number_format($trans['amount'], 2) ?></span>
                        <span class="trans-status <?= $trans['status'] ?>"><?= ucfirst($trans['status']) ?></span>
                        <span class="trans-date"><?= date('M d, Y h:i A', strtotime($trans['transaction_date'])) ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <p class="empty-state">📭 No transactions yet</p>
                <?php endif; ?>
                <?php if ($transactionPage > 1 || $transactionsHaveMore): ?>
                <nav class="pagination-controls" aria-label="Transaction pages">
                    <?php if ($transactionPage > 1): ?>
                    <a href="?page=transactions&transactions_page=<?= $transactionPage - 1 ?>">Previous</a>
                    <?php endif; ?>
                    <?php if ($transactionsHaveMore): ?>
                    <a href="?page=transactions&transactions_page=<?= $transactionPage + 1 ?>">Next</a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- REPORT - Direct Sells -->
            <?php if ($page === 'report'): ?>
            <div class="page-header">
                <h1>Direct Sells Report</h1>
                <button class="btn btn-primary" onclick="openSellForm()">
                    <i class="fas fa-plus"></i> Add Sell
                </button>
            </div>
            
            <!-- Sell Form Modal -->
            <div id="sellModal" class="modal">
                <div class="modal-content" style="width: 600px;">
                    <span class="close" onclick="closeSellForm()">&times;</span>
                    <h2>New Direct Sell</h2>
                    <form id="sellForm" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="submit_report" value="1">
                        <input type="hidden" name="submission_key" value="<?= htmlspecialchars(getOrderSubmissionKey(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <div id="productItems">
                            <div class="sell-item">
                                <div class="form-group">
                                    <label>Product</label>
                                    <select name="product_id[]" class="product-select" onchange="updatePrice(this)" required>
                                        <option value="">Select Product</option>
                                        <?php 
                                        $products = $conn->query("SELECT * FROM products WHERE quantity > 0");
                                        while ($p = $products->fetch_assoc()):
                                        ?>
                                        <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
                                            <?= htmlspecialchars($p['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (₱<?= number_format($p['price'], 2) ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Quantity</label>
                                    <input type="number" name="quantity[]" class="item-qty" min="1" value="1" oninput="updateSellTotal()">
                                </div>
                                <div class="form-group">
                                    <label>Price</label>
                                    <input type="text" name="price[]" class="item-price" readonly>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeSellItem(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-secondary" onclick="addSellItem()">
                            <i class="fas fa-plus"></i> Add Another Product
                        </button>
                        
                        <div class="sell-total">
                            <p><strong>Total Amount: ₱<span id="sellTotal">0.00</span></strong></p>
                        </div>

                        <div class="form-group">
                            <label for="customerName">Customer</label>
                            <input type="text" name="customer_name" id="customerName" maxlength="255" value="Walk-in customer" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Customer Payment</label>
                            <input type="number" name="payment" id="customerPayment" step="0.01" placeholder="Enter payment amount" oninput="updateChange()">
                        </div>
                        
                        <div class="form-group">
                            <label>Change</label>
                            <input type="text" id="customerChange" readonly placeholder="Change will be calculated" class="form-control">
                        </div>
                        
                        <input type="hidden" name="products" id="productsData">
                        <input type="hidden" name="total" id="totalData">
                        
                        <div class="sell-actions">
                            <button type="button" class="btn btn-danger" onclick="closeSellForm()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Report</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Sold Items List -->
            <div class="sold-items">
                <h3>Recent Sells</h3>
                <?php 
                $sells = $conn->query("SELECT o.*, u.fullname as assistant FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.status = 'completed' AND o.user_id = $user_id
                    ORDER BY o.order_date DESC
                    LIMIT 10");
                if ($sells->num_rows > 0):
                ?>
                <div class="sold-list">
                    <?php while ($sell = $sells->fetch_assoc()): ?>
                    <div class="sold-item">
                        <div class="sold-header">
                            <span class="sold-id">#<?= $sell['id'] ?></span>
                            <span class="sold-by">Customer: <?= htmlspecialchars($sell['customer_name'] ?: 'Walk-in customer', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="sold-by">Recorded by: <?= htmlspecialchars($sell['assistant'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="sold-date"><?= date('M d, Y h:i A', strtotime($sell['order_date'])) ?></span>
                        </div>
                        <div class="sold-body">
                            <p><strong>Total:</strong> ₱<?= number_format($sell['total'], 2) ?></p>
                            <p><strong>Payment:</strong> ₱<?= number_format((float) ($sell['payment'] ?? 0), 2) ?></p>
                            <p><strong>Change:</strong> ₱<?= number_format((float) ($sell['change_amount'] ?? 0), 2) ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="empty-state">📭 No sold items yet</p>
                <?php endif; ?>
            </div>
            
            <script>
            let sellItems = [];
            
            function openSellForm() {
                document.getElementById('sellModal').style.display = 'block';
                document.getElementById('productItems').innerHTML = '';
                addSellItem();
            }
            
            function closeSellForm() {
                document.getElementById('sellModal').style.display = 'none';
                document.getElementById('productItems').innerHTML = '';
                document.getElementById('sellTotal').textContent = '0.00';
                document.getElementById('customerPayment').value = '';
                document.getElementById('customerChange').value = '';
            }
            
            function addSellItem() {
                const container = document.getElementById('productItems');
                const itemDiv = document.createElement('div');
                itemDiv.className = 'sell-item';
                itemDiv.innerHTML = `
                    <div class="form-group">
                        <label>Product</label>
                        <select name="product_id[]" class="product-select" onchange="updatePrice(this)" required>
                            <option value="">Select Product</option>
                            <?php 
                            $products = $conn->query("SELECT * FROM products WHERE quantity > 0");
                            while ($p = $products->fetch_assoc()):
                            ?>
                            <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
                                <?= htmlspecialchars($p['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (₱<?= number_format($p['price'], 2) ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity[]" class="item-qty" min="1" value="1" oninput="updateSellTotal()">
                    </div>
                    <div class="form-group">
                        <label>Price</label>
                        <input type="text" name="price[]" class="item-price" readonly>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeSellItem(this)">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(itemDiv);
                updateSellTotal();
            }
            
            function removeSellItem(btn) {
                const item = btn.closest('.sell-item');
                if (document.querySelectorAll('.sell-item').length > 1) {
                    item.remove();
                    updateSellTotal();
                } else {
                    alert('Must have at least one product!');
                }
            }

            function updatePrice(select) {
                const price = select.options[select.selectedIndex].getAttribute('data-price') || 0;
                const item = select.closest('.sell-item');
                item.querySelector('.item-price').value = parseFloat(price).toFixed(2);
                updateSellTotal();
            }
            
            function updateSellTotal() {
                let total = 0;
                document.querySelectorAll('.sell-item').forEach(item => {
                    const price = parseFloat(item.querySelector('.item-price').value) || 0;
                    const qty = parseInt(item.querySelector('.item-qty').value) || 0;
                    total += price * qty;
                });
                document.getElementById('sellTotal').textContent = total.toFixed(2);
                document.getElementById('totalData').value = total.toFixed(2);
                updateChange();
            }
            
            function updateChange() {
                const total = parseFloat(document.getElementById('sellTotal').textContent) || 0;
                const payment = parseFloat(document.getElementById('customerPayment').value) || 0;
                const change = payment - total;
                document.getElementById('customerChange').value = change >= 0 ? '₱' + change.toFixed(2) : 'Insufficient payment!';
            }
            
            // Submit form handler
            document.getElementById('sellForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const products = [];
                let total = 0;
                
                document.querySelectorAll('.sell-item').forEach(item => {
                    const select = item.querySelector('.product-select');
                    const id = select.value;
                    const name = select.options[select.selectedIndex].text;
                    const price = parseFloat(item.querySelector('.item-price').value) || 0;
                    const qty = parseInt(item.querySelector('.item-qty').value) || 0;
                    
                    if (id) {
                        products.push({id, name, price, quantity: qty});
                        total += price * qty;
                    }
                });
                
                if (products.length === 0) {
                    alert('Please add at least one product!');
                    return;
                }
                
                const payment = parseFloat(document.getElementById('customerPayment').value) || 0;
                if (payment < total) {
                    alert('Payment is less than total amount!');
                    return;
                }
                
                document.getElementById('productsData').value = JSON.stringify(products);
                document.getElementById('totalData').value = total.toFixed(2);
                this.submit();
            });
            
            window.onclick = function(event) {
                const modal = document.getElementById('sellModal');
                if (event.target === modal) closeSellForm();
            }
            </script>
            <?php endif; ?>

            <!-- MESSAGES -->
            <?php if ($page === 'messages'): ?>
            <div class="page-header">
                <h1>Messages</h1>
            </div>
            <?php if (isset($_GET['sent'])): ?>
            <div class="alert alert-success" role="status">Message sent.</div>
            <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-error" role="alert">Unable to send that message.</div>
            <?php endif; ?>
            <div class="messages-container">
                <div class="message-list">
                    <?php 
                    $messages = $conn->query("SELECT m.*, u.fullname as sender FROM messages m 
                        JOIN users u ON m.sender_id = u.id 
                        WHERE m.receiver_id = $user_id OR m.sender_id = $user_id
                        ORDER BY m.created_at DESC LIMIT 20");
                    if ($messages->num_rows > 0):
                    while ($msg = $messages->fetch_assoc()): 
                    ?>
                    <div class="message-item <?= $msg['is_read'] ? 'read' : 'unread' ?>">
                        <div class="msg-header">
                            <strong><?= htmlspecialchars($msg['sender']) ?></strong>
                            <small><?= date('M d, h:i A', strtotime($msg['created_at'])) ?></small>
                        </div>
                        <p><?= htmlspecialchars($msg['message']) ?></p>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <p class="empty-state">📭 No messages</p>
                    <?php endif; ?>
                </div>
                <div class="message-compose">
                    <h3>Send Message</h3>
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="form-group">
                            <select name="receiver_id" required>
                                <option value="">Select Recipient</option>
                                <?php 
                                $users = $conn->query("SELECT id, fullname, role FROM users WHERE id != $user_id AND is_active = 1");
                                while ($u = $users->fetch_assoc()):
                                ?>
                                <option value="<?= (int) $u['id'] ?>"><?= htmlspecialchars($u['fullname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (<?= htmlspecialchars($u['role'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <textarea name="message" placeholder="Type your message..." required></textarea>
                        </div>
                        <button type="submit" name="send_message" class="btn btn-primary">Send</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- SETTINGS -->
            <?php if ($page === 'settings'): 
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
                $fullname = trim($_POST['fullname'] ?? '');
                $birthdate = trim($_POST['birthdate'] ?? '');
                $darkmode = isset($_POST['darkmode']) ? 1 : 0;
                $anonymous = isset($_POST['anonymous']) ? 1 : 0;

                if ($fullname === '') {
                    echo "<script>alert('Full name is required.'); window.location='assistant.php?page=settings';</script>";
                    exit();
                }

                $birthdateError = birthdateValidationMessage($birthdate);
                if ($birthdateError !== '') {
                    echo "<script>alert('$birthdateError'); window.location='assistant.php?page=settings';</script>";
                    exit();
                }

                if ($birthdate === '') {
                    $update_stmt = $conn->prepare("UPDATE users SET fullname = ?, birthdate = NULL, darkmode = ?, anonymous = ? WHERE id = ?");
                    $update_stmt->bind_param('siii', $fullname, $darkmode, $anonymous, $user_id);
                } else {
                    $update_stmt = $conn->prepare("UPDATE users SET fullname = ?, birthdate = ?, darkmode = ?, anonymous = ? WHERE id = ?");
                    $update_stmt->bind_param('ssiii', $fullname, $birthdate, $darkmode, $anonymous, $user_id);
                }

                if ($update_stmt->execute()) {
                    echo "<script>alert('Settings updated!'); window.location='assistant.php?page=settings';</script>";
                } else {
                    echo "<script>alert('Unable to update settings.'); window.location='assistant.php?page=settings';</script>";
                }
                exit();
            }
            $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $user_stmt->bind_param('i', $user_id);
            $user_stmt->execute();
            $user = $user_stmt->get_result()->fetch_assoc();
            ?>
            <div class="page-header">
                <h1>Settings</h1>
            </div>
            <div class="settings-container">
                <div class="settings-card">
                    <h3>Profile Information</h3>
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Birthdate</label>
                            <input type="date" name="birthdate" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                            <small>Username cannot be changed</small>
                        </div>
                        
                        <div class="settings-options">
                            <div class="toggle-group">
                                <label>Dark Mode</label>
                                <div class="toggle-switch">
                                    <input type="checkbox" name="darkmode" <?= $user['darkmode'] ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </div>
                            </div>
                            <div class="toggle-group">
                                <label>Anonymous Mode</label>
                                <div class="toggle-switch">
                                    <input type="checkbox" name="anonymous" <?= $user['anonymous'] ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_settings" class="btn btn-primary">Update Settings</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
    <script>
    function filterProducts() {
        const input = document.getElementById('searchProduct');
        if (!input) return;
        const filter = input.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            card.style.display = (card.getAttribute('data-name') || '').includes(filter) ? '' : 'none';
        });
    }

    function showTab(event, tab) {
        const target = document.getElementById('tab-' + tab);
        if (!target) return;
        document.querySelectorAll('.order-list').forEach(list => list.style.display = 'none');
        document.querySelectorAll('.order-tabs .tab-btn').forEach(button => button.classList.remove('active'));
        target.style.display = 'block';
        event.currentTarget.classList.add('active');
    }

    function updateOrder(orderId, status) {
        if (confirm(`Update order #${orderId} to ${status}?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            const fields = {
                csrf_token: document.body.dataset.csrfToken,
                update_order: '1',
                order_id: orderId,
                status
            };
            Object.entries(fields).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
<script src="js/script.js"></script>
</body>
</html>