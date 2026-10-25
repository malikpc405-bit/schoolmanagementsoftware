<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$success = '';
$error = '';

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if($conn->query("DELETE FROM teachers WHERE id = $id")) {
        $success = "Teacher deleted successfully!";
    } else {
        $error = "Error deleting teacher: " . $conn->error;
    }
}

// Fetch stats
$total_teachers = $conn->query("SELECT COUNT(*) as cnt FROM teachers")->fetch_assoc()['cnt'];
$active_teachers = $conn->query("SELECT COUNT(*) as cnt FROM teachers WHERE status='Active'")->fetch_assoc()['cnt'];
$total_payroll = $conn->query("SELECT SUM(salary) as total FROM teachers WHERE status='Active'")->fetch_assoc()['total'];
$total_payroll = $total_payroll ? $total_payroll : 0;
$total_assignments = $conn->query("SELECT COUNT(*) as cnt FROM teacher_assignments")->fetch_assoc()['cnt'];

// Filtering & Search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

$where_clauses = [];
if(!empty($search)) {
    $where_clauses[] = "(name LIKE '%$search%' OR phone LIKE '%$search%' OR qualification LIKE '%$search%')";
}
if(!empty($status_filter)) {
    $where_clauses[] = "status = '$status_filter'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
$query = "SELECT t.*, 
          (SELECT COUNT(*) FROM teacher_assignments ta WHERE ta.teacher_id = t.id) as assigned_classes
          FROM teachers t $where_sql ORDER BY t.name ASC";
$result = $conn->query($query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-chalkboard-user me-2 text-primary"></i>Teacher Management</h2>
    <div class="btn-group">
        <a href="add.php" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i> Add New Teacher</a>
        <a href="assign.php" class="btn btn-outline-primary"><i class="fa-solid fa-clock me-1"></i> Assign Classes (30 Mins)</a>
        <a href="salaries.php" class="btn btn-outline-success"><i class="fa-solid fa-money-check-dollar me-1"></i> Teacher Salaries</a>
    </div>
</div>

<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white shadow-sm rounded-4 border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small">Total Teachers</div>
                        <h3 class="mb-0 fw-bold"><?= $total_teachers ?></h3>
                    </div>
                    <i class="fa-solid fa-users-gear fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white shadow-sm rounded-4 border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small">Active Staff</div>
                        <h3 class="mb-0 fw-bold"><?= $active_teachers ?></h3>
                    </div>
                    <i class="fa-solid fa-user-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white shadow-sm rounded-4 border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small">Class Assignments</div>
                        <h3 class="mb-0 fw-bold"><?= $total_assignments ?></h3>
                    </div>
                    <i class="fa-solid fa-calendar-day fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-dark shadow-sm rounded-4 border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-dark-50 small">Monthly Payroll</div>
                        <h3 class="mb-0 fw-bold">Rs <?= number_format($total_payroll, 2) ?></h3>
                    </div>
                    <i class="fa-solid fa-wallet fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search teacher by name, phone, qualification..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Active" <?= $status_filter == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $status_filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="fa-solid fa-filter"></i> Filter</button>
                <a href="manage.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Teachers Table -->
<div class="card shadow border-0 rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Teacher Name</th>
                        <th>Phone / Email</th>
                        <th>Qualification</th>
                        <th>Base Salary</th>
                        <th>Assigned Classes</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold" style="width: 40px; height: 40px;">
                                            <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></div>
                                            <small class="text-muted">ID: #TCH-<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="fa-solid fa-phone me-1 text-muted"></i> <?= htmlspecialchars($row['phone']) ?></div>
                                    <?php if(!empty($row['email'])): ?>
                                        <small class="text-muted"><i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($row['email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['qualification']) ?></span></td>
                                <td class="fw-bold text-success">Rs <?= number_format($row['salary'], 2) ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <i class="fa-solid fa-book-open me-1"></i> <?= $row['assigned_classes'] ?> Classes (30m)
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($row['join_date'])) ?></td>
                                <td>
                                    <?php if($row['status'] == 'Active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="assign.php?teacher_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info" title="Assign 30-Min Classes"><i class="fa-solid fa-calendar-plus"></i> Assign</a>
                                        <a href="salaries.php?teacher_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success" title="Pay Salary"><i class="fa-solid fa-hand-holding-dollar"></i> Salary</a>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Teacher"><i class="fa-solid fa-pen"></i></a>
                                        <a href="manage.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete Teacher" onclick="return confirm('Are you sure you want to delete this teacher?')"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-user-slash fs-3 d-block mb-2"></i>
                                No teachers found. Click "Add New Teacher" to create one.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
