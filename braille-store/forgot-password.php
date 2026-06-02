<?php
// ============================================================
// forgot-password.php
// Sistem reset password via email token (aman, expire 1 jam)
// ============================================================
session_start();
require_once 'includes/config.php';

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: user/dashboard.php");
    exit;
}

$step    = isset($_GET['step']) ? $_GET['step'] : 'request'; // request | reset
$token   = isset($_GET['token']) ? trim($_GET['token']) : '';
$message = '';
$error   = '';
$success = false;

// ============================================================
// STEP 1 — Proses permintaan reset (kirim link ke email)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_reset'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    // Cek apakah email terdaftar
    $q    = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE email = '$email'");
    $user = mysqli_fetch_assoc($q);

    if (!$user) {
        $error = "Email tidak ditemukan dalam sistem.";
    } else {
        // Hapus token lama yang belum expired untuk email ini
        mysqli_query($conn, "DELETE FROM password_resets WHERE email = '$email'");

        // Generate token aman
        $token_raw = bin2hex(random_bytes(32)); // 64 karakter hex
        $expires   = date('Y-m-d H:i:s', strtotime('+1 hour'));

        mysqli_query($conn,
            "INSERT INTO password_resets (email, token, expires_at)
             VALUES ('$email', '$token_raw', '$expires')"
        );

        // Buat link reset
        $reset_link = "http://localhost/braille-store/forgot-password.php?step=reset&token=$token_raw";
        $nama       = $user['nama_lengkap'];

        // Kirim email menggunakan mail() bawaan PHP
        $subject = "Reset Password - Braille Book Catalog";
        $body    = "Halo $nama,\n\n"
                 . "Anda menerima email ini karena ada permintaan reset password untuk akun Anda.\n\n"
                 . "Klik link berikut untuk mengatur password baru:\n"
                 . "$reset_link\n\n"
                 . "Link ini berlaku selama 1 jam.\n\n"
                 . "Jika Anda tidak meminta reset password, abaikan email ini.\n\n"
                 . "Salam,\n"
                 . "Tim Braille Book Catalog\n"
                 . "Yayasan Raudlatul Makfufin";

        $headers  = "From: noreply@raudlatulmakfufin.org\r\n";
        $headers .= "Reply-To: noreply@raudlatulmakfufin.org\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $sent = mail($email, $subject, $body, $headers);

        if ($sent) {
            $success = true;
            $message = "Link reset password telah dikirim ke <strong>$email</strong>. Silakan cek inbox (dan folder spam) Anda. Link berlaku 1 jam.";
        } else {
            // Fallback jika mail() gagal (development/XAMPP) — tampilkan link langsung
            // HAPUS BLOK INI DI PRODUCTION!
            $success = true;
            $message = "Email tidak dapat dikirim (konfigurasi SMTP belum aktif). "
                     . "Untuk development, gunakan link ini langsung: "
                     . "<a href='$reset_link' class='text-decoration-underline fw-bold'>Klik di sini untuk reset password</a>";
        }
    }
}

// ============================================================
// STEP 2 — Tampilkan form password baru (setelah klik link)
// ============================================================
$token_valid = false;
$user_email  = '';

if ($step === 'reset' && $token !== '') {
    $token_esc  = mysqli_real_escape_string($conn, $token);
    $now        = date('Y-m-d H:i:s');
    $q_token    = mysqli_query($conn,
        "SELECT * FROM password_resets
         WHERE token = '$token_esc'
           AND used = 0
           AND expires_at > '$now'
         LIMIT 1"
    );
    $reset_row  = mysqli_fetch_assoc($q_token);

    if ($reset_row) {
        $token_valid = true;
        $user_email  = $reset_row['email'];
    } else {
        $error = "Link reset tidak valid atau sudah kedaluwarsa. Silakan minta link baru.";
    }
}

// ============================================================
// STEP 2 — Proses simpan password baru
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_password'])) {
    $token_esc    = mysqli_real_escape_string($conn, trim($_POST['token']));
    $pass_baru    = $_POST['password_baru'];
    $pass_konfirm = $_POST['password_konfirmasi'];
    $now          = date('Y-m-d H:i:s');

    $q_t = mysqli_query($conn,
        "SELECT * FROM password_resets
         WHERE token = '$token_esc' AND used = 0 AND expires_at > '$now'
         LIMIT 1"
    );
    $row_t = mysqli_fetch_assoc($q_t);

    if (!$row_t) {
        $error = "Token tidak valid atau sudah kedaluwarsa.";
    } elseif (strlen($pass_baru) < 6) {
        $error        = "Password minimal 6 karakter.";
        $token_valid  = true;
        $user_email   = $row_t['email'];
    } elseif ($pass_baru !== $pass_konfirm) {
        $error        = "Konfirmasi password tidak cocok.";
        $token_valid  = true;
        $user_email   = $row_t['email'];
    } else {
        $hash      = password_hash($pass_baru, PASSWORD_DEFAULT);
        $email_esc = mysqli_real_escape_string($conn, $row_t['email']);

        // Update password
        mysqli_query($conn, "UPDATE users SET password = '$hash' WHERE email = '$email_esc'");

        // Tandai token sebagai sudah dipakai
        mysqli_query($conn, "UPDATE password_resets SET used = 1 WHERE token = '$token_esc'");

        $success = true;
        $message = "Password berhasil diubah! Silakan <a href='login.php' class='fw-bold text-decoration-underline'>login dengan password baru</a>.";
        $step    = 'done';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Braille Book Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-main:#1cceac; --primary-dark:#15a38a; --accent-gold:#FFD700; }
        body {
            background:linear-gradient(135deg,var(--primary-main) 0%,var(--primary-dark) 100%);
            min-height:100vh; display:flex; align-items:center; padding:20px;
        }
        .card { border-radius:20px; box-shadow:0 15px 35px rgba(0,0,0,.2); border:none; }
        .logo-icon { color:var(--primary-main); }
        .form-control { border-left:none; }
        .form-control:focus { border-color:var(--primary-main); box-shadow:0 0 0 .2rem rgba(28,206,172,.25); }
        .input-group-text { background:#f8f9fa; border-right:none; }
        .btn-primary-custom { background:var(--primary-main); color:white; font-weight:600; padding:12px; border:none; transition:all .3s; width:100%; }
        .btn-primary-custom:hover { background:var(--primary-dark); color:white; transform:translateY(-2px); box-shadow:0 5px 15px rgba(28,206,172,.4); }
        .link-back { color:var(--primary-dark); font-weight:500; text-decoration:none; transition:all .3s; }
        .link-back:hover { color:var(--primary-main); text-decoration:underline; }
        .step-indicator { display:flex; justify-content:center; gap:8px; margin-bottom:24px; }
        .step-dot { width:12px; height:12px; border-radius:50%; background:#e0e0e0; }
        .step-dot.active { background:var(--primary-main); }
        .step-dot.done { background:var(--primary-dark); }
        /* Password strength */
        .strength-bar { height:5px; border-radius:10px; background:#eee; margin-top:6px; overflow:hidden; }
        .strength-fill { height:100%; border-radius:10px; width:0%; transition:all .4s; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card">
                <div class="card-body p-4">

                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <i class="fas fa-book-open fa-3x logo-icon"></i>
                        <h3 class="mt-2" style="color:var(--primary-dark)">Braille Book Catalog</h3>
                        <p class="text-muted">Yayasan Raudlatul Makfufin</p>
                    </div>

                    <!-- Step indicator -->
                    <div class="step-indicator">
                        <div class="step-dot <?= ($step=='request'||$step=='done') ? 'active' : 'done' ?>"></div>
                        <div class="step-dot <?= $step=='reset' ? 'active' : ($step=='done' ? 'done' : '') ?>"></div>
                        <div class="step-dot <?= $step=='done' ? 'active' : '' ?>"></div>
                    </div>

                    <!-- Alert -->
                    <?php if ($error): ?>
                    <div class="alert alert-danger border-0" style="border-radius:10px;">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success border-0" style="border-radius:10px;">
                        <i class="fas fa-check-circle me-2"></i><?= $message ?>
                    </div>
                    <?php endif; ?>

                    <!-- ===== STEP 1: Form Email ===== -->
                    <?php if ($step === 'request' && !$success): ?>
                    <h5 class="fw-bold mb-1" style="color:var(--primary-dark)">
                        <i class="fas fa-key me-2"></i>Lupa Password
                    </h5>
                    <p class="text-muted small mb-4">
                        Masukkan email yang terdaftar. Kami akan mengirimkan link untuk membuat password baru.
                    </p>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Email Terdaftar</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control"
                                       placeholder="email@contoh.com"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       required autofocus>
                            </div>
                        </div>
                        <button type="submit" name="kirim_reset" class="btn btn-primary-custom mb-3">
                            <i class="fas fa-paper-plane me-2"></i> Kirim Link Reset
                        </button>
                    </form>

                    <!-- ===== STEP 2: Form Password Baru ===== -->
                    <?php elseif ($step === 'reset' && $token_valid): ?>
                    <h5 class="fw-bold mb-1" style="color:var(--primary-dark)">
                        <i class="fas fa-lock-open me-2"></i>Buat Password Baru
                    </h5>
                    <p class="text-muted small mb-4">
                        Untuk akun: <strong><?= htmlspecialchars($user_email) ?></strong>
                    </p>
                    <form method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password_baru" id="passBaru"
                                       class="form-control" placeholder="Minimal 6 karakter"
                                       oninput="cekKekuatan(this.value)" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePass('passBaru',this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="strength-bar mt-1">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="form-text" id="strengthText"></div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Konfirmasi Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password_konfirmasi" id="passKonfirmasi"
                                       class="form-control" placeholder="Ulangi password baru"
                                       oninput="cekKonfirmasi()" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePass('passKonfirmasi',this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text" id="konfirmasiText"></div>
                        </div>
                        <button type="submit" name="simpan_password" class="btn btn-primary-custom mb-3">
                            <i class="fas fa-save me-2"></i> Simpan Password Baru
                        </button>
                    </form>

                    <?php elseif ($step === 'reset' && !$token_valid && !$error): ?>
                    <div class="alert alert-warning border-0" style="border-radius:10px;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Link tidak valid. Silakan minta link reset baru.
                    </div>
                    <?php endif; ?>

                    <!-- Kembali ke Login -->
                    <div class="text-center mt-3">
                        <a href="login.php" class="link-back">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Login
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') { input.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
    else { input.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}
function cekKekuatan(val) {
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    if (!fill) return;
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const cfg = [
        {w:'0%',c:'#eee',l:''},
        {w:'25%',c:'#e74c3c',l:'Lemah'},
        {w:'50%',c:'#ffc107',l:'Cukup'},
        {w:'75%',c:'#1cceac',l:'Kuat'},
        {w:'100%',c:'#15a38a',l:'Sangat Kuat'},
    ];
    const c = cfg[Math.min(score,4)];
    fill.style.width = val.length ? c.w : '0%';
    fill.style.background = c.c;
    text.textContent = val.length ? c.l : '';
    text.style.color = c.c;
}
function cekKonfirmasi() {
    const baru = document.getElementById('passBaru')?.value;
    const konf = document.getElementById('passKonfirmasi')?.value;
    const text = document.getElementById('konfirmasiText');
    if (!text || !konf) return;
    if (baru === konf) { text.textContent = '✓ Password cocok'; text.style.color = '#2e7d32'; }
    else { text.textContent = '✗ Belum cocok'; text.style.color = '#e74c3c'; }
}
</script>
</body>
</html>