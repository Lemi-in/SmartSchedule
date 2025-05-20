<?php
// include './includes/auth.php';
session_start();

include './includes/header.php';
require '../db.php'; // Make sure this file initializes a PDO connection

// if ($_SESSION['role'] != 'student') {
//     header('Location: ../login_admin.php');
//     exit();
// }

$user_id = $_SESSION['user_id'];

try {
    // Get student basic info
    $stmt = $pdo->prepare("
        SELECT u.first_name, u.last_name, u.email, s.admission_number,
               sec.name AS section, d.name AS department, d.id AS department_id
        FROM users u
        JOIN students s ON u.user_id = s.student_id
        JOIN sections sec ON s.section_id = sec.section_id
        JOIN departments d ON sec.department_id = d.department_id
        WHERE u.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get current schedules
    $stmt = $pdo->prepare("
        SELECT sc.*, c.title AS course_name, cr.building, cr.room_number,
               ts.start_time, ts.end_time, u.first_name AS teacher_first, u.last_name AS teacher_last
        FROM schedules sc
        JOIN courses c ON sc.course_id = c.course_id
        JOIN classrooms cr ON sc.room_id = cr.room_id
        JOIN time_slots ts ON sc.slot_id = ts.slot_id
        JOIN teachers t ON sc.teacher_id = t.teacher_id
        JOIN users u ON t.teacher_id = u.user_id
        WHERE sc.section_id = (SELECT section_id FROM students WHERE student_id = :user_id)
        AND sc.academic_year = YEAR(CURDATE())
        AND sc.semester = IF(MONTH(CURDATE()) BETWEEN 2 AND 6, 2, 1)
        ORDER BY sc.day_of_week, ts.start_time
    ");
    $stmt->execute([':user_id' => $user_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get today's schedule
    $stmt = $pdo->prepare("
        SELECT sc.*, c.title AS course_name, cr.building, cr.room_number,
               ts.start_time, ts.end_time, u.first_name AS teacher_first, u.last_name AS teacher_last
        FROM schedules sc
        JOIN courses c ON sc.course_id = c.course_id
        JOIN classrooms cr ON sc.room_id = cr.room_id
        JOIN time_slots ts ON sc.slot_id = ts.slot_id
        JOIN teachers t ON sc.teacher_id = t.teacher_id
        JOIN users u ON t.teacher_id = u.user_id
        WHERE sc.section_id = (SELECT section_id FROM students WHERE student_id = :user_id)
        AND sc.day_of_week = DAYOFWEEK(CURDATE()) - 1
        AND sc.academic_year = YEAR(CURDATE())
        AND sc.semester = IF(MONTH(CURDATE()) BETWEEN 2 AND 6, 2, 1)
        ORDER BY ts.start_time
    ");
    $stmt->execute([':user_id' => $user_id]);
    $today_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get upcoming exams
    $stmt = $pdo->prepare("
        SELECT sc.*, c.title AS course_name, cr.building, cr.room_number,
               ts.start_time, ts.end_time, u.first_name AS teacher_first, u.last_name AS teacher_last
        FROM schedules sc
        JOIN courses c ON sc.course_id = c.course_id
        JOIN classrooms cr ON sc.room_id = cr.room_id
        JOIN time_slots ts ON sc.slot_id = ts.slot_id
        JOIN teachers t ON sc.teacher_id = t.teacher_id
        JOIN users u ON t.teacher_id = u.user_id
        WHERE sc.section_id = (SELECT section_id FROM students WHERE student_id = :user_id)
        AND sc.class_type = 'exam'
        AND (sc.day_of_week > DAYOFWEEK(CURDATE()) - 1 OR
            (sc.day_of_week = DAYOFWEEK(CURDATE()) - 1 AND ts.start_time > CURTIME()))
        AND sc.academic_year = YEAR(CURDATE())
        ORDER BY sc.day_of_week, ts.start_time
        LIMIT 3
    ");
    $stmt->execute([':user_id' => $user_id]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get registered courses
    $stmt = $pdo->prepare("
        SELECT c.code, c.title, c.credit_hours,
               CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
        FROM teacher_courses tc
        JOIN courses c ON tc.course_id = c.course_id
        JOIN teachers t ON tc.teacher_id = t.teacher_id
        JOIN users u ON t.teacher_id = u.user_id
        JOIN sections sec ON sec.department_id = c.department_id
        JOIN students s ON sec.section_id = s.section_id
        WHERE s.student_id = :user_id
        AND tc.academic_year = YEAR(CURDATE())
    ");
    $stmt->execute([':user_id' => $user_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all time slots for weekly schedule
    $time_slots = $pdo->query("SELECT * FROM time_slots ORDER BY start_time")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database errors
    die("Database error: " . $e->getMessage());
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">🎓 Student Dashboard</h3>
        <div class="text-muted">Welcome back, <?php echo htmlspecialchars($student_data['first_name']); ?>!</div>
    </div>

    <div class="row mb-4">
        <!-- Student Info Card -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title">📝 Student Information</h5>
                    <div class="mb-3">
                        <small class="text-muted">Full Name</small>
                        <p class="mb-0"><?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Admission Number</small>
                        <p class="mb-0"><?php echo htmlspecialchars($student_data['admission_number']); ?></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Email</small>
                        <p class="mb-0"><?php echo htmlspecialchars($student_data['email']); ?></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Department</small>
                        <p class="mb-0"><?php echo htmlspecialchars($student_data['department']); ?></p>
                    </div>
                    <div>
                        <small class="text-muted">Section</small>
                        <p class="mb-0"><?php echo htmlspecialchars($student_data['section']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Schedule Card -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title">📅 Today's Schedule</h5>
                    <?php if (count($today_schedule) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($today_schedule as $today): ?>
                                <div class="list-group-item border-0 py-2 px-0">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo htmlspecialchars($today['course_name']); ?></strong>
                                        <span class="badge bg-primary"><?php echo substr($today['start_time'], 0, 5); ?></span>
                                    </div>
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($today['building'] . ' ' . $today['room_number']); ?>
                                    </small>
                                    <small class="text-muted">
                                        With <?php echo htmlspecialchars($today['teacher_first'] . ' ' . $today['teacher_last']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-calendar-check fs-1"></i>
                            <p class="mb-0">No classes scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Exams Card -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title">📝 Upcoming Exams</h5>
                    <?php if (count($exams) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($exams as $exam): ?>
                                <div class="list-group-item border-0 py-2 px-0">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo htmlspecialchars($exam['course_name']); ?></strong>
                                        <span class="badge bg-danger"><?php echo date('D', strtotime('Sunday +' . $exam['day_of_week'] . ' days')); ?></span>
                                    </div>
                                    <small class="text-muted d-block">
                                        <?php echo substr($exam['start_time'], 0, 5) . ' - ' . substr($exam['end_time'], 0, 5); ?>
                                    </small>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($exam['building'] . ' ' . $exam['room_number']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-emoji-smile fs-1"></i>
                            <p class="mb-0">No upcoming exams</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Schedule -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">🗓️ Weekly Schedule</h5>
                <a href="../admin/view_calendar.php" class="btn btn-sm btn-outline-primary">View Full Calendar</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Time/Day</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                            <th>Saturday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($time_slots as $slot): ?>
                            <tr>
                                <td class="text-nowrap"><?php echo substr($slot['start_time'], 0, 5) . ' - ' . substr($slot['end_time'], 0, 5); ?></td>

                                <?php for ($day = 1; $day <= 6; $day++): ?>
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT c.title, cr.building, cr.room_number,
                                               CONCAT(u.first_name, ' ', LEFT(u.last_name, 1), '.') AS teacher
                                        FROM schedules sc
                                        JOIN courses c ON sc.course_id = c.course_id
                                        JOIN classrooms cr ON sc.room_id = cr.room_id
                                        JOIN teachers t ON sc.teacher_id = t.teacher_id
                                        JOIN users u ON t.teacher_id = u.user_id
                                        WHERE sc.section_id = (SELECT section_id FROM students WHERE student_id = :user_id)
                                        AND sc.slot_id = :slot_id
                                        AND sc.day_of_week = :day
                                        AND sc.academic_year = YEAR(CURDATE())
                                        AND sc.semester = IF(MONTH(CURDATE()) BETWEEN 2 AND 6, 2, 1)
                                    ");
                                    $stmt->execute([
                                        ':user_id' => $user_id,
                                        ':slot_id' => $slot['slot_id'],
                                        ':day' => $day
                                    ]);
                                    $class = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>

                                    <td>
                                        <?php if ($class): ?>
                                            <div class="p-2 bg-light rounded">
                                                <strong><?php echo htmlspecialchars($class['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($class['building'] . ' ' . $class['room_number']); ?></small><br>
                                                <small><?php echo htmlspecialchars($class['teacher']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Registered Courses -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title mb-3">📚 Registered Courses</h5>

            <?php if (count($courses) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Credit Hours</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo htmlspecialchars($course['credit_hours']); ?></td>
                                    <td><?php echo htmlspecialchars($course['teacher_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-book fs-1"></i>
                    <p class="mb-0">No courses registered for this semester</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include './includes/footer.php'; ?>