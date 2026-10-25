<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// Generate monthly billing for all students
if(isset($_POST['generate_billing'])) {
    $conn->query("UPDATE students SET previous_dues = previous_dues + monthly_fee");
    echo "<div class='alert alert-success'>Monthly billing processed. The monthly fee has been added to everyone's outstanding dues.</div>";
}

if(isset($_POST['collect_fee'])) {
    $student_id = (int)$_POST['student_id'];
    $month = $conn->real_escape_string($_POST['month']);
    $year = (int)$_POST['year'];
    $amount_paid = (float)$_POST['amount'];
    
    // Fetch current student details to calculate new dues
    $student = $conn->query("SELECT previous_dues FROM students WHERE id = $student_id")->fetch_assoc();
    $current_due = (float)$student['previous_dues'];
    
    // The amount they pay reduces their total outstanding due
    $new_due = $current_due - $amount_paid;
    
    // Update student's dues
    $conn->query("UPDATE students SET previous_dues = $new_due WHERE id = $student_id");
    
    // Record the fee payment
    $conn->query("INSERT INTO fees (student_id, month, year, amount) VALUES ($student_id, '$month', $year, $amount_paid)");
    $fee_id = $conn->insert_id;
    
    echo "<div class='alert alert-success'>Fee collected successfully. Remaining Dues: Rs " . number_format($new_due, 2) . ". <a href='print_receipt.php?id=$fee_id' target='_blank' class='btn btn-sm btn-primary ml-2'>Print Receipt</a></div>";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Fee Management</h2>
    <form method="POST" onsubmit="return confirm('Are you sure you want to add the Monthly Fee to EVERY student\'s outstanding dues? (Run this once a month)')">
        <button type="submit" name="generate_billing" class="btn btn-warning"><i class="fa-solid fa-calculator"></i> Run Monthly Fee Billing</button>
    </form>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow border-0 rounded-4 mb-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold">Collect Fee</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select name="student_id" id="student_id" class="form-select" required onchange="fetchFeeDetails()">
                            <option value="">Select Student</option>
                            <?php
                            $students = $conn->query("SELECT s.id, s.name, s.admission_no, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id ORDER BY s.name");
                            while($s = $students->fetch_assoc()) {
                                echo "<option value='{$s['id']}'>{$s['name']} ({$s['admission_no']}) - {$s['class_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div id="fee_details_box" class="alert alert-info d-none">
                        <strong>Monthly Fee:</strong> Rs <span id="display_monthly_fee">0</span><br>
                        <strong>Total Outstanding Dues:</strong> Rs <span id="display_outstanding">0</span>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select" required>
                                <?php
                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                foreach($months as $m) {
                                    $selected = (date('F') == $m) ? 'selected' : '';
                                    echo "<option value='$m' $selected>$m</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Paying (Rs )</label>
                        <input type="number" step="0.01" name="amount" id="amount_paying" class="form-control" required>
                    </div>
                    <button type="submit" name="collect_fee" class="btn btn-primary w-100">Collect Fee</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card shadow border-0 rounded-4 mb-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold"><i class="fa-solid fa-file-invoice me-2"></i>Print Annual Fee Card</h5>
            </div>
            <div class="card-body">
                <form action="fee_card.php" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php
                            $students_fc = $conn->query("SELECT s.id, s.name, s.admission_no, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id ORDER BY s.name");
                            while($s = $students_fc->fetch_assoc()) {
                                echo "<option value='{$s['id']}'>{$s['name']} ({$s['admission_no']}) - {$s['class_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-secondary w-100"><i class="fa-solid fa-calendar-days me-1"></i> Generate Annual Fee Card (All Months)</button>
                </form>
                <small class="text-muted mt-2 d-block"><i class="fa-solid fa-info-circle me-1"></i>Shows all 12 months with Paid / Unpaid status</small>
            </div>
        </div>

        <div class="card shadow border-0 rounded-4 mb-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold"><i class="fa-solid fa-users me-2"></i>Print Class Fee Cards</h5>
            </div>
            <div class="card-body">
                <form action="class_fee_cards.php" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php
                            $classes_fc = $conn->query("SELECT * FROM classes ORDER BY name");
                            while($c = $classes_fc->fetch_assoc()) {
                                echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-print me-1"></i> Generate Class Fee Cards (All Students)</button>
                </form>
                <small class="text-muted mt-2 d-block"><i class="fa-solid fa-info-circle me-1"></i>Generates printable annual fee cards for every student in the selected class</small>
            </div>
        </div>

        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold">Recent Fee Collections</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Receipt No</th>
                                <th>Student</th>
                                <th>Month/Year</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $fees = $conn->query("SELECT f.*, s.name, s.admission_no FROM fees f JOIN students s ON f.student_id = s.id ORDER BY f.id DESC LIMIT 20");
                            while($row = $fees->fetch_assoc()):
                            ?>
                            <tr>
                                <td>#<?= sprintf('%05d', $row['id']) ?></td>
                                <td><?= $row['name'] ?> <br><small class="text-muted">(<?= $row['admission_no'] ?>)</small></td>
                                <td><?= $row['month'] ?> <?= $row['year'] ?></td>
                                <td class="fw-bold text-success">Rs <?= number_format($row['amount'], 2) ?></td>
                                <td><?= date('d M Y', strtotime($row['paid_on'])) ?></td>
                                <td>
                                    <a href="print_receipt.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-info text-white"><i class="fa-solid fa-print"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fetchFeeDetails() {
    var student_id = document.getElementById('student_id').value;
    var box = document.getElementById('fee_details_box');
    
    if(!student_id) {
        box.classList.add('d-none');
        return;
    }
    
    fetch('get_student_fee.php?student_id=' + student_id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('display_monthly_fee').innerText = data.monthly_fee;
            document.getElementById('display_outstanding').innerText = data.previous_dues;
            document.getElementById('amount_paying').value = data.previous_dues; // Auto-fill amount to outstanding
            box.classList.remove('d-none');
            
            if(parseFloat(data.previous_dues) > 0) {
                box.className = 'alert alert-warning';
            } else {
                box.className = 'alert alert-success';
            }
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
