<?php
session_start();
require '../../db.php';
require './header.php';

// Verify admin role and check student_id exists
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1 || !isset($_GET['student_id'])) {
    header("Location: ../index.php");
    exit();
}

$student_id = intval($_GET['student_id']);

// Get student info
$stmt = $conn->prepare("SELECT u.first_name, u.last_name, sec.name as section_name, d.name as department_name
                        FROM users u
                        JOIN students st ON u.user_id = st.student_id
                        JOIN sections sec ON st.section_id = sec.section_id
                        JOIN departments d ON sec.department_id = d.department_id
                        WHERE u.user_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get student's section schedule
$stmt = $conn->prepare("SELECT s.day_of_week, ts.name as time_slot, ts.start_time, ts.end_time,
                               c.code as course_code, c.title as course_title,
                               u.first_name as teacher_first, u.last_name as teacher_last,
                               r.building, r.room_number, s.class_type
                        FROM schedules s
                        JOIN time_slots ts ON s.slot_id = ts.slot_id
                        JOIN courses c ON s.course_id = c.course_id
                        JOIN teachers t ON s.teacher_id = t.teacher_id
                        JOIN users u ON t.teacher_id = u.user_id
                        JOIN classrooms r ON s.room_id = r.room_id
                        WHERE s.section_id = (SELECT section_id FROM students WHERE student_id = ?)
                        ORDER BY s.day_of_week, ts.start_time");
$stmt->execute([$student_id]);
$schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="wrapper">
    <?php include '../includes/admin-sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>
                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>'s Schedule
                            <small><?= htmlspecialchars($student['department_name'] . ' - ' . $student['section_name']) ?></small>
                            <a href="dashboard.php" class="btn btn-secondary float-right">Back to Dashboard</a>
                        </h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Course</th>
                                    <th>Teacher</th>
                                    <th>Room</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                foreach ($schedule as $row):
                                ?>
                                <tr>
                                    <td><?= $days[$row['day_of_week']-1] ?></td>
                                    <td><?= date("g:i A", strtotime($row['start_time'])) ?> - <?= date("g:i A", strtotime($row['end_time'])) ?></td>
                                    <td><?= htmlspecialchars($row['course_code']) ?>: <?= htmlspecialchars($row['course_title']) ?></td>
                                    <td><?= htmlspecialchars($row['teacher_first'] . ' ' . $row['teacher_last']) ?></td>
                                    <td><?= htmlspecialchars($row['building']) ?> <?= htmlspecialchars($row['room_number']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($row['class_type'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include '../includes/footer.php'; ?>