<?php
// ENABLE ERRORS FOR DEBUGGING
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

// Check Admin Access
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// HANDLE APPROVAL / REJECTION ACTIONS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action']; // 'approved' or 'rejected'

    // 1. Update the status in the users table
    $stmt = $conn->prepare("UPDATE users SET custom_name_status = ? WHERE id = ?");
    $stmt->bind_param("si", $action, $user_id);
    if (!$stmt->execute()) {
        die("Error updating status: " . $conn->error);
    }
    $stmt->close(); // Close to prevent conflicts

    // 2. If APPROVED, we must add the candidate and mark user as voted
    if ($action === 'approved') {
        
        // A. Fetch the custom name requested by the user
        $stmt = $conn->prepare("SELECT custom_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $custom_name = trim($user['custom_name']);

            // B. Check if this candidate name already exists
            $stmt = $conn->prepare("SELECT id FROM candidates WHERE name = ?");
            $stmt->bind_param("s", $custom_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Candidate exists -> Increment their vote count
                $existing = $result->fetch_assoc();
                $cand_id = $existing['id'];
                $stmt->close(); // Close previous stmt

                $stmt = $conn->prepare("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?");
                $stmt->bind_param("i", $cand_id);
                $stmt->execute();
                $stmt->close();
            } else {
                // New Candidate -> Insert them with 1 vote
                $stmt->close(); // Close previous stmt
                
                $stmt = $conn->prepare("INSERT INTO candidates (name, vote_count) VALUES (?, 1)");
                $stmt->bind_param("s", $custom_name);
                $stmt->execute();
                $stmt->close();
            }

            // C. CRITICAL FIX: Mark the user as having voted!
            $stmt = $conn->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Redirect to prevent form resubmission crashes
    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Results & Moderation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root{ --bg1: #0f2027; --bg2: #203a43; --bg3: #2c5364; --glass-border: rgba(255,255,255,0.08); --muted: rgba(255,255,255,0.75); --shadow: 0 10px 30px rgba(2,6,23,0.6); }
        html,body{ height:100%; margin:0; font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, var(--bg1), var(--bg2), var(--bg3)); color:#fff; }
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
        .dashboard{width:100%;max-width:1100px;display:grid;grid-template-columns: 1fr 360px;gap:28px;}
        .card{background:rgba(255,255,255,0.03);border-radius:16px;padding:22px;box-shadow:var(--shadow);border:1px solid var(--glass-border);}
        .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
        .title{display:flex;gap:14px;align-items:center;}
        .logo{width:56px;height:56px;border-radius:12px;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;font-weight:700;color:#7C4DFF;font-size:20px;}
        h2{margin:0;font-size:20px;color:#fff;}
        .sub{font-size:13px;color:var(--muted);}
        .chart-wrap{display:flex;justify-content:center;padding:10px 0;}
        canvas{max-width:100%;height:auto;}
        .stats{display:flex;gap:12px;margin-top:14px;flex-wrap:wrap;}
        .stat{background:rgba(255,255,255,0.02);padding:10px 12px;border-radius:10px;border:1px solid var(--glass-border);min-width:120px;text-align:center;}
        .stat .num{font-size:18px;font-weight:700;}
        .stat .label{font-size:12px;color:var(--muted);}
        .sidebar{display:flex;flex-direction:column;gap:18px;}
        .candidates{padding:14px;border-radius:12px;background:rgba(255,255,255,0.02);border:1px solid var(--glass-border);}
        .candidate{display:flex;gap:12px;align-items:center;padding:10px;border-radius:10px;}
        .avatar{width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:16px;}
        .cand-info{flex:1;}
        .cand-name{font-weight:600;font-size:15px;margin:0;}
        .cand-meta{font-size:12px;color:var(--muted);display:flex;justify-content:space-between;margin-top:4px;}
        .progress{height:8px;background:rgba(255,255,255,0.04);border-radius:8px;overflow:hidden;margin-top:8px;}
        .progress > i{display:block;height:100%;border-radius:8px;}
        .moderation-box{padding:14px;border-radius:12px;background:rgba(255,255,255,0.02);border:1px solid var(--glass-border);}
        .pending-item{margin:8px 0;padding:8px;background:rgba(255,255,255,0.04);border-radius:8px;}
        .approve{background:#4BC0C0;color:#fff;border:none;border-radius:6px;padding:6px 10px;cursor:pointer;}
        .reject{background:#FF6384;color:#fff;border:none;border-radius:6px;padding:6px 10px;cursor:pointer;}
        @media(max-width:980px){.dashboard{grid-template-columns:1fr;}.sidebar{order:2;}}
    </style>
</head>
<body>
<div class="wrap">
  <div class="dashboard">
    <div class="card">
      <div class="header">
        <div class="title">
          <div class="logo">EV</div>
          <div>
            <h2>Live Election Results</h2>
            <div class="sub">Real-time vote counts and moderation</div>
          </div>
        </div>
        <a href="admin_logout.php" style="color:#ff6b6b;text-decoration:none;font-size:14px;">Logout</a>
      </div>

      <div class="chart-wrap"><canvas id="voteChart"></canvas></div>

      <div class="stats">
        <?php
        $labels = [];
        $data = [];
        $result = $conn->query("SELECT * FROM candidates");
        $totalVotes = 0;
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['name'];
            $data[] = (int)$row['vote_count'];
            $totalVotes += (int)$row['vote_count'];
        }
        ?>
        <div class="stat"><div class="num"><?php echo $totalVotes; ?></div><div class="label">Total Votes</div></div>
        <div class="stat"><div class="num"><?php echo count($labels); ?></div><div class="label">Candidates</div></div>
        <div class="stat"><div class="num" id="leadingName">—</div><div class="label">Leading</div></div>
      </div>
    </div>

    <aside class="sidebar">
      <div class="candidates card">
        <h3>Candidates</h3>
        <?php
        $result = $conn->query("SELECT * FROM candidates ORDER BY vote_count DESC");
        $palette = ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#8BC34A','#E91E63'];
        $i=0;
        while ($row = $result->fetch_assoc()) {
            $name = htmlspecialchars($row['name']);
            $votes = (int)$row['vote_count'];
            $pct = $totalVotes>0 ? round(($votes/$totalVotes)*100,1) : 0;
            $color = $palette[$i % count($palette)];
            $initials = substr($name, 0, 1);
            echo "<div class='candidate'>
                    <div class='avatar' style='background:$color;'>{$initials}</div>
                    <div class='cand-info'>
                      <p class='cand-name'>{$name}</p>
                      <div class='cand-meta'><span>{$votes} votes</span><span>{$pct}%</span></div>
                      <div class='progress'><i style='width:{$pct}%;background:$color;'></i></div>
                    </div>
                  </div>";
            $i++;
        }
        ?>
      </div>

      <div class="card moderation-box">
        <h3>📑 Pending Custom Names</h3>
        <?php
        $pending = $conn->query("SELECT id, username, custom_name FROM users WHERE custom_name_status = 'pending'");
        if ($pending->num_rows > 0) {
            while ($row = $pending->fetch_assoc()) {
                echo "<div class='pending-item'>
                        <strong>".htmlspecialchars($row['username'])."</strong> → ".htmlspecialchars($row['custom_name'])."
                        <form method='POST' style='display:inline; float:right;'>
                           <input type='hidden' name='user_id' value='".$row['id']."'>
                           <button class='approve' name='action' value='approved'>✔</button>
                           <button class='reject' name='action' value='rejected'>✖</button>
                        </form>
                        <div style='clear:both;'></div>
                      </div>";
            }
        } else {
            echo "<p style='color:#ccc;'>No pending names.</p>";
        }
        ?>
      </div>
    </aside>
  </div>
</div>

<script>
  const labels = <?php echo json_encode($labels); ?>;
  const votes = <?php echo json_encode($data); ?>;
  const palette = ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#8BC34A','#E91E63'];
  const bgColors = labels.map((_, idx) => palette[idx % palette.length]);

  function getLeading(labels, votes) {
    if (!votes.length) return '—';
    let maxIdx = 0;
    for (let i = 1; i < votes.length; i++) {
      if (votes[i] > votes[maxIdx]) maxIdx = i;
    }
    return labels[maxIdx] || '—';
  }
  document.getElementById('leadingName').textContent = getLeading(labels, votes);
  const totalVotes = votes.reduce((a,b) => a+b, 0);

  const ctx = document.getElementById('voteChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: votes,
        backgroundColor: bgColors,
        borderColor: 'rgba(255,255,255,0.06)',
        borderWidth: 2,
        hoverOffset: 12
      }]
    },
    options: {
      responsive: true,
      cutout: '58%',
      plugins: {
        legend: { position: 'bottom', labels: { color: '#fff', usePointStyle: true } },
        tooltip: {
          callbacks: {
            label: function(context) {
              const value = context.raw || 0;
              const pct = totalVotes ? ((value/totalVotes)*100).toFixed(1) : '0.0';
              return `${context.label}: ${value} votes • ${pct}%`;
            }
          }
        }
      }
    }
  });
</script>
</body>
</html>