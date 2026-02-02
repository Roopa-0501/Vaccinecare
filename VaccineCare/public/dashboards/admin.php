<?php
session_start();



require_once $_SERVER['DOCUMENT_ROOT'].'/VaccineCare/core/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/VaccineCare/core/db.php';



//Only admin access
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin'){
    header("Location: ../../login.php");
    exit;
}

// Handle form submission
$message = '';
$message_type = '';
if(isset($_POST['register_anm'])){
    $response = registerUser(
        $_POST['name'],
        $_POST['email'],
        $_POST['password'],
        $_POST['phone'],
        $_POST['location'],
        'anm' // role set automatically
    );

    $message = $response['message'];
    $message_type = $response['status']; // success or error
}

// === Dashboard Summary Counts ===
// Example for total ANMs

// Total ANMs
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_anm FROM users WHERE role = :role");
$stmt->execute(['role' => 'anm']);
$total_anm = $stmt->fetch(PDO::FETCH_ASSOC)['total_anm'];


// Total Parents
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_parents FROM users WHERE role = :role");
$stmt->execute(['role' => 'parent']);
$total_parents = $stmt->fetch(PDO::FETCH_ASSOC)['total_parents'];

// Total Children
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_children FROM children");
$stmt->execute();
$total_children = $stmt->fetch(PDO::FETCH_ASSOC)['total_children'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - VaccineCare</title>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <style>
    /* ---------- Body ---------- */
    body.admin-page {
      background: #f4f7fc;
      font-family: 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* ---------- Sidebar ---------- */
    .sidebar {
      height: 100vh;
      width: 250px;
      position: fixed;
      top: 0;
      left: 0;
      background: #0d6efd;
      color: #fff;
      padding-top: 30px;
      transition: all 0.3s;
    }
    .sidebar h4 {
      font-family: 'Montserrat', sans-serif;
      text-align: center;
      margin-bottom: 40px;
      font-size: 1.5rem;
    }
    .sidebar a {
      color: #fff;
      display: block;
      padding: 12px 20px;
      margin: 5px 10px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: 0.3s;
      cursor: pointer;
    }
    .sidebar a:hover, .sidebar a.active {
      background: rgba(255,255,255,0.2);
      color: #fff;
    }

    /* ---------- Main content ---------- */
    .main-wrapper {
      margin-left: 240px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .content {
      flex: 1;
      padding: 40px 30px;
    }
    h2, h4 {
      font-family: 'Montserrat', sans-serif;
    }

    /* ---------- Cards ---------- */
    .card {
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      margin-bottom: 30px;
    }

    /* ---------- Tables ---------- */
    .table th {
      background: #f1f1f1;
      font-weight: 600;
    }
    .table td, .table th {
      vertical-align: middle;
    }

    /* ---------- Message Box ---------- */
    .message-box {
      max-height: 350px;
      overflow-y: auto;
    }

    /* ---------- System Stats ---------- */
    .stats-card {
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.05);
      padding: 20px;
      text-align: center;
      background: #fff;
      transition: transform 0.2s;
    }
    .stats-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    }
    .stats-card h5 {
      font-size: 1.1rem;
      margin-bottom: 10px;
    }
    .stats-card p {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 0;
    }

    /* ---------- Footer ---------- */
    /* ---------- Footer ---------- */
footer {
  background: #212529;
  color: #fff;
  text-align: center;
  padding: 15px;
  font-size: 0.9rem;
  margin-left: 0px;       /* Leave space for sidebar */
  width: 100% /* Full width minus sidebar */
  position: relative;        /* Keep it at bottom naturally */
  bottom: 0;
}

/* ---------- Main wrapper ---------- */



    /* ---------- Form Inputs ---------- */
    .form-control {
      border-radius: 8px;
    }
   

    /* Button base */
.btn {
    border-radius: 8px;
    font-weight: 600;
    width: 40%;
    padding: 10px;
    background: linear-gradient(90deg, #004080, #0066cc);
    border: none;
    transition: 0.3s;
    color: white;
    cursor: pointer;
}

/* Hover effect */
.btn:hover {
    background: linear-gradient(90deg, #0066cc, #004080); /* reverse gradient */
    transform: translateY(-5px); /* slight lift */
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}


    /* ---------- Sections visibility ---------- */
    .tab-section {
      display: none;
      animation: fadeIn 0.4s ease;
    }
    .tab-section.active {
      display: block;
    }
    @keyframes fadeIn {
      from {opacity: 0;}
      to {opacity: 1;}
    }

    /* ---------- ANM Registration Card Bigger ---------- */
    #anm-management .card {
      padding: 40px;
    }
    #anm-management .form-control {
      height: 55px;
      font-size: 1rem;
    }
    #anm-management .btn {
      height: 55px;
      font-size: 1.1rem;
    }

    /* Responsive Fix */
    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
      }
      .main-wrapper {
        margin-left: 0;
      }
    }
  </style>
</head>
<body class="admin-page">

  <!-- Sidebar -->
  <div class="sidebar">
    <h4>Admin Panel</h4>
    <a class="tab-link active" data-target="anm-management">Manage ANMs</a>
    <a class="tab-link" data-target="stats">System Stats</a>
    <a href="../logout.php" class="logout-link">Logout</a>
  </div>

  <div class="main-wrapper">
    <!-- Main Content -->
    <div class="content">
      <h2 class="mb-4">Admin Dashboard</h2>

      <!-- ANM Management -->
      <!-- ANM Management -->
<section id="anm-management" class="tab-section active">
  <h4 class="mb-4">ANM Registrations</h4>
  <?php if($message): ?>
        <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger'; ?>">
            <?= $message; ?>
        </div>
    <?php endif; ?>
  
  <div class="card p-2 mx-auto" style="max-width: 900px;">
    
    <form method="POST">
        <div class="mb-3">
            <label>Full Name</label><br>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email</label><br>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label><br>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Phone</label><br>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Location</label><br>
            <input type="text" name="location" class="form-control" required>
        </div>
        <h1> </h1>
        <button type="submit" name="register_anm" class="btn btn-primary">Register ANM</button>
    </form>
  </div>
</section>

      <!-- System Stats -->
      <section id="stats" class="tab-section">
        <h4>System Overview</h4>
        <div class="row mt-3">
          <div class="col-md-4 mb-3">
            <div class="stats-card">
              <h5>👨‍👩‍👧 Parents</h5>
              <p><?= $total_parents ?></p>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="stats-card">
              <h5>👩‍⚕️ ANMs</h5>
              <p><?= $total_anm ?></p>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="stats-card">
              <h5>👶 Children</h5>
              <p><?= $total_children ?></p>
            </div>
          </div>

        </div>
      </section>
    </div>

    <!-- Footer -->
    <footer>
      <p>© 2025 VaccineCare Admin Panel. All rights reserved.</p>
    </footer>
  </div>

  <!-- Bootstrap & Custom JS -->
  <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    // Tab functionality
    const tabs = document.querySelectorAll('.tab-link');
    const sections = document.querySelectorAll('.tab-section');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        // Remove active class from tabs
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // Show the targeted section, hide others
        const target = tab.getAttribute('data-target');
        sections.forEach(section => {
          section.classList.remove('active');
          if(section.id === target){
            section.classList.add('active');
          }
        });
      });
    });
  </script>
</body>
</html>