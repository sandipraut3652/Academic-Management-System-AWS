<?php
session_start();
if (isset($_SESSION['email'])) {
    if ($_SESSION['role'] == 'teacher') {
        header("Location: teacher-dashboard.php");
    } else {
        if (($_SESSION['status'] ?? '') == 'pending') {
            header("Location: pending.php");
        } else {
            header("Location: homepage.php");
        }
    }
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
    <title>ScholarPoint – Register & Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .hod-link{position:fixed;bottom:24px;right:24px;background:#0f1e3d;color:#fff;padding:10px 20px;border-radius:12px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 4px 16px rgba(0,0,0,.3);transition:.2s;font-family:'DM Sans',sans-serif}
        .hod-link:hover{background:#2563eb}
    </style>
</head>
<body>
    <!-- SIGN UP FORM -->
    <div class="container" id="signup" style="display:none;">
      <h1 class="form-title">Register</h1>
      <form method="post" action="register.php">
        <div class="input-group">
           <i class="fas fa-user"></i>
           <input type="text" name="fName" placeholder="First Name" required>
           <label>First Name</label>
        </div>
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="lName" placeholder="Last Name" required>
            <label>Last Name</label>
        </div>
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="Email" required>
            <label>Email</label>
        </div>
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Password" required>
            <label>Password</label>
        </div>
        <div class="input-group">
            <i class="fas fa-user-tag"></i><br>
            <label>Select Role:</label>
            <select name="role" id="roleSelect" required onchange="toggleFields()">
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
            </select>
        </div>
        <div class="input-group" id="classField">
            <i class="fas fa-graduation-cap"></i><br>
            <label>Select Class:</label>
            <select name="class_id" id="classSelect">
                <option value="1">SE - Second Year</option>
                <option value="2">TE - Third Year</option>
                <option value="3">BE - Final Year</option>
            </select>
        </div>
        <div class="input-group" id="teacherCodeField" style="display:none;">
            <i class="fas fa-key"></i>
            <input type="password" name="teacher_code" id="teacherCode" placeholder="Teacher Secret Code">
            <label>Teacher Secret Code</label>
        </div>
        <br>
        <input type="submit" class="btn" value="Sign Up" name="signUp">
      </form>
      <p class="or"> --------or-------- </p>
      <div class="icons">
        <i class="fab fa-google"></i>
        <i class="fab fa-facebook"></i>
      </div>
      <div class="links">
        <p>Already Have Account?</p>
        <button id="signInButton">Sign In</button>
      </div>
    </div>

    <!-- SIGN IN FORM -->
    <div class="container" id="signIn">
        <h1 class="form-title">Sign In</h1>
        <form method="post" action="register.php">
          <div class="input-group">
              <i class="fas fa-envelope"></i>
              <input type="email" name="email" placeholder="Email" required>
              <label>Email</label>
          </div>
          <div class="input-group">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" placeholder="Password" required>
              <label>Password</label>
          </div>
          <p class="recover"><a href="#">Recover Password</a></p>
         <input type="submit" class="btn" value="Sign In" name="signIn">
        </form>
        <p class="or"> --------or-------- </p>
        <div class="icons">
          <i class="fab fa-google"></i>
          <i class="fab fa-facebook"></i>
        </div>
        <div class="links">
          <p>Don't have account yet?</p>
          <button id="signUpButton">Sign Up</button>
        </div>
    </div>

    <!-- HOD Login Button -->
    <a href="hod-login.php" class="hod-link">🏛️ HOD Login</a>

    <script src="script.js"></script>
    <script>
    function toggleFields() {
        const role = document.getElementById('roleSelect').value;
        const classField = document.getElementById('classField');
        const teacherCodeField = document.getElementById('teacherCodeField');
        const classSelect = document.getElementById('classSelect');
        const teacherCode = document.getElementById('teacherCode');
        if (role === 'student') {
            classField.style.display = 'block';
            teacherCodeField.style.display = 'none';
            classSelect.required = true;
            teacherCode.required = false;
        } else {
            classField.style.display = 'none';
            teacherCodeField.style.display = 'block';
            classSelect.required = false;
            teacherCode.required = true;
        }
    }
    toggleFields();
    </script>
</body>
</html>
