<?php
header("Content-Type: application/json");
$db = new mysqli('localhost', 'root', '', 'panitia_db');

// Validasi input
$division_id = isset($_GET['division_id']) ? (int)$_GET['division_id'] : 0;

if ($division_id <= 0) {
  echo json_encode(['error' => 'Invalid division ID']);
  exit();
}

// Query kapasitas
$result = $db->query("
  SELECT 
    d.max_markas_capacity,
    (
      SELECT COUNT(*) FROM registrants 
      WHERE organization_id = 3 AND division_id = d.id
    ) + (
      SELECT COUNT(*) FROM pre_registered 
      WHERE division_id = d.id AND is_completed = FALSE
    ) AS current_count
  FROM divisions d
  WHERE d.id = $division_id
");

if (!$result) {
  echo json_encode(['error' => 'Database error']);
  exit();
}

$data = $result->fetch_assoc();
echo json_encode([
  'available' => $data['max_markas_capacity'] === null || 
                $data['current_count'] < $data['max_markas_capacity']
]);
?>