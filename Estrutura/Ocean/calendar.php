<?php
require_once 'config/session.php';
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

// Buscar categorias para filtro
$query = "SELECT * FROM categories WHERE user_id = :user_id ORDER BY name";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT DISTINCT subject FROM tasks WHERE user_id = :user_id AND subject IS NOT NULL AND subject != '' ORDER BY subject";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$month = $_GET['month'] ?? date('Y-m');

// Buscar tarefas do mês
$query = "SELECT t.*, c.name as category_name, c.color as category_color
          FROM tasks t
          LEFT JOIN categories c ON t.category_id = c.id
          WHERE t.user_id = :user_id
          AND DATE_FORMAT(t.due_date, '%Y-%m') = :month";

if ($status_filter) {
    $query .= " AND t.status = :status";
}
if ($category_filter) {
    $query .= " AND t.category_id = :category_id";
}
if ($subject_filter) {
    $query .= " AND t.subject = :subject";
}

$query .= " ORDER BY t.due_date ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':month', $month);
if ($status_filter) {
    $stmt->bindParam(':status', $status_filter);
}
if ($category_filter) {
    $stmt->bindParam(':category_id', $category_filter);
}
if ($subject_filter) {
    $stmt->bindParam(':subject', $subject_filter);
}
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar tarefas por data
$tasks_by_date = [];
foreach ($tasks as $task) {
    $date = $task['due_date'];
    if (!isset($tasks_by_date[$date])) {
        $tasks_by_date[$date] = [];
    }
    $tasks_by_date[$date][] = $task;
}

// Gerar calendário
$first_day = strtotime($month . '-01');
$days_in_month = date('t', $first_day);
$first_weekday = date('w', $first_day);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário - Gerenciador de Tarefas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Calendário de Tarefas</h2>
            </div>
        </div>
        
        <!-- Controles do Calendário -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <a href="?month=<?php echo date('Y-m', strtotime($month . '-01 -1 month')); ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>&subject=<?php echo $subject_filter; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-chevron-left"></i> Anterior
                        </a>
                        <span class="mx-3 fw-bold"><?php echo strftime('%B de %Y', $first_day); ?></span>
                        <a href="?month=<?php echo date('Y-m', strtotime($month . '-01 +1 month')); ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>&subject=<?php echo $subject_filter; ?>" class="btn btn-outline-primary">
                            Próximo <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                    <a href="?month=<?php echo date('Y-m'); ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>&subject=<?php echo $subject_filter; ?>" class="btn btn-secondary">
                        Hoje
                    </a>
                </div>
                
                <!-- Filtros -->
                <form method="GET" class="row g-2">
                    <input type="hidden" name="month" value="<?php echo $month; ?>">
                    <div class="col-md-3">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos os Status</option>
                            <option value="fazendo" <?php echo $status_filter === 'fazendo' ? 'selected' : ''; ?>>Fazendo</option>
                            <option value="feito" <?php echo $status_filter === 'feito' ? 'selected' : ''; ?>>Feito</option>
                            <option value="cancelado" <?php echo $status_filter === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">Todas as Categorias</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="subject" class="form-select" onchange="this.form.submit()">
                            <option value="">Todas as Disciplinas</option>
                            <?php foreach ($subjects as $subj): ?>
                                <option value="<?php echo htmlspecialchars($subj['subject']); ?>" <?php echo $subject_filter === $subj['subject'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subj['subject']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($status_filter || $category_filter || $subject_filter): ?>
                        <div class="col-md-2">
                            <a href="?month=<?php echo $month; ?>" class="btn btn-secondary">Limpar Filtros</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Calendário -->
        <div class="calendar">
            <div class="calendar-header">
                <div class="calendar-day-header">Dom</div>
                <div class="calendar-day-header">Seg</div>
                <div class="calendar-day-header">Ter</div>
                <div class="calendar-day-header">Qua</div>
                <div class="calendar-day-header">Qui</div>
                <div class="calendar-day-header">Sex</div>
                <div class="calendar-day-header">Sáb</div>
            </div>
            <div class="calendar-body">
                <?php
                // Dias vazios antes do primeiro dia
                for ($i = 0; $i < $first_weekday; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                
                // Dias do mês
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $current_date = date('Y-m-d', strtotime($month . '-' . $day));
                    $is_today = $current_date === date('Y-m-d');
                    $day_tasks = $tasks_by_date[$current_date] ?? [];
                    
                    echo '<div class="calendar-day' . ($is_today ? ' today' : '') . '">';
                    echo '<div class="day-number">' . $day . '</div>';
                    
                    foreach ($day_tasks as $task) {
                        $status_class = $task['status'] === 'feito' ? 'success' : ($task['status'] === 'cancelado' ? 'danger' : 'primary');
                        echo '<a href="view_task.php?id=' . $task['id'] . '" class="task-badge badge bg-' . $status_class . ' mb-1" title="' . htmlspecialchars($task['title']) . '">';
                        echo htmlspecialchars(substr($task['title'], 0, 20)) . (strlen($task['title']) > 20 ? '...' : '');
                        echo '</a>';
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Botão flutuante adicionar tarefa -->
    <a href="add_task.php" class="btn btn-primary btn-add-task">
        <i class="bi bi-plus-lg"></i> Adicionar Tarefa
    </a>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
