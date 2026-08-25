<?php
session_start();
include "config/db.php";

$category_filter = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);

// Fetch categories for the filter UI
$cat_sql = "SELECT category_id, name FROM Categories ORDER BY name";
$cat_result = mysqli_query($conn, $cat_sql);
$categories = [];
if ($cat_result) {
    while ($row = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $row;
    }
}

// Fetch products (filtered if category is chosen)
if ($category_filter) {
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
            WHERE p.category_id = ?
            ORDER BY p.product_id";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $category_filter);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
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
}

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
                <div class="category-filters text-center mb-5">
                    <a href="products.php" class="btn btn-sm <?php echo !$category_filter ? 'btn-premium' : 'btn-outline-premium'; ?> me-2 mb-2">All Products</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="products.php?category=<?php echo $cat['category_id']; ?>" class="btn btn-sm <?php echo $category_filter === (int)$cat['category_id'] ? 'btn-premium' : 'btn-outline-premium'; ?> me-2 mb-2">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
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
            <p class="small mb-0">Built with PHP &amp; SQL</p>
        </div>
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
