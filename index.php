<?php
require_once 'includes/functions.php';
$page = 'home';
$content = getSiteContent();
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
    <link rel="stylesheet" href="css/style.css?v=20261111-overview-mobile">
</head>
<body class="overview-default">
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
                <h1><?= htmlspecialchars($content['home_title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars($content['home_subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="hero-buttons">
                    <a href="<?= htmlspecialchars($getStartedUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary"><?= $dashboardUrl ? 'Open dashboard' : htmlspecialchars($content['home_cta_label'], ENT_QUOTES, 'UTF-8') ?></a>
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
                <h3><?= htmlspecialchars($content['home_feature_1_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($content['home_feature_1_description'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="feature-card">
                <i class="fas fa-heart"></i>
                <h3><?= htmlspecialchars($content['home_feature_2_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($content['home_feature_2_description'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="feature-card">
                <i class="fas fa-store"></i>
                <h3><?= htmlspecialchars($content['home_feature_3_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($content['home_feature_3_description'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <p>&copy; 2026 BakeTrack - <?= htmlspecialchars($content['footer_text'], ENT_QUOTES, 'UTF-8') ?></p>
        </footer>
    </div>
    <script src="js/script.js"></script>
</body>
</html>