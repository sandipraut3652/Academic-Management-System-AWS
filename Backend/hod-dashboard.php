<?php
session_start();
include("connect.php");

if(!isset($_SESSION['hod']) || $_SESSION['hod'] !== true){
    header("Location: hod-login.php");
    exit();
}

// Add Teacher
if(isset($_POST['add_teacher'])){
    $firstName = trim($_POST['fName']);
    $lastName  = trim($_POST['lName']);
    $email     = trim($_POST['email']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role      = 'teacher';
    $status    = 'approved';

    $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
    $chk->bind_param("s", $email);
    $chk->execute();
    $chk->store_result();

    if($chk->num_rows > 0){
        $_SESSION['flash_msg']  = "Email already exists!";
        $_SESSION['flash_type'] = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, password, role, status) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $firstName, $lastName, $email, $password, $role, $status);
        $stmt->execute();
        $_SESSION['flash_msg']  = "Teacher added successfully!";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: hod-dashboard.php");
    exit();
}

// Delete Teacher
if(isset($_POST['delete_teacher'])){
    $tid = (int)$_POST['teacher_id'];
    $conn->prepare("DELETE FROM teacher_subjects WHERE teacher_id=?")->execute();
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='teacher'");
    $stmt->bind_param("i", $tid);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Teacher deleted!";
    $_SESSION['flash_type'] = "success";
    header("Location: hod-dashboard.php");
    exit();
}

// Assign Subject to Teacher
if(isset($_POST['assign_subject'])){
    $teacher_id = (int)$_POST['teacher_id'];
    $subject_id = (int)$_POST['subject_id'];
    $stmt = $conn->prepare("INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id) VALUES (?,?)");
    $stmt->bind_param("ii", $teacher_id, $subject_id);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Subject assigned to teacher!";
    $_SESSION['flash_type'] = "success";
    header("Location: hod-dashboard.php");
    exit();
}

// Remove Subject from Teacher
if(isset($_POST['remove_subject'])){
    $teacher_id = (int)$_POST['teacher_id'];
    $subject_id = (int)$_POST['subject_id'];
    $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE teacher_id=? AND subject_id=?");
    $stmt->bind_param("ii", $teacher_id, $subject_id);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Subject removed from teacher!";
    $_SESSION['flash_type'] = "success";
    header("Location: hod-dashboard.php");
    exit();
}

// Add Subject
if(isset($_POST['add_subject'])){
    $name     = trim($_POST['subject_name']);
    $code     = trim($_POST['subject_code']);
    $class_id = (int)$_POST['class_id'];
    $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id) VALUES (?,?,?)");
    $stmt->bind_param("ssi", $name, $code, $class_id);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Subject added!";
    $_SESSION['flash_type'] = "success";
    header("Location: hod-dashboard.php");
    exit();
}

// Delete Subject
if(isset($_POST['delete_subject'])){
    $sid = (int)$_POST['subject_id'];
    $conn->query("DELETE FROM teacher_subjects WHERE subject_id=$sid");
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Subject deleted!";
    $_SESSION['flash_type'] = "success";
    header("Location: hod-dashboard.php");
    exit();
}

// Approve/Reject Student
if(isset($_POST['approve_student'])){
    $sid = (int)$_POST['student_id'];
    $cid = (int)$_POST['class_id'];
    $stmt = $conn->prepare("UPDATE users SET status='approved', class_id=? WHERE id=?");
    $stmt->bind_param("ii", $cid, $sid);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Student approved!";
    $_SESSION['flash_type'] = "success";
    header("Location: hod-dashboard.php");
    exit();
}

if(isset($_POST['reject_student'])){
    $sid = (int)$_POST['student_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='student'");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $_SESSION['flash_msg']  = "Student rejected!";
    $_SESSION['flash_type'] = "danger";
    header("Location: hod-dashboard.php");
    exit();
}

// Fetch data
$teachers  = $conn->query("SELECT * FROM users WHERE role='teacher' ORDER BY firstName")->fetch_all(MYSQLI_ASSOC);
$students  = $conn->query("SELECT u.*, c.class_name FROM users u LEFT JOIN classes c ON u.class_id=c.id WHERE u.role='student' AND u.status='approved' ORDER BY c.class_name, u.firstName")->fetch_all(MYSQLI_ASSOC);
$pending   = $conn->query("SELECT u.*, c.class_name FROM users u LEFT JOIN classes c ON u.class_id=c.id WHERE u.role='student' AND u.status='pending' ORDER BY u.id DESC")->fetch_all(MYSQLI_ASSOC);
$subjects  = $conn->query("SELECT s.*, c.class_name FROM subjects s JOIN classes c ON s.class_id=c.id ORDER BY c.id, s.subject_name")->fetch_all(MYSQLI_ASSOC);
$classes   = $conn->query("SELECT * FROM classes ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// Teacher → Subject mapping
$mappings = $conn->query("SELECT ts.teacher_id, ts.subject_id, s.subject_name, c.class_name FROM teacher_subjects ts JOIN subjects s ON ts.subject_id=s.id JOIN classes c ON s.class_id=c.id")->fetch_all(MYSQLI_ASSOC);
$teacherSubjects = [];
foreach($mappings as $m){
    $teacherSubjects[$m['teacher_id']][] = $m;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard – ScholarPoint</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--navy:#0f1e3d;--blue:#2563eb;--indigo:#4f46e5;--bg:#eef2ff;--card:#fff;--border:#dde3f5;--text:#1e293b;--muted:#64748b;--radius:16px;--shadow:0 4px 28px rgba(37,99,235,.10)}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        .navbar{background:var(--navy);padding:0 32px;display:flex;align-items:center;height:64px;gap:4px}
        .navbar .brand{font-weight:700;font-size:18px;color:#fff;margin-right:24px}
        .navbar .spacer{flex:1}
        .navbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;padding:7px 14px;border-radius:8px;transition:.18s}
        .navbar a:hover{background:rgba(255,255,255,.13);color:#fff}
        .hod-badge{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px}
        .hero{background:linear-gradient(135deg,#1e3a8a 0%,#0f1e3d 100%);padding:36px 32px 44px}
        .hero-inner{max-width:980px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px}
        .hero h1{font-size:28px;font-weight:800;color:#fff}
        .hero h1 span{color:#fbbf24}
        .hero .sub{font-size:14px;color:rgba(255,255,255,.55);margin-top:4px}
        .hero-stats{display:flex;gap:12px;flex-wrap:wrap}
        .hstat{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:12px 20px;text-align:center}
        .hstat .num{font-size:24px;font-weight:800;color:#fff}
        .hstat .lbl{font-size:11px;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.8px}
        .page{max-width:980px;margin:28px auto;padding:0 20px 80px}
        .tabs{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap}
        .tab{padding:9px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:2px solid var(--border);background:var(--card);color:var(--muted);transition:.18s}
        .tab.active,.tab:hover{background:var(--navy);color:#fff;border-color:var(--navy)}
        .tab-content{display:none}
        .tab-content.active{display:block}
        .flash{padding:12px 18px;border-radius:10px;font-size:14px;font-weight:500;margin-bottom:20px}
        .flash.success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
        .flash.danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
        .section-title{font-size:16px;font-weight:700;color:var(--navy);margin:0 0 14px;display:flex;align-items:center;gap:8px}
        .badge{background:var(--blue);color:#fff;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px}
        .badge-warn{background:#fef3c7;color:#d97706;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px;border:1px solid #fde68a}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
        .form-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:24px}
        .form-card h3{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:16px}
        label{display:block;font-size:13px;font-weight:600;color:var(--muted);margin-bottom:5px}
        input[type=text],input[type=email],input[type=password],select{width:100%;padding:9px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:14px;font-family:'DM Sans',sans-serif;margin-bottom:12px;outline:none;transition:.18s}
        input:focus,select:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .btn-primary{background:var(--blue);color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;width:100%;font-family:'DM Sans',sans-serif;transition:.2s}
        .btn-primary:hover{background:#1d4ed8}
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
        .btn-del{background:none;border:none;color:#dc2626;cursor:pointer;padding:5px 8px;border-radius:8px;transition:.15s;font-size:14px}
        .btn-del:hover{background:#fee2e2}
        .btn-approve{background:#dcfce7;color:#15803d;border:none;padding:5px 12px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s}
        .btn-approve:hover{background:#15803d;color:#fff}
        .btn-reject{background:#fee2e2;color:#dc2626;border:none;padding:5px 12px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;margin-left:6px;transition:.2s}
        .btn-reject:hover{background:#dc2626;color:#fff}
        .subject-tag{display:inline-block;background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;margin:2px}
        .remove-btn{background:none;border:none;color:#dc2626;cursor:pointer;font-size:11px;font-weight:700;padding:1px 5px}
        .empty{text-align:center;padding:32px;color:var(--muted);font-size:14px}
        select.class-assign{padding:5px 10px;border-radius:8px;border:1.5px solid var(--border);font-size:13px;margin-right:6px;width:auto}
        @media(max-width:640px){.grid2{grid-template-columns:1fr}}
    </style>
</head>
<body>
<nav class="navbar">
    <span class="brand">📚 ScholarPoint</span>
    <div class="spacer"></div>
    <span class="hod-badge">🏛️ HOD Panel</span>
    &nbsp;
    <a href="hod-logout.php">👋 Logout</a>
</nav>

<div class="hero">
    <div class="hero-inner">
        <div>
            <h1>HOD Dashboard <span>🏛️</span></h1>
            <div class="sub">E&TC Department · Parikrama College of Engineering · <?= date('d F Y') ?></div>
        </div>
        <div class="hero-stats">
            <div class="hstat"><div class="num"><?= count($teachers) ?></div><div class="lbl">Teachers</div></div>
            <div class="hstat"><div class="num"><?= count($students) ?></div><div class="lbl">Students</div></div>
            <div class="hstat"><div class="num"><?= count($pending) ?></div><div class="lbl">Pending</div></div>
            <div class="hstat"><div class="num"><?= count($subjects) ?></div><div class="lbl">Subjects</div></div>
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

    <!-- TABS -->
    <div class="tabs">
        <div class="tab active" onclick="showTab('teachers')">👨‍🏫 Teachers</div>
        <div class="tab" onclick="showTab('subjects')">📚 Subjects</div>
        <div class="tab" onclick="showTab('pending')">🔔 Pending <?php if(count($pending)>0): ?><span class="badge-warn"><?= count($pending) ?></span><?php endif; ?></div>
        <div class="tab" onclick="showTab('students')">👥 Students</div>
    </div>

    <!-- TEACHERS TAB -->
    <div id="tab-teachers" class="tab-content active">
        <div class="grid2">
            <!-- Add Teacher Form -->
            <div class="form-card">
                <h3>➕ Add New Teacher</h3>
                <form method="post">
                    <label>First Name</label>
                    <input type="text" name="fName" placeholder="First Name" required>
                    <label>Last Name</label>
                    <input type="text" name="lName" placeholder="Last Name" required>
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Email" required>
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" name="add_teacher" class="btn-primary">Add Teacher</button>
                </form>
            </div>

            <!-- Assign Subject to Teacher -->
            <div class="form-card">
                <h3>📌 Assign Subject to Teacher</h3>
                <form method="post">
                    <label>Select Teacher</label>
                    <select name="teacher_id" required>
                        <?php foreach($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['firstName'].' '.$t['lastName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Select Subject</label>
                    <select name="subject_id" required>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>">[<?= $s['class_name'] ?>] <?= htmlspecialchars($s['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign_subject" class="btn-primary">Assign Subject</button>
                </form>
            </div>
        </div>

        <!-- Teachers List -->
        <div class="section-title">👨‍🏫 All Teachers <span class="badge"><?= count($teachers) ?></span></div>
        <div class="table-card">
            <?php if(count($teachers) === 0): ?>
                <div class="empty">No teachers added yet</div>
            <?php else: ?>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Assigned Subjects</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach($teachers as $t): ?>
                    <tr>
                        <td>
                            <span class="avatar"><?= strtoupper(substr($t['firstName'],0,1).substr($t['lastName'],0,1)) ?></span>
                            <?= htmlspecialchars($t['firstName'].' '.$t['lastName']) ?>
                        </td>
                        <td><?= htmlspecialchars($t['email']) ?></td>
                        <td>
                            <?php if(isset($teacherSubjects[$t['id']])): ?>
                                <?php foreach($teacherSubjects[$t['id']] as $ts): ?>
                                    <span class="subject-tag">
                                        [<?= $ts['class_name'] ?>] <?= htmlspecialchars($ts['subject_name']) ?>
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="subject_id" value="<?= $ts['subject_id'] ?>">
                                            <button type="submit" name="remove_subject" class="remove-btn" title="Remove">✕</button>
                                        </form>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:12px">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this teacher?')">
                                <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                <button type="submit" name="delete_teacher" class="btn-del"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- SUBJECTS TAB -->
    <div id="tab-subjects" class="tab-content">
        <div class="form-card" style="max-width:480px;margin-bottom:24px">
            <h3>➕ Add Subject</h3>
            <form method="post">
                <label>Subject Name</label>
                <input type="text" name="subject_name" placeholder="e.g. Digital Electronics" required>
                <label>Subject Code</label>
                <input type="text" name="subject_code" placeholder="e.g. DEC301">
                <label>Class</label>
                <select name="class_id" required>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['class_name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_subject" class="btn-primary">Add Subject</button>
            </form>
        </div>

        <?php foreach($classes as $cls): ?>
            <?php $clsSubs = array_filter($subjects, fn($s) => $s['class_id'] == $cls['id']); ?>
            <?php if(count($clsSubs) > 0): ?>
            <div class="section-title"><span class="class-pill <?= $cls['class_name'] ?>"><?= $cls['class_name'] ?></span>&nbsp;Subjects</div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Subject</th><th>Code</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($clsSubs as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['subject_name']) ?></td>
                            <td><?= htmlspecialchars($s['subject_code'] ?? '—') ?></td>
                            <td>
                                <form method="post" style="display:inline" onsubmit="return confirm('Delete subject?')">
                                    <input type="hidden" name="subject_id" value="<?= $s['id'] ?>">
                                    <button type="submit" name="delete_subject" class="btn-del"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- PENDING TAB -->
    <div id="tab-pending" class="tab-content">
        <div class="section-title">🔔 Pending Approvals <span class="badge-warn"><?= count($pending) ?></span></div>
        <div class="table-card">
            <?php if(count($pending) === 0): ?>
                <div class="empty">✅ No pending approvals</div>
            <?php else: ?>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Requested Class</th><th>Assign & Approve</th></tr></thead>
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
                                <button type="submit" name="approve_student" class="btn-approve">✅ Approve</button>
                                <button type="submit" name="reject_student" class="btn-reject" onclick="return confirm('Reject?')">❌ Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- STUDENTS TAB -->
    <div id="tab-students" class="tab-content">
        <div class="section-title">👥 All Students <span class="badge"><?= count($students) ?></span></div>
        <div class="table-card">
            <?php if(count($students) === 0): ?>
                <div class="empty">No approved students yet</div>
            <?php else: ?>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Class</th></tr></thead>
                <tbody>
                    <?php foreach($students as $row): ?>
                    <tr>
                        <td>
                            <span class="avatar"><?= strtoupper(substr($row['firstName'],0,1).substr($row['lastName'],0,1)) ?></span>
                            <?= htmlspecialchars($row['firstName'].' '.$row['lastName']) ?>
                        </td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><span class="class-pill <?= $row['class_name'] ?>"><?= $row['class_name'] ?? 'N/A' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>
