<?php
session_start();
include "config/db.php";

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$email) {
        $error = "Please enter a valid email address.";
    } elseif (empty($password)) {
        $error = "Password cannot be empty.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM Users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($exists) {
            $error = "An account with this email address already exists.";
        } else {
            // Begin transaction to ensure both User and Customer records are created
            mysqli_begin_transaction($conn);

            try {
                // Insert into Users table
                $stmt = mysqli_prepare($conn, "INSERT INTO Users (email, password) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, "ss", $email, $password);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error creating user account.");
                }
                
                $user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                // Insert into Customer table (needed for order and ticket bindings)
                $cust_stmt = mysqli_prepare($conn, "INSERT INTO Customer (user_id, loyalty_pts) VALUES (?, 0)");
                mysqli_stmt_bind_param($cust_stmt, "i", $user_id);
                
                if (!mysqli_stmt_execute($cust_stmt)) {
                    throw new Exception("Error creating customer profile.");
                }
                
                mysqli_stmt_close($cust_stmt);

                // Commit transaction
                mysqli_commit($conn);

                // Log the user in automatically
                $_SESSION['user_id'] = $user_id;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'customer';
                $_SESSION['success'] = "Account registered successfully! Welcome to Stuart's Comic Shop.";

                header("Location: index.php");
                exit();

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Stuart's Comic Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .register-card {
            background: var(--ink-soft);
            border: 1px solid var(--border-dark);
            border-radius: 0.5rem;
            max-width: 480px;
            margin: 4rem auto;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .form-control {
            background-color: rgba(13, 12, 10, 0.6);
            border: 1px solid var(--border-dark);
            color: var(--paper);
        }
        .form-control:focus {
            background-color: rgba(13, 12, 10, 0.8);
            border-color: var(--gold);
            color: var(--paper);
            box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.25);
        }
        .form-label {
            color: var(--paper);
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="site-shell">
    <nav class="navbar navbar-expand-lg site-nav" data-bs-theme="dark">
        <div class="content-width">
            <a class="navbar-brand brand-lockup" href="index.php">
                <span class="brand-main">STUART'S COMIC SHOP</span>
                <span class="brand-sub">Comics · Collectibles · Community</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="index.php">Home</a>
                    <a class="nav-link" href="products.php">Products</a>
                    <a class="nav-link" href="events.php">Events</a>
                    <a class="nav-link" href="cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                    <a class="nav-link" href="login.php">Login</a>
                    <a class="nav-link active" href="register.php">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="site-main">
        <div class="content-width">
            <div class="register-card">
                <div class="text-center mb-4">
                    <p class="eyebrow">Authentication</p>
                    <h1 class="h3 text-light">Create Customer Account</h1>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert" style="background-color: rgba(128, 0, 32, 0.2); border-color: var(--burgundy); color: #ffccd5;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="register.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Create password">
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    </div>
                    <button type="submit" class="btn btn-premium w-100 mb-3">Sign Up</button>
                    <div class="text-center mt-3">
                        <span class="text-muted small">Already have an account? <a href="login.php" style="color: var(--gold); text-decoration: underline;">Login here</a></span>
                    </div>
                </form>
            </div>
        </div>
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
