<?php
session_start();
include "config/db.php";

$user_id = 1; // TEMPORARY DEMO USER FOR CSE370 PRESENTATION

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: products.php");
    exit();
}

// Fetch loyalty points
$stmt = mysqli_prepare($conn, "SELECT loyalty_pts FROM Customer WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$customer = mysqli_fetch_assoc($res);
$loyalty_pts = $customer ? (int)$customer['loyalty_pts'] : 0;
mysqli_stmt_close($stmt);

$cart_items = [];
$grand_total = 0;

$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$sql = "SELECT product_id, name, price, stock FROM Products WHERE product_id IN ($placeholders)";
$stmt = mysqli_prepare($conn, $sql);
$types = str_repeat('i', count($ids));
mysqli_stmt_bind_param($stmt, $types, ...$ids);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($res)) {
    $product_id = $row['product_id'];
    $qty = $_SESSION['cart'][$product_id];
    $row['quantity'] = $qty;
    $row['line_total'] = $row['price'] * $qty;
    $grand_total += $row['line_total'];
    $cart_items[] = $row;
}
mysqli_stmt_close($stmt);

$loyalty_discount_value = 0.10; // $0.10 per point
$max_points_usable = min($loyalty_pts, floor($grand_total / $loyalty_discount_value));
$loyalty_discount = $max_points_usable * $loyalty_discount_value;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - Stuart's Comic Shop</title>
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
                    <a class="nav-link" href="products.php">Products</a>
                    <a class="nav-link" href="events.php">Events</a>
                    <a class="nav-link" href="cart.php">Cart (<?php echo count($_SESSION['cart']); ?>)</a>
                    <a class="nav-link" href="customer/order-history.php">My Orders</a>
                    <a class="nav-link" href="admin/dashboard.php">Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="site-main">
        <header class="page-header">
            <div class="content-width">
                <p class="eyebrow">Purchase</p>
                <h1 class="page-title">Checkout</h1>
                <p class="page-intro mb-0">Confirm your order details and shipping address.</p>
            </div>
        </header>

        <section class="section-space">
            <div class="content-width">
                <a class="back-link" href="cart.php">&larr; Back to Cart</a>

                <div class="row g-4 mt-2">
                    <div class="col-lg-7">
                        <section class="detail-panel">
                            <h2 class="h3 mb-4 text-light">Shipping Details</h2>
                            <form action="place-order.php" method="post">
                                <div class="mb-3">
                                    <label for="shipping_address" class="form-label fw-semibold text-light">Shipping Address</label>
                                    <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" placeholder="Enter your full street address, city, and zip code" style="background-color: #2b1111; color: #fff; border: 1px solid #732222;" required></textarea>
                                </div>

                                <?php if ($loyalty_pts > 0): ?>
                                    <div class="interaction-card mb-4 p-3" style="background-color: #2b1111; border: 1px solid #732222;">
                                        <h3 class="h5 text-light mb-2">Loyalty Points Discount</h3>
                                        <p class="muted-text small mb-3">You have <strong><?php echo $loyalty_pts; ?></strong> loyalty points available.</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="use_loyalty" id="use_loyalty" value="1">
                                            <label class="form-check-label text-light" for="use_loyalty">
                                                Apply <?php echo $max_points_usable; ?> points for a discount of <strong>$<?php echo number_format($loyalty_discount, 2); ?></strong>
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="btn btn-premium w-100 py-3 mt-3">Place Order</button>
                            </form>
                        </section>
                    </div>

                    <div class="col-lg-5">
                        <aside class="interaction-card">
                            <h2 class="h4 mb-4 text-light">Order Summary</h2>
                            <div class="table-responsive mb-4">
                                <table class="table premium-table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <tr>
                                                <td class="small text-light">
                                                    <?php echo htmlspecialchars($item['name']); ?> <span class="text-muted">x<?php echo $item['quantity']; ?></span>
                                                </td>
                                                <td class="text-end text-light small">$<?php echo number_format($item['line_total'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-light small">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($grand_total, 2); ?></span>
                            </div>
                            <div id="loyalty_discount_row" class="d-flex justify-content-between mb-2 text-success small d-none">
                                <span>Loyalty Points Discount</span>
                                <span>-$<?php echo number_format($loyalty_discount, 2); ?></span>
                            </div>
                            <hr style="border-color: #732222;">
                            <div class="d-flex justify-content-between text-light fs-5">
                                <strong>Total</strong>
                                <strong id="order_total" class="text-premium">$<?php echo number_format($grand_total, 2); ?></strong>
                            </div>
                        </aside>
                    </div>
                </div>
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
<script>
    document.getElementById('use_loyalty')?.addEventListener('change', function() {
        const discountRow = document.getElementById('loyalty_discount_row');
        const orderTotalEl = document.getElementById('order_total');
        const discountVal = <?php echo $loyalty_discount; ?>;
        const subtotalVal = <?php echo $grand_total; ?>;
        
        if (this.checked) {
            discountRow.classList.remove('d-none');
            orderTotalEl.textContent = '$' + (subtotalVal - discountVal).toFixed(2);
        } else {
            discountRow.classList.add('d-none');
            orderTotalEl.textContent = '$' + subtotalVal.toFixed(2);
        }
    });
</script>
</body>
</html>
