<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Handle filters
$month_filter = $_GET['month'] ?? '';
$year_filter = $_GET['year'] ?? date('Y');

// Build query
$where_conditions = ["user_id = ?"];
$params = [$user_id];

if ($month_filter) {
    $where_conditions[] = "MONTH(date) = ?";
    $params[] = $month_filter;
}

if ($year_filter) {
    $where_conditions[] = "YEAR(date) = ?";
    $params[] = $year_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get attendance records
$stmt = $pdo->prepare("
    SELECT * FROM attendance 
    $where_clause
    ORDER BY date DESC
");
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendance 
    $where_clause
");
$stmt->execute($params);
$stats = $stmt->fetch();

// Get leave requests
$stmt = $pdo->prepare("
    SELECT * FROM leave_requests 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$leave_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Absensi - Sistem Absensi</title>
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
                    <a href="../logout.php" class="btn-logout">🚪 Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container" style="padding-top: 2rem;">
        <div class="content-header">
            <h1><i class='bx bx-history'></i> Riwayat Absensi</h1>
            <a href="dashboard.php" class="btn btn-secondary"><i class='bx bx-home'></i> Kembali ke Dashboard</a>
        </div>
        
        <!-- Filter Section -->
        <div class="card mb-6">
            <div class="card-header">
                <h2><i class='bx bx-search-alt'></i> Filter Data</h2>
            </div>
            <div class="card-content">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div>
                        <label for="month" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-calendar'></i> Bulan:</label>
                        <select id="month" name="month" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                            <option value="">Semua Bulan</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $month_filter == $i ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="year" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-calendar-alt'></i> Tahun:</label>
                        <select id="year" name="year" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $year_filter == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary"><i class='bx bx-search-alt'></i> Filter</button>
                        <a href="history.php" class="btn btn-secondary"><i class='bx bx-refresh'></i> Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid mb-6">
            <div class="stat-card">
                <div class="stat-icon"><i class='bx bx-bar-chart-alt-2'></i></div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_days']; ?></h3>
                    <p>Total Hari Kerja</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class='bx bx-check-circle'></i></div>
                <div class="stat-info">
                    <h3><?php echo $stats['present_days']; ?></h3>
                    <p>Hadir Tepat Waktu</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class='bx bx-time'></i></div>
                <div class="stat-info">
                    <h3><?php echo $stats['late_days']; ?></h3>
                    <p>Terlambat</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class='bx bx-x-circle'></i></div>
                <div class="stat-info">
                    <h3><?php echo $stats['absent_days']; ?></h3>
                    <p>Tidak Hadir</p>
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Attendance History -->
            <div class="table-container">
                <div class="table-header">
                    <h2>📋 Riwayat Absensi Lengkap</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                            <th>Durasi Kerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance_records)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 3rem;">
                                📭 Tidak ada data absensi untuk periode yang dipilih
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($record['date'])); ?></td>
                                <td><?php echo date('l', strtotime($record['date'])); ?></td>
                                <td>
                                    <?php echo $record['check_in'] ? '🕐 ' . date('H:i', strtotime($record['check_in'])) : '➖'; ?>
                                </td>
                                <td>
                                    <?php echo $record['check_out'] ? '🕐 ' . date('H:i', strtotime($record['check_out'])) : '➖'; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $record['status']; ?>">
                                        <?php 
                                        $status_icons = [
                                            'present' => '✅ Hadir',
                                            'late' => '⏰ Terlambat',
                                            'absent' => '❌ Tidak Hadir'
                                        ];
                                        echo $status_icons[$record['status']]; 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($record['check_in'] && $record['check_out']) {
                                        $start = new DateTime($record['check_in']);
                                        $end = new DateTime($record['check_out']);
                                        $duration = $start->diff($end);
                                        echo '⏱️ ' . $duration->format('%h jam %i menit');
                                    } else {
                                        echo '➖';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Leave Requests -->
            <div class="card">
                <div class="card-header">
                    <h2>📝 Riwayat Pengajuan</h2>
                </div>
                <div class="card-content">
                    <?php if (empty($leave_requests)): ?>
                        <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                            📭 Belum ada pengajuan cuti/izin
                        </p>
                    <?php else: ?>
                        <?php foreach ($leave_requests as $request): ?>
                        <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <span style="font-weight: 600; text-transform: uppercase; font-size: 0.9rem;">
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
                            <p style="font-size: 0.85rem; margin-bottom: 0.5rem;">
                                <strong>📅 Periode:</strong> <?php echo date('d/m/Y', strtotime($request['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($request['end_date'])); ?>
                            </p>
                            <p style="font-size: 0.85rem; margin-bottom: 0.5rem;">
                                <strong>📝 Alasan:</strong> <?php echo htmlspecialchars($request['reason']); ?>
                            </p>
                            <p style="font-size: 0.8rem; color: var(--text-secondary);">
                                📅 Diajukan: <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                            </p>
                            <?php if ($request['admin_notes']): ?>
                                <p style="font-size: 0.85rem; margin-top: 0.5rem; padding: 0.5rem; background: #f8fafc; border-radius: 4px;">
                                    <strong>💬 Catatan Admin:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'slideUp 0.6s ease-out forwards';
            });
        });
    </script>
</body>
</html>
