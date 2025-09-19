<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireAdmin();

$success = '';
$error = '';

// Handle employee actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        if (empty($username) || empty($password) || empty($full_name)) {
            $error = 'Username, password, dan nama lengkap harus diisi';
        } else {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan';
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email, phone) VALUES (?, ?, ?, 'employee', ?, ?)");
                
                if ($stmt->execute([$username, $password, $full_name, $email, $phone])) {
                    $success = 'Karyawan berhasil ditambahkan';
                } else {
                    $error = 'Terjadi kesalahan saat menambahkan karyawan';
                }
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $username = $_POST['username'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($id) || empty($username) || empty($full_name)) {
            $error = 'Data tidak lengkap';
        } else {
            // Check if username exists for other users
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan';
            } else {
                if ($password) {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, full_name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$username, $password, $full_name, $email, $phone, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$username, $full_name, $email, $phone, $id]);
                }
                
                $success = 'Data karyawan berhasil diperbarui';
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
            if ($stmt->execute([$id])) {
                $success = 'Karyawan berhasil dihapus';
            } else {
                $error = 'Terjadi kesalahan saat menghapus karyawan';
            }
        }
    }
}

// Get all employees
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'employee' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan - Admin</title>
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
                <li><a href="employees.php" class="active">👥 Data Karyawan</a></li>
                <li><a href="leave_requests.php">📝 Pengajuan Cuti/Izin</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <div class="content-header">
                <h1><i class='bx bx-group'></i> Data Karyawan</h1>
                <button onclick="openModal('addModal')" class="btn btn-primary"><i class='bx bx-plus'></i> Tambah Karyawan</button>
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
            
            <div class="table-container">
                <div class="table-header">
                    <h2>📊 Daftar Karyawan</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 3rem;">
                                📭 Belum ada data karyawan
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($employee['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($employee['phone'] ?? '-'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($employee['created_at'])); ?></td>
                                <td>
                                    <button onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)" class="btn btn-warning btn-sm"><i class='bx bx-edit-alt'></i> Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('⚠️ Apakah Anda yakin ingin menghapus karyawan ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class='bx bx-trash'></i> Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Add Employee Modal -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-plus'></i> Tambah Karyawan Baru</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div style="padding: 1.5rem;">
                    <div style="margin-bottom: 1rem;">
                        <label for="add_username" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-user'></i> Username</label>
                        <input type="text" id="add_username" name="username" required 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label for="add_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-lock-alt'></i> Password</label>
                        <input type="password" id="add_password" name="password" required 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label for="add_full_name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-user-circle'></i> Nama Lengkap</label>
                        <input type="text" id="add_full_name" name="full_name" required 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label for="add_email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-envelope'></i> Email</label>
                        <input type="email" id="add_email" name="email" 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label for="add_phone" style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class='bx bx-phone'></i> Telepon</label>
                        <input type="text" id="add_phone" name="phone" 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary"><i class='bx bx-x'></i> Batal</button>
                        <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Employee Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-edit-alt'></i> Edit Karyawan</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div style="padding: 1.5rem;">
                    <div style="margin-bottom: 1rem;">
                        <label for="edit_username" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">👤 Username</label>
                        <input type="text" id="edit_username" name="username" required 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label for="edit_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">🔒 Password (kosongkan jika tidak diubah)</label>
                        <input type="password" id="edit_password" name="password" 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label for="edit_full_name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">👨‍💼 Nama Lengkap</label>
                        <input type="text" id="edit_full_name" name="full_name" required 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label for="edit_email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">📧 Email</label>
                        <input type="email" id="edit_email" name="email" 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label for="edit_phone" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">📱 Telepon</label>
                        <input type="text" id="edit_phone" name="phone" 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: var(--border-radius);">
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">❌ Batal</button>
                        <button type="submit" class="btn btn-primary">💾 Update</button>
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
        
        function editEmployee(employee) {
            document.getElementById('edit_id').value = employee.id;
            document.getElementById('edit_username').value = employee.username;
            document.getElementById('edit_full_name').value = employee.full_name;
            document.getElementById('edit_email').value = employee.email || '';
            document.getElementById('edit_phone').value = employee.phone || '';
            openModal('editModal');
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
            margin: 5% auto;
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
