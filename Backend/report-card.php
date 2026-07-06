<!-- <?php
session_start();
include("connect.php");

// Redirect if student ID is not set
if (!isset($_SESSION['student_id'])) {
    die("Error: No student ID found. Please log in again.");
}
$student_id = $_SESSION['student_id'];

// Fetch student details
$query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();

if (!$student_data) {
    die("Error: Student record not found.");
}

// Fetch exam marks
$query = "SELECT * FROM exam_marks WHERE student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$exam_marks = $stmt->get_result();

// Fetch attendance
$query = "SELECT * FROM attendance WHERE student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$attendance = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            text-align: center;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #4CAF50;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Student Report Card</h2>
        <table>
            <tr><th>Name</th><td><?= htmlspecialchars($student_data['name']) ?></td></tr>
            <tr><th>Class</th><td><?= htmlspecialchars($student_data['class']) ?></td></tr>
            <tr><th>Section</th><td><?= htmlspecialchars($student_data['section']) ?></td></tr>
        </table>
        
        <h2>Exam Marks</h2>
        <table>
            <tr><th>Exam Name</th><th>Marks</th></tr>
            <?php while ($row = $exam_marks->fetch_assoc()): ?>
            <tr><td><?= htmlspecialchars($row['exam_name']) ?></td><td><?= htmlspecialchars($row['marks']) ?></td></tr>
            <?php endwhile; ?>
        </table>
        
        <h2>Attendance Report</h2>
        <table>
            <tr><th>Month</th><th>Attendance</th></tr>
            <?php while ($row = $attendance->fetch_assoc()): ?>
            <tr><td><?= htmlspecialchars($row['month']) ?></td><td><?= htmlspecialchars($row['attendance']) ?></td></tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html> -->
