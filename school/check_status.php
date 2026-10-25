<?php
require_once __DIR__ . '/includes/db.php';

echo "Connected successfully to school_db!\n";

$res = $conn->query("SHOW COLUMNS FROM attendance");
echo "Attendance Columns:\n";
while($r = $res->fetch_assoc()) {
    echo "- " . $r['Field'] . " (" . $r['Type'] . ")\n";
}

$res2 = $conn->query("SHOW COLUMNS FROM students");
echo "\nStudents Columns:\n";
while($r = $res2->fetch_assoc()) {
    echo "- " . $r['Field'] . " (" . $r['Type'] . ")\n";
}

$res3 = $conn->query("SELECT s.id, s.admission_no, s.name, c.name as class_name, s.photo FROM students s JOIN classes c ON s.class_id=c.id");
echo "\nRegistered Students:\n";
while($r = $res3->fetch_assoc()) {
    echo "- ID: {$r['id']} | Adm: {$r['admission_no']} | Name: {$r['name']} | Class: {$r['class_name']} | Photo: " . ($r['photo'] ? $r['photo'] : 'None') . "\n";
}
?>
