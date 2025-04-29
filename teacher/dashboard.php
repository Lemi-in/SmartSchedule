<?php
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

<div class="container py-4">

    <!-- Logout button -->
    <div class="d-flex justify-content-end mb-3">
        <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>

    <!-- Page title -->
    <h3 class="mb-4">👨‍🏫 Teacher Dashboard</h3>

    <!-- Sections card -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title">Your Sections</h5>
            <p class="card-text"><?php echo implode(', ', $sections); ?></p>
        </div>
    </div>

    <!-- Schedules table -->
    <h5 class="mb-3">Your Schedules</h5>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
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
    </div>

    <!-- Action buttons -->
    <div class="mt-3">
        <a href="reschedule.php" class="btn btn-warning me-2">Reschedule</a>
        <a href="../admin/view_calendar.php" class="btn btn-primary">View Calendar</a>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
