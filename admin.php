<?php
require_once 'includes/functions.php';
requireValidCsrfToken();

requireAdmin();

$user_id = $_SESSION['user_id'];
$allowed_pages = ['dashboard', 'bakery', 'uploads', 'orders', 'transactions', 'messages', 'inventory', 'database', 'settings', 'content'];
$requested_page = $_GET['page'] ?? 'dashboard';
if (!is_string($requested_page) || !in_array($requested_page, $allowed_pages, true)) {
    header('Location: admin.php?page=dashboard');
    exit();
}
$page = $requested_page;
$orderPages = [];
foreach (['waiting', 'preparing', 'ready'] as $orderStatus) {
    $orderPage = filter_var($_GET[$orderStatus . '_page'] ?? 1, FILTER_VALIDATE_INT);
    $orderPages[$orderStatus] = $orderPage && $orderPage > 0 ? $orderPage : 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_site_content'])) {
    try {
        saveSiteContent($_POST['site_content'] ?? null);
        header('Location: admin.php?page=content&saved=1');
    } catch (Throwable $error) {
        error_log('Admin site content update failed: ' . $error->getMessage());
        header('Location: admin.php?page=content&error=content');
    }
    exit();
}

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
if ($page === 'messages') {
    $read_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
    $read_stmt->bind_param('i', $user_id);
    $read_stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'])) {
    $target_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $action = is_string($_POST['user_action']) ? $_POST['user_action'] : '';
    $updated = false;

    if ($action === 'create_assistant') {
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (
            preg_match('/\A[a-zA-Z0-9._-]{3,100}\z/', $username)
            && $fullname !== ''
            && filter_var($email, FILTER_VALIDATE_EMAIL)
            && isValidPassword($password)
            && !userExists($username, $email)
        ) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $assistant_stmt = $conn->prepare(
                "INSERT INTO users (username, password, email, fullname, role, is_active)
                 VALUES (?, ?, ?, ?, 'assistant', 1)"
            );
            $assistant_stmt->bind_param('ssss', $username, $password_hash, $email, $fullname);
            $updated = $assistant_stmt->execute();
        }
    } elseif ($target_id && $action === 'update_user') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        if ($fullname !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)
            && in_array($role, ['admin', 'assistant', 'customer'], true)
            && ($target_id !== (int) $user_id || $role === 'admin')) {
            $email_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND is_active = 1");
            $email_stmt->bind_param('si', $email, $target_id);
            $email_stmt->execute();
            if ($email_stmt->get_result()->num_rows === 0) {
                $user_stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, role = ? WHERE id = ? AND is_active = 1");
                $user_stmt->bind_param('sssi', $fullname, $email, $role, $target_id);
                $updated = $user_stmt->execute();
            }
        }
    } elseif ($target_id && $action === 'change_password') {
        $password = $_POST['password'] ?? '';
        if (isValidPassword($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $password_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND is_active = 1");
            $password_stmt->bind_param('si', $hashed_password, $target_id);
            $updated = $password_stmt->execute();
        }
    } elseif ($target_id && $action === 'delete') {
        $updated = anonymizeUserAccount((int) $target_id, (int) $user_id);
    }

    if (!$updated) {
        error_log('Admin user action failed: ' . $conn->error);
    }
    $resultFlag = $updated ? ($action === 'create_assistant' ? 'created=1' : 'updated=1') : 'error=user';
    header('Location: admin.php?page=database&' . $resultFlag);
    exit();
}

// Handle Product Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Edit Product
    if (isset($_POST['save_product'])) {
        $product_id = trim($_POST['product_id'] ?? '');
        $id = $product_id === '' ? 0 : filter_var($product_id, FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        $description = trim($_POST['description'] ?? '');
        if ($id === false || $id < 0 || ($product_id !== '' && $id === 0)
            || $name === '' || $price === false || $price < 0 || $quantity === false || $quantity < 0) {
            header('Location: admin.php?page=uploads&error=product');
            exit();
        }

        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            $file_type = $_FILES['image']['error'] === UPLOAD_ERR_OK
                ? (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name'])
                : false;
            if (!$file_type || !isset($allowed_types[$file_type]) || $_FILES['image']['size'] > 4 * 1024 * 1024) {
                header('Location: admin.php?page=uploads&error=image');
                exit();
            }
            try {
                $image = saveProductImage(
                    $_FILES['image']['tmp_name'],
                    $allowed_types[$file_type],
                    $file_type
                );
            } catch (RuntimeException $exception) {
                error_log('Product image upload failed: ' . $exception->getMessage());
                header('Location: admin.php?page=uploads&error=storage');
                exit();
            }
        }

        $current_product = $id > 0 ? getProductById($id) : null;
        $saved = $id > 0
            ? updateProduct($id, $name, $price, $quantity, $description, $image)
            : addProduct($name, $price, $quantity, $description, $image ?? 'default.svg');
        if (!$saved) {
            if ($image !== null) {
                try {
                    if (!removeProductImage($image)) {
                        error_log('Unable to remove unused product image.');
                    }
                } catch (RuntimeException $exception) {
                    error_log('Unable to remove unused product image: ' . $exception->getMessage());
                }
            }
            error_log('Product save failed: ' . $conn->error);
            header('Location: admin.php?page=uploads&error=product');
        } else {
            if ($image !== null && $current_product && $current_product['image'] !== $image) {
                try {
                    if (!removeProductImage($current_product['image'])) {
                        error_log('Unable to remove the replaced product image.');
                    }
                } catch (RuntimeException $exception) {
                    error_log('Unable to remove the replaced product image: ' . $exception->getMessage());
                }
            }
            header('Location: admin.php?page=uploads&saved=1');
        }
        exit();
    }
    
    // Delete Product
    if (isset($_POST['delete_product'])) {
        $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        $product = $id ? getProductById($id) : null;
        if (!$id || !$product || !deleteProduct($id)) {
            error_log('Product delete failed: ' . $conn->error);
            header('Location: admin.php?page=uploads&error=delete');
        } else {
            try {
                if (!removeProductImage($product['image'])) {
                    error_log('Unable to remove the deleted product image.');
                }
            } catch (RuntimeException $exception) {
                error_log('Unable to remove the deleted product image: ' . $exception->getMessage());
            }
            header('Location: admin.php?page=uploads&deleted=1');
        }
        exit();
    }
}

// Handle Order Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $status = isset($_POST['status']) && is_string($_POST['status']) ? $_POST['status'] : '';
    $reason = isset($_POST['reason']) && is_string($_POST['reason']) ? trim($_POST['reason']) : '';
    
    if ($order_id && in_array($status, ['waiting', 'preparing', 'ready', 'completed'], true)) {
        try {
            transitionOrder($order_id, $status, $reason, getCurrentUserRole(), (int) $user_id);
            header('Location: admin.php?page=orders&updated=1');
        } catch (Throwable $error) {
            error_log('Admin order update failed: ' . $error->getMessage());
            header('Location: admin.php?page=orders&error=update');
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = filter_var($_POST['receiver_id'] ?? null, FILTER_VALIDATE_INT);
    $message = trim($_POST['message'] ?? '');
    if ($receiver_id && $receiver_id !== (int) $user_id && $message !== '') {
        $message_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $message_stmt->bind_param('iis', $user_id, $receiver_id, $message);
        if ($message_stmt->execute()) {
            header('Location: admin.php?page=messages&sent=1');
            exit();
        }
    }
    header('Location: admin.php?page=messages&error=message');
    exit();
}

// Dashboard stats
$stats = [
    'products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role='customer' AND is_active = 1")->fetch_assoc()['count'],
    'reviews' => $conn->query("SELECT AVG(rating) as avg FROM reviews")->fetch_assoc()['avg'] ?? 0
];

// Get inventory data for chart
$monthly_data = [];
$report_year = (int) date('Y');
for ($i = 1; $i <= 12; $i++) {
    $result = $conn->query("SELECT SUM(quantity) as total FROM products WHERE MONTH(date_added)=$i AND YEAR(date_added)=$report_year");
    $monthly_data[$i] = $result->fetch_assoc()['total'] ?? 0;
}
$siteContent = $page === 'content' ? getSiteContent(true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BakeTrack - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="<?= !empty($user['darkmode']) ? 'dark-mode' : '' ?>" data-live-orders="<?= $page === 'orders' ? 'true' : 'false' ?>" data-csrf-token="<?= htmlspecialchars(csrfToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="dashboard-sidebar">
            <div class="sidebar-brand">
                <h2>BakeTrack</h2>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-nav" aria-label="Admin navigation">
                <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>" <?= $page === 'dashboard' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="?page=bakery" class="<?= $page === 'bakery' ? 'active' : '' ?>" <?= $page === 'bakery' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-store"></i> Bakery
                </a>
                <a href="?page=uploads" class="<?= $page === 'uploads' ? 'active' : '' ?>" <?= $page === 'uploads' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-upload"></i> Uploads
                </a>
                <a href="?page=orders" class="<?= $page === 'orders' ? 'active' : '' ?>" <?= $page === 'orders' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-clipboard-list"></i> Orders
                </a>
                <a href="?page=transactions" class="<?= $page === 'transactions' ? 'active' : '' ?>" <?= $page === 'transactions' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-credit-card"></i> Transactions
                </a>
                <a href="?page=messages" class="<?= $page === 'messages' ? 'active' : '' ?>" <?= $page === 'messages' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="?page=inventory" class="<?= $page === 'inventory' ? 'active' : '' ?>" <?= $page === 'inventory' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-boxes"></i> Inventory
                </a>
                <a href="?page=database" class="<?= $page === 'database' ? 'active' : '' ?>" <?= $page === 'database' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-database"></i> Database
                </a>
                <a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>" <?= $page === 'settings' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="?page=content" class="<?= $page === 'content' ? 'active' : '' ?>" <?= $page === 'content' ? 'aria-current="page"' : '' ?>>
                    <i class="fas fa-edit"></i> Site Content
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
            <!-- ==================== DASHBOARD ==================== -->
            <?php if ($page === 'dashboard'): ?>
            <div class="page-header dashboard-summary-header">
                <h1>Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($user['fullname']) ?>!</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card" style="border-left: 4px solid #8B6914;">
                    <i class="fas fa-bread-slice"></i>
                    <h3><?= $stats['products'] ?></h3>
                    <p>Products</p>
                </div>
                <div class="stat-card" style="border-left: 4px solid #D4A574;">
                    <i class="fas fa-users"></i>
                    <h3><?= $stats['users'] ?></h3>
                    <p>Customers</p>
                </div>
                <div class="stat-card" style="border-left: 4px solid #F39C12;">
                    <i class="fas fa-star"></i>
                    <h3><?= number_format($stats['reviews'], 1) ?>★</h3>
                    <p>Reviews</p>
                </div>
            </div>

            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="?page=bakery" class="action-btn">
                        <i class="fas fa-store"></i> Browse Bakery
                    </a>
                    <a href="?page=uploads" class="action-btn">
                        <i class="fas fa-edit"></i> Manage Products
                    </a>
                    <a href="?page=orders" class="action-btn">
                        <i class="fas fa-clipboard-list"></i> Review Orders
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== BAKERY ==================== -->
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

            <!-- ==================== UPLOADS ==================== -->
            <?php if ($page === 'uploads'): ?>
            <div class="page-header">
                <h1>Product Uploads</h1>
                <button class="btn btn-primary" onclick="openProductModal()">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>
            <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success" role="status">Product saved.</div>
            <?php elseif (isset($_GET['deleted'])): ?>
            <div class="alert alert-success" role="status">Product deleted.</div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'image'): ?>
            <div class="alert alert-error" role="alert">Choose a valid JPG, PNG, GIF, or WebP image no larger than 4 MB.</div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'storage'): ?>
            <div class="alert alert-error" role="alert">Image storage is unavailable. Check the deployment's Blob configuration and try again.</div>
            <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-error" role="alert">Unable to save that product.</div>
            <?php endif; ?>
            <div class="uploads-grid">
                <?php 
                $products = $conn->query("SELECT * FROM products ORDER BY date_added DESC");
                while ($product = $products->fetch_assoc()): 
                ?>
                <div class="upload-card">
                    <img src="<?= htmlspecialchars(getProductImageUrl($product['image']), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='uploads/products/default.svg'">
                    <div class="upload-info">
                        <h4><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <p>₱<?= number_format($product['price'], 2) ?></p>
                        <p>Qty: <?= $product['quantity'] ?></p>
                        <span class="status <?= $product['status'] === 'available' ? 'available' : 'unavailable' ?>">
                            <?= ucfirst($product['status']) ?>
                        </span>
                        <div class="upload-actions">
                            <button class="btn-edit" onclick='editProduct(<?= (int) $product['id'] ?>, <?= htmlspecialchars(json_encode($product['name'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>, <?= json_encode((float) $product['price']) ?>, <?= (int) $product['quantity'] ?>, <?= htmlspecialchars(json_encode($product['description'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete" onclick="deleteProduct(<?= $product['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Modal -->
            <div id="productModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeProductModal()">&times;</span>
                    <h2 id="modalTitle">Add Product</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="product_id" id="productId">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" id="prodName" required>
                        </div>
                        <div class="form-group">
                            <label>Price (₱)</label>
                            <input type="number" name="price" id="prodPrice" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" id="prodQty" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" id="prodDesc" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="image" accept="image/*">
                        </div>
                        <button type="submit" name="save_product" class="btn btn-primary">Save Product</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== ORDERS ==================== -->
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
                                <?php if ($order['notes']): ?>
                                <p><strong>Notes:</strong> <?= htmlspecialchars($order['notes']) ?></p>
                                <?php endif; ?>
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
                                    <button class="btn btn-danger" onclick="unprepareOrder(<?= $order['id'] ?>)">
                                        <i class="fas fa-undo"></i> Unprepare
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($status === 'ready'): ?>
                                    <button class="btn btn-success" onclick="updateOrder(<?= $order['id'] ?>, 'completed')">
                                        <i class="fas fa-check"></i> Mark Completed
                                    </button>
                                    <button class="btn btn-primary" onclick="viewAdminReceipt(<?= $order['id'] ?>)">
                                        <i class="fas fa-receipt"></i> View Receipt
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
            <?php if (isset($_GET['view_receipt'])):
                $receipt_id = (int) $_GET['view_receipt'];
                $receipt_order = $conn->query("SELECT o.*, u.fullname as customer FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $receipt_id")->fetch_assoc();
                if ($receipt_order):
                    $receipt_items = $conn->query("SELECT oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $receipt_id");
            ?>
            <section class="receipt-preview" id="receiptPreview">
                <div class="receipt-actions"><button class="btn btn-primary" type="button" onclick="window.print()"><i class="fas fa-receipt"></i> Print Receipt</button></div>
                <h2>BakeTrack</h2>
                <p>Order #<?= str_pad($receipt_order['id'], 6, '0', STR_PAD_LEFT) ?></p>
                <p>Customer: <?= htmlspecialchars($receipt_order['customer']) ?></p>
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

            <!-- ==================== TRANSACTIONS ==================== -->
            <?php if ($page === 'transactions'): ?>
            <div class="page-header">
                <h1>Transactions</h1>
            </div>
            <div class="transactions-list">
                <?php 
                $transactionPage = filter_var($_GET['transactions_page'] ?? 1, FILTER_VALIDATE_INT);
                $transactionPage = $transactionPage && $transactionPage > 0 ? $transactionPage : 1;
                $transactions = getAllTransactions($transactionPage);
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
                    <?php if ($trans['receipt']): ?>
                    <button class="btn-small" onclick="alert('Receipt:\n<?= addslashes($trans['receipt']) ?>')">
                        <i class="fas fa-receipt"></i> View
                    </button>
                    <?php endif; ?>
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

            <!-- ==================== MESSAGES ==================== -->
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
                    $messages = $conn->query("SELECT m.*, u.fullname as sender, u2.fullname as receiver 
                        FROM messages m 
                        JOIN users u ON m.sender_id = u.id 
                        JOIN users u2 ON m.receiver_id = u2.id 
                        ORDER BY m.created_at DESC LIMIT 20");
                    if ($messages->num_rows > 0):
                    while ($msg = $messages->fetch_assoc()): 
                    ?>
                    <div class="message-item <?= $msg['is_read'] ? 'read' : 'unread' ?>">
                        <div class="msg-header">
                            <strong><?= htmlspecialchars($msg['sender']) ?></strong>
                            <span>→ <?= htmlspecialchars($msg['receiver']) ?></span>
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

            <!-- ==================== INVENTORY ==================== -->
            <?php if ($page === 'inventory'): ?>
            <div class="page-header">
                <h1>Inventory Management</h1>
                <div class="search-box">
                    <input type="text" id="searchInventory" placeholder="Search products..." oninput="filterInventory()">
                    <button type="button" onclick="filterInventory()"><i class="fas fa-search"></i></button>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="chart-container">
                <h3>Monthly Inventory Trend</h3>
                <canvas id="inventoryChart"></canvas>
                <p id="chartUnavailable" class="empty-state" role="status" hidden>Chart unavailable. Inventory details are still shown in the table below.</p>
            </div>
            
            <!-- Inventory Table -->
            <div class="inventory-table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Date Added</th>
                            <th>Expected Earnings</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryBody">
                        <?php 
                        $total_earnings = 0;
                        $products = $conn->query("SELECT * FROM products ORDER BY date_added DESC");
                        while ($product = $products->fetch_assoc()):
                        $earnings = $product['price'] * $product['quantity'];
                        $total_earnings += $earnings;
                        ?>
                        <tr data-name="<?= htmlspecialchars(mb_strtolower($product['name']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                            <td><?= htmlspecialchars($product['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td>₱<?= number_format($product['price'], 2) ?></td>
                            <td><?= $product['quantity'] ?></td>
                            <td><?= date('M d, Y', strtotime($product['date_added'])) ?></td>
                            <td>₱<?= number_format($earnings, 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <tr class="total-row">
                            <td colspan="4"><strong>Total Expected Earnings</strong></td>
                            <td><strong>₱<?= number_format($total_earnings, 2) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <script>
            // Inventory Chart
            const chartCanvas = document.getElementById('inventoryChart');
            if (typeof Chart === 'undefined' || !chartCanvas) {
                document.getElementById('chartUnavailable').hidden = false;
            } else {
            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Monthly Inventory',
                        data: <?= json_encode(array_values($monthly_data)) ?>,
                        borderColor: '#8B6914',
                        backgroundColor: 'rgba(139, 105, 20, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            }
            
            function filterInventory() {
                const input = document.getElementById('searchInventory').value.toLowerCase();
                const rows = document.querySelectorAll('#inventoryBody tr:not(.total-row)');
                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    row.style.display = name.includes(input) ? '' : 'none';
                });
            }
            </script>
            <?php endif; ?>

            <!-- ==================== DATABASE ==================== -->
            <?php if ($page === 'database'): ?>
            <div class="page-header">
                <h1>User Database</h1>
                <div class="search-box">
                    <input type="text" id="searchDatabase" placeholder="Search users..." aria-label="Search users" oninput="filterDatabase()">
                    <button type="button" aria-label="Search users" onclick="filterDatabase()"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success" role="status">User updated successfully.</div>
            <?php elseif (isset($_GET['created'])): ?>
            <div class="alert alert-success" role="status">Assistant account created successfully.</div>
            <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-error" role="alert">Unable to update that user.</div>
            <?php endif; ?>

            <div class="settings-card assistant-account-form">
                <h3>Add Assistant</h3>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="user_action" value="create_assistant">
                    <div class="form-group">
                        <label for="assistantUsername">Username</label>
                        <input type="text" id="assistantUsername" name="username" minlength="3" maxlength="100" pattern="[A-Za-z0-9._-]{3,100}" required>
                    </div>
                    <div class="form-group">
                        <label for="assistantFullname">Full name</label>
                        <input type="text" id="assistantFullname" name="fullname" maxlength="255" required>
                    </div>
                    <div class="form-group">
                        <label for="assistantEmail">Email</label>
                        <input type="email" id="assistantEmail" name="email" maxlength="255" required>
                    </div>
                    <div class="form-group">
                        <label for="assistantPassword">Temporary password</label>
                        <input type="password" id="assistantPassword" name="password" minlength="<?= MIN_PASSWORD_LENGTH ?>" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Assistant</button>
                </form>
            </div>
            
            <div class="database-table-container">
                <table class="database-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="databaseBody">
                        <?php 
                        $users = $conn->query("SELECT * FROM users WHERE is_active = 1 ORDER BY created_at DESC");
                        while ($u = $users->fetch_assoc()):
                        ?>
                        <tr data-username="<?= htmlspecialchars(mb_strtolower($u['username']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                            data-fullname="<?= htmlspecialchars(mb_strtolower($u['fullname']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                            data-email="<?= htmlspecialchars(mb_strtolower($u['email']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                            data-role="<?= htmlspecialchars(mb_strtolower($u['role']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($u['fullname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($u['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <td><span class="role-badge <?= htmlspecialchars($u['role'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($u['role']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <button class="btn-small btn-edit" onclick='editUser(<?= (int) $u['id'] ?>, <?= htmlspecialchars(json_encode($u['username'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($u['fullname'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($u['email'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($u['role'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-small btn-danger" onclick="changePassword(<?= $u['id'] ?>)">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($u['role'] !== 'admin'): ?>
                                <button class="btn-small btn-delete" onclick="deleteUser(<?= $u['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <script>
            function filterDatabase() {
                const input = document.getElementById('searchDatabase').value.toLowerCase();
                const rows = document.querySelectorAll('#databaseBody tr');
                rows.forEach(row => {
                    const matches = ['username', 'fullname', 'email', 'role'].some(field => {
                        const value = row.getAttribute('data-' + field) || '';
                        return value.includes(input);
                    });
                    row.style.display = matches ? '' : 'none';
                });
            }

            function editUser(id, username, fullname, email, role) {
                const newFullname = prompt('Edit Full Name:', fullname);
                if (newFullname === null || newFullname.trim() === '') return;
                const newEmail = prompt('Edit Email Address:', email);
                if (newEmail === null || newEmail.trim() === '') return;
                const newRole = prompt('Edit Role (admin, assistant, customer):', role);
                if (newRole === null) return;
                submitUserUpdate(id, newFullname.trim(), newEmail.trim(), newRole.trim().toLowerCase());
            }

            function submitUserUpdate(id, fullname, email, role) {
                submitAdminPost({user_id: id, user_action: 'update_user', fullname, email, role}, 'admin.php?page=database');
            }
            
            function changePassword(id) {
                const newPass = prompt('Enter new password (min <?= MIN_PASSWORD_LENGTH ?> characters):');
                if (newPass && newPass.length >= <?= MIN_PASSWORD_LENGTH ?>) {
                    submitUserAction(id, 'change_password', 'password', newPass);
                } else if (newPass) {
                    alert('Password must be at least <?= MIN_PASSWORD_LENGTH ?> characters!');
                }
            }
            
            function deleteUser(id) {
                if (confirm('Are you sure you want to delete this user?')) {
                    submitUserAction(id, 'delete');
                }
            }

            function submitUserAction(id, action, fieldName, value) {
                const fields = {user_id: id, user_action: action};
                if (fieldName) fields[fieldName] = value;
                submitAdminPost(fields, 'admin.php?page=database');
            }
            </script>
            <?php endif; ?>

            <!-- ==================== SITE CONTENT ==================== -->
            <?php if ($page === 'content'):
            $contentFields = [
                'Overview page' => [
                    'home_title' => 'Main heading',
                    'home_subtitle' => 'Introductory text',
                    'home_cta_label' => 'Get Started button',
                    'home_feature_1_title' => 'Feature 1 title',
                    'home_feature_1_description' => 'Feature 1 description',
                    'home_feature_2_title' => 'Feature 2 title',
                    'home_feature_2_description' => 'Feature 2 description',
                    'home_feature_3_title' => 'Feature 3 title',
                    'home_feature_3_description' => 'Feature 3 description'
                ],
                'About page' => [
                    'about_story_title' => 'Story heading',
                    'about_story_paragraph_1' => 'Story paragraph 1',
                    'about_story_paragraph_2' => 'Story paragraph 2',
                    'about_home_button' => 'Home button',
                    'about_join_button' => 'Join Us button',
                    'about_value_1_title' => 'Value 1 title',
                    'about_value_1_description' => 'Value 1 description',
                    'about_value_2_title' => 'Value 2 title',
                    'about_value_2_description' => 'Value 2 description',
                    'about_value_3_title' => 'Value 3 title',
                    'about_value_3_description' => 'Value 3 description'
                ],
                'Shared footer' => [
                    'footer_text' => 'Footer text'
                ]
            ];
            ?>
            <div class="page-header">
                <h1>Site Content</h1>
                <p>Edit the text displayed on the public overview and about pages.</p>
            </div>
            <?php if (isset($_GET['saved'])): ?>
                <div class="alert alert-success" role="status">Site content updated.</div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">Unable to update site content. Please check the fields and try again.</div>
            <?php endif; ?>
            <div class="settings-container site-content-container">
                <div class="settings-card site-content-card">
                    <form method="POST">
                        <?= csrfField() ?>
                        <?php foreach ($contentFields as $group => $fields): ?>
                            <fieldset class="site-content-group">
                                <legend><?= htmlspecialchars($group, ENT_QUOTES, 'UTF-8') ?></legend>
                                <?php foreach ($fields as $key => $label): ?>
                                    <div class="form-group">
                                        <label for="site-content-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
                                        <textarea id="site-content-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" name="site_content[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" rows="<?= str_contains($key, 'paragraph') || str_contains($key, 'description') || $key === 'footer_text' ? '3' : '2' ?>" maxlength="2000" required><?= htmlspecialchars($siteContent[$key], ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endforeach; ?>
                        <button type="submit" name="save_site_content" value="1" class="btn btn-primary">Save Site Content</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== SETTINGS ==================== -->
            <?php if ($page === 'settings'): 
            // Handle settings update
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
                $fullname = trim($_POST['fullname'] ?? '');
                $birthdate = trim($_POST['birthdate'] ?? '');
                $darkmode = isset($_POST['darkmode']) ? 1 : 0;
                $anonymous = isset($_POST['anonymous']) ? 1 : 0;

                if ($fullname === '') {
                    echo "<script>alert('Full name is required.'); window.location='admin.php?page=settings';</script>";
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
                    echo "<script>alert('Settings updated!'); window.location='admin.php?page=settings';</script>";
                } else {
                    echo "<script>alert('Unable to update settings.'); window.location='admin.php?page=settings';</script>";
                }
                exit();
            }

            // Get updated user data
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
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?= htmlspecialchars($user['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" disabled>
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
    // ==================== JAVASCRIPT FUNCTIONS ====================

    function submitAdminPost(fields, action = 'admin.php') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;
        const values = {...fields, csrf_token: document.body.dataset.csrfToken};
        Object.entries(values).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }
    
    // Product functions
    function openProductModal() {
        document.getElementById('productModal').style.display = 'block';
        document.getElementById('modalTitle').textContent = 'Add Product';
        document.getElementById('productId').value = '';
        document.getElementById('prodName').value = '';
        document.getElementById('prodPrice').value = '';
        document.getElementById('prodQty').value = '';
        document.getElementById('prodDesc').value = '';
    }
    
    function closeProductModal() {
        document.getElementById('productModal').style.display = 'none';
    }
    
    function editProduct(id, name, price, qty, desc) {
        document.getElementById('productModal').style.display = 'block';
        document.getElementById('modalTitle').textContent = 'Edit Product';
        document.getElementById('productId').value = id;
        document.getElementById('prodName').value = name;
        document.getElementById('prodPrice').value = price;
        document.getElementById('prodQty').value = qty;
        document.getElementById('prodDesc').value = desc;
    }
    
    function deleteProduct(id) {
        if (confirm('Are you sure you want to delete this product?')) {
            submitAdminPost({product_id: id, delete_product: '1'}, 'admin.php?page=uploads');
        }
    }
    
    // Order functions
    function updateOrder(orderId, status) {
        if (confirm(`Update order #${orderId} to ${status}?`)) {
            submitAdminPost({update_order: '1', order_id: orderId, status}, 'admin.php?page=orders');
        }
    }
    
    function unprepareOrder(orderId) {
        const reason = prompt('Why are you unpreparing this order? (Optional)');
        if (reason !== null) {
            submitAdminPost({update_order: '1', order_id: orderId, status: 'waiting', reason}, 'admin.php?page=orders');
        }
    }
    
    function viewAdminReceipt(orderId) {
        window.location.href = `?page=orders&view_receipt=${orderId}`;
    }
    
    // Tab functions
    function showTab(event, tab) {
        document.querySelectorAll('.order-list').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tab).style.display = 'block';
        event.currentTarget.classList.add('active');
    }
    
    // Product filter
    function filterProducts() {
        const input = document.getElementById('searchProduct').value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');
        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            card.style.display = name.includes(input) ? '' : 'none';
        });
    }
    
    // Close modal on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('productModal');
        if (event.target === modal) closeProductModal();
    }
    </script>
<script src="js/script.js"></script>
</body>
</html>