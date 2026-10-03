<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && $password === $admin['password_hash']) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $username;
        header("Location: admin.php");
        exit();
    } else {
        $error = "Invalid login credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - Class Election System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .login-box {
      background: #fff;
      padding: 35px;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.35);
      width: 360px;
      text-align: center;
      animation: fadeIn 0.8s ease-in-out;
    }
    h2 {
      margin-bottom: 10px;
      color: #333;
      font-size: 22px;
      font-weight: 600;
    }
    .subtitle {
      font-size: 13px;
      color: #666;
      margin-bottom: 20px;
    }
    .input-group {
      position: relative;
      margin-bottom: 15px;
      text-align: center;
    }
    input {
      width: 85%;
      padding: 12px 40px 12px 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      outline: none;
      transition: border 0.3s, box-shadow 0.3s;
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
      box-shadow: 0 6px 15px rgba(0,0,0,0.25);
    }
    .error {
      background: #ffe0e0;
      color: #d8000c;
      border: 1px solid #d8000c;
      padding: 10px;
      border-radius: 8px;
      margin-top: 15px;
      font-size: 13px;
    }
    .footer-note {
      margin-top: 15px;
      font-size: 12px;
      color: #666;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(-20px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>🔑 Admin Login</h2>
    <div class="subtitle">Secure Access to Election Results</div>
    <form method="POST">
      <div class="input-group">
        <input type="text" name="username" placeholder="Admin Username" required>
      </div>
      <div class="input-group">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <span class="toggle-eye" onclick="togglePassword()">👁️</span>
      </div>
      <button type="submit">Login</button>
    </form>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
    <div class="footer-note">This portal is restricted to authorized election administrators.</div>
  </div>

  <script>
    function togglePassword() {
      const field = document.getElementById('password');
      field.type = field.type === "password" ? "text" : "password";
    }
  </script>
</body>
</html>