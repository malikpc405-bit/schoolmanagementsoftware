<?php
require_once '../includes/db.php';

$admission_no = isset($_GET['adm']) ? trim($_GET['adm']) : '';
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$student = null;
if (!empty($admission_no)) {
    $stmt = $conn->prepare("SELECT s.*, c.name as class_name, sf.photo_path as face_photo 
                            FROM students s 
                            JOIN classes c ON s.class_id = c.id 
                            LEFT JOIN student_faces sf ON s.id = sf.student_id 
                            WHERE s.admission_no = ?");
    $stmt->bind_param("s", $admission_no);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
    }
} elseif ($student_id > 0) {
    $stmt = $conn->prepare("SELECT s.*, c.name as class_name, sf.photo_path as face_photo 
                            FROM students s 
                            JOIN classes c ON s.class_id = c.id 
                            LEFT JOIN student_faces sf ON s.id = sf.student_id 
                            WHERE s.id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
    }
}

// Fetch School Info
$school_settings = [
    'school_name' => 'ASLAM PUBLIC HIGH SCHOOL',
    'tagline' => 'Excellence in Knowledge & Character',
    'campus' => 'Main Campus',
    'phone' => '+92 300 1234567',
    'email' => 'info@aslamschool.edu',
    'address' => 'College Road, Model Town',
    'session_year' => '2026 - 2027'
];
$set_res = $conn->query("SELECT * FROM id_card_settings LIMIT 1");
if ($set_res && $set_res->num_rows > 0) {
    $school_settings = array_merge($school_settings, $set_res->fetch_assoc());
}

// Photo resolver
$photo_src = "https://ui-avatars.com/api/?name=" . urlencode($student['name'] ?? 'Student') . "&background=0f172a&color=fff&size=200";
if ($student) {
    if (!empty($student['photo']) && file_exists('../uploads/students/' . $student['photo'])) {
        $photo_src = '../uploads/students/' . htmlspecialchars($student['photo']);
    } elseif (!empty($student['face_photo']) && file_exists('../uploads/faces/' . $student['face_photo'])) {
        $photo_src = '../uploads/faces/' . htmlspecialchars($student['face_photo']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Card Verification - <?= htmlspecialchars($school_settings['school_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0b132b 0%, #1c2541 50%, #3a506b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verify-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }
        .verify-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            color: #ffffff;
            padding: 28px 24px 45px;
            text-align: center;
            position: relative;
        }
        .verify-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: #ffffff;
            border-radius: 24px 24px 0 0;
        }
        .school-badge-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fbbf24;
            margin-bottom: 10px;
        }
        .student-avatar-wrap {
            position: relative;
            z-index: 5;
            margin-top: -55px;
            text-align: center;
        }
        .student-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ffffff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.18);
            background: #e2e8f0;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #15803d;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 6px 16px;
            border-radius: 20px;
            border: 1px solid #86efac;
            margin-top: 10px;
        }
        .status-badge.invalid {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fca5a5;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.92rem;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #64748b;
            font-weight: 600;
        }
        .detail-val {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }
        .btn-call {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border-radius: 12px;
            font-weight: 600;
            padding: 10px;
            transition: all 0.2s;
        }
        .btn-call:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            color: #fff;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<div class="verify-card">
    <div class="verify-header">
        <div class="school-badge-icon">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h5 class="fw-bold mb-1 text-white"><?= htmlspecialchars($school_settings['school_name']) ?></h5>
        <p class="small text-white-50 mb-0"><?= htmlspecialchars($school_settings['tagline']) ?></p>
    </div>

    <?php if ($student): ?>
        <div class="student-avatar-wrap">
            <img src="<?= $photo_src ?>" alt="<?= htmlspecialchars($student['name']) ?>" class="student-avatar">
            <div class="mt-2">
                <span class="status-badge">
                    <i class="fa-solid fa-shield-check"></i> VERIFIED ACTIVE STUDENT
                </span>
            </div>
            <h4 class="fw-bold text-dark mt-2 mb-0"><?= htmlspecialchars($student['name']) ?></h4>
            <p class="text-primary fw-bold mb-0">Class: <?= htmlspecialchars($student['class_name']) ?></p>
        </div>

        <div class="p-4 pt-3">
            <div class="bg-light p-3 rounded-4 mb-3 border">
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-id-card-clip me-2 text-primary"></i>Admission No</span>
                    <span class="detail-val text-primary"><?= htmlspecialchars($student['admission_no']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-user-tag me-2 text-muted"></i>Father's Name</span>
                    <span class="detail-val"><?= htmlspecialchars($student['father_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-calendar me-2 text-muted"></i>Date of Birth</span>
                    <span class="detail-val"><?= !empty($student['dob']) && $student['dob'] != '0000-00-00' ? date('d M Y', strtotime($student['dob'])) : 'N/A' ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-droplet me-2 text-danger"></i>Blood Group</span>
                    <span class="detail-val badge bg-danger-subtle text-danger fs-6"><?= !empty($student['blood_group']) ? htmlspecialchars($student['blood_group']) : 'O+' ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-phone me-2 text-success"></i>Emergency Contact</span>
                    <span class="detail-val"><?= htmlspecialchars($student['phone']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-location-dot me-2 text-muted"></i>Address</span>
                    <span class="detail-val" style="max-width: 200px;"><?= htmlspecialchars($student['address']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i>Session</span>
                    <span class="detail-val"><?= htmlspecialchars($school_settings['session_year']) ?></span>
                </div>
            </div>

            <div class="d-grid gap-2">
                <?php if (!empty($student['phone'])): ?>
                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $student['phone']) ?>" class="btn btn-call">
                        <i class="fa-solid fa-phone-volume me-2"></i> Call Emergency Contact
                    </a>
                <?php endif; ?>
                <a href="/school/students/id_card.php?id=<?= $student['id'] ?>" class="btn btn-outline-secondary rounded-3">
                    <i class="fa-solid fa-id-card me-2"></i> View ID Card Studio
                </a>
            </div>

            <div class="text-center mt-3 text-muted small">
                <i class="fa-solid fa-building me-1"></i> <?= htmlspecialchars($school_settings['campus']) ?> • <?= htmlspecialchars($school_settings['phone']) ?>
            </div>
        </div>
    <?php else: ?>
        <div class="student-avatar-wrap">
            <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px; border: 4px solid #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.1);">
                <i class="fa-solid fa-triangle-exclamation fs-1"></i>
            </div>
            <div class="mt-2">
                <span class="status-badge invalid">
                    <i class="fa-solid fa-circle-xmark"></i> RECORD NOT FOUND
                </span>
            </div>
            <h5 class="fw-bold text-dark mt-3 mb-1">Invalid ID Card</h5>
            <p class="text-muted small px-4">No student records match the provided ID card credentials.</p>
        </div>
        <div class="p-4 text-center">
            <a href="/school/dashboard.php" class="btn btn-primary rounded-3 w-100">
                <i class="fa-solid fa-home me-2"></i> Go to Dashboard
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
