<?php
$db = new mysqli('localhost', 'root', '', 'panitia_db');
if ($db->connect_error) {
    die("Koneksi database gagal: " . $db->connect_error);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Panitia - PMI</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <img src="pmi-logo.png" alt="Logo PMI" class="header-logo">
        <h1>PENDAFTARAN PANITIA</h1>
    </div>

    <div class="container">
        <form action="process_register.php" method="POST" id="registrationForm">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" required placeholder="Masukkan nama lengkap">
            </div>

            <div class="form-group">
                <label for="organization_id">Organisasi</label>
                <select id="organization_id" name="organization_id" required>
                    <option value="">-- Pilih Organisasi --</option>
                    <?php
                    $orgs = $db->query("SELECT * FROM organizations");
                    while ($org = $orgs->fetch_assoc()): ?>
                        <option value="<?= $org['id'] ?>"><?= $org['name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div id="markas_section" style="display:none;">
                <div class="form-group">
                    <label>Pre-Registered (Khusus Markas)</label>
                    <select name="pre_registered_id" id="pre_registered_id" required>
                        <option value="">-- Pilih nama Anda --</option>
                    </select>
                </div>
                <div id="pre_reg_notif" class="pre-reg-notif" style="display:none;">
                    Anda terdaftar di divisi: <strong id="pre_reg_division"></strong>
                </div>
            </div>

            <div class="form-group">
                <label for="division_id">Divisi</label>
                <select id="division_id" name="division_id" required>
                    <option value="">-- Pilih Divisi --</option>
                    <?php
                    $divisions = $db->query("
                        SELECT 
                            d.id, 
                            d.name, 
                            d.max_markas_capacity,
                            (
                                SELECT COUNT(*) FROM registrants 
                                WHERE division_id = d.id AND organization_id = 3
                            ) + (
                                SELECT COUNT(*) FROM pre_registered 
                                WHERE division_id = d.id AND is_completed = FALSE
                            ) AS used
                        FROM divisions d
                    ");
                    
                    while ($div = $divisions->fetch_assoc()): 
                        $quota_info = ($div['max_markas_capacity'] !== null) 
                            ? " (Markas: {$div['used']}/{$div['max_markas_capacity']})" 
                            : "";
                    ?>
                        <option value="<?= $div['id'] ?>">
                            <?= $div['name'] . $quota_info ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="phone">Nomor HP</label>
                <input type="tel" id="phone" name="phone" required placeholder="Contoh: 081234567890">
            </div>

            <div class="form-group">
                <label for="address">Alamat Lengkap</label>
                <textarea id="address" name="address" required placeholder="Masukkan alamat lengkap"></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">DAFTAR SEKARANG</button>
            </div>
        </form>
    </div>

    <script>
        // Tampilkan section pre-registered hanya untuk Markas
        document.getElementById("organization_id").addEventListener("change", function() {
            const markasSection = document.getElementById("markas_section");
            const divisionSelect = document.getElementById("division_id");
            
            markasSection.style.display = this.value === "3" ? "block" : "none";
            divisionSelect.disabled = false;
            document.getElementById("pre_reg_notif").style.display = "none";
            
            if (this.value === "3") {
                loadPreRegisteredNames();
            }
        });

        // Load data pre-registered
        function loadPreRegisteredNames() {
            fetch("get_pre_registered.php")
                .then(response => response.json())
                .then(data => {
                    const dropdown = document.getElementById("pre_registered_id");
                    dropdown.innerHTML = '<option value="">-- Pilih nama Anda --</option>';
                    data.forEach(item => {
                        dropdown.innerHTML += `
                            <option 
                                value="${item.id}" 
                                data-division-id="${item.division_id}"
                                data-division-name="${item.division_name}"
                            >
                                ${item.name}
                            </option>`;
                    });
                });
        }

        // Kunci divisi ketika memilih pre-registered
        document.getElementById("pre_registered_id").addEventListener("change", function() {
            const selectedOption = this.options[this.selectedIndex];
            const divisionSelect = document.getElementById("division_id");
            const notifDiv = document.getElementById("pre_reg_notif");
            
            if (selectedOption.value !== "") {
                // Kunci divisi
                divisionSelect.value = selectedOption.getAttribute("data-division-id");
                divisionSelect.disabled = true;
                
                // Tampilkan notifikasi
                document.getElementById("pre_reg_division").textContent = 
                    selectedOption.getAttribute("data-division-name");
                notifDiv.style.display = "block";
            } else {
                // Buka kembali pilihan divisi
                divisionSelect.disabled = false;
                notifDiv.style.display = "none";
            }
        });

        // Validasi sebelum submit
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const orgId = document.getElementById('organization_id').value;
            const preRegId = document.getElementById('pre_registered_id').value;
            
            if (orgId === "3" && preRegId === "") {
                e.preventDefault();
                alert('Anda harus memilih nama dari daftar pre-registered!');
                return false;
            }
            return true;
        });
    </script>
</body>
</html>