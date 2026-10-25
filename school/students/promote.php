<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'promote') {
    $to_class = (int)$_POST['to_class'];
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

    if (empty($student_ids)) {
        $message = "Please select at least one student to promote.";
        $messageType = "danger";
    } elseif ($to_class <= 0) {
        $message = "Please select a valid destination class.";
        $messageType = "danger";
    } else {
        $success_count = 0;
        foreach ($student_ids as $id) {
            $id = (int)$id;
            $stmt = $conn->prepare("UPDATE students SET class_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $to_class, $id);
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        $message = "$success_count student(s) promoted successfully.";
        $messageType = "success";
    }
}

$from_class = isset($_GET['from_class']) ? (int)$_GET['from_class'] : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Promote Students</h2>
    <a href="manage.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Students</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
<?php endif; ?>

<div class="card shadow border-0 rounded-4 mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold">Select Class to Promote From:</label>
            </div>
            <div class="col-md-4">
                <select name="from_class" class="form-select" onchange="this.form.submit()">
                    <option value="0">-- Select Class --</option>
                    <?php
                    $classes = $conn->query("SELECT * FROM classes");
                    while($c = $classes->fetch_assoc()) {
                        $sel = ($from_class == $c['id']) ? 'selected' : '';
                        echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
                    }
                    ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($from_class > 0): ?>
<div class="card shadow border-0 rounded-4">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="promote">
            
            <div class="row mb-4 align-items-center bg-light p-3 rounded">
                <div class="col-auto">
                    <label class="fw-bold">Promote Selected Students To:</label>
                </div>
                <div class="col-md-4">
                    <select name="to_class" class="form-select" required>
                        <option value="">-- Select Destination Class --</option>
                        <?php
                        $classes = $conn->query("SELECT * FROM classes");
                        while($c = $classes->fetch_assoc()) {
                            if ($c['id'] != $from_class) {
                                echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-rocket"></i> Promote Students</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="selectAll" checked class="form-check-input"></th>
                            <th>Adm No</th>
                            <th>Name</th>
                            <th>Father's Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $students = $conn->query("SELECT * FROM students WHERE class_id = $from_class ORDER BY name ASC");
                        if($students->num_rows > 0):
                            while($row = $students->fetch_assoc()):
                        ?>
                        <tr>
                            <td><input type="checkbox" name="student_ids[]" value="<?= $row['id'] ?>" checked class="form-check-input student-checkbox"></td>
                            <td><?= $row['admission_no'] ?></td>
                            <td><?= $row['name'] ?></td>
                            <td><?= $row['father_name'] ?></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center">No students found in this class</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.student-checkbox');
    for (var checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
