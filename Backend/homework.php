<?php
session_start();
include "connect.php";

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$message = '';
$msgType = '';

// ── STUDENT: upload homework ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'student' && isset($_POST['submit'])) {
    $file           = $_FILES['file'];
    $fileActualExt  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed        = ['jpg','jpeg','pdf','docx','png'];

    if (!in_array($fileActualExt, $allowed)) {
        $message = "File type not allowed. Allowed: JPG, PDF, DOCX, PNG.";
        $msgType = "error";
    } elseif ($file['error'] !== 0) {
        $message = "Upload error. Please try again.";
        $msgType = "error";
    } elseif ($file['size'] > 5000000000000000000) {
        $message = "File too large. Max size is 5 MB.";
        $msgType = "error";
    } else {
        $fileNameNew    = uniqid('', true) . "." . $fileActualExt;
        $fileDestination = 'uploads/' . $fileNameNew;
        if (move_uploaded_file($file['tmp_name'], $fileDestination)) {
            $stmt = $conn->prepare("INSERT INTO assignments (user_id, file_name, file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $file['name'], $fileDestination);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_msg']  = "Homework submitted successfully! ✅";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_msg']  = "Failed to save file. Check server permissions.";
            $_SESSION['flash_type'] = "error";
        }
        header("Location: homework.php");
        exit();
    }
}

if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $msgType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// ── FETCH: students see own submissions; teachers see all ─────────────────
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT file_name, file_path, uploaded_at FROM assignments WHERE user_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT a.id, a.file_name, a.file_path, a.uploaded_at, u.firstName, u.lastName
                            FROM assignments a
                            JOIN users u ON a.user_id = u.id
                            ORDER BY a.uploaded_at DESC");
}
$submissions = [];
while ($r = $result->fetch_assoc()) $submissions[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homework – EduPortal</title>
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

        .page{max-width:860px;margin:38px auto;padding:0 20px 80px}
        .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
        .page-header h1{font-size:26px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:10px}

        .alert{padding:14px 18px;border-radius:var(--radius);font-size:14px;font-weight:500;margin-bottom:22px;display:flex;align-items:center;gap:10px;animation:slideDown .3s ease}
        .alert.success{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
        .alert.error{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}
        @keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

        /* UPLOAD CARD */
        .upload-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:28px 32px;margin-bottom:36px}
        .upload-card h2{font-size:16px;font-weight:700;color:var(--navy);margin-bottom:20px}

        .dropzone{border:2px dashed var(--border);border-radius:12px;padding:32px 20px;text-align:center;background:#f8faff;cursor:pointer;transition:.2s;position:relative}
        .dropzone:hover,.dropzone.dragover{border-color:var(--blue);background:#eff6ff}
        .dropzone .dz-icon{font-size:40px;margin-bottom:10px}
        .dropzone .dz-label{font-size:14px;font-weight:600;color:var(--navy);margin-bottom:4px}
        .dropzone .dz-hint{font-size:12px;color:var(--muted)}
        .dropzone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
        .file-preview{margin-top:12px;padding:10px 14px;background:#eff6ff;border-radius:9px;font-size:13px;color:var(--blue);font-weight:600;display:none;align-items:center;gap:8px}

        .upload-footer{display:flex;justify-content:flex-end;margin-top:18px}
        .btn-primary{background:var(--blue);color:#fff;border:none;padding:10px 22px;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;display:inline-flex;align-items:center;gap:7px;transition:.18s;box-shadow:0 2px 10px rgba(37,99,235,.28)}
        .btn-primary:hover{background:var(--blue2)}

        /* SECTION TITLE */
        .section-title{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .badge{background:var(--blue);color:#fff;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px}

        /* SUBMISSIONS LIST */
        .sub-list{display:flex;flex-direction:column;gap:12px}
        .sub-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:16px 22px;display:flex;align-items:center;gap:16px;transition:box-shadow .2s,transform .2s;animation:fadeUp .35s ease both}
        .sub-card:hover{box-shadow:0 8px 36px rgba(37,99,235,.14);transform:translateY(-2px)}
        @keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

        .file-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
        .file-icon.pdf{background:#fee2e2;color:#dc2626}
        .file-icon.doc{background:#dbeafe;color:#2563eb}
        .file-icon.img{background:#dcfce7;color:#16a34a}
        .file-icon.other{background:#f3f4f6;color:#64748b}

        .sub-info{flex:1;min-width:0}
        .sub-name{font-size:14px;font-weight:700;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .sub-meta{font-size:12px;color:var(--muted);margin-top:3px}
        .sub-student{font-size:13px;font-weight:600;color:var(--blue);margin-bottom:2px}

        .btn-view{background:var(--blue);color:#fff;text-decoration:none;padding:7px 16px;border-radius:8px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:.18s;white-space:nowrap}
        .btn-view:hover{background:var(--blue2)}
        .btn-dl{background:#f1f5f9;color:var(--navy);text-decoration:none;padding:7px 16px;border-radius:8px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;transition:.18s;white-space:nowrap}
        .btn-dl:hover{background:#e2e8f0}

        .empty{text-align:center;padding:60px 20px;color:var(--muted)}
        .empty .icon{font-size:52px;margin-bottom:12px}

        .allowed-types{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
        .type-chip{background:#f1f5f9;border-radius:6px;padding:3px 10px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}

        @media(max-width:600px){
            .sub-card{flex-wrap:wrap}
            .page-header{flex-direction:column;align-items:flex-start;gap:8px}
        }
    </style>
</head>
<body>

<nav class="navbar">
    <span class="brand">📚 EduPortal</span>
    <?php if($role === 'teacher'): ?>
        <a href="teacher-dashboard.php">Home</a>
        <a href="study-materials.php">Materials</a>
        <a href="homework.php" class="active">Homework</a>
        <a href="meetings.php">Meetings</a>
        <a href="announcements.php">Announcements</a>
    <?php else: ?>
        <a href="student-dashboard.php">Home</a>
        <a href="study-materials.php">Materials</a>
        <a href="homework.php" class="active">Homework</a>
        <a href="meetings.php">Meetings</a>
        <a href="announcements.php">Announcements</a>
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
        <h1>📝 Homework</h1>
        <?php if($role === 'teacher'): ?>
            <span style="font-size:13px;color:var(--muted)"><?= count($submissions) ?> submission<?= count($submissions) !== 1 ? 's' : '' ?> received</span>
        <?php endif; ?>
    </div>

    <!-- UPLOAD SECTION (students only) -->
    <?php if($role === 'student'): ?>
    <div class="upload-card">
        <h2>📤 Submit Your Homework</h2>
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="dropzone" id="dropzone">
                <div class="dz-icon">☁️</div>
                <div class="dz-label">Drag & drop your file here</div>
                <div class="dz-hint">or click to browse files</div>
                <input type="file" name="file" id="fileInput" required onchange="previewFile(this)">
            </div>
            <div class="file-preview" id="filePreview">
                <span>📎</span>
                <span id="fileName"></span>
            </div>
            <div class="allowed-types">
                <span class="type-chip">PDF</span>
                <span class="type-chip">DOCX</span>
                <span class="type-chip">JPG</span>
                <span class="type-chip">PNG</span>
                <span style="font-size:12px;color:var(--muted);display:flex;align-items:center">· Max 5 MB</span>
            </div>
            <div class="upload-footer">
                <button type="submit" name="submit" class="btn-primary">📤 Submit Homework</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- SUBMISSIONS LIST -->
    <div class="section-title">
        <?= $role === 'teacher' ? '📋 Student Submissions' : '📋 Your Submissions' ?>
        <span class="badge"><?= count($submissions) ?></span>
    </div>

    <?php if(empty($submissions)): ?>
        <div class="empty">
            <div class="icon">📭</div>
            <p><?= $role === 'teacher' ? 'No homework submitted yet.' : "You haven't submitted any homework yet." ?></p>
        </div>
    <?php else: ?>
    <div class="sub-list">
        <?php foreach($submissions as $i => $row):
            $ext = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
            $iconClass = match($ext) {
                'pdf' => 'pdf', 'docx','doc' => 'doc', 'jpg','jpeg','png' => 'img', default => 'other'
            };
            $icon = match($ext) {
                'pdf' => '📄', 'docx','doc' => '📝', 'jpg','jpeg','png' => '🖼️', default => '📎'
            };
        ?>
        <div class="sub-card" style="animation-delay:<?= $i*.05 ?>s">
            <div class="file-icon <?= $iconClass ?>"><?= $icon ?></div>
            <div class="sub-info">
                <?php if($role === 'teacher'): ?>
                    <div class="sub-student">🎒 <?= htmlspecialchars($row['firstName'].' '.$row['lastName']) ?></div>
                <?php endif; ?>
                <div class="sub-name"><?= htmlspecialchars($row['file_name']) ?></div>
                <div class="sub-meta">🕐 <?= date('D, d M Y · g:i A', strtotime($row['uploaded_at'])) ?></div>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0">
                <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn-view">👁 View</a>
                <a href="<?= htmlspecialchars($row['file_path']) ?>" download class="btn-dl">⬇ Download</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script>
function previewFile(input) {
    const preview = document.getElementById('filePreview');
    const nameEl  = document.getElementById('fileName');
    if (input.files && input.files[0]) {
        nameEl.textContent = input.files[0].name;
        preview.style.display = 'flex';
    }
}

// Drag-over highlight
const dz = document.getElementById('dropzone');
if (dz) {
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
    dz.addEventListener('drop', () => dz.classList.remove('dragover'));
}
</script>
</body>
</html>