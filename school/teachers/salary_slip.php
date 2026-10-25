<?php
require_once '../includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0) {
    die("Invalid Salary Slip ID");
}

$stmt = $conn->prepare("SELECT ts.*, t.name as teacher_name, t.phone, t.email, t.qualification, t.join_date 
                        FROM teacher_salaries ts 
                        JOIN teachers t ON ts.teacher_id = t.id 
                        WHERE ts.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if(!$data) {
    die("Salary Slip record not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip - <?= htmlspecialchars($data['teacher_name']) ?> (<?= $data['month'] ?> <?= $data['year'] ?>)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .slip-card {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
        }
        .header-title {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 15px;
        }
        @media print {
            body { background: #fff; }
            .slip-card { box-shadow: none; border: none; margin: 0; padding: 20px; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="slip-card">
        <!-- Print Button -->
        <div class="d-flex justify-content-end mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print me-1"></i> Print Salary Slip</button>
            <a href="salaries.php" class="btn btn-outline-secondary ms-2"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
        </div>

        <!-- School Header -->
        <div class="text-center header-title mb-4">
            <h2 class="fw-bold text-uppercase text-primary mb-1"><i class="fa-solid fa-graduation-cap me-2"></i>Aslam School</h2>
            <p class="text-muted mb-0">Official Salary Disbursement Receipt</p>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold mb-0">SALARY SLIP</h5>
                <small class="text-muted">Voucher #: SAL-<?= str_pad($data['id'], 5, '0', STR_PAD_LEFT) ?></small>
            </div>
            <div class="text-end">
                <span class="badge bg-success fs-6 py-2 px-3">PAID</span>
                <div class="small text-muted mt-1">Payment Date: <?= date('d M Y', strtotime($data['paid_on'])) ?></div>
            </div>
        </div>

        <!-- Teacher Details -->
        <div class="row mb-4">
            <div class="col-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 120px;">Teacher Name:</td>
                        <td class="fw-bold"><?= htmlspecialchars($data['teacher_name']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Teacher ID:</td>
                        <td>#TCH-<?= str_pad($data['teacher_id'], 3, '0', STR_PAD_LEFT) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Qualification:</td>
                        <td><?= htmlspecialchars($data['qualification']) ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 130px;">Salary Period:</td>
                        <td class="fw-bold"><?= $data['month'] ?> <?= $data['year'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Payment Method:</td>
                        <td><?= htmlspecialchars($data['payment_method']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Contact:</td>
                        <td><?= htmlspecialchars($data['phone']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Salary Breakdown -->
        <table class="table table-bordered mb-4">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th class="text-end" style="width: 200px;">Amount (Rs)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Base Monthly Salary</td>
                    <td class="text-end">Rs <?= number_format($data['base_salary'], 2) ?></td>
                </tr>
                <tr>
                    <td>Allowance / Bonus (+)</td>
                    <td class="text-end text-success">+ Rs <?= number_format($data['allowance'], 2) ?></td>
                </tr>
                <tr>
                    <td>Deductions (-)</td>
                    <td class="text-end text-danger">- Rs <?= number_format($data['deduction'], 2) ?></td>
                </tr>
                <tr class="table-active fw-bold">
                    <td class="fs-5">Net Payable Salary</td>
                    <td class="text-end fs-5 text-success">Rs <?= number_format($data['net_salary'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <?php if(!empty($data['remarks'])): ?>
            <div class="mb-4">
                <strong>Remarks:</strong>
                <p class="text-muted mb-0"><?= htmlspecialchars($data['remarks']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="row pt-5 mt-4">
            <div class="col-6 text-center">
                <div style="border-top: 1px dashed #666; width: 70%; margin: 0 auto 5px auto;"></div>
                <small class="fw-bold">Teacher Signature</small>
            </div>
            <div class="col-6 text-center">
                <div style="border-top: 1px dashed #666; width: 70%; margin: 0 auto 5px auto;"></div>
                <small class="fw-bold">Authorized Signature / Principal</small>
            </div>
        </div>

    </div>
</div>

</body>
</html>
