<?php
require_once '../includes/db.php';

if(isset($_GET['student_id'])) {
    $id = (int)$_GET['student_id'];
    $student = $conn->query("SELECT monthly_fee, previous_dues FROM students WHERE id = $id")->fetch_assoc();
    echo json_encode($student);
}
?>
