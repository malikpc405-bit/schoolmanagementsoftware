<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch statistics
$students_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$teachers_count = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE status='Active'")->fetch_assoc()['count'];
$classes_count = $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'];
$subjects_count = $conn->query("SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count'];
$fees_total = $conn->query("SELECT SUM(amount) as total FROM fees")->fetch_assoc()['total'];
$fees_total = $fees_total ? $fees_total : 0;

$fees_this_week = $conn->query("SELECT SUM(amount) as total FROM fees WHERE YEARWEEK(paid_on, 1) = YEARWEEK(CURDATE(), 1)")->fetch_assoc()['total'];
$fees_this_week = $fees_this_week ? $fees_this_week : 0;

$fees_this_month = $conn->query("SELECT SUM(amount) as total FROM fees WHERE MONTH(paid_on) = MONTH(CURDATE()) AND YEAR(paid_on) = YEAR(CURDATE())")->fetch_assoc()['total'];
$fees_this_month = $fees_this_month ? $fees_this_month : 0;
?>

<h2 class="mb-4">Dashboard</h2>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white shadow rounded-4 border-0">
            <div class="card-body">
                <h5 class="card-title">Total Students</h5>
                <h2 class="mb-0"><?= $students_count ?></h2>
                <i class="fa-solid fa-users position-absolute opacity-25" style="right: 20px; bottom: 20px; font-size: 3rem;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-purple text-white shadow rounded-4 border-0" style="background-color: #6f42c1;">
            <div class="card-body">
                <h5 class="card-title">Active Teachers</h5>
                <h2 class="mb-0"><?= $teachers_count ?></h2>
                <i class="fa-solid fa-chalkboard-user position-absolute opacity-25" style="right: 20px; bottom: 20px; font-size: 3rem;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white shadow rounded-4 border-0">
            <div class="card-body">
                <h5 class="card-title">Total Classes</h5>
                <h2 class="mb-0"><?= $classes_count ?></h2>
                <i class="fa-solid fa-chalkboard position-absolute opacity-25" style="right: 20px; bottom: 20px; font-size: 3rem;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-dark shadow rounded-4 border-0">
            <div class="card-body">
                <h5 class="card-title">Subjects</h5>
                <h2 class="mb-0"><?= $subjects_count ?></h2>
                <i class="fa-solid fa-book position-absolute opacity-25" style="right: 20px; bottom: 20px; font-size: 3rem;"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card bg-info text-white shadow rounded-4 border-0">
            <div class="card-body">
                <h5 class="card-title">Total Fees Collected</h5>
                <h2 class="mb-0">Rs <?= number_format($fees_total, 2) ?></h2>
                <i class="fa-solid fa-money-bill-wave position-absolute opacity-25" style="right: 20px; bottom: 20px; font-size: 3rem;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-danger text-white shadow rounded-4 border-0">
            <div class="card-body">
                <h5 class="card-title">Fees This Week</h5>
                <h2 class="mb-0">Rs <?= number_format($fees_this_week, 2) ?></h2>
                <i class="fa-solid fa-calendar-week position-absolute opacity-25" style="right: 20px; bottom: 20px; font-size: 3rem;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-secondary text-white shadow rounded-4 border-0">
            <div class="card-body">
                <h5 class="card-title">Fees This Month</h5>
                <h2 class="mb-0">Rs <?= number_format($fees_this_month, 2) ?></h2>
                <i class="fa-solid fa-calendar-alt position-absolute opacity-25" style="right: 20px; bottom: 20px; font-size: 3rem;"></i>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card shadow border-0 rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-camera text-primary me-2"></i>Face Recognition Attendance</h5>
                <a href="/school/attendance/face_attendance.php" class="btn btn-sm btn-success"><i class="fa-solid fa-expand me-1"></i> Launch Kiosk</a>
            </div>
            <div class="card-body">
                <div class="p-3 bg-light rounded-4 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted fw-semibold">Biometric Enrollment Status</span>
                        <?php
                        $enrolled_cnt = $conn->query("SELECT COUNT(DISTINCT student_id) as cnt FROM student_faces")->fetch_assoc()['cnt'];
                        $today_face_cnt = $conn->query("SELECT COUNT(*) as cnt FROM attendance WHERE att_date = CURDATE() AND method = 'face_recognition'")->fetch_assoc()['cnt'];
                        $enroll_pct = $students_count > 0 ? round(($enrolled_cnt / $students_count) * 100) : 0;
                        ?>
                        <span class="fw-bold text-primary"><?= $enrolled_cnt ?> / <?= $students_count ?> Enrolled (<?= $enroll_pct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $enroll_pct ?>%"></div>
                    </div>
                </div>

                <div class="row g-2 text-center mb-3">
                    <div class="col-6">
                        <div class="border rounded-3 p-2 bg-success-subtle">
                            <div class="fs-4 fw-bold text-success"><?= $today_face_cnt ?></div>
                            <div class="small text-muted">Marked by Face Today</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded-3 p-2 bg-info-subtle">
                            <div class="fs-4 fw-bold text-info"><?= $enrolled_cnt ?></div>
                            <div class="small text-muted">Ready for Auto-Scan</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="/school/attendance/face_attendance.php" class="btn btn-primary flex-fill py-2 fw-semibold">
                        <i class="fa-solid fa-camera me-1"></i> Open Scanner Kiosk
                    </a>
                    <a href="/school/attendance/face_register.php" class="btn btn-outline-primary flex-fill py-2 fw-semibold">
                        <i class="fa-solid fa-id-card-clip me-1"></i> Register Student Faces
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow border-0 rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-user-graduate text-secondary me-2"></i>Recent Students</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Adm No</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Face Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent = $conn->query("SELECT s.*, c.name as class_name, sf.id as face_id 
                                                FROM students s 
                                                JOIN classes c ON s.class_id = c.id 
                                                LEFT JOIN student_faces sf ON s.id = sf.student_id
                                                ORDER BY s.id DESC LIMIT 5");
                        if($recent->num_rows > 0):
                            while($row = $recent->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= $row['admission_no'] ?></td>
                            <td><?= $row['name'] ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $row['class_name'] ?></span></td>
                            <td>
                                <?php if($row['face_id']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-circle-check me-1"></i>Enrolled</span>
                                <?php else: ?>
                                    <a href="/school/attendance/face_register.php?student_id=<?= $row['id'] ?>" class="badge bg-warning-subtle text-dark border border-warning-subtle text-decoration-none"><i class="fa-solid fa-camera me-1"></i>Register</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center">No students found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Student ID Card Feature Banner -->
<div class="card shadow-sm border-0 rounded-4 mb-4 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="fa-solid fa-sparkles me-1"></i>NEW FEATURE</span>
                    <span class="badge bg-white-subtle text-white border border-light-subtle">Standard Size: 2.0" × 3.4"</span>
                </div>
                <h4 class="fw-bold mb-2">Auto Design Student ID Cards with QR Code</h4>
                <p class="text-white-50 mb-3 mb-md-0">
                    Instantly generate and print professional vertical student identity cards (L=2", H=3.4") with student details, high-res photos, scannable QR verification badges, and customizable themes.
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="/school/students/id_card.php" class="btn btn-warning fw-bold px-4 py-2 me-2 mb-2 mb-sm-0">
                    <i class="fa-solid fa-id-card me-1"></i> ID Card Studio
                </a>
                <a href="/school/students/id_card.php?mode=batch" class="btn btn-outline-light fw-bold px-3 py-2">
                    <i class="fa-solid fa-layer-group me-1"></i> Batch Print
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

