<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireAdmin();

// Handle filters
$date_filter = $_GET['date'] ?? '';
$month_filter = $_GET['month'] ?? '';
$name_filter = $_GET['name'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($date_filter) {
    $where_conditions[] = "a.date = ?";
    $params[] = $date_filter;
}

if ($month_filter) {
    $where_conditions[] = "DATE_FORMAT(a.date, '%Y-%m') = ?";
    $params[] = $month_filter;
}

if ($name_filter) {
    $where_conditions[] = "u.full_name LIKE ?";
    $params[] = "%$name_filter%";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name 
    FROM attendance a 
    JOIN users u ON a.user_id = u.id 
    $where_clause
    ORDER BY a.date DESC, a.check_in DESC
");
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="laporan_absensi_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "<table border='1'>";
    echo "<tr><th>Nama</th><th>Tanggal</th><th>Masuk</th><th>Pulang</th><th>Status</th><th>Durasi</th></tr>";
    
    foreach ($attendance_records as $record) {
        $duration = '';
        if ($record['check_in'] && $record['check_out']) {
            $start = new DateTime($record['check_in']);
            $end = new DateTime($record['check_out']);
            $diff = $start->diff($end);
            $duration = $diff->format('%h jam %i menit');
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($record['full_name']) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($record['date'])) . "</td>";
        echo "<td>" . ($record['check_in'] ? date('H:i', strtotime($record['check_in'])) : '-') . "</td>";
        echo "<td>" . ($record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '-') . "</td>";
        echo "<td>" . ucfirst($record['status']) . "</td>";
        echo "<td>" . $duration . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Absensi - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="admin-container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>🏢 Admin Panel</h2>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class='bx bx-tachometer'></i> Dashboard</a></li>
                <li><a href="attendance.php" class="active"><i class='bx bx-list-ul'></i> Data Absensi</a></li>
                <li><a href="employees.php"><i class='bx bx-group'></i> Data Karyawan</a></li>
                <li><a href="leave_requests.php"><i class='bx bx-edit-alt'></i> Pengajuan Cuti/Izin</a></li>
                <li><a href="../logout.php"><i class='bx bx-log-out'></i> Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <div class="content-header">
                <h1><i class='bx bx-list-ul'></i> Data Absensi</h1>
                <div class="current-time" id="currentTime"></div>
            </div>
            
            <!-- Filter Section -->
            <div class="card mb-6">
                <div class="card-header">
                    <h2><i class='bx bx-search-alt'></i> Filter & Export Data</h2>
                </div>
                <div class="card-content">
                    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                        <div>
                            <label for="date" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">📅 Tanggal:</label>
                            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>"
                                   style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                        </div>
                        
                        <div>
                            <label for="month" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">📆 Bulan:</label>
                            <input type="month" id="month" name="month" value="<?php echo htmlspecialchars($month_filter); ?>"
                                   style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                        </div>
                        
                        <div>
                            <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">👤 Nama:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name_filter); ?>" placeholder="Cari nama karyawan..."
                                   style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="submit" class="btn btn-primary"><i class='bx bx-search-alt'></i> Filter</button>
                            <a href="attendance.php" class="btn btn-secondary"><i class='bx bx-refresh'></i> Reset</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-success"><i class='bx bx-download'></i> Export Excel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Data Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>📊 Data Absensi Karyawan</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Nama Karyawan</th>
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
                            <td colspan="7" class="text-center" style="padding: 3rem;">
                                📭 Tidak ada data absensi yang ditemukan
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($record['full_name']); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($record['date'])); ?></td>
                                <td><?php echo date('l', strtotime($record['date'])); ?></td>
                                <td><?php echo $record['check_in'] ? '🕐 ' . date('H:i', strtotime($record['check_in'])) : '➖'; ?></td>
                                <td><?php echo $record['check_out'] ? '🕐 ' . date('H:i', strtotime($record['check_out'])) : '➖'; ?></td>
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
        </main>
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
                timeElement.innerHTML = '🕐 ' + timeString;
            }
        }
        
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>
