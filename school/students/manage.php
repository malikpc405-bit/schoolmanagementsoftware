<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM students WHERE id = $id");
    echo "<div class='alert alert-success'>Student deleted successfully.</div>";
}
?>
<?php
$filter_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Students</h2>
    <div>
        <a href="id_card.php<?= $filter_class > 0 ? '?mode=batch&class_id=' . $filter_class : '' ?>" class="btn btn-outline-primary me-2"><i class="fa-solid fa-id-card"></i> <?= $filter_class > 0 ? 'Print Class ID Cards' : 'ID Cards Studio' ?></a>
        <a href="promote.php" class="btn btn-success me-2"><i class="fa-solid fa-rocket"></i> Promote Students</a>
        <?php if($filter_class > 0): ?>
            <a href="print_list.php?class_id=<?= $filter_class ?>" target="_blank" class="btn btn-secondary me-2"><i class="fa-solid fa-print"></i> Print Class List</a>
        <?php endif; ?>
        <a href="add.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Student</a>
    </div>
</div>

<div class="card shadow border-0 rounded-4 mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold">Filter by Class:</label>
            </div>
            <div class="col-md-3">
                <select name="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">All Classes</option>
                    <?php
                    $classes = $conn->query("SELECT * FROM classes");
                    while($c = $classes->fetch_assoc()) {
                        $sel = ($filter_class == $c['id']) ? 'selected' : '';
                        echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-auto ms-auto">
                <label class="col-form-label fw-bold">Search ID/Name:</label>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Enter Admission No or Name..." value="<?= htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : '') ?>">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow border-0 rounded-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Adm No</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Father's Name</th>
                        <th>Phone</th>
                        <th>Face ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $where = "WHERE 1=1";
                    if($filter_class > 0) {
                        $where .= " AND s.class_id = $filter_class";
                    }
                    if(!empty($search)) {
                        $where .= " AND (s.admission_no LIKE '%$search%' OR s.name LIKE '%$search%')";
                    }
                    $result = $conn->query("SELECT s.*, c.name as class_name, sf.id as face_id 
                                            FROM students s 
                                            JOIN classes c ON s.class_id = c.id 
                                            LEFT JOIN student_faces sf ON s.id = sf.student_id 
                                            $where ORDER BY s.name ASC");
                    if($result->num_rows > 0):
                        while($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $row['admission_no'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><span class="badge bg-info text-dark"><?= $row['class_name'] ?></span></td>
                        <td><?= $row['father_name'] ?></td>
                        <td><?= $row['phone'] ?></td>
                        <td>
                            <?php if ($row['face_id']): ?>
                                <a href="/school/attendance/face_register.php?student_id=<?= $row['id'] ?>" class="badge bg-success text-decoration-none" title="Click to view/verify face profile">
                                    <i class="fa-solid fa-circle-check me-1"></i>Enrolled
                                </a>
                            <?php else: ?>
                                <a href="/school/attendance/face_register.php?student_id=<?= $row['id'] ?>" class="badge bg-warning text-dark text-decoration-none" title="Click to register face">
                                    <i class="fa-solid fa-camera me-1"></i>Register Face
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="id_card.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-dark text-white" title="Generate ID Card"><i class="fa-solid fa-id-card"></i></a>
                            <a href="view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info text-white" title="View Profile"><i class="fa-solid fa-eye"></i></a>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" title="Edit Student"><i class="fa-solid fa-edit"></i></a>
                            <a href="manage.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" title="Delete Student" onclick="return confirm('Are you sure you want to delete this student?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="7" class="text-center">No students found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
