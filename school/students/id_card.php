<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// Handle AJAX Save Settings
if (isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    header('Content-Type: application/json');
    $school_name = $conn->real_escape_string($_POST['school_name'] ?? '');
    $tagline = $conn->real_escape_string($_POST['tagline'] ?? '');
    $campus = $conn->real_escape_string($_POST['campus'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $address = $conn->real_escape_string($_POST['address'] ?? '');
    $session_year = $conn->real_escape_string($_POST['session_year'] ?? '');
    $principal_title = $conn->real_escape_string($_POST['principal_title'] ?? '');
    $signature_text = $conn->real_escape_string($_POST['signature_text'] ?? '');
    $theme = $conn->real_escape_string($_POST['theme'] ?? 'royal_navy');

    $chk = $conn->query("SELECT id FROM id_card_settings LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $row = $chk->fetch_assoc();
        $set_id = $row['id'];
        $sql = "UPDATE id_card_settings SET 
                school_name='$school_name', tagline='$tagline', campus='$campus', 
                phone='$phone', email='$email', address='$address', 
                session_year='$session_year', principal_title='$principal_title', 
                signature_text='$signature_text', theme='$theme' 
                WHERE id=$set_id";
    } else {
        $sql = "INSERT INTO id_card_settings (school_name, tagline, campus, phone, email, address, session_year, principal_title, signature_text, theme) 
                VALUES ('$school_name', '$tagline', '$campus', '$phone', '$email', '$address', '$session_year', '$principal_title', '$signature_text', '$theme')";
    }
    $success = $conn->query($sql);
    echo json_encode(['status' => $success ? 'success' : 'error', 'message' => $conn->error]);
    exit;
}

// Fetch current Settings
$school_settings = [
    'school_name' => 'ASLAM PUBLIC HIGH SCHOOL',
    'tagline' => 'Excellence in Knowledge & Character',
    'campus' => 'Main Campus',
    'phone' => '+92 300 1234567',
    'email' => 'info@aslamschool.edu',
    'address' => 'College Road, Model Town',
    'session_year' => '2026 - 2027',
    'principal_title' => 'Principal',
    'signature_text' => 'Authorized Signatory',
    'theme' => 'royal_navy'
];
$set_res = $conn->query("SELECT * FROM id_card_settings LIMIT 1");
if ($set_res && $set_res->num_rows > 0) {
    $school_settings = array_merge($school_settings, $set_res->fetch_assoc());
}

// Parameters
$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$mode = isset($_GET['mode']) ? $_GET['mode'] : ($selected_class > 0 && !$selected_id ? 'batch' : 'single');

// Fetch classes
$classes_res = $conn->query("SELECT * FROM classes ORDER BY id ASC");
$classes = [];
while ($c = $classes_res->fetch_assoc()) {
    $classes[] = $c;
}

// Fetch student list for selection
$students_res = $conn->query("SELECT s.id, s.admission_no, s.name, c.name as class_name, s.class_id 
                             FROM students s 
                             JOIN classes c ON s.class_id = c.id 
                             ORDER BY s.class_id ASC, s.name ASC");
$all_students = [];
while ($st = $students_res->fetch_assoc()) {
    $all_students[] = $st;
}

// Default student if none selected
if ($selected_id <= 0 && $mode === 'single' && !empty($all_students)) {
    $selected_id = $all_students[0]['id'];
}

// Fetch target students to render
$cards_data = [];
if ($mode === 'batch' && $selected_class > 0) {
    $target_query = "SELECT s.*, c.name as class_name, sf.photo_path as face_photo 
                     FROM students s 
                     JOIN classes c ON s.class_id = c.id 
                     LEFT JOIN student_faces sf ON s.id = sf.student_id 
                     WHERE s.class_id = $selected_class 
                     ORDER BY s.admission_no ASC";
    $res = $conn->query($target_query);
    while ($r = $res->fetch_assoc()) {
        $cards_data[] = $r;
    }
} elseif ($mode === 'batch' && $selected_class === 0) {
    // All students
    $target_query = "SELECT s.*, c.name as class_name, sf.photo_path as face_photo 
                     FROM students s 
                     JOIN classes c ON s.class_id = c.id 
                     LEFT JOIN student_faces sf ON s.id = sf.student_id 
                     ORDER BY s.class_id ASC, s.admission_no ASC";
    $res = $conn->query($target_query);
    while ($r = $res->fetch_assoc()) {
        $cards_data[] = $r;
    }
} else {
    // Single student
    if ($selected_id > 0) {
        $target_query = "SELECT s.*, c.name as class_name, sf.photo_path as face_photo 
                         FROM students s 
                         JOIN classes c ON s.class_id = c.id 
                         LEFT JOIN student_faces sf ON s.id = sf.student_id 
                         WHERE s.id = $selected_id";
        $res = $conn->query($target_query);
        if ($res && $res->num_rows > 0) {
            $cards_data[] = $res->fetch_assoc();
        }
    }
}

// Host for QR code verification URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_verify_url = $protocol . $host . "/school/students/verify_card.php?adm=";
?>

<!-- QR Code & Canvas Scripts -->
<script src="/school/js/qrcode.min.js"></script>
<script src="/school/js/html2canvas.min.js"></script>

<style>
/* =========================================================
   STUDENT ID CARD - EXACT L=2in, H=3.4in DESIGN SPECIFICATION
   ========================================================= */

:root {
    --id-card-width: 2.0in;
    --id-card-height: 3.4in;
    --id-scale: 1.45; /* Crisp preview scale on screen */
}

/* Print Rules - Precise 2.0in x 3.4in output */
@media print {
    body {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #sidebar-wrapper, .navbar, .no-print, .studio-sidebar, .card-control-header {
        display: none !important;
    }
    #page-content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
    }
    .print-sheet-container {
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .id-cards-grid {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8mm 6mm !important;
        justify-content: flex-start !important;
        padding: 6mm !important;
    }
    .id-card-wrapper {
        margin: 0 !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
        box-shadow: none !important;
    }
    .id-card {
        width: 2.0in !important;
        height: 3.4in !important;
        min-width: 2.0in !important;
        min-height: 3.4in !important;
        max-width: 2.0in !important;
        max-height: 3.4in !important;
        border: 1px solid #94a3b8 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        transform: none !important;
    }
    .cutting-guides {
        display: block !important;
    }
}

/* Screen Preview Styling */
.studio-layout {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}
.studio-sidebar {
    width: 360px;
    flex-shrink: 0;
}
.studio-stage {
    flex-grow: 1;
    min-width: 0;
}

/* Card Container */
.id-card-wrapper {
    position: relative;
    display: inline-block;
    transition: transform 0.2s ease;
}

.id-card {
    width: calc(2.0in * var(--id-scale));
    height: calc(3.4in * var(--id-scale));
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    color: #1e293b;
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-sizing: border-box;
    user-select: none;
}

/* Cutting Marks (Visible in print/bulk) */
.cutting-guides {
    display: none;
    position: absolute;
    top: -4px;
    left: -4px;
    right: -4px;
    bottom: -4px;
    border: 1px dashed #cbd5e1;
    pointer-events: none;
    border-radius: 14px;
}

/* Header Section */
.card-header-band {
    padding: 10px 8px 6px;
    text-align: center;
    position: relative;
    z-index: 2;
}
.card-school-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-bottom: 2px;
    font-size: 13px;
}
.card-school-title {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.3px;
    line-height: 1.15;
    text-transform: uppercase;
    margin: 0;
}
.card-school-tagline {
    font-size: 0.44rem;
    font-weight: 600;
    letter-spacing: 0.4px;
    opacity: 0.9;
    margin-top: 1px;
    text-transform: uppercase;
}
.card-badge-pill {
    display: inline-block;
    font-size: 0.46rem;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 2px 10px;
    border-radius: 10px;
    margin-top: 4px;
}

/* Photo and Student Identity */
.card-body-section {
    padding: 2px 12px 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
    flex-grow: 1;
}
.photo-frame-wrap {
    position: relative;
    margin-top: -2px;
    margin-bottom: 5px;
}
.card-student-photo {
    width: 68px;
    height: 74px;
    border-radius: 10px;
    object-fit: cover;
    background: #e2e8f0;
    display: block;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}
.active-pulse-dot {
    position: absolute;
    bottom: -3px;
    right: -3px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #22c55e;
    border: 2.5px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.student-primary-name {
    font-size: 0.84rem;
    font-weight: 800;
    text-align: center;
    line-height: 1.15;
    margin-bottom: 2px;
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.class-roll-pills {
    display: flex;
    gap: 4px;
    justify-content: center;
    margin-bottom: 6px;
}
.pill-adm, .pill-class {
    font-size: 0.50rem;
    font-weight: 700;
    padding: 1.5px 7px;
    border-radius: 6px;
    letter-spacing: 0.2px;
}

/* Student Field Rows */
.student-fields-box {
    width: 100%;
    background: rgba(241, 245, 249, 0.6);
    border-radius: 8px;
    padding: 5px 8px;
    margin-bottom: 6px;
    border: 1px solid rgba(226, 232, 240, 0.9);
}
.field-item-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.52rem;
    line-height: 1.4;
    padding: 1px 0;
    border-bottom: 1px dashed rgba(203, 213, 225, 0.6);
}
.field-item-row:last-child {
    border-bottom: none;
}
.field-label {
    color: #64748b;
    font-weight: 600;
}
.field-value {
    color: #0f172a;
    font-weight: 700;
    text-align: right;
    max-width: 125px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Bottom Bar with QR Code */
.card-footer-area {
    padding: 4px 10px 8px;
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.qr-code-box {
    background: #ffffff;
    padding: 3px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    box-shadow: 0 2px 5px rgba(0,0,0,0.06);
    display: inline-flex;
    flex-direction: column;
    align-items: center;
}
.qr-code-box .qr-img {
    width: 52px;
    height: 52px;
}
.qr-scan-micro {
    font-size: 0.38rem;
    font-weight: 800;
    letter-spacing: 0.3px;
    color: #475569;
    margin-top: 1px;
    text-transform: uppercase;
}
.footer-auth-sign {
    text-align: center;
    flex-grow: 1;
    padding-left: 8px;
}
.sign-line {
    border-top: 1px solid #94a3b8;
    margin: 18px 8px 2px;
}
.sign-title {
    font-size: 0.44rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

/* =========================================================
   BACK SIDE DESIGN SPECIFICATION
   ========================================================= */
.card-back-body {
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
}
.back-rules-title {
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.back-rules-list {
    list-style: none;
    padding: 0;
    margin: 0 0 8px;
    font-size: 0.46rem;
    line-height: 1.35;
    color: #334155;
}
.back-rules-list li {
    position: relative;
    padding-left: 10px;
    margin-bottom: 3px;
}
.back-rules-list li::before {
    content: "•";
    position: absolute;
    left: 0;
    font-weight: bold;
    color: #64748b;
}
.back-contact-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 5px 8px;
    font-size: 0.48rem;
    line-height: 1.35;
    margin-bottom: 8px;
}
.back-contact-title {
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
    font-size: 0.52rem;
}
.back-barcode-wrap {
    text-align: center;
    padding-top: 4px;
    border-top: 1px dashed #cbd5e1;
}
.barcode-visual {
    height: 22px;
    background: repeating-linear-gradient(90deg, #0f172a, #0f172a 1.5px, transparent 1.5px, transparent 3.5px, #0f172a 3.5px, #0f172a 5.5px, transparent 5.5px, transparent 7px);
    width: 85%;
    margin: 0 auto 3px;
    border-radius: 2px;
}
.barcode-number {
    font-size: 0.48rem;
    font-family: monospace;
    font-weight: 700;
    letter-spacing: 2px;
    color: #334155;
}

/* =========================================================
   THEME STYLES (Auto Design Themes)
   ========================================================= */

/* Theme 1: Royal Navy & Gold (Default) */
.theme-royal_navy .card-header-band {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
    color: #ffffff;
    border-bottom: 2.5px solid #f59e0b;
}
.theme-royal_navy .card-school-badge {
    background: #f59e0b;
    color: #0f172a;
}
.theme-royal_navy .card-badge-pill {
    background: #f59e0b;
    color: #0f172a;
}
.theme-royal_navy .card-student-photo {
    border: 2.5px solid #1e3a8a;
}
.theme-royal_navy .student-primary-name {
    color: #0f172a;
}
.theme-royal_navy .pill-adm {
    background: #1e3a8a;
    color: #ffffff;
}
.theme-royal_navy .pill-class {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}
.theme-royal_navy .back-rules-title {
    color: #1e3a8a;
}

/* Theme 2: Emerald Prestige */
.theme-emerald_prestige .card-header-band {
    background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
    color: #ffffff;
    border-bottom: 2.5px solid #34d399;
}
.theme-emerald_prestige .card-school-badge {
    background: #34d399;
    color: #064e3b;
}
.theme-emerald_prestige .card-badge-pill {
    background: #34d399;
    color: #064e3b;
}
.theme-emerald_prestige .card-student-photo {
    border: 2.5px solid #047857;
}
.theme-emerald_prestige .student-primary-name {
    color: #064e3b;
}
.theme-emerald_prestige .pill-adm {
    background: #047857;
    color: #ffffff;
}
.theme-emerald_prestige .pill-class {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.theme-emerald_prestige .back-rules-title {
    color: #064e3b;
}

/* Theme 3: Crimson Elite */
.theme-crimson_elite .card-header-band {
    background: linear-gradient(135deg, #4c0519 0%, #be123c 100%);
    color: #ffffff;
    border-bottom: 2.5px solid #fb7185;
}
.theme-crimson_elite .card-school-badge {
    background: #fb7185;
    color: #4c0519;
}
.theme-crimson_elite .card-badge-pill {
    background: #fb7185;
    color: #ffffff;
}
.theme-crimson_elite .card-student-photo {
    border: 2.5px solid #be123c;
}
.theme-crimson_elite .student-primary-name {
    color: #881337;
}
.theme-crimson_elite .pill-adm {
    background: #be123c;
    color: #ffffff;
}
.theme-crimson_elite .pill-class {
    background: #ffe4e6;
    color: #9f1239;
    border: 1px solid #fecdd3;
}
.theme-crimson_elite .back-rules-title {
    color: #881337;
}

/* Theme 4: Dark Titanium / Tech */
.theme-dark_tech .card-header-band {
    background: linear-gradient(135deg, #090d16 0%, #1e293b 100%);
    color: #ffffff;
    border-bottom: 2.5px solid #06b6d4;
}
.theme-dark_tech .card-school-badge {
    background: #06b6d4;
    color: #0f172a;
}
.theme-dark_tech .card-badge-pill {
    background: #06b6d4;
    color: #0f172a;
}
.theme-dark_tech .card-student-photo {
    border: 2.5px solid #06b6d4;
}
.theme-dark_tech .student-primary-name {
    color: #0f172a;
}
.theme-dark_tech .pill-adm {
    background: #0f172a;
    color: #38bdf8;
}
.theme-dark_tech .pill-class {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.theme-dark_tech .back-rules-title {
    color: #0f172a;
}

/* Theme 5: Classic Clean Academic */
.theme-classic_clean .card-header-band {
    background: #ffffff;
    color: #1e3a8a;
    border-bottom: 2.5px solid #1e3a8a;
}
.theme-classic_clean .card-school-badge {
    background: #1e3a8a;
    color: #ffffff;
}
.theme-classic_clean .card-badge-pill {
    background: #1e3a8a;
    color: #ffffff;
}
.theme-classic_clean .card-student-photo {
    border: 2.5px solid #1e3a8a;
}
.theme-classic_clean .student-primary-name {
    color: #1e3a8a;
}
.theme-classic_clean .pill-adm {
    background: #334155;
    color: #ffffff;
}
.theme-classic_clean .pill-class {
    background: #f1f5f9;
    color: #1e293b;
    border: 1px solid #cbd5e1;
}
.theme-classic_clean .back-rules-title {
    color: #1e3a8a;
}

/* Theme Selector Pills */
.theme-pill-btn {
    cursor: pointer;
    border-radius: 12px;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 2px solid transparent;
    transition: all 0.2s ease;
    background: #f8fafc;
    margin-bottom: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}
.theme-pill-btn:hover {
    background: #f1f5f9;
}
.theme-pill-btn.active {
    border-color: #2563eb;
    background: #eff6ff;
}
.theme-color-dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

/* Size Pill Indicator */
.size-indicator-badge {
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 800;
    font-size: 0.78rem;
    padding: 4px 10px;
    border-radius: 8px;
    border: 1px solid #bae6fd;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
</style>

<!-- Top Navigation & Actions Bar -->
<div class="card shadow-sm border-0 rounded-4 mb-4 no-print">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h3 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-id-card text-primary me-2"></i>Auto Design Student ID Cards
                </h3>
                <p class="text-muted small mb-0">
                    Auto-formatted to exact portrait card size <span class="badge bg-primary-subtle text-primary fw-bold">L = 2.0" × H = 3.4"</span> with dynamic QR code & high-res print engine.
                </p>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Size spec chip -->
                <div class="size-indicator-badge">
                    <i class="fa-solid fa-ruler-combined"></i>
                    <span>Size: <strong>2" × 3.4"</strong></span>
                </div>

                <!-- Print Button -->
                <button onclick="window.print()" class="btn btn-primary fw-bold px-3">
                    <i class="fa-solid fa-print me-2"></i> Print Card(s)
                </button>

                <!-- Download Image Button -->
                <button onclick="downloadCardImage()" class="btn btn-outline-dark fw-bold px-3" id="btnDownload">
                    <i class="fa-solid fa-download me-2"></i> Download Image
                </button>

                <a href="/school/students/manage.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Students
                </a>
            </div>
        </div>
    </div>
</div>

<div class="studio-layout">
    <!-- LEFT SIDEBAR: AUTO DESIGN CONTROLS -->
    <div class="studio-sidebar no-print">
        <div class="card shadow border-0 rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-sliders text-primary me-2"></i>Card Studio Controls
                </h6>
            </div>
            <div class="card-body">
                <!-- Selection Mode Tabs -->
                <ul class="nav nav-pills nav-fill mb-3" id="modeTabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $mode === 'single' ? 'active' : '' ?>" href="?mode=single<?= $selected_id ? "&id=$selected_id" : '' ?>">
                            <i class="fa-solid fa-user me-1"></i> Single Student
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $mode === 'batch' ? 'active' : '' ?>" href="?mode=batch<?= $selected_class ? "&class_id=$selected_class" : '' ?>">
                            <i class="fa-solid fa-users me-1"></i> Class Batch
                        </a>
                    </li>
                </ul>

                <?php if ($mode === 'single'): ?>
                    <!-- Single Student Picker -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Select Student</label>
                        <select class="form-select" onchange="window.location.href='?mode=single&id=' + this.value">
                            <?php foreach ($all_students as $st): ?>
                                <option value="<?= $st['id'] ?>" <?= $st['id'] == $selected_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['name']) ?> (<?= htmlspecialchars($st['class_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <!-- Batch Class Picker -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Select Class for Batch Print</label>
                        <select class="form-select" onchange="window.location.href='?mode=batch&class_id=' + this.value">
                            <option value="0" <?= $selected_class == 0 ? 'selected' : '' ?>>All Classes (Whole School)</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $selected_class ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">
                            <i class="fa-solid fa-info-circle me-1"></i> Generates print sheet for <?= count($cards_data) ?> students.
                        </small>
                    </div>
                <?php endif; ?>

                <hr class="my-3">

                <!-- Card View Side Selector -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Card View Sides</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="btnViewFront" onclick="setViewSide('front')">
                            Front
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="btnViewBack" onclick="setViewSide('back')">
                            Back
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="btnViewBoth" onclick="setViewSide('both')">
                            Both Sides
                        </button>
                    </div>
                </div>

                <!-- Auto Design Themes -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Auto Design Template Theme</label>
                    <div class="theme-selector-list">
                        <div class="theme-pill-btn <?= $school_settings['theme'] === 'royal_navy' ? 'active' : '' ?>" onclick="switchTheme('royal_navy', this)">
                            <span class="theme-color-dot" style="background: linear-gradient(135deg, #0f172a, #1e3a8a);"></span>
                            <span>Royal Navy & Gold</span>
                        </div>
                        <div class="theme-pill-btn <?= $school_settings['theme'] === 'emerald_prestige' ? 'active' : '' ?>" onclick="switchTheme('emerald_prestige', this)">
                            <span class="theme-color-dot" style="background: linear-gradient(135deg, #064e3b, #047857);"></span>
                            <span>Emerald Prestige</span>
                        </div>
                        <div class="theme-pill-btn <?= $school_settings['theme'] === 'crimson_elite' ? 'active' : '' ?>" onclick="switchTheme('crimson_elite', this)">
                            <span class="theme-color-dot" style="background: linear-gradient(135deg, #4c0519, #be123c);"></span>
                            <span>Crimson Elite</span>
                        </div>
                        <div class="theme-pill-btn <?= $school_settings['theme'] === 'dark_tech' ? 'active' : '' ?>" onclick="switchTheme('dark_tech', this)">
                            <span class="theme-color-dot" style="background: linear-gradient(135deg, #090d16, #06b6d4);"></span>
                            <span>Dark Titanium & Neon</span>
                        </div>
                        <div class="theme-pill-btn <?= $school_settings['theme'] === 'classic_clean' ? 'active' : '' ?>" onclick="switchTheme('classic_clean', this)">
                            <span class="theme-color-dot" style="background: #ffffff; border-color: #1e3a8a;"></span>
                            <span>Classic Academic White</span>
                        </div>
                    </div>
                </div>

                <!-- Field Toggles -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Visible Details</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-check form-switch small">
                                <input class="form-check-input" type="checkbox" id="toggleFather" checked onchange="toggleField('field-father', this.checked)">
                                <label class="form-check-label" for="toggleFather">Father Name</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch small">
                                <input class="form-check-input" type="checkbox" id="toggleDob" checked onchange="toggleField('field-dob', this.checked)">
                                <label class="form-check-label" for="toggleDob">Date of Birth</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch small">
                                <input class="form-check-input" type="checkbox" id="toggleBlood" checked onchange="toggleField('field-blood', this.checked)">
                                <label class="form-check-label" for="toggleBlood">Blood Group</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch small">
                                <input class="form-check-input" type="checkbox" id="togglePhone" checked onchange="toggleField('field-phone', this.checked)">
                                <label class="form-check-label" for="togglePhone">Phone / Contact</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch small">
                                <input class="form-check-input" type="checkbox" id="toggleQR" checked onchange="toggleField('field-qr', this.checked)">
                                <label class="form-check-label" for="toggleQR">QR Code</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch small">
                                <input class="form-check-input" type="checkbox" id="toggleSign" checked onchange="toggleField('field-sign', this.checked)">
                                <label class="form-check-label" for="toggleSign">Signature</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accordion: Edit School Info & Branding -->
                <div class="accordion" id="settingsAccordion">
                    <div class="accordion-item border-0 bg-light rounded-3 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-light text-dark fw-bold small py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSettings">
                                <i class="fa-solid fa-school text-primary me-2"></i> Customise School Branding
                            </button>
                        </h2>
                        <div id="collapseSettings" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                            <div class="accordion-body p-3">
                                <form id="settingsForm" onsubmit="saveSchoolSettings(event)">
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted mb-1">School Name</label>
                                        <input type="text" name="school_name" class="form-control form-control-sm" value="<?= htmlspecialchars($school_settings['school_name']) ?>" oninput="updateLiveText('live-school-name', this.value)">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted mb-1">Tagline / Motto</label>
                                        <input type="text" name="tagline" class="form-control form-control-sm" value="<?= htmlspecialchars($school_settings['tagline']) ?>" oninput="updateLiveText('live-school-tagline', this.value)">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted mb-1">Academic Session</label>
                                        <input type="text" name="session_year" class="form-control form-control-sm" value="<?= htmlspecialchars($school_settings['session_year']) ?>" oninput="updateLiveText('live-session-year', this.value)">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted mb-1">School Phone</label>
                                        <input type="text" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($school_settings['phone']) ?>" oninput="updateLiveText('live-school-phone', this.value)">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted mb-1">School Address</label>
                                        <input type="text" name="address" class="form-control form-control-sm" value="<?= htmlspecialchars($school_settings['address']) ?>" oninput="updateLiveText('live-school-address', this.value)">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted mb-1">Signatory Title</label>
                                        <input type="text" name="principal_title" class="form-control form-control-sm" value="<?= htmlspecialchars($school_settings['principal_title']) ?>" oninput="updateLiveText('live-sign-title', this.value)">
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-success w-100 mt-2">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Save As Default
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- RIGHT STAGE: LIVE CARD PREVIEW & PRINT CANVAS -->
    <div class="studio-stage">
        <?php if (empty($cards_data)): ?>
            <div class="alert alert-warning text-center py-5 shadow-sm rounded-4">
                <i class="fa-solid fa-circle-exclamation fs-1 text-warning mb-3"></i>
                <h5>No Student Records Found</h5>
                <p class="text-muted">Please add students to generate their ID cards.</p>
                <a href="/school/students/add.php" class="btn btn-primary mt-2">Add Student</a>
            </div>
        <?php else: ?>
            <!-- Card Stage Container -->
            <div class="card shadow border-0 rounded-4 p-4 text-center bg-white print-sheet-container" id="printableArea">
                
                <div class="d-flex justify-content-between align-items-center mb-4 no-print border-bottom pb-3">
                    <div class="text-start">
                        <span class="badge bg-light text-dark border me-2">
                            <i class="fa-solid fa-layer-group text-primary me-1"></i>
                            Showing: <strong><?= count($cards_data) ?> Card<?= count($cards_data) > 1 ? 's' : '' ?></strong>
                        </span>
                        <span class="badge bg-light text-dark border">
                            <i class="fa-solid fa-arrows-alt text-success me-1"></i>
                            Aspect Ratio: <strong>2 : 3.4 (Standard Portrait Badge)</strong>
                        </span>
                    </div>

                    <!-- Zoom / Scale Control -->
                    <div class="d-flex align-items-center gap-2">
                        <label class="small fw-bold text-muted">Preview Scale:</label>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="setPreviewScale(this.value)">
                            <option value="1.0">100% (Actual 2" x 3.4")</option>
                            <option value="1.25">125%</option>
                            <option value="1.45" selected>145% (Retina HD)</option>
                            <option value="1.75">175% (Large View)</option>
                        </select>
                    </div>
                </div>

                <!-- Cards Grid -->
                <div class="id-cards-grid d-flex flex-wrap gap-4 justify-content-center" id="cardsGridContainer">
                    <?php foreach ($cards_data as $index => $stu): 
                        // Photo resolve logic
                        $stu_photo = "https://ui-avatars.com/api/?name=" . urlencode($stu['name']) . "&background=0f172a&color=fff&size=200";
                        if (!empty($stu['photo']) && file_exists('../uploads/students/' . $stu['photo'])) {
                            $stu_photo = '/school/uploads/students/' . htmlspecialchars($stu['photo']);
                        } elseif (!empty($stu['face_photo']) && file_exists('../uploads/faces/' . $stu['face_photo'])) {
                            $stu_photo = '/school/uploads/faces/' . htmlspecialchars($stu['face_photo']);
                        }

                        $qr_verify_data = $base_verify_url . urlencode($stu['admission_no']);
                        $card_unique_id = "card_" . $stu['id'];
                    ?>

                    <!-- Individual Student Card Group (Front & Back) -->
                    <div class="student-card-pair d-flex flex-wrap gap-3 justify-content-center" id="<?= $card_unique_id ?>">
                        
                        <!-- FRONT SIDE -->
                        <div class="id-card-wrapper card-side-front">
                            <div class="cutting-guides"></div>
                            <div class="id-card theme-<?= htmlspecialchars($school_settings['theme']) ?>" id="front_<?= $stu['id'] ?>">
                                
                                <!-- Header Band -->
                                <div class="card-header-band">
                                    <div class="card-school-badge">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                    </div>
                                    <h4 class="card-school-title live-school-name"><?= htmlspecialchars($school_settings['school_name']) ?></h4>
                                    <div class="card-school-tagline live-school-tagline"><?= htmlspecialchars($school_settings['tagline']) ?></div>
                                    <span class="card-badge-pill">STUDENT IDENTITY CARD</span>
                                </div>

                                <!-- Body Section: Photo & Info -->
                                <div class="card-body-section">
                                    <div class="photo-frame-wrap">
                                        <img src="<?= $stu_photo ?>" alt="<?= htmlspecialchars($stu['name']) ?>" class="card-student-photo" crossorigin="anonymous">
                                        <div class="active-pulse-dot" title="Active Student"></div>
                                    </div>

                                    <div class="student-primary-name"><?= htmlspecialchars($stu['name']) ?></div>
                                    <div class="class-roll-pills">
                                        <span class="pill-adm">ADM: <?= htmlspecialchars($stu['admission_no']) ?></span>
                                        <span class="pill-class"><?= htmlspecialchars($stu['class_name']) ?></span>
                                    </div>

                                    <!-- Fields Details Box -->
                                    <div class="student-fields-box">
                                        <div class="field-item-row field-father">
                                            <span class="field-label">Father:</span>
                                            <span class="field-value"><?= htmlspecialchars($stu['father_name']) ?></span>
                                        </div>
                                        <div class="field-item-row field-dob">
                                            <span class="field-label">DOB:</span>
                                            <span class="field-value"><?= !empty($stu['dob']) && $stu['dob'] != '0000-00-00' ? date('d M Y', strtotime($stu['dob'])) : 'N/A' ?></span>
                                        </div>
                                        <div class="field-item-row field-blood">
                                            <span class="field-label">Blood:</span>
                                            <span class="field-value text-danger fw-bold"><?= !empty($stu['blood_group']) ? htmlspecialchars($stu['blood_group']) : 'O+' ?></span>
                                        </div>
                                        <div class="field-item-row field-phone">
                                            <span class="field-label">Phone:</span>
                                            <span class="field-value"><?= htmlspecialchars($stu['phone']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Area with QR Code & Signature -->
                                <div class="card-footer-area">
                                    <div class="qr-code-box field-qr">
                                        <div class="qr-img" id="qr_<?= $stu['id'] ?>" data-qr="<?= htmlspecialchars($qr_verify_data) ?>"></div>
                                        <span class="qr-scan-micro">SCAN ME</span>
                                    </div>
                                    <div class="footer-auth-sign field-sign">
                                        <div class="sign-line"></div>
                                        <div class="sign-title live-sign-title"><?= htmlspecialchars($school_settings['principal_title']) ?></div>
                                        <div style="font-size: 0.38rem; color: #64748b; margin-top: 1px;" class="live-session-year"><?= htmlspecialchars($school_settings['session_year']) ?></div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- BACK SIDE -->
                        <div class="id-card-wrapper card-side-back" style="display: none;">
                            <div class="cutting-guides"></div>
                            <div class="id-card theme-<?= htmlspecialchars($school_settings['theme']) ?>" id="back_<?= $stu['id'] ?>">
                                
                                <div class="card-header-band py-2">
                                    <h4 class="card-school-title live-school-name" style="font-size: 0.65rem;"><?= htmlspecialchars($school_settings['school_name']) ?></h4>
                                    <div class="card-school-tagline" style="font-size: 0.40rem;">RULES & CARD HOLDER INSTRUCTIONS</div>
                                </div>

                                <div class="card-back-body">
                                    <div>
                                        <div class="back-rules-title">
                                            <i class="fa-solid fa-shield-halved me-1"></i> Important Instructions
                                        </div>
                                        <ul class="back-rules-list">
                                            <li>This card is the property of the school and non-transferable.</li>
                                            <li>The student must wear this card visibly inside school premises.</li>
                                            <li>If lost or damaged, report immediately to the admin office.</li>
                                            <li>Present this card during exams and library access.</li>
                                        </ul>

                                        <div class="back-contact-box">
                                            <div class="back-contact-title">Student Address:</div>
                                            <div class="text-truncate-2" style="color: #475569; font-weight: 500;">
                                                <?= htmlspecialchars($stu['address'] ?: 'Campus Residence') ?>
                                            </div>
                                        </div>

                                        <div class="back-contact-box">
                                            <div class="back-contact-title">School Contact:</div>
                                            <div class="live-school-address" style="color: #475569;"><?= htmlspecialchars($school_settings['address']) ?></div>
                                            <div style="color: #0284c7; font-weight: 700;">Tel: <span class="live-school-phone"><?= htmlspecialchars($school_settings['phone']) ?></span></div>
                                        </div>
                                    </div>

                                    <div class="back-barcode-wrap">
                                        <div class="barcode-visual"></div>
                                        <div class="barcode-number">*<?= htmlspecialchars($stu['admission_no']) ?>*</div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<!-- QR Verification Test Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-qrcode text-primary me-2"></i>QR Code Verification URL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted small">When anyone scans this QR Code with their phone, it instantly opens the verified digital student record:</p>
                <div class="bg-light p-3 rounded-3 mb-3 border text-break font-monospace small" id="modalQrUrl"></div>
                <a href="#" target="_blank" id="modalVerifyBtn" class="btn btn-primary px-4 rounded-pill">
                    <i class="fa-solid fa-external-link me-2"></i> Open Verification Page
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// =========================================================
// AUTO GENERATE QR CODES VIA PURE JS
// =========================================================
document.addEventListener('DOMContentLoaded', function() {
    renderAllQRCodes();
});

function renderAllQRCodes() {
    const qrContainers = document.querySelectorAll('.qr-img');
    qrContainers.forEach(container => {
        container.innerHTML = ''; // Clear existing
        const dataUrl = container.getAttribute('data-qr');
        if (dataUrl && typeof QRCode !== 'undefined') {
            try {
                new QRCode(container, {
                    text: dataUrl,
                    width: 52,
                    height: 52,
                    colorDark: "#0f172a",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch (e) {
                console.error("QR Code generation error:", e);
                // Fallback image
                container.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=52x52&data=${encodeURIComponent(dataUrl)}" class="img-fluid" alt="QR">`;
            }
        }
    });
}

// =========================================================
// VIEW SIDE CONTROLLER (FRONT / BACK / BOTH)
// =========================================================
let currentSide = 'front';
function setViewSide(side) {
    currentSide = side;
    document.getElementById('btnViewFront').classList.remove('active');
    document.getElementById('btnViewBack').classList.remove('active');
    document.getElementById('btnViewBoth').classList.remove('active');

    const frontCards = document.querySelectorAll('.card-side-front');
    const backCards = document.querySelectorAll('.card-side-back');

    if (side === 'front') {
        document.getElementById('btnViewFront').classList.add('active');
        frontCards.forEach(c => c.style.display = 'inline-block');
        backCards.forEach(c => c.style.display = 'none');
    } else if (side === 'back') {
        document.getElementById('btnViewBack').classList.add('active');
        frontCards.forEach(c => c.style.display = 'none');
        backCards.forEach(c => c.style.display = 'inline-block');
    } else if (side === 'both') {
        document.getElementById('btnViewBoth').classList.add('active');
        frontCards.forEach(c => c.style.display = 'inline-block');
        backCards.forEach(c => c.style.display = 'inline-block');
    }
}

// =========================================================
// THEME SWITCHER
// =========================================================
let currentTheme = '<?= htmlspecialchars($school_settings['theme']) ?>';
function switchTheme(themeName, element) {
    currentTheme = themeName;
    document.querySelectorAll('.theme-pill-btn').forEach(btn => btn.classList.remove('active'));
    if (element) element.classList.add('active');

    // Update all card elements
    const cards = document.querySelectorAll('.id-card');
    cards.forEach(card => {
        card.className = card.className.replace(/\btheme-\S+/g, '');
        card.classList.add('theme-' + themeName);
    });
}

// =========================================================
// PREVIEW SCALE CONTROLLER
// =========================================================
function setPreviewScale(scale) {
    document.documentElement.style.setProperty('--id-scale', scale);
    renderAllQRCodes();
}

// =========================================================
// FIELD TOGGLES
// =========================================================
function toggleField(className, isVisible) {
    const elems = document.querySelectorAll('.' + className);
    elems.forEach(el => {
        el.style.display = isVisible ? '' : 'none';
    });
}

// =========================================================
// LIVE TEXT PREVIEW UPDATER
// =========================================================
function updateLiveText(className, text) {
    const targets = document.querySelectorAll('.' + className);
    targets.forEach(t => {
        t.textContent = text;
    });
}

// =========================================================
// SAVE SETTINGS VIA AJAX
// =========================================================
function saveSchoolSettings(e) {
    e.preventDefault();
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);
    formData.append('action', 'save_settings');
    formData.append('theme', currentTheme);

    fetch('id_card.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            alert('School Branding & Settings saved successfully!');
        } else {
            alert('Save error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Network error while saving settings');
    });
}

// =========================================================
// DOWNLOAD CARD AS PNG IMAGE (html2canvas)
// =========================================================
function downloadCardImage() {
    const btn = document.getElementById('btnDownload');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Generating Image...';
    btn.disabled = true;

    // Pick first active visible card or front card
    let targetCard = document.querySelector('.card-side-front .id-card');
    if (currentSide === 'back') {
        targetCard = document.querySelector('.card-side-back .id-card');
    }

    if (!targetCard) {
        alert('Card element not found to download.');
        btn.innerHTML = originalText;
        btn.disabled = false;
        return;
    }

    html2canvas(targetCard, {
        scale: 3, // Ultra-sharp 3x high resolution
        useCORS: true,
        allowTaint: true,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = `student_id_card_${currentSide}_${Date.now()}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        btn.innerHTML = originalText;
        btn.disabled = false;
    }).catch(err => {
        console.error(err);
        alert('Could not render image. Please use Print -> Save as PDF.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
