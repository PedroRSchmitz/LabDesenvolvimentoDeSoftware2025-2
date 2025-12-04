<?php
require_once '../config/session.php';
require_once '../config/database.php';
requireLogin();

$task_id = $_GET['id'] ?? 0;
$user_id = getUserId();

$database = new Database();
$db = $database->getConnection();

// Buscar e deletar arquivos físicos
$query = "SELECT file_path FROM attachments WHERE task_id = :task_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->execute();
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($attachments as $attachment) {
    if (file_exists('../' . $attachment['file_path'])) {
        unlink('../' . $attachment['file_path']);
    }
}

// Deletar tarefa (anexos serão deletados por CASCADE)
$query = "DELETE FROM tasks WHERE id = :task_id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

header("Location: ../dashboard.php");
exit();
?>
