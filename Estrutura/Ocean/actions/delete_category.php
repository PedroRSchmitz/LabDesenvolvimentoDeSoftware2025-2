<?php
require_once '../config/session.php';
require_once '../config/database.php';
requireLogin();

$category_id = $_GET['id'] ?? 0;
$user_id = getUserId();

$database = new Database();
$db = $database->getConnection();

// Deletar categoria (tarefas associadas terão category_id = NULL por SET NULL)
$query = "DELETE FROM categories WHERE id = :category_id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':category_id', $category_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

header("Location: ../categories.php");
exit();
?>
