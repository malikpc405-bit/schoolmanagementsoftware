<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$admission_no = isset($_GET['search_id']) ? $_GET['search_id'] : '';

$student = null;
if ($id > 0) {
    $result = $conn->query("SELECT s.*, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.id = $id");
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    }
} elseif (!empty($admission_no)) {
    $stmt = $conn->prepare("SELECT s.*, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.admission_no = ?");
    $stmt->bind_param("s", $admission_no);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $id = $student['id'];
    }
}

if (!$student) {
    echo "<div class='alert alert-danger'>Student not found.</div>";
    require_once '../includes/footer.php';
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Student Profile</h2>
    <div>
        <a href="id_card.php?id=<?= $student['id'] ?>" class="btn btn-primary me-2"><i class="fa-solid fa-id-card"></i> Generate ID Card</a>
        <a href="edit.php?id=<?= $student['id'] ?>" class="btn btn-warning me-2"><i class="fa-solid fa-edit"></i> Edit</a>
        <a href="manage.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow border-0 rounded-4 h-100">
            <div class="card-body text-center py-5">
                <?php 
                $profile_photo = "https://ui-avatars.com/api/?name=" . urlencode($student['name']) . "&background=random&size=128";
                if (!empty($student['photo']) && file_exists('../uploads/students/' . $student['photo'])) {
                    $profile_photo = '../uploads/students/' . htmlspecialchars($student['photo']);
                }
                ?>
                <img src="<?= $profile_photo ?>" class="rounded-circle mb-3 shadow" style="width: 128px; height: 128px; object-fit: cover;" alt="Student">
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($student['name']) ?></h4>
                <p class="text-muted mb-3"><?= htmlspecialchars($student['class_name']) ?></p>
                <span class="badge bg-primary fs-6">Adm No: <?= htmlspecialchars($student['admission_no']) ?></span>
                <div class="mt-3">
                    <a href="id_card.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="fa-solid fa-id-card me-1"></i> View 2"x3.4" ID Card
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8 mb-4">
        <div class="card shadow border-0 rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-user text-primary me-2"></i> Personal Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fw-bold">Father's Name</div>
                    <div class="col-sm-8"><?= htmlspecialchars($student['father_name']) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fw-bold">Date of Birth</div>
                    <div class="col-sm-8"><?= date('d M Y', strtotime($student['dob'])) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fw-bold">Gender</div>
                    <div class="col-sm-8"><?= ucfirst($student['gender']) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fw-bold">Blood Group</div>
                    <div class="col-sm-8"><span class="badge bg-danger-subtle text-danger fw-bold fs-6"><?= !empty($student['blood_group']) ? htmlspecialchars($student['blood_group']) : 'O+' ?></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fw-bold">Phone</div>
                    <div class="col-sm-8"><?= htmlspecialchars($student['phone']) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fw-bold">Address</div>
                    <div class="col-sm-8"><?= nl2br(htmlspecialchars($student['address'])) ?></div>
                </div>
                <hr>
                <h5 class="fw-bold mt-4 mb-3"><i class="fa-solid fa-school text-primary me-2"></i> Admission Details</h5>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fw-bold">Admission Date</div>
                    <div class="col-sm-8"><?= date('d M Y', strtotime($student['admission_date'])) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fw-bold">Monthly Fee</div>
                    <div class="col-sm-8">Rs <?= number_format($student['monthly_fee'], 2) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted fw-bold">Previous Dues</div>
                    <div class="col-sm-8 text-danger">Rs <?= number_format($student['previous_dues'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
