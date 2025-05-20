<?php
session_start();

// Check if user is logged in and is a teacher (role_id = 2)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../index.php");
    exit();
}

require '../db.php'; // This gives us $pdo connection

$teacher_id = $_SESSION['user_id'];
$current_year = date('Y');

// Get teacher information
$stmt = $pdo->prepare("
    SELECT u.*, t.qualification, t.hire_date
    FROM users u
    JOIN teachers t ON u.user_id = t.teacher_id
    WHERE u.user_id = ?
");
$stmt->execute([$teacher_id]);
$teacher_info = $stmt->fetch();

// Get assigned courses for current academic year
$stmt = $pdo->prepare("
    SELECT c.course_id, c.code, c.title, c.credit_hours, d.name AS department
    FROM teacher_courses tc
    JOIN courses c ON tc.course_id = c.course_id
    JOIN departments d ON c.department_id = d.department_id
    WHERE tc.teacher_id = ? AND tc.academic_year = ?
");
$stmt->execute([$teacher_id, $current_year]);
$assigned_courses = $stmt->fetchAll();

// Get upcoming classes (next 7 days)
$current_day_of_week = date('N'); // 1-7 (Monday-Sunday)
$next_week_day = ($current_day_of_week + 6) > 6 ? 6 : ($current_day_of_week + 6);

$stmt = $pdo->prepare("
    SELECT s.schedule_id, s.day_of_week, s.class_type, s.semester,
           ts.name AS time_slot, ts.start_time, ts.end_time,
           cr.building, cr.room_number,
           c.code AS course_code, c.title AS course_title,
           sec.name AS section_name, d.name AS department_name
    FROM schedules s
    JOIN time_slots ts ON s.slot_id = ts.slot_id
    JOIN classrooms cr ON s.room_id = cr.room_id
    JOIN courses c ON s.course_id = c.course_id
    JOIN sections sec ON s.section_id = sec.section_id
    JOIN departments d ON sec.department_id = d.department_id
    WHERE s.teacher_id = ?
    AND s.academic_year = ?
    AND s.day_of_week BETWEEN ? AND ?
    ORDER BY s.day_of_week, ts.start_time
");
$stmt->execute([
    $teacher_id,
    $current_year,
    $current_day_of_week,
    $next_week_day
]);
$upcoming_classes = $stmt->fetchAll();

// Get schedule change requests
$stmt = $pdo->prepare("
    SELECT sc.*,
           s.day_of_week AS original_day,
           ts.name AS original_slot_name,
           ts.start_time AS original_start_time,
           ts.end_time AS original_end_time,
           cr.building AS original_building,
           cr.room_number AS original_room,
           c.code AS course_code,
           c.title AS course_title,
           rts.name AS requested_slot_name,
           rts.start_time AS requested_start_time,
           rts.end_time AS requested_end_time,
           rcr.building AS requested_building,
           rcr.room_number AS requested_room
    FROM schedule_changes sc
    JOIN schedules s ON sc.schedule_id = s.schedule_id
    JOIN time_slots ts ON s.slot_id = ts.slot_id
    JOIN classrooms cr ON s.room_id = cr.room_id
    JOIN courses c ON s.course_id = c.course_id
    JOIN time_slots rts ON sc.requested_slot_id = rts.slot_id
    JOIN classrooms rcr ON sc.requested_room_id = rcr.room_id
    WHERE sc.requested_by = ?
    ORDER BY sc.created_at DESC
");
$stmt->execute([$teacher_id]);
$change_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - University Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            /* Primary Colors */
            --color-primary: #6f6af8; /* Soft violet */
            --color-primary-light: hsla(242, 91%, 69%, 0.18);
            --color-primary-variant: #5854c7;

            /* Accent Colors */
            --color-red: #da0f3f;
            --color-red-light: hsla(346, 87%, 46%, 0.15);
            --color-green: #00c476;
            --color-green-light: hsla(156, 100%, 38%, 0.15);

            /* Grays and Background */
            --color-gray-900: #1e1e66;   /* Dark text */
            --color-gray-700: #2d2b7c;   /* For headings or accents */
            --color-gray-300: rgba(0, 0, 0, 0.08);  /* light borders or backgrounds */
            --color-gray-200: rgba(0, 0, 0, 0.05);  /* even lighter accents */
            --color-white: #ffffff;
            --color-bg: #fffddd; /* Ivory background */
        }

        body {
            background-color: var(--color-bg);
            color: var(--color-gray-900);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: var(--color-white);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background-color: var(--color-primary);
            color: var(--color-white);
            border-radius: 12px 12px 0 0 !important;
            padding: 1rem 1.5rem;
            font-weight: 600;
            border-bottom: none;
        }

        .day-label {
            font-weight: bold;
            color: var(--color-primary-variant);
        }

        .badge-lecture {
            background-color: var(--color-green);
            color: white;
        }

        .badge-exam {
            background-color: var(--color-red);
            color: white;
        }

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }

        .btn-primary:hover {
            background-color: var(--color-primary-variant);
            border-color: var(--color-primary-variant);
        }

        .table th {
            background-color: var(--color-primary-light);
            color: var(--color-gray-700);
        }

        .list-group-item {
            border-left: 3px solid var(--color-primary);
            margin-bottom: 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .list-group-item:hover {
            border-left-width: 6px;
            background-color: var(--color-primary-light);
        }

        .text-primary {
            color: var(--color-primary) !important;
        }

        .bg-primary-light {
            background-color: var(--color-primary-light);
        }

        .modal-header {
            background-color: var(--color-primary);
            color: var(--color-white);
        }

        .nav-pills .nav-link.active {
            background-color: var(--color-primary);
        }

        @media (max-width: 768px) {
            .dashboard-card {
                margin-bottom: 16px;
            }

            .card-header h5 {
                font-size: 1.1rem;
            }

            .table-responsive {
                overflow-x: auto;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--color-gray-200);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--color-primary);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--color-primary-variant);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="text-primary mb-1">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></h2>
                        <p class="text-muted mb-0">Teacher Dashboard | <?= date('F j, Y') ?></p>
                    </div>
                    <div class="d-none d-md-block">
                        <span class="badge bg-primary-light text-primary p-2">
                            <i class="bi bi-person-badge me-1"></i> Teacher
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Upcoming Classes -->
            <div class="col-lg-6">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Upcoming Classes (Next 7 Days)</h5>
                        <span class="badge bg-white text-primary">
                            <?= count($upcoming_classes) ?> classes
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_classes)): ?>
                            <div class="alert alert-info bg-primary-light border-primary">
                                <i class="bi bi-calendar-x me-2"></i> No upcoming classes scheduled.
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcoming_classes as $class): ?>
                                    <div class="list-group-item mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <span class="text-primary"><?= htmlspecialchars($class['course_code']) ?></span> -
                                                    <?= htmlspecialchars($class['course_title']) ?>
                                                </h6>
                                                <div class="day-label mb-1">
                                                    <?= getDayName($class['day_of_week']) ?>
                                                    (<?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?>)
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-building me-1"></i>
                                                        <?= htmlspecialchars($class['building'] . ' ' . $class['room_number']) ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-people me-1"></i>
                                                        <?= htmlspecialchars($class['department_name'] . ' - Sec ' . $class['section_name']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <span class="badge <?= $class['class_type'] == 'lecture' ? 'badge-lecture' : 'badge-exam' ?>">
                                                <?= ucfirst($class['class_type']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Assigned Courses -->
            <div class="col-lg-6">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">My Courses (<?= $current_year ?>)</h5>
                        <span class="badge bg-white text-primary">
                            <?= count($assigned_courses) ?> courses
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assigned_courses)): ?>
                            <div class="alert alert-warning bg-primary-light border-primary">
                                <i class="bi bi-book me-2"></i> No courses assigned for this academic year.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th class="text-primary">Code</th>
                                            <th class="text-primary">Title</th>
                                            <th class="text-primary">Credits</th>
                                            <th class="text-primary">Department</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assigned_courses as $course): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold text-primary"><?= htmlspecialchars($course['code']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($course['title']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary-light text-primary">
                                                        <?= $course['credit_hours'] ?> CR
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($course['department']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Change Requests -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">My Schedule Change Requests</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#requestModal">
                            <i class="bi bi-plus-circle me-1"></i> New Request
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($change_requests)): ?>
                            <div class="alert alert-info bg-primary-light border-primary">
                                <i class="bi bi-info-circle me-2"></i> You haven't made any schedule change requests.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th class="text-primary">Course</th>
                                            <th class="text-primary">Original Schedule</th>
                                            <th class="text-primary">Requested Schedule</th>
                                            <th class="text-primary">Status</th>
                                            <th class="text-primary">Reason</th>
                                            <th class="text-primary">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($change_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <strong class="text-primary"><?= htmlspecialchars($request['course_code']) ?></strong><br>
                                                    <?= htmlspecialchars($request['course_title']) ?>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?= getDayName($request['original_day']) ?><br>
                                                        <?= htmlspecialchars($request['original_slot_name']) ?>
                                                        (<?= date('g:i A', strtotime($request['original_start_time'])) ?>-<?= date('g:i A', strtotime($request['original_end_time'])) ?>)<br>
                                                        <?= htmlspecialchars($request['original_building'] . ' ' . $request['original_room']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?= getDayName($request['requested_day']) ?><br>
                                                        <?= htmlspecialchars($request['requested_slot_name']) ?>
                                                        (<?= date('g:i A', strtotime($request['requested_start_time'])) ?>-<?= date('g:i A', strtotime($request['requested_end_time'])) ?>)<br>
                                                        <?= htmlspecialchars($request['requested_building'] . ' ' . $request['requested_room']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    if ($request['status'] == 'approved') {
                                                        $status_class = 'text-success';
                                                        $icon = 'bi-check-circle';
                                                    } elseif ($request['status'] == 'rejected') {
                                                        $status_class = 'text-danger';
                                                        $icon = 'bi-x-circle';
                                                    } else {
                                                        $status_class = 'text-warning';
                                                        $icon = 'bi-hourglass';
                                                    }
                                                    ?>
                                                    <span class="<?= $status_class ?>">
                                                        <i class="bi <?= $icon ?> me-1"></i>
                                                        <?= ucfirst($request['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?= htmlspecialchars($request['reason']) ?></small>
                                                </td>
                                                <td>
                                                    <small><?= date('M j, Y', strtotime($request['created_at'])) ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teacher Information -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">My Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="text-primary mb-3"><i class="bi bi-person-lines-fill me-2"></i>Personal Details</h6>
                                    <p><strong>Name:</strong> <?= htmlspecialchars($teacher_info['first_name'] . ' ' . $teacher_info['last_name']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($teacher_info['email']) ?></p>
                                    <p><strong>Phone:</strong> <?= htmlspecialchars($teacher_info['phone'] ?? 'Not provided') ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="text-primary mb-3"><i class="bi bi-award me-2"></i>Professional Details</h6>
                                    <p><strong>Qualification:</strong> <?= htmlspecialchars($teacher_info['qualification']) ?></p>
                                    <p><strong>Hire Date:</strong> <?= date('M j, Y', strtotime($teacher_info['hire_date'])) ?></p>
                                    <p><strong>Username:</strong> <?= htmlspecialchars($teacher_info['username']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Change Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestModalLabel">
                        <i class="bi bi-calendar2-event me-2"></i> New Schedule Change Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="submit_request.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="schedule_id" class="form-label text-primary">Select Class to Reschedule</label>
                            <select class="form-select" id="schedule_id" name="schedule_id" required>
                                <option value="">-- Select Class --</option>
                                <?php
                                // Get all teacher's schedules for current academic year
                                $stmt = $pdo->prepare("
                                    SELECT s.schedule_id,
                                           c.code AS course_code, c.title AS course_title,
                                           ts.name AS slot_name, ts.start_time, ts.end_time,
                                           cr.building, cr.room_number,
                                           sec.name AS section_name, d.name AS department_name,
                                           s.day_of_week
                                    FROM schedules s
                                    JOIN courses c ON s.course_id = c.course_id
                                    JOIN time_slots ts ON s.slot_id = ts.slot_id
                                    JOIN classrooms cr ON s.room_id = cr.room_id
                                    JOIN sections sec ON s.section_id = sec.section_id
                                    JOIN departments d ON sec.department_id = d.department_id
                                    WHERE s.teacher_id = ? AND s.academic_year = ?
                                    ORDER BY s.day_of_week, ts.start_time
                                ");
                                $stmt->execute([$teacher_id, $current_year]);
                                $schedules = $stmt->fetchAll();

                                foreach ($schedules as $schedule):
                                ?>
                                    <option value="<?= $schedule['schedule_id'] ?>">
                                        <?= htmlspecialchars($schedule['course_code'] . ' - ' . $schedule['course_title']) ?> |
                                        <?= getDayName($schedule['day_of_week']) ?>
                                        (<?= date('g:i A', strtotime($schedule['start_time'])) ?>-<?= date('g:i A', strtotime($schedule['end_time'])) ?>) |
                                        <?= htmlspecialchars($schedule['building'] . ' ' . $schedule['room_number']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="requested_day" class="form-label text-primary">New Day</label>
                                    <select class="form-select" id="requested_day" name="requested_day" required>
                                        <option value="">-- Select Day --</option>
                                        <option value="1">Monday</option>
                                        <option value="2">Tuesday</option>
                                        <option value="3">Wednesday</option>
                                        <option value="4">Thursday</option>
                                        <option value="5">Friday</option>
                                        <option value="6">Saturday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="requested_slot_id" class="form-label text-primary">New Time Slot</label>
                                    <select class="form-select" id="requested_slot_id" name="requested_slot_id" required>
                                        <option value="">-- Select Time Slot --</option>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM time_slots ORDER BY start_time");
                                        while ($slot = $stmt->fetch()):
                                        ?>
                                            <option value="<?= $slot['slot_id'] ?>">
                                                <?= htmlspecialchars($slot['name']) ?>
                                                (<?= date('g:i A', strtotime($slot['start_time'])) ?>-<?= date('g:i A', strtotime($slot['end_time'])) ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="requested_room_id" class="form-label text-primary">New Classroom</label>
                                    <select class="form-select" id="requested_room_id" name="requested_room_id" required>
                                        <option value="">-- Select Classroom --</option>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM classrooms ORDER BY building, room_number");
                                        while ($room = $stmt->fetch()):
                                        ?>
                                            <option value="<?= $room['room_id'] ?>">
                                                <?= htmlspecialchars($room['building'] . ' ' . $room['room_number']) ?>
                                                (Capacity: <?= $room['capacity'] ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label text-primary">Reason for Change</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Helper function to convert day number to name
function getDayName($dayNumber) {
    $days = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday'
    ];
    return $days[$dayNumber] ?? 'Unknown';
}
?>