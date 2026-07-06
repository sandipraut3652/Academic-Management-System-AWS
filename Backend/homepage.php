<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}
if ($_SESSION['role'] === 'student' && ($_SESSION['status'] ?? '') === 'pending') {
    header("Location: pending.php");
    exit();
}
if ($_SESSION['role'] === 'teacher') {
    header("Location: teacher-dashboard.php");
    exit();
}

$firstName  = $_SESSION['firstName'] ?? 'Student';
$class_id   = $_SESSION['class_id'] ?? null;
$student_id = $_SESSION['user_id'] ?? null;

// Get class name
$className = 'N/A';
if($class_id){
    $r = $conn->prepare("SELECT class_name FROM classes WHERE id=?");
    $r->bind_param("i", $class_id);
    $r->execute();
    if($row = $r->get_result()->fetch_assoc()) $className = $row['class_name'];
}

// Get subjects for student's class with teacher names + subject id
$subjects = [];
if($class_id){
    $sq = $conn->prepare("
        SELECT s.id, s.subject_name, s.subject_code,
               GROUP_CONCAT(CONCAT(u.firstName, ' ', u.lastName) SEPARATOR ', ') as teachers
        FROM subjects s
        LEFT JOIN teacher_subjects ts ON s.id = ts.subject_id
        LEFT JOIN users u ON ts.teacher_id = u.id
        WHERE s.class_id = ?
        GROUP BY s.id
        ORDER BY s.subject_name
    ");
    $sq->bind_param("i", $class_id);
    $sq->execute();
    $subjects = $sq->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Stats
$announcementsCount = $conn->query("SELECT COUNT(*) as c FROM subject_announcements")->fetch_assoc()['c'] ?? 0;
$materialsCount     = $conn->query("SELECT COUNT(*) as c FROM subject_materials")->fetch_assoc()['c'] ?? 0;
$meetingsCount      = $conn->query("SELECT COUNT(*) as c FROM meetings WHERE is_live=1")->fetch_assoc()['c'] ?? 0;
$subjectsCount      = count($subjects);

$classColors = ['SE' => '#1d4ed8', 'TE' => '#15803d', 'BE' => '#7c3aed'];
$classBg     = ['SE' => '#dbeafe', 'TE' => '#dcfce7', 'BE' => '#ede9fe'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScholarPoint – Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--navy:#0f1e3d;--blue:#2563eb;--indigo:#4f46e5;--bg:#eef2ff;--card:#fff;--border:#dde3f5;--text:#1e293b;--muted:#64748b;--radius:16px;--shadow:0 4px 28px rgba(37,99,235,.10)}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        .navbar{background:var(--navy);padding:0 32px;display:flex;align-items:center;height:64px;gap:4px;box-shadow:0 2px 16px rgba(0,0,0,.22)}
        .navbar .brand{font-weight:700;font-size:18px;color:#fff;margin-right:24px}
        .navbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;padding:7px 14px;border-radius:8px;transition:.18s}
        .navbar a:hover,.navbar a.active{background:rgba(255,255,255,.13);color:#fff}
        .navbar .spacer{flex:1}
        .hero{background:linear-gradient(135deg,var(--navy) 0%,#1e3a8a 60%,var(--indigo) 100%);padding:40px 32px 48px;position:relative;overflow:hidden}
        .hero-inner{max-width:980px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;position:relative}
        .hero-left .eyebrow{font-size:12px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:2px;margin-bottom:8px}
        .hero-left h1{font-size:28px;font-weight:800;color:#fff;margin-bottom:6px}
        .hero-left h1 span{background:linear-gradient(90deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-left .sub{font-size:14px;color:rgba(255,255,255,.55)}
        .hero-badges{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
        .hero-badge{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.85);padding:5px 14px;border-radius:20px;font-size:13px;font-weight:500}
        .class-badge{background:linear-gradient(135deg,<?= $classBg[$className] ?? '#dbeafe' ?>,<?= $classColors[$className] ?? '#1d4ed8' ?>22);border:2px solid <?= $classBg[$className] ?? '#dbeafe' ?>;padding:16px 28px;border-radius:16px;text-align:center}
        .class-badge .lbl{font-size:11px;color:rgba(255,255,255,.6);font-weight:600;text-transform:uppercase;letter-spacing:1px}
        .class-badge .cls{font-size:44px;font-weight:800;color:#fff;line-height:1}
        .class-badge .dept{font-size:11px;color:rgba(255,255,255,.6);margin-top:2px}
        .page{max-width:980px;margin:28px auto;padding:0 20px 80px}
        .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:32px}
        .stat-card{background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:18px 20px;box-shadow:var(--shadow)}
        .stat-card .lbl{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}
        .stat-card .val{font-size:30px;font-weight:800;color:var(--navy)}
        .stat-card .sub{font-size:12px;color:var(--muted);margin-top:4px}
        .section-title{font-size:16px;font-weight:700;color:var(--navy);margin:0 0 16px;display:flex;align-items:center;gap:8px}
        .badge{background:var(--blue);color:#fff;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px}

        /* SUBJECT CARDS - Clickable */
        .subjects-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-bottom:32px}
        .subject-card{background:var(--card);border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow);padding:24px 20px;cursor:pointer;transition:.2s;text-decoration:none;color:inherit;display:block}
        .subject-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(37,99,235,.18);border-color:var(--blue)}
        .subject-card .sub-icon{width:54px;height:54px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:14px;background:<?= $classBg[$className] ?? '#dbeafe' ?>}
        .subject-card h3{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:4px}
        .subject-card .code{font-size:12px;color:var(--muted);font-family:monospace;background:#f1f5f9;padding:2px 8px;border-radius:6px;display:inline-block;margin-bottom:10px}
        .subject-card .teacher-name{font-size:12px;color:var(--muted);margin-bottom:10px}
        .subject-card .go{font-size:12px;color:var(--blue);font-weight:700}
        .subject-card .tabs-preview{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
        .subject-card .mini-tab{background:#f1f5f9;color:var(--muted);font-size:10px;font-weight:600;padding:2px 8px;border-radius:8px}
        .empty-state{background:var(--card);border-radius:var(--radius);padding:48px;text-align:center;color:var(--muted);border:1px solid var(--border)}
        .empty-state .icon{font-size:48px;margin-bottom:12px}

        /* QUICK ACCESS */
        .cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:32px}
        .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:20px 14px;text-align:center;cursor:pointer;transition:.2s;text-decoration:none;color:inherit;display:flex;flex-direction:column;align-items:center;gap:10px}
        .card:hover{transform:translateY(-3px);box-shadow:0 10px 36px rgba(37,99,235,.16)}
        .card-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px}
        .card-icon.blue{background:linear-gradient(135deg,#dbeafe,#bfdbfe)}
        .card-icon.purple{background:linear-gradient(135deg,#ede9fe,#ddd6fe)}
        .card-icon.teal{background:linear-gradient(135deg,#ccfbf1,#99f6e4)}
        .card h3{font-size:13px;font-weight:700;color:var(--navy)}
    </style>
</head>
<body>
<nav class="navbar">
    <span class="brand">📚 ScholarPoint</span>
    <a href="homepage.php" class="active">Home</a>
    <a href="meetings.php">Meetings</a>
    <a href="attendance.php">Attendance</a>
    <div class="spacer"></div>
    <a href="logout.php">👋 Logout</a>
</nav>

<div class="hero">
    <div class="hero-inner">
        <div class="hero-left">
            <div class="eyebrow">Student Dashboard</div>
            <h1>Hello, <span><?= htmlspecialchars($firstName) ?></span> 👋</h1>
            <div class="sub">Electronics & Computer Engineering — ScholarPoint</div>
            <div class="hero-badges">
                <span class="hero-badge">📅 <?= date('l, d F Y') ?></span>
                <span class="hero-badge">🎒 Student</span>
                <span class="hero-badge">🏫 Class: <?= htmlspecialchars($className) ?></span>
            </div>
        </div>
        <div class="class-badge">
            <div class="lbl">Your Class</div>
            <div class="cls"><?= htmlspecialchars($className) ?></div>
            <div class="dept">E&TC Dept.</div>
        </div>
    </div>
</div>

<div class="page">
    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="lbl">📚 Subjects</div>
            <div class="val"><?= $subjectsCount ?></div>
            <div class="sub">This semester</div>
        </div>
        <div class="stat-card">
            <div class="lbl">📖 Materials</div>
            <div class="val"><?= $materialsCount ?></div>
            <div class="sub">Available</div>
        </div>
        <div class="stat-card">
            <div class="lbl">📢 Notices</div>
            <div class="val"><?= $announcementsCount ?></div>
            <div class="sub">Announcements</div>
        </div>
        <div class="stat-card">
            <div class="lbl">🔴 Live</div>
            <div class="val"><?= $meetingsCount ?></div>
            <div class="sub">Live meetings</div>
        </div>
    </div>

    <!-- MY SUBJECTS - CLICKABLE CARDS -->
    <div class="section-title">
        📚 My Subjects — <?= htmlspecialchars($className) ?>
        <span class="badge"><?= $subjectsCount ?></span>
    </div>

    <?php if(count($subjects) === 0): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>No subjects added yet for your class.</p>
            <p style="margin-top:8px;font-size:12px;color:var(--muted)">Teacher will add subjects soon.</p>
        </div>
    <?php else: ?>
    <div class="subjects-grid">
        <?php foreach($subjects as $sub): ?>
        <a href="subject.php?id=<?= $sub['id'] ?>" class="subject-card">
            <div class="sub-icon">📗</div>
            <h3><?= htmlspecialchars($sub['subject_name']) ?></h3>
            <?php if($sub['subject_code']): ?>
                <div class="code"><?= htmlspecialchars($sub['subject_code']) ?></div>
            <?php endif; ?>
            <?php if($sub['teachers']): ?>
                <div class="teacher-name">👨‍🏫 <?= htmlspecialchars($sub['teachers']) ?></div>
            <?php endif; ?>
            <div class="tabs-preview">
                <span class="mini-tab">📖 Materials</span>
                <span class="mini-tab">📢 Announcements</span>
                <span class="mini-tab">📝 Homework</span>
            </div>
            <div class="go" style="margin-top:12px">Open Subject →</div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- QUICK ACCESS -->
    <div class="section-title">⚡ Quick Access</div>
    <div class="cards-grid">
        <a href="meetings.php" class="card">
            <div class="card-icon purple">📹</div>
            <h3>Meetings</h3>
        </a>
        <a href="attendance.php" class="card">
            <div class="card-icon teal">✅</div>
            <h3>Attendance</h3>
        </a>
        <a href="quizzes.php" class="card">
            <div class="card-icon blue">🧠</div>
            <h3>Quiz</h3>
        </a>
    </div>
</div>
</body>
</html>
