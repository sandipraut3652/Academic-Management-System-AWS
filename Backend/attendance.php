<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$role      = $_SESSION['role'];
$user_id   = $_SESSION['user_id'];
$firstName = $_SESSION['firstName'];
$message   = '';
$msgType   = '';

// ── AUTO-CREATE TABLE ─────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS attendance (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    class_date   DATE NOT NULL,
    class_label  VARCHAR(150) NOT NULL DEFAULT 'Class',
    status       ENUM('present','absent') NOT NULL DEFAULT 'present',
    marked_by    VARCHAR(100) NOT NULL,
    marked_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_att (student_id, class_date, class_label)
)");

// ── TEACHER: SAVE ATTENDANCE ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'teacher' && isset($_POST['action'])) {

    if ($_POST['action'] === 'save') {
        $class_date  = $_POST['class_date'];
        $class_label = trim($_POST['class_label']) ?: 'Class';
        $statuses    = $_POST['status'] ?? [];

        $saved = 0;
        foreach ($statuses as $sid => $status) {
            $sid    = (int)$sid;
            $status = $status === 'present' ? 'present' : 'absent';

            $s = $conn->prepare("SELECT CONCAT(firstName,' ',lastName) AS name FROM users WHERE id=? AND role='student'");
            $s->bind_param("i", $sid);
            $s->execute();
            $row = $s->get_result()->fetch_assoc();
            $s->close();
            if (!$row) continue;

            $sname = $row['name'];
            $stmt  = $conn->prepare("INSERT INTO attendance (student_id, student_name, class_date, class_label, status, marked_by)
                                     VALUES (?,?,?,?,?,?)
                                     ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by), marked_at=NOW()");
            $stmt->bind_param("isssss", $sid, $sname, $class_date, $class_label, $status, $firstName);
            $stmt->execute();
            $stmt->close();
            $saved++;
        }
        $_SESSION['flash_msg']  = "Attendance saved for $saved student(s)!";
        $_SESSION['flash_type'] = "success";
        header("Location: attendance.php");
        exit();
    }

    if ($_POST['action'] === 'delete_session') {
        $date  = $_POST['class_date'];
        $label = $_POST['class_label'];
        $stmt  = $conn->prepare("DELETE FROM attendance WHERE class_date=? AND class_label=?");
        $stmt->bind_param("ss", $date, $label);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_msg']  = "Session deleted.";
        $_SESSION['flash_type'] = "success";
        header("Location: attendance.php");
        exit();
    }
}

// ── FLASH ─────────────────────────────────────────────────────────────────
if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $msgType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// ── FETCH ALL STUDENTS (teacher) ──────────────────────────────────────────
$allStudents = [];
if ($role === 'teacher') {
    $sr = $conn->query("SELECT id, CONCAT(firstName,' ',lastName) AS name FROM users WHERE role='student' ORDER BY firstName");
    while ($r = $sr->fetch_assoc()) $allStudents[] = $r;
}

// ── FETCH ALL SESSIONS ────────────────────────────────────────────────────
$sessions = [];
$sr2 = $conn->query("SELECT class_date, class_label,
                     COUNT(*) AS total,
                     SUM(status='present') AS present_count
                     FROM attendance
                     GROUP BY class_date, class_label
                     ORDER BY class_date DESC, class_label ASC");
while ($r = $sr2->fetch_assoc()) $sessions[] = $r;

// ── DRILL-DOWN SESSION RECORDS ────────────────────────────────────────────
$selDate  = $_GET['date']  ?? null;
$selLabel = $_GET['label'] ?? null;
$sessionRecords = [];
if ($role === 'teacher' && $selDate && $selLabel) {
    $sr3 = $conn->prepare("SELECT * FROM attendance WHERE class_date=? AND class_label=? ORDER BY student_name");
    $sr3->bind_param("ss", $selDate, $selLabel);
    $sr3->execute();
    $res = $sr3->get_result();
    while ($r = $res->fetch_assoc()) $sessionRecords[] = $r;
    $sr3->close();
}

// ── STUDENT: OWN STATS ────────────────────────────────────────────────────
$studentStats = ['total'=>0,'present'=>0,'absent'=>0,'pct'=>0,'records'=>[]];
if ($role === 'student') {
    $ss = $conn->prepare("SELECT * FROM attendance WHERE student_id=? ORDER BY class_date DESC");
    $ss->bind_param("i", $user_id);
    $ss->execute();
    $res = $ss->get_result();
    while ($r = $res->fetch_assoc()) {
        $studentStats['records'][] = $r;
        $studentStats['total']++;
        if ($r['status'] === 'present') $studentStats['present']++;
        else $studentStats['absent']++;
    }
    $ss->close();
    $studentStats['pct'] = $studentStats['total'] > 0
        ? round(($studentStats['present'] / $studentStats['total']) * 100) : 0;
}

// ── TEACHER: PER-STUDENT OVERVIEW ────────────────────────────────────────
$studentOverview = [];
if ($role === 'teacher') {
    foreach ($allStudents as $stu) {
        $sid = $stu['id'];
        $sq  = $conn->prepare("SELECT COUNT(*) AS t, SUM(status='present') AS p FROM attendance WHERE student_id=?");
        $sq->bind_param("i", $sid);
        $sq->execute();
        $stats = $sq->get_result()->fetch_assoc();
        $sq->close();
        $t   = (int)$stats['t'];
        $p   = (int)$stats['p'];
        $pct = $t > 0 ? round(($p / $t) * 100) : 0;
        $studentOverview[] = ['id'=>$sid,'name'=>$stu['name'],'total'=>$t,'present'=>$p,'absent'=>$t-$p,'pct'=>$pct];
    }
    usort($studentOverview, fn($a,$b) => $a['pct'] <=> $b['pct']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance – EduPortal</title>
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
.page{max-width:980px;margin:36px auto;padding:0 20px 80px}
.alert{padding:14px 18px;border-radius:var(--radius);font-size:14px;font-weight:500;margin-bottom:22px;display:flex;align-items:center;gap:10px;animation:slideDown .3s ease}
.alert.success{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.alert.error{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.page-header h1{font-size:26px;font-weight:700;color:var(--navy)}
.tab-bar{display:flex;gap:4px;background:#e2e8f0;border-radius:12px;padding:4px;margin-bottom:28px;width:fit-content}
.tab-btn{padding:9px 20px;border-radius:9px;font-size:14px;font-weight:600;color:var(--muted);cursor:pointer;border:none;background:transparent;font-family:'DM Sans',sans-serif;transition:.18s}
.tab-btn.active{background:#fff;color:var(--navy);box-shadow:0 2px 8px rgba(0,0,0,.08)}
.card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;margin-bottom:24px;animation:fadeUp .35s ease both}
.card-header{padding:18px 26px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:#f8faff}
.card-header h2{font-size:16px;font-weight:700;color:var(--navy)}
.card-body{padding:24px 26px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group label{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px}
.form-group input{border:1.5px solid var(--border);border-radius:9px;padding:10px 14px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text);background:#f8faff;outline:none;transition:.18s}
.form-group input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.1);background:#fff}
.student-list{display:flex;flex-direction:column}
.student-row{display:flex;align-items:center;justify-content:space-between;padding:13px 0;border-bottom:1px solid #f1f5f9}
.student-row:last-child{border-bottom:none}
.stu-info{display:flex;align-items:center;gap:12px}
.avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--indigo));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0}
.toggle-group{display:flex;border:1.5px solid var(--border);border-radius:9px;overflow:hidden}
.toggle-group input[type=radio]{display:none}
.toggle-group label{padding:8px 20px;font-size:13px;font-weight:600;cursor:pointer;transition:.18s;color:var(--muted);background:#f8faff;user-select:none}
.toggle-group input[value=present]:checked + label{background:#dcfce7;color:#15803d}
.toggle-group input[value=absent]:checked  + label{background:#fee2e2;color:var(--red)}
.bulk-row{display:flex;align-items:center;justify-content:space-between;padding:12px 26px;border-bottom:1px solid var(--border);background:#f8faff;flex-wrap:wrap;gap:8px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:.18s;font-family:'DM Sans',sans-serif;text-decoration:none}
.btn:active{transform:scale(.97)}
.btn-primary{background:var(--blue);color:#fff;box-shadow:0 2px 8px rgba(37,99,235,.25)}
.btn-primary:hover{background:var(--blue2)}
.btn-green{background:var(--green);color:#fff}
.btn-green:hover{background:#15803d}
.btn-red-soft{background:#fee2e2;color:var(--red)}
.btn-red-soft:hover{background:#fecaca}
.btn-ghost{background:#f1f5f9;color:var(--navy)}
.btn-ghost:hover{background:#e2e8f0}
.btn-sm{padding:6px 12px;font-size:12px;border-radius:7px}
.card-footer{padding:16px 26px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;background:#f8faff}
.progress-wrap{background:#f1f5f9;border-radius:20px;height:10px;overflow:hidden}
.progress-bar{height:100%;border-radius:20px;background:linear-gradient(90deg,#16a34a,#22c55e);transition:.6s ease}
.progress-bar.warn{background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.progress-bar.danger{background:linear-gradient(90deg,var(--red),#f87171)}
.tbl{width:100%;border-collapse:collapse}
.tbl thead th{padding:11px 18px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;background:#f8faff;border-bottom:1px solid var(--border);text-align:left}
.tbl tbody td{padding:13px 18px;font-size:14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.tbl tbody tr:last-child td{border-bottom:none}
.tbl tbody tr:hover td{background:#f8faff}
.pill{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px}
.pill.present{background:#dcfce7;color:#15803d}
.pill.absent{background:#fee2e2;color:#b91c1c}
.session-item{display:flex;align-items:center;justify-content:space-between;padding:14px 26px;border-bottom:1px solid #f1f5f9;gap:12px;flex-wrap:wrap;transition:.15s}
.session-item:last-child{border-bottom:none}
.session-item:hover{background:#f8faff}
.mini-bar{height:6px;background:#f1f5f9;border-radius:10px;overflow:hidden;width:80px}
.mini-fill{height:100%;border-radius:10px;background:linear-gradient(90deg,var(--green),#22c55e)}
.empty{padding:50px 20px;text-align:center;color:var(--muted)}
.empty .icon{font-size:46px;margin-bottom:10px}
/* Donut */
.donut-rel{position:relative;width:160px;height:160px}
.donut-svg{width:160px;height:160px;transform:rotate(-90deg)}
.donut-track{fill:none;stroke:#f1f5f9;stroke-width:18}
.donut-fill{fill:none;stroke-width:18;stroke-linecap:round}
.donut-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
.donut-pct{font-size:30px;font-weight:800;line-height:1}
.donut-sub{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-top:2px}
@media(max-width:640px){.form-row{grid-template-columns:1fr}}
</style>
</head>
<body>

<nav class="navbar">
  <span class="brand">📚 EduPortal</span>
  <?php if($role==='teacher'): ?>
    <a href="teacher-dashboard.php">Home</a>
    <a href="study-materials.php">Materials</a>
    <a href="homework.php">Homework</a>
    <a href="meetings.php">Meetings</a>
    <a href="announcements.php">Announcements</a>
    <a href="attendance.php" class="active">Attendance</a>
  <?php else: ?>
    <a href="student-dashboard.php">Home</a>
    <a href="study-materials.php">Materials</a>
    <a href="homework.php">Homework</a>
    <a href="meetings.php">Meetings</a>
    <a href="announcements.php">Announcements</a>
    <a href="attendance.php" class="active">Attendance</a>
  <?php endif; ?>
  <div class="spacer"></div>
  <a href="logout.php">👋 Logout</a>
</nav>

<div class="page">
  <?php if($message): ?>
    <div class="alert <?= $msgType ?>">
      <?= $msgType==='success'?'✅':'❌' ?> <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div class="page-header">
    <h1>✅ Attendance</h1>
    <?php if($role==='teacher'): ?>
      <a href="attendance.php?export=1" class="btn btn-ghost">⬇ Export CSV</a>
    <?php endif; ?>
  </div>

<!-- ════════════ TEACHER VIEW ════════════ -->
<?php if($role==='teacher'): ?>

  <div class="tab-bar">
    <button class="tab-btn active" onclick="switchTab('mark',this)">✏️ Mark Attendance</button>
    <button class="tab-btn" onclick="switchTab('sessions',this)">📋 Sessions</button>
    <button class="tab-btn" onclick="switchTab('students',this)">👥 Students</button>
  </div>

  <!-- TAB: MARK -->
  <div id="tab-mark">
    <?php if(empty($allStudents)): ?>
      <div class="card"><div class="empty"><div class="icon">🎒</div><p>No students registered yet.</p></div></div>
    <?php else: ?>
    <div class="card">
      <div class="card-header"><h2>📝 Mark Attendance</h2></div>
      <form method="POST">
        <input type="hidden" name="action" value="save">
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>Date *</label>
              <input type="date" name="class_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
              <label>Session Label *</label>
              <input type="text" name="class_label" placeholder="e.g. Lecture 1, Lab 3, Tutorial A…" required>
            </div>
          </div>
        </div>
        <div class="bulk-row">
          <span style="font-size:13px;color:var(--muted);font-weight:500"><?= count($allStudents) ?> students</span>
          <div style="display:flex;gap:8px">
            <button type="button" class="btn btn-green btn-sm" onclick="markAll('present')">✅ All Present</button>
            <button type="button" class="btn btn-red-soft btn-sm" onclick="markAll('absent')">❌ All Absent</button>
          </div>
        </div>
        <div class="card-body" style="padding-top:8px;padding-bottom:8px">
          <div class="student-list">
            <?php foreach($allStudents as $stu): ?>
            <div class="student-row">
              <div class="stu-info">
                <div class="avatar"><?= strtoupper(substr($stu['name'],0,1)) ?></div>
                <span style="font-size:14px;font-weight:600;color:var(--navy)"><?= htmlspecialchars($stu['name']) ?></span>
              </div>
              <div class="toggle-group">
                <input type="radio" name="status[<?= $stu['id'] ?>]" id="p<?= $stu['id'] ?>" value="present" checked class="att-r">
                <label for="p<?= $stu['id'] ?>">✅ Present</label>
                <input type="radio" name="status[<?= $stu['id'] ?>]" id="a<?= $stu['id'] ?>" value="absent" class="att-r">
                <label for="a<?= $stu['id'] ?>">❌ Absent</label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">💾 Save Attendance</button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- TAB: SESSIONS -->
  <div id="tab-sessions" style="display:none">
    <div class="card">
      <div class="card-header">
        <h2>📋 All Sessions</h2>
        <span style="font-size:13px;color:var(--muted)"><?= count($sessions) ?> sessions</span>
      </div>
      <?php if(empty($sessions)): ?>
        <div class="empty"><div class="icon">📭</div><p>No sessions recorded yet.</p></div>
      <?php else: ?>
        <?php foreach($sessions as $sess):
          $pct = $sess['total']>0 ? round(($sess['present_count']/$sess['total'])*100) : 0;
          $col = $pct>=75?'var(--green)':($pct>=50?'#d97706':'var(--red)');
        ?>
        <div class="session-item">
          <div>
            <div style="font-size:14px;font-weight:700;color:var(--navy)"><?= htmlspecialchars($sess['class_label']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">📅 <?= date('D, d M Y', strtotime($sess['class_date'])) ?></div>
          </div>
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
            <div>
              <div style="font-size:11px;color:var(--muted);margin-bottom:3px;text-align:right"><?= $sess['present_count'] ?>/<?= $sess['total'] ?> present</div>
              <div class="mini-bar"><div class="mini-fill" style="width:<?= $pct ?>%"></div></div>
            </div>
            <span style="font-size:15px;font-weight:800;color:<?= $col ?>;width:44px;text-align:right"><?= $pct ?>%</span>
            <a href="attendance.php?date=<?= urlencode($sess['class_date']) ?>&label=<?= urlencode($sess['class_label']) ?>"
               class="btn btn-ghost btn-sm" onclick="setTimeout(()=>switchTab('sessions',document.querySelectorAll('.tab-btn')[1]),50)">👁 View</a>
            <form method="POST" onsubmit="return confirm('Delete this entire session?')" style="display:inline">
              <input type="hidden" name="action" value="delete_session">
              <input type="hidden" name="class_date" value="<?= htmlspecialchars($sess['class_date']) ?>">
              <input type="hidden" name="class_label" value="<?= htmlspecialchars($sess['class_label']) ?>">
              <button type="submit" class="btn btn-red-soft btn-sm">🗑</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Drill-down -->
        <?php if($selDate && $selLabel && !empty($sessionRecords)): ?>
        <div style="padding:22px 26px;border-top:2px solid var(--border)">
          <div style="font-size:15px;font-weight:700;color:var(--navy);margin-bottom:16px">
            📋 <?= htmlspecialchars($selLabel) ?> — <?= date('D, d M Y', strtotime($selDate)) ?>
          </div>
          <table class="tbl">
            <thead><tr><th>Student</th><th>Status</th><th>Marked By</th><th>Time</th></tr></thead>
            <tbody>
              <?php foreach($sessionRecords as $sr): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px">
                    <div class="avatar" style="width:30px;height:30px;font-size:11px"><?= strtoupper(substr($sr['student_name'],0,1)) ?></div>
                    <?= htmlspecialchars($sr['student_name']) ?>
                  </div>
                </td>
                <td><span class="pill <?= $sr['status'] ?>"><?= $sr['status']==='present'?'✅':'❌' ?> <?= ucfirst($sr['status']) ?></span></td>
                <td style="color:var(--muted);font-size:13px">🎓 <?= htmlspecialchars($sr['marked_by']) ?></td>
                <td style="color:var(--muted);font-size:13px"><?= date('g:i A', strtotime($sr['marked_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB: STUDENTS OVERVIEW -->
  <div id="tab-students" style="display:none">
    <?php
      $below75 = count(array_filter($studentOverview, fn($s)=>$s['pct']<75));
      if($below75>0): ?>
      <div class="alert error">⚠️ <strong><?= $below75 ?> student<?= $below75>1?'s':'' ?></strong> below 75% attendance.</div>
    <?php endif; ?>
    <div class="card">
      <div class="card-header">
        <h2>👥 Student Overview</h2>
        <span style="font-size:13px;color:var(--muted)"><?= count($studentOverview) ?> students · <?= count($sessions) ?> sessions total</span>
      </div>
      <?php if(empty($studentOverview)): ?>
        <div class="empty"><div class="icon">📭</div><p>No attendance data yet.</p></div>
      <?php else: ?>
      <table class="tbl">
        <thead><tr><th>Student</th><th>Present</th><th>Absent</th><th>Attendance %</th></tr></thead>
        <tbody>
          <?php foreach($studentOverview as $ov):
            $pct = $ov['pct'];
            $bc  = $pct<75?'danger':($pct<90?'warn':'');
            $col = $pct<75?'var(--red)':($pct<90?'#d97706':'var(--green)');
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="avatar" style="width:34px;height:34px;font-size:12px;<?= $pct<75?'background:linear-gradient(135deg,var(--red),#f87171)':'' ?>">
                  <?= strtoupper(substr($ov['name'],0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:600;color:var(--navy)"><?= htmlspecialchars($ov['name']) ?></div>
                  <?php if($pct<75): ?><div style="font-size:11px;color:var(--red);font-weight:600">⚠ Low attendance</div><?php endif; ?>
                </div>
              </div>
            </td>
            <td style="font-weight:700;color:var(--green)"><?= $ov['present'] ?></td>
            <td style="font-weight:700;color:var(--red)"><?= $ov['absent'] ?></td>
            <td style="min-width:160px">
              <div style="display:flex;align-items:center;gap:10px">
                <div style="flex:1"><div class="progress-wrap"><div class="progress-bar <?= $bc ?>" style="width:<?= $pct ?>%"></div></div></div>
                <span style="font-weight:800;font-size:14px;color:<?= $col ?>;width:42px;text-align:right"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

<!-- ════════════ STUDENT VIEW ════════════ -->
<?php else:
  $pct = $studentStats['pct'];
  $color = $pct>=75?'var(--green)':($pct>=50?'#d97706':'var(--red)');
  $bc   = $pct>=75?'':''.($pct>=50?'warn':'danger');
  $radius=70; $circ=round(2*M_PI*$radius,2); $filled=round($circ*$pct/100,2);
  $stroke=$pct>=75?'#16a34a':($pct>=50?'#f59e0b':'#dc2626');
?>

  <!-- BIG DONUT -->
  <div class="card">
    <div class="card-header"><h2>📊 Your Attendance</h2></div>
    <div style="display:flex;align-items:center;justify-content:center;gap:48px;padding:36px 24px;flex-wrap:wrap">
      <div class="donut-rel">
        <svg class="donut-svg" viewBox="0 0 160 160">
          <circle class="donut-track" cx="80" cy="80" r="<?= $radius ?>"/>
          <circle class="donut-fill" cx="80" cy="80" r="<?= $radius ?>"
                  stroke="<?= $stroke ?>"
                  stroke-dasharray="<?= $filled ?> <?= $circ ?>"/>
        </svg>
        <div class="donut-center">
          <div class="donut-pct" style="color:<?= $color ?>"><?= $pct ?>%</div>
          <div class="donut-sub">attended</div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:14px;min-width:210px">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;background:#f8faff;border-radius:12px;border:1px solid var(--border)">
          <span style="font-size:14px;color:var(--muted);font-weight:500">Total Classes</span>
          <span style="font-size:22px;font-weight:800;color:var(--navy)"><?= $studentStats['total'] ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;background:#dcfce7;border-radius:12px;border:1px solid #bbf7d0">
          <span style="font-size:14px;color:var(--green);font-weight:600">✅ Present</span>
          <span style="font-size:22px;font-weight:800;color:var(--green)"><?= $studentStats['present'] ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;background:#fee2e2;border-radius:12px;border:1px solid #fecaca">
          <span style="font-size:14px;color:var(--red);font-weight:600">❌ Absent</span>
          <span style="font-size:22px;font-weight:800;color:var(--red)"><?= $studentStats['absent'] ?></span>
        </div>
      </div>
    </div>
    <div style="margin:0 24px 24px;padding:14px 18px;border-radius:12px;text-align:center;font-size:14px;font-weight:600;
         background:<?= $pct>=75?'#dcfce7':($pct>=50?'#fef3c7':'#fee2e2') ?>;
         color:<?= $color ?>;border:1px solid <?= $pct>=75?'#bbf7d0':($pct>=50?'#fde68a':'#fecaca') ?>">
      <?php if($pct>=75): ?>🎉 Great job! Your attendance is on track.
      <?php elseif($pct>=50): ?>⚠️ Attendance below recommended. Try to attend more.
      <?php else: ?>🚨 Very low attendance! Please speak with your teacher.<?php endif; ?>
    </div>
  </div>

  <!-- SESSION LIST -->
  <?php if(!empty($studentStats['records'])): ?>
  <div class="card">
    <div class="card-header"><h2>📋 Session-wise Record</h2></div>
    <table class="tbl">
      <thead><tr><th>Session</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($studentStats['records'] as $r): ?>
        <tr>
          <td style="font-weight:600;color:var(--navy)"><?= htmlspecialchars($r['class_label']) ?></td>
          <td style="color:var(--muted);font-size:13px">📅 <?= date('D, d M Y', strtotime($r['class_date'])) ?></td>
          <td><span class="pill <?= $r['status'] ?>"><?= $r['status']==='present'?'✅':'❌' ?> <?= ucfirst($r['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="card"><div class="empty"><div class="icon">📭</div><p>No attendance recorded for you yet.</p></div></div>
  <?php endif; ?>

<?php endif; ?>
</div>

<?php
if(isset($_GET['export']) && $role==='teacher'){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance.csv"');
    $out=fopen('php://output','w');
    fputcsv($out,['Student','Session','Date','Status','Marked By','Time']);
    $all=$conn->query("SELECT student_name,class_label,class_date,status,marked_by,marked_at FROM attendance ORDER BY class_date DESC,student_name");
    while($r=$all->fetch_assoc()) fputcsv($out,[$r['student_name'],$r['class_label'],$r['class_date'],$r['status'],$r['marked_by'],$r['marked_at']]);
    fclose($out); exit();
}
?>
<script>
function switchTab(name,btn){
    ['mark','sessions','students'].forEach(t=>{
        const el=document.getElementById('tab-'+t);
        if(el) el.style.display='none';
    });
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    const el=document.getElementById('tab-'+name);
    if(el) el.style.display='block';
    btn.classList.add('active');
}
function markAll(status){
    document.querySelectorAll('.att-r').forEach(r=>{
        r.checked=(r.value===status);
    });
}
window.addEventListener('DOMContentLoaded',()=>{
    if(new URLSearchParams(window.location.search).has('date'))
        switchTab('sessions',document.querySelectorAll('.tab-btn')[1]);
});
</script>
</body>
</html>
<?php $conn->close(); ?>