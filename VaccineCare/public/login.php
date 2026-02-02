<?php
session_start();
require_once "../core/db.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, location FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);          

            if ($user && password_verify($password, $user['password'])) {
                $role = $user['role'];

                // ✅ Allow admin, parent, and anm
                if (!in_array($role, ['admin', 'anm', 'parent'])) {
                    $error = "Unauthorized account type.";
                } else {
                    // ✅ Set session
                    $_SESSION['user'] = [
                        'id'       => $user['id'],
                        'email'    => $user['email'],
                        'role'     => $role,
                        'name'     => $user['name'],
                        'location' => $user['location']
                    ];

                    // ✅ Redirect based on role
                    if ($role === 'admin') {
                        header("Location: dashboards/admin.php");
                    } elseif ($role === 'anm') {
                        header("Location: dashboards/anm.php");
                    } elseif ($role === 'parent') {
                        header("Location: dashboards/parent.php");
                    }
                    exit;
                }

            } else {
                $error = "Invalid email or password.";
            }

        } catch (PDOException $e) {
            $error = "Server error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VaccineCare - Login</title>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <!-- In your login page <head> -->
<style>
body.login-page {
    background-image: url("assets/images/child.png") !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
    background-size: cover !important;
}


</style>



</head>
<body class="login-page">

<!-- Navbar -->
<!-- <nav class="navbar navbar-expand-lg fixed-top py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.html">
      VaccineCare
    </a>
  </div>
</nav> -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">VaccineCare</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>
</nav>

<!-- Login Section -->
<section class="login-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="login-card p-4">
          <h3 class="text-center mb-4">Login</h3>
	  <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
          <form method="POST" autocomplete="off">


  <!-- Email -->
  <div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" name="email" class="form-control" id="email" placeholder="Enter your email" autocomplete="off" required>
  </div>

  <!-- Password -->
  <div class="mb-3">
    <label for="password" class="form-label">Password</label>
    <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password" autocomplete="off" required>
  </div>

  <!-- Button -->
  <div class="d-grid mb-3">
    <button type="submit" class="btn btn-primary">Login</button>

             
            </div>

            <!-- Extra Links -->
            <p class="text-center small">
              Don’t have an account? <a href="register.php">Register</a><br>
            </p>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Footer -->
<footer class="bg-dark text-white text-center py-3">
  <p>© 2025 VaccineCare. All rights reserved.</p>
</footer>

<!-- Bootstrap JS -->
<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
