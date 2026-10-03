<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$candidate_id = (int)$_POST['candidate_id'];

// Check if user already voted OR requested a custom name
$stmt = $conn->prepare("SELECT has_voted, custom_name_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['has_voted'] == 1 || $user['custom_name_status'] == 'pending' || $user['custom_name_status'] == 'approved') {
    // Block action
    die("You have already participated. You cannot vote and request a custom name both.");
}

// Record vote
$stmt = $conn->prepare("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?");
$stmt->bind_param("i", $candidate_id);
$stmt->execute();

// Check if a row was actually updated (Candidate exists)
if ($stmt->affected_rows > 0) {
    // 2. Mark user as voted
    $stmt = $conn->prepare("UPDATE users SET has_voted = 1, voted_for = ? WHERE id = ?");
	$stmt->bind_param("ii", $candidate_id, $user_id);
    $stmt->execute();

    // 3. Redirect to RECEIPT instead of dashboard
    header("Location: receipt.php?user_id=$user_id&candidate_id=$candidate_id");
    exit();
} else {
    // Candidate ID didn't exist (invalid request)
    die("Error: Invalid Candidate Selected.");
}

header("Location: receipt.php?user_id=$user_id&candidate_id=$candidate_id");
exit();
?>