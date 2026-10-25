<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $adm_no = $conn->real_escape_string($_POST['admission_no']);
    $name = $conn->real_escape_string($_POST['name']);
    $fname = $conn->real_escape_string($_POST['father_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $conn->real_escape_string($_POST['blood_group'] ?? 'O+');
    $class_id = (int)$_POST['class_id'];
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $adm_date = $_POST['admission_date'];
    $monthly_fee = (float)$_POST['monthly_fee'];
    $previous_dues = (float)$_POST['previous_dues'];

    $photo_name = null;
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        if(in_array(strtolower($ext), $allowed)) {
            $new_name = uniqid('stu_') . '.' . $ext;
            if(move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/students/' . $new_name)) {
                $photo_name = $new_name;
            }
        }
    }

    $photo_val = $photo_name ? "'$photo_name'" : "NULL";

    $sql = "INSERT INTO students (admission_no, name, father_name, dob, gender, blood_group, class_id, phone, address, admission_date, monthly_fee, previous_dues, photo) 
            VALUES ('$adm_no', '$name', '$fname', '$dob', '$gender', '$blood_group', $class_id, '$phone', '$address', '$adm_date', $monthly_fee, $previous_dues, $photo_val)";
    
    if($conn->query($sql)) {
        echo "<div class='alert alert-success'>Student added successfully. <a href='manage.php'>View Students</a></div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}

$auto_adm_no = '1001';
$res = $conn->query("SELECT admission_no FROM students ORDER BY id DESC LIMIT 1");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $last_adm = $row['admission_no'];
    if (preg_match('/^(\D*)(\d+)(\D*)$/', $last_adm, $matches)) {
        $prefix = $matches[1];
        $number = (int)$matches[2];
        $suffix = $matches[3];
        $auto_adm_no = $prefix . str_pad($number + 1, strlen($matches[2]), '0', STR_PAD_LEFT) . $suffix;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add New Student</h2>
    <a href="manage.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="card shadow border-0 rounded-4">
    <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Roll No</label>
                    <input type="text" name="admission_no" class="form-control" value="<?= htmlspecialchars($auto_adm_no) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Student Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Father's Name</label>
                    <input type="text" name="father_name" class="form-control" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class_id" class="form-select" required>
                        <?php
                        $classes = $conn->query("SELECT * FROM classes");
                        while($c = $classes->fetch_assoc()) {
                            echo "<option value='{$c['id']}'>{$c['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Admission Date</label>
                    <input type="date" name="admission_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2" required></textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Student Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Monthly Fee (Rs )</label>
                    <input type="number" step="0.01" name="monthly_fee" class="form-control" required value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Opening Balance / Previous Dues (Rs )</label>
                    <input type="number" step="0.01" name="previous_dues" class="form-control" required value="0">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Student</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
