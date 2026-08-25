<?php
session_start();
include __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to join the loyalty quiz.";
    header("Location: ../login.php");
    exit();
}

if (($_SESSION['role'] ?? 'customer') === 'admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$eligibility_sql = "SELECT EXISTS(
                        SELECT 1 FROM Orders
                        WHERE user_id = ?
                    ) AS eligible";
$statement = mysqli_prepare($conn, $eligibility_sql);
mysqli_stmt_bind_param($statement, "i", $user_id);
mysqli_stmt_execute($statement);
$eligible = (bool) mysqli_fetch_assoc(mysqli_stmt_get_result($statement))['eligible'];
mysqli_stmt_close($statement);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
    $selected_option = strtoupper(trim($_POST['selected_option'] ?? ''));

    if (!$eligible) {
        $_SESSION['quiz_error'] = "You need at least one order to join the quiz.";
    } elseif (!$quiz_id || !in_array($selected_option, ['A', 'B', 'C', 'D'], true)) {
        $_SESSION['quiz_error'] = "Please select one answer.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $statement = mysqli_prepare($conn, "SELECT correct_option, reward_points FROM Quiz_Questions WHERE quiz_id = ? AND is_active = TRUE FOR UPDATE");
            mysqli_stmt_bind_param($statement, "i", $quiz_id);
            mysqli_stmt_execute($statement);
            $question = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
            mysqli_stmt_close($statement);

            if (!$question) {
                throw new Exception("Quiz question not found.");
            }

            $statement = mysqli_prepare($conn, "SELECT attempt_id FROM Quiz_Attempts WHERE user_id = ? AND quiz_id = ?");
            mysqli_stmt_bind_param($statement, "ii", $user_id, $quiz_id);
            mysqli_stmt_execute($statement);
            $already_attempted = mysqli_num_rows(mysqli_stmt_get_result($statement)) > 0;
            mysqli_stmt_close($statement);

            if ($already_attempted) {
                throw new Exception("You have already attempted this question.");
            }

            $is_correct = $selected_option === $question['correct_option'];
            $points_awarded = $is_correct ? (int) $question['reward_points'] : 0;

            $statement = mysqli_prepare($conn, "INSERT INTO Quiz_Attempts (user_id, quiz_id, selected_option, is_correct, points_awarded) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($statement, "iisii", $user_id, $quiz_id, $selected_option, $is_correct, $points_awarded);
            if (!mysqli_stmt_execute($statement)) {
                throw new Exception("Could not save quiz attempt.");
            }
            mysqli_stmt_close($statement);

            if ($points_awarded > 0) {
                $statement = mysqli_prepare($conn, "UPDATE Customer SET loyalty_pts = loyalty_pts + ? WHERE user_id = ?");
                mysqli_stmt_bind_param($statement, "ii", $points_awarded, $user_id);
                if (!mysqli_stmt_execute($statement)) {
                    throw new Exception("Could not award loyalty points.");
                }
                mysqli_stmt_close($statement);
            }

            mysqli_commit($conn);
            $_SESSION['quiz_success'] = $is_correct
                ? "Correct! You earned $points_awarded loyalty points."
                : "That answer was incorrect. Better luck on the next question!";
        } catch (Throwable $error) {
            mysqli_rollback($conn);
            $_SESSION['quiz_error'] = $error->getMessage();
        }
    }

    header("Location: quiz.php");
    exit();
}

$points_statement = mysqli_prepare($conn, "SELECT loyalty_pts FROM Customer WHERE user_id = ?");
mysqli_stmt_bind_param($points_statement, "i", $user_id);
mysqli_stmt_execute($points_statement);
$loyalty_points = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($points_statement))['loyalty_pts'];

$quiz_sql = "SELECT q.quiz_id, q.question, q.option_a, q.option_b, q.option_c, q.option_d,
                    q.reward_points, a.selected_option, a.is_correct, a.points_awarded
             FROM Quiz_Questions q
             LEFT JOIN Quiz_Attempts a ON a.quiz_id = q.quiz_id AND a.user_id = ?
             WHERE q.is_active = TRUE
             ORDER BY q.quiz_id";
$quiz_statement = mysqli_prepare($conn, $quiz_sql);
mysqli_stmt_bind_param($quiz_statement, "i", $user_id);
mysqli_stmt_execute($quiz_statement);
$quiz_result = mysqli_stmt_get_result($quiz_statement);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loyalty Quiz - Stuart's Comic Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="site-shell">
    <nav class="navbar navbar-expand-lg site-nav" data-bs-theme="dark">
        <div class="content-width">
            <a class="navbar-brand brand-lockup" href="../index.php"><span class="brand-main">STUART'S</span><span class="brand-sub">Comic Shop</span></a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Home</a>
                <a class="nav-link" href="../products.php">Products</a>
                <a class="nav-link" href="../events.php">Events</a>
                <a class="nav-link" href="../cart.php">Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                <a class="nav-link" href="order-history.php">My Orders</a>
                <a class="nav-link active" href="quiz.php">Loyalty Quiz</a>
                <a class="nav-link" href="account.php">My Account</a>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="site-main">
        <header class="page-header">
            <div class="content-width">
                <p class="eyebrow">Rewards challenge</p>
                <h1 class="page-title">Loyalty Quiz</h1>
                <p class="page-intro mb-0">Answer each question once and earn 10 points for every correct answer.</p>
            </div>
        </header>

        <section class="section-space">
            <div class="content-width">
                <div class="interaction-card mb-4 d-flex justify-content-between align-items-center">
                    <div><strong>Your balance</strong><p class="muted-text mb-0">Quiz rewards can be used during your next checkout.</p></div>
                    <strong class="h2 text-premium mb-0"><?php echo $loyalty_points; ?> points</strong>
                </div>

                <?php if (isset($_SESSION['quiz_success'])): ?><div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['quiz_success']); unset($_SESSION['quiz_success']); ?></div><?php endif; ?>
                <?php if (isset($_SESSION['quiz_error'])): ?><div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['quiz_error']); unset($_SESSION['quiz_error']); ?></div><?php endif; ?>

                <?php if (!$eligible): ?>
                    <div class="empty-state text-center"><h2 class="h4">Place an order to unlock the quiz</h2><p class="muted-text mb-3">Only customers who have ordered can participate.</p><a class="btn btn-premium" href="../products.php">Shop Comics</a></div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php $number = 1; while ($quiz = mysqli_fetch_assoc($quiz_result)): ?>
                            <div class="col-lg-6">
                                <article class="interaction-card h-100">
                                    <p class="eyebrow">Question <?php echo $number++; ?></p>
                                    <h2 class="h4"><?php echo htmlspecialchars($quiz['question']); ?></h2>
                                    <?php if ($quiz['selected_option'] !== null): ?>
                                        <p class="mt-3 mb-1"><strong>Your answer:</strong> <?php echo htmlspecialchars($quiz['selected_option']); ?></p>
                                        <span class="status-badge <?php echo $quiz['is_correct'] ? 'status-complete' : 'status-cancelled'; ?>"><?php echo $quiz['is_correct'] ? '+' . (int) $quiz['points_awarded'] . ' Points' : 'Attempted'; ?></span>
                                    <?php else: ?>
                                        <form method="post" action="quiz.php" class="mt-3">
                                            <input type="hidden" name="quiz_id" value="<?php echo (int) $quiz['quiz_id']; ?>">
                                            <?php foreach (['A', 'B', 'C', 'D'] as $option): $field = 'option_' . strtolower($option); ?>
                                                <label class="d-block mb-2"><input type="radio" name="selected_option" value="<?php echo $option; ?>" required> <strong><?php echo $option; ?>.</strong> <?php echo htmlspecialchars($quiz[$field]); ?></label>
                                            <?php endforeach; ?>
                                            <button class="btn btn-premium mt-2" type="submit">Submit Answer</button>
                                        </form>
                                    <?php endif; ?>
                                </article>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>
