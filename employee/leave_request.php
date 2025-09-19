<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $type = $_POST['type'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
    if (empty($start_date) || empty($end_date) || empty($type) || empty($reason)) {
        $error = 'Semua field harus diisi';
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $error = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai';
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = 'Tanggal mulai tidak boleh kurang dari hari ini';
    } else {
        $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, start_date, end_date, type, reason) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$user_id, $start_date, $end_date, $type, $reason])) {
            $success = 'Pengajuan berhasil dikirim dan menunggu persetujuan admin';
        } else {
            $error = 'Terjadi kesalahan saat mengirim pengajuan';
        }
    }
}

// Get user's leave requests
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$leave_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cuti/Izin - Sistem Absensi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <div class="navbar-brand">
                    <h2>🏢 Sistem Absensi</h2>
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
            <h1><i class='bx bx-edit-alt'></i> Pengajuan Cuti/Izin</h1>
            <a href="dashboard.php" class="btn btn-secondary"><i class='bx bx-home'></i> Kembali ke Dashboard</a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                🎉 <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Form Pengajuan -->
            <div class="card">
                <div class="card-header">
                    <h2>📋 Form Pengajuan Baru</h2>
                </div>
                <div class="card-content">
                    <form method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div>
                                <label for="start_date" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-calendar'></i> Tanggal Mulai</label>
                                <input type="date" id="start_date" name="start_date" required min="<?php echo date('Y-m-d'); ?>" 
                                       style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                            </div>
                            
                            <div>
                                <label for="end_date" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-calendar'></i> Tanggal Selesai</label>
                                <input type="date" id="end_date" name="end_date" required min="<?php echo date('Y-m-d'); ?>"
                                       style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label for="type" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-list-ul'></i> Jenis Pengajuan</label>
                            <select id="type" name="type" required 
                                    style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                                <option value="">Pilih jenis pengajuan</option>
                                <option value="cuti"><i class='bx bx-calendar-minus'></i> Cuti</option>
                                <option value="izin"><i class='bx bx-edit-alt'></i> Izin</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label for="reason" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-edit-alt'></i> Alasan</label>
                            <textarea id="reason" name="reason" rows="4" required placeholder="Jelaskan alasan pengajuan cuti/izin..."
                                      style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius); resize: vertical;"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                            <i class='bx bx-send'></i> Kirim Pengajuan
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Riwayat Pengajuan -->
            <div class="card">
                <div class="card-header">
                    <h2>📊 Riwayat Pengajuan</h2>
                </div>
                <div class="card-content">
                    <?php if (empty($leave_requests)): ?>
                        <p class="text-center" style="color: var(--text-secondary); padding: 3rem;">
                            📭 Belum ada pengajuan cuti/izin
                        </p>
                    <?php else: ?>
                        <div style="max-height: 500px; overflow-y: auto;">
                            <?php foreach ($leave_requests as $request): ?>
                            <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1.25rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <div>
                                        <span style="font-weight: 600; text-transform: uppercase; font-size: 0.9rem;">
                                            <?php echo $request['type'] === 'cuti' ? '🏖️ Cuti' : '📋 Izin'; ?>
                                        </span>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                            📅 <?php echo date('d/m/Y', strtotime($request['created_at'])); ?>
                                        </div>
                                    </div>
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
                                
                                <div style="margin-bottom: 0.75rem;">
                                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                        <strong>📅 Periode:</strong> <?php echo date('d/m/Y', strtotime($request['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($request['end_date'])); ?>
                                    </p>
                                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                        <strong>📝 Alasan:</strong> <?php echo htmlspecialchars($request['reason']); ?>
                                    </p>
                                </div>
                                
                                <?php if ($request['admin_notes']): ?>
                                    <div style="background: #f8fafc; padding: 0.75rem; border-radius: 6px; border-left: 3px solid #667eea;">
                                        <p style="font-size: 0.85rem; margin: 0;">
                                            <strong>💬 Catatan Admin:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            startDateInput.addEventListener('change', function() {
                endDateInput.min = this.value;
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
            });
            
            // Add loading state to form
            document.querySelector('form').addEventListener('submit', function() {
                const btn = document.querySelector('button[type="submit"]');
                btn.innerHTML = '<span class="loading"></span> Mengirim...';
                btn.disabled = true;
            });
        });
    </script>
</body>
</html>
