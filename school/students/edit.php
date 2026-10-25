<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if(!isset($_GET['id'])) die("Invalid Request");
$id = (int)$_GET['id'];

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

    $student_current = $conn->query("SELECT photo FROM students WHERE id=$id")->fetch_assoc();
    $photo_name = $student_current['photo'];

    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        if(in_array(strtolower($ext), $allowed)) {
            $new_name = uniqid('stu_') . '.' . $ext;
            if(move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/students/' . $new_name)) {
                if($photo_name && file_exists('../uploads/students/' . $photo_name)) {
                    unlink('../uploads/students/' . $photo_name);
                }
                $photo_name = $new_name;
            }
        }
    }

    $photo_val = $photo_name ? "'$photo_name'" : "NULL";

    $sql = "UPDATE students SET 
            admission_no='$adm_no', name='$name', father_name='$fname', 
            dob='$dob', gender='$gender', blood_group='$blood_group', class_id=$class_id, 
            phone='$phone', address='$address', admission_date='$adm_date',
            monthly_fee=$monthly_fee, previous_dues=$previous_dues, photo=$photo_val
            WHERE id=$id";
    
    if($conn->query($sql)) {
        echo "<div class='alert alert-success'>Student updated successfully. <a href='manage.php'>Back to Students</a></div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}

$student = $conn->query("SELECT * FROM students WHERE id = $id")->fetch_assoc();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Student</h2>
    <a href="manage.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="card shadow border-0 rounded-4">
    <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Admission Number</label>
                    <input type="text" name="admission_no" class="form-control" required value="<?= $student['admission_no'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Student Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= $student['name'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Father's Name</label>
                    <input type="text" name="father_name" class="form-control" required value="<?= $student['father_name'] ?>">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" required value="<?= $student['dob'] ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select" required>
                        <option value="Male" <?= $student['gender']=='Male'?'selected':'' ?>>Male</option>
                        <option value="Female" <?= $student['gender']=='Female'?'selected':'' ?>>Female</option>
                        <option value="Other" <?= $student['gender']=='Other'?'selected':'' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Blood Group</label>
                    <?php $curr_bg = !empty($student['blood_group']) ? $student['blood_group'] : 'O+'; ?>
                    <select name="blood_group" class="form-select">
                        <option value="O+" <?= $curr_bg=='O+'?'selected':'' ?>>O+</option>
                        <option value="O-" <?= $curr_bg=='O-'?'selected':'' ?>>O-</option>
                        <option value="A+" <?= $curr_bg=='A+'?'selected':'' ?>>A+</option>
                        <option value="A-" <?= $curr_bg=='A-'?'selected':'' ?>>A-</option>
                        <option value="B+" <?= $curr_bg=='B+'?'selected':'' ?>>B+</option>
                        <option value="B-" <?= $curr_bg=='B-'?'selected':'' ?>>B-</option>
                        <option value="AB+" <?= $curr_bg=='AB+'?'selected':'' ?>>AB+</option>
                        <option value="AB-" <?= $curr_bg=='AB-'?'selected':'' ?>>AB-</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class_id" class="form-select" required>
                        <?php
                        $classes = $conn->query("SELECT * FROM classes");
                        while($c = $classes->fetch_assoc()) {
                            $sel = $c['id'] == $student['class_id'] ? 'selected' : '';
                            echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" required value="<?= $student['phone'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Admission Date</label>
                    <input type="date" name="admission_date" class="form-control" required value="<?= $student['admission_date'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2" required><?= $student['address'] ?></textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Student Photo (Leave blank to keep current)</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <?php if($student['photo']): ?>
                        <div class="mt-2">
                            <img src="../uploads/students/<?= $student['photo'] ?>" alt="Student Photo" width="80" class="rounded">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Monthly Fee (Rs )</label>
                    <input type="number" step="0.01" name="monthly_fee" class="form-control" required value="<?= $student['monthly_fee'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Outstanding Balance / Dues (Rs )</label>
                    <input type="number" step="0.01" name="previous_dues" class="form-control" required value="<?= $student['previous_dues'] ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-warning"><i class="fa-solid fa-edit"></i> Update Student</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
