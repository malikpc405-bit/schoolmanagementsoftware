<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /school/index.php"); exit(); }
require_once '../includes/db.php';

if(!isset($_GET['student_id']) || !isset($_GET['exam_term'])) die("Invalid Request");

$student_id = (int)$_GET['student_id'];
$exam_term = $conn->real_escape_string($_GET['exam_term']);

// Fetch Student details
$stu_q = $conn->query("SELECT s.*, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.id = $student_id");
if($stu_q->num_rows == 0) die("Student not found");
$student = $stu_q->fetch_assoc();

// Fetch Marks
$marks_q = $conn->query("SELECT m.*, sub.name as subject_name FROM marks m JOIN subjects sub ON m.subject_id = sub.id WHERE m.student_id = $student_id AND m.exam_term = '$exam_term'");

if($marks_q->num_rows == 0) die("No marks found for this exam term.");

$marks = [];
$total_obtained = 0;
$total_max = 0;
$has_failed_subject = false;

while($m = $marks_q->fetch_assoc()) {
    $marks[] = $m;
    $total_obtained += $m['obtained_marks'];
    $total_max += $m['total_marks'];
    if($m['obtained_marks'] < ($m['total_marks'] * 0.33)) {
        $has_failed_subject = true;
    }
}

$percentage = ($total_max > 0) ? ($total_obtained / $total_max) * 100 : 0;
$grade = 'F';
if($percentage >= 90) $grade = 'A+';
elseif($percentage >= 80) $grade = 'A';
elseif($percentage >= 70) $grade = 'B';
elseif($percentage >= 60) $grade = 'C';
elseif($percentage >= 50) $grade = 'D';

$status = ($has_failed_subject || $percentage < 33) ? 'FAIL' : 'PASS';

// Calculate class rank
$class_id = $student['class_id'];
$rank_q = $conn->query("
    SELECT m.student_id, SUM(m.obtained_marks) as total
    FROM marks m
    JOIN students s ON m.student_id = s.id
    WHERE s.class_id = $class_id AND m.exam_term = '$exam_term'
    GROUP BY m.student_id
    ORDER BY total DESC
");

$rank = 0;
$current_pos = 1;
while($r = $rank_q->fetch_assoc()) {
    if($r['student_id'] == $student_id) {
        $rank = $current_pos;
        break;
    }
    $current_pos++;
}

$rank_str = $rank;
if($rank == 1) $rank_str .= 'st';
elseif($rank == 2) $rank_str .= 'nd';
elseif($rank == 3) $rank_str .= 'rd';
else $rank_str .= 'th';

if($status == 'FAIL') $rank_str = '-'; // Fails don't get a rank
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - <?= $student['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap');
        
        body { 
            background: #0f172a url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%231e293b" fill-opacity="0.4"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
            font-family: 'Inter', sans-serif; 
            padding: 40px 0;
            color: #334155;
        }

        .report-card { 
            max-width: 210mm; 
            margin: 0 auto; 
            background: #ffffff url('data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23f8fafc" fill-opacity="0.8" fill-rule="evenodd"%3E%3Ccircle cx="3" cy="3" r="3"/%3E%3Ccircle cx="13" cy="13" r="3"/%3E%3C/g%3E%3C/svg%3E');
            padding: 12px; 
            box-shadow: 0 25px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.1); 
            border-radius: 8px; 
            position: relative;
            z-index: 1;
        }

        .report-card-inner {
            border: 3px double #cbd5e1;
            height: 100%;
            background: rgba(255,255,255,0.95);
            display: flex;
            flex-direction: column;
            position: relative;
            padding: 40px;
        }

        .school-header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #f1f5f9; 
            padding-bottom: 20px; 
        }
        
        .school-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 5px;
        }
        .school-header p {
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 13px;
        }

        .report-header-badge {
            display: inline-block;
            margin-top: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            padding: 8px 30px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .student-info { margin-bottom: 30px; }
        .student-info th { color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        .student-info td { font-weight: 700; color: #0f172a; border-bottom: 1px dashed #e2e8f0; }
        
        .marks-table { border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 30px; }
        .marks-table th { background: #f8fafc !important; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; color: #334155; }
        .marks-table th, .marks-table td { text-align: center; vertical-align: middle; padding: 12px !important; }
        .marks-table th:first-child, .marks-table td:first-child { text-align: left; }
        .marks-table tbody tr:hover td { background: #f8fafc; }
        .marks-table tfoot td { background: #f1f5f9 !important; font-weight: 800; border-top: 2px solid #cbd5e1; }

        .result-summary { 
            border: 1px solid #e2e8f0; 
            padding: 25px; 
            border-radius: 8px; 
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            margin-bottom: 30px;
        }
        .result-summary h6 { text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; font-size: 11px; }
        .result-summary h4 { font-family: 'Playfair Display', serif; font-size: 28px; }

        .signature-section { margin-top: auto; padding-top: 30px; }
        .signature-section p { font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 1px; }
        .signature-section span { display: block; width: 140px; margin: 0 auto 5px; border-top: 1px solid #0f172a; }

        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); opacity: 0.04; font-size: 120px; font-family: 'Playfair Display', serif; font-weight: bold; pointer-events: none; letter-spacing: 8px;}
        
        .btn-print-main {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: 1px solid #60a5fa;
            color: white;
            font-weight: 600;
            border-radius: 50px;
            letter-spacing: 0.5px;
            transition: all 0.4s ease;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
            text-transform: uppercase;
        }
        .btn-print-main:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.7);
            color: white;
        }

        @media print {
            body { background: white; margin: 0; padding: 0; }
            .report-card { 
                box-shadow: none; 
                margin: 0; 
                padding: 10mm;
                box-sizing: border-box;
                width: 210mm;
                height: 296mm;
                display: flex;
                flex-direction: column;
                page-break-inside: avoid;
            }
            .report-card-inner { border: 2px solid #000; padding: 10mm; }
            .school-header { border-bottom: 1px solid #000; }
            .school-header h1 { color: #000; }
            .student-info td { border-bottom: 1px dotted #000; }
            .marks-table { border: 1px solid #000; flex-grow: 1; }
            .marks-table th { background: #eee !important; border-bottom: 2px solid #000; }
            .marks-table td { border-bottom: 1px solid #ccc; }
            .result-summary { border: 1px solid #000; box-shadow: none; }
            .signature-section { margin-top: auto; }
            .no-print { display: none; }
            .report-card-inner, .marks-table th, .result-summary { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="container report-card position-relative">
        <div class="report-card-inner">
            <div class="watermark">ASLAM SCHOOL</div>
            <div class="school-header">
            <h1 class="fw-bold">ASLAM SCHOOL</h1>
            <p class="mb-0">Affiliated to Central Board of Secondary Education</p>
            <p class="mb-0">123 Education Street, City, Country</p>
            <h3 class="mt-4 fw-bold border d-inline-block px-4 py-2 rounded-pill">ACADEMIC PERFORMANCE REPORT</h3>
        </div>
        
        <div class="row student-info align-items-center">
            <div class="col-md-2 text-center">
                <?php if(!empty($student['photo'])): ?>
                    <img src="../uploads/students/<?= $student['photo'] ?>" alt="Photo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #cbd5e1;">
                <?php else: ?>
                    <div style="width: 100px; height: 100px; border-radius: 8px; border: 2px dashed #cbd5e1; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:12px; margin:auto; background:white;">No Photo</div>
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="150">Student Name</th><td>: <span class="fw-bold"><?= strtoupper($student['name']) ?></span></td></tr>
                    <tr><th>Father's Name</th><td>: <?= strtoupper($student['father_name']) ?></td></tr>
                    <tr><th>Date of Birth</th><td>: <?= date('d-m-Y', strtotime($student['dob'])) ?></td></tr>
                </table>
            </div>
            <div class="col-md-5">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="150">Admission No.</th><td>: <?= $student['admission_no'] ?></td></tr>
                    <tr><th>Class</th><td>: <?= $student['class_name'] ?></td></tr>
                    <tr><th>Exam Term</th><td>: <?= $exam_term ?></td></tr>
                </table>
            </div>
        </div>

        <table class="table table-bordered marks-table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Subject</th>
                    <th width="120">Max Marks</th>
                    <th width="120">Marks Obtained</th>
                    <th width="120">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($marks as $m): 
                    $sub_pct = ($m['obtained_marks'] / $m['total_marks']) * 100;
                    $remark = 'Poor';
                    if($sub_pct >= 90) $remark = 'Excellent';
                    elseif($sub_pct >= 75) $remark = 'Very Good';
                    elseif($sub_pct >= 60) $remark = 'Good';
                    elseif($sub_pct >= 40) $remark = 'Average';
                ?>
                <tr>
                    <td class="fw-bold"><?= $m['subject_name'] ?></td>
                    <td><?= $m['total_marks'] ?></td>
                    <td class="fw-bold <?= $m['obtained_marks'] < ($m['total_marks'] * 0.33) ? 'text-danger' : '' ?>"><?= $m['obtained_marks'] ?></td>
                    <td><?= $remark ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td class="text-end">GRAND TOTAL</td>
                    <td><?= $total_max ?></td>
                    <td><?= $total_obtained ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="result-summary">
            <div class="row text-center">
                <div class="col-3">
                    <h6 class="text-muted mb-1">Percentage</h6>
                    <h4 class="fw-bold"><?= number_format($percentage, 2) ?>%</h4>
                </div>
                <div class="col-3 border-start">
                    <h6 class="text-muted mb-1">Grade</h6>
                    <h4 class="fw-bold"><?= $grade ?></h4>
                </div>
                <div class="col-3 border-start">
                    <h6 class="text-muted mb-1">Result</h6>
                    <h4 class="fw-bold <?= $status == 'PASS' ? 'text-success' : 'text-danger' ?>"><?= $status ?></h4>
                </div>
                <div class="col-3 border-start">
                    <h6 class="text-muted mb-1">Class Position</h6>
                    <h4 class="fw-bold <?= $rank <= 3 && $status == 'PASS' ? 'text-warning' : '' ?>"><?= $rank_str ?></h4>
                </div>
            </div>
        </div>

        <div class="row signature-section">
            <div class="col-4 text-center">
                <p>_______________________<br><small class="text-muted">Class Teacher</small></p>
            </div>
            <div class="col-4 text-center">
                <p>_______________________<br><small class="text-muted">Parent/Guardian</small></p>
            </div>
            <div class="col-4 text-center">
                <p>_______________________<br><small class="text-muted">Principal</small></p>
            </div>
            </div>

            <div class="text-center mt-5 no-print">
                <button class="btn btn-primary btn-print-main btn-lg px-5 shadow"><i class="fa-solid fa-print me-2"></i> Print Report Card</button>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const printBtn = document.querySelector('.btn-print-main');
            if (printBtn) {
                printBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    triggerPrint();
                });
            }

            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                    e.preventDefault();
                    triggerPrint();
                }
            });

            function triggerPrint() {
                Swal.fire({
                    title: 'Print Instructions',
                    html: `<div style="text-align:left; font-size:15px;">
                            For perfect A4 alignment, please set the following in your print dialog:<br><br>
                            <ul style="margin-left: 20px;">
                                <li><b>Paper Size:</b> A4</li>
                                <li><b>Margins:</b> None / Minimum</li>
                                <li><b>Scale:</b> Default / 100%</li>
                                <li><b>Options:</b> Check 'Background graphics'</li>
                            </ul>
                           </div>`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-print"></i> Proceed to Print',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#667eea',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.print();
                    }
                });
            }
        });
    </script>
</body>
</html>
