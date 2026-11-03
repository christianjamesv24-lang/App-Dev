<?php
require_once 'includes/functions.php';
$page = 'about';
$dashboardUrl = getAuthenticatedDashboardUrl();
$accountUrl = $dashboardUrl ?? 'auth.php';
$accountLabel = $dashboardUrl ? 'My dashboard' : 'Sign in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BakeTrack - About</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="nav-left">
                <a href="index.php" class="nav-btn"><i class="fas fa-eye"></i> TAKE LOOK</a>
            </div>
            <div class="nav-center">
                <h1 class="brand"><span class="brand-mark" aria-hidden="true">BT</span> About Us</h1>
            </div>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" aria-label="Open navigation menu">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-right" id="site-menu">
                <a href="index.php" class="public-nav-link">Home</a>
                <a href="<?= htmlspecialchars($accountUrl, ENT_QUOTES, 'UTF-8') ?>" class="public-nav-link public-nav-link-primary"><?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </nav>

        <section class="hero">
            <div class="hero-content">
                <h1>Our Story</h1>
                <p>Since 2020, BakeTrack has been serving the community with love and passion for baking.</p>
                <p>Every product is made with carefully selected ingredients and traditional techniques.</p>
                <div class="hero-buttons">
                    <a href="index.php" class="btn btn-primary">Home</a>
                    <a href="<?= htmlspecialchars($accountUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary"><?= $dashboardUrl ? 'Open dashboard' : 'Join Us' ?></a>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500" alt="Our Bakery">
            </div>
        </section>

        <section class="about-details">
            <div class="about-grid">
                <div class="about-item">
                    <i class="fas fa-seedling"></i>
                    <h3>Quality Ingredients</h3>
                    <p>We use only the finest ingredients</p>
                </div>
                <div class="about-item">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>Community Love</h3>
                    <p>Supporting local farmers and suppliers</p>
                </div>
                <div class="about-item">
                    <i class="fas fa-award"></i>
                    <h3>Excellence</h3>
                    <p>Recognized for quality and service</p>
                </div>
            </div>
        </section>

        <footer>
            <p>&copy; 2026 BakeTrack - Your favorite bakery shop</p>
        </footer>
    </div>
    <script src="js/script.js"></script>
</body>
</html>