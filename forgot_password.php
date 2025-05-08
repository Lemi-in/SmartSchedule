<?php
require 'db.php';
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(50)); 

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();

        $message = "Reset link: <a href='reset_password.php?token=$token'>Click here</a>";
    } else {
        $message = "Email not found.";
    }
}
?>

<h3>Forgot Password</h3>
<form method="POST">
    <label>Enter your email:</label>
    <input type="email" name="email" required>
    <button type="submit">Send Reset Link</button>
</form>
<p><?= $message ?></p>
