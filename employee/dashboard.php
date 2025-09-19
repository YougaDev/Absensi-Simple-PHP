<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Check if already checked in today
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = CURDATE()");
$stmt->execute([$user_id]);
$today_attendance = $stmt->fetch();

// Handle check-in/check-out with improved response time
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Set response headers for faster processing
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    if ($action === 'check_in' && !$today_attendance) {
        $check_in_time = date('H:i:s');
        $status = (strtotime($check_in_time) > strtotime('08:00:00')) ? 'late' : 'present';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, check_in, status) VALUES (?, CURDATE(), ?, ?)");
            $stmt->execute([$user_id, $check_in_time, $status]);
            
            // Immediate redirect for faster response
            header('Location: dashboard.php?success=checkin&t=' . time());
            exit();
        } catch (Exception $e) {
            header('Location: dashboard.php?error=checkin');
            exit();
        }
    } elseif ($action === 'check_out' && $today_attendance && !$today_attendance['check_out']) {
        $check_out_time = date('H:i:s');
        
        try {
            $stmt = $pdo->prepare("UPDATE attendance SET check_out = ? WHERE user_id = ? AND date = CURDATE()");
            $stmt->execute([$check_out_time, $user_id]);
            
            // Immediate redirect for faster response
            header('Location: dashboard.php?success=checkout&t=' . time());
            exit();
        } catch (Exception $e) {
            header('Location: dashboard.php?error=checkout');
            exit();
        }
    }
}

// Get attendance history
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY date DESC LIMIT 10");
$stmt->execute([$user_id]);
$attendance_history = $stmt->fetchAll();

// Get leave requests
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$leave_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - Sistem Absensi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <div class="navbar-brand">
                    <h2><i class='bx bx-buildings'></i> Sistem Absensi</h2>
                </div>
                <div class="navbar-user">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <div class="user-role">Karyawan</div>
                    </div>
                    <a href="../logout.php" class="btn-logout"><i class='bx bx-log-out'></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container" style="padding-top: 2rem;">
        <div class="content-header">
            <h1><i class='bx bx-tachometer'></i> Dashboard Karyawan</h1>
            <div class="current-time" id="currentTime"></div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php if ($_GET['success'] === 'checkin'): ?>
                    🎉 Absen masuk berhasil dicatat! Selamat bekerja.
                <?php elseif ($_GET['success'] === 'checkout'): ?>
                    ✅ Absen pulang berhasil dicatat! Terima kasih atas kerja keras Anda.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i>
                <?php if ($_GET['error'] === 'checkin'): ?>
                    Gagal mencatat absen masuk. Silakan coba lagi.
                <?php elseif ($_GET['error'] === 'checkout'): ?>
                    Gagal mencatat absen pulang. Silakan coba lagi.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="attendance-card">
            <h2><i class='bx bx-calendar-check'></i> Absensi Hari Ini</h2>
            <div class="attendance-status">
                <?php if (!$today_attendance): ?>
                    <p class="status-text"><i class='bx bx-time'></i> Anda belum melakukan absen masuk hari ini</p>
                    <form method="POST" style="display: inline;" id="checkinForm">
                        <input type="hidden" name="action" value="check_in">
                        <button type="submit" class="btn btn-primary btn-large" id="checkinBtn">
                            <i class='bx bx-log-in'></i> Absen Masuk
                        </button>
                    </form>
                <?php elseif (!$today_attendance['check_out']): ?>
                    <div class="attendance-info">
                        <p><strong><i class='bx bx-time'></i> Jam Masuk:</strong> <?php echo date('H:i', strtotime($today_attendance['check_in'])); ?></p>
                        <p><strong><i class='bx bx-bar-chart-alt-2'></i> Status:</strong> 
                            <span class="status-badge status-<?php echo $today_attendance['status']; ?>">
                                <?php 
                                $status_icons = [
                                    'present' => '<i class="bx bx-check-circle"></i> Tepat Waktu',
                                    'late' => '<i class="bx bx-time"></i> Terlambat',
                                    'absent' => '<i class="bx bx-x-circle"></i> Tidak Hadir'
                                ];
                                echo $status_icons[$today_attendance['status']]; 
                                ?>
                            </span>
                        </p>
                    </div>
                    <form method="POST" style="display: inline;" id="checkoutForm">
                        <input type="hidden" name="action" value="check_out">
                        <button type="submit" class="btn btn-danger btn-large" id="checkoutBtn">
                            <i class='bx bx-log-out'></i> Absen Pulang
                        </button>
                    </form>
                <?php else: ?>
                    <div class="attendance-info">
                        <p><strong><i class='bx bx-time'></i> Jam Masuk:</strong> <?php echo date('H:i', strtotime($today_attendance['check_in'])); ?></p>
                        <p><strong><i class='bx bx-time'></i> Jam Pulang:</strong> <?php echo date('H:i', strtotime($today_attendance['check_out'])); ?></p>
                        <p><strong><i class='bx bx-bar-chart-alt-2'></i> Status:</strong> 
                            <span class="status-badge status-<?php echo $today_attendance['status']; ?>">
                                <?php 
                                $status_icons = [
                                    'present' => '<i class="bx bx-check-circle"></i> Tepat Waktu',
                                    'late' => '<i class="bx bx-time"></i> Terlambat',
                                    'absent' => '<i class="bx bx-x-circle"></i> Tidak Hadir'
                                ];
                                echo $status_icons[$today_attendance['status']]; 
                                ?>
                            </span>
                        </p>
                    </div>
                    <p class="completed-text"><i class='bx bx-check-double'></i> Absensi hari ini sudah lengkap! Terima kasih.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <a href="leave_request.php" class="card" style="text-decoration: none; color: inherit;">
                <div class="card-content text-center">
                    <div style="font-size: 3rem; margin-bottom: 1rem;"><i class='bx bx-edit-alt'></i></div>
                    <h3 style="margin-bottom: 0.5rem;">Pengajuan Cuti/Izin</h3>
                    <p style="color: var(--text-secondary);">Ajukan permohonan cuti atau izin</p>
                </div>
            </a>
            
            <a href="history.php" class="card" style="text-decoration: none; color: inherit;">
                <div class="card-content text-center">
                    <div style="font-size: 3rem; margin-bottom: 1rem;"><i class='bx bx-history'></i></div>
                    <h3 style="margin-bottom: 0.5rem;">Riwayat Lengkap</h3>
                    <p style="color: var(--text-secondary);">Lihat riwayat absensi lengkap</p>
                </div>
            </a>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div class="table-container">
                <div class="table-header">
                    <h2><i class='bx bx-list-ul'></i> Riwayat Absensi Terbaru</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance_history)): ?>
                        <tr>
                            <td colspan="4" class="text-center" style="padding: 3rem;">
                                📭 Belum ada riwayat absensi
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($attendance_history as $record): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($record['date'])); ?></td>
                                <td><?php echo $record['check_in'] ? '🕐 ' . date('H:i', strtotime($record['check_in'])) : '➖'; ?></td>
                                <td><?php echo $record['check_out'] ? '🕐 ' . date('H:i', strtotime($record['check_out'])) : '➖'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $record['status']; ?>">
                                        <?php 
                                        $status_icons = [
                                            'present' => '<i class="bx bx-check-circle"></i>',
                                            'late' => '<i class="bx bx-time"></i>',
                                            'absent' => '<i class="bx bx-x-circle"></i>'
                                        ];
                                        echo $status_icons[$record['status']] . ' ' . ucfirst($record['status']); 
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class='bx bx-edit-alt'></i> Pengajuan Terbaru</h2>
                </div>
                <div class="card-content">
                    <?php if (empty($leave_requests)): ?>
                        <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                            📭 Belum ada pengajuan
                        </p>
                    <?php else: ?>
                        <?php foreach ($leave_requests as $request): ?>
                        <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600; text-transform: uppercase; font-size: 0.8rem;">
                                    <?php echo $request['type'] === 'cuti' ? '🏖️ Cuti' : '📋 Izin'; ?>
                                </span>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php 
                                    $status_icons = [
                                        'pending' => '⏳ Menunggu',
                                        'approved' => '✅ Disetujui',
                                        'rejected' => '❌ Ditolak'
                                    ];
                                    echo $status_icons[$request['status']]; 
                                    ?>
                                </span>
                            </div>
                            <p style="font-size: 0.8rem; color: var(--text-secondary);">
                                📅 <?php echo date('d/m/Y', strtotime($request['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($request['end_date'])); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Real-time clock
        function updateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            
            const timeString = now.toLocaleDateString('id-ID', options);
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.innerHTML = '<i class="bx bx-time"></i> ' + timeString;
            }
        }
        
        setInterval(updateTime, 1000);
        updateTime();

    // Improved form handling with faster response
    document.addEventListener('DOMContentLoaded', function() {
        const checkinForm = document.getElementById('checkinForm');
        const checkoutForm = document.getElementById('checkoutForm');
        
        if (checkinForm) {
            checkinForm.addEventListener('submit', function(e) {
                const btn = document.getElementById('checkinBtn');
                btn.innerHTML = '<span class="loading"></span><i class="bx bx-loader-alt bx-spin"></i> Memproses...';
                btn.disabled = true;
                btn.classList.add('loading', 'processing');
            });
        }
        
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                const btn = document.getElementById('checkoutBtn');
                btn.innerHTML = '<span class="loading"></span><i class="bx bx-loader-alt bx-spin"></i> Memproses...';
                btn.disabled = true;
                btn.classList.add('loading', 'processing');
            });
        }
    });

    // Show success notification
    <?php if (isset($_GET['success'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const message = '<?php echo $_GET['success'] === 'checkin' ? 'Absen masuk berhasil dicatat!' : 'Absen pulang berhasil dicatat!'; ?>';
        showNotification(message, 'success');
    });
    <?php endif; ?>

    // Notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type} show`;
        notification.innerHTML = `<i class='bx ${type === 'success' ? 'bx-check-circle' : 'bx-error-circle'}'></i> ${message}`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
</script>
</body>
</html>
