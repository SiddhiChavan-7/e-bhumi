<?php
require_once 'config.php';
$hash = password_hash('admin123', PASSWORD_BCRYPT);
try {
    $pdo->exec("UPDATE users SET password='$hash' WHERE username='admin'");
    echo "Admin password updated to 'admin123'";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
