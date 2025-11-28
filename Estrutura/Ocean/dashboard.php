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

$active_tab = $_GET['tab'] ?? 'fazendo';

// Filtros
$category_filter = $_GET['category'] ?? '';
$moodle_filter = $_GET['moodle'] ?? '';
$sort_by = $_GET['sort'] ?? 'date'; // 'date' ou 'subject'

$query = "SELECT t.*, c.name as category_name, c.color as category_color,
          (SELECT COUNT(*) FROM attachments WHERE task_id = t.id) as attachment_count
          FROM tasks t
          LEFT JOIN categories c ON t.category_id = c.id
          WHERE t.user_id = :user_id AND t.status = :status";

if ($category_filter) {
    $query .= " AND t.category_id = :category_id";
}
if ($moodle_filter === 'yes') {
    $query .= " AND t.is_from_moodle = 1";
} elseif ($moodle_filter === 'no') {
    $query .= " AND t.is_from_moodle = 0";
}

if ($sort_by === 'subject') {
    $query .= " ORDER BY t.subject ASC, t.due_date ASC, t.created_at DESC";
} else {
    $query .= " ORDER BY t.due_date ASC, t.created_at DESC";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':status', $active_tab);
if ($category_filter) {
    $stmt->bindParam(':category_id', $category_filter);
}
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query_count = "SELECT status, COUNT(*) as count FROM tasks WHERE user_id = :user_id GROUP BY status";
$stmt_count = $db->prepare($query_count);
$stmt_count->bindParam(':user_id', $user_id);
$stmt_count->execute();
$status_counts = [];
while ($row = $stmt_count->fetch(PDO::FETCH_ASSOC)) {
    $status_counts[$row['status']] = $row['count'];
}

$tasks_by_subject = [];
if ($sort_by === 'subject') {
    foreach ($tasks as $task) {
        $subject = $task['subject'] ?: 'Sem disciplina';
        if (!isset($tasks_by_subject[$subject])) {
            $tasks_by_subject[$subject] = [];
        }
        $tasks_by_subject[$subject][] = $task;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gerenciador de Tarefas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Minhas Tarefas</h2>
            </div>
        </div>

        <!-- Sistema de abas -->
        <ul class="nav nav-tabs mb-3" id="taskTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $active_tab === 'fazendo' ? 'active' : ''; ?>"
                    href="?tab=fazendo&category=<?php echo $category_filter; ?>&moodle=<?php echo $moodle_filter; ?>&sort=<?php echo $sort_by; ?>">
                    <i class="bi bi-play-circle"></i> Fazendo
                    <span class="badge bg-primary"><?php echo $status_counts['fazendo'] ?? 0; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $active_tab === 'feito' ? 'active' : ''; ?>"
                    href="?tab=feito&category=<?php echo $category_filter; ?>&moodle=<?php echo $moodle_filter; ?>&sort=<?php echo $sort_by; ?>">
                    <i class="bi bi-check-circle"></i> Concluídas
                    <span class="badge bg-success"><?php echo $status_counts['feito'] ?? 0; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $active_tab === 'cancelado' ? 'active' : ''; ?>"
                    href="?tab=cancelado&category=<?php echo $category_filter; ?>&moodle=<?php echo $moodle_filter; ?>&sort=<?php echo $sort_by; ?>">
                    <i class="bi bi-x-circle"></i> Canceladas
                    <span class="badge bg-danger"><?php echo $status_counts['cancelado'] ?? 0; ?></span>
                </a>
            </li>
        </ul>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-12">
                <form method="GET" class="row g-2">
                    <!-- Manter a aba ativa nos filtros -->
                    <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">

                    <div class="col-md-3">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">Todas as Categorias</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="moodle" class="form-select" onchange="this.form.submit()">
                            <option value="">Todas as Origens</option>
                            <option value="yes" <?php echo $moodle_filter === 'yes' ? 'selected' : ''; ?>>Apenas Moodle
                            </option>
                            <option value="no" <?php echo $moodle_filter === 'no' ? 'selected' : ''; ?>>Apenas Locais
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="date" <?php echo $sort_by === 'date' ? 'selected' : ''; ?>>
                                <i class="bi bi-calendar"></i> Ordenar por Data
                            </option>
                            <option value="subject" <?php echo $sort_by === 'subject' ? 'selected' : ''; ?>>
                                <i class="bi bi-book"></i> Ordenar por Disciplina
                            </option>
                        </select>
                    </div>
                    <?php if ($category_filter || $moodle_filter): ?>
                    <div class="col-md-2">
                        <a href="dashboard.php?tab=<?php echo $active_tab; ?>&sort=<?php echo $sort_by; ?>"
                            class="btn btn-secondary">Limpar Filtros</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Lista de Tarefas -->
        <div class="row">
            <?php if (count($tasks) > 0): ?>
            <?php if ($sort_by === 'subject'): ?>
            <!-- Exibir tarefas agrupadas por disciplina -->
            <?php foreach ($tasks_by_subject as $subject => $subject_tasks): ?>
            <div class="col-md-12">
                <div class="subject-separator">
                    <i class="bi bi-book"></i>
                    <h4><?php echo htmlspecialchars($subject); ?></h4>
                    <span class="subject-count"><?php echo count($subject_tasks); ?> tarefa(s)</span>
                </div>
            </div>
            <?php foreach ($subject_tasks as $task): ?>
            <?php include 'includes/task_card.php'; ?>
            <?php endforeach; ?>
            <?php endforeach; ?>
            <?php else: ?>
            <!-- Exibir tarefas ordenadas por data -->
            <?php foreach ($tasks as $task): ?>
            <?php include 'includes/task_card.php'; ?>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php else: ?>
            <div class="col-md-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> Nenhuma tarefa
                    <?php echo $active_tab === 'fazendo' ? 'em andamento' : ($active_tab === 'feito' ? 'concluída' : 'cancelada'); ?>
                    encontrada.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botão flutuante adicionar tarefa -->
    <a href="add_task.php" class="btn btn-primary btn-add-task">
        <i class="bi bi-plus-lg"></i> Adicionar Tarefa
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>