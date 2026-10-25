<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $date      = $conn->real_escape_string($_POST['att_date']);
    $class_id  = (int)$_POST['class_id'];

    if (!empty($date) && $class_id > 0 && isset($_POST['attendance'])) {
        // Remove existing records for this date & class so we can re-save
        $conn->query("DELETE FROM attendance 
                      WHERE att_date = '$date' 
                      AND student_id IN (SELECT id FROM students WHERE class_id = $class_id)");

        $stmt = $conn->prepare("INSERT INTO attendance (student_id, att_date, status) VALUES (?, ?, ?)");
        foreach ($_POST['attendance'] as $student_id => $status) {
            $student_id = (int)$student_id;
            $status     = ($status === 'present') ? 'present' : 'absent';
            $stmt->bind_param("iss", $student_id, $date, $status);
            $stmt->execute();
        }
        $stmt->close();

        $message      = "Attendance saved successfully for " . date('d M Y', strtotime($date)) . "!";
        $message_type = "success";
    } else {
        $message      = "Please select a class and date before saving.";
        $message_type = "danger";
    }
}

// For pre-filling the form after save
$sel_class = isset($_POST['class_id']) ? (int)$_POST['class_id']   : (isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0);
$sel_date  = isset($_POST['att_date'])  ? $_POST['att_date']        : (isset($_GET['att_date'])  ? $_GET['att_date']     : date('Y-m-d'));

// Load classes
$classes = $conn->query("SELECT * FROM classes ORDER BY id");

// Load students for selected class
$students = [];
if ($sel_class > 0) {
    $res = $conn->query("SELECT * FROM students WHERE class_id = $sel_class ORDER BY name ASC");
    while ($r = $res->fetch_assoc()) {
        $students[] = $r;
    }
}

// Load existing attendance for that date/class
$existing = [];
if ($sel_class > 0 && $sel_date) {
    $esc_date = $conn->real_escape_string($sel_date);
    $att_res = $conn->query("SELECT student_id, status, att_time, method FROM attendance 
                             WHERE att_date = '$esc_date' 
                             AND student_id IN (SELECT id FROM students WHERE class_id = $sel_class)");
    while ($a = $att_res->fetch_assoc()) {
        $existing[$a['student_id']] = $a;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0"><i class="fa-solid fa-calendar-check me-2 text-primary"></i>Attendance Management</h2>
        <div class="text-muted small">View, record, or scan student daily attendance</div>
    </div>
    <div class="d-flex gap-2">
        <a href="face_attendance.php" class="btn btn-success">
            <i class="fa-solid fa-camera me-1"></i> Face Attendance Scanner
        </a>
        <a href="face_register.php" class="btn btn-outline-primary">
            <i class="fa-solid fa-id-card-clip me-1"></i> Register Faces
        </a>
        <a href="report.php" class="btn btn-outline-danger">
            <i class="fa-solid fa-user-xmark me-1"></i> Absent Report
        </a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
    <i class="fa-solid fa-<?= $message_type === 'success' ? 'circle-check' : 'triangle-exclamation' ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter Card -->
<div class="card shadow border-0 rounded-4 mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row align-items-end g-3" id="filterForm">
            <div class="col-md-4">
                <label class="form-label fw-bold">Select Class</label>
                <select name="class_id" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="0">-- Choose Class --</option>
                    <?php foreach ([] as $dummy): endforeach; ?>
                    <?php
                    $classes->data_seek(0);
                    while ($c = $classes->fetch_assoc()):
                        $sel = ($sel_class == $c['id']) ? 'selected' : '';
                    ?>
                    <option value="<?= $c['id'] ?>" <?= $sel ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Select Date</label>
                <input type="date" name="att_date" class="form-control" value="<?= htmlspecialchars($sel_date) ?>"
                       onchange="document.getElementById('filterForm').submit()">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter me-1"></i> Load
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($sel_class > 0 && count($students) > 0): ?>
<!-- Attendance Table -->
<div class="card shadow border-0 rounded-4">
    <div class="card-header bg-white border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">
            Students — <?php
                $classes->data_seek(0);
                while ($c = $classes->fetch_assoc()) {
                    if ($c['id'] == $sel_class) echo htmlspecialchars($c['name']);
                }
            ?>
            <span class="text-muted fs-6 ms-2"><?= date('d M Y', strtotime($sel_date)) ?></span>
        </h5>
        <div>
            <button type="button" class="btn btn-sm btn-success me-2" onclick="markAll('present')">
                <i class="fa-solid fa-check me-1"></i> All Present
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="markAll('absent')">
                <i class="fa-solid fa-times me-1"></i> All Absent
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <form method="POST">
            <input type="hidden" name="att_date"  value="<?= htmlspecialchars($sel_date) ?>">
            <input type="hidden" name="class_id"  value="<?= $sel_class ?>">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Adm No</th>
                            <th>Student Name</th>
                            <th>Father's Name</th>
                            <th class="text-center">Present</th>
                            <th class="text-center">Absent</th>
                            <th class="text-center">Status</th>
                            <th>Recorded Via</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $i => $st):
                        $rec = $existing[$st['id']] ?? null;
                        $curr_status = $rec ? $rec['status'] : 'present';
                        $att_time = $rec && $rec['att_time'] ? date('h:i A', strtotime($rec['att_time'])) : null;
                        $method = $rec ? $rec['method'] : null;
                    ?>
                        <tr id="row-<?= $st['id'] ?>">
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($st['admission_no']) ?></span></td>
                            <td class="fw-semibold"><?= htmlspecialchars($st['name']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($st['father_name']) ?></td>
                            <td class="text-center">
                                <div class="form-check d-inline-block">
                                    <input class="form-check-input att-radio" type="radio"
                                           name="attendance[<?= $st['id'] ?>]"
                                           id="p_<?= $st['id'] ?>"
                                           value="present"
                                           data-student="<?= $st['id'] ?>"
                                           <?= ($curr_status === 'present') ? 'checked' : '' ?>
                                           onchange="updateRow(<?= $st['id'] ?>, 'present')">
                                     <label class="form-check-label text-success fw-bold" for="p_<?= $st['id'] ?>">P</label>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="form-check d-inline-block">
                                    <input class="form-check-input att-radio" type="radio"
                                           name="attendance[<?= $st['id'] ?>]"
                                           id="a_<?= $st['id'] ?>"
                                           value="absent"
                                           data-student="<?= $st['id'] ?>"
                                           <?= ($curr_status === 'absent') ? 'checked' : '' ?>
                                           onchange="updateRow(<?= $st['id'] ?>, 'absent')">
                                     <label class="form-check-label text-danger fw-bold" for="a_<?= $st['id'] ?>">A</label>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $curr_status === 'present' ? 'bg-success' : 'bg-danger' ?>" id="badge-<?= $st['id'] ?>">
                                    <?= ucfirst($curr_status) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($rec && $method === 'face_recognition'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" title="Marked automatically by camera">
                                        <i class="fa-solid fa-camera me-1"></i>Face ID <?= $att_time ? "($att_time)" : "" ?>
                                    </span>
                                <?php elseif ($rec): ?>
                                    <span class="badge bg-light text-muted border">
                                        Manual <?= $att_time ? "($att_time)" : "" ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top bg-light rounded-bottom-4 d-flex justify-content-between align-items-center">
                <span class="text-muted"><i class="fa-solid fa-info-circle me-1"></i> Total Students: <strong><?= count($students) ?></strong></span>
                <button type="submit" name="save_attendance" class="btn btn-primary px-5">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Attendance
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($sel_class > 0): ?>
<div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>No students found in the selected class.</div>
<?php else: ?>
<div class="alert alert-info"><i class="fa-solid fa-arrow-up me-2"></i>Please select a class and date to mark attendance.</div>
<?php endif; ?>

<script>
function updateRow(studentId, status) {
    const row   = document.getElementById('row-' + studentId);
    const badge = document.getElementById('badge-' + studentId);
    if (status === 'absent') {
        row.classList.add('table-danger');
        row.classList.remove('table-success');
        badge.className = 'badge bg-danger';
        badge.textContent = 'Absent';
    } else {
        row.classList.add('table-success');
        row.classList.remove('table-danger');
        badge.className = 'badge bg-success';
        badge.textContent = 'Present';
    }
}

function markAll(status) {
    document.querySelectorAll('.att-radio[value="' + status + '"]').forEach(radio => {
        radio.checked = true;
        updateRow(radio.dataset.student, status);
    });
}

// Colour rows on initial load
document.querySelectorAll('.att-radio:checked').forEach(radio => {
    updateRow(radio.dataset.student, radio.value);
});
</script>

<?php require_once '../includes/footer.php'; ?>
