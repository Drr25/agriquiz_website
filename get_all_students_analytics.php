<?php
header('Content-Type: application/json');

$db = new mysqli('sql306.infinityfree.com', 'if0_42619600', 'MnbvcxzQwerty', 'if0_42619600_agriquiz');
if($db->connect_error) {
  die(json_encode(['success' => false, 'message' => 'DB error']));
}

$sql = "
  SELECT DISTINCT student_email FROM quiz_attempts
";
$result = $db->query($sql);
$emails = [];
while($row = $result->fetch_assoc()) {
  $emails[] = $row['student_email'];
}

$students = [];
foreach($emails as $email) {
  $stmt = $db->prepare("
    SELECT 
      ? as student_email,
      COUNT(*) as total_attempts,
      ROUND(AVG(pct), 1) as overall_avg,
      ROUND(MAX(pct), 1) as best_score,
      COUNT(DISTINCT field) as fields_attempted,
      MAX(taken_at) as last_active
    FROM quiz_attempts
    WHERE student_email = ?
  ");
  $stmt->bind_param('ss', $email, $email);
  $stmt->execute();
  $data = $stmt->get_result()->fetch_assoc();
  $students[] = $data;
  $stmt->close();
}

// Sort by overall_avg DESC
usort($students, function($a, $b) {
  return $b['overall_avg'] <=> $a['overall_avg'];
});

echo json_encode(['success' => true, 'data' => $students]);
$db->close();
?>