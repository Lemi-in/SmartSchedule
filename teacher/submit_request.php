<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../index.php");
    exit();
}

require '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$schedule_id = $_POST['schedule_id'] ?? null;
$requested_day = $_POST['requested_day'] ?? null;
$requested_slot_id = $_POST['requested_slot_id'] ?? null;
$requested_room_id = $_POST['requested_room_id'] ?? null;
$reason = $_POST['reason'] ?? null;

// Validate inputs
if (!$schedule_id || !$requested_day || !$requested_slot_id || !$requested_room_id || !$reason) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: dashboard.php");
    exit();
}

try {
    // Insert the change request
    $stmt = $pdo->prepare("
        INSERT INTO schedule_changes (
            schedule_id, requested_by, requested_slot_id,
            requested_room_id, requested_day, reason
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $schedule_id, $teacher_id, $requested_slot_id,
        $requested_room_id, $requested_day, $reason
    ]);

    $_SESSION['success'] = "Schedule change request submitted successfully!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error submitting request: " . $e->getMessage();
}

header("Location: dashboard.php");
exit();
?>