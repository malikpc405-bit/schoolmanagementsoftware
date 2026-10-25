<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$error = '';
$success = '';
$new_teacher_id = 0;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);
    $qualification = $conn->real_escape_string($_POST['qualification']);
    $join_date = $conn->real_escape_string($_POST['join_date']);
    $salary = (float)$_POST['salary'];
    $status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : 'Active';
    
    if(empty($name) || empty($phone) || empty($qualification) || empty($join_date)) {
        $error = 'Please fill all required fields';
    } else {
        $stmt = $conn->prepare("INSERT INTO teachers (name, phone, email, qualification, join_date, salary, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssds", $name, $phone, $email, $qualification, $join_date, $salary, $status);
        
        if($stmt->execute()) {
            $new_teacher_id = $conn->insert_id;
            $success = 'Teacher added successfully!';
        } else {
            $error = 'Error adding teacher: ' . $conn->error;
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-user-plus me-2 text-primary"></i>Add New Teacher</h2>
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
        <h5 class="alert-heading"><i class="fa-solid fa-circle-check me-2"></i><?= $success ?></h5>
        <p class="mb-0">Teacher record has been created.</p>
        <hr>
        <div class="d-flex gap-2">
            <a href="assign.php?teacher_id=<?= $new_teacher_id ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-clock me-1"></i> Assign 30-Min Classes Now</a>
            <a href="salaries.php?teacher_id=<?= $new_teacher_id ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-money-bill-wave me-1"></i> Manage Teacher Salary</a>
            <a href="add.php" class="btn btn-sm btn-outline-secondary">Add Another Teacher</a>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow border-0 rounded-4">
    <div class="card-header bg-primary text-white py-3 rounded-top-4">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-id-card me-2"></i>Teacher Profile Information</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Prof. Mohammad Ali" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g. 0300-1234567" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="e.g. teacher@school.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Qualification <span class="text-danger">*</span></label>
                    <input type="text" name="qualification" class="form-control" placeholder="e.g. M.Sc Mathematics / M.A English" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Join Date <span class="text-danger">*</span></label>
                    <input type="date" name="join_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Base Monthly Salary (Rs) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rs</span>
                        <input type="number" step="0.01" name="salary" class="form-control" required value="25000.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Save Teacher</button>
                <button type="reset" class="btn btn-secondary px-4">Reset</button>
                <a href="manage.php" class="btn btn-outline-dark ms-auto">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
