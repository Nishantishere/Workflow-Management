<?php
require 'db.php'; // or wherever your PDO connection is

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['id'] ?? null;
    $new_status = $_POST['status'] ?? null;

    if ($task_id && $new_status) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $task_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
