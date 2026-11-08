<?php
header('Content-Type: application/json');
session_start();

// Database connection
$db = new mysqli('sql306.infinityfree.com', 'if0_42619600', 'MnbvcxzQwerty', 'if0_42619600_agriquiz');
if($db->connect_error) {
  http_response_code(500);
  die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $db->connect_error]));
}

$action = $_GET['action'] ?? '';
$email = $_GET['email'] ?? '';

// Validate email
if(!$email) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'Email required']));
}

switch($action) {
  case 'student_summary':
    $stmt = $db->prepare("
      SELECT 
        COUNT(*) as total_attempts,
        ROUND(
          (
            SELECT SUM(field_avg)
            FROM (
              SELECT AVG(pct) as field_avg
              FROM quiz_attempts
              WHERE student_email = ?
              GROUP BY field
            ) as field_avgs
          ) / 6, 1
        ) as overall_avg,
        ROUND(MAX(pct), 1) as best_score,
        COUNT(DISTINCT field) as fields_attempted
      FROM quiz_attempts
      WHERE student_email = ?
    ");
    
    if(!$stmt) {
      http_response_code(500);
      die(json_encode(['success' => false, 'message' => $db->error]));
    }
    
    // Two binds: first for subquery, second for outer query
    $stmt->bind_param('ss', $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    // Handle NULL values and ensure proper types
    $data['total_attempts']   = (int)($data['total_attempts'] ?? 0);
    $data['overall_avg']      = (float)($data['overall_avg'] ?? 0);
    $data['best_score']       = (float)($data['best_score'] ?? 0);
    $data['fields_attempted'] = (int)($data['fields_attempted'] ?? 0);
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
    break;

  case 'student_field_performance':
    $stmt = $db->prepare("
      SELECT
        field,
        COUNT(*) as attempts,
        ROUND(AVG(pct), 1) as avg_pct,
        ROUND(MAX(pct), 1) as best_pct,
        SUM(score) as total_correct,
        SUM(total) as total_questions,
        MAX(taken_at) as last_taken
      FROM quiz_attempts
      WHERE student_email = ?
      GROUP BY field
      ORDER BY last_taken DESC
    ");
    
    if(!$stmt) {
      http_response_code(500);
      die(json_encode(['success' => false, 'message' => $db->error]));
    }
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
      $row['attempts']        = (int)($row['attempts'] ?? 0);
      $row['avg_pct']         = (float)($row['avg_pct'] ?? 0);
      $row['best_pct']        = (float)($row['best_pct'] ?? 0);
      $row['total_correct']   = (int)($row['total_correct'] ?? 0);
      $row['total_questions'] = (int)($row['total_questions'] ?? 0);
      $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
    break;

  case 'student_recent':
    $limit = intval($_GET['limit'] ?? 20);
    if($limit < 1 || $limit > 100) $limit = 20;
    
    $stmt = $db->prepare("
      SELECT
        id,
        field,
        quiz_title,
        score,
        total,
        pct,
        taken_at
      FROM quiz_attempts
      WHERE student_email = ?
      ORDER BY taken_at DESC
      LIMIT ?
    ");
    
    if(!$stmt) {
      http_response_code(500);
      die(json_encode(['success' => false, 'message' => $db->error]));
    }
    
    $stmt->bind_param('si', $email, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
      $row['id']    = (int)($row['id'] ?? 0);
      $row['score'] = (int)($row['score'] ?? 0);
      $row['total'] = (int)($row['total'] ?? 0);
      $row['pct']   = (int)($row['pct'] ?? 0);
      $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
    break;

  case 'attempt_detail':
    $attempt_id = intval($_GET['id'] ?? 0);
    
    if($attempt_id < 1) {
      http_response_code(400);
      die(json_encode(['success' => false, 'message' => 'Invalid attempt ID']));
    }
    
    $stmt = $db->prepare("
      SELECT *
      FROM quiz_attempts
      WHERE id = ? AND student_email = ?
    ");
    
    if(!$stmt) {
      http_response_code(500);
      die(json_encode(['success' => false, 'message' => $db->error]));
    }
    
    $stmt->bind_param('is', $attempt_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
      http_response_code(404);
      die(json_encode(['success' => false, 'message' => 'Attempt not found']));
    }
    
    $data = $result->fetch_assoc();
    // Decode JSON answers if present
    if(!empty($data['answers_json'])) {
      $data['answers'] = json_decode($data['answers_json'], true);
    } else {
      $data['answers'] = [];
    }
    unset($data['answers_json']);
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
    break;

  case 'field_summary':
    $field = $_GET['field'] ?? '';
    
    if(!$field) {
      http_response_code(400);
      die(json_encode(['success' => false, 'message' => 'Field name required']));
    }
    
    $stmt = $db->prepare("
      SELECT
        field,
        COUNT(*) as attempts,
        ROUND(AVG(pct), 1) as avg_pct,
        ROUND(MAX(pct), 1) as best_pct,
        ROUND(MIN(pct), 1) as worst_pct,
        SUM(score) as total_correct,
        SUM(total) as total_questions,
        MIN(taken_at) as first_attempt,
        MAX(taken_at) as last_attempt
      FROM quiz_attempts
      WHERE student_email = ? AND field = ?
      GROUP BY field
    ");
    
    if(!$stmt) {
      http_response_code(500);
      die(json_encode(['success' => false, 'message' => $db->error]));
    }
    
    $stmt->bind_param('ss', $email, $field);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
      http_response_code(404);
      die(json_encode(['success' => false, 'message' => 'No data for this field']));
    }
    
    $data = $result->fetch_assoc();
    
    // Type cast
    $data['attempts']        = (int)($data['attempts'] ?? 0);
    $data['avg_pct']         = (float)($data['avg_pct'] ?? 0);
    $data['best_pct']        = (float)($data['best_pct'] ?? 0);
    $data['worst_pct']       = (float)($data['worst_pct'] ?? 0);
    $data['total_correct']   = (int)($data['total_correct'] ?? 0);
    $data['total_questions'] = (int)($data['total_questions'] ?? 0);
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
    break;

  case 'progress_stats':
    $stmt = $db->prepare("
      SELECT
        DATE(taken_at) as day,
        COUNT(*) as attempts,
        ROUND(AVG(pct), 1) as avg_pct
      FROM quiz_attempts
      WHERE student_email = ? AND taken_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      GROUP BY DATE(taken_at)
      ORDER BY day ASC
    ");
    
    if(!$stmt) {
      http_response_code(500);
      die(json_encode(['success' => false, 'message' => $db->error]));
    }
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
      $row['attempts'] = (int)($row['attempts'] ?? 0);
      $row['avg_pct']  = (float)($row['avg_pct'] ?? 0);
      $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
    break;

  default:
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
}

$db->close();
?>