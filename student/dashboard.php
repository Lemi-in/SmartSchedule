<?php
include './includes/auth.php';
include './includes/header.php';
require '../db.php';

if ($_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['id'];

$user_result = $conn->query("SELECT section, department_id FROM users WHERE id = $user_id");
$user_data = $user_result->fetch_assoc();
$section = $user_data['section'];
$department_id = $user_data['department_id'];

$department_result = $conn->query("SELECT name FROM departments WHERE id = $department_id");
$department_data = $department_result->fetch_assoc();
$department_name = $department_data['name'];

$schedules = $conn->query("SELECT * FROM schedules WHERE section = '$section' AND department_id = $department_id ORDER BY start_time ASC");
?>

<div class="container py-4">


    <hr>

    <h3 class="mb-4">🎓 Student Dashboard</h3>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title">Your Department</h5>
            <p class="card-text text-muted fs-5"><?php echo $department_name; ?></p>
        </div>
    </div>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title">Your Section</h5>
            <p class="card-text text-muted fs-5"><?php echo $section; ?></p>
        </div>
    </div>

    <h5 class="mb-3">📅 Upcoming Schedules</h5>
    <div class="table-responsive mb-4">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-light">
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
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($row['schedule_type'])); ?></td>
                    <td><?php echo $row['start_time']; ?></td>
                    <td><?php echo $row['end_time']; ?></td>
                    <td><?php echo htmlspecialchars($row['room']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <a href="../admin/view_calendar.php" class="btn btn-primary">View Calendar</a>

</div>

<?php include './includes/footer.php'; ?>
