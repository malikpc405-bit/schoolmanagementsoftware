<?php
require_once '../includes/db.php';

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if($class_id <= 0) {
    die("Invalid Class Selected.");
}

$class = $conn->query("SELECT * FROM classes WHERE id = $class_id")->fetch_assoc();
if(!$class) {
    die("Class not found.");
}

$query = "SELECT tt.*, s.name as subject_name, t.name as teacher_name 
          FROM timetable tt 
          LEFT JOIN subjects s ON tt.subject_id = s.id 
          LEFT JOIN teachers t ON tt.teacher_id = t.id 
          WHERE tt.class_id = $class_id 
          ORDER BY tt.period_no ASC, tt.start_time ASC";
$timetable = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Timetable - <?= htmlspecialchars($class['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .timetable-card {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
        }
        @media print {
            body { background: #fff; }
            .timetable-card { box-shadow: none; border: none; margin: 0; padding: 20px; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="timetable-card">
        <!-- Action Buttons -->
        <div class="d-flex justify-content-end mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print me-1"></i> Print Timetable</button>
            <a href="timetable.php?class_id=<?= $class_id ?>" class="btn btn-outline-secondary ms-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Timetable Editor</a>
        </div>

        <!-- School Header -->
        <div class="text-center pb-3 border-bottom mb-4">
            <h2 class="fw-bold text-uppercase text-primary mb-1"><i class="fa-solid fa-graduation-cap me-2"></i>Aslam School</h2>
            <h4 class="fw-bold text-dark mt-2">CLASS TIMETABLE: <?= strtoupper(htmlspecialchars($class['name'])) ?></h4>
            <span class="badge bg-light text-dark border"><i class="fa-solid fa-clock me-1 text-primary"></i> Standard School Time: 09:00 AM - 01:00 PM (30 Mints Classes)</span>
        </div>

        <!-- Timetable Table -->
        <table class="table table-bordered table-striped align-middle mb-4">
            <thead class="table-dark text-center">
                <tr>
                    <th style="width: 100px;">Period #</th>
                    <th style="width: 220px;">Time Slot (30 Mins)</th>
                    <th>Subject</th>
                    <th>Teacher Assigned</th>
                </tr>
            </thead>
            <tbody>
                <?php if($timetable && $timetable->num_rows > 0): ?>
                    <?php while($row = $timetable->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center fw-bold fs-5">Period <?= $row['period_no'] ?></td>
                            <td class="text-center fw-semibold text-primary">
                                <i class="fa-regular fa-clock me-1 text-muted"></i><?= htmlspecialchars($row['start_time']) ?> - <?= htmlspecialchars($row['end_time']) ?>
                            </td>
                            <td class="fw-bold">
                                <?php if(!empty($row['subject_name'])): ?>
                                    <span class="text-dark"><i class="fa-solid fa-book me-2 text-info"></i><?= htmlspecialchars($row['subject_name']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark px-3 py-2"><i class="fa-solid fa-mug-hot me-1"></i> Break / Recess</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($row['teacher_name'])): ?>
                                    <span class="text-success fw-semibold"><i class="fa-solid fa-user-tie me-2 text-muted"></i><?= htmlspecialchars($row['teacher_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Unassigned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No timetable periods set for this class.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="row pt-5 mt-5">
            <div class="col-6 text-center">
                <div style="border-top: 1px dashed #666; width: 70%; margin: 0 auto 5px auto;"></div>
                <small class="fw-bold">Class Incharge Signature</small>
            </div>
            <div class="col-6 text-center">
                <div style="border-top: 1px dashed #666; width: 70%; margin: 0 auto 5px auto;"></div>
                <small class="fw-bold">Principal Signature</small>
            </div>
        </div>
    </div>
</div>

</body>
</html>
