<?php
session_start();
// Auto-login for direct access
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Admin';
$_SESSION['role'] = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aslam School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/school/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white border-right" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-4 fs-4 fw-bold border-bottom">
                <i class="fa-solid fa-graduation-cap me-2"></i>Aslam School
            </div>
            <div class="list-group list-group-flush mt-3">
                <a href="/school/dashboard.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="/school/students/manage.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-users me-2"></i> Students
                </a>
                <a href="/school/students/id_card.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-id-card me-2 text-warning"></i> Student ID Cards
                </a>
                <a href="/school/teachers/manage.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-chalkboard-user me-2"></i> Teachers
                </a>
                <a href="/school/attendance/face_attendance.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-camera me-2 text-warning"></i> Face Attendance
                </a>
                <a href="/school/attendance/face_register.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-id-card-clip me-2 text-info"></i> Face Registration
                </a>
                <a href="/school/attendance/manage.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-calendar-check me-2"></i> Attendance Sheet
                </a>
                <a href="/school/classes/manage.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-chalkboard me-2"></i> Classes
                </a>
                <a href="/school/classes/timetable.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-calendar-days me-2"></i> Class Timetable
                </a>
                <a href="/school/classes/all_timetable.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-calendar-week me-2"></i> All School Timetable
                </a>
                <a href="/school/subjects/manage.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-book me-2"></i> Subjects
                </a>
                <a href="/school/fees/manage.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-money-bill-wave me-2"></i> Fees
                </a>
                <a href="/school/results/manage.php" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fa-solid fa-chart-bar me-2"></i> Results
                </a>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div id="page-content-wrapper" class="w-100 bg-light">
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4 py-3">
                <button class="btn btn-outline-secondary" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
                <div class="ms-auto d-flex align-items-center">
                    <span class="me-3 fw-bold"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name']) ?>&background=random" class="rounded-circle" width="40" height="40" alt="User">
                </div>
            </nav>
            <div class="container-fluid p-4">
