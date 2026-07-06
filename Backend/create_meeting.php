<?php
session_start();
include("connect.php");

// Restrict access to teachers only
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$firstName  = $_SESSION['firstName'];
$teacher_id = $_SESSION['user_id'];
$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meetingTitle       = trim($_POST['meeting_title']);
    $meetingDate        = $_POST['meeting_date'];
    $meetingTime        = $_POST['meeting_time'];
    $meetingDescription = trim($_POST['meeting_description']);

    if ($meetingTitle && $meetingDate && $meetingTime) {
        $slug      = preg_replace('/[^a-z0-9]+/', '-', strtolower($meetingTitle));
        $room_name = 'eduportal-' . $slug . '-' . bin2hex(random_bytes(4));

        // DEBUG: show exact DB error if prepare fails
        $sql  = "INSERT INTO meetings (teacher_id, teacher_name, title, description, meet_date, meet_time, room_name) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("<h2 style='color:red;font-family:monospace;padding:30px'>
                PREPARE FAILED<br><br>
                MySQL Error: " . $conn->error . "<br><br>
                MySQL Errno: " . $conn->errno . "<br><br>
                SQL used: <code>" . htmlspecialchars($sql) . "</code><br><br>
                DB selected: <code>" . $conn->query('SELECT DATABASE()')->fetch_row()[0] . "</code>
            </h2>");
        }

        $stmt->bind_param("issssss", $teacher_id, $firstName, $meetingTitle, $meetingDescription, $meetingDate, $meetingTime, $room_name);

        if ($stmt->execute()) {
            $successMessage = "Meeting created successfully!";
        } else {
            $errorMessage = "Error creating meeting: " . $stmt->error;
        }
        $stmt->close();

    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Meeting</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; min-height: 100vh; }
        .header { background-color: #4CAF50; color: white; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; }
        nav { display: flex; justify-content: center; background-color: #333; padding: 10px; flex-wrap: wrap; gap: 4px; }
        nav a { color: white; text-decoration: none; padding: 10px 20px; font-size: 16px; border-radius: 5px; transition: 0.3s; }
        nav a:hover, nav a.active { background-color: #575757; }
        .container { max-width: 540px; margin: 40px auto; background: white; padding: 36px 32px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.10); }
        .container h2 { text-align: center; color: #333; margin-bottom: 28px; font-size: 22px; }
        .message { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 12px 16px; border-radius: 8px; font-weight: 600; margin-bottom: 20px; text-align: center; }
        .error-message { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; padding: 12px 16px; border-radius: 8px; font-weight: 600; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: #444; margin-bottom: 7px; font-size: 14px; }
        .form-group input, .form-group textarea { width: 100%; padding: 11px 14px; border: 1.5px solid #ddd; border-radius: 7px; font-size: 15px; font-family: Arial, sans-serif; transition: border-color 0.2s, box-shadow 0.2s; outline: none; background: #fafafa; }
        .form-group input:focus, .form-group textarea:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.13); background: #fff; }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .btn-submit { width: 100%; padding: 13px; background-color: #4CAF50; color: white; border: none; border-radius: 7px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.2s; margin-top: 6px; }
        .btn-submit:hover { background-color: #388E3C; }
        .back-link { display: block; text-align: center; margin-top: 18px; color: #4CAF50; text-decoration: none; font-size: 15px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="header">📅 Create Meeting</div>
<nav>
    <a href="teacher-dashboard.php">🏠 Home</a>
    <a href="study-materials.php">📚 Study Materials</a>
    <a href="assignments.php">📝 Assignments</a>
    <a href="meetings.php">🎥 Meetings</a>
    <a href="announcements.php">📢 Announcements</a>
    <a href="logout.php">🚪 Logout</a>
</nav>

<div class="container">
    <h2>📅 Schedule a New Meeting</h2>

    <?php if ($successMessage): ?>
        <div class="message">✅ <?= htmlspecialchars($successMessage) ?></div>
        <p style="text-align:center;margin-bottom:16px;color:#555;font-size:14px">
            Go to <a href="meetings.php" style="color:#4CAF50;font-weight:600">Meetings page</a> to start the video call.
        </p>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="error-message">❌ <?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="meeting_title">Meeting Title *</label>
            <input type="text" id="meeting_title" name="meeting_title"
                   placeholder="e.g. Chapter 5 – Data Structures"
                   value="<?= isset($_POST['meeting_title']) ? htmlspecialchars($_POST['meeting_title']) : '' ?>"
                   required>
        </div>
        <div class="form-group">
            <label for="meeting_date">Meeting Date *</label>
            <input type="date" id="meeting_date" name="meeting_date"
                   min="<?= date('Y-m-d') ?>"
                   value="<?= isset($_POST['meeting_date']) ? htmlspecialchars($_POST['meeting_date']) : '' ?>"
                   required>
        </div>
        <div class="form-group">
            <label for="meeting_time">Meeting Time *</label>
            <input type="time" id="meeting_time" name="meeting_time"
                   value="<?= isset($_POST['meeting_time']) ? htmlspecialchars($_POST['meeting_time']) : '' ?>"
                   required>
        </div>
        <div class="form-group">
            <label for="meeting_description">Description / Agenda</label>
            <textarea id="meeting_description" name="meeting_description"
                      placeholder="Topics to be covered (optional)"><?= isset($_POST['meeting_description']) ? htmlspecialchars($_POST['meeting_description']) : '' ?></textarea>
        </div>
        <button type="submit" class="btn-submit">📅 Create Meeting</button>
    </form>

    <a class="back-link" href="meetings.php">🔙 Back to Meetings</a>
</div>

</body>
</html>
<?php $conn->close(); ?>