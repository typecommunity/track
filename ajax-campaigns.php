<?php
/**
 * ========================================
 * AJAX HANDLER V2.1 - PROCESSADOR AJAX
 * ========================================
 * Processa todas as requisições AJAX do dashboard
 * Caminho: /public/ajax-campaigns.php
 */

// Inicia sessão
session_start();

// CRÍTICO: Limpa qualquer output anterior
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Headers corretos
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Desabilita exibição de erros no output
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Define constante de debug
define('AJAX_DEBUG', true);

// Função helper para resposta JSON
function ajaxResponse($data, $httpCode = 200) {
    $output = ob_get_clean();
    
    if (!empty($output) && AJAX_DEBUG) {
        error_log("[AJAX WARNING] Output capturado: " . substr($output, 0, 200));
    }
    
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function ajaxError($message, $httpCode = 400, $details = null) {
    ajaxResponse([
        'success' => false,
        'message' => $message,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ], $httpCode);
}

// Função para atualizar campo no Meta Ads
function updateMetaField($metaCampaignId, $field, $value, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$metaCampaignId}";
    
    // Mapeia campos
    $fieldMap = [
        'campaign_name' => 'name',
        'daily_budget' => 'daily_budget',
        'lifetime_budget' => 'lifetime_budget',
        'spend_cap' => 'spend_cap'
    ];
    
    $metaField = $fieldMap[$field] ?? $field;
    
    // Converte para centavos se for orçamento
    if (in_array($field, ['daily_budget', 'lifetime_budget', 'spend_cap'])) {
        $value = intval(floatval($value) * 100);
    }
    
    $postData = [
        $metaField => $value,
        'access_token' => $accessToken
    ];
    
    if (AJAX_DEBUG) {
        error_log("[META API] Updating {$metaField} to {$value} for campaign {$metaCampaignId}");
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (AJAX_DEBUG) {
        error_log("[META API] Response: HTTP {$httpCode} - " . substr($response, 0, 200));
    }
    
    return [
        'success' => $httpCode === 200,
        'http_code' => $httpCode,
        'response' => $response
    ];
}

if (AJAX_DEBUG) {
    error_log("[AJAX] Action: " . ($_GET['ajax_action'] ?? $_GET['action'] ?? 'none') . " | Method: " . $_SERVER['REQUEST_METHOD']);
}

if (!isset($_SESSION['user_id'])) {
    ajaxError('Não autorizado - faça login novamente', 401);
}

$userId = intval($_SESSION['user_id']);
$action = $_GET['ajax_action'] ?? $_GET['action'] ?? null;

$rawInput = file_get_contents('php://input');
$requestData = json_decode($rawInput, true) ?? $_POST;

if (AJAX_DEBUG) {
    error_log("[AJAX] Request data: " . substr($rawInput, 0, 500));
}

try {
    $baseDir = dirname(__DIR__);
    
    if (AJAX_DEBUG) {
        error_log("[AJAX] Base directory: " . $baseDir);
    }
    
    // Carrega Database.php
    $databaseFile = $baseDir . '/core/Database.php';
    if (!file_exists($databaseFile)) {
        ajaxError("Arquivo não encontrado: Database.php", 500, [
            'path_checked' => $databaseFile
        ]);
    }
    require_once $databaseFile;
    
    // Config é opcional
    $configFile = $baseDir . '/config/app.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    }
    
    // Para sincronização, carrega MetaAds classes
    if (in_array($action, ['sync_complete', 'sync_all', 'sync_campaigns'])) {
        $metaDataStructureFile = $baseDir . '/core/MetaAdsDataStructure.php';
        $metaSyncFile = $baseDir . '/core/MetaAdsSync.php';
        
        if (!file_exists($metaDataStructureFile)) {
            ajaxError("Arquivo não encontrado: MetaAdsDataStructure.php", 500);
        }
        
        if (!file_exists($metaSyncFile)) {
            ajaxError("Arquivo não encontrado: MetaAdsSync.php", 500);
        }
        
        require_once $metaDataStructureFile;
        require_once $metaSyncFile;
    }
    
    $db = Database::getInstance();
    
    if (AJAX_DEBUG) {
        error_log("[AJAX] Database initialized successfully");
    }
    
    switch ($action) {
        
        // ========================================
        // SINCRONIZAÇÃO COMPLETA
        // ========================================
        case 'sync_complete':
        case 'sync_all':
            if (AJAX_DEBUG) {
                error_log("[AJAX] Iniciando sincronização completa...");
            }
            
            $metaSync = new MetaAdsSync($db, $userId);
            
            $options = [
                'date_preset' => $requestData['date_preset'] ?? 'maximum',
                'time_range' => $requestData['time_range'] ?? null,
                'breakdowns' => $requestData['breakdowns'] ?? [],
                'include_insights' => $requestData['include_insights'] ?? true
            ];
            
            $results = $metaSync->syncAll($options);
            
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
            
            // Calcula stats
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
            
            if (AJAX_DEBUG) {
                error_log("[AJAX] Sincronização concluída: " . $results['campaigns']['synced'] . " campanhas");
            }
            
            ajaxResponse([
                'success' => true,
                'message' => "Sincronizado: {$results['campaigns']['synced']} campanhas | {$results['duration']}s",
                'data' => [
                    'campaigns' => $campaigns,
                    'stats' => $stats,
                    'sync_results' => $results
                ]
            ]);
            break;
            
        // ========================================
        // ATUALIZAR STATUS
        // ========================================
        case 'update_status':
            $campaignId = intval($requestData['campaign_id'] ?? 0);
            $metaCampaignId = $requestData['meta_campaign_id'] ?? null;
            $newStatus = strtoupper($requestData['status'] ?? '');
            
            if (AJAX_DEBUG) {
                error_log("[AJAX] Update status: Campaign {$campaignId} -> {$newStatus}");
            }
            
            if (!$campaignId || !in_array($newStatus, ['ACTIVE', 'PAUSED', 'DELETED'])) {
                ajaxError('Parâmetros inválidos', 400);
            }
            
            // Busca campanha
            $campaign = $db->fetch("
                SELECT c.*, aa.access_token 
                FROM campaigns c
                LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", ['id' => $campaignId, 'user_id' => $userId]);
            
            if (!$campaign) {
                ajaxError('Campanha não encontrada', 404);
            }
            
            // Atualiza localmente
            $db->update('campaigns',
                ['status' => strtolower($newStatus)],
                'id = :id',
                ['id' => $campaignId]
            );
            
            // Tenta atualizar no Meta
            $metaUpdated = false;
            $metaError = null;
            
            if ($metaCampaignId && !empty($campaign['access_token'])) {
                try {
                    $url = "https://graph.facebook.com/v18.0/{$metaCampaignId}";
                    
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                        'status' => $newStatus,
                        'access_token' => $campaign['access_token']
                    ]));
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    $metaUpdated = ($httpCode === 200);
                    
                    if (!$metaUpdated) {
                        $metaError = "HTTP {$httpCode}";
                    }
                } catch (Exception $e) {
                    $metaError = $e->getMessage();
                }
            }
            
            ajaxResponse([
                'success' => true,
                'message' => $metaUpdated 
                    ? 'Status atualizado no Meta Ads' 
                    : 'Status atualizado localmente' . ($metaError ? " (Erro Meta: {$metaError})" : ''),
                'data' => [
                    'campaign_id' => $campaignId,
                    'new_status' => strtolower($newStatus),
                    'meta_updated' => $metaUpdated
                ]
            ]);
            break;
            
        // ========================================
        // ATUALIZAR CAMPO
        // ========================================
        case 'update_field':
        case 'update_budget':
            $campaignId = intval($requestData['campaign_id'] ?? 0);
            $field = $requestData['field'] ?? null;
            $value = $requestData['value'] ?? null;
            
            if (AJAX_DEBUG) {
                error_log("[AJAX] Update field: Campaign {$campaignId} | Field: {$field} | Value: {$value}");
            }
            
            // Campos permitidos
            $allowedFields = [
                'campaign_name', 'daily_budget', 'lifetime_budget', 
                'spend_cap', 'bid_strategy', 'start_time', 'stop_time'
            ];
            
            if (!$campaignId || !$field || !in_array($field, $allowedFields)) {
                ajaxError('Campo não permitido ou parâmetros inválidos', 400);
            }
            
            // Busca campanha
            $campaign = $db->fetch("
                SELECT c.*, aa.access_token 
                FROM campaigns c
                LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", ['id' => $campaignId, 'user_id' => $userId]);
            
            if (!$campaign) {
                ajaxError('Campanha não encontrada', 404);
            }
            
            // Detecta se é ASC (apenas para informação)
            $isASC = (
                stripos($campaign['campaign_name'], 'advantage') !== false ||
                stripos($campaign['campaign_name'], 'asc') !== false ||
                stripos($campaign['campaign_name'], 'shopping') !== false ||
                $campaign['objective'] === 'OUTCOME_SALES'
            );
            
            // Atualiza localmente
            $db->update('campaigns',
                [$field => $value],
                'id = :id',
                ['id' => $campaignId]
            );
            
            if (AJAX_DEBUG) {
                error_log("[AJAX] Campo local atualizado | ASC: " . ($isASC ? 'SIM' : 'NÃO'));
            }
            
            // SEMPRE tenta atualizar no Meta
            $metaUpdated = false;
            $metaError = null;
            
            if (!empty($campaign['campaign_id']) && !empty($campaign['access_token'])) {
                $result = updateMetaField(
                    $campaign['campaign_id'],
                    $field,
                    $value,
                    $campaign['access_token']
                );
                
                $metaUpdated = $result['success'];
                
                if (!$metaUpdated) {
                    $responseData = json_decode($result['response'], true);
                    $metaError = $responseData['error']['message'] ?? "HTTP {$result['http_code']}";
                    
                    if (AJAX_DEBUG) {
                        error_log("[AJAX] Erro ao atualizar no Meta: " . $metaError);
                    }
                }
            }
            
            // Mensagem apropriada
            $message = 'Campo atualizado com sucesso';
            
            if ($metaUpdated) {
                $message = 'Campo atualizado no Meta Ads';
            } else {
                if ($isASC && in_array($field, ['daily_budget', 'lifetime_budget'])) {
                    $message = 'Campanha ASC - orçamento atualizado localmente';
                    if ($metaError) {
                        $message .= " (Meta: {$metaError})";
                    }
                } else {
                    $message = 'Campo atualizado localmente';
                    if ($metaError) {
                        $message .= " (Erro Meta: {$metaError})";
                    }
                }
            }
            
            ajaxResponse([
                'success' => true,
                'message' => $message,
                'data' => [
                    'campaign_id' => $campaignId,
                    'field' => $field,
                    'value' => $value,
                    'is_asc' => $isASC,
                    'meta_updated' => $metaUpdated,
                    'meta_error' => $metaError
                ]
            ]);
            break;
            
        // ========================================
        // AÇÕES EM MASSA
        // ========================================
        case 'bulk_action':
            $bulkAction = $requestData['bulk_action'] ?? null;
            $campaignIds = $requestData['campaign_ids'] ?? [];
            
            if (AJAX_DEBUG) {
                error_log("[AJAX] Bulk action: {$bulkAction} | Count: " . count($campaignIds));
            }
            
            if (empty($campaignIds) || !$bulkAction) {
                ajaxError('Parâmetros inválidos', 400);
            }
            
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];
            
            foreach ($campaignIds as $campaignId) {
                try {
                    switch ($bulkAction) {
                        case 'activate':
                            $db->update('campaigns', 
                                ['status' => 'active'], 
                                'id = :id AND user_id = :user_id', 
                                ['id' => $campaignId, 'user_id' => $userId]
                            );
                            break;
                        case 'pause':
                            $db->update('campaigns', 
                                ['status' => 'paused'], 
                                'id = :id AND user_id = :user_id', 
                                ['id' => $campaignId, 'user_id' => $userId]
                            );
                            break;
                        case 'delete':
                            $db->update('campaigns', 
                                ['status' => 'deleted'], 
                                'id = :id AND user_id = :user_id', 
                                ['id' => $campaignId, 'user_id' => $userId]
                            );
                            break;
                        default:
                            throw new Exception('Ação inválida');
                    }
                    $results['success']++;
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'campaign_id' => $campaignId,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            ajaxResponse([
                'success' => $results['failed'] === 0,
                'message' => "{$results['success']} campanha(s) processada(s)" . 
                            ($results['failed'] > 0 ? ", {$results['failed']} falharam" : ""),
                'data' => $results
            ]);
            break;
            
        // ========================================
        // SALVAR COLUNAS
        // ========================================
        case 'save_columns':
            $columns = $requestData['columns'] ?? [];
            
            if (empty($columns)) {
                ajaxError('Colunas inválidas', 400);
            }
            
            try {
                $tableExists = $db->fetch("SHOW TABLES LIKE 'user_preferences'");
                
                if (!$tableExists) {
                    $db->query("
                        CREATE TABLE IF NOT EXISTS user_preferences (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            preference_key VARCHAR(100) NOT NULL,
                            preference_value TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            UNIQUE KEY unique_user_pref (user_id, preference_key)
                        )
                    ");
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
                
                ajaxResponse([
                    'success' => true,
                    'message' => 'Colunas salvas com sucesso',
                    'data' => ['columns' => $columns]
                ]);
                
            } catch (Exception $e) {
                ajaxError('Erro ao salvar colunas: ' . $e->getMessage(), 500);
            }
            break;
            
        // ========================================
        // EXPORTAR
        // ========================================
        case 'export':
            $format = $requestData['format'] ?? 'csv';
            
            $campaigns = $db->fetchAll("
                SELECT c.*, aa.account_name, ci.*
                FROM campaigns c
                LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
                LEFT JOIN campaign_insights ci ON ci.campaign_id = c.id
                WHERE c.user_id = :user_id
            ", ['user_id' => $userId]);
            
            ajaxResponse([
                'success' => true,
                'data' => $campaigns,
                'format' => $format,
                'count' => count($campaigns)
            ]);
            break;
            
        default:
            ajaxError("Ação '{$action}' não reconhecida", 400);
    }
    
} catch (Exception $e) {
    if (AJAX_DEBUG) {
        error_log("[AJAX EXCEPTION] " . $e->getMessage());
    }
    
    ajaxError(
        'Erro no servidor: ' . $e->getMessage(),
        500,
        AJAX_DEBUG ? [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ] : null
    );
}