-- Database: absen (Updated Clean Version)
DROP DATABASE IF EXISTS absen;
CREATE DATABASE absen CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absen;

-- Table: users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: attendance
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('present', 'late', 'absent') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date),
    INDEX idx_user_date (user_id, date)
);

-- Table: leave_requests
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type ENUM('cuti', 'izin') NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_user_status (user_id, status)
);

-- Insert users dengan password tanpa hash (plain text)
INSERT INTO users (username, password, full_name, role, email, phone) VALUES
('admin', 'admin', 'Administrator Sistem', 'admin', 'admin@company.com', '081234567890'),
('karyawan1', '123456', 'John Doe', 'employee', 'john.doe@company.com', '081234567891'),
('karyawan2', '123456', 'Jane Smith', 'employee', 'jane.smith@company.com', '081234567892'),
('karyawan3', '123456', 'Bob Wilson', 'employee', 'bob.wilson@company.com', '081234567893'),
('karyawan4', '123456', 'Alice Johnson', 'employee', 'alice.johnson@company.com', '081234567894');

-- Sample attendance data dengan tanggal dinamis
INSERT INTO attendance (user_id, date, check_in, check_out, status) VALUES
-- Data hari ini
(2, CURDATE(), '08:00:00', '17:00:00', 'present'),
(3, CURDATE(), '07:55:00', '17:10:00', 'present'),
(4, CURDATE(), '08:30:00', '17:15:00', 'late'),
(5, CURDATE(), '08:05:00', NULL, 'present'),

-- Data kemarin
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:15:00', '17:05:00', 'late'),
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 'present'),
(4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '07:50:00', '17:20:00', 'present'),
(5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:45:00', '17:30:00', 'late'),

-- Data 2 hari lalu
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:10:00', '17:15:00', 'late'),
(3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '07:58:00', '17:05:00', 'present'),
(4, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00', '17:00:00', 'present'),

-- Data minggu lalu
(2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '08:00:00', '17:00:00', 'present'),
(3, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '08:20:00', '17:25:00', 'late'),
(4, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '07:55:00', '17:10:00', 'present'),
(5, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '08:00:00', '17:00:00', 'present');

-- Sample leave requests dengan tanggal dinamis
INSERT INTO leave_requests (user_id, start_date, end_date, type, reason, status, admin_notes) VALUES
(2, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'cuti', 'Liburan keluarga ke Bali', 'pending', NULL),
(3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'izin', 'Keperluan keluarga mendadak', 'approved', 'Disetujui, semoga lancar urusannya'),
(4, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'cuti', 'Cuti tahunan', 'pending', NULL),
(5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'izin', 'Sakit demam', 'rejected', 'Mohon sertakan surat keterangan dokter untuk izin sakit'),
(2, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'cuti', 'Acara pernikahan saudara', 'approved', 'Selamat untuk keluarga, semoga bahagia selalu');

-- Create views for easier reporting
CREATE VIEW attendance_summary AS
SELECT 
    u.id as user_id,
    u.full_name,
    u.username,
    COUNT(a.id) as total_attendance,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
FROM users u
LEFT JOIN attendance a ON u.id = a.user_id
WHERE u.role = 'employee'
GROUP BY u.id, u.full_name, u.username;

-- Create view for monthly attendance
CREATE VIEW monthly_attendance AS
SELECT 
    u.full_name,
    YEAR(a.date) as year,
    MONTH(a.date) as month,
    MONTHNAME(a.date) as month_name,
    COUNT(a.id) as total_days,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days
FROM users u
LEFT JOIN attendance a ON u.id = a.user_id
WHERE u.role = 'employee'
GROUP BY u.id, u.full_name, YEAR(a.date), MONTH(a.date)
ORDER BY year DESC, month DESC, u.full_name;
