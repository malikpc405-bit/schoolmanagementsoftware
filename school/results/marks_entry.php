<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if(!isset($_GET['class_id']) || !isset($_GET['exam_term'])) {
    die("Invalid request");
}

$class_id = (int)$_GET['class_id'];
$exam_term = $conn->real_escape_string($_GET['exam_term']);

$class_info = $conn->query("SELECT name FROM classes WHERE id = $class_id")->fetch_assoc();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach($_POST['marks'] as $student_id => $subjects) {
        foreach($subjects as $subject_id => $obtained) {
            if($obtained !== '') {
                $obtained = (int)$obtained;
                $subject_id = (int)$subject_id;
                $student_id = (int)$student_id;
                
                // Check if exists
                $check = $conn->query("SELECT id FROM marks WHERE student_id=$student_id AND subject_id=$subject_id AND exam_term='$exam_term'");
                $sub_total_res = $conn->query("SELECT total_marks FROM subjects WHERE id=$subject_id");
                $sub_total = $sub_total_res->num_rows > 0 ? $sub_total_res->fetch_assoc()['total_marks'] : 100;
                
                if($check->num_rows > 0) {
                    $conn->query("UPDATE marks SET obtained_marks = $obtained, total_marks = $sub_total WHERE student_id=$student_id AND subject_id=$subject_id AND exam_term='$exam_term'");
                } else {
                    $conn->query("INSERT INTO marks (student_id, subject_id, exam_term, obtained_marks, total_marks) VALUES ($student_id, $subject_id, '$exam_term', $obtained, $sub_total)");
                }
            }
        }
    }
    echo "<div class='alert alert-success'>Marks saved successfully!</div>";
}

$students = $conn->query("SELECT * FROM students WHERE class_id = $class_id ORDER BY name");
$subjects = $conn->query("SELECT * FROM subjects WHERE class_id = $class_id ORDER BY name");
$subject_list = [];
while($sub = $subjects->fetch_assoc()) $subject_list[] = $sub;

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Marks Entry: <?= $class_info['name'] ?> - <?= $exam_term ?></h2>
    <a href="manage.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<?php if(count($subject_list) == 0): ?>
    <div class="alert alert-warning">No subjects assigned to this class. Please add subjects first.</div>
<?php elseif($students->num_rows == 0): ?>
    <div class="alert alert-warning">No students found in this class.</div>
<?php else: ?>
<div class="card shadow border-0 rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <form method="POST">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <?php foreach($subject_list as $sub): ?>
                                <th><?= $sub['name'] ?> (<?= $sub['total_marks'] ?? 100 ?>)</th>
            
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($stu = $students->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $stu['name'] ?></strong><br><small class="text-muted"><?= $stu['admission_no'] ?></small></td>
                            <?php foreach($subject_list as $sub): 
                                // Get existing mark
                                $existing = $conn->query("SELECT obtained_marks FROM marks WHERE student_id={$stu['id']} AND subject_id={$sub['id']} AND exam_term='$exam_term'");
                                $mark_val = $existing->num_rows > 0 ? $existing->fetch_assoc()['obtained_marks'] : '';
                            ?>
                                <td>
                                    <input type="number" name="marks[<?= $stu['id'] ?>][<?= $sub['id'] ?>]" class="form-control form-control-sm" value="<?= $mark_val ?>" min="0" max="<?= $sub['total_marks'] ?? 100 ?>">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="p-3 bg-light text-end border-top">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Marks</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
