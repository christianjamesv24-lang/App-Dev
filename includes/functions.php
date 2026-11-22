<?php
/**
 * BAKE TRACK - Functions Library
 * Reusable functions for the entire application
 */

require_once dirname(__DIR__) . '/config.php';

function getApplicationEnvironment($name, $default = '') {
    if (isset($_ENV[$name]) && is_string($_ENV[$name])) {
        return $_ENV[$name];
    }
    if (isset($_SERVER[$name]) && is_string($_SERVER[$name])) {
        return $_SERVER[$name];
    }

    $value = getenv($name);
    return $value === false ? $default : (string) $value;
}

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
function ensureOrderCustomerNameColumn() {
    global $conn;
    static $checked = false;
    if ($checked) {
        return;
    }

    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'customer_name'");
    if (!$result) {
        throw new RuntimeException('Unable to check the order customer field.');
    }
    if ($result->num_rows === 0 && !$conn->query("ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) DEFAULT NULL AFTER user_id")) {
        throw new RuntimeException('Unable to add the order customer field.');
    }
    $checked = true;
}

function createCashSale($user_id, $items, $payment, $customerName = '') {
    global $conn;
    if ($user_id <= 0 || !is_array($items) || $items === [] || !is_numeric($payment)) {
        throw new InvalidArgumentException('Add products and a valid payment amount.');
    }
    if (!is_string($customerName)) {
        throw new InvalidArgumentException('Enter a valid customer name.');
    }
    $customerName = trim($customerName);
    if ($customerName === '') {
        $customerName = 'Walk-in customer';
    }
    if (mb_strlen($customerName, 'UTF-8') > 255) {
        throw new InvalidArgumentException('Customer name must be 255 characters or fewer.');
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

    ensureOrderCustomerNameColumn();
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
        $order_stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, total, payment, change_amount, status) VALUES (?, ?, ?, ?, ?, ?)");
        $order_stmt->bind_param('isddds', $user_id, $customerName, $total, $payment, $change, $status);
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

function isVercelDeployment() {
    return getApplicationEnvironment('VERCEL', 'false') === '1'
        || getApplicationEnvironment('VERCEL_ENV', '') !== '';
}

function isVercelBlobUrl($url) {
    if (!is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    $parts = parse_url($url);
    return isset($parts['scheme'], $parts['host'])
        && strtolower($parts['scheme']) === 'https'
        && str_ends_with(strtolower($parts['host']), '.blob.vercel-storage.com');
}

function runVercelBlobCommand($operation, $pathname, $contentType = '', $sourcePath = '') {
    if (!function_exists('proc_open')) {
        throw new RuntimeException('Process execution is unavailable for Vercel Blob storage.');
    }

    $scriptPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'vercel-blob.mjs';
    $command = [
        getApplicationEnvironment('NODE_BINARY', 'node'),
        $scriptPath,
        $operation,
        $pathname,
        $contentType,
        $sourcePath
    ];
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ], $pipes, dirname(__DIR__));

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the Vercel Blob operation.');
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $errorOutput = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        error_log('Vercel Blob operation failed with exit code ' . $exitCode . '.');
        throw new RuntimeException('The Vercel Blob operation failed.');
    }

    $result = json_decode($output, true);
    if (!is_array($result)) {
        error_log('Vercel Blob operation returned an invalid response.');
        throw new RuntimeException('The Vercel Blob operation returned an invalid response.');
    }

    return $result;
}

function saveProductImage($temporaryPath, $extension, $contentType) {
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $blobToken = getApplicationEnvironment('BLOB_READ_WRITE_TOKEN', '');
    $blobStoreId = getApplicationEnvironment('BLOB_STORE_ID', '');
    $oidcToken = getApplicationEnvironment('VERCEL_OIDC_TOKEN', '');
    $hasBlobCredentials = $blobToken !== '' || ($blobStoreId !== '' && $oidcToken !== '');

    if (isVercelDeployment()) {
        if (!$hasBlobCredentials) {
            throw new RuntimeException('Connect a Vercel Blob store to this project before uploading images.');
        }

        $result = runVercelBlobCommand('put', 'products/' . $filename, $contentType, $temporaryPath);
        if (!isset($result['url']) || !isVercelBlobUrl($result['url'])) {
            throw new RuntimeException('Vercel Blob returned an invalid image URL.');
        }

        return $result['url'];
    }

    $targetDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
        throw new RuntimeException('Unable to create the product image directory.');
    }
    if (!move_uploaded_file($temporaryPath, $targetDirectory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('Unable to save the uploaded product image.');
    }

    return $filename;
}

function removeProductImage($image) {
    if (!is_string($image) || $image === '' || $image === 'default.svg') {
        return true;
    }

    if (isVercelBlobUrl($image)) {
        $result = runVercelBlobCommand('delete', $image);
        return !empty($result['deleted']);
    }

    $filename = basename($image);
    $imagePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . $filename;
    return !is_file($imagePath) || unlink($imagePath);
}

function getProductImageUrl($image) {
    if (!is_string($image) || trim($image) === '' || $image === 'default.svg') {
        return 'uploads/products/default.svg';
    }
    if (isVercelBlobUrl($image)) {
        return $image;
    }

    return 'uploads/products/' . rawurlencode(basename($image));
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
 * Ensure password reset storage exists for installations not yet re-imported from SQL.
 */
function ensurePasswordResetTable() {
    global $conn;

    $created = $conn->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        token_hash CHAR(64) NOT NULL PRIMARY KEY,
        user_id INT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        KEY user_id (user_id),
        KEY expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");

    if (!$created) {
        throw new RuntimeException('Unable to prepare password reset storage.');
    }
}

/**
 * Send a one-hour password reset link without revealing whether an email exists.
 */
function sendPasswordResetEmail($email) {
    global $conn;

    ensurePasswordResetTable();
    $conn->query('DELETE FROM password_reset_tokens WHERE expires_at <= NOW()');
    $vercelUrl = trim(getApplicationEnvironment('VERCEL_URL'));
    $defaultAppUrl = $vercelUrl !== ''
        ? 'https://' . $vercelUrl
        : getApplicationEnvironment('RENDER_EXTERNAL_URL');
    $appUrl = rtrim(getApplicationEnvironment('APP_URL', $defaultAppUrl), '/');
    $mailProvider = strtolower(trim(getApplicationEnvironment('MAIL_PROVIDER', 'smtp')));
    $mailFrom = trim(getApplicationEnvironment('MAIL_FROM'));
    $appUrlParts = filter_var($appUrl, FILTER_VALIDATE_URL) ? parse_url($appUrl) : false;
    if (
        !$appUrlParts
        || !isset($appUrlParts['scheme'], $appUrlParts['host'])
        || !in_array(strtolower($appUrlParts['scheme']), ['http', 'https'], true)
        || array_intersect(['user', 'pass', 'query', 'fragment'], array_keys($appUrlParts)) !== []
        || !filter_var($mailFrom, FILTER_VALIDATE_EMAIL)
    ) {
        throw new RuntimeException('Password recovery email settings are missing or invalid.');
    }

    $smtpUsername = '';
    $smtpPassword = '';
    $smtpHost = '';
    $smtpPort = false;
    $smtpSecure = '';
    $smtpAuth = true;
    $brevoApiKey = '';
    if ($mailProvider === 'brevo') {
        $brevoApiKey = trim(getApplicationEnvironment('BREVO_API_KEY'));
        if ($brevoApiKey === '' || !function_exists('curl_init')) {
            throw new RuntimeException('Brevo email settings are missing or the PHP cURL extension is unavailable.');
        }
    } elseif ($mailProvider === 'smtp') {
        $smtpUsername = trim(getApplicationEnvironment('SMTP_USERNAME'));
        $smtpPassword = getApplicationEnvironment('SMTP_PASSWORD');
        $smtpHost = trim(getApplicationEnvironment('SMTP_HOST', 'smtp.gmail.com'));
        $smtpPort = filter_var(getApplicationEnvironment('SMTP_PORT', '587'), FILTER_VALIDATE_INT);
        $smtpSecure = strtolower(trim(getApplicationEnvironment('SMTP_SECURE', 'tls')));
        $smtpAuthSetting = getApplicationEnvironment('SMTP_AUTH', 'true');
        $smtpAuth = $smtpAuthSetting === false
            ? true
            : filter_var($smtpAuthSetting, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $localSmtpHost = in_array(strtolower($smtpHost), ['localhost', '127.0.0.1', '::1'], true);
        if (
            $smtpHost === ''
            || $smtpPort === false
            || $smtpPort < 1
            || $smtpPort > 65535
            || !in_array($smtpSecure, ['tls', 'ssl', 'none'], true)
            || $smtpAuth === null
            || ($smtpAuth && (!filter_var($smtpUsername, FILTER_VALIDATE_EMAIL) || $smtpPassword === ''))
            || ($smtpSecure === 'none' && (!$localSmtpHost || $smtpAuth))
        ) {
            throw new RuntimeException('Password recovery email settings are missing or invalid.');
        }

        $autoloadPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!is_file($autoloadPath)) {
            throw new RuntimeException('Composer dependencies are missing; run composer install.');
        }
        require_once $autoloadPath;
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            throw new RuntimeException('PHPMailer is not installed; run composer install.');
        }
    } else {
        throw new RuntimeException('MAIL_PROVIDER must be set to smtp or brevo.');
    }

    $stmt = $conn->prepare('SELECT id, fullname, email FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        return;
    }

    $recent = $conn->prepare(
        'SELECT token_hash FROM password_reset_tokens
         WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
         LIMIT 1'
    );
    $recent->bind_param('i', $user['id']);
    $recent->execute();
    if ($recent->get_result()->num_rows > 0) {
        return;
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $delete = $conn->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
    $delete->bind_param('i', $user['id']);
    $delete->execute();

    $insert = $conn->prepare(
        'INSERT INTO password_reset_tokens (token_hash, user_id, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
    );
    $insert->bind_param('si', $tokenHash, $user['id']);
    $insert->execute();

    $resetUrl = $appUrl . '/auth.php?mode=reset&token=' . rawurlencode($token);
    $message = "Hello {$user['fullname']},\n\n"
        . "We received a request to reset your BakeTrack password. Use this link within one hour:\n\n"
        . $resetUrl . "\n\n"
        . "If you did not request this, you can ignore this email.";

    $mailer = null;
    try {
        if ($mailProvider === 'brevo') {
            $payload = json_encode([
                'sender' => ['name' => 'BakeTrack', 'email' => $mailFrom],
                'to' => [['email' => $user['email'], 'name' => $user['fullname']]],
                'subject' => 'BakeTrack password reset',
                'textContent' => $message
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $request = curl_init('https://api.brevo.com/v3/smtp/email');
            if ($request === false) {
                throw new RuntimeException('Unable to initialize the Brevo email request.');
            }
            if (!curl_setopt_array($request, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'accept: application/json',
                    'api-key: ' . $brevoApiKey,
                    'content-type: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS
            ])) {
                curl_close($request);
                throw new RuntimeException('Unable to configure the Brevo email request.');
            }
            $response = curl_exec($request);
            $curlError = curl_error($request);
            $statusCode = curl_getinfo($request, CURLINFO_RESPONSE_CODE);
            curl_close($request);
            if ($response === false) {
                throw new RuntimeException('Brevo email API request failed: ' . $curlError);
            }
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new RuntimeException('Brevo email API returned HTTP ' . $statusCode . '.');
            }
        } else {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $smtpHost;
            $mailer->SMTPAuth = $smtpAuth;
            if ($smtpAuth) {
                $mailer->Username = $smtpUsername;
                $mailer->Password = $smtpPassword;
            }
            $mailer->SMTPSecure = match ($smtpSecure) {
                'tls' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
                'ssl' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
                default => ''
            };
            $mailer->Port = $smtpPort;
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom($mailFrom, 'BakeTrack');
            $mailer->addAddress($user['email'], $user['fullname']);
            $mailer->Subject = 'BakeTrack password reset';
            $mailer->Body = $message;
            $mailer->send();
        }
    } catch (Throwable $mailError) {
        $remove = $conn->prepare('DELETE FROM password_reset_tokens WHERE token_hash = ?');
        $remove->bind_param('s', $tokenHash);
        $remove->execute();
        $mailerError = $mailer instanceof \PHPMailer\PHPMailer\PHPMailer ? $mailer->ErrorInfo : '';
        $diagnostic = $mailerError !== '' ? $mailerError : $mailError->getMessage();
        $secretsToRedact = array_filter(
            [$brevoApiKey, $smtpUsername, $smtpPassword, $user['email']],
            static fn($value) => $value !== ''
        );
        $diagnostic = str_replace($secretsToRedact, '[redacted]', $diagnostic);
        error_log(ucfirst($mailProvider) . ' password reset email failed: ' . $diagnostic);
        throw new RuntimeException(
            'The password reset email could not be sent through ' . ucfirst($mailProvider) . '.',
            0,
            $mailError
        );
    }
}

/**
 * Check whether a raw reset token is still active.
 */
function isPasswordResetTokenValid($token) {
    global $conn;

    if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        return false;
    }

    ensurePasswordResetTable();
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare(
        'SELECT 1 FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    return $stmt->get_result()->num_rows === 1;
}

/**
 * Replace a password once and invalidate every reset token for that account.
 */
function resetPasswordWithToken($token, $password) {
    global $conn;

    if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        return false;
    }

    ensurePasswordResetTable();
    $tokenHash = hash('sha256', $token);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'SELECT user_id FROM password_reset_tokens
             WHERE token_hash = ? AND expires_at > NOW() FOR UPDATE'
        );
        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();
        $reset = $stmt->get_result()->fetch_assoc();
        if (!$reset) {
            $conn->rollback();
            return false;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->bind_param('si', $passwordHash, $reset['user_id']);
        if (!$update->execute() || $update->affected_rows !== 1) {
            throw new RuntimeException('The password could not be updated for the reset account.');
        }

        $delete = $conn->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
        $delete->bind_param('i', $reset['user_id']);
        $delete->execute();
        $conn->commit();
        return true;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
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
// PUBLIC SITE CONTENT
// ============================

/**
 * Get editable overview and about page text, falling back to the original copy.
 */
function getSiteContent($ensureTable = false) {
    global $conn;
    static $tableReady = false;

    $content = [
        'home_title' => 'Welcome to BakeTrack',
        'home_subtitle' => 'Where every bite tells a story of love and tradition',
        'home_cta_label' => 'Get Started',
        'home_feature_1_title' => 'Fresh Baked',
        'home_feature_1_description' => 'Daily fresh bread and pastries',
        'home_feature_2_title' => 'Made with Love',
        'home_feature_2_description' => 'Traditional recipes with a modern twist',
        'home_feature_3_title' => 'Visit Us',
        'home_feature_3_description' => 'Experience the warmth of our bakery',
        'about_story_title' => 'Our Story',
        'about_story_paragraph_1' => 'Since 2020, BakeTrack has been serving the community with love and passion for baking.',
        'about_story_paragraph_2' => 'Every product is made with carefully selected ingredients and traditional techniques.',
        'about_home_button' => 'Home',
        'about_join_button' => 'Join Us',
        'about_value_1_title' => 'Quality Ingredients',
        'about_value_1_description' => 'We use only the finest ingredients',
        'about_value_2_title' => 'Community Love',
        'about_value_2_description' => 'Supporting local farmers and suppliers',
        'about_value_3_title' => 'Excellence',
        'about_value_3_description' => 'Recognized for quality and service',
        'footer_text' => 'Your favorite bakery shop'
    ];

    if (!$tableReady && $ensureTable) {
        $conn->query("CREATE TABLE IF NOT EXISTS site_content (
            content_key VARCHAR(100) NOT NULL PRIMARY KEY,
            content_value TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        $tableReady = true;
    }

    if (!$tableReady) {
        $tableCheck = $conn->query(
            "SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = 'site_content'"
        );
        if ($tableCheck->num_rows === 0) {
            return $content;
        }
        $tableReady = true;
    }

    $result = $conn->query('SELECT content_key, content_value FROM site_content');
    while ($row = $result->fetch_assoc()) {
        if (array_key_exists($row['content_key'], $content)) {
            $content[$row['content_key']] = $row['content_value'];
        }
    }

    return $content;
}

/**
 * Save only known public page fields and reject oversized or malformed values.
 */
function saveSiteContent($submittedContent) {
    global $conn;

    if (!is_array($submittedContent)) {
        throw new InvalidArgumentException('Site content must be submitted as a field list.');
    }

    $defaults = getSiteContent(true);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'INSERT INTO site_content (content_key, content_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE content_value = VALUES(content_value)'
        );
        foreach (array_keys($defaults) as $key) {
            if (!array_key_exists($key, $submittedContent) || !is_string($submittedContent[$key])) {
                throw new InvalidArgumentException('A site content field is missing or invalid.');
            }
            if (mb_strlen($submittedContent[$key], 'UTF-8') > 2000) {
                throw new InvalidArgumentException('Site content fields cannot exceed 2000 characters.');
            }

            $value = $submittedContent[$key];
            $stmt->bind_param('ss', $key, $value);
            $stmt->execute();
        }
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
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