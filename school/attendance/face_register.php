<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$classes = $conn->query("SELECT * FROM classes ORDER BY id");
$initial_student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
?>

<!-- Custom Styling for Face Registration & HUD -->
<style>
.face-cam-container {
    position: relative;
    width: 100%;
    max-width: 540px;
    margin: 0 auto;
    border-radius: 16px;
    overflow: hidden;
    background: #0f172a;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}
.face-cam-container video {
    width: 100%;
    height: auto;
    display: block;
    transform: scaleX(-1); /* Mirror camera for natural view */
}
.face-cam-container canvas.overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    transform: scaleX(-1);
}
.camera-hud-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 10;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 5px 12px;
    border-radius: 20px;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.3);
}
.camera-hud-guide {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 220px;
    height: 270px;
    border: 2px dashed rgba(56, 189, 248, 0.4);
    border-radius: 50% 50% 45% 45%;
    pointer-events: none;
    transition: all 0.3s;
}
.camera-hud-guide.active {
    border-color: #22c55e;
    box-shadow: 0 0 25px rgba(34, 197, 94, 0.4);
}
.student-list-item {
    cursor: pointer;
    transition: all 0.2s;
    border-left: 4px solid transparent;
}
.student-list-item:hover {
    background: #f1f5f9;
}
.student-list-item.active {
    background: #e0f2fe;
    border-left-color: #0284c7;
}
.badge-registered {
    background-color: #dcfce7;
    color: #15803d;
    font-weight: 600;
}
.badge-unregistered {
    background-color: #fee2e2;
    color: #b91c1c;
    font-weight: 600;
}
.sample-pill {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: bold;
    background: #e2e8f0;
    color: #64748b;
    transition: all 0.3s;
}
.sample-pill.done {
    background: #22c55e;
    color: #fff;
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="fa-solid fa-id-card-clip text-primary me-2"></i>Student Face Registration & Verification</h2>
        <p class="text-muted mb-0">Enroll student facial biometric descriptors and verify recognition accuracy in real time.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="face_attendance.php" class="btn btn-success">
            <i class="fa-solid fa-camera me-1"></i> Open Attendance Scanner
        </a>
        <a href="manage.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-table me-1"></i> Attendance Records
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Student Selector -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-users me-2 text-primary"></i>Select Student</h5>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-search"></i></span>
                            <input type="text" id="studentSearch" class="form-control" placeholder="Search by name or admission no...">
                        </div>
                    </div>
                    <div class="col-12">
                        <select id="classFilter" class="form-select form-select-sm">
                            <option value="0">All Classes</option>
                            <?php while ($c = $classes->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="studentListContainer" style="max-height: 580px; overflow-y: auto;">
                    <div class="p-4 text-center text-muted">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <div>Loading students...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Registration & Verification Studio -->
    <div class="col-lg-8">
        <!-- Selected Student Summary Card -->
        <div class="card shadow-sm border-0 rounded-4 mb-3" id="studentDetailCard" style="display: none;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <img id="selectedStudentPhoto" src="https://ui-avatars.com/api/?name=Student&background=0284c7&color=fff" 
                             class="rounded-circle me-3 border shadow-sm" width="56" height="56" style="object-fit: cover;">
                        <div>
                            <h5 class="fw-bold mb-0" id="selectedStudentName">Student Name</h5>
                            <div class="text-muted small">
                                Admission No: <span class="fw-semibold text-dark" id="selectedStudentAdm">--</span> | 
                                Class: <span class="fw-semibold text-dark" id="selectedStudentClass">--</span> | 
                                Father: <span class="fw-semibold text-dark" id="selectedStudentFather">--</span>
                            </div>
                        </div>
                    </div>
                    <div id="selectedStudentStatusBadge">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Studio Workspace Card -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white border-bottom pt-3 pb-0">
                <ul class="nav nav-tabs border-0" id="studioTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold px-4" id="register-tab" data-bs-toggle="tab" data-bs-target="#tab-register" type="button" role="tab">
                            <i class="fa-solid fa-camera me-2 text-primary"></i>1. Capture & Register Face
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold px-4" id="verify-tab" data-bs-toggle="tab" data-bs-target="#tab-verify" type="button" role="tab">
                            <i class="fa-solid fa-shield-halved me-2 text-success"></i>2. Live Face Verification
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="studioTabContent">
                    <!-- Tab 1: Registration -->
                    <div class="tab-pane fade show active" id="tab-register" role="tabpanel">
                        <div id="noStudentAlert" class="alert alert-info border-0 rounded-3 mb-4">
                            <i class="fa-solid fa-circle-info me-2"></i>Please select a student from the left panel to begin face registration.
                        </div>

                        <div id="registrationControls" style="display: none;">
                            <!-- Camera Viewport -->
                            <div class="face-cam-container mb-3">
                                <video id="videoRegister" autoplay muted playsinline></video>
                                <canvas id="canvasRegister" class="overlay"></canvas>
                                <div class="camera-hud-badge" id="hudStatus">
                                    <i class="fa-solid fa-circle text-danger me-1"></i> CAMERA READY
                                </div>
                                <div class="camera-hud-guide" id="hudGuide"></div>
                            </div>

                            <!-- Quality & Detection Feedback -->
                            <div class="text-center mb-3">
                                <div id="feedbackText" class="fw-bold text-muted small mb-2">
                                    Position student's face within the guide frame.
                                </div>
                                <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                                    <span class="small text-muted me-1">Quality Samples:</span>
                                    <span class="sample-pill" id="pill-1">1</span>
                                    <span class="sample-pill" id="pill-2">2</span>
                                    <span class="sample-pill" id="pill-3">3</span>
                                    <span class="sample-pill" id="pill-4">4</span>
                                    <span class="sample-pill" id="pill-5">5</span>
                                </div>
                                <div class="progress mx-auto" style="height: 6px; max-width: 300px;">
                                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="sampleProgressBar" style="width: 0%;"></div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <button type="button" class="btn btn-primary px-4 py-2 fw-bold" id="btnAutoCapture">
                                    <i class="fa-solid fa-bolt me-1"></i> Capture 5-Angle Samples
                                </button>
                                <button type="button" class="btn btn-outline-primary px-3 py-2 fw-bold" id="btnSingleCapture">
                                    <i class="fa-solid fa-camera me-1"></i> Capture Single
                                </button>
                                <label class="btn btn-outline-secondary px-3 py-2 fw-bold mb-0">
                                    <i class="fa-solid fa-upload me-1"></i> Upload Photo
                                    <input type="file" id="uploadPhotoInput" accept="image/*" style="display: none;">
                                </label>
                                <button type="button" class="btn btn-danger px-3 py-2 fw-bold" id="btnDeleteFace" style="display: none;">
                                    <i class="fa-solid fa-trash-can me-1"></i> Remove Face
                                </button>
                            </div>

                            <!-- Status message container -->
                            <div id="registerAlert" class="mt-3" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Tab 2: Live Verification -->
                    <div class="tab-pane fade" id="tab-verify" role="tabpanel">
                        <div id="verifyNoStudentAlert" class="alert alert-info border-0 rounded-3 mb-4">
                            <i class="fa-solid fa-circle-info me-2"></i>Select a registered student to test and verify recognition.
                        </div>

                        <div id="verifyControls" style="display: none;">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <div class="face-cam-container mb-3">
                                        <video id="videoVerify" autoplay muted playsinline></video>
                                        <canvas id="canvasVerify" class="overlay"></canvas>
                                        <div class="camera-hud-badge" id="verifyHudStatus">
                                            <i class="fa-solid fa-shield me-1 text-primary"></i> VERIFYING
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="card border rounded-4 bg-light p-3 text-center">
                                        <h6 class="fw-bold text-muted mb-3">Verification Result</h6>
                                        <div class="mb-3">
                                            <div id="verifyIcon" class="display-4 text-muted mb-2">
                                                <i class="fa-solid fa-user-astronaut"></i>
                                            </div>
                                            <h4 class="fw-bold mb-1" id="verifyStatusTitle">Ready to Test</h4>
                                            <p class="text-muted small mb-2" id="verifyStatusDesc">Look into the camera to verify biometric match.</p>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small fw-semibold text-muted mb-1">Match Confidence</div>
                                            <div class="progress" style="height: 14px;">
                                                <div id="verifyConfidenceBar" class="progress-bar bg-success" style="width: 0%;">0%</div>
                                            </div>
                                        </div>
                                        <div class="alert alert-secondary py-2 small mb-0" id="verifyDetailsBox">
                                            Euclidean Distance: <span class="fw-bold" id="verifyDistanceVal">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Local face-api library -->
<script src="/school/js/face-api.min.js"></script>

<script>
// State Management
let studentsList = [];
let selectedStudent = null;
let currentDescriptors = []; // Array of Float32Array descriptors
let collectedSamples = [];
let isCapturingMulti = false;
let modelsLoaded = false;
let registerStream = null;
let verifyStream = null;
let verifyInterval = null;
let isRegisteringActive = true;

const MODEL_URL = '/school/models';

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    await loadModels();
    await fetchStudents(<?= $initial_student_id ?>);
    setupEventListeners();
});

// Load AI Models
async function loadModels() {
    try {
        updateHud('Loading AI Models...', 'warning');
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL)
        ]);
        modelsLoaded = true;
        updateHud('AI Models Loaded', 'success');
    } catch (err) {
        console.error("Error loading face-api models:", err);
        // Fallback to CDN if local fail
        try {
            const CDN_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(CDN_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(CDN_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(CDN_URL)
            ]);
            modelsLoaded = true;
            updateHud('AI Models Loaded (Cloud)', 'success');
        } catch (cdnErr) {
            updateHud('Model Load Failed', 'danger');
            alert("Could not load face recognition models. Please check network/local files.");
        }
    }
}

// Fetch Students from API
async function fetchStudents(autoSelectId = 0) {
    const classId = document.getElementById('classFilter').value;
    const search = document.getElementById('studentSearch').value.trim();
    const container = document.getElementById('studentListContainer');

    try {
        const res = await fetch(`api_face.php?action=get_students&class_id=${classId}&search=${encodeURIComponent(search)}`);
        const json = await res.json();
        if (json.success) {
            studentsList = json.students;
            renderStudentList(studentsList, autoSelectId);
        }
    } catch (e) {
        container.innerHTML = `<div class="p-4 text-center text-danger"><i class="fa-solid fa-triangle-exclamation mb-2"></i><br>Failed to load students.</div>`;
    }
}

// Render Student List in Sidebar
function renderStudentList(students, autoSelectId = 0) {
    const container = document.getElementById('studentListContainer');
    if (!students || students.length === 0) {
        container.innerHTML = `<div class="p-4 text-center text-muted">No students found matching filters.</div>`;
        return;
    }

    container.innerHTML = '';
    let autoSelectEl = null;

    students.forEach(st => {
        const item = document.createElement('a');
        item.className = `list-group-item list-group-item-action student-list-item d-flex align-items-center justify-content-between p-3 ${selectedStudent && selectedStudent.id === st.id ? 'active' : ''}`;
        item.dataset.id = st.id;

        const photoSrc = st.photo ? `/school/uploads/students/${st.photo}` : 
                         (st.face_photo ? `/school/${st.face_photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(st.name)}&background=0284c7&color=fff`);

        const badgeHtml = st.is_registered 
            ? `<span class="badge badge-registered"><i class="fa-solid fa-circle-check me-1"></i>Enrolled</span>`
            : `<span class="badge badge-unregistered"><i class="fa-solid fa-circle-xmark me-1"></i>No Face</span>`;

        item.innerHTML = `
            <div class="d-flex align-items-center">
                <img src="${photoSrc}" class="rounded-circle me-3 border" width="42" height="42" style="object-fit: cover;">
                <div>
                    <div class="fw-bold text-dark text-truncate" style="max-width: 150px;">${escapeHtml(st.name)}</div>
                    <div class="text-muted small">Adm: ${escapeHtml(st.admission_no)} | ${escapeHtml(st.class_name)}</div>
                </div>
            </div>
            <div>${badgeHtml}</div>
        `;

        item.addEventListener('click', () => selectStudent(st));
        container.appendChild(item);

        if (autoSelectId && st.id === autoSelectId) {
            autoSelectEl = item;
        }
    });

    if (autoSelectEl) {
        autoSelectEl.click();
        autoSelectEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Select a student
async function selectStudent(student) {
    selectedStudent = student;

    // Highlight item
    document.querySelectorAll('.student-list-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.id) === student.id);
    });

    // Populate Top Details Card
    document.getElementById('studentDetailCard').style.display = 'block';
    document.getElementById('selectedStudentName').innerText = student.name;
    document.getElementById('selectedStudentAdm').innerText = student.admission_no;
    document.getElementById('selectedStudentClass').innerText = student.class_name;
    document.getElementById('selectedStudentFather').innerText = student.father_name;

    const photoSrc = student.photo ? `/school/uploads/students/${student.photo}` : 
                     (student.face_photo ? `/school/${student.face_photo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}&background=0284c7&color=fff`);
    document.getElementById('selectedStudentPhoto').src = photoSrc;

    const badgeContainer = document.getElementById('selectedStudentStatusBadge');
    if (student.is_registered) {
        badgeContainer.innerHTML = `<span class="badge bg-success p-2 fs-6"><i class="fa-solid fa-circle-check me-1"></i>Face Enrolled (${student.sample_count || 1} samples)</span>`;
        document.getElementById('btnDeleteFace').style.display = 'inline-block';
    } else {
        badgeContainer.innerHTML = `<span class="badge bg-warning text-dark p-2 fs-6"><i class="fa-solid fa-circle-exclamation me-1"></i>Face Not Enrolled</span>`;
        document.getElementById('btnDeleteFace').style.display = 'none';
    }

    // Toggle panels
    document.getElementById('noStudentAlert').style.display = 'none';
    document.getElementById('registrationControls').style.display = 'block';

    document.getElementById('verifyNoStudentAlert').style.display = 'none';
    document.getElementById('verifyControls').style.display = 'block';

    resetRegistrationPills();
    hideAlert();

    // Start appropriate camera
    if (isRegisteringActive) {
        await startRegisterCamera();
    } else {
        await startVerifyCamera();
    }
}

// Event Listeners setup
function setupEventListeners() {
    document.getElementById('studentSearch').addEventListener('input', () => fetchStudents());
    document.getElementById('classFilter').addEventListener('change', () => fetchStudents());

    document.getElementById('register-tab').addEventListener('shown.bs.tab', async () => {
        isRegisteringActive = true;
        stopVerifyCamera();
        if (selectedStudent) await startRegisterCamera();
    });

    document.getElementById('verify-tab').addEventListener('shown.bs.tab', async () => {
        isRegisteringActive = false;
        stopRegisterCamera();
        if (selectedStudent) await startVerifyCamera();
    });

    document.getElementById('btnAutoCapture').addEventListener('click', captureMultiSamples);
    document.getElementById('btnSingleCapture').addEventListener('click', captureSingleSample);
    document.getElementById('btnDeleteFace').addEventListener('click', deleteFaceProfile);
    document.getElementById('uploadPhotoInput').addEventListener('change', handleUploadedPhoto);
}

// Start Camera for Registration
async function startRegisterCamera() {
    const video = document.getElementById('videoRegister');
    if (registerStream) return;

    try {
        registerStream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
            audio: false
        });
        video.srcObject = registerStream;
        video.onloadedmetadata = () => {
            video.play();
            startRegisterFaceTracking();
        };
        updateHud('CAMERA LIVE', 'success');
    } catch (e) {
        console.error("Camera access error:", e);
        updateHud('CAMERA ERROR', 'danger');
        showAlert('danger', 'Unable to access camera. Please allow camera permissions.');
    }
}

function stopRegisterCamera() {
    if (registerStream) {
        registerStream.getTracks().forEach(t => t.stop());
        registerStream = null;
    }
}

// Real-time Face Tracking during Registration
let trackingInterval = null;
function startRegisterFaceTracking() {
    const video = document.getElementById('videoRegister');
    const canvas = document.getElementById('canvasRegister');
    const guide = document.getElementById('hudGuide');
    const feedback = document.getElementById('feedbackText');

    if (trackingInterval) clearInterval(trackingInterval);

    trackingInterval = setInterval(async () => {
        if (!modelsLoaded || !registerStream || video.paused || video.ended) return;

        const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
        faceapi.matchDimensions(canvas, displaySize);

        const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (detection) {
            guide.classList.add('active');
            feedback.innerHTML = `<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i>Face Detected (Confidence: ${Math.round(detection.detection.score * 100)}%) - Ready to capture!</span>`;
            
            // Draw smooth bounding box
            const resizedDetection = faceapi.resizeResults(detection, displaySize);
            const box = resizedDetection.detection.box;
            ctx.strokeStyle = '#22c55e';
            ctx.lineWidth = 3;
            ctx.strokeRect(box.x, box.y, box.width, box.height);
        } else {
            guide.classList.remove('active');
            feedback.innerHTML = `<span class="text-muted"><i class="fa-solid fa-face-smile me-1"></i>Position face inside the oval frame.</span>`;
        }
    }, 200);
}

// Multi-angle sample capture (5 fast samples)
async function captureMultiSamples() {
    if (!selectedStudent) return;
    if (isCapturingMulti) return;

    isCapturingMulti = true;
    collectedSamples = [];
    resetRegistrationPills();

    const video = document.getElementById('videoRegister');
    const feedback = document.getElementById('feedbackText');
    const btn = document.getElementById('btnAutoCapture');
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Capturing...`;

    const instructions = [
        "Sample 1: Look directly at camera...",
        "Sample 2: Smile slightly...",
        "Sample 3: Turn face slightly left...",
        "Sample 4: Turn face slightly right...",
        "Sample 5: Look slightly upwards..."
    ];

    for (let i = 0; i < 5; i++) {
        feedback.innerHTML = `<span class="text-primary fw-bold"><i class="fa-solid fa-spinner fa-spin me-1"></i>${instructions[i]}</span>`;
        await new Promise(r => setTimeout(r, 600));

        const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (detection) {
            collectedSamples.push(Array.from(detection.descriptor));
            setSamplePillDone(i + 1);
            setProgressBar((i + 1) * 20);
        } else {
            // Retry once
            await new Promise(r => setTimeout(r, 400));
            const retryDet = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.4 }))
                .withFaceLandmarks()
                .withFaceDescriptor();
            if (retryDet) {
                collectedSamples.push(Array.from(retryDet.descriptor));
                setSamplePillDone(i + 1);
                setProgressBar((i + 1) * 20);
            }
        }
    }

    btn.disabled = false;
    btn.innerHTML = `<i class="fa-solid fa-bolt me-1"></i> Capture 5-Angle Samples`;
    isCapturingMulti = false;

    if (collectedSamples.length >= 2) {
        feedback.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Captured ${collectedSamples.length} high-quality biometric samples! Saving profile...</span>`;
        // Capture snapshot from video
        const snapshot = getFrameSnapshot(video);
        await saveFaceProfile(collectedSamples, snapshot);
    } else {
        feedback.innerHTML = `<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i>Only ${collectedSamples.length} samples captured. Please hold still and try again.</span>`;
        showAlert('warning', 'Could not capture enough clear face angles. Please ensure good lighting and try again.');
    }
}

// Single Snapshot Capture
async function captureSingleSample() {
    if (!selectedStudent) return;
    const video = document.getElementById('videoRegister');
    const feedback = document.getElementById('feedbackText');

    feedback.innerHTML = `<span class="text-primary fw-bold"><i class="fa-solid fa-spinner fa-spin me-1"></i>Analyzing face...</span>`;

    const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 }))
        .withFaceLandmarks()
        .withFaceDescriptor();

    if (detection) {
        resetRegistrationPills();
        setSamplePillDone(1);
        setProgressBar(100);
        const descriptor = [Array.from(detection.descriptor)];
        const snapshot = getFrameSnapshot(video);
        await saveFaceProfile(descriptor, snapshot);
    } else {
        showAlert('danger', 'No face detected in camera frame. Please center face and try again.');
        feedback.innerHTML = `<span class="text-danger">Face not detected.</span>`;
    }
}

// Upload photo file
async function handleUploadedPhoto(e) {
    const file = e.target.files[0];
    if (!file || !selectedStudent) return;

    showAlert('info', 'Analyzing uploaded image for facial features...');
    const img = new Image();
    img.src = URL.createObjectURL(file);
    img.onload = async () => {
        try {
            const detection = await faceapi.detectSingleFace(img, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (detection) {
                // Convert image to base64
                const canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                const base64 = canvas.toDataURL('image/jpeg', 0.85);

                const descriptor = [Array.from(detection.descriptor)];
                resetRegistrationPills();
                setSamplePillDone(1);
                setProgressBar(100);
                await saveFaceProfile(descriptor, base64);
            } else {
                showAlert('danger', 'No face detected in the uploaded photo. Please upload a clear portrait.');
            }
        } catch (err) {
            console.error(err);
            showAlert('danger', 'Error processing image: ' + err.message);
        }
    };
}

// Save Face Profile to Database via API
async function saveFaceProfile(descriptors, imageBase64) {
    try {
        showAlert('info', '<i class="fa-solid fa-spinner fa-spin me-2"></i>Registering face biometric profile in database...');
        const res = await fetch('api_face.php?action=save_face', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_id: selectedStudent.id,
                descriptors: descriptors,
                image: imageBase64
            })
        });
        const data = await res.json();
        if (data.success) {
            showAlert('success', `<i class="fa-solid fa-circle-check me-2"></i>${data.message}`);
            selectedStudent.is_registered = true;
            selectedStudent.sample_count = data.sample_count;
            if (data.photo_path) selectedStudent.face_photo = data.photo_path;

            // Re-render list and badges
            fetchStudents(selectedStudent.id);

            // Suggest verification
            setTimeout(() => {
                const verifyTabBtn = document.getElementById('verify-tab');
                if (confirm('Face registered successfully! Would you like to run the live verification test now?')) {
                    const tab = new bootstrap.Tab(verifyTabBtn);
                    tab.show();
                }
            }, 500);
        } else {
            showAlert('danger', data.message || 'Error saving face profile.');
        }
    } catch (err) {
        console.error(err);
        showAlert('danger', 'Network or server error while saving face profile.');
    }
}

// Delete Face Profile
async function deleteFaceProfile() {
    if (!selectedStudent || !confirm(`Are you sure you want to remove the registered face profile for ${selectedStudent.name}?`)) return;

    try {
        const res = await fetch('api_face.php?action=delete_face', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_id: selectedStudent.id })
        });
        const data = await res.json();
        if (data.success) {
            showAlert('success', 'Face profile deleted successfully.');
            selectedStudent.is_registered = false;
            fetchStudents(selectedStudent.id);
        } else {
            showAlert('danger', data.message || 'Error deleting face profile.');
        }
    } catch (e) {
        showAlert('danger', 'Server error while deleting profile.');
    }
}

// Live Face Verification Tab Engine
async function startVerifyCamera() {
    const video = document.getElementById('videoVerify');
    const canvas = document.getElementById('canvasVerify');

    if (!verifyStream) {
        try {
            verifyStream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                audio: false
            });
            video.srcObject = verifyStream;
            video.onloadedmetadata = () => video.play();
        } catch (e) {
            alert("Could not access camera for verification: " + e.message);
            return;
        }
    }

    // Fetch registered descriptors for this student
    let referenceDescriptors = [];
    try {
        const descRes = await fetch(`api_face.php?action=get_face_descriptors&student_id=${selectedStudent.id}`);
        const descData = await descRes.json();
        const found = descData.data.find(d => d.student_id === selectedStudent.id);
        if (found && found.descriptors) {
            referenceDescriptors = found.descriptors.map(d => new Float32Array(d));
        }
    } catch (e) {
        console.error(e);
    }

    if (referenceDescriptors.length === 0) {
        document.getElementById('verifyStatusTitle').innerText = "Not Enrolled";
        document.getElementById('verifyStatusDesc').innerText = "This student does not have a registered face yet. Register in Tab 1 first.";
        document.getElementById('verifyIcon').innerHTML = `<i class="fa-solid fa-triangle-exclamation text-warning"></i>`;
        return;
    }

    const faceMatcher = new faceapi.FaceMatcher(
        referenceDescriptors.map(d => new faceapi.LabeledFaceDescriptors(selectedStudent.name, [d])),
        0.55 // threshold
    );

    if (verifyInterval) clearInterval(verifyInterval);

    verifyInterval = setInterval(async () => {
        if (!modelsLoaded || !verifyStream || video.paused || video.ended) return;

        const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
        faceapi.matchDimensions(canvas, displaySize);

        const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (detection) {
            const bestMatch = faceMatcher.findBestMatch(detection.descriptor);
            const dist = bestMatch.distance;
            // Match score percentage
            const confidence = Math.max(0, Math.min(100, Math.round((1 - (dist / 0.7)) * 100)));

            const box = faceapi.resizeResults(detection, displaySize).detection.box;

            if (bestMatch.label !== 'unknown' && dist < 0.55) {
                // MATCH CONFIRMED
                ctx.strokeStyle = '#22c55e';
                ctx.lineWidth = 4;
                ctx.strokeRect(box.x, box.y, box.width, box.height);

                document.getElementById('verifyIcon').innerHTML = `<i class="fa-solid fa-circle-check text-success fa-bounce"></i>`;
                document.getElementById('verifyStatusTitle').innerHTML = `<span class="text-success">Verified: ${escapeHtml(selectedStudent.name)}</span>`;
                document.getElementById('verifyStatusDesc').innerText = "Biometric face verification 100% successful! System is ready for attendance.";
                
                const bar = document.getElementById('verifyConfidenceBar');
                bar.style.width = `${confidence}%`;
                bar.className = 'progress-bar bg-success';
                bar.innerText = `${confidence}%`;

                document.getElementById('verifyDistanceVal').innerText = `${dist.toFixed(4)} (Threshold: < 0.55)`;
            } else {
                // MISMATCH
                ctx.strokeStyle = '#ef4444';
                ctx.lineWidth = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);

                document.getElementById('verifyIcon').innerHTML = `<i class="fa-solid fa-circle-xmark text-danger"></i>`;
                document.getElementById('verifyStatusTitle').innerHTML = `<span class="text-danger">Unrecognized Face</span>`;
                document.getElementById('verifyStatusDesc').innerText = "Face does not match registered profile.";

                const bar = document.getElementById('verifyConfidenceBar');
                bar.style.width = `${confidence}%`;
                bar.className = 'progress-bar bg-danger';
                bar.innerText = `${confidence}%`;

                document.getElementById('verifyDistanceVal').innerText = `${dist.toFixed(4)} (Threshold: < 0.55)`;
            }
        }
    }, 250);
}

function stopVerifyCamera() {
    if (verifyInterval) clearInterval(verifyInterval);
    if (verifyStream) {
        verifyStream.getTracks().forEach(t => t.stop());
        verifyStream = null;
    }
}

// Helpers
function getFrameSnapshot(video) {
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    // Mirror back snapshot
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg', 0.85);
}

function updateHud(text, type = 'info') {
    const hud = document.getElementById('hudStatus');
    const color = type === 'success' ? '#22c55e' : (type === 'danger' ? '#ef4444' : (type === 'warning' ? '#f59e0b' : '#38bdf8'));
    hud.innerHTML = `<i class="fa-solid fa-circle me-1" style="color: ${color}"></i> ${text}`;
}

function setSamplePillDone(n) {
    const p = document.getElementById(`pill-${n}`);
    if (p) p.classList.add('done');
}

function resetRegistrationPills() {
    for (let i = 1; i <= 5; i++) {
        const p = document.getElementById(`pill-${i}`);
        if (p) p.classList.remove('done');
    }
    setProgressBar(0);
}

function setProgressBar(percent) {
    document.getElementById('sampleProgressBar').style.width = `${percent}%`;
}

function showAlert(type, msg) {
    const el = document.getElementById('registerAlert');
    el.style.display = 'block';
    el.className = `alert alert-${type} alert-dismissible fade show rounded-3 shadow-sm`;
    el.innerHTML = `${msg} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
}

function hideAlert() {
    document.getElementById('registerAlert').style.display = 'none';
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}
</script>

<?php require_once '../includes/footer.php'; ?>
