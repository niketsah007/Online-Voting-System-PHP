<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$custom_name = trim($_POST['custom_name']);

// Check if user already voted OR requested a custom name
$stmt = $conn->prepare("SELECT has_voted, custom_name_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['has_voted'] == 1 || $user['custom_name_status'] == 'pending' || $user['custom_name_status'] == 'approved') {
    die("You have already participated. You cannot request a custom name and vote both.");
}

// Save custom name request
$stmt = $conn->prepare("UPDATE users SET custom_name = ?, custom_name_status = 'pending' WHERE id = ?");
$stmt->bind_param("si", $custom_name, $user_id);
$stmt->execute();

header("Location: dashboard.php");
exit();
?>