<?php
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT kp.*, k.nomor as kamar_nomor, p.nama as penghuni_nama
        FROM tb_kmr_penghuni kp
        JOIN tb_kamar k ON kp.id_kamar = k.id
        JOIN tb_penghuni p ON kp.id_penghuni = p.id
        WHERE kp.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $penempatan = $stmt->fetch();
    
    header('Content-Type: application/json');
    echo json_encode($penempatan);
}
?> 