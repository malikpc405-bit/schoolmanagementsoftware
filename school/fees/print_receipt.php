<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /school/index.php"); exit(); }
require_once '../includes/db.php';

if(!isset($_GET['id'])) die("Invalid Request");

$id = (int)$_GET['id'];
$query = $conn->query("SELECT f.*, s.name, s.father_name, s.admission_no, c.name as class_name FROM fees f JOIN students s ON f.student_id = s.id JOIN classes c ON s.class_id = c.id WHERE f.id = $id");

if($query->num_rows == 0) die("Receipt not found");
$fee = $query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt - #<?= sprintf('%05d', $fee['id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .receipt { max-width: 800px; margin: 40px auto; background: white; padding: 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .school-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
        @media print {
            body { background: white; }
            .receipt { box-shadow: none; margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container receipt">
        <div class="school-header">
            <h2>Aslam School</h2>
            <p class="mb-0">123 Education Street, City, Country</p>
            <p class="mb-0">Phone: +1 234 567 8900</p>
            <h4 class="mt-3">FEE RECEIPT</h4>
        </div>
        
        <div class="row mb-4">
            <div class="col-6">
                <strong>Receipt No:</strong> #<?= sprintf('%05d', $fee['id']) ?><br>
                <strong>Date:</strong> <?= date('d M Y h:i A', strtotime($fee['paid_on'])) ?><br>
            </div>
            <div class="col-6 text-end">
                <strong>Student Name:</strong> <?= $fee['name'] ?><br>
                <strong>Admission No:</strong> <?= $fee['admission_no'] ?><br>
                <strong>Class:</strong> <?= $fee['class_name'] ?><br>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount (Rs )</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tuition Fee for <?= $fee['month'] ?> <?= $fee['year'] ?></td>
                    <td class="text-end"><?= number_format($fee['amount'], 2) ?></td>
                </tr>
                <tr>
                    <td class="text-end fw-bold">Total</td>
                    <td class="text-end fw-bold">Rs <?= number_format($fee['amount'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="row mt-5">
            <div class="col-6">
                <p>_______________________<br>Signature of Student/Parent</p>
            </div>
            <div class="col-6 text-end">
                <p>_______________________<br>Authorized Signatory</p>
            </div>
        </div>

        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print Receipt</button>
            <a href="manage.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
</body>
</html>
