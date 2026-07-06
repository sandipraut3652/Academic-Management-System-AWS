<?php
session_start();
include("connect.php");

// Only teachers can access this endpoint
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

// Must be a POST request with a user_id
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['user_id'])) {
    header("Location: teacher-dashboard.php");
    exit();
}

$userIdToDelete = (int)$_POST['user_id'];

// Prevent teacher from deleting their own account
$currentEmail = $_SESSION['email'];

// Fetch the email of the user to be deleted
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $userIdToDelete);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash_msg'] = "User not found.";
    $_SESSION['flash_type'] = "danger";
    header("Location: teacher-dashboard.php");
    exit();
}

$userToDelete = $result->fetch_assoc();

if ($userToDelete['email'] === $currentEmail) {
    $_SESSION['flash_msg'] = "You cannot delete your own account.";
    $_SESSION['flash_type'] = "danger";
    header("Location: teacher-dashboard.php");
    exit();
}

// Perform deletion
$deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$deleteStmt->bind_param("i", $userIdToDelete);

if ($deleteStmt->execute()) {
    $_SESSION['flash_msg'] = "User deleted successfully.";
    $_SESSION['flash_type'] = "success";
} else {
    $_SESSION['flash_msg'] = "Failed to delete user.";
    $_SESSION['flash_type'] = "danger";
}

$deleteStmt->close();
$stmt->close();
$conn->close();

header("Location: teacher-dashboard.php");
exit();