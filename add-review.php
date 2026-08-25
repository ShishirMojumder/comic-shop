<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to submit a review.");
}
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

$product_id = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, "rating", FILTER_VALIDATE_INT);
$comment = trim($_POST["comment"] ?? "");

if (!$product_id || $product_id < 1) {
    die("Invalid product ID.");
}

if ($rating === false || $rating < 1 || $rating > 5) {
    die("Rating must be between 1 and 5.");
}

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

if (!(bool) $eligibility["has_purchased"]) {
    die("Only customers who purchased this product can review it.");
}

$insert_sql = "INSERT INTO Reviews
                (user_id, product_id, rating, comment)
                VALUES (?, ?, ?, ?)";

$insert_statement = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param($insert_statement, "iiis", $user_id, $product_id, $rating, $comment);

if (!mysqli_stmt_execute($insert_statement)) {
    die("Could not add review: " . mysqli_stmt_error($insert_statement));
}

header("Location: product.php?id=" . $product_id);
exit;
