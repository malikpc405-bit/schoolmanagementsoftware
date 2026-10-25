<?php
require_once 'includes/db.php';
$new_username = 'malik';
$new_password = password_hash('malik123', PASSWORD_DEFAULT);

if ($conn->query("UPDATE users SET username='$new_username', password='$new_password' WHERE id=1")) {
    echo "User updated successfully";
} else {
    echo "Error updating user: " . $conn->error;
}
?>
