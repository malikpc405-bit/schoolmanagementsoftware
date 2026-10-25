<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "school_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-create teacher-related tables if not exists
$conn->query("CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `qualification` varchar(100) NOT NULL,
  `join_date` date NOT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Check and add status column if missing in teachers
$col_status = $conn->query("SHOW COLUMNS FROM `teachers` LIKE 'status'");
if ($col_status && $col_status->num_rows == 0) {
    $conn->query("ALTER TABLE `teachers` ADD `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'");
}

$conn->query("CREATE TABLE IF NOT EXISTS `teacher_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `duration_mins` int(11) NOT NULL DEFAULT 30,
  `start_time` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Check and add duration_mins / start_time columns if missing in teacher_assignments
$col_duration = $conn->query("SHOW COLUMNS FROM `teacher_assignments` LIKE 'duration_mins'");
if ($col_duration && $col_duration->num_rows == 0) {
    $conn->query("ALTER TABLE `teacher_assignments` ADD `duration_mins` INT(11) NOT NULL DEFAULT 30");
}
$col_starttime = $conn->query("SHOW COLUMNS FROM `teacher_assignments` LIKE 'start_time'");
if ($col_starttime && $col_starttime->num_rows == 0) {
    $conn->query("ALTER TABLE `teacher_assignments` ADD `start_time` VARCHAR(20) DEFAULT NULL");
}

$conn->query("CREATE TABLE IF NOT EXISTS `teacher_salaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int(4) NOT NULL,
  `base_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_on` date NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Check and add missing columns in teacher_salaries if table pre-existed
$ts_cols = [
    'base_salary' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'allowance' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'deduction' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'net_salary' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'paid_on' => "DATE NOT NULL",
    'payment_method' => "VARCHAR(50) NOT NULL DEFAULT 'Cash'",
    'remarks' => "TEXT DEFAULT NULL"
];

foreach ($ts_cols as $col_name => $col_def) {
    $col_chk = $conn->query("SHOW COLUMNS FROM `teacher_salaries` LIKE '$col_name'");
    if ($col_chk && $col_chk->num_rows == 0) {
        $conn->query("ALTER TABLE `teacher_salaries` ADD `$col_name` $col_def");
        // If net_salary added and old column amount exists, copy amount over
        if ($col_name == 'net_salary') {
            $amt_chk = $conn->query("SHOW COLUMNS FROM `teacher_salaries` LIKE 'amount'");
            if ($amt_chk && $amt_chk->num_rows > 0) {
                $conn->query("UPDATE `teacher_salaries` SET `net_salary` = `amount` WHERE `net_salary` = 0");
            }
        }
    }
}

// Auto-create timetable table
$conn->query("CREATE TABLE IF NOT EXISTS `timetable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `period_no` int(11) NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `day_name` varchar(20) DEFAULT 'Daily',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Auto-create student_faces table for Face Recognition Attendance
$conn->query("CREATE TABLE IF NOT EXISTS `student_faces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `face_descriptor` LONGTEXT NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `sample_count` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_student_face` (`student_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Add missing columns to attendance table if needed
$att_time_chk = $conn->query("SHOW COLUMNS FROM `attendance` LIKE 'att_time'");
if ($att_time_chk && $att_time_chk->num_rows == 0) {
    $conn->query("ALTER TABLE `attendance` ADD `att_time` TIME NULL AFTER `att_date`");
}
$att_method_chk = $conn->query("SHOW COLUMNS FROM `attendance` LIKE 'method'");
if ($att_method_chk && $att_method_chk->num_rows == 0) {
    $conn->query("ALTER TABLE `attendance` ADD `method` VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER `status`");
}
$att_conf_chk = $conn->query("SHOW COLUMNS FROM `attendance` LIKE 'confidence'");
if ($att_conf_chk && $att_conf_chk->num_rows == 0) {
    $conn->query("ALTER TABLE `attendance` ADD `confidence` DECIMAL(5,2) NULL AFTER `method`");
}

// Add blood_group column to students if missing
$bg_chk = $conn->query("SHOW COLUMNS FROM `students` LIKE 'blood_group'");
if ($bg_chk && $bg_chk->num_rows == 0) {
    $conn->query("ALTER TABLE `students` ADD `blood_group` VARCHAR(10) NULL DEFAULT 'O+' AFTER `gender`");
}

// Auto-create ID Card Settings table
$conn->query("CREATE TABLE IF NOT EXISTS `id_card_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_name` varchar(150) NOT NULL DEFAULT 'ASLAM PUBLIC HIGH SCHOOL',
  `tagline` varchar(200) NOT NULL DEFAULT 'Excellence in Knowledge & Character',
  `campus` varchar(100) NOT NULL DEFAULT 'Main Campus',
  `phone` varchar(50) NOT NULL DEFAULT '+92 300 1234567',
  `email` varchar(100) NOT NULL DEFAULT 'info@aslamschool.edu',
  `address` varchar(255) NOT NULL DEFAULT 'College Road, Model Town',
  `session_year` varchar(50) NOT NULL DEFAULT '2026 - 2027',
  `principal_title` varchar(50) NOT NULL DEFAULT 'Principal',
  `signature_text` varchar(100) NOT NULL DEFAULT 'Authorized Signatory',
  `theme` varchar(50) NOT NULL DEFAULT 'royal_navy',
  `logo_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$set_count_res = $conn->query("SELECT COUNT(*) as cnt FROM id_card_settings");
if ($set_count_res) {
    $set_count = $set_count_res->fetch_assoc()['cnt'];
    if ($set_count == 0) {
        $conn->query("INSERT INTO id_card_settings (school_name, tagline, campus, phone, email, address, session_year, principal_title, signature_text, theme) 
                     VALUES ('ASLAM PUBLIC HIGH SCHOOL', 'Excellence in Knowledge & Character', 'Main Campus', '+92 300 1234567', 'info@aslamschool.edu', 'College Road, Model Town', '2026 - 2027', 'Principal', 'Authorized Signatory', 'royal_navy')");
    }
}
?>
