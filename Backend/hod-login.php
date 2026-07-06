<?php
session_start();

if(isset($_SESSION['hod']) && $_SESSION['hod'] === true){
    header("Location: hod-dashboard.php");
    exit();
}

define('HOD_PASSCODE', 'HODETC2025');

$error = '';
if(isset($_POST['passcode'])){
    if(trim($_POST['passcode']) === HOD_PASSCODE){
        $_SESSION['hod'] = true;
        $_SESSION['hod_name'] = 'HOD';
        header("Location: hod-dashboard.php");
        exit();
    } else {
        $error = 'Invalid Passcode!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Login – ScholarPoint</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#0f1e3d 0%,#1e3a8a 60%,#4f46e5 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .card{background:#fff;border-radius:20px;padding:48px 40px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);text-align:center}
        .icon{font-size:56px;margin-bottom:16px}
        h1{font-size:24px;font-weight:800;color:#0f1e3d;margin-bottom:6px}
        .sub{font-size:14px;color:#64748b;margin-bottom:28px}
        .dept{display:inline-block;background:#dbeafe;color:#1d4ed8;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;margin-bottom:24px}
        input[type=password]{width:100%;padding:12px 16px;border:2px solid #dde3f5;border-radius:10px;font-size:15px;font-family:'DM Sans',sans-serif;outline:none;transition:.18s;margin-bottom:16px;text-align:center;letter-spacing:4px}
        input[type=password]:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .btn{width:100%;padding:13px;background:#0f1e3d;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:.2s;font-family:'DM Sans',sans-serif}
        .btn:hover{background:#2563eb}
        .error{background:#fee2e2;color:#dc2626;padding:10px;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:16px}
        .back{display:block;margin-top:20px;color:#64748b;font-size:13px;text-decoration:none}
        .back:hover{color:#2563eb}
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🏛️</div>
        <h1>HOD Portal</h1>
        <div class="dept">E&TC Department</div>
        <div class="sub">Enter HOD Passcode to access the dashboard</div>
        <?php if($error): ?>
            <div class="error">❌ <?= $error ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="password" name="passcode" placeholder="Enter Passcode" autofocus required>
            <button type="submit" class="btn">🔐 Login as HOD</button>
        </form>
        <a href="index.php" class="back">← Back to Student/Teacher Login</a>
    </div>
</body>
</html>
