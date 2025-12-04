<?php
require_once 'config/session.php';
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();
$task_id = $_GET['id'] ?? 0;

// Buscar tarefa
$query = "SELECT t.*, c.name as category_name, c.color as category_color
          FROM tasks t
          LEFT JOIN categories c ON t.category_id = c.id
          WHERE t.id = :task_id AND t.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header("Location: dashboard.php");
    exit();
}

// Buscar anexos
$query = "SELECT * FROM attachments WHERE task_id = :task_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->execute();
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($task['title']); ?> - Detalhes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?php echo htmlspecialchars($task['title']); ?></h4>
                        <span class="badge bg-<?php echo $task['status'] === 'feito' ? 'success' : ($task['status'] === 'cancelado' ? 'danger' : 'primary'); ?>">
                            <?php echo ucfirst($task['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if ($task['category_name']): ?>
                            <div class="mb-3">
                                <strong>Categoria:</strong>
                                <span class="badge ms-2" style="background-color: <?php echo $task['category_color']; ?>">
                                    <?php echo htmlspecialchars($task['category_name']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <strong>Data de Vencimento:</strong>
                            <span class="ms-2"><?php echo date('d/m/Y', strtotime($task['due_date'])); ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Descrição:</strong>
                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                        </div>
                        
                        <?php if (count($attachments) > 0): ?>
                            <div class="mb-3">
                                <strong>Anexos:</strong>
                                <ul class="list-group mt-2">
                                    <?php foreach ($attachments as $attachment): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-paperclip"></i>
                                                <?php echo htmlspecialchars($attachment['original_filename']); ?>
                                                <small class="text-muted">(<?php echo number_format($attachment['file_size'] / 1024, 2); ?> KB)</small>
                                            </div>
                                            <a href="<?php echo $attachment['file_path']; ?>" download class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-download"></i> Baixar
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex gap-2 mt-4">
                            <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php if ($task['status'] !== 'feito'): ?>
                                <a href="actions/update_status.php?id=<?php echo $task['id']; ?>&status=feito" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Marcar como Feito
                                </a>
                            <?php endif; ?>
                            <?php if ($task['status'] !== 'cancelado'): ?>
                                <a href="actions/update_status.php?id=<?php echo $task['id']; ?>&status=cancelado" class="btn btn-danger" onclick="return confirm('Deseja cancelar esta tarefa?')">
                                    <i class="bi bi-x-circle"></i> Cancelar Tarefa
                                </a>
                            <?php endif; ?>
                            <?php if ($task['status'] === 'cancelado' || $task['status'] === 'feito'): ?>
                                <a href="actions/update_status.php?id=<?php echo $task['id']; ?>&status=fazendo" class="btn btn-warning">
                                    <i class="bi bi-arrow-clockwise"></i> Reativar
                                </a>
                            <?php endif; ?>
                            <a href="actions/delete_task.php?id=<?php echo $task['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Deseja excluir permanentemente esta tarefa?')">
                                <i class="bi bi-trash"></i> Excluir
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
