<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0) {
    header("Location: manage.php");
    exit();
}

// Fetch teacher details
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if(!$teacher) {
    echo "<div class='alert alert-danger'>Teacher not found.</div>";
    require_once '../includes/footer.php';
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);
    $qualification = $conn->real_escape_string($_POST['qualification']);
    $join_date = $conn->real_escape_string($_POST['join_date']);
    $salary = (float)$_POST['salary'];
    $status = $conn->real_escape_string($_POST['status']);

    if(empty($name) || empty($phone) || empty($qualification) || empty($join_date)) {
        $error = 'Please fill all required fields.';
    } else {
        $update_stmt = $conn->prepare("UPDATE teachers SET name=?, phone=?, email=?, qualification=?, join_date=?, salary=?, status=? WHERE id=?");
        $update_stmt->bind_param("sssssdsi", $name, $phone, $email, $qualification, $join_date, $salary, $status, $id);
        
        if($update_stmt->execute()) {
            $success = 'Teacher updated successfully!';
            // Refresh teacher array
            $teacher['name'] = $name;
            $teacher['phone'] = $phone;
            $teacher['email'] = $email;
            $teacher['qualification'] = $qualification;
            $teacher['join_date'] = $join_date;
            $teacher['salary'] = $salary;
            $teacher['status'] = $status;
        } else {
            $error = 'Error updating teacher: ' . $conn->error;
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-user-pen me-2 text-primary"></i>Edit Teacher Profile</h2>
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

<div class="card shadow border-0 rounded-4">
    <div class="card-header bg-primary text-white py-3 rounded-top-4">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-id-card me-2"></i>Update Teacher Information</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($teacher['name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($teacher['phone']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($teacher['email']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Qualification <span class="text-danger">*</span></label>
                    <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($teacher['qualification']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Join Date <span class="text-danger">*</span></label>
                    <input type="date" name="join_date" class="form-control" value="<?= $teacher['join_date'] ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Base Monthly Salary (Rs) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rs</span>
                        <input type="number" step="0.01" name="salary" class="form-control" value="<?= $teacher['salary'] ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="Active" <?= $teacher['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $teacher['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Update Teacher</button>
                <a href="manage.php" class="btn btn-secondary px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
