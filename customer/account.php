<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$sql = "SELECT u.user_id, u.email, c.loyalty_pts,
               (SELECT COUNT(*) FROM Orders o WHERE o.user_id = u.user_id) AS total_orders,
               (SELECT COUNT(*) FROM Tickets t WHERE t.user_id = u.user_id) AS total_tickets,
               (SELECT COUNT(*) FROM Reviews r WHERE r.user_id = u.user_id) AS total_reviews
        FROM Users u
        JOIN Customer c ON c.user_id = u.user_id
        WHERE u.user_id = ?";
$statement = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($statement, "i", $user_id);
mysqli_stmt_execute($statement);
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));

if (!$account) {
    die("Customer account not found.");
}

$username = explode('@', $account['email'])[0];
$discount_value = (int) $account['loyalty_pts'] * 0.10;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account | Stuart's Comic Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark site-navbar">
        <div class="container-fluid content-width">
            <a class="navbar-brand" href="../index.php">Stuart's Comic Shop</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Home</a>
                <a class="nav-link" href="../products.php">Products</a>
                <a class="nav-link" href="../events.php">Events</a>
                <a class="nav-link" href="../cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                <a class="nav-link" href="order-history.php">My Orders</a>
                <a class="nav-link" href="quiz.php">Loyalty Quiz</a>
                <a class="nav-link active" href="account.php">My Account</a>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="site-main">
        <header class="page-header">
            <div class="content-width">
                <p class="eyebrow">Customer profile</p>
                <h1 class="page-title">My Account</h1>
                <p class="page-intro mb-0">Account details and loyalty rewards.</p>
            </div>
        </header>

        <section class="section-space">
            <div class="content-width">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <article class="order-card h-100">
                            <header class="order-header"><h2 class="h3 mb-0">Account Details</h2></header>
                            <div class="order-body">
                                <p><span class="order-meta-label">Username</span><strong><?php echo htmlspecialchars($username); ?></strong></p>
                                <p><span class="order-meta-label">Email</span><strong><?php echo htmlspecialchars($account['email']); ?></strong></p>
                                <p class="mb-0"><span class="order-meta-label">Customer ID</span><strong>#<?php echo (int) $account['user_id']; ?></strong></p>
                            </div>
                        </article>
                    </div>
                    <div class="col-lg-5">
                        <article class="interaction-card h-100 text-center p-4">
                            <p class="eyebrow">Available rewards</p>
                            <h2 class="display-4 text-premium fw-bold"><?php echo (int) $account['loyalty_pts']; ?></h2>
                            <p class="h5 text-light">Loyalty Points</p>
                            <p class="muted-text mb-0">Worth up to <strong class="text-premium">$<?php echo number_format($discount_value, 2); ?></strong> on your next checkout.</p>
                        </article>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-md-4"><div class="interaction-card text-center p-4"><strong class="display-6 text-premium"><?php echo (int) $account['total_orders']; ?></strong><p class="mb-0 text-light">Orders</p></div></div>
                    <div class="col-md-4"><div class="interaction-card text-center p-4"><strong class="display-6 text-premium"><?php echo (int) $account['total_tickets']; ?></strong><p class="mb-0 text-light">Event Tickets</p></div></div>
                    <div class="col-md-4"><div class="interaction-card text-center p-4"><strong class="display-6 text-premium"><?php echo (int) $account['total_reviews']; ?></strong><p class="mb-0 text-light">Reviews</p></div></div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
