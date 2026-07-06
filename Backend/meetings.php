<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$role      = $_SESSION['role'];
$firstName = $_SESSION['firstName'];
$user_id   = $_SESSION['user_id'];
$message   = '';
$msgType   = '';

// ── CREATE MEETING (teacher only) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create' && $role === 'teacher') {
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $meet_date   = $_POST['meet_date'];
        $meet_time   = $_POST['meet_time'];
        $slug        = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $room_name   = 'eduportal-' . $slug . '-' . bin2hex(random_bytes(4));

        if ($title && $meet_date && $meet_time) {
            $stmt = $conn->prepare("INSERT INTO meetings (teacher_id, teacher_name, title, description, meet_date, meet_time, room_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $user_id, $firstName, $title, $description, $meet_date, $meet_time, $room_name);
            if ($stmt->execute()) {
                $_SESSION['flash_msg']  = "Meeting scheduled successfully!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg']  = "Error: " . $stmt->error;
                $_SESSION['flash_type'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['flash_msg']  = "Please fill in all required fields.";
            $_SESSION['flash_type'] = "error";
        }
        header("Location: meetings.php");
        exit();
    }

    // ── START CALL ── (PRG: redirect to ?join=ID after setting is_live=1)
    if ($action === 'start' && $role === 'teacher') {
        $mid = (int)$_POST['meeting_id'];
        $stmt = $conn->prepare("UPDATE meetings SET is_live = 1 WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ii", $mid, $user_id);
        $stmt->execute();
        $stmt->close();
        // Redirect so the GET picks up the freshly-live meeting
        header("Location: meetings.php?join=" . $mid);
        exit();
    }

    // ── END CALL ──────────────────────────────────────────────────────────
    if ($action === 'end' && $role === 'teacher') {
        $mid = (int)$_POST['meeting_id'];
        $stmt = $conn->prepare("UPDATE meetings SET is_live = 0 WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ii", $mid, $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_msg']  = "Meeting ended.";
        $_SESSION['flash_type'] = "success";
        header("Location: meetings.php");
        exit();
    }

    // ── DELETE ────────────────────────────────────────────────────────────
    if ($action === 'delete' && $role === 'teacher') {
        $mid = (int)$_POST['meeting_id'];
        $stmt = $conn->prepare("DELETE FROM meetings WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ii", $mid, $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_msg']  = "Meeting deleted.";
        $_SESSION['flash_type'] = "success";
        header("Location: meetings.php");
        exit();
    }
}

// ── Flash messages (set before redirect) ─────────────────────────────────
if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $msgType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// ── FETCH JOINING MEETING (GET ?join=ID) ──────────────────────────────────
$joining_meeting = null;
$join_id = isset($_GET['join']) ? (int)$_GET['join'] : null;

if ($join_id) {
    $stmt = $conn->prepare("SELECT * FROM meetings WHERE id = ?");
    $stmt->bind_param("i", $join_id);
    $stmt->execute();
    $joining_meeting = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Students can only join if meeting is live
    if ($role === 'student' && (!$joining_meeting || !$joining_meeting['is_live'])) {
        $joining_meeting = null;
        $message = "This meeting has not started yet. Please wait for your teacher to start it.";
        $msgType = "error";
    }
}

// ── FETCH ALL MEETINGS ────────────────────────────────────────────────────
if ($role === 'student') {
    $result = $conn->query("SELECT * FROM meetings ORDER BY is_live DESC, meet_date ASC, meet_time ASC");
} else {
    $result = $conn->query("SELECT * FROM meetings WHERE teacher_id = $user_id ORDER BY is_live DESC, meet_date DESC, meet_time DESC");
}
$meetings = [];
while ($row = $result->fetch_assoc()) $meetings[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meetings – EduPortal</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://meet.jit.si/external_api.js"></script>
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

/* NAVBAR */
.navbar{background:var(--navy);padding:0 32px;display:flex;align-items:center;height:64px;gap:4px;box-shadow:0 2px 16px rgba(0,0,0,.22)}
.navbar .brand{font-weight:700;font-size:18px;color:#fff;margin-right:24px;letter-spacing:-.4px}
.navbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;padding:7px 14px;border-radius:8px;transition:.18s}
.navbar a:hover,.navbar a.active{background:rgba(255,255,255,.13);color:#fff}
.navbar .spacer{flex:1}

/* VIDEO OVERLAY */
.call-overlay{position:fixed;inset:0;background:#0a0f23;z-index:1000;display:flex;flex-direction:column;animation:fadeIn .25s ease}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.call-header{display:flex;align-items:center;justify-content:space-between;padding:14px 28px;background:rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0}
.call-title{font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:10px}
.live-dot{width:9px;height:9px;border-radius:50%;background:#22c55e;box-shadow:0 0 8px #22c55e;animation:pulse 1.4s infinite;flex-shrink:0}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.btn-hangup{background:#dc2626;color:#fff;border:none;padding:9px 20px;border-radius:9px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;transition:.18s;font-family:'DM Sans',sans-serif}
.btn-hangup:hover{background:#b91c1c}
#jitsi-container{flex:1;width:100%;min-height:0}

/* PAGE */
.page{max-width:980px;margin:38px auto;padding:0 20px 80px}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.page-header h1{font-size:26px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:10px}
.alert{padding:14px 18px;border-radius:var(--radius);font-size:14px;font-weight:500;margin-bottom:22px;display:flex;align-items:center;gap:10px;animation:slideDown .3s ease}
.alert.success{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.alert.error{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

/* FORM */
.form-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:28px 32px;margin-bottom:36px;border:1px solid var(--border)}
.form-card h2{font-size:17px;font-weight:700;color:var(--navy);margin-bottom:20px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
.form-group label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.form-group input,.form-group textarea{border:1.5px solid var(--border);border-radius:9px;padding:10px 14px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text);background:#f8faff;transition:.18s;outline:none}
.form-group textarea{resize:vertical;min-height:72px}
.form-group input:focus,.form-group textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.12);background:#fff}
.form-footer{margin-top:20px;display:flex;justify-content:flex-end}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:9px;font-size:14px;font-weight:600;border:none;cursor:pointer;transition:.18s;font-family:'DM Sans',sans-serif;text-decoration:none}
.btn:active{transform:scale(.97)}
.btn-primary{background:var(--blue);color:#fff;box-shadow:0 2px 10px rgba(37,99,235,.28)}
.btn-primary:hover{background:var(--blue2)}
.btn-start{background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-size:13px;padding:8px 16px;box-shadow:0 2px 8px rgba(22,163,74,.28)}
.btn-rejoin{background:linear-gradient(135deg,var(--indigo),var(--blue));color:#fff;font-size:13px;padding:8px 18px;animation:glow 2s infinite}
@keyframes glow{0%,100%{box-shadow:0 2px 10px rgba(79,70,229,.3)}50%{box-shadow:0 4px 24px rgba(79,70,229,.6)}}
.btn-join-live{background:linear-gradient(135deg,var(--indigo),var(--blue));color:#fff;font-size:13px;padding:8px 18px;animation:glow 2s infinite}
.btn-end-meeting{background:#fee2e2;color:var(--red);font-size:13px;padding:8px 16px}
.btn-end-meeting:hover{background:#fecaca}
.btn-delete{background:#f1f5f9;color:#64748b;font-size:13px;padding:8px 14px}
.btn-delete:hover{background:#fee2e2;color:var(--red)}

/* SECTION TITLE */
.section-title{font-size:16px;font-weight:700;color:var(--navy);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.badge{background:var(--blue);color:#fff;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px}

/* MEETING CARDS */
.meetings-list{display:flex;flex-direction:column;gap:16px}
.meeting-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:20px 24px;display:flex;align-items:flex-start;gap:20px;transition:box-shadow .2s,transform .2s;animation:fadeUp .35s ease both}
.meeting-card.is-live{border:2px solid #22c55e;box-shadow:0 4px 28px rgba(34,197,94,.2)}
.meeting-card:hover{box-shadow:0 8px 36px rgba(37,99,235,.14);transform:translateY(-2px)}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

.date-block{flex-shrink:0;width:64px;text-align:center;background:linear-gradient(135deg,var(--blue),var(--indigo));border-radius:12px;padding:10px 6px;color:#fff}
.date-block .day{font-size:26px;font-weight:800;line-height:1}
.date-block .mon{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;opacity:.88}

.meeting-body{flex:1;min-width:0}
.meeting-title{font-size:16px;font-weight:700;color:var(--navy);margin-bottom:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.meeting-meta{display:flex;flex-wrap:wrap;gap:12px;font-size:13px;color:var(--muted);margin-bottom:8px}
.meeting-meta span{display:flex;align-items:center;gap:5px}
.meeting-desc{font-size:13.5px;color:#475569;line-height:1.55}
.meeting-actions{flex-shrink:0;display:flex;flex-direction:column;gap:8px;align-items:flex-end}

.pill{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.4px}
.pill.live{background:#dcfce7;color:#15803d}
.pill.upcoming{background:#dbeafe;color:#1d4ed8}
.pill.past{background:#f1f5f9;color:#94a3b8}
.live-ind{width:7px;height:7px;border-radius:50%;background:#16a34a;animation:pulse 1.4s infinite}

/* WAITING NOTICE */
.waiting-notice{background:linear-gradient(135deg,#eff6ff,#eef2ff);border:1.5px solid #bfdbfe;border-radius:var(--radius);padding:18px 22px;font-size:14px;color:#1e40af;display:flex;align-items:center;gap:12px;margin-bottom:20px}
.spin{animation:spin 1.6s linear infinite;font-size:20px;display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}

.empty{text-align:center;padding:60px 20px;color:var(--muted)}
.empty .icon{font-size:52px;margin-bottom:12px}

/* Permission banner */
.perm-banner{background:#1e293b;color:#f8fafc;border-radius:12px;padding:14px 20px;font-size:13px;margin-bottom:0;display:flex;align-items:center;gap:10px}
.perm-banner a{color:#60a5fa;text-decoration:underline}

@media(max-width:600px){
  .form-grid{grid-template-columns:1fr}
  .meeting-card{flex-direction:column;gap:14px}
  .meeting-actions{flex-direction:row;flex-wrap:wrap}
  .page-header{flex-direction:column;align-items:flex-start;gap:8px}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <span class="brand">📚 EduPortal</span>
  <?php if($role==='teacher'): ?>
    <a href="teacher-dashboard.php">Home</a>
    <a href="study-materials.php">Materials</a>
    <a href="homework.php">Homework</a>
    <a href="meetings.php" class="active">Meetings</a>
    <a href="announcements.php">Announcements</a>
  <?php else: ?>
    <a href="homepage.php">Home</a>
    <a href="study-materials.php">Materials</a>
    <a href="homework.php">Homework</a>
    <a href="meetings.php" class="active">Meetings</a>
    <a href="announcements.php">Announcements</a>
  <?php endif; ?>
  <div class="spacer"></div>
  <a href="logout.php">👋 Logout</a>
</nav>

<?php if ($joining_meeting): ?>
<!-- ══════════════════════════════════════
     LIVE VIDEO CALL SCREEN
══════════════════════════════════════ -->
<div class="call-overlay" id="callOverlay">
  <div class="call-header">
    <div class="call-title">
      <span class="live-dot"></span>
      📹 <?= htmlspecialchars($joining_meeting['title']) ?>
      &nbsp;·&nbsp;
      <span style="font-weight:400;font-size:13px;opacity:.75">
        Hosted by <?= htmlspecialchars($joining_meeting['teacher_name']) ?>
      </span>
    </div>

    <div style="display:flex;align-items:center;gap:10px">
      <div class="perm-banner" id="permBanner" style="display:none">
        🎥 Camera/mic blocked?
        <a href="https://support.google.com/chrome/answer/2693767" target="_blank">Allow in browser settings</a>
      </div>

      <?php if($role==='teacher'): ?>
        <form method="POST" id="endForm">
          <input type="hidden" name="action" value="end">
          <input type="hidden" name="meeting_id" value="<?= $joining_meeting['id'] ?>">
          <button type="button" class="btn-hangup" onclick="endMeeting()">⏹ End Meeting for Everyone</button>
        </form>
      <?php else: ?>
        <button class="btn-hangup" onclick="leaveCall()" style="background:#475569">🚪 Leave Call</button>
      <?php endif; ?>
    </div>
  </div>

  <div id="jitsi-container"></div>
</div>

<script>
const ROOM       = <?= json_encode($joining_meeting['room_name']) ?>;
const MYNAME     = <?= json_encode($firstName) ?>;
const IS_TEACHER = <?= json_encode($role === 'teacher') ?>;

// Show permission helper after 4 s if camera hasn't activated
const permTimer = setTimeout(() => {
  document.getElementById('permBanner').style.display = 'flex';
}, 4000);

const api = new JitsiMeetExternalAPI("meet.jit.si", {
  roomName: ROOM,
  width: "100%",
  height: "100%",
  parentNode: document.getElementById("jitsi-container"),
  userInfo: {
    displayName: (IS_TEACHER ? "🎓 Prof. " : "🎒 ") + MYNAME
  },
  configOverwrite: {
    // ── Camera & mic ON for everyone by default ──────────────
    startWithAudioMuted: false,
    startWithVideoMuted: false,

    // ── Cleaner UX ────────────────────────────────────────────
    enableWelcomePage: false,
    prejoinPageEnabled: true,      // shows device-picker before entering
    disableDeepLinking: true,
    enableNoisyMicDetection: true,
    enableNoAudioDetection: true,
    disableSimulcast: false,

    // ── Permissions ───────────────────────────────────────────
    requireDisplayName: true,
    enableUserRolesBasedOnToken: false,
  },
  interfaceConfigOverwrite: {
    SHOW_JITSI_WATERMARK: false,
    SHOW_WATERMARK_FOR_GUESTS: false,
    // Full toolbar so everyone can control camera, mic, share screen
    TOOLBAR_BUTTONS: IS_TEACHER
      ? [
          'microphone','camera','desktop','chat','participants-pane',
          'tileview','filmstrip','security','mute-everyone',
          'recording','hangup','raisehand','settings'
        ]
      : [
          'microphone','camera','desktop','chat',
          'tileview','filmstrip','raisehand','hangup','settings'
        ],
    SETTINGS_SECTIONS: ['devices','language'],
    DEFAULT_REMOTE_DISPLAY_NAME: 'Participant',
    FILM_STRIP_MAX_HEIGHT: 150,
    VIDEO_QUALITY_LABEL_DISABLED: false,
  }
});

// Hide permission banner once video track starts
api.addListener('videoConferenceJoined', () => {
  clearTimeout(permTimer);
  document.getElementById('permBanner').style.display = 'none';
});

// Student leaves → back to meetings list
api.addListener('videoConferenceLeft', () => {
  if (!IS_TEACHER) window.location.href = 'meetings.php';
});

function leaveCall() {
  api.executeCommand('hangup');
  setTimeout(() => { window.location.href = 'meetings.php'; }, 700);
}

function endMeeting() {
  if (!confirm('End this meeting for all participants?')) return;
  api.executeCommand('hangup');
  setTimeout(() => { document.getElementById('endForm').submit(); }, 700);
}
</script>

<?php else: ?>
<!-- ══════════════════════════════════════
     MEETINGS LIST / SCHEDULE PAGE
══════════════════════════════════════ -->
<div class="page">

  <?php if($message): ?>
    <div class="alert <?= $msgType ?>">
      <?= $msgType==='success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div class="page-header">
    <h1>📅 Meetings</h1>
    <?php if($role==='student'): ?>
      <span style="font-size:13px;color:var(--muted)">Live classes appear here when your teacher starts them</span>
    <?php endif; ?>
  </div>

  <!-- SCHEDULE FORM (teacher) -->
  <?php if($role==='teacher'): ?>
  <div class="form-card">
    <h2>🗓️ Schedule a New Meeting</h2>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="form-grid">
        <div class="form-group full">
          <label>Meeting Title *</label>
          <input type="text" name="title" placeholder="e.g. Chapter 5 – Data Structures" required>
        </div>
        <div class="form-group">
          <label>Date *</label>
          <input type="date" name="meet_date" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label>Time *</label>
          <input type="time" name="meet_time" required>
        </div>
        <div class="form-group full">
          <label>Description / Agenda</label>
          <textarea name="description" placeholder="Topics to be covered in this session (optional)"></textarea>
        </div>
      </div>
      <div class="form-footer">
        <button type="submit" class="btn btn-primary">📅 Schedule Meeting</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- WAITING NOTICE FOR STUDENTS (no live meeting) -->
  <?php
  $anyLive = count(array_filter($meetings, fn($m) => $m['is_live'])) > 0;
  if ($role === 'student' && !$anyLive):
  ?>
  <div class="waiting-notice">
    <span class="spin">🔄</span>
    <div>
      <strong>No live class right now.</strong>
      When your teacher starts a meeting, a glowing <em>Join Live Class</em> button will appear.
      This page auto-refreshes every 15 seconds.
    </div>
  </div>
  <script>setTimeout(() => location.reload(), 15000);</script>
  <?php endif; ?>

  <!-- MEETINGS LIST -->
  <div class="section-title">
    <?= $role==='teacher' ? '📋 Your Meetings' : '📋 Scheduled Classes' ?>
    <span class="badge"><?= count($meetings) ?></span>
  </div>

  <?php if (empty($meetings)): ?>
    <div class="empty">
      <div class="icon">📭</div>
      <p><?= $role==='teacher' ? "No meetings scheduled yet." : "No classes scheduled yet." ?></p>
    </div>
  <?php else: ?>
  <div class="meetings-list">
    <?php foreach($meetings as $i => $m):
      $dt      = new DateTime($m['meet_date'].' '.$m['meet_time']);
      $now     = new DateTime();
      $isLive  = (bool)$m['is_live'];
      $isPast  = ($dt < $now) && !$isLive;
    ?>
    <div class="meeting-card <?= $isLive ? 'is-live' : '' ?>" style="animation-delay:<?= $i*.06 ?>s">

      <div class="date-block">
        <div class="day"><?= $dt->format('d') ?></div>
        <div class="mon"><?= $dt->format('M') ?></div>
      </div>

      <div class="meeting-body">
        <div class="meeting-title"><?= htmlspecialchars($m['title']) ?></div>
        <div class="meeting-meta">
          <span>🕐 <?= $dt->format('g:i A') ?></span>
          <span>📆 <?= $dt->format('D, d M Y') ?></span>
          <span>👤 <?= htmlspecialchars($m['teacher_name']) ?></span>
          <?php if($isLive): ?>
            <span style="color:#16a34a;font-weight:700">🟢 Live now</span>
          <?php endif; ?>
        </div>
        <?php if(!empty($m['description'])): ?>
          <div class="meeting-desc"><?= nl2br(htmlspecialchars($m['description'])) ?></div>
        <?php endif; ?>
      </div>

      <div class="meeting-actions">

        <!-- Status pill -->
        <?php if($isLive): ?>
          <span class="pill live"><span class="live-ind"></span> Live</span>
        <?php elseif($isPast): ?>
          <span class="pill past">Ended</span>
        <?php else: ?>
          <span class="pill upcoming">Upcoming</span>
        <?php endif; ?>

        <!-- TEACHER ACTIONS -->
        <?php if($role==='teacher'): ?>

          <?php if(!$isLive && !$isPast): ?>
            <form method="POST">
              <input type="hidden" name="action" value="start">
              <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-start">▶ Start Call</button>
            </form>
          <?php endif; ?>

          <?php if($isLive): ?>
            <a href="meetings.php?join=<?= $m['id'] ?>" class="btn btn-rejoin">📹 Rejoin</a>
            <form method="POST">
              <input type="hidden" name="action" value="end">
              <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-end-meeting" onclick="return confirm('End this live meeting?')">⏹ End Call</button>
            </form>
          <?php endif; ?>

          <?php if(!$isLive): ?>
            <form method="POST" onsubmit="return confirm('Delete this meeting?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-delete">🗑 Delete</button>
            </form>
          <?php endif; ?>

        <!-- STUDENT ACTIONS -->
        <?php else: ?>

          <?php if($isLive): ?>
            <a href="meetings.php?join=<?= $m['id'] ?>" class="btn btn-join-live">📹 Join Live Class</a>
          <?php elseif(!$isPast): ?>
            <span style="font-size:12px;color:var(--muted);text-align:right">Waiting for<br>teacher to start…</span>
          <?php endif; ?>

        <?php endif; ?>

      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>

</body>
</html>
<?php $conn->close(); ?>