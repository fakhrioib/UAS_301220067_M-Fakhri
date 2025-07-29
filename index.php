<?php
require_once 'config/database.php';

// Get available rooms
$stmt = $pdo->query("
    SELECT k.nomor, k.harga 
    FROM tb_kamar k 
    LEFT JOIN tb_kmr_penghuni kp ON k.id = kp.id_kamar AND kp.tgl_keluar IS NULL
    WHERE kp.id IS NULL
    ORDER BY k.nomor
");
$available_rooms = $stmt->fetchAll();

// Get rooms with upcoming payments (due in next 7 days)
$stmt = $pdo->query("
    SELECT DISTINCT k.nomor, p.nama, p.tgl_masuk, k.harga
    FROM tb_kamar k
    JOIN tb_kmr_penghuni kp ON k.id = kp.id_kamar
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    WHERE kp.tgl_keluar IS NULL 
    AND p.tgl_keluar IS NULL
    AND DATE_ADD(p.tgl_masuk, INTERVAL 30 DAY) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY p.tgl_masuk
");
$upcoming_payments = $stmt->fetchAll();

// Get overdue payments (more than 30 days since entry)
$stmt = $pdo->query("
    SELECT DISTINCT k.nomor, p.nama, p.tgl_masuk, k.harga,
           DATEDIFF(CURDATE(), p.tgl_masuk) as days_overdue
    FROM tb_kamar k
    JOIN tb_kmr_penghuni kp ON k.id = kp.id_kamar
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    WHERE kp.tgl_keluar IS NULL 
    AND p.tgl_keluar IS NULL
    AND DATEDIFF(CURDATE(), p.tgl_masuk) > 30
    ORDER BY p.tgl_masuk
");
$overdue_payments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-home me-2"></i>Sistem Manajemen Kost
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin/index.php">
                    <i class="fas fa-user-shield me-1"></i>Admin Panel
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">Selamat Datang di Sistem Manajemen Kost</h1>
            <p class="lead">Kelola kost Anda dengan mudah dan efisien</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Available Rooms -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-door-open me-2"></i>Kamar Tersedia
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($available_rooms)): ?>
                            <p class="text-muted">Tidak ada kamar tersedia saat ini.</p>
                        <?php else: ?>
                            <?php foreach ($available_rooms as $room): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Kamar <?= htmlspecialchars($room['nomor']) ?></span>
                                    <span class="badge bg-success">Rp <?= number_format($room['harga'], 0, ',', '.') ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Payments -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>Pembayaran Mendatang
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_payments)): ?>
                            <p class="text-muted">Tidak ada pembayaran mendatang.</p>
                        <?php else: ?>
                            <?php foreach ($upcoming_payments as $payment): ?>
                                <div class="border-bottom pb-2 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Kamar <?= htmlspecialchars($payment['nomor']) ?></span>
                                        <span class="badge bg-warning text-dark">Rp <?= number_format($payment['harga'], 0, ',', '.') ?></span>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars($payment['nama']) ?></small><br>
                                    <small class="text-muted">Masuk: <?= date('d/m/Y', strtotime($payment['tgl_masuk'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Overdue Payments -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Pembayaran Terlambat
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($overdue_payments)): ?>
                            <p class="text-muted">Tidak ada pembayaran terlambat.</p>
                        <?php else: ?>
                            <?php foreach ($overdue_payments as $payment): ?>
                                <div class="border-bottom pb-2 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Kamar <?= htmlspecialchars($payment['nomor']) ?></span>
                                        <span class="badge bg-danger"><?= $payment['days_overdue'] ?> hari</span>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars($payment['nama']) ?></small><br>
                                    <small class="text-muted">Masuk: <?= date('d/m/Y', strtotime($payment['tgl_masuk'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Statistik Singkat
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="text-primary"><?= count($available_rooms) ?></h4>
                                <p class="text-muted">Kamar Tersedia</p>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-warning"><?= count($upcoming_payments) ?></h4>
                                <p class="text-muted">Pembayaran Mendatang</p>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-danger"><?= count($overdue_payments) ?></h4>
                                <p class="text-muted">Pembayaran Terlambat</p>
                            </div>
                            <div class="col-md-3">
                                <a href="admin/index.php" class="btn btn-primary">
                                    <i class="fas fa-cog me-2"></i>Kelola Sistem
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2024 Sistem Manajemen Kost. UAS_30122007_MuhammadFakhri</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 