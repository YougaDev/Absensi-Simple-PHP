<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: employee/dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        // Database connection
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: employee/dashboard.php');
                }
                exit();
            } else {
                $error = 'Username atau password salah';
            }
        } catch (PDOException $e) {
            $error = 'Koneksi database gagal: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Absensi Karyawan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><i class='bx bx-buildings'></i> Sistem Absensi</h1>
                <p>Kelola kehadiran karyawan dengan mudah dan efisien</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="credentials-info">
                <h4><i class='bx bx-key'></i> Akun Demo:</h4>
                <p><strong><i class='bx bx-user-circle'></i> Admin:</strong> admin / admin</p>
                <p><strong><i class='bx bx-user'></i> Karyawan 1:</strong> karyawan1 / 123456</p>
                <p><strong><i class='bx bx-user'></i> Karyawan 2:</strong> karyawan2 / 123456</p>
                <p><strong><i class='bx bx-user'></i> Karyawan 3:</strong> karyawan3 / 123456</p>
            </div>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username"><i class='bx bx-user'></i> Username</label>
                    <input type="text" id="username" name="username" required placeholder="Masukkan username">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class='bx bx-lock-alt'></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="Masukkan password">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class='bx bx-log-in'></i> Masuk ke Sistem
                </button>
            </form>
            
            <div class="login-footer">
                <p>© 2024 Sistem Absensi Karyawan - Dibuat dengan <i class='bx bx-heart' style='color: #e74c3c;'></i></p>
            </div>
        </div>
    </div>

    <script>
        // Auto focus on username field
        document.getElementById('username').focus();
        
        // Add loading state to login button
        document.querySelector('.login-form').addEventListener('submit', function() {
            const btn = document.querySelector('.btn-login');
            btn.innerHTML = '<span class="loading"></span> Memproses...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
