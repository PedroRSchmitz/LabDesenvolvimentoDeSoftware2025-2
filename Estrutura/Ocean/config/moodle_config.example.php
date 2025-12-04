<?php
/**
 * CONFIGURAÇÃO DA INTEGRAÇÃO COM MOODLE
 * 
 * Copie este arquivo para moodle_config.php e configure com os dados da sua instituição
 */

// URL da instalação do Moodle (sem barra no final)
define('MOODLE_URL', 'https://moodle.suainstituicao.edu.br');

// Nome do serviço web (geralmente 'moodle_mobile_app')
define('MOODLE_SERVICE', 'moodle_mobile_app');

/**
 * INSTRUÇÕES DE CONFIGURAÇÃO NO MOODLE
 * 
 * Para habilitar a integração, o administrador do Moodle precisa:
 * 
 * 1. Ativar Web Services:
 *    - Vá em: Administração do site > Recursos avançados
 *    - Marque "Habilitar web services"
 * 
 * 2. Habilitar protocolo REST:
 *    - Vá em: Administração do site > Plugins > Web services > Gerenciar protocolos
 *    - Habilite "REST protocol"
 * 
 * 3. Criar um serviço de web service:
 *    - Vá em: Administração do site > Plugins > Web services > Serviços externos
 *    - Adicione um novo serviço ou use o existente "moodle_mobile_app"
 *    - Adicione as seguintes funções:
 *      - core_webservice_get_site_info
 *      - core_enrol_get_users_courses
 *      - mod_assign_get_assignments
 * 
 * 4. Permitir que usuários obtenham tokens:
 *    - Vá em: Administração do site > Plugins > Web services > Gerenciar tokens
 *    - Configure "Criar token via serviço de login"
 * 
 * IMPORTANTE: Cada usuário precisará fazer login com suas próprias credenciais
 * do Moodle para obter um token de acesso pessoal.
 */

/**
 * PERSONALIZAÇÃO (OPCIONAL)
 */

// Cores para a categoria Moodle (formato hexadecimal)
define('MOODLE_CATEGORY_COLOR', '#ff6b6b');

// Sincronização automática (em horas)
// 0 = desativado, usuário sincroniza manualmente
define('MOODLE_AUTO_SYNC_HOURS', 0);
?>
