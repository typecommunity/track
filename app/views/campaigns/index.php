<?php
/**
 * ========================================
 * UTMTRACK - DASHBOARD DE CAMPANHAS V3.0
 * ========================================
 * 
 * Caminho: /utmtrack/app/views/campaigns/index.php
 * 
 * ✅ VERSÃO CORRIGIDA - URLs AJAX apontando para /ajax-campaigns.php
 * 
 * Funcionalidades completas:
 * - Edição inline de campos
 * - Filtros avançados
 * - Período customizado
 * - Todas as colunas disponíveis
 * - Handler AJAX completo
 * - ✅ URLs AJAX corrigidas (linhas 2527 e 2736)
 */

// ========================================
// HANDLER AJAX
// ========================================
if (isset($_GET['ajax_action'])) {
    // Limpa qualquer output anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Headers JSON
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Desabilita display de erros para JSON limpo
    ini_set('display_errors', '0');
    error_reporting(0);
    
    /**
     * Responde com JSON
     */
    function ajaxResponse($data, $httpCode = 200) {
        ob_get_clean();
        http_response_code($httpCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Responde com erro JSON
     */
    function ajaxError($message, $httpCode = 400, $details = null) {
        ajaxResponse([
            'success' => false,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ], $httpCode);
    }
    
    // Verifica autenticação
    if (!isset($_SESSION['user_id'])) {
        ajaxError('Não autorizado - faça login novamente', 401);
    }
    
    $userId = intval($_SESSION['user_id']);
    $action = $_GET['ajax_action'];
    
    // Pega dados da requisição
    $rawInput = file_get_contents('php://input');
    $requestData = json_decode($rawInput, true) ?? $_POST;
    
    try {
        // Carrega dependências
        $baseDir = dirname(__DIR__, 2);
        
        $requiredFiles = [
            $baseDir . '/core/Database.php',
            $baseDir . '/core/Config.php',
            $baseDir . '/core/MetaAdsDataStructure.php',
            $baseDir . '/core/MetaAdsSync.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                ajaxError("Arquivo não encontrado: " . basename($file), 500);
            }
            require_once $file;
        }
        
        $db = Database::getInstance();
        
        // Processa ação
        switch ($action) {
            
            // ========================================
            // SINCRONIZAÇÃO COMPLETA
            // ========================================
            case 'sync_complete':
            case 'sync_all':
                $metaSync = new MetaAdsSync($db, $userId);
                
                $options = [
                    'date_preset' => $requestData['date_preset'] ?? 'maximum',
                    'time_range' => $requestData['time_range'] ?? null,
                    'breakdowns' => $requestData['breakdowns'] ?? [],
                    'include_insights' => $requestData['include_insights'] ?? true,
                    'include_actions' => true,
                    'include_video_data' => true
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
                        ci.margin,
                        ci.cpa,
                        ci.cpi
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
                    'total_purchases' => 0,
                    'avg_roas' => 0
                ];
                
                foreach ($campaigns as $c) {
                    if ($c['status'] === 'active') {
                        $stats['active_campaigns']++;
                    }
                    $stats['total_spend'] += floatval($c['spend'] ?? 0);
                    $stats['total_revenue'] += floatval($c['purchase_value'] ?? 0);
                    $stats['total_purchases'] += intval($c['purchase'] ?? 0);
                }
                
                if ($stats['total_spend'] > 0) {
                    $stats['avg_roas'] = round($stats['total_revenue'] / $stats['total_spend'], 2);
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
                
                if (!$campaignId || !in_array($newStatus, ['ACTIVE', 'PAUSED', 'DELETED'])) {
                    ajaxError('Parâmetros inválidos', 400, [
                        'campaign_id' => $campaignId,
                        'status' => $newStatus
                    ]);
                }
                
                // Busca campanha com token
                $campaign = $db->fetch("
                    SELECT c.*, aa.access_token 
                    FROM campaigns c
                    LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
                    WHERE c.id = :id AND c.user_id = :user_id
                ", ['id' => $campaignId, 'user_id' => $userId]);
                
                if (!$campaign) {
                    ajaxError('Campanha não encontrada', 404);
                }
                
                // Atualiza no banco
                $updated = $db->update('campaigns',
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
                        'meta_updated' => $metaUpdated,
                        'meta_error' => $metaError
                    ]
                ]);
                break;
                
            // ========================================
            // ATUALIZAR CAMPO (EDIÇÃO INLINE)
            // ========================================
            case 'update_field':
            case 'update_budget':
                $campaignId = intval($requestData['campaign_id'] ?? 0);
                $field = $requestData['field'] ?? null;
                $value = $requestData['value'] ?? null;
                
                $allowedFields = [
                    'campaign_name', 'daily_budget', 'lifetime_budget', 
                    'spend_cap', 'bid_strategy', 'start_time', 'stop_time'
                ];
                
                if (!$campaignId || !$field || !in_array($field, $allowedFields)) {
                    ajaxError('Campo não permitido ou parâmetros inválidos', 400, [
                        'field' => $field,
                        'allowed' => $allowedFields
                    ]);
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
                
                // Verifica se é ASC/CBO
                $isASC = (
                    stripos($campaign['campaign_name'], 'advantage') !== false ||
                    stripos($campaign['campaign_name'], 'asc') !== false ||
                    stripos($campaign['campaign_name'], 'shopping') !== false ||
                    $campaign['objective'] === 'OUTCOME_SALES'
                );
                
                // Atualiza no banco
                $updated = $db->update('campaigns',
                    [$field => $value],
                    'id = :id',
                    ['id' => $campaignId]
                );
                
                $metaUpdated = false;
                
                ajaxResponse([
                    'success' => true,
                    'message' => $isASC && in_array($field, ['daily_budget', 'lifetime_budget'])
                        ? 'Campanha ASC/CBO - orçamento atualizado apenas localmente'
                        : 'Campo atualizado com sucesso',
                    'data' => [
                        'campaign_id' => $campaignId,
                        'field' => $field,
                        'value' => $value,
                        'is_asc' => $isASC,
                        'meta_updated' => $metaUpdated
                    ]
                ]);
                break;
                
            // ========================================
            // AÇÕES EM MASSA
            // ========================================
            case 'bulk_action':
                $bulkAction = $requestData['bulk_action'] ?? null;
                $campaignIds = $requestData['campaign_ids'] ?? [];
                
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
        ajaxError('Erro no servidor: ' . $e->getMessage(), 500);
    }
}

// ========================================
// CARREGAMENTO DE DADOS - PARTE PRINCIPAL
// ========================================

// Carrega dependências se ainda não carregadas
if (!class_exists('Database')) {
    require_once dirname(__DIR__, 2) . '/core/Database.php';
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'] ?? 0;

// Fallback para buscar campanhas
if (!isset($campaigns) || empty($campaigns)) {
    $campaigns = $db->fetchAll("
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
            ci.margin,
            ci.cpa,
            ci.cpi
        FROM campaigns c
        LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
        LEFT JOIN campaign_insights ci ON ci.campaign_id = c.id
        WHERE c.user_id = :user_id
        ORDER BY c.created_at DESC
    ", ['user_id' => $userId]);
    
    error_log("[VIEW] Campanhas carregadas direto da view: " . count($campaigns));
}

// Inicializa variáveis padrão
if (!isset($campaigns)) $campaigns = [];
if (!isset($stats)) $stats = [];
if (!isset($userColumns)) $userColumns = null;
if (!isset($user)) $user = [];
if (!isset($config)) $config = [];

// Calcula estatísticas
$stats = array_merge([
    'total_campaigns' => 0,
    'active_campaigns' => 0,
    'total_spend' => 0,
    'total_impressions' => 0,
    'total_clicks' => 0,
    'total_conversions' => 0,
    'total_purchases' => 0,
    'total_revenue' => 0,
    'total_profit' => 0,
    'ctr' => 0,
    'avg_cpc' => 0,
    'avg_roas' => 0,
    'avg_roi' => 0
], $stats);

if (is_array($campaigns) && count($campaigns) > 0) {
    $stats['total_campaigns'] = count($campaigns);
    $stats['active_campaigns'] = 0;
    $stats['total_spend'] = 0;
    $stats['total_revenue'] = 0;
    $stats['total_profit'] = 0;
    $stats['total_purchases'] = 0;
    $stats['total_impressions'] = 0;
    $stats['total_clicks'] = 0;
    $stats['total_conversions'] = 0;
    
    foreach ($campaigns as $campaign) {
        if (isset($campaign['status']) && $campaign['status'] === 'active') {
            $stats['active_campaigns']++;
        }
        
        $stats['total_spend'] += floatval($campaign['spend'] ?? 0);
        $stats['total_revenue'] += floatval($campaign['purchase_value'] ?? 0);
        $stats['total_purchases'] += intval($campaign['purchase'] ?? 0);
        $stats['total_impressions'] += intval($campaign['impressions'] ?? 0);
        $stats['total_clicks'] += intval($campaign['clicks'] ?? 0);
        
        $revenue = floatval($campaign['purchase_value'] ?? 0);
        $spend = floatval($campaign['spend'] ?? 0);
        $stats['total_profit'] += ($revenue - $spend);
    }
    
    if ($stats['total_spend'] > 0) {
        $stats['avg_roas'] = $stats['total_revenue'] / $stats['total_spend'];
        $stats['avg_roi'] = (($stats['total_revenue'] - $stats['total_spend']) / $stats['total_spend']) * 100;
    }
    if ($stats['total_impressions'] > 0) {
        $stats['ctr'] = ($stats['total_clicks'] / $stats['total_impressions']) * 100;
    }
    if ($stats['total_clicks'] > 0) {
        $stats['avg_cpc'] = $stats['total_spend'] / $stats['total_clicks'];
    }
}

// ========================================
// BUSCA PREFERÊNCIAS DE COLUNAS DO USUÁRIO
// ========================================

// Mapeamento: Nome do JS → Nome do PHP (slug)
$columnMapping = [
    'campaign_name' => 'nome',
    'status' => 'status',
    'account_name' => 'conta',
    'daily_budget' => 'orcamento',
    'spend' => 'gastos',
    'impressions' => 'impressoes',
    'clicks' => 'cliques',
    'ctr' => 'ctr',
    'cpc' => 'cpc',
    'cpm' => 'cpm',
    'purchase' => 'vendas',
    'purchase_value' => 'faturamento',
    'roas' => 'roas',
    'roi' => 'roi',
    'margin' => 'margem',
    'cpa' => 'cpa',
    'initiate_checkout' => 'ic',
    'cpi' => 'cpi',
    'add_to_cart' => 'add_carrinho',
    'view_content' => 'ver_conteudo',
    'lead' => 'leads',
    'reach' => 'alcance',
    'frequency' => 'frequencia',
    'objective' => 'objetivo',
    'last_sync' => 'ultima_sync'
];

$userColumnsData = $db->fetch("
    SELECT preference_value 
    FROM user_preferences 
    WHERE user_id = :user_id 
    AND preference_key = 'campaign_columns'
", ['user_id' => $userId]);

$userColumns = null;
if ($userColumnsData && !empty($userColumnsData['preference_value'])) {
    $decodedColumns = json_decode($userColumnsData['preference_value'], true);
    if (is_array($decodedColumns) && count($decodedColumns) > 0) {
        // Converte nomes do JS para nomes do PHP usando o mapeamento
        $mappedColumns = [];
        foreach ($decodedColumns as $col) {
            if ($col === 'checkbox') {
                $mappedColumns[] = 'checkbox';
                continue;
            }
            
            // Se tem mapeamento, usa o nome mapeado; senão usa o original
            $mappedColumns[] = $columnMapping[$col] ?? $col;
        }
        
        $userColumns = $mappedColumns;
        error_log("[VIEW] Colunas customizadas carregadas e mapeadas: " . implode(', ', $userColumns));
    }
}

// Colunas visíveis (usa as do usuário ou padrão)
$defaultColumns = [
    'checkbox', 'nome', 'status', 'orcamento', 'vendas', 'faturamento', 
    'gastos', 'lucro', 'roas', 'roi', 'margem', 'cpa'
];
$visibleColumns = $userColumns ?? $defaultColumns;

// Garante que checkbox está sempre presente
if (!in_array('checkbox', $visibleColumns)) {
    array_unshift($visibleColumns, 'checkbox');
}

// Cria mapeamento reverso para usar no modal
$reverseMapping = array_flip($columnMapping);

error_log("[VIEW] Colunas finais a exibir: " . implode(', ', $visibleColumns));

// Configuração de assets
$projectRoot = 'https://ataweb.com.br/utmtrack';
$assetVersion = '3.0.' . time();
$cssPath = $projectRoot . '/assets/css/utmtrack-dashboard-v2.css?v=' . $assetVersion;
$jsPath = $projectRoot . '/assets/js/utmtrack-dashboard-v2.js?v=' . $assetVersion;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTMTrack - Dashboard de Campanhas v3.0</title>
    
    <link rel="stylesheet" href="<?= $cssPath ?>">
    
    <script>
        window.userColumnsConfig = <?= json_encode($visibleColumns) ?>;
        window.baseUrl = '<?= $projectRoot ?>';
        window.currentPage = '<?= $_SERVER['REQUEST_URI'] ?>';
        window.userId = <?= $_SESSION['user_id'] ?? 0 ?>;
    </script>
</head>
<body>

<!-- TABS DE NAVEGAÇÃO -->
<div class="tabs-container">
    <button class="tab-button" onclick="window.location.href='index.php?page=integracoes-meta-contas'">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        Contas
    </button>
    <button class="tab-button active">
        <svg class="icon" viewBox="0 0 24 24">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
        Campanhas
    </button>
    <button class="tab-button" onclick="window.location.href='index.php?page=conjuntos'">
        <svg class="icon" viewBox="0 0 24 24">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="3" y1="9" x2="21" y2="9"></line>
            <line x1="9" y1="21" x2="9" y2="9"></line>
        </svg>
        Conjuntos
    </button>
    <button class="tab-button" onclick="window.location.href='index.php?page=anuncios'">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        Anúncios
    </button>
</div>

<!-- TOOLBAR -->
<div class="toolbar">
    <div class="toolbar-left">
        <div class="status-indicator">
            <span class="status-dot"></span>
            Sistema Online
        </div>
        <button class="btn btn-secondary" onclick="openSettings()" title="Configurações">
            <svg class="icon" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
            </svg>
        </button>
        <button class="btn btn-secondary" onclick="exportData()" title="Exportar">
            <svg class="icon" viewBox="0 0 24 24">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            Exportar
        </button>
    </div>
    
    <div class="toolbar-right">
        <div class="stats-summary">
            <span class="stat-item">
                <strong><?= $stats['total_campaigns'] ?></strong> campanhas
            </span>
            <span class="stat-separator">|</span>
            <span class="stat-item">
                <strong><?= $stats['active_campaigns'] ?></strong> ativas
            </span>
            <span class="stat-separator">|</span>
            <span class="stat-item">
                Gasto: <strong>R$ <?= number_format($stats['total_spend'], 2, ',', '.') ?></strong>
            </span>
            <span class="stat-separator">|</span>
            <span class="stat-item">
                ROAS Médio: <strong><?= number_format($stats['avg_roas'], 2, ',', '.') ?>x</strong>
            </span>
        </div>
        <button onclick="syncAllCampaigns()" class="btn btn-primary" id="syncButton">
            <svg class="icon sync-icon" viewBox="0 0 24 24">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            Sincronizar
        </button>
    </div>
</div>

<!-- BARRA DE PERÍODO -->
<div class="period-bar">
    <div class="period-container">
        <div class="period-label">
            <svg class="icon" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>Período dos Dados</span>
        </div>
        
        <div class="period-tabs">
            <button class="period-tab" data-period="today" onclick="changePeriod('today', this)">Hoje</button>
            <button class="period-tab" data-period="yesterday" onclick="changePeriod('yesterday', this)">Ontem</button>
            <button class="period-tab" data-period="last_7d" onclick="changePeriod('last_7d', this)">Últimos 7 dias</button>
            <button class="period-tab" data-period="last_30d" onclick="changePeriod('last_30d', this)">Últimos 30 dias</button>
            <button class="period-tab" data-period="this_month" onclick="changePeriod('this_month', this)">Este mês</button>
            <button class="period-tab" data-period="last_month" onclick="changePeriod('last_month', this)">Mês passado</button>
            <button class="period-tab active" data-period="maximum" onclick="changePeriod('maximum', this)">Máximo</button>
            <button class="period-tab" data-period="custom" onclick="toggleCustomPeriod(this)">
                <svg class="icon" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                </svg>
                Personalizado
            </button>
        </div>
    </div>
    
    <div class="custom-date-range" id="customDateRange" style="display: none;">
        <input type="date" id="startDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
        <span>até</span>
        <input type="date" id="endDate" value="<?= date('Y-m-d') ?>">
        <button class="btn btn-primary" onclick="applyCustomPeriod()">Aplicar</button>
    </div>
</div>

<!-- FILTROS -->
<div class="filters-bar">
    <input 
        type="text" 
        class="filter-input" 
        id="searchInput"
        placeholder="Filtrar por nome da campanha..."
        onkeyup="filterTable()"
    >
    
    <select class="filter-select" id="statusFilter" onchange="filterTable()">
        <option value="">Todos os Status</option>
        <option value="active">Ativas</option>
        <option value="paused">Pausadas</option>
    </select>
    
    <select class="filter-select" id="accountFilter" onchange="filterTable()">
        <option value="">Todas as Contas</option>
        <?php
        $accounts = [];
        foreach ($campaigns as $c) {
            if (!empty($c['account_name']) && !in_array($c['account_name'], $accounts)) {
                $accounts[] = $c['account_name'];
            }
        }
        foreach ($accounts as $account): ?>
            <option value="<?= htmlspecialchars($account) ?>"><?= htmlspecialchars($account) ?></option>
        <?php endforeach; ?>
    </select>
    
    <button onclick="openColumnsModal()" class="btn btn-secondary">
        <svg class="icon" viewBox="0 0 24 24">
            <line x1="4" y1="21" x2="4" y2="14"></line>
            <line x1="4" y1="10" x2="4" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12" y2="3"></line>
            <line x1="20" y1="21" x2="20" y2="16"></line>
            <line x1="20" y1="12" x2="20" y2="3"></line>
        </svg>
        Personalizar Colunas
    </button>
</div>

<!-- BARRA DE AÇÕES EM MASSA -->
<div class="bulk-actions-bar" id="bulkActionsBar" style="display: none;">
    <div class="bulk-actions-left">
        <span id="selectedCount">0</span> campanhas selecionadas
    </div>
    <div class="bulk-actions-right">
        <button class="btn btn-secondary" onclick="bulkAction('activate')">
            <svg class="icon" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Ativar
        </button>
        <button class="btn btn-secondary" onclick="bulkAction('pause')">
            <svg class="icon" viewBox="0 0 24 24">
                <rect x="6" y="4" width="4" height="16"></rect>
                <rect x="14" y="4" width="4" height="16"></rect>
            </svg>
            Pausar
        </button>
        <button class="btn btn-danger" onclick="bulkAction('delete')">
            <svg class="icon" viewBox="0 0 24 24">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            Excluir
        </button>
    </div>
</div>

<!-- TABELA -->
<div class="table-wrapper">
    <div class="table-container" id="tableContainer">
        <table class="campaigns-table" id="campaignsTable">
            <thead>
                <tr id="tableHeader">
                    <th class="checkbox-cell" data-column="checkbox">
                        <div class="custom-checkbox">
                            <input type="checkbox" id="selectAllCampaigns" onchange="toggleSelectAll()">
                            <span class="checkmark"></span>
                        </div>
                    </th>
                    
                    <?php 
                    $columnLabels = [
                        'nome' => 'Nome da Campanha',
                        'status' => 'Status',
                        'orcamento' => 'Orçamento',
                        'gastos' => 'Gastos',
                        'impressoes' => 'Impressões',
                        'cliques' => 'Cliques',
                        'ctr' => 'CTR',
                        'cpc' => 'CPC',
                        'cpm' => 'CPM',
                        'vendas' => 'Compras',
                        'faturamento' => 'Faturamento',
                        'lucro' => 'Lucro',
                        'roas' => 'ROAS',
                        'roi' => 'ROI',
                        'margem' => 'Margem',
                        'cpa' => 'CPA',
                        'ic' => 'IC',
                        'cpi' => 'CPI',
                        'add_carrinho' => 'Add Carrinho',
                        'ver_conteudo' => 'Ver Conteúdo',
                        'leads' => 'Leads',
                        'conversoes' => 'Conversões',
                        'alcance' => 'Alcance',
                        'frequencia' => 'Frequência',
                        'conta' => 'Conta',
                        'objetivo' => 'Objetivo',
                        'criado' => 'Criado em',
                        'atualizado' => 'Atualizado em',
                        'ultima_sync' => 'Última Sync'
                    ];
                    
                    $columnWidths = [
                        'nome' => 280,
                        'status' => 100,
                        'orcamento' => 130,
                        'gastos' => 130,
                        'impressoes' => 130,
                        'cliques' => 110,
                        'ctr' => 100,
                        'cpc' => 120,
                        'cpm' => 120,
                        'vendas' => 110,
                        'faturamento' => 140,
                        'lucro' => 130,
                        'roas' => 100,
                        'roi' => 100,
                        'margem' => 120,
                        'cpa' => 120,
                        'ic' => 100,
                        'cpi' => 120,
                        'add_carrinho' => 140,
                        'ver_conteudo' => 140,
                        'leads' => 100,
                        'conversoes' => 130,
                        'alcance' => 120,
                        'frequencia' => 120,
                        'conta' => 180,
                        'objetivo' => 150,
                        'criado' => 150,
                        'atualizado' => 150,
                        'ultima_sync' => 150
                    ];
                    
                    foreach ($visibleColumns as $col):
                        if ($col === 'checkbox') continue;
                        $label = $columnLabels[$col] ?? ucfirst($col);
                        $width = $columnWidths[$col] ?? 120;
                    ?>
                    <th data-column="<?= $col ?>" class="sortable" style="min-width: <?= $width ?>px;">
                        <div class="th-content">
                            <span class="drag-handle">⋮⋮</span>
                            <span class="th-label"><?= $label ?></span>
                            <span class="sort-icon">⇅</span>
                        </div>
                        <div class="resize-handle"></div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($campaigns)): ?>
                <tr>
                    <td colspan="50" style="border: none;">
                        <div class="empty-state">
                            <div class="empty-icon">📊</div>
                            <div class="empty-title">Nenhuma campanha encontrada</div>
                            <p class="empty-description">
                                Conecte suas contas Meta Ads e sincronize para ver suas campanhas
                            </p>
                            <button onclick="window.location.href='index.php?page=integracoes-meta-contas'" class="btn btn-primary">
                                Conectar Conta
                            </button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $c): ?>
                    <?php 
                        $c = array_merge([
                            'id' => 0,
                            'campaign_id' => '',
                            'campaign_name' => 'Sem nome',
                            'status' => 'paused',
                            'daily_budget' => 0,
                            'lifetime_budget' => 0,
                            'spend' => 0,
                            'impressions' => 0,
                            'clicks' => 0,
                            'reach' => 0,
                            'frequency' => 0,
                            'ctr' => 0,
                            'cpc' => 0,
                            'cpm' => 0,
                            'purchase' => 0,
                            'purchase_value' => 0,
                            'add_to_cart' => 0,
                            'initiate_checkout' => 0,
                            'view_content' => 0,
                            'lead' => 0,
                            'roas' => 0,
                            'roi' => 0,
                            'margin' => 0,
                            'cpa' => 0,
                            'cpi' => 0,
                            'account_name' => '',
                            'objective' => '',
                            'created_time' => '',
                            'updated_time' => '',
                            'last_sync' => date('Y-m-d H:i:s')
                        ], $c);
                        
                        $profit = $c['purchase_value'] - $c['spend'];
                    ?>
                    <tr 
                        data-id="<?= $c['id'] ?>"
                        data-campaign-id="<?= htmlspecialchars($c['campaign_id']) ?>"
                        data-name="<?= strtolower($c['campaign_name']) ?>"
                        data-status="<?= $c['status'] ?>"
                        data-account="<?= htmlspecialchars($c['account_name']) ?>"
                    >
                        <td class="checkbox-cell" data-column="checkbox">
                            <div class="custom-checkbox">
                                <input type="checkbox" class="campaign-checkbox" value="<?= $c['id'] ?>" onchange="updateSelectedCount()">
                                <span class="checkmark"></span>
                            </div>
                        </td>
                        
                        <?php if (in_array('nome', $visibleColumns)): ?>
                        <td data-column="nome">
                            <div class="campaign-name-cell editable-field" 
                                 ondblclick="editField(this, <?= $c['id'] ?>, 'campaign_name', 'text')"
                                 data-value="<?= htmlspecialchars($c['campaign_name']) ?>">
                                <?= htmlspecialchars($c['campaign_name']) ?>
                            </div>
                            <div class="campaign-id">ID: <?= htmlspecialchars($c['campaign_id']) ?></div>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('status', $visibleColumns)): ?>
                        <td data-column="status">
                            <label class="toggle-switch">
                                <input 
                                    type="checkbox" 
                                    <?= $c['status'] === 'active' ? 'checked' : '' ?>
                                    onchange="toggleStatus(this, <?= $c['id'] ?>, '<?= $c['campaign_id'] ?>')"
                                >
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('orcamento', $visibleColumns)): ?>
                        <td data-column="orcamento">
                            <div class="editable-field" 
                                 ondblclick="editField(this, <?= $c['id'] ?>, 'daily_budget', 'currency')"
                                 data-value="<?= $c['daily_budget'] ?>">
                                R$ <?= number_format($c['daily_budget'], 2, ',', '.') ?>
                            </div>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('gastos', $visibleColumns)): ?>
                        <td data-column="gastos">
                            <strong>R$ <?= number_format($c['spend'], 2, ',', '.') ?></strong>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('impressoes', $visibleColumns)): ?>
                        <td data-column="impressoes">
                            <?= number_format($c['impressions'], 0, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('cliques', $visibleColumns)): ?>
                        <td data-column="cliques">
                            <?= number_format($c['clicks'], 0, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('ctr', $visibleColumns)): ?>
                        <td data-column="ctr">
                            <?= number_format($c['ctr'], 2, ',', '.') ?>%
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('cpc', $visibleColumns)): ?>
                        <td data-column="cpc">
                            R$ <?= number_format($c['cpc'], 2, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('cpm', $visibleColumns)): ?>
                        <td data-column="cpm">
                            R$ <?= number_format($c['cpm'], 2, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('vendas', $visibleColumns)): ?>
                        <td data-column="vendas">
                            <?= number_format($c['purchase'], 0, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('faturamento', $visibleColumns)): ?>
                        <td data-column="faturamento" class="<?= $c['purchase_value'] > 0 ? 'metric-positive' : '' ?>">
                            R$ <?= number_format($c['purchase_value'], 2, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('lucro', $visibleColumns)): ?>
                        <td data-column="lucro" class="<?= $profit > 0 ? 'metric-positive' : ($profit < 0 ? 'metric-negative' : '') ?>">
                            R$ <?= number_format($profit, 2, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('roas', $visibleColumns)): ?>
                        <td data-column="roas" class="<?= $c['roas'] >= 2 ? 'metric-positive' : ($c['roas'] >= 1 ? '' : 'metric-negative') ?>">
                            <?= number_format($c['roas'], 2, ',', '.') ?>x
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('roi', $visibleColumns)): ?>
                        <td data-column="roi" class="<?= $c['roi'] > 0 ? 'metric-positive' : ($c['roi'] < 0 ? 'metric-negative' : '') ?>">
                            <?= number_format($c['roi'], 2, ',', '.') ?>%
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('margem', $visibleColumns)): ?>
                        <td data-column="margem" class="<?= $c['margin'] > 0 ? 'metric-positive' : '' ?>">
                            <?= number_format($c['margin'], 2, ',', '.') ?>%
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('cpa', $visibleColumns)): ?>
                        <td data-column="cpa">
                            R$ <?= number_format($c['cpa'], 2, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('ic', $visibleColumns)): ?>
                        <td data-column="ic">
                            <?= number_format($c['initiate_checkout'], 0, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('cpi', $visibleColumns)): ?>
                        <td data-column="cpi">
                            R$ <?= number_format($c['cpi'], 2, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('add_carrinho', $visibleColumns)): ?>
                        <td data-column="add_carrinho">
                            <?= number_format($c['add_to_cart'], 0, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('ver_conteudo', $visibleColumns)): ?>
                        <td data-column="ver_conteudo">
                            <?= number_format($c['view_content'], 0, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('leads', $visibleColumns)): ?>
                        <td data-column="leads">
                            <?= number_format($c['lead'], 0, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('alcance', $visibleColumns)): ?>
                        <td data-column="alcance">
                            <?= number_format($c['reach'], 0, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('frequencia', $visibleColumns)): ?>
                        <td data-column="frequencia">
                            <?= number_format($c['frequency'], 2, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('conta', $visibleColumns)): ?>
                        <td data-column="conta">
                            <?= htmlspecialchars($c['account_name']) ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('objetivo', $visibleColumns)): ?>
                        <td data-column="objetivo">
                            <?= htmlspecialchars($c['objective']) ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('ultima_sync', $visibleColumns)): ?>
                        <td data-column="ultima_sync">
                            <?= !empty($c['last_sync']) ? date('d/m/Y H:i', strtotime($c['last_sync'])) : '-' ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL COLUNAS -->
<div class="modal-overlay" id="columnsModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Personalizar Colunas</h3>
            <button class="modal-close" onclick="closeColumnsModal()">×</button>
        </div>
        <div class="modal-body">
            <input type="text" id="columnSearch" placeholder="Buscar coluna..." class="filter-input" onkeyup="filterColumnsModal()">
            <div id="columnsCheckboxes">
                <?php
                $allColumns = [
                    'nome' => 'Nome da Campanha',
                    'status' => 'Status',
                    'orcamento' => 'Orçamento',
                    'gastos' => 'Gastos',
                    'impressoes' => 'Impressões',
                    'cliques' => 'Cliques',
                    'ctr' => 'CTR',
                    'cpc' => 'CPC',
                    'cpm' => 'CPM',
                    'vendas' => 'Compras',
                    'faturamento' => 'Faturamento',
                    'lucro' => 'Lucro',
                    'roas' => 'ROAS',
                    'roi' => 'ROI',
                    'margem' => 'Margem',
                    'cpa' => 'CPA',
                    'ic' => 'IC (Initiate Checkout)',
                    'cpi' => 'CPI',
                    'add_carrinho' => 'Add ao Carrinho',
                    'ver_conteudo' => 'Ver Conteúdo',
                    'leads' => 'Leads',
                    'conversoes' => 'Conversões',
                    'alcance' => 'Alcance',
                    'frequencia' => 'Frequência',
                    'conta' => 'Conta',
                    'objetivo' => 'Objetivo',
                    'ultima_sync' => 'Última Sincronização'
                ];
                
                foreach ($allColumns as $key => $label):
                    // Usa o nome JS (do reverseMapping) como value, mas mostra o label em português
                    $jsName = $reverseMapping[$key] ?? $key;
                    $checked = in_array($key, $visibleColumns) ? 'checked' : '';
                ?>
                <div class="column-checkbox-item" style="padding: 8px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" value="<?= $jsName ?>" <?= $checked ?> style="margin-right: 8px;">
                        <?= $label ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="resetColumns()">Restaurar Padrão</button>
            <button class="btn btn-secondary" onclick="closeColumnsModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveColumns()">Salvar</button>
        </div>
    </div>
</div>

<script src="<?= $jsPath ?>"></script>

<script>
// ========================================
// FUNÇÕES JAVASCRIPT INLINE - ✅ CORRIGIDAS COM URLs DO ajax-campaigns.php
// ========================================

console.log('='.repeat(60));
console.log('📊 DASHBOARD CAMPANHAS V3.0 - ✅ URLS CORRIGIDAS');
console.log('='.repeat(60));
console.log('Total campanhas PHP:', <?= count($campaigns) ?>);
console.log('Linhas <tr> renderizadas:', document.querySelectorAll('#tableBody tr[data-id]').length);
console.log('Stats:', <?= json_encode($stats) ?>);
console.log('='.repeat(60));

const phpCount = <?= count($campaigns) ?>;
const domCount = document.querySelectorAll('#tableBody tr[data-id]').length;

if (phpCount > 0 && domCount === 0) {
    console.error('❌ ERRO: PHP tem dados mas DOM está vazio!');
} else if (phpCount > 0 && domCount > 0) {
    console.log('✅ SUCESSO: ' + domCount + ' campanhas renderizadas!');
}

function editField(element, campaignId, field, type) {
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('📝 EDITFIELD INLINE - Iniciando');
    console.log('Campaign ID:', campaignId, 'Field:', field, 'Type:', type);
    
    if (element.querySelector('input')) {
        console.log('⚠️ Campo já em edição');
        return;
    }
    
    const dataValue = element.getAttribute('data-value');
    const textValue = element.textContent.trim();
    const currentValue = (dataValue !== null && dataValue !== '') ? dataValue : textValue;
    
    console.log('Valores:', { dataValue, textValue, currentValue });
    
    const cleanValue = type === 'currency' 
        ? currentValue.replace(/[R$\s.]/g, '').replace(',', '.')
        : currentValue;
    
    console.log('Valor limpo:', cleanValue);
    
    const originalHTML = element.innerHTML;
    
    const input = document.createElement('input');
    input.type = type === 'currency' ? 'number' : 'text';
    input.value = type === 'currency' ? (parseFloat(cleanValue) || 0) : currentValue;
    input.style.cssText = 'width:100%;padding:6px 8px;background:#2a2a2a;border:2px solid #4CAF50;color:#fff;border-radius:4px;font-size:13px;outline:none;box-sizing:border-box;';
    
    if (type === 'currency') {
        input.step = '0.01';
        input.min = '0';
    }
    
    const save = async () => {
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('💾 SALVANDO CAMPO - INLINE');
        
        const newValue = type === 'currency' ? parseFloat(input.value) : input.value.trim();
        const compareValue = type === 'currency' ? parseFloat(cleanValue) : currentValue;
        
        console.log('Comparação:', { antigo: compareValue, novo: newValue, mudou: newValue != compareValue });
        
        if (newValue == compareValue) {
            console.log('⚠️ Valor não mudou, restaurando');
            element.innerHTML = originalHTML;
            return;
        }
        
        try {
            element.style.opacity = '0.6';
            element.style.pointerEvents = 'none';
            
            // ✅ CORREÇÃO: URL correta para ajax-campaigns.php
            const url = `${window.baseUrl}/ajax-campaigns.php?ajax_action=update_field`;
            console.log('🌐 URL CORRIGIDA:', url);
            
            const payload = { campaign_id: campaignId, field: field, value: newValue };
            console.log('📦 Payload:', JSON.stringify(payload, null, 2));
            
            console.log('🚀 Enviando requisição fetch...');
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify(payload)
            });
            
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('📥 RESPOSTA RECEBIDA');
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('📊 Status HTTP:', response.status);
            console.log('📋 Content-Type:', response.headers.get('Content-Type'));
            console.log('✅ Response OK:', response.ok);
            
            const responseText = await response.text();
            
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('📄 RESPOSTA COMPLETA (texto bruto):');
            console.log(responseText);
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('📏 Tamanho:', responseText.length, 'caracteres');
            console.log('🔍 Primeiro caractere:', responseText.charAt(0), '(código:', responseText.charCodeAt(0) + ')');
            
            if (responseText.includes('<!DOCTYPE') || responseText.includes('<html') || responseText.includes('<HTML')) {
                console.error('❌ SERVIDOR RETORNOU HTML AO INVÉS DE JSON!');
                throw new Error('Servidor retornou HTML. Possível sessão expirada. Recarregue a página.');
            }
            
            if (responseText.includes('Fatal error') || responseText.includes('Warning:') || responseText.includes('Notice:') || responseText.includes('Parse error')) {
                console.error('❌ ERRO PHP DETECTADO NA RESPOSTA!');
                console.error('Resposta completa:', responseText);
                throw new Error('Erro PHP no servidor. Verifique os logs do servidor.');
            }
            
            if (responseText.trim() === '') {
                console.error('❌ RESPOSTA VAZIA DO SERVIDOR!');
                throw new Error('Servidor retornou resposta vazia. Verifique se o endpoint está correto.');
            }
            
            let data;
            try {
                console.log('🔄 Tentando fazer parse do JSON...');
                data = JSON.parse(responseText);
                console.log('✅ JSON parseado com sucesso!');
                console.log('📊 Objeto resultante:', data);
            } catch (parseError) {
                console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                console.error('❌ ERRO AO FAZER PARSE DO JSON');
                console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                console.error('Parse Error:', parseError.message);
                console.error('Stack:', parseError.stack);
                console.error('Resposta (primeiros 500 chars):', responseText.substring(0, 500));
                console.error('Resposta (últimos 500 chars):', responseText.substring(Math.max(0, responseText.length - 500)));
                console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                throw new Error('Resposta inválida do servidor (não é JSON válido). Veja o console para detalhes.');
            }
            
            console.log('🔍 Verificando estrutura do JSON...');
            console.log('Tem "success"?', 'success' in data);
            console.log('Tem "message"?', 'message' in data);
            console.log('Valor de success:', data.success);
            
            if (data.success) {
                console.log('✅ Servidor indicou sucesso!');
                
                element.setAttribute('data-value', newValue);
                
                if (type === 'currency') {
                    const formatted = 'R$ ' + parseFloat(newValue).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    element.textContent = formatted;
                    console.log('💰 Valor formatado:', formatted);
                } else {
                    element.textContent = newValue;
                    console.log('📝 Valor atualizado:', newValue);
                }
                
                console.log('✅ Elemento atualizado:', {
                    'data-value': element.getAttribute('data-value'),
                    'textContent': element.textContent
                });
                
                element.style.backgroundColor = '#4CAF5044';
                element.style.transition = 'background-color 0.3s ease';
                
                setTimeout(() => {
                    element.style.backgroundColor = '';
                }, 2000);
                
                if (typeof showToast !== 'undefined') {
                    showToast(data.message || '✅ Campo atualizado com sucesso!', 'success');
                }
                
                console.log('✅ SALVAMENTO CONCLUÍDO COM SUCESSO!');
                console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            } else {
                console.error('⚠️ Servidor retornou success=false');
                console.error('Mensagem:', data.message);
                throw new Error(data.message || 'Erro ao salvar campo');
            }
            
        } catch (error) {
            console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.error('❌ ERRO FINAL NO SALVAMENTO');
            console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.error('Erro:', error.message);
            console.error('Stack:', error.stack);
            console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            
            alert('Erro ao salvar: ' + error.message);
            element.innerHTML = originalHTML;
            
        } finally {
            element.style.opacity = '';
            element.style.pointerEvents = '';
            console.log('🔓 Interatividade restaurada');
        }
    };
    
    const cancel = () => {
        console.log('🚫 Edição cancelada');
        element.innerHTML = originalHTML;
    };
    
    input.onkeydown = function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            console.log('⌨️ Enter pressionado');
            save();
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            console.log('⌨️ Escape pressionado');
            cancel();
        }
    };
    
    input.onblur = function() {
        console.log('👁️ Input perdeu foco');
        save();
    };
    
    element.innerHTML = '';
    element.appendChild(input);
    input.focus();
    input.select();
    
    console.log('✅ Input criado e focado');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
}

function toggleStatus(checkbox, campaignId, metaCampaignId) {
    console.log('🔄 TOGGLE STATUS', campaignId, metaCampaignId);
    
    const newStatus = checkbox.checked ? 'ACTIVE' : 'PAUSED';
    
    // ✅ CORREÇÃO: URL correta para ajax-campaigns.php
    const url = `${window.baseUrl}/ajax-campaigns.php?ajax_action=update_status`;
    console.log('🌐 URL CORRIGIDA:', url);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include',
        body: JSON.stringify({
            campaign_id: campaignId,
            meta_campaign_id: metaCampaignId,
            status: newStatus
        })
    })
    .then(response => response.text())
    .then(text => {
        console.log('Resposta:', text);
        
        try {
            const data = JSON.parse(text);
            
            if (!data.success) {
                checkbox.checked = !checkbox.checked;
                alert('Erro: ' + data.message);
            } else if (typeof showToast !== 'undefined') {
                showToast(data.message || 'Status atualizado!', 'success');
            }
        } catch (parseError) {
            console.error('Erro parse:', parseError);
            checkbox.checked = !checkbox.checked;
            alert('Erro ao processar resposta');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        checkbox.checked = !checkbox.checked;
    });
}

function showToast(message, type) {
    const existingToast = document.querySelector('.toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `position:fixed;bottom:20px;right:20px;padding:14px 24px;background:${type === 'success' ? '#4CAF50' : '#f44336'};color:white;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,0.3);z-index:10000;font-weight:500;`;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 3000);
}

window.editField = editField;
window.toggleStatus = toggleStatus;
window.showToast = showToast;

console.log('✅ Funções inline registradas com URLs CORRIGIDAS para /ajax-campaigns.php');
console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
</script>

</body>
</html>