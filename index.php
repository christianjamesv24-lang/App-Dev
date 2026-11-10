
<?php
require_once 'includes/functions.php';

$page = 'home';

$dashboardUrl = getAuthenticatedDashboardUrl();
$accountUrl = $dashboardUrl ?? 'auth.php';
$getStartedUrl = $dashboardUrl ?? 'auth.php?mode=signup';
$accountLabel = $dashboardUrl ? 'My Dashboard' : 'Sign In';
$primaryAction = $dashboardUrl ? 'Open Dashboard' : 'Get Started';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Discover BakeTrack, your home for freshly baked bread, delightful pastries, and bakery favorites.">

    <title>BakeTrack | Freshly Baked, Made with Love</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/home.css">
</head>

<body class="overview-default home-page">

    <div class="home-wrapper">

        <!-- NAVIGATION -->
        <header class="home-header">

            <nav class="home-navbar">

                <a href="index.php" class="home-brand">
                    <span class="home-brand-icon">B</span>

                    <span class="home-brand-name">
                        Bake<span>Track.</span>
                    </span>
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

                    <a href="index.php" class="home-nav-link active">
                        Home
                    </a>

                    <a href="about.php" class="home-nav-link">
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
                        <?= htmlspecialchars($primaryAction, ENT_QUOTES, 'UTF-8') ?>

                        <span aria-hidden="true">→</span>
                    </a>

                </div>

            </nav>

        </header>


        <main>

            <!-- HERO SECTION -->
            <section class="home-hero">

                <div class="home-hero-content">

                    <div class="home-eyebrow">
                        <span class="eyebrow-dot"></span>
                        A LITTLE TASTE OF HAPPINESS
                    </div>

                    <h1>
                        Freshly Baked.
                        <span>Made with Love.</span>
                    </h1>

                    <p class="home-hero-description">
                        Discover the warmth of freshly baked goodness.
                        From delightful pastries to comforting bread,
                        every bite brings a little happiness to your day.
                    </p>

                    <div class="home-hero-actions">

                        <a
                            href="<?= htmlspecialchars($getStartedUrl, ENT_QUOTES, 'UTF-8') ?>"
                            class="home-primary-button"
                        >
                            Explore Our Shop

                            <span aria-hidden="true">→</span>
                        </a>

                        <a href="about.php" class="home-secondary-button">
                            Discover Our Story
                        </a>

                    </div>

                    <div class="home-hero-note">

                        <div class="home-note-icon">
                            <span>✦</span>
                        </div>

                        <div>
                            <strong>A little sweetness in every moment</strong>
                            <p>Thoughtfully made, always delightful.</p>
                        </div>

                    </div>

                </div>


                <div class="home-hero-visual">

                    <div class="home-image-decoration"></div>

                    <div class="home-image-main">

                        <img
                            src="https://images.unsplash.com/photo-1586444248902-2f64eddc13df?w=1000&q=85"
                            alt="Freshly baked artisan bakery pastries"
                        >

                    </div>

                    <div class="home-floating-card">

                        <div class="floating-card-icon">
                            ✦
                        </div>

                        <div>
                            <span>BAKERY GOODNESS</span>
                            <strong>Made with care</strong>
                        </div>

                    </div>

                    <div class="home-image-caption">
                        GOOD FOOD. GOOD MOOD.
                    </div>

                </div>

            </section>


            <!-- HIGHLIGHTS -->
            <section class="home-highlights">

                <div class="home-section-heading">

                    <span class="home-section-label">
                        THE BAKE TRACK EXPERIENCE
                    </span>

                    <h2>
                        A little more than
                        <span>just a bakery.</span>
                    </h2>

                    <p>
                        Good food brings people together.
                        We celebrate every moment through the simple
                        joy of bakery favorites.
                    </p>

                </div>


                <div class="home-highlights-grid">

                    <article class="home-highlight-card">

                        <div class="highlight-icon highlight-icon-gold">
                            <span>✧</span>
                        </div>

                        <span class="highlight-number">01</span>

                        <h3>Freshly Baked</h3>

                        <p>
                            Enjoy delightful bread and pastries
                            inspired by the comfort of fresh baking.
                        </p>

                    </article>


                    <article class="home-highlight-card featured-highlight">

                        <div class="highlight-icon highlight-icon-cream">
                            <span>♡</span>
                        </div>

                        <span class="highlight-number">02</span>

                        <h3>Made with Love</h3>

                        <p>
                            Every bakery favorite is a reminder
                            that the best things are made with care.
                        </p>

                    </article>


                    <article class="home-highlight-card">

                        <div class="highlight-icon highlight-icon-gold">
                            <span>✦</span>
                        </div>

                        <span class="highlight-number">03</span>

                        <h3>Bakery Favorites</h3>

                        <p>
                            Discover delicious treats and find
                            something special for every craving.
                        </p>

                    </article>

                </div>

            </section>


            <!-- ABOUT PREVIEW -->
            <section class="home-story">

                <div class="home-story-image">

                    <img
                        src="https://images.unsplash.com/photo-1509440159596-0249088772ff?w=1000&q=85"
                        alt="Artisan bread displayed in a bakery"
                        loading="lazy"
                    >

                    <div class="home-story-image-label">
                        <span>BAKED WITH</span>
                        <strong>Happiness ♡</strong>
                    </div>

                </div>


                <div class="home-story-content">

                    <span class="home-section-label">
                        OUR LITTLE BAKERY STORY
                    </span>

                    <h2>
                        Every bite has
                        <span>a story to tell.</span>
                    </h2>

                    <p>
                        At BakeTrack, we believe that bakery favorites
                        are more than just food. They bring comfort,
                        create memories, and make ordinary moments
                        a little more special.
                    </p>

                    <p>
                        We invite you to explore our bakery world
                        and discover something delicious along the way.
                    </p>

                    <a href="about.php" class="home-story-link">
                        Get to know us

                        <span aria-hidden="true">→</span>
                    </a>

                </div>

            </section>


            <!-- CALL TO ACTION -->
            <section class="home-cta">

                <div class="home-cta-decoration cta-decoration-one"></div>
                <div class="home-cta-decoration cta-decoration-two"></div>

                <div class="home-cta-content">

                    <span>YOUR NEXT FAVORITE TREAT AWAITS</span>

                    <h2>
                        Life is sweeter
                        with a little bakery goodness.
                    </h2>

                    <p>
                        Come explore, discover your favorites,
                        and make every moment a little sweeter.
                    </p>

                    <a
                        href="<?= htmlspecialchars($getStartedUrl, ENT_QUOTES, 'UTF-8') ?>"
                        class="home-cta-button"
                    >
                        <?= htmlspecialchars($primaryAction, ENT_QUOTES, 'UTF-8') ?>

                        <span aria-hidden="true">→</span>
                    </a>

                </div>

            </section>

        </main>


        <!-- FOOTER -->
        <footer class="home-footer">

            <div class="home-footer-main">

                <div class="home-footer-brand">

                    <a href="index.php" class="home-brand footer-brand">
                        <span class="home-brand-icon">B</span>

                        <span class="home-brand-name">
                            Bake<span>Track.</span>
                        </span>
                    </a>

                    <p>
                        A little taste of happiness,
                        made for life's sweetest moments.
                    </p>

                </div>


                <div class="home-footer-links">

                    <h4>Explore</h4>

                    <a href="index.php">Home</a>
                    <a href="about.php">About Us</a>
                    <a href="auth.php?mode=signup">Get Started</a>

                </div>


                <div class="home-footer-links">

                    <h4>Your Account</h4>

                    <a href="<?= htmlspecialchars($accountUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?>
                    </a>

                    <a href="<?= htmlspecialchars($getStartedUrl, ENT_QUOTES, 'UTF-8') ?>">
                        Explore Our Shop
                    </a>

                </div>

            </div>


            <div class="home-footer-bottom">

                <p>
                    &copy; <span class="current-year">2026</span>
                    BakeTrack. All rights reserved.
                </p>

                <span>MADE WITH ♡ FOR EVERY SWEET MOMENT</span>

            </div>

        </footer>

    </div>


    <!-- HOME NAVIGATION SCRIPT -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const toggle = document.querySelector('.home-menu-toggle');
            const navigation = document.getElementById('home-navigation');

            if (!toggle || !navigation) return;

            function closeMenu() {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Open navigation menu');
                navigation.classList.remove('is-open');
            }

            toggle.addEventListener('click', function () {

                const isOpen =
                    toggle.getAttribute('aria-expanded') === 'true';

                toggle.setAttribute('aria-expanded', String(!isOpen));

                toggle.setAttribute(
                    'aria-label',
                    isOpen ? 'Open navigation menu' : 'Close navigation menu'
                );

                navigation.classList.toggle('is-open', !isOpen);

            });

            navigation.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeMenu);
            });

            document.addEventListener('click', function (event) {

                if (
                    !toggle.contains(event.target) &&
                    !navigation.contains(event.target)
                ) {
                    closeMenu();
                }

            });

            document.addEventListener('keydown', function (event) {

                if (event.key === 'Escape') {
                    closeMenu();
                    toggle.focus();
                }

            });

            window.addEventListener('resize', function () {

                if (window.innerWidth > 768) {
                    closeMenu();
                }

            });

        });
    </script>

    <script src="js/script.js"></script>

</body>
</html>
