<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if(isset($_POST['add_class'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $conn->query("INSERT INTO classes (name) VALUES ('$name')");
    echo "<div class='alert alert-success'>Class added successfully.</div>";
}

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM classes WHERE id = $id");
    echo "<div class='alert alert-success'>Class deleted successfully.</div>";
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow border-0 rounded-4 mb-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold">Add Class</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Class Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Class 1">
                    </div>
                    <button type="submit" name="add_class" class="btn btn-primary w-100">Add Class</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <h5 class="fw-bold">Manage Classes</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Class Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $classes = $conn->query("SELECT * FROM classes");
                        while($row = $classes->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['name']) ?></td>
                            <td>
                                <a href="timetable.php?class_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-calendar-days me-1"></i> Timetable</a>
                                <a href="manage.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger ms-1" onclick="return confirm('Delete this class? This will delete all students and subjects linked to it!')"><i class="fa-solid fa-trash"></i></a>
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
