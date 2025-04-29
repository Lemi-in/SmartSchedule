<?php
include '../includes/auth.php';
include '../includes/header.php';
require '../db.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = $_POST['course_name'];
    $section = $_POST['section'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room = $_POST['room'];
    $schedule_type = $_POST['schedule_type'];

    $department_id = 1; 
    $scheduled_by = $_SESSION['id'];

    $sql = "INSERT INTO schedules (course_name, department_id, section, scheduled_by, schedule_type, start_time, end_time, room)
            VALUES ('$course_name', '$department_id', '$section', '$scheduled_by', '$schedule_type', '$start_time', '$end_time', '$room')";

    if ($conn->query($sql)) {
        echo "<div class='alert alert-success'>Schedule created successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error creating schedule: " . $conn->error . "</div>";
    }
}
?>

<h3>Create New Schedule</h3>
<form method="post" class="row g-3">
  <div class="col-md-6">
    <label>Course Name</label>
    <input type="text" name="course_name" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label>Section</label>
    <input type="text" name="section" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label>Start Time</label>
    <input type="datetime-local" name="start_time" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label>End Time</label>
    <input type="datetime-local" name="end_time" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label>Room</label>
    <input type="text" name="room" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label>Schedule Type</label>
    <select name="schedule_type" class="form-select" required>
        <option value="course">Course</option>
        <option value="test">Test</option>
        <option value="assignment">Assignment</option>
    </select>
  </div>
  <div class="col-12">
    <button class="btn btn-success">Create Schedule</button>
  </div>
</form>

<?php include '../includes/footer.php'; ?>
