<?php
session_start();
require_once "../core/db.php";

$error = ''; 
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $location = sanitize($_POST['location'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $role     = sanitize($_POST['role'] ?? 'parent');

    // Validate required fields
    if (!$name || !$email || !$password || !$location || !$phone) {
        $error = "All fields are required.";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email is already registered.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert into DB
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, location, phone, role, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $email, $hashedPassword, $location, $phone, $role]);

                $userId = $pdo->lastInsertId();

                // Set session
                $_SESSION['user'] = [
                    'id'       => $userId,
                    'name'     => $name,
                    'email'    => $email,
                    'role'     => $role,
                    'location' => $location,
                    'phone'    => $phone
                ];

                // Redirect to dashboard
                header("Location: dashboards/{$role}.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Server error: please try again later.";
            // Optionally log $e->getMessage() in a file for debugging
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
<section class="login-section register-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="login-card p-4">
          <h3 class="text-center mb-4">Parent Registration</h3>
          <!-- Show error / success -->
          <?php if (!empty($error)): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <?php if (!empty($success)): ?>
              <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
          <?php endif; ?>
          <form method="POST" autocomplete="off">
              

              <div class="mb-3">
                <label for="name" class="form-label" >Full Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" autocomplete="off" required>
              </div>

              <div class="mb-3">
                <label for="email" class="form-label" >Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" autocomplete="off" required>
              </div>

              <div class="mb-3">
                <label for="password" class="form-label" >Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" autocomplete="new-password" required>
              </div>

              <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" id="location" name="location" placeholder="Enter your location" required>
              </div>

  <div class="mb-3">
    <label for="phone" class="form-label">Phone Number</label>
    <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter your phone number" required>
  </div>

  <!-- Hidden role -->
  <input type="hidden" name="role" value="parent">

  <div class="d-grid mb-3">
    <button type="submit" class="btn btn-primary">Register</button>
  </div>

  <p class="text-center small">
    Already have an account? <a href="login.php">Login</a><br>
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
