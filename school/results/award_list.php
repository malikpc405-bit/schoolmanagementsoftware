<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /school/index.php"); exit(); }
require_once '../includes/db.php';

if(!isset($_GET['class_id']) || !isset($_GET['exam_term'])) die("Invalid Request");

$class_id = (int)$_GET['class_id'];
$exam_term = $conn->real_escape_string($_GET['exam_term']);

$class_info = $conn->query("SELECT name FROM classes WHERE id = $class_id")->fetch_assoc();
if(!$class_info) die("Class not found");

// Get subjects
$subjects_q = $conn->query("SELECT * FROM subjects WHERE class_id = $class_id ORDER BY id");
$subjects = [];
while($s = $subjects_q->fetch_assoc()) {
    $subjects[] = $s;
}

// Get all students in class
$students_q = $conn->query("SELECT * FROM students WHERE class_id = $class_id");
$students = [];
while($s = $students_q->fetch_assoc()) {
    $students[$s['id']] = $s;
}

// Get all marks for this class and term
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

// Calculate totals and compile student records
$compiled_students = [];
foreach($students as $stu_id => $stu) {
    $total_obtained = 0;
    $total_max = 0;
    $has_failed = false;
    $stu_marks = isset($marks_data[$stu_id]) ? $marks_data[$stu_id] : [];
    
    foreach($subjects as $sub) {
        if(isset($stu_marks[$sub['id']])) {
            $obtained = $stu_marks[$sub['id']]['obtained_marks'];
            $max = $stu_marks[$sub['id']]['total_marks'];
            $total_obtained += $obtained;
            $total_max += $max;
            if($obtained < ($max * 0.33)) $has_failed = true;
        } else {
            // Assume 0 if marks not entered
            $total_max += $sub['total_marks']; // Default max is from subject
            $has_failed = true;
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
    
    $compiled_students[] = [
        'id' => $stu_id,
        'name' => $stu['name'],
        'admission_no' => $stu['admission_no'],
        'marks' => $stu_marks,
        'total' => $total_obtained,
        'percentage' => $percentage,
        'grade' => $grade,
        'status' => $status
    ];
}

// Sort students by total marks descending to assign positions
usort($compiled_students, function($a, $b) {
    return $b['total'] <=> $a['total'];
});

// Assign Positions
$rank = 1;
foreach($compiled_students as $key => $stu) {
    // Only pass students get a position, or everyone gets a position?
    // Let's give everyone a position based on marks, but add a 1st, 2nd, 3rd label.
    $compiled_students[$key]['position'] = $rank;
    $rank++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Award List - <?= $class_info['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; font-family: Arial, sans-serif; font-size: 12px; }
        .school-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; margin-top: 20px;}
        .school-header h2 { margin: 0; font-weight: bold; font-size: 22px; }
        .school-header p { margin: 0; font-size: 14px; }
        table th, table td { vertical-align: middle; text-align: center; border: 1px solid #000 !important; }
        table th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; }
        .text-left { text-align: left !important; }
        .pos-1 { background-color: #ffd700 !important; font-weight: bold; } /* Gold */
        .pos-2 { background-color: #e3e4e5 !important; font-weight: bold; } /* Silver */
        .pos-3 { background-color: #cd7f32 !important; font-weight: bold; } /* Bronze */
        @media print {
            .no-print { display: none; }
            @page { size: landscape; }
        }
    </style>
</head>
<body>
    <div class="text-center mt-3 no-print">
        <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print Award List</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="container-fluid px-4">
        <div class="school-header">
            <h2>ASLAM SCHOOL</h2>
            <p>123 Education Street, City, Country</p>
            <h4 class="mt-3">CONSOLIDATED AWARD LIST / MERIT LIST</h4>
            <p class="fw-bold">Class: <?= strtoupper($class_info['name']) ?> | Exam Term: <?= $exam_term ?></p>
        </div>

        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th rowspan="2" width="40">Pos.</th>
                    <th rowspan="2" width="80">Adm No</th>
                    <th rowspan="2" class="text-left">Student Name</th>
                    <th colspan="<?= count($subjects) ?>">Subjects (Marks Obtained)</th>
                    <th rowspan="2" width="60">Total</th>
                    <th rowspan="2" width="60">Pct %</th>
                    <th rowspan="2" width="60">Grade</th>
                    <th rowspan="2" width="60">Status</th>
                </tr>
                <tr>
                    <?php foreach($subjects as $sub): ?>
                        <th><?= substr($sub['name'], 0, 8) ?>.<br><small>(<?= $sub['total_marks'] ?>)</small></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                if(count($compiled_students) > 0):
                    foreach($compiled_students as $stu): 
                        $pos_class = '';
                        if($stu['position'] == 1) $pos_class = 'pos-1';
                        elseif($stu['position'] == 2) $pos_class = 'pos-2';
                        elseif($stu['position'] == 3) $pos_class = 'pos-3';
                ?>
                <tr class="<?= $pos_class ?>">
                    <td class="fs-6"><?= $stu['position'] ?></td>
                    <td><?= $stu['admission_no'] ?></td>
                    <td class="text-left fw-bold"><?= strtoupper($stu['name']) ?></td>
                    <?php foreach($subjects as $sub): 
                        $mark = isset($stu['marks'][$sub['id']]) ? $stu['marks'][$sub['id']]['obtained_marks'] : '-';
                    ?>
                        <td><?= $mark ?></td>
                    <?php endforeach; ?>
                    <td class="fw-bold"><?= $stu['total'] ?></td>
                    <td><?= number_format($stu['percentage'], 1) ?>%</td>
                    <td><?= $stu['grade'] ?></td>
                    <td class="<?= $stu['status']=='FAIL' ? 'text-danger fw-bold' : 'text-success fw-bold' ?>"><?= $stu['status'] ?></td>
                </tr>
                <?php 
                    endforeach; 
                else: 
                ?>
                <tr>
                    <td colspan="<?= count($subjects) + 7 ?>" class="text-center py-4">No student records found for this class.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="mt-5 d-flex justify-content-between no-print" style="max-width: 800px; margin: auto;">
            <div class="text-center border-top pt-2" style="width: 200px;">Class Teacher</div>
            <div class="text-center border-top pt-2" style="width: 200px;">Exam Controller</div>
            <div class="text-center border-top pt-2" style="width: 200px;">Principal</div>
        </div>
    </div>
</body>
</html>
