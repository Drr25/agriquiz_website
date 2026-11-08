<?php
session_start();
require '../db_conn.php';

header('Content-Type: application/json');

// ── Auth check ──
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // ── Global Stats ──
    $statsRow = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT
            COUNT(*)                                             AS total_records,
            COUNT(DISTINCT student_id)                          AS students_recorded,
            ROUND(AVG((score / total_items) * 100), 1)         AS avg_score
        FROM preboard_manual_records
        WHERE total_items > 0
    "));

    $stats = [
        'total_records'     => (int)   ($statsRow['total_records']     ?? 0),
        'students_recorded' => (int)   ($statsRow['students_recorded'] ?? 0),
        'avg_score'         => (float) ($statsRow['avg_score']         ?? 0),
    ];

    // ── Per-Field Breakdown ──
    $fieldRes = mysqli_query($conn, "
        SELECT
            pmr.subject,
            COUNT(*)                                            AS total_records,
            COUNT(DISTINCT pmr.student_id)                     AS student_count,
            ROUND(AVG((pmr.score / pmr.total_items) * 100), 1) AS avg_pct,
            ROUND(MAX((pmr.score / pmr.total_items) * 100), 1) AS best_pct
        FROM preboard_manual_records pmr
        WHERE pmr.total_items > 0
        GROUP BY pmr.subject
        ORDER BY avg_pct DESC
    ");

    $by_field = [];
    while ($row = mysqli_fetch_assoc($fieldRes)) {
        $by_field[] = [
            'subject'       => $row['subject'],
            'total_records' => (int)   $row['total_records'],
            'student_count' => (int)   $row['student_count'],
            'avg_pct'       => (float) $row['avg_pct'],
            'best_pct'      => (float) $row['best_pct'],
        ];
    }

    // ── All Records (for table + client-side filtering) ──
    $recRes = mysqli_query($conn, "
        SELECT
            pmr.id,
            u.username,
            u.email,
            pmr.subject,
            pmr.score,
            pmr.total_items,
            pmr.recorded_at
        FROM preboard_manual_records pmr
        JOIN users u ON u.id = pmr.student_id
        ORDER BY pmr.recorded_at DESC
    ");

    $recent = [];
    while ($row = mysqli_fetch_assoc($recRes)) {
        $recent[] = [
            'id'          => (int)   $row['id'],
            'username'    => $row['username'],
            'email'       => $row['email'],
            'subject'     => $row['subject'],
            'score'       => (float) $row['score'],
            'total_items' => (int)   $row['total_items'],
            'recorded_at' => $row['recorded_at'],
        ];
    }

    mysqli_close($conn);

    echo json_encode([
        'success'  => true,
        'stats'    => $stats,
        'by_field' => $by_field,
        'recent'   => $recent,
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
}
?>