<?php
include './includes/auth.php';
include './includes/header.php';
require '../db.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['id'];


if ($role === 'admin') {
    $sql = "SELECT * FROM schedules";
} elseif ($role === 'teacher') {
    $sql = "SELECT s.* FROM schedules s WHERE s.section IN (
        SELECT section FROM teacher_sections WHERE teacher_id = $user_id
    )";
} elseif ($role === 'student') {
    $user_data = $conn->query("SELECT section, department_id FROM users WHERE id = $user_id")->fetch_assoc();
    $section = $user_data['section'];
    $department_id = $user_data['department_id'];
    $sql = "SELECT * FROM schedules WHERE section = '$section' AND department_id = $department_id";
} else {
    echo "Access denied.";
    exit();
}

$result = $conn->query($sql);
$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = [
        "title" => $row['course_name'] . " (" . ucfirst($row['schedule_type']) . ")",
        "start" => $row['start_time'],
        "end"   => $row['end_time'],
        "color" => $row['schedule_type'] === 'test' ? '#f44336' :
                   ($row['schedule_type'] === 'assignment' ? '#ff9800' : '#2196f3')
    ];
}
?>

<div class="text-end mb-3">
    <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.js'></script>

<div id='calendar'></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        events: <?php echo json_encode($schedules); ?>,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        }
    });
    calendar.render();
});
</script>

<?php include './includes/footer.php'; ?>
