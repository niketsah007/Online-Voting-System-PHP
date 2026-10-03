<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$is_forced = isset($_GET['forced']); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($old_pass != $row['password']) {
        $message = "<div class='alert error'>❌ Old password is incorrect.</div>";
    } elseif ($new_pass != $confirm_pass) {
        $message = "<div class='alert error'>❌ New passwords do not match.</div>";
    } elseif ($new_pass == "pass123") {
        $message = "<div class='alert error'>❌ You cannot use the default password!</div>";
    } else {
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_pass, $user_id);
        
        if ($update->execute()) {
            $message = "<div class='alert success'>✅ Password Updated! You can now vote.</div>";
            header("refresh:2;url=dashboard.php");
        } else {
            $message = "<div class='alert error'>❌ Database Error.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password</title>
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
      text-align: center;
      animation: fadeIn 0.8s ease-in-out;
    }
    h2 {
      margin-bottom: 10px;
      color: #333;
      font-size: 22px;
    }
    .subtitle {
      font-size: 13px;
      color: #666;
      margin-bottom: 20px;
    }
    .alert {
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      font-size: 13px;
      text-align: left;
    }
    .alert.error {
      background: #ffe0e0;
      color: #d8000c;
      border: 1px solid #d8000c;
    }
    .alert.success {
      background: #e6fffa;
      color: #00b894;
      border: 1px solid #00b894;
    }
    .input-group {
      position: relative;
      margin-bottom: 15px;
      width: 100%;
    }
    input[type="password"], input[type="text"] {
      width: 100%;
      box-sizing: border-box;
      padding: 12px 40px 12px 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      outline: none;
      transition: border 0.3s;
      font-size: 14px;
    }
    input:focus {
      border-color: #667eea;
      box-shadow: 0 0 6px rgba(102,126,234,0.4);
    }
    .toggle-eye {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 18px;
      color: #888;
    }
    .strength {
      margin-top: 6px;
      height: 8px;
      border-radius: 5px;
      background: #ddd;
      overflow: hidden;
      width: 100%;
    }
    .strength-bar {
      height: 100%;
      width: 0%;
      transition: width 0.3s;
    }
    .strength-text, .match-text {
      margin-top: 6px;
      font-size: 13px;
      font-weight: 600;
      text-align: left;
    }
    button {
      margin-top: 15px;
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: #fff;
      font-weight: 600;
      cursor: pointer;
      font-size: 14px;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(-20px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>
  <form method="post">
    <h2>🔒 Change Password</h2>
    <div class="subtitle">Secure Student Account Management</div>

    <?php if (!empty($message)) echo $message; ?>

    <div class="input-group">
      <input type="password" name="old_pass" id="old_pass" placeholder="Current Password" required>
      <span class="toggle-eye" onclick="togglePassword('old_pass')">👁️</span>
    </div>

    <div class="input-group">
      <input type="password" name="new_pass" id="new_pass" placeholder="New Password" required>
      <span class="toggle-eye" onclick="togglePassword('new_pass')">👁️</span>
    </div>
    <div class="strength">
      <div class="strength-bar" id="strength-bar"></div>
    </div>
    <div class="strength-text" id="strength-text"></div>

    <div class="input-group">
      <input type="password" name="confirm_pass" id="confirm_pass" placeholder="Confirm New Password" required>
      <span class="toggle-eye" onclick="togglePassword('confirm_pass')">👁️</span>
    </div>
    <div class="match-text" id="match-text"></div>

    <button type="submit">Update Password</button>
  </form>

  <script>
    function togglePassword(id) {
      const field = document.getElementById(id);
      field.type = field.type === "password" ? "text" : "password";
    }

    const newPass = document.getElementById('new_pass');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');

    newPass.addEventListener('input', () => {
      const val = newPass.value;
      let strength = 0;
      if (val.length >= 6) strength++;
      if (/[A-Z]/.test(val)) strength++;
      if (/[0-9]/.test(val)) strength++;
      if (/[^A-Za-z0-9]/.test(val)) strength++;

      const colors = ["#ff4d4d", "#ffb84d", "#4dff4d"];
      const texts = ["Weak", "Medium", "Strong"];

      if (strength === 0) {
        strengthBar.style.width = "0%";
        strengthText.textContent = "";
      } else {
        strengthBar.style.width = (strength * 25) + "%";
        strengthBar.style.background = colors[strength-1];
        strengthText.textContent = texts[strength-1];
        strengthText.style.color = colors[strength-1];
      }
    });

    const confirmPass = document.getElementById('confirm_pass');
    const matchText = document.getElementById('match-text');

    function checkMatch() {
      if (!confirmPass.value) {
        matchText.textContent = "";
        return;
      }
      if (newPass.value === confirmPass.value) {
        matchText.textContent = "✅ Passwords match";
        matchText.style.color = "#4dff4d";
      } else {
        matchText.textContent = "❌ Passwords do not match";
        matchText.style.color = "#ff4d4d";
      }
    }

    newPass.addEventListener('input', checkMatch);
    confirmPass.addEventListener('input', checkMatch);
  </script>
</body>
</html>