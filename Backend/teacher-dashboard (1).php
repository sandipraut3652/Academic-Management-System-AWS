<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$firstName  = $_SESSION['firstName'] ?? 'Teacher';
$teacher_id = $_SESSION['user_id'] ?? 0;

// Fetch subjects assigned to this teacher (via teacher_subjects)
$mySubjects = $conn->prepare("
    SELECT s.id, s.subject_name, s.subject_code, c.class_name, c.id as class_id,
           (SELECT COUNT(*) FROM users WHERE class_id = s.class_id AND role='student' AND status='approved') as student_count
    FROM teacher_subjects ts
    JOIN subjects s ON ts.subject_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE ts.teacher_id = ?
    ORDER BY c.id, s.subject_name
");
$mySubjects->bind_param("i", $teacher_id);
$mySubjects->execute();
$mySubjects = $mySubjects->get_result()->fetch_all(MYSQLI_ASSOC);

// Approve student
if(isset($_POST['approve'])){
    $sid = (int)$_POST['student_id'];
    $cid = (int)$_POST['class_id'];
    $stmt = $conn->prepare("UPDATE users SET status='approved', class_id=? WHERE id=?");
    $stmt->bind_param("ii", $cid, $sid);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Student approved successfully!";
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
    $_SESSION['flash_msg']  = "Student rejected and removed.";
    $_SESSION['flash_type'] = "danger";
    header("Location: teacher-dashboard.php");
    exit();
}

// Delete user
if(isset($_POST['delete_user'])){
    $uid = (int)$_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $_SESSION['flash_msg']  = "User deleted.";
    $_SESSION['flash_type'] = "success";
    header("Location: teacher-dashboard.php");
    exit();
}

// Fetch pending students
$pending = $conn->query("SELECT u.id, u.firstName, u.lastName, u.email, c.class_name 
    FROM users u 
    LEFT JOIN classes c ON u.class_id = c.id
    WHERE u.role='student' AND u.status='pending' 
    ORDER BY u.id DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch approved students
$students = $conn->query("SELECT u.id, u.firstName, u.lastName, u.email, c.class_name 
    FROM users u 
    LEFT JOIN classes c ON u.class_id = c.id
    WHERE u.role='student' AND u.status='approved' 
    ORDER BY c.class_name, u.firstName")->fetch_all(MYSQLI_ASSOC);

// Fetch teachers
$teachers = $conn->query("SELECT id, firstName, lastName, email FROM users WHERE role='teacher' ORDER BY firstName")->fetch_all(MYSQLI_ASSOC);

// Fetch classes
$classes = $conn->query("SELECT * FROM classes ORDER BY id")->fetch_all(MYSQLI_ASSOC);
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
        :root{
            --navy:#0f1e3d;--blue:#2563eb;--indigo:#4f46e5;
            --green:#16a34a;--red:#dc2626;--amber:#d97706;
            --bg:#eef2ff;--card:#fff;--border:#dde3f5;
            --text:#1e293b;--muted:#64748b;--radius:16px;
            --shadow:0 4px 28px rgba(37,99,235,.10);
        }
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        .navbar{background:var(--navy);padding:0 32px;display:flex;align-items:center;height:64px;gap:4px;box-shadow:0 2px 16px rgba(0,0,0,.22)}
        .navbar .brand{font-weight:700;font-size:18px;color:#fff;margin-right:24px}
        .navbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;padding:7px 14px;border-radius:8px;transition:.18s}
        .navbar a:hover,.navbar a.active{background:rgba(255,255,255,.13);color:#fff}
        .navbar .spacer{flex:1}
        .hero{background:linear-gradient(135deg,var(--navy) 0%,#1e3a8a 60%,var(--indigo) 100%);padding:44px 32px 52px}
        .hero-inner{max-width:1000px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap}
        .hero-left .eyebrow{font-size:12px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:2px;margin-bottom:8px}
        .hero-left h1{font-size:30px;font-weight:800;color:#fff;margin-bottom:6px}
        .hero-left h1 span{background:linear-gradient(90deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-left .sub{font-size:14px;color:rgba(255,255,255,.55)}
        .hero-stats{display:flex;gap:16px;flex-wrap:wrap}
        .hstat{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:14px 20px;text-align:center}
        .hstat .num{font-size:26px;font-weight:800;color:#fff}
        .hstat .lbl{font-size:11px;font-weight:600;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.8px;margin-top:2px}
        .page{max-width:1000px;margin:32px auto;padding:0 20px 80px}
        .flash-msg{max-width:1000px;margin:18px auto 0;padding:0 20px}
        .alert{padding:12px 18px;border-radius:10px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px}
        .alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
        .alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
        .section-title{font-size:16px;font-weight:700;color:var(--navy);margin:28px 0 14px;display:flex;align-items:center;gap:8px}
        .badge-count{background:var(--blue);color:#fff;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px}
        .badge-pending{background:#fef3c7;color:#d97706;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px;border:1px solid #fde68a}
        .table-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;margin-bottom:28px}
        .table-head{padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
        .table-head h3{font-size:15px;font-weight:700;color:var(--navy)}
        table{width:100%;border-collapse:collapse}
        thead th{padding:11px 20px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;background:#f8faff;border-bottom:1px solid var(--border);text-align:left}
        tbody td{padding:13px 20px;font-size:14px;color:var(--text);border-bottom:1px solid #f1f5f9}
        tbody tr:last-child td{border-bottom:none}
        tbody tr:hover td{background:#f8faff}
        .avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--indigo));display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;margin-right:10px;vertical-align:middle}
        .name-cell{display:flex;align-items:center}
        .class-pill{display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
        .class-pill.SE{background:#dbeafe;color:#1d4ed8}
        .class-pill.TE{background:#dcfce7;color:#15803d}
        .class-pill.BE{background:#ede9fe;color:#7c3aed}
        .btn-approve{background:#dcfce7;color:#15803d;border:none;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s}
        .btn-approve:hover{background:#15803d;color:#fff}
        .btn-reject{background:#fee2e2;color:#dc2626;border:none;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;margin-left:6px}
        .btn-reject:hover{background:#dc2626;color:#fff}
        .btn-delete{background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:6px 8px;border-radius:8px;transition:.15s}
        .btn-delete:hover{background:#fee2e2}
        .cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:32px}
        .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:22px 16px;text-align:center;cursor:pointer;transition:.2s;text-decoration:none;color:inherit;display:flex;flex-direction:column;align-items:center;gap:10px}
        .card:hover{transform:translateY(-4px);box-shadow:0 10px 40px rgba(37,99,235,.18)}
        .card-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px}
        .card-icon.blue{background:linear-gradient(135deg,#dbeafe,#bfdbfe)}
        .card-icon.green{background:linear-gradient(135deg,#dcfce7,#bbf7d0)}
        .card-icon.purple{background:linear-gradient(135deg,#ede9fe,#ddd6fe)}
        .card-icon.orange{background:linear-gradient(135deg,#ffedd5,#fed7aa)}
        .card-icon.teal{background:linear-gradient(135deg,#ccfbf1,#99f6e4)}
        .card h3{font-size:13px;font-weight:700;color:var(--navy)}
        .card-arrow{font-size:12px;color:var(--blue);font-weight:600}
        select.class-assign{padding:5px 10px;border-radius:8px;border:1.5px solid var(--border);font-size:13px;margin-right:6px}
        .empty-state{text-align:center;padding:32px;color:var(--muted);font-size:14px}

        /* SUBJECT CARDS */
        .subject-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:18px;margin-bottom:36px}
        .subject-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:22px 20px;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:10px;transition:.2s;cursor:pointer}
        .subject-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(37,99,235,.18);border-color:var(--blue)}
        .subject-card-top{display:flex;align-items:center;justify-content:space-between}
        .subject-class-pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
        .subject-class-pill.SE{background:#dbeafe;color:#1d4ed8}
        .subject-class-pill.TE{background:#dcfce7;color:#15803d}
        .subject-class-pill.BE{background:#ede9fe;color:#7c3aed}
        .subject-icon-big{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);display:flex;align-items:center;justify-content:center;font-size:20px}
        .subject-card h3{font-size:15px;font-weight:700;color:var(--navy);margin:0}
        .subject-card .code{font-size:12px;color:var(--muted);font-family:monospace;background:#f1f5f9;padding:2px 8px;border-radius:6px;display:inline-block}
        .subject-card-footer{display:flex;align-items:center;justify-content:space-between;margin-top:4px}
        .student-count{font-size:12px;color:var(--muted);font-weight:500}
        .open-arrow{font-size:13px;color:var(--blue);font-weight:700}
        .subject-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
        .subject-tab-badge{font-size:11px;background:#f1f5ff;color:#4f46e5;padding:3px 9px;border-radius:8px;font-weight:600}
    </style>
</head>
<body>
<nav class="navbar">
    <span class="brand">📚 ScholarPoint</span>
    <a href="teacher-dashboard.php" class="active">Home</a>
    <a href="study-materials.php">Materials</a>
    <a href="homework.php">Homework</a>
    <a href="meetings.php">Meetings</a>
    <a href="announcements.php">Announcements</a>
    <a href="attendance.php">Attendance</a>
    <a href="quizzes.php">Quiz</a>
    <a href="manage-subjects.php">Subjects</a>
    <div class="spacer"></div>
    <a href="logout.php">👋 Logout</a>
</nav>

<div class="flash-msg">
    <?php if(isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'success' ?>">
            <i class="fas fa-<?= ($_SESSION['flash_type'] ?? 'success') === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($_SESSION['flash_msg']) ?>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>
</div>

<div class="hero">
    <div class="hero-inner">
        <div class="hero-left">
            <div class="eyebrow">Teacher Dashboard</div>
            <h1>Welcome, <span><?= htmlspecialchars($firstName) ?></span> 🎓</h1>
            <div class="sub">E&TC Department · <?= date('l, d F Y') ?></div>
        </div>
        <div class="hero-stats">
            <div class="hstat">
                <div class="num"><?= count($pending) ?></div>
                <div class="lbl">Pending</div>
            </div>
            <div class="hstat">
                <div class="num"><?= count($students) ?></div>
                <div class="lbl">Students</div>
            </div>
            <div class="hstat">
                <div class="num"><?= count($teachers) ?></div>
                <div class="lbl">Teachers</div>
            </div>
        </div>
    </div>
</div>

<div class="page">
    <!-- MY SUBJECTS -->
    <div class="section-title">
        📚 My Subjects
        <span class="badge-count"><?= count($mySubjects) ?></span>
    </div>
    <?php if(count($mySubjects) === 0): ?>
        <div class="table-card"><div class="empty-state">📭 HOD ne abhi koi subject assign nahi kela. HOD la contact kara.</div></div>
    <?php else: ?>
    <div class="subject-cards">
        <?php foreach($mySubjects as $sub): ?>
        <a href="subject-view.php?subject_id=<?= $sub['id'] ?>" class="subject-card">
            <div class="subject-card-top">
                <div class="subject-icon-big">📗</div>
                <span class="subject-class-pill <?= htmlspecialchars($sub['class_name']) ?>"><?= htmlspecialchars($sub['class_name']) ?></span>
            </div>
            <h3><?= htmlspecialchars($sub['subject_name']) ?></h3>
            <span class="code"><?= htmlspecialchars($sub['subject_code'] ?? 'N/A') ?></span>
            <div class="subject-tabs">
                <span class="subject-tab-badge">👥 Students</span>
                <span class="subject-tab-badge">✅ Attendance</span>
                <span class="subject-tab-badge">📖 Materials</span>
                <span class="subject-tab-badge">📢 Notice</span>
            </div>
            <div class="subject-card-footer">
                <span class="student-count">👨‍🎓 <?= $sub['student_count'] ?> students</span>
                <span class="open-arrow">Open →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="section-title">⚡ Quick Actions</div>
    <div class="cards-grid">
        <a href="study-materials.php" class="card">
            <div class="card-icon blue">📖</div>
            <h3>Study Materials</h3>
            <span class="card-arrow">Manage →</span>
        </a>
        <a href="homework.php" class="card">
            <div class="card-icon green">📝</div>
            <h3>Assignments</h3>
            <span class="card-arrow">Review →</span>
        </a>
        <a href="meetings.php" class="card">
            <div class="card-icon purple">📹</div>
            <h3>Meetings</h3>
            <span class="card-arrow">Schedule →</span>
        </a>
        <a href="announcements.php" class="card">
            <div class="card-icon orange">📢</div>
            <h3>Announcements</h3>
            <span class="card-arrow">Post →</span>
        </a>
        <a href="manage-subjects.php" class="card">
            <div class="card-icon teal">📚</div>
            <h3>Manage Subjects</h3>
            <span class="card-arrow">Setup →</span>
        </a>
    </div>

    <!-- PENDING STUDENTS -->
    <div class="section-title">
        🔔 Pending Approvals
        <?php if(count($pending) > 0): ?>
            <span class="badge-pending"><?= count($pending) ?> waiting</span>
        <?php endif; ?>
    </div>
    <div class="table-card">
        <?php if(count($pending) === 0): ?>
            <div class="empty-state">✅ No pending approvals</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Requested Class</th>
                    <th>Assign Class & Approve</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pending as $row): ?>
                <tr>
                    <td>
                        <div class="name-cell">
                            <span class="avatar"><?= strtoupper(substr($row['firstName'],0,1).substr($row['lastName'],0,1)) ?></span>
                            <?= htmlspecialchars($row['firstName'].' '.$row['lastName']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <?php if($row['class_name']): ?>
                            <span class="class-pill <?= $row['class_name'] ?>"><?= $row['class_name'] ?></span>
                        <?php else: ?>
                            <span style="color:var(--muted)">Not selected</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline-flex;align-items:center;gap:6px">
                            <input type="hidden" name="student_id" value="<?= $row['id'] ?>">
                            <select name="class_id" class="class-assign" required>
                                <?php foreach($classes as $cls): ?>
                                    <option value="<?= $cls['id'] ?>" <?= $row['class_name'] === $cls['class_name'] ? 'selected' : '' ?>>
                                        <?= $cls['class_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="approve" class="btn-approve">✅ Approve</button>
                            <button type="submit" name="reject" class="btn-reject" onclick="return confirm('Reject this student?')">❌ Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- APPROVED STUDENTS -->
    <div class="section-title">
        👥 Approved Students
        <span class="badge-count"><?= count($students) ?></span>
    </div>
    <div class="table-card">
        <?php if(count($students) === 0): ?>
            <div class="empty-state">No approved students yet</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Class</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $row): ?>
                <tr>
                    <td>
                        <div class="name-cell">
                            <span class="avatar"><?= strtoupper(substr($row['firstName'],0,1).substr($row['lastName'],0,1)) ?></span>
                            <?= htmlspecialchars($row['firstName'].' '.$row['lastName']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><span class="class-pill <?= $row['class_name'] ?>"><?= $row['class_name'] ?? 'N/A' ?></span></td>
                    <td>
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete this student?')">
                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="delete_user" class="btn-delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- TEACHERS -->
    <div class="section-title">
        🧑‍🏫 Teachers
        <span class="badge-count"><?= count($teachers) ?></span>
    </div>
    <div class="table-card">
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach($teachers as $row): ?>
                <tr>
                    <td>
                        <div class="name-cell">
                            <span class="avatar"><?= strtoupper(substr($row['firstName'],0,1).substr($row['lastName'],0,1)) ?></span>
                            <?= htmlspecialchars($row['firstName'].' '.$row['lastName']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete this teacher?')">
                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="delete_user" class="btn-delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
