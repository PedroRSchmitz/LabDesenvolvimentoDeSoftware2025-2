<?php
require_once '../config/session.php';
require_once '../config/database.php';
requireLogin();

$task_id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? '';
$user_id = getUserId();

if (in_array($status, ['fazendo', 'feito', 'cancelado'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE tasks SET status = :status WHERE id = :task_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../dashboard.php'));
exit();
?>
