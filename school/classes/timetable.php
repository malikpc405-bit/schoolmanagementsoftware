<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$success = '';
$error = '';

$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($selected_class_id == 0) {
    // Pick first class if available
    $first_class = $conn->query("SELECT id FROM classes ORDER BY id ASC LIMIT 1")->fetch_assoc();
    if ($first_class) {
        $selected_class_id = $first_class['id'];
    }
}

// Auto Generate Standard Schedule starting at 9:00 AM (30 mins slots)
if (isset($_POST['auto_generate'])) {
    $class_id = (int)$_POST['class_id'];
    $start_hour = 9; // 9:00 AM
    $start_min = 0;
    $duration = 30; // 30 minutes

    // Clear existing for this class if requested
    $conn->query("DELETE FROM timetable WHERE class_id = $class_id");

    $slots = [
        ["p" => 1, "start" => "09:00 AM", "end" => "09:30 AM"],
        ["p" => 2, "start" => "09:30 AM", "end" => "10:00 AM"],
        ["p" => 3, "start" => "10:00 AM", "end" => "10:30 AM"],
        ["p" => 4, "start" => "10:30 AM", "end" => "11:00 AM"],
        ["p" => 5, "start" => "11:00 AM", "end" => "11:30 AM"],
        ["p" => 6, "start" => "11:30 AM", "end" => "12:00 PM"],
        ["p" => 7, "start" => "12:00 PM", "end" => "12:30 PM"],
        ["p" => 8, "start" => "12:30 PM", "end" => "01:00 PM"]
    ];

    $stmt = $conn->prepare("INSERT INTO timetable (class_id, period_no, start_time, end_time) VALUES (?, ?, ?, ?)");
    foreach ($slots as $slot) {
        $stmt->bind_param("iiss", $class_id, $slot['p'], $slot['start'], $slot['end']);
        $stmt->execute();
    }
    $success = "Generated standard 30-minute period schedule starting at 9:00 AM!";
}

// Save / Update Timetable
if (isset($_POST['save_timetable'])) {
    $class_id = (int)$_POST['class_id'];
    $periods = $_POST['periods']; // Array of period details

    foreach ($periods as $period_id => $data) {
        $start_time = $conn->real_escape_string($data['start_time']);
        $end_time = $conn->real_escape_string($data['end_time']);
        $subject_id = !empty($data['subject_id']) ? (int)$data['subject_id'] : 'NULL';
        $teacher_id = !empty($data['teacher_id']) ? (int)$data['teacher_id'] : 'NULL';
        $period_no = (int)$data['period_no'];

        if ($period_id > 0) {
            $conn->query("UPDATE timetable SET 
                period_no = $period_no,
                start_time = '$start_time', 
                end_time = '$end_time', 
                subject_id = $subject_id, 
                teacher_id = $teacher_id 
                WHERE id = $period_id AND class_id = $class_id");
        } else {
            $conn->query("INSERT INTO timetable (class_id, period_no, start_time, end_time, subject_id, teacher_id) 
                VALUES ($class_id, $period_no, '$start_time', '$end_time', $subject_id, $teacher_id)");
        }
    }

    // Also sync teacher assignments table if subject and teacher are selected
    foreach ($periods as $data) {
        if (!empty($data['subject_id']) && !empty($data['teacher_id'])) {
            $sub_id = (int)$data['subject_id'];
            $tch_id = (int)$data['teacher_id'];
            $st_time = $conn->real_escape_string($data['start_time']);
            // Check if already in teacher_assignments
            $chk = $conn->query("SELECT id FROM teacher_assignments WHERE teacher_id = $tch_id AND class_id = $class_id AND subject_id = $sub_id");
            if ($chk && $chk->num_rows == 0) {
                $conn->query("INSERT INTO teacher_assignments (teacher_id, class_id, subject_id, duration_mins, start_time) VALUES ($tch_id, $class_id, $sub_id, 30, '$st_time')");
            }
        }
    }

    $success = "Class Timetable updated successfully!";
}

// Add New Single Period Row
if (isset($_POST['add_period'])) {
    $class_id = (int)$_POST['class_id'];
    $period_no = (int)$_POST['period_no'];
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);

    $conn->query("INSERT INTO timetable (class_id, period_no, start_time, end_time) VALUES ($class_id, $period_no, '$start_time', '$end_time')");
    $success = "Period added to timetable!";
}

// Delete Period Slot
if (isset($_GET['delete_period'])) {
    $p_id = (int)$_GET['delete_period'];
    $conn->query("DELETE FROM timetable WHERE id = $p_id");
    $success = "Period removed from timetable.";
}

// Fetch all classes
$classes = $conn->query("SELECT * FROM classes ORDER BY id ASC");

// Fetch class details
$current_class = null;
if ($selected_class_id > 0) {
    $current_class = $conn->query("SELECT * FROM classes WHERE id = $selected_class_id")->fetch_assoc();
}

// Fetch subjects for this class
$subjects_res = $conn->query("SELECT id, name FROM subjects WHERE class_id = $selected_class_id ORDER BY name ASC");
$subjects_list = [];
while ($s = $subjects_res->fetch_assoc()) {
    $subjects_list[] = $s;
}

// Fetch active teachers
$teachers_res = $conn->query("SELECT id, name FROM teachers WHERE status='Active' ORDER BY name ASC");
$teachers_list = [];
while ($t = $teachers_res->fetch_assoc()) {
    $teachers_list[] = $t;
}

// Fetch current timetable for selected class
$timetable_items = [];
if ($selected_class_id > 0) {
    $res = $conn->query("SELECT * FROM timetable WHERE class_id = $selected_class_id ORDER BY period_no ASC, start_time ASC");
    while ($row = $res->fetch_assoc()) {
        $timetable_items[] = $row;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-calendar-days me-2 text-primary"></i>Class Timetable Management</h2>
    <div class="btn-group">
        <a href="all_timetable.php" class="btn btn-outline-primary"><i class="fa-solid fa-calendar-week me-1"></i> View All School Timetable</a>
        <?php if ($selected_class_id > 0): ?>
            <a href="print_timetable.php?class_id=<?= $selected_class_id ?>" target="_blank" class="btn btn-outline-dark"><i class="fa-solid fa-print me-1"></i> Print Timetable</a>
        <?php endif; ?>
        <a href="manage.php" class="btn btn-secondary"><i class="fa-solid fa-chalkboard me-1"></i> Manage Classes</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Class Selection Card -->
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <label class="form-label fw-bold mb-1">Select Class to View/Edit Timetable</label>
                <select name="class_id" class="form-select" onchange="this.form.submit()">
                    <?php
                    $classes->data_seek(0);
                    while ($c = $classes->fetch_assoc()):
                    ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $selected_class_id ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-7 d-flex align-items-end gap-2 mt-md-4">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-1"></i> Load Timetable</button>
                <?php if ($selected_class_id > 0): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reset and auto-generate 30-min periods starting at 9:00 AM for this class?')">
                        <input type="hidden" name="class_id" value="<?= $selected_class_id ?>">
                        <button type="submit" name="auto_generate" class="btn btn-outline-success">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto-Set 30m Slots (9:00 AM Start)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($current_class): ?>
    <div class="card shadow border-0 rounded-4 mb-4">
        <div class="card-header bg-primary text-white py-3 rounded-top-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-clock me-2"></i>Timetable Schedule: <?= htmlspecialchars($current_class['name']) ?>
            </h5>
            <span class="badge bg-light text-dark"><i class="fa-solid fa-sun me-1 text-warning"></i> Standard School Start: 9:00 AM (30 Mins Periods)</span>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="class_id" value="<?= $selected_class_id ?>">

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 90px;">Period #</th>
                                <th style="width: 170px;">Start Time</th>
                                <th style="width: 170px;">End Time</th>
                                <th>Subject</th>
                                <th>Assigned Teacher</th>
                                <th style="width: 80px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($timetable_items) > 0): ?>
                                <?php foreach ($timetable_items as $index => $item): ?>
                                    <tr>
                                        <td>
                                            <input type="number" name="periods[<?= $item['id'] ?>][period_no]" class="form-control text-center fw-bold" value="<?= $item['period_no'] ?>" min="1" required>
                                        </td>
                                        <td>
                                            <input type="text" name="periods[<?= $item['id'] ?>][start_time]" class="form-control" value="<?= htmlspecialchars($item['start_time']) ?>" placeholder="e.g. 09:00 AM" required>
                                        </td>
                                        <td>
                                            <input type="text" name="periods[<?= $item['id'] ?>][end_time]" class="form-control" value="<?= htmlspecialchars($item['end_time']) ?>" placeholder="e.g. 09:30 AM" required>
                                        </td>
                                        <td>
                                            <select name="periods[<?= $item['id'] ?>][subject_id]" class="form-select">
                                                <option value="">-- Select Subject / Break --</option>
                                                <?php foreach ($subjects_list as $sub): ?>
                                                    <option value="<?= $sub['id'] ?>" <?= $item['subject_id'] == $sub['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sub['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="periods[<?= $item['id'] ?>][teacher_id]" class="form-select">
                                                <option value="">-- Assign Teacher --</option>
                                                <?php foreach ($teachers_list as $tch): ?>
                                                    <option value="<?= $tch['id'] ?>" <?= $item['teacher_id'] == $tch['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tch['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <a href="timetable.php?class_id=<?= $selected_class_id ?>&delete_period=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this period slot?')" title="Delete Period">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-calendar-xmark fs-2 d-block mb-2"></i>
                                        No period timetable found for this class.<br>
                                        Click <strong>"Auto-Set 30m Slots (9:00 AM Start)"</strong> above to generate 8 standard periods automatically!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($timetable_items) > 0): ?>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="save_timetable" class="btn btn-success px-4 py-2 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Class Timetable
                        </button>
                        <a href="print_timetable.php?class_id=<?= $selected_class_id ?>" target="_blank" class="btn btn-outline-dark px-4 py-2">
                            <i class="fa-solid fa-print me-1"></i> Print Timetable
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Quick Add Single Period -->
    <div class="card shadow border-0 rounded-4">
        <div class="card-header bg-light py-3 border-0">
            <h6 class="fw-bold mb-0"><i class="fa-solid fa-plus-circle me-2 text-primary"></i>Add Custom Period Slot</h6>
        </div>
        <div class="card-body p-3">
            <form method="POST" class="row g-3 align-items-center">
                <input type="hidden" name="class_id" value="<?= $selected_class_id ?>">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Period #</label>
                    <input type="number" name="period_no" class="form-control" value="<?= count($timetable_items) + 1 ?>" required min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Start Time</label>
                    <input type="text" name="start_time" class="form-control" placeholder="e.g. 09:00 AM" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">End Time</label>
                    <input type="text" name="end_time" class="form-control" placeholder="e.g. 09:30 AM" required>
                </div>
                <div class="col-md-4 d-flex align-items-end mt-md-4">
                    <button type="submit" name="add_period" class="btn btn-primary w-100"><i class="fa-solid fa-plus me-1"></i> Add Period</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
