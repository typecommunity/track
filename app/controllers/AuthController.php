<?php
/**
 * UTMTrack - Controller de Autenticação
 * Arquivo: app/controllers/AuthController.php
 */

class AuthController extends Controller {
    
    /**
     * Página de login
     */
    public function login() {
        // Se já estiver logado, redireciona
        if ($this->auth->check()) {
            if ($this->auth->isAdmin()) {
                $this->redirect('../admin/index.php');
            } else {
                $this->redirect('index.php?page=dashboard');
            }
            return;
        }
        
        // Se for POST, processa login
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processLogin();
        }
        
        // Mostra página de login
        $this->view('auth/login', [
            'config' => $this->config,
            'error' => $_GET['error'] ?? null
        ]);
    }
    
    /**
     * Processa login
     */
    private function processLogin() {
        $email = $this->sanitize($this->post('email'));
        $password = $this->post('password');
        $remember = $this->post('remember') === 'on';
        
        // Valida campos
        $errors = $this->validate([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->view('auth/login', [
                'config' => $this->config,
                'error' => 'Por favor, preencha todos os campos corretamente',
                'errors' => $errors,
                'old' => $_POST
            ]);
            return;
        }
        
        // Tenta fazer login
        $result = $this->auth->login($email, $password, $remember);
        
        if ($result['success']) {
            // Redireciona baseado no papel do usuário
            if ($result['user']['role'] === 'admin') {
                $this->redirect('../admin/index.php');
            } else {
                $this->redirect('index.php?page=dashboard');
            }
        } else {
            $this->view('auth/login', [
                'config' => $this->config,
                'error' => $result['message'],
                'old' => $_POST
            ]);
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        $this->auth->logout();
        $this->redirect('index.php?page=login');
    }
    
    /**
     * Página de registro
     */
    public function register() {
        // Se já estiver logado, redireciona
        if ($this->auth->check()) {
            $this->redirect('index.php?page=dashboard');
            return;
        }
        
        // Se for POST, processa registro
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processRegister();
        }
        
        // Mostra página de registro
        $this->view('auth/register', [
            'config' => $this->config
        ]);
    }
    
    /**
     * Processa registro
     */
    private function processRegister() {
        $data = [
            'name' => $this->sanitize($this->post('name')),
            'email' => $this->sanitize($this->post('email')),
            'password' => $this->post('password'),
            'password_confirm' => $this->post('password_confirm')
        ];
        
        // Valida campos
        $errors = $this->validate($data, [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'password_confirm' => 'required'
        ]);
        
        // Verifica se senhas coincidem
        if ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'As senhas não coincidem';
        }
        
        if (!empty($errors)) {
            $this->view('auth/register', [
                'config' => $this->config,
                'errors' => $errors,
                'old' => $_POST
            ]);
            return;
        }
        
        // Registra usuário
        $result = $this->auth->register($data);
        
        if ($result['success']) {
            // Faz login automático
            $this->auth->login($data['email'], $data['password']);
            $this->redirect('index.php?page=dashboard');
        } else {
            $this->view('auth/register', [
                'config' => $this->config,
                'error' => $result['message'],
                'old' => $_POST
            ]);
        }
    }
}