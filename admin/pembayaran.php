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
                    $stmt = $pdo->prepare("INSERT INTO tb_bayar (id_tagihan, jml_bayar, status) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['id_tagihan'], $_POST['jml_bayar'], $_POST['status']]);
                    $message = "Pembayaran berhasil ditambahkan!";
                    $message_type = "success";
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM tb_bayar WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = "Pembayaran berhasil dihapus!";
                    $message_type = "success";
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get payments data
$stmt = $pdo->query("
    SELECT b.*, 
           t.bulan,
           t.jml_tagihan,
           k.nomor as kamar_nomor,
           p.nama as penghuni_nama,
           p.no_hp as penghuni_hp
    FROM tb_bayar b
    JOIN tb_tagihan t ON b.id_tagihan = t.id
    JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN tb_kamar k ON kp.id_kamar = k.id
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    ORDER BY b.tgl_bayar DESC
");
$pembayaran = $stmt->fetchAll();

// Get bills for payment form
$stmt = $pdo->query("
    SELECT t.*, 
           k.nomor as kamar_nomor,
           p.nama as penghuni_nama,
           COALESCE(SUM(b.jml_bayar), 0) as total_bayar,
           (t.jml_tagihan - COALESCE(SUM(b.jml_bayar), 0)) as sisa_tagihan
    FROM tb_tagihan t
    JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
    JOIN tb_kamar k ON kp.id_kamar = k.id
    JOIN tb_penghuni p ON kp.id_penghuni = p.id
    LEFT JOIN tb_bayar b ON t.id = b.id_tagihan
    GROUP BY t.id
    HAVING sisa_tagihan > 0
    ORDER BY t.bulan DESC
");
$unpaid_bills = $stmt->fetchAll();

// Get specific bill for payment if tagihan_id is provided
$selected_bill = null;
if (isset($_GET['tagihan_id'])) {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               k.nomor as kamar_nomor,
               p.nama as penghuni_nama,
               COALESCE(SUM(b.jml_bayar), 0) as total_bayar,
               (t.jml_tagihan - COALESCE(SUM(b.jml_bayar), 0)) as sisa_tagihan
        FROM tb_tagihan t
        JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
        JOIN tb_kamar k ON kp.id_kamar = k.id
        JOIN tb_penghuni p ON kp.id_penghuni = p.id
        LEFT JOIN tb_bayar b ON t.id = b.id_tagihan
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmt->execute([$_GET['tagihan_id']]);
    $selected_bill = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pembayaran - Admin Panel</title>
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
                            <a class="nav-link active" href="pembayaran.php">
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
                    <h1 class="h2">Data Pembayaran</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus me-2"></i>Tambah Pembayaran
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Payments Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Pembayaran
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pembayaran)): ?>
                            <p class="text-muted">Tidak ada data pembayaran.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Penghuni</th>
                                            <th>Kamar</th>
                                            <th>Bulan Tagihan</th>
                                            <th>Jumlah Bayar</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pembayaran as $index => $p): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($p['tgl_bayar'])) ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($p['penghuni_nama']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($p['penghuni_hp']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($p['kamar_nomor']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= date('F Y', strtotime($p['bulan'] . '-01')) ?>
                                                    </span>
                                                </td>
                                                <td>Rp <?= number_format($p['jml_bayar'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($p['status'] == 'lunas'): ?>
                                                        <span class="badge bg-success">Lunas</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Cicil</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deletePembayaran(<?= $p['id'] ?>, '<?= htmlspecialchars($p['penghuni_nama']) ?>')">
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

                <!-- Unpaid Bills Summary -->
                <?php if (!empty($unpaid_bills)): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Tagihan Belum Lunas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Penghuni</th>
                                            <th>Kamar</th>
                                            <th>Bulan</th>
                                            <th>Total Tagihan</th>
                                            <th>Sudah Bayar</th>
                                            <th>Sisa</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($unpaid_bills as $bill): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($bill['penghuni_nama']) ?></td>
                                                <td><span class="badge bg-primary"><?= htmlspecialchars($bill['kamar_nomor']) ?></span></td>
                                                <td><?= date('F Y', strtotime($bill['bulan'] . '-01')) ?></td>
                                                <td>Rp <?= number_format($bill['jml_tagihan'], 0, ',', '.') ?></td>
                                                <td>Rp <?= number_format($bill['total_bayar'], 0, ',', '.') ?></td>
                                                <td>
                                                    <span class="text-danger fw-bold">
                                                        Rp <?= number_format($bill['sisa_tagihan'], 0, ',', '.') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-success" onclick="bayarTagihan(<?= $bill['id'] ?>, '<?= htmlspecialchars($bill['penghuni_nama']) ?>', <?= $bill['sisa_tagihan'] ?>)">
                                                        <i class="fas fa-money-bill me-1"></i>Bayar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
                    <h5 class="modal-title">Tambah Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="id_tagihan" class="form-label">Pilih Tagihan</label>
                            <select class="form-select" id="id_tagihan" name="id_tagihan" required>
                                <option value="">Pilih Tagihan...</option>
                                <?php foreach ($unpaid_bills as $bill): ?>
                                    <option value="<?= $bill['id'] ?>">
                                        <?= htmlspecialchars($bill['penghuni_nama']) ?> - 
                                        Kamar <?= htmlspecialchars($bill['kamar_nomor']) ?> - 
                                        <?= date('F Y', strtotime($bill['bulan'] . '-01')) ?> 
                                        (Sisa: Rp <?= number_format($bill['sisa_tagihan'], 0, ',', '.') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jml_bayar" class="form-label">Jumlah Bayar (Rp)</label>
                            <input type="number" class="form-control" id="jml_bayar" name="jml_bayar" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="cicil">Cicil</option>
                                <option value="lunas">Lunas</option>
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
        function deletePembayaran(id, nama) {
            if (confirm(`Apakah Anda yakin ingin menghapus pembayaran untuk "${nama}"?`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function bayarTagihan(tagihanId, nama, sisa) {
            // Populate the payment form
            document.getElementById('id_tagihan').value = tagihanId;
            document.getElementById('jml_bayar').value = sisa;
            document.getElementById('status').value = 'lunas';
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('addModal')).show();
        }

        // Auto-select status based on payment amount
        document.getElementById('jml_bayar').addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            const tagihanSelect = document.getElementById('id_tagihan');
            const statusSelect = document.getElementById('status');
            
            if (tagihanSelect.value) {
                // Get the selected option text to extract remaining amount
                const selectedOption = tagihanSelect.options[tagihanSelect.selectedIndex];
                const text = selectedOption.text;
                const match = text.match(/Sisa: Rp ([\d,]+)/);
                
                if (match) {
                    const sisa = parseFloat(match[1].replace(/,/g, ''));
                    if (amount >= sisa) {
                        statusSelect.value = 'lunas';
                    } else {
                        statusSelect.value = 'cicil';
                    }
                }
            }
        });
    </script>
</body>
</html> 