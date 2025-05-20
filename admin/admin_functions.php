<?php
require '../db.php';

// Add Teacher
if (isset($_POST['add_teacher'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $qualification = $_POST['qualification'];

    try {
        $pdo->beginTransaction();

        // Add to users table
        $stmt = $pdo->prepare("INSERT INTO users (role_id, username, email, password_hash, first_name, last_name, is_active)
                              VALUES (2, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$username, $email, $password, $first_name, $last_name]);
        $teacher_id = $pdo->lastInsertId();

        // Add to teachers table
        $stmt = $pdo->prepare("INSERT INTO teachers (teacher_id, qualification, hire_date)
                              VALUES (?, ?, CURDATE())");
        $stmt->execute([$teacher_id, $qualification]);

        $pdo->commit();
        $_SESSION['success'] = "Teacher added successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error adding teacher: " . $e->getMessage();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Edit Teacher
if (isset($_POST['edit_teacher'])) {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $qualification = $_POST['qualification'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        $pdo->beginTransaction();

        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, is_active = ?
                              WHERE user_id = ?");
        $stmt->execute([$first_name, $last_name, $email, $is_active, $user_id]);

        // Update teachers table
        $stmt = $pdo->prepare("UPDATE teachers SET qualification = ? WHERE teacher_id = ?");
        $stmt->execute([$qualification, $user_id]);

        $pdo->commit();
        $_SESSION['success'] = "Teacher updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating teacher: " . $e->getMessage();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Delete Teacher
if (isset($_POST['delete_teacher'])) {
    $user_id = $_POST['user_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "Teacher deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting teacher: " . $e->getMessage();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Add Student
if (isset($_POST['add_student'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $section_id = $_POST['section_id'];
    $admission_number = $_POST['admission_number'];

    try {
        $pdo->beginTransaction();

        // Add to users table
        $stmt = $pdo->prepare("INSERT INTO users (role_id, username, email, password_hash, first_name, last_name, is_active)
                              VALUES (3, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$username, $email, $password, $first_name, $last_name]);
        $student_id = $pdo->lastInsertId();

        // Add to students table
        $stmt = $pdo->prepare("INSERT INTO students (student_id, section_id, admission_number, admission_date)
                              VALUES (?, ?, ?, CURDATE())");
        $stmt->execute([$student_id, $section_id, $admission_number]);

        $pdo->commit();
        $_SESSION['success'] = "Student added successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error adding student: " . $e->getMessage();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Edit Student
if (isset($_POST['edit_student'])) {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $section_id = $_POST['section_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        $pdo->beginTransaction();

        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, is_active = ?
                              WHERE user_id = ?");
        $stmt->execute([$first_name, $last_name, $email, $is_active, $user_id]);

        // Update students table
        $stmt = $pdo->prepare("UPDATE students SET section_id = ? WHERE student_id = ?");
        $stmt->execute([$section_id, $user_id]);

        $pdo->commit();
        $_SESSION['success'] = "Student updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating student: " . $e->getMessage();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Delete Student
if (isset($_POST['delete_student'])) {
    $user_id = $_POST['user_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "Student deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Add Department
if (isset($_POST['add_department'])) {
    $name = $_POST['name'];
    $code = $_POST['code'];

    try {
        $stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
        $stmt->execute([$name, $code]);
        $_SESSION['success'] = "Department added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding department: " . $e->getMessage();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Edit Department
if (isset($_POST['edit_department'])) {
    $department_id = $_POST['department_id'];
    $name = $_POST['name'];
    $code = $_POST['code'];

    try {
        $stmt = $pdo->prepare("UPDATE departments SET name = ?, code = ? WHERE department_id = ?");
        $stmt->execute([$name, $code, $department_id]);
        $_SESSION['success'] = "Department updated successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating department: " . $e->getMessage();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Delete Department
if (isset($_POST['delete_department'])) {
    $department_id = $_POST['department_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt->execute([$department_id]);
        $_SESSION['success'] = "Department deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting department: " . $e->getMessage();
    }
    header("Location: admin_dashboard.php");
    exit();
}
?>