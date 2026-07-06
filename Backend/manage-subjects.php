<?php
session_start();
include("connect.php");

if(!isset($_SESSION['email']) || $_SESSION['role'] !== 'teacher'){
    header("Location: index.php");
    exit();
}

// Add subject
if(isset($_POST['add_subject'])){
    $name     = trim($_POST['subject_name']);
    $code     = trim($_POST['subject_code']);
    $class_id = (int)$_POST['class_id'];
    $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id) VALUES (?,?,?)");
    $stmt->bind_param("ssi", $name, $code, $class_id);
    $stmt->execute();
    $_SESSION['flash_msg'] = "Subject added!";
    header("Location: manage-subjects.php");
    exit();
}

// Assign teacher to subject
if(isset($_POST['assign_teacher'])){
    $teacher_id = (int)$_POST['teacher_id'];
    $subject_id = (int)$_POST['subject_id'];
    $stmt = $conn->prepare("INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id) VALUES (?,?)");
    $stmt->bind_param("ii", $teacher_id, $subject_id);
    $stmt->execute();
    $_SESSION['flash_msg'] = "Teacher assigned to subject!";
    header("Location: manage-subjects.php");
    exit();
}

// Delete subject
if(isset($_POST['delete_subject'])){
    $sid = (int)$_POST['subject_id'];
    $conn->prepare("DELETE FROM teacher_subjects WHERE subject_id=?")->execute() ;
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $_SESSION['flash_msg'] = "Subject deleted!";
    header("Location: manage-subjects.php");
    exit();
}

$classes  = $conn->query("SELECT * FROM classes ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT s.*, c.class_name FROM subjects s JOIN classes c ON s.class_id=c.id ORDER BY c.id, s.subject_name")->fetch_all(MYSQLI_ASSOC);
$teachers = $conn->query("SELECT id, firstName, lastName FROM users WHERE role='teacher' ORDER BY firstName")->fetch_all(MYSQLI_ASSOC);

// Teacher-subject mapping
$mappings = $conn->query("SELECT ts.subject_id, u.firstName, u.lastName 
    FROM teacher_subjects ts JOIN users u ON ts.teacher_id=u.id")->fetch_all(MYSQLI_ASSOC);
$subjectTeachers = [];
foreach($mappings as $m){
    $subjectTeachers[$m['subject_id']][] = $m['firstName'].' '.$m['lastName'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects – ScholarPoint</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--navy:#0f1e3d;--blue:#2563eb;--indigo:#4f46e5;--bg:#eef2ff;--card:#fff;--border:#dde3f5;--text:#1e293b;--muted:#64748b;--radius:16px;--shadow:0 4px 28px rgba(37,99,235,.10)}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        .navbar{background:var(--navy);padding:0 32px;display:flex;align-items:center;height:64px;gap:4px}
        .navbar .brand{font-weight:700;font-size:18px;color:#fff;margin-right:24px}
        .navbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;padding:7px 14px;border-radius:8px;transition:.18s}
        .navbar a:hover,.navbar a.active{background:rgba(255,255,255,.13);color:#fff}
        .navbar .spacer{flex:1}
        .page{max-width:960px;margin:32px auto;padding:0 20px 80px}
        .page-title{font-size:22px;font-weight:800;color:var(--navy);margin-bottom:24px}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px}
        .form-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:24px}
        .form-card h3{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:16px}
        label{display:block;font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px}
        input[type=text],select{width:100%;padding:9px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:14px;font-family:'DM Sans',sans-serif;margin-bottom:14px;outline:none;transition:.18s}
        input[type=text]:focus,select:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .btn-primary{background:var(--blue);color:#fff;border:none;padding:10px 24px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;width:100%;transition:.2s}
        .btn-primary:hover{background:#1d4ed8}
        .flash{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:12px 18px;border-radius:10px;margin-bottom:20px;font-size:14px;font-weight:500}
        .section-title{font-size:16px;font-weight:700;color:var(--navy);margin:28px 0 14px}
        .table-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;margin-bottom:28px}
        table{width:100%;border-collapse:collapse}
        thead th{padding:11px 20px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;background:#f8faff;border-bottom:1px solid var(--border);text-align:left}
        tbody td{padding:12px 20px;font-size:14px;border-bottom:1px solid #f1f5f9}
        tbody tr:last-child td{border-bottom:none}
        tbody tr:hover td{background:#f8faff}
        .class-pill{display:inline-flex;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
        .class-pill.SE{background:#dbeafe;color:#1d4ed8}
        .class-pill.TE{background:#dcfce7;color:#15803d}
        .class-pill.BE{background:#ede9fe;color:#7c3aed}
        .btn-del{background:none;border:none;color:#dc2626;cursor:pointer;padding:5px 8px;border-radius:8px;transition:.15s}
        .btn-del:hover{background:#fee2e2}
        .teacher-tag{display:inline-block;background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;margin:2px}
        @media(max-width:640px){.grid2{grid-template-columns:1fr}}
    </style>
</head>
<body>
<nav class="navbar">
    <span class="brand">📚 ScholarPoint</span>
    <a href="teacher-dashboard.php">Home</a>
    <a href="manage-subjects.php" class="active">Subjects</a>
    <a href="attendance.php">Attendance</a>
    <a href="announcements.php">Announcements</a>
    <div class="spacer"></div>
    <a href="logout.php">👋 Logout</a>
</nav>

<div class="page">
    <div class="page-title">📚 Manage Subjects</div>

    <?php if(isset($_SESSION['flash_msg'])): ?>
        <div class="flash">✅ <?= htmlspecialchars($_SESSION['flash_msg']) ?></div>
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>

    <div class="grid2">
        <!-- Add Subject -->
        <div class="form-card">
            <h3>➕ Add New Subject</h3>
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

        <!-- Assign Teacher -->
        <div class="form-card">
            <h3>👨‍🏫 Assign Teacher to Subject</h3>
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
                <button type="submit" name="assign_teacher" class="btn-primary">Assign Teacher</button>
            </form>
        </div>
    </div>

    <!-- Subjects List -->
    <?php foreach($classes as $cls): ?>
        <?php $clsSubjects = array_filter($subjects, fn($s) => $s['class_id'] == $cls['id']); ?>
        <?php if(count($clsSubjects) > 0): ?>
        <div class="section-title">
            <span class="class-pill <?= $cls['class_name'] ?>"><?= $cls['class_name'] ?></span>
            &nbsp;Subjects
        </div>
        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Subject</th><th>Code</th><th>Teachers</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach($clsSubjects as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['subject_name']) ?></td>
                        <td><?= htmlspecialchars($s['subject_code'] ?? '-') ?></td>
                        <td>
                            <?php if(isset($subjectTeachers[$s['id']])): ?>
                                <?php foreach($subjectTeachers[$s['id']] as $t): ?>
                                    <span class="teacher-tag">👨‍🏫 <?= htmlspecialchars($t) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:12px">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this subject?')">
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
</body>
</html>
