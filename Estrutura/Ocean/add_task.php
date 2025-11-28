<?php
require_once 'config/session.php';
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

// Buscar categorias
$query = "SELECT * FROM categories WHERE user_id = :user_id ORDER BY name";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'] ?: null;
    $subject = trim($_POST['subject']);
    $due_date = $_POST['due_date'];
    
    if (empty($title)) {
        $error = 'O título é obrigatório';
    } else {
        $query = "INSERT INTO tasks (user_id, category_id, title, description, subject, due_date) 
                  VALUES (:user_id, :category_id, :title, :description, :subject, :due_date)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':due_date', $due_date);
        
        if ($stmt->execute()) {
            $task_id = $db->lastInsertId();
            
            // Upload de arquivos
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
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
            
            header("Location: dashboard.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Tarefa</title>
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
                        <h4>Adicionar Nova Tarefa</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Título *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Disciplina/Matéria</label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="Ex: Matemática, História, etc.">
                                <small class="form-text text-muted">Opcional: ajuda a organizar suas tarefas por matéria</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Categoria</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Sem categoria</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descrição</label>
                                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Data de Vencimento *</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="attachments" class="form-label">Anexos</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                                <small class="form-text text-muted">Você pode selecionar múltiplos arquivos</small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Salvar Tarefa
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
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
