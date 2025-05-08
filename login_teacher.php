<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];
  
  $sql = "SELECT * FROM users WHERE email = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
          $_SESSION['id'] = $user['id'];
          $_SESSION['role'] = $user['role'];
          header("Location: {$user['role']}/dashboard.php");
          exit();
      } else {
          $error = "Invalid credentials!";
      }
  } else {
      $error = "Invalid credentials!";
  }
  
}

include 'includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <h3 class="text-center">Login</h3>
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <form method="post">
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-primary w-100">Login</button>
    </form>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
