<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit();
    }

        if (isset($_POST['goal_id']) && isset($_POST['progress'])) {
        $goal_id = intval($_POST['goal_id']);
        $progress = intval($_POST['progress']);

        if ($progress < 0 || $progress > 100) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid progress value']);
            exit();
        }

        
        require_once 'db/db_connect.php';

        // Update progress
        $stmt = $conn->prepare("UPDATE daily_goals SET progress = ? WHERE id = ?");
        $stmt->bind_param("ii", $progress, $goal_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Progress updated']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
