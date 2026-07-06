<?php
session_start();
include("connect.php");

if(!isset($_SESSION['email'])){
    header("Location: index.php");
    exit();
}

$subject_id = (int)($_GET['id'] ?? 0);
$role       = $_SESSION['role'];
$user_id    = $_SESSION['user_id'];
$firstName  = $_SESSION['firstName'] ?? '';

if(!$subject_id){
    header("Location: " . ($role === 'teacher' ? 'teacher-dashboard.php' : 'homepage.php'));
    exit();
}

// Get subject info
$stmt = $conn->prepare("SELECT s.*, c.class_name FROM subjects s JOIN classes c ON s.class_id=c.id WHERE s.id=?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if(!$subject){
    header("Location: " . ($role === 'teacher' ? 'teacher-dashboard.php' : 'homepage.php'));
    exit();
}

$cls = $subject['class_name'];

// Get teachers for this subject
$teacherRes = $conn->query("SELECT u.id, u.firstName, u.lastName FROM teacher_subjects ts JOIN users u ON ts.teacher_id=u.id WHERE ts.subject_id=$subject_id");
$teachers = $teacherRes->fetch_all(MYSQLI_ASSOC);

// ============ TEACHER ACTIONS ============
if($role === 'teacher'){
    // Upload material
    if(isset($_POST['upload_material'])){
        $title = trim($_POST['material_title']);
        if(isset($_FILES['material_file']) && $_FILES['material_file']['error'] === 0){
            $uploadDir = '/var/www/html/uploads/study-materials/';
            if(!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
            $fileName = time().'_'.basename($_FILES['material_file']['name']);
            $filePath = $uploadDir . $fileName;
            if(move_uploaded_file($_FILES['material_file']['tmp_name'], $filePath)){
                $dbPath = 'uploads/study-materials/' . $fileName;
                $stmt = $conn->prepare("INSERT INTO subject_materials (subject_id, title, file_path, uploaded_by) VALUES (?,?,?,?)");
                $stmt->bind_param("issi", $subject_id, $title, $dbPath, $user_id);
                $stmt->execute();
                $_SESSION['flash_msg'] = "Material uploaded!";
            }
        }
        header("Location: subject.php?id=$subject_id&tab=materials");
        exit();
    }

    // Delete material
    if(isset($_POST['delete_material'])){
        $mid = (int)$_POST['material_id'];
        $res = $conn->query("SELECT file_path FROM subject_materials WHERE id=$mid")->fetch_assoc();
        if($res) @unlink('/var/www/html/' . $res['file_path']);
        $conn->query("DELETE FROM subject_materials WHERE id=$mid");
        $_SESSION['flash_msg'] = "Material deleted!";
        header("Location: subject.php?id=$subject_id&tab=materials");
        exit();
    }

    // Post announcement
    if(isset($_POST['post_announcement'])){
        $text = trim($_POST['announcement_text']);
        $stmt = $conn->prepare("INSERT INTO subject_announcements (subject_id, teacher_id, announcement_text) VALUES (?,?,?)");
        $stmt->bind_param("iis", $subject_id, $user_id, $text);
        $stmt->execute();
        $_SESSION['flash_msg'] = "Announcement posted!";
        header("Location: subject.php?id=$subject_id&tab=announcements");
        exit();
    }

    // Schedule Meeting
    if(isset($_POST['schedule_meeting'])){
        $title    = trim($_POST['meeting_title']);
        $desc     = trim($_POST['meeting_desc']);
        $date     = $_POST['meet_date'];
        $time     = $_POST['meet_time'];
        $room     = 'SP-' . strtoupper(substr(md5($title.time()), 0, 8));
        $stmt = $conn->prepare("INSERT INTO meetings (teacher_id, teacher_name, title, description, meet_date, meet_time, room_name, subject_id) VALUES (?,?,?,?,?,?,?,?)");
        $tName = $firstName;
        $stmt->bind_param("issssssi", $user_id, $tName, $title, $desc, $date, $time, $room, $subject_id);
        $stmt->execute();
        $_SESSION['flash_msg'] = "Meeting scheduled!";
        header("Location: subject.php?id=$subject_id&tab=meetings");
        exit();
    }

    // Start/Stop Meeting Live
    if(isset($_POST['toggle_live'])){
        $mid     = (int)$_POST['meeting_id'];
        $is_live = (int)$_POST['is_live'];
        $conn->query("UPDATE meetings SET is_live=$is_live WHERE id=$mid");
        $_SESSION['flash_msg'] = $is_live ? "Meeting is now LIVE!" : "Meeting stopped.";
        header("Location: subject.php?id=$subject_id&tab=meetings");
        exit();
    }

    // Mark Attendance
    if(isset($_POST['mark_attendance'])){
        $date  = $_POST['att_date'];
        $label = trim($_POST['att_label']);
        // Get class students
        $classStudents = $conn->query("SELECT id, firstName, lastName FROM users WHERE role='student' AND status='approved' AND class_id={$subject['class_id']}")->fetch_all(MYSQLI_ASSOC);
        foreach($classStudents as $st){
            $status = isset($_POST['att_'.$st['id']]) ? 'present' : 'absent';
            $sName  = $st['firstName'].' '.$st['lastName'];
            $stmt   = $conn->prepare("INSERT INTO attendance (student_id, student_name, class_date, class_label, status, marked_by, subject_id) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
            $stmt->bind_param("isssssi", $st['id'], $sName, $date, $label, $status, $firstName, $subject_id);
            $stmt->execute();
        }
        $_SESSION['flash_msg'] = "Attendance marked!";
        header("Location: subject.php?id=$subject_id&tab=attendance");
        exit();
    }
}

// ============ STUDENT ACTIONS ============
if($role === 'student'){
    // Submit homework
    if(isset($_POST['submit_homework'])){
        $title = trim($_POST['hw_title']);
        if(isset($_FILES['hw_file']) && $_FILES['hw_file']['error'] === 0){
            $uploadDir = '/var/www/html/uploads/homework/';
            if(!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
            $fileName = time().'_'.basename($_FILES['hw_file']['name']);
            $filePath = $uploadDir . $fileName;
            if(move_uploaded_file($_FILES['hw_file']['tmp_name'], $filePath)){
                $dbPath = 'uploads/homework/' . $fileName;
                $stmt = $conn->prepare("INSERT INTO subject_homework (subject_id, student_id, title, file_path) VALUES (?,?,?,?)");
                $stmt->bind_param("iiis", $subject_id, $user_id, $title, $dbPath);
                $stmt->execute();
                $_SESSION['flash_msg'] = "Homework submitted!";
            }
        }
        header("Location: subject.php?id=$subject_id&tab=homework");
        exit();
    }
}

// ============ FETCH DATA ============
$materials     = $conn->query("SELECT * FROM subject_materials WHERE subject_id=$subject_id ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$announcements = $conn->query("SELECT sa.*, u.firstName, u.lastName FROM subject_announcements sa JOIN users u ON sa.teacher_id=u.id WHERE sa.subject_id=$subject_id ORDER BY sa.id DESC")->fetch_all(MYSQLI_ASSOC);
$meetings      = $conn->query("SELECT * FROM meetings WHERE subject_id=$subject_id ORDER BY meet_date DESC, meet_time DESC")->fetch_all(MYSQLI_ASSOC);

// Homework
if($role === 'teacher'){
    $homework = $conn->query("SELECT sh.*, u.firstName, u.lastName FROM subject_homework sh JOIN users u ON sh.student_id=u.id WHERE sh.subject_id=$subject_id ORDER BY sh.id DESC")->fetch_all(MYSQLI_ASSOC);
    // Class students for attendance
    $classStudents = $conn->query("SELECT id, firstName, lastName FROM users WHERE role='student' AND status='approved' AND class_id={$subject['class_id']} ORDER BY firstName")->fetch_all(MYSQLI_ASSOC);
    // Attendance records
    $attRecords = $conn->query("SELECT * FROM attendance WHERE subject_id=$subject_id ORDER BY class_date DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
} else {
    $homework   = $conn->query("SELECT * FROM subject_homework WHERE subject_id=$subject_id AND student_id=$user_id ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
    // Student attendance for this subject
    $myAttendance = $conn->query("SELECT * FROM attendance WHERE subject_id=$subject_id AND student_id=$user_id ORDER BY class_date DESC")->fetch_all(MYSQLI_ASSOC);
    $totalClasses  = count($myAttendance);
    $presentCount  = count(array_filter($myAttendance, fn($a) => $a['status'] === 'present'));
    $attPercent    = $totalClasses > 0 ? round(($presentCount/$totalClasses)*100) : 0;
}

$activeTab = $_GET['tab'] ?? 'materials';
$classBg   = ['SE' => '#dbeafe', 'TE' => '#dcfce7', 'BE' => '#ede9fe'];
$classClr  = ['SE' => '#1d4ed8', 'TE' => '#15803d', 'BE' => '#7c3aed'];
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
        :root{--navy:#0f1e3d;--blue:#2563eb;--bg:#eef2ff;--card:#fff;--border:#dde3f5;--text:#1e293b;--muted:#64748b;--radius:16px;--shadow:0 4px 28px rgba(37,99,235,.10)}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        .navbar{background:var(--navy);padding:0 32px;display:flex;align-items:center;height:64px;gap:8px}
        .navbar .brand{font-weight:700;font-size:18px;color:#fff;margin-right:16px}
        .navbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;padding:7px 14px;border-radius:8px;transition:.18s}
        .navbar a:hover{background:rgba(255,255,255,.13);color:#fff}
        .navbar .spacer{flex:1}
        .hero{padding:32px 32px 40px;background:linear-gradient(135deg,var(--navy),#1e3a8a)}
        .hero-inner{max-width:900px;margin:0 auto;display:flex;align-items:center;gap:20px;flex-wrap:wrap}
        .sub-badge{width:66px;height:66px;border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:30px;background:<?= $classBg[$cls] ?? '#dbeafe' ?>;flex-shrink:0}
        .hero h1{font-size:24px;font-weight:800;color:#fff;margin-bottom:4px}
        .hero .meta{font-size:13px;color:rgba(255,255,255,.55);display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .class-tag{display:inline-block;background:<?= $classBg[$cls] ?? '#dbeafe' ?>;color:<?= $classClr[$cls] ?? '#1d4ed8' ?>;font-size:12px;font-weight:700;padding:3px 12px;border-radius:20px}
        .page{max-width:900px;margin:24px auto;padding:0 20px 80px}
        .flash{padding:12px 18px;border-radius:10px;font-size:14px;font-weight:500;margin-bottom:18px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0}

        /* TABS */
        .tabs{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap}
        .tab{padding:9px 18px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:2px solid var(--border);background:var(--card);color:var(--muted);transition:.18s;text-decoration:none}
        .tab.active,.tab:hover{background:var(--navy);color:#fff;border-color:var(--navy)}
        .tab-content{display:none}
        .tab-content.active{display:block}

        /* FORMS */
        .form-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:24px;margin-bottom:20px}
        .form-card h3{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:16px}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        label{display:block;font-size:13px;font-weight:600;color:var(--muted);margin-bottom:5px}
        input[type=text],input[type=date],input[type=time],textarea{width:100%;padding:9px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:14px;font-family:'DM Sans',sans-serif;margin-bottom:12px;outline:none;transition:.18s}
        input:focus,textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        textarea{resize:vertical;min-height:80px}
        input[type=file]{width:100%;padding:8px;border:1.5px dashed var(--border);border-radius:9px;font-size:13px;margin-bottom:12px;background:#f8faff}
        .btn{background:var(--blue);color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;transition:.2s}
        .btn:hover{background:#1d4ed8}
        .btn-red{background:#fee2e2;color:#dc2626;border:none;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
        .btn-green{background:#dcfce7;color:#15803d;border:none;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
        .btn-dl{background:#dbeafe;color:#1d4ed8;border:none;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}

        /* ITEMS */
        .item-list{display:flex;flex-direction:column;gap:12px}
        .item{background:var(--card);border-radius:12px;border:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;box-shadow:var(--shadow)}
        .item-left{display:flex;align-items:center;gap:14px}
        .item-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;background:#f1f5f9;flex-shrink:0}
        .item-title{font-size:14px;font-weight:700;color:var(--navy)}
        .item-meta{font-size:12px;color:var(--muted);margin-top:2px}
        .item-actions{display:flex;gap:8px;align-items:center;flex-shrink:0}

        /* ANNOUNCEMENT */
        .ann-card{background:var(--card);border-radius:12px;border-left:4px solid var(--blue);padding:16px 20px;margin-bottom:12px;box-shadow:var(--shadow)}
        .ann-text{font-size:14px;color:var(--text);margin-bottom:6px;line-height:1.6}
        .ann-meta{font-size:12px;color:var(--muted)}

        /* MEETING */
        .meeting-card{background:var(--card);border-radius:12px;border:1px solid var(--border);padding:20px;margin-bottom:12px;box-shadow:var(--shadow)}
        .meeting-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:10px}
        .meeting-title{font-size:15px;font-weight:700;color:var(--navy)}
        .live-badge{background:#dc2626;color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;animation:pulse 1.5s infinite}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}
        .scheduled-badge{background:#f1f5f9;color:var(--muted);font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
        .meeting-info{font-size:13px;color:var(--muted);display:flex;gap:16px;flex-wrap:wrap}
        .meeting-actions{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
        .btn-join{background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;padding:9px 20px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}

        /* ATTENDANCE */
        .att-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px}
        .att-stat{background:var(--card);border-radius:12px;border:1px solid var(--border);padding:16px;text-align:center;box-shadow:var(--shadow)}
        .att-stat .num{font-size:28px;font-weight:800;color:var(--navy)}
        .att-stat .lbl{font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:600;letter-spacing:.5px}
        .att-percent{font-size:28px;font-weight:800}
        .att-percent.good{color:#16a34a}
        .att-percent.warn{color:#d97706}
        .att-percent.bad{color:#dc2626}
        .att-table{width:100%;border-collapse:collapse}
        .att-table th{padding:10px 16px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;background:#f8faff;border-bottom:1px solid var(--border);text-align:left}
        .att-table td{padding:11px 16px;font-size:14px;border-bottom:1px solid #f1f5f9}
        .present-pill{background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
        .absent-pill{background:#fee2e2;color:#dc2626;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
        .student-check{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9}
        .student-check:last-child{border-bottom:none}
        .empty{text-align:center;padding:40px;color:var(--muted);font-size:14px}
        .empty .icon{font-size:40px;margin-bottom:10px}
        @media(max-width:600px){.form-row{grid-template-columns:1fr}.att-stats{grid-template-columns:1fr 1fr}}
    </style>
</head>
<body>
<nav class="navbar">
    <span class="brand">📚 ScholarPoint</span>
    <a href="<?= $role === 'teacher' ? 'teacher-dashboard.php' : 'homepage.php' ?>">← Back</a>
    <div class="spacer"></div>
    <a href="logout.php">👋 Logout</a>
</nav>

<div class="hero">
    <div class="hero-inner">
        <div class="sub-badge">📗</div>
        <div>
            <h1><?= htmlspecialchars($subject['subject_name']) ?></h1>
            <div class="meta">
                <span class="class-tag"><?= $cls ?></span>
                <?php if($subject['subject_code']): ?>
                    <span><?= htmlspecialchars($subject['subject_code']) ?></span>
                <?php endif; ?>
                <?php if(count($teachers) > 0): ?>
                    <span>👨‍🏫 <?= implode(', ', array_map(fn($t) => $t['firstName'].' '.$t['lastName'], $teachers)) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page">
    <?php if(isset($_SESSION['flash_msg'])): ?>
        <div class="flash">✅ <?= htmlspecialchars($_SESSION['flash_msg']) ?></div>
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>

    <!-- TABS -->
    <div class="tabs">
        <a href="?id=<?= $subject_id ?>&tab=materials" class="tab <?= $activeTab==='materials'?'active':'' ?>">📖 Materials</a>
        <a href="?id=<?= $subject_id ?>&tab=announcements" class="tab <?= $activeTab==='announcements'?'active':'' ?>">📢 Announcements</a>
        <a href="?id=<?= $subject_id ?>&tab=homework" class="tab <?= $activeTab==='homework'?'active':'' ?>">📝 <?= $role==='teacher'?'Homework':'Submit HW' ?></a>
        <a href="?id=<?= $subject_id ?>&tab=meetings" class="tab <?= $activeTab==='meetings'?'active':'' ?>">📹 Meetings</a>
        <a href="?id=<?= $subject_id ?>&tab=attendance" class="tab <?= $activeTab==='attendance'?'active':'' ?>">✅ Attendance</a>
    </div>

    <!-- MATERIALS TAB -->
    <div class="tab-content <?= $activeTab==='materials'?'active':'' ?>">
        <?php if($role === 'teacher'): ?>
        <div class="form-card">
            <h3>📤 Upload Study Material</h3>
            <form method="post" enctype="multipart/form-data">
                <label>Title</label>
                <input type="text" name="material_title" placeholder="e.g. Chapter 1 Notes" required>
                <label>File (PDF, PPT, DOC, etc.)</label>
                <input type="file" name="material_file" required>
                <button type="submit" name="upload_material" class="btn">Upload</button>
            </form>
        </div>
        <?php endif; ?>
        <?php if(count($materials) === 0): ?>
            <div class="empty"><div class="icon">📭</div><p>No study materials yet.</p></div>
        <?php else: ?>
        <div class="item-list">
            <?php foreach($materials as $m): ?>
            <div class="item">
                <div class="item-left">
                    <div class="item-icon">📄</div>
                    <div>
                        <div class="item-title"><?= htmlspecialchars($m['title']) ?></div>
                        <div class="item-meta"><?= date('d M Y', strtotime($m['uploaded_at'] ?? 'now')) ?></div>
                    </div>
                </div>
                <div class="item-actions">
                    <a href="/<?= htmlspecialchars($m['file_path']) ?>" class="btn-dl" download>⬇ Download</a>
                    <?php if($role === 'teacher'): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="material_id" value="<?= $m['id'] ?>">
                        <button type="submit" name="delete_material" class="btn-red">🗑</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ANNOUNCEMENTS TAB -->
    <div class="tab-content <?= $activeTab==='announcements'?'active':'' ?>">
        <?php if($role === 'teacher'): ?>
        <div class="form-card">
            <h3>📢 Post Announcement</h3>
            <form method="post">
                <label>Announcement</label>
                <textarea name="announcement_text" placeholder="Write announcement..." required></textarea>
                <button type="submit" name="post_announcement" class="btn">Post</button>
            </form>
        </div>
        <?php endif; ?>
        <?php if(count($announcements) === 0): ?>
            <div class="empty"><div class="icon">📭</div><p>No announcements yet.</p></div>
        <?php else: ?>
            <?php foreach($announcements as $a): ?>
            <div class="ann-card">
                <div class="ann-text"><?= nl2br(htmlspecialchars($a['announcement_text'])) ?></div>
                <div class="ann-meta">👨‍🏫 <?= htmlspecialchars($a['firstName'].' '.$a['lastName']) ?> · <?= date('d M Y', strtotime($a['created_at'] ?? 'now')) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- HOMEWORK TAB -->
    <div class="tab-content <?= $activeTab==='homework'?'active':'' ?>">
        <?php if($role === 'student'): ?>
        <div class="form-card">
            <h3>📝 Submit Homework</h3>
            <form method="post" enctype="multipart/form-data">
                <label>Title</label>
                <input type="text" name="hw_title" placeholder="e.g. Assignment 1" required>
                <label>File</label>
                <input type="file" name="hw_file" required>
                <button type="submit" name="submit_homework" class="btn">Submit</button>
            </form>
        </div>
        <?php endif; ?>
        <?php if(count($homework) === 0): ?>
            <div class="empty"><div class="icon">📭</div><p><?= $role==='teacher'?'No submissions yet.':'No homework submitted yet.' ?></p></div>
        <?php else: ?>
        <div class="item-list">
            <?php foreach($homework as $hw): ?>
            <div class="item">
                <div class="item-left">
                    <div class="item-icon">📝</div>
                    <div>
                        <div class="item-title"><?= htmlspecialchars($hw['title']) ?></div>
                        <div class="item-meta">
                            <?php if($role === 'teacher'): ?>
                                👤 <?= htmlspecialchars($hw['firstName'].' '.$hw['lastName']) ?> ·
                            <?php endif; ?>
                            <?= date('d M Y', strtotime($hw['submitted_at'] ?? 'now')) ?>
                        </div>
                    </div>
                </div>
                <a href="/<?= htmlspecialchars($hw['file_path']) ?>" class="btn-dl" download>⬇ Download</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- MEETINGS TAB -->
    <div class="tab-content <?= $activeTab==='meetings'?'active':'' ?>">
        <?php if($role === 'teacher'): ?>
        <div class="form-card">
            <h3>📹 Schedule Meeting</h3>
            <form method="post">
                <label>Meeting Title</label>
                <input type="text" name="meeting_title" placeholder="e.g. Chapter 3 Live Session" required>
                <label>Description</label>
                <input type="text" name="meeting_desc" placeholder="Optional description">
                <div class="form-row">
                    <div>
                        <label>Date</label>
                        <input type="date" name="meet_date" required>
                    </div>
                    <div>
                        <label>Time</label>
                        <input type="time" name="meet_time" required>
                    </div>
                </div>
                <button type="submit" name="schedule_meeting" class="btn">Schedule Meeting</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if(count($meetings) === 0): ?>
            <div class="empty"><div class="icon">📭</div><p>No meetings scheduled yet.</p></div>
        <?php else: ?>
            <?php foreach($meetings as $m): ?>
            <div class="meeting-card">
                <div class="meeting-header">
                    <div>
                        <div class="meeting-title"><?= htmlspecialchars($m['title']) ?></div>
                        <?php if($m['description']): ?>
                            <div style="font-size:13px;color:var(--muted);margin-top:3px"><?= htmlspecialchars($m['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if($m['is_live']): ?>
                        <span class="live-badge">🔴 LIVE</span>
                    <?php else: ?>
                        <span class="scheduled-badge">📅 Scheduled</span>
                    <?php endif; ?>
                </div>
                <div class="meeting-info">
                    <span>📅 <?= date('d M Y', strtotime($m['meet_date'])) ?></span>
                    <span>🕐 <?= date('h:i A', strtotime($m['meet_time'])) ?></span>
                    <span>🔗 Room: <?= htmlspecialchars($m['room_name']) ?></span>
                </div>
                <div class="meeting-actions">
                    <?php if($m['is_live'] || $role === 'student' && $m['is_live']): ?>
                        <a href="https://meet.jit.si/<?= htmlspecialchars($m['room_name']) ?>" target="_blank" class="btn-join">🎥 Join Meeting</a>
                    <?php endif; ?>
                    <?php if($role === 'teacher'): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
                            <input type="hidden" name="is_live" value="<?= $m['is_live'] ? 0 : 1 ?>">
                            <button type="submit" name="toggle_live" class="<?= $m['is_live'] ? 'btn-red' : 'btn-green' ?>">
                                <?= $m['is_live'] ? '⏹ Stop' : '▶ Go Live' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ATTENDANCE TAB -->
    <div class="tab-content <?= $activeTab==='attendance'?'active':'' ?>">
        <?php if($role === 'teacher'): ?>
        <div class="form-card">
            <h3>✅ Mark Attendance</h3>
            <form method="post">
                <div class="form-row">
                    <div>
                        <label>Date</label>
                        <input type="date" name="att_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label>Class Label</label>
                        <input type="text" name="att_label" placeholder="e.g. Lecture 1" value="Lecture">
                    </div>
                </div>
                <label>Students</label>
                <?php if(count($classStudents) === 0): ?>
                    <p style="color:var(--muted);font-size:13px">No students in this class yet.</p>
                <?php else: ?>
                    <?php foreach($classStudents as $st): ?>
                    <div class="student-check">
                        <input type="checkbox" name="att_<?= $st['id'] ?>" id="att_<?= $st['id'] ?>" checked style="width:18px;height:18px;accent-color:var(--blue)">
                        <label for="att_<?= $st['id'] ?>" style="font-size:14px;color:var(--text);font-weight:500;margin-bottom:0">
                            <?= htmlspecialchars($st['firstName'].' '.$st['lastName']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <br>
                    <button type="submit" name="mark_attendance" class="btn">✅ Save Attendance</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Attendance Records -->
        <?php if(count($attRecords) > 0): ?>
        <div style="background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:700;color:var(--navy)">📋 Attendance Records</div>
            <table class="att-table">
                <thead><tr><th>Student</th><th>Date</th><th>Label</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach($attRecords as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['student_name']) ?></td>
                        <td><?= date('d M Y', strtotime($a['class_date'])) ?></td>
                        <td><?= htmlspecialchars($a['class_label']) ?></td>
                        <td><?php if($a['status']==='present'): ?><span class="present-pill">✅ Present</span><?php else: ?><span class="absent-pill">❌ Absent</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- STUDENT ATTENDANCE VIEW -->
        <div class="att-stats">
            <div class="att-stat">
                <div class="num"><?= $totalClasses ?></div>
                <div class="lbl">Total Classes</div>
            </div>
            <div class="att-stat">
                <div class="num" style="color:#16a34a"><?= $presentCount ?></div>
                <div class="lbl">Present</div>
            </div>
            <div class="att-stat">
                <div class="<?= $attPercent >= 75 ? 'att-percent good' : ($attPercent >= 60 ? 'att-percent warn' : 'att-percent bad') ?>"><?= $attPercent ?>%</div>
                <div class="lbl">Attendance</div>
            </div>
        </div>

        <?php if($attPercent < 75 && $totalClasses > 0): ?>
            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#d97706;font-weight:500">
                ⚠️ Your attendance is below 75%. Please attend more classes.
            </div>
        <?php endif; ?>

        <?php if(count($myAttendance) === 0): ?>
            <div class="empty"><div class="icon">📭</div><p>No attendance records yet.</p></div>
        <?php else: ?>
        <div style="background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden">
            <table class="att-table">
                <thead><tr><th>Date</th><th>Class</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach($myAttendance as $a): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($a['class_date'])) ?></td>
                        <td><?= htmlspecialchars($a['class_label']) ?></td>
                        <td><?php if($a['status']==='present'): ?><span class="present-pill">✅ Present</span><?php else: ?><span class="absent-pill">❌ Absent</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
