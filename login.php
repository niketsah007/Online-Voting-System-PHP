<?php
// ENABLE ERROR REPORTING FOR DEBUGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    // Prepare SQL to find the user
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verify Password (Plain text comparison as per your setup)
        if ($pass === $row['password']) {
            // Login Success
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Handle "Remember Me"
            if (isset($_POST['remember'])) {
                setcookie("remember_user", $row['username'], time() + (86400 * 30), "/");
            }

            // Redirect EVERYONE to the dashboard (Admins too, to avoid loops)
            header("Location: dashboard.php");
            exit();

        } else {
            $message = "<div class='alert error'>❌ Invalid Password!</div>";
        }
    } else {
        $message = "<div class='alert error'>❌ User not found!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Class Election Voting System - Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #667eea, #764ba2);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    form {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.2);
      width: 360px;
      box-sizing: border-box;
      text-align: center;
      animation: fadeIn 0.8s ease-in-out;
    }
    h2 { margin-bottom: 10px; color: #333; font-size: 22px; }
    .subtitle { font-size: 13px; color: #666; margin-bottom: 20px; }
    .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; text-align: left; }
    .alert.error { background: #ffe0e0; color: #d8000c; border: 1px solid #d8000c; }
    .input-group { position: relative; margin-bottom: 15px; width: 100%; }
    input[type="text"], input[type="password"] {
      width: 100%; box-sizing: border-box; padding: 12px 40px 12px 12px;
      border: 1px solid #ddd; border-radius: 8px; outline: none; transition: border 0.3s; font-size: 14px;
    }
    input:focus { border-color: #667eea; box-shadow: 0 0 6px rgba(102,126,234,0.4); }
    .toggle-eye { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px; color: #888; }
    .options { display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-top: 10px; }
    .options a { color: #667eea; text-decoration: none; }
    button {
      margin-top: 20px; width: 100%; padding: 12px; border: none; border-radius: 8px;
      background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; font-weight: 600; cursor: pointer; transition: transform 0.2s;
    }
    button:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.2); }
    .footer-note { margin-top: 15px; font-size: 12px; color: #666; }
    @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }
  </style>
</head>
<body>
  <form method="post" autocomplete="off">
    <h2>🔒 Class Election Voting System</h2>
    <div class="subtitle">Secure Student Login</div>

    <?php if (!empty($message)) echo $message; ?>

    <div class="input-group">
      <input type="text" name="username" placeholder="Enter Roll Number" required>
    </div>

    <div class="input-group">
      <input type="password" name="password" id="password" placeholder="Enter Password" required>
      <span class="toggle-eye" onclick="togglePassword()">👁️</span>
    </div>

    <div class="options">
      <label><input type="checkbox" name="remember"> Remember Me</label>
      <a href="forgot_password.php">Forgot Password?</a>
    </div>

    <button type="submit">Login to Vote</button>

    <div class="footer-note">
      This system is for student election voting only.<br>
      <a href="admin_login.php" style="color:#667eea; text-decoration:none; font-weight:600;">
        🔑 Admin Login
      </a>
    </div>
  </form>

  <script>
    function togglePassword() {
      const field = document.getElementById('password');
      field.type = field.type === "password" ? "text" : "password";
    }
  </script>
</body>
</html>