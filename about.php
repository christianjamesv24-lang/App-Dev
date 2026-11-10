
<?php
require_once 'includes/functions.php';

$page = 'about';

$dashboardUrl = getAuthenticatedDashboardUrl();
$accountUrl = $dashboardUrl ?? 'auth.php';
$getStartedUrl = $dashboardUrl ?? 'auth.php?mode=signup';

$accountLabel = $dashboardUrl ? 'My Dashboard' : 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Discover the story, passion, and values behind BakeTrack.">

    <title>Our Story | BakeTrack</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/about.css">
    <link rel="stylesheet" href="css/home.css">
</head>

<body class="about-page">

    <div class="container">

        <!-- HEADER: SAME AS HOME PAGE -->
        <header class="home-header">
            <nav class="home-navbar">

                <a href="index.php" class="home-brand">
                    <span class="home-brand-icon">B</span>
                    <span class="home-brand-name">Bake<span>Track.</span></span>
                </a>

                <button
                    class="home-menu-toggle"
                    type="button"
                    aria-expanded="false"
                    aria-controls="home-navigation"
                    aria-label="Open navigation menu"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="home-nav-links" id="home-navigation">

                    <a href="index.php" class="home-nav-link">
                        Home
                    </a>

                    <a
                        href="about.php"
                        class="home-nav-link active"
                        aria-current="page"
                    >
                        About Us
                    </a>

                    <a
                        href="<?= htmlspecialchars($accountUrl, ENT_QUOTES, 'UTF-8') ?>"
                        class="home-nav-link home-account-link"
                    >
                        <?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?>
                    </a>

                    <a
                        href="<?= htmlspecialchars($getStartedUrl, ENT_QUOTES, 'UTF-8') ?>"
                        class="home-nav-cta"
                    >
                        Get Started <span aria-hidden="true">→</span>
                    </a>

                </div>

            </nav>
        </header>


        <!-- HERO / OUR STORY -->
        <section class="hero">

            <div class="hero-content">

                <span class="about-eyebrow">
                    <span class="about-eyebrow-dot"></span>
                    THE HEART BEHIND BAKETRACK
                </span>

                <h1>
                    Baked with Passion,
                    <span>Made with Love.</span>
                </h1>

                <p>
                    Since 2020, BakeTrack has been bringing warmth and
                    happiness to the community through the simple joy
                    of freshly baked goodness.
                </p>

                <p>
                    Every creation reflects our love for baking,
                    carefully selected ingredients, and the timeless
                    beauty of traditional baking techniques.
                </p>

                <div class="hero-buttons">
                    <a href="index.php" class="btn btn-primary">
                        Explore Our Bakery
                        <span aria-hidden="true">→</span>
                    </a>

                    <a
                        href="<?= htmlspecialchars($accountUrl, ENT_QUOTES, 'UTF-8') ?>"
                        class="btn btn-secondary"
                    >
                        <?= $dashboardUrl ? 'Open Dashboard' : 'Join Our Community' ?>
                    </a>
                </div>

                <div class="about-hero-note">
                    <span class="about-note-icon">✦</span>
                    <div>
                        <strong>A Little Joy in Every Bite</strong>
                        <p>Made with care, shared with love.</p>
                    </div>
                </div>

            </div>

            <div class="hero-image">
                <div class="about-image-decoration"></div>

                <img
                    src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=1000&auto=format&fit=crop&q=85"
                    alt="Freshly baked artisan pastries"
                >

                <div class="about-image-label">
                    <span>OUR PROMISE</span>
                    <strong>Freshness & Quality</strong>
                </div>
            </div>

        </section>


        <!-- OUR STORY -->
        <section class="about-story">

            <div class="about-story-image">
                <img
                    src="https://images.unsplash.com/photo-1509440159596-0249088772ff?w=1000&auto=format&fit=crop&q=85"
                    alt="Fresh artisan bread prepared with care"
                >
            </div>

            <div class="about-story-content">

                <span class="about-section-label">A STORY OF PASSION</span>

                <h2>
                    More Than Just
                    <span>Good Baking.</span>
                </h2>

                <p>
                    BakeTrack was built around a simple idea: good food
                    brings people together. What makes a bakery special
                    is not only the products it creates, but also the
                    care, dedication, and passion behind every creation.
                </p>

                <p>
                    From selecting quality ingredients to preparing
                    every product with attention to detail, we believe
                    that every little step matters.
                </p>

                <blockquote>
                    "Every bake has a story, and every bite should feel
                    like coming home."
                </blockquote>

            </div>

        </section>


        <!-- OUR VALUES -->
        <section class="about-details">

            <div class="about-section-heading">
                <span class="about-section-label">WHAT WE BELIEVE IN</span>

                <h2>
                    The Values Behind
                    <span>Every Bake.</span>
                </h2>

                <p>
                    The principles that inspire us to create,
                    improve, and serve something meaningful every day.
                </p>
            </div>

            <div class="about-grid">

                <div class="about-item">
                    <div class="about-value-icon">
                        <i class="fas fa-seedling"></i>
                    </div>

                    <span class="about-value-number">01</span>

                    <h3>Quality Ingredients</h3>

                    <p>
                        We value carefully selected ingredients
                        because great baking starts with great quality.
                    </p>
                </div>

                <div class="about-item featured-about-item">
                    <div class="about-value-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>

                    <span class="about-value-number">02</span>

                    <h3>Community Love</h3>

                    <p>
                        We believe in building meaningful connections
                        and supporting the community around us.
                    </p>
                </div>

                <div class="about-item">
                    <div class="about-value-icon">
                        <i class="fas fa-award"></i>
                    </div>

                    <span class="about-value-number">03</span>

                    <h3>Commitment to Excellence</h3>

                    <p>
                        We strive to maintain quality, consistency,
                        and care in everything we do.
                    </p>
                </div>

            </div>

        </section>


        <!-- BAKING PHILOSOPHY -->
        <section class="about-philosophy">

            <div class="about-philosophy-decoration"></div>

            <div class="about-philosophy-content">

                <span>OUR BAKING PHILOSOPHY</span>

                <h2>
                    Good Baking Takes Time.
                    <br>
                    <em>Great Baking Takes Heart.</em>
                </h2>

                <p>
                    We believe that the best things are made with patience,
                    passion, and attention to every detail. That is the
                    spirit we want to bring into every BakeTrack experience.
                </p>

                <a href="index.php" class="about-philosophy-button">
                    Discover BakeTrack
                    <span aria-hidden="true">→</span>
                </a>

            </div>

        </section>


        <!-- FOOTER -->
        <footer class="about-footer">

            <div class="about-footer-main">

                <div class="about-footer-brand">
                    <h2 class="about-footer-logo">
                        <span>BT</span> BakeTrack
                    </h2>

                    <p>
                        Bringing people together through the warmth,
                        comfort, and happiness of baking.
                    </p>
                </div>

                <div class="about-footer-links">
                    <h4>Explore</h4>
                    <a href="index.php">Home</a>
                    <a href="about.php">About Us</a>
                </div>

                <div class="about-footer-links">
                    <h4>Your Account</h4>

                    <a href="<?= htmlspecialchars($accountUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>

            </div>

            <div class="about-footer-bottom">
                <p>&copy; 2026 BakeTrack. All rights reserved.</p>
                <span>MADE WITH LOVE & PASSION</span>
            </div>

        </footer>

    </div>

    <script src="js/script.js"></script>

</body>
</html>
