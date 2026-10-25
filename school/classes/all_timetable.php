<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// Fetch all classes
$classes_res = $conn->query("SELECT * FROM classes ORDER BY id ASC");
$classes = [];
while($c = $classes_res->fetch_assoc()) {
    $classes[] = $c;
}

// Fetch all periods defined across school
$periods_res = $conn->query("SELECT DISTINCT period_no, start_time, end_time FROM timetable ORDER BY period_no ASC, start_time ASC");
$periods = [];
while($p = $periods_res->fetch_assoc()) {
    $periods[] = $p;
}

// If default 8 periods don't exist yet in DB, create standard 8 slots list for display
if(count($periods) == 0) {
    $periods = [
        ["period_no" => 1, "start_time" => "09:00 AM", "end_time" => "09:30 AM"],
        ["period_no" => 2, "start_time" => "09:30 AM", "end_time" => "10:00 AM"],
        ["period_no" => 3, "start_time" => "10:00 AM", "end_time" => "10:30 AM"],
        ["period_no" => 4, "start_time" => "10:30 AM", "end_time" => "11:00 AM"],
        ["period_no" => 5, "start_time" => "11:00 AM", "end_time" => "11:30 AM"],
        ["period_no" => 6, "start_time" => "11:30 AM", "end_time" => "12:00 PM"],
        ["period_no" => 7, "start_time" => "12:00 PM", "end_time" => "12:30 PM"],
        ["period_no" => 8, "start_time" => "12:30 PM", "end_time" => "01:00 PM"]
    ];
}

// Fetch all timetable data into a 2D lookup array: $matrix[period_no][class_id]
$matrix = [];
$raw_tt = $conn->query("SELECT tt.*, s.name as subject_name, t.name as teacher_name 
                        FROM timetable tt 
                        LEFT JOIN subjects s ON tt.subject_id = s.id 
                        LEFT JOIN teachers t ON tt.teacher_id = t.id");
while($row = $raw_tt->fetch_assoc()) {
    $matrix[$row['period_no']][$row['class_id']] = $row;
}
?>

<style>
@media print {
    #sidebar-wrapper, .navbar, .no-print { display: none !important; }
    #page-content-wrapper { padding: 0 !important; margin: 0 !important; width: 100% !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table-responsive { overflow: visible !important; }
    body { background: #fff !important; font-size: 12px; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2><i class="fa-solid fa-calendar-week me-2 text-primary"></i>All School Master Timetable</h2>
    <div class="btn-group">
        <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print me-1"></i> Print Master Timetable</button>
        <a href="timetable.php" class="btn btn-outline-secondary"><i class="fa-solid fa-pen-to-square me-1"></i> Edit Class Timetables</a>
    </div>
</div>

<!-- Printable Header Info -->
<div class="text-center mb-4 d-none d-print-block">
    <h2 class="fw-bold text-uppercase text-primary mb-1"><i class="fa-solid fa-graduation-cap me-2"></i>Aslam School</h2>
    <h3 class="fw-bold text-dark mt-2">ALL SCHOOL MASTER CLASS TIMETABLE</h3>
    <p class="text-muted mb-0">School Time: 09:00 AM - 01:00 PM (30 Mints Periods)</p>
</div>

<div class="card shadow border-0 rounded-4 mb-4">
    <div class="card-header bg-primary text-white py-3 rounded-top-4 d-flex justify-content-between align-items-center no-print">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-border-all me-2"></i>Master Timetable Grid (All Classes)</h5>
        <span class="badge bg-light text-dark"><i class="fa-solid fa-clock me-1 text-warning"></i> 30-Minute Period Slots</span>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 140px;" class="align-middle">Period / Time</th>
                        <?php foreach($classes as $c): ?>
                            <th class="align-middle fs-6" style="min-width: 130px;"><?= htmlspecialchars($c['name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($periods as $p): ?>
                        <tr>
                            <td class="table-light fw-bold">
                                <div>Period <?= $p['period_no'] ?></div>
                                <small class="text-muted fw-normal"><?= htmlspecialchars($p['start_time']) ?> - <?= htmlspecialchars($p['end_time']) ?></small>
                            </td>
                            <?php foreach($classes as $c): ?>
                                <?php 
                                $cell = isset($matrix[$p['period_no']][$c['id']]) ? $matrix[$p['period_no']][$c['id']] : null;
                                ?>
                                <td class="p-2">
                                    <?php if($cell && !empty($cell['subject_name'])): ?>
                                        <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($cell['subject_name']) ?></div>
                                        <?php if(!empty($cell['teacher_name'])): ?>
                                            <span class="badge bg-success-subtle text-success border border-success small" style="font-size: 0.75rem;">
                                                <i class="fa-solid fa-user-tie me-1"></i><?= htmlspecialchars($cell['teacher_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <small class="text-muted fst-italic">No Teacher</small>
                                        <?php endif; ?>
                                    <?php elseif($cell && empty($cell['subject_name'])): ?>
                                        <span class="badge bg-warning text-dark"><i class="fa-solid fa-mug-hot me-1"></i>Break</span>
                                    <?php else: ?>
                                        <span class="text-muted opacity-50 small">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row pt-4 d-none d-print-block">
    <div class="col-6 text-center">
        <div style="border-top: 1px dashed #666; width: 60%; margin: 0 auto 5px auto;"></div>
        <small class="fw-bold">Time Table Incharge</small>
    </div>
    <div class="col-6 text-center">
        <div style="border-top: 1px dashed #666; width: 60%; margin: 0 auto 5px auto;"></div>
        <small class="fw-bold">Principal Signature</small>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
