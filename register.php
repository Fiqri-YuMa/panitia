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
                    <select name="pre_registered_id" id="pre_registered_id">
                        <option value="">-- Pilih nama Anda jika sudah pre-registered --</option>
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
                        ORDER BY d.id
                        LIMIT 11
                    ");
                    
                    while ($div = $divisions->fetch_assoc()): 
                        $quota_info = ($div['max_markas_capacity'] !== null) 
                            ? " (Kuota Markas: {$div['used']}/{$div['max_markas_capacity']})" 
                            : "";
                    ?>
                        <option value="<?= $div['id'] ?>">
                            <?= $div['name'] . $quota_info ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="hidden" id="real_division_id" name="real_division_id">
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
        // ... (bagian sebelumnya tetap sama)

document.getElementById("organization_id").addEventListener("change", function() {
    const markasSection = document.getElementById("markas_section");
    const divisionSelect = document.getElementById("division_id");
    
    markasSection.style.display = this.value === "3" ? "block" : "none";
    
    // Reload divisi saat organisasi berubah
    fetchDivisions(this.value === "3");
    
    if (this.value === "3") {
        loadPreRegisteredNames();
    } else {
        document.getElementById("pre_registered_id").value = "";
        document.getElementById("pre_reg_notif").style.display = "none";
    }
});

// Fungsi untuk memuat ulang divisi
function fetchDivisions(isMarkas) {
    fetch("get_divisions.php?is_markas=" + (isMarkas ? '1' : '0'))
        .then(response => response.json())
        .then(data => {
            const dropdown = document.getElementById("division_id");
            dropdown.innerHTML = '<option value="">-- Pilih Divisi --</option>';
            data.forEach(div => {
                const option = document.createElement("option");
                option.value = div.id;
                option.textContent = div.name + div.quota_info;
                if (div.disabled) {
                    option.disabled = true;
                }
                dropdown.appendChild(option);
            });
        });
}

// ... (bagian lainnya tetap sama)

        // Load data pre-registered
        function loadPreRegisteredNames() {
    fetch("get_pre_registered.php")
        .then(response => response.json())
        .then(data => {
            const dropdown = document.getElementById("pre_registered_id");
            dropdown.innerHTML = '<option value="">-- Pilih nama Anda jika sudah pre-registered --</option>';
            data.forEach(item => {
                dropdown.innerHTML += `
                    <option 
                        value="${item.id}" 
                        data-division-id="${item.division_id}"
                        data-division-name="${item.division_name}"
                    >
                        ${item.name} (Divisi: ${item.division_name})
                    </option>`;
            });
        });
}

    // Kunci divisi ketika memilih pre-registered
    document.getElementById("pre_registered_id").addEventListener("change", function() {
        const selectedOption = this.options[this.selectedIndex];
        const divisionSelect = document.getElementById("division_id");
        const realDivisionInput = document.getElementById("real_division_id");
        const notifDiv = document.getElementById("pre_reg_notif");
        
        if (selectedOption.value !== "") {
            // Set divisi otomatis
            const divisionId = selectedOption.getAttribute("data-division-id");
            const divisionName = selectedOption.getAttribute("data-division-name");
            
            // Simpan nilai sebenarnya di hidden input
            realDivisionInput.value = divisionId;
            
            // Tampilkan nilai di select yang disabled
            divisionSelect.value = divisionId;
            divisionSelect.disabled = true;
            
            // Nonaktifkan validasi required
            divisionSelect.required = false;
            
            // Tampilkan notifikasi
            document.getElementById("pre_reg_division").textContent = divisionName;
            notifDiv.style.display = "block";
        } else {
            // Kembalikan ke keadaan semula
            realDivisionInput.value = "";
            divisionSelect.disabled = false;
            divisionSelect.required = true;
            notifDiv.style.display = "none";
        }
    });

    // Validasi sebelum submit
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const orgId = document.getElementById('organization_id').value;
        const preRegId = document.getElementById('pre_registered_id').value;
        const divisionSelect = document.getElementById('division_id');
        const realDivisionInput = document.getElementById('real_division_id');
        
        // Jika markas dan memilih pre-registered
        if (orgId === "3" && preRegId !== "") {
            // Pastikan nilai tersimpan di hidden input
            const preRegDiv = document.getElementById('pre_registered_id')
                            .options[document.getElementById('pre_registered_id').selectedIndex]
                            .getAttribute('data-division-id');
            
            realDivisionInput.value = preRegDiv;
            divisionSelect.value = preRegDiv;
        }
        
        return true;
    });
</script>
</body>
</html>