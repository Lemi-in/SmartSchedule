<?php
require '../db.php';

header('Content-Type: text/html');

if (!isset($_GET['course_id'])) {
    echo '<option value="">Invalid Request</option>';
    exit;
}

$courseId = (int)$_GET['course_id'];
$currentYear = date('Y');

$stmt = $conn->prepare("SELECT t.teacher_id, u.first_name, u.last_name
                       FROM teacher_courses tc
                       JOIN teachers t ON tc.teacher_id = t.teacher_id
                       JOIN users u ON t.teacher_id = u.user_id
                       WHERE tc.course_id = ? AND tc.academic_year = ?");
$stmt->execute([$courseId, $currentYear]);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($teachers)) {
    echo '<option value="">No teachers assigned to this course</option>';
    exit;
}

$html = '<option value="">Select Teacher</option>';
foreach ($teachers as $teacher) {
    $html .= sprintf('<option value="%d">%s %s</option>',
        $teacher['teacher_id'],
        htmlspecialchars($teacher['first_name']),
        htmlspecialchars($teacher['last_name'])
    );
}
echo $html;
?>