<?php
require_once '../config/session.php';
require_once '../config/database.php';
requireLogin();

$attachment_id = $_GET['id'] ?? 0;
$task_id = $_GET['task_id'] ?? 0;

$database = new Database();
$db = $database->getConnection();

// Buscar arquivo
$query = "SELECT a.file_path FROM attachments a
          INNER JOIN tasks t ON a.task_id = t.id
          WHERE a.id = :attachment_id AND t.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':attachment_id', $attachment_id);
$stmt->bindParam(':user_id', getUserId());
$stmt->execute();
$attachment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($attachment) {
    // Deletar arquivo físico
    if (file_exists('../' . $attachment['file_path'])) {
        unlink('../' . $attachment['file_path']);
    }
    
    // Deletar registro
    $query = "DELETE FROM attachments WHERE id = :attachment_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':attachment_id', $attachment_id);
    $stmt->execute();
}

header("Location: ../edit_task.php?id=" . $task_id);
exit();
?>
