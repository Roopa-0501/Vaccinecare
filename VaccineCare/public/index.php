<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VaccineCare</title>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<!-- Navbar -->


<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">VaccineCare</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav align-items-center">
          <li class="nav-item">
  		<a class="nav-link" href="#" id="contact" data-bs-toggle="modal" data-bs-target="#contactModal">
    		<img src="assets/images/caller-removebg-preview.png" alt="abc" style="height:20px; margin-right:5px;">
    		Contact
  		</a>
	 </li>
          <li class="nav-item">
            <a class="nav-link" href="register.php" id="signup">
              <img src="assets/images/register.png" alt="abc" style="height:20px; margin-right:5px;">
              Register
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="login.php" id="login">
              <img src="assets/images/image.png" alt="abc" style="height:20px; margin-right:5px;">
              Login
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>


<!-- Hero Section -->
<header class="text-center" style="margin-top: 80px;">
  <div class="container">
    <h1 class="display-4">Track Your Child’s Vaccination & Growth</h1>
    <p class="lead">Easy, secure, and organized tracking for parents and health workers.</p>
  </div>
</header>


<!-- Features Section -->
<section id="features" class="py-5">
  <div class="container">
    <div class="row row-cols-1 row-cols-md-5 g-4 text-center">

      <!-- Feature 1 -->
      <div class="col">
        <div class="card h-100">
          <img src="assets/images/register.webp" class="card-img-top" alt="Register">
          <div class="card-body">
            <h5 class="card-title">Register & Create Profile</h5>
          </div>
        </div>
      </div>

      <!-- Feature 2 -->
      <div class="col">
        <div class="card h-100">
          <img src="assets/images/Vaccine.png" class="card-img-top" alt="Vaccination Schedule">
          <div class="card-body">
            <h5 class="card-title">View Vaccination Schedule & History</h5>
          </div>
        </div>
      </div>

      <!-- Feature 3 -->
      <div class="col">
        <div class="card h-100">
          <img src="assets/images/profile.webp" class="card-img-top" alt="Child Profile">
          <div class="card-body">
            <h5 class="card-title">View Child Profile</h5>
          </div>
        </div>
      </div>

      <!-- Feature 4 -->
      <div class="col">
        <div class="card h-100">
          <img src="assets/images/notify.webp" class="card-img-top" alt="Reminders">
          <div class="card-body">
            <h5 class="card-title">Get Notifications & Reminders</h5>
          </div>
        </div>
      </div>

      <!-- Feature 5 -->
      <div class="col">
        <div class="card h-100">
          <img src="assets/images/status.jpg" class="card-img-top" alt="Update Status">
          <div class="card-body">
            <h5 class="card-title">Update Child Vaccine&Growth Status</h5>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- About Section -->
<section id="about" class="about-section py-5">
  <div class="container">
    <div class="about-card">
      <h3>About VaccineCare</h3>
      <p>
        At VaccineCare, we aim to close the communication gap between parents and healthcare workers to ensure every child gets timely vaccinations. Today, parents often lack access to vaccination records and reminders, while ANMs and ASHA workers spend hours manually informing families, leading to delays and missed vaccines.

Our platform provides secure, role-based access for both parents and healthcare workers. Parents can register children, view vaccination schedules, get reminders, track growth with charts, and receive suggestions. ANMs can update vaccination records, monitor growth, and manage children in their assigned areas more efficiently.
      </p>
    </div>
  </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="faq-section py-5">
  <div class="container">
    <h3>Frequently Asked Questions (FAQ)</h3>

    <!-- FAQ 1 -->
    <div class="faq-item">
      <input type="checkbox" id="faq1">
      <label for="faq1">How do I register my child?</label>
      <div class="faq-content">
        Click on the Register link in the navbar, fill in your details, and create a profile then add your children.
      </div>
    </div>

    <!-- FAQ 2 -->
    <div class="faq-item">
      <input type="checkbox" id="faq2">
      <label for="faq2">Who can update my child's growth and health records?</label>
      <div class="faq-content">
        Only authorized ANMs can update vaccination, growth, and health records. Parents can view them in the portal.
      </div>
    </div>

    <!-- FAQ 3 -->
    <div class="faq-item">
      <input type="checkbox" id="faq3">
      <label for="faq3">Will I get reminders for upcoming vaccinations?</label>
      <div class="faq-content">
        Yes, VaccineCare sends notifications and reminders based on your child's vaccination schedule.
      </div>
    </div>

    <!-- FAQ 4 -->
    <div class="faq-item">
      <input type="checkbox" id="faq4">
      <label for="faq4">What should I do if I miss a vaccination date?</label>
      <div class="faq-content">
        You can contact your assigned ANM or visit the nearest health center. The system will automatically reschedule the missed dose.
      </div>
    </div>

    <!-- FAQ 5 -->
    <div class="faq-item">
      <input type="checkbox" id="faq5">
      <label for="faq5">Can I update my contact details after registration?</label>
      <div class="faq-content">
        Yes, you can contact the adim via email and give your details.
      </div>
    </div>

    <!-- FAQ 6 -->
    <div class="faq-item">
      <input type="checkbox" id="faq6">
      <label for="faq6">Can I register multiple children under one account?</label>
      <div class="faq-content">
        Yes, you can add multiple children profiles under a single parent account and manage their vaccination schedules together.
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-3">
  <p>© 2025 VaccineCare. All rights reserved.</p>
</footer>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contactModalLabel">Contact Us</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>For any inquiries, please contact us:</p>
        <p><strong>Email:</strong> <a href="mailto:vaccinecare@gmail.com">vaccinecare10@gmail.com</a></p>
      </div>
    </div>
  </div>
</div>


<!-- Bootstrap JS -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/home.js"></script>

</body>
</html>