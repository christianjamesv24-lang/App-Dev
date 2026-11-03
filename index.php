<?php
require_once 'includes/functions.php';
$page = 'home';
$dashboardUrl = getAuthenticatedDashboardUrl();
$accountUrl = $dashboardUrl ?? 'auth.php';
$getStartedUrl = $dashboardUrl ?? 'auth.php?mode=signup';
$accountLabel = $dashboardUrl ? 'My dashboard' : 'Sign in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BakeTrack - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="overview-default" data-default-theme="true">
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-left">
                <a href="index.php" class="nav-btn"><i class="fas fa-home"></i> OVERVIEW</a>
            </div>
            <div class="nav-center">
                <h1 class="brand"><span class="brand-mark" aria-hidden="true">BT</span> BakeTrack</h1>
            </div>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" aria-label="Open navigation menu">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-right" id="site-menu">
                <a href="about.php" class="public-nav-link">About</a>
                <a href="<?= htmlspecialchars($accountUrl, ENT_QUOTES, 'UTF-8') ?>" class="public-nav-link public-nav-link-primary"><?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Welcome to BakeTrack</h1>
                <p>Where every bite tells a story of love and tradition</p>
                <div class="hero-buttons">
                    <a href="<?= htmlspecialchars($getStartedUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary"><?= $dashboardUrl ? 'Open dashboard' : 'Get Started' ?></a>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1586444248902-2f64eddc13df?w=500" alt="Bakery">
            </div>
        </section>

        <!-- Features -->
        <section class="features">
            <div class="feature-card">
                <i class="fas fa-bread-slice"></i>
                <h3>Fresh Baked</h3>
                <p>Daily fresh bread and pastries</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-heart"></i>
                <h3>Made with Love</h3>
                <p>Traditional recipes with a modern twist</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-store"></i>
                <h3>Visit Us</h3>
                <p>Experience the warmth of our bakery</p>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <p>&copy; 2026 BakeTrack - Your favorite bakery shop</p>
        </footer>
    </div>
    <script src="js/script.js"></script>
</body>
</html>