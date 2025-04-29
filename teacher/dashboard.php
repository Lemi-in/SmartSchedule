<div class="text-end"><a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a></div><hr><?php
include '../includes/auth.php';
include '../includes/header.php';
require '../db.php';

if ($_SESSION['role'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['id'];

// Fetch teacher sections
$sections_result = $conn->query("SELECT section FROM teacher_sections WHERE teacher_id = $user_id");
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
    $sections[] = $row['section'];
}
$section_list = implode("', '", $sections);

// Fetch schedules
$schedule_result = $conn->query("SELECT * FROM schedules WHERE section IN ('$section_list') ORDER BY start_time ASC");
?>

<h3 class="mb-4">Teacher Dashboard</h3>

<div class="card mb-3">
    <div class="card-body">
        <h5>Your Sections</h5>
        <p><?php echo implode(', ', $sections); ?></p>
    </div>
</div>

<h5 class="mb-3">Your Schedules</h5>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Course</th>
            <th>Section</th>
            <th>Type</th>
            <th>Start</th>
            <th>End</th>
            <th>Room</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $schedule_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['course_name']; ?></td>
            <td><?php echo $row['section']; ?></td>
            <td><?php echo ucfirst($row['schedule_type']); ?></td>
            <td><?php echo $row['start_time']; ?></td>
            <td><?php echo $row['end_time']; ?></td>
            <td><?php echo $row['room']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<a href="reschedule.php" class="btn btn-warning">Reschedule</a>
<a href="../admin/view_calendar.php" class="btn btn-primary">View Calendar</a>

<?php include '../includes/footer.php'; ?>
