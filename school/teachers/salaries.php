<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$error = '';
$success = '';

$selected_teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
$filter_month = isset($_GET['month']) ? $conn->real_escape_string($_GET['month']) : date('F');
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Handle salary payment submit
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_salary'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $month = $conn->real_escape_string($_POST['month']);
    $year = (int)$_POST['year'];
    $base_salary = (float)$_POST['base_salary'];
    $allowance = (float)$_POST['allowance'];
    $deduction = (float)$_POST['deduction'];
    $net_salary = $base_salary + $allowance - $deduction;
    $paid_on = $conn->real_escape_string($_POST['paid_on']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $remarks = $conn->real_escape_string($_POST['remarks']);

    if($teacher_id <= 0 || empty($month) || $year <= 0 || empty($paid_on)) {
        $error = 'Please fill all required fields.';
    } else {
        // Check if salary for this month and year already paid
        $check = $conn->prepare("SELECT id FROM teacher_salaries WHERE teacher_id = ? AND month = ? AND year = ?");
        $check->bind_param("isi", $teacher_id, $month, $year);
        $check->execute();
        $check_res = $check->get_result();

        if($check_res->num_rows > 0) {
            $error = "Salary for $month $year has already been paid to this teacher!";
        } else {
            $stmt = $conn->prepare("INSERT INTO teacher_salaries (teacher_id, month, year, base_salary, allowance, deduction, net_salary, paid_on, payment_method, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isidddssss", $teacher_id, $month, $year, $base_salary, $allowance, $deduction, $net_salary, $paid_on, $payment_method, $remarks);
            if($stmt->execute()) {
                $salary_id = $conn->insert_id;
                $success = "Salary paid successfully! Net Salary: Rs " . number_format($net_salary, 2) . " <a href='salary_slip.php?id=$salary_id' class='btn btn-sm btn-outline-dark ms-2' target='_blank'><i class='fa-solid fa-print'></i> Print Salary Slip</a>";
            } else {
                $error = "Error recording salary: " . $conn->error;
            }
        }
    }
}

// Handle Delete Salary Record
if(isset($_GET['delete_salary'])) {
    $sal_id = (int)$_GET['delete_salary'];
    $conn->query("DELETE FROM teacher_salaries WHERE id = $sal_id");
    $success = "Salary record removed successfully!";
}

// Fetch total paid stats
$current_month = date('F');
$current_year = date('Y');

$total_this_month = $conn->query("SELECT SUM(net_salary) as total FROM teacher_salaries WHERE month='$current_month' AND year=$current_year")->fetch_assoc()['total'];
$total_this_month = $total_this_month ? $total_this_month : 0;

$total_all_time = $conn->query("SELECT SUM(net_salary) as total FROM teacher_salaries")->fetch_assoc()['total'];
$total_all_time = $total_all_time ? $total_all_time : 0;

$months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-money-check-dollar me-2 text-success"></i>Teacher Salary Management</h2>
    <div class="btn-group">
        <a href="manage.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back to Teachers</a>
        <a href="assign.php" class="btn btn-outline-primary"><i class="fa-solid fa-clock me-1"></i> Class Assignments (30m)</a>
    </div>
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

<!-- Salary Stats Cards -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card bg-success text-white shadow-sm rounded-4 border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small">Salary Paid This Month (<?= $current_month ?> <?= $current_year ?>)</div>
                        <h3 class="mb-0 fw-bold">Rs <?= number_format($total_this_month, 2) ?></h3>
                    </div>
                    <i class="fa-solid fa-calendar-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card bg-dark text-white shadow-sm rounded-4 border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small">Total All-Time Salary Disbursed</div>
                        <h3 class="mb-0 fw-bold">Rs <?= number_format($total_all_time, 2) ?></h3>
                    </div>
                    <i class="fa-solid fa-vault fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pay Salary Form -->
    <div class="col-md-5 mb-4">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-success text-white py-3 rounded-top-4">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Pay Teacher Salary</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Teacher <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="teacherSelect" class="form-select" onchange="updateBaseSalary()" required>
                            <option value="" data-salary="0">-- Select Teacher --</option>
                            <?php
                            $teachers = $conn->query("SELECT id, name, salary FROM teachers WHERE status='Active' ORDER BY name ASC");
                            while($t = $teachers->fetch_assoc()) {
                                $selected = ($t['id'] == $selected_teacher_id) ? 'selected' : '';
                                echo "<option value='{$t['id']}' data-salary='{$t['salary']}' $selected>" . htmlspecialchars($t['name']) . " (Rs " . number_format($t['salary'], 2) . ")</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Month <span class="text-danger">*</span></label>
                            <select name="month" class="form-select" required>
                                <?php foreach($months as $m): ?>
                                    <option value="<?= $m ?>" <?= $m == date('F') ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Year <span class="text-danger">*</span></label>
                            <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required min="2020" max="2050">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Base Monthly Salary (Rs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="base_salary" id="baseSalary" class="form-control" required value="0.00" oninput="calculateNetSalary()">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Allowance / Bonus (Rs)</label>
                            <input type="number" step="0.01" name="allowance" id="allowance" class="form-control" value="0.00" oninput="calculateNetSalary()">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Deduction (Rs)</label>
                            <input type="number" step="0.01" name="deduction" id="deduction" class="form-control" value="0.00" oninput="calculateNetSalary()">
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark fs-6">Net Payable Salary:</span>
                            <span class="fw-bold text-success fs-4" id="netSalaryDisplay">Rs 0.00</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="paid_on" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="EasyPaisa/JazzCash">EasyPaisa / JazzCash</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Notes</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Paid in full for current month">
                    </div>

                    <button type="submit" name="pay_salary" class="btn btn-success w-100 py-2 fw-bold"><i class="fa-solid fa-check-circle me-1"></i> Record & Process Salary</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Salary Payment History -->
    <div class="col-md-7">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-receipt me-2 text-success"></i>Salary Payout History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Teacher</th>
                                <th>Month/Year</th>
                                <th>Net Salary</th>
                                <th>Paid On</th>
                                <th>Method</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $where_sal = [];
                            if($selected_teacher_id > 0) {
                                $where_sal[] = "ts.teacher_id = $selected_teacher_id";
                            }
                            $where_sal_sql = count($where_sal) > 0 ? "WHERE " . implode(" AND ", $where_sal) : "";

                            $history_sql = "SELECT ts.*, t.name as teacher_name 
                                            FROM teacher_salaries ts
                                            JOIN teachers t ON ts.teacher_id = t.id
                                            $where_sal_sql
                                            ORDER BY ts.paid_on DESC, ts.id DESC";
                            $history_res = $conn->query($history_sql);

                            if($history_res && $history_res->num_rows > 0):
                                while($row = $history_res->fetch_assoc()):
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['teacher_name']) ?></div>
                                    <small class="text-muted">Base: Rs <?= number_format($row['base_salary'], 2) ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?= $row['month'] ?> <?= $row['year'] ?></span></td>
                                <td><strong class="text-success">Rs <?= number_format($row['net_salary'], 2) ?></strong></td>
                                <td><?= date('d M Y', strtotime($row['paid_on'])) ?></td>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['payment_method']) ?></span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="salary_slip.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-dark" target="_blank" title="Print Salary Slip">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <a href="salaries.php?delete_salary=<?= $row['id'] ?><?= $selected_teacher_id > 0 ? '&teacher_id='.$selected_teacher_id : '' ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this salary payment record?')" title="Delete Record">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
                                    No salary payments recorded yet.
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
function updateBaseSalary() {
    var select = document.getElementById('teacherSelect');
    var selectedOption = select.options[select.selectedIndex];
    var salary = selectedOption.getAttribute('data-salary') || 0;
    document.getElementById('baseSalary').value = parseFloat(salary).toFixed(2);
    calculateNetSalary();
}

function calculateNetSalary() {
    var base = parseFloat(document.getElementById('baseSalary').value) || 0;
    var allowance = parseFloat(document.getElementById('allowance').value) || 0;
    var deduction = parseFloat(document.getElementById('deduction').value) || 0;
    var net = base + allowance - deduction;
    document.getElementById('netSalaryDisplay').innerText = 'Rs ' + net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Auto populate on page load if teacher preselected
document.addEventListener("DOMContentLoaded", function() {
    updateBaseSalary();
});
</script>

<?php require_once '../includes/footer.php'; ?>
