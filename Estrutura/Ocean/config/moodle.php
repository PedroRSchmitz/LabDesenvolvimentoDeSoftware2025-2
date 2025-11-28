<?php
class MoodleAPI {
    private $moodle_url;
    private $token;
    
    public function __construct() {
        // Configure estas variáveis com os dados da sua instituição
        $this->moodle_url = "https://moodle.canoas.ifrs.edu.br"; // URL do Moodle
        $this->token = ""; // Token será configurado por usuário
    }
    
    public function setToken($token) {
        $this->token = $token;
    }
    
    // Função para fazer requisições à API do Moodle
    private function callAPI($function, $params = []) {
        if (empty($this->token)) {
            return ['error' => 'Token não configurado'];
        }
        
        $url = $this->moodle_url . "/webservice/rest/server.php";
        
        $params['wstoken'] = $this->token;
        $params['wsfunction'] = $function;
        $params['moodlewsrestformat'] = 'json';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['error' => $error];
        }
        
        return json_decode($response, true);
    }
    
    // Autenticar usuário no Moodle
    public function authenticate($username, $password) {
        $url = $this->moodle_url . "/login/token.php";
        
        $params = [
            'username' => $username,
            'password' => $password,
            'service' => 'moodle_mobile_app' // Serviço padrão do Moodle
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['token'])) {
            $this->token = $data['token'];
            return $data;
        }
        
        return ['error' => $data['error'] ?? 'Erro ao autenticar'];
    }
    
    // Obter informações do usuário
    public function getUserInfo() {
        return $this->callAPI('core_webservice_get_site_info');
    }
    
    // Buscar cursos do usuário
    public function getUserCourses($userid) {
        return $this->callAPI('core_enrol_get_users_courses', ['userid' => $userid]);
    }
    
    // Buscar assignments (tarefas) de um curso
    public function getCourseAssignments($courseid) {
        $result = $this->callAPI('mod_assign_get_assignments', ['courseids[0]' => $courseid]);
        return $result['courses'][0]['assignments'] ?? [];
    }
    
    // Buscar todas as tarefas do usuário
    public function getAllUserAssignments() {
        $userInfo = $this->getUserInfo();
        
        if (isset($userInfo['error'])) {
            return ['error' => $userInfo['error']];
        }
        
        $userid = $userInfo['userid'];
        $courses = $this->getUserCourses($userid);
        
        $allAssignments = [];
        
        if (!isset($courses['error'])) {
            foreach ($courses as $course) {
                $assignments = $this->getCourseAssignments($course['id']);
                foreach ($assignments as $assignment) {
                    $assignment['coursename'] = $course['fullname'];
                    $allAssignments[] = $assignment;
                }
            }
        }
        
        return $allAssignments;
    }
}
?>