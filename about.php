<?php
require_once 'includes/functions.php';
$page = 'about';
$content = getSiteContent();
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
    <link rel="stylesheet" href="css/style.css?v=20261111-overview-mobile">
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
                <h1><?= htmlspecialchars($content['about_story_title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars($content['about_story_paragraph_1'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><?= htmlspecialchars($content['about_story_paragraph_2'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="hero-buttons">
                    <a href="index.php" class="btn btn-primary"><?= htmlspecialchars($content['about_home_button'], ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="<?= htmlspecialchars($accountUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary"><?= $dashboardUrl ? 'Open dashboard' : htmlspecialchars($content['about_join_button'], ENT_QUOTES, 'UTF-8') ?></a>
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
                    <h3><?= htmlspecialchars($content['about_value_1_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($content['about_value_1_description'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="about-item">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3><?= htmlspecialchars($content['about_value_2_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($content['about_value_2_description'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="about-item">
                    <i class="fas fa-award"></i>
                    <h3><?= htmlspecialchars($content['about_value_3_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($content['about_value_3_description'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </section>

        <footer>
            <p>&copy; 2026 BakeTrack - <?= htmlspecialchars($content['footer_text'], ENT_QUOTES, 'UTF-8') ?></p>
        </footer>
    </div>
    <script src="js/script.js"></script>
</body>
</html>