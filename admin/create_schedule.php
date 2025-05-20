<?php
session_start();
require '../db.php';
require './includes/header.php';

// Verify admin role
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

$msg = ''; // Initialize message variable

// Get initial data for dropdowns
$departments = $conn->query("SELECT department_id, name FROM departments")->fetchAll(PDO::FETCH_ASSOC);
$days = [
    ['id' => 1, 'name' => 'Monday'],
    ['id' => 2, 'name' => 'Tuesday'],
    ['id' => 3, 'name' => 'Wednesday'],
    ['id' => 4, 'name' => 'Thursday'],
    ['id' => 5, 'name' => 'Friday'],
    ['id' => 6, 'name' => 'Saturday']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input
        $department_id = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $section_id = filter_input(INPUT_POST, 'section_id', FILTER_VALIDATE_INT);
        $teacher_id = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
        $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
        $slot_id = filter_input(INPUT_POST, 'slot_id', FILTER_VALIDATE_INT);
        $day_of_week = filter_input(INPUT_POST, 'day_of_week', FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 6]]);
        $class_type = in_array($_POST['class_type'], ['lecture', 'exam']) ? $_POST['class_type'] : 'lecture';
        $academic_year = date('Y');
        $semester = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 2]]);

        // Check for conflicts
        $conflict_check = $conn->prepare("SELECT 1 FROM schedules
                                         WHERE (teacher_id = ? AND day_of_week = ? AND slot_id = ? AND academic_year = ? AND semester = ?)
                                         OR (section_id = ? AND day_of_week = ? AND slot_id = ? AND academic_year = ? AND semester = ?)
                                         OR (room_id = ? AND day_of_week = ? AND slot_id = ? AND academic_year = ? AND semester = ?)");
        $conflict_check->execute([
            $teacher_id, $day_of_week, $slot_id, $academic_year, $semester,
            $section_id, $day_of_week, $slot_id, $academic_year, $semester,
            $room_id, $day_of_week, $slot_id, $academic_year, $semester
        ]);

        if ($conflict_check->fetch()) {
            $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        Schedule conflict detected! Please choose different time, teacher, or room.
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
        } else {
            // Insert new schedule
            $stmt = $conn->prepare("INSERT INTO schedules
                                   (section_id, course_id, teacher_id, slot_id, room_id, class_type,
                                    day_of_week, academic_year, semester, created_by)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $section_id, $course_id, $teacher_id, $slot_id, $room_id, $class_type,
                $day_of_week, $academic_year, $semester, $_SESSION['user_id']
            ]);

            $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        Schedule created successfully!
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
        }
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    Error: " . htmlspecialchars($e->getMessage()) . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
}

// Get all time slots for JavaScript
$all_time_slots = $conn->query("SELECT slot_id, name, start_time, end_time FROM time_slots")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Increased margin to mt-5 (48px) -->
<div class="container py-4 mt-5">
    <div class="dashboard-card">
        <div class="card-header">
            <h3 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Create New Schedule</h3>
        </div>
        <div class="card-body">
            <?php echo $msg; ?>

            <form method="post" class="row g-3" id="scheduleForm">
                <!-- Department Selection -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Department</label>
                    <select name="department_id" id="department_id" class="form-select" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['department_id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Course Selection (dynamic) -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Course</label>
                    <select name="course_id" id="course_id" class="form-select" required disabled>
                        <option value="">Select Department First</option>
                    </select>
                </div>

                <!-- Section Selection (dynamic) -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Section</label>
                    <select name="section_id" id="section_id" class="form-select" required disabled>
                        <option value="">Select Department First</option>
                    </select>
                </div>

                <!-- Day of Week -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Day of Week</label>
                    <select name="day_of_week" id="day_of_week" class="form-select" required>
                        <option value="">Select Day</option>
                        <?php foreach ($days as $day): ?>
                        <option value="<?= $day['id'] ?>"><?= htmlspecialchars($day['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Time Slot Selection (dynamic) -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Time Slot</label>
                    <select name="slot_id" id="slot_id" class="form-select" required disabled>
                        <option value="">Select Day First</option>
                    </select>
                </div>

                <!-- Teacher Selection (dynamic) -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Teacher</label>
                    <select name="teacher_id" id="teacher_id" class="form-select" required disabled>
                        <option value="">Select Course First</option>
                    </select>
                </div>

                <!-- Room Selection (dynamic) -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Room</label>
                    <select name="room_id" id="room_id" class="form-select" required>
                        <option value="">Select Room</option>
                        <?php
                        $rooms = $conn->query("SELECT room_id, building, room_number FROM classrooms")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($rooms as $room):
                        ?>
                        <option value="<?= $room['room_id'] ?>">
                            <?= htmlspecialchars($room['building']) ?> - <?= htmlspecialchars($room['room_number']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Class Type -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Class Type</label>
                    <select name="class_type" class="form-select" required>
                        <option value="lecture">Lecture</option>
                        <option value="exam">Exam</option>
                    </select>
                </div>

                <!-- Semester -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-primary">Semester</label>
                    <select name="semester" class="form-select" required>
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                    </select>
                </div>

                <div class="col-12 text-end mt-4">
                    <a href="dashboard.php" class="btn btn-secondary me-2">
                        <i class="bi bi-arrow-left me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Create Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for dynamic dropdowns -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Store all time slots for filtering
    const timeSlots = <?= json_encode($all_time_slots) ?>;

    // Department changed - load courses and sections
    $('#department_id').change(function() {
        const deptId = $(this).val();
        if (!deptId) {
            $('#course_id, #section_id').prop('disabled', true)
                .html('<option value="">Select Department First</option>');
            return;
        }

        // Load courses
        $.get('../api/get_courses.php?department_id=' + deptId)
            .done(function(data) {
                $('#course_id').prop('disabled', false).html(data);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error("Courses error:", textStatus, errorThrown);
                $('#course_id').html('<option value="">Error loading courses</option>');
            });

        // Load sections
        $.get('../api/get_sections.php?department_id=' + deptId)
            .done(function(data) {
                $('#section_id').prop('disabled', false).html(data);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error("Sections error:", textStatus, errorThrown);
                $('#section_id').html('<option value="">Error loading sections</option>');
            });
    });

    // Course changed - load teachers for this course
    $('#course_id').change(function() {
        const courseId = $(this).val();
        if (!courseId) {
            $('#teacher_id').prop('disabled', true).html('<option value="">Select Course First</option>');
            return;
        }

        $.get('../api/get_teachers.php?course_id=' + courseId, function(data) {
            $('#teacher_id').prop('disabled', false).html(data);
        });
    });

    // Day changed - filter time slots
    $('#day_of_week').change(function() {
        const day = $(this).val();
        if (!day) {
            $('#slot_id').prop('disabled', true).html('<option value="">Select Day First</option>');
            return;
        }

        let options = '<option value="">Select Time Slot</option>';
        timeSlots.forEach(slot => {
            options += `<option value="${slot.slot_id}">${slot.name} (${formatTime(slot.start_time)} - ${formatTime(slot.end_time)})</option>`;
        });
        $('#slot_id').prop('disabled', false).html(options);
    });

    function formatTime(timeString) {
        const time = new Date('1970-01-01T' + timeString + 'Z');
        return time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
});
</script>

<?php include '../includes/footer.php'; ?>