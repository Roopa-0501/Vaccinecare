<?php
session_start();
require_once "../../core/db.php";

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
            $stmt = $pdo->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $role = $user['role'];
                if ($role !== 'parent' && $role !== 'anm') {
                    $error = "Only Parent or ANM accounts allowed.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email']   = $user['email'];
                    $_SESSION['role']    = $role;

                    if ($role === 'parent') {
                        header("Location: ../../public/dashboards/parent.php");
                    } elseif ($role === 'anm') {
                        header("Location: ../../public/dashboards/anm.php");
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
