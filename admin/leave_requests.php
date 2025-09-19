<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireAdmin();

$success = '';
$error = '';

// Handle leave request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = $_POST['request_id'] ?? '';
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if ($action === 'approve' && $request_id) {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'approved', admin_notes = ? WHERE id = ?");
        if ($stmt->execute([$admin_notes, $request_id])) {
            $success = 'Pengajuan berhasil disetujui';
        } else {
            $error = 'Terjadi kesalahan saat menyetujui pengajuan';
        }
    } elseif ($action === 'reject' && $request_id) {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'rejected', admin_notes = ? WHERE id = ?");
        if ($stmt->execute([$admin_notes, $request_id])) {
            $success = 'Pengajuan berhasil ditolak';
        } else {
            $error = 'Terjadi kesalahan saat menolak pengajuan';
        }
    }
}

// Get all leave requests
$stmt = $pdo->prepare("
    SELECT lr.*, u.full_name 
    FROM leave_requests lr 
    JOIN users u ON lr.user_id = u.id 
    ORDER BY lr.created_at DESC
");
$stmt->execute();
$leave_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cuti/Izin - Admin</title>
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
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="attendance.php">📋 Data Absensi</a></li>
                <li><a href="employees.php">👥 Data Karyawan</a></li>
                <li><a href="leave_requests.php" class="active">📝 Pengajuan Cuti/Izin</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <div class="content-header">
                <h1><i class='bx bx-edit-alt'></i> Pengajuan Cuti/Izin</h1>
                <div class="current-time" id="currentTime"></div>
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
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.5rem;">
                <?php if (empty($leave_requests)): ?>
                    <div style="grid-column: 1 / -1;">
                        <div class="card">
                            <div class="card-content text-center" style="padding: 4rem;">
                                <div style="font-size: 4rem; margin-bottom: 1rem;">📭</div>
                                <h3>Tidak ada pengajuan cuti/izin</h3>
                                <p style="color: var(--text-secondary);">Belum ada karyawan yang mengajukan cuti atau izin</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($leave_requests as $request): ?>
                    <div class="card">
                        <div class="card-content">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin-bottom: 0.25rem; font-size: 1.1rem;">
                                        <i class='bx bx-user'></i> <?php echo htmlspecialchars($request['full_name']); ?>
                                    </h3>
                                    <span style="background: #f0f4ff; color: #667eea; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">
                                        <?php echo $request['type'] === 'cuti' ? '<i class="bx bx-calendar-minus"></i> Cuti' : '<i class="bx bx-edit-alt"></i> Izin'; ?>
                                    </span>
                                </div>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php 
                                    $status_icons = [
                                        'pending' => '<i class="bx bx-time"></i> Menunggu',
                                        'approved' => '<i class="bx bx-check-circle"></i> Disetujui',
                                        'rejected' => '<i class="bx bx-x-circle"></i> Ditolak'
                                    ];
                                    echo $status_icons[$request['status']]; 
                                    ?>
                                </span>
                            </div>
                            
                            <div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: var(--border-radius); border-left: 3px solid #667eea;">
                                <p style="margin-bottom: 0.5rem; font-size: 0.9rem;">
                                    <strong>📅 Periode:</strong> <?php echo date('d/m/Y', strtotime($request['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($request['end_date'])); ?>
                                </p>
                                
                                <p style="margin-bottom: 0.5rem; font-size: 0.9rem;">
                                    <strong>📝 Alasan:</strong> <?php echo htmlspecialchars($request['reason']); ?>
                                </p>
                                
                                <p style="font-size: 0.8rem; color: var(--text-secondary);">
                                    <strong>📅 Diajukan:</strong> <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                                </p>
                                
                                <?php if ($request['admin_notes']): ?>
                                <div style="margin-top: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <p style="font-size: 0.85rem; margin: 0;">
                                        <strong>💬 Catatan Admin:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($request['status'] === 'pending'): ?>
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="openActionModal(<?php echo $request['id']; ?>, 'approve')" class="btn btn-success btn-sm" style="flex: 1;">
                                    <i class='bx bx-check'></i> Setujui
                                </button>
                                <button onclick="openActionModal(<?php echo $request['id']; ?>, 'reject')" class="btn btn-danger btn-sm" style="flex: 1;">
                                    <i class='bx bx-x'></i> Tolak
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Action Modal -->
    <div id="actionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Konfirmasi Aksi</h2>
                <span class="close" onclick="closeModal('actionModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" id="modal_action" name="action">
                <input type="hidden" id="modal_request_id" name="request_id">
                
                <div style="padding: 1.5rem;">
                    <div style="margin-bottom: 1.5rem;">
                        <label for="admin_notes" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">💬 Catatan Admin (opsional)</label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" placeholder="Tambahkan catatan jika diperlukan..."
                                  style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius); resize: vertical;"></textarea>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" onclick="closeModal('actionModal')" class="btn btn-secondary">❌ Batal</button>
                        <button type="submit" id="confirmButton" class="btn">✅ Konfirmasi</button>
                    </div>
                </div>
            </form>
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
                timeElement.innerHTML = '🕐 ' + timeString;
            }
        }
        
        setInterval(updateTime, 1000);
        updateTime();

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function openActionModal(requestId, action) {
            document.getElementById('modal_request_id').value = requestId;
            document.getElementById('modal_action').value = action;
            
            const modalTitle = document.getElementById('modalTitle');
            const confirmButton = document.getElementById('confirmButton');
            
            if (action === 'approve') {
                modalTitle.innerHTML = '<i class="bx bx-check"></i> Setujui Pengajuan';
                confirmButton.innerHTML = '<i class="bx bx-check"></i> Setujui';
                confirmButton.className = 'btn btn-success';
            } else {
                modalTitle.innerHTML = '<i class="bx bx-x"></i> Tolak Pengajuan';
                confirmButton.innerHTML = '<i class="bx bx-x"></i> Tolak';
                confirmButton.className = 'btn btn-danger';
            }
            
            openModal('actionModal');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>

    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: var(--bg-secondary);
            margin: 10% auto;
            border-radius: var(--border-radius-lg);
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-xl);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 1.5rem;
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .close {
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: var(--transition);
        }

        .close:hover {
            opacity: 0.7;
        }
    </style>
</body>
</html>
