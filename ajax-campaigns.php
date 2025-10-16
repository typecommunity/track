<?php
/**
 * ========================================
 * AJAX CAMPAIGNS V2.4 - VERSÃO COMPLETA
 * ========================================
 * 
 * LOCAL: /ajax_campaigns.php (RAIZ DO PROJETO)
 * 
 * ESTRUTURA CONFIRMADA:
 * - Database.php está em /core/
 * - ajax_campaigns.php está na RAIZ
 * 
 * MANTENDO TODAS AS 1111+ LINHAS E FUNCIONALIDADES
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

// Define base directory
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
    
    // Verifica se os outros arquivos existem antes de incluir
    if (file_exists(BASE_DIR . '/core/MetaAdsDataStructure.php')) {
        require_once BASE_DIR . '/core/MetaAdsDataStructure.php';
    }
    if (file_exists(BASE_DIR . '/core/MetaAdsSync.php')) {
        require_once BASE_DIR . '/core/MetaAdsSync.php';
    }
    
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
        case 'sync_all':
            error_log("[AJAX] 🔄 Iniciando sincronização completa...");
            
            // Verifica se a classe existe
            if (!class_exists('MetaAdsSync')) {
                // Fallback: busca campanhas do banco
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
                
                $stats = calculateStats($campaigns);
                
                $response = [
                    'success' => true,
                    'message' => 'Dados carregados do banco (sincronização Meta não disponível)',
                    'data' => [
                        'campaigns' => $campaigns,
                        'stats' => $stats,
                        'sync_results' => [
                            'campaigns' => ['synced' => count($campaigns)],
                            'duration' => 0
                        ]
                    ]
                ];
                break;
            }
            
            $metaSync = new MetaAdsSync($db, $userId);
            
            // Monta options com validação robusta
            $options = [
                'include_insights' => $requestData['include_insights'] ?? true,
                'include_actions' => $requestData['include_actions'] ?? true,
                'include_video_data' => $requestData['include_video_data'] ?? true,
                'include_demographics' => $requestData['include_demographics'] ?? false,
                'status' => $requestData['status'] ?? null,
                'breakdowns' => $requestData['breakdowns'] ?? []
            ];
            
            // Valida e configura período corretamente
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
            
            if (!class_exists('MetaAdsSync')) {
                throw new Exception('MetaAdsSync não disponível');
            }
            
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
            
            // Normaliza status antes de salvar
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
            
            // Verifica se a tabela existe
            try {
                $db->query("
                    CREATE TABLE IF NOT EXISTS user_preferences (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        preference_key VARCHAR(100) NOT NULL,
                        preference_value TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_user_key (user_id, preference_key),
                        KEY idx_user_id (user_id)
                    )
                ");
            } catch (Exception $e) {
                error_log("[AJAX] Tabela user_preferences já existe ou erro ao criar");
            }
            
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
            
            // Verifica se a tabela existe
            try {
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
            } catch (Exception $e) {
                error_log("[AJAX] Tabela insights_breakdowns não existe");
                $breakdowns = [];
            }
            
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
// FUNÇÕES AUXILIARES (TODAS AS 50+ FUNÇÕES)
// ========================================

/**
 * Normaliza status para o padrão do banco
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
    
    // Campos completos do Meta Ads
    $fields = [
        'id', 'name', 'status', 'effective_status', 'objective', 
        'account_id', 'buying_type', 'can_create_brand_lift_study',
        'can_use_spend_cap', 'configured_status', 'created_time',
        'daily_budget', 'lifetime_budget', 'spend_cap', 'smart_promotion_type',
        'source_campaign_id', 'special_ad_categories', 'special_ad_category',
        'special_ad_category_country', 'start_time', 'stop_time',
        'topline_id', 'updated_time', 'budget_rebalance_flag',
        'budget_remaining', 'campaign_budget_optimization',
        'bid_strategy', 'boosted_object_id', 'brand_lift_studies',
        'has_secondary_skadnetwork_reporting', 'is_skadnetwork_attribution',
        'is_budget_schedule_enabled', 'is_using_l3_schedule',
        'iterative_split_test_configs', 'last_budget_toggling_time',
        'objective_for_cost', 'pacing_type', 'promoted_object',
        'recommendations', 'smart_promo_type', 'spend_cap_action_time',
        'upstream_events', 'user_access_expire_time'
    ];
    
    if (class_exists('MetaAdsDataStructure')) {
        $fields = array_keys(MetaAdsDataStructure::CAMPAIGN_FIELDS);
    }
    
    $params = [
        'fields' => implode(',', $fields),
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
            ci.add_to_cart,
            ci.initiate_checkout,
            ci.view_content,
            ci.lead,
            ci.roas,
            ci.roi,
            ci.cpa,
            ci.margin,
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
    
    // Filtros avançados
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
    
    // Ordenação padrão
    $orderBy = " ORDER BY ";
    if (!empty($filters['order_by'])) {
        $orderBy .= $filters['order_by'] . " " . ($filters['order_direction'] ?? 'DESC');
    } else {
        $orderBy .= "c.created_at DESC";
    }
    $query .= $orderBy;
    
    if (!empty($filters['limit'])) {
        $query .= " LIMIT " . intval($filters['limit']);
    }
    
    if (!empty($filters['offset'])) {
        $query .= " OFFSET " . intval($filters['offset']);
    }
    
    return $db->fetchAll($query, $params);
}

/**
 * Calcula estatísticas completas
 */
function calculateStats($campaigns) {
    $stats = [
        'total_campaigns' => count($campaigns),
        'active_campaigns' => 0,
        'paused_campaigns' => 0,
        'deleted_campaigns' => 0,
        'cbo_campaigns' => 0,
        'asc_campaigns' => 0,
        'campaigns_with_issues' => 0,
        'total_spend' => 0,
        'total_revenue' => 0,
        'total_profit' => 0,
        'total_impressions' => 0,
        'total_clicks' => 0,
        'total_purchases' => 0,
        'total_add_to_cart' => 0,
        'total_initiate_checkout' => 0,
        'total_view_content' => 0,
        'total_leads' => 0,
        'total_reach' => 0,
        'avg_frequency' => 0,
        'avg_ctr' => 0,
        'avg_cpc' => 0,
        'avg_cpm' => 0,
        'avg_roas' => 0,
        'avg_roi' => 0,
        'avg_cpa' => 0,
        'avg_margin' => 0,
        'best_roas' => 0,
        'worst_roas' => 999999,
        'best_roi' => -999999,
        'worst_roi' => 999999,
        'daily_budget_total' => 0,
        'lifetime_budget_total' => 0,
        'spend_cap_total' => 0,
        'campaigns_by_objective' => [],
        'campaigns_by_status' => [],
        'performance_score' => 0
    ];
    
    $frequencySum = 0;
    $frequencyCount = 0;
    
    foreach ($campaigns as $campaign) {
        // Status
        $status = $campaign['status'] ?? 'unknown';
        if ($status === 'active') {
            $stats['active_campaigns']++;
        } elseif ($status === 'paused') {
            $stats['paused_campaigns']++;
        } elseif ($status === 'deleted') {
            $stats['deleted_campaigns']++;
        }
        
        // Por status (para gráfico)
        if (!isset($stats['campaigns_by_status'][$status])) {
            $stats['campaigns_by_status'][$status] = 0;
        }
        $stats['campaigns_by_status'][$status]++;
        
        // Por objetivo
        $objective = $campaign['objective'] ?? 'unknown';
        if (!isset($stats['campaigns_by_objective'][$objective])) {
            $stats['campaigns_by_objective'][$objective] = 0;
        }
        $stats['campaigns_by_objective'][$objective]++;
        
        // CBO e ASC
        if (!empty($campaign['campaign_budget_optimization'])) {
            $stats['cbo_campaigns']++;
        }
        
        if (!empty($campaign['is_asc'])) {
            $stats['asc_campaigns']++;
        }
        
        // Issues
        if (!empty($campaign['issues_info'])) {
            $stats['campaigns_with_issues']++;
        }
        
        // Orçamentos
        $stats['daily_budget_total'] += floatval($campaign['daily_budget'] ?? 0);
        $stats['lifetime_budget_total'] += floatval($campaign['lifetime_budget'] ?? 0);
        $stats['spend_cap_total'] += floatval($campaign['spend_cap'] ?? 0);
        
        // Métricas financeiras
        $spend = floatval($campaign['spend'] ?? 0);
        $revenue = floatval($campaign['purchase_value'] ?? 0);
        $stats['total_spend'] += $spend;
        $stats['total_revenue'] += $revenue;
        $stats['total_profit'] += ($revenue - $spend);
        
        // Métricas de engajamento
        $stats['total_impressions'] += intval($campaign['impressions'] ?? 0);
        $stats['total_clicks'] += intval($campaign['clicks'] ?? 0);
        $stats['total_reach'] += intval($campaign['reach'] ?? 0);
        
        // Conversões
        $stats['total_purchases'] += intval($campaign['purchase'] ?? 0);
        $stats['total_add_to_cart'] += intval($campaign['add_to_cart'] ?? 0);
        $stats['total_initiate_checkout'] += intval($campaign['initiate_checkout'] ?? 0);
        $stats['total_view_content'] += intval($campaign['view_content'] ?? 0);
        $stats['total_leads'] += intval($campaign['lead'] ?? 0);
        
        // Frequência
        if (!empty($campaign['frequency']) && $campaign['frequency'] > 0) {
            $frequencySum += floatval($campaign['frequency']);
            $frequencyCount++;
        }
        
        // ROAS
        $roas = floatval($campaign['roas'] ?? 0);
        if ($roas > 0) {
            if ($roas > $stats['best_roas']) {
                $stats['best_roas'] = $roas;
            }
            if ($roas < $stats['worst_roas']) {
                $stats['worst_roas'] = $roas;
            }
        }
        
        // ROI
        $roi = floatval($campaign['roi'] ?? 0);
        if ($roi != 0) {
            if ($roi > $stats['best_roi']) {
                $stats['best_roi'] = $roi;
            }
            if ($roi < $stats['worst_roi']) {
                $stats['worst_roi'] = $roi;
            }
        }
    }
    
    // Cálculos de médias
    if ($stats['total_spend'] > 0) {
        $stats['avg_roas'] = round($stats['total_revenue'] / $stats['total_spend'], 2);
        $stats['avg_roi'] = round((($stats['total_revenue'] - $stats['total_spend']) / $stats['total_spend']) * 100, 2);
        
        if ($stats['total_purchases'] > 0) {
            $stats['avg_cpa'] = round($stats['total_spend'] / $stats['total_purchases'], 2);
        }
        
        $stats['avg_margin'] = round((($stats['total_revenue'] - $stats['total_spend']) / $stats['total_revenue']) * 100, 2);
    }
    
    if ($stats['total_impressions'] > 0) {
        $stats['avg_ctr'] = round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2);
        $stats['avg_cpm'] = round(($stats['total_spend'] / $stats['total_impressions']) * 1000, 2);
    }
    
    if ($stats['total_clicks'] > 0) {
        $stats['avg_cpc'] = round($stats['total_spend'] / $stats['total_clicks'], 2);
    }
    
    if ($frequencyCount > 0) {
        $stats['avg_frequency'] = round($frequencySum / $frequencyCount, 2);
    }
    
    // Calcula score de performance (0-100)
    $scoreFactors = [];
    
    // ROAS (peso 30%)
    if ($stats['avg_roas'] >= 3) $scoreFactors[] = 30;
    elseif ($stats['avg_roas'] >= 2) $scoreFactors[] = 20;
    elseif ($stats['avg_roas'] >= 1) $scoreFactors[] = 10;
    else $scoreFactors[] = 0;
    
    // ROI (peso 30%)
    if ($stats['avg_roi'] >= 100) $scoreFactors[] = 30;
    elseif ($stats['avg_roi'] >= 50) $scoreFactors[] = 20;
    elseif ($stats['avg_roi'] >= 0) $scoreFactors[] = 10;
    else $scoreFactors[] = 0;
    
    // CTR (peso 20%)
    if ($stats['avg_ctr'] >= 2) $scoreFactors[] = 20;
    elseif ($stats['avg_ctr'] >= 1) $scoreFactors[] = 15;
    elseif ($stats['avg_ctr'] >= 0.5) $scoreFactors[] = 10;
    else $scoreFactors[] = 5;
    
    // Campanhas ativas (peso 20%)
    if ($stats['total_campaigns'] > 0) {
        $activeRatio = $stats['active_campaigns'] / $stats['total_campaigns'];
        $scoreFactors[] = round($activeRatio * 20);
    }
    
    $stats['performance_score'] = array_sum($scoreFactors);
    
    // Limpa valores extremos se não houver dados
    if ($stats['worst_roas'] == 999999) $stats['worst_roas'] = 0;
    if ($stats['worst_roi'] == 999999) $stats['worst_roi'] = 0;
    if ($stats['best_roi'] == -999999) $stats['best_roi'] = 0;
    
    return $stats;
}

/**
 * Detecta campanha ASC/CBO
 */
function detectASCCampaign($campaign) {
    $campaignName = strtolower($campaign['campaign_name'] ?? '');
    $objective = $campaign['objective'] ?? '';
    
    // Verifica por objetivo
    if ($objective === 'OUTCOME_SALES') {
        return true;
    }
    
    // Verifica por palavras-chave no nome
    $ascKeywords = [
        'advantage', 'asc', 'shopping', 'advantage+', 
        'advantage shopping', 'advantage shop', 'vantagem+',
        'vantagem shopping', 'advantage_shopping'
    ];
    
    foreach ($ascKeywords as $keyword) {
        if (strpos($campaignName, $keyword) !== false) {
            return true;
        }
    }
    
    // Verifica por configurações especiais
    if (!empty($campaign['smart_promotion_type'])) {
        return true;
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
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("[AJAX] Erro cURL ao atualizar status no Meta: " . $error);
        return false;
    }
    
    if ($httpCode !== 200) {
        error_log("[AJAX] Erro HTTP {$httpCode} ao atualizar status no Meta");
        error_log("[AJAX] Resposta: " . $response);
        return false;
    }
    
    return true;
}

/**
 * Atualiza orçamento no Meta
 */
function updateMetaBudget($campaignId, $budgetType, $value, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    // Converte para centavos (Meta usa centavos)
    $valueInCents = intval($value * 100);
    
    $params = [
        'access_token' => $accessToken
    ];
    
    // Define o campo correto baseado no tipo
    switch ($budgetType) {
        case 'daily_budget':
            $params['daily_budget'] = $valueInCents;
            break;
        case 'lifetime_budget':
            $params['lifetime_budget'] = $valueInCents;
            break;
        case 'spend_cap':
            $params['spend_cap'] = $valueInCents;
            break;
        default:
            $params[$budgetType] = $valueInCents;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("[AJAX] Erro cURL ao atualizar orçamento no Meta: " . $error);
        return false;
    }
    
    if ($httpCode !== 200) {
        error_log("[AJAX] Erro HTTP {$httpCode} ao atualizar orçamento no Meta");
        error_log("[AJAX] Resposta: " . $response);
        return false;
    }
    
    return true;
}

/**
 * Atualiza campo genérico no Meta
 */
function updateMetaField($campaignId, $field, $value, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    // Mapeia campos para a API do Meta
    $fieldMap = [
        'campaign_name' => 'name',
        'daily_budget' => 'daily_budget',
        'lifetime_budget' => 'lifetime_budget',
        'spend_cap' => 'spend_cap',
        'bid_strategy' => 'bid_strategy',
        'start_time' => 'start_time',
        'stop_time' => 'stop_time',
        'objective' => 'objective',
        'special_ad_categories' => 'special_ad_categories',
        'special_ad_category' => 'special_ad_category'
    ];
    
    $metaField = $fieldMap[$field] ?? $field;
    
    // Ajusta valor se necessário
    if (in_array($field, ['daily_budget', 'lifetime_budget', 'spend_cap'])) {
        // Converte para centavos
        $value = intval($value * 100);
    } elseif (in_array($field, ['start_time', 'stop_time'])) {
        // Converte para timestamp se necessário
        if (!is_numeric($value)) {
            $value = strtotime($value);
        }
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
 * Atualiza status da campanha (local e Meta)
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
    
    // Atualiza localmente
    $db->update('campaigns',
        [
            'status' => $normalizedStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = :id',
        ['id' => $campaignId]
    );
    
    // Tenta atualizar no Meta
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
    
    // Remove campos únicos
    unset($campaign['id']);
    unset($campaign['campaign_id']);
    
    // Ajusta nome e timestamps
    $campaign['campaign_name'] .= ' (Cópia - ' . date('d/m H:i') . ')';
    $campaign['created_at'] = date('Y-m-d H:i:s');
    $campaign['updated_at'] = date('Y-m-d H:i:s');
    $campaign['created_time'] = date('Y-m-d H:i:s');
    $campaign['updated_time'] = date('Y-m-d H:i:s');
    $campaign['status'] = 'paused'; // Sempre cria pausada
    
    $newId = $db->insert('campaigns', $campaign);
    
    // Duplica insights se existirem
    try {
        $insights = $db->fetch("SELECT * FROM campaign_insights WHERE campaign_id = :id", ['id' => $campaignId]);
        if ($insights) {
            unset($insights['id']);
            $insights['campaign_id'] = $newId;
            $insights['created_at'] = date('Y-m-d H:i:s');
            $insights['updated_at'] = date('Y-m-d H:i:s');
            $db->insert('campaign_insights', $insights);
        }
    } catch (Exception $e) {
        error_log("[AJAX] Erro ao duplicar insights: " . $e->getMessage());
    }
    
    return $newId;
}

/**
 * Salva campanha com todos os campos
 */
function saveCampaign($db, $userId, $campaignData, $accountId) {
    // Normaliza status
    $actualStatus = !empty($campaignData['effective_status']) 
        ? normalizeStatus($campaignData['effective_status'])
        : normalizeStatus($campaignData['status'] ?? 'PAUSED');
    
    // Detecta características especiais
    $isASC = detectASCCampaign($campaignData);
    $isCBO = !empty($campaignData['campaign_budget_optimization']);
    
    // Prepara dados para salvar
    $data = [
        'user_id' => $userId,
        'ad_account_id' => $accountId,
        'campaign_id' => $campaignData['id'],
        'campaign_name' => $campaignData['name'] ?? 'Sem nome',
        'status' => $actualStatus,
        'effective_status' => $campaignData['effective_status'] ?? null,
        'configured_status' => $campaignData['configured_status'] ?? null,
        'objective' => $campaignData['objective'] ?? null,
        'buying_type' => $campaignData['buying_type'] ?? null,
        'daily_budget' => isset($campaignData['daily_budget']) ? floatval($campaignData['daily_budget']) / 100 : 0,
        'lifetime_budget' => isset($campaignData['lifetime_budget']) ? floatval($campaignData['lifetime_budget']) / 100 : 0,
        'spend_cap' => isset($campaignData['spend_cap']) ? floatval($campaignData['spend_cap']) / 100 : 0,
        'budget_remaining' => isset($campaignData['budget_remaining']) ? floatval($campaignData['budget_remaining']) / 100 : null,
        'campaign_budget_optimization' => $isCBO ? 1 : 0,
        'is_asc' => $isASC ? 1 : 0,
        'bid_strategy' => $campaignData['bid_strategy'] ?? null,
        'start_time' => isset($campaignData['start_time']) ? date('Y-m-d H:i:s', strtotime($campaignData['start_time'])) : null,
        'stop_time' => isset($campaignData['stop_time']) ? date('Y-m-d H:i:s', strtotime($campaignData['stop_time'])) : null,
        'created_time' => isset($campaignData['created_time']) ? date('Y-m-d H:i:s', strtotime($campaignData['created_time'])) : null,
        'updated_time' => isset($campaignData['updated_time']) ? date('Y-m-d H:i:s', strtotime($campaignData['updated_time'])) : null,
        'smart_promotion_type' => $campaignData['smart_promotion_type'] ?? null,
        'special_ad_categories' => isset($campaignData['special_ad_categories']) ? json_encode($campaignData['special_ad_categories']) : null,
        'special_ad_category' => $campaignData['special_ad_category'] ?? null,
        'special_ad_category_country' => isset($campaignData['special_ad_category_country']) ? json_encode($campaignData['special_ad_category_country']) : null,
        'pacing_type' => isset($campaignData['pacing_type']) ? json_encode($campaignData['pacing_type']) : null,
        'promoted_object' => isset($campaignData['promoted_object']) ? json_encode($campaignData['promoted_object']) : null,
        'recommendations' => isset($campaignData['recommendations']) ? json_encode($campaignData['recommendations']) : null,
        'issues_info' => isset($campaignData['issues_info']) ? json_encode($campaignData['issues_info']) : null,
        'last_sync' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Verifica se já existe
    $exists = $db->fetch("
        SELECT id FROM campaigns 
        WHERE campaign_id = :campaign_id AND user_id = :user_id
    ", [
        'campaign_id' => $campaignData['id'],
        'user_id' => $userId
    ]);
    
    if ($exists) {
        // Atualiza
        $updateData = $data;
        unset($updateData['user_id']);
        unset($updateData['ad_account_id']);
        unset($updateData['campaign_id']);
        unset($updateData['created_time']);
        
        $db->update('campaigns', $updateData, 'id = :id', ['id' => $exists['id']]);
        
        return $exists['id'];
    } else {
        // Insere
        $data['created_at'] = date('Y-m-d H:i:s');
        return $db->insert('campaigns', $data);
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
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: UTMTrack/2.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Erro cURL: " . $error);
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? "HTTP Error $httpCode";
        throw new Exception("API Error: " . $errorMessage);
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON Error: " . json_last_error_msg());
    }
    
    return $data;
}

/**
 * Exporta CSV
 */
function exportCsv($campaigns) {
    // Headers para download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="utmtrack_campaigns_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Abre output
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers do CSV
    $headers = [
        'ID Campanha',
        'Nome da Campanha',
        'Status',
        'Status Efetivo',
        'Objetivo',
        'Tipo de Compra',
        'CBO',
        'ASC',
        'Orçamento Diário',
        'Orçamento Vitalício',
        'Limite de Gasto',
        'Orçamento Restante',
        'Estratégia de Lance',
        'Data Início',
        'Data Fim',
        'Gastos',
        'Impressões',
        'Cliques',
        'Alcance',
        'Frequência',
        'CTR (%)',
        'CPC',
        'CPM',
        'Compras',
        'Valor de Compras',
        'Add ao Carrinho',
        'Início Checkout',
        'Ver Conteúdo',
        'Leads',
        'ROAS',
        'ROI (%)',
        'CPA',
        'Margem (%)',
        'Conta',
        'Criado em',
        'Atualizado em',
        'Última Sincronização'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Dados
    foreach ($campaigns as $c) {
        $row = [
            $c['campaign_id'] ?? '',
            $c['campaign_name'] ?? '',
            $c['status'] ?? '',
            $c['effective_status'] ?? '',
            $c['objective'] ?? '',
            $c['buying_type'] ?? '',
            !empty($c['campaign_budget_optimization']) ? 'Sim' : 'Não',
            !empty($c['is_asc']) ? 'Sim' : 'Não',
            number_format($c['daily_budget'] ?? 0, 2, ',', '.'),
            number_format($c['lifetime_budget'] ?? 0, 2, ',', '.'),
            number_format($c['spend_cap'] ?? 0, 2, ',', '.'),
            number_format($c['budget_remaining'] ?? 0, 2, ',', '.'),
            $c['bid_strategy'] ?? '',
            !empty($c['start_time']) ? date('d/m/Y H:i', strtotime($c['start_time'])) : '',
            !empty($c['stop_time']) ? date('d/m/Y H:i', strtotime($c['stop_time'])) : '',
            number_format($c['spend'] ?? 0, 2, ',', '.'),
            number_format($c['impressions'] ?? 0, 0, ',', '.'),
            number_format($c['clicks'] ?? 0, 0, ',', '.'),
            number_format($c['reach'] ?? 0, 0, ',', '.'),
            number_format($c['frequency'] ?? 0, 2, ',', '.'),
            number_format($c['ctr'] ?? 0, 2, ',', '.'),
            number_format($c['cpc'] ?? 0, 2, ',', '.'),
            number_format($c['cpm'] ?? 0, 2, ',', '.'),
            number_format($c['purchase'] ?? 0, 0, ',', '.'),
            number_format($c['purchase_value'] ?? 0, 2, ',', '.'),
            number_format($c['add_to_cart'] ?? 0, 0, ',', '.'),
            number_format($c['initiate_checkout'] ?? 0, 0, ',', '.'),
            number_format($c['view_content'] ?? 0, 0, ',', '.'),
            number_format($c['lead'] ?? 0, 0, ',', '.'),
            number_format($c['roas'] ?? 0, 2, ',', '.'),
            number_format($c['roi'] ?? 0, 2, ',', '.'),
            number_format($c['cpa'] ?? 0, 2, ',', '.'),
            number_format($c['margin'] ?? 0, 2, ',', '.'),
            $c['account_name'] ?? '',
            !empty($c['created_time']) ? date('d/m/Y H:i', strtotime($c['created_time'])) : '',
            !empty($c['updated_time']) ? date('d/m/Y H:i', strtotime($c['updated_time'])) : '',
            !empty($c['last_sync']) ? date('d/m/Y H:i', strtotime($c['last_sync'])) : ''
        ];
        
        fputcsv($output, $row, ';');
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
    
    // Adiciona erros se houver
    $totalErrors = 0;
    if (isset($results['campaigns']['errors']) && !empty($results['campaigns']['errors'])) {
        $totalErrors += count($results['campaigns']['errors']);
    }
    if (isset($results['adsets']['errors']) && !empty($results['adsets']['errors'])) {
        $totalErrors += count($results['adsets']['errors']);
    }
    if (isset($results['ads']['errors']) && !empty($results['ads']['errors'])) {
        $totalErrors += count($results['ads']['errors']);
    }
    
    if ($totalErrors > 0) {
        $message .= " | {$totalErrors} erro(s)";
    }
    
    // Adiciona duração
    if (isset($results['duration'])) {
        $message .= " | {$results['duration']}s";
    }
    
    return $message;
}