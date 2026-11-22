<?php
require_once 'includes/functions.php';
requireValidCsrfToken();

requireCustomer();

$user_id = $_SESSION['user_id'];
$allowed_pages = ['bakery', 'orders', 'messages', 'reviews', 'settings'];
$requested_page = $_GET['page'] ?? 'bakery';
if (!is_string($requested_page) || !in_array($requested_page, $allowed_pages, true)) {
    header('Location: customer.php?page=bakery');
    exit();
}
$page = $requested_page;
$orderPage = filter_var($_GET['orders_page'] ?? 1, FILTER_VALIDATE_INT);
$orderPage = $orderPage && $orderPage > 0 ? $orderPage : 1;
$orderOffset = ($orderPage - 1) * 100;

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
if ($page === 'messages') {
    $read_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
    $read_stmt->bind_param('i', $user_id);
    $read_stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['received_order'])) {
    $order_id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $order_stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'ready'");
    if ($order_id) {
        $order_stmt->bind_param('ii', $order_id, $user_id);
        $order_stmt->execute();
        if ($order_stmt->get_result()->fetch_assoc()) {
            try {
                transitionOrder($order_id, 'completed', '', 'customer', (int) $user_id);
                header('Location: customer.php?page=orders&received=1');
            } catch (Throwable $error) {
                error_log('Customer order receipt failed: ' . $error->getMessage());
                header('Location: customer.php?page=orders&error=received');
            }
            exit();
        }
    }
    header('Location: customer.php?page=orders&error=received');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = filter_var($_POST['receiver_id'] ?? null, FILTER_VALIDATE_INT);
    $message = trim($_POST['message'] ?? '');
    if ($receiver_id && $receiver_id !== (int) $user_id && $message !== '') {
        $message_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $message_stmt->bind_param('iis', $user_id, $receiver_id, $message);
        if ($message_stmt->execute()) {
            header('Location: customer.php?page=messages&sent=1');
            exit();
        }
    }
    header('Location: customer.php?page=messages&error=message');
    exit();
}

// Handle Order Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $submissionKey = $_POST['submission_key'] ?? '';
    $productsJson = $_POST['products'] ?? '';
    $products = is_string($productsJson) ? json_decode($productsJson, true) : null;
    $expectedKey = $_SESSION['order_submission_key'] ?? '';
    if (
        !is_string($submissionKey)
        || !preg_match('/\A[a-f0-9]{64}\z/', $submissionKey)
        || !is_string($expectedKey)
        || !hash_equals($expectedKey, $submissionKey)
        || !is_array($products)
        || $products === []
    ) {
        header('Location: customer.php?page=bakery&error=order');
        exit();
    }

    try {
        createOnlineOrder((int) $user_id, $products, $submissionKey);
        unset($_SESSION['order_submission_key']);
        header('Location: customer.php?page=orders&placed=1');
    } catch (InvalidArgumentException $error) {
        header('Location: customer.php?page=bakery&error=order');
    } catch (Throwable $error) {
        error_log('Customer order creation failed: ' . $error->getMessage());
        header('Location: customer.php?page=bakery&error=order');
    }
    exit();
}

// Handle Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
    $order_id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
    $commentValue = $_POST['comment'] ?? '';
    $comment = is_string($commentValue) ? trim($commentValue) : '';
    if ($comment === '' || mb_strlen($comment, 'UTF-8') > 2000) {
        header('Location: customer.php?page=reviews&error=review');
        exit();
    }
    $eligibility_stmt = $conn->prepare("SELECT o.id FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE o.id = ? AND o.user_id = ? AND o.status = 'completed' AND oi.product_id = ? AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.order_id = o.id AND r.product_id = oi.product_id AND r.user_id = o.user_id) LIMIT 1");
    $eligibility_stmt->bind_param('iii', $order_id, $user_id, $product_id);
    $eligibility_stmt->execute();
    if (
        $product_id === false || $product_id <= 0
        || $order_id === false || $order_id <= 0
        || $rating === false || $rating < 1 || $rating > 5
        || !$eligibility_stmt->get_result()->fetch_assoc()
    ) {
        header('Location: customer.php?page=reviews&error=review');
        exit();
    }
    $review_stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, order_id, rating, `comment`) VALUES (?, ?, ?, ?, ?)");
    $review_stmt->bind_param('iiiis', $user_id, $product_id, $order_id, $rating, $comment);
    if (!$review_stmt->execute()) {
        error_log('Review submission failed: ' . $review_stmt->error);
        header('Location: customer.php?page=reviews&error=review');
        exit();
    }
    header('Location: customer.php?page=reviews&submitted=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BakeTrack - Customer</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="<?= !empty($user['darkmode']) ? 'dark-mode' : '' ?>" data-csrf-token="<?= htmlspecialchars(csrfToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="dashboard-sidebar">
            <div class="sidebar-brand">
                <h2><span class="brand-mark" aria-hidden="true">BT</span> BakeTrack</h2>
                <p>Welcome, <?= htmlspecialchars($user['fullname']) ?></p>
            </div>
            <nav class="sidebar-nav" aria-label="Customer navigation">
                <a href="?page=bakery" class="<?= $page === 'bakery' ? 'active' : '' ?>" <?= $page === 'bakery' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-store"></i> Bakery
                </a>
                <a href="?page=orders" class="<?= $page === 'orders' ? 'active' : '' ?>" <?= $page === 'orders' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-clipboard-list"></i> Orders
                </a>
                <a href="?page=messages" class="<?= $page === 'messages' ? 'active' : '' ?>" <?= $page === 'messages' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="?page=reviews" class="<?= $page === 'reviews' ? 'active' : '' ?>" <?= $page === 'reviews' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-star"></i> Reviews
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
                // Get reviews for this product
                $reviews = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE product_id = {$product['id']}");
                $review_data = $reviews->fetch_assoc();
                $avg_rating = number_format($review_data['avg_rating'] ?? 0, 1);
                $review_count = $review_data['count'] ?? 0;
                ?>
                <div class="product-card" data-name="<?= htmlspecialchars(mb_strtolower($product['name']), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(getProductImageUrl($product['image']), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='uploads/products/default.svg'">
                    <h4><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <p class="price">₱<?= number_format($product['price'], 2) ?></p>
                    <div class="product-rating">
                        <i class="fas fa-star" style="color: #F39C12;"></i>
                        <span><?= (int) $review_count === 0 ? '0.0%' : $avg_rating . ' (' . $review_count . ' reviews)' ?></span>
                    </div>
                    <span class="status <?= $product['status'] === 'available' ? 'available' : 'unavailable' ?>">
                        <?= ucfirst($product['status']) ?>
                    </span>
                    <?php if ($product['status'] === 'available'): ?>
                    <button class="btn btn-primary btn-sm" onclick='openOrderForm(<?= (int) $product['id'] ?>, <?= htmlspecialchars(json_encode($product['name'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>, <?= json_encode((float) $product['price']) ?>)'>
                        <i class="fas fa-shopping-cart"></i> Order
                    </button>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
            <p id="searchEmpty" class="empty-state" style="display: none;">Not Available</p>

            <!-- Order Modal -->
            <div id="orderModal" class="modal">
                <div class="modal-content" style="width: 550px;">
                    <span class="close" onclick="closeOrderForm()">&times;</span>
                    <h2>Place Order</h2>
                    <form id="orderForm" method="POST">
                        <?= csrfField() ?>
                        <div id="orderItems">
                            <div class="order-item">
                                <div class="form-group">
                                    <label>Product</label>
                                    <select name="product_id[]" class="product-select" onchange="updateOrderPrice(this)" required>
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
                                    <input type="number" name="quantity[]" class="order-qty" min="1" value="1" oninput="updateOrderTotal()">
                                </div>
                                <div class="form-group">
                                    <label>Price</label>
                                    <input type="text" name="price[]" class="order-price" readonly>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeOrderItem(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-secondary" onclick="addOrderItem()">
                            <i class="fas fa-plus"></i> Add Another Product
                        </button>
                        
                        <div class="order-total">
                            <p><strong>Total Amount: ₱<span id="orderTotal">0.00</span></strong></p>
                        </div>
                        
                        <input type="hidden" name="products" id="orderProductsData">
                        <input type="hidden" name="total" id="orderTotalData">
                        <input type="hidden" name="place_order" value="1">
                        <input type="hidden" name="submission_key" value="<?= htmlspecialchars(getOrderSubmissionKey(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        
                        <div class="order-actions">
                            <button type="button" class="btn btn-danger" onclick="closeOrderForm()">Cancel</button>
                            <button type="submit" name="place_order" class="btn btn-primary">Send Request</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- ORDERS -->
            <?php if ($page === 'orders'): ?>
            <div class="page-header">
                <h1>My Orders</h1>
            </div>
                <?php if (isset($_GET['received'])): ?>
                <div class="alert alert-success" role="status">Order marked as received. Thank you!</div>
                <?php elseif (isset($_GET['placed'])): ?>
                <div class="alert alert-success" role="status">Order placed successfully! Waiting for approval.</div>
                <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-error" role="alert">Unable to update that order.</div>
                <?php endif; ?>
                <div class="order-tabs">
                    <button class="tab-btn active" onclick="showTab(event, 'waiting')">Waiting</button>
                    <button class="tab-btn" onclick="showTab(event, 'preparing')">On Prepare</button>
                    <button class="tab-btn" onclick="showTab(event, 'ready')">To Grab</button>
                    <button class="tab-btn" onclick="showTab(event, 'completed')">Completed</button>
                </div>
                <div class="order-content">
                <?php 
                    $statuses = ['waiting', 'preparing', 'ready', 'completed'];
                foreach ($statuses as $status):
                $orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id AND status = '$status' ORDER BY order_date DESC, id DESC LIMIT 101 OFFSET $orderOffset");
                $orderHasMore = $orders->num_rows > 100;
                ?>
                <div class="order-list" id="tab-<?= $status ?>" style="<?= $status === 'waiting' ? 'display:block' : 'display:none' ?>">
                    <?php if ($orders->num_rows > 0): ?>
                        <?php $orderShown = 0; while ($orderShown < 100 && ($order = $orders->fetch_assoc())): $orderShown++; ?>
                        <div class="order-card">
                            <div class="order-header">
                                <span class="order-id">#<?= $order['id'] ?></span>
                                <span class="order-date"><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></span>
                            </div>
                            <div class="order-body">
                                <p><strong>Total:</strong> ₱<?= number_format($order['total'], 2) ?></p>
                                <a class="btn btn-secondary btn-sm" href="?page=orders&view_receipt=<?= $order['id'] ?>">
                                    <i class="fas fa-receipt"></i> View Receipt
                                </a>
                                <?php if ($status === 'ready'): ?>
                                <form method="POST" class="inline-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <button type="submit" name="received_order" value="1" class="btn btn-success">
                                        <i class="fas fa-hand"></i> Grab Order
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="empty-state">📭 No orders in <?= $status ?></p>
                    <?php endif; ?>
                    <?php if ($orderPage > 1 || $orderHasMore): ?>
                    <nav class="pagination-controls" aria-label="<?= htmlspecialchars(ucfirst($status), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> order pages">
                        <?php if ($orderPage > 1): ?>
                        <a href="?page=orders&orders_page=<?= $orderPage - 1 ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($orderHasMore): ?>
                        <a href="?page=orders&orders_page=<?= $orderPage + 1 ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (isset($_GET['view_receipt'])):
                $receipt_id = (int) $_GET['view_receipt'];
                $receipt_order = $conn->query("SELECT * FROM orders WHERE id = $receipt_id AND user_id = $user_id")->fetch_assoc();
                if ($receipt_order):
                    $receipt_items = $conn->query("SELECT oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $receipt_id");
            ?>
            <section class="receipt-preview" id="receiptPreview">
                <div class="receipt-actions"><button class="btn btn-primary" type="button" onclick="window.print()"><i class="fas fa-receipt"></i> Print Receipt</button></div>
                <h2>BakeTrack</h2>
                <p>Order #<?= str_pad($receipt_order['id'], 6, '0', STR_PAD_LEFT) ?></p>
                <p><?= date('M d, Y h:i A', strtotime($receipt_order['order_date'])) ?></p>
                <hr>
                <?php while ($receipt_item = $receipt_items->fetch_assoc()): ?>
                    <p><?= htmlspecialchars($receipt_item['name']) ?> x <?= (int) $receipt_item['quantity'] ?> <span>₱<?= number_format($receipt_item['price'] * $receipt_item['quantity'], 2) ?></span></p>
                <?php endwhile; ?>
                <hr>
                <p><strong>Total <span>₱<?= number_format($receipt_order['total'], 2) ?></span></strong></p>
            </section>
            <?php endif; endif; ?>
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

            <!-- REVIEWS -->
            <?php if ($page === 'reviews'): ?>
            <div class="page-header">
                <h1>My Reviews</h1>
            </div>
            <?php if (isset($_GET['submitted'])): ?>
            <div class="alert alert-success" role="status">Review submitted. Thank you!</div>
            <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-error" role="alert">That review could not be submitted.</div>
            <?php endif; ?>
            
            <!-- Reviewable Orders -->
            <div class="reviewable-orders">
                <h3>Orders Ready for Review</h3>
                <?php 
                $reviewable = $conn->query("SELECT o.*, oi.product_id, p.name AS product_name FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    LEFT JOIN reviews r ON o.id = r.order_id AND r.user_id = $user_id AND r.product_id = oi.product_id
                    WHERE o.user_id = $user_id AND o.status = 'completed' AND r.id IS NULL
                    ORDER BY o.order_date DESC");
                if ($reviewable->num_rows > 0):
                while ($order = $reviewable->fetch_assoc()):
                ?>
                <div class="review-order-item">
                    <div class="review-order-info">
                        <span>Order #<?= $order['id'] ?></span>
                        <span>Total: ₱<?= number_format($order['total'], 2) ?></span>
                        <span><?= date('M d, Y', strtotime($order['order_date'])) ?></span>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openReviewForm(<?= $order['id'] ?>, <?= $order['product_id'] ?>)">
                        <i class="fas fa-star"></i> Review <?= htmlspecialchars($order['product_name']) ?>
                    </button>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <p class="empty-state">No orders ready for review yet</p>
                <?php endif; ?>
            </div>
            
            <!-- My Reviews List -->
            <div class="my-reviews">
                <h3>My Reviews</h3>
                <?php 
                $reviews = $conn->query("SELECT r.*, p.name as product_name FROM reviews r 
                    JOIN products p ON r.product_id = p.id 
                    WHERE r.user_id = $user_id 
                    ORDER BY r.created_at DESC");
                if ($reviews->num_rows > 0):
                while ($review = $reviews->fetch_assoc()):
                ?>
                <div class="review-item">
                    <div class="review-header">
                        <span class="review-product"><?= htmlspecialchars($review['product_name']) ?></span>
                        <span class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?= $i <= $review['rating'] ? '★' : '☆' ?>
                            <?php endfor; ?>
                        </span>
                        <span class="review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                    </div>
                    <p><?= htmlspecialchars($review['comment']) ?></p>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <p class="empty-state">You haven't written any reviews yet</p>
                <?php endif; ?>
            </div>

            <!-- Review Modal -->
            <div id="reviewModal" class="modal">
                <div class="modal-content" style="width: 500px;">
                    <span class="close" onclick="closeReviewForm()">&times;</span>
                    <h2>Write a Review</h2>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="order_id" id="reviewOrderId">
                        <input type="hidden" name="product_id" id="reviewProductId">
                        
                        <div class="form-group">
                            <label>Rate this product</label>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span onclick="setRating(<?= $i ?>)" id="star<?= $i ?>" style="font-size: 30px; cursor: pointer;">☆</span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="reviewRating" value="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Your Comment</label>
                            <textarea name="comment" id="reviewComment" rows="4" placeholder="Share your experience..." required></textarea>
                        </div>
                        
                        <div class="review-actions">
                            <button type="button" class="btn btn-danger" onclick="closeReviewForm()">Cancel</button>
                            <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                        </div>
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
                    echo "<script>alert('Full name is required.'); window.location='customer.php?page=settings';</script>";
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
                    echo "<script>alert('Settings updated!'); window.location='customer.php?page=settings';</script>";
                } else {
                    echo "<script>alert('Unable to update settings.'); window.location='customer.php?page=settings';</script>";
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
                            <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Birthdate</label>
                            <input type="date" name="birthdate" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" disabled>
                            <small>Email cannot be changed</small>
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
    // ==================== JAVASCRIPT FUNCTIONS ====================
    
    // Product filter
    function filterProducts() {
        const input = document.getElementById('searchProduct').value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');
        let visible = 0;
        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const matches = name.includes(input);
            card.style.display = matches ? '' : 'none';
            if (matches) visible++;
        });
        const emptyMessage = document.getElementById('searchEmpty');
        if (emptyMessage) emptyMessage.style.display = visible === 0 ? 'block' : 'none';
    }
    
    // Order functions
    function openOrderForm(productId, productName, productPrice) {
        document.getElementById('orderModal').style.display = 'block';
        // Pre-fill with selected product
        const container = document.getElementById('orderItems');
        container.innerHTML = '';
        const itemDiv = document.createElement('div');
        itemDiv.className = 'order-item';
        itemDiv.innerHTML = `
            <div class="form-group">
                <label>Product</label>
                <select name="product_id[]" class="product-select" onchange="updateOrderPrice(this)" required>
                    <option value="${productId}" data-price="${productPrice}" selected>${productName} (₱${parseFloat(productPrice).toFixed(2)})</option>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity[]" class="order-qty" min="1" value="1" oninput="updateOrderTotal()">
            </div>
            <div class="form-group">
                <label>Price</label>
                <input type="text" name="price[]" class="order-price" value="${parseFloat(productPrice).toFixed(2)}" readonly>
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeOrderItem(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(itemDiv);
        updateOrderTotal();
    }
    
    function closeOrderForm() {
        document.getElementById('orderModal').style.display = 'none';
        document.getElementById('orderItems').innerHTML = '';
        document.getElementById('orderTotal').textContent = '0.00';
    }
    
    function addOrderItem() {
        const container = document.getElementById('orderItems');
        const itemDiv = document.createElement('div');
        itemDiv.className = 'order-item';
        itemDiv.innerHTML = `
            <div class="form-group">
                <label>Product</label>
                <select name="product_id[]" class="product-select" onchange="updateOrderPrice(this)" required>
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
                <input type="number" name="quantity[]" class="order-qty" min="1" value="1" oninput="updateOrderTotal()">
            </div>
            <div class="form-group">
                <label>Price</label>
                <input type="text" name="price[]" class="order-price" readonly>
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeOrderItem(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(itemDiv);
        updateOrderTotal();
    }
    
    function removeOrderItem(btn) {
        const item = btn.closest('.order-item');
        if (document.querySelectorAll('.order-item').length > 1) {
            item.remove();
            updateOrderTotal();
        } else {
            alert('Must have at least one product!');
        }
    }
    
    function updateOrderPrice(select) {
        const price = select.options[select.selectedIndex].getAttribute('data-price') || 0;
        const item = select.closest('.order-item');
        item.querySelector('.order-price').value = parseFloat(price).toFixed(2);
        updateOrderTotal();
    }
    
    function updateOrderTotal() {
        let total = 0;
        document.querySelectorAll('.order-item').forEach(item => {
            const price = parseFloat(item.querySelector('.order-price').value) || 0;
            const qty = parseInt(item.querySelector('.order-qty').value) || 0;
            total += price * qty;
        });
        document.getElementById('orderTotal').textContent = total.toFixed(2);
        document.getElementById('orderTotalData').value = total.toFixed(2);
    }
    
    // Order form submit
    const orderForm = document.getElementById('orderForm');
    if (orderForm) orderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const products = [];
        let total = 0;
        
        document.querySelectorAll('.order-item').forEach(item => {
            const select = item.querySelector('.product-select');
            const id = select.value;
            const name = select.options[select.selectedIndex].text;
            const price = parseFloat(item.querySelector('.order-price').value) || 0;
            const qty = parseInt(item.querySelector('.order-qty').value) || 0;
            
            if (id) {
                products.push({id, name, price, quantity: qty});
                total += price * qty;
            }
        });
        
        if (products.length === 0) {
            alert('Please add at least one product!');
            return;
        }
        
        document.getElementById('orderProductsData').value = JSON.stringify(products);
        document.getElementById('orderTotalData').value = total.toFixed(2);
        this.submit();
    });
    
    // Tab functions
    function showTab(event, tab) {
        document.querySelectorAll('.order-list').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tab).style.display = 'block';
        event.currentTarget.classList.add('active');
    }
    
    function markAsReceived(orderId) {
        if (confirm('Have you received your order? This will mark it as completed.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            const fields = {
                csrf_token: document.body.dataset.csrfToken,
                received_order: '1',
                order_id: orderId
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
    
    // Review functions
    let selectedRating = 0;
    
    function openReviewForm(orderId, productId) {
        document.getElementById('reviewModal').style.display = 'block';
        document.getElementById('reviewOrderId').value = orderId;
        document.getElementById('reviewProductId').value = productId;
        document.getElementById('reviewRating').value = 0;
        document.getElementById('reviewComment').value = '';
        selectedRating = 0;
        for (let i = 1; i <= 5; i++) {
            document.getElementById('star' + i).textContent = '☆';
        }
    }
    
    function closeReviewForm() {
        document.getElementById('reviewModal').style.display = 'none';
    }
    
    function setRating(rating) {
        selectedRating = rating;
        document.getElementById('reviewRating').value = rating;
        for (let i = 1; i <= 5; i++) {
            document.getElementById('star' + i).textContent = i <= rating ? '★' : '☆';
        }
    }
    
    // Modal close handlers
    window.onclick = function(event) {
        const orderModal = document.getElementById('orderModal');
        const reviewModal = document.getElementById('reviewModal');
        if (event.target === orderModal) closeOrderForm();
        if (event.target === reviewModal) closeReviewForm();
    }
    </script>
<script src="js/script.js"></script>
</body>
</html>