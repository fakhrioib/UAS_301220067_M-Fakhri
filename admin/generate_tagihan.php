<?php
require_once '../config/database.php';

$message = '';
$message_type = '';

// Handle bill generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    try {
        $bulan = $_POST['bulan'];
        
        // Get active room placements
        $stmt = $pdo->prepare("
            SELECT kp.id, kp.id_kamar, kp.id_penghuni, k.harga as harga_kamar
            FROM tb_kmr_penghuni kp
            JOIN tb_kamar k ON kp.id_kamar = k.id
            WHERE kp.tgl_keluar IS NULL
        ");
        $stmt->execute();
        $active_placements = $stmt->fetchAll();
        
        $generated_count = 0;
        
        foreach ($active_placements as $placement) {
            // Check if bill already exists for this month and placement
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM tb_tagihan 
                WHERE bulan = ? AND id_kmr_penghuni = ?
            ");
            $stmt->execute([$bulan, $placement['id']]);
            $exists = $stmt->fetch()['count'] > 0;
            
            if (!$exists) {
                // Calculate total bill (room rent + additional items)
                $total_bill = $placement['harga_kamar'];
                
                // Add additional items cost
                $stmt = $pdo->prepare("
                    SELECT SUM(b.harga) as total_barang
                    FROM tb_brng_bawaan bb
                    JOIN tb_barang b ON bb.id_barang = b.id
                    WHERE bb.id_penghuni = ?
                ");
                $stmt->execute([$placement['id_penghuni']]);
                $barang_result = $stmt->fetch();
                $total_bill += $barang_result['total_barang'] ?? 0;
                
                // Insert bill
                $stmt = $pdo->prepare("
                    INSERT INTO tb_tagihan (bulan, id_kmr_penghuni, jml_tagihan) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$bulan, $placement['id'], $total_bill]);
                $generated_count++;
            }
        }
        
        if ($generated_count > 0) {
            $message = "Berhasil generate $generated_count tagihan untuk bulan $bulan!";
            $message_type = "success";
        } else {
            $message = "Tidak ada tagihan baru yang perlu digenerate untuk bulan $bulan.";
            $message_type = "info";
        }
        
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Get current month and year
$current_month = date('Y-m');

// Get recent bills
$stmt = $pdo->query("
    SELECT t.*, k.nomor as kamar_nomor, p.nama as penghuni_nama, kp.tgl_masuk
    FROM tb_tagihan t
    JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN tb_kamar k ON kp.id_kamar = k.id
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    ORDER BY t.bulan DESC, t.id DESC
    LIMIT 10
");
$recent_bills = $stmt->fetchAll();

// Get active tenants without bills for current month
$stmt = $pdo->prepare("
    SELECT kp.id, k.nomor as kamar_nomor, p.nama as penghuni_nama, k.harga as harga_kamar
    FROM tb_kmr_penghuni kp
    JOIN tb_kamar k ON kp.id_kamar = k.id
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    WHERE kp.tgl_keluar IS NULL
    AND NOT EXISTS (
        SELECT 1 FROM tb_tagihan t 
        WHERE t.id_kmr_penghuni = kp.id 
        AND t.bulan = ?
    )
");
$stmt->execute([$current_month]);
$tenants_without_bills = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Tagihan - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .main-content {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-user-shield me-2"></i>Admin Panel
                        </h4>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="penghuni.php">
                                <i class="fas fa-users me-2"></i>Data Penghuni
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kamar.php">
                                <i class="fas fa-door-open me-2"></i>Data Kamar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="barang.php">
                                <i class="fas fa-box me-2"></i>Data Barang
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="penempatan.php">
                                <i class="fas fa-bed me-2"></i>Penempatan Kamar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="barang_bawaan.php">
                                <i class="fas fa-suitcase me-2"></i>Barang Bawaan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tagihan.php">
                                <i class="fas fa-file-invoice me-2"></i>Tagihan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pembayaran.php">
                                <i class="fas fa-money-bill-wave me-2"></i>Pembayaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="generate_tagihan.php">
                                <i class="fas fa-plus-circle me-2"></i>Generate Tagihan
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="../index.php">
                                <i class="fas fa-home me-2"></i>Kembali ke Beranda
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Generate Tagihan</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Generate Bill Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-invoice me-2"></i>Generate Tagihan Bulanan
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bulan" class="form-label">Pilih Bulan</label>
                                        <input type="month" class="form-control" id="bulan" name="bulan" value="<?= $current_month ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" name="generate" class="btn btn-primary">
                                                <i class="fas fa-magic me-2"></i>Generate Tagihan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Info:</strong> Tagihan akan dibuat otomatis untuk semua penghuni aktif. 
                            Jumlah tagihan = Harga sewa kamar + Harga barang bawaan.
                        </div>
                    </div>
                </div>

                <!-- Tenants Without Bills -->
                <?php if (!empty($tenants_without_bills)): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Penghuni Belum Ada Tagihan Bulan Ini
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Penghuni</th>
                                            <th>Kamar</th>
                                            <th>Harga Sewa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tenants_without_bills as $index => $tenant): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($tenant['penghuni_nama']) ?></td>
                                                <td><span class="badge bg-primary"><?= htmlspecialchars($tenant['kamar_nomor']) ?></span></td>
                                                <td>Rp <?= number_format($tenant['harga_kamar'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recent Bills -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Tagihan Terbaru
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_bills)): ?>
                            <p class="text-muted">Belum ada tagihan yang dibuat.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Bulan</th>
                                            <th>Penghuni</th>
                                            <th>Kamar</th>
                                            <th>Jumlah Tagihan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bills as $bill): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= date('F Y', strtotime($bill['bulan'] . '-01')) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($bill['penghuni_nama']) ?></td>
                                                <td><span class="badge bg-primary"><?= htmlspecialchars($bill['kamar_nomor']) ?></span></td>
                                                <td>Rp <?= number_format($bill['jml_tagihan'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php
                                                    // Check payment status
                                                    $stmt = $pdo->prepare("
                                                        SELECT SUM(jml_bayar) as total_bayar 
                                                        FROM tb_bayar 
                                                        WHERE id_tagihan = ?
                                                    ");
                                                    $stmt->execute([$bill['id']]);
                                                    $payment = $stmt->fetch();
                                                    $total_bayar = $payment['total_bayar'] ?? 0;
                                                    
                                                    if ($total_bayar >= $bill['jml_tagihan']) {
                                                        echo '<span class="badge bg-success">Lunas</span>';
                                                    } elseif ($total_bayar > 0) {
                                                        echo '<span class="badge bg-warning">Cicil</span>';
                                                    } else {
                                                        echo '<span class="badge bg-danger">Belum Bayar</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 