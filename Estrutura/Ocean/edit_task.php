<?php
require_once 'config/session.php';
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();
$task_id = $_GET['id'] ?? 0;

// Buscar tarefa
$query = "SELECT * FROM tasks WHERE id = :task_id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header("Location: dashboard.php");
    exit();
}

// Buscar categorias
$query = "SELECT * FROM categories WHERE user_id = :user_id ORDER BY name";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'] ?: null;
    $due_date = $_POST['due_date'];
    
    if (empty($title)) {
        $error = 'O título é obrigatório';
    } else {
        $query = "UPDATE tasks SET title = :title, description = :description, 
                  category_id = :category_id, due_date = :due_date 
                  WHERE id = :task_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            // Upload de novos arquivos
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $upload_dir = 'uploads/';
                
                foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $original_filename = $_FILES['attachments']['name'][$key];
                        $file_size = $_FILES['attachments']['size'][$key];
                        $file_ext = pathinfo($original_filename, PATHINFO_EXTENSION);
                        $filename = uniqid() . '.' . $file_ext;
                        $file_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $query = "INSERT INTO attachments (task_id, filename, original_filename, file_path, file_size) 
                                      VALUES (:task_id, :filename, :original_filename, :file_path, :file_size)";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':task_id', $task_id);
                            $stmt->bindParam(':filename', $filename);
                            $stmt->bindParam(':original_filename', $original_filename);
                            $stmt->bindParam(':file_path', $file_path);
                            $stmt->bindParam(':file_size', $file_size);
                            $stmt->execute();
                        }
                    }
                }
            }
            
            header("Location: view_task.php?id=" . $task_id);
            exit();
        }
    }
}

// Buscar anexos existentes
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
    <title>Editar Tarefa</title>
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
                    <div class="card-header">
                        <h4>Editar Tarefa</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Título *</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Categoria</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Sem categoria</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $task['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descrição</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($task['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Data de Vencimento *</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $task['due_date']; ?>" required>
                            </div>
                            
                            <?php if (count($attachments) > 0): ?>
                                <div class="mb-3">
                                    <label class="form-label">Anexos Existentes:</label>
                                    <ul class="list-group">
                                        <?php foreach ($attachments as $attachment): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($attachment['original_filename']); ?>
                                                <a href="actions/delete_attachment.php?id=<?php echo $attachment['id']; ?>&task_id=<?php echo $task_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja excluir este anexo?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="attachments" class="form-label">Adicionar Novos Anexos</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Salvar Alterações
                                </button>
                                <a href="view_task.php?id=<?php echo $task_id; ?>" class="btn btn-secondary">
                                    <i class="bi bi-x"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
