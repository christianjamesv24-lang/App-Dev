<?php
/**
 * BAKE TRACK - Functions Library
 * Reusable functions for the entire application
 */

require_once dirname(__DIR__) . '/config.php';

// ============================
// USER FUNCTIONS
// ============================

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? 0;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get the dashboard path for an authenticated user with a recognized role.
 */
function getAuthenticatedDashboardUrl() {
    if (!isLoggedIn()) {
        return null;
    }

    $dashboards = [
        'admin' => 'admin.php',
        'assistant' => 'assistant.php',
        'customer' => 'customer.php'
    ];

    return $dashboards[getCurrentUserRole()] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    global $conn;
    $user_id = getCurrentUserId();
    if ($user_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    return null;
}

/**
 * Get user by ID
 */
function getUserById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get user by username or email
 */
function getUserByLogin($login) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return getCurrentUserRole() === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is assistant
 */
function isAssistant() {
    return hasRole('assistant');
}

/**
 * Check if user is customer
 */
function isCustomer() {
    return hasRole('customer');
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: auth.php');
        exit();
    }
}

/**
 * Require a specific authenticated role and preserve role-based redirects.
 */
function requireRole($role, $allowAdmin = false) {
    requireLogin();

    $hasRequiredRole = getCurrentUserRole() === $role;
    $isAllowedAdmin = $allowAdmin && isAdmin();
    if ($hasRequiredRole || $isAllowedAdmin) {
        return;
    }

    $dashboard = getAuthenticatedDashboardUrl();
    header('Location: ' . ($dashboard ?? 'auth.php'));
    exit();
}

/**
 * Redirect if not admin
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Redirect if not assistant
 */
function requireAssistant() {
    requireRole('assistant', true);
}

/**
 * Redirect if not customer
 */
function requireCustomer() {
    requireRole('customer');
}

// ============================
// PRODUCT FUNCTIONS
// ============================

/**
 * Get all products
 */
function getAllProducts($limit = null, $order = 'DESC') {
    global $conn;
    $sql = "SELECT * FROM products ORDER BY date_added $order";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return $conn->query($sql);
}

/**
 * Get available products only
 */
function getAvailableProducts() {
    global $conn;
    return $conn->query("SELECT * FROM products WHERE status = 'available' ORDER BY date_added DESC");
}

/**
 * Get product by ID
 */
function getProductById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Update product status based on quantity
 */
function updateProductStatus($id) {
    global $conn;
    $product = getProductById($id);
    if ($product) {
        $status = $product['quantity'] > 0 ? 'available' : 'not available';
        $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }
    return false;
}

/**
 * Update product quantity
 */
function updateProductQuantity($id, $quantity) {
    global $conn;
    $stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $quantity, $id);
    return $stmt->execute();
}

/**
 * Add new product
 */
function addProduct($name, $price, $quantity, $description = '', $image = 'default.svg') {
    global $conn;
    $status = $quantity > 0 ? 'available' : 'not available';
    $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, description, image, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdisss", $name, $price, $quantity, $description, $image, $status);
    return $stmt->execute();
}

/**
 * Update product
 */
function updateProduct($id, $name, $price, $quantity, $description = '', $image = null) {
    global $conn;
    $status = $quantity > 0 ? 'available' : 'not available';
    
    if ($image) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, description = ?, image = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sdisssi", $name, $price, $quantity, $description, $image, $status, $id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, description = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sdissi", $name, $price, $quantity, $description, $status, $id);
    }
    return $stmt->execute();
}

/**
 * Delete product
 */
function deleteProduct($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

/**
 * Search products
 */
function searchProducts($keyword) {
    global $conn;
    $keyword = "%$keyword%";
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY date_added DESC");
    $stmt->bind_param("ss", $keyword, $keyword);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================
// ORDER FUNCTIONS
// ============================

/**
 * Get orders by status
 */
function getOrdersByStatus($status) {
    global $conn;
    $stmt = $conn->prepare("SELECT o.*, u.fullname as customer FROM orders o 
                            JOIN users u ON o.user_id = u.id 
                            WHERE o.status = ? 
                            ORDER BY o.order_date DESC");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get user orders
 */
function getUserOrders($user_id, $status = null) {
    global $conn;
    if ($status) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND status = ? ORDER BY order_date DESC");
        $stmt->bind_param("is", $user_id, $status);
    } else {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get order by ID
 */
function getOrderById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT o.*, u.fullname as customer FROM orders o 
                            JOIN users u ON o.user_id = u.id 
                            WHERE o.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get order items
 */
function getOrderItems($order_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Calculate order total
 */
function calculateOrderTotal($order_id) {
    $items = getOrderItems($order_id);
    $total = 0;
    while ($item = $items->fetch_assoc()) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Create new order
 */
function createOrder($user_id, $total, $payment = 0, $change = 0, $notes = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, payment, change_amount, notes, status) VALUES (?, ?, ?, ?, ?, 'waiting')");
    $stmt->bind_param("iddds", $user_id, $total, $payment, $change, $notes);
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

/**
 * Add order item
 */
function addOrderItem($order_id, $product_id, $quantity, $price) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
    return $stmt->execute();
}

/**
 * Update order status
 */
function updateOrderStatus($order_id, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    return $stmt->execute();
}

/**
 * Update an order and create its sale transaction exactly once when completed.
 */
function transitionOrder($order_id, $status, $reason = '') {
    global $conn;
    $allowed_statuses = ['waiting', 'preparing', 'ready', 'completed'];
    if ($order_id <= 0 || !in_array($status, $allowed_statuses, true)) {
        return false;
    }

    $conn->begin_transaction();
    try {
        $order_stmt = $conn->prepare("SELECT user_id, total FROM orders WHERE id = ? FOR UPDATE");
        $order_stmt->bind_param('i', $order_id);
        if (!$order_stmt->execute()) {
            throw new RuntimeException('Unable to retrieve the order.');
        }
        $order = $order_stmt->get_result()->fetch_assoc();
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }

        if ($reason !== '') {
            $update_stmt = $conn->prepare("UPDATE orders SET status = ?, notes = ? WHERE id = ?");
            $update_stmt->bind_param('ssi', $status, $reason, $order_id);
        } else {
            $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $update_stmt->bind_param('si', $status, $order_id);
        }
        if (!$update_stmt->execute()) {
            throw new RuntimeException('Unable to update the order.');
        }

        if ($status === 'completed') {
            $transaction_stmt = $conn->prepare("SELECT id FROM transactions WHERE order_id = ? AND type = 'sale' LIMIT 1");
            $transaction_stmt->bind_param('i', $order_id);
            if (!$transaction_stmt->execute()) {
                throw new RuntimeException('Unable to check the order transaction.');
            }
            if (!$transaction_stmt->get_result()->fetch_assoc()) {
                $type = 'sale';
                $transaction_status = 'completed';
                $receipt = '';
                $sale_stmt = $conn->prepare("INSERT INTO transactions (user_id, order_id, amount, type, status, receipt) VALUES (?, ?, ?, ?, ?, ?)");
                $sale_stmt->bind_param('iidsss', $order['user_id'], $order_id, $order['total'], $type, $transaction_status, $receipt);
                if (!$sale_stmt->execute()) {
                    throw new RuntimeException('Unable to record the completed sale.');
                }
            }
        }

        $conn->commit();
        return true;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

/**
 * Create an in-person sale using current inventory prices and stock levels.
 */
function createCashSale($user_id, $items, $payment) {
    global $conn;
    if ($user_id <= 0 || !is_array($items) || $items === [] || !is_numeric($payment)) {
        throw new InvalidArgumentException('Add products and a valid payment amount.');
    }

    $quantities = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            throw new InvalidArgumentException('Choose valid products and quantities.');
        }
        $product_id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
        if (!$product_id || !$quantity || $quantity < 1) {
            throw new InvalidArgumentException('Choose valid products and quantities.');
        }
        $quantities[$product_id] = ($quantities[$product_id] ?? 0) + $quantity;
    }

    $conn->begin_transaction();
    try {
        $product_stmt = $conn->prepare("SELECT name, price, quantity FROM products WHERE id = ? FOR UPDATE");
        $stock_stmt = $conn->prepare("UPDATE products SET quantity = quantity - ?, status = CASE WHEN quantity - ? > 0 THEN 'available' ELSE 'not available' END WHERE id = ? AND quantity >= ?");
        $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $validated_items = [];
        $total = 0.0;

        foreach ($quantities as $product_id => $quantity) {
            $product_stmt->bind_param('i', $product_id);
            if (!$product_stmt->execute()) {
                throw new RuntimeException('Unable to check product inventory.');
            }
            $product = $product_stmt->get_result()->fetch_assoc();
            if (!$product || (int) $product['quantity'] < $quantity) {
                throw new RuntimeException('One or more products are out of stock.');
            }
            $price = (float) $product['price'];
            $validated_items[] = ['id' => $product_id, 'quantity' => $quantity, 'price' => $price];
            $total += $price * $quantity;
        }

        $payment = (float) $payment;
        if (!is_finite($payment) || $payment < $total) {
            throw new InvalidArgumentException('Payment must cover the sale total.');
        }

        $change = $payment - $total;
        $status = 'completed';
        $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total, payment, change_amount, status) VALUES (?, ?, ?, ?, ?)");
        $order_stmt->bind_param('iddds', $user_id, $total, $payment, $change, $status);
        if (!$order_stmt->execute()) {
            throw new RuntimeException('Unable to save the sale.');
        }
        $order_id = $conn->insert_id;

        foreach ($validated_items as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $item_stmt->bind_param('iiid', $order_id, $product_id, $quantity, $price);
            if (!$item_stmt->execute()) {
                throw new RuntimeException('Unable to save a sale item.');
            }
            $stock_stmt->bind_param('iiii', $quantity, $quantity, $product_id, $quantity);
            if (!$stock_stmt->execute() || $stock_stmt->affected_rows !== 1) {
                throw new RuntimeException('Product inventory changed during checkout.');
            }
        }

        $type = 'sale';
        $transaction_status = 'completed';
        $receipt = '';
        $transaction_stmt = $conn->prepare("INSERT INTO transactions (user_id, order_id, amount, type, status, receipt) VALUES (?, ?, ?, ?, ?, ?)");
        $transaction_stmt->bind_param('iidsss', $user_id, $order_id, $total, $type, $transaction_status, $receipt);
        if (!$transaction_stmt->execute()) {
            throw new RuntimeException('Unable to record the sale transaction.');
        }

        $conn->commit();
        return $order_id;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

/**
 * Get order status count
 */
function getOrderStatusCount($status) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

// ============================
// TRANSACTION FUNCTIONS
// ============================

/**
 * Get all transactions
 */
function getAllTransactions() {
    global $conn;
    return $conn->query("SELECT t.*, u.fullname as user FROM transactions t 
                         JOIN users u ON t.user_id = u.id 
                         ORDER BY t.transaction_date DESC");
}

/**
 * Get user transactions
 */
function getUserTransactions($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Create transaction
 */
function createTransaction($user_id, $order_id, $amount, $type = 'sale', $status = 'completed', $receipt = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, order_id, amount, type, status, receipt) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidsss", $user_id, $order_id, $amount, $type, $status, $receipt);
    return $stmt->execute();
}

/**
 * Get transaction stats
 */
function getTransactionStats() {
    global $conn;
    $result = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN type = 'sale' AND status = 'completed' THEN amount ELSE 0 END) as total_sales
        FROM transactions");
    return $result->fetch_assoc();
}

// ============================
// REVIEW FUNCTIONS
// ============================

/**
 * Get product reviews
 */
function getProductReviews($product_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT r.*, u.fullname FROM reviews r 
                            JOIN users u ON r.user_id = u.id 
                            WHERE r.product_id = ? 
                            ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get user reviews
 */
function getUserReviews($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT r.*, p.name as product_name FROM reviews r 
                            JOIN products p ON r.product_id = p.id 
                            WHERE r.user_id = ? 
                            ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get product average rating
 */
function getProductAverageRating($product_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Add review
 */
function addReview($user_id, $product_id, $order_id, $rating, $comment) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiids", $user_id, $product_id, $order_id, $rating, $comment);
    return $stmt->execute();
}

/**
 * Check if user already reviewed an order
 */
function hasReviewedOrder($user_id, $order_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ? AND order_id = ?");
    $stmt->bind_param("ii", $user_id, $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'] > 0;
}

// ============================
// MESSAGE FUNCTIONS
// ============================

/**
 * Get user messages
 */
function getUserMessages($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT m.*, u.fullname as sender_name, u2.fullname as receiver_name 
                            FROM messages m 
                            JOIN users u ON m.sender_id = u.id 
                            JOIN users u2 ON m.receiver_id = u2.id 
                            WHERE m.sender_id = ? OR m.receiver_id = ? 
                            ORDER BY m.created_at DESC");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Send message
 */
function sendMessage($sender_id, $receiver_id, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    return $stmt->execute();
}

/**
 * Mark message as read
 */
function markMessageRead($message_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    return $stmt->execute();
}

/**
 * Get unread message count
 */
function getUnreadMessageCount($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

// ============================
// INVENTORY FUNCTIONS
// ============================

/**
 * Get inventory summary
 */
function getInventorySummary() {
    global $conn;
    return $conn->query("SELECT 
        COUNT(*) as total_products,
        SUM(quantity) as total_quantity,
        SUM(price * quantity) as total_value,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_products,
        SUM(CASE WHEN status = 'not available' THEN 1 ELSE 0 END) as unavailable_products
        FROM products")->fetch_assoc();
}

/**
 * Get inventory by month
 */
function getMonthlyInventory($year = null) {
    global $conn;
    $year = $year ?? date('Y');
    $data = [];
    for ($i = 1; $i <= 12; $i++) {
        $month = date('Y-m', strtotime("$year-$i-01"));
        $result = $conn->query("SELECT SUM(quantity) as total FROM products WHERE MONTH(date_added) = $i AND YEAR(date_added) = $year");
        $data[$i] = $result->fetch_assoc()['total'] ?? 0;
    }
    return $data;
}

/**
 * Get low stock products
 */
function getLowStockProducts($threshold = 10) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM products WHERE quantity <= ? AND quantity > 0 ORDER BY quantity ASC");
    $stmt->bind_param("i", $threshold);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================
// DASHBOARD FUNCTIONS
// ============================

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    global $conn;
    
    $stats = [];
    
    // Products count
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $stats['products'] = $result->fetch_assoc()['count'];
    
    // Users count
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    $stats['customers'] = $result->fetch_assoc()['count'];
    
    // Orders count by status
    $result = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $stats['orders'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['orders'][$row['status']] = $row['count'];
    }
    
    // Average rating
    $result = $conn->query("SELECT AVG(rating) as avg FROM reviews");
    $stats['avg_rating'] = number_format($result->fetch_assoc()['avg'] ?? 0, 1);
    
    // Transaction efficiency
    $result = $conn->query("SELECT 
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
        COUNT(*) as total 
        FROM transactions");
    $row = $result->fetch_assoc();
    $stats['efficiency'] = ($row['total'] > 0) ? round(($row['successful'] / $row['total']) * 100) : 0;
    $stats['total_transactions'] = $row['total'] ?? 0;
    
    // Total sales
    $result = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE type = 'sale' AND status = 'completed'");
    $stats['total_sales'] = $result->fetch_assoc()['total'] ?? 0;
    
    return $stats;
}

/**
 * Get recent orders
 */
function getRecentOrders($limit = 5) {
    global $conn;
    return $conn->query("SELECT o.*, u.fullname as customer FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         ORDER BY o.order_date DESC LIMIT $limit");
}

/**
 * Get recent transactions
 */
function getRecentTransactions($limit = 5) {
    global $conn;
    return $conn->query("SELECT t.*, u.fullname as user FROM transactions t 
                         JOIN users u ON t.user_id = u.id 
                         ORDER BY t.transaction_date DESC LIMIT $limit");
}

// ============================
// UTILITY FUNCTIONS
// ============================

/**
 * Generate receipt
 */
function generateReceipt($order_id) {
    $order = getOrderById($order_id);
    if (!$order) return null;
    
    $items = getOrderItems($order_id);
    $receipt = "═══════════════════════════════\n";
    $receipt .= "         BAKE TRACK        \n";
    $receipt .= "        Order Receipt        \n";
    $receipt .= "═══════════════════════════════\n";
    $receipt .= "Order #: " . str_pad($order_id, 6, '0', STR_PAD_LEFT) . "\n";
    $receipt .= "Date: " . date('Y-m-d h:i A', strtotime($order['order_date'])) . "\n";
    $receipt .= "─────────────────────────────\n";
    
    while ($item = $items->fetch_assoc()) {
        $name = substr($item['name'], 0, 20);
        $qty = $item['quantity'];
        $price = number_format($item['price'], 2);
        $total = number_format($item['price'] * $item['quantity'], 2);
        $receipt .= sprintf("%-20s x%3d  %s\n", $name, $qty, $total);
    }
    
    $receipt .= "─────────────────────────────\n";
    $receipt .= sprintf("%-20s %s\n", "Total:", "₱" . number_format($order['total'], 2));
    $receipt .= sprintf("%-20s %s\n", "Payment:", "₱" . number_format($order['payment'], 2));
    $receipt .= sprintf("%-20s %s\n", "Change:", "₱" . number_format($order['change_amount'], 2));
    $receipt .= "═══════════════════════════════\n";
    $receipt .= "  Thank you for your order!  \n";
    $receipt .= "        BakeTrack        \n";
    
    return $receipt;
}

/**
 * Upload image
 */
function uploadImage($file, $target_dir = 'uploads/products/') {
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($imageFileType, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $imageFileType;
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

/**
 * Format price
 */
function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    if ($diff < 31536000) return floor($diff / 2592000) . 'mo ago';
    return floor($diff / 31536000) . 'y ago';
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Return JSON response
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message
 */
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $class = $flash['type'] === 'success' ? 'alert-success' : 'alert-error';
        echo "<div class='alert $class'>{$flash['message']}</div>";
    }
}

/**
 * Get page title
 */
function getPageTitle($page) {
    $titles = [
        'dashboard' => 'Dashboard',
        'bakery' => 'Bakery',
        'uploads' => 'Product Uploads',
        'orders' => 'Orders',
        'transactions' => 'Transactions',
        'messages' => 'Messages',
        'inventory' => 'Inventory',
        'database' => 'Database',
        'settings' => 'Settings',
        'report' => 'Direct Sells Report',
        'reviews' => 'Reviews'
    ];
    return $titles[$page] ?? ucfirst($page);
}

// ============================
// DATABASE FUNCTIONS
// ============================

/**
 * Get table count
 */
function getTableCount($table) {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    return $result->fetch_assoc()['count'];
}

/**
 * Get all users with role filter
 */
function getUsersByRole($role = null) {
    global $conn;
    if ($role) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        return $stmt->get_result();
    }
    return $conn->query("SELECT * FROM users ORDER BY created_at DESC");
}

/**
 * Check if user exists
 */
function userExists($username, $email = null) {
    global $conn;
    if ($email) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
    }
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Create user
 */
function createUser($username, $email, $password, $fullname, $role = 'customer') {
    global $conn;
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, fullname, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $hashed, $fullname, $role);
    return $stmt->execute();
}

/**
 * Update user
 */
function updateUser($id, $data) {
    global $conn;
    $set = [];
    $types = '';
    $values = [];
    
    foreach ($data as $key => $value) {
        $set[] = "$key = ?";
        $types .= 's';
        $values[] = $value;
    }
    
    if (empty($set)) return false;
    
    $sql = "UPDATE users SET " . implode(', ', $set) . " WHERE id = ?";
    $types .= 'i';
    $values[] = $id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    return $stmt->execute();
}

/**
 * Change user password
 */
function changeUserPassword($id, $new_password) {
    global $conn;
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $id);
    return $stmt->execute();
}

/**
 * Verify user password
 */
function verifyUserPassword($user_id, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result && password_verify($password, $result['password']);
}

// ============================
// SETTINGS FUNCTIONS
// ============================

/**
 * Get user settings
 */
function getUserSettings($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Create default settings
    $stmt = $conn->prepare("INSERT INTO settings (user_id, darkmode) VALUES (?, 0)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    return ['user_id' => $user_id, 'darkmode' => 0];
}

/**
 * Update user settings
 */
function updateUserSettings($user_id, $darkmode) {
    global $conn;
    $stmt = $conn->prepare("UPDATE settings SET darkmode = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $darkmode, $user_id);
    return $stmt->execute();
}

// ============================
// VALIDATION FUNCTIONS
// ============================

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function isValidPassword($password) {
    return strlen($password) >= 6;
}

/**
 * Validate phone number
 */
function isValidPhone($phone) {
    return preg_match('/^[0-9]{10,11}$/', $phone);
}

/**
 * Validate date
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>