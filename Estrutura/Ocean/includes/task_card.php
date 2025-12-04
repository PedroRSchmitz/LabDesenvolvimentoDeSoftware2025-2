<!-- Card de tarefa reutilizável -->
<div class="col-md-6 mb-3">
    <div class="card task-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0">
                    <?php echo htmlspecialchars($task['title']); ?>
                    <?php if ($task['is_from_moodle']): ?>
                    <span class="badge bg-info ms-1" title="Importado do Moodle">
                        <i class="bi bi-mortarboard"></i> Moodle
                    </span>
                    <?php endif; ?>
                </h5>
                <span
                    class="badge bg-<?php echo $task['status'] === 'feito' ? 'success' : ($task['status'] === 'cancelado' ? 'danger' : 'primary'); ?>">
                    <?php echo ucfirst($task['status']); ?>
                </span>
            </div>

            <?php if ($task['subject']): ?>
            <span class="badge bg-secondary mb-2">
                <i class="bi bi-book"></i> <?php echo htmlspecialchars($task['subject']); ?>
            </span>
            <?php endif; ?>

            <?php if ($task['category_name']): ?>
            <span class="badge mb-2" style="background-color: <?php echo $task['category_color']; ?>">
                <?php echo htmlspecialchars($task['category_name']); ?>
            </span>
            <?php endif; ?>

            <?php 
            $description = $task['description'];
            // Remover múltiplas quebras de linha (manter no máximo 2)
            $description = preg_replace('/\n{3,}/', "\n\n", $description);
            // Remover espaços em branco extras
            $description = trim($description);
            // Limitar a 150 caracteres
            if (strlen($description) > 150) {
                $description = substr($description, 0, 150);
                $has_more = true;
            } else {
                $has_more = false;
            }
            ?>

            <!-- Container com altura fixa para manter cards uniformes -->
            <div class="task-description">
                <p class="card-text">
                    <?php echo nl2br(htmlspecialchars($description)); ?><?php echo $has_more ? '...' : ''; ?></p>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($task['due_date'])); ?>
                    <?php if ($task['attachment_count'] > 0): ?>
                    <i class="bi bi-paperclip ms-2"></i> <?php echo $task['attachment_count']; ?>
                    <?php endif; ?>
                </small>
                <div class="btn-group btn-group-sm">
                    <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-outline-primary"
                        title="Ver detalhes">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php if ($task['status'] !== 'feito'): ?>
                    <a href="actions/update_status.php?id=<?php echo $task['id']; ?>&status=feito"
                        class="btn btn-outline-success" title="Marcar como feito">
                        <i class="bi bi-check-circle"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($task['status'] !== 'cancelado'): ?>
                    <a href="actions/update_status.php?id=<?php echo $task['id']; ?>&status=cancelado"
                        class="btn btn-outline-danger" title="Cancelar"
                        onclick="return confirm('Deseja cancelar esta tarefa?')">
                        <i class="bi bi-x-circle"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (!$task['is_from_moodle']): ?>
                    <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-outline-secondary"
                        title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>