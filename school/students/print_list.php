<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /school/index.php"); exit(); }
require_once '../includes/db.php';

if(!isset($_GET['class_id'])) die("Invalid Request");

$class_id = (int)$_GET['class_id'];

$class_info = $conn->query("SELECT name FROM classes WHERE id = $class_id")->fetch_assoc();
if(!$class_info) die("Class not found");

$students = $conn->query("SELECT * FROM students WHERE class_id = $class_id ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student List - <?= $class_info['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; font-family: Arial, sans-serif; }
        .school-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; margin-top: 20px;}
        .school-header h2 { margin: 0; font-weight: bold; font-size: 24px; }
        .school-header p { margin: 0; font-size: 14px; }
        table th { background-color: #f0f0f0 !important; }
        @media print {
            .no-print { display: none; }
            table { font-size: 14px; }
            th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="text-center mt-3 no-print">
        <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print List</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="container">
        <div class="school-header">
            <h2>ASLAM SCHOOL</h2>
            <p>123 Education Street, City, Country</p>
            <h4 class="mt-3">Student List: <?= strtoupper($class_info['name']) ?></h4>
        </div>

        <p><strong>Total Students:</strong> <?= $students->num_rows ?></p>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="50">S.No</th>
                    <th>Adm No</th>
                    <th>Student Name</th>
                    <th>Father's Name</th>
                    <th>Gender</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                if($students->num_rows > 0):
                    while($row = $students->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td><?= $row['admission_no'] ?></td>
                    <td class="fw-bold"><?= strtoupper($row['name']) ?></td>
                    <td><?= strtoupper($row['father_name']) ?></td>
                    <td><?= $row['gender'] ?></td>
                    <td><?= $row['phone'] ?></td>
                </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                <tr>
                    <td colspan="6" class="text-center">No students found in this class.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="mt-5 d-flex justify-content-between no-print" style="max-width: 400px; margin: auto;">
            <div class="text-center border-top pt-2" style="width: 150px;">Class Teacher</div>
            <div class="text-center border-top pt-2" style="width: 150px;">Principal</div>
        </div>
    </div>
</body>
</html>
