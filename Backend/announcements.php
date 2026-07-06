<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$role      = $_SESSION['role'];
$firstName = $_SESSION['firstName'];
$teacher_id = $_SESSION['user_id'];
$message = '';
$msgType = '';

// Handle new announcement (teacher only) — PRG pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'teacher') {
    $text = trim($_POST['announcement_text']);
    if ($text) {
        $stmt = $conn->prepare("INSERT INTO announcements (teacher_id, teacher_name, announcement_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $teacher_id, $firstName, $text);
        if ($stmt->execute()) {
            $_SESSION['flash_msg']  = "Announcement posted successfully!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_msg']  = "Error: " . $stmt->error;
            $_SESSION['flash_type'] = "error";
        }
        $stmt->close();
    }
    header("Location: announcements.php");
    exit();
}

if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $msgType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// Handle delete (teacher deletes own)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && $role === 'teacher') {
    // handled above via PRG — keep a separate check here if needed
}

$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
$rows = [];
while ($r = $announcements->fetch_assoc()) $rows[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements – EduPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --navy:#0f1e3d;--blue:#2563eb;--blue2:#1d4ed8;--indigo:#4f46e5;
            --green:#16a34a;--red:#dc2626;
            --bg:#eef2ff;--card:#fff;--border:#dde3f5;
            --text:#1e293b;--muted:#64748b;--radius:16px;
            --shadow:0 4px 28px rgba(37,99,235,.10);
        }
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

        .navbar{background:var(--navy);padding:0 32px;display:flex;align-items:center;height:64px;gap:4px;box-shadow:0 2px 16px rgba(0,0,0,.22)}
        .navbar .brand{font-weight:700;font-size:18px;color:#fff;margin-right:24px;letter-spacing:-.4px}
        .navbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;padding:7px 14px;border-radius:8px;transition:.18s}
        .navbar a:hover,.navbar a.active{background:rgba(255,255,255,.13);color:#fff}
        .navbar .spacer{flex:1}

        .page{max-width:820px;margin:38px auto;padding:0 20px 80px}
        .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
        .page-header h1{font-size:26px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:10px}

        .alert{padding:14px 18px;border-radius:var(--radius);font-size:14px;font-weight:500;margin-bottom:22px;display:flex;align-items:center;gap:10px;animation:slideDown .3s ease}
        .alert.success{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
        .alert.error{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}
        @keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

        /* COMPOSE CARD */
        .compose-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:26px 30px;margin-bottom:36px}
        .compose-card h2{font-size:16px;font-weight:700;color:var(--navy);margin-bottom:16px}
        .compose-card textarea{width:100%;border:1.5px solid var(--border);border-radius:10px;padding:13px 16px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text);background:#f8faff;resize:vertical;min-height:100px;outline:none;transition:.18s}
        .compose-card textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.12);background:#fff}
        .compose-footer{display:flex;justify-content:flex-end;margin-top:14px}
        .btn-primary{background:var(--blue);color:#fff;border:none;padding:10px 22px;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;display:inline-flex;align-items:center;gap:7px;transition:.18s;box-shadow:0 2px 10px rgba(37,99,235,.28)}
        .btn-primary:hover{background:var(--blue2)}

        /* SECTION TITLE */
        .section-title{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .badge{background:var(--blue);color:#fff;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px}

        /* ANNOUNCEMENT CARDS */
        .ann-list{display:flex;flex-direction:column;gap:16px}
        .ann-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:20px 24px;animation:fadeUp .35s ease both;transition:box-shadow .2s,transform .2s}
        .ann-card:hover{box-shadow:0 8px 36px rgba(37,99,235,.14);transform:translateY(-2px)}
        @keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

        .ann-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
        .ann-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--indigo));display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;flex-shrink:0}
        .ann-meta{flex:1;min-width:0}
        .ann-author{font-size:14px;font-weight:700;color:var(--navy)}
        .ann-time{font-size:12px;color:var(--muted);margin-top:1px}
        .ann-body{font-size:14px;color:#334155;line-height:1.65;white-space:pre-wrap}

        .empty{text-align:center;padding:60px 20px;color:var(--muted)}
        .empty .icon{font-size:52px;margin-bottom:12px}

        @media(max-width:600px){
            .page-header{flex-direction:column;align-items:flex-start;gap:8px}
            .compose-card{padding:18px}
        }
    </style>
</head>
<body>

<nav class="navbar">
    <span class="brand">📚 EduPortal</span>
    <?php if($role === 'teacher'): ?>
        <a href="teacher-dashboard.php">Home</a>
        <a href="study-materials.php">Materials</a>
        <a href="homework.php">Homework</a>
        <a href="meetings.php">Meetings</a>
        <a href="announcements.php" class="active">Announcements</a>
        <a href="attendance.php">Attendance</a>
    <?php else: ?>
        <a href="homepage.php">Home</a>
        <a href="study-materials.php">Materials</a>
        <a href="homework.php">Homework</a>
        <a href="meetings.php">Meetings</a>
        <a href="announcements.php" class="active">Announcements</a>
        <a href="attendance.php">Announcements</a>
    <?php endif; ?>
    <div class="spacer"></div>
    <a href="logout.php">👋 Logout</a>
</nav>

<div class="page">

    <?php if($message): ?>
        <div class="alert <?= $msgType ?>">
            <?= $msgType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h1>📢 Announcements</h1>
    </div>

    <?php if($role === 'teacher'): ?>
    <div class="compose-card">
        <h2>✏️ Post a New Announcement</h2>
        <form method="POST">
            <textarea name="announcement_text" placeholder="Write your announcement here… (supports multiple lines)" required></textarea>
            <div class="compose-footer">
                <button type="submit" class="btn-primary">📢 Post Announcement</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="section-title">
        🗂️ All Announcements
        <span class="badge"><?= count($rows) ?></span>
    </div>

    <?php if(empty($rows)): ?>
        <div class="empty">
            <div class="icon">📭</div>
            <p>No announcements yet.</p>
        </div>
    <?php else: ?>
    <div class="ann-list">
        <?php foreach($rows as $i => $row): ?>
        <div class="ann-card" style="animation-delay:<?= $i*.06 ?>s">
            <div class="ann-header">
                <div class="ann-avatar"><?= strtoupper(substr($row['teacher_name'], 0, 1)) ?></div>
                <div class="ann-meta">
                    <div class="ann-author">🎓 <?= htmlspecialchars($row['teacher_name']) ?></div>
                    <div class="ann-time">🕐 <?= date('D, d M Y · g:i A', strtotime($row['created_at'])) ?></div>
                </div>
            </div>
            <div class="ann-body"><?= htmlspecialchars($row['announcement_text']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
<?php $conn->close(); ?>