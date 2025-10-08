<?php
/**
 * UTMTrack - Controller Base
 */

class Controller {
    protected $db;
    protected $auth;
    protected $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
        $this->config = require dirname(__DIR__) . '/config/app.php';
    }
    
    /**
     * Carrega uma view
     */
    protected function view($view, $data = []) {
        extract($data);
        
        $viewPath = dirname(__DIR__) . '/app/views/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View não encontrada: {$view}");
        }
    }
    
    /**
     * Carrega uma view com layout
     */
    protected function render($view, $data = [], $layout = 'layouts/header') {
        extract($data);
        
        // Header
        $headerPath = dirname(__DIR__) . '/app/views/' . $layout . '.php';
        if (file_exists($headerPath)) {
            require $headerPath;
        }
        
        // Sidebar
        $sidebarPath = dirname(__DIR__) . '/app/views/layouts/sidebar.php';
        if (file_exists($sidebarPath)) {
            require $sidebarPath;
        }
        
        // View principal
        $viewPath = dirname(__DIR__) . '/app/views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View não encontrada: {$view}");
        }
        
        // Footer
        $footerPath = dirname(__DIR__) . '/app/views/layouts/footer.php';
        if (file_exists($footerPath)) {
            require $footerPath;
        }
    }
    
    /**
     * Retorna JSON
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redireciona para uma URL
     */
    protected function redirect($url, $params = []) {
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        header("Location: {$url}{$query}");
        exit;
    }
    
    /**
     * Valida dados do formulário
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $rulesList = explode('|', $rule);
            
            foreach ($rulesList as $r) {
                // Required
                if ($r === 'required' && empty($data[$field])) {
                    $errors[$field] = "O campo {$field} é obrigatório";
                    break;
                }
                
                // Email
                if ($r === 'email' && !empty($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "O campo {$field} deve ser um e-mail válido";
                    break;
                }
                
                // Min length
                if (strpos($r, 'min:') === 0) {
                    $min = (int)substr($r, 4);
                    if (!empty($data[$field]) && strlen($data[$field]) < $min) {
                        $errors[$field] = "O campo {$field} deve ter no mínimo {$min} caracteres";
                        break;
                    }
                }
                
                // Max length
                if (strpos($r, 'max:') === 0) {
                    $max = (int)substr($r, 4);
                    if (!empty($data[$field]) && strlen($data[$field]) > $max) {
                        $errors[$field] = "O campo {$field} deve ter no máximo {$max} caracteres";
                        break;
                    }
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitiza string
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
            return $data;
        }
        
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Helper para obter valor do POST
     */
    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Helper para obter valor do GET
     */
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Helper para base URL
     */
    protected function baseUrl($path = '') {
        return $this->config['base_url'] . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    /**
     * Helper para asset URL
     */
    protected function asset($path) {
        return $this->baseUrl('assets/' . ltrim($path, '/'));
    }
}