<?php
session_start();
include "config/db.php";

$cart_items = [];
$grand_total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $sql = "SELECT product_id, name, price, stock FROM Products WHERE product_id IN ($placeholders)";
    $stmt = mysqli_prepare($conn, $sql);
    
    // Bind dynamic types (all are integers)
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
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shopping Cart - Stuart's Comic Shop</title>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a class="nav-link" href="admin/dashboard.php">Admin</a>
                        <?php else: ?>
                            <a class="nav-link active" href="cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                            <a class="nav-link" href="customer/order-history.php">My Orders</a>
                        <?php endif; ?>
                        <a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?>)</a>
                    <?php else: ?>
                        <a class="nav-link active" href="cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
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
                <p class="eyebrow">Shopping</p>
                <h1 class="page-title">Your Cart</h1>
                <p class="page-intro mb-0">Review the comics and collectibles before checking out.</p>
            </div>
        </header>

        <section class="section-space">
            <div class="content-width">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <?php if (empty($cart_items)): ?>
                    <div class="empty-state text-center">
                        <h2 class="h4">Your cart is empty.</h2>
                        <p class="muted-text mb-3">Add some awesome comics or action figures!</p>
                        <a class="btn btn-premium" href="products.php">Browse Products</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="table-responsive">
                                <table class="table premium-table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-semibold text-light"><?php echo htmlspecialchars($item['name']); ?></span>
                                                </td>
                                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <form action="cart-action.php" method="post" class="d-flex align-items-center gap-2">
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <input type="number" name="quantity" class="form-control" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" style="width: 70px; background-color: #2b1111; color: #fff; border: 1px solid #732222;" onchange="this.form.submit()">
                                                    </form>
                                                </td>
                                                <td class="fw-semibold text-light">$<?php echo number_format($item['line_total'], 2); ?></td>
                                                <td>
                                                    <form action="cart-action.php" method="post">
                                                        <input type="hidden" name="action" value="remove">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" style="border-color: #732222; color: #f5a9a9;">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <form action="cart-action.php" method="post">
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="btn btn-outline-secondary">Clear Cart</button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <aside class="interaction-card">
                                <h2 class="h4 mb-4 text-light">Order Summary</h2>
                                <div class="d-flex justify-content-between mb-3 text-light">
                                    <span>Subtotal</span>
                                    <span class="fw-semibold">$<?php echo number_format($grand_total, 2); ?></span>
                                </div>
                                <hr style="border-color: #732222;">
                                <div class="d-flex justify-content-between mb-4 text-light fs-5">
                                    <strong>Total</strong>
                                    <strong class="text-premium">$<?php echo number_format($grand_total, 2); ?></strong>
                                </div>
                                <a class="btn btn-premium w-100 py-3" href="checkout.php">Proceed to Checkout</a>
                            </aside>
                        </div>
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
