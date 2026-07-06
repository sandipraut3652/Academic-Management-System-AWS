<?php
session_start();
include("connect.php");

// Restrict access to only teachers
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$message = "";
$teacher_name = isset($_SESSION['firstName']) ? $_SESSION['firstName'] : "Unknown Teacher";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $announcement_text = $_POST['announcement_text'];

    $stmt = $conn->prepare("INSERT INTO announcements (teacher_name, announcement_text) VALUES (?, ?)");
    $stmt->bind_param("ss", $teacher_name, $announcement_text);
    
    if ($stmt->execute()) {
        $message = "Announcement posted successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all announcements
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Announcement</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 24px;
        }
        nav {
            display: flex;
            justify-content: center;
            background-color: #333;
            padding: 10px;
        }
        nav a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            margin: 0 10px;
            font-size: 18px;
        }
        nav a:hover {
            background-color: #575757;
            border-radius: 5px;
        }
        .container {
            width: 50%;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            resize: none;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #388E3C;
        }
        .announcement {
            background: #fff;
            padding: 15px;
            margin-top: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: left;
        }
        .announcement strong {
            font-size: 18px;
            color: #333;
        }
        .announcement p {
            font-size: 16px;
            color: #555;
        }
        .announcement small {
            color: gray;
        }
    </style>
</head>
<body>
    <header>Teacher Dashboard</header>
    <nav>
        <a href="teacher-dashboard.php">Home</a>
        <a href="study-materials.php">Study Materials</a>
        <a href="homework.php">Assignments</a>
        <a href="create_meeting.php">Meetings</a>
        <a href="announcements.php">Announcements</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="container">
        <h1>Create Announcement</h1>
        <?php if (!empty($message)) echo "<p>$message</p>"; ?>
        <form method="POST">
            <textarea name="announcement_text" placeholder="Write your announcement..." required></textarea>
            <button type="submit">Post Announcement</button>
        </form>
    </div>

    <div class="container">
        <h2>All Announcements</h2>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <div class="announcement">
                <strong><?php echo htmlspecialchars($row['teacher_name']); ?>:</strong>
                <p><?php echo htmlspecialchars($row['announcement_text']); ?></p>
                <small>Posted on: <?php echo $row['created_at']; ?></small>
            </div>
        <?php } ?>
    </div>
</body>
</html>