<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}
if ($_SESSION['role'] === 'teacher' || $_SESSION['status'] === 'approved') {
    header("Location: homepage.php");
    exit();
}
$firstName = $_SESSION['firstName'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Pending – ScholarPoint</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #0f1e3d 0%, #1e3a8a 60%, #4f46e5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 48px 40px;
            text-align: center;
            max-width: 460px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .icon { font-size: 64px; margin-bottom: 20px; }
        h1 { font-size: 24px; font-weight: 800; color: #0f1e3d; margin-bottom: 12px; }
        p { font-size: 15px; color: #64748b; line-height: 1.6; margin-bottom: 8px; }
        .highlight { color: #2563eb; font-weight: 700; }
        .status-badge {
            display: inline-block;
            background: #fef3c7;
            color: #d97706;
            font-size: 13px;
            font-weight: 700;
            padding: 6px 18px;
            border-radius: 20px;
            margin: 20px 0;
            border: 1px solid #fde68a;
        }
        .logout-btn {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 28px;
            background: #0f1e3d;
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .logout-btn:hover { background: #2563eb; }
        .steps {
            background: #f8faff;
            border-radius: 12px;
            padding: 18px 20px;
            margin: 20px 0;
            text-align: left;
        }
        .steps p { font-size: 13px; color: #475569; margin-bottom: 6px; }
        .steps p:last-child { margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⏳</div>
        <h1>Hi, <?= htmlspecialchars($firstName) ?>!</h1>
        <div class="status-badge">🔔 Approval Pending</div>
        <p>तुझी registration successful झाली आहे.</p>
        <p>आता <span class="highlight">Teacher तुला approve</span> करेपर्यंत थोडी वाट बघावी लागेल.</p>
        <div class="steps">
            <p>✅ Step 1: Registration Complete</p>
            <p>⏳ Step 2: Teacher Approval (Pending)</p>
            <p>🔒 Step 3: Class Access Unlock</p>
        </div>
        <p>Approved झाल्यावर login केल्यावर automatically class मध्ये जाशील.</p>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</body>
</html>
