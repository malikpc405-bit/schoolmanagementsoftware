<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$sel_date = isset($_GET['att_date']) ? $_GET['att_date'] : date('Y-m-d');
$esc_date = $conn->real_escape_string($sel_date);

?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-user-xmark me-2 text-danger"></i>Absent Students Report</h2>
    <a href="manage.php" class="btn btn-outline-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Attendance
    </a>
</div>

<div class="card shadow border-0 rounded-4 mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row align-items-end g-3" id="filterForm">
            <div class="col-md-4">
                <label class="form-label fw-bold">Select Date</label>
                <input type="date" name="att_date" class="form-control" value="<?= htmlspecialchars($sel_date) ?>"
                       onchange="document.getElementById('filterForm').submit()">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow border-0 rounded-4">
    <div class="card-header bg-white border-bottom pt-3 pb-2">
        <h5 class="mb-0 fw-bold text-danger">
            Absent Students on <?= date('d M Y', strtotime($sel_date)) ?>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Adm No</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Father's Name</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Fetch all absent students for this date
                $query = "
                    SELECT s.*, c.name as class_name 
                    FROM attendance a
                    JOIN students s ON a.student_id = s.id
                    JOIN classes c ON s.class_id = c.id
                    WHERE a.att_date = '$esc_date' AND a.status = 'absent'
                    ORDER BY c.id, s.name ASC
                ";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0):
                    $i = 1;
                    while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td class="text-muted"><?= $i++ ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['admission_no']) ?></span></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['class_name']) ?></span></td>
                        <td class="text-muted"><?= htmlspecialchars($row['father_name']) ?></td>
                        <td>
                            <?= htmlspecialchars($row['phone']) ?>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $row['phone']) ?>?text=<?= urlencode('Dear Parent, this is to inform you that your child ' . $row['name'] . ' is absent today (' . date('d M Y', strtotime($sel_date)) . ').') ?>" target="_blank" class="btn btn-sm btn-success ms-2">
                                <i class="fa-brands fa-whatsapp"></i> Message
                            </a>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-check-circle text-success mb-2 fs-3"></i><br>
                            No absent students found for this date.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
