<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/moodle.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

// Buscar token do Moodle do usuário
$query = "SELECT moodle_token, moodle_userid FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$error = '';
$synced_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || !isset($_SESSION['first_sync_done'])) {
    if ($user && $user['moodle_token']) {
        $moodleAPI = new MoodleAPI();
        $moodleAPI->setToken($user['moodle_token']);
        
        // Buscar tarefas do Moodle
        $assignments = $moodleAPI->getAllUserAssignments();
        
        if (isset($assignments['error'])) {
            $error = 'Erro ao sincronizar com Moodle: ' . $assignments['error'];
        } else {
            // Obter ou criar categoria "Moodle"
            $query = "SELECT id FROM categories WHERE user_id = :user_id AND name = 'Moodle'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $moodle_category = $stmt->fetch(PDO::FETCH_ASSOC);
                $category_id = $moodle_category['id'];
            } else {
                $query = "INSERT INTO categories (user_id, name, color) VALUES (:user_id, 'Moodle', '#6c757d')";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $category_id = $db->lastInsertId();
            }
            
            $current_time = time();
            
            // Sincronizar tarefas
            foreach ($assignments as $assignment) {
                // Pular se não tiver data de vencimento
                if (empty($assignment['duedate'])) {
                    continue;
                }
                
                if ($assignment['duedate'] < $current_time) {
                    continue;
                }
                
                // Nota: Esta verificação depende da API do Moodle retornar status de conclusão
                // Algumas instalações do Moodle podem não ter esta informação
                if (isset($assignment['completionstate']) && $assignment['completionstate'] == 1) {
                    continue; // Tarefa concluída, não sincronizar
                }
                
                // Verificar se a tarefa já existe
                $query = "SELECT id FROM tasks WHERE moodle_id = :moodle_id AND user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':moodle_id', $assignment['id']);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $due_date = date('Y-m-d', $assignment['duedate']);
                $description = strip_tags($assignment['intro']);
                $subject = $assignment['coursename'] ?? 'Sem disciplina';
                
                if ($stmt->rowCount() > 0) {
                    // Atualizar tarefa existente
                    $task = $stmt->fetch(PDO::FETCH_ASSOC);
                    $query = "UPDATE tasks SET title = :title, description = :description, 
                             due_date = :due_date, subject = :subject, moodle_sync_at = NOW() 
                             WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':title', $assignment['name']);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':due_date', $due_date);
                    $stmt->bindParam(':subject', $subject);
                    $stmt->bindParam(':id', $task['id']);
                    $stmt->execute();
                } else {
                    // Criar nova tarefa
                    $query = "INSERT INTO tasks (user_id, category_id, title, description, due_date, subject,
                             moodle_id, moodle_course_id, is_from_moodle, moodle_sync_at, status) 
                             VALUES (:user_id, :category_id, :title, :description, :due_date, :subject,
                             :moodle_id, :moodle_course_id, 1, NOW(), 'fazendo')";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':category_id', $category_id);
                    $stmt->bindParam(':title', $assignment['name']);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':due_date', $due_date);
                    $stmt->bindParam(':subject', $subject);
                    $stmt->bindParam(':moodle_id', $assignment['id']);
                    $stmt->bindParam(':moodle_course_id', $assignment['course']);
                    $stmt->execute();
                    $synced_count++;
                }
            }
            
            // Atualizar data da última sincronização
            $query = "UPDATE users SET last_moodle_sync = NOW() WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $message = "Sincronização concluída! $synced_count novas tarefas adicionadas.";
            $_SESSION['first_sync_done'] = true;
            
            if (isset($_POST['manual_sync'])) {
                header("Location: sync_moodle.php?success=1");
                exit();
            }
        }
    } else {
        $error = 'Token do Moodle não encontrado. Faça login novamente com o Moodle.';
    }
}

// Buscar data da última sincronização
$query = "SELECT last_moodle_sync FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronizar Moodle - Gerenciador de Tarefas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">
                            <i class="bi bi-cloud-download"></i> Sincronização com Moodle
                        </h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Sincronização manual concluída com sucesso!
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle"></i> Como funciona?</h5>
                            <p class="mb-2">
                                Suas tarefas do Moodle serão automaticamente importadas para o sistema. 
                                Você pode sincronizar manualmente a qualquer momento para buscar novas tarefas.
                            </p>
                            <p class="mb-0">
                                <strong>Nota:</strong> Apenas tarefas pendentes e dentro do prazo serão sincronizadas.
                            </p>
                        </div>
                        
                        <?php if ($user_data['last_moodle_sync']): ?>
                            <div class="alert alert-secondary">
                                <strong>Última sincronização:</strong> 
                                <?php echo date('d/m/Y H:i', strtotime($user_data['last_moodle_sync'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="text-center">
                            <input type="hidden" name="manual_sync" value="1">
                            <button type="submit" class="btn btn-primary btn-lg mb-3">
                                <i class="bi bi-arrow-repeat"></i> Sincronizar Agora
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-house"></i> Ir para Dashboard
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
