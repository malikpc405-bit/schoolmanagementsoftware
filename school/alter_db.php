<?php
require_once 'includes/db.php';
$res = $conn->query("DESCRIBE subjects");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
