<?php
include('../includes/auth.php'); // Ensure only teachers can access
include('../includes/db_connection.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Reschedule</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <?php include('../includes/header.php'); ?>
    <h2>Request Class Reschedule</h2>
    <form action="submit_request.php" method="POST">
        <label>Date:</label>
        <input type="date" name="new_date" required>
        <label>Reason:</label>
        <textarea name="reason" required></textarea>
        <button type="submit">Submit Request</button>
    </form>
    <?php include('../includes/footer.php'); ?>
</body>
</html>
