<?php include 'includes/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <h3 class="text-center">Choose Login Role</h3>
    <form method="post">
      <div class="mb-3">
        <label>Select Role</label>
        <select name="role" class="form-select" required>
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <button class="btn btn-primary w-100" name="go">Continue</button>
    </form>
  </div>
</div>
<?php
if (isset($_POST['go'])) {
    $role = $_POST['role'];
    header("Location: login_" . $role . ".php");
    exit();
}
include 'includes/footer.php';
?>