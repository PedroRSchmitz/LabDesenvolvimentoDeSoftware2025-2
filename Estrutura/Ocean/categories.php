<?php
require_once 'config/session.php';
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

$error = '';
$success = '';

// Adicionar categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $color = $_POST['color'];
    
    if (empty($name)) {
        $error = 'O nome da categoria é obrigatório';
    } else {
        $query = "INSERT INTO categories (user_id, name, color) VALUES (:user_id, :name, :color)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':color', $color);
        
        if ($stmt->execute()) {
            $success = 'Categoria adicionada com sucesso!';
        }
    }
}

// Buscar categorias
$query = "SELECT c.*, COUNT(t.id) as task_count 
          FROM categories c
          LEFT JOIN tasks t ON c.id = t.category_id
          WHERE c.user_id = :user_id
          GROUP BY c.id
          ORDER BY c.name";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Gerenciar Categorias</h2>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Adicionar Categoria</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="color" class="form-label">Cor</label>
                                <input type="color" class="form-control form-control-color" id="color" name="color" value="#007bff">
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary w-100">
                                <i class="bi bi-plus"></i> Adicionar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Minhas Categorias</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($categories as $cat): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge" style="background-color: <?php echo $cat['color']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </span>
                                        <small class="text-muted ms-2"><?php echo $cat['task_count']; ?> tarefa(s)</small>
                                    </div>
                                    <a href="actions/delete_category.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deseja excluir esta categoria?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
