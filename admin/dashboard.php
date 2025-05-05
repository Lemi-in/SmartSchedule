<?php
include './includes/auth.php';
include './includes/header.php';
require '../db.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$dept_count = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$teacher_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'")->fetch_assoc()['count'];
$student_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student'")->fetch_assoc()['count'];
$schedule_count = $conn->query("SELECT COUNT(*) as count FROM schedules")->fetch_assoc()['count'];
?>

<div class="container py-4">
    

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white text-center">
                <div class="card-body">
                    <h5>Departments</h5>
                    <p class="h3"><?php echo $dept_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white text-center">
                <div class="card-body">
                    <h5>Teachers</h5>
                    <p class="h3"><?php echo $teacher_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white text-center">
                <div class="card-body">
                    <h5>Students</h5>
                    <p class="h3"><?php echo $student_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-danger text-white text-center">
                <div class="card-body">
                    <h5>Schedules</h5>
                    <p class="h3"><?php echo $schedule_count; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="create_schedule.php" class="btn btn-primary me-2">Create New Schedule</a>
        <a href="view_calendar.php" class="btn btn-success">View Calendar</a>
    </div>
</div>

<?php include './includes/footer.php'; ?>
