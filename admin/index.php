<?php
require_once '../config/database.php';

// Get dashboard statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM tb_kamar");
$total_rooms = $stmt->fetch()['total_rooms'];

$stmt = $pdo->query("
    SELECT COUNT(*) as occupied_rooms 
    FROM tb_kamar k 
    JOIN tb_kmr_penghuni kp ON k.id = kp.id_kamar 
    WHERE kp.tgl_keluar IS NULL
");
$occupied_rooms = $stmt->fetch()['occupied_rooms'];

$stmt = $pdo->query("
    SELECT COUNT(*) as total_tenants 
    FROM tb_penghuni 
    WHERE tgl_keluar IS NULL
");
$total_tenants = $stmt->fetch()['total_tenants'];

$stmt = $pdo->query("
    SELECT COUNT(*) as overdue_payments
    FROM tb_kamar k
    JOIN tb_kmr_penghuni kp ON k.id = kp.id_kamar
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    WHERE kp.tgl_keluar IS NULL 
    AND p.tgl_keluar IS NULL
    AND DATEDIFF(CURDATE(), p.tgl_masuk) > 30
");
$overdue_payments = $stmt->fetch()['overdue_payments'];

// Get recent activities
$stmt = $pdo->query("
    SELECT p.nama, k.nomor, kp.tgl_masuk
    FROM tb_kmr_penghuni kp
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    JOIN tb_kamar k ON kp.id_kamar = k.id
    WHERE kp.tgl_masuk >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY kp.tgl_masuk DESC
    LIMIT 5
");
$recent_activities = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Sistem Manajemen Kost</title>
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
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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
                            <a class="nav-link active" href="index.php">
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
                            <a class="nav-link" href="generate_tagihan.php">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="text-muted"><?= date('d F Y') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Kamar</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_rooms ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-door-open fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Kamar Terisi</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $occupied_rooms ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bed fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Penghuni</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_tenants ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Terlambat Bayar</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $overdue_payments ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2"></i>Aksi Cepat
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <a href="penghuni.php?action=add" class="btn btn-primary w-100">
                                            <i class="fas fa-user-plus me-2"></i>Tambah Penghuni
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="penempatan.php?action=add" class="btn btn-success w-100">
                                            <i class="fas fa-bed me-2"></i>Penempatan Baru
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="generate_tagihan.php" class="btn btn-warning w-100">
                                            <i class="fas fa-file-invoice me-2"></i>Generate Tagihan
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="pembayaran.php?action=add" class="btn btn-info w-100">
                                            <i class="fas fa-money-bill me-2"></i>Input Pembayaran
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Aktivitas Terbaru
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_activities)): ?>
                                    <p class="text-muted">Tidak ada aktivitas terbaru.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nama Penghuni</th>
                                                    <th>Kamar</th>
                                                    <th>Tanggal Masuk</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_activities as $activity): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($activity['nama']) ?></td>
                                                        <td><span class="badge bg-primary"><?= htmlspecialchars($activity['nomor']) ?></span></td>
                                                        <td><?= date('d/m/Y', strtotime($activity['tgl_masuk'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 