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
                    $stmt = $pdo->prepare("INSERT INTO tb_kmr_penghuni (id_kamar, id_penghuni, tgl_masuk) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['id_kamar'], $_POST['id_penghuni'], $_POST['tgl_masuk']]);
                    $message = "Penempatan berhasil ditambahkan!";
                    $message_type = "success";
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
                
            case 'edit':
                try {
                    $stmt = $pdo->prepare("UPDATE tb_kmr_penghuni SET id_kamar = ?, id_penghuni = ?, tgl_masuk = ?, tgl_keluar = ? WHERE id = ?");
                    $tgl_keluar = !empty($_POST['tgl_keluar']) ? $_POST['tgl_keluar'] : null;
                    $stmt->execute([$_POST['id_kamar'], $_POST['id_penghuni'], $_POST['tgl_masuk'], $tgl_keluar, $_POST['id']]);
                    $message = "Data penempatan berhasil diperbarui!";
                    $message_type = "success";
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM tb_kmr_penghuni WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = "Penempatan berhasil dihapus!";
                    $message_type = "success";
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get placement data
$stmt = $pdo->query("
    SELECT kp.*, 
           k.nomor as kamar_nomor,
           k.harga as kamar_harga,
           p.nama as penghuni_nama,
           p.no_hp as penghuni_hp
    FROM tb_kmr_penghuni kp
    JOIN tb_kamar k ON kp.id_kamar = k.id
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    ORDER BY kp.tgl_masuk DESC
");
$penempatan = $stmt->fetchAll();

// Get available rooms
$stmt = $pdo->query("
    SELECT k.id, k.nomor, k.harga
    FROM tb_kamar k 
    LEFT JOIN tb_kmr_penghuni kp ON k.id = kp.id_kamar AND kp.tgl_keluar IS NULL
    WHERE kp.id IS NULL
    ORDER BY k.nomor
");
$available_rooms = $stmt->fetchAll();

// Get available tenants
$stmt = $pdo->query("
    SELECT p.id, p.nama, p.no_hp
    FROM tb_penghuni p
    LEFT JOIN tb_kmr_penghuni kp ON p.id = kp.id_penghuni AND kp.tgl_keluar IS NULL
    WHERE p.tgl_keluar IS NULL AND kp.id IS NULL
    ORDER BY p.nama
");
$available_tenants = $stmt->fetchAll();

// Get placement for editing
$edit_penempatan = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT kp.*, k.nomor as kamar_nomor, p.nama as penghuni_nama
        FROM tb_kmr_penghuni kp
        JOIN tb_kamar k ON kp.id_kamar = k.id
        JOIN tb_penghuni p ON kp.id_penghuni = p.id
        WHERE kp.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $edit_penempatan = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penempatan Kamar - Admin Panel</title>
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
                            <a class="nav-link active" href="penempatan.php">
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
                    <h1 class="h2">Penempatan Kamar</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus me-2"></i>Tambah Penempatan
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Placement Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Penempatan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Penghuni</th>
                                        <th>Kamar</th>
                                        <th>Harga Sewa</th>
                                        <th>Tanggal Masuk</th>
                                        <th>Tanggal Keluar</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($penempatan as $index => $p): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($p['penghuni_nama']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($p['penghuni_hp']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($p['kamar_nomor']) ?></span>
                                            </td>
                                            <td>Rp <?= number_format($p['kamar_harga'], 0, ',', '.') ?></td>
                                            <td><?= date('d/m/Y', strtotime($p['tgl_masuk'])) ?></td>
                                            <td>
                                                <?php if ($p['tgl_keluar']): ?>
                                                    <?= date('d/m/Y', strtotime($p['tgl_keluar'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($p['tgl_keluar']): ?>
                                                    <span class="badge bg-secondary">Selesai</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editPenempatan(<?= $p['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deletePenempatan(<?= $p['id'] ?>, '<?= htmlspecialchars($p['penghuni_nama']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-door-open me-2"></i>Kamar Tersedia
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($available_rooms)): ?>
                                    <p class="text-muted mb-0">Tidak ada kamar tersedia.</p>
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
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>Penghuni Tersedia
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($available_tenants)): ?>
                                    <p class="text-muted mb-0">Tidak ada penghuni tersedia.</p>
                                <?php else: ?>
                                    <?php foreach ($available_tenants as $tenant): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold"><?= htmlspecialchars($tenant['nama']) ?></span>
                                            <small class="text-muted"><?= htmlspecialchars($tenant['no_hp']) ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Penempatan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="id_penghuni" class="form-label">Pilih Penghuni</label>
                            <select class="form-select" id="id_penghuni" name="id_penghuni" required>
                                <option value="">Pilih Penghuni...</option>
                                <?php foreach ($available_tenants as $tenant): ?>
                                    <option value="<?= $tenant['id'] ?>">
                                        <?= htmlspecialchars($tenant['nama']) ?> - <?= htmlspecialchars($tenant['no_hp']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_kamar" class="form-label">Pilih Kamar</label>
                            <select class="form-select" id="id_kamar" name="id_kamar" required>
                                <option value="">Pilih Kamar...</option>
                                <?php foreach ($available_rooms as $room): ?>
                                    <option value="<?= $room['id'] ?>">
                                        Kamar <?= htmlspecialchars($room['nomor']) ?> - Rp <?= number_format($room['harga'], 0, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="tgl_masuk" class="form-label">Tanggal Masuk</label>
                            <input type="date" class="form-control" id="tgl_masuk" name="tgl_masuk" required>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Penempatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_id_penghuni" class="form-label">Penghuni</label>
                            <input type="text" class="form-control" id="edit_penghuni_nama" readonly>
                            <input type="hidden" id="edit_id_penghuni" name="id_penghuni">
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_kamar" class="form-label">Kamar</label>
                            <input type="text" class="form-control" id="edit_kamar_nomor" readonly>
                            <input type="hidden" id="edit_id_kamar" name="id_kamar">
                        </div>
                        <div class="mb-3">
                            <label for="edit_tgl_masuk" class="form-label">Tanggal Masuk</label>
                            <input type="date" class="form-control" id="edit_tgl_masuk" name="tgl_masuk" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tgl_keluar" class="form-label">Tanggal Keluar (Opsional)</label>
                            <input type="date" class="form-control" id="edit_tgl_keluar" name="tgl_keluar">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
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
        function editPenempatan(id) {
            // Fetch placement data and populate modal
            fetch(`get_penempatan.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_id_penghuni').value = data.id_penghuni;
                    document.getElementById('edit_id_kamar').value = data.id_kamar;
                    document.getElementById('edit_penghuni_nama').value = data.penghuni_nama;
                    document.getElementById('edit_kamar_nomor').value = data.kamar_nomor;
                    document.getElementById('edit_tgl_masuk').value = data.tgl_masuk;
                    document.getElementById('edit_tgl_keluar').value = data.tgl_keluar || '';
                    
                    new bootstrap.Modal(document.getElementById('editModal')).show();
                });
        }

        function deletePenempatan(id, nama) {
            if (confirm(`Apakah Anda yakin ingin menghapus penempatan untuk "${nama}"?`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Set today's date as default for new placement
        document.getElementById('tgl_masuk').value = new Date().toISOString().split('T')[0];
    </script>
</body>
</html> 