<?php
// view_section_schedule.php
require '../config.php';
require '../includes/header.php';

// Database Functions
function getDepartments($pdo) {
    return $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

function getSections($pdo, $departmentId) {
    $stmt = $pdo->prepare("SELECT * FROM sections WHERE department_id = ? ORDER BY name");
    $stmt->execute([$departmentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSectionSchedule($pdo, $sectionId) {
    $currentYear = date('Y');
    $stmt = $pdo->prepare("
        SELECT
            sc.schedule_id,
            sc.day_of_week,
            c.code as course_code,
            c.title as course_title,
            CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
            cl.building,
            cl.room_number,
            ts.name as time_slot,
            TIME_FORMAT(ts.start_time, '%h:%i %p') as start_time,
            TIME_FORMAT(ts.end_time, '%h:%i %p') as end_time
        FROM schedules sc
        JOIN courses c ON sc.course_id = c.course_id
        JOIN teachers t ON sc.teacher_id = t.teacher_id
        JOIN users u ON t.teacher_id = u.user_id
        JOIN classrooms cl ON sc.room_id = cl.room_id
        JOIN time_slots ts ON sc.slot_id = ts.slot_id
        WHERE sc.section_id = ?
        AND sc.academic_year = ?
        ORDER BY sc.day_of_week, ts.start_time
    ");
    $stmt->execute([$sectionId, $currentYear]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSectionInfo($pdo, $sectionId) {
    $stmt = $pdo->prepare("
        SELECT s.name as section_name, d.name as department_name, d.code as department_code
        FROM sections s
        JOIN departments d ON s.department_id = d.department_id
        WHERE s.section_id = ?
    ");
    $stmt->execute([$sectionId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Main Logic
$departments = getDepartments($pdo);
$selectedDepartment = $_GET['department'] ?? null;
$selectedSection = $_GET['section'] ?? null;
$sections = [];
$scheduleData = [];
$sectionInfo = null;
$error = '';

if ($selectedDepartment) {
    $sections = getSections($pdo, $selectedDepartment);

    if ($selectedSection) {
        $sectionInfo = getSectionInfo($pdo, $selectedSection);
        $scheduleData = getSectionSchedule($pdo, $selectedSection);

        if (empty($scheduleData)) {
            $error = "No schedule found for selected section in current academic year";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Section Schedule - University Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Your existing dashboard CSS variables */
        :root {
            --color-primary: #6f6af8;
            --color-primary-light: hsla(242, 91%, 69%, 0.18);
            --color-primary-variant: #5854c7;
            --color-red: #da0f3f;
            --color-red-light: hsla(346, 87%, 46%, 0.15);
            --color-green: #00c476;
            --color-green-light: hsla(156, 100%, 38%, 0.15);
            --color-orange: #ff7b00;
            --color-orange-light: hsla(28, 100%, 50%, 0.15);
            --color-gray-900: #1e1e66;
            --color-gray-700: #2d2b7c;
            --color-gray-300: rgba(0, 0, 0, 0.08);
            --color-gray-200: rgba(0, 0, 0, 0.05);
            --color-white: #ffffff;
            --color-bg: #fffddd;
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
            background-color: var(--color-white);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
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

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }

        .btn-primary:hover {
            background-color: var(--color-primary-variant);
            border-color: var(--color-primary-variant);
        }

        .search-panel {
            background-color: var(--color-white);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .schedule-container {
            background-color: var(--color-white);
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .schedule-day {
            border-bottom: 1px solid var(--color-gray-300);
            padding: 1rem;
        }

        .day-header {
            background-color: var(--color-primary-light);
            color: var(--color-primary);
            padding: 0.75rem 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            border-radius: 8px;
        }

        .schedule-item {
            border-left: 4px solid var(--color-primary);
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: var(--color-primary-light);
            border-radius: 8px;
            transition: all 0.2s;
        }

        .schedule-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .course-code {
            font-weight: 600;
            color: var(--color-gray-900);
            margin-bottom: 0.5rem;
        }

        .empty-day {
            color: var(--color-gray-700);
            text-align: center;
            padding: 1.5rem;
            font-style: italic;
        }

        .info-header {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .table-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 1rem;
            padding-top: 1rem;
            border-bottom: 2px solid var(--color-primary-light);
            padding-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .schedule-day {
                padding: 0.75rem;
            }

            .search-panel {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Added mt-5 class for margin between nav and content -->
    <div class="container py-4 mt-5">
        <!-- Header Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="mb-0"><i class="bi bi-calendar-week me-2"></i> Section Schedule Viewer</h3>
            </div>
            <div class="card-body">
                <!-- Search Panel -->
                <div class="search-panel">
                    <form method="get" action="" class="row g-3">
                        <!-- Department Selection -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-primary">Department</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id'] ?>"
                                        <?= $selectedDepartment == $dept['department_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['name']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Section Selection -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-primary">Section</label>
                            <select class="form-select" id="section" name="section" <?= empty($sections) ? 'disabled' : '' ?> required>
                                <option value="">-- Select Section --</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= $section['section_id'] ?>"
                                        <?= $selectedSection == $section['section_id'] ? 'selected' : '' ?>>
                                        Section <?= htmlspecialchars($section['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-1"></i> View
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Error Messages -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Schedule Display -->
                <?php if (!empty($scheduleData)): ?>
                    <!-- Information Header -->
                    <div class="info-header">
                        <h4 class="mb-0">
                            <i class="bi bi-calendar-check me-2"></i>
                            Schedule for Section <?= htmlspecialchars($sectionInfo['section_name']) ?>
                            - <?= htmlspecialchars($sectionInfo['department_name']) ?>
                            <small class="float-end">Academic Year: <?= date('Y') ?></small>
                        </h4>
                    </div>

                    <!-- Schedule View -->
                    <div class="schedule-container">
                        <?php
                        $days = [
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday'
                        ];

                        foreach ($days as $dayNum => $dayName):
                            $dayClasses = array_filter($scheduleData, function($class) use ($dayNum) {
                                return $class['day_of_week'] == $dayNum;
                            });
                        ?>
                            <div class="schedule-day">
                                <div class="day-header">
                                    <i class="bi bi-calendar-day me-2"></i>
                                    <?= $dayName ?>
                                </div>

                                <?php if (!empty($dayClasses)): ?>
                                    <?php foreach ($dayClasses as $class): ?>
                                        <div class="schedule-item">
                                            <div class="course-code">
                                                <?= htmlspecialchars($class['course_code']) ?>:
                                                <?= htmlspecialchars($class['course_title']) ?>
                                            </div>
                                            <div class="text-muted small mb-2">
                                                <i class="bi bi-clock"></i>
                                                <?= htmlspecialchars($class['time_slot']) ?>
                                                (<?= $class['start_time'] ?> - <?= $class['end_time'] ?>)
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($class['teacher_name']) ?>
                                                </div>
                                                <div>
                                                    <i class="bi bi-building"></i>
                                                    <?= htmlspecialchars($class['building'] . ' ' . $class['room_number']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-day">
                                        <i class="bi bi-calendar-x" style="font-size: 1.5rem;"></i>
                                        <div>No classes scheduled</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($selectedSection && empty($error)): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="bi bi-info-circle me-2"></i>
                        No schedule data found for the selected section
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($selectedDepartment && !$selectedSection): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Please select a section to view schedule
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="bi bi-info-circle me-2"></i>
                        Please select a department and section to begin
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable section dropdown when department is selected
        document.getElementById('department').addEventListener('change', function() {
            if (this.value) {
                this.form.submit();
            }
        });

        // Auto-focus section dropdown when enabled
        document.addEventListener('DOMContentLoaded', function() {
            const sectionSelect = document.getElementById('section');
            if (!sectionSelect.disabled) {
                sectionSelect.focus();
            }
        });
    </script>
</body>
</html>