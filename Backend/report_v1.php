<!-- <?php
session_start();
include("connect.php"); // Ensure the database connection

// Get the student's ID from the session
$student_id = $_SESSION['user_id'];

// Create the students table if it does not exist
$query = "SELECT id, firstName AS name, role FROM users WHERE id = '$student_id' AND role = 'student'";
$result = mysqli_query($conn, $query);
$student_data = mysqli_fetch_assoc($result);


// Query to get the student's exam marks data
$query = "SELECT * FROM exam_marks WHERE student_id = '$student_id'";
$result = mysqli_query($conn, $query);
$exam_marks_data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $exam_marks_data[] = $row;
}

// Query to get the student's attendance data
$query = "SELECT * FROM attendance WHERE student_id = '$student_id'";
$result = mysqli_query($conn, $query);
$attendance_data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $attendance_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .navbar {
            background-color: #4CAF50;
            overflow: hidden;
            padding: 10px 0;
            text-align: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 14px 20px;
            display: inline-block;
        }
        .navbar a:hover {
            background-color: #45a049;
            border-radius: 5px;
        }
        .report-card {
            width: 80%;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2, h3 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="student-dashboard.php">Home</a>
        <a href="report-card.php">Report Card</a>
        <a href="homework.php">Homework</a>
        <a href="meetings.php">Meetings</a>
        <a href="announcements.php">Announcements</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="report-card">
        <h2>Student Report Card</h2>
        <table>
            <tr>
                <th>Name</th>
                <td><?= $student_data['name'] ?></td>
            </tr>
            <tr>
                <th>Class</th>
                <td>T.Y. B.Sc.</td>
            </tr>
            <tr>
                <th>Section</th>
                <td>Computer Science 'A'</td>
            </tr>
        </table><br>
        <h3>Academic Performance</h3>
        <table>
            <tr>
                <th>Semester</th>
                <th>Performance</th>
            </tr>
        
            <tr>
                <td>Semester I</td>
                <td>8.9 CGPA</td>
            </tr>

            <tr>
                <td>Semester II</td>
                <td>9.5 CGPA</td>
            </tr>

            <tr>
                <td>Semester III</td>
                <td>8.1 CGPA</td>
            </tr>  
        </table><br>
        <h3>Attendance Report</h3>
        <table>
            <tr>
                <th>Semester</th>
                <th>Attendance</th>
            </tr>
            <tr>
                <td>Semester I</td>
                <td>90%</td>
            </tr>
            <tr>
                <td>Semester II</td>
                <td>86%</td>
            </tr>
            <tr>
                <td>Semester III</td>
                <td>90%</td>
            </tr>
            <tr>
                <td>Semester IV</td>
                <td>80%</td>
            </tr>
            <tr>
                <td>Semester V</td>
                <td>90%</td>
            </tr>
            
        </table>
    </div>
</body>
</html> -->
