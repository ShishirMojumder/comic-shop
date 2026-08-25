<?php
session_start();
include "config/db.php";

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $password = $_POST['password'] ?? '';

    if (!$email || empty($password)) {
        $error = "Please provide both a valid email and password.";
    } else {
        // Query user details
        $stmt = mysqli_prepare($conn, "SELECT user_id, email, password FROM Users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($user) {
            // Verify password (supports both plain text for demo seeds and optional bcrypt hashing)
            $is_valid = false;
            if ($user['password'] === $password) {
                $is_valid = true;
            } elseif (password_verify($password, $user['password'])) {
                $is_valid = true;
            }

            if ($is_valid) {
                // Check if user is Admin
                $admin_stmt = mysqli_prepare($conn, "SELECT role FROM Admin WHERE user_id = ?");
                mysqli_stmt_bind_param($admin_stmt, "i", $user['user_id']);
                mysqli_stmt_execute($admin_stmt);
                $admin_res = mysqli_stmt_get_result($admin_stmt);
                $admin = mysqli_fetch_assoc($admin_res);
                mysqli_stmt_close($admin_stmt);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];

                if ($admin) {
                    $_SESSION['role'] = 'admin';
                    $_SESSION['success'] = "Logged in successfully as Administrator.";
                    header("Location: admin/dashboard.php");
                } else {
                    $_SESSION['role'] = 'customer';
                    $_SESSION['success'] = "Logged in successfully.";
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Stuart's Comic Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .login-card {
            background: var(--ink-soft);
            border: 1px solid var(--border-dark);
            border-radius: 0.5rem;
            max-width: 450px;
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
                    <a class="nav-link active" href="login.php">Login</a>
                    <a class="nav-link" href="register.php">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="site-main">
        <div class="content-width">
            <div class="login-card">
                <div class="text-center mb-4">
                    <p class="eyebrow">Authentication</p>
                    <h1 class="h3 text-light">Login to Account</h1>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert" style="background-color: rgba(128, 0, 32, 0.2); border-color: var(--burgundy); color: #ffccd5;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" role="alert" style="background-color: rgba(46, 125, 50, 0.2); border-color: var(--success); color: #d1e7dd;">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Enter password">
                    </div>
                    <button type="submit" class="btn btn-premium w-100 mb-3">Sign In</button>
                    <div class="text-center mt-3">
                        <span class="text-muted small">Don't have an account? <a href="register.php" style="color: var(--gold); text-decoration: underline;">Register here</a></span>
                    </div>
                </form>
            </div>
        </div>
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
