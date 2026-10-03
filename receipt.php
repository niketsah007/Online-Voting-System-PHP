<?php
// DISABLE ERROR REPORTING FOR FINAL VERSION
error_reporting(0);

session_start();
include 'db.php';

$user_id = 0;
$candidate_id = 0;

// 1. GET PARAMETERS
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $candidate_id = isset($_GET['candidate_id']) ? (int)$_GET['candidate_id'] : 0;
}
// 2. FALLBACK (If security redirect stripped params)
if ($user_id == 0) {
    $raw_url = $_SERVER['REQUEST_URI']; 
    if (preg_match('/user_id=(\d+)/', $raw_url, $matches)) $user_id = (int)$matches[1];
    if (preg_match('/candidate_id=(\d+)/', $raw_url, $matches)) $candidate_id = (int)$matches[1];
}

// 3. FETCH DATA
$studentID = 'Unknown';
$candidateName = 'Unknown';

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $studentID = $row['username'];
}

if ($candidate_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM candidates WHERE id = ?");
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $candidateName = $row['name'];
}

// 4. GENERATE LINKS
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$qrLink = $protocol . "://" . $host . "/receipt.php?user_id=" . $user_id . "&candidate_id=" . $candidate_id;
$timestamp = date("d-m-Y h:i A");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Voting Receipt</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
  <style>
    body{margin:0;font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;justify-content:center;align-items:center; flex-direction: column;}
    .slip-container{background:#fff;padding:30px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,0.25);width:90%;max-width:420px;text-align:center}
    .header h2{margin:0;font-size:22px;color:#333}
    .header small{color:#666;font-size:13px}
    hr{border:1px dashed #ddd;margin:15px 0}
    .details{text-align:left;margin:15px 0}
    .details p{margin:6px 0;color:#444;font-size:14px;}
    .success-box{background:#e6fffa;color:#00b894;padding:12px;border-radius:8px;margin:20px 0;font-weight:bold}
    #qrcode{margin:20px auto; display:flex; justify-content:center;}
    .footer{margin-top:15px;font-size:12px;color:#666}
    button{margin-top:15px;width:100%;padding:12px;border:none;border-radius:8px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;font-weight:600;cursor:pointer}
    a { display: inline-block; margin-top: 15px; color: #667eea; text-decoration: none; font-size: 14px; font-weight: 600; }
    a:hover { text-decoration: underline; }
    @media print{button,a{display:none}body{background:white;margin:0}.slip-container{box-shadow:none;border:1px solid #ddd}}
  </style>
</head>
<body>
  <div class="slip-container">
    <div class="header">
      <h2>Class Election System</h2>
      <small>Official Voting Receipt</small>
    </div>
    <hr>
    <div class="details">
      <p><b>Student ID:</b> <?php echo htmlspecialchars($studentID); ?></p>
      <p><b>Date:</b> <?php echo htmlspecialchars($timestamp); ?></p>
      <p><b>Candidate:</b> <?php echo htmlspecialchars($candidateName); ?></p>
    </div>
    
    <div class="success-box">✅ VOTE CAST SUCCESSFULLY</div>

    <div id="qrcode"></div>
    <script>
      new QRCode(document.getElementById("qrcode"), {
        text: <?php echo json_encode($qrLink); ?>,
        width: 140, height: 140,
        colorDark : "#000000", colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.L
      });
    </script>

    <p style="font-size:13px; color:#666;">Scan to verify receipt online.</p>
    
    <div class="footer">Authorized by Election Officer</div>
    
    <button type="button" onclick="window.print()">🖨️ Print Page</button>
    <br>
    <a href="logout.php">← Return to Login</a>
  </div>
</body>
</html>