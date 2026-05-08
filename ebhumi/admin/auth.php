<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both username and password.";
        header("Location: ../login.php?error=1");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['login_error'] = "Invalid username or password.";
            header("Location: ../login.php?error=1");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['login_error'] = "System error. Please try again later.";
        header("Location: ../login.php?error=1");
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>
