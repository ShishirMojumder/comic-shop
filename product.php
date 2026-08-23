<?php

include "config/db.php";

$user_id = 1; // TEMPORARY DEMO USER FOR CSE370 PRESENTATION
$product_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$product_id || $product_id < 1) {
    die("Invalid product ID.");
}

$product_sql = "SELECT
                    p.product_id,
                    p.name,
                    p.price,
                    p.stock,
                    CASE
                        WHEN c.product_id IS NOT NULL THEN 'Comic'
                        WHEN m.product_id IS NOT NULL THEN 'Merchandise'
                        ELSE 'General Product'
                    END AS product_type,
                    c.issue_no,
                    c.author,
                    m.size,
                    m.material,
                    prs.average_rating,
                    COALESCE(prs.total_reviews, 0) AS total_reviews
                FROM Products p
                LEFT JOIN Comics c
                    ON p.product_id = c.product_id
                LEFT JOIN Merchandise m
                    ON p.product_id = m.product_id
                LEFT JOIN product_rating_summary prs
                    ON p.product_id = prs.product_id
                WHERE p.product_id = ?";

$product_statement = mysqli_prepare($conn, $product_sql);
mysqli_stmt_bind_param($product_statement, "i", $product_id);
mysqli_stmt_execute($product_statement);
$product_result = mysqli_stmt_get_result($product_statement);
$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    die("Product not found.");
}

$reviews_sql = "SELECT rating, comment
                FROM Reviews
                WHERE product_id = ?
                ORDER BY review_id DESC";

$reviews_statement = mysqli_prepare($conn, $reviews_sql);
mysqli_stmt_bind_param($reviews_statement, "i", $product_id);
mysqli_stmt_execute($reviews_statement);
$reviews_result = mysqli_stmt_get_result($reviews_statement);

$eligibility_sql = "SELECT EXISTS(
                        SELECT 1
                        FROM Orders o
                        JOIN Order_Items oi
                            ON o.Order_id = oi.Order_id
                        WHERE o.user_id = ?
                          AND oi.product_id = ?
                          AND o.Status = 'COMPLETED'
                    ) AS has_purchased";

$eligibility_statement = mysqli_prepare($conn, $eligibility_sql);
mysqli_stmt_bind_param($eligibility_statement, "ii", $user_id, $product_id);
mysqli_stmt_execute($eligibility_statement);
$eligibility_result = mysqli_stmt_get_result($eligibility_statement);
$eligibility = mysqli_fetch_assoc($eligibility_result);
$has_purchased = (bool) $eligibility["has_purchased"];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($product["name"]); ?> - Stuart's Comic Shop</title>
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

    <main class="site-main section-space">
        <div class="content-width">
            <a class="back-link" href="products.php">&larr; Back to Products</a>

            <div class="row g-4 align-items-start">
                <div class="col-lg-7">
                    <section class="detail-panel">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <span class="type-badge"><?php echo htmlspecialchars($product["product_type"]); ?></span>
                            <span class="stock-badge <?php echo (int) $product["stock"] > 0 ? "stock-in" : "stock-out"; ?>">
                                <?php echo (int) $product["stock"] > 0 ? "In Stock" : "Out of Stock"; ?>
                            </span>
                        </div>

                        <h1 class="detail-name"><?php echo htmlspecialchars($product["name"]); ?></h1>
                        <p class="price mt-3 mb-0">$<?php echo number_format((float) $product["price"], 2); ?></p>

                        <div class="detail-list">
                            <?php if ($product["product_type"] === "Comic"): ?>
                                <p><span class="muted-text">Author</span><strong><?php echo htmlspecialchars($product["author"]); ?></strong></p>
                                <p><span class="muted-text">Issue</span><strong><?php echo htmlspecialchars($product["issue_no"]); ?></strong></p>
                            <?php elseif ($product["product_type"] === "Merchandise"): ?>
                                <p><span class="muted-text">Size</span><strong><?php echo htmlspecialchars($product["size"]); ?></strong></p>
                                <p><span class="muted-text">Material</span><strong><?php echo htmlspecialchars($product["material"]); ?></strong></p>
                            <?php endif; ?>
                            <p><span class="muted-text">Available stock</span><strong><?php echo (int) $product["stock"]; ?></strong></p>
                            <p>
                                <span class="muted-text">Average rating</span>
                                <strong class="rating"><span class="rating-star">★</span> <?php echo $product["average_rating"] === null ? "Not rated" : number_format((float) $product["average_rating"], 2); ?></strong>
                            </p>
                            <p><span class="muted-text">Reviews</span><strong><?php echo (int) $product["total_reviews"]; ?></strong></p>
                        </div>
                    </section>
                </div>

                <div class="col-lg-5">
                    <aside class="interaction-card">
                        <?php if ($has_purchased): ?>
                            <p class="eyebrow">Verified Buyer</p>
                            <h2 class="h3 mb-3">Share Your Review</h2>
                            <p class="muted-text mb-4">Tell other collectors what you thought about this product.</p>
                            <form action="add-review.php" method="post">
                                <input type="hidden" name="product_id" value="<?php echo (int) $product_id; ?>">

                                <label for="rating" class="form-label fw-semibold">Rating</label>
                                <select id="rating" name="rating" class="form-select mb-3" required>
                                    <option value="">Choose a rating</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>

                                <label for="comment" class="form-label fw-semibold">Comment</label>
                                <textarea id="comment" name="comment" class="form-control mb-4" rows="5" maxlength="1000"></textarea>

                                <button type="submit" class="btn btn-premium w-100">Submit Review</button>
                            </form>
                        <?php else: ?>
                            <div class="verified-notice">
                                <h2 class="h4">Verified Purchase Required</h2>
                                <p class="mb-0">Only customers who purchased this product can leave a review.</p>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>

            <section id="reviews" class="pt-5 mt-4">
                <p class="eyebrow">Customer Reviews</p>
                <h2 class="section-heading">What readers are saying.</h2>

                <?php if (mysqli_num_rows($reviews_result) === 0): ?>
                    <div class="empty-state text-center">
                        <h3 class="h5">No reviews yet.</h3>
                        <p class="muted-text mb-0">This product is waiting for its first verified review.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                            <div class="col-md-6">
                                <article class="review-card h-100">
                                    <p class="review-stars mb-2" aria-label="<?php echo (int) $review["rating"]; ?> out of 5 stars"><?php echo str_repeat("★", (int) $review["rating"]); ?></p>
                                    <p><?php echo htmlspecialchars($review["comment"] ?? ""); ?></p>
                                </article>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
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
