<?php
/**
 * ========================================
 * AJAX HANDLER MELHORADO COM LOGS
 * ========================================
 * CAMINHO: /utmtrack/public/ajax-campaigns.php
 * 
 * SUBSTITUI O HANDLER AJAX NO INÍCIO DO index.php
 */

// Habilita log de erros
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/ajax_errors.log');

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa buffer anterior
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Headers obrigatórios
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

/**
 * Função auxiliar para resposta JSON
 */
function ajaxResponse($data, $httpCode = 200) {
    ob_get_clean();
    http_response_code($httpCode);
    
    // Adiciona timestamp e debug info
    $data['timestamp'] = date('Y-m-d H:i:s');
    $data['debug'] = [
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
    ];
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Função auxiliar para erro
 */
function ajaxError($message, $httpCode = 400, $details = null) {
    error_log("[AJAX ERROR] $message" . ($details ? " | Details: " . json_encode($details) : ""));
    
    ajaxResponse([
        'success' => false,
        'message' => $message,
        'details' => $details
    ], $httpCode);
}

// ========================================
// VERIFICAÇÕES INICIAIS
// ========================================

// Verifica se é uma requisição AJAX
if (!isset($_GET['ajax_action'])) {
    exit; // Não é uma requisição AJAX, continua para o HTML normal
}

error_log("========================================");
error_log("[AJAX] Nova requisição: " . $_GET['ajax_action']);
error_log("[AJAX] Método: " . $_SERVER['REQUEST_METHOD']);
error_log("[AJAX] User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));

// Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    error_log("[AJAX] Usuário não autenticado");
    ajaxError('Não autorizado - faça login novamente', 401);
}

$userId = intval($_SESSION['user_id']);
$action = $_GET['ajax_action'];

error_log("[AJAX] User ID: $userId");
error_log("[AJAX] Action: $action");

// Lê body da requisição
$rawInput = file_get_contents('php://input');
$requestData = json_decode($rawInput, true) ?? $_POST;

if (!empty($rawInput)) {
    error_log("[AJAX] Request body: " . substr($rawInput, 0, 500));
}

// ========================================
// CARREGA DEPENDÊNCIAS
// ========================================
try {
    $baseDir = dirname(__DIR__);
    
    $requiredFiles = [
        $baseDir . '/app/core/Database.php',
        $baseDir . '/app/core/Config.php',
        $baseDir . '/app/core/MetaAdsDataStructure.php',
        $baseDir . '/app/core/MetaAdsSync.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            error_log("[AJAX] Arquivo não encontrado: $file");
            ajaxError("Arquivo não encontrado: " . basename($file), 500);
        }
        require_once $file;
    }
    
    $db = Database::getInstance();
    error_log("[AJAX] Dependências carregadas com sucesso");
    
} catch (Exception $e) {
    error_log("[AJAX] Erro ao carregar dependências: " . $e->getMessage());
    ajaxError('Erro ao inicializar sistema: ' . $e->getMessage(), 500);
}

// ========================================
// PROCESSA AÇÕES
// ========================================
try {
    
    switch ($action) {
        
        // ========================================
        // SINCRONIZAÇÃO COMPLETA
        // ========================================
        case 'sync_complete':
        case 'sync_all':
            error_log("[SYNC] Iniciando sincronização completa");
            
            // Verifica contas ativas
            $activeAccounts = $db->fetchAll("
                SELECT id, account_id, account_name, access_token, token_expires_at
                FROM ad_accounts
                WHERE user_id = :user_id
                AND platform = 'meta'
                AND status = 'active'
                AND access_token IS NOT NULL
            ", ['user_id' => $userId]);
            
            error_log("[SYNC] Contas ativas encontradas: " . count($activeAccounts));
            
            if (empty($activeAccounts)) {
                error_log("[SYNC] Nenhuma conta ativa");
                ajaxError('Nenhuma conta Meta Ads ativa. Conecte e ative suas contas primeiro.', 400);
            }
            
            // Verifica tokens
            $validTokens = 0;
            foreach ($activeAccounts as $acc) {
                if (strtotime($acc['token_expires_at']) > time()) {
                    $validTokens++;
                } else {
                    error_log("[SYNC] Token expirado para conta: {$acc['account_name']}");
                }
            }
            
            if ($validTokens === 0) {
                error_log("[SYNC] Todos os tokens expirados");
                ajaxError('Todos os tokens expiraram. Reconecte suas contas.', 400);
            }
            
            error_log("[SYNC] Tokens válidos: $validTokens");
            
            // Inicializa sync
            $metaSync = new MetaAdsSync($db, $userId);
            
            $options = [
                'date_preset' => $requestData['date_preset'] ?? 'today',
                'time_range' => $requestData['time_range'] ?? null,
                'breakdowns' => $requestData['breakdowns'] ?? [],
                'include_insights' => $requestData['include_insights'] ?? true,
                'include_actions' => $requestData['include_actions'] ?? true,
                'include_video_data' => $requestData['include_video_data'] ?? true
            ];
            
            error_log("[SYNC] Opções: " . json_encode($options));
            
            // Executa sincronização
            $startTime = microtime(true);
            $results = $metaSync->syncAll($options);
            $duration = round(microtime(true) - $startTime, 2);
            
            error_log("[SYNC] Concluída em {$duration}s");
            error_log("[SYNC] Resultados: " . json_encode($results));
            
            // Busca campanhas atualizadas
            $campaigns = $db->fetchAll("
                SELECT 
                    c.*,
                    aa.account_name,
                    ci.impressions,
                    ci.clicks,
                    ci.spend,
                    ci.purchase,
                    ci.purchase_value,
                    ci.roas,
                    ci.roi,
                    ci.cpa
                FROM campaigns c
                LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
                LEFT JOIN campaign_insights ci ON ci.campaign_id = c.id
                WHERE c.user_id = :user_id
                ORDER BY c.created_at DESC
            ", ['user_id' => $userId]);
            
            error_log("[SYNC] Campanhas retornadas: " . count($campaigns));
            
            // Estatísticas
            $stats = [
                'total_campaigns' => count($campaigns),
                'active_campaigns' => 0,
                'total_spend' => 0,
                'total_revenue' => 0,
                'avg_roas' => 0
            ];
            
            foreach ($campaigns as $c) {
                if ($c['status'] === 'active') {
                    $stats['active_campaigns']++;
                }
                $stats['total_spend'] += floatval($c['spend'] ?? 0);
                $stats['total_revenue'] += floatval($c['purchase_value'] ?? 0);
            }
            
            if ($stats['total_spend'] > 0) {
                $stats['avg_roas'] = round($stats['total_revenue'] / $stats['total_spend'], 2);
            }
            
            $message = "Sincronizados: {$results['campaigns']['synced']} campanhas";
            if (isset($results['campaigns']['errors']) && !empty($results['campaigns']['errors'])) {
                $message .= " | " . count($results['campaigns']['errors']) . " erro(s)";
            }
            $message .= " | {$duration}s";
            
            error_log("[SYNC] " . $message);
            
            ajaxResponse([
                'success' => true,
                'message' => $message,
                'data' => [
                    'campaigns' => $campaigns,
                    'stats' => $stats,
                    'sync_results' => $results,
                    'duration' => $duration
                ]
            ]);
            break;
            
        // ========================================
        // ATUALIZAR STATUS
        // ========================================
        case 'update_status':
            $campaignId = intval($requestData['campaign_id'] ?? 0);
            $newStatus = strtoupper($requestData['status'] ?? '');
            
            error_log("[UPDATE_STATUS] Campaign ID: $campaignId | Status: $newStatus");
            
            if (!$campaignId || !in_array($newStatus, ['ACTIVE', 'PAUSED', 'DELETED'])) {
                ajaxError('Parâmetros inválidos', 400);
            }
            
            $campaign = $db->fetch("
                SELECT c.*, aa.access_token 
                FROM campaigns c
                LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", ['id' => $campaignId, 'user_id' => $userId]);
            
            if (!$campaign) {
                error_log("[UPDATE_STATUS] Campanha não encontrada");
                ajaxError('Campanha não encontrada', 404);
            }
            
            // Atualiza no banco
            $updated = $db->update('campaigns',
                ['status' => strtolower($newStatus)],
                'id = :id',
                ['id' => $campaignId]
            );
            
            error_log("[UPDATE_STATUS] Atualizado no banco: " . ($updated ? 'sim' : 'não'));
            
            ajaxResponse([
                'success' => true,
                'message' => 'Status atualizado com sucesso',
                'data' => [
                    'campaign_id' => $campaignId,
                    'new_status' => strtolower($newStatus)
                ]
            ]);
            break;
            
        // ========================================
        // SALVAR COLUNAS
        // ========================================
        case 'save_columns':
            $columns = $requestData['columns'] ?? [];
            
            error_log("[SAVE_COLUMNS] Colunas: " . json_encode($columns));
            
            if (empty($columns)) {
                ajaxError('Colunas inválidas', 400);
            }
            
            $db->query("
                INSERT INTO user_preferences (user_id, preference_key, preference_value)
                VALUES (:user_id, :key, :value)
                ON DUPLICATE KEY UPDATE preference_value = :value
            ", [
                'user_id' => $userId,
                'key' => 'campaign_columns',
                'value' => json_encode($columns)
            ]);
            
            error_log("[SAVE_COLUMNS] Salvo com sucesso");
            
            ajaxResponse([
                'success' => true,
                'message' => 'Colunas salvas com sucesso',
                'data' => ['columns' => $columns]
            ]);
            break;
            
        // ========================================
        // AÇÃO NÃO RECONHECIDA
        // ========================================
        default:
            error_log("[AJAX] Ação não reconhecida: $action");
            ajaxError("Ação '{$action}' não reconhecida", 400);
    }
    
} catch (Exception $e) {
    error_log("[AJAX] Exception: " . $e->getMessage());
    error_log("[AJAX] Stack trace: " . $e->getTraceAsString());
    
    ajaxError('Erro no servidor: ' . $e->getMessage(), 500, [
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}