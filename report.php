<?php
$db = new mysqli('localhost', 'root', '', 'panitia_db');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Pendaftar - PMI</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="header">
    <img src="pmi-logo.png" alt="Logo PMI" class="header-logo">
    <h1>LAPORAN PENDAFTAR</h1>
  </div>

  <div class="container">
    <h2 style="color: var(--primary); margin-bottom: 20px;">Statistik Pendaftar</h2>
    
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
      <thead>
        <tr style="background-color: var(--primary); color: white;">
          <th style="padding: 12px; text-align: left;">Divisi</th>
          <th style="padding: 12px; text-align: left;">Organisasi</th>
          <th style="padding: 12px; text-align: right;">Jumlah</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $report = $db->query("
          SELECT 
            d.name AS division,
            o.name AS organization,
            COUNT(r.id) AS total
          FROM registrants r
          JOIN divisions d ON r.division_id = d.id
          JOIN organizations o ON r.organization_id = o.id
          GROUP BY d.name, o.name
          ORDER BY d.name, o.name
        ");
        
        while ($row = $report->fetch_assoc()): ?>
          <tr style="border-bottom: 1px solid var(--border);">
            <td style="padding: 12px;"><?= htmlspecialchars($row['division']) ?></td>
            <td style="padding: 12px;"><?= htmlspecialchars($row['organization']) ?></td>
            <td style="padding: 12px; text-align: right;"><?= $row['total'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <h3 style="color: var(--primary); margin-top: 40px;">Kuota Markas</h3>
    <table style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr style="background-color: var(--accent); color: white;">
          <th style="padding: 12px; text-align: left;">Divisi</th>
          <th style="padding: 12px; text-align: right;">Terdaftar</th>
          <th style="padding: 12px; text-align: right;">Maksimal</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $quota = $db->query("
          SELECT 
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
          WHERE d.max_markas_capacity IS NOT NULL
        ");
        
        while ($row = $quota->fetch_assoc()): ?>
          <tr style="border-bottom: 1px solid var(--border);">
            <td style="padding: 12px;"><?= htmlspecialchars($row['name']) ?></td>
            <td style="padding: 12px; text-align: right; <?= ($row['used'] >= $row['max_markas_capacity']) ? 'color: var(--primary); font-weight: bold;' : '' ?>">
              <?= $row['used'] ?>
            </td>
            <td style="padding: 12px; text-align: right;"><?= $row['max_markas_capacity'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <div style="margin-top: 30px;">
      <a href="register.php" class="btn">Kembali ke Form</a>
    </div>
  </div>
</body>
</html>