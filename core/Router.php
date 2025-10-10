<?php
/**
 * UTMTrack - Sistema de Rotas COMPLETO
 * Versão 3.1 - Corrigido com todas as rotas necessárias
 * 
 * Arquivo: core/Router.php
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
            // ========================================
            // AUTH
            // ========================================
            'login' => ['AuthController', 'login'],
            'logout' => ['AuthController', 'logout'],
            'register' => ['AuthController', 'register'],
            
            // ========================================
            // DASHBOARD
            // ========================================
            'dashboard' => ['DashboardController', 'index'],
            'resumo' => ['DashboardController', 'index'],
            
            // ========================================
            // CAMPANHAS - ROTAS PRINCIPAIS
            // ========================================
            'campanhas' => ['CampaignController', 'index'],
            'campanhas-meta' => ['CampaignController', 'meta'],
            'campanha-detalhes' => ['CampaignController', 'show'],
            'campanhas-export' => ['CampaignController', 'export'],
            
            // 🔥 ROTAS CRÍTICAS QUE FALTAVAM - CAMPANHAS
            'campanhas-sync' => ['CampaignController', $method === 'GET' ? 'sync' : 'syncAll'], // POST usa syncAll
            'campanhas-sync-all' => ['CampaignController', 'syncAll'],
            'campanhas-save-columns' => ['CampaignController', 'saveColumns'],
            'campanhas-update-field' => ['CampaignController', 'updateField'],
            'campanhas-bulk-action' => ['CampaignController', 'bulkAction'],
            'campanhas-duplicate' => ['CampaignController', 'duplicate'],
            
            // ========================================
            // CONJUNTOS DE ANÚNCIOS (AD SETS)
            // ========================================
            'conjuntos' => ['AdSetController', 'index'],
            'adsets' => ['AdSetController', 'index'],
            'conjunto-detalhes' => ['AdSetController', 'show'],
            'conjuntos-export' => ['AdSetController', 'export'],
            
            // 🔥 ROTAS CRÍTICAS QUE FALTAVAM - CONJUNTOS
            'conjuntos-sync' => ['AdSetController', $method === 'GET' ? 'sync' : 'syncAll'],
            'conjuntos-sync-all' => ['AdSetController', 'syncAll'],
            'conjuntos-save-columns' => ['AdSetController', 'saveColumns'],
            'conjuntos-update-field' => ['AdSetController', 'updateField'],
            
            // ========================================
            // ANÚNCIOS (ADS)
            // ========================================
            'anuncios' => ['AdController', 'index'],
            'ads' => ['AdController', 'index'],
            'anuncio-detalhes' => ['AdController', 'show'],
            'anuncio-preview' => ['AdController', 'preview'],
            'anuncios-export' => ['AdController', 'export'],
            
            // 🔥 ROTAS CRÍTICAS QUE FALTAVAM - ANÚNCIOS
            'anuncios-sync' => ['AdController', $method === 'GET' ? 'sync' : 'syncAll'],
            'anuncios-sync-all' => ['AdController', 'syncAll'],
            'anuncios-save-columns' => ['AdController', 'saveColumns'],
            'anuncios-update-field' => ['AdController', 'updateField'],
            
            // ========================================
            // META ADS (Legacy - Compatibilidade)
            // ========================================
            'meta' => ['MetaController', 'index'],
            'meta-contas' => ['MetaController', 'accounts'],
            'meta-campanhas' => ['MetaController', 'campaigns'],
            
            // ========================================
            // GOOGLE ADS
            // ========================================
            'google' => ['GoogleController', 'index'],
            
            // ========================================
            // INTEGRAÇÕES
            // ========================================
            'integracoes' => ['IntegrationController', 'index'],
            'integracoes-meta' => ['IntegrationController', 'meta'],
            'integracoes-meta-salvar' => ['IntegrationController', 'metaSave'],
            'integracoes-meta-save' => ['IntegrationController', 'metaSave'], // Alias
            'integracoes-meta-conectar' => ['IntegrationController', 'metaConnect'],
            'integracoes-meta-connect' => ['IntegrationController', 'metaConnect'], // Alias
            'integracoes-meta-contas' => ['IntegrationController', 'metaAccounts'],
            'integracoes-meta-accounts' => ['IntegrationController', 'metaAccounts'], // Alias
            'integracoes-meta-toggle' => ['IntegrationController', 'metaToggleAccount'],
            'integracoes-meta-sync' => ['IntegrationController', 'metaSync'],
            'integracoes-meta-remover' => ['IntegrationController', 'metaRemove'],
            'integracoes-webhook' => ['IntegrationController', 'webhook'],
            
            // ========================================
            // UTMs
            // ========================================
            'utms' => ['UtmController', 'index'],
            'utm-generate' => ['UtmController', 'generate'],
            'utm-delete' => ['UtmController', 'delete'],
            'utm-export' => ['UtmController', 'export'],
            'utms-scripts' => ['UtmController', 'scripts'],
            'utms-stats' => ['UtmController', 'stats'],
            
            // ========================================
            // REGRAS DE AUTOMAÇÃO
            // ========================================
            'regras' => ['RuleController', 'index'],
            'regra-create' => ['RuleController', 'create'],
            'regra-update' => ['RuleController', 'update'],
            'regra-delete' => ['RuleController', 'delete'],
            'regra-toggle' => ['RuleController', 'toggle'],
            
            // ========================================
            // PRODUTOS
            // ========================================
            'produtos' => ['ProductController', 'index'],
            'product-create' => ['ProductController', 'create'],
            'product-update' => ['ProductController', 'update'],
            'product-delete' => ['ProductController', 'delete'],
            'product-get' => ['ProductController', 'get'],
            
            // ========================================
            // VENDAS
            // ========================================
            'vendas' => ['SalesController', 'index'],
            'sales-create' => ['SalesController', 'create'],
            'sales-update' => ['SalesController', 'update'],
            'sales-delete' => ['SalesController', 'delete'],
            'sales-import' => ['SalesController', 'import'],
            
            // ========================================
            // WEBHOOKS
            // ========================================
            'webhooks' => ['WebhookController', 'index'],
            'webhook-create' => ['WebhookController', 'create'],
            'webhook-update' => ['WebhookController', 'update'],
            'webhook-delete' => ['WebhookController', 'delete'],
            'webhook-get' => ['WebhookController', 'get'],
            'webhook-logs' => ['WebhookController', 'logs'],
            
            // ========================================
            // RELATÓRIOS
            // ========================================
            'relatorios' => ['ReportController', 'index'],
            'report-export' => ['ReportController', 'export'],
            'report-generate' => ['ReportController', 'generate'],
            
            // ========================================
            // TAXAS E DESPESAS
            // ========================================
            'taxas' => ['TaxController', 'index'],
            'despesas' => ['ExpenseController', 'index'],
            
            // ========================================
            // ADMIN
            // ========================================
            'admin' => ['AdminController', 'dashboard'],
            'admin-clientes' => ['AdminController', 'clients'],
            'admin-configuracoes' => ['AdminController', 'settings'],
            
            // ========================================
            // API HELPERS (para AJAX)
            // ========================================
            'get-meta-accounts' => ['IntegrationController', 'getMetaAccountsJson'],
            'get-campaigns' => ['CampaignController', 'getCampaignsJson'],
            'get-adsets' => ['AdSetController', 'getAdSetsJson'],
            'get-ads' => ['AdController', 'getAdsJson'],
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
    private function callController($controllerName, $methodName) {
        $controllerPath = dirname(__DIR__) . '/app/controllers/' . $controllerName . '.php';
        
        if (!file_exists($controllerPath)) {
            // Tenta criar controller temporário para páginas "Em breve"
            if ($this->createTempController($controllerName, $methodName)) {
                return true;
            }
            
            // Se for requisição AJAX, retorna erro JSON
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Controller não encontrado: {$controllerName}"
                ]);
                exit;
            }
            
            $this->error("Controller não encontrado: {$controllerName}");
            return false;
        }
        
        require_once $controllerPath;
        
        if (!class_exists($controllerName)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Classe não encontrada: {$controllerName}"
                ]);
                exit;
            }
            
            $this->error("Classe do controller não encontrada: {$controllerName}");
            return false;
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $methodName)) {
            // Se o método não existe mas é syncAll, tenta chamar sync
            if ($methodName === 'syncAll' && method_exists($controller, 'sync')) {
                return call_user_func([$controller, 'sync']);
            }
            
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Método não encontrado: {$controllerName}@{$methodName}"
                ]);
                exit;
            }
            
            $this->error("Método não encontrado: {$controllerName}@{$methodName}");
            return false;
        }
        
        return call_user_func([$controller, $methodName]);
    }
    
    /**
     * Verifica se é requisição AJAX
     */
    private function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Cria controller temporário para páginas em desenvolvimento
     */
    private function createTempController($controllerName, $method) {
        // Controllers que podem usar view temporária
        $tempControllers = [
            'AdSetController' => 'campaigns/adsets',
            'AdController' => 'campaigns/ads'
        ];
        
        if (isset($tempControllers[$controllerName]) && $method === 'index') {
            $viewFile = dirname(__DIR__) . '/app/views/' . $tempControllers[$controllerName] . '.php';
            
            if (file_exists($viewFile)) {
                // Carrega configuração e auth
                $config = require dirname(__DIR__) . '/config/app.php';
                $auth = new Auth();
                $db = Database::getInstance();
                
                // Verifica autenticação
                if (!$auth->check()) {
                    header('Location: index.php?page=login');
                    exit;
                }
                
                // Dados padrão para views
                $user = $auth->user();
                $pageTitle = ucfirst(str_replace('Controller', '', $controllerName));
                
                // Dados específicos
                $adsets = [];
                $ads = [];
                $stats = [];
                $userColumns = null;
                
                // Inclui layout
                include dirname(__DIR__) . '/app/views/layout/header.php';
                include $viewFile;
                include dirname(__DIR__) . '/app/views/layout/footer.php';
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Exibe mensagem de erro
     */
    private function error($message) {
        http_response_code(500);
        
        // Se for requisição AJAX, retorna JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
            exit;
        }
        
        // Senão, exibe erro HTML
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Erro 500</title>";
        echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
        echo ".error{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
        echo "h1{color:#e74c3c;}a{color:#3498db;text-decoration:none;}</style></head>";
        echo "<body><div class='error'>";
        echo "<h1>Erro 500</h1>";
        echo "<p>{$message}</p>";
        echo "<p><a href='index.php?page=dashboard'>← Voltar ao Dashboard</a></p>";
        echo "</div></body></html>";
        exit;
    }
    
    /**
     * Página não encontrada
     */
    private function notFound() {
        http_response_code(404);
        
        // Se for requisição AJAX, retorna JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Página não encontrada'
            ]);
            exit;
        }
        
        // Senão, exibe 404 HTML
        echo "<!DOCTYPE html>";
        echo "<html><head><title>404 - Página não encontrada</title>";
        echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
        echo ".error{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);text-align:center;}";
        echo "h1{color:#e67e22;font-size:72px;margin:0;}h2{color:#7f8c8d;}";
        echo "a{display:inline-block;margin-top:20px;padding:10px 20px;background:#3498db;color:white;text-decoration:none;border-radius:5px;}</style></head>";
        echo "<body><div class='error'>";
        echo "<h1>404</h1>";
        echo "<h2>Página não encontrada</h2>";
        echo "<a href='index.php?page=dashboard'>Voltar ao Dashboard</a>";
        echo "</div></body></html>";
        exit;
    }
    
    /**
     * Middleware para verificar autenticação
     */
    public function authMiddleware() {
        session_start();
        
        $publicPages = ['login', 'register', 'logout'];
        $page = $_GET['page'] ?? 'login';
        
        if (!in_array($page, $publicPages) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }
}