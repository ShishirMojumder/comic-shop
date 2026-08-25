<?php
session_start();
include "config/db.php";

$user_id = 1; // TEMPORARY DEMO USER FOR CSE370 PRESENTATION

$sql = "SELECT e.event_id, e.name, e.date, e.location, e.max_seats,
               (SELECT COUNT(*) FROM Tickets t WHERE t.event_id = e.event_id) AS tickets_sold
        FROM Events e
        ORDER BY e.date ASC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Error loading events: " . mysqli_error($conn));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Events - Stuart's Comic Shop</title>
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
                    <a class="nav-link active" href="events.php">Events</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a class="nav-link" href="admin/dashboard.php">Admin</a>
                        <?php else: ?>
                            <a class="nav-link" href="cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                            <a class="nav-link" href="customer/order-history.php">My Orders</a>
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
                <p class="eyebrow">Community</p>
                <h1 class="page-title">Comic-Con Events</h1>
                <p class="page-intro mb-0">Join local comic book fans and book your entry tickets here.</p>
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

                <?php if (mysqli_num_rows($result) === 0): ?>
                    <div class="empty-state text-center">
                        <h2 class="h4">No upcoming events.</h2>
                        <p class="muted-text mb-0">Please check back later for new events.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php while ($event = mysqli_fetch_assoc($result)): 
                            $available_seats = $event['max_seats'] - $event['tickets_sold'];
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <article class="card product-card">
                                    <div class="card-body d-flex flex-column" style="min-height: 320px;">
                                        <div class="mb-3">
                                            <span class="stock-badge <?php echo $available_seats > 0 ? 'stock-in' : 'stock-out'; ?>">
                                                <?php echo $available_seats > 0 ? "$available_seats Seats Left" : "Sold Out"; ?>
                                            </span>
                                        </div>
                                        <h2 class="product-name h4 text-light"><?php echo htmlspecialchars($event['name']); ?></h2>
                                        <p class="text-muted small mb-2">📍 <?php echo htmlspecialchars($event['location']); ?></p>
                                        <p class="text-premium fw-semibold mb-4">📅 <?php echo htmlspecialchars($event['date']); ?></p>
                                        
                                        <div class="mt-auto">
                                             <?php if ($available_seats > 0): ?>
                                                 <?php if (isset($_SESSION['user_id'])): ?>
                                                     <form action="buy-ticket.php" method="post">
                                                         <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                                         <div class="mb-3">
                                                             <label for="seat_no_<?php echo $event['event_id']; ?>" class="form-label small text-light">Preferred Seat No.</label>
                                                             <input type="text" id="seat_no_<?php echo $event['event_id']; ?>" name="seat_no" class="form-control form-control-sm" placeholder="e.g. A-12" style="background-color: #2b1111; color: #fff; border: 1px solid #732222;" required>
                                                         </div>
                                                         <button type="submit" class="btn btn-premium w-100 btn-sm">Buy Ticket</button>
                                                     </form>
                                                 <?php else: ?>
                                                     <a href="login.php" class="btn btn-outline-premium w-100 btn-sm">Login to Buy Ticket</a>
                                                 <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-secondary w-100 btn-sm" disabled>Sold Out</button>
                                            <?php endif; ?>
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
