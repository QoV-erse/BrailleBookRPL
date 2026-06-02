<?php
require_once '../../includes/session.php';
require_once '../../includes/config.php';

// Cek role admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Proses jika form disubmit (logika sama seperti sebelumnya)
if (isset($_POST['simpan'])) {
    $judul        = mysqli_real_escape_string($conn, $_POST['judul']);
    $pengarang    = mysqli_real_escape_string($conn, $_POST['pengarang']);
    $penerbit     = mysqli_real_escape_string($conn, $_POST['penerbit']);
    $jml_halaman  = (int)$_POST['jml_halaman'];
    $ukuran       = $_POST['ukuran'];
    $jml_lembaran = (int)$_POST['jml_lembaran'];
    $kategori     = $_POST['kategori'];
    $sinopsis     = mysqli_real_escape_string($conn, $_POST['sinopsis']);
    $harga        = (float)$_POST['harga'];

    // Upload Gambar
    $gambar = 'default.jpg';
    if ($_FILES['gambar']['error'] === 0) {
        $nama_file  = time() . '_' . basename($_FILES['gambar']['name']);
        $target_dir = '../../assets/img/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_dir . $nama_file)) {
            $gambar = $nama_file;
        }
    }

    $query = "INSERT INTO buku 
                (judul, pengarang, penerbit, jml_halaman, ukuran, jml_lembaran,
                 kategori, sinopsis, harga, gambar)
              VALUES 
                ('$judul', '$pengarang', '$penerbit', $jml_halaman, '$ukuran', $jml_lembaran,
                 '$kategori', '$sinopsis', $harga, '$gambar')";

    if (mysqli_query($conn, $query)) {
        header("Location: index.php?status=sukses");
        exit;
    } else {
        $error = "Gagal menyimpan: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - Admin Braille</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary-main: #1cceac;
            --primary-dark: #15a38a;
            --accent-gold:  #FFD700;
            --accent-yellow:#FFC107;
            --light-bg:     #f4f6f9;
            --dark-text:    #2c3e50;
            --sidebar-width:280px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--light-bg); color:var(--dark-text); }

        /* ===== SIDEBAR (identik dengan dashboard & index buku) ===== */
        .sidebar {
            position:fixed; top:0; left:0; height:100vh; width:var(--sidebar-width);
            background:linear-gradient(180deg,var(--primary-main) 0%,var(--primary-dark) 100%);
            box-shadow:4px 0 20px rgba(28,206,172,.15); z-index:1000; overflow-y:auto;
        }
        .sidebar-brand { padding:25px 20px; border-bottom:1px solid rgba(255,255,255,.2); text-align:center; }
        .sidebar-brand i { font-size:40px; color:var(--accent-gold); }
        .sidebar-brand h4 { color:white; margin-top:10px; font-weight:bold; }

        .sidebar-nav { padding:20px 0; }
        .sidebar-nav .nav-link {
            color:rgba(255,255,255,.85); padding:15px 25px; font-size:16px;
            font-weight:500; transition:all .3s; border-left:4px solid transparent;
            display:flex; align-items:center;
        }
        .sidebar-nav .nav-link:hover { color:white; background:rgba(255,255,255,.15); border-left-color:var(--accent-gold); }
        .sidebar-nav .nav-link.active { color:white; background:rgba(255,255,255,.2); border-left-color:var(--accent-gold); }
        .sidebar-nav .nav-link i { width:25px; margin-right:10px; color:var(--accent-gold); }

        .sidebar-footer { position:absolute; bottom:0; width:100%; padding:20px; border-top:1px solid rgba(255,255,255,.2); color:rgba(255,255,255,.8); }

        /* ===== MAIN CONTENT ===== */
        .main-content { margin-left:var(--sidebar-width); min-height:100vh; padding:25px 30px; }

        /* ===== TOP BAR ===== */
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; padding-bottom:20px; border-bottom:1px solid #e0e0e0; }
        .top-bar h2 { color:var(--primary-dark); margin:0; font-size:1.5rem; }
        .top-bar h2 i { color:var(--accent-gold); margin-right:8px; }
        .admin-avatar { width:45px; height:45px; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-size:20px; font-weight:bold; }

        /* ===== FORM CARD ===== */
        .form-card { background:white; border-radius:18px; padding:30px; box-shadow:0 5px 20px rgba(0,0,0,.06); border:1px solid #eee; margin-bottom:24px; }

        .form-card-title {
            font-size:1rem; font-weight:700; color:var(--primary-dark);
            margin-bottom:20px; padding-bottom:12px;
            border-bottom:2px solid #f0fdf9;
            display:flex; align-items:center; gap:8px;
        }
        .form-card-title i { color:var(--primary-main); }

        /* ===== INPUT STYLE ===== */
        .form-label { font-weight:600; font-size:.9rem; margin-bottom:5px; color:var(--dark-text); }
        .form-label .required { color:#e74c3c; margin-left:2px; }

        .form-control, .form-select {
            border:1.5px solid #e0e0e0; border-radius:10px;
            padding:10px 14px; font-size:.95rem; transition:all .3s;
        }
        .form-control:focus, .form-select:focus {
            border-color:var(--primary-main);
            box-shadow:0 0 0 3px rgba(28,206,172,.15);
        }
        .form-text { font-size:.8rem; color:#888; margin-top:4px; }

        /* ===== UKURAN PREVIEW ===== */
        .ukuran-hint {
            background:#f0fdf9; border:1.5px solid #c8f0e8;
            border-radius:10px; padding:10px 14px; font-size:.85rem;
            color:var(--primary-dark); margin-top:8px; display:none;
        }
        .ukuran-hint i { color:var(--primary-main); margin-right:6px; }

        /* ===== GAMBAR PREVIEW ===== */
        .preview-box {
            width:100%; height:160px; border-radius:12px; overflow:hidden;
            background:linear-gradient(135deg,var(--primary-main),var(--primary-dark));
            display:flex; align-items:center; justify-content:center;
            color:white; font-size:40px; margin-bottom:12px;
        }
        .preview-box img { width:100%; height:100%; object-fit:cover; }

        /* ===== ALERT ===== */
        .alert-error { background:#ffebee; border:1.5px solid #f44336; border-radius:12px; padding:14px 18px; color:#c62828; margin-bottom:20px; display:flex; align-items:center; gap:10px; }

        /* ===== TOMBOL ===== */
        .btn-simpan {
            background:linear-gradient(135deg,var(--primary-main),var(--primary-dark));
            color:white; border:none; padding:13px 30px; border-radius:12px;
            font-weight:700; font-size:1rem; transition:all .3s;
            box-shadow:0 5px 15px rgba(28,206,172,.3);
            display:inline-flex; align-items:center; gap:8px;
        }
        .btn-simpan:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(28,206,172,.4); color:white; }

        .btn-batal {
            background:white; color:#555; border:2px solid #e0e0e0;
            padding:13px 25px; border-radius:12px; font-weight:600; font-size:1rem;
            transition:all .3s; text-decoration:none;
            display:inline-flex; align-items:center; gap:8px;
        }
        .btn-batal:hover { border-color:var(--primary-main); color:var(--primary-dark); }

        /* ===== FOOTER ===== */
        .footer-admin { margin-top:30px; padding-top:20px; border-top:1px solid #e0e0e0; text-align:center; color:#aaa; font-size:.875rem; }

        /* ===== RESPONSIVE ===== */
        @media(max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.show { transform:translateX(0); }
            .main-content { margin-left:0; padding:15px; }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-book-open"></i>
        <h4>Braille Admin</h4>
        <small class="text-white-50">Yayasan Raudlatul Makfufin</small>
    </div>
    <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
            <a class="nav-link" href="../dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="index.php">
                <i class="fas fa-book"></i> Kelola Buku
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../pesanan/index.php">
                <i class="fas fa-shopping-cart"></i> Pesanan Masuk
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../users/index.php">
                <i class="fas fa-users"></i> Data Pengguna
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <small><i class="far fa-clock me-1"></i><?= date('d M Y, H:i') ?> WIB</small>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">

    <!-- Top Bar -->
    <div class="top-bar">
        <div>
            <h2><i class="fas fa-plus-circle"></i> Tambah Buku Baru</h2>
            <p class="text-muted mb-0" style="font-size:.9rem;">
                Isi data buku braille yang akan ditambahkan ke katalog
            </p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <strong style="font-size:.95rem;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong><br>
                <small class="text-muted">Administrator</small>
            </div>
            <div class="admin-avatar">
                <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?>
            </div>
        </div>
    </div>

    <!-- Alert Error -->
    <?php if (isset($error)): ?>
    <div class="alert-error">
        <i class="fas fa-exclamation-circle fa-lg"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
    <div class="row g-4">

        <!-- KOLOM KIRI: Info Utama -->
        <div class="col-lg-8">

            <!-- Identitas Buku -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-book"></i> Identitas Buku
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Judul Buku <span class="required">*</span></label>
                        <input type="text" name="judul" class="form-control"
                               placeholder="Contoh: Al-Qur'an Braille Juz 1"
                               value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pengarang <span class="required">*</span></label>
                        <input type="text" name="pengarang" class="form-control"
                               placeholder="Nama pengarang / penyusun"
                               value="<?= htmlspecialchars($_POST['pengarang'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Penerbit</label>
                        <input type="text" name="penerbit" class="form-control"
                               placeholder="Nama penerbit"
                               value="<?= htmlspecialchars($_POST['penerbit'] ?? 'Yayasan Raudlatul Makfufin') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kategori <span class="required">*</span></label>
                        <select name="kategori" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Al-Quran"  <?= (($_POST['kategori'] ?? '') == 'Al-Quran')  ? 'selected':'' ?>>Al-Qur'an</option>
                            <option value="Islam"     <?= (($_POST['kategori'] ?? '') == 'Islam')     ? 'selected':'' ?>>Buku Islam</option>
                            <option value="Panduan"   <?= (($_POST['kategori'] ?? '') == 'Panduan')   ? 'selected':'' ?>>Buku Panduan</option>
                            <option value="Solat"     <?= (($_POST['kategori'] ?? '') == 'Solat')     ? 'selected':'' ?>>Buku Tentang Solat</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga Jual (Rp) <span class="required">*</span></label>
                        <input type="number" name="harga" class="form-control"
                               placeholder="Contoh: 75000" min="0" step="1000"
                               value="<?= htmlspecialchars($_POST['harga'] ?? '75000') ?>" required>
                        <div class="form-text">Harga per eksemplar sebelum ongkir</div>
                    </div>
                </div>
            </div>

            <!-- Spesifikasi Braille -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-ruler"></i> Spesifikasi Braille
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Ukuran Layout <span class="required">*</span></label>
                        <select name="ukuran" class="form-select" id="selectUkuran"
                                onchange="showUkuranHint()" required>
                            <option value="">-- Pilih Ukuran --</option>
                            <option value="Besar" <?= (($_POST['ukuran'] ?? '') == 'Besar') ? 'selected':'' ?>>Besar (25.5 × 30.5 cm)</option>
                            <option value="Kecil" <?= (($_POST['ukuran'] ?? '') == 'Kecil') ? 'selected':'' ?>>Kecil (1.5 × 25.5 cm)</option>
                        </select>
                        <div class="ukuran-hint" id="ukuranHint">
                            <i class="fas fa-info-circle"></i>
                            <span id="ukuranHintText"></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jumlah Halaman</label>
                        <input type="number" name="jml_halaman" class="form-control"
                               placeholder="0" min="1"
                               value="<?= htmlspecialchars($_POST['jml_halaman'] ?? '') ?>">
                        <div class="form-text">Total halaman buku</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jumlah Lembaran</label>
                        <input type="number" name="jml_lembaran" class="form-control"
                               placeholder="0" min="1"
                               value="<?= htmlspecialchars($_POST['jml_lembaran'] ?? '') ?>">
                        <div class="form-text">Total lembar braille</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Sinopsis / Deskripsi Buku</label>
                        <textarea name="sinopsis" class="form-control" rows="4"
                                  placeholder="Tulis deskripsi singkat tentang isi buku ini..."
                        ><?= htmlspecialchars($_POST['sinopsis'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

        </div><!-- /kolom kiri -->

        <!-- KOLOM KANAN: Gambar + Tombol -->
        <div class="col-lg-4">

            <!-- Upload Gambar -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-image"></i> Gambar Sampul
                </div>

                <!-- Preview -->
                <div class="preview-box" id="previewBox">
                    <i class="fas fa-book" id="previewIcon"></i>
                    <img id="previewImg" src="" alt="Preview" style="display:none;">
                </div>

                <input type="file" name="gambar" id="inputGambar"
                       class="form-control" accept="image/*"
                       onchange="previewGambar(this)">
                <div class="form-text">Format: JPG, PNG, WEBP. Maks 2MB.</div>
            </div>

            <!-- Tombol Aksi -->
            <div class="form-card">
                <div class="form-card-title">
                    <i class="fas fa-paper-plane"></i> Simpan Data
                </div>
                <p class="text-muted" style="font-size:.875rem; margin-bottom:20px;">
                    Pastikan semua data sudah benar sebelum menyimpan. Buku akan langsung tampil di katalog.
                </p>
                <div class="d-grid gap-3">
                    <button type="submit" name="simpan" class="btn-simpan">
                        <i class="fas fa-save"></i> Simpan Buku
                    </button>
                    <a href="index.php" class="btn-batal justify-content-center">
                        <i class="fas fa-arrow-left"></i> Batal & Kembali
                    </a>
                </div>
            </div>

        </div><!-- /kolom kanan -->

    </div>
    </form>

    <div class="footer-admin">
        <i class="fas fa-book-open me-2" style="color:var(--primary-main)"></i>
        © 2026 Yayasan Raudlatul Makfufin — Sistem Informasi Katalog Buku Braille
    </div>

</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Preview gambar sebelum upload
    function previewGambar(input) {
        const icon = document.getElementById('previewIcon');
        const img  = document.getElementById('previewImg');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                img.src = e.target.result;
                img.style.display = 'block';
                icon.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Hint ukuran buku
    function showUkuranHint() {
        const val  = document.getElementById('selectUkuran').value;
        const hint = document.getElementById('ukuranHint');
        const text = document.getElementById('ukuranHintText');
        if (val === 'Besar') {
            text.textContent = 'Ukuran besar: 25.5 × 30.5 cm — format standar Al-Qur\'an Braille';
            hint.style.display = 'block';
        } else if (val === 'Kecil') {
            text.textContent = 'Ukuran kecil: 1.5 × 25.5 cm — format buku panduan / iqroh';
            hint.style.display = 'block';
        } else {
            hint.style.display = 'none';
        }
    }

    // Inisialisasi hint jika ada nilai dari POST
    document.addEventListener('DOMContentLoaded', showUkuranHint);
</script>
</body>
</html>