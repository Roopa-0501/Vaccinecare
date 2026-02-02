<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../../core/db.php";

// ✅ Check login
if(!isset($_SESSION['user'])){
    header("Location: ../login.php");
    exit;
}

// ✅ Identify role
$user = $_SESSION['user'];
$role = $user['role'];
$userId = $user['id'];

// Block access if not ANM or parent
if(!in_array($role, ['anm', 'parent'])){
    die("Access denied.");
}

// ✅ Get child_id from URL
if(!isset($_GET['id']) || empty($_GET['id'])){
    die("Child not found");
}
$childId = intval($_GET['id']);

// ✅ Role-specific access
if($role === 'parent') {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name AS parent_name 
        FROM children c 
        JOIN users u ON c.parent_id = u.id 
        WHERE c.id = ? AND c.parent_id = ?
    ");
    $stmt->execute([$childId, $userId]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name AS parent_name 
        FROM children c 
        JOIN users u ON c.parent_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$childId]);
}

$child = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$child) die("Child not found or access denied.");

// ✅ Fetch vaccines
$query = "SELECT vc.id, v.vaccine_id, v.vaccine_name, v.recommended_age, v.recommended_age_months, vc.status, vc.date_completed
          FROM vaccine_children vc
          JOIN vaccinations v ON vc.vaccine_id = v.vaccine_id
          WHERE vc.child_id = :child_id";
$stmtV = $pdo->prepare($query);
$stmtV->execute([':child_id'=>$childId]);
$vaccines = $stmtV->fetchAll(PDO::FETCH_ASSOC);

// ✅ Calculate child age in months
$dob = new DateTime($child['dob']);
$today = new DateTime();
$ageInterval = $today->diff($dob);
$childAgeMonths = ($ageInterval->y*12) + $ageInterval->m;

// ✅ Auto-update Pending/Skipped
foreach($vaccines as &$vaccine){
    $recommended = intval($vaccine['recommended_age_months']);
    $status = $vaccine['status'];

    // Calculate child age in months + days for more precision
    $dob = new DateTime($child['dob']);
    $today = new DateTime();
    $ageDays = $dob->diff($today)->days;

    // ✅ Determine if vaccine should be marked Skipped
    if($status === 'Pending'){
        if($recommended === 0 && $ageDays > 0){ 
            // Vaccine recommended at birth but not given on birth date
            $vaccine['status'] = 'Skipped';
        } elseif($childAgeMonths > $recommended){
            // Vaccine overdue
            $vaccine['status'] = 'Skipped';
        } else {
            $vaccine['status'] = 'Pending';
        }

        // Update database
        $stmtUpdate = $pdo->prepare("
            UPDATE vaccine_children SET status=:status
            WHERE child_id=:child_id AND vaccine_id=:vaccine_id
        ");
        $stmtUpdate->execute([
            ':status'=>$vaccine['status'],
            ':child_id'=>$childId,
            ':vaccine_id'=>$vaccine['vaccine_id']
        ]);
    }
}


$isAnm = ($role==='anm');

// ✅ Fetch growth
$stmt = $pdo->prepare("SELECT * FROM growth WHERE child_id=? ORDER BY age_months ASC");
$stmt->execute([$childId]);
$growthRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clean invalid growth data
$growthRecords = array_values(array_filter($growthRecords,function($r){
    return isset($r['age_months'],$r['height'],$r['weight']) 
        && is_numeric($r['age_months'])
        && is_numeric($r['height'])
        && is_numeric($r['weight']);
}));

// ✅ WHO standards
$stmt = $pdo->prepare("SELECT age_months, mean_height, mean_weight FROM  who_growth_standards WHERE gender=(SELECT gender FROM children WHERE id=? LIMIT 1)");
$stmt->execute([$childId]);
$whoDataRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$whoData = [];
foreach($whoDataRaw as $row){
    $whoData[$row['age_months']]=[
        'mean_height'=>$row['mean_height'],
        'mean_weight'=>$row['mean_weight']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Child Details - VaccineCare</title>
<link href="../assets/css/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  /* Navbar */
html, body {
  height: 100%;
  margin: 0;
}
body {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
.navbar {
  background: linear-gradient(90deg, #003366, #0056b3);
  height: 80px;
  position: fixed;    /* keep navbar fixed */
  top: 0;
  left: 0;
  width: 100%;
  z-index: 1100;
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

/* Sidebar */
#sidebar {
  position: fixed;
  top: 80px;          /* starts below navbar */
  left: -220px;
  width: 220px;
  height: calc(100% - 80px);  /* fills screen below navbar */
  background: #f8f9fa;
  border-right: 1px solid #ddd;
  transition: left 0.3s ease;
  z-index: 1050;
  padding: 15px;
  overflow-y: auto;   /* scroll if sidebar content is large */
}
#sidebar.active {
  left: 0;
}
#sidebar .nav-link.active {
  background-color: #0d6efd;
  color: white !important;
}

/* Sidebar Toggle Button */
#sidebarToggle {
  font-size: 1.5rem;
  background: none;
  border: none;
  color: white;
}

/* Main Content */
/* Main Content */
/* Main Content */
main {
  margin-top: 100px; /* push down to avoid overlap with navbar */
  padding: 20px;
  transition: all 0.3s ease;
  margin-left: 0;
  width: 100%;
  flex: 1;
}
main.active {
  margin-left: 220px;
  width: calc(100% - 220px);
}
/* Profile Section */
.profile-section {
  display: flex;
  justify-content: center;
  margin-top: 40px;
}

.profile-card {
  text-align: center;
  width: 320px;
  transition: transform 0.3s;
}

/* Avatar Circle */
.profile-avatar {
  width: 120px;
  height: 120px;
  background: linear-gradient(135deg, #e3e8eeff, #c8cbceaf);
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0 auto 15px auto;
  color: white;
  font-size: 60px;
}

/* Profile Name */
.profile-name {
  font-family: 'Times New Roman', Times, serif;
  font-size: 1.6rem;
  font-weight: bold;
  margin-bottom: 10px;
}

/* Profile Details */
.profile-card p {
  font-size: 1.1rem;
  margin: 5px 0;
  color: #333;
}



/* Chatbox */
.chatbox {
  border: 1px solid #ddd;
  border-radius: 8px;
  height: 300px;
  display: flex;
  flex-direction: column;
  padding: 10px;
  background: #fff;
}
.chat-messages {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 10px;
}
.chat-input {
  display: flex;
  gap: 5px;
}

#sidebarToggle {
  font-size: 1.8rem;
  background: none;
  border: none;
  color: #ffffff;
  cursor: pointer;
  transition: color 0.3s;
}

.update-status,
.update-date {
  width: 100% !important;   /* take full cell width */
  min-width: 120px;         /* optional: avoid too small on very tiny screens */
  box-sizing: border-box;   /* include padding in width */
}




/* Footer */
footer {
  background: linear-gradient(90deg, #003366, #0056b3);
  color: white;
  text-align: center;
  padding: 15px 0;
  margin-top: 20px;  /* spacing from content */
  position: relative; /* allow scrolling */
  bottom: 0;
  width: 100%;
}
  
  @media (max-width: 768px) {
  main {
    margin-left: 0;
    width: 100%;
  }
  main.active {
    margin-left: 0;   /* no shifting */
    width: 100%;      /* keep full width */
  }
  .sidebar {
    width: 220px;
    left: -220px;
    position: fixed;
    top: 0;
    height: 100%;
  }
  .sidebar.active {
    left: 0;
  }
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
<div class="container-fluid d-flex align-items-center">
<button id="sidebarToggle" class="me-3"><i class="fas fa-bars"></i></button>
<a class="navbar-brand" href="#">VaccineCare</a>
<div class="ms-auto d-flex align-items-center">
<span class="text-white me-3">Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
<a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
</div>
</div>
</nav>

<div id="sidebar">
<ul class="nav flex-column">
<li class="nav-item"><a class="nav-link active" href="#" data-section="profile">Profile</a></li>
<li class="nav-item"><a class="nav-link" href="#" data-section="vaccination">Vaccination</a></li>
<li class="nav-item"><a class="nav-link" href="#" data-section="growth">Growth</a></li>
<li class="nav-item"><a class="nav-link" href="#" data-section="chatbot">Chatbot</a></li>
</ul>
</div>

<main>
<!-- Profile Section -->
<section id="profile" class="content-section profile-section">
<div class="profile-card text-center">
<div class="profile-avatar"><i class="fas fa-user"></i></div>
<h3><?= htmlspecialchars($child['name']) ?></h3>
<p><strong>Parent:</strong> <?= htmlspecialchars($child['parent_name']) ?></p>
<p><strong>Gender:</strong> <?= htmlspecialchars($child['gender']) ?></p>
<p><strong>DOB:</strong> <?= date('d-m-Y', strtotime($child['dob'])) ?></p>
<p><strong>Age:</strong> <?= $ageInterval->y ?> years</p>
</div>
</section>

<!-- Vaccination Section -->
<section id="vaccination" class="content-section d-none">
<h3>Vaccination Schedule</h3>
<div class="card shadow-sm mb-3">
    <div class="card-body">
      <h4>Vaccine Information</h4>
      <ul class="list-group text-start">
        <li class="list-group-item">
          <strong>Hepatitis B:</strong> Prevents lifelong liver infections and cancer. Usually given at birth, 6 weeks, and 14 weeks. 
        </li>
        <li class="list-group-item">
          <strong>BCG:</strong> Prevents severe tuberculosis, especially TB meningitis in children. Given at birth.
        </li>
        <li class="list-group-item">
          <strong>Pentavalent (DPT, Hepatitis B, Hib):</strong> A single injection protecting from 5 diseases. Given at 6, 10, and 14 weeks.
        </li>
        <li class="list-group-item">
          <strong>Hib:</strong> Protects against meningitis, pneumonia, and infections of the throat and joints.
        </li>
        <li class="list-group-item">
          <strong>Diphtheria:</strong> A throat infection that can block breathing. Part of DPT / Pentavalent.
        </li>
        <li class="list-group-item">
          <strong>Tetanus:</strong> Caused by contaminated wounds, leads to severe spasms. Booster doses given every few years.
        </li>
        <li class="list-group-item">
          <strong>Pertussis (Whooping Cough):</strong> Severe coughing fits in infants. Covered in DPT / Pentavalent.
        </li>
        <li class="list-group-item">
          <strong>PCV (Pneumococcal):</strong> Protects against pneumonia, ear infections, blood infections. Given in multiple doses.
        </li>
        <li class="list-group-item">
          <strong>IPV (Injectable Polio):</strong> Protects against paralysis caused by polio. Given with OPV at different ages.
        </li>
        <li class="list-group-item">
          <strong>OPV (Oral Polio Drops):</strong> Easy-to-administer drops, part of global polio eradication campaigns.
        </li>
        <li class="list-group-item">
          <strong>Rotavirus:</strong> Oral drops preventing severe diarrhea and dehydration. Given at 6 and 10 weeks.
        </li>
        <li class="list-group-item">
          <strong>MR (Measles-Rubella):</strong> Prevents measles and rubella. First dose at 9 months, booster at 15 months.
        </li>
        <li class="list-group-item">
          <strong>JE (Japanese Encephalitis):</strong> Prevents mosquito-borne brain infection. Given in endemic areas after 9 months.
        </li>
        <li class="list-group-item">
          <strong>Vitamin A Supplementation:</strong> Improves immunity, supports healthy vision, reduces childhood infections. Given every 6 months from 9 months to 5 years.
        </li>
        <li class="list-group-item">
          <strong>DPT Booster:</strong> Maintains protection against diphtheria, pertussis, tetanus. Given at 16–24 months and again at 5 years.
        </li>
        <li class="list-group-item">
          <strong>Td (Tetanus + Diphtheria):</strong> Given at 10 and 16 years to ensure lifelong protection.
        </li>
      </ul>

      <!-- Learn More -->
      <div class="mt-3 text-center">
        <a href="https://www.unicef.org/immunization" target="_blank" class="btn btn-primary">
          Learn More About Vaccines
        </a>
      </div>
    </div>
</div>
<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
<thead class="table-primary">
<tr>
<th>Vaccine</th>
<th>Recommended Age</th>
<th>Status</th>
<th>Date Completed</th>
<?php if($isAnm): ?><th>Update Status</th><th>Update Date</th><?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach($vaccines as $v): ?>
<tr>
<td><?= htmlspecialchars($v['vaccine_name']) ?></td>
<td><?= htmlspecialchars($v['recommended_age']) ?></td>
<td><span class="badge <?= $v['status']=='Completed'?'bg-success':($v['status']=='Pending'?'bg-warning text-dark':'bg-danger') ?>"><?= htmlspecialchars($v['status']) ?></span></td>
<td><?= $v['date_completed']?date('d-m-Y',strtotime($v['date_completed'])):'-' ?></td>
<?php if($isAnm): ?>
<td><select class="form-select form-select-sm update-status" data-id="<?= $v['id'] ?>">
<option <?= $v['status']=='Completed'?'selected':'' ?>>Completed</option>
<option <?= $v['status']=='Pending'?'selected':'' ?>>Pending</option>
<option <?= $v['status']=='Skipped'?'selected':'' ?>>Skipped</option>
</select></td>
<td><input type="date" class="form-control form-control-sm update-date" value="<?= $v['date_completed'] ?>"></td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>

<!-- Growth Section -->
<section id="growth" class="content-section d-none">
<h4>Child Growth Tracking</h4>
<table class="table table-bordered">
<thead>
<tr>
<th>Age (Months)</th><th>Height (cm)</th><th>Weight (kg)</th><th>Status</th>
<?php if($isAnm): ?><th colspan="3" class="text-center">ANM Actions</th><?php endif; ?>
</tr>
</thead>
<tbody id="growthTable">
<?php if(empty($growthRecords)): ?>
<tr><td colspan="<?= $isAnm?7:4 ?>" class="text-center text-muted">No growth record found.</td></tr>
<?php else: foreach($growthRecords as $g): 
$height=$g['height']; $weight=$g['weight']; $ageM=$g['age_months'];
$status = (!isset($whoData[$ageM]))?'No Data':($height<$whoData[$ageM]['mean_height']*0.9||$weight<$whoData[$ageM]['mean_weight']*0.9?'Underweight':($height>$whoData[$ageM]['mean_height']*1.1||$weight>$whoData[$ageM]['mean_weight']*1.1?'Overweight':'Normal'));
$badge = $status=='Normal'?'bg-success':($status=='Underweight'?'bg-warning text-dark':'bg-danger');
?>
<tr>
<td><?= $ageM ?></td>
<td><?= $height ?></td>
<td><?= $weight ?></td>
<td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
<?php if($isAnm): ?>
<td><input type="number" class="form-control form-control-sm" value="<?= $height ?>"></td>
<td><input type="number" class="form-control form-control-sm" value="<?= $weight ?>"></td>
<td><button class="btn btn-sm btn-primary save-growth">Save</button></td>
<?php endif; ?>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>

<?php if($isAnm): ?>
<div class="card p-3 mt-3">
<h6>Add Growth Record</h6>
<div class="row g-2">
<div class="col"><input type="number" id="newAge" class="form-control" placeholder="Age (months)"></div>
<div class="col"><input type="number" id="newHeight" class="form-control" placeholder="Height (cm)"></div>
<div class="col"><input type="number" id="newWeight" class="form-control" placeholder="Weight (kg)"></div>
<div class="col"><button id="addGrowthBtn" class="btn btn-success">Add</button></div>
</div>
</div>
<?php endif; ?>

<canvas id="growthChart" height="100" class="mt-4"></canvas>
</section>

<!-- Chatbot -->
<section id="chatbot" class="content-section d-none">
<h3>VaccineCare Assistant</h3>
<div class="card shadow-sm p-2">
<div class="chatbox d-flex flex-column">
<div class="chat-messages flex-grow-1 overflow-auto" id="chatMessages"><div><strong>Bot:</strong> Hello! How can I help you?</div></div>
<div class="chat-input d-flex gap-2 mt-2">
<input type="text" id="chatInput" class="form-control" placeholder="Type message...">
<button id="sendBtn" class="btn btn-primary">Send</button>
</div>
</div>
</div>
</section>
</main>

<footer>
<p class="mb-0">© 2025 VaccineCare. All rights reserved.</p>
</footer>

<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
toggleBtn.addEventListener('click',()=>{sidebar.classList.toggle('active');document.querySelector('main').classList.toggle('active');});

// Section switch
document.querySelectorAll('#sidebar .nav-link').forEach(link=>{
link.addEventListener('click',e=>{
e.preventDefault();
document.querySelectorAll('#sidebar .nav-link').forEach(l=>l.classList.remove('active'));
link.classList.add('active');
document.querySelectorAll('.content-section').forEach(s=>s.classList.add('d-none'));
document.getElementById(link.dataset.section).classList.remove('d-none');
});
});


// ✅ Vaccine update AJAX
document.querySelectorAll('.update-status, .update-date').forEach(el=>{
el.addEventListener('change',e=>{
const row=e.target.closest('tr');
const id=row.querySelector('.update-status').dataset.id;
const status=row.querySelector('.update-status').value;
const date_completed=row.querySelector('.update-date')?.value||null;
fetch('update_vaccine.php',{
method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,status,date_completed})
})
.then(res=>res.json())
.then(data=>{
if(data.success){
const badge=row.querySelector('td:nth-child(3) span');
badge.textContent=status;
badge.className='badge '+(status==='Completed'?'bg-success':status==='Pending'?'bg-warning text-dark':'bg-danger');
const dateCell=row.querySelector('td:nth-child(4)');
dateCell.textContent=date_completed?`${new Date(date_completed).toLocaleDateString('en-GB')}`:'-';
alert('✅ Status updated!');
}else alert('⚠️ '+(data.error||'Update failed'));
}).catch(err=>{console.error(err);alert('⚠️ Something went wrong');});
});
});



// Growth chart & JS
document.addEventListener("DOMContentLoaded", function () {
    const whoData = <?= json_encode($whoData ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const childId = <?= json_encode($childId) ?>;
    const isAnm = <?= json_encode($isAnm) ?>;

    // === Get references ===
    const growthTable = document.getElementById("growthTable");
    const growthChart = document.getElementById("growthChart").getContext("2d");

    // === Load initial data from table ===
    let ages = [], heights = [], weights = [], whoHeights = [], whoWeights = [];
    Array.from(growthTable.querySelectorAll("tr")).forEach(row => {
        if(row.querySelectorAll("td").length < 4) return; // skip "no record" row
        const age = parseInt(row.children[0].textContent);
        const height = parseFloat(row.children[1].textContent);
        const weight = parseFloat(row.children[2].textContent);
        ages.push(age);
        heights.push(height);
        weights.push(weight);
        whoHeights.push(whoData[age]?.mean_height || null);
        whoWeights.push(whoData[age]?.mean_weight || null);
    });

    // === Chart ===
    const chart = new Chart(growthChart, {
        type: "line",
        data: {
            labels: ages.map(a => a + "m"),
            datasets: [
                { label: "Height (cm)", data: heights, borderColor: "green", tension: 0.3 },
                { label: "WHO Median Height", data: whoHeights, borderColor: "lightgreen", borderDash: [5,5], tension: 0.3 },
                { label: "Weight (kg)", data: weights, borderColor: "blue", tension: 0.3 },
                { label: "WHO Median Weight", data: whoWeights, borderColor: "lightblue", borderDash: [5,5], tension: 0.3 }
            ]
        },
        options: { responsive: true, plugins: { legend:{position:"top"}, tooltip:{mode:"index", intersect:false} } }
    });

    // === Evaluate growth ===
    function evaluateGrowth(age, height, weight) {
        const ref = whoData[age];
        if(!ref) return "No Data";
        if(height < ref.mean_height*0.9 || weight < ref.mean_weight*0.9) return "Underweight";
        if(height > ref.mean_height*1.1 || weight > ref.mean_weight*1.1) return "Overweight";
        return "Normal";
    }

    // === Add New Growth Record ===
    const addBtn = document.getElementById("addGrowthBtn");
    if(addBtn){
        addBtn.addEventListener("click", async () => {
            const age = parseInt(document.getElementById("newAge").value);
            const height = parseFloat(document.getElementById("newHeight").value);
            const weight = parseFloat(document.getElementById("newWeight").value);
            if(isNaN(age) || isNaN(height) || isNaN(weight)) return alert("⚠️ Fill all fields correctly!");

            try{
                const res = await fetch("update_growth.php", {
                    method:"POST",
                    headers: {"Content-Type":"application/json"},
                    body: JSON.stringify({child_id: childId, age, height, weight})
                });
                const data = await res.json();
                if(data.success){
                    const tbody = growthTable;
                    const status = evaluateGrowth(age,height,weight);
                    const badgeClass = status=="Normal"?"bg-success":(status=="Underweight"?"bg-warning text-dark":"bg-danger");

                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${age}</td>
                        <td>${height}</td>
                        <td>${weight}</td>
                        <td><span class="badge ${badgeClass}">${status}</span></td>
                        ${isAnm?`<td class="anm-only"><input type="number" class="form-control form-control-sm" value="${height}"></td>
                                  <td class="anm-only"><input type="number" class="form-control form-control-sm" value="${weight}"></td>
                                  <td class="anm-only"><button class="btn btn-sm btn-primary save-btn">Save</button></td>`:""}
                    `;
                    tbody.appendChild(tr);

                    // Update chart arrays and refresh chart
                    // Push new data
                    ages.push(age);
                    heights.push(height);
                    weights.push(weight);
                    whoHeights.push(whoData[age]?.mean_height||null);
                    whoWeights.push(whoData[age]?.mean_weight||null);

                    // ✅ Sort all arrays based on age
                    const combined = ages.map((a,i)=>({age:a,height:heights[i],weight:weights[i],whoH:whoHeights[i],whoW:whoWeights[i]}));
                    combined.sort((a,b)=>a.age-b.age);

                    ages = combined.map(c=>c.age);
                    heights = combined.map(c=>c.height);
                    weights = combined.map(c=>c.weight);
                    whoHeights = combined.map(c=>c.whoH);
                    whoWeights = combined.map(c=>c.whoW);

                      // Update chart
                    chart.data.labels = ages.map(a => a + "m");
                    chart.data.datasets[0].data = heights;
                    chart.data.datasets[1].data = whoHeights;
                    chart.data.datasets[2].data = weights;
                    chart.data.datasets[3].data = whoWeights;
                    chart.update();


                    document.getElementById("newAge").value='';
                    document.getElementById("newHeight").value='';
                    document.getElementById("newWeight").value='';

                    alert("✅ Record added!");
                }else alert("⚠️ "+(data.error||"Failed to add record"));
            }catch(err){ console.error(err); alert("⚠️ Something went wrong."); }
        });
    }

    // Update Existing Row on Save button click
growthTable.addEventListener("click", async (e)=>{
    if(!e.target.classList.contains("save-btn")) return;
    const row = e.target.closest("tr");
    const age = parseInt(row.children[0].textContent);
    const heightInput = row.querySelectorAll("input")[0];
    const weightInput = row.querySelectorAll("input")[1];
    const height = parseFloat(heightInput.value);
    const weight = parseFloat(weightInput.value);
    if(isNaN(height)||isNaN(weight)) return alert("⚠️ Invalid data!");

    try{
        const res = await fetch("update_growth.php", {
            method:"POST",
            headers:{"Content-Type":"application/json"},
            body:JSON.stringify({child_id:childId, age, height, weight})
        });
        const data = await res.json();
        if(data.success){
            const status = evaluateGrowth(age,height,weight);
            const badge = row.querySelector("td:nth-child(4) span");
            badge.textContent = status;
            badge.className = "badge " + (status=="Normal"?"bg-success":(status=="Underweight"?"bg-warning text-dark":"bg-danger"));

            // ✅ Update the table cells with the new values so frontend matches backend
            row.children[1].textContent = height;
            row.children[2].textContent = weight;

            // ✅ Update chart
            const idx = ages.indexOf(age);
            if(idx != -1){ heights[idx] = height; weights[idx] = weight; chart.update(); }

            alert("✅ Growth record updated!");
        } else alert("⚠️ " + (data.error||"Update failed"));
    }catch(err){ console.error(err); alert("⚠️ Something went wrong."); }
});

});

//chatbot
document.getElementById('sendBtn').addEventListener('click', async () => {
    const inputField = document.getElementById('chatInput');
    const message = inputField.value.trim();
    if (!message) return;

    // Display user message
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML += `<div><strong>You:</strong> ${message}</div>`;
    chatMessages.scrollTop = chatMessages.scrollHeight;

    // Clear input
    inputField.value = '';

    try {
        // Call your API
        const response = await fetch('chatbot_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message })
        });

        const data = await response.json();

        if(data.success && data.reply){
            chatMessages.innerHTML += `<div><strong>Bot:</strong> ${data.reply}</div>`;
        } else {
            chatMessages.innerHTML += `<div><strong>Bot:</strong> Sorry, I could not process your request.</div>`;
        }

        chatMessages.scrollTop = chatMessages.scrollHeight;
    } catch(err){
        console.error(err);
        chatMessages.innerHTML += `<div><strong>Bot:</strong> ⚠️ Something went wrong!</div>`;
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});

document.getElementById('sendBtn').addEventListener('click', async () => {
    const inputField = document.getElementById('chatInput');
    const message = inputField.value.trim();
    if (!message) return;

    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML += `<div><strong>You:</strong> ${message}</div>`;
    chatMessages.scrollTop = chatMessages.scrollHeight;

    inputField.value = '';

    try {
        const res = await fetch('chatbot_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message })
        });

        const data = await res.json();

        if (data.success && data.reply) {
            chatMessages.innerHTML += `<div><strong>Bot:</strong> ${data.reply}</div>`;
        } else {
            const errMsg = data.error || "Sorry, I could not process your request.";
            chatMessages.innerHTML += `<div><strong>Bot:</strong> ${errMsg}</div>`;
        }

        chatMessages.scrollTop = chatMessages.scrollHeight;
    } catch(err){
        console.error(err);
        chatMessages.innerHTML += `<div><strong>Bot:</strong> ⚠️ Something went wrong!</div>`;
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});



</script>
</body>
</html>
