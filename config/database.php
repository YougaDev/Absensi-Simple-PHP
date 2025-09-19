<?php
$host = 'localhost';
$dbname = 'absen';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("
    <div style='font-family: Inter, sans-serif; padding: 2rem; background: linear-gradient(135deg, #fee 0%, #fef5e7 100%); color: #c53030; border: 2px solid #fc8181; border-radius: 12px; margin: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1);'>
        <h3 style='margin-bottom: 1rem; font-size: 1.5rem;'>🚨 Database Connection Error</h3>
        <p style='margin-bottom: 1rem;'><strong>Error:</strong> " . $e->getMessage() . "</p>
        <div style='background: white; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #fc8181;'>
            <p style='margin-bottom: 1rem; font-weight: 600;'>🔧 Langkah Perbaikan:</p>
            <ol style='margin-left: 1.5rem; line-height: 1.8;'>
                <li>Pastikan <strong>XAMPP</strong> sudah running</li>
                <li>Pastikan service <strong>MySQL</strong> sudah start</li>
                <li>Import file <code style='background: #f7fafc; padding: 0.25rem 0.5rem; border-radius: 4px;'>database/absen.sql</code> ke phpMyAdmin</li>
                <li>Pastikan database bernama <strong>'absen'</strong> sudah ada</li>
                <li>Refresh halaman ini setelah database berhasil dibuat</li>
            </ol>
        </div>
    </div>
    ");
}
?>
