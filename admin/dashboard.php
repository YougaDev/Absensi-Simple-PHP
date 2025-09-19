<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireAdmin();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_employees FROM users WHERE role = 'employee'");
$total_employees = $stmt->fetch()['total_employees'];

$stmt = $pdo->query("SELECT COUNT(*) as today_attendance FROM attendance WHERE date = CURDATE()");
$today_attendance = $stmt->fetch()['today_attendance'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_requests FROM leave_requests WHERE status = 'pending'");
$pending_requests = $stmt->fetch()['pending_requests'];

$stmt = $pdo->query("SELECT COUNT(*) as total_attendance FROM attendance");
$total_attendance = $stmt->fetch()['total_attendance'];

// Get recent attendance
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name 
    FROM attendance a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.date DESC, a.check_in DESC 
    LIMIT 10
");
$stmt->execute();
$recent_attendance = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Absensi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="admin-container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class='bx bx-buildings'></i> Admin Panel</h2>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class='bx bx-tachometer'></i> Dashboard</a></li>
                <li><a href="attendance.php"><i class='bx bx-list-ul'></i> Data Absensi</a></li>
                <li><a href="employees.php"><i class='bx bx-group'></i> Data Karyawan</a></li>
                <li><a href="leave_requests.php"><i class='bx bx-edit-alt'></i> Pengajuan Cuti/Izin</a></li>
                <li><a href="../logout.php"><i class='bx bx-log-out'></i> Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <div class="content-header">
                <h1><i class='bx bx-tachometer'></i> Dashboard Admin</h1>
                <div class="current-time" id="currentTime"></div>
            </div>
            
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i> Selamat datang di Dashboard Admin! Sistem berjalan dengan baik.
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-group'></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_employees; ?></h3>
                        <p>Total Karyawan</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-check-circle'></i></div>
                    <div class="stat-info">
                        <h3><?php echo $today_attendance; ?></h3>
                        <p>Absen Hari Ini</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-time'></i></div>
                    <div class="stat-info">
                        <h3><?php echo $pending_requests; ?></h3>
                        <p>Pengajuan Menunggu</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-bar-chart-alt-2'></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_attendance; ?></h3>
                        <p>Total Absensi</p>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h2><i class='bx bx-time'></i> Aktivitas Absensi Terbaru</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Nama Karyawan</th>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_attendance)): ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 3rem;">
                                📭 Belum ada data absensi hari ini
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recent_attendance as $record): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['full_name']); ?></strong>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($record['date'])); ?></td>
                                <td>
                                    <?php echo $record['check_in'] ? '<i class="bx bx-time"></i> ' . date('H:i', strtotime($record['check_in'])) : '<i class="bx bx-minus"></i>'; ?>
                                </td>
                                <td>
                                    <?php echo $record['check_out'] ? '<i class="bx bx-time"></i> ' . date('H:i', strtotime($record['check_out'])) : '<i class="bx bx-minus"></i>'; ?>
                                </td>
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
        </main>
    </div>
    
    <script>
        // Real-time clock with enhanced formatting
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
        
        // Update time every second
        setInterval(updateTime, 1000);
        updateTime();

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'slideUp 0.6s ease-out forwards';
            });
        });
    </script>
</body>
</html>
