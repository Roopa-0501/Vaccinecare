<?php
// dashboards/parent.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "../../core/db.php";


// ✅ Session check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'anm') {
    header("Location: ../login.php");
    exit;
}

$anmId = $_SESSION['user']['id'];


// ✅ Fetch parent details
try {
    $stmt = $pdo->prepare("SELECT name, email, phone, location FROM users WHERE id = ?");
    $stmt->execute([$anmId]);
    $anm = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$anm) {
        die("ANM not found in database.");
    }

    // ✅ Fetch children from DB
    $stmt = $pdo->prepare("SELECT c.id, c.name, c.dob, c.gender, u.name AS parent_name, u.location FROM children c JOIN users u ON c.parent_id = u.id WHERE u.location = ?");
    $stmt->execute([$anm['location']]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);


     $stmt = $pdo->prepare("
    SELECT r.id AS reminder_id, c.id AS child_id, c.name AS child_name, c.dob,
           u.name AS parent_name, vac.vaccine_name, r.scheduled_date, r.status
    FROM child_vaccine_reminders r
    JOIN children c ON r.child_id = c.id
    JOIN users u ON c.parent_id = u.id
    JOIN vaccinations vac ON r.vaccine_id = vac.vaccine_id
    WHERE u.location = ?   -- location is from parent
      AND r.status = 'Pending'
      AND CURDATE() BETWEEN r.start_date AND r.end_date
    ORDER BY r.scheduled_date ASC
");
$stmt->execute([$anm['location']]);
$weeklyReminders = $stmt->fetchAll(PDO::FETCH_ASSOC);



} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ANM Dashboard - VaccineCare</title>
  
  <!-- Bootstrap 5 CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-top: 70px; /* for fixed navbar */
    }
    .navbar {
      background: linear-gradient(90deg, #003366, #0056b3);
      height: 80px;
    }
    .navbar-brand {
      font-family: 'Times New Roman', Times, serif;
      font-size: 2.5rem;
      font-weight: bold;
      color: white !important;
    }
    .navbar-nav .nav-link {
      color: white !important;
      font-size: 1.3rem;
      font-weight: 500;
      font-family: 'Times New Roman', Times, serif;
    }
    .navbar-nav .nav-link:hover {
      text-decoration: underline;
      color: white !important;
    }
    .nav-link.btn-white:hover {
      text-decoration: underline;
      background: none;
      color: white !important;
    }
    .navbar-toggler {
  border: none;
}
.navbar-toggler:focus {
  box-shadow: none;
}

.navbar {
  background: linear-gradient(90deg, #003366, #0056b3);
  height: 80px;
}



    .container {
      padding: 40px;
      text-align: center;
    }

    /* Primary Button */
    .primary-btn {
      background: #004080;
      color: white;
      border: none;
      padding: 10px 20px;
      margin-top: 15px;
      border-radius: 5px;
      cursor: pointer;
    }
    .primary-btn:hover {
      background: #0066cc;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 10;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
    }
    .modal-content {
      background: #fff;
      margin: 10% auto;
      padding: 25px;
      border-radius: 10px;
      width: 350px;
      text-align: left;
      position: relative;
    }
    .modal-content h3 {
      margin-bottom: 15px;
    }
    .modal-content label {
      display: block;
      margin: 10px 0 5px;
    }
    .modal-content input, .modal-content select {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
    }
    .close {
      position: absolute;
      top: 10px; right: 15px;
      font-size: 20px;
      cursor: pointer;
    }

    /* Dashboard Popup */
    /* Dashboard Popup */
/* Dashboard Popup */
/* Dashboard Popup */
.popup-card {
  display: none;
  position: absolute;
  top: 65px; /* just below navbar */
  right: 20px;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  width: 230px;
  padding: 15px;
  text-align: center;
  z-index: 1050;
}

.popup-card .close {
  position: absolute;
  top: 8px;
  right: 10px;
  font-size: 18px;
  color: #333;
  cursor: pointer;
}

.popup-card .popup-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
}

.popup-card .popup-name {
  font-weight: bold;
  margin: 10px 0;
}

    .popup-card button {
      background: #004080;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 5px;
      cursor: pointer;
    }
    .popup-card button:hover {
      background: #0066cc;
    }

    #childrenContainer {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  justify-items: center;
}

.child-card {
  width: 100%;
  max-width: 350px;
  height: auto;
  border: 1px solid #ddd;
  border-radius: 10px;
  background: #fff;
  padding: 20px;
  text-align: center;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.child-avatar {
  width: 70px;
  height: 70px;
  margin-bottom: 15px;
}


.child-info {
  flex: 1;
}


    footer {
      background: linear-gradient(90deg, #003366, #0056b3);
      color: white;
      text-align: center;
      padding: 15px 0;
      position: fixed;
      width: 100%;
      bottom: 0;
    }

    #searchInput {
  border-radius: 8px;
  padding: 10px 15px;
  font-size: 1rem;
}


    @media (max-width: 576px) {
  .navbar-brand {
    font-size: 1.8rem;
  }
  .navbar-nav .nav-link {
    font-size: 1.1rem;
  }
  .modal-content {
    width: 90%;
    margin-top: 30%;
  }
}

@media (max-width: 768px) {
  #dashboardPopup {
    position: fixed;
    top: 120px;
    right: 10px;
    width: 200px;
    z-index: 1100;
  }
}

/* =======================
   Mobile Navbar Fix (Sidebar Style)
======================= */
@media (max-width: 992px) {
  .navbar-collapse {
    position: fixed;
    top: 80px; /* below navbar */
    right: 0;
    width: 200px;
    background-color: #fff;
    padding: 1rem;
    border-left: 1px solid #ddd;
    box-shadow: -2px 0 8px rgba(0,0,0,0.1);
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
    z-index: 1050;
  }

  .navbar-collapse.show {
    transform: translateX(0);
  }

  /* ✅ Nav Links visible */
  .navbar-nav .nav-link {
    color: #000 !important;
    font-size: 1.1rem;
    margin: 10px 0;
    transition: all 0.3s ease;
  }

  /* ✅ Hover effect visible */
  .navbar-nav .nav-link:hover {
    color: #007bff !important;
  }

  /* ✅ Make toggler icon black (visible on white) */
  .navbar-toggler-icon {
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba%28255,255,255,1%29' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
  }
}




  </style>
</head>
<body>

  <!-- Navbar -->
  <!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">ANM Dashboard</a>

    <!-- Mobile Toggler Button -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Collapsible Content -->
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item">
          <a class="nav-link active" href="javascript:void(0)" id="childDetailsNav"> Child Details</a>
        </li>
        <li class="nav-item ms-3">
          <a class="nav-link" href="javascript:void(0)" id="scheduleNav"> Today's Schedule</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="dashboardBtn" href="javascript:void(0)">
            <img src="../assets/images/sign-removebg-preview.png" alt="Dashboard Icon" style="height:20px; margin-right:5px;">
            Dashboard
          </a>
          <!-- Dashboard Popup -->
          <div id="dashboardPopup" class="popup-card">
            <span class="close" id="closeDashboard">&times;</span>
            <div class="popup-content">
              <img src="../assets/images/anm-removebg-preview.png" alt="ANM Icon" class="popup-icon">
              <p class="popup-name"><?php echo htmlspecialchars($anm['name']); ?></p>
              <p class="text-muted small">Role: ANM</p>
              <a href="../logout.php" class="btn btn-sm btn-danger w-100">Logout</a>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>



  <!-- Main Section -->
  <main class="container" id="mainContent">
    <!-- Default: Child Details -->
    <div id="childDetailsSection">
        <!-- Search Bar -->
        <div class="row justify-content-center mb-3">
            <div class="col-md-6 col-sm-8">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Child Name">
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4 align-items-center justify-content-center">
            <div class="col-md-4 col-sm-6 mb-2">
                <select id="genderFilter" class="form-select">
                    <option value="">Filter by Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-4 col-sm-6 mb-2">
                <select id="ageFilter" class="form-select">
                    <option value="">Filter by Age</option>
                    <option value="0-1">0 - 1 year</option>
                    <option value="1-5">1 - 5 years</option>
                    <option value="5-10">5 - 10 years</option>
                    <option value="10+">10+ years</option>
                </select>
            </div>
        </div>

        <div id="childrenContainer">
            <?php if ($children): ?>
                <?php foreach ($children as $c): ?>
                    <div class="child-card"
                        data-gender="<?= htmlspecialchars($c['gender']) ?>"
                        data-dob="<?= htmlspecialchars($c['dob']) ?>">
                        <img src="../assets/images/child_avatar.png" alt="Child Avatar" class="child-avatar">
                        <h5><?= htmlspecialchars($c['name']) ?></h5>
                        <p><strong>DOB:</strong> <?= htmlspecialchars($c['dob']) ?></p>
                        <p><strong>Gender:</strong> <?= htmlspecialchars($c['gender']) ?></p>
                        <p><strong>Parent:</strong> <?= htmlspecialchars($c['parent_name']) ?></p>
                        <a href="../children/view.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm mt-2">View Child</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <h2 id="noChildrenMsg">No children found</h2>
            <?php endif; ?>
        </div>
    </div>

    <!-- Vaccine Schedule Section (hidden by default) -->
    <div id="scheduleSection" style="display:none;">
        <h3>Children Scheduled for Vaccines</h3>
<?php if ($weeklyReminders): ?>
    <ul class="list-group">
        <?php foreach ($weeklyReminders as $r): ?>
            <li class="list-group-item">
                <strong><?= htmlspecialchars($r['child_name']) ?></strong> 
                (Parent: <?= htmlspecialchars($r['parent_name']) ?>) - 
                <?= htmlspecialchars($r['vaccine_name']) ?> 
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No children have vaccines scheduled this week.</p>
<?php endif; ?>

    </div>
</main>



  <!-- Footer -->
  <footer>
    &copy; 2025 VaccineCare. All Rights Reserved.
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Custom JS -->
  <script>

const dashboardBtn = document.getElementById("dashboardBtn");
const dashboardPopup = document.getElementById("dashboardPopup");
const closeDashboard = document.getElementById("closeDashboard");

dashboardBtn.onclick = () => {
  dashboardPopup.style.display =
    dashboardPopup.style.display === "block" ? "none" : "block";
};
closeDashboard.onclick = () => dashboardPopup.style.display = "none";

    // ===============================
// Dynamic Filter Logic
// ===============================

const genderFilter = document.getElementById("genderFilter");
const ageFilter = document.getElementById("ageFilter");
const childCards = document.querySelectorAll(".child-card");

function calculateAgeMonths(dob) {
  const birthDate = new Date(dob);
  const now = new Date();
  const months =
    (now.getFullYear() - birthDate.getFullYear()) * 12 +
    (now.getMonth() - birthDate.getMonth());
  return months;
}

function applyFilters() {
  const genderValue = genderFilter.value;
  const ageValue = ageFilter.value;

  childCards.forEach((card) => {
    const gender = card.getAttribute("data-gender");
    const dob = card.getAttribute("data-dob");
    const ageMonths = calculateAgeMonths(dob);

    let show = true;

    // Gender filter
    if (genderValue && gender !== genderValue) show = false;

    // Age filter
    if (ageValue) {
      if (ageValue === "0-1" && ageMonths > 12) show = false;
      if (ageValue === "1-5" && (ageMonths <= 12 || ageMonths > 60)) show = false;
      if (ageValue === "5-10" && (ageMonths <= 60 || ageMonths > 120)) show = false;
      if (ageValue === "10+" && ageMonths <= 120) show = false;
    }

    card.style.display = show ? "block" : "none";
  });
}

genderFilter.addEventListener("change", applyFilters);
ageFilter.addEventListener("change", applyFilters);

// ===============================
// Search Functionality
// ===============================
const searchInput = document.getElementById("searchInput");

searchInput.addEventListener("input", () => {
  const query = searchInput.value.toLowerCase().trim();
  let anyVisible = false;

  childCards.forEach((card) => {
    const name = card.querySelector("h5").textContent.toLowerCase();
    const match = name.includes(query);
    if (match) {
      card.style.display = "block";
      anyVisible = true;
    } else {
      card.style.display = "none";
    }
  });

  // Optional: Show "No children found" if nothing matches
  const noChildrenMsg = document.getElementById("noChildrenMsg");
  if (noChildrenMsg) {
    noChildrenMsg.style.display = anyVisible ? "none" : "block";
  }
});

const childDetailsNav = document.getElementById("childDetailsNav");
const scheduleNav = document.getElementById("scheduleNav");

const childDetailsSection = document.getElementById("childDetailsSection");
const scheduleSection = document.getElementById("scheduleSection");

childDetailsNav.onclick = () => {
    childDetailsSection.style.display = "block";
    scheduleSection.style.display = "none";
    childDetailsNav.classList.add("active");
    scheduleNav.classList.remove("active");
};

scheduleNav.onclick = () => {
    childDetailsSection.style.display = "none";
    scheduleSection.style.display = "block";
    scheduleNav.classList.add("active");
    childDetailsNav.classList.remove("active");
};


  </script>
</body>
</html>
