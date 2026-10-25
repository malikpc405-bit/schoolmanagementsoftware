<?php
require_once '../includes/db.php';

if (isset($_GET['class_id'])) {
    $class_id = (int)$_GET['class_id'];
    $stmt = $conn->prepare("SELECT id, name FROM subjects WHERE class_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
        }
    } else {
        echo "";
    }
}
?>
