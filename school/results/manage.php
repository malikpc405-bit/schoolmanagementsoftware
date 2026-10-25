<?php
require_once '../includes/db.php';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Result Management</h2>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold">Enter Marks</h5>
            </div>
            <div class="card-body">
                <form action="marks_entry.php" method="GET">
                    <div class="mb-3">
                        <label class="form-label">Select Class</label>
                        <select name="class_id" class="form-select" required>
                            <?php
                            $classes = $conn->query("SELECT * FROM classes");
                            while($c = $classes->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Exam Term</label>
                        <select name="exam_term" class="form-select" required>
                            <option value="First Term">First Term</option>
                            <option value="Mid Term">Mid Term</option>
                            <option value="Final Term">Final Term</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Proceed to Enter Marks</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold">Individual Report Card</h5>
            </div>
            <div class="card-body">
                <form action="generate_report.php" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Select Student</label>
                        <select name="student_id" class="form-select" required>
                            <?php
                            $students = $conn->query("SELECT s.id, s.name, s.admission_no, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id ORDER BY s.name");
                            while($s = $students->fetch_assoc()) {
                                echo "<option value='{$s['id']}'>{$s['name']} ({$s['admission_no']}) - {$s['class_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Exam Term</label>
                        <select name="exam_term" class="form-select" required>
                            <option value="First Term">First Term</option>
                            <option value="Mid Term">Mid Term</option>
                            <option value="Final Term">Final Term</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-file-pdf"></i> Generate Report Card</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold">Class Award / Merit List</h5>
            </div>
            <div class="card-body">
                <form action="award_list.php" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Select Class</label>
                        <select name="class_id" class="form-select" required>
                            <?php
                            $classes_al = $conn->query("SELECT * FROM classes");
                            while($c = $classes_al->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Exam Term</label>
                        <select name="exam_term" class="form-select" required>
                            <option value="First Term">First Term</option>
                            <option value="Mid Term">Mid Term</option>
                            <option value="Final Term">Final Term</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info text-white w-100"><i class="fa-solid fa-trophy"></i> Print Award List</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Class-wise Result Sheets (Print All) -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow border-0 rounded-4 border-start border-4 border-primary">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold"><i class="fa-solid fa-print me-2 text-primary"></i>Print All Class Result Sheets</h5>
                <small class="text-muted">Print individual result cards for every student in a class at once</small>
            </div>
            <div class="card-body">
                <form action="class_results.php" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Select Class</label>
                        <select name="class_id" class="form-select" required>
                            <?php
                            $classes_cr = $conn->query("SELECT * FROM classes");
                            while($c = $classes_cr->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Exam Term</label>
                        <select name="exam_term" class="form-select" required>
                            <option value="First Term">First Term</option>
                            <option value="Mid Term">Mid Term</option>
                            <option value="Final Term">Final Term</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-file-lines me-1"></i> Print All Student Results</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
