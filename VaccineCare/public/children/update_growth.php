<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../../core/db.php";

header('Content-Type: application/json');

// ✅ Only allow logged-in ANM users
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'anm') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// ✅ Read and validate JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (
    !$data ||
    !isset($data['child_id'], $data['age'], $data['height'], $data['weight'])
) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$child_id = intval($data['child_id']);
$age = intval($data['age']);
$height = floatval($data['height']);
$weight = floatval($data['weight']);

// Validate positive values
if ($child_id <= 0 || $age < 0 || $height <= 0 || $weight <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid numeric values']);
    exit;
}

try {
    // ✅ Check if growth record exists
    $stmt = $pdo->prepare("SELECT id FROM growth WHERE child_id = ? AND age_months = ?");
    $stmt->execute([$child_id, $age]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // ✅ Update existing record
        $stmt = $pdo->prepare("
            UPDATE growth 
            SET height = ?, weight = ?, updated_at = NOW() 
            WHERE child_id = ? AND age_months = ?
        ");
        $stmt->execute([$height, $weight, $child_id, $age]);
        $action = "updated";
    } else {
        // ✅ Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO growth (child_id, age_months, height, weight, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$child_id, $age, $height, $weight]);
        $action = "inserted";
    }

    echo json_encode(['success' => true, 'message' => "Record $action successfully"]);
} 
catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
