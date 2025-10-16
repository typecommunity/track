<?php
/**
 * ========================================
 * CAMPAIGN CONTROLLER V3.0 - COMPLETO
 * ========================================
 * BUSCA E CONSOME TODOS OS 150+ CAMPOS
 * - ✅ 50+ campos de campanhas
 * - ✅ 80+ campos de insights
 * - ✅ Filtros avançados (CBO, ASC, quality_ranking)
 * - ✅ Parse automático de JSONs
 * - ✅ Métricas calculadas avançadas
 */

require_once __DIR__ . '/../../core/MetaAdsDataStructure.php';
require_once __DIR__ . '/../../core/MetaAdsSync.php';

class CampaignControllerV2 extends Controller {
    
    private $metaSync;
    private $dataStructure;
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
        $this->metaSync = new MetaAdsSync($this->db, $this->auth->id());
        $this->dataStructure = new MetaAdsDataStructure();
    }
    
    /**
     * ========================================
     * DASHBOARD PRINCIPAL - COMPLETO
     * ========================================
     */
    public function index() {
        // Detecta período da URL
        $requestedPeriod = $_GET['period'] ?? 'maximum';
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        // Flag para sincronização manual
        $forceSync = isset($_GET['force_sync']) && $_GET['force_sync'] === '1';
        
        // Busca último período sincronizado
        $lastSyncPeriod = $this->getLastSyncPeriod();
        
        $syncResults = null;
        $shouldAutoSync = false;
        
        // Sincronização manual sempre executa
        if ($forceSync) {
            $shouldAutoSync = true;
            error_log("[CONTROLLER] 🔄 Sincronização MANUAL forçada pelo usuário");
        }
        // Sincronização automática se período mudou ou passou 1 hora
        elseif ($requestedPeriod !== $lastSyncPeriod || $this->shouldResync($requestedPeriod)) {
            $shouldAutoSync = true;
            error_log("[CONTROLLER] 🔄 Sincronização AUTOMÁTICA por mudança de período");
        }
        
        // Executa sincronização se necessário
        if ($shouldAutoSync) {
            try {
                error_log("[CONTROLLER] Sincronizando - Período: {$requestedPeriod}");
                
                $syncOptions = [
                    'include_insights' => true,
                    'include_actions' => true,
                    'include_video_data' => true
                ];
                
                // Configura período
                if ($requestedPeriod === 'custom' && $startDate && $endDate) {
                    $syncOptions['date_preset'] = null;
                    $syncOptions['time_range'] = [
                        'since' => $startDate,
                        'until' => $endDate
                    ];
                } else {
                    $syncOptions['date_preset'] = $requestedPeriod;
                }
                
                // Executa sincronização
                $syncResults = $this->metaSync->syncAll($syncOptions);
                
                // Salva último período sincronizado
                $this->saveLastSyncPeriod($requestedPeriod);
                
                error_log("[CONTROLLER] ✅ Sincronização concluída: " . 
                          $syncResults['campaigns']['synced'] . " campanhas | " . 
                          $syncResults['duration'] . "s");
                
            } catch (Exception $e) {
                error_log("[CONTROLLER] ❌ Erro na sincronização: " . $e->getMessage());
            }
        }
        
        // Carrega preferências de colunas do usuário
        $userColumns = $this->getUserColumns();
        
        // ✅ Busca campanhas com TODOS os 150+ campos
        $campaigns = $this->getCampaignsWithFullData([], $requestedPeriod, $startDate, $endDate);
        
        // Estatísticas
        $stats = $this->calculateStats($campaigns);
        
        // Configuração de colunas
        $availableColumns = MetaAdsDataStructure::getAllFieldsByCategory();
        $tableConfig = MetaAdsDataStructure::getTableConfiguration();
        
        // Renderiza a view
        $this->render('campaigns/index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'userColumns' => $userColumns,
            'availableColumns' => $availableColumns,
            'tableConfig' => $tableConfig,
            'metaFields' => array_merge(
                MetaAdsDataStructure::CAMPAIGN_FIELDS ?? [],
                MetaAdsDataStructure::INSIGHTS_FIELDS ?? []
            ),
            'currentPeriod' => $requestedPeriod,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'syncResults' => $syncResults,
            'config' => $this->config,
            'user' => $this->auth->user()
        ]);
    }
    
    /**
     * ========================================
     * BUSCA CAMPANHAS - TODOS OS 150+ CAMPOS
     * ========================================
     */
    private function getCampaignsWithFullData($filters = [], $period = 'maximum', $startDate = null, $endDate = null) {
        // Calcula datas do período
        $periodDates = $this->calculatePeriodDates($period, $startDate, $endDate);
        
        error_log("[CONTROLLER] 📅 Buscando campanhas - Período: {$period} ({$periodDates['start']} até {$periodDates['end']})");
        
        $query = "
            SELECT 
                -- ========================================
                -- CAMPANHAS - TODOS OS 50+ CAMPOS
                -- ========================================
                c.id,
                c.user_id,
                c.ad_account_id,
                c.campaign_id,
                c.campaign_name,
                
                -- Status
                c.status,
                c.effective_status,
                c.configured_status,
                
                -- Objetivo e Tipo
                c.objective,
                c.buying_type,
                c.can_use_spend_cap,
                
                -- Orçamento Básico
                c.daily_budget,
                c.lifetime_budget,
                c.spend_cap,
                c.budget_remaining,
                
                -- ✅ CBO e Limites Detalhados
                c.campaign_budget_optimization,
                c.daily_min_spend_target,
                c.daily_spend_cap,
                c.lifetime_min_spend_target,
                c.lifetime_spend_cap,
                
                -- ✅ ASC Detection
                c.is_asc,
                
                -- Lance
                c.bid_strategy,
                c.bid_amount,
                c.bid_constraints,
                
                -- Configurações
                c.pacing_type,
                c.promoted_object,
                
                -- Categorias Especiais
                c.special_ad_categories,
                c.special_ad_category,
                c.special_ad_category_country,
                
                -- SKAdNetwork
                c.is_skadnetwork_attribution,
                c.smart_promotion_type,
                c.source_campaign_id,
                
                -- ✅ Issues e Recomendações
                c.issues_info,
                c.recommendations,
                
                -- ✅ Labels e Organização
                c.adlabels,
                c.campaign_group_id,
                c.topline_id,
                
                -- ✅ Outros Campos
                c.budget_rebalance_flag,
                c.can_create_brand_lift_study,
                c.has_secondary_skadnetwork_reporting,
                c.is_budget_schedule_enabled,
                c.iterative_split_test_configs,
                c.last_budget_toggling_time,
                c.upstream_events,
                
                -- Datas
                c.start_time,
                c.stop_time,
                c.created_time,
                c.updated_time,
                c.last_sync,
                
                -- Conta de Anúncios
                aa.account_name,
                aa.account_id as meta_account_id,
                aa.access_token,
                
                -- ========================================
                -- INSIGHTS - TODOS OS 80+ CAMPOS
                -- ========================================
                
                -- Período
                ci.date_start,
                ci.date_stop,
                
                -- Métricas Básicas
                ci.impressions,
                ci.clicks,
                ci.spend,
                ci.reach,
                ci.frequency,
                ci.social_spend,
                
                -- CTR e Custos Básicos
                ci.ctr,
                ci.cpc,
                ci.cpm,
                ci.cpp,
                
                -- ✅ Custos Detalhados
                ci.cost_per_action_type,
                ci.cost_per_unique_action_type,
                ci.cost_per_conversion,
                ci.cost_per_inline_link_click,
                ci.cost_per_inline_post_engagement,
                ci.cost_per_unique_click,
                ci.cost_per_unique_inline_link_click,
                
                -- Conversões Principais
                ci.purchase,
                ci.purchase_value,
                ci.add_to_cart,
                ci.add_to_cart_value,
                ci.initiate_checkout,
                ci.initiate_checkout_value,
                ci.lead,
                ci.complete_registration,
                ci.view_content,
                ci.search,
                
                -- ✅ Conversões Adicionais
                ci.add_payment_info,
                ci.add_to_wishlist,
                ci.contact,
                ci.customize_product,
                ci.donate,
                ci.find_location,
                ci.schedule,
                ci.start_trial,
                ci.submit_application,
                ci.subscribe,
                
                -- Vídeo Completo
                ci.video_play_actions,
                ci.video_p25_watched,
                ci.video_p50_watched,
                ci.video_p75_watched,
                ci.video_p95_watched,
                ci.video_p100_watched,
                ci.thruplay,
                
                -- ✅ Vídeo Detalhado
                ci.video_avg_time_watched_actions,
                ci.video_continuous_2_sec_watched_actions,
                ci.video_30_sec_watched_actions,
                
                -- Engajamento
                ci.post_engagement,
                ci.page_engagement,
                ci.post_reactions,
                ci.post_saves,
                ci.post_shares,
                ci.post_comments,
                ci.photo_view,
                ci.inline_link_clicks,
                ci.inline_link_click_ctr,
                ci.inline_post_engagement,
                
                -- ✅ Rankings de Qualidade (CRÍTICO!)
                ci.quality_ranking,
                ci.engagement_rate_ranking,
                ci.conversion_rate_ranking,
                
                -- ✅ Leilão
                ci.auction_bid,
                ci.auction_competitiveness,
                
                -- ✅ Links Externos
                ci.outbound_clicks,
                ci.outbound_clicks_ctr,
                ci.unique_outbound_clicks,
                ci.unique_outbound_clicks_ctr,
                ci.website_ctr,
                
                -- ✅ Mobile App
                ci.app_install,
                ci.app_use,
                ci.app_custom_event_fb_mobile_achievement_unlocked,
                ci.app_custom_event_fb_mobile_add_payment_info,
                ci.app_custom_event_fb_mobile_add_to_cart,
                ci.app_custom_event_fb_mobile_add_to_wishlist,
                ci.app_custom_event_fb_mobile_complete_registration,
                ci.app_custom_event_fb_mobile_content_view,
                ci.app_custom_event_fb_mobile_initiated_checkout,
                ci.app_custom_event_fb_mobile_level_achieved,
                ci.app_custom_event_fb_mobile_purchase,
                ci.app_custom_event_fb_mobile_rate,
                ci.app_custom_event_fb_mobile_search,
                ci.app_custom_event_fb_mobile_spent_credits,
                ci.app_custom_event_fb_mobile_tutorial_completion,
                
                -- ✅ Brand (Recall)
                ci.estimated_ad_recall_rate,
                ci.estimated_ad_recall_lift,
                ci.estimated_ad_recallers,
                
                -- ✅ Catálogo (E-commerce)
                ci.catalog_segment_value,
                ci.catalog_segment_actions,
                ci.catalog_segment_value_mobile_purchase_roas,
                ci.catalog_segment_value_omni_purchase_roas,
                ci.catalog_segment_value_website_purchase_roas,
                
                -- ✅ Canvas/Instant Experience
                ci.instant_experience_clicks_to_open,
                ci.instant_experience_clicks_to_start,
                ci.instant_experience_outbound_clicks,
                
                -- ✅ DDA
                ci.dda_results,
                
                -- ✅ Conversões por Fonte
                ci.mobile_app_purchase_roas,
                ci.website_purchase_roas,
                
                -- ✅ Cliques Únicos
                ci.unique_clicks,
                ci.unique_ctr,
                ci.unique_inline_link_clicks,
                ci.unique_inline_link_click_ctr,
                ci.unique_link_clicks_ctr,
                
                -- ✅ Actions e Conversions
                ci.actions,
                ci.action_values,
                ci.conversions,
                ci.conversion_values,
                ci.cost_per_thruplay,
                
                -- Métricas Calculadas
                ci.roas,
                ci.roi,
                ci.margin,
                ci.cpa,
                ci.cpi,
                ci.cpl,
                ci.conversion_rate
                
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            LEFT JOIN campaign_insights ci ON ci.campaign_id = c.id
                AND ci.date_start = :date_start
                AND ci.date_stop = :date_stop
            WHERE c.user_id = :user_id
        ";
        
        $params = [
            'user_id' => $this->auth->id(),
            'date_start' => $periodDates['start'],
            'date_stop' => $periodDates['end']
        ];
        
        // ✅ Aplica filtros avançados
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
            $query .= " AND (c.campaign_name LIKE :search OR c.campaign_id LIKE :search_id)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search_id'] = '%' . $filters['search'] . '%';
        }
        
        // ✅ NOVO: Filtro por CBO
        if (isset($filters['cbo'])) {
            $query .= " AND c.campaign_budget_optimization = :cbo";
            $params['cbo'] = $filters['cbo'] ? 1 : 0;
        }
        
        // ✅ NOVO: Filtro por ASC
        if (isset($filters['asc'])) {
            $query .= " AND c.is_asc = :asc";
            $params['asc'] = $filters['asc'] ? 1 : 0;
        }
        
        // ✅ NOVO: Filtro por issues
        if (isset($filters['has_issues'])) {
            $query .= " AND c.issues_info IS NOT NULL";
        }
        
        // ✅ NOVO: Filtro por quality_ranking
        if (!empty($filters['quality_ranking'])) {
            $query .= " AND ci.quality_ranking = :quality_ranking";
            $params['quality_ranking'] = $filters['quality_ranking'];
        }
        
        // Ordenação
        $orderBy = $filters['order_by'] ?? 'created_time';
        $orderDirection = $filters['order_direction'] ?? 'DESC';
        $query .= " ORDER BY c.{$orderBy} {$orderDirection}";
        
        // Limite
        if (!empty($filters['limit'])) {
            $query .= " LIMIT " . intval($filters['limit']);
        }
        
        $campaigns = $this->db->fetchAll($query, $params);
        
        error_log("[CONTROLLER] ✅ Encontradas " . count($campaigns) . " campanhas com TODOS os 150+ campos");
        
        // Processa métricas calculadas e parse de JSONs
        foreach ($campaigns as &$campaign) {
            $campaign = $this->calculateCampaignMetrics($campaign);
            $campaign = $this->parseJsonFields($campaign);
        }
        
        return $campaigns;
    }
    
    /**
     * ✅ Parse automático dos campos JSON
     */
    private function parseJsonFields($campaign) {
        $jsonFields = [
            'issues_info',
            'recommendations',
            'adlabels',
            'bid_constraints',
            'pacing_type',
            'promoted_object',
            'special_ad_categories',
            'special_ad_category_country',
            'iterative_split_test_configs',
            'upstream_events',
            'cost_per_action_type',
            'actions',
            'action_values',
            'conversions',
            'conversion_values',
            'catalog_segment_actions',
            'dda_results'
        ];
        
        foreach ($jsonFields as $field) {
            if (!empty($campaign[$field]) && is_string($campaign[$field])) {
                $decoded = json_decode($campaign[$field], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $campaign[$field] = $decoded;
                }
            }
        }
        
        return $campaign;
    }
    
    /**
     * ✅ Calcula métricas avançadas
     */
    private function calculateCampaignMetrics($campaign) {
        // ROAS, ROI, Margem
        if ($campaign['spend'] > 0 && $campaign['purchase_value'] > 0) {
            $campaign['roas'] = round($campaign['purchase_value'] / $campaign['spend'], 2);
            $campaign['roi'] = round((($campaign['purchase_value'] - $campaign['spend']) / $campaign['spend']) * 100, 2);
            $campaign['margin'] = round((($campaign['purchase_value'] - $campaign['spend']) / $campaign['purchase_value']) * 100, 2);
        } else {
            $campaign['roas'] = 0;
            $campaign['roi'] = 0;
            $campaign['margin'] = 0;
        }
        
        // CPA
        if ($campaign['purchase'] > 0) {
            $campaign['cpa'] = round($campaign['spend'] / $campaign['purchase'], 2);
        } else {
            $campaign['cpa'] = 0;
        }
        
        // CPI (Custo por IC)
        if ($campaign['initiate_checkout'] > 0) {
            $campaign['cpi'] = round($campaign['spend'] / $campaign['initiate_checkout'], 2);
        } else {
            $campaign['cpi'] = 0;
        }
        
        // CPL
        if ($campaign['lead'] > 0) {
            $campaign['cpl'] = round($campaign['spend'] / $campaign['lead'], 2);
        } else {
            $campaign['cpl'] = 0;
        }
        
        // Taxa de conversão
        if ($campaign['clicks'] > 0) {
            $campaign['conversion_rate'] = round(($campaign['purchase'] / $campaign['clicks']) * 100, 2);
        } else {
            $campaign['conversion_rate'] = 0;
        }
        
        // ✅ Taxa de engajamento
        if ($campaign['impressions'] > 0) {
            $campaign['engagement_rate'] = round(($campaign['post_engagement'] / $campaign['impressions']) * 100, 2);
        } else {
            $campaign['engagement_rate'] = 0;
        }
        
        // ✅ Taxa de conclusão de vídeo
        if ($campaign['video_play_actions'] > 0) {
            $campaign['video_completion_rate'] = round(($campaign['video_p100_watched'] / $campaign['video_play_actions']) * 100, 2);
        } else {
            $campaign['video_completion_rate'] = 0;
        }
        
        // ✅ ROAS por canal
        if (!empty($campaign['mobile_app_purchase_roas'])) {
            $campaign['mobile_roas'] = floatval($campaign['mobile_app_purchase_roas']);
        } else {
            $campaign['mobile_roas'] = 0;
        }
        
        if (!empty($campaign['website_purchase_roas'])) {
            $campaign['web_roas'] = floatval($campaign['website_purchase_roas']);
        } else {
            $campaign['web_roas'] = 0;
        }
        
        // ✅ Click-through rate de vídeo
        if ($campaign['video_play_actions'] > 0) {
            $campaign['video_ctr'] = round(($campaign['clicks'] / $campaign['video_play_actions']) * 100, 2);
        } else {
            $campaign['video_ctr'] = 0;
        }
        
        return $campaign;
    }
    
    /**
     * Calcula datas do período
     */
    private function calculatePeriodDates($period, $startDate = null, $endDate = null) {
        $today = date('Y-m-d');
        
        switch ($period) {
            case 'today':
                return ['start' => $today, 'end' => $today];
                
            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                return ['start' => $yesterday, 'end' => $yesterday];
                
            case 'last_7d':
                return [
                    'start' => date('Y-m-d', strtotime('-7 days')),
                    'end' => $today
                ];
                
            case 'last_30d':
                return [
                    'start' => date('Y-m-d', strtotime('-30 days')),
                    'end' => $today
                ];
                
            case 'this_month':
                return [
                    'start' => date('Y-m-01'),
                    'end' => $today
                ];
                
            case 'last_month':
                return [
                    'start' => date('Y-m-01', strtotime('first day of last month')),
                    'end' => date('Y-m-t', strtotime('last day of last month'))
                ];
                
            case 'custom':
                if ($startDate && $endDate) {
                    return ['start' => $startDate, 'end' => $endDate];
                }
                return [
                    'start' => date('Y-m-d', strtotime('-2 years')),
                    'end' => $today
                ];
                
            case 'maximum':
            default:
                return [
                    'start' => date('Y-m-d', strtotime('-2 years')),
                    'end' => $today
                ];
        }
    }
    
    /**
     * Busca último período sincronizado
     */
    private function getLastSyncPeriod() {
        $result = $this->db->fetch("
            SELECT preference_value 
            FROM user_preferences 
            WHERE user_id = :user_id 
            AND preference_key = 'last_sync_period'
        ", ['user_id' => $this->auth->id()]);
        
        return $result ? $result['preference_value'] : null;
    }
    
    /**
     * Salva último período sincronizado
     */
    private function saveLastSyncPeriod($period) {
        $this->db->query("
            INSERT INTO user_preferences (user_id, preference_key, preference_value)
            VALUES (:user_id, 'last_sync_period', :period)
            ON DUPLICATE KEY UPDATE 
                preference_value = :period,
                updated_at = NOW()
        ", [
            'user_id' => $this->auth->id(),
            'period' => $period
        ]);
        
        $this->db->query("
            INSERT INTO user_preferences (user_id, preference_key, preference_value)
            VALUES (:user_id, 'last_sync_time', :time)
            ON DUPLICATE KEY UPDATE 
                preference_value = :time,
                updated_at = NOW()
        ", [
            'user_id' => $this->auth->id(),
            'time' => time()
        ]);
    }
    
    /**
     * Verifica se precisa resincronizar (1 hora)
     */
    private function shouldResync($period) {
        $result = $this->db->fetch("
            SELECT preference_value 
            FROM user_preferences 
            WHERE user_id = :user_id 
            AND preference_key = 'last_sync_time'
        ", ['user_id' => $this->auth->id()]);
        
        if (!$result) {
            return true;
        }
        
        $lastSyncTime = intval($result['preference_value']);
        $hourAgo = time() - 3600;
        
        return $lastSyncTime < $hourAgo;
    }
    
    /**
     * Busca colunas salvas pelo usuário
     */
    private function getUserColumns() {
        $result = $this->db->fetch("
            SELECT preference_value 
            FROM user_preferences 
            WHERE user_id = :user_id 
            AND preference_key = 'campaign_columns'
        ", ['user_id' => $this->auth->id()]);
        
        if ($result && !empty($result['preference_value'])) {
            $columns = json_decode($result['preference_value'], true);
            if (is_array($columns) && count($columns) > 0) {
                return $columns;
            }
        }
        
        return null;
    }
    
    /**
     * ========================================
     * SINCRONIZAÇÃO MANUAL VIA AJAX
     * ========================================
     */
    public function syncComplete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método não permitido');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        error_log("[CONTROLLER] 🔄 SINCRONIZAÇÃO MANUAL via AJAX");
        
        $options = [
            'date_preset' => $data['date_preset'] ?? 'maximum',
            'custom_range' => $data['custom_range'] ?? null,
            'breakdowns' => $data['breakdowns'] ?? [],
            'include_insights' => $data['include_insights'] ?? true,
            'include_actions' => $data['include_actions'] ?? true,
            'include_video_data' => $data['include_video_data'] ?? true,
            'include_demographics' => $data['include_demographics'] ?? false
        ];
        
        try {
            $results = $this->metaSync->syncAll($options);
            
            $this->saveLastSyncPeriod($options['date_preset']);
            
            $periodDates = $this->calculatePeriodDates($options['date_preset']);
            $campaigns = $this->getCampaignsWithFullData([], $options['date_preset']);
            
            $stats = $this->calculateStats($campaigns);
            
            $this->jsonResponse([
                'success' => true,
                'campaigns' => $campaigns,
                'stats' => $stats,
                'sync_results' => $results,
                'message' => $this->buildSyncMessage($results)
            ]);
            
        } catch (Exception $e) {
            error_log("[CONTROLLER] ❌ Erro: " . $e->getMessage());
            $this->jsonError($e->getMessage());
        }
    }
    
    /**
     * Atualiza campo específico
     */
    public function updateField() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método não permitido');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $campaignId = intval($data['campaign_id'] ?? 0);
        $field = $data['field'] ?? '';
        $value = $data['value'] ?? null;
        
        $allowedFields = ['campaign_name', 'daily_budget', 'lifetime_budget', 'spend_cap', 
                         'status', 'bid_strategy', 'start_time', 'stop_time'];
        
        if (!in_array($field, $allowedFields)) {
            $this->jsonError('Campo não permitido');
        }
        
        $campaign = $this->db->fetch("
            SELECT c.*, aa.access_token 
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.id = :id AND c.user_id = :user_id
        ", [
            'id' => $campaignId,
            'user_id' => $this->auth->id()
        ]);
        
        if (!$campaign) {
            $this->jsonError('Campanha não encontrada');
        }
        
        $this->db->update('campaigns', 
            [$field => $value],
            'id = :id',
            ['id' => $campaignId]
        );
        
        $metaUpdated = false;
        $metaError = null;
        
        if ($campaign['campaign_id'] && $campaign['access_token']) {
            try {
                $metaUpdated = $this->updateInMeta(
                    $campaign['campaign_id'],
                    $field,
                    $value,
                    $campaign['access_token']
                );
            } catch (Exception $e) {
                $metaError = $e->getMessage();
            }
        }
        
        $this->jsonResponse([
            'success' => true,
            'meta_updated' => $metaUpdated,
            'message' => $metaUpdated 
                ? 'Atualizado no Meta Ads'
                : 'Atualizado localmente' . ($metaError ? " (Erro Meta: $metaError)" : '')
        ]);
    }
    
    /**
     * Atualiza no Meta Ads
     */
    private function updateInMeta($campaignId, $field, $value, $accessToken) {
        $url = "https://graph.facebook.com/v18.0/{$campaignId}";
        
        $fieldMap = [
            'campaign_name' => 'name',
            'daily_budget' => 'daily_budget',
            'lifetime_budget' => 'lifetime_budget',
            'spend_cap' => 'spend_cap',
            'status' => 'status',
            'bid_strategy' => 'bid_strategy',
            'start_time' => 'start_time',
            'stop_time' => 'stop_time'
        ];
        
        $metaField = $fieldMap[$field] ?? $field;
        
        if (in_array($field, ['daily_budget', 'lifetime_budget', 'spend_cap'])) {
            $value = intval($value * 100);
        }
        
        $postData = [
            $metaField => $value,
            'access_token' => $accessToken
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            throw new Exception($error['error']['message'] ?? "HTTP $httpCode");
        }
        
        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true;
    }
    
    /**
     * Ações em massa
     */
    public function bulkAction() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método não permitido');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $action = $data['bulk_action'] ?? '';
        $campaignIds = $data['campaign_ids'] ?? [];
        
        if (empty($campaignIds)) {
            $this->jsonError('Nenhuma campanha selecionada');
        }
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($campaignIds as $campaignId) {
            try {
                switch ($action) {
                    case 'activate':
                        $this->updateCampaignStatus($campaignId, 'ACTIVE');
                        break;
                    case 'pause':
                        $this->updateCampaignStatus($campaignId, 'PAUSED');
                        break;
                    case 'delete':
                        $this->updateCampaignStatus($campaignId, 'DELETED');
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
        
        $this->jsonResponse([
            'success' => $results['failed'] === 0,
            'results' => $results,
            'message' => "{$results['success']} campanhas processadas" . 
                        ($results['failed'] > 0 ? ", {$results['failed']} falharam" : "")
        ]);
    }
    
    /**
     * Atualiza status da campanha
     */
    private function updateCampaignStatus($campaignId, $status) {
        $campaign = $this->db->fetch("
            SELECT c.*, aa.access_token 
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.id = :id AND c.user_id = :user_id
        ", [
            'id' => $campaignId,
            'user_id' => $this->auth->id()
        ]);
        
        if (!$campaign) {
            throw new Exception('Campanha não encontrada');
        }
        
        $this->db->update('campaigns', 
            ['status' => strtolower($status)],
            'id = :id',
            ['id' => $campaignId]
        );
        
        if ($campaign['campaign_id'] && $campaign['access_token']) {
            $this->updateInMeta(
                $campaign['campaign_id'],
                'status',
                $status,
                $campaign['access_token']
            );
        }
    }
    
    /**
     * Salva configuração de colunas
     */
    public function saveColumns() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método não permitido');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $columns = $data['columns'] ?? [];
        
        if (empty($columns)) {
            $this->jsonError('Colunas inválidas');
        }
        
        $this->db->query("
            INSERT INTO user_preferences (user_id, preference_key, preference_value, updated_at)
            VALUES (:user_id, 'campaign_columns', :value, NOW())
            ON DUPLICATE KEY UPDATE 
                preference_value = :value,
                updated_at = NOW()
        ", [
            'user_id' => $this->auth->id(),
            'value' => json_encode($columns)
        ]);
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Colunas salvas com sucesso',
            'columns' => $columns
        ]);
    }
    
    /**
     * Exporta dados
     */
    public function export() {
        $format = $_GET['format'] ?? 'csv';
        $filters = $_GET['filters'] ?? [];
        $period = $_GET['period'] ?? 'maximum';
        
        $campaigns = $this->getCampaignsWithFullData($filters, $period);
        
        switch ($format) {
            case 'csv':
                $this->exportCsv($campaigns);
                break;
            case 'json':
                $this->exportJson($campaigns);
                break;
            default:
                $this->jsonError('Formato não suportado');
        }
    }
    
    /**
     * Exporta CSV
     */
    private function exportCsv($campaigns) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="campaigns_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($campaigns)) {
            $headers = array_keys($campaigns[0]);
            fputcsv($output, $headers);
            
            foreach ($campaigns as $campaign) {
                fputcsv($output, $campaign);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exporta JSON
     */
    private function exportJson($campaigns) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="campaigns_' . date('Y-m-d_H-i-s') . '.json"');
        echo json_encode($campaigns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Helpers
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    private function jsonError($message, $code = 400) {
        http_response_code($code);
        $this->jsonResponse([
            'success' => false,
            'message' => $message
        ]);
    }
    
    /**
     * Calcula estatísticas
     */
    private function calculateStats($campaigns) {
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
     * Monta mensagem de sincronização
     */
    private function buildSyncMessage($results) {
        $parts = [];
        
        if (isset($results['campaigns']['synced']) && $results['campaigns']['synced'] > 0) {
            $parts[] = "{$results['campaigns']['synced']} campanhas";
        }
        if (isset($results['adsets']['synced']) && $results['adsets']['synced'] > 0) {
            $parts[] = "{$results['adsets']['synced']} conjuntos";
        }
        if (isset($results['ads']['synced']) && $results['ads']['synced'] > 0) {
            $parts[] = "{$results['ads']['synced']} anúncios";
        }
        
        $message = !empty($parts) ? 
            "Sincronizados: " . implode(', ', $parts) : 
            "Nenhum dado novo";
        
        if (isset($results['campaigns']['errors']) && !empty($results['campaigns']['errors'])) {
            $message .= " | " . count($results['campaigns']['errors']) . " erro(s)";
        }
        
        if (isset($results['duration'])) {
            $message .= " | {$results['duration']}s";
        }
        
        return $message;
    }
}