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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .navbar {
            background: rgba(0, 0, 0, 0.9) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
            padding: 80px 0;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, #ffffff, #f8f9fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease-out 0.2s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px 30px 0 0;
            margin-top: -30px;
            position: relative;
            z-index: 3;
            padding: 50px 0;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            border: none;
            padding: 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .room-item {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 12px 20px;
            border-radius: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .room-item:hover {
            transform: translateX(5px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .payment-item {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .payment-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .overdue-item {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .overdue-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
        }

        .stats-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .btn-manage {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-manage:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
        }

        .footer {
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .main-content {
                margin-top: -20px;
                padding: 30px 0;
            }
            
            .card {
                margin-bottom: 20px;
            }
            
            .stats-number {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-section {
                padding: 60px 0;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .room-item, .payment-item, .overdue-item {
                padding: 10px 15px;
            }
        }

        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .slide-in-left {
            animation: slideInLeft 0.6s ease-out;
        }

        .slide-in-right {
            animation: slideInRight 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
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
        <div class="container hero-content">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="hero-title">Selamat Datang di Sistem Manajemen Kost</h1>
                    <p class="hero-subtitle">Kelola kost Anda dengan mudah dan efisien menggunakan teknologi modern</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Status Cards -->
            <div class="row mb-5">
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 slide-in-left">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-door-open me-2"></i>Kamar Tersedia
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($available_rooms)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-door-closed"></i>
                                    <p>Tidak ada kamar tersedia saat ini.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($available_rooms as $room): ?>
                                    <div class="room-item">
                                        <span class="fw-bold">Kamar <?= htmlspecialchars($room['nomor']) ?></span>
                                        <span class="badge bg-light text-success">Rp <?= number_format($room['harga'], 0, ',', '.') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100 fade-in">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2"></i>Pembayaran Mendatang
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcoming_payments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>Tidak ada pembayaran mendatang.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($upcoming_payments as $payment): ?>
                                    <div class="payment-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-bold">Kamar <?= htmlspecialchars($payment['nomor']) ?></div>
                                                <small><?= htmlspecialchars($payment['nama']) ?></small>
                                            </div>
                                            <span class="badge bg-light text-warning">Rp <?= number_format($payment['harga'], 0, ',', '.') ?></span>
                                        </div>
                                        <small class="d-block mt-2">Masuk: <?= date('d/m/Y', strtotime($payment['tgl_masuk'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100 slide-in-right">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Pembayaran Terlambat
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($overdue_payments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <p>Tidak ada pembayaran terlambat.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($overdue_payments as $payment): ?>
                                    <div class="overdue-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-bold">Kamar <?= htmlspecialchars($payment['nomor']) ?></div>
                                                <small><?= htmlspecialchars($payment['nama']) ?></small>
                                            </div>
                                            <span class="badge bg-light text-danger"><?= $payment['days_overdue'] ?> hari</span>
                                        </div>
                                        <small class="d-block mt-2">Masuk: <?= date('d/m/Y', strtotime($payment['tgl_masuk'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row">
                <div class="col-12">
                    <div class="stats-card">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="stats-number"><?= count($available_rooms) ?></div>
                                <div class="stats-label">Kamar Tersedia</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-number"><?= count($upcoming_payments) ?></div>
                                <div class="stats-label">Pembayaran Mendatang</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-number"><?= count($overdue_payments) ?></div>
                                <div class="stats-label">Pembayaran Terlambat</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="admin/index.php" class="btn btn-manage btn-lg">
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
    <footer class="footer">
        <div class="container text-center">
            <p class="mb-0">&copy; 2024 Sistem Manajemen Kost. UAS_30122007_MuhammadFakhri</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease-out';
            observer.observe(card);
        });

        // Smooth scroll for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html> 