<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'perpustakaan_farash';

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    exit("Koneksi ke database gagal: " . $e->getMessage());
}

$pesan = '';
$jenisPesan = '';
$error = [];
$form = ['id' => '', 'judul' => '', 'penulis' => '', 'tahun' => '', 'stok' => ''];

// ===== HAPUS =====
if (isset($_GET['hapus'])) {
    $id = filter_var($_GET['hapus'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM buku WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: indexx.php?status=deleted");
    } else {
        header("Location: indexx.php?status=error");
    }
    exit();
}

// ===== SIMPAN (TAMBAH / UBAH) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'id'      => $_POST['id'] ?? '',
        'judul'   => trim($_POST['judul'] ?? ''),
        'penulis' => trim($_POST['penulis'] ?? ''),
        'tahun'   => $_POST['terbit'] ?? '',
        'stok'    => $_POST['stok'] ?? '',
    ];

    if (strlen($form['judul']) < 3) {
        $error[] = "Judul buku minimal 3 karakter.";
    }
    if ($form['penulis'] === '') {
        $error[] = "Penulis harus diisi.";
    }
    if (!ctype_digit($form['tahun']) || (int)$form['tahun'] < 1900 || (int)$form['tahun'] > (int)date('Y')) {
        $error[] = "Tahun terbit tidak valid (1900 - " . date('Y') . ").";
    }
    if (!ctype_digit($form['stok'])) {
        $error[] = "Stok harus berupa angka 0 atau lebih.";
    }

    if (empty($error)) {
        $data = [
            ':judul'   => $form['judul'],
            ':penulis' => $form['penulis'],
            ':tahun'   => (int)$form['tahun'],
            ':stok'    => (int)$form['stok'],
        ];

        if ($form['id'] === '') {
            // TAMBAH
            $stmt = $conn->prepare(
                "INSERT INTO buku (judul, penulis, tahun, stok, created_at)
                 VALUES (:judul, :penulis, :tahun, :stok, NOW())"
            );
            $stmt->execute($data);
            header("Location: indexx.php?status=success");
        } else {
            // UBAH
            $data[':id'] = (int)$form['id'];
            $stmt = $conn->prepare(
                "UPDATE buku SET judul = :judul, penulis = :penulis, tahun = :tahun, stok = :stok
                 WHERE id = :id"
            );
            $stmt->execute($data);
            header("Location: indexx.php?status=updated");
        }
        exit();
    }

    $pesan = implode("<br>", array_map('htmlspecialchars', $error));
    $jenisPesan = 'danger';
}

// ===== ISI FORM UNTUK EDIT =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM buku WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $form = [
                'id'      => (string)$row['id'],
                'judul'   => $row['judul'],
                'penulis' => $row['penulis'],
                'tahun'   => (string)$row['tahun'],
                'stok'    => (string)$row['stok'],
            ];
        } else {
            $pesan = "Data buku tidak ditemukan.";
            $jenisPesan = 'danger';
        }
    }
}

// ===== PESAN STATUS =====
$status = $_GET['status'] ?? '';
$daftarStatus = [
    'success' => ['Data buku berhasil ditambahkan.', 'success'],
    'updated' => ['Data buku berhasil diubah.', 'success'],
    'deleted' => ['Data buku berhasil dihapus.', 'success'],
    'error'   => ['Terjadi kesalahan.', 'danger'],
];
if (isset($daftarStatus[$status])) {
    [$pesan, $jenisPesan] = $daftarStatus[$status];
}

// ===== AMBIL DATA (setelah semua proses di atas) =====
$list_buku = $conn->query("SELECT * FROM buku ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$modeEdit = $form['id'] !== '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .bg-merah { background-color: #8b0000; }
        .bg-merah-muda { background-color: #f8e6e6; }
        .border-merah { border-color: #8b0000 !important; }
        .btn-merah { background-color: #8b0000; border-color: #8b0000; color: #fff; }
        .btn-merah:hover { background-color: #6b0000; border-color: #6b0000; color: #fff; }
    </style>
    <title>Website Praktik - Daftar Buku</title>
</head>
<body>
    <header class="bg-merah text-white p-3">
        <h1 class="h3 m-0">Website Praktik</h1>
    </header>

    <nav class="navbar navbar-light bg-merah-muda">
        <div class="container-fluid">
            <a class="navbar-brand" href="indexx.php">Navbar</a>
            <form class="d-flex" role="search">
                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-dark" type="submit">Search</button>
            </form>
        </div>
    </nav>

    <div class="container-fluid my-4">
        <div class="row">
            <aside class="col-md-3 mb-3">
                <div class="bg-merah-muda border border-merah rounded p-3">
                    <h2>Menu</h2>
                    <ul class="list-group">
                        <li class="list-group-item"><a href="dashboard.html" class="text-decoration-none text-black">Dashboard</a></li>
                        <li class="list-group-item"><a href="indexx.php" class="text-decoration-none text-black">Daftar Buku</a></li>
                        <li class="list-group-item">Form Peminjaman</li>
                    </ul>
                </div>
            </aside>

            <main class="col-md-9">
                <!-- FORM TAMBAH / EDIT -->
                <section>
                    <h2><?= $modeEdit ? 'Form Edit Buku' : 'Form Tambah Buku' ?></h2>
                    <form action="indexx.php" method="post" id="form-buku" novalidate>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($form['id']) ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="judul" class="form-label">Judul Buku</label>
                                <input type="text" class="form-control" name="judul" id="judul"
                                       required minlength="3"
                                       value="<?= htmlspecialchars($form['judul']) ?>">
                                <div class="invalid-feedback">Minimal 3 karakter</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="penulis" class="form-label">Penulis</label>
                                <input type="text" class="form-control" name="penulis" id="penulis"
                                       required
                                       value="<?= htmlspecialchars($form['penulis']) ?>">
                                <div class="invalid-feedback">Isi Penulis</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="terbit" class="form-label">Tahun Terbit</label>
                                <input type="number" class="form-control" name="terbit" id="terbit"
                                       min="1900" max="<?= date('Y') ?>" required
                                       value="<?= htmlspecialchars($form['tahun']) ?>">
                                <div class="invalid-feedback">Isi Tahun Terbit (1900 - <?= date('Y') ?>)</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stok" class="form-label">Stok</label>
                                <input type="number" class="form-control" name="stok" id="stok"
                                       min="0" step="1" required
                                       value="<?= htmlspecialchars($form['stok']) ?>">
                                <div class="invalid-feedback">Stok tidak boleh kosong</div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-merah">
                            <?= $modeEdit ? 'Simpan Perubahan' : 'Tambah Buku' ?>
                        </button>
                        <?php if ($modeEdit): ?>
                            <a href="indexx.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </form>
                </section>

                <!-- DAFTAR BUKU -->
                <section class="border rounded p-3 mt-4">
                    <h2>Daftar Buku</h2>

                    <?php if ($pesan !== ''): ?>
                        <div class="alert alert-<?= $jenisPesan ?>">
                            <?= $pesan ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Penulis</th>
                                    <th>Tahun Terbit</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($list_buku)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data buku</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($list_buku as $index => $buku): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($buku['judul']) ?></td>
                                            <td><?= htmlspecialchars($buku['penulis']) ?></td>
                                            <td><?= (int)$buku['tahun'] ?></td>
                                            <td><?= (int)$buku['stok'] ?></td>
                                            <td>
                                                <a href="indexx.php?edit=<?= (int)$buku['id'] ?>"
                                                   class="btn btn-sm btn-merah">Edit</a>
                                                <a href="indexx.php?hapus=<?= (int)$buku['id'] ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <footer class="bg-merah-muda text-black text-center p-2 m-4">
        <p class="mb-0">&copy; <?= date('Y') ?> Website Praktik. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi sisi browser saja. Data tetap dikirim ke PHP kalau valid.
        const form = document.getElementById("form-buku");
        form.addEventListener("submit", function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add("was-validated");
        });
    </script>
</body>
</html>