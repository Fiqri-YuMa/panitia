<?php
// Koneksi database
$db = new mysqli('localhost', 'root', '', 'panitia_db');
if ($db->connect_error) {
    die("Koneksi database gagal: " . $db->connect_error);
}

// Fungsi untuk menampilkan error
function showErrorMessage($message) {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Error</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <div class="error-message">
                <h2>Error</h2>
                <p>'.$message.'</p>
                <a href="register.php" class="btn">Kembali ke Form</a>
            </div>
        </div>
    </body>
    </html>';
    exit();
}

// Fungsi untuk menampilkan sukses
function showSuccessMessage() {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Sukses</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <div class="success-message">
                <h2>Pendaftaran Berhasil!</h2>
                <p>Terima kasih telah mendaftar sebagai panitia PMI.</p>
                <a href="report.php" class="btn">Lihat Laporan</a>
                <a href="register.php" class="btn" style="background: var(--accent); margin-top: 10px;">Daftar Lagi</a>
            </div>
        </div>
    </body>
    </html>';
    exit();
}

// Proses data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi required fields
    if (empty($_POST['name'])) {
        showErrorMessage("Nama harus diisi!");
    }
    if (empty($_POST['organization_id'])) {
        showErrorMessage("Organisasi harus dipilih!");
    }
    if (empty($_POST['division_id'])) {
        showErrorMessage("Divisi harus dipilih!");
    }

    // Escape input
    $name = $db->real_escape_string($_POST['name']);
    $organization_id = (int)$_POST['organization_id'];
    $division_id = (int)$_POST['division_id'];
    $phone = $db->real_escape_string($_POST['phone']);
    $address = $db->real_escape_string($_POST['address']);
    $pre_registered_id = isset($_POST['pre_registered_id']) ? (int)$_POST['pre_registered_id'] : null;

    // Validasi kuota khusus Markas
    if ($organization_id == 3) {
        $query = $db->query("
            SELECT COUNT(*) as total FROM (
                SELECT id FROM registrants 
                WHERE organization_id = 3 AND division_id = $division_id
                UNION ALL
                SELECT id FROM pre_registered 
                WHERE division_id = $division_id AND is_completed = FALSE
            ) AS combined
        ");
        
        if (!$query) {
            showErrorMessage("Error saat memeriksa kuota: " . $db->error);
        }

        $current_count = $query->fetch_assoc()['total'];
        $max_capacity = $db->query(
            "SELECT max_markas_capacity FROM divisions WHERE id = $division_id"
        )->fetch_row()[0];

        if ($max_capacity !== null && $current_count >= $max_capacity) {
            showErrorMessage("Kuota Markas untuk divisi ini sudah terpenuhi!");
        }
    }

    // Simpan data
    $query = $db->prepare("INSERT INTO registrants (name, phone, address, organization_id, division_id) VALUES (?, ?, ?, ?, ?)");
    $query->bind_param("sssii", $name, $phone, $address, $organization_id, $division_id);
    
    if ($query->execute()) {
        // Update status pre-registered jika ada
        if ($pre_registered_id) {
            $update = $db->prepare("UPDATE pre_registered SET is_completed = TRUE WHERE id = ?");
            $update->bind_param("i", $pre_registered_id);
            $update->execute();
        }
        showSuccessMessage();
    } else {
        showErrorMessage("Terjadi kesalahan database: " . $db->error);
    }
} else {
    showErrorMessage("Akses tidak valid!");
}
?>