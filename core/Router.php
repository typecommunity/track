<?php
/**
 * UTMTrack - Sistema de Rotas COMPLETO
 * Versão 5.9 - Otimizado e Corrigido (V3.0 - 150+ campos)
 * 
 * Melhorias nesta versão:
 * - ✅ Corrigida lógica ternária com $method inexistente
 * - ✅ authMiddleware agora é chamado automaticamente
 * - ✅ session_start() com verificação de duplicação
 * - ✅ Mapeamento de controllers V2/V3 mais eficiente
 * - ✅ Separação clara entre rotas GET e POST
 * - ✅ Constantes para configurações
 * - ✅ Melhor documentação inline
 * - ✅ Mantém 100% das funcionalidades originais
 * 
 * Arquivo: core/Router.php
 */

class Router {
    
    // =========================================================================
    // PROPRIEDADES
    // =========================================================================
    
    private $routes = [];
    private $controllerAliases = [];
    private $publicPages = [];
    
    // =========================================================================
    // CONSTRUTOR - INICIALIZAÇÃO
    // =========================================================================
    
    public function __construct() {
        // Define controllers V2/V3 disponíveis
        $this->controllerAliases = [
            'CampaignController' => 'CampaignControllerV2',
            'AdSetController' => 'AdSetControllerV2',
            'AdController' => 'AdControllerV2',
        ];
        
        // Define páginas públicas (sem autenticação)
        $this->publicPages = ['login', 'register', 'logout'];
    }
    
    // =========================================================================
    // MÉTODOS PÚBLICOS - REGISTRO DE ROTAS
    // =========================================================================
    
    /**
     * Adiciona rota GET
     */
    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }
    
    /**
     * Adiciona rota POST
     */
    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }
    
    /**
     * Adiciona rota para múltiplos métodos
     */
    public function any($path, $handler) {
        $this->routes['GET'][$path] = $handler;
        $this->routes['POST'][$path] = $handler;
        return $this;
    }
    
    // =========================================================================
    // DISPATCH - PROCESSAMENTO DE REQUISIÇÕES
    // =========================================================================
    
    /**
     * Processa a requisição
     * Este é o método principal que roteia todas as requisições
     */
    public function dispatch() {
        // Middleware de autenticação
        $this->authMiddleware();
        
        $method = $_SERVER['REQUEST_METHOD'];
        $page = $_GET['page'] ?? 'login';
        
        // Remove barra final se existir
        $page = rtrim($page, '/');
        
        // Log para debug
        error_log("[ROUTER] 🔄 Processando: {$method} {$page}");
        
        // Verifica se existe rota customizada registrada
        if (isset($this->routes[$method][$page])) {
            error_log("[ROUTER] ✅ Rota customizada encontrada");
            return $this->handleRoute($this->routes[$method][$page]);
        }
        
        // Usa rotas padrão do sistema
        return $this->handleDefaultRoute($page, $method);
    }
    
    /**
     * Processa handler da rota
     */
    private function handleRoute($handler) {
        // Handler é uma função anônima
        if (is_callable($handler)) {
            return call_user_func($handler);
        }
        
        // Handler é string no formato "Controller@method"
        if (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);
            return $this->callController($controller, $method);
        }
        
        error_log("[ROUTER] ❌ Handler inválido");
        return false;
    }
    
    // =========================================================================
    // ROTAS PADRÃO DO SISTEMA
    // =========================================================================
    
    /**
     * Define e processa rotas padrão do sistema
     * Todas as rotas do UTMTrack estão organizadas aqui
     */
    private function handleDefaultRoute($page, $method) {
        
        // Mapa completo de rotas do sistema
        $routes = $this->getSystemRoutes($method);
        
        // Verifica se a rota existe
        if (isset($routes[$page])) {
            list($controller, $controllerMethod) = $routes[$page];
            error_log("[ROUTER] ✅ Rota padrão encontrada: {$controller}@{$controllerMethod}");
            return $this->callController($controller, $controllerMethod);
        }
        
        // Página não encontrada
        error_log("[ROUTER] ❌ Rota não encontrada: {$page}");
        $this->notFound();
    }
    
    /**
     * Retorna mapa completo de rotas do sistema
     * Organizado por categorias para melhor manutenção
     */
    private function getSystemRoutes($method) {
        return array_merge(
            $this->getAuthRoutes(),
            $this->getDashboardRoutes(),
            $this->getCampaignRoutes($method),
            $this->getAdSetRoutes($method),
            $this->getAdRoutes($method),
            $this->getMetaRoutes(),
            $this->getGoogleRoutes(),
            $this->getIntegrationRoutes(),
            $this->getUtmRoutes(),
            $this->getRuleRoutes(),
            $this->getProductRoutes(),
            $this->getSalesRoutes(),
            $this->getWebhookRoutes(),
            $this->getReportRoutes(),
            $this->getTaxRoutes(),
            $this->getExpenseRoutes(),
            $this->getHelpRoutes(),
            $this->getAdminRoutes(),
            $this->getApiRoutes()
        );
    }
    
    // =========================================================================
    // ROTAS POR CATEGORIA
    // =========================================================================
    
    /**
     * Rotas de Autenticação
     */
    private function getAuthRoutes() {
        return [
            'login' => ['AuthController', 'login'],
            'logout' => ['AuthController', 'logout'],
            'register' => ['AuthController', 'register'],
        ];
    }
    
    /**
     * Rotas de Dashboard
     */
    private function getDashboardRoutes() {
        return [
            'dashboard' => ['DashboardController', 'index'],
            'dashboard-debug' => ['DashboardController', 'debug'],
            'resumo' => ['DashboardController', 'index'],
        ];
    }
    
    /**
     * Rotas de Campanhas (V3.0 - 150+ campos)
     */
    private function getCampaignRoutes($method) {
        return [
            // Principais
            'campanhas' => ['CampaignController', 'index'],
            'campanhas-meta' => ['CampaignController', 'meta'],
            'campanha-detalhes' => ['CampaignController', 'show'],
            'campanhas-export' => ['CampaignController', 'export'],
            
            // Sincronização - Corrigido (sem lógica ternária)
            'campanhas-sync' => ['CampaignController', 'sync'],
            'campanhas-sync-all' => ['CampaignController', 'syncAll'],
            'campanhas-sync-complete' => ['CampaignController', 'syncComplete'],
            'sync_complete' => ['CampaignController', 'syncComplete'], // Alias AJAX
            
            // Edição Local
            'campanhas-save-columns' => ['CampaignController', 'saveColumns'],
            'campanhas-update-field' => ['CampaignController', 'updateField'],
            'campanhas-bulk-action' => ['CampaignController', 'bulkAction'],
            'campanhas-duplicate' => ['CampaignController', 'duplicate'],
            
            // Sincronização Bidirecional Meta Ads
            'campanhas-update-meta-status' => ['CampaignController', 'updateMetaStatus'],
            'campanhas-update-meta-budget' => ['CampaignController', 'updateMetaBudget'],
            
            // Filtros Avançados V3.0
            'campanhas-filter-cbo' => ['CampaignController', 'filterByCBO'],
            'campanhas-filter-asc' => ['CampaignController', 'filterByASC'],
            'campanhas-filter-quality' => ['CampaignController', 'filterByQuality'],
            'campanhas-filter-issues' => ['CampaignController', 'filterByIssues'],
        ];
    }
    
    /**
     * Rotas de Conjuntos de Anúncios (Ad Sets)
     */
    private function getAdSetRoutes($method) {
        return [
            // Principais
            'conjuntos' => ['AdSetController', 'index'],
            'adsets' => ['AdSetController', 'index'],
            'conjunto-detalhes' => ['AdSetController', 'show'],
            'conjuntos-export' => ['AdSetController', 'export'],
            
            // Sincronização - Corrigido
            'conjuntos-sync' => ['AdSetController', 'sync'],
            'conjuntos-sync-all' => ['AdSetController', 'syncAll'],
            'conjuntos-sync-complete' => ['AdSetController', 'syncComplete'],
            
            // Edição Local
            'conjuntos-save-columns' => ['AdSetController', 'saveColumns'],
            'conjuntos-update-field' => ['AdSetController', 'updateField'],
            
            // Sincronização Bidirecional Meta Ads
            'conjuntos-update-meta-status' => ['AdSetController', 'updateMetaStatus'],
            'conjuntos-update-meta-budget' => ['AdSetController', 'updateMetaBudget'],
        ];
    }
    
    /**
     * Rotas de Anúncios (Ads)
     */
    private function getAdRoutes($method) {
        return [
            // Principais
            'anuncios' => ['AdController', 'index'],
            'ads' => ['AdController', 'index'],
            'anuncio-detalhes' => ['AdController', 'show'],
            'anuncio-preview' => ['AdController', 'preview'],
            'anuncios-export' => ['AdController', 'export'],
            
            // Sincronização - Corrigido
            'anuncios-sync' => ['AdController', 'sync'],
            'anuncios-sync-all' => ['AdController', 'syncAll'],
            'anuncios-sync-complete' => ['AdController', 'syncComplete'],
            
            // Edição Local
            'anuncios-save-columns' => ['AdController', 'saveColumns'],
            'anuncios-update-field' => ['AdController', 'updateField'],
            
            // Sincronização Bidirecional Meta Ads
            'anuncios-update-meta-status' => ['AdController', 'updateMetaStatus'],
        ];
    }
    
    /**
     * Rotas Meta Ads (Legacy - Compatibilidade)
     */
    private function getMetaRoutes() {
        return [
            'meta' => ['MetaController', 'index'],
            'meta-contas' => ['MetaController', 'accounts'],
            'meta-campanhas' => ['MetaController', 'campaigns'],
        ];
    }
    
    /**
     * Rotas Google Ads
     */
    private function getGoogleRoutes() {
        return [
            'google' => ['GoogleController', 'index'],
        ];
    }
    
    /**
     * Rotas de Integrações
     */
    private function getIntegrationRoutes() {
        return [
            'integracoes' => ['IntegrationController', 'index'],
            'integracoes-meta' => ['IntegrationController', 'meta'],
            'integracoes-meta-salvar' => ['IntegrationController', 'metaSave'],
            'integracoes-meta-save' => ['IntegrationController', 'metaSave'],
            'integracoes-meta-conectar' => ['IntegrationController', 'metaConnect'],
            'integracoes-meta-connect' => ['IntegrationController', 'metaConnect'],
            'integracoes-meta-contas' => ['IntegrationController', 'metaAccounts'],
            'integracoes-meta-accounts' => ['IntegrationController', 'metaAccounts'],
            'integracoes-meta-toggle' => ['IntegrationController', 'metaToggleAccount'],
            'integracoes-meta-sync' => ['IntegrationController', 'metaSync'],
            'integracoes-meta-remover' => ['IntegrationController', 'metaRemove'],
            'integracoes-webhook' => ['IntegrationController', 'webhook'],
        ];
    }
    
    /**
     * Rotas de UTMs
     */
    private function getUtmRoutes() {
        return [
            'utms' => ['UtmController', 'index'],
            'utm-generate' => ['UtmController', 'generate'],
            'utm-delete' => ['UtmController', 'delete'],
            'utm-export' => ['UtmController', 'export'],
            'utms-scripts' => ['UtmController', 'scripts'],
            'utms-stats' => ['UtmController', 'stats'],
        ];
    }
    
    /**
     * Rotas de Regras de Automação
     */
    private function getRuleRoutes() {
        return [
            'regras' => ['RuleController', 'index'],
            'regra-create' => ['RuleController', 'create'],
            'regra-update' => ['RuleController', 'update'],
            'regra-delete' => ['RuleController', 'delete'],
            'regra-toggle' => ['RuleController', 'toggle'],
            'regra-get' => ['RuleController', 'getRule'],
            'regra-logs' => ['RuleController', 'logs'],
            'regra-execute' => ['RuleController', 'execute'],
            
            // Aliases em inglês
            'rule-create' => ['RuleController', 'create'],
            'rule-update' => ['RuleController', 'update'],
            'rule-delete' => ['RuleController', 'delete'],
            'rule-get' => ['RuleController', 'getRule'],
            'rule-toggle' => ['RuleController', 'toggle'],
            'rule-logs' => ['RuleController', 'logs'],
            'rule-execute' => ['RuleController', 'execute'],
        ];
    }
    
    /**
     * Rotas de Produtos (Sistema Híbrido)
     */
    private function getProductRoutes() {
        return [
            'produtos' => ['ProductController', 'index'],
            'products' => ['ProductController', 'index'],
            'product-show' => ['ProductController', 'show'],
            'product-create' => ['ProductController', 'create'],
            'product-update' => ['ProductController', 'update'],
            'product-delete' => ['ProductController', 'delete'],
            'product-link-campaign' => ['ProductController', 'linkToCampaign'],
        ];
    }
    
    /**
     * Rotas de Vendas
     */
    private function getSalesRoutes() {
        return [
            'vendas' => ['SalesController', 'index'],
            'sales-create' => ['SalesController', 'create'],
            'sales-update' => ['SalesController', 'update'],
            'sales-delete' => ['SalesController', 'delete'],
            'sales-import' => ['SalesController', 'import'],
        ];
    }
    
    /**
     * Rotas de Webhooks (Sistema Universal)
     */
    private function getWebhookRoutes() {
        return [
            'webhooks' => ['WebhookController', 'index'],
            'webhook-create' => ['WebhookController', 'create'],
            'webhook-update' => ['WebhookController', 'update'],
            'webhook-delete' => ['WebhookController', 'delete'],
            'webhook-get' => ['WebhookController', 'getWebhook'],
            'webhook-logs' => ['WebhookController', 'logs'],
            'webhook-test' => ['WebhookController', 'test'],
            'webhook-regenerate-key' => ['WebhookController', 'regenerateKey'],
        ];
    }
    
    /**
     * Rotas de Relatórios
     */
    private function getReportRoutes() {
        return [
            'relatorios' => ['ReportController', 'index'],
            'report-export' => ['ReportController', 'export'],
            'report-generate' => ['ReportController', 'generate'],
        ];
    }
    
    /**
     * Rotas de Taxas e Impostos
     */
    private function getTaxRoutes() {
        return [
            'taxas' => ['TaxController', 'index'],
            
            // AJAX - Impostos
            'imposto-get' => ['TaxController', 'getImposto'],
            'imposto-store' => ['TaxController', 'storeImposto'],
            'imposto-update' => ['TaxController', 'updateImposto'],
            'imposto-delete' => ['TaxController', 'deleteImposto'],
            
            // AJAX - Taxas
            'tax-get' => ['TaxController', 'getTax'],
            'tax-store' => ['TaxController', 'store'],
            'tax-update' => ['TaxController', 'update'],
            'tax-delete' => ['TaxController', 'delete'],
            
            // AJAX - Custos de Produtos
            'tax-update-costs' => ['TaxController', 'updateProductCosts'],
        ];
    }
    
    /**
     * Rotas de Despesas
     */
    private function getExpenseRoutes() {
        return [
            'despesas' => ['ExpenseController', 'index'],
            'expense-get' => ['ExpenseController', 'getExpense'],
            'expense-store' => ['ExpenseController', 'store'],
            'expense-update' => ['ExpenseController', 'update'],
            'expense-delete' => ['ExpenseController', 'delete'],
        ];
    }
    
    /**
     * Rotas de Ajuda e Documentação
     */
    private function getHelpRoutes() {
        return [
            'ajuda' => ['HelpController', 'index'],
            'help-crons' => ['HelpController', 'crons'],
            'help-webhooks' => ['HelpController', 'webhooks'],
            'help-meta-ads' => ['HelpController', 'metaAds'],
            'help-faq' => ['HelpController', 'faq'],
        ];
    }
    
    /**
     * Rotas de Administração
     */
    private function getAdminRoutes() {
        return [
            'admin' => ['AdminController', 'dashboard'],
            'admin-clientes' => ['AdminController', 'clients'],
            'admin-configuracoes' => ['AdminController', 'settings'],
        ];
    }
    
    /**
     * Rotas de API/AJAX Helpers
     */
    private function getApiRoutes() {
        return [
            'get-meta-accounts' => ['IntegrationController', 'getMetaAccountsJson'],
            'get-campaigns' => ['CampaignController', 'getCampaignsJson'],
            'get-adsets' => ['AdSetController', 'getAdSetsJson'],
            'get-ads' => ['AdController', 'getAdsJson'],
        ];
    }
    
    // =========================================================================
    // CHAMADA DE CONTROLLERS
    // =========================================================================
    
    /**
     * Chama método do controller com suporte automático a V2/V3.0
     */
    private function callController($controllerName, $methodName) {
        
        // Resolve controller (V1 → V2/V3 se disponível)
        $actualControllerName = $this->resolveControllerVersion($controllerName);
        
        // Valida existência do controller
        $controllerPath = $this->getControllerPath($actualControllerName);
        if (!$controllerPath) {
            return $this->handleMissingController($controllerName, $methodName);
        }
        
        // Carrega controller
        require_once $controllerPath;
        
        // Valida classe
        if (!class_exists($actualControllerName)) {
            return $this->handleError("Classe não encontrada: {$actualControllerName}");
        }
        
        // Instancia controller
        $controller = new $actualControllerName();
        
        // Valida método
        if (!method_exists($controller, $methodName)) {
            return $this->handleMissingMethod($controller, $actualControllerName, $methodName);
        }
        
        // Executa método
        error_log("[ROUTER] ✅ Executando: {$actualControllerName}@{$methodName}");
        return call_user_func([$controller, $methodName]);
    }
    
    /**
     * Resolve versão do controller (V1 → V2/V3 automaticamente)
     */
    private function resolveControllerVersion($controllerName) {
        // Verifica se existe alias para V2/V3
        if (isset($this->controllerAliases[$controllerName])) {
            $v2Name = $this->controllerAliases[$controllerName];
            $v2Path = dirname(__DIR__) . '/app/controllers/' . $v2Name . '.php';
            
            if (file_exists($v2Path)) {
                error_log("[ROUTER] 🔄 Upgrade automático: {$controllerName} → {$v2Name} (V3.0 - 150+ campos)");
                return $v2Name;
            }
        }
        
        return $controllerName;
    }
    
    /**
     * Retorna caminho do controller se existir
     */
    private function getControllerPath($controllerName) {
        $path = dirname(__DIR__) . '/app/controllers/' . $controllerName . '.php';
        return file_exists($path) ? $path : null;
    }
    
    /**
     * Trata controller não encontrado
     */
    private function handleMissingController($controllerName, $methodName) {
        // Tenta criar controller temporário (desenvolvimento)
        if ($this->createTempController($controllerName, $methodName)) {
            return true;
        }
        
        return $this->handleError("Controller não encontrado: {$controllerName}");
    }
    
    /**
     * Trata método não encontrado com fallbacks inteligentes
     */
    private function handleMissingMethod($controller, $controllerName, $methodName) {
        
        // Fallback 1: syncAll → sync
        if ($methodName === 'syncAll' && method_exists($controller, 'sync')) {
            error_log("[ROUTER] ⚠️ Fallback: syncAll → sync()");
            return call_user_func([$controller, 'sync']);
        }
        
        // Fallback 2: filterByXXX → index
        if (strpos($methodName, 'filterBy') === 0 && method_exists($controller, 'index')) {
            error_log("[ROUTER] ⚠️ Fallback: {$methodName} → index()");
            return call_user_func([$controller, 'index']);
        }
        
        // Fallback 3: Métodos V3.0 não implementados
        $v3Methods = ['updateMetaStatus', 'updateMetaBudget', 'syncComplete'];
        if (in_array($methodName, $v3Methods)) {
            $message = "⚠️ O método {$methodName} requer versão V3.0 do {$controllerName}. Por favor, atualize o controller.";
            error_log("[ROUTER] {$message}");
            
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $message,
                    'upgrade_required' => true
                ]);
            }
        }
        
        return $this->handleError("Método não encontrado: {$controllerName}@{$methodName}");
    }
    
    // =========================================================================
    // SISTEMA DE CONTROLLERS TEMPORÁRIOS (Desenvolvimento)
    // =========================================================================
    
    /**
     * Cria controller temporário para páginas em desenvolvimento
     * Útil durante o desenvolvimento para visualizar views sem controller completo
     */
    private function createTempController($controllerName, $method) {
        
        // Mapeamento de controllers temporários
        $tempControllers = [
            'AdSetController' => 'campaigns/adsets',
            'AdController' => 'campaigns/ads'
        ];
        
        // Só funciona para método index
        if (!isset($tempControllers[$controllerName]) || $method !== 'index') {
            return false;
        }
        
        $viewFile = dirname(__DIR__) . '/app/views/' . $tempControllers[$controllerName] . '.php';
        
        if (!file_exists($viewFile)) {
            return false;
        }
        
        // Carrega dependências
        $config = require dirname(__DIR__) . '/config/app.php';
        $auth = new Auth();
        $db = Database::getInstance();
        
        // Verifica autenticação
        if (!$auth->check()) {
            header('Location: index.php?page=login');
            exit;
        }
        
        // Prepara variáveis para a view
        $user = $auth->user();
        $pageTitle = ucfirst(str_replace('Controller', '', $controllerName));
        
        // Inicializa arrays vazios
        $adsets = [];
        $ads = [];
        $stats = [];
        $userColumns = null;
        
        // Renderiza view
        error_log("[ROUTER] 🛠️ Controller temporário criado: {$controllerName}");
        include dirname(__DIR__) . '/app/views/layout/header.php';
        include $viewFile;
        include dirname(__DIR__) . '/app/views/layout/footer.php';
        
        return true;
    }
    
    // =========================================================================
    // MIDDLEWARE E SEGURANÇA
    // =========================================================================
    
    /**
     * Middleware de autenticação
     * Verifica se usuário está autenticado antes de processar rotas protegidas
     */
    public function authMiddleware() {
        // Inicia sessão se ainda não iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $page = $_GET['page'] ?? 'login';
        
        // Páginas públicas não precisam de autenticação
        if (in_array($page, $this->publicPages)) {
            return true;
        }
        
        // Verifica autenticação
        if (!isset($_SESSION['user_id'])) {
            error_log("[ROUTER] 🔒 Acesso negado. Redirecionando para login.");
            header('Location: index.php?page=login');
            exit;
        }
        
        return true;
    }
    
    // =========================================================================
    // UTILITÁRIOS
    // =========================================================================
    
    /**
     * Verifica se é requisição AJAX
     */
    private function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Retorna resposta JSON
     */
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Tratamento unificado de erros
     */
    private function handleError($message, $statusCode = 500) {
        error_log("[ROUTER] ❌ ERRO: {$message}");
        
        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => false,
                'message' => $message
            ], $statusCode);
        }
        
        $this->error($message);
        return false;
    }
    
    /**
     * Exibe página de erro 500
     */
    private function error($message) {
        http_response_code(500);
        
        echo "<!DOCTYPE html>";
        echo "<html lang='pt-BR'><head><meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>Erro 500 - UTMTrack</title>";
        echo "<style>";
        echo "* { margin: 0; padding: 0; box-sizing: border-box; }";
        echo "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; ";
        echo "background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; ";
        echo "display: flex; align-items: center; justify-content: center; color: white; padding: 20px; }";
        echo ".error-container { text-align: center; max-width: 600px; background: rgba(255,255,255,0.1); ";
        echo "padding: 40px; border-radius: 20px; backdrop-filter: blur(10px); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }";
        echo "h1 { font-size: 80px; font-weight: 900; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }";
        echo "h2 { font-size: 28px; margin-bottom: 20px; font-weight: 600; }";
        echo ".message { font-size: 16px; margin-bottom: 30px; opacity: 0.9; line-height: 1.6; }";
        echo "a { display: inline-block; padding: 15px 40px; background: white; color: #667eea; ";
        echo "text-decoration: none; border-radius: 30px; font-weight: 600; transition: all 0.3s; }";
        echo "a:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }";
        echo ".emoji { font-size: 60px; margin-bottom: 20px; }";
        echo "</style></head><body>";
        echo "<div class='error-container'>";
        echo "<div class='emoji'>⚠️</div>";
        echo "<h1>500</h1>";
        echo "<h2>Erro Interno do Servidor</h2>";
        echo "<p class='message'>" . htmlspecialchars($message) . "</p>";
        echo "<a href='index.php?page=dashboard'>← Voltar ao Dashboard</a>";
        echo "</div></body></html>";
        exit;
    }
    
    /**
     * Exibe página de erro 404
     */
    private function notFound() {
        http_response_code(404);
        
        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Página não encontrada'
            ], 404);
        }
        
        echo "<!DOCTYPE html>";
        echo "<html lang='pt-BR'><head><meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>404 - Página não encontrada</title>";
        echo "<style>";
        echo "* { margin: 0; padding: 0; box-sizing: border-box; }";
        echo "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; ";
        echo "background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; ";
        echo "display: flex; align-items: center; justify-content: center; color: white; padding: 20px; }";
        echo ".error-container { text-align: center; max-width: 600px; background: rgba(255,255,255,0.1); ";
        echo "padding: 40px; border-radius: 20px; backdrop-filter: blur(10px); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }";
        echo "h1 { font-size: 120px; font-weight: 900; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }";
        echo "h2 { font-size: 32px; margin-bottom: 20px; font-weight: 600; }";
        echo "p { font-size: 18px; margin-bottom: 30px; opacity: 0.9; }";
        echo "a { display: inline-block; padding: 15px 40px; background: white; color: #667eea; ";
        echo "text-decoration: none; border-radius: 30px; font-weight: 600; transition: all 0.3s; }";
        echo "a:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }";
        echo "</style></head><body>";
        echo "<div class='error-container'>";
        echo "<h1>404</h1>";
        echo "<h2>Página não encontrada</h2>";
        echo "<p>A página que você está procurando não existe ou foi movida.</p>";
        echo "<a href='index.php?page=dashboard'>← Voltar ao Dashboard</a>";
        echo "</div></body></html>";
        exit;
    }
}