<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Utility helper to send JSON response
function json_resp($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 1. Get all students with face registration status
if ($action === 'get_students') {
    $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
    $search = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';

    $where = "WHERE 1=1";
    if ($class_id > 0) {
        $where .= " AND s.class_id = $class_id";
    }
    if ($search !== '') {
        $where .= " AND (s.name LIKE '%$search%' OR s.admission_no LIKE '%$search%' OR s.phone LIKE '%$search%')";
    }

    $sql = "SELECT s.id, s.admission_no, s.name, s.father_name, s.class_id, s.photo, s.phone,
                   c.name AS class_name,
                   sf.id AS face_id, sf.photo_path AS face_photo, sf.updated_at AS face_updated,
                   sf.sample_count,
                   (CASE WHEN sf.id IS NOT NULL THEN 1 ELSE 0 END) AS is_registered
            FROM students s
            JOIN classes c ON s.class_id = c.id
            LEFT JOIN student_faces sf ON s.id = sf.student_id
            $where
            ORDER BY s.class_id ASC, s.name ASC";

    $res = $conn->query($sql);
    $students = [];
    while ($row = $res->fetch_assoc()) {
        $students[] = [
            'id' => (int)$row['id'],
            'admission_no' => $row['admission_no'],
            'name' => $row['name'],
            'father_name' => $row['father_name'],
            'class_id' => (int)$row['class_id'],
            'class_name' => $row['class_name'],
            'photo' => $row['photo'],
            'phone' => $row['phone'],
            'is_registered' => (bool)$row['is_registered'],
            'face_photo' => $row['face_photo'],
            'face_updated' => $row['face_updated'],
            'sample_count' => (int)$row['sample_count']
        ];
    }
    json_resp(['success' => true, 'students' => $students]);
}

// 2. Get registered face descriptors for recognition engine
if ($action === 'get_face_descriptors') {
    $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
    $where = "WHERE 1=1";
    if ($class_id > 0) {
        $where .= " AND s.class_id = $class_id";
    }

    $sql = "SELECT s.id, s.admission_no, s.name, s.class_id, s.photo,
                   c.name AS class_name,
                   sf.face_descriptor, sf.photo_path AS face_photo
            FROM student_faces sf
            JOIN students s ON sf.student_id = s.id
            JOIN classes c ON s.class_id = c.id
            $where";

    $res = $conn->query($sql);
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $descriptors = json_decode($row['face_descriptor'], true);
        if ($descriptors) {
            $items[] = [
                'student_id' => (int)$row['id'],
                'admission_no' => $row['admission_no'],
                'name' => $row['name'],
                'class_id' => (int)$row['class_id'],
                'class_name' => $row['class_name'],
                'photo' => $row['photo'] ? $row['photo'] : $row['face_photo'],
                'face_photo' => $row['face_photo'],
                'descriptors' => $descriptors
            ];
        }
    }
    json_resp(['success' => true, 'count' => count($items), 'data' => $items]);
}

// 3. Save student face descriptor & snapshot
if ($action === 'save_face' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $student_id = isset($input['student_id']) ? (int)$input['student_id'] : 0;
    $descriptor_data = isset($input['descriptors']) ? $input['descriptors'] : null;
    $image_base64 = isset($input['image']) ? $input['image'] : '';

    if ($student_id <= 0 || empty($descriptor_data)) {
        json_resp(['success' => false, 'message' => 'Invalid student ID or face descriptor data.'], 400);
    }

    // Verify student exists
    $st_chk = $conn->query("SELECT id, name, admission_no FROM students WHERE id = $student_id");
    if (!$st_chk || $st_chk->num_rows === 0) {
        json_resp(['success' => false, 'message' => 'Student not found.'], 404);
    }
    $student = $st_chk->fetch_assoc();

    // Standardize descriptor as JSON string
    if (is_array($descriptor_data)) {
        $sample_count = is_array($descriptor_data[0]) ? count($descriptor_data) : 1;
        $descriptor_json = json_encode($descriptor_data);
    } else {
        $sample_count = 1;
        $descriptor_json = $descriptor_data;
    }

    // Save image snapshot if provided
    $photo_path = null;
    if (!empty($image_base64) && preg_match('/^data:image\/(\w+);base64,/', $image_base64, $type)) {
        $data = substr($image_base64, strpos($image_base64, ',') + 1);
        $data = base64_decode($data);
        if ($data !== false) {
            $ext = strtolower($type[1]);
            if ($ext === 'jpeg') $ext = 'jpg';
            $faces_dir = __DIR__ . '/../uploads/faces';
            if (!is_dir($faces_dir)) {
                mkdir($faces_dir, 0777, true);
            }
            $filename = 'face_' . $student_id . '_' . time() . '.' . $ext;
            if (file_put_contents($faces_dir . '/' . $filename, $data)) {
                $photo_path = 'uploads/faces/' . $filename;
            }
        }
    }

    // Save or update face descriptor
    $esc_desc = $conn->real_escape_string($descriptor_json);
    $esc_photo = $photo_path ? "'" . $conn->real_escape_string($photo_path) . "'" : "NULL";

    $chk = $conn->query("SELECT id, photo_path FROM student_faces WHERE student_id = $student_id");
    if ($chk && $chk->num_rows > 0) {
        $old = $chk->fetch_assoc();
        // Keep old photo if new photo wasn't sent
        if (!$photo_path && !empty($old['photo_path'])) {
            $esc_photo = "'" . $conn->real_escape_string($old['photo_path']) . "'";
        }
        $conn->query("UPDATE student_faces 
                      SET face_descriptor = '$esc_desc', 
                          photo_path = $esc_photo, 
                          sample_count = $sample_count,
                          updated_at = NOW() 
                      WHERE student_id = $student_id");
    } else {
        $conn->query("INSERT INTO student_faces (student_id, face_descriptor, photo_path, sample_count) 
                      VALUES ($student_id, '$esc_desc', $esc_photo, $sample_count)");
    }

    json_resp([
        'success' => true,
        'message' => "Face registered successfully for {$student['name']} ({$student['admission_no']})!",
        'student_id' => $student_id,
        'photo_path' => $photo_path,
        'sample_count' => $sample_count
    ]);
}

// 4. Mark attendance with duplicate prevention
if ($action === 'mark_attendance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $student_id = isset($input['student_id']) ? (int)$input['student_id'] : 0;
    $confidence = isset($input['confidence']) ? (float)$input['confidence'] : 0.0;
    $custom_date = isset($input['att_date']) && !empty($input['att_date']) ? $input['att_date'] : date('Y-m-d');
    $custom_time = isset($input['att_time']) && !empty($input['att_time']) ? $input['att_time'] : date('H:i:s');

    if ($student_id <= 0) {
        json_resp(['success' => false, 'message' => 'Invalid student ID.'], 400);
    }

    // Verify student info
    $stmt = $conn->prepare("SELECT s.id, s.admission_no, s.name, s.father_name, s.class_id, s.photo,
                                   c.name AS class_name, sf.photo_path AS face_photo
                            FROM students s
                            JOIN classes c ON s.class_id = c.id
                            LEFT JOIN student_faces sf ON s.id = sf.student_id
                            WHERE s.id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        json_resp(['success' => false, 'message' => 'Student not found.'], 404);
    }

    $att_date = $conn->real_escape_string($custom_date);

    // Check for existing attendance on this date (PREVENT DUPLICATES)
    $chk_stmt = $conn->prepare("SELECT id, student_id, att_date, att_time, status, method, confidence 
                                FROM attendance 
                                WHERE student_id = ? AND att_date = ?");
    $chk_stmt->bind_param("is", $student_id, $att_date);
    $chk_stmt->execute();
    $existing = $chk_stmt->get_result()->fetch_assoc();
    $chk_stmt->close();

    if ($existing) {
        // If already marked present
        if ($existing['status'] === 'present') {
            $formatted_time = $existing['att_time'] ? date('h:i:s A', strtotime($existing['att_time'])) : 'earlier today';
            json_resp([
                'success' => false,
                'duplicate' => true,
                'message' => "Already Marked Present: {$student['name']} ({$student['admission_no']}) was already marked present at {$formatted_time} on " . date('d M Y', strtotime($att_date)) . ".",
                'student' => [
                    'id' => $student['id'],
                    'name' => $student['name'],
                    'admission_no' => $student['admission_no'],
                    'class_name' => $student['class_name'],
                    'photo' => $student['photo'] ? $student['photo'] : $student['face_photo']
                ],
                'attendance' => [
                    'id' => $existing['id'],
                    'att_date' => $existing['att_date'],
                    'att_time' => $existing['att_time'],
                    'formatted_time' => $formatted_time,
                    'status' => $existing['status'],
                    'method' => $existing['method']
                ]
            ]);
        } else {
            // Was marked absent, update to present via face recognition
            $upd = $conn->prepare("UPDATE attendance 
                                   SET status = 'present', att_time = ?, method = 'face_recognition', confidence = ? 
                                   WHERE id = ?");
            $upd->bind_param("sdi", $custom_time, $confidence, $existing['id']);
            $upd->execute();
            $upd->close();

            json_resp([
                'success' => true,
                'duplicate' => false,
                'updated' => true,
                'message' => "Attendance updated: {$student['name']} marked Present via Face Recognition at " . date('h:i:s A', strtotime($custom_time)) . "!",
                'student' => [
                    'id' => $student['id'],
                    'name' => $student['name'],
                    'admission_no' => $student['admission_no'],
                    'class_name' => $student['class_name'],
                    'photo' => $student['photo'] ? $student['photo'] : $student['face_photo']
                ],
                'attendance' => [
                    'id' => $existing['id'],
                    'att_date' => $att_date,
                    'att_time' => $custom_time,
                    'formatted_time' => date('h:i:s A', strtotime($custom_time)),
                    'status' => 'present',
                    'method' => 'face_recognition',
                    'confidence' => $confidence
                ]
            ]);
        }
    }

    // Insert new attendance record
    $ins = $conn->prepare("INSERT INTO attendance (student_id, att_date, att_time, status, method, confidence) 
                          VALUES (?, ?, ?, 'present', 'face_recognition', ?)");
    $ins->bind_param("issd", $student_id, $att_date, $custom_time, $confidence);
    if ($ins->execute()) {
        $new_id = $conn->insert_id;
        $ins->close();

        json_resp([
            'success' => true,
            'duplicate' => false,
            'message' => "Attendance Marked Successfully: {$student['name']} ({$student['admission_no']}) at " . date('h:i:s A', strtotime($custom_time)) . "!",
            'student' => [
                'id' => $student['id'],
                'name' => $student['name'],
                'admission_no' => $student['admission_no'],
                'class_name' => $student['class_name'],
                'photo' => $student['photo'] ? $student['photo'] : $student['face_photo']
            ],
            'attendance' => [
                'id' => $new_id,
                'att_date' => $att_date,
                'att_time' => $custom_time,
                'formatted_time' => date('h:i:s A', strtotime($custom_time)),
                'status' => 'present',
                'method' => 'face_recognition',
                'confidence' => $confidence
            ]
        ]);
    } else {
        json_resp(['success' => false, 'message' => "Database error: " . $conn->error], 500);
    }
}

// 5. Get today's attendance log
if ($action === 'get_today_attendance') {
    $date = isset($_GET['date']) && !empty($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
    $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

    $where = "WHERE a.att_date = '$date'";
    if ($class_id > 0) {
        $where .= " AND s.class_id = $class_id";
    }

    $sql = "SELECT a.id, a.student_id, a.att_date, a.att_time, a.status, a.method, a.confidence,
                   s.admission_no, s.name, s.father_name, s.photo,
                   c.name AS class_name, sf.photo_path AS face_photo
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            JOIN classes c ON s.class_id = c.id
            LEFT JOIN student_faces sf ON s.id = sf.student_id
            $where
            ORDER BY a.att_time DESC, a.id DESC";

    $res = $conn->query($sql);
    $records = [];
    $total_present = 0;
    $total_face = 0;

    while ($r = $res->fetch_assoc()) {
        if ($r['status'] === 'present') {
            $total_present++;
            if ($r['method'] === 'face_recognition') {
                $total_face++;
            }
        }
        $records[] = [
            'id' => (int)$r['id'],
            'student_id' => (int)$r['student_id'],
            'admission_no' => $r['admission_no'],
            'name' => $r['name'],
            'father_name' => $r['father_name'],
            'class_name' => $r['class_name'],
            'photo' => $r['photo'] ? $r['photo'] : $r['face_photo'],
            'att_date' => $r['att_date'],
            'att_time' => $r['att_time'],
            'formatted_time' => $r['att_time'] ? date('h:i:s A', strtotime($r['att_time'])) : '—',
            'status' => $r['status'],
            'method' => $r['method'],
            'confidence' => $r['confidence'] ? (float)$r['confidence'] : null
        ];
    }

    json_resp([
        'success' => true,
        'date' => $date,
        'formatted_date' => date('d M Y', strtotime($date)),
        'stats' => [
            'total_present' => $total_present,
            'total_face' => $total_face
        ],
        'records' => $records
    ]);
}

// 6. Delete a student face profile
if ($action === 'delete_face' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    $student_id = isset($input['student_id']) ? (int)$input['student_id'] : 0;
    if ($student_id <= 0) {
        json_resp(['success' => false, 'message' => 'Invalid student ID.'], 400);
    }

    $chk = $conn->query("SELECT photo_path FROM student_faces WHERE student_id = $student_id");
    if ($chk && $row = $chk->fetch_assoc()) {
        if (!empty($row['photo_path']) && file_exists(__DIR__ . '/../' . $row['photo_path'])) {
            @unlink(__DIR__ . '/../' . $row['photo_path']);
        }
    }

    $conn->query("DELETE FROM student_faces WHERE student_id = $student_id");
    json_resp(['success' => true, 'message' => 'Face profile removed successfully.']);
}

// Default fallback
json_resp(['success' => false, 'message' => 'Invalid action specified.'], 400);
