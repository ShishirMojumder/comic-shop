<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Administrator privileges required.";
    header("Location: ../login.php");
    exit();
}

// Handle Order Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $new_status = filter_input(INPUT_POST, 'status', FILTER_DEFAULT);
    
    if ($order_id && $new_status) {
        $stmt = mysqli_prepare($conn, "UPDATE Orders SET Status = ? WHERE Order_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Order #$order_id status updated to $new_status.";
        } else {
            $_SESSION['error'] = "Failed to update order status.";
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: dashboard.php");
    exit();
}

// 1. Total Revenue (SUM)
$revenue_sql = "SELECT SUM(quantity * unit_price) AS total_revenue FROM Order_Items";
$revenue_res = mysqli_query($conn, $revenue_sql);
$revenue_data = mysqli_fetch_assoc($revenue_res);
$total_revenue = $revenue_data ? (float)$revenue_data['total_revenue'] : 0.0;

// 2. Best Customers (SUM, GROUP BY, JOIN)
$top_customers_sql = "SELECT u.email, SUM(oi.quantity * oi.unit_price) AS total_spent, c.loyalty_pts
                      FROM Orders o
                      JOIN Order_Items oi ON o.Order_id = oi.Order_id
                      JOIN Users u ON o.user_id = u.user_id
                      JOIN Customer c ON o.user_id = c.user_id
                      GROUP BY o.user_id, u.email, c.loyalty_pts
                      ORDER BY total_spent DESC
                      LIMIT 5";
$top_customers_res = mysqli_query($conn, $top_customers_sql);

// 3. Out of Stock Items (COUNT, SELECT)
$out_of_stock_sql = "SELECT product_id, name, price FROM Products WHERE stock = 0";
$out_of_stock_res = mysqli_query($conn, $out_of_stock_sql);

// 4. All Orders with Items
$orders_sql = "SELECT o.Order_id, u.email, o.Date, o.Status, o.Shipping_address,
                      SUM(oi.quantity * oi.unit_price) AS order_total
               FROM Orders o
               JOIN Order_Items oi ON o.Order_id = oi.Order_id
               JOIN Users u ON o.user_id = u.user_id
               GROUP BY o.Order_id, u.email, o.Date, o.Status, o.Shipping_address
               ORDER BY o.Date DESC";
$orders_res = mysqli_query($conn, $orders_sql);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Stuart's Comic Shop</title>
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
                    <a class="nav-link" href="../events.php">Events</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a class="nav-link active" href="dashboard.php">Admin</a>
                        <?php else: ?>
                            <a class="nav-link" href="../cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                            <a class="nav-link" href="../customer/order-history.php">My Orders</a>
                        <?php endif; ?>
                        <a class="nav-link" href="../logout.php">Logout (<?php echo htmlspecialchars(explode('@', $_SESSION['email'])[0]); ?>)</a>
                    <?php else: ?>
                        <a class="nav-link" href="../cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                        <a class="nav-link" href="../login.php">Login</a>
                        <a class="nav-link" href="../register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="site-main">
        <header class="page-header">
            <div class="content-width">
                <p class="eyebrow">Management</p>
                <h1 class="page-title">Sales Dashboard</h1>
                <p class="page-intro mb-0">Shop owner dashboard for tracking business metrics.</p>
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

                <div class="row g-4 mb-5">
                    <div class="col-md-6 col-lg-4">
                        <div class="interaction-card text-center py-4">
                            <span class="muted-text small d-block mb-1">TOTAL REVENUE</span>
                            <span class="text-premium fs-2 fw-bold">$<?php echo number_format($total_revenue, 2); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="interaction-card text-center py-4">
                            <span class="muted-text small d-block mb-1">OUT OF STOCK PRODUCTS</span>
                            <span class="text-light fs-2 fw-bold"><?php echo mysqli_num_rows($out_of_stock_res); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-lg-6">
                        <section class="detail-panel">
                            <h2 class="h4 mb-4 text-light">Top Customers</h2>
                            <div class="table-responsive">
                                <table class="table premium-table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>Loyalty Points</th>
                                            <th class="text-end">Total Spent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($top_customers_res) === 0): ?>
                                            <tr><td colspan="3" class="text-center text-muted">No customer sales records.</td></tr>
                                        <?php else: ?>
                                            <?php while ($cust = mysqli_fetch_assoc($top_customers_res)): ?>
                                                <tr>
                                                    <td class="text-light small"><?php echo htmlspecialchars($cust['email']); ?></td>
                                                    <td><?php echo (int)$cust['loyalty_pts']; ?> pts</td>
                                                    <td class="text-end text-premium fw-semibold">$<?php echo number_format($cust['total_spent'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-lg-6">
                        <section class="detail-panel">
                            <h2 class="h4 mb-4 text-light">Out-of-Stock Items</h2>
                            <div class="table-responsive">
                                <table class="table premium-table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th class="text-end">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($out_of_stock_res) === 0): ?>
                                            <tr><td colspan="2" class="text-center text-muted">All products are in stock.</td></tr>
                                        <?php else: ?>
                                            <?php while ($prod = mysqli_fetch_assoc($out_of_stock_res)): ?>
                                                <tr>
                                                    <td class="text-light small"><?php echo htmlspecialchars($prod['name']); ?></td>
                                                    <td class="text-end text-light fw-semibold">$<?php echo number_format($prod['price'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>

                <section class="detail-panel">
                    <h2 class="h4 mb-4 text-light">Order History & Status Manager</h2>
                    <div class="table-responsive">
                        <table class="table premium-table align-middle">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Shipping Address</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-end">Update Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($orders_res) === 0): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No orders placed yet.</td></tr>
                                <?php else: ?>
                                    <?php while ($order = mysqli_fetch_assoc($orders_res)): ?>
                                        <tr>
                                            <td class="text-light fw-semibold">#<?php echo $order['Order_id']; ?></td>
                                            <td class="text-light small"><?php echo htmlspecialchars($order['email']); ?></td>
                                            <td><?php echo htmlspecialchars($order['Date']); ?></td>
                                            <td class="small"><?php echo htmlspecialchars($order['Shipping_address']); ?></td>
                                            <td class="text-premium fw-semibold">$<?php echo number_format($order['order_total'], 2); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $order['Status'] === 'COMPLETED' ? 'status-complete' : ($order['Status'] === 'CANCELLED' ? 'status-cancelled' : 'status-pending'); ?>">
                                                    <?php echo htmlspecialchars($order['Status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <form action="dashboard.php" method="post" class="d-inline-flex gap-1">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['Order_id']; ?>">
                                                    <select name="status" class="form-select form-select-sm" style="background-color: #2b1111; color: #fff; border: 1px solid #732222; font-size: 0.8rem; width: 110px;">
                                                        <option value="PENDING" <?php echo $order['Status'] === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="COMPLETED" <?php echo $order['Status'] === 'COMPLETED' ? 'selected' : ''; ?>>Complete</option>
                                                        <option value="CANCELLED" <?php echo $order['Status'] === 'CANCELLED' ? 'selected' : ''; ?>>Cancel</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-premium btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Save</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
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
