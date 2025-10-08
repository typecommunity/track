<?php
/**
 * UTMTrack - Classe de Autenticação
 * Arquivo: core/Auth.php
 */

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Inicia sessão se não estiver iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Realiza login do usuário
     */
    public function login($email, $password, $remember = false) {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = :email AND status = 'active'",
            ['email' => $email]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'E-mail ou senha incorretos'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'E-mail ou senha incorretos'];
        }
        
        // Cria sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // Atualiza último login
        $this->db->update('users', 
            ['updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Faz logout do usuário
     */
    public function logout() {
        session_destroy();
        return true;
    }
    
    /**
     * Verifica se usuário está autenticado
     */
    public function check() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Verifica se usuário é admin
     */
    public function isAdmin() {
        return $this->check() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Verifica se usuário é cliente
     */
    public function isClient() {
        return $this->check() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';
    }
    
    /**
     * Retorna usuário autenticado
     */
    public function user() {
        if (!$this->check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
        ];
    }
    
    /**
     * Retorna ID do usuário autenticado
     */
    public function id() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Registra novo usuário
     */
    public function register($data) {
        // Valida e-mail
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'E-mail inválido'];
        }
        
        // Verifica se e-mail já existe
        $exists = $this->db->fetch(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $data['email']]
        );
        
        if ($exists) {
            return ['success' => false, 'message' => 'E-mail já cadastrado'];
        }
        
        // Cria hash da senha
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insere usuário
        $userId = $this->db->insert('users', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'client',
            'status' => 'active'
        ]);
        
        if ($userId) {
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar usuário'];
    }
    
    /**
     * Middleware para proteger rotas
     */
    public function middleware($requiredRole = null) {
        if (!$this->check()) {
            header('Location: ../public/index.php?page=login');
            exit;
        }
        
        if ($requiredRole && $_SESSION['user_role'] !== $requiredRole) {
            header('Location: ../public/index.php?page=unauthorized');
            exit;
        }
    }
}