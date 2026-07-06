<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$firstName  = $_SESSION['firstName'] ?? 'Teacher';

// Approve student
if(isset($_POST['approve'])){
    $sid = (int)$_POST['student_id'];
    $cid = (int)$_POST['class_id'];
    $stmt = $conn->prepare("UPDATE users SET status='approved', class_id=? WHERE id=?");
    $stmt->bind_param("ii", $cid, $sid);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Student approved!";
    $_SESSION['flash_type'] = "success";
    header("Location: teacher-dashboard.php");
    exit();
}

// Reject student
if(isset($_POST['reject'])){
    $sid = (int)$_POST['student_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='student'");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Student rejected!";
    $_SESSION['flash_type'] = "danger";
    header("Location: teacher-dashboard.php");
    exit();
}

// Fetch pending students
$pending = $conn->query("SELECT u.id, u.firstName, u.lastName, u.email, c.class_name, u.class_id
    FROM users u
    LEFT JOIN classes c ON u.class_id = c.id
    WHERE u.role='student' AND u.status='pending'
    ORDER BY u.id DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch classes
$classes = $conn->query("SELECT * FROM classes ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// Fetch teacher's assigned subjects grouped by class
$mySubjects = $conn->query("
    SELECT s.id, s.subject_name, s.subject_code, s.class_id, c.class_name
    FROM teacher_subjects ts
    JOIN subjects s ON ts.subject_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE ts.teacher_id = $teacher_id
    ORDER BY c.id, s.subject_name
")->fetch_all(MYSQLI_ASSOC);

// Group subjects by class
$subjectsByClass = [];
foreach($mySubjects as $sub){
    $subjectsByClass[$sub['class_name']][] = $sub;
}

// Stats
$totalSubjects = count($mySubjects);
$totalPending  = count($pending);
$totalStudents = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student' AND status='approved'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard – ScholarPoint</title>
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
        .hero{background:linear-gradient(135deg,var(--navy) 0%,#1e3a8a 60%,var(--indigo) 100%);padding:40px 32px 48px}
        .hero-inner{max-width:980px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
        .hero-left .eyebrow{font-size:12px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:2px;margin-bottom:8px}
        .hero-left h1{font-size:28px;font-weight:800;color:#fff;margin-bottom:6px}
        .hero-left h1 span{background:linear-gradient(90deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-left .sub{font-size:14px;color:rgba(255,255,255,.55)}
        .hero-stats{display:flex;gap:12px;flex-wrap:wrap}
        .hstat{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:12px 20px;text-align:center}
        .hstat .num{font-size:24px;font-weight:800;color:#fff}
        .hstat .lbl{font-size:11px;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.8px}
        .page{max-width:980px;margin:28px auto;padding:0 20px 80px}
        .flash{padding:12px 18px;border-radius:10px;font-size:14px;font-weight:500;margin-bottom:20px}
        .flash.success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
        .flash.danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
        .section-title{font-size:16px;font-weight:700;color:var(--navy);margin:0 0 14px;display:flex;align-items:center;gap:8px}
        .badge{background:var(--blue);color:#fff;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px}
        .badge-warn{background:#fef3c7;color:#d97706;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px;border:1px solid #fde68a}

        /* CLASS TABS */
        .class-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
        .class-tab{padding:9px 24px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;border:2px solid var(--border);background:var(--card);color:var(--muted);transition:.18s}
        .class-tab:hover{border-color:var(--blue);color:var(--blue)}
        .class-tab.SE.active{background:#1d4ed8;color:#fff;border-color:#1d4ed8}
        .class-tab.TE.active{background:#15803d;color:#fff;border-color:#15803d}
        .class-tab.BE.active{background:#7c3aed;color:#fff;border-color:#7c3aed}
        .class-content{display:none}
        .class-content.active{display:block}

        /* SUBJECT CARDS */
        .subjects-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-bottom:28px}
        .subject-card{background:var(--card);border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow);padding:24px 20px;cursor:pointer;transition:.2s;text-decoration:none;color:inherit;display:block}
        .subject-card:hover{transform:translateY(-4px);box-shadow:0 10px 40px rgba(37,99,235,.18);border-color:var(--blue)}
        .subject-card .sub-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:14px}
        .subject-card .sub-icon.SE{background:linear-gradient(135deg,#dbeafe,#bfdbfe)}
        .subject-card .sub-icon.TE{background:linear-gradient(135deg,#dcfce7,#bbf7d0)}
        .subject-card .sub-icon.BE{background:linear-gradient(135deg,#ede9fe,#ddd6fe)}
        .subject-card h3{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:4px}
        .subject-card .code{font-size:12px;color:var(--muted);font-family:monospace;background:#f1f5f9;padding:2px 8px;border-radius:6px;display:inline-block;margin-bottom:12px}
        .subject-card .go{font-size:12px;color:var(--blue);font-weight:600}
        .no-subjects{background:var(--card);border-radius:var(--radius);padding:40px;text-align:center;color:var(--muted);border:1px solid var(--border)}
        .no-subjects .icon{font-size:40px;margin-bottom:12px}

        /* PENDING TABLE */
        .table-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;margin-bottom:24px}
        table{width:100%;border-collapse:collapse}
        thead th{padding:10px 18px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;background:#f8faff;border-bottom:1px solid var(--border);text-align:left}
        tbody td{padding:12px 18px;font-size:14px;border-bottom:1px solid #f1f5f9}
        tbody tr:last-child td{border-bottom:none}
        tbody tr:hover td{background:#f8faff}
        .avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--indigo));display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;margin-right:8px;vertical-align:middle}
        .class-pill{display:inline-flex;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
        .class-pill.SE{background:#dbeafe;color:#1d4ed8}
        .class-pill.TE{background:#dcfce7;color:#15803d}
        .class-pill.BE{background:#ede9fe;color:#7c3aed}
        .btn-approve{background:#dcfce7;color:#15803d;border:none;padding:5px 12px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s}
        .btn-approve:hover{background:#15803d;color:#fff}
        .btn-reject{background:#fee2e2;color:#dc2626;border:none;padding:5px 12px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;margin-left:6px;transition:.2s}
        .btn-reject:hover{background:#dc2626;color:#fff}
        select.class-assign{padding:5px 10px;border-radius:8px;border:1.5px solid var(--border);font-size:13px;margin-right:6px;width:auto}
        .empty{text-align:center;padding:28px;color:var(--muted);font-size:14px}
    </style>
</head>
<body>
<nav class="navbar">
    <span class="brand">📚 ScholarPoint</span>
    <a href="teacher-dashboard.php" class="active">Home</a>
    <a href="announcements.php">Announcements</a>
    <a href="meetings.php">Meetings</a>
    <a href="attendance.php">Attendance</a>
    <div class="spacer"></div>
    <a href="logout.php">👋 Logout</a>
</nav>

<div class="hero">
    <div class="hero-inner">
        <div class="hero-left">
            <div class="eyebrow">Teacher Dashboard</div>
            <h1>Welcome, <span><?= htmlspecialchars($firstName) ?></span> 🎓</h1>
            <div class="sub">E&TC Department · <?= date('l, d F Y') ?></div>
        </div>
        <div class="hero-stats">
            <div class="hstat"><div class="num"><?= $totalSubjects ?></div><div class="lbl">My Subjects</div></div>
            <div class="hstat"><div class="num"><?= $totalStudents ?></div><div class="lbl">Students</div></div>
            <div class="hstat"><div class="num"><?= $totalPending ?></div><div class="lbl">Pending</div></div>
        </div>
    </div>
</div>

<div class="page">
    <?php if(isset($_SESSION['flash_msg'])): ?>
        <div class="flash <?= $_SESSION['flash_type'] ?? 'success' ?>">
            <?= htmlspecialchars($_SESSION['flash_msg']) ?>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <!-- MY SUBJECTS BY CLASS -->
    <div class="section-title">
        📚 My Subjects
        <span class="badge"><?= $totalSubjects ?></span>
    </div>

    <?php if(count($mySubjects) === 0): ?>
        <div class="no-subjects">
            <div class="icon">📭</div>
            <p>No subjects assigned yet.</p>
            <p style="font-size:12px;margin-top:6px">HOD will assign subjects to you.</p>
        </div>
    <?php else: ?>

    <!-- Class Tabs -->
    <div class="class-tabs">
        <?php $firstClass = true; ?>
        <?php foreach($subjectsByClass as $className => $subs): ?>
            <div class="class-tab <?= $className ?> <?= $firstClass ? 'active' : '' ?>"
                 onclick="showClass('<?= $className ?>', this)">
                <?= $className ?>
                <span style="font-size:11px;opacity:.7">(<?= count($subs) ?>)</span>
            </div>
            <?php $firstClass = false; ?>
        <?php endforeach; ?>
    </div>

    <!-- Subjects Grid per Class -->
    <?php $firstClass = true; ?>
    <?php foreach($subjectsByClass as $className => $subs): ?>
        <div id="class-<?= $className ?>" class="class-content <?= $firstClass ? 'active' : '' ?>">
            <div class="subjects-grid">
                <?php foreach($subs as $sub): ?>
                <a href="subject.php?id=<?= $sub['id'] ?>" class="subject-card">
                    <div class="sub-icon <?= $className ?>">📗</div>
                    <h3><?= htmlspecialchars($sub['subject_name']) ?></h3>
                    <?php if($sub['subject_code']): ?>
                        <div class="code"><?= htmlspecialchars($sub['subject_code']) ?></div>
                    <?php endif; ?>
                    <div class="go">Open Subject →</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php $firstClass = false; ?>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- PENDING STUDENTS -->
    <?php if(count($pending) > 0): ?>
    <div class="section-title" style="margin-top:32px">
        🔔 Pending Approvals
        <span class="badge-warn"><?= count($pending) ?> waiting</span>
    </div>
    <div class="table-card">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Class</th><th>Assign & Approve</th></tr></thead>
            <tbody>
                <?php foreach($pending as $row): ?>
                <tr>
                    <td>
                        <span class="avatar"><?= strtoupper(substr($row['firstName'],0,1).substr($row['lastName'],0,1)) ?></span>
                        <?= htmlspecialchars($row['firstName'].' '.$row['lastName']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?php if($row['class_name']): ?><span class="class-pill <?= $row['class_name'] ?>"><?= $row['class_name'] ?></span><?php else: ?>—<?php endif; ?></td>
                    <td>
                        <form method="post" style="display:inline-flex;align-items:center;gap:6px">
                            <input type="hidden" name="student_id" value="<?= $row['id'] ?>">
                            <select name="class_id" class="class-assign" required>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $row['class_id'] == $c['id'] ? 'selected' : '' ?>><?= $c['class_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="approve" class="btn-approve">✅ Approve</button>
                            <button type="submit" name="reject" class="btn-reject" onclick="return confirm('Reject?')">❌ Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<script>
function showClass(name, el) {
    document.querySelectorAll('.class-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.class-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('class-' + name).classList.add('active');
    el.classList.add('active');
}
</script>
</body>
</html>
