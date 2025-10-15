<?php
/**
 * UTMTrack - Sistema de Rotas COMPLETO
 * Versão 5.8 - Atualizado para V3.0 (150+ campos)
 * 
 * Correções nesta versão:
 * - ✅ Rotas explícitas para syncComplete
 * - ✅ Rotas para filtros avançados (CBO, ASC, quality_ranking)
 * - ✅ Suporte completo para CampaignControllerV2 V3.0
 * - ✅ Mantém todas funcionalidades anteriores
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
            'dashboard-debug' => ['DashboardController', 'debug'],
            'resumo' => ['DashboardController', 'index'],
            
            // ========================================
            // CAMPANHAS - ROTAS PRINCIPAIS
            // ========================================
            'campanhas' => ['CampaignController', 'index'],
            'campanhas-meta' => ['CampaignController', 'meta'],
            'campanha-detalhes' => ['CampaignController', 'show'],
            'campanhas-export' => ['CampaignController', 'export'],
            
            // 🔥 ROTAS DE SINCRONIZAÇÃO - CAMPANHAS
            'campanhas-sync' => ['CampaignController', $method === 'GET' ? 'sync' : 'syncAll'],
            'campanhas-sync-all' => ['CampaignController', 'syncAll'],
            'campanhas-sync-complete' => ['CampaignController', 'syncComplete'], // ✅ NOVO V3.0
            'sync_complete' => ['CampaignController', 'syncComplete'], // ✅ NOVO V3.0 - Alias para AJAX
            
            // 🔥 ROTAS DE EDIÇÃO LOCAL - CAMPANHAS
            'campanhas-save-columns' => ['CampaignController', 'saveColumns'],
            'campanhas-update-field' => ['CampaignController', 'updateField'],
            'campanhas-bulk-action' => ['CampaignController', 'bulkAction'],
            'campanhas-duplicate' => ['CampaignController', 'duplicate'],
            
            // 🔥 ROTAS - SINCRONIZAÇÃO BIDIRECIONAL META ADS (CAMPANHAS)
            'campanhas-update-meta-status' => ['CampaignController', 'updateMetaStatus'],
            'campanhas-update-meta-budget' => ['CampaignController', 'updateMetaBudget'],
            
            // ✅ NOVO V3.0: Rotas para filtros avançados
            'campanhas-filter-cbo' => ['CampaignController', 'filterByCBO'],
            'campanhas-filter-asc' => ['CampaignController', 'filterByASC'],
            'campanhas-filter-quality' => ['CampaignController', 'filterByQuality'],
            'campanhas-filter-issues' => ['CampaignController', 'filterByIssues'],
            
            // ========================================
            // CONJUNTOS DE ANÚNCIOS (AD SETS)
            // ========================================
            'conjuntos' => ['AdSetController', 'index'],
            'adsets' => ['AdSetController', 'index'],
            'conjunto-detalhes' => ['AdSetController', 'show'],
            'conjuntos-export' => ['AdSetController', 'export'],
            
            // 🔥 ROTAS DE SINCRONIZAÇÃO - CONJUNTOS
            'conjuntos-sync' => ['AdSetController', $method === 'GET' ? 'sync' : 'syncAll'],
            'conjuntos-sync-all' => ['AdSetController', 'syncAll'],
            'conjuntos-sync-complete' => ['AdSetController', 'syncComplete'], // ✅ NOVO V3.0
            
            // 🔥 ROTAS DE EDIÇÃO LOCAL - CONJUNTOS
            'conjuntos-save-columns' => ['AdSetController', 'saveColumns'],
            'conjuntos-update-field' => ['AdSetController', 'updateField'],
            
            // 🔥 ROTAS - SINCRONIZAÇÃO BIDIRECIONAL META ADS (CONJUNTOS)
            'conjuntos-update-meta-status' => ['AdSetController', 'updateMetaStatus'],
            'conjuntos-update-meta-budget' => ['AdSetController', 'updateMetaBudget'],
            
            // ========================================
            // ANÚNCIOS (ADS)
            // ========================================
            'anuncios' => ['AdController', 'index'],
            'ads' => ['AdController', 'index'],
            'anuncio-detalhes' => ['AdController', 'show'],
            'anuncio-preview' => ['AdController', 'preview'],
            'anuncios-export' => ['AdController', 'export'],
            
            // 🔥 ROTAS DE SINCRONIZAÇÃO - ANÚNCIOS
            'anuncios-sync' => ['AdController', $method === 'GET' ? 'sync' : 'syncAll'],
            'anuncios-sync-all' => ['AdController', 'syncAll'],
            'anuncios-sync-complete' => ['AdController', 'syncComplete'], // ✅ NOVO V3.0
            
            // 🔥 ROTAS DE EDIÇÃO LOCAL - ANÚNCIOS
            'anuncios-save-columns' => ['AdController', 'saveColumns'],
            'anuncios-update-field' => ['AdController', 'updateField'],
            
            // 🔥 ROTAS - SINCRONIZAÇÃO BIDIRECIONAL META ADS (ANÚNCIOS)
            'anuncios-update-meta-status' => ['AdController', 'updateMetaStatus'],
            
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
            'integracoes-meta-save' => ['IntegrationController', 'metaSave'],
            'integracoes-meta-conectar' => ['IntegrationController', 'metaConnect'],
            'integracoes-meta-connect' => ['IntegrationController', 'metaConnect'],
            'integracoes-meta-contas' => ['IntegrationController', 'metaAccounts'],
            'integracoes-meta-accounts' => ['IntegrationController', 'metaAccounts'],
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
            'regra-get' => ['RuleController', 'getRule'],
            'regra-logs' => ['RuleController', 'logs'],
            'regra-execute' => ['RuleController', 'execute'],
            
            // Aliases para compatibilidade
            'rule-create' => ['RuleController', 'create'],
            'rule-update' => ['RuleController', 'update'],
            'rule-delete' => ['RuleController', 'delete'],
            'rule-get' => ['RuleController', 'getRule'],
            'rule-toggle' => ['RuleController', 'toggle'],
            'rule-logs' => ['RuleController', 'logs'],
            'rule-execute' => ['RuleController', 'execute'],
            
            // ========================================
            // PRODUTOS - SISTEMA HÍBRIDO
            // ========================================
            'produtos' => ['ProductController', 'index'],
            'products' => ['ProductController', 'index'],
            
            // Rotas AJAX
            'product-show' => ['ProductController', 'show'],
            'product-create' => ['ProductController', 'create'],
            'product-update' => ['ProductController', 'update'],
            'product-delete' => ['ProductController', 'delete'],
            'product-link-campaign' => ['ProductController', 'linkToCampaign'],
            
            // ========================================
            // VENDAS
            // ========================================
            'vendas' => ['SalesController', 'index'],
            'sales-create' => ['SalesController', 'create'],
            'sales-update' => ['SalesController', 'update'],
            'sales-delete' => ['SalesController', 'delete'],
            'sales-import' => ['SalesController', 'import'],
            
            // ========================================
            // WEBHOOKS - SISTEMA UNIVERSAL
            // ========================================
            'webhooks' => ['WebhookController', 'index'],
            'webhook-create' => ['WebhookController', 'create'],
            'webhook-update' => ['WebhookController', 'update'],
            'webhook-delete' => ['WebhookController', 'delete'],
            'webhook-get' => ['WebhookController', 'getWebhook'],
            'webhook-logs' => ['WebhookController', 'logs'],
            'webhook-test' => ['WebhookController', 'test'],
            'webhook-regenerate-key' => ['WebhookController', 'regenerateKey'],
            
            // ========================================
            // RELATÓRIOS
            // ========================================
            'relatorios' => ['ReportController', 'index'],
            'report-export' => ['ReportController', 'export'],
            'report-generate' => ['ReportController', 'generate'],
            
            // ========================================
            // TAXAS, IMPOSTOS E DESPESAS
            // ========================================
            'taxas' => ['TaxController', 'index'],
            'despesas' => ['ExpenseController', 'index'],
            
            // 🔥 ROTAS AJAX - IMPOSTOS
            'imposto-get' => ['TaxController', 'getImposto'],
            'imposto-store' => ['TaxController', 'storeImposto'],
            'imposto-update' => ['TaxController', 'updateImposto'],
            'imposto-delete' => ['TaxController', 'deleteImposto'],
            
            // 🔥 ROTAS AJAX - TAXAS
            'tax-get' => ['TaxController', 'getTax'],
            'tax-store' => ['TaxController', 'store'],
            'tax-update' => ['TaxController', 'update'],
            'tax-delete' => ['TaxController', 'delete'],
            
            // 🔥 ROTAS AJAX - CUSTOS DE PRODUTOS
            'tax-update-costs' => ['TaxController', 'updateProductCosts'],
            
            // 🔥 ROTAS AJAX - DESPESAS
            'expense-get' => ['ExpenseController', 'getExpense'],
            'expense-store' => ['ExpenseController', 'store'],
            'expense-update' => ['ExpenseController', 'update'],
            'expense-delete' => ['ExpenseController', 'delete'],
            
            // ========================================
            // 💡 AJUDA E DOCUMENTAÇÃO
            // ========================================
            'ajuda' => ['HelpController', 'index'],
            'help-crons' => ['HelpController', 'crons'],
            'help-webhooks' => ['HelpController', 'webhooks'],
            'help-meta-ads' => ['HelpController', 'metaAds'],
            'help-faq' => ['HelpController', 'faq'],
            
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
     * Chama método do controller com suporte a Controllers V2 e V3.0
     */
    private function callController($controllerName, $methodName) {
        // 🔥 MAPEAMENTO DE ALIASES - Controllers V2/V3.0
        $controllerAliases = [
            'CampaignController' => 'CampaignControllerV2',
            'AdSetController' => 'AdSetControllerV2',
            'AdController' => 'AdControllerV2',
        ];
        
        // Verifica se existe versão V2 do controller
        $actualControllerName = $controllerName;
        if (isset($controllerAliases[$controllerName])) {
            $v2Path = dirname(__DIR__) . '/app/controllers/' . $controllerAliases[$controllerName] . '.php';
            if (file_exists($v2Path)) {
                $actualControllerName = $controllerAliases[$controllerName];
                error_log("[ROUTER] ✅ Usando {$actualControllerName} (V3.0 - 150+ campos)");
            }
        }
        
        $controllerPath = dirname(__DIR__) . '/app/controllers/' . $actualControllerName . '.php';
        
        if (!file_exists($controllerPath)) {
            // Tenta criar controller temporário
            if ($this->createTempController($controllerName, $methodName)) {
                return true;
            }
            
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Controller não encontrado: {$actualControllerName}"
                ]);
                exit;
            }
            
            $this->error("Controller não encontrado: {$actualControllerName}");
            return false;
        }
        
        require_once $controllerPath;
        
        if (!class_exists($actualControllerName)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Classe não encontrada: {$actualControllerName}"
                ]);
                exit;
            }
            
            $this->error("Classe não encontrada: {$actualControllerName}");
            return false;
        }
        
        $controller = new $actualControllerName();
        
        if (!method_exists($controller, $methodName)) {
            // Fallback: syncAll → sync
            if ($methodName === 'syncAll' && method_exists($controller, 'sync')) {
                error_log("[ROUTER] ⚠️ Método syncAll não existe, usando sync()");
                return call_user_func([$controller, 'sync']);
            }
            
            // ✅ NOVO V3.0: Fallback para métodos de filtro
            if (strpos($methodName, 'filterBy') === 0 && method_exists($controller, 'index')) {
                error_log("[ROUTER] ⚠️ Método {$methodName} não existe, usando index()");
                return call_user_func([$controller, 'index']);
            }
            
            // Aviso para métodos novos do Meta Ads
            if (in_array($methodName, ['updateMetaStatus', 'updateMetaBudget', 'syncComplete'])) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => "O método {$methodName} ainda não foi implementado no {$actualControllerName}. Atualize para a versão V3.0 do controller."
                    ]);
                    exit;
                }
            }
            
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Método não encontrado: {$actualControllerName}@{$methodName}"
                ]);
                exit;
            }
            
            $this->error("Método não encontrado: {$actualControllerName}@{$methodName}");
            return false;
        }
        
        error_log("[ROUTER] ✅ Executando: {$actualControllerName}@{$methodName}");
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
        $tempControllers = [
            'AdSetController' => 'campaigns/adsets',
            'AdController' => 'campaigns/ads'
        ];
        
        if (isset($tempControllers[$controllerName]) && $method === 'index') {
            $viewFile = dirname(__DIR__) . '/app/views/' . $tempControllers[$controllerName] . '.php';
            
            if (file_exists($viewFile)) {
                $config = require dirname(__DIR__) . '/config/app.php';
                $auth = new Auth();
                $db = Database::getInstance();
                
                if (!$auth->check()) {
                    header('Location: index.php?page=login');
                    exit;
                }
                
                $user = $auth->user();
                $pageTitle = ucfirst(str_replace('Controller', '', $controllerName));
                
                $adsets = [];
                $ads = [];
                $stats = [];
                $userColumns = null;
                
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
        
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
            exit;
        }
        
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
        echo "<p class='message'>{$message}</p>";
        echo "<a href='index.php?page=dashboard'>← Voltar ao Dashboard</a>";
        echo "</div></body></html>";
        exit;
    }
    
    /**
     * Página não encontrada
     */
    private function notFound() {
        http_response_code(404);
        
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Página não encontrada'
            ]);
            exit;
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