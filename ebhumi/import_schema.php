<?php
// Connect without selecting DB
$pdo = new PDO("mysql:host=127.0.0.1;port=3307", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec("DROP DATABASE IF EXISTS ebhumi_db");
    $pdo->exec("CREATE DATABASE ebhumi_db");
    $pdo->exec("USE ebhumi_db");
    
    $sql = file_get_contents('schema.sql');
    $pdo->exec($sql);
    
    // Hash admin password correctly
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("UPDATE users SET password='$hash' WHERE username='admin'");
    
    echo "Database rebuilt perfectly on ebhumi_db!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
