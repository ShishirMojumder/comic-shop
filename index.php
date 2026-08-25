<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stuart's Comic Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="site-shell">
    <nav class="navbar navbar-expand-lg site-nav" data-bs-theme="dark">
        <div class="content-width">
            <a class="navbar-brand brand-lockup" href="index.php">
                <span class="brand-main">STUART'S COMIC SHOP</span>
                <span class="brand-sub">Comics · Collectibles · Community</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link active" href="index.php">Home</a>
                    <a class="nav-link" href="products.php">Products</a>
                    <a class="nav-link" href="events.php">Events</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a class="nav-link" href="admin/dashboard.php">Admin</a>
                        <?php else: ?>
                            <a class="nav-link" href="cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                            <a class="nav-link" href="customer/order-history.php">My Orders</a>
                            <a class="nav-link" href="customer/quiz.php">Loyalty Quiz</a>
                            <a class="nav-link" href="customer/account.php">My Account</a>
                        <?php endif; ?>
                        <a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?>)</a>
                    <?php else: ?>
                        <a class="nav-link" href="cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                        <a class="nav-link" href="login.php">Login</a>
                        <a class="nav-link" href="register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="site-main home-editorial">
        <section class="feature-hero">
            <div class="content-width">
                <div class="row align-items-end g-0">
                    <div class="col-lg-5">
                        <div class="hero-character-wrap">
                            <img class="hero-character" src="assets/images/original-comic-guardian.png" alt="Original masked comic guardian in a burgundy coat">
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <article class="featured-comic-panel">
                            <p class="eyebrow">Featured Comic</p>
                            <p class="feature-issue">The definitive origin story</p>
                            <h1>Batman:<br>Year One</h1>
                            <p class="feature-description">A legendary beginning. Frank Miller's defining story follows Bruce Wayne's first year as Gotham's watchful protector.</p>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4">
                                <span class="feature-price">$15.99</span>
                                <a class="btn btn-premium" href="product.php?id=1">Shop Now <span aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <a class="btn btn-outline-premium" href="products.php">Browse Products</a>
                </div>
            </div>
        </section>

        <section class="section-space featured-products-section">
            <div class="content-width">
                <div class="text-center mb-5">
                    <p class="eyebrow">Curated Selection</p>
                    <h2 class="section-heading mb-0">Featured Products</h2>
                </div>

                <div class="row g-4 justify-content-center">
                    <div class="col-md-4">
                        <article class="home-product-card">
                            <div class="comic-cover cover-batman" aria-hidden="true">
                                <span class="cover-kicker">Gotham</span>
                                <strong>Year<br>One</strong>
                                <span class="cover-number">01</span>
                            </div>
                            <div class="home-product-info">
                                <h3>Batman: Year One</h3>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="home-product-price">$15.99</span>
                                    <span class="home-product-rating"><span>★</span> 5.0</span>
                                </div>
                                <a class="btn btn-premium w-100 mt-3" href="product.php?id=1">View</a>
                            </div>
                        </article>
                    </div>

                    <div class="col-md-4">
                        <article class="home-product-card">
                            <div class="comic-cover cover-spiderman" aria-hidden="true">
                                <span class="cover-kicker">A Story in Blue</span>
                                <strong>Spider<br>Man</strong>
                                <span class="cover-number">02</span>
                            </div>
                            <div class="home-product-info">
                                <h3>Spider-Man: Blue</h3>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="home-product-price">$18.50</span>
                                    <span class="home-product-rating"><span>★</span> 4.0</span>
                                </div>
                                <a class="btn btn-premium w-100 mt-3" href="product.php?id=2">View</a>
                            </div>
                        </article>
                    </div>

                    <div class="col-md-4">
                        <article class="home-product-card">
                            <div class="comic-cover cover-watchmen" aria-hidden="true">
                                <span class="cover-kicker">Who Watches</span>
                                <strong>Watch<br>Men</strong>
                                <span class="cover-number">03</span>
                            </div>
                            <div class="home-product-info">
                                <h3>Watchmen</h3>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="home-product-price">$22.00</span>
                                    <span class="home-product-rating"><span>★</span> Not rated</span>
                                </div>
                                <a class="btn btn-premium w-100 mt-3" href="product.php?id=3">View</a>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="content-width promo-banner">
            <div>
                <p class="promo-overline">Stuart's Exclusive</p>
                <h2>True Fan Collection</h2>
                <p>Comics &amp; Collectibles</p>
            </div>
            <a class="btn btn-light" href="products.php">Explore Shop</a>
        </section>

        <section class="section-space">
            <div class="content-width">
                <div class="text-center mb-5">
                    <p class="eyebrow">Find Your Next Favorite</p>
                    <h2 class="section-heading mb-0">Shop by Type</h2>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <article class="shop-type-card shop-type-comics">
                            <span class="shop-type-number">01</span>
                            <div>
                                <p class="eyebrow">Stories &amp; Graphic Novels</p>
                                <h3>Comics</h3>
                                <p>Explore classic stories and modern favorites.</p>
                                <a class="btn btn-outline-premium" href="products.php">Browse</a>
                            </div>
                        </article>
                    </div>
                    <div class="col-md-6">
                        <article class="shop-type-card shop-type-merch">
                            <span class="shop-type-number">02</span>
                            <div>
                                <p class="eyebrow">Wearables &amp; Figures</p>
                                <h3>Merchandise</h3>
                                <p>Collectibles made for devoted fans.</p>
                                <a class="btn btn-outline-premium" href="products.php">Browse</a>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-space" style="background: var(--ink-soft); border-top: 1px solid var(--border-dark); border-bottom: 1px solid var(--border-dark);">
            <div class="content-width">
                <div class="text-center mb-5">
                    <p class="eyebrow">Community &amp; Events</p>
                    <h2 class="section-heading mb-0">Comic-Con Events &amp; Ticketing</h2>
                </div>
                <div class="row g-4 justify-content-center">
                    <div class="col-md-8 text-center">
                        <p class="mb-4 text-muted" style="font-size: 1.1rem;">Secure your seats for local Comic-Cons, expo seminars, trivia nights, and cosplay championships! Our database-driven ticketing system tracks seat occupancy in real-time.</p>
                        <a class="btn btn-premium btn-lg" href="events.php">Explore &amp; Book Tickets Now</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="content-width text-center">
            <p class="footer-brand">Stuart's Comic Shop</p>
            <p class="small mb-1">CSE370 Database Systems Project · 2026</p>
            <p class="small mb-0">Built with PHP &amp; SQL</p>
        </div>
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
