<?php
session_start();
require 'db.php'; // This defines $conn

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $hashedPassword = md5($password);

    try {
        $stmt = $conn->prepare("SELECT user_id, role_id, username, password_hash, first_name, last_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $hashedPassword === $user['password_hash']) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];

            // Redirect based on role
            switch ($user['role_id']) {
                case 1:
                    header("Location: admin/dashboard.php");
                    break;
                case 2:
                    header("Location: teacher/dashboard.php");
                    break;
                case 3:
                    header("Location: student/dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid email or password!";
        }

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <h3 class="text-center">University Scheduling System</h3>
    <?php if (isset($error)): ?>
        <div class='alert alert-danger'><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    <div class="mt-3 text-center">
        <a href="forgot-password.php">Forgot password?</a>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
