<?php
session_start();
include("connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $plainPassword = trim($_POST['password']); // FIXED: plain password घेतो

    if (!empty($email) && !empty($plainPassword)) {
        // FIXED: फक्त email ने fetch करतो
        $stmt = $conn->prepare("SELECT id, firstName, role, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // FIXED: password_verify वापरतो
            if (password_verify($plainPassword, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user['role'];
                $_SESSION['firstName'] = $user['firstName'];

                if ($user['role'] == 'teacher') {
                    header("Location: teacher-dashboard.php");
                } else {
                    header("Location: homepage.php");
                }
                exit();
            } else {
                $errorMessage = "Invalid email or password!";
            }
        } else {
            $errorMessage = "No account found with that email!";
        }
    } else {
        $errorMessage = "Please enter both email and password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <?php if (isset($errorMessage)) { echo "<p class='error'>$errorMessage</p>"; } ?>
        <form method="POST" action="">
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="index.php">Register here</a></p>
    </div>
</body>
</html>
