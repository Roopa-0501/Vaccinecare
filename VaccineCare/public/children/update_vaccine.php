<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../../core/db.php";

// ✅ Check login
if(!isset($_SESSION['user'])){
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ✅ Only ANM can update
if($_SESSION['user']['role'] !== 'anm'){
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// ✅ Read JSON input
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;
$status = isset($data['status']) ? trim($data['status']) : '';
$date_completed = isset($data['date_completed']) && $data['date_completed'] !== '' 
                  ? date('Y-m-d', strtotime($data['date_completed'])) 
                  : null;

// ✅ Validate inputs
$allowedStatus = ['Pending', 'Completed', 'Overdue', 'Skipped'];
if(!$id || !in_array($status, $allowedStatus)){
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE vaccine_children SET status = :status, date_completed = :date_completed WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':date_completed' => $date_completed,
        ':id' => $id
    ]);
    
    echo json_encode(['success' => true]);
} catch(PDOException $e){
    echo json_encode(['error' => $e->getMessage()]);
}
