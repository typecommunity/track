<?php
/**
 * ========================================
 * AJAX SYNC V2.1 - CORREÇÃO DE STATUS
 * ========================================
 * CORREÇÃO: Status sincroniza corretamente
 * - ✅ Atualiza status ao sincronizar
 * - ✅ Retorna status correto nas respostas
 */

session_start();

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Response padrão
$response = [
    'success' => false,
    'message' => 'Requisição inválida',
    'data' => null
];

try {
    // Verifica autenticação
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Não autorizado - faça login novamente', 401);
    }
    
    $userId = $_SESSION['user_id'];
    
    // Carrega dependências
    $baseDir = dirname(__FILE__);
    require_once $baseDir . '/app/core/Database.php';
    require_once $baseDir . '/app/core/Config.php';
    require_once $baseDir . '/app/core/MetaAdsDataStructure.php';
    require_once $baseDir . '/app/core/MetaAdsSync.php';
    
    // Inicializa database
    $db = Database::getInstance();
    
    // Pega action e dados
    $action = $_GET['ajax_action'] ?? $_POST['action'] ?? null;
    $requestData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Log para debug
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("AJAX Action: " . $action);
        error_log("Request Data: " . json_encode($requestData));
    }
    
    // Processa ação
    switch ($action) {
        
        // ========================================
        // SINCRONIZAÇÃO COMPLETA
        // ========================================
        case 'sync_complete':
            $metaSync = new MetaAdsSync($db, $userId);
            
            $options = [
                'date_preset' => $requestData['date_preset'] ?? 'maximum',
                'time_range' => $requestData['time_range'] ?? null,
                'breakdowns' => $requestData['breakdowns'] ?? [],
                'include_insights' => $requestData['include_insights'] ?? true,
                'include_actions' => $requestData['include_actions'] ?? true,
                'include_video_data' => $requestData['include_video_data'] ?? true,
                'include_demographics' => $requestData['include_demographics'] ?? false,
                'status' => $requestData['status'] ?? null
            ];
            
            error_log("[AJAX] Iniciando sincronização completa com opções: " . json_encode($options));
            
            $results = $metaSync->syncAll($options);
            
            // Log de sincronização
            $db->insert('sync_logs', [
                'user_id' => $userId,
                'sync_type' => 'full',
                'status' => empty($results['campaigns']['errors']) ? 'success' : 'partial',
                'records_synced' => $results['campaigns']['synced'] + $results['adsets']['synced'] + $results['ads']['synced'],
                'errors' => !empty($results['campaigns']['errors']) ? json_encode($results['campaigns']['errors']) : null,
                'duration' => $results['duration']
            ]);
            
            error_log("[AJAX] ✅ Sincronização completa finalizada: {$results['campaigns']['synced']} campanhas");
            
            $response = [
                'success' => true,
                'message' => buildSyncMessage($results),
                'data' => $results
            ];
            break;
            
        // ========================================
        // SINCRONIZAÇÃO DE CAMPANHAS
        // ========================================
        case 'sync_campaigns':
            $metaSync = new MetaAdsSync($db, $userId);
            
            $accounts = $db->fetchAll("
                SELECT * FROM ad_accounts 
                WHERE user_id = :user_id 
                AND platform = 'meta' 
                AND status = 'active'
            ", ['user_id' => $userId]);
            
            $totalSynced = 0;
            $errors = [];
            
            foreach ($accounts as $account) {
                try {
                    $campaigns = syncCampaignsOnly($account, $db, $userId, $requestData);
                    $totalSynced += count($campaigns);
                } catch (Exception $e) {
                    $errors[] = [
                        'account' => $account['account_name'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $response = [
                'success' => empty($errors),
                'message' => "$totalSynced campanhas sincronizadas",
                'data' => [
                    'synced' => $totalSynced,
                    'errors' => $errors
                ]
            ];
            break;
            
        // ========================================
        // BUSCAR CAMPANHAS
        // ========================================
        case 'get_campaigns':
            $filters = [
                'status' => $requestData['status'] ?? null,
                'objective' => $requestData['objective'] ?? null,
                'account_id' => $requestData['account_id'] ?? null,
                'search' => $requestData['search'] ?? null
            ];
            
            $campaigns = getCampaigns($db, $userId, $filters);
            $stats = calculateStats($campaigns);
            
            $response = [
                'success' => true,
                'data' => [
                    'campaigns' => $campaigns,
                    'stats' => $stats
                ]
            ];
            break;
            
        // ========================================
        // ATUALIZAR STATUS
        // ========================================
        case 'update_status':
            $campaignId = intval($requestData['campaign_id'] ?? 0);
            $metaCampaignId = $requestData['meta_campaign_id'] ?? null;
            $newStatus = $requestData['status'] ?? null;
            
            if (!$campaignId || !$newStatus) {
                throw new Exception('Parâmetros inválidos');
            }
            
            error_log("[AJAX] Atualizando status da campanha ID {$campaignId} para: {$newStatus}");
            
            // Busca campanha
            $campaign = $db->fetch("
                SELECT c.*, aa.access_token 
                FROM campaigns c
                LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", [
                'id' => $campaignId,
                'user_id' => $userId
            ]);
            
            if (!$campaign) {
                throw new Exception('Campanha não encontrada');
            }
            
            // ✅ CORREÇÃO: Normaliza o status antes de salvar
            $normalizedStatus = normalizeStatus($newStatus);
            
            // Atualiza localmente
            $db->update('campaigns',
                ['status' => $normalizedStatus],
                'id = :id',
                ['id' => $campaignId]
            );
            
            error_log("[AJAX] ✅ Status atualizado no banco: {$normalizedStatus}");
            
            // Tenta atualizar no Meta
            $metaUpdated = false;
            if ($metaCampaignId && $campaign['access_token']) {
                $metaUpdated = updateMetaStatus(
                    $metaCampaignId,
                    $newStatus, // Meta espera UPPERCASE
                    $campaign['access_token']
                );
                
                if ($metaUpdated) {
                    error_log("[AJAX] ✅ Status atualizado no Meta Ads");
                } else {
                    error_log("[AJAX] ⚠️ Falha ao atualizar status no Meta Ads");
                }
            }
            
            $response = [
                'success' => true,
                'message' => $metaUpdated ? 'Status atualizado no Meta' : 'Status atualizado localmente',
                'data' => [
                    'meta_updated' => $metaUpdated,
                    'new_status' => $normalizedStatus
                ]
            ];
            break;
            
        // ========================================
        // ATUALIZAR ORÇAMENTO
        // ========================================
        case 'update_budget':
            $campaignId = intval($requestData['campaign_id'] ?? 0);
            $metaCampaignId = $requestData['meta_campaign_id'] ?? null;
            $newBudget = floatval($requestData['value'] ?? 0);
            $budgetType = $requestData['budget_type'] ?? 'daily_budget';
            
            if (!$campaignId || $newBudget < 0) {
                throw new Exception('Parâmetros inválidos');
            }
            
            // Busca campanha
            $campaign = $db->fetch("
                SELECT c.*, aa.access_token 
                FROM campaigns c
                LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", [
                'id' => $campaignId,
                'user_id' => $userId
            ]);
            
            if (!$campaign) {
                throw new Exception('Campanha não encontrada');
            }
            
            // Detecta se é ASC/CBO
            $isASC = detectASCCampaign($campaign);
            
            // Atualiza localmente
            $db->update('campaigns',
                [$budgetType => $newBudget],
                'id = :id',
                ['id' => $campaignId]
            );
            
            // Tenta atualizar no Meta (se não for ASC)
            $metaUpdated = false;
            if (!$isASC && $metaCampaignId && $campaign['access_token']) {
                $metaUpdated = updateMetaBudget(
                    $metaCampaignId,
                    $budgetType,
                    $newBudget,
                    $campaign['access_token']
                );
            }
            
            $response = [
                'success' => true,
                'message' => $isASC 
                    ? 'Campanha ASC - orçamento atualizado apenas localmente' 
                    : ($metaUpdated ? 'Orçamento atualizado no Meta' : 'Orçamento atualizado localmente'),
                'data' => [
                    'meta_updated' => $metaUpdated,
                    'is_asc' => $isASC
                ]
            ];
            break;
            
        // ========================================
        // ATUALIZAR CAMPO
        // ========================================
        case 'update_field':
            $campaignId = intval($requestData['campaign_id'] ?? 0);
            $field = $requestData['field'] ?? null;
            $value = $requestData['value'] ?? null;
            
            // Campos permitidos
            $allowedFields = [
                'campaign_name', 'daily_budget', 'lifetime_budget', 
                'spend_cap', 'bid_strategy', 'start_time', 'stop_time'
            ];
            
            if (!$campaignId || !$field || !in_array($field, $allowedFields)) {
                throw new Exception('Campo não permitido ou parâmetros inválidos');
            }
            
            // Busca campanha
            $campaign = $db->fetch("
                SELECT c.*, aa.access_token 
                FROM campaigns c
                LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", [
                'id' => $campaignId,
                'user_id' => $userId
            ]);
            
            if (!$campaign) {
                throw new Exception('Campanha não encontrada');
            }
            
            // Atualiza localmente
            $db->update('campaigns',
                [$field => $value],
                'id = :id',
                ['id' => $campaignId]
            );
            
            // Tenta atualizar no Meta
            $metaUpdated = false;
            if ($campaign['campaign_id'] && $campaign['access_token']) {
                $metaUpdated = updateMetaField(
                    $campaign['campaign_id'],
                    $field,
                    $value,
                    $campaign['access_token']
                );
            }
            
            $response = [
                'success' => true,
                'message' => $metaUpdated ? 'Campo atualizado no Meta' : 'Campo atualizado localmente',
                'data' => [
                    'meta_updated' => $metaUpdated,
                    'field' => $field,
                    'value' => $value
                ]
            ];
            break;
            
        // ========================================
        // AÇÕES EM MASSA
        // ========================================
        case 'bulk_action':
            $bulkAction = $requestData['bulk_action'] ?? null;
            $campaignIds = $requestData['campaign_ids'] ?? [];
            
            if (empty($campaignIds) || !$bulkAction) {
                throw new Exception('Nenhuma campanha selecionada ou ação inválida');
            }
            
            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => []
            ];
            
            foreach ($campaignIds as $campaignId) {
                try {
                    switch ($bulkAction) {
                        case 'activate':
                            updateCampaignStatus($db, $userId, $campaignId, 'ACTIVE');
                            break;
                        case 'pause':
                            updateCampaignStatus($db, $userId, $campaignId, 'PAUSED');
                            break;
                        case 'delete':
                            updateCampaignStatus($db, $userId, $campaignId, 'DELETED');
                            break;
                        case 'duplicate':
                            duplicateCampaign($db, $userId, $campaignId);
                            break;
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
            
            $response = [
                'success' => $results['failed'] === 0,
                'message' => "{$results['success']} campanhas processadas" . 
                            ($results['failed'] > 0 ? ", {$results['failed']} falharam" : ""),
                'data' => $results
            ];
            break;
            
        // ========================================
        // SALVAR COLUNAS
        // ========================================
        case 'save_columns':
            $columns = $requestData['columns'] ?? [];
            
            if (empty($columns)) {
                throw new Exception('Colunas inválidas');
            }
            
            // Salva preferência
            $db->query("
                INSERT INTO user_preferences (user_id, preference_key, preference_value)
                VALUES (:user_id, :key, :value)
                ON DUPLICATE KEY UPDATE preference_value = :value
            ", [
                'user_id' => $userId,
                'key' => 'campaign_columns',
                'value' => json_encode($columns)
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Colunas salvas com sucesso',
                'data' => ['columns' => $columns]
            ];
            break;
            
        // ========================================
        // BUSCAR BREAKDOWNS
        // ========================================
        case 'get_breakdowns':
            $campaignId = intval($requestData['campaign_id'] ?? 0);
            $breakdownType = $requestData['breakdown_type'] ?? 'age,gender';
            
            $breakdowns = $db->fetchAll("
                SELECT * FROM insights_breakdowns
                WHERE campaign_id = :campaign_id
                AND breakdown_type = :breakdown_type
                ORDER BY date_stop DESC
                LIMIT 30
            ", [
                'campaign_id' => $campaignId,
                'breakdown_type' => $breakdownType
            ]);
            
            $response = [
                'success' => true,
                'data' => $breakdowns
            ];
            break;
            
        // ========================================
        // EXPORTAR DADOS
        // ========================================
        case 'export':
            $format = $requestData['format'] ?? 'csv';
            $filters = $requestData['filters'] ?? [];
            
            $campaigns = getCampaigns($db, $userId, $filters);
            
            switch ($format) {
                case 'csv':
                    exportCsv($campaigns);
                    break;
                case 'json':
                    $response = [
                        'success' => true,
                        'data' => $campaigns
                    ];
                    break;
                default:
                    throw new Exception('Formato não suportado');
            }
            break;
            
        // ========================================
        // AÇÃO NÃO RECONHECIDA
        // ========================================
        default:
            throw new Exception("Ação '$action' não reconhecida");
    }
    
} catch (Exception $e) {
    $code = $e->getCode() ?: 400;
    http_response_code($code);
    
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $code
    ];
    
    // Log de erro
    error_log("AJAX Error: " . $e->getMessage() . " - Action: " . ($action ?? 'none'));
}

// Envia resposta
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

/**
 * ✅ NOVO: Normaliza status para o padrão do banco
 */
function normalizeStatus($status) {
    $status = strtoupper(trim($status));
    
    $statusMap = [
        'ACTIVE' => 'active',
        'PAUSED' => 'paused',
        'DELETED' => 'deleted',
        'ARCHIVED' => 'archived',
        'CAMPAIGN_PAUSED' => 'paused',
        'ADSET_PAUSED' => 'paused',
        'IN_PROCESS' => 'active',
        'WITH_ISSUES' => 'paused',
        'DISAPPROVED' => 'paused',
        'PREAPPROVED' => 'active',
        'PENDING_REVIEW' => 'active',
        'PENDING_BILLING_INFO' => 'paused',
        'NOT_DELIVERING' => 'paused'
    ];
    
    return $statusMap[$status] ?? 'paused';
}

/**
 * Sincroniza apenas campanhas
 */
function syncCampaignsOnly($account, $db, $userId, $options = []) {
    $campaigns = [];
    $accountId = str_replace('act_', '', $account['account_id']);
    $accessToken = $account['access_token'];
    
    $url = "https://graph.facebook.com/v18.0/act_{$accountId}/campaigns";
    
    $params = [
        'fields' => implode(',', array_keys(MetaAdsDataStructure::CAMPAIGN_FIELDS)),
        'access_token' => $accessToken,
        'limit' => 50
    ];
    
    if (!empty($options['date_preset'])) {
        $params['date_preset'] = $options['date_preset'];
    }
    
    $response = makeApiCall($url . '?' . http_build_query($params));
    
    if (!empty($response['data'])) {
        foreach ($response['data'] as $campaign) {
            // ✅ CORREÇÃO: Log do status
            error_log("[AJAX-SYNC] Campanha {$campaign['name']}: status={$campaign['status']}, effective={$campaign['effective_status']}");
            
            // Salva campanha
            saveCampaign($db, $userId, $campaign, $account['id']);
            $campaigns[] = $campaign;
        }
    }
    
    return $campaigns;
}

/**
 * Busca campanhas do banco
 */
function getCampaigns($db, $userId, $filters = []) {
    $query = "
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
    ";
    
    $params = ['user_id' => $userId];
    
    // Aplica filtros
    if (!empty($filters['status'])) {
        $query .= " AND c.status = :status";
        $params['status'] = $filters['status'];
    }
    
    if (!empty($filters['search'])) {
        $query .= " AND c.campaign_name LIKE :search";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    $query .= " ORDER BY c.created_at DESC";
    
    return $db->fetchAll($query, $params);
}

/**
 * Calcula estatísticas
 */
function calculateStats($campaigns) {
    $stats = [
        'total_campaigns' => count($campaigns),
        'active_campaigns' => 0,
        'total_spend' => 0,
        'total_revenue' => 0,
        'total_impressions' => 0,
        'total_clicks' => 0,
        'total_purchases' => 0,
        'avg_roas' => 0
    ];
    
    foreach ($campaigns as $campaign) {
        if ($campaign['status'] === 'active') {
            $stats['active_campaigns']++;
        }
        $stats['total_spend'] += $campaign['spend'] ?? 0;
        $stats['total_revenue'] += $campaign['purchase_value'] ?? 0;
        $stats['total_impressions'] += $campaign['impressions'] ?? 0;
        $stats['total_clicks'] += $campaign['clicks'] ?? 0;
        $stats['total_purchases'] += $campaign['purchase'] ?? 0;
    }
    
    if ($stats['total_spend'] > 0) {
        $stats['avg_roas'] = round($stats['total_revenue'] / $stats['total_spend'], 2);
    }
    
    return $stats;
}

/**
 * Detecta campanha ASC/CBO
 */
function detectASCCampaign($campaign) {
    $campaignName = strtolower($campaign['campaign_name']);
    
    return (
        strpos($campaignName, 'advantage') !== false || 
        strpos($campaignName, 'asc') !== false ||
        strpos($campaignName, 'shopping') !== false ||
        $campaign['objective'] === 'OUTCOME_SALES'
    );
}

/**
 * Atualiza status no Meta
 */
function updateMetaStatus($campaignId, $status, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'status' => strtoupper($status), // Meta espera UPPERCASE
        'access_token' => $accessToken
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

/**
 * Atualiza orçamento no Meta
 */
function updateMetaBudget($campaignId, $budgetType, $value, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    // Converte para centavos
    $valueInCents = intval($value * 100);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        $budgetType => $valueInCents,
        'access_token' => $accessToken
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

/**
 * Atualiza campo genérico no Meta
 */
function updateMetaField($campaignId, $field, $value, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    // Mapeia campos
    $fieldMap = [
        'campaign_name' => 'name',
        'daily_budget' => 'daily_budget',
        'lifetime_budget' => 'lifetime_budget',
        'spend_cap' => 'spend_cap',
        'bid_strategy' => 'bid_strategy',
        'start_time' => 'start_time',
        'stop_time' => 'stop_time'
    ];
    
    $metaField = $fieldMap[$field] ?? $field;
    
    // Ajusta valor se necessário
    if (in_array($field, ['daily_budget', 'lifetime_budget', 'spend_cap'])) {
        $value = intval($value * 100);
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        $metaField => $value,
        'access_token' => $accessToken
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

/**
 * ✅ CORREÇÃO: Atualiza status da campanha (normaliza antes de salvar)
 */
function updateCampaignStatus($db, $userId, $campaignId, $status) {
    $campaign = $db->fetch("
        SELECT c.*, aa.access_token 
        FROM campaigns c
        LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
        WHERE c.id = :id AND c.user_id = :user_id
    ", [
        'id' => $campaignId,
        'user_id' => $userId
    ]);
    
    if (!$campaign) {
        throw new Exception('Campanha não encontrada');
    }
    
    // ✅ CORREÇÃO: Normaliza status antes de salvar
    $normalizedStatus = normalizeStatus($status);
    
    // Atualiza localmente
    $db->update('campaigns',
        ['status' => $normalizedStatus],
        'id = :id',
        ['id' => $campaignId]
    );
    
    // Atualiza no Meta
    if ($campaign['campaign_id'] && $campaign['access_token']) {
        updateMetaStatus($campaign['campaign_id'], $status, $campaign['access_token']);
    }
}

/**
 * Duplica campanha
 */
function duplicateCampaign($db, $userId, $campaignId) {
    $campaign = $db->fetch("
        SELECT * FROM campaigns 
        WHERE id = :id AND user_id = :user_id
    ", ['id' => $campaignId, 'user_id' => $userId]);
    
    if (!$campaign) {
        throw new Exception('Campanha não encontrada');
    }
    
    unset($campaign['id']);
    $campaign['campaign_name'] .= ' (Copy)';
    $campaign['created_time'] = date('Y-m-d H:i:s');
    $campaign['updated_time'] = date('Y-m-d H:i:s');
    
    $db->insert('campaigns', $campaign);
}

/**
 * ✅ CORREÇÃO: Salva campanha com status normalizado
 */
function saveCampaign($db, $userId, $campaignData, $accountId) {
    // ✅ CORREÇÃO: Determina status real (effective_status > status)
    $actualStatus = !empty($campaignData['effective_status']) 
        ? normalizeStatus($campaignData['effective_status'])
        : normalizeStatus($campaignData['status'] ?? 'PAUSED');
    
    $data = [
        'user_id' => $userId,
        'ad_account_id' => $accountId,
        'campaign_id' => $campaignData['id'],
        'campaign_name' => $campaignData['name'] ?? 'Sem nome',
        'status' => $actualStatus, // ✅ USA STATUS CORRETO
        'objective' => $campaignData['objective'] ?? null,
        'daily_budget' => isset($campaignData['daily_budget']) ? floatval($campaignData['daily_budget']) / 100 : 0,
        'lifetime_budget' => isset($campaignData['lifetime_budget']) ? floatval($campaignData['lifetime_budget']) / 100 : 0,
        'spend_cap' => isset($campaignData['spend_cap']) ? floatval($campaignData['spend_cap']) / 100 : 0,
        'created_time' => isset($campaignData['created_time']) ? date('Y-m-d H:i:s', strtotime($campaignData['created_time'])) : null,
        'updated_time' => isset($campaignData['updated_time']) ? date('Y-m-d H:i:s', strtotime($campaignData['updated_time'])) : null,
        'last_sync' => date('Y-m-d H:i:s')
    ];
    
    $exists = $db->fetch("
        SELECT id FROM campaigns 
        WHERE campaign_id = :campaign_id AND user_id = :user_id
    ", [
        'campaign_id' => $campaignData['id'],
        'user_id' => $userId
    ]);
    
    if ($exists) {
        $updateData = $data;
        unset($updateData['user_id']);
        
        error_log("[AJAX-SAVE] Atualizando campanha ID {$exists['id']} com status: {$actualStatus}");
        
        $db->update('campaigns', $updateData, 'id = :id', ['id' => $exists['id']]);
    } else {
        error_log("[AJAX-SAVE] Inserindo nova campanha com status: {$actualStatus}");
        
        $db->insert('campaigns', $data);
    }
}

/**
 * Faz chamada à API
 */
function makeApiCall($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("API Error: HTTP $httpCode");
    }
    
    return json_decode($response, true);
}

/**
 * Exporta CSV
 */
function exportCsv($campaigns) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="campaigns_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    $headers = [
        'ID', 'Nome', 'Status', 'Orçamento', 'Gastos', 
        'Impressões', 'Cliques', 'CTR', 'CPC', 'Compras', 
        'Valor Compras', 'ROAS', 'ROI', 'CPA'
    ];
    fputcsv($output, $headers);
    
    // Dados
    foreach ($campaigns as $campaign) {
        $row = [
            $campaign['campaign_id'],
            $campaign['campaign_name'],
            $campaign['status'],
            $campaign['daily_budget'] ?? $campaign['lifetime_budget'] ?? 0,
            $campaign['spend'] ?? 0,
            $campaign['impressions'] ?? 0,
            $campaign['clicks'] ?? 0,
            $campaign['ctr'] ?? 0,
            $campaign['cpc'] ?? 0,
            $campaign['purchase'] ?? 0,
            $campaign['purchase_value'] ?? 0,
            $campaign['roas'] ?? 0,
            $campaign['roi'] ?? 0,
            $campaign['cpa'] ?? 0
        ];
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Monta mensagem de sincronização
 */
function buildSyncMessage($results) {
    $parts = [];
    
    if ($results['campaigns']['synced'] > 0) {
        $parts[] = $results['campaigns']['synced'] . " campanhas";
    }
    if ($results['adsets']['synced'] > 0) {
        $parts[] = $results['adsets']['synced'] . " conjuntos";
    }
    if ($results['ads']['synced'] > 0) {
        $parts[] = $results['ads']['synced'] . " anúncios";
    }
    
    $message = !empty($parts) 
        ? "Sincronizados: " . implode(', ', $parts) 
        : "Nenhum dado novo";
    
    if (!empty($results['campaigns']['errors'])) {
        $message .= " | " . count($results['campaigns']['errors']) . " erro(s)";
    }
    
    $message .= " | " . $results['duration'] . "s";
    
    return $message;
}