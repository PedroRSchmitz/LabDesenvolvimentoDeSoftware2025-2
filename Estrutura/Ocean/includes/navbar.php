<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-check-square"></i> Ocean - Study Flow Sync
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-list-task"></i> Tarefas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="calendar.php">
                        <i class="bi bi-calendar3"></i> Calendário
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="categories.php">
                        <i class="bi bi-tags"></i> Categorias
                    </a>
                </li>
                <!-- Adicionar link para sincronização Moodle -->
                <?php
                // Verificar se usuário tem integração Moodle
                $db_check = new Database();
                $conn_check = $db_check->getConnection();
                $user_id_check = getUserId();
                $query_check = "SELECT moodle_token FROM users WHERE id = :user_id AND moodle_token IS NOT NULL";
                $stmt_check = $conn_check->prepare($query_check);
                $stmt_check->bindParam(':user_id', $user_id_check);
                $stmt_check->execute();
                $has_moodle = $stmt_check->rowCount() > 0;
                
                if ($has_moodle):
                ?>
                <li class="nav-item">
                    <a class="nav-link" href="sync_moodle.php">
                        <i class="bi bi-cloud-download"></i> Sincronizar Moodle
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo getUsername(); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>