<?php

include "config/db.php";

$sql = "SELECT
            p.product_id,
            p.name,
            p.price,
            p.stock,
            prs.average_rating,
            COALESCE(prs.total_reviews, 0) AS total_reviews
        FROM Products p
        LEFT JOIN product_rating_summary prs
            ON p.product_id = prs.product_id
        ORDER BY p.product_id";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Could not load products: " . mysqli_error($conn));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Catalog - Stuart's Comic Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="site-shell">
    <nav class="navbar navbar-expand-lg site-nav" data-bs-theme="dark">
        <div class="content-width">
            <a class="navbar-brand brand-lockup" href="index.php">
                <span class="brand-main">STUART'S</span>
                <span class="brand-sub">Comic Shop</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="index.php">Home</a>
                    <a class="nav-link active" href="products.php">Products</a>
                    <a class="nav-link" href="customer/order-history.php">My Orders</a>
                    <a class="nav-link" href="admin/dashboard.php">Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="site-main">
        <header class="page-header">
            <div class="content-width">
                <p class="eyebrow">Collection</p>
                <h1 class="page-title">Explore the Shop</h1>
                <p class="page-intro mb-0">Comics, graphic novels and collectibles selected for every kind of fan.</p>
            </div>
        </header>

        <section class="section-space">
            <div class="content-width">
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <div class="empty-state text-center">
                        <h2 class="h4">The collection is currently empty.</h2>
                        <p class="muted-text mb-0">Please check back when new products arrive.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                    <?php while ($product = mysqli_fetch_assoc($result)): ?>
                        <div class="col-md-6 col-lg-4">
                            <article class="card product-card">
                    <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                        <span class="stock-badge <?php echo (int) $product["stock"] > 0 ? "stock-in" : "stock-out"; ?>">
                                            <?php echo (int) $product["stock"] > 0 ? "In Stock" : "Out of Stock"; ?>
                                        </span>
                                        <span class="muted-text"><?php echo (int) $product["stock"]; ?> available</span>
                                    </div>
                                    <h2 class="product-name"><?php echo htmlspecialchars($product["name"]); ?></h2>
                                    <p class="price mb-4">$<?php echo number_format((float) $product["price"], 2); ?></p>
                                    <div class="mt-auto">
                                        <p class="rating mb-0">
                                            <span class="rating-star">★</span>
                                            <?php echo $product["average_rating"] === null ? "Not rated" : number_format((float) $product["average_rating"], 2); ?>
                                        </p>
                                        <p class="review-count mb-4"><?php echo (int) $product["total_reviews"]; ?> Reviews</p>
                                        <a class="btn btn-premium w-100" href="product.php?id=<?php echo (int) $product["product_id"]; ?>">View Details</a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="content-width text-center">
            <p class="footer-brand">Stuart's Comic Shop</p>
            <p class="small mb-1">CSE370 Database Systems Project · 2026</p>
            <p class="small mb-0">Built with PHP &amp; Raw SQL</p>
        </div>
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
