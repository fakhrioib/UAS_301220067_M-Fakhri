<?php
require_once '../config/database.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM tb_tagihan WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = "Tagihan berhasil dihapus!";
                    $message_type = "success";
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get bills data with payment status
$stmt = $pdo->query("
    SELECT t.*, 
           k.nomor as kamar_nomor,
           p.nama as penghuni_nama,
           p.no_hp as penghuni_hp,
           kp.tgl_masuk,
           COALESCE(SUM(b.jml_bayar), 0) as total_bayar,
           (t.jml_tagihan - COALESCE(SUM(b.jml_bayar), 0)) as sisa_tagihan
    FROM tb_tagihan t
    JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN tb_kamar k ON kp.id_kamar = k.id
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    LEFT JOIN tb_bayar b ON t.id = b.id_tagihan
    GROUP BY t.id
    ORDER BY t.bulan DESC, t.id DESC
");
$tagihan = $stmt->fetchAll();

// Filter by month if specified
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
if ($filter_bulan) {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               k.nomor as kamar_nomor,
               p.nama as penghuni_nama,
               p.no_hp as penghuni_hp,
               kp.tgl_masuk,
               COALESCE(SUM(b.jml_bayar), 0) as total_bayar,
               (t.jml_tagihan - COALESCE(SUM(b.jml_bayar), 0)) as sisa_tagihan
        FROM tb_tagihan t
        JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
        JOIN tb_kamar k ON kp.id_kamar = k.id
        JOIN tb_penghuni p ON kp.id_penghuni = p.id
        LEFT JOIN tb_bayar b ON t.id = b.id_tagihan
        WHERE t.bulan = ?
        GROUP BY t.id
        ORDER BY t.id DESC
    ");
    $stmt->execute([$filter_bulan]);
    $tagihan = $stmt->fetchAll();
}

// Get unique months for filter
$stmt = $pdo->query("SELECT DISTINCT bulan FROM tb_tagihan ORDER BY bulan DESC");
$months = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tagihan - Admin Panel</title>
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
                            <a class="nav-link active" href="tagihan.php">
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
                    <h1 class="h2">Data Tagihan</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="generate_tagihan.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Generate Tagihan
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="bulan" class="form-label">Filter Bulan</label>
                                <select class="form-select" id="bulan" name="bulan">
                                    <option value="">Semua Bulan</option>
                                    <?php foreach ($months as $month): ?>
                                        <option value="<?= $month['bulan'] ?>" <?= $filter_bulan == $month['bulan'] ? 'selected' : '' ?>>
                                            <?= date('F Y', strtotime($month['bulan'] . '-01')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i>Filter
                                    </button>
                                    <a href="tagihan.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bills Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Tagihan
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tagihan)): ?>
                            <p class="text-muted">Tidak ada data tagihan.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Bulan</th>
                                            <th>Penghuni</th>
                                            <th>Kamar</th>
                                            <th>Jumlah Tagihan</th>
                                            <th>Total Bayar</th>
                                            <th>Sisa</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tagihan as $index => $t): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= date('F Y', strtotime($t['bulan'] . '-01')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($t['penghuni_nama']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($t['penghuni_hp']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($t['kamar_nomor']) ?></span>
                                                </td>
                                                <td>Rp <?= number_format($t['jml_tagihan'], 0, ',', '.') ?></td>
                                                <td>Rp <?= number_format($t['total_bayar'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($t['sisa_tagihan'] > 0): ?>
                                                        <span class="text-danger fw-bold">
                                                            Rp <?= number_format($t['sisa_tagihan'], 0, ',', '.') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-success">Rp 0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($t['sisa_tagihan'] <= 0): ?>
                                                        <span class="badge bg-success">Lunas</span>
                                                    <?php elseif ($t['total_bayar'] > 0): ?>
                                                        <span class="badge bg-warning">Cicil</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Belum Bayar</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="pembayaran.php?tagihan_id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-money-bill"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTagihan(<?= $t['id'] ?>, '<?= htmlspecialchars($t['penghuni_nama']) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Summary -->
                <?php if (!empty($tagihan)): ?>
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4><?= count($tagihan) ?></h4>
                                    <p class="mb-0">Total Tagihan</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4><?= count(array_filter($tagihan, function($t) { return $t['sisa_tagihan'] <= 0; })) ?></h4>
                                    <p class="mb-0">Lunas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h4><?= count(array_filter($tagihan, function($t) { return $t['sisa_tagihan'] > 0 && $t['total_bayar'] > 0; })) ?></h4>
                                    <p class="mb-0">Cicil</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h4><?= count(array_filter($tagihan, function($t) { return $t['total_bayar'] == 0; })) ?></h4>
                                    <p class="mb-0">Belum Bayar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteTagihan(id, nama) {
            if (confirm(`Apakah Anda yakin ingin menghapus tagihan untuk "${nama}"?`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html> 