<?php

include "config/db.php";

$sql = "SHOW TABLES";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Could not list database tables: " . mysqli_error($conn));
}
?>

<h1>Database Connection Successful</h1>

<?php while ($row = mysqli_fetch_row($result)): ?>
    <p><?php echo htmlspecialchars($row[0]); ?></p>
<?php endwhile; ?>
