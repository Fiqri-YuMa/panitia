<?php
header("Content-Type: application/json");
$db = new mysqli('localhost', 'root', '', 'panitia_db');

if ($db->connect_error) {
    die(json_encode(['error' => 'Koneksi database gagal']));
}

$query = "
    SELECT 
        p.id, 
        p.name, 
        p.division_id,
        d.name as division_name
    FROM pre_registered p
    JOIN divisions d ON p.division_id = d.id
    WHERE p.is_completed = FALSE
";

$result = $db->query($query);

if (!$result) {
    die(json_encode(['error' => 'Query gagal']));
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'division_id' => $row['division_id'],
        'division_name' => $row['division_name']
    ];
}

echo json_encode($data);
?>