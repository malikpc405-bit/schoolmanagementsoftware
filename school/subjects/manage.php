<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if(isset($_POST['add_subject'])) {
    $class_id = (int)$_POST['class_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $total_marks = isset($_POST['total_marks']) ? (int)$_POST['total_marks'] : 100;
    $conn->query("INSERT INTO subjects (class_id, name, total_marks) VALUES ($class_id, '$name', $total_marks)");
    echo "<div class='alert alert-success'>Subject added successfully.</div>";
}

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM subjects WHERE id = $id");
    echo "<div class='alert alert-success'>Subject deleted successfully.</div>";
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow border-0 rounded-4 mb-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold">Add Subject</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
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
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Mathematics">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Marks</label>
                        <input type="number" name="total_marks" class="form-control" value="100" required min="1">
                    </div>
                    <button type="submit" name="add_subject" class="btn btn-primary w-100">Add Subject</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold">Manage Subjects</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Class</th>
                            <th>Subject Name</th>
                            <th>Total Marks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subjects = $conn->query("SELECT s.*, c.name as class_name FROM subjects s JOIN classes c ON s.class_id = c.id ORDER BY c.id, s.name");
                        while($row = $subjects->fetch_assoc()):
                        ?>
                        <tr>
                            <td><span class="badge bg-info text-dark"><?= $row['class_name'] ?></span></td>
                            <td><?= $row['name'] ?></td>
                            <td><?= $row['total_marks'] ?></td>
                            <td>
                                <a href="manage.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this subject?')"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
