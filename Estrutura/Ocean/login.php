<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/moodle.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_type'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_POST['login_type'] === 'moodle') {
        $moodle_username = trim($_POST['moodle_username']);
        $moodle_password = $_POST['moodle_password'];
        
        $moodleAPI = new MoodleAPI();
        $auth_result = $moodleAPI->authenticate($moodle_username, $moodle_password);
        
        if (isset($auth_result['token'])) {
            $moodleAPI->setToken($auth_result['token']);
            $userInfo = $moodleAPI->getUserInfo();
            
            if (!isset($userInfo['error'])) {
                // Verificar se usuário já existe
                $query = "SELECT id FROM users WHERE moodle_userid = :moodle_userid OR username = :username";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':moodle_userid', $userInfo['userid']);
                $stmt->bindParam(':username', $userInfo['username']);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Atualizar token do usuário existente
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $query = "UPDATE users SET moodle_token = :token, moodle_userid = :moodle_userid, 
                             moodle_username = :moodle_username WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':token', $auth_result['token']);
                    $stmt->bindParam(':moodle_userid', $userInfo['userid']);
                    $stmt->bindParam(':moodle_username', $userInfo['username']);
                    $stmt->bindParam(':id', $user['id']);
                    $stmt->execute();
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $userInfo['username'];
                    header("Location: sync_moodle.php");
                    exit();
                } else {
                    // Criar novo usuário
                    $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $query = "INSERT INTO users (username, email, password, moodle_token, moodle_userid, moodle_username) 
                             VALUES (:username, :email, :password, :token, :moodle_userid, :moodle_username)";
                    $stmt = $db->prepare($query);
                    $email = $userInfo['email'] ?? $userInfo['username'] . '@moodle.local';
                    $stmt->bindParam(':username', $userInfo['username']);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $random_password);
                    $stmt->bindParam(':token', $auth_result['token']);
                    $stmt->bindParam(':moodle_userid', $userInfo['userid']);
                    $stmt->bindParam(':moodle_username', $userInfo['username']);
                    $stmt->execute();
                    
                    $_SESSION['user_id'] = $db->lastInsertId();
                    $_SESSION['username'] = $userInfo['username'];
                    header("Location: sync_moodle.php");
                    exit();
                }
            } else {
                $error = 'Erro ao obter informações do Moodle';
            }
        } else {
            $error = 'Credenciais do Moodle inválidas';
        }
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        $query = "SELECT id, username, password FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit();
            }
        }
        
        $error = 'Usuário ou senha incorretos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gerenciador de Tarefas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Login</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <!-- Tabs para escolher tipo de login -->
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="local-tab" data-bs-toggle="tab" data-bs-target="#local" type="button">
                                    Login Local
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="moodle-tab" data-bs-toggle="tab" data-bs-target="#moodle" type="button">
                                    Login Moodle
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content">
                            <!-- Login tradicional -->
                            <div class="tab-pane fade show active" id="local" role="tabpanel">
                                <form method="POST">
                                    <input type="hidden" name="login_type" value="local">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Usuário</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Senha</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                                </form>
                            </div>
                            
                            <!-- Login via Moodle -->
                            <div class="tab-pane fade" id="moodle" role="tabpanel">
                                <form method="POST">
                                    <input type="hidden" name="login_type" value="moodle">
                                    <div class="alert alert-info">
                                        <small>Entre com suas credenciais do Moodle da instituição</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="moodle_username" class="form-label">Usuário Moodle</label>
                                        <input type="text" class="form-control" id="moodle_username" name="moodle_username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="moodle_password" class="form-label">Senha Moodle</label>
                                        <input type="password" class="form-control" id="moodle_password" name="moodle_password" required>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Entrar com Moodle</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="register.php">Não tem conta? Registre-se</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
