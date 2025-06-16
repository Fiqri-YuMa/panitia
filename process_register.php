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
    if (empty($_POST['division_id']) && empty($_POST['real_division_id'])) {
        showErrorMessage("Divisi harus dipilih!");
    }

    // Escape input
    $name = $db->real_escape_string($_POST['name']);
    $organization_id = (int)$_POST['organization_id'];
    if(!empty($_POST['division_id'])) {
        $division_id = (int)$_POST['division_id'];
    }
    if (empty($division_id)) {
        $division_id = (int)$_POST['real_division_id'];
    }
    $phone = $db->real_escape_string($_POST['phone']);
    $address = $db->real_escape_string($_POST['address']);
    $pre_registered_id = isset($_POST['pre_registered_id']) ? (int)$_POST['pre_registered_id'] : null;
    $is_verified = 1;
    // Validasi kuota khusus Markas yang pre-registered
    if ($organization_id == 3 && $pre_registered_id) {
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
        $is_verified = 0;
    }
    // ... (bagian awal tetap sama)

// Validasi kuota khusus Markas
if ($organization_id == 3 && !$pre_registered_id) {
    $query = $db->query("
        SELECT 
            d.max_markas_capacity,
            (
                SELECT COUNT(*) FROM registrants 
                WHERE division_id = d.id AND organization_id = 3
            ) + (
                SELECT COUNT(*) FROM pre_registered 
                WHERE division_id = d.id AND is_completed = FALSE
            ) AS used
        FROM divisions d
        WHERE d.id = $division_id
    ");
    
    $data = $query->fetch_assoc();
    
    if ($data['max_markas_capacity'] !== null && 
        $data['used'] >= $data['max_markas_capacity']) {
        showErrorMessage("Kuota Markas untuk divisi ini sudah terpenuhi!");
    }
}

// ... (bagian selanjutnya tetap sama)

    // Simpan data
    $query = $db->prepare("INSERT INTO registrants (name, phone, address, organization_id, division_id, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
    $query->bind_param("sssiii", $name, $phone, $address, $organization_id, $division_id, $is_verified);
    
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