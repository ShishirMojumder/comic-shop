<?php
session_start();
include "config/db.php";

$user_id = 1; // TEMPORARY DEMO USER FOR CSE370 PRESENTATION

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: products.php");
    exit();
}

$shipping_address = filter_input(INPUT_POST, 'shipping_address', FILTER_DEFAULT);
$shipping_address = $shipping_address !== null ? trim($shipping_address) : '';
$use_loyalty = filter_input(INPUT_POST, 'use_loyalty', FILTER_VALIDATE_INT);

if ($shipping_address === '') {
    $_SESSION['error'] = "Shipping address is required.";
    header("Location: checkout.php");
    exit();
}

// begin transaction
mysqli_begin_transaction($conn);

try {
    // calculate totals and check stock
    $cart_items = [];
    $grand_total = 0;
    
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $sql = "SELECT product_id, name, price, stock FROM Products WHERE product_id IN ($placeholders) FOR UPDATE";
    $stmt = mysqli_prepare($conn, $sql);
    $types = str_repeat('i', count($ids));
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($res)) {
        $pid = $row['product_id'];
        $qty = $_SESSION['cart'][$pid];
        
        if ($qty > $row['stock']) {
            throw new Exception("Insufficient stock for " . $row['name'] . ". Available: " . $row['stock']);
        }
        
        $row['quantity'] = $qty;
        $row['line_total'] = $row['price'] * $qty;
        $grand_total += $row['line_total'];
        $cart_items[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    // get user loyalty points
    $stmt = mysqli_prepare($conn, "SELECT loyalty_pts FROM Customer WHERE user_id = ? FOR UPDATE");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $customer = mysqli_fetch_assoc($res);
    $loyalty_pts = $customer ? (int)$customer['loyalty_pts'] : 0;
    mysqli_stmt_close($stmt);
    
    $loyalty_discount_value = 0.10;
    $max_points_usable = min($loyalty_pts, floor($grand_total / $loyalty_discount_value));
    $loyalty_discount = $max_points_usable * $loyalty_discount_value;
    
    // insert order
    $stmt = mysqli_prepare($conn, "INSERT INTO Orders (user_id, Date, Status, Shipping_address) VALUES (?, CURRENT_DATE, 'PENDING', ?)");
    mysqli_stmt_bind_param($stmt, "is", $user_id, $shipping_address);
    mysqli_stmt_execute($stmt);
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // create order items and reduce stock
    $item_no = 1;
    $insert_item_sql = "INSERT INTO Order_Items (Order_id, item_no, product_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
    $update_stock_sql = "UPDATE Products SET stock = stock - ? WHERE product_id = ?";
    
    foreach ($cart_items as $item) {
        $stmt = mysqli_prepare($conn, $insert_item_sql);
        mysqli_stmt_bind_param($stmt, "iiiid", $order_id, $item_no, $item['product_id'], $item['quantity'], $item['price']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        $stmt = mysqli_prepare($conn, $update_stock_sql);
        mysqli_stmt_bind_param($stmt, "ii", $item['quantity'], $item['product_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        $item_no++;
    }
    
    // deduct points if checked
    if ($use_loyalty && $max_points_usable > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE Customer SET loyalty_pts = loyalty_pts - ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $max_points_usable, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // commit
    mysqli_commit($conn);
    
    // reset cart
    $_SESSION['cart'] = [];
    $_SESSION['success'] = "Order placed successfully! Order ID: #" . $order_id;
    header("Location: customer/order-history.php");
    exit();
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Failed to place order: " . $e->getMessage();
    header("Location: checkout.php");
    exit();
}
