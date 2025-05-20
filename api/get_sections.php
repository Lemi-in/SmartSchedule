<?php
require '../db.php';

header('Content-Type: text/html');

if (!isset($_GET['department_id'])) {
    echo '<option value="">Invalid Request</option>';
    exit;
}

$deptId = (int)$_GET['department_id'];
$stmt = $conn->prepare("SELECT section_id, name FROM sections WHERE department_id = ?");
$stmt->execute([$deptId]);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($sections)) {
    echo '<option value="">No sections found</option>';
    exit;
}

$html = '<option value="">Select Section</option>';
foreach ($sections as $section) {
    $html .= sprintf('<option value="%d">%s</option>',
        $section['section_id'],
        htmlspecialchars($section['name'])
    );
}
echo $html;
?>