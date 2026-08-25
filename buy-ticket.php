<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to book a ticket.";
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$seat_no = filter_input(INPUT_POST, 'seat_no', FILTER_DEFAULT);
$seat_no = $seat_no !== null ? trim($seat_no) : '';

if (!$event_id || $seat_no === '') {
    $_SESSION['error'] = "Invalid ticket booking request. Seat number cannot be empty.";
    header("Location: events.php");
    exit();
}

// begin transaction
mysqli_begin_transaction($conn);

try {
    // check event capacity
    $stmt = mysqli_prepare($conn, "SELECT max_seats, name FROM Events WHERE event_id = ? FOR UPDATE");
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$event) {
        throw new Exception("Event not found.");
    }

    // count sold tickets
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS sold FROM Tickets WHERE event_id = ? FOR UPDATE");
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $tickets = mysqli_fetch_assoc($res);
    $sold = (int)$tickets['sold'];
    mysqli_stmt_close($stmt);

    if ($sold >= $event['max_seats']) {
        throw new Exception("Event '" . $event['name'] . "' is fully booked.");
    }

    // check if seat is already taken
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS seat_taken FROM Tickets WHERE event_id = ? AND seat_no = ?");
    mysqli_stmt_bind_param($stmt, "is", $event_id, $seat_no);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $seat_check = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ((int)$seat_check['seat_taken'] > 0) {
        throw new Exception("Seat " . htmlspecialchars($seat_no) . " is already booked. Please choose another seat.");
    }

    // book the ticket
    $stmt = mysqli_prepare($conn, "INSERT INTO Tickets (user_id, seat_no, event_id) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isi", $user_id, $seat_no, $event_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // commit
    mysqli_commit($conn);
    $_SESSION['success'] = "Ticket booked successfully for " . htmlspecialchars($event['name']) . "! Seat: " . htmlspecialchars($seat_no);

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = $e->getMessage();
}

header("Location: events.php");
exit();
