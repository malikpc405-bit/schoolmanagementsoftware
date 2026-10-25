<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$error = '';
$success = '';

$selected_teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teacher_id = (int)$_POST['teacher_id'];
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $duration = (int)$_POST['duration'];
    $start_time = isset($_POST['start_time']) ? $conn->real_escape_string($_POST['start_time']) : '';

    if($teacher_id <= 0 || $class_id <= 0 || $subject_id <= 0) {
        $error = 'Please select Teacher, Class, and Subject.';
    } else {
        $stmt = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, class_id, subject_id, duration_mins, start_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $teacher_id, $class_id, $subject_id, $duration, $start_time);
        if($stmt->execute()) {
            $success = 'Class assigned successfully (30 Mins Period)!';
            $selected_teacher_id = $teacher_id; // Keep selected teacher active
        } else {
            $error = 'Error assigning class: ' . $conn->error;
        }
    }
}

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM teacher_assignments WHERE id = $id");
    $success = 'Assignment removed successfully.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Assign 30-Minute Classes to Teachers</h2>
    <a href="manage.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back to Teachers</a>
</div>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Assignment Form -->
    <div class="col-md-4 mb-4">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-primary text-white py-3 rounded-top-4">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>Assign Class Slot</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Teacher <span class="text-danger">*</span></label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">-- Select Teacher --</option>
                            <?php
                            $teachers = $conn->query("SELECT id, name, qualification FROM teachers WHERE status='Active' ORDER BY name ASC");
                            while($t = $teachers->fetch_assoc()) {
                                $selected = ($t['id'] == $selected_teacher_id) ? 'selected' : '';
                                echo "<option value='{$t['id']}' $selected>" . htmlspecialchars($t['name']) . " (" . htmlspecialchars($t['qualification']) . ")</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" id="classSelect" onchange="fetchSubjects()" required>
                            <option value="">-- Select Class --</option>
                            <?php
                            $classes = $conn->query("SELECT id, name FROM classes ORDER BY id ASC");
                            while($c = $classes->fetch_assoc()) {
                                echo "<option value='{$c['id']}'>" . htmlspecialchars($c['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" id="subjectSelect" required>
                            <option value="">-- Select Class First --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Time Slot / Period Start</label>
                        <input type="text" name="start_time" class="form-control" placeholder="e.g. 08:30 AM or Period 1">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Class Duration (Minutes) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="duration" class="form-control" value="30" required min="5" max="180">
                            <span class="input-group-text">Minutes</span>
                        </div>
                        <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i> Standard period duration is 30 Mints</small>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2"><i class="fa-solid fa-plus-circle me-1"></i> Assign Class Slot</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Assignments List -->
    <div class="col-md-8">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i>Current Assigned Classes</h5>
                <?php if($selected_teacher_id > 0): ?>
                    <a href="assign.php" class="btn btn-sm btn-outline-secondary">Show All Teachers</a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Teacher</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Time Slot</th>
                                <th>Duration</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $where_assign = $selected_teacher_id > 0 ? "WHERE ta.teacher_id = $selected_teacher_id" : "";
                            $sql = "SELECT ta.*, t.name as teacher_name, c.name as class_name, s.name as subject_name 
                                    FROM teacher_assignments ta
                                    JOIN teachers t ON ta.teacher_id = t.id
                                    JOIN classes c ON ta.class_id = c.id
                                    JOIN subjects s ON ta.subject_id = s.id
                                    $where_assign
                                    ORDER BY t.name ASC, ta.id DESC";
                            $result = $conn->query($sql);
                            if($result && $result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['teacher_name']) ?></div>
                                </td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($row['class_name']) ?></span></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['subject_name']) ?></span></td>
                                <td>
                                    <?php if(!empty($row['start_time'])): ?>
                                        <small class="badge bg-light text-dark border"><i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($row['start_time']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted small">Flex Time</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success">
                                        <i class="fa-solid fa-stopwatch me-1"></i><?= $row['duration_mins'] ?> Mins
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="assign.php?delete=<?= $row['id'] ?><?= $selected_teacher_id > 0 ? '&teacher_id='.$selected_teacher_id : '' ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this class assignment?')" title="Remove Assignment">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-calendar-xmark fs-3 d-block mb-2"></i>
                                    No class assignments found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fetchSubjects() {
    var classId = document.getElementById('classSelect').value;
    var subjectSelect = document.getElementById('subjectSelect');
    
    subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
    
    if(classId == '') {
        subjectSelect.innerHTML = '<option value="">-- Select Class First --</option>';
        return;
    }
    
    fetch('../subjects/get_subjects.php?class_id=' + classId)
        .then(response => response.text())
        .then(data => {
            if(data.trim() == '') {
                subjectSelect.innerHTML = '<option value="">No subjects found for this class</option>';
            } else {
                subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>' + data;
            }
        })
        .catch(err => {
            console.error(err);
            subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
