<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to book a ticket.";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: events.php");
    exit();
}

$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$seat_no = trim($_POST['seat_no'] ?? '');

if (!$event_id || strlen($seat_no) > 10) {
    $_SESSION['error'] = "Invalid ticket booking request.";
    header("Location: events.php");
    exit();
}

$sql = "SELECT e.event_id, e.name, e.date, e.location, e.max_seats,
               COUNT(t.ticket_id) AS tickets_sold
        FROM Events e
        LEFT JOIN Tickets t ON t.event_id = e.event_id
        WHERE e.event_id = ?
        GROUP BY e.event_id, e.name, e.date, e.location, e.max_seats";
$statement = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($statement, "i", $event_id);
mysqli_stmt_execute($statement);
$event = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));

if (!$event) {
    $_SESSION['error'] = "Event not found.";
    header("Location: events.php");
    exit();
}

$available_seats = (int) $event['max_seats'] - (int) $event['tickets_sold'];
if ($available_seats <= 0) {
    $_SESSION['error'] = "This event is fully booked.";
    header("Location: events.php");
    exit();
}

if ($seat_no !== '') {
    $seat_statement = mysqli_prepare($conn, "SELECT COUNT(*) AS seat_taken FROM Tickets WHERE event_id = ? AND seat_no = ?");
    mysqli_stmt_bind_param($seat_statement, "is", $event_id, $seat_no);
    mysqli_stmt_execute($seat_statement);
    $seat_taken = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($seat_statement))['seat_taken'] > 0;
    if ($seat_taken) {
        $_SESSION['error'] = "That preferred seat is already booked. Please choose another seat.";
        header("Location: events.php");
        exit();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm Ticket - Stuart's Comic Shop</title>
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="products.php">Products</a>
                <a class="nav-link active" href="events.php">Events</a>
                <a class="nav-link" href="customer/account.php">My Account</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="site-main">
        <header class="page-header">
            <div class="content-width">
                <p class="eyebrow">Final step</p>
                <h1 class="page-title">Confirm Your Ticket</h1>
                <p class="page-intro mb-0">Review the event details before booking.</p>
            </div>
        </header>

        <section class="section-space">
            <div class="content-width" style="max-width: 780px;">
                <article class="order-card">
                    <header class="order-header">
                        <div>
                            <p class="eyebrow mb-2">Event ticket</p>
                            <h2 class="h3 mb-0"><?php echo htmlspecialchars($event['name']); ?></h2>
                        </div>
                        <span class="stock-badge stock-in"><?php echo $available_seats; ?> Seats Left</span>
                    </header>
                    <div class="order-body">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <span class="order-meta-label">Date</span>
                                <strong><?php echo htmlspecialchars($event['date']); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="order-meta-label">Location</span>
                                <strong><?php echo htmlspecialchars($event['location']); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="order-meta-label">Seat</span>
                                <strong><?php echo $seat_no === '' ? 'Automatically assigned' : htmlspecialchars($seat_no); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="order-meta-label">Customer</span>
                                <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong>
                            </div>
                        </div>

                        <div class="verified-notice mb-4">
                            Your ticket has not been booked yet. Availability will be checked again when you confirm.
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <a class="btn btn-outline-premium flex-fill" href="events.php">Cancel</a>
                            <form class="flex-fill" action="buy-ticket.php" method="post">
                                <input type="hidden" name="event_id" value="<?php echo (int) $event['event_id']; ?>">
                                <input type="hidden" name="seat_no" value="<?php echo htmlspecialchars($seat_no); ?>">
                                <button class="btn btn-premium w-100" type="submit">Confirm Booking</button>
                            </form>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </main>
</div>
</body>
</html>
