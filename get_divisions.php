<?php
header("Content-Type: application/json");
$db = new mysqli('localhost', 'root', '', 'panitia_db');
$is_markas = isset($_GET['is_markas']) && $_GET['is_markas'] == '1';

$query = "
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
";

$result = $db->query($query);
$divisions = [];

while ($row = $result->fetch_assoc()) {
    $is_full = $is_markas && $row['max_markas_capacity'] !== null && 
               $row['used'] >= $row['max_markas_capacity'];
    
    $divisions[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'quota_info' => ($row['max_markas_capacity'] !== null) 
            ? " (Kuota Markas: {$row['used']}/{$row['max_markas_capacity']})" 
            : "",
        'disabled' => $is_full
    ];
}

echo json_encode($divisions);
?>