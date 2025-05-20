<?php
session_start();
require '../db.php';
require './admin_functions.php';

// Verify admin role
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.*
    FROM users u
    WHERE u.user_id = ?
");
$stmt->execute([$admin_id]);
$admin_info = $stmt->fetch();

// Count statistics for dashboard
$teacher_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn();
$student_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
$dept_count = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

// Get all departments with their sections for student forms
$departments = $pdo->query("
    SELECT d.department_id, d.name as department_name,
           GROUP_CONCAT(s.section_id ORDER BY s.name) as section_ids,
           GROUP_CONCAT(s.name ORDER BY s.name) as section_names
    FROM departments d
    LEFT JOIN sections s ON d.department_id = s.department_id
    GROUP BY d.department_id
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Your original CSS styles remain exactly the same */
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
            --color-orange: #ff7b00;
            --color-orange-light: hsla(28, 100%, 50%, 0.15);

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

        .welcome-section {
            background-color: var(--color-white);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .stat-card {
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            background-color: var(--color-white);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--color-primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--color-gray-700);
            font-weight: 500;
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

        .table-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 1rem;
            padding-top: 1rem;
            border-bottom: 2px solid var(--color-primary-light);
            padding-bottom: 0.5rem;
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
            border-bottom: 2px solid var(--color-primary);
        }

        .table td {
            vertical-align: middle;
        }

        .text-primary {
            color: var(--color-primary) !important;
        }

        .bg-primary-light {
            background-color: var(--color-primary-light);
        }

        .badge-primary {
            background-color: var(--color-primary);
            color: white;
        }

        .badge-active {
            background-color: var(--color-green);
            color: white;
        }

        .badge-inactive {
            background-color: var(--color-red);
            color: white;
        }

        .badge-pending {
            background-color: var(--color-orange);
            color: white;
        }

        .search-box .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.25rem rgba(111, 106, 248, 0.25);
        }

        .sort-icon {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .sort-icon:hover {
            color: var(--color-primary);
        }

        .sorted-asc {
            color: var(--color-primary);
        }

        .sorted-desc {
            color: var(--color-primary);
            transform: rotate(180deg);
        }

        /* Action buttons styling to match your original design */
        .action-btn {
            width: 30px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 3px;
        }

        @media (max-width: 768px) {
            .welcome-title {
                font-size: 2rem;
            }

            .table-title {
                font-size: 1.3rem;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .search-box {
                width: 100%;
                margin-top: 1rem;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body>
    <?php include './includes/header.php'; ?>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="container py-4">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="welcome-title">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></h1>
                    <p class="lead text-muted">Administrator Dashboard | <?= date('F j, Y') ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-primary-light text-primary p-3 fs-6">
                        <i class="bi bi-shield-lock me-2"></i> Super Administrator
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4 g-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-person-video3"></i>
                    </div>
                    <div class="stat-number"><?= $teacher_count ?></div>
                    <div class="stat-label">Teachers</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-number"><?= $student_count ?></div>
                    <div class="stat-label">Students</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="stat-number"><?= $dept_count ?></div>
                    <div class="stat-label">Departments</div>
                </div>
            </div>
        </div>

        <!-- Personal Information Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i> My Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3"><i class="bi bi-person-lines-fill me-2"></i>Personal Details</h6>
                                    <div class="ps-3">
                                        <p><strong>Full Name:</strong> <?= htmlspecialchars($admin_info['first_name'] . ' ' . $admin_info['last_name']) ?></p>
                                        <p><strong>Username:</strong> <?= htmlspecialchars($admin_info['username']) ?></p>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($admin_info['email']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3"><i class="bi bi-gear-fill me-2"></i>Account Information</h6>
                                    <div class="ps-3">
                                        <p><strong>Account Created:</strong> <?= date('M j, Y', strtotime($admin_info['created_at'])) ?></p>
                                        <p><strong>Last Login:</strong> <?= date('M j, Y h:i A', strtotime($admin_info['last_login'] ?? 'now')) ?></p>
                                        <p><strong>Status:</strong>
                                            <span class="badge <?= $admin_info['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= $admin_info['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teachers Table Section -->
        <div class="dashboard-card">
            <div class="card-body">
                <h3 class="table-title"><i class="bi bi-list-check me-2"></i> List of Teachers</h3>
                <div class="table-header d-flex justify-content-between align-items-center mb-3">
                    <div class="search-box">
                        <div class="input-group">
                            <input type="text" class="form-control teacher-search" placeholder="Search teachers...">
                            <button class="btn btn-primary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Teacher
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover teacher-table">
                        <thead>
                            <tr>
                                <th class="text-primary">ID <i class="bi bi-arrow-down-up sort-icon" data-column="0"></i></th>
                                <th class="text-primary">Name <i class="bi bi-arrow-down-up sort-icon" data-column="1"></i></th>
                                <th class="text-primary">Email <i class="bi bi-arrow-down-up sort-icon" data-column="2"></i></th>
                                <th class="text-primary">Qualification <i class="bi bi-arrow-down-up sort-icon" data-column="3"></i></th>
                                <th class="text-primary">Status</th>
                                <th class="text-primary">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT u.user_id, u.first_name, u.last_name, u.email, u.is_active, t.qualification
                                               FROM users u JOIN teachers t ON u.user_id = t.teacher_id
                                               WHERE u.role_id = 2");
                            while ($teacher = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($teacher['user_id']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($teacher['email']) ?></td>
                                <td><?= htmlspecialchars($teacher['qualification']) ?></td>
                                <td>
                                    <span class="badge <?= $teacher['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $teacher['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary action-btn edit-teacher"
                                            data-id="<?= $teacher['user_id'] ?>"
                                            data-firstname="<?= htmlspecialchars($teacher['first_name']) ?>"
                                            data-lastname="<?= htmlspecialchars($teacher['last_name']) ?>"
                                            data-email="<?= htmlspecialchars($teacher['email']) ?>"
                                            data-qualification="<?= htmlspecialchars($teacher['qualification']) ?>"
                                            data-active="<?= $teacher['is_active'] ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger action-btn delete-teacher"
                                            data-id="<?= $teacher['user_id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Students Table Section -->
        <div class="dashboard-card">
            <div class="card-body">
                <h3 class="table-title"><i class="bi bi-list-check me-2"></i> List of Students</h3>
                <div class="table-header d-flex justify-content-between align-items-center mb-3">
                    <div class="search-box">
                        <div class="input-group">
                            <input type="text" class="form-control student-search" placeholder="Search students...">
                            <button class="btn btn-primary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Student
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover student-table">
                        <thead>
                            <tr>
                                <th class="text-primary">ID <i class="bi bi-arrow-down-up sort-icon" data-column="0"></i></th>
                                <th class="text-primary">Name <i class="bi bi-arrow-down-up sort-icon" data-column="1"></i></th>
                                <th class="text-primary">Email <i class="bi bi-arrow-down-up sort-icon" data-column="2"></i></th>
                                <th class="text-primary">Section <i class="bi bi-arrow-down-up sort-icon" data-column="3"></i></th>
                                <th class="text-primary">Status</th>
                                <th class="text-primary">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT u.user_id, u.first_name, u.last_name, u.email, u.is_active,
                                                 sec.section_id, sec.name as section_name, d.name as department_name
                                                 FROM users u
                                                 JOIN students s ON u.user_id = s.student_id
                                                 JOIN sections sec ON s.section_id = sec.section_id
                                                 JOIN departments d ON sec.department_id = d.department_id
                                                 WHERE u.role_id = 3");
                            while ($student = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($student['user_id']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($student['email']) ?></td>
                                <td><?= htmlspecialchars($student['department_name'] . ' - ' . $student['section_name']) ?></td>
                                <td>
                                    <span class="badge <?= $student['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $student['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary action-btn edit-student"
                                            data-id="<?= $student['user_id'] ?>"
                                            data-firstname="<?= htmlspecialchars($student['first_name']) ?>"
                                            data-lastname="<?= htmlspecialchars($student['last_name']) ?>"
                                            data-email="<?= htmlspecialchars($student['email']) ?>"
                                            data-section="<?= $student['section_id'] ?>"
                                            data-department="<?= $student['department_name'] ?>"
                                            data-active="<?= $student['is_active'] ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger action-btn delete-student"
                                            data-id="<?= $student['user_id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Departments Table Section -->
        <div class="dashboard-card">
            <div class="card-body">
                <h3 class="table-title"><i class="bi bi-list-check me-2"></i> List of Departments</h3>
                <div class="table-header d-flex justify-content-between align-items-center mb-3">
                    <div class="search-box">
                        <div class="input-group">
                            <input type="text" class="form-control dept-search" placeholder="Search departments...">
                            <button class="btn btn-primary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Department
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover dept-table">
                        <thead>
                            <tr>
                                <th class="text-primary">ID <i class="bi bi-arrow-down-up sort-icon" data-column="0"></i></th>
                                <th class="text-primary">Name <i class="bi bi-arrow-down-up sort-icon" data-column="1"></i></th>
                                <th class="text-primary">Code <i class="bi bi-arrow-down-up sort-icon" data-column="2"></i></th>
                                <th class="text-primary">Courses <i class="bi bi-arrow-down-up sort-icon" data-column="3"></i></th>
                                <th class="text-primary">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT d.department_id, d.name, d.code,
                                               COUNT(c.course_id) as course_count
                                               FROM departments d
                                               LEFT JOIN courses c ON d.department_id = c.department_id
                                               GROUP BY d.department_id");
                            while ($dept = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($dept['department_id']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($dept['name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($dept['code']) ?></td>
                                <td>
                                    <span class="badge bg-primary-light text-primary">
                                        <?= htmlspecialchars($dept['course_count']) ?> courses
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary action-btn edit-department"
                                            data-id="<?= $dept['department_id'] ?>"
                                            data-name="<?= htmlspecialchars($dept['name']) ?>"
                                            data-code="<?= htmlspecialchars($dept['code']) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger action-btn delete-department"
                                            data-id="<?= $dept['department_id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTeacherModalLabel">Add New Teacher</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="qualification" class="form-label">Qualification</label>
                            <input type="text" class="form-control" id="qualification" name="qualification" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="add_teacher">Add Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="user_id" id="edit_teacher_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTeacherModalLabel">Edit Teacher</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_qualification" class="form-label">Qualification</label>
                            <input type="text" class="form-control" id="edit_qualification" name="qualification" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="edit_teacher">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Teacher Confirmation Modal -->
    <div class="modal fade" id="deleteTeacherModal" tabindex="-1" aria-labelledby="deleteTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="user_id" id="delete_teacher_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteTeacherModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this teacher? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_teacher">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="student_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="student_first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="student_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="student_last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="student_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="student_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="student_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="student_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="student_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="student_password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="department_id" class="form-label">Department</label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id'] ?>">
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="section_id" class="form-label">Section</label>
                            <select class="form-select" id="section_id" name="section_id" required disabled>
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admission_number" class="form-label">Admission Number</label>
                            <input type="text" class="form-control" id="admission_number" name="admission_number" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="add_student">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="user_id" id="edit_student_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_student_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_student_first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_student_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_student_last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_student_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_student_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_department_id" class="form-label">Department</label>
                            <select class="form-select" id="edit_department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id'] ?>">
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_section_id" class="form-label">Section</label>
                            <select class="form-select" id="edit_section_id" name="section_id" required>
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_student_is_active" name="is_active">
                            <label class="form-check-label" for="edit_student_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="edit_student">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Student Confirmation Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="user_id" id="delete_student_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteStudentModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this student? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_student">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Department Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="dept_name" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="dept_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="dept_code" class="form-label">Department Code</label>
                            <input type="text" class="form-control" id="dept_code" name="code" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="add_department">Add Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="department_id" id="edit_department_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_dept_name" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="edit_dept_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_dept_code" class="form-label">Department Code</label>
                            <input type="text" class="form-control" id="edit_dept_code" name="code" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="edit_department">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Department Confirmation Modal -->
    <div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="department_id" id="delete_department_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteDepartmentModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this department? This will also delete all associated sections and courses.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_department">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // Search functionality for each table
        $('.teacher-search').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('.teacher-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });

        $('.student-search').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('.student-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });

        $('.dept-search').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('.dept-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });

        // Sort functionality
        $('.sort-icon').click(function() {
            const $icon = $(this);
            const table = $icon.closest('table');
            const column = $icon.data('column');
            const rows = table.find('tbody > tr').get();

            // Remove all sort classes first
            table.find('.sort-icon').removeClass('sorted-asc sorted-desc');

            rows.sort(function(a, b) {
                const A = $(a).find('td').eq(column).text().toUpperCase();
                const B = $(b).find('td').eq(column).text().toUpperCase();

                // Check if numeric
                if ($.isNumeric(A) && $.isNumeric(B)) {
                    return A - B;
                }
                return A.localeCompare(B);
            });

            // Reverse if already sorted
            if ($icon.hasClass('sorted-asc')) {
                rows.reverse();
                $icon.removeClass('sorted-asc').addClass('sorted-desc');
            } else {
                $icon.removeClass('sorted-desc').addClass('sorted-asc');
            }

            $.each(rows, function(index, row) {
                table.find('tbody').append(row);
            });
        });

        // Department-Section relationship for Add Student form
        $('#department_id').change(function() {
            const deptId = $(this).val();
            const sectionSelect = $('#section_id');

            if (!deptId) {
                sectionSelect.prop('disabled', true);
                sectionSelect.html('<option value="">Select Section</option>');
                return;
            }

            // Find the selected department
            const department = <?= json_encode($departments) ?>.find(d => d.department_id == deptId);

            if (department && department.section_ids) {
                const sectionIds = department.section_ids.split(',');
                const sectionNames = department.section_names.split(',');

                let options = '<option value="">Select Section</option>';
                for (let i = 0; i < sectionIds.length; i++) {
                    options += `<option value="${sectionIds[i]}">${sectionNames[i]}</option>`;
                }

                sectionSelect.html(options).prop('disabled', false);
            } else {
                sectionSelect.html('<option value="">No sections available</option>').prop('disabled', true);
            }
        });

        // Department-Section relationship for Edit Student form
        $('#edit_department_id').change(function() {
            const deptId = $(this).val();
            const sectionSelect = $('#edit_section_id');

            if (!deptId) {
                sectionSelect.prop('disabled', true);
                sectionSelect.html('<option value="">Select Section</option>');
                return;
            }

            // Find the selected department
            const department = <?= json_encode($departments) ?>.find(d => d.department_id == deptId);

            if (department && department.section_ids) {
                const sectionIds = department.section_ids.split(',');
                const sectionNames = department.section_names.split(',');

                let options = '<option value="">Select Section</option>';
                for (let i = 0; i < sectionIds.length; i++) {
                    options += `<option value="${sectionIds[i]}">${sectionNames[i]}</option>`;
                }

                sectionSelect.html(options).prop('disabled', false);
            } else {
                sectionSelect.html('<option value="">No sections available</option>').prop('disabled', true);
            }
        });

        // Teacher edit button click handler
        $('.edit-teacher').click(function() {
            const id = $(this).data('id');
            const firstname = $(this).data('firstname');
            const lastname = $(this).data('lastname');
            const email = $(this).data('email');
            const qualification = $(this).data('qualification');
            const active = $(this).data('active');

            $('#edit_teacher_id').val(id);
            $('#edit_first_name').val(firstname);
            $('#edit_last_name').val(lastname);
            $('#edit_email').val(email);
            $('#edit_qualification').val(qualification);
            $('#edit_is_active').prop('checked', active == 1);

            $('#editTeacherModal').modal('show');
        });

        // Teacher delete button click handler
        $('.delete-teacher').click(function() {
            const id = $(this).data('id');
            $('#delete_teacher_id').val(id);
            $('#deleteTeacherModal').modal('show');
        });

        // Student edit button click handler
        $('.edit-student').click(function() {
            const id = $(this).data('id');
            const firstname = $(this).data('firstname');
            const lastname = $(this).data('lastname');
            const email = $(this).data('email');
            const section = $(this).data('section');
            const department = $(this).data('department');
            const active = $(this).data('active');

            $('#edit_student_id').val(id);
            $('#edit_student_first_name').val(firstname);
            $('#edit_student_last_name').val(lastname);
            $('#edit_student_email').val(email);
            $('#edit_student_is_active').prop('checked', active == 1);

            // Find and select the department and section
            const dept = <?= json_encode($departments) ?>.find(d =>
                d.section_ids && d.section_ids.split(',').includes(section.toString())
            );

            if (dept) {
                $('#edit_department_id').val(dept.department_id).trigger('change');

                // Wait for sections to load
                setTimeout(() => {
                    $('#edit_section_id').val(section);
                }, 100);
            }

            $('#editStudentModal').modal('show');
        });

        // Student delete button click handler
        $('.delete-student').click(function() {
            const id = $(this).data('id');
            $('#delete_student_id').val(id);
            $('#deleteStudentModal').modal('show');
        });

        // Department edit button click handler
        $('.edit-department').click(function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const code = $(this).data('code');

            $('#edit_department_id').val(id);
            $('#edit_dept_name').val(name);
            $('#edit_dept_code').val(code);

            $('#editDepartmentModal').modal('show');
        });

        // Department delete button click handler
        $('.delete-department').click(function() {
            const id = $(this).data('id');
            $('#delete_department_id').val(id);
            $('#deleteDepartmentModal').modal('show');
        });
    });
    </script>
</body>
</html>