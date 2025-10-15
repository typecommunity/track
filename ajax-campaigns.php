<?php
/**
 * ========================================
 * AJAX CAMPAIGNS V2.4 - VERSÃO FINAL
 * ========================================
 * 
 * LOCAL: /ajax_campaigns.php (RAIZ DO PROJETO)
 * 
 * ESTRUTURA CONFIRMADA:
 * - Database.php está em /core/
 * - Config.php NÃO EXISTE
 * - ajax_campaigns.php está na RAIZ
 * 
 * CORREÇÕES NESTA VERSÃO:
 * - ✅ Caminhos corretos (arquivo na raiz)
 * - ✅ Sem dependência de Config.php
 * - ✅ Normalização de status consistente
 * - ✅ Validação robusta de time_range
 * - ✅ Todos os 150+ campos suportados
 * - ✅ Logs detalhados para debug
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

// ✅ CORREÇÃO: Define base directory (ajax_campaigns.php está na RAIZ)
define('BASE_DIR', __DIR__);

// Response padrão
$response = [
    'success' => false,
    'message' => 'Requisição inválida',
    'data' => null
];

try {
    // ========================================
    // 1. VERIFICAÇÃO DE AUTENTICAÇÃO
    // ========================================
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Não autorizado - faça login novamente', 401);
    }
    
    $userId = $_SESSION['user_id'];
    
    // ========================================
    // 2. CARREGA DEPENDÊNCIAS (CAMINHOS CORRETOS)
    // ========================================
    require_once BASE_DIR . '/core/Database.php';
    require_once BASE_DIR . '/core/MetaAdsDataStructure.php';
    require_once BASE_DIR . '/core/MetaAdsSync.php';
    
    // ✅ CORREÇÃO: Config.php não existe, não precisa carregar
    
    // Inicializa database
    $db = Database::getInstance();
    
    // ========================================
    // 3. PEGA ACTION E DADOS
    // ========================================
    $action = $_GET['ajax_action'] ?? $_POST['action'] ?? null;
    $requestData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Log para debug
    error_log("[AJAX] ========================================");
    error_log("[AJAX] Action: {$action}");
    error_log("[AJAX] User ID: {$userId}");
    error_log("[AJAX] Request Data: " . json_encode($requestData));
    error_log("[AJAX] ========================================");
    
    // ========================================
    // 4. PROCESSA AÇÃO
    // ========================================
    
    switch ($action) {
        
        // ========================================
        // SINCRONIZAÇÃO COMPLETA - 150+ CAMPOS
        // ========================================
        case 'sync_complete':
            error_log("[AJAX] 🔄 Iniciando sincronização completa...");
            
            $metaSync = new MetaAdsSync($db, $userId);
            
            // ✅ CORREÇÃO: Monta options com validação robusta
            $options = [
                'include_insights' => $requestData['include_insights'] ?? true,
                'include_actions' => $requestData['include_actions'] ?? true,
                'include_video_data' => $requestData['include_video_data'] ?? true,
                'include_demographics' => $requestData['include_demographics'] ?? false,
                'status' => $requestData['status'] ?? null,
                'breakdowns' => $requestData['breakdowns'] ?? []
            ];
            
            // ✅ CORREÇÃO: Valida e configura período corretamente
            if (!empty($requestData['date_preset'])) {
                $options['date_preset'] = $requestData['date_preset'];
                error_log("[AJAX] Usando date_preset: {$requestData['date_preset']}");
            } 
            elseif (!empty($requestData['time_range']) && 
                    is_array($requestData['time_range']) && 
                    isset($requestData['time_range']['since']) && 
                    isset($requestData['time_range']['until'])) {
                
                $options['date_preset'] = null;
                $options['time_range'] = $requestData['time_range'];
                error_log("[AJAX] Usando time_range: " . json_encode($requestData['time_range']));
            } 
            else {
                $options['date_preset'] = 'maximum';
                error_log("[AJAX] ⚠️ Nenhum período válido fornecido, usando 'maximum'");
            }
            
            // Executa sincronização
            $startTime = microtime(true);
            $results = $metaSync->syncAll($options);
            $duration = round(microtime(true) - $startTime, 2);
            
            // Log de conclusão
            error_log("[AJAX] ✅ Sincronização concluída em {$duration}s");
            error_log("[AJAX] Campanhas: {$results['campaigns']['synced']}");
            error_log("[AJAX] AdSets: {$results['adsets']['synced']}");
            error_log("[AJAX] Ads: {$results['ads']['synced']}");
            
            // Salva log no banco
            try {
                $db->insert('sync_logs', [
                    'user_id' => $userId,
                    'sync_type' => 'full',
                    'status' => empty($results['campaigns']['errors']) ? 'success' : 'partial',
                    'records_synced' => $results['campaigns']['synced'] + $results['adsets']['synced'] + $results['ads']['synced'],
                    'errors' => !empty($results['campaigns']['errors']) ? json_encode($results['campaigns']['errors']) : null,
                    'duration' => $duration,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                error_log("[AJAX] ⚠️ Erro ao salvar log: " . $e->getMessage());
            }
            
            $response = [
                'success' => true,
                'message' => buildSyncMessage($results),
                'data' => $results
            ];
            break;
            
        // ========================================
        // SINCRONIZAÇÃO APENAS DE CAMPANHAS
        // ========================================
        case 'sync_campaigns':
            error_log("[AJAX] 🔄 Sincronizando apenas campanhas...");
            
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
                    error_log("[AJAX] ✅ Conta {$account['account_name']}: " . count($campaigns) . " campanhas");
                } catch (Exception $e) {
                    $errors[] = [
                        'account' => $account['account_name'],
                        'error' => $e->getMessage()
                    ];
                    error_log("[AJAX] ❌ Erro na conta {$account['account_name']}: " . $e->getMessage());
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
            error_log("[AJAX] 📊 Buscando campanhas...");
            
            $filters = [
                'status' => $requestData['status'] ?? null,
                'objective' => $requestData['objective'] ?? null,
                'account_id' => $requestData['account_id'] ?? null,
                'search' => $requestData['search'] ?? null,
                'cbo' => isset($requestData['cbo']) ? (bool)$requestData['cbo'] : null,
                'asc' => isset($requestData['asc']) ? (bool)$requestData['asc'] : null,
                'has_issues' => isset($requestData['has_issues']) ? (bool)$requestData['has_issues'] : null,
                'quality_ranking' => $requestData['quality_ranking'] ?? null
            ];
            
            $campaigns = getCampaigns($db, $userId, $filters);
            $stats = calculateStats($campaigns);
            
            error_log("[AJAX] ✅ " . count($campaigns) . " campanhas encontradas");
            
            $response = [
                'success' => true,
                'data' => [
                    'campaigns' => $campaigns,
                    'stats' => $stats,
                    'count' => count($campaigns)
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
            
            error_log("[AJAX] 🔄 Atualizando status: Campaign ID {$campaignId} → {$newStatus}");
            
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
            
            // ✅ CORREÇÃO: Normaliza status antes de salvar
            $normalizedStatus = normalizeStatus($newStatus);
            
            // Atualiza localmente
            $db->update('campaigns',
                ['status' => $normalizedStatus],
                'id = :id',
                ['id' => $campaignId]
            );
            
            error_log("[AJAX] ✅ Status local atualizado: {$normalizedStatus}");
            
            // Tenta atualizar no Meta
            $metaUpdated = false;
            if ($metaCampaignId && $campaign['access_token']) {
                try {
                    $metaUpdated = updateMetaStatus(
                        $metaCampaignId,
                        strtoupper($newStatus), // Meta espera UPPERCASE
                        $campaign['access_token']
                    );
                    
                    if ($metaUpdated) {
                        error_log("[AJAX] ✅ Status atualizado no Meta Ads");
                    } else {
                        error_log("[AJAX] ⚠️ Falha ao atualizar status no Meta Ads");
                    }
                } catch (Exception $e) {
                    error_log("[AJAX] ❌ Erro ao atualizar Meta: " . $e->getMessage());
                }
            }
            
            $response = [
                'success' => true,
                'message' => $metaUpdated ? 'Status atualizado no Meta Ads' : 'Status atualizado localmente',
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
            
            error_log("[AJAX] 💰 Atualizando orçamento: Campaign ID {$campaignId}, {$budgetType} = R$ {$newBudget}");
            
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
            $isCBO = !empty($campaign['campaign_budget_optimization']);
            
            error_log("[AJAX] ASC: " . ($isASC ? 'Sim' : 'Não') . " | CBO: " . ($isCBO ? 'Sim' : 'Não'));
            
            // Atualiza localmente
            $db->update('campaigns',
                [$budgetType => $newBudget],
                'id = :id',
                ['id' => $campaignId]
            );
            
            // Tenta atualizar no Meta (se não for ASC)
            $metaUpdated = false;
            if (!$isASC && $metaCampaignId && $campaign['access_token']) {
                try {
                    $metaUpdated = updateMetaBudget(
                        $metaCampaignId,
                        $budgetType,
                        $newBudget,
                        $campaign['access_token']
                    );
                    
                    if ($metaUpdated) {
                        error_log("[AJAX] ✅ Orçamento atualizado no Meta Ads");
                    }
                } catch (Exception $e) {
                    error_log("[AJAX] ❌ Erro ao atualizar Meta: " . $e->getMessage());
                }
            }
            
            $message = $isASC 
                ? 'Campanha ASC - orçamento atualizado apenas localmente' 
                : ($metaUpdated ? 'Orçamento atualizado no Meta Ads' : 'Orçamento atualizado localmente');
            
            $response = [
                'success' => true,
                'message' => $message,
                'data' => [
                    'meta_updated' => $metaUpdated,
                    'is_asc' => $isASC,
                    'is_cbo' => $isCBO
                ]
            ];
            break;
            
        // ========================================
        // ATUALIZAR CAMPO GENÉRICO
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
            
            error_log("[AJAX] ✏️ Atualizando campo: Campaign ID {$campaignId}, {$field} = {$value}");
            
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
                try {
                    $metaUpdated = updateMetaField(
                        $campaign['campaign_id'],
                        $field,
                        $value,
                        $campaign['access_token']
                    );
                } catch (Exception $e) {
                    error_log("[AJAX] ❌ Erro ao atualizar Meta: " . $e->getMessage());
                }
            }
            
            $response = [
                'success' => true,
                'message' => $metaUpdated ? 'Campo atualizado no Meta Ads' : 'Campo atualizado localmente',
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
            
            error_log("[AJAX] 📦 Ação em massa: {$bulkAction} em " . count($campaignIds) . " campanhas");
            
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
            
            error_log("[AJAX] ✅ Sucesso: {$results['success']} | ❌ Falhas: {$results['failed']}");
            
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
            
            error_log("[AJAX] 💾 Salvando " . count($columns) . " colunas");
            
            // Salva preferência
            $db->query("
                INSERT INTO user_preferences (user_id, preference_key, preference_value, updated_at)
                VALUES (:user_id, :key, :value, NOW())
                ON DUPLICATE KEY UPDATE 
                    preference_value = :value,
                    updated_at = NOW()
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
            
            error_log("[AJAX] 📊 Buscando breakdowns: Campaign {$campaignId}, tipo {$breakdownType}");
            
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
            
            error_log("[AJAX] 📥 Exportando dados em formato: {$format}");
            
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
            throw new Exception("Ação '{$action}' não reconhecida");
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
    error_log("[AJAX] ❌ ERRO: " . $e->getMessage() . " | Action: " . ($action ?? 'none') . " | Line: " . $e->getLine());
}

// Envia resposta
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

/**
 * ✅ Normaliza status para o padrão do banco
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
            saveCampaign($db, $userId, $campaign, $account['id']);
            $campaigns[] = $campaign;
        }
    }
    
    return $campaigns;
}

/**
 * Busca campanhas do banco com TODOS os filtros
 */
function getCampaigns($db, $userId, $filters = []) {
    $query = "
        SELECT 
            c.*,
            aa.account_name,
            ci.impressions,
            ci.clicks,
            ci.spend,
            ci.reach,
            ci.frequency,
            ci.ctr,
            ci.cpc,
            ci.cpm,
            ci.purchase,
            ci.purchase_value,
            ci.roas,
            ci.roi,
            ci.cpa,
            ci.quality_ranking,
            ci.engagement_rate_ranking,
            ci.conversion_rate_ranking
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
    
    if (!empty($filters['objective'])) {
        $query .= " AND c.objective = :objective";
        $params['objective'] = $filters['objective'];
    }
    
    if (!empty($filters['account_id'])) {
        $query .= " AND c.ad_account_id = :account_id";
        $params['account_id'] = $filters['account_id'];
    }
    
    if (!empty($filters['search'])) {
        $query .= " AND (c.campaign_name LIKE :search OR c.campaign_id LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    // ✅ NOVOS: Filtros avançados
    if (isset($filters['cbo']) && $filters['cbo'] !== null) {
        $query .= " AND c.campaign_budget_optimization = :cbo";
        $params['cbo'] = $filters['cbo'] ? 1 : 0;
    }
    
    if (isset($filters['asc']) && $filters['asc'] !== null) {
        $query .= " AND c.is_asc = :asc";
        $params['asc'] = $filters['asc'] ? 1 : 0;
    }
    
    if (isset($filters['has_issues']) && $filters['has_issues']) {
        $query .= " AND c.issues_info IS NOT NULL";
    }
    
    if (!empty($filters['quality_ranking'])) {
        $query .= " AND ci.quality_ranking = :quality_ranking";
        $params['quality_ranking'] = $filters['quality_ranking'];
    }
    
    $query .= " ORDER BY c.created_time DESC";
    
    if (!empty($filters['limit'])) {
        $query .= " LIMIT " . intval($filters['limit']);
    }
    
    return $db->fetchAll($query, $params);
}

/**
 * Calcula estatísticas
 */
function calculateStats($campaigns) {
    $stats = [
        'total_campaigns' => count($campaigns),
        'active_campaigns' => 0,
        'cbo_campaigns' => 0,
        'asc_campaigns' => 0,
        'campaigns_with_issues' => 0,
        'total_spend' => 0,
        'total_revenue' => 0,
        'total_impressions' => 0,
        'total_clicks' => 0,
        'total_purchases' => 0,
        'avg_roas' => 0,
        'avg_roi' => 0
    ];
    
    foreach ($campaigns as $campaign) {
        if ($campaign['status'] === 'active') {
            $stats['active_campaigns']++;
        }
        
        if (!empty($campaign['campaign_budget_optimization'])) {
            $stats['cbo_campaigns']++;
        }
        
        if (!empty($campaign['is_asc'])) {
            $stats['asc_campaigns']++;
        }
        
        if (!empty($campaign['issues_info'])) {
            $stats['campaigns_with_issues']++;
        }
        
        $stats['total_spend'] += $campaign['spend'] ?? 0;
        $stats['total_revenue'] += $campaign['purchase_value'] ?? 0;
        $stats['total_impressions'] += $campaign['impressions'] ?? 0;
        $stats['total_clicks'] += $campaign['clicks'] ?? 0;
        $stats['total_purchases'] += $campaign['purchase'] ?? 0;
    }
    
    if ($stats['total_spend'] > 0) {
        $stats['avg_roas'] = round($stats['total_revenue'] / $stats['total_spend'], 2);
        $stats['avg_roi'] = round((($stats['total_revenue'] - $stats['total_spend']) / $stats['total_spend']) * 100, 2);
    }
    
    return $stats;
}

/**
 * Detecta campanha ASC/CBO
 */
function detectASCCampaign($campaign) {
    $campaignName = strtolower($campaign['campaign_name'] ?? '');
    $objective = $campaign['objective'] ?? '';
    
    if ($objective === 'OUTCOME_SALES') {
        return true;
    }
    
    $ascKeywords = ['advantage', 'asc', 'shopping', 'advantage+', 'advantage shopping'];
    foreach ($ascKeywords as $keyword) {
        if (strpos($campaignName, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
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
        'status' => strtoupper($status),
        'access_token' => $accessToken
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

/**
 * Atualiza status da campanha
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
    
    $normalizedStatus = normalizeStatus($status);
    
    $db->update('campaigns',
        ['status' => $normalizedStatus],
        'id = :id',
        ['id' => $campaignId]
    );
    
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
    $campaign['campaign_name'] .= ' (Cópia)';
    $campaign['created_time'] = date('Y-m-d H:i:s');
    $campaign['updated_time'] = date('Y-m-d H:i:s');
    
    $db->insert('campaigns', $campaign);
}

/**
 * Salva campanha com status normalizado
 */
function saveCampaign($db, $userId, $campaignData, $accountId) {
    $actualStatus = !empty($campaignData['effective_status']) 
        ? normalizeStatus($campaignData['effective_status'])
        : normalizeStatus($campaignData['status'] ?? 'PAUSED');
    
    $data = [
        'user_id' => $userId,
        'ad_account_id' => $accountId,
        'campaign_id' => $campaignData['id'],
        'campaign_name' => $campaignData['name'] ?? 'Sem nome',
        'status' => $actualStatus,
        'objective' => $campaignData['objective'] ?? null,
        'daily_budget' => isset($campaignData['daily_budget']) ? floatval($campaignData['daily_budget']) / 100 : 0,
        'lifetime_budget' => isset($campaignData['lifetime_budget']) ? floatval($campaignData['lifetime_budget']) / 100 : 0,
        'spend_cap' => isset($campaignData['spend_cap']) ? floatval($campaignData['spend_cap']) / 100 : 0,
        'campaign_budget_optimization' => isset($campaignData['campaign_budget_optimization']) ? 1 : 0,
        'is_asc' => detectASCCampaign($campaignData) ? 1 : 0,
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
        $db->update('campaigns', $updateData, 'id = :id', ['id' => $exists['id']]);
    } else {
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
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="campaigns_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    // Headers
    $headers = [
        'ID', 'Nome', 'Status', 'Objetivo', 'CBO', 'ASC',
        'Orçamento Diário', 'Gastos', 'Impressões', 'Cliques', 
        'CTR', 'CPC', 'CPM', 'Compras', 'Valor Compras', 
        'ROAS', 'ROI', 'CPA', 'Margem'
    ];
    fputcsv($output, $headers);
    
    // Dados
    foreach ($campaigns as $campaign) {
        $row = [
            $campaign['campaign_id'],
            $campaign['campaign_name'],
            $campaign['status'],
            $campaign['objective'] ?? '',
            $campaign['campaign_budget_optimization'] ? 'Sim' : 'Não',
            $campaign['is_asc'] ? 'Sim' : 'Não',
            $campaign['daily_budget'] ?? $campaign['lifetime_budget'] ?? 0,
            $campaign['spend'] ?? 0,
            $campaign['impressions'] ?? 0,
            $campaign['clicks'] ?? 0,
            ($campaign['ctr'] ?? 0) . '%',
            $campaign['cpc'] ?? 0,
            $campaign['cpm'] ?? 0,
            $campaign['purchase'] ?? 0,
            $campaign['purchase_value'] ?? 0,
            $campaign['roas'] ?? 0,
            ($campaign['roi'] ?? 0) . '%',
            $campaign['cpa'] ?? 0,
            ($campaign['margin'] ?? 0) . '%'
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
    
    if (isset($results['campaigns']['synced']) && $results['campaigns']['synced'] > 0) {
        $parts[] = $results['campaigns']['synced'] . " campanhas";
    }
    if (isset($results['adsets']['synced']) && $results['adsets']['synced'] > 0) {
        $parts[] = $results['adsets']['synced'] . " conjuntos";
    }
    if (isset($results['ads']['synced']) && $results['ads']['synced'] > 0) {
        $parts[] = $results['ads']['synced'] . " anúncios";
    }
    
    $message = !empty($parts) 
        ? "Sincronizados: " . implode(', ', $parts) 
        : "Nenhum dado novo";
    
    if (isset($results['campaigns']['errors']) && !empty($results['campaigns']['errors'])) {
        $message .= " | " . count($results['campaigns']['errors']) . " erro(s)";
    }
    
    if (isset($results['duration'])) {
        $message .= " | {$results['duration']}s";
    }
    
    return $message;
}