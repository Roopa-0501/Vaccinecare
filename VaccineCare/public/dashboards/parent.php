<?php
// dashboards/parent.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "../../core/db.php";


// ✅ Session check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'parent') {
    header("Location: ../login.php");
    exit;
}

$parentId = $_SESSION['user']['id'];

// ✅ Handle Add Child form (backend insert)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_child'])) {
    $childName = trim($_POST['child_name']);
    $childDob = $_POST['child_dob'];
    $childGender = $_POST['child_gender'];

    if ($childName && $childDob && $childGender) {
        // Fetch parent location before inserting child
          $stmtLoc = $pdo->prepare("SELECT location FROM users WHERE id = ?");
          $stmtLoc->execute([$parentId]);
          $parentData = $stmtLoc->fetch(PDO::FETCH_ASSOC);
          $parentLocation = $parentData ? $parentData['location'] : "";

        // Insert child with parent's location as address
          $stmt = $pdo->prepare("INSERT INTO children (parent_id, name, dob, gender, address) VALUES (?, ?, ?, ?, ?)");
          $stmt->execute([$parentId, $childName, $childDob, $childGender, $parentLocation]);

            // 2 Get the ID of the newly inserted child
        $childId = $pdo->lastInsertId();

        //  Automatically assign all vaccines to this child
        $pdo->exec("INSERT INTO vaccine_children (child_id, vaccine_id, status, date_completed)
                    SELECT $childId, vaccine_id, 'Pending', NULL FROM vaccinations");

        header("Location: parent.php"); // refresh page
        exit;
    }
}

// ✅ Fetch parent details
try {
    $stmt = $pdo->prepare("SELECT name, email, phone, location FROM users WHERE id = ?");
    $stmt->execute([$parentId]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        die("Parent not found in database.");
    }

    // ✅ Fetch children from DB
    $stmt = $pdo->prepare("SELECT id, name, dob, gender FROM children WHERE parent_id = ?");
    $stmt->execute([$parentId]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Parent Dashboard - VaccineCare</title>
  
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
.popup-card {
  display: none;
  position: absolute;
  top: 70px; /* some space below navbar */
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  width: 220px;
  padding: 15px;
  text-align: center;
  z-index: 1050;
}

.popup-card .close {
  position: absolute;
  top: 8px;
  right: 10px;
  font-size: 20px;
  color: #333;
  cursor: pointer;
}


    .popup-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
    }
    .popup-name {
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

  /* ✅ Dashboard popup positioning in mobile */
  #dashboardPopup {
    position: fixed;
    top: 150px;
    right: 20px;
    width: 220px;
    z-index: 1100;
  }
}




  </style>
</head>
<body>

  <!-- Navbar -->
  <!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Parent Dashboard</a>

    <!-- Mobile Toggler Button -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Collapsible Content -->
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item">
          <a class="nav-link" id="dashboardBtn" href="javascript:void(0)">
            <img src="../assets/images/sign-removebg-preview.png" alt="Dashboard Icon" style="height:20px; margin-right:5px;">
            Dashboard
          </a>
          <!-- Dashboard Popup -->
          <div id="dashboardPopup" class="popup-card">
            <span class="close" id="closeDashboard">&times;</span>
            <div class="popup-content">
              <img src="../assets/images/parenticon-removebg-preview.png" alt="Parent Icon" class="popup-icon">
              <p class="popup-name"><?= htmlspecialchars($parent['name']) ?></p>
              <p class="text-muted small">Role: Parent</p>
              <a href="../logout.php" class="btn btn-sm btn-danger w-100">Logout</a>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link" id="addChildBtn" href="javascript:void(0)">
            <img src="../assets/images/add-removebg-preview.png" alt="Add Icon" style="height:20px; margin-right:5px;">
            Add Child
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>



  <!-- Main Section -->
  <main class="container">
    <div id="childrenContainer">
      <?php if ($children): ?>
        <?php foreach ($children as $c): ?>
          <div class="child-card">
            <img src="../assets/images/child_avatar.png" alt="Child Avatar" class="child-avatar">
            <h5><?= htmlspecialchars($c['name']) ?></h5>
            <p><strong>DOB:</strong> <?= htmlspecialchars($c['dob']) ?></p>
            <p><strong>Gender:</strong> <?= htmlspecialchars($c['gender']) ?></p>
            <a href="../children/view.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm mt-2">View Child</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <h2 id="noChildrenMsg">No children found</h2>
      <?php endif; ?>
    </div>
  </main>

  <!-- Add Child Modal -->
  <div id="childModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeModal">&times;</span>
      <h3>Add New Child</h3>
      <form method="POST">
        <input type="hidden" name="add_child" value="1">
        <label>Child Name</label>
        <input type="text" name="child_name" placeholder="Enter child name" required>
        
        <label>Date of Birth</label>
        <input type="date" name="child_dob" required>

        <label>Gender</label>
        <select name="child_gender" required>
          <option value="">Select gender</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>
        
        <button type="submit" class="primary-btn">Add Child</button>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    &copy; 2025 VaccineCare. All Rights Reserved.
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Custom JS -->
  <script>
    const modal = document.getElementById("childModal");
    const addBtn = document.getElementById("addChildBtn");
    const closeModal = document.getElementById("closeModal");

    addBtn.onclick = () => modal.style.display = "block";
    closeModal.onclick = () => modal.style.display = "none";
    window.onclick = (e) => { if(e.target == modal) modal.style.display = "none"; }

    // Dashboard popup
    const dashboardBtn = document.getElementById("dashboardBtn");
    const dashboardPopup = document.getElementById("dashboardPopup");
    const closeDashboard = document.getElementById("closeDashboard");

    dashboardBtn.onclick = () => {
      dashboardPopup.style.display =
        dashboardPopup.style.display === "block" ? "none" : "block";
    };
    closeDashboard.onclick = () => dashboardPopup.style.display = "none";
  </script>
</body>
</html>
