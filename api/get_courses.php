<?php
require '../db.php';

header('Content-Type: text/html');

if (!isset($_GET['department_id'])) {
    echo '<option value="">Invalid Request</option>';
    exit;
}

$deptId = (int)$_GET['department_id'];
$stmt = $conn->prepare("SELECT course_id, code, title FROM courses WHERE department_id = ?");
$stmt->execute([$deptId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($courses)) {
    echo '<option value="">No courses found</option>';
    exit;
}

$html = '<option value="">Select Course</option>';
foreach ($courses as $course) {
    $html .= sprintf('<option value="%d">%s - %s</option>',
        $course['course_id'],
        htmlspecialchars($course['code']),
        htmlspecialchars($course['title'])
    );
}
echo $html;
?>