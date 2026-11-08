<?php
header('Content-Type: application/json');

$db = new mysqli('sql306.infinityfree.com', 'if0_42619600', 'MnbvcxzQwerty', 'if0_42619600_agriquiz');
if($db->connect_error) {
  die(json_encode(['success' => false, 'message' => 'DB error']));
}

$sql = "
  SELECT
    field,
    COUNT(DISTINCT student_email) as student_count,
    COUNT(*) as total_attempts,
    ROUND(AVG(pct), 1) as avg_pct,
    ROUND(MAX(pct), 1) as best_pct,
    ROUND(MIN(pct), 1) as worst_pct
  FROM quiz_attempts
  GROUP BY field
  ORDER BY avg_pct DESC
";

$result = $db->query($sql);
$fields = [];
while($row = $result->fetch_assoc()) {
  $fields[] = $row;
}

echo json_encode(['success' => true, 'data' => $fields]);
$db->close();
?>