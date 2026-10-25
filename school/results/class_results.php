<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /school/index.php"); exit(); }
require_once '../includes/db.php';

if(!isset($_GET['class_id']) || !isset($_GET['exam_term'])) die("Invalid Request");

$class_id = (int)$_GET['class_id'];
$exam_term = $conn->real_escape_string($_GET['exam_term']);

$class_info = $conn->query("SELECT name FROM classes WHERE id = $class_id")->fetch_assoc();
if(!$class_info) die("Class not found");

// Get subjects for this class
$subjects_q = $conn->query("SELECT * FROM subjects WHERE class_id = $class_id ORDER BY id");
$subjects = [];
while($s = $subjects_q->fetch_assoc()) {
    $subjects[] = $s;
}

// Get all students in this class
$students_q = $conn->query("SELECT s.*, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.class_id = $class_id ORDER BY s.name");
$students = [];
while($s = $students_q->fetch_assoc()) {
    $students[] = $s;
}

if(count($students) == 0) die("No students found in this class.");

// Get all marks for this class and exam term
$marks_q = $conn->query("
    SELECT m.* 
    FROM marks m 
    JOIN students s ON m.student_id = s.id 
    WHERE s.class_id = $class_id AND m.exam_term = '$exam_term'
");

$marks_data = [];
while($m = $marks_q->fetch_assoc()) {
    $marks_data[$m['student_id']][$m['subject_id']] = $m;
}

// Calculate class rankings first
$ranking_data = [];
foreach($students as $stu) {
    $total = 0;
    $max = 0;
    if(isset($marks_data[$stu['id']])) {
        foreach($marks_data[$stu['id']] as $m) {
            $total += $m['obtained_marks'];
            $max += $m['total_marks'];
        }
    }
    $ranking_data[] = ['student_id' => $stu['id'], 'total' => $total];
}
usort($ranking_data, function($a, $b) { return $b['total'] <=> $a['total']; });
$ranks = [];
$pos = 1;
foreach($ranking_data as $r) {
    $ranks[$r['student_id']] = $pos++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Result Sheet - <?= $class_info['name'] ?> - <?= $exam_term ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap');
        
        * { box-sizing: border-box; }
        body { 
            background: #0f172a url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%231e293b" fill-opacity="0.4"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
            font-family: 'Inter', sans-serif; 
            color: #334155;
        }

        .no-print-area {
            padding: 40px 20px;
            text-align: center;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .btn-print-main {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: 1px solid #60a5fa;
            color: white;
            padding: 14px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.4), inset 0 2px 0 rgba(255,255,255,0.2);
            text-transform: uppercase;
        }
        .btn-print-main:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.7), inset 0 2px 0 rgba(255,255,255,0.3);
            color: white;
        }
        .btn-back {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #cbd5e1;
            padding: 14px 35px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-color: rgba(255,255,255,0.3);
        }
        .info-text {
            color: #94a3b8;
            font-size: 15px;
            margin-top: 15px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* Certificate-Style Report Card */
        .report-card {
            max-width: 210mm;
            margin: 0 auto 60px;
            background: #ffffff url('data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23f8fafc" fill-opacity="0.8" fill-rule="evenodd"%3E%3Ccircle cx="3" cy="3" r="3"/%3E%3Ccircle cx="13" cy="13" r="3"/%3E%3C/g%3E%3C/svg%3E');
            border-radius: 8px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.1);
            position: relative;
            padding: 12px; /* Outer border spacing */
            z-index: 1;
        }
        
        .report-card-inner {
            border: 3px double #cbd5e1; /* Elegant inner border */
            height: 100%;
            background: rgba(255,255,255,0.95);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .report-card .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            opacity: 0.04;
            font-size: 110px;
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            pointer-events: none;
            color: #000;
            letter-spacing: 8px;
            white-space: nowrap;
            z-index: 0;
        }

        .report-header {
            text-align: center;
            padding: 40px 40px 25px;
            position: relative;
            z-index: 1;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .report-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 1px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .report-header .sub-text {
            font-size: 14px;
            color: #64748b;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .report-header .title-badge {
            display: inline-block;
            margin-top: 25px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            padding: 8px 30px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 4px;
            text-transform: uppercase;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .student-details {
            padding: 25px 50px;
            background: linear-gradient(to right, transparent, rgba(241, 245, 249, 0.5), transparent);
            position: relative;
            z-index: 1;
        }
        .student-details table {
            width: 100%;
        }
        .student-details th {
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 6px 0;
            width: 35%;
        }
        .student-details td {
            color: #0f172a;
            font-weight: 700;
            font-size: 14px;
            padding: 6px 0;
            border-bottom: 1px dashed #e2e8f0;
        }

        .marks-area {
            padding: 20px 50px;
            position: relative;
            z-index: 1;
            flex-grow: 1;
        }
        .marks-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        .marks-table th {
            background: #f8fafc;
            color: #334155;
            padding: 12px 15px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
        }
        .marks-table th:first-child { text-align: left; }
        
        .marks-table td {
            padding: 12px 15px;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
            text-align: center;
            color: #475569;
            font-weight: 500;
        }
        .marks-table tr:last-child td { border-bottom: none; }
        .marks-table tbody tr:hover td { background: #f8fafc; }
        
        .marks-table td:first-child {
            text-align: left;
            font-weight: 700;
            color: #0f172a;
        }
        
        .marks-table tfoot td {
            font-weight: 800;
            background: #f1f5f9;
            border-top: 2px solid #cbd5e1;
            font-size: 15px;
            color: #0f172a;
        }
        .text-fail { color: #e11d48 !important; font-weight: 800 !important; }

        .result-summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 10px 50px 30px;
            position: relative;
            z-index: 1;
        }
        .result-summary-row .box {
            text-align: center;
            padding: 15px 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .result-summary-row .box:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }
        .result-summary-row .box .lbl {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .result-summary-row .box .val {
            font-size: 24px;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
        }
        
        .box-pct { border-bottom: 3px solid #3b82f6 !important; }
        .box-pct .val { color: #2563eb; }
        .box-grd { border-bottom: 3px solid #8b5cf6 !important; }
        .box-grd .val { color: #7c3aed; }
        .box-res { border-bottom: 3px solid #10b981 !important; }
        .box-res .val { color: #059669; }
        .box-res.fail { border-bottom: 3px solid #ef4444 !important; background: #fef2f2; }
        .box-res.fail .val { color: #dc2626; }
        .box-rnk { border-bottom: 3px solid #f59e0b !important; }
        .box-rnk .val { color: #d97706; }

        .signatures-row {
            display: flex;
            justify-content: space-between;
            padding: 30px 60px 40px;
            position: relative;
            z-index: 1;
        }
        .signatures-row .sig {
            text-align: center;
            font-size: 13px;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .signatures-row .sig .line {
            display: block;
            width: 160px;
            border-top: 1px solid #0f172a;
            margin-bottom: 8px;
        }

        .page-break { page-break-after: always; }

        @media print {
            body { background: white !important; margin: 0; padding: 0; }
            .no-print-area { display: none !important; }
            .report-card { 
                box-shadow: none !important; 
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 10mm;
                box-sizing: border-box;
                width: 210mm;
                height: 296mm;
                page-break-inside: avoid;
            }
            .report-card-inner {
                border: 2px solid #000;
            }
            .report-header h1 { color: #000 !important; }
            .report-header { border-bottom: 1px solid #000; }
            .student-details td { border-bottom: 1px dotted #000; color: #000; }
            .marks-table { border: 1px solid #000; }
            .marks-table th { background: #eee !important; border-bottom: 2px solid #000; color: #000; }
            .marks-table td { border-bottom: 1px solid #ccc; color: #000; }
            .marks-table tfoot td { border-top: 2px solid #000; background: #eee !important; }
            .result-summary-row .box { border: 1px solid #000 !important; box-shadow: none !important; }
            .box .val { color: #000 !important; }
            
            .report-header, .marks-table th, .result-summary-row .box, .report-card, .report-card-inner {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page { 
                size: A4;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print-area">
        <button class="btn btn-print-main me-3">
            <i class="fa-solid fa-print me-2"></i>Print All Result Sheets (<?= count($students) ?> Students)
        </button>
        <a href="../dashboard.php" class="btn btn-back me-3">
            <i class="fa-solid fa-home me-2"></i>Back to Dashboard
        </a>
        <a href="manage.php" class="btn btn-back">
            <i class="fa-solid fa-arrow-left me-2"></i>Back
        </a>
        <div class="info-text">
            <i class="fa-solid fa-info-circle me-1"></i>
            <?= $class_info['name'] ?> — <?= $exam_term ?> — <?= count($students) ?> student(s)
        </div>
    </div>

    <?php 
    $student_count = count($students);
    foreach($students as $index => $student): 
        $stu_marks = isset($marks_data[$student['id']]) ? $marks_data[$student['id']] : [];
        
        // Calculate results
        $total_obtained = 0;
        $total_max = 0;
        $has_failed = false;
        $marks_list = [];
        
        foreach($subjects as $sub) {
            if(isset($stu_marks[$sub['id']])) {
                $m = $stu_marks[$sub['id']];
                $obtained = $m['obtained_marks'];
                $max = $m['total_marks'];
                $total_obtained += $obtained;
                $total_max += $max;
                if($obtained < ($max * 0.33)) $has_failed = true;
                $marks_list[] = [
                    'subject' => $sub['name'],
                    'max' => $max,
                    'obtained' => $obtained
                ];
            } else {
                $total_max += $sub['total_marks'];
                $has_failed = true;
                $marks_list[] = [
                    'subject' => $sub['name'],
                    'max' => $sub['total_marks'],
                    'obtained' => 0
                ];
            }
        }
        
        $percentage = ($total_max > 0) ? ($total_obtained / $total_max) * 100 : 0;
        $grade = 'F';
        if($percentage >= 90) $grade = 'A+';
        elseif($percentage >= 80) $grade = 'A';
        elseif($percentage >= 70) $grade = 'B';
        elseif($percentage >= 60) $grade = 'C';
        elseif($percentage >= 50) $grade = 'D';

        $status = ($has_failed || $percentage < 33) ? 'FAIL' : 'PASS';

        $rank = isset($ranks[$student['id']]) ? $ranks[$student['id']] : '-';
        $rank_str = $rank;
        if(is_numeric($rank)) {
            if($rank == 1) $rank_str .= 'st';
            elseif($rank == 2) $rank_str .= 'nd';
            elseif($rank == 3) $rank_str .= 'rd';
            else $rank_str .= 'th';
        }
        if($status == 'FAIL') $rank_str = '-';
    ?>
    
    <div class="report-card">
        <div class="watermark">Aslam School</div>
        
        <div class="report-card-inner">
            <!-- Header -->
            <div class="report-header">
                <h1>Aslam School</h1>
                <div class="sub-text">Pirkalay Matta Swat &nbsp;&bull;&nbsp; Call 0343-7727490</div>
                <div class="title-badge">Academic Result Sheet</div>
            </div>

            <!-- Student Details -->
            <div class="student-details">
                <div class="row align-items-center">
                    <div class="col-2 text-center">
                        <?php if(!empty($student['photo'])): ?>
                            <img src="../uploads/students/<?= $student['photo'] ?>" alt="Photo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #cbd5e1;">
                        <?php else: ?>
                            <div style="width: 100px; height: 100px; border-radius: 8px; border: 2px dashed #cbd5e1; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:12px; margin:auto; background:white;">No Photo</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-5">
                        <table>
                            <tr><th>Student Name</th><td><?= strtoupper($student['name']) ?></td></tr>
                            <tr><th>Father's Name</th><td><?= strtoupper($student['father_name']) ?></td></tr>
                            <tr><th>Date of Birth</th><td><?= date('d-M-Y', strtotime($student['dob'])) ?></td></tr>
                        </table>
                    </div>
                    <div class="col-5">
                        <table>
                            <tr><th>Roll No</th><td><?= $student['admission_no'] ?></td></tr>
                            <tr><th>Class</th><td><?= $student['class_name'] ?></td></tr>
                            <tr><th>Exam Term</th><td><?= $exam_term ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Marks Table -->
            <div class="marks-area">
                <table class="marks-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="text-align:left;">Subject</th>
                            <th style="width:120px;">Max Marks</th>
                            <th style="width:120px;">Obtained</th>
                            <th style="width:120px;">Remarks</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php 
                    $sr = 1;
                    foreach($marks_list as $m): 
                        $sub_pct = ($m['max'] > 0) ? ($m['obtained'] / $m['max']) * 100 : 0;
                        $remark = 'Poor';
                        if($sub_pct >= 90) $remark = 'Excellent';
                        elseif($sub_pct >= 75) $remark = 'Very Good';
                        elseif($sub_pct >= 60) $remark = 'Good';
                        elseif($sub_pct >= 40) $remark = 'Average';
                    ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td style="text-align:left; font-weight:600;"><?= $m['subject'] ?></td>
                        <td><?= $m['max'] ?></td>
                        <td class="<?= $m['obtained'] < ($m['max'] * 0.33) ? 'text-fail' : '' ?>" style="font-weight:700;"><?= $m['obtained'] ?></td>
                        <td style="font-size:11px;"><?= $remark ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td style="text-align:right; padding-right:20px;">GRAND TOTAL</td>
                        <td><?= $total_max ?></td>
                        <td><?= $total_obtained ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Result Summary -->
        <div class="result-summary-row">
            <div class="box box-pct">
                <div class="lbl">Percentage</div>
                <div class="val"><?= number_format($percentage, 1) ?>%</div>
            </div>
            <div class="box box-grd">
                <div class="lbl">Grade</div>
                <div class="val"><?= $grade ?></div>
            </div>
            <div class="box box-res <?= $status == 'FAIL' ? 'fail' : '' ?>">
                <div class="lbl">Result</div>
                <div class="val"><?= $status ?></div>
            </div>
            <div class="box box-rnk">
                <div class="lbl">Position</div>
                <div class="val"><?= $rank_str ?></div>
            </div>
        </div>

            <!-- Signatures -->
            <div class="signatures-row">
                <div class="sig">
                    <span class="line"></span>
                    Class Teacher
                </div>
                <div class="sig">
                    <span class="line"></span>
                    Principal
                </div>
            </div>
        </div>
    </div>

    <?php if($index < $student_count - 1): ?>
        <div class="page-break"></div>
    <?php endif; ?>

    <?php endforeach; ?>

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
