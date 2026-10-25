<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$classes = $conn->query("SELECT * FROM classes ORDER BY id");
$today_date = date('Y-m-d');
$today_formatted = date('l, d F Y');

// Quick initial stats
$stats_res = $conn->query("SELECT 
    COUNT(*) as total_students,
    (SELECT COUNT(DISTINCT student_id) FROM student_faces) as total_faces,
    (SELECT COUNT(DISTINCT student_id) FROM attendance WHERE att_date = '$today_date' AND status = 'present') as present_today,
    (SELECT COUNT(DISTINCT student_id) FROM attendance WHERE att_date = '$today_date' AND status = 'present' AND method = 'face_recognition') as face_today
FROM students");
$stats = $stats_res->fetch_assoc();
?>

<!-- Custom CSS for Kiosk and Scanner -->
<style>
.kiosk-wrapper {
    transition: all 0.3s;
}
.kiosk-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    background: #0f172a;
    padding: 20px;
    overflow-y: auto;
}
.scanner-card {
    background: #0f172a;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0,0,0,0.4);
    border: 1px solid rgba(255,255,255,0.08);
}
.scanner-viewport {
    position: relative;
    width: 100%;
    min-height: 480px;
    background: #020617;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.scanner-viewport video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scaleX(-1); /* Natural mirror view */
    display: block;
}
.scanner-viewport canvas.overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    transform: scaleX(-1);
}

/* Sci-Fi Scanning HUD overlay */
.scanner-scan-line {
    position: absolute;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #38bdf8, #818cf8, #38bdf8, transparent);
    box-shadow: 0 0 15px #38bdf8, 0 0 30px #818cf8;
    top: 0;
    opacity: 0;
    pointer-events: none;
    z-index: 5;
}
.scanner-scan-line.scanning {
    opacity: 0.85;
    animation: scanAnimation 2.4s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}
@keyframes scanAnimation {
    0% { top: 5%; }
    50% { top: 92%; }
    100% { top: 5%; }
}

/* Corner Reticles */
.scanner-reticle {
    position: absolute;
    width: 30px;
    height: 30px;
    pointer-events: none;
    z-index: 6;
    border: 3px solid #38bdf8;
}
.scanner-reticle.tl { top: 20px; left: 20px; border-right: none; border-bottom: none; }
.scanner-reticle.tr { top: 20px; right: 20px; border-left: none; border-bottom: none; }
.scanner-reticle.bl { bottom: 20px; left: 20px; border-right: none; border-top: none; }
.scanner-reticle.br { bottom: 20px; right: 20px; border-left: none; border-top: none; }

/* Status HUD Badge */
.scanner-status-hud {
    position: absolute;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(56, 189, 248, 0.35);
    padding: 8px 20px;
    border-radius: 30px;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

/* Pop-up result cards on camera */
.recognition-popup {
    position: absolute;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    width: 90%;
    max-width: 480px;
    border-radius: 18px;
    padding: 16px 20px;
    z-index: 20;
    box-shadow: 0 12px 35px rgba(0,0,0,0.5);
    opacity: 0;
    pointer-events: none;
    transition: all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.recognition-popup.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
    pointer-events: auto;
}
.recognition-popup.success {
    background: linear-gradient(135deg, #065f46 0%, #047857 100%);
    border: 2px solid #34d399;
    color: #fff;
}
.recognition-popup.duplicate {
    background: linear-gradient(135deg, #78350f 0%, #b45309 100%);
    border: 2px solid #fbbf24;
    color: #fff;
}
.stat-pill {
    background: #f8fafc;
    border-radius: 16px;
    padding: 15px 20px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s;
}
.stat-pill:hover {
    transform: translateY(-2px);
}
.stat-pill-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}
.live-pulse {
    width: 10px;
    height: 10px;
    background: #22c55e;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    animation: pulseGlow 1.8s infinite;
}
@keyframes pulseGlow {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}
</style>

<div id="kioskContainer" class="kiosk-wrapper">
    <!-- Top Header Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="live-pulse"></span>
                <span class="badge bg-dark text-white px-2 py-1 small">AI KIOSK ENGINE ACTIVE</span>
                <span class="text-muted small" id="liveClockText">--:--:--</span>
            </div>
            <h2 class="mb-0 fw-bold mt-1">
                <i class="fa-solid fa-camera-retro text-primary me-2"></i>Face Recognition Attendance
            </h2>
            <div class="text-muted"><?= $today_formatted ?></div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button class="btn btn-outline-dark" id="btnToggleSound">
                <i class="fa-solid fa-volume-high me-1 text-primary"></i> <span id="soundLabel">Voice: ON</span>
            </button>
            <button class="btn btn-outline-dark" id="btnToggleFullscreen">
                <i class="fa-solid fa-expand me-1"></i> Fullscreen Kiosk
            </button>
            <a href="face_register.php" class="btn btn-primary">
                <i class="fa-solid fa-user-plus me-1"></i> Register Student Face
            </a>
            <a href="manage.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-table me-1"></i> Manual List
            </a>
        </div>
    </div>

    <!-- Live Statistics KPI Bar -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-pill shadow-sm">
                <div class="stat-pill-icon bg-primary-subtle text-primary">
                    <i class="fa-solid fa-id-card-clip"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Enrolled Faces</div>
                    <div class="fs-4 fw-bold text-dark" id="statFaces"><?= $stats['total_faces'] ?> / <?= $stats['total_students'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-pill shadow-sm">
                <div class="stat-pill-icon bg-success-subtle text-success">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Present Today</div>
                    <div class="fs-4 fw-bold text-success" id="statPresent"><?= $stats['present_today'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-pill shadow-sm">
                <div class="stat-pill-icon bg-info-subtle text-info">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Marked by Face AI</div>
                    <div class="fs-4 fw-bold text-info" id="statFaceToday"><?= $stats['face_today'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-pill shadow-sm">
                <div class="stat-pill-icon bg-warning-subtle text-warning">
                    <i class="fa-solid fa-shield-virus"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Duplicates Prevented</div>
                    <div class="fs-4 fw-bold text-warning" id="statDuplicates">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace: Camera Scanner on Left, Real-Time Activity Feed on Right -->
    <div class="row g-4 mb-4">
        <!-- Camera Scanner Card -->
        <div class="col-lg-7">
            <div class="scanner-card">
                <!-- Scanner Toolbar -->
                <div class="p-3 bg-dark border-bottom border-secondary-subtle d-flex justify-content-between align-items-center flex-wrap gap-2 text-white">
                    <div class="d-flex align-items-center gap-2">
                        <select id="cameraSelect" class="form-select form-select-sm bg-dark text-white border-secondary" style="max-width: 200px;">
                            <option value="">Select Camera...</option>
                        </select>
                        <select id="classScanFilter" class="form-select form-select-sm bg-dark text-white border-secondary" style="max-width: 170px;">
                            <option value="0">All Classes</option>
                            <?php 
                            $classes->data_seek(0);
                            while ($c = $classes->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-2 small">
                        <span class="text-secondary">Sensitivity:</span>
                        <input type="range" id="sensitivitySlider" class="form-range" min="0.40" max="0.65" step="0.02" value="0.52" style="width: 90px;" title="Match Threshold (Lower = Stricter)">
                        <span id="sensitivityVal" class="text-info fw-bold">52%</span>
                    </div>
                </div>

                <!-- Live Camera Viewport -->
                <div class="scanner-viewport" id="viewportContainer">
                    <video id="scannerVideo" autoplay muted playsinline></video>
                    <canvas id="scannerCanvas" class="overlay"></canvas>
                    
                    <!-- Sci-fi Laser Scanner Line -->
                    <div class="scanner-scan-line scanning" id="scanLine"></div>

                    <!-- Reticle Corners -->
                    <div class="scanner-reticle tl"></div>
                    <div class="scanner-reticle tr"></div>
                    <div class="scanner-reticle bl"></div>
                    <div class="scanner-reticle br"></div>

                    <!-- HUD Status Badge -->
                    <div class="scanner-status-hud" id="scannerHud">
                        <span class="spinner-grow spinner-grow-sm text-info"></span>
                        <span id="hudMsg">INITIALIZING AI SCANNER...</span>
                    </div>

                    <!-- Success / Duplicate Pop-up Notification Overlay -->
                    <div class="recognition-popup shadow" id="recognitionPopup">
                        <div class="d-flex align-items-center">
                            <img id="popupStudentPhoto" src="" class="rounded-circle border border-white me-3 shadow" width="60" height="60" style="object-fit: cover;">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="fw-bold mb-0 text-white" id="popupStudentName">Student Name</h5>
                                    <span class="badge bg-white text-dark small" id="popupBadge">Present</span>
                                </div>
                                <div class="small text-white-50 mt-1" id="popupStudentDetails">
                                    Admission No: 1001 | Class: Class 3rd
                                </div>
                                <div class="fw-bold small mt-1 text-white" id="popupStatusDesc">
                                    <i class="fa-solid fa-clock me-1"></i> Marked at 09:30:15 AM
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer guidance -->
                <div class="p-3 bg-dark text-secondary small d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-circle-info text-info me-1"></i>
                        <span>Students should face the camera. The system identifies registered faces automatically.</span>
                    </div>
                    <button class="btn btn-sm btn-outline-light" id="btnRestartCam" title="Restart Camera Stream">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Real-Time Activity Feed & Records -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-bolt me-2 text-warning"></i>Today's Live Attendance Feed</h5>
                        <div class="text-muted small">Real-time attendance logs with timestamps</div>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="fetchTodayAttendance()">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="liveAttendanceTable">
                            <thead class="table-light small sticky-top">
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Time</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTbody">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
                                        <div>Loading today's attendance...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-0 py-3 text-center">
                    <a href="manage.php" class="btn btn-outline-primary btn-sm me-2">
                        <i class="fa-solid fa-calendar-day me-1"></i> Open Full Attendance Register
                    </a>
                    <a href="report.php" class="btn btn-outline-danger btn-sm">
                        <i class="fa-solid fa-user-xmark me-1"></i> Absent Students Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Local face-api Library -->
<script src="/school/js/face-api.min.js"></script>

<script>
// Scanner Global State
let faceMatcher = null;
let registeredFacesData = [];
let isModelReady = false;
let isScannerActive = false;
let activeStream = null;
let scanLoopId = null;
let soundEnabled = true;

// Cooldown debounce map: studentId => timestamp
const cooldownMap = new Map();
const COOLDOWN_MS = 6000; // 6 seconds before recognizing the same person again

// Audio chime synthesizers (Web Audio API for zero-dependency high-fidelity chimes!)
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

function playSuccessChime() {
    if (!soundEnabled) return;
    try {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
        osc.frequency.setValueAtTime(880.00, audioCtx.currentTime + 0.1); // A5
        gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.35);
    } catch(e) {}
}

function playWarningChime() {
    if (!soundEnabled) return;
    try {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(349.23, audioCtx.currentTime); // F4
        osc.frequency.setValueAtTime(261.63, audioCtx.currentTime + 0.12); // C4
        gain.gain.setValueAtTime(0.18, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.35);
    } catch(e) {}
}

function speakVoice(text) {
    if (!soundEnabled || !('speechSynthesis' in window)) return;
    try {
        window.speechSynthesis.cancel(); // Stop any pending speech
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 1.05;
        utterance.pitch = 1.0;
        window.speechSynthesis.speak(utterance);
    } catch(e) {}
}

// Clock Display
function updateLiveClock() {
    const now = new Date();
    document.getElementById('liveClockText').innerText = now.toLocaleTimeString('en-US', { hour12: true });
}
setInterval(updateLiveClock, 1000);
updateLiveClock();

// Sensitivity Slider
const slider = document.getElementById('sensitivitySlider');
const sensitivityVal = document.getElementById('sensitivityVal');
slider.addEventListener('input', () => {
    sensitivityVal.innerText = `${Math.round(slider.value * 100)}%`;
    if (registeredFacesData.length > 0) {
        buildFaceMatcher(registeredFacesData);
    }
});

// Sound Toggle
document.getElementById('btnToggleSound').addEventListener('click', () => {
    soundEnabled = !soundEnabled;
    document.getElementById('soundLabel').innerText = soundEnabled ? 'Voice: ON' : 'Voice: OFF';
    document.getElementById('btnToggleSound').classList.toggle('btn-outline-dark', !soundEnabled);
    document.getElementById('btnToggleSound').classList.toggle('btn-dark', soundEnabled);
});

// Fullscreen Toggle
document.getElementById('btnToggleFullscreen').addEventListener('click', () => {
    const kiosk = document.getElementById('kioskContainer');
    kiosk.classList.toggle('kiosk-fullscreen');
    const isFull = kiosk.classList.contains('kiosk-fullscreen');
    document.getElementById('btnToggleFullscreen').innerHTML = isFull 
        ? `<i class="fa-solid fa-compress me-1"></i> Exit Fullscreen` 
        : `<i class="fa-solid fa-expand me-1"></i> Fullscreen Kiosk`;
});

// Initialize on Load
document.addEventListener('DOMContentLoaded', async () => {
    await initCameraList();
    await loadModels();
    await loadFaceMatcher();
    await fetchTodayAttendance();
    await startCamera();
});

// 1. Initialize Available Video Devices
async function initCameraList() {
    const sel = document.getElementById('cameraSelect');
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(d => d.kind === 'videoinput');
        sel.innerHTML = '';
        videoDevices.forEach((dev, idx) => {
            const opt = document.createElement('option');
            opt.value = dev.deviceId;
            opt.text = dev.label || `Camera ${idx + 1}`;
            sel.appendChild(opt);
        });
        sel.addEventListener('change', () => startCamera(sel.value));
    } catch (e) {
        console.warn("Could not list video devices", e);
    }
}

// 2. Load face-api AI models
async function loadModels() {
    const MODEL_URL = '/school/models';
    setHud('LOADING AI MODELS...', 'warning');
    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        isModelReady = true;
        setHud('AI MODELS READY', 'success');
    } catch (err) {
        console.error("Local model error, falling back to CDN:", err);
        const CDN_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(CDN_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(CDN_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(CDN_URL)
        ]);
        isModelReady = true;
        setHud('AI MODELS READY (CDN)', 'success');
    }
}

// 3. Load Registered Face Descriptors from Database
async function loadFaceMatcher() {
    const classId = document.getElementById('classScanFilter').value;
    try {
        const res = await fetch(`api_face.php?action=get_face_descriptors&class_id=${classId}`);
        const json = await res.json();
        if (json.success) {
            registeredFacesData = json.data;
            document.getElementById('statFaces').innerText = `${json.count} Enrolled`;
            buildFaceMatcher(registeredFacesData);
            if (json.count === 0) {
                setHud('NO FACES REGISTERED YET', 'warning');
            } else {
                setHud(`AI READY (${json.count} FACES LOADED)`, 'success');
            }
        }
    } catch (e) {
        console.error("Failed to load descriptors:", e);
        setHud('DATABASE SYNC ERROR', 'danger');
    }
}

document.getElementById('classScanFilter').addEventListener('change', () => loadFaceMatcher());
document.getElementById('btnRestartCam').addEventListener('click', () => startCamera());

// Build FaceMatcher with configured sensitivity
function buildFaceMatcher(data) {
    if (!data || data.length === 0) {
        faceMatcher = null;
        return;
    }

    const labeledDescriptors = [];
    data.forEach(item => {
        const rawDesc = item.descriptors;
        const descriptorsArray = [];

        if (Array.isArray(rawDesc)) {
            if (Array.isArray(rawDesc[0])) {
                // Multiple samples
                rawDesc.forEach(d => descriptorsArray.push(new Float32Array(d)));
            } else {
                // Single sample
                descriptorsArray.push(new Float32Array(rawDesc));
            }
        }

        if (descriptorsArray.length > 0) {
            // Label is student_id
            labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(String(item.student_id), descriptorsArray));
        }
    });

    const threshold = parseFloat(document.getElementById('sensitivitySlider').value) || 0.52;
    faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, threshold);
}

// 4. Start Camera Stream
async function startCamera(deviceId = null) {
    const video = document.getElementById('scannerVideo');

    if (activeStream) {
        activeStream.getTracks().forEach(t => t.stop());
        activeStream = null;
    }
    if (scanLoopId) {
        cancelAnimationFrame(scanLoopId);
        scanLoopId = null;
    }

    const constraints = {
        video: {
            width: { ideal: 960 },
            height: { ideal: 720 },
            facingMode: 'user'
        },
        audio: false
    };
    if (deviceId) {
        constraints.video.deviceId = { exact: deviceId };
    }

    try {
        activeStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = activeStream;
        video.onloadedmetadata = () => {
            video.play();
            isScannerActive = true;
            document.getElementById('scanLine').classList.add('scanning');
            setHud('SCANNING FOR FACES...', 'info');
            startDetectionLoop();
        };
    } catch (err) {
        console.error("Camera access error:", err);
        setHud('CAMERA PERMISSION DENIED', 'danger');
    }
}

// 5. High-Performance Real-Time Detection & Recognition Loop
let isProcessingFrame = false;
let lastProcessTime = 0;

function startDetectionLoop() {
    const video = document.getElementById('scannerVideo');
    const canvas = document.getElementById('scannerCanvas');

    async function step(timestamp) {
        if (!isScannerActive || !isModelReady || video.paused || video.ended) {
            scanLoopId = requestAnimationFrame(step);
            return;
        }

        // Throttle processing to every ~180ms for maximum fluid framerate without pegging the CPU
        if (timestamp - lastProcessTime > 180 && !isProcessingFrame) {
            isProcessingFrame = true;
            lastProcessTime = timestamp;

            const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
            faceapi.matchDimensions(canvas, displaySize);

            try {
                // Detect all faces in viewport with landmarks and descriptors
                const detections = await faceapi.detectAllFaces(
                    video, 
                    new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })
                )
                .withFaceLandmarks()
                .withFaceDescriptors();

                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                if (detections.length > 0) {
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);

                    for (const d of resizedDetections) {
                        const box = d.detection.box;

                        if (faceMatcher) {
                            const bestMatch = faceMatcher.findBestMatch(d.descriptor);
                            const matchedStudentId = parseInt(bestMatch.label);

                            if (!isNaN(matchedStudentId) && bestMatch.label !== 'unknown') {
                                const studentInfo = registeredFacesData.find(s => s.student_id === matchedStudentId);
                                const studentName = studentInfo ? studentInfo.name : 'Student';
                                const confidence = Math.max(0, Math.min(100, Math.round((1 - (bestMatch.distance / 0.7)) * 100)));

                                // Draw glowing green box
                                ctx.strokeStyle = '#22c55e';
                                ctx.lineWidth = 3;
                                ctx.strokeRect(box.x, box.y, box.width, box.height);

                                // Name Tag Badge above box
                                const tagText = `${studentName} (${confidence}%)`;
                                ctx.font = 'bold 15px sans-serif';
                                const textWidth = ctx.measureText(tagText).width;
                                ctx.fillStyle = 'rgba(34, 197, 94, 0.9)';
                                ctx.fillRect(box.x, box.y - 28, textWidth + 16, 26);
                                ctx.fillStyle = '#ffffff';
                                ctx.fillText(tagText, box.x + 8, box.y - 10);

                                setHud(`RECOGNIZED: ${studentName.toUpperCase()}`, 'success');

                                // Check Cooldown before triggering attendance call
                                const now = Date.now();
                                const lastSeen = cooldownMap.get(matchedStudentId) || 0;
                                if (now - lastSeen > COOLDOWN_MS) {
                                    cooldownMap.set(matchedStudentId, now);
                                    handleAttendanceMark(matchedStudentId, confidence);
                                }
                            } else {
                                // Face detected but not recognized / un-enrolled
                                ctx.strokeStyle = '#f59e0b';
                                ctx.lineWidth = 2;
                                ctx.strokeRect(box.x, box.y, box.width, box.height);

                                ctx.font = 'bold 13px sans-serif';
                                ctx.fillStyle = 'rgba(245, 158, 11, 0.9)';
                                ctx.fillRect(box.x, box.y - 24, 130, 22);
                                ctx.fillStyle = '#ffffff';
                                ctx.fillText('Unregistered Face', box.x + 6, box.y - 8);

                                setHud('FACE DETECTED (UNREGISTERED)', 'warning');
                            }
                        } else {
                            // No face profiles in matcher yet
                            ctx.strokeStyle = '#38bdf8';
                            ctx.lineWidth = 2;
                            ctx.strokeRect(box.x, box.y, box.width, box.height);
                            setHud('FACE DETECTED - ENROLL FACES FIRST', 'info');
                        }
                    }
                } else {
                    setHud('SCANNING FOR FACES...', 'info');
                }
            } catch (err) {
                console.error("Frame processing error:", err);
            } finally {
                isProcessingFrame = false;
            }
        }

        scanLoopId = requestAnimationFrame(step);
    }

    scanLoopId = requestAnimationFrame(step);
}

// 6. Call API to Mark Attendance with Duplicate Prevention
async function handleAttendanceMark(studentId, confidence) {
    try {
        const res = await fetch('api_face.php?action=mark_attendance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_id: studentId,
                confidence: confidence
            })
        });
        const data = await res.json();

        if (data.duplicate) {
            // DUPLICATE PREVENTED!
            triggerDuplicateFeedback(data);
        } else if (data.success) {
            // NEW ATTENDANCE MARKED SUCCESSFULLY!
            triggerSuccessFeedback(data);
        }
    } catch (e) {
        console.error("Failed to mark attendance via API:", e);
    }
}

// Feedback: Duplicate Prevented
function triggerDuplicateFeedback(data) {
    playWarningChime();
    speakVoice(`Already marked: ${data.student.name}`);

    // Update Duplicate Counter
    const dupCount = document.getElementById('statDuplicates');
    dupCount.innerText = parseInt(dupCount.innerText || 0) + 1;

    showPopup({
        type: 'duplicate',
        name: data.student.name,
        photo: data.student.photo,
        details: `Adm: ${data.student.admission_no} | Class: ${data.student.class_name}`,
        badge: 'Already Marked',
        desc: `<i class="fa-solid fa-triangle-exclamation me-1"></i> Already marked present at ${data.attendance.formatted_time} today.`
    });
}

// Feedback: New Attendance Marked
function triggerSuccessFeedback(data) {
    playSuccessChime();
    speakVoice(`Welcome, ${data.student.name}! Attendance marked.`);

    // Update Counters
    const presCount = document.getElementById('statPresent');
    presCount.innerText = parseInt(presCount.innerText || 0) + 1;
    const faceCount = document.getElementById('statFaceToday');
    faceCount.innerText = parseInt(faceCount.innerText || 0) + 1;

    showPopup({
        type: 'success',
        name: data.student.name,
        photo: data.student.photo,
        details: `Adm: ${data.student.admission_no} | Class: ${data.student.class_name}`,
        badge: 'Present (Face ID)',
        desc: `<i class="fa-solid fa-circle-check me-1"></i> Marked Present at ${data.attendance.formatted_time} (${data.attendance.confidence || 98}% match)`
    });

    // Refresh Live Attendance Table
    fetchTodayAttendance();
}

// Pop-up Manager
let popupTimeout = null;
function showPopup({ type, name, photo, details, badge, desc }) {
    const popup = document.getElementById('recognitionPopup');
    if (popupTimeout) clearTimeout(popupTimeout);

    popup.className = `recognition-popup shadow ${type} show`;
    document.getElementById('popupStudentName').innerText = name;
    document.getElementById('popupStudentDetails').innerText = details;
    document.getElementById('popupBadge').innerText = badge;
    document.getElementById('popupStatusDesc').innerHTML = desc;

    const photoSrc = photo 
        ? (photo.startsWith('uploads/') ? `/school/${photo}` : `/school/uploads/students/${photo}`)
        : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=0284c7&color=fff`;
    document.getElementById('popupStudentPhoto').src = photoSrc;

    popupTimeout = setTimeout(() => {
        popup.classList.remove('show');
    }, 4500);
}

// 7. Fetch Today's Attendance Table Log
async function fetchTodayAttendance() {
    const tbody = document.getElementById('attendanceTbody');
    const classId = document.getElementById('classScanFilter').value;

    try {
        const res = await fetch(`api_face.php?action=get_today_attendance&class_id=${classId}`);
        const data = await res.json();

        if (data.success) {
            document.getElementById('statPresent').innerText = data.stats.total_present;
            document.getElementById('statFaceToday').innerText = data.stats.total_face;

            if (data.records.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted"><i class="fa-solid fa-clock-rotate-left mb-2 fs-5"></i><br>No attendance marked yet today.</td></tr>`;
                return;
            }

            let html = '';
            data.records.forEach(r => {
                const photoSrc = r.photo 
                    ? (r.photo.startsWith('uploads/') ? `/school/${r.photo}` : `/school/uploads/students/${r.photo}`)
                    : `https://ui-avatars.com/api/?name=${encodeURIComponent(r.name)}&background=0284c7&color=fff`;

                const isFace = r.method === 'face_recognition';
                const methodBadge = isFace 
                    ? `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-camera me-1"></i>Face AI</span>`
                    : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Manual</span>`;

                html += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="${photoSrc}" class="rounded-circle me-2 border shadow-sm" width="34" height="34" style="object-fit: cover;">
                                <div>
                                    <div class="fw-bold text-dark small">${escapeHtml(r.name)}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Adm: ${escapeHtml(r.admission_no)}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border small">${escapeHtml(r.class_name)}</span></td>
                        <td><span class="fw-semibold text-dark small">${escapeHtml(r.formatted_time)}</span></td>
                        <td>${methodBadge}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    } catch (e) {
        console.error(e);
    }
}

// HUD Status Text Setter
function setHud(msg, type = 'info') {
    const hud = document.getElementById('scannerHud');
    const msgEl = document.getElementById('hudMsg');
    msgEl.innerText = msg;

    let dotColor = '#38bdf8';
    if (type === 'success') dotColor = '#22c55e';
    if (type === 'warning') dotColor = '#f59e0b';
    if (type === 'danger') dotColor = '#ef4444';

    hud.style.borderColor = dotColor;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}
</script>

<?php require_once '../includes/footer.php'; ?>
