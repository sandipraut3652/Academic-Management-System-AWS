<?php
session_start();
if(isset($_SESSION['email'])){
    if($_SESSION['role'] === 'teacher') header("Location: teacher-dashboard.php");
    elseif(($_SESSION['status'] ?? '') === 'pending') header("Location: pending.php");
    else header("Location: homepage.php");
    exit();
}
if(isset($_SESSION['hod']) && $_SESSION['hod'] === true){
    header("Location: hod-dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScholarPoint — AWS Powered Academic Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--navy:#050d1f;--blue:#2563eb;--blue2:#3b82f6;--indigo:#4f46e5;--accent:#60a5fa;--gold:#f59e0b;--white:#f8faff;--muted:rgba(248,250,255,0.55)}
        html{scroll-behavior:smooth}
        body{font-family:'DM Sans',sans-serif;background:var(--navy);color:var(--white);min-height:100vh;overflow-x:hidden}

        /* NAVBAR */
        nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:18px 48px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(16px);background:rgba(5,13,31,0.75);border-bottom:1px solid rgba(96,165,250,0.1);transition:.3s}
        .nav-brand{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--white);letter-spacing:-0.5px;display:flex;align-items:center;gap:10px}
        .nav-dot{width:8px;height:8px;background:var(--blue2);border-radius:50%;animation:blink 2s infinite}
        @keyframes blink{0%,100%{opacity:1}50%{opacity:0.3}}
        .nav-right{display:flex;align-items:center;gap:14px}
        .nav-badge{font-size:12px;font-weight:600;color:var(--accent);background:rgba(96,165,250,0.1);border:1px solid rgba(96,165,250,0.2);padding:5px 14px;border-radius:20px}
        .nav-btn{font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;color:#fff;background:var(--blue);border:none;padding:9px 22px;border-radius:10px;cursor:pointer;text-decoration:none;transition:.2s}
        .nav-btn:hover{background:var(--blue2);transform:translateY(-1px)}

        /* HERO with college photo */
        .hero{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:120px 24px 80px;overflow:hidden}
        .hero-bg{position:absolute;inset:0;background-image:url('college.jpg');background-size:cover;background-position:center;z-index:0}
        .hero-overlay{position:absolute;inset:0;background:linear-gradient(to bottom, rgba(5,13,31,0.72) 0%, rgba(5,13,31,0.60) 50%, rgba(5,13,31,0.90) 100%);z-index:1}
        .hero-inner{position:relative;z-index:2;max-width:860px;animation:fadeUp 0.8s ease both}
        .hero-eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--accent);background:rgba(96,165,250,0.12);border:1px solid rgba(96,165,250,0.3);padding:6px 18px;border-radius:20px;margin-bottom:28px;letter-spacing:1.5px;text-transform:uppercase}
        .hero-title{font-family:'Syne',sans-serif;font-size:clamp(40px,8vw,82px);font-weight:800;line-height:1.0;letter-spacing:-2px;margin-bottom:20px}
        .hero-title .line1{display:block;color:var(--white);text-shadow:0 2px 20px rgba(0,0,0,0.5)}
        .hero-title .line2{display:block;background:linear-gradient(135deg,var(--accent) 0%,#a78bfa 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-sub{font-size:clamp(15px,2.5vw,19px);color:rgba(248,250,255,0.75);line-height:1.7;max-width:560px;margin:0 auto 36px}
        .hero-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:52px}
        .btn-primary{font-family:'DM Sans',sans-serif;font-size:15px;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--blue),var(--indigo));border:none;padding:14px 32px;border-radius:12px;cursor:pointer;text-decoration:none;transition:.25s;box-shadow:0 8px 32px rgba(37,99,235,0.4)}
        .btn-primary:hover{transform:translateY(-3px);box-shadow:0 14px 40px rgba(37,99,235,0.5)}
        .btn-outline{font-family:'DM Sans',sans-serif;font-size:15px;font-weight:600;color:var(--white);background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.25);padding:14px 32px;border-radius:12px;cursor:pointer;text-decoration:none;transition:.25s;backdrop-filter:blur(8px)}
        .btn-outline:hover{background:rgba(255,255,255,0.18);transform:translateY(-2px)}
        .stats-row{display:flex;justify-content:center;gap:36px;flex-wrap:wrap}
        .stat{text-align:center}
        .stat .num{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:var(--white);display:block;text-shadow:0 2px 10px rgba(0,0,0,0.5)}
        .stat .lbl{font-size:12px;color:rgba(248,250,255,0.55);font-weight:500}
        .stat-div{width:1px;background:rgba(255,255,255,0.15);align-self:stretch}

        /* SCROLL INDICATOR */
        .scroll-ind{position:absolute;bottom:32px;left:50%;transform:translateX(-50%);z-index:2;display:flex;flex-direction:column;align-items:center;gap:6px;color:rgba(248,250,255,0.4);font-size:11px;font-weight:500;letter-spacing:1px;text-transform:uppercase;animation:bounce 2s infinite}
        @keyframes bounce{0%,100%{transform:translateX(-50%) translateY(0)}50%{transform:translateX(-50%) translateY(6px)}}
        .scroll-ind::after{content:'';width:1px;height:32px;background:linear-gradient(to bottom,rgba(248,250,255,0.3),transparent)}

        /* FEATURES */
        .features{position:relative;z-index:1;padding:88px 24px}
        .features-inner{max-width:1000px;margin:0 auto}
        .section-label{text-align:center;font-size:11px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:2.5px;margin-bottom:12px}
        .section-title{font-family:'Syne',sans-serif;font-size:clamp(26px,5vw,42px);font-weight:800;text-align:center;margin-bottom:52px;letter-spacing:-1px}
        .features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
        .feature-card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:30px 26px;transition:.3s;position:relative;overflow:hidden}
        .feature-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--blue2),transparent);opacity:0;transition:.3s}
        .feature-card:hover{background:rgba(255,255,255,0.07);transform:translateY(-4px);border-color:rgba(96,165,250,0.2)}
        .feature-card:hover::before{opacity:1}
        .feature-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:16px}
        .fi-blue{background:rgba(37,99,235,0.2)}
        .fi-purple{background:rgba(79,70,229,0.2)}
        .fi-green{background:rgba(22,163,74,0.2)}
        .fi-amber{background:rgba(245,158,11,0.2)}
        .fi-teal{background:rgba(20,184,166,0.2)}
        .fi-rose{background:rgba(244,63,94,0.2)}
        .feature-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:700;margin-bottom:8px}
        .feature-desc{font-size:13px;color:var(--muted);line-height:1.7}

        /* ROLES */
        .roles{position:relative;z-index:1;padding:80px 24px;background:rgba(255,255,255,0.02)}
        .roles-inner{max-width:900px;margin:0 auto}
        .roles-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-top:44px}
        .role-card{border-radius:20px;padding:32px 22px;text-align:center;transition:.3s;text-decoration:none;display:block}
        .role-card.hod{background:linear-gradient(135deg,rgba(245,158,11,0.12),rgba(245,158,11,0.04));border:1px solid rgba(245,158,11,0.22)}
        .role-card.teacher{background:linear-gradient(135deg,rgba(37,99,235,0.12),rgba(37,99,235,0.04));border:1px solid rgba(37,99,235,0.22)}
        .role-card.student{background:linear-gradient(135deg,rgba(79,70,229,0.12),rgba(79,70,229,0.04));border:1px solid rgba(79,70,229,0.22)}
        .role-card:hover{transform:translateY(-6px)}
        .role-icon{font-size:44px;margin-bottom:14px;display:block}
        .role-name{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;margin-bottom:10px;color:var(--white)}
        .role-desc{font-size:13px;color:var(--muted);line-height:1.6;margin-bottom:20px}
        .role-btn{display:inline-block;font-size:13px;font-weight:700;padding:8px 20px;border-radius:8px;text-decoration:none;transition:.2s}
        .role-card.hod .role-btn{background:rgba(245,158,11,0.18);color:#f59e0b}
        .role-card.teacher .role-btn{background:rgba(37,99,235,0.18);color:var(--accent)}
        .role-card.student .role-btn{background:rgba(79,70,229,0.18);color:#a78bfa}

        /* AWS */
        .aws-section{position:relative;z-index:1;padding:60px 24px;text-align:center}
        .aws-inner{max-width:800px;margin:0 auto}
        .aws-label{font-size:11px;font-weight:700;color:rgba(248,250,255,0.3);text-transform:uppercase;letter-spacing:2.5px;margin-bottom:20px}
        .aws-badges{display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
        .aws-badge{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:10px;padding:9px 16px;font-size:13px;font-weight:600;color:rgba(248,250,255,0.45);display:flex;align-items:center;gap:7px;transition:.2s}
        .aws-badge:hover{background:rgba(255,255,255,0.09);color:var(--white)}
        .aws-badge .dot{width:6px;height:6px;border-radius:50%;background:var(--blue2)}

        /* COLLEGE PHOTO STRIP */
        .photo-strip{position:relative;height:280px;overflow:hidden}
        .photo-strip img{width:100%;height:100%;object-fit:cover;filter:brightness(0.6)}
        .photo-strip-overlay{position:absolute;inset:0;background:linear-gradient(to right,rgba(5,13,31,0.9),rgba(5,13,31,0.3),rgba(5,13,31,0.9));display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px}
        .photo-strip-title{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:#fff;text-align:center}
        .photo-strip-sub{font-size:14px;color:rgba(255,255,255,0.6);text-align:center}

        /* FOOTER */
        footer{position:relative;z-index:1;padding:28px 48px;border-top:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px}
        footer .brand{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--white)}
        footer .copy{font-size:13px;color:rgba(248,250,255,0.3)}
        footer .hod-link{font-size:13px;color:rgba(248,250,255,0.3);text-decoration:none;transition:.2s}
        footer .hod-link:hover{color:var(--gold)}

        @keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:640px){nav{padding:14px 20px}.nav-badge{display:none}footer{padding:20px 20px}}
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav>
    <div class="nav-brand">
        <div class="nav-dot"></div>
        ScholarPoint
    </div>
    <div class="nav-right">
        <span class="nav-badge">E&TC Department</span>
        <a href="index.php" class="nav-btn">Login →</a>
    </div>
</nav>

<!-- HERO with college photo background -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-inner">
        <div class="hero-eyebrow">🎓 Parikrama College of Engineering</div>
        <h1 class="hero-title">
            <span class="line1">Academic Portal</span>
            <span class="line2">Powered by AWS</span>
        </h1>
        <p class="hero-sub">A modern cloud-based learning platform for SE, TE & BE students and teachers of the Electronics & Computer Engineering department.</p>
        <div class="hero-btns">
            <a href="index.php" class="btn-primary">🚀 Get Started</a>
            <a href="#features" class="btn-outline">Explore Features ↓</a>
        </div>
        <div class="stats-row">
            <div class="stat"><span class="num">3</span><span class="lbl">Classes</span></div>
            <div class="stat-div"></div>
            <div class="stat"><span class="num">AWS</span><span class="lbl">Powered</span></div>
            <div class="stat-div"></div>
            <div class="stat"><span class="num">5+</span><span class="lbl">Features</span></div>
            <div class="stat-div"></div>
            <div class="stat"><span class="num">24/7</span><span class="lbl">Available</span></div>
        </div>
    </div>
    <div class="scroll-ind">Scroll</div>
</section>

<!-- FEATURES -->
<section class="features" id="features">
    <div class="features-inner">
        <div class="section-label">What's Inside</div>
        <h2 class="section-title">Everything in one place</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon fi-blue">📖</div>
                <div class="feature-title">Study Materials</div>
                <div class="feature-desc">Subject-wise notes, PDFs, and resources uploaded by teachers — accessible anytime, anywhere.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-purple">📹</div>
                <div class="feature-title">Live Meetings</div>
                <div class="feature-desc">Subject-specific live sessions via Jitsi Meet. Join with one click when teacher goes live.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-green">✅</div>
                <div class="feature-title">Attendance Tracking</div>
                <div class="feature-desc">Subject-wise attendance with percentage tracking and automatic warnings below 75%.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-amber">📝</div>
                <div class="feature-title">Assignments</div>
                <div class="feature-desc">Submit homework per subject. Teachers can download and review each student's submission.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-teal">📢</div>
                <div class="feature-title">Announcements</div>
                <div class="feature-desc">Subject-specific announcements from teachers, visible instantly to all enrolled students.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-rose">🏛️</div>
                <div class="feature-title">HOD Control Panel</div>
                <div class="feature-desc">HOD manages teachers, assigns subjects, approves students — complete department control.</div>
            </div>
        </div>
    </div>
</section>

<!-- COLLEGE PHOTO STRIP -->
<div class="photo-strip">
    <img src="college.jpg" alt="Parikrama College of Engineering">
    <div class="photo-strip-overlay">
        <div class="photo-strip-title">Parikrama College of Engineering</div>
        <div class="photo-strip-sub">Electronics & Computer Engineering Department</div>
    </div>
</div>

<!-- ROLES -->
<section class="roles" id="roles">
    <div class="roles-inner">
        <div class="section-label">Access Levels</div>
        <h2 class="section-title" style="text-align:center">Login as your role</h2>
        <div class="roles-grid">
            <a href="hod-login.php" class="role-card hod">
                <span class="role-icon">🏛️</span>
                <div class="role-name">HOD</div>
                <div class="role-desc">Manage teachers, assign subjects, approve students and control the department.</div>
                <span class="role-btn">HOD Login →</span>
            </a>
            <a href="index.php" class="role-card teacher">
                <span class="role-icon">👨‍🏫</span>
                <div class="role-name">Teacher</div>
                <div class="role-desc">Upload materials, take attendance, schedule meetings and review assignments.</div>
                <span class="role-btn">Teacher Login →</span>
            </a>
            <a href="index.php" class="role-card student">
                <span class="role-icon">🎒</span>
                <div class="role-name">Student</div>
                <div class="role-desc">Access subject resources, submit homework, join live sessions and track attendance.</div>
                <span class="role-btn">Student Login →</span>
            </a>
        </div>
    </div>
</section>

<!-- AWS BADGES -->
<section class="aws-section">
    <div class="aws-inner">
        <div class="aws-label">Deployed on Amazon Web Services</div>
        <div class="aws-badges">
            <div class="aws-badge"><span class="dot"></span> EC2 Ubuntu 22.04</div>
            <div class="aws-badge"><span class="dot"></span> RDS MySQL 8.0</div>
            <div class="aws-badge"><span class="dot"></span> Application Load Balancer</div>
            <div class="aws-badge"><span class="dot"></span> Custom VPC</div>
            <div class="aws-badge"><span class="dot"></span> CloudWatch Monitoring</div>
            <div class="aws-badge"><span class="dot"></span> IAM Roles</div>
            <div class="aws-badge"><span class="dot"></span> Security Groups</div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="brand">📚 ScholarPoint</div>
    <div class="copy">Parikrama College of Engineering · E&TC Department · 2025–26</div>
    <a href="hod-login.php" class="hod-link">🏛️ HOD Access</a>
</footer>

</body>
</html>
