<?php
session_start();
include "config/db.php";

$action = filter_input(INPUT_POST, 'action', FILTER_DEFAULT);
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$action) {
    $action = filter_input(INPUT_GET, 'action', FILTER_DEFAULT);
    $product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
}

if (!$action) {
    header("Location: products.php");
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($action === 'add' && $product_id) {
    $qty = $quantity ? (int)$quantity : 1;
    if ($qty < 1) {
        $qty = 1;
    }
    
    // Check product stock first
    $stmt = mysqli_prepare($conn, "SELECT stock FROM Products WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($product) {
        $current_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
        $new_qty = $current_qty + $qty;
        
        if ($new_qty > $product['stock']) {
            $_SESSION['error'] = "Cannot add more items. Only " . $product['stock'] . " items available.";
        } else {
            $_SESSION['cart'][$product_id] = $new_qty;
            $_SESSION['success'] = "Added to cart successfully.";
        }
    } else {
        $_SESSION['error'] = "Product not found.";
    }
} elseif ($action === 'update' && $product_id && $quantity !== false && $quantity !== null) {
    $qty = (int)$quantity;
    if ($qty < 1) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success'] = "Item removed from cart.";
    } else {
        // Check product stock
        $stmt = mysqli_prepare($conn, "SELECT stock FROM Products WHERE product_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($product) {
            if ($qty > $product['stock']) {
                $_SESSION['error'] = "Cannot update quantity. Only " . $product['stock'] . " items available.";
            } else {
                $_SESSION['cart'][$product_id] = $qty;
                $_SESSION['success'] = "Cart updated successfully.";
            }
        }
    }
} elseif ($action === 'remove' && $product_id) {
    unset($_SESSION['cart'][$product_id]);
    $_SESSION['success'] = "Item removed from cart.";
} elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
}

header("Location: cart.php");
exit();
