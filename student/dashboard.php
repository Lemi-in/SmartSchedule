<div class="text-end"><a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a></div><hr><?php
include '../includes/auth.php';
include '../includes/header.php';
require '../db.php';

if ($_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['id'];

// Get the student's section and department
$user_result = $conn->query("SELECT section, department_id FROM users WHERE id = $user_id");
$user_data = $user_result->fetch_assoc();
$section = $user_data['section'];
$department_id = $user_data['department_id'];

// Get relevant schedules
$schedules = $conn->query("SELECT * FROM schedules WHERE section = '$section' AND department_id = $department_id ORDER BY start_time ASC");

?>

<h3 class="mb-4">Student Dashboard</h3>

<div class="card mb-4">
    <div class="card-body">
        <h5>Your Section</h5>
        <p><?php echo $section; ?></p>
    </div>
</div>

<h5 class="mb-3">Upcoming Schedules</h5>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Course</th>
            <th>Type</th>
            <th>Start</th>
            <th>End</th>
            <th>Room</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $schedules->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['course_name']; ?></td>
            <td><?php echo ucfirst($row['schedule_type']); ?></td>
            <td><?php echo $row['start_time']; ?></td>
            <td><?php echo $row['end_time']; ?></td>
            <td><?php echo $row['room']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<a href="../admin/view_calendar.php" class="btn btn-primary">View Calendar</a>

<?php include '../includes/footer.php'; ?>
