<?php
// ENABLE ERRORS FOR DEBUGGING
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user details (NOW INCLUDING 'voted_for')
$stmt = $conn->prepare("SELECT password, has_voted, voted_for, custom_name, custom_name_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Force password change if default
if ($user_data['password'] === 'pass123') {
    header("Location: change_password.php?forced=1");
    exit();
}

$has_voted = $user_data['has_voted'];
$voted_for = $user_data['voted_for'];
$custom_name = $user_data['custom_name'];
$custom_status = $user_data['custom_name_status'];

// IF VOTED: FETCH CANDIDATE NAME FOR RECEIPT
$candidate_name = "Unknown"; // Default
if ($has_voted == 1 && $voted_for > 0) {
    $cStmt = $conn->prepare("SELECT name FROM candidates WHERE id = ?");
    $cStmt->bind_param("i", $voted_for);
    $cStmt->execute();
    $cRes = $cStmt->get_result();
    if ($row = $cRes->fetch_assoc()) {
        $candidate_name = $row['name'];
    }
} elseif ($custom_status == 'approved') {
    $candidate_name = $custom_name . " (Custom)";
}

// Generate Receipt URL for QR
$receiptUrl = "https://my-class-election.infinityfree.me/receipt.php/" . $user_id . "/" . $voted_for;
$timestamp = date("d-m-Y h:i A"); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard & Receipt</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
    <style>
        body { margin: 0; font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .dashboard-container { background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); width: 450px; animation: fadeIn 0.8s ease-in-out; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #333; font-size: 22px; }
        .header small { color: #666; font-size: 13px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 0.9rem; }
        .top-bar a { text-decoration: none; font-weight: 600; }
        hr { border: 0; border-top: 1px dashed #ddd; margin: 15px 0; }
        
        /* Receipt Styles */
        .receipt-box { text-align: center; }
        .details { text-align: left; margin: 15px 0; font-size: 14px; color: #444; }
        .details p { margin: 8px 0; }
        .success-box { background: #e6fffa; color: #00b894; padding: 12px; border-radius: 8px; margin: 20px 0; font-weight: bold; text-align: center; }
        #qrcode { margin: 20px auto; display: flex; justify-content: center; }
        
        /* Voting Styles */
        .candidate-box { display: block; background: #f9f9f9; padding: 14px; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: transform 0.2s, background 0.2s; }
        .candidate-box:hover { background: #eef2ff; transform: translateY(-2px); }
        .candidate-box input { margin-right: 10px; }
        button { margin-top: 15px; width: 100%; padding: 12px; border: none; border-radius: 8px; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; font-weight: 600; cursor: pointer; transition: transform 0.2s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.2); }
        
        @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }
    </style>
</head>
<body>
    <div class="dashboard-container">
        
        <div class="top-bar">
            <span>👤 <b><?php echo htmlspecialchars($username); ?></b></span>
            <div>
                <a href="change_password.php" style="color:#667eea;">⚙️</a>
                <a href="logout.php" style="color:#e74c3c; margin-left:10px;">Logout</a>
            </div>
        </div>
        
        <div class="header">
            <h2>Class Election System</h2>
            <small>Official Student Dashboard</small>
        </div>
        <hr>

        <?php if ($has_voted == 1 || $custom_status == 'approved'): ?>
            <div class="receipt-box">
                <div class="details">
                    <p><b>Student ID:</b> <?php echo htmlspecialchars($username); ?></p>
                    <p><b>Date:</b> <?php echo $timestamp; ?></p>
                    <p><b>Candidate:</b> <?php echo htmlspecialchars($candidate_name); ?></p>
                </div>

                <div class="success-box">✅ VOTE CAST SUCCESSFULLY</div>

                <div id="qrcode"></div>
                <script>
                    new QRCode(document.getElementById("qrcode"), {
                        text: <?php echo json_encode($receiptUrl); ?>,
                        width: 130, height: 130,
                        colorDark : "#000000", colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    });
                </script>

                <p style="font-size:12px; color:#666; margin-top:10px;">Scan to verify receipt online.</p>
                <button onclick="window.print()">🖨️ Print Receipt</button>
            </div>

        <?php elseif ($custom_status == 'pending'): ?>
            <div class="success-box" style="background:#fff3cd; color:#856404;">
                ⏳ Custom Name Pending
            </div>
            <p style="text-align:center; color:#666;">
                Your request "<strong><?php echo htmlspecialchars($custom_name); ?></strong>" is under review.
            </p>

        <?php else: ?>
            <?php if ($custom_status == 'rejected'): ?>
                <div style="background:#ffe0e0; color:#d8000c; padding:10px; border-radius:6px; margin-bottom:15px; text-align:center;">
                    ❌ Previous request rejected. Please vote below.
                </div>
            <?php endif; ?>

            <form action="vote.php" method="POST">
                <p style="color:#666; font-size:0.9rem;">Select one candidate below:</p>
                <?php
                $candidates = $conn->query("SELECT * FROM candidates");
                while ($row = $candidates->fetch_assoc()) {
                    echo "<label class='candidate-box'>";
                    echo "<input type='radio' name='candidate_id' value='" . $row['id'] . "' required>";
                    echo "<strong>" . htmlspecialchars($row['name']) . "</strong>";
                    echo "</label>";
                }
                ?>
                <button type="submit">Confirm Vote</button>
            </form>

            <div style="margin-top:20px; text-align:center;">
                <h4 style="margin-bottom:10px; color:#555;">OR</h4>
                <form action="custom_names.php" method="POST">
                    <input type="text" name="custom_name" placeholder="Request Custom Name" required style="width:70%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                    <button type="submit" style="width:auto; padding:8px 15px; margin-top:0;">Submit</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>