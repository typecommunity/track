<?php
/**
 * UTMTrack - Sistema de Rotas
 */

class Router {
    private $routes = [];
    
    /**
     * Adiciona rota GET
     */
    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }
    
    /**
     * Adiciona rota POST
     */
    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }
    
    /**
     * Processa a requisição
     */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $page = $_GET['page'] ?? 'login';
        
        // Remove barra final
        $page = rtrim($page, '/');
        
        // Verifica se existe rota registrada
        if (isset($this->routes[$method][$page])) {
            return $this->handleRoute($this->routes[$method][$page]);
        }
        
        // Rotas padrão baseadas em página
        return $this->handleDefaultRoute($page, $method);
    }
    
    /**
     * Processa handler da rota
     */
    private function handleRoute($handler) {
        if (is_callable($handler)) {
            return call_user_func($handler);
        }
        
        if (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);
            return $this->callController($controller, $method);
        }
        
        return false;
    }
    
    /**
     * Rotas padrão do sistema
     */
    private function handleDefaultRoute($page, $method) {
        // Mapa de páginas para controllers
        $routes = [
            // Auth
            'login' => ['AuthController', 'login'],
            'logout' => ['AuthController', 'logout'],
            'register' => ['AuthController', 'register'],
            
            // Dashboard
            'dashboard' => ['DashboardController', 'index'],
            'resumo' => ['DashboardController', 'index'],
            
            // Meta Ads (Legacy - manter por compatibilidade)
            'meta' => ['MetaController', 'index'],
            'meta-contas' => ['MetaController', 'accounts'],
            'meta-campanhas' => ['MetaController', 'campaigns'],
            
            // Google Ads
            'google' => ['GoogleController', 'index'],
            
            // Integrações (NOVO)
            'integracoes' => ['IntegrationController', 'index'],
            'integracoes-meta' => ['IntegrationController', 'meta'],
            'integracoes-meta-salvar' => ['IntegrationController', 'metaSave'],
            'integracoes-meta-conectar' => ['IntegrationController', 'metaConnect'],
            'integracoes-meta-contas' => ['IntegrationController', 'metaAccounts'],
            'integracoes-meta-toggle' => ['IntegrationController', 'metaToggleAccount'],
            'integracoes-meta-sync' => ['IntegrationController', 'metaSync'],
            'integracoes-meta-remover' => ['IntegrationController', 'metaRemove'],
            'integracoes-webhook' => ['IntegrationController', 'webhook'],
            
            // UTMs
            'utms' => ['UtmController', 'index'],
            'utm-generate' => ['UtmController', 'generate'],
            'utm-delete' => ['UtmController', 'delete'],
            'utm-export' => ['UtmController', 'export'],
            'utms-scripts' => ['UtmController', 'scripts'],
            'utms-stats' => ['UtmController', 'stats'],
            
            // Regras de Automação
            'regras' => ['RuleController', 'index'],
            
            // Produtos
            'produtos' => ['ProductController', 'index'],
            'product-create' => ['ProductController', 'create'],
            'product-update' => ['ProductController', 'update'],
            'product-delete' => ['ProductController', 'delete'],
            'product-get' => ['ProductController', 'get'],
            
            // Webhooks
            'webhooks' => ['WebhookController', 'index'],
            'webhook-create' => ['WebhookController', 'create'],
            'webhook-update' => ['WebhookController', 'update'],
            'webhook-delete' => ['WebhookController', 'delete'],
            'webhook-get' => ['WebhookController', 'get'],
            'webhook-logs' => ['WebhookController', 'logs'],
            
            // Relatórios
            'relatorios' => ['ReportController', 'index'],
            'report-export' => ['ReportController', 'export'],
            
            // Taxas e Despesas
            'taxas' => ['TaxController', 'index'],
            'despesas' => ['ExpenseController', 'index'],
            
            // Admin
            'admin' => ['AdminController', 'dashboard'],
            'admin-clientes' => ['AdminController', 'clients'],
            'admin-configuracoes' => ['AdminController', 'settings'],
        ];
        
        if (isset($routes[$page])) {
            list($controller, $method) = $routes[$page];
            return $this->callController($controller, $method);
        }
        
        // Página não encontrada
        $this->notFound();
    }
    
    /**
     * Chama método do controller
     */
    private function callController($controllerName, $method) {
        $controllerPath = dirname(__DIR__) . '/app/controllers/' . $controllerName . '.php';
        
        if (!file_exists($controllerPath)) {
            die("Controller não encontrado: {$controllerName}");
        }
        
        require_once $controllerPath;
        
        if (!class_exists($controllerName)) {
            die("Classe do controller não encontrada: {$controllerName}");
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $method)) {
            die("Método não encontrado: {$controllerName}@{$method}");
        }
        
        return call_user_func([$controller, $method]);
    }
    
    /**
     * Página não encontrada
     */
    private function notFound() {
        http_response_code(404);
        echo "Página não encontrada";
        exit;
    }
}