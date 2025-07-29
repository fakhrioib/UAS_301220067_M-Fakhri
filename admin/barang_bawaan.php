<?php
require_once '../config/database.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("INSERT INTO tb_brng_bawaan (id_penghuni, id_barang) VALUES (?, ?)");
                    $stmt->execute([$_POST['id_penghuni'], $_POST['id_barang']]);
                    $message = "Barang bawaan berhasil ditambahkan!";
                    $message_type = "success";
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM tb_brng_bawaan WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = "Barang bawaan berhasil dihapus!";
                    $message_type = "success";
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get tenant items data
$stmt = $pdo->query("
    SELECT bb.*, 
           p.nama as penghuni_nama,
           p.no_hp as penghuni_hp,
           b.nama as barang_nama,
           b.harga as barang_harga,
           k.nomor as kamar_nomor
    FROM tb_brng_bawaan bb
    JOIN tb_penghuni p ON bb.id_penghuni = p.id
    JOIN tb_barang b ON bb.id_barang = b.id
    LEFT JOIN tb_kmr_penghuni kp ON p.id = kp.id_penghuni AND kp.tgl_keluar IS NULL
    LEFT JOIN tb_kamar k ON kp.id_kamar = k.id
    ORDER BY p.nama, b.nama
");
$barang_bawaan = $stmt->fetchAll();

// Get active tenants
$stmt = $pdo->query("
    SELECT p.id, p.nama, p.no_hp, k.nomor as kamar_nomor
    FROM tb_penghuni p
    LEFT JOIN tb_kmr_penghuni kp ON p.id = kp.id_penghuni AND kp.tgl_keluar IS NULL
    LEFT JOIN tb_kamar k ON kp.id_kamar = k.id
    WHERE p.tgl_keluar IS NULL
    ORDER BY p.nama
");
$active_tenants = $stmt->fetchAll();

// Get available items
$stmt = $pdo->query("SELECT * FROM tb_barang ORDER BY nama");
$available_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Bawaan - Admin Panel</title>
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
                            <a class="nav-link active" href="barang_bawaan.php">
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
                    <h1 class="h2">Barang Bawaan</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus me-2"></i>Tambah Barang Bawaan
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tenant Items Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Barang Bawaan
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($barang_bawaan)): ?>
                            <p class="text-muted">Tidak ada data barang bawaan.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Penghuni</th>
                                            <th>Kamar</th>
                                            <th>Barang</th>
                                            <th>Harga</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($barang_bawaan as $index => $bb): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($bb['penghuni_nama']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($bb['penghuni_hp']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($bb['kamar_nomor']): ?>
                                                        <span class="badge bg-primary"><?= htmlspecialchars($bb['kamar_nomor']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($bb['barang_nama']) ?></strong>
                                                </td>
                                                <td>Rp <?= number_format($bb['barang_harga'], 0, ',', '.') ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBarangBawaan(<?= $bb['id'] ?>, '<?= htmlspecialchars($bb['penghuni_nama']) ?>', '<?= htmlspecialchars($bb['barang_nama']) ?>')">
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

                <!-- Summary by Tenant -->
                <?php if (!empty($barang_bawaan)): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-pie me-2"></i>Ringkasan per Penghuni
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $tenant_summary = [];
                            foreach ($barang_bawaan as $bb) {
                                $tenant_id = $bb['id_penghuni'];
                                if (!isset($tenant_summary[$tenant_id])) {
                                    $tenant_summary[$tenant_id] = [
                                        'nama' => $bb['penghuni_nama'],
                                        'kamar' => $bb['kamar_nomor'],
                                        'items' => [],
                                        'total' => 0
                                    ];
                                }
                                $tenant_summary[$tenant_id]['items'][] = $bb['barang_nama'];
                                $tenant_summary[$tenant_id]['total'] += $bb['barang_harga'];
                            }
                            ?>
                            
                            <div class="row">
                                <?php foreach ($tenant_summary as $summary): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-info">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <?= htmlspecialchars($summary['nama']) ?>
                                                    <?php if ($summary['kamar']): ?>
                                                        <span class="badge bg-primary ms-2"><?= htmlspecialchars($summary['kamar']) ?></span>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="card-text">
                                                    <strong>Barang:</strong> <?= implode(', ', $summary['items']) ?><br>
                                                    <strong>Total:</strong> Rp <?= number_format($summary['total'], 0, ',', '.') ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Barang Bawaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="id_penghuni" class="form-label">Pilih Penghuni</label>
                            <select class="form-select" id="id_penghuni" name="id_penghuni" required>
                                <option value="">Pilih Penghuni...</option>
                                <?php foreach ($active_tenants as $tenant): ?>
                                    <option value="<?= $tenant['id'] ?>">
                                        <?= htmlspecialchars($tenant['nama']) ?>
                                        <?php if ($tenant['kamar_nomor']): ?>
                                            - Kamar <?= htmlspecialchars($tenant['kamar_nomor']) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_barang" class="form-label">Pilih Barang</label>
                            <select class="form-select" id="id_barang" name="id_barang" required>
                                <option value="">Pilih Barang...</option>
                                <?php foreach ($available_items as $item): ?>
                                    <option value="<?= $item['id'] ?>">
                                        <?= htmlspecialchars($item['nama']) ?> - Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteBarangBawaan(id, nama, barang) {
            if (confirm(`Apakah Anda yakin ingin menghapus barang "${barang}" dari "${nama}"?`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html> 