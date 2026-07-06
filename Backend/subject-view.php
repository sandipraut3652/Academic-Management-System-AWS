<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'] ?? 0;
$firstName  = $_SESSION['firstName'] ?? 'Teacher';
$subject_id = (int)($_GET['subject_id'] ?? 0);
$tab        = $_GET['tab'] ?? 'students';

if (!$subject_id) {
    header("Location: teacher-dashboard.php");
    exit();
}

// Verify this subject is assigned to this teacher
$chk = $conn->prepare("SELECT s.id, s.subject_name, s.subject_code, c.class_name, c.id as class_id
    FROM teacher_subjects ts
    JOIN subjects s ON ts.subject_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE ts.teacher_id = ? AND s.id = ?");
$chk->bind_param("ii", $teacher_id, $subject_id);
$chk->execute();
$subject = $chk->get_result()->fetch_assoc();

if (!$subject) {
    header("Location: teacher-dashboard.php");
    exit();
}

$class_id   = $subject['class_id'];
$class_name = $subject['class_name'];

// ── HANDLE ACTIONS ──────────────────────────────────────────

// Mark Attendance
if (isset($_POST['mark_attendance'])) {
    $date       = $_POST['att_date'];
    $class_label = $_POST['class_label'];
    $students_list = $_POST['students'] ?? [];
    $present_ids   = $_POST['present'] ?? [];

    foreach ($students_list as $sid) {
        $sid    = (int)$sid;
        $status = in_array((string)$sid, array_map('strval', $present_ids)) ? 'present' : 'absent';
        // Delete existing record for same date+label+student to avoid duplicates
        $del = $conn->prepare("DELETE FROM attendance WHERE student_id=? AND class_date=? AND class_label=?");
        $del->bind_param("iss", $sid, $date, $class_label);
        $del->execute();
        // Insert new
        $ins = $conn->prepare("INSERT INTO attendance (student_id, student_name, class_date, class_label, status, marked_by) 
            SELECT ?, CONCAT(firstName,' ',lastName), ?, ?, ?, ? FROM users WHERE id=?");
        $ins->bind_param("issssi", $sid, $date, $class_label, $status, $firstName, $sid);
        $ins->execute();
    }
    $_SESSION['flash_msg']  = "Attendance saved!";
    $_SESSION['flash_type'] = "success";
    header("Location: subject-view.php?subject_id=$subject_id&tab=attendance");
    exit();
}

// Upload Study Material
if (isset($_POST['add_material'])) {
    $title      = trim($_POST['title']);
    $desc       = trim($_POST['description'] ?? '');
    $link       = trim($_POST['material_link'] ?? '');
    $stmt = $conn->prepare("INSERT INTO subject_materials (subject_id, teacher_id, title, description, link, class_id) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iisssi", $subject_id, $teacher_id, $title, $desc, $link, $class_id);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Material added!";
    $_SESSION['flash_type'] = "success";
    header("Location: subject-view.php?subject_id=$subject_id&tab=materials");
    exit();
}

// Post Announcement
if (isset($_POST['post_announcement'])) {
    $text = trim($_POST['announcement_text']);
    $stmt = $conn->prepare("INSERT INTO subject_announcements (subject_id, teacher_id, teacher_name, announcement_text, class_id) VALUES (?,?,?,?,?)");
    $stmt->bind_param("iissi", $subject_id, $teacher_id, $firstName, $text, $class_id);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Announcement posted!";
    $_SESSION['flash_type'] = "success";
    header("Location: subject-view.php?subject_id=$subject_id&tab=announcements");
    exit();
}

// Delete Material
if (isset($_POST['delete_material'])) {
    $mid = (int)$_POST['material_id'];
    $conn->prepare("DELETE FROM subject_materials WHERE id=? AND teacher_id=?")->execute();
    $stmt = $conn->prepare("DELETE FROM subject_materials WHERE id=? AND teacher_id=?");
    $stmt->bind_param("ii", $mid, $teacher_id);
    $stmt->execute();
    header("Location: subject-view.php?subject_id=$subject_id&tab=materials");
    exit();
}

// Delete Announcement
if (isset($_POST['delete_announcement'])) {
    $aid  = (int)$_POST['ann_id'];
    $stmt = $conn->prepare("DELETE FROM subject_announcements WHERE id=? AND teacher_id=?");
    $stmt->bind_param("ii", $aid, $teacher_id);
    $stmt->execute();
    header("Location: subject-view.php?subject_id=$subject_id&tab=announcements");
    exit();
}

// ── FETCH DATA ───────────────────────────────────────────────

// Students of this class
$students = $conn->prepare("SELECT id, firstName, lastName, email FROM users WHERE class_id=? AND role='student' AND status='approved' ORDER BY firstName");
$students->bind_param("i", $class_id);
$students->execute();
$students = $students->get_result()->fetch_all(MYSQLI_ASSOC);

// Attendance records for this subject's class
$attRecords = $conn->prepare("SELECT * FROM attendance WHERE class_label=? ORDER BY class_date DESC, student_name");
$attRecords->bind_param("s", $class_name);
$attRecords->execute();
$attRecords = $attRecords->get_result()->fetch_all(MYSQLI_ASSOC);

// Group attendance by date
$attByDate = [];
foreach ($attRecords as $r) {
    $attByDate[$r['class_date']][$r['student_id']] = $r['status'];
}

// Study Materials for this subject
// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS subject_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$mats = $conn->prepare("SELECT * FROM subject_materials WHERE subject_id=? ORDER BY created_at DESC");
$mats->bind_param("i", $subject_id);
$mats->execute();
$mats = $mats->get_result()->fetch_all(MYSQLI_ASSOC);

// Announcements for this subject
$conn->query("CREATE TABLE IF NOT EXISTS subject_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_name VARCHAR(100),
    announcement_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$anns = $conn->prepare("SELECT * FROM subject_announcements WHERE subject_id=? ORDER BY created_at DESC");
$anns->bind_param("i", $subject_id);
$anns->execute();
$anns = $anns->get_result()->fetch_all(MYSQLI_ASSOC);

// Attendance summary per student
$attSummary = [];
foreach ($students as $st) {
    $sid     = $st['id'];
    $present = 0; $total = 0;
    foreach ($attByDate as $date => $dayData) {
        $total++;
        if (($dayData[$sid] ?? '') === 'present') $present++;
    }
    $attSummary[$sid] = ['present' => $present, 'total' => $total];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject['subject_name']) ?> – ScholarPoint</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --navy:#0f1e3d;--blue:#2563eb;--indigo:#4f46e5;
            --green:#16a34a;--red:#dc2626;--amber:#d97706;
            --bg:#eef2ff;--card:#fff;--border:#dde3f5;
            --text:#1e293b;--muted:#64748b;--radius:16px;
            --shadow:0 4px 28px rgba(37,99,235,.10);
        }
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

        /* NAVBAR */
        .navbar{background:var(--navy);padding:0 32px;display:flex;align-items:center;height:64px;gap:4px;box-shadow:0 2px 16px rgba(0,0,0,.22)}
        .navbar .brand{font-weight:700;font-size:18px;color:#fff;margin-right:24px}
        .navbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;padding:7px 14px;border-radius:8px;transition:.18s}
        .navbar a:hover,.navbar a.active{background:rgba(255,255,255,.13);color:#fff}
        .navbar .spacer{flex:1}

        /* HERO */
        .hero{background:linear-gradient(135deg,var(--navy) 0%,#1e3a8a 60%,var(--indigo) 100%);padding:36px 32px 44px}
        .hero-inner{max-width:1000px;margin:0 auto}
        .breadcrumb{font-size:13px;color:rgba(255,255,255,.5);margin-bottom:12px}
        .breadcrumb a{color:rgba(255,255,255,.6);text-decoration:none}
        .breadcrumb a:hover{color:#fff}
        .hero h1{font-size:28px;font-weight:800;color:#fff;margin-bottom:6px}
        .hero h1 span{background:linear-gradient(90deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
        .hero-badge{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.85);padding:5px 14px;border-radius:20px;font-size:13px;font-weight:500}

        /* TABS */
        .tabs-bar{background:var(--card);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:10;box-shadow:0 2px 8px rgba(0,0,0,.06)}
        .tabs-inner{max-width:1000px;margin:0 auto;padding:0 20px;display:flex;gap:4px}
        .tab-btn{padding:14px 20px;font-size:14px;font-weight:600;color:var(--muted);border:none;border-bottom:3px solid transparent;background:none;cursor:pointer;transition:.18s;display:flex;align-items:center;gap:7px}
        .tab-btn:hover{color:var(--navy)}
        .tab-btn.active{color:var(--blue);border-bottom-color:var(--blue)}

        /* PAGE */
        .page{max-width:1000px;margin:28px auto;padding:0 20px 80px}

        /* FLASH */
        .alert{padding:12px 18px;border-radius:10px;font-size:14px;font-weight:500;margin-bottom:20px}
        .alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
        .alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}

        /* CARDS */
        .table-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;margin-bottom:28px}
        .card-header{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
        .card-header h3{font-size:15px;font-weight:700;color:var(--navy)}
        table{width:100%;border-collapse:collapse}
        thead th{padding:11px 20px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;background:#f8faff;border-bottom:1px solid var(--border);text-align:left}
        tbody td{padding:12px 20px;font-size:14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
        tbody tr:last-child td{border-bottom:none}
        tbody tr:hover td{background:#f8faff}
        .avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--indigo));display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;margin-right:10px;vertical-align:middle}
        .empty-state{text-align:center;padding:40px;color:var(--muted)}
        .empty-state .icon{font-size:40px;margin-bottom:10px}

        /* FORM CARDS */
        .form-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:24px;margin-bottom:24px}
        .form-card h3{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:16px}
        label{display:block;font-size:13px;font-weight:600;color:var(--muted);margin-bottom:5px}
        input[type=text],input[type=date],input[type=url],textarea,select{width:100%;padding:9px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:14px;font-family:'DM Sans',sans-serif;margin-bottom:14px;outline:none;transition:.18s}
        input:focus,textarea:focus,select:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        textarea{resize:vertical;min-height:90px}
        .btn-primary{background:var(--blue);color:#fff;border:none;padding:10px 24px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:.2s}
        .btn-primary:hover{background:#1d4ed8}
        .btn-del{background:none;border:none;color:var(--red);cursor:pointer;padding:5px 8px;border-radius:8px;transition:.15s;font-size:16px}
        .btn-del:hover{background:#fee2e2}

        /* ATTENDANCE SPECIFIC */
        .att-toggle{display:flex;align-items:center;gap:10px}
        .toggle-switch{position:relative;width:48px;height:26px}
        .toggle-switch input{opacity:0;width:0;height:0}
        .slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#e2e8f0;border-radius:26px;transition:.3s}
        .slider:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s}
        input:checked + .slider{background:#16a34a}
        input:checked + .slider:before{transform:translateX(22px)}
        .status-present{color:#15803d;font-weight:700;font-size:13px}
        .status-absent{color:#dc2626;font-weight:700;font-size:13px}
        .att-pct{font-size:12px;font-weight:700;padding:2px 8px;border-radius:10px}
        .att-pct.good{background:#dcfce7;color:#15803d}
        .att-pct.warn{background:#fef3c7;color:#d97706}
        .att-pct.bad{background:#fee2e2;color:#dc2626}

        /* MATERIAL ITEM */
        .material-item{display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-bottom:1px solid #f1f5f9}
        .material-item:last-child{border-bottom:none}
        .mat-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
        .mat-info{flex:1}
        .mat-info h4{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:3px}
        .mat-info p{font-size:13px;color:var(--muted)}
        .mat-link{font-size:13px;color:var(--blue);font-weight:600;text-decoration:none}
        .mat-link:hover{text-decoration:underline}
        .mat-date{font-size:11px;color:var(--muted);margin-top:4px}

        /* ANNOUNCEMENT ITEM */
        .ann-item{padding:16px 22px;border-bottom:1px solid #f1f5f9}
        .ann-item:last-child{border-bottom:none}
        .ann-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
        .ann-author{font-size:13px;font-weight:700;color:var(--navy)}
        .ann-date{font-size:12px;color:var(--muted)}
        .ann-text{font-size:14px;color:var(--text);line-height:1.6}

        /* PROGRESS BAR */
        .progress-bar{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;margin-top:4px}
        .progress-fill{height:100%;border-radius:3px;transition:.3s}

        @media(max-width:600px){
            .tabs-inner{overflow-x:auto}
            .tab-btn{white-space:nowrap;padding:12px 14px;font-size:13px}
        }
    </style>
</head>
<body>

<nav class="navbar">
    <span class="brand">📚 ScholarPoint</span>
    <a href="teacher-dashboard.php">Home</a>
    <a href="teacher-dashboard.php">My Subjects</a>
    <a href="meetings.php">Meetings</a>
    <a href="manage-subjects.php">Manage Subjects</a>
    <div class="spacer"></div>
    <a href="logout.php">👋 Logout</a>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-inner">
        <div class="breadcrumb">
            <a href="teacher-dashboard.php">Dashboard</a> › My Subjects › <?= htmlspecialchars($subject['subject_name']) ?>
        </div>
        <h1>📗 <span><?= htmlspecialchars($subject['subject_name']) ?></span></h1>
        <div class="hero-meta">
            <span class="hero-badge">🏷️ <?= htmlspecialchars($subject['subject_code'] ?? 'N/A') ?></span>
            <span class="hero-badge">🏫 Class: <?= htmlspecialchars($class_name) ?></span>
            <span class="hero-badge">👨‍🎓 <?= count($students) ?> Students</span>
            <span class="hero-badge">👨‍🏫 <?= htmlspecialchars($firstName) ?></span>
        </div>
    </div>
</div>

<!-- TABS -->
<div class="tabs-bar">
    <div class="tabs-inner">
        <a href="?subject_id=<?= $subject_id ?>&tab=students" style="text-decoration:none">
            <div class="tab-btn <?= $tab === 'students' ? 'active' : '' ?>">👥 Students <span style="background:#e0e7ff;color:#4f46e5;font-size:11px;padding:1px 7px;border-radius:10px;font-weight:700"><?= count($students) ?></span></div>
        </a>
        <a href="?subject_id=<?= $subject_id ?>&tab=attendance" style="text-decoration:none">
            <div class="tab-btn <?= $tab === 'attendance' ? 'active' : '' ?>">✅ Attendance</div>
        </a>
        <a href="?subject_id=<?= $subject_id ?>&tab=materials" style="text-decoration:none">
            <div class="tab-btn <?= $tab === 'materials' ? 'active' : '' ?>">📖 Study Materials <span style="background:#e0e7ff;color:#4f46e5;font-size:11px;padding:1px 7px;border-radius:10px;font-weight:700"><?= count($mats) ?></span></div>
        </a>
        <a href="?subject_id=<?= $subject_id ?>&tab=announcements" style="text-decoration:none">
            <div class="tab-btn <?= $tab === 'announcements' ? 'active' : '' ?>">📢 Announcements <span style="background:#e0e7ff;color:#4f46e5;font-size:11px;padding:1px 7px;border-radius:10px;font-weight:700"><?= count($anns) ?></span></div>
        </a>
    </div>
</div>

<div class="page">
    <?php if(isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'success' ?>">
            ✅ <?= htmlspecialchars($_SESSION['flash_msg']) ?>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <?php if($tab === 'students'): ?>
    <!-- ── STUDENTS TAB ── -->
    <div class="table-card">
        <div class="card-header">
            <h3>👥 Students — <?= htmlspecialchars($class_name) ?></h3>
            <span style="font-size:13px;color:var(--muted)"><?= count($students) ?> total</span>
        </div>
        <?php if(count($students) === 0): ?>
            <div class="empty-state"><div class="icon">👥</div><p>No students in this class yet.</p></div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Attendance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $i => $st): ?>
                <?php
                    $summ    = $attSummary[$st['id']] ?? ['present'=>0,'total'=>0];
                    $pct     = $summ['total'] > 0 ? round($summ['present'] / $summ['total'] * 100) : 0;
                    $pctClass = $pct >= 75 ? 'good' : ($pct >= 60 ? 'warn' : 'bad');
                    $pctColor = $pct >= 75 ? '#16a34a' : ($pct >= 60 ? '#d97706' : '#dc2626');
                ?>
                <tr>
                    <td style="color:var(--muted);font-size:13px"><?= $i+1 ?></td>
                    <td>
                        <span class="avatar"><?= strtoupper(substr($st['firstName'],0,1).substr($st['lastName'],0,1)) ?></span>
                        <?= htmlspecialchars($st['firstName'].' '.$st['lastName']) ?>
                    </td>
                    <td style="color:var(--muted);font-size:13px"><?= htmlspecialchars($st['email']) ?></td>
                    <td style="min-width:130px">
                        <?php if($summ['total'] > 0): ?>
                            <span class="att-pct <?= $pctClass ?>"><?= $pct ?>%</span>
                            <span style="font-size:12px;color:var(--muted);margin-left:4px"><?= $summ['present'] ?>/<?= $summ['total'] ?></span>
                            <div class="progress-bar" style="margin-top:6px">
                                <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pctColor ?>"></div>
                            </div>
                        <?php else: ?>
                            <span style="color:var(--muted);font-size:12px">No records</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php elseif($tab === 'attendance'): ?>
    <!-- ── ATTENDANCE TAB ── -->
    <div class="form-card">
        <h3>✅ Mark Attendance</h3>
        <?php if(count($students) === 0): ?>
            <p style="color:var(--muted);font-size:14px">No students in this class to mark attendance.</p>
        <?php else: ?>
        <form method="post">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <label>Date</label>
                    <input type="date" name="att_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label>Class Label</label>
                    <input type="text" name="class_label" value="<?= htmlspecialchars($class_name) ?>" required>
                </div>
            </div>
            <table style="margin-bottom:16px">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Present / Absent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $st): ?>
                    <tr>
                        <td>
                            <input type="hidden" name="students[]" value="<?= $st['id'] ?>">
                            <span class="avatar"><?= strtoupper(substr($st['firstName'],0,1).substr($st['lastName'],0,1)) ?></span>
                            <?= htmlspecialchars($st['firstName'].' '.$st['lastName']) ?>
                        </td>
                        <td>
                            <div class="att-toggle">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="present[]" value="<?= $st['id'] ?>" checked>
                                    <span class="slider"></span>
                                </label>
                                <span style="font-size:13px;color:var(--muted)">Present</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="mark_attendance" class="btn-primary">💾 Save Attendance</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Past Attendance Records -->
    <?php if(count($attByDate) > 0): ?>
    <div class="table-card">
        <div class="card-header"><h3>📋 Past Attendance Records</h3></div>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <?php foreach(array_keys($attByDate) as $d): ?>
                        <th><?= date('d M', strtotime($d)) ?></th>
                    <?php endforeach; ?>
                    <th>Total %</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $st): ?>
                <tr>
                    <td>
                        <span class="avatar"><?= strtoupper(substr($st['firstName'],0,1).substr($st['lastName'],0,1)) ?></span>
                        <?= htmlspecialchars($st['firstName'].' '.$st['lastName']) ?>
                    </td>
                    <?php foreach(array_keys($attByDate) as $d): ?>
                        <td>
                            <?php $s = $attByDate[$d][$st['id']] ?? null; ?>
                            <?php if($s === 'present'): ?>
                                <span class="status-present">✅ P</span>
                            <?php elseif($s === 'absent'): ?>
                                <span class="status-absent">❌ A</span>
                            <?php else: ?>
                                <span style="color:var(--muted)">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <?php
                            $summ = $attSummary[$st['id']];
                            $pct  = $summ['total'] > 0 ? round($summ['present']/$summ['total']*100) : 0;
                            $pc   = $pct >= 75 ? 'good' : ($pct >= 60 ? 'warn' : 'bad');
                        ?>
                        <span class="att-pct <?= $pc ?>"><?= $pct ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php elseif($tab === 'materials'): ?>
    <!-- ── STUDY MATERIALS TAB ── -->
    <div class="form-card">
        <h3>➕ Add Study Material</h3>
        <form method="post">
            <label>Title</label>
            <input type="text" name="title" placeholder="e.g. Chapter 3 Notes — Digital Gates" required>
            <label>Description (optional)</label>
            <textarea name="description" placeholder="Brief description..."></textarea>
            <label>Link (Google Drive, PDF URL, etc.)</label>
            <input type="text" name="material_link" placeholder="https://drive.google.com/...">
            <button type="submit" name="add_material" class="btn-primary">📎 Add Material</button>
        </form>
    </div>

    <div class="table-card">
        <div class="card-header"><h3>📖 Study Materials — <?= htmlspecialchars($subject['subject_name']) ?></h3></div>
        <?php if(count($mats) === 0): ?>
            <div class="empty-state"><div class="icon">📭</div><p>No materials uploaded yet.</p></div>
        <?php else: ?>
        <?php foreach($mats as $m): ?>
        <div class="material-item">
            <div class="mat-icon">📄</div>
            <div class="mat-info">
                <h4><?= htmlspecialchars($m['title']) ?></h4>
                <?php if($m['description']): ?><p><?= htmlspecialchars($m['description']) ?></p><?php endif; ?>
                <?php if($m['link']): ?>
                    <a href="<?= htmlspecialchars($m['link']) ?>" target="_blank" class="mat-link">🔗 Open Material →</a>
                <?php endif; ?>
                <div class="mat-date"><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></div>
            </div>
            <form method="post" onsubmit="return confirm('Delete this material?')">
                <input type="hidden" name="material_id" value="<?= $m['id'] ?>">
                <button type="submit" name="delete_material" class="btn-del"><i class="fas fa-trash-alt"></i></button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php elseif($tab === 'announcements'): ?>
    <!-- ── ANNOUNCEMENTS TAB ── -->
    <div class="form-card">
        <h3>📢 Post Announcement</h3>
        <form method="post">
            <label>Announcement</label>
            <textarea name="announcement_text" placeholder="Write your announcement for <?= htmlspecialchars($subject['subject_name']) ?> students..." required></textarea>
            <button type="submit" name="post_announcement" class="btn-primary">📢 Post Announcement</button>
        </form>
    </div>

    <div class="table-card">
        <div class="card-header"><h3>📢 Announcements — <?= htmlspecialchars($subject['subject_name']) ?></h3></div>
        <?php if(count($anns) === 0): ?>
            <div class="empty-state"><div class="icon">📭</div><p>No announcements yet.</p></div>
        <?php else: ?>
        <?php foreach($anns as $a): ?>
        <div class="ann-item">
            <div class="ann-header">
                <div>
                    <span class="ann-author">👨‍🏫 <?= htmlspecialchars($a['teacher_name']) ?></span>
                    <span class="ann-date" style="margin-left:10px"><?= date('d M Y, h:i A', strtotime($a['created_at'])) ?></span>
                </div>
                <form method="post" onsubmit="return confirm('Delete this announcement?')">
                    <input type="hidden" name="ann_id" value="<?= $a['id'] ?>">
                    <button type="submit" name="delete_announcement" class="btn-del"><i class="fas fa-trash-alt"></i></button>
                </form>
            </div>
            <div class="ann-text"><?= nl2br(htmlspecialchars($a['announcement_text'])) ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
