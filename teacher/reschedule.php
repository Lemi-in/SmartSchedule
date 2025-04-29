<?php
include '../includes/auth.php';
include '../includes/header.php';
require '../db.php';

if ($_SESSION['role'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['id'];

// Fetch teacher's schedules
$schedules_result = $conn->query("SELECT * FROM schedules WHERE section IN (SELECT section FROM teacher_sections WHERE teacher_id = $user_id)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'];
    $new_start = $_POST['start_time'];
    $new_end = $_POST['end_time'];
    $new_room = $_POST['room'];

    // Fetch original schedule info
    $orig = $conn->query("SELECT section FROM schedules WHERE id = $schedule_id")->fetch_assoc();
    $section = $orig['section'];

    // Check for time/room conflicts in that section
    $conflict_sql = "SELECT * FROM schedules WHERE id != $schedule_id AND section = '$section'
        AND ((start_time <= '$new_start' AND end_time > '$new_start') OR
             (start_time < '$new_end' AND end_time >= '$new_end') OR
             ('$new_start' <= start_time AND '$new_end' >= end_time))
        AND room = '$new_room'";

    $conflicts = $conn->query($conflict_sql);

    if ($conflicts->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>Conflict detected with another schedule in this section or room.</div>";
    } else {
        $update_sql = "UPDATE schedules SET start_time = '$new_start', end_time = '$new_end', room = '$new_room' WHERE id = $schedule_id";
        if ($conn->query($update_sql)) {
            $msg = "<div class='alert alert-success'>Schedule updated successfully.</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Error updating schedule.</div>";
        }
    }
}
?>

<h3 class="mb-4">Reschedule Your Class/Test/Assignment</h3>
<?php if (isset($msg)) echo $msg; ?>

<form method="post" class="row g-3">
    <div class="col-md-12">
        <label for="schedule_id">Select Schedule</label>
        <select class="form-select" name="schedule_id" required>
            <option value="">Choose...</option>
            <?php while ($row = $schedules_result->fetch_assoc()): ?>
            <option value="<?php echo $row['id']; ?>">
                <?php echo $row['course_name'] . " (" . $row['schedule_type'] . ") on " . $row['start_time']; ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label>New Start Time</label>
        <input type="datetime-local" name="start_time" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label>New End Time</label>
        <input type="datetime-local" name="end_time" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label>New Room</label>
        <input type="text" name="room" class="form-control" required>
    </div>
    <div class="col-12">
        <button class="btn btn-warning">Update Schedule</button>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
