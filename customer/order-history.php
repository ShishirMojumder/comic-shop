<?php

include __DIR__ . "/../config/db.php";

$user_id = 1;
// TEMPORARY DEMO USER FOR CSE370 PRESENTATION

$orders_sql = "SELECT
                    o.Order_id,
                    o.Date,
                    o.Status,
                    o.Shipping_address,
                    SUM(oi.quantity * oi.unit_price) AS order_total
                FROM Orders o
                JOIN Order_Items oi
                    ON o.Order_id = oi.Order_id
                WHERE o.user_id = ?
                GROUP BY
                    o.Order_id,
                    o.Date,
                    o.Status,
                    o.Shipping_address
                ORDER BY o.Date DESC";

$orders_statement = mysqli_prepare($conn, $orders_sql);
mysqli_stmt_bind_param($orders_statement, "i", $user_id);
mysqli_stmt_execute($orders_statement);
$orders_result = mysqli_stmt_get_result($orders_statement);

$items_sql = "SELECT
                    p.name,
                    oi.quantity,
                    oi.unit_price,
                    (oi.quantity * oi.unit_price) AS line_total
                FROM Order_Items oi
                JOIN Products p
                    ON oi.product_id = p.product_id
                WHERE oi.Order_id = ?
                ORDER BY oi.item_no";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Order History - Stuart's Comic Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="site-shell">
    <nav class="navbar navbar-expand-lg site-nav" data-bs-theme="dark">
        <div class="content-width">
            <a class="navbar-brand brand-lockup" href="../index.php">
                <span class="brand-main">STUART'S</span>
                <span class="brand-sub">Comic Shop</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="../index.php">Home</a>
                    <a class="nav-link" href="../products.php">Products</a>
                    <a class="nav-link active" href="order-history.php">My Orders</a>
                    <a class="nav-link" href="../admin/dashboard.php">Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="site-main">
        <header class="page-header">
            <div class="content-width">
                <p class="eyebrow">Account</p>
                <h1 class="page-title">My Orders</h1>
                <p class="page-intro mb-0">A record of your purchases from Stuart's Comic Shop.</p>
            </div>
        </header>

        <section class="section-space">
            <div class="content-width">
                <a class="back-link" href="../index.php">&larr; Back to Home</a>

                <?php if (mysqli_num_rows($orders_result) === 0): ?>
                    <div class="empty-state text-center">
                        <h2 class="h4">No orders yet.</h2>
                        <p class="muted-text mb-3">Your completed purchases will appear here.</p>
                        <a class="btn btn-premium" href="../products.php">Explore Collection</a>
                    </div>
                <?php else: ?>
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                        <article class="order-card mb-4">
                            <header class="order-header">
                                <h2 class="h3 mb-0">Order #<?php echo (int) $order["Order_id"]; ?></h2>
                                <span class="status-badge status-complete"><?php echo htmlspecialchars($order["Status"]); ?></span>
                            </header>
                            <div class="order-body">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <span class="order-meta-label">Order Date</span>
                                        <strong><?php echo htmlspecialchars($order["Date"]); ?></strong>
                                    </div>
                                    <div class="col-md-5">
                                        <span class="order-meta-label">Shipping Address</span>
                                        <strong><?php echo htmlspecialchars($order["Shipping_address"]); ?></strong>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <span class="order-meta-label">Order Total</span>
                                        <span class="order-total">$<?php echo number_format((float) $order["order_total"], 2); ?></span>
                                    </div>
                                </div>

                    <div class="table-responsive">
                                    <table class="table premium-table align-middle">
                            <thead>
                                <tr>
                                                <th>Item</th>
                                                <th>Qty</th>
                                    <th>Unit Price</th>
                                                <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $order_id = (int) $order["Order_id"];
                                $items_statement = mysqli_prepare($conn, $items_sql);
                                mysqli_stmt_bind_param($items_statement, "i", $order_id);
                                mysqli_stmt_execute($items_statement);
                                $items_result = mysqli_stmt_get_result($items_statement);
                                ?>
                                <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item["name"]); ?></td>
                                        <td><?php echo (int) $item["quantity"]; ?></td>
                                        <td>$<?php echo number_format((float) $item["unit_price"], 2); ?></td>
                                                <td class="text-end fw-semibold">$<?php echo number_format((float) $item["line_total"], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php mysqli_stmt_close($items_statement); ?>
                            </tbody>
                        </table>
                    </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
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
