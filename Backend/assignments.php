<?php
session_start();
include("connect.php");

// Ensure the user is a teacher
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

// Fetch submitted assignments from students
$query = "SELECT assignments.id, assignments.uploaded_at, 
                 users.firstName, users.lastName, assignments.file_path
          FROM assignments
          JOIN users ON assignments.user_id = users.id
          ORDER BY assignments.uploaded_at DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Assignments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .download-btn {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        .download-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <header>Submitted Assignments</header>
    <div class="container">
        <h2>Student Assignments</h2>
        <table>
            <tr>
                <th>Student Name</th>
                <th>Submitted At</th>
                <th>Download</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['firstName'] . " " . $row['lastName']) ?></td>
                <td><?= htmlspecialchars($row['uploaded_at']) ?></td>
                <td>
                    <a class="download-btn" href="<?= htmlspecialchars($row['file_path']) ?>" download>
                        <i class="fas fa-download"></i> Download
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
