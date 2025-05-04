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

    $scheduled_by = $_SESSION['id'];

    $sql = "INSERT INTO schedules (course_name, department_id, section, scheduled_by, schedule_type, start_time, end_time, room)
            VALUES ('$course_name', '$department_id', '$section', '$scheduled_by', '$schedule_type', '$start_time', '$end_time', '$room')";

    if ($conn->query($sql)) {
        $msg = "<div class='alert alert-success'>Schedule created successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error creating schedule: " . $conn->error . "</div>";
    }
}
?>

<div class="container py-4">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="card-title mb-4">Create New Schedule</h3>

            <?php if (isset($msg)) echo $msg; ?>

            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Course Name</label>
                    <input type="text" name="course_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department ID</label>
                    <input type="text" name="department_id" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Start Time</label>
                    <input type="datetime-local" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Time</label>
                    <input type="datetime-local" name="end_time" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Room</label>
                    <input type="text" name="room" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Schedule Type</label>
                    <select name="schedule_type" class="form-select" required>
                        <option value="course">Lecture</option>
                        <option value="test">Test</option>
                        <option value="assignment">Assignment</option>
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button class="btn btn-success">Create Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
