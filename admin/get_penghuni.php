<?php
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM tb_penghuni WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $penghuni = $stmt->fetch();
    
    header('Content-Type: application/json');
    echo json_encode($penghuni);
}
?> 