<?php
require_once 'includes/db.php';
$conn->query("ALTER TABLE students ADD COLUMN photo VARCHAR(255) DEFAULT NULL");
echo "Done";
?>
