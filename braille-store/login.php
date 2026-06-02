<?php
session_start();
require_once 'includes/config.php';

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

// Proses Login
if (isset($_POST['login'])) {
    $login_input = mysqli_real_escape_string($conn, $_POST['login_input']); // Bisa username atau email
    $password = $_POST['password'];
    
    // Cari user berdasarkan username ATAU email
    $query = "SELECT * FROM users WHERE username = '$login_input' OR email = '$login_input'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar'] = $user['avatar'];
            
            // Redirect berdasarkan role
            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username/Email tidak ditemukan!";
    }
}

// Proses Registrasi User Baru
if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Validasi
    $errors = [];
    
    // Cek username sudah ada?
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $errors[] = "Username sudah digunakan!";
    }
    
    // Cek email sudah ada?
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($cek) > 0) {
        $errors[] = "Email sudah terdaftar!";
    }
    
    // Cek password match
    if ($password !== $password_confirm) {
        $errors[] = "Konfirmasi password tidak cocok!";
    }
    
    // Cek panjang password
    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, nama_lengkap, role) 
                  VALUES ('$username', '$email', '$password_hash', '$nama_lengkap', 'user')";
        
        if (mysqli_query($conn, $query)) {
            $success = "Registrasi berhasil! Silakan login.";
        } else {
            $error = "Gagal registrasi: " . mysqli_error($conn);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - Braille Book Catalog</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ========== WARNA HIJAU TOSCA ========== */
        :root {
            --primary-main: #1cceac;      /* Hijau Tosca */
            --primary-dark: #15a38a;       /* Hijau Tosca Gelap */
            --accent-gold: #FFD700;        /* Kuning Emas */
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .card {
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border: none;
        }
        
        /* Logo / Icon */
        .logo-icon {
            color: var(--primary-main);
        }
        
        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid #e0e0e0;
        }
        
        .nav-tabs .nav-link {
            font-weight: bold;
            color: #666;
            border: none;
            padding: 12px 20px;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-main);
            border: none;
        }
        
        .nav-tabs .nav-link.active {
            background-color: var(--primary-main);
            color: white;
            border: none;
            border-radius: 8px 8px 0 0;
        }
        
        /* Input Groups */
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
        }
        
        .form-control:focus {
            border-color: var(--primary-main);
            box-shadow: 0 0 0 0.2rem rgba(28, 206, 172, 0.25);
        }
        
        /* Buttons */
        .btn-primary-custom {
            background-color: var(--primary-main);
            color: white;
            font-weight: 600;
            padding: 12px;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            background-color: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 206, 172, 0.4);
        }
        
        .btn-google {
            background-color: #fff;
            color: #333;
            border: 1px solid #ddd;
            font-weight: 500;
            padding: 12px;
            transition: all 0.3s;
        }
        
        .btn-google:hover {
            background-color: #f8f9fa;
            border-color: #ccc;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .btn-google i {
            color: #4285F4;
        }
        
        /* Links */
        .link-home {
            color: var(--primary-dark);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .link-home:hover {
            color: var(--primary-main);
            text-decoration: underline;
        }
        
        /* Alert */
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card">
                    <div class="card-body p-4">
                        
                        <!-- Logo & Title -->
                        <div class="text-center mb-4">
                            <i class="fas fa-book-open fa-3x logo-icon"></i>
                            <h3 class="mt-2" style="color: var(--primary-dark);">Braille Book Catalog</h3>
                            <p class="text-muted">Yayasan Raudlatul Makfufin</p>
                        </div>
                        
                        <!-- Tabs Login & Register -->
                        <ul class="nav nav-tabs nav-fill mb-4" id="authTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">
                                    <i class="fas fa-sign-in-alt me-1"></i> Login
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button">
                                    <i class="fas fa-user-plus me-1"></i> Daftar
                                </button>
                            </li>
                        </ul>
                        
                        <!-- Notifikasi Error / Success -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Tab Content -->
                        <div class="tab-content" id="authTabContent">
                            
                            <!-- ========== FORM LOGIN ========== -->
                            <div class="tab-pane fade show active" id="login">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Username atau Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" name="login_input" class="form-control" 
                                                   placeholder="Masukkan username atau email" required autofocus>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" name="password" class="form-control" 
                                                   placeholder="Masukkan password" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="login" class="btn btn-primary-custom w-100">
                                        <i class="fas fa-sign-in-alt me-2"></i> Masuk
                                    </button>
                                </form>
                                
                                <!-- Lupa Password -->
                                 <div class="text-center mt-3">
                                  <a href="forgot-password.php" class="link-home" style="font-size:.9rem;">
                                    <i class="fas fa-key me-1"></i> Lupa Password?
                                  </a>
                                </div>
                            </div>
                            
                            <!-- ========== FORM REGISTER ========== -->
                            <div class="tab-pane fade" id="register">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" class="form-control" 
                                               placeholder="Nama lengkap Anda" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Username</label>
                                        <input type="text" name="username" class="form-control" 
                                               placeholder="Username unik" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="email" name="email" class="form-control" 
                                               placeholder="email@contoh.com" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Password</label>
                                        <input type="password" name="password" class="form-control" 
                                               placeholder="Minimal 6 karakter" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Konfirmasi Password</label>
                                        <input type="password" name="password_confirm" class="form-control" 
                                               placeholder="Ulangi password" required>
                                    </div>
                                    
                                    <button type="submit" name="register" class="btn btn-primary-custom w-100">
                                        <i class="fas fa-user-plus me-2"></i> Daftar Sekarang
                                    </button>
                                </form>
                                
                                <p class="text-muted small text-center mt-3">
                                    Dengan mendaftar, Anda menyetujui Syarat & Ketentuan kami.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Kembali ke Beranda -->
                        <div class="text-center mt-4">
                            <a href="index.php" class="link-home">
                                <i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda
                            </a>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>