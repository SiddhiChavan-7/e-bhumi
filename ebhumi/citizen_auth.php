<?php
session_start();
require_once 'config.php';

// If already logged in, redirect
if (isset($_SESSION['citizen_logged_in']) && $_SESSION['citizen_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $full_name = trim($_POST['full_name']);
        $mobile_number = trim($_POST['mobile_number']);
        $aadhaar_number = trim($_POST['aadhaar_number']);
        $password = $_POST['password'];

        if (empty($full_name) || empty($mobile_number) || empty($aadhaar_number) || empty($password)) {
            header("Location: login.php?error=empty_fields&form=register");
            exit;
        }

        try {
            // Check if mobile or aadhaar already exists
            $stmt = $pdo->prepare("SELECT id FROM citizens WHERE mobile_number = ? OR aadhaar_number = ?");
            $stmt->execute([$mobile_number, $aadhaar_number]);
            if ($stmt->rowCount() > 0) {
                header("Location: login.php?error=exists&form=register");
                exit;
            }

            // Hash the password and insert
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO citizens (full_name, mobile_number, aadhaar_number, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $mobile_number, $aadhaar_number, $hashed_password]);

            $citizen_id = $pdo->lastInsertId();

            // Log them in immediately
            $_SESSION['citizen_logged_in'] = true;
            $_SESSION['citizen_id'] = $citizen_id;
            $_SESSION['citizen_name'] = $full_name;

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            header("Location: login.php?error=db&form=register");
            exit;
        }

    } elseif ($action === 'login') {
        $mobile_number = trim($_POST['mobile_number']);
        $password = $_POST['password'];

        if (empty($mobile_number) || empty($password)) {
            header("Location: login.php?error=empty_fields&form=login");
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, full_name, password FROM citizens WHERE mobile_number = ?");
            $stmt->execute([$mobile_number]);
            
            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch();
                if (password_verify($password, $row['password'])) {
                    // Password is correct
                    $_SESSION['citizen_logged_in'] = true;
                    $_SESSION['citizen_id'] = $row['id'];
                    $_SESSION['citizen_name'] = $row['full_name'];
                    
                    header("Location: index.php");
                    exit;
                } else {
                    // Invalid password
                    header("Location: login.php?error=invalid_credentials&form=login");
                    exit;
                }
            } else {
                // Invalid mobile number
                header("Location: login.php?error=invalid_credentials&form=login");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            header("Location: login.php?error=db&form=login");
            exit;
        }
    }
} else {
    // Not a POST request
    header("Location: login.php");
    exit;
}
?>
