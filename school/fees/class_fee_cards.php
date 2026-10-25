<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /school/index.php"); exit(); }
require_once '../includes/db.php';

if(!isset($_GET['class_id']) || !isset($_GET['year'])) die("Invalid Request");

$class_id = (int)$_GET['class_id'];
$year = (int)$_GET['year'];

$class_q = $conn->query("SELECT name FROM classes WHERE id = $class_id");
if($class_q->num_rows == 0) die("Class not found");
$class_info = $class_q->fetch_assoc();

// Get all students in this class
$students_q = $conn->query("SELECT s.*, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.class_id = $class_id ORDER BY s.name");
$students = [];
while($s = $students_q->fetch_assoc()) {
    $students[] = $s;
}

if(count($students) == 0) die("No students found in this class.");

// Fetch all fee payments for all students in this class for the school year
$all_fee_payments = [];
$fees_q = $conn->query("
    SELECT f.* 
    FROM fees f 
    JOIN students s ON f.student_id = s.id 
    WHERE s.class_id = $class_id 
      AND ((f.year = $year AND f.month IN ('April','May','June','July','August','September','October','November','December')) 
       OR (f.year = ".($year+1)." AND f.month IN ('January','February','March')))
    ORDER BY f.id ASC
");
while($f = $fees_q->fetch_assoc()) {
    $all_fee_payments[$f['student_id']][$f['month']] = (float)$f['amount'];
}

// School year months: April to March
$school_months = ['April','May','June','July','August','September','October','November','December','January','February','March'];
$months_short = [
    'April' => 'April', 'May' => 'May', 'June' => 'Jun', 'July' => 'July', 'August' => 'Aug', 'September' => 'Sep',
    'October' => 'Oct', 'November' => 'Nov', 'December' => 'Dec', 'January' => 'Jan', 'February' => 'Feb', 'March' => 'Mar'
];
$months_full = [
    'April' => 'April', 'May' => 'May', 'June' => 'June', 'July' => 'July', 'August' => 'August', 'September' => 'September',
    'October' => 'October', 'November' => 'November', 'December' => 'December', 'January' => 'January', 'February' => 'February', 'March' => 'March'
];

// Function to check if a school month is billed so far based on current date
function getBilledStatus($school_year) {
    $current_month = (int)date('n'); // 1 to 12
    $current_year = (int)date('Y');
    
    $billed = [];
    $school_months = ['April','May','June','July','August','September','October','November','December','January','February','March'];
    
    foreach($school_months as $m) {
        $is_next_year = in_array($m, ['January','February','March']);
        $cal_year = $is_next_year ? ($school_year + 1) : $school_year;
        
        if ($current_year > $cal_year) {
            $billed[$m] = true;
        } elseif ($current_year < $cal_year) {
            $billed[$m] = false;
        } else {
            // Same calendar year, map month name to calendar month index
            $month_nums = [
                'January'=>1, 'February'=>2, 'March'=>3, 'April'=>4, 'May'=>5, 'June'=>6,
                'July'=>7, 'August'=>8, 'September'=>9, 'October'=>10, 'November'=>11, 'December'=>12
            ];
            $billed[$m] = ($current_month >= $month_nums[$m]);
        }
    }
    return $billed;
}

$billed_status = getBilledStatus($year);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Fee Cards - <?= htmlspecialchars($class_info['name']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', Arial, sans-serif; 
            background: #2d2d3f;
            padding: 20px;
        }
        
        .no-print {
            text-align: center;
            padding: 10px 0 20px;
        }
        .no-print button, .no-print a {
            display: inline-block;
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        .btn-print {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102,126,234,0.4);
        }
        .btn-print:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(102,126,234,0.5); }
        .btn-back {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2) !important;
            color: white;
            margin-left: 10px;
        }
        .btn-back:hover { background: rgba(255,255,255,0.2); }

        .fee-card-page {
            max-width: 1200px;
            margin: 0 auto 40px;
            background: white;
            display: flex;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border-radius: 4px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .card-half {
            width: 50%;
            padding: 15px;
            font-size: 10px;
            position: relative;
        }
        .card-half.school-copy {
            border-right: 2px dashed #999;
        }

        .copy-label {
            text-align: center;
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            background: #1e293b;
            color: white;
            padding: 3px 0;
            margin-bottom: 8px;
            border-radius: 2px;
        }

        /* School Header */
        .school-title {
            text-align: center;
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .school-title h2 {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1px;
        }
        .school-title p {
            font-size: 9px;
            color: #444;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Student Info Table */
        .info-table {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 1px 2px;
            font-size: 10px;
            vertical-align: top;
        }
        .info-table .lbl {
            font-weight: 700;
            width: 22%;
        }
        .info-table .val {
            font-weight: 500;
            border-bottom: 1px dotted #888;
        }
        .info-table .lbl-r {
            font-weight: 700;
            text-align: right;
            padding-right: 4px;
            width: 20%;
        }

        /* Main Months Grid */
        .fee-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .fee-grid th, .fee-grid td {
            border: 1px solid #000;
            padding: 3px 1px;
            text-align: center;
            font-size: 9px;
        }
        .fee-grid th {
            background: #f0f0f0;
            font-weight: 700;
        }
        .fee-grid .row-lbl {
            text-align: left;
            padding-left: 3px;
            font-weight: 700;
            background: #f5f5f5;
            width: 70px;
        }

        /* Bottom Section Side by Side */
        .bottom-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-top: 10px;
        }

        .charges-table {
            width: 58%;
            border-collapse: collapse;
        }
        .charges-table th, .charges-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            font-size: 8.5px;
        }
        .charges-table th {
            background: #f0f0f0;
            font-weight: 700;
        }

        .totals-box-table {
            width: 38%;
            border-collapse: collapse;
        }
        .totals-box-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 9.5px;
        }
        .totals-box-table .t-lbl {
            font-weight: 700;
            background: #f5f5f5;
        }
        .totals-box-table .t-val {
            text-align: center;
            font-weight: 800;
        }

        .cashier-area {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9.5px;
            font-weight: 700;
        }
        .cashier-area .dots {
            width: 100px;
            border-bottom: 1px dotted #000;
            margin-left: 4px;
            display: inline-block;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body { background: white !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .fee-card-page { 
                box-shadow: none !important; 
                border-radius: 0 !important;
                margin: 0 auto 10px !important;
                max-width: 100% !important;
            }
            .fee-grid th, .copy-label, .charges-table th, .totals-box-table .t-lbl {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page {
                size: landscape;
                margin: 4mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">🖨️ Print All Fee Cards (<?= count($students) ?> Students)</button>
        <a href="manage.php" class="btn-back">← Back</a>
    </div>

    <?php 
    $student_count = count($students);
    foreach($students as $index => $student): 
        $student_id = $student['id'];
        $monthly_fee = isset($student['monthly_fee']) ? (float)$student['monthly_fee'] : 0;
        $student_payments = isset($all_fee_payments[$student_id]) ? $all_fee_payments[$student_id] : [];
        
        // Calculate totals to find opening balance
        $total_billed_this_year = 0;
        $total_paid_this_year = 0;
        foreach($school_months as $m) {
            if($billed_status[$m]) {
                $total_billed_this_year += $monthly_fee;
            }
            if(isset($student_payments[$m])) {
                $total_paid_this_year += $student_payments[$m];
            }
        }

        // Opening balance / B/F for April
        $opening_balance = (float)$student['previous_dues'] + $total_paid_this_year - $total_billed_this_year;

        // Build monthly records
        $month_records = [];
        $running_balance = $opening_balance;

        $billed_months_count = 0;
        $paid_months_count = 0;
        $unpaid_months_count = 0;

        $current_fee_sum = 0;
        $paid_sum = 0;

        $first_unbilled = true;
        foreach($school_months as $m) {
            if($billed_status[$m]) {
                $billed_months_count++;
                $bf = $running_balance;
                $current_fee = $monthly_fee;
                $total = $bf + $current_fee;
                $paid = isset($student_payments[$m]) ? $student_payments[$m] : 0;
                $discount = 0;
                $bal = $total - $paid - $discount;
                
                $current_fee_sum += $current_fee;
                $paid_sum += $paid;
                
                if($paid >= $monthly_fee) {
                    $paid_months_count++;
                } else {
                    $unpaid_months_count++;
                }
                
                $month_records[$m] = [
                    'bf' => $bf,
                    'current_fee' => $current_fee,
                    'total' => $total,
                    'paid' => $paid > 0 ? $paid : '',
                    'discount' => '',
                    'balance' => $bal
                ];
                $running_balance = $bal;
            } else {
                $bf_val = '';
                if ($first_unbilled) {
                    $bf_val = $running_balance;
                    $first_unbilled = false;
                }
                $month_records[$m] = [
                    'bf' => $bf_val,
                    'current_fee' => '',
                    'total' => '',
                    'paid' => '',
                    'discount' => '',
                    'balance' => ''
                ];
            }
        }

        // Fee upto string
        $fee_upto = '—';
        foreach(array_reverse($school_months) as $m) {
            if(isset($student_payments[$m]) && $student_payments[$m] > 0) {
                $is_next_year = in_array($m, ['January','February','March']);
                $fee_upto = $months_full[$m] . ' ' . ($is_next_year ? ($year + 1) : $year);
                break;
            }
        }
        if($fee_upto == '—' && $billed_months_count > 0) {
            foreach(array_reverse($school_months) as $m) {
                if($billed_status[$m]) {
                    $is_next_year = in_array($m, ['January','February','March']);
                    $fee_upto = $months_full[$m] . ' ' . ($is_next_year ? ($year + 1) : $year);
                    break;
                }
            }
        }
    ?>

    <div class="fee-card-page">
        <?php for($copy = 0; $copy < 2; $copy++): 
            $copy_name = ($copy == 0) ? 'School Copy' : 'Student Copy';
        ?>
        <div class="card-half <?= $copy == 0 ? 'school-copy' : '' ?>">
            <!-- Copy Label -->
            <div class="copy-label"><?= $copy_name ?></div>

            <!-- School Header -->
            <div class="school-title">
                <h2>Aslam Malik School</h2>
                <p>Matta Swat Pirkalay</p>
            </div>

            <!-- Student Info -->
            <table class="info-table">
                <tr>
                    <td class="lbl">Name:</td>
                    <td class="val"><?= htmlspecialchars($student['name']) ?></td>
                    <td class="lbl-r">Fee upto:</td>
                    <td class="val" style="font-weight: 700;"><?= $fee_upto ?></td>
                </tr>
                <tr>
                    <td class="lbl">Father's Name:</td>
                    <td class="val"><?= htmlspecialchars($student['father_name']) ?></td>
                    <td class="lbl-r">ID:</td>
                    <td class="val" style="font-weight: 700;"><?= $student['admission_no'] ?></td>
                </tr>
                <tr>
                    <td class="lbl">Address:</td>
                    <td class="val"><?= htmlspecialchars($student['address']) ?></td>
                    <td class="lbl-r">Class:</td>
                    <td class="val"><?= htmlspecialchars($student['class_name']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Cell:</td>
                    <td class="val"><?= htmlspecialchars($student['phone']) ?></td>
                    <td class="lbl-r"></td>
                    <td class="val"></td>
                </tr>
            </table>

            <!-- Main Months Grid -->
            <table class="fee-grid">
                <thead>
                    <tr>
                        <th>Month</th>
                        <?php foreach($school_months as $m): ?>
                        <th style="font-size: 8px;"><?= $months_short[$m] ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="row-lbl">B/F</td>
                        <?php foreach($school_months as $m): ?>
                        <td><?= $month_records[$m]['bf'] !== '' ? number_format($month_records[$m]['bf'], 0) : '' ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="row-lbl">Current Fee</td>
                        <?php foreach($school_months as $m): ?>
                        <td style="font-weight: 600;"><?= $month_records[$m]['current_fee'] !== '' ? number_format($month_records[$m]['current_fee'], 0) : '' ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="row-lbl">Total Fee</td>
                        <?php foreach($school_months as $m): ?>
                        <td style="font-weight: 600;"><?= $month_records[$m]['total'] !== '' ? number_format($month_records[$m]['total'], 0) : '' ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="row-lbl" style="color: #059669;">Paid</td>
                        <?php foreach($school_months as $m): ?>
                        <td style="color: #059669; font-weight: 700;"><?= $month_records[$m]['paid'] !== '' ? number_format((float)$month_records[$m]['paid'], 0) : '' ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="row-lbl">Discount</td>
                        <?php foreach($school_months as $m): ?>
                        <td><?= $month_records[$m]['discount'] ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr style="background: #fbfbfb;">
                        <td class="row-lbl" style="color: #dc2626;">Balance</td>
                        <?php foreach($school_months as $m): ?>
                        <td style="color: #dc2626; font-weight: 700;"><?= $month_records[$m]['balance'] !== '' ? number_format($month_records[$m]['balance'], 0) : '' ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>

            <!-- Bottom Section -->
            <div class="bottom-section">
                <!-- Left: Charges Breakdown -->
                <table class="charges-table">
                    <thead>
                        <tr>
                            <th>Adm/Annual</th>
                            <th>Tuition</th>
                            <th>Transport</th>
                            <th>Exam</th>
                            <th>Late Fee</th>
                            <th>Others</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>0</td>
                            <td style="font-weight: 600;"><?= number_format($monthly_fee, 0) ?></td>
                            <td>0</td>
                            <td>0</td>
                            <td>0</td>
                            <td>0</td>
                            <td style="font-weight: 700;"><?= number_format($monthly_fee, 0) ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Right: Net Totals and Month Counts -->
                <table class="totals-box-table">
                    <tr>
                        <td class="t-lbl">Net Total</td>
                        <td class="t-val"><?= number_format($current_fee_sum + $opening_balance, 0) ?></td>
                    </tr>
                    <tr>
                        <td class="t-lbl">Paid (<?= $paid_months_count ?> Mo)</td>
                        <td class="t-val" style="color: #059669;"><?= number_format($paid_sum, 0) ?></td>
                    </tr>
                    <tr>
                        <td class="t-lbl">Balance (<?= $unpaid_months_count ?> Mo)</td>
                        <td class="t-val" style="color: #dc2626;"><?= number_format(($current_fee_sum + $opening_balance) - $paid_sum, 0) ?></td>
                    </tr>
                </table>
            </div>

            <!-- Cashier Sign Area -->
            <div class="cashier-area">
                <div>Cashier <span class="dots"></span></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <?php if($index < $student_count - 1): ?>
        <div class="page-break"></div>
    <?php endif; ?>

    <?php endforeach; ?>
</body>
</html>
