<?php
/**
 * ========================================
 * SINCRONIZADOR COMPLETO META ADS V3.1
 * ========================================
 * VERSÃO DEFINITIVA COM TUDO:
 * - ✅ 150+ campos (campanhas, insights, adsets, ads)
 * - ✅ Custom Audiences
 * - ✅ Pixels
 * - ✅ Insights com breakdowns
 * - ✅ Detalhes de criativos
 * - ✅ CBO e ASC detection
 * - ✅ Quality rankings
 * - ✅ Learning stage
 * - ✅ Issues e recommendations
 * - ✅ Status correto (effective_status)
 */

// ✅ CORREÇÃO: Usa caminho absoluto
require_once __DIR__ . '/MetaAdsDataStructure.php';

class MetaAdsSync {
    
    private $db;
    private $userId;
    private $accounts = [];
    private $batchSize = 50;
    private $apiVersion = 'v18.0';
    private $rateLimitDelay = 200000;
    private $maxRetries = 3;
    private $retryDelay = 1000000;
    
    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
        $this->loadAccounts();
        
        // ✅ Log para debug
        error_log("[METASYNC] Inicializado para usuário ID: {$userId}");
    }
    
    private function loadAccounts() {
        $this->accounts = $this->db->fetchAll("
            SELECT * FROM ad_accounts 
            WHERE user_id = :user_id 
            AND platform = 'meta' 
            AND status = 'active'
            AND access_token IS NOT NULL
        ", ['user_id' => $this->userId]);
    }
    
    /**
     * ========================================
     * SINCRONIZAÇÃO COMPLETA - TUDO
     * ========================================
     */
    public function syncAll($options = []) {
        $results = [
            'campaigns' => ['synced' => 0, 'errors' => []],
            'adsets' => ['synced' => 0, 'errors' => []],
            'ads' => ['synced' => 0, 'errors' => []],
            'insights' => ['synced' => 0, 'errors' => []],
            'custom_audiences' => ['synced' => 0, 'errors' => []],
            'pixels' => ['synced' => 0, 'errors' => []],
            'duration' => 0
        ];
        
        $startTime = microtime(true);
        
        // ✅ Log de início
        error_log("[METASYNC] ========================================");
        error_log("[METASYNC] Iniciando sincronização completa");
        error_log("[METASYNC] Usuário: {$this->userId}");
        error_log("[METASYNC] Contas: " . count($this->accounts));
        error_log("[METASYNC] Opções: " . json_encode($options));
        error_log("[METASYNC] ========================================");
        
        foreach ($this->accounts as $account) {
            try {
                error_log("[METASYNC] 📊 Processando conta: {$account['account_name']}");
                
                // 1. Campanhas + Insights
                $campaigns = $this->syncCampaigns($account, $options);
                $results['campaigns']['synced'] += count($campaigns);
                error_log("[METASYNC] ✅ {$account['account_name']}: " . count($campaigns) . " campanhas");
                
                // 2. AdSets + Insights
                foreach ($campaigns as $campaign) {
                    $adsets = $this->syncAdSets($account, $campaign['id'], $options);
                    $results['adsets']['synced'] += count($adsets);
                    
                    // 3. Ads + Insights + Creatives
                    foreach ($adsets as $adset) {
                        $ads = $this->syncAds($account, $adset['id'], $options);
                        $results['ads']['synced'] += count($ads);
                    }
                }
                
                error_log("[METASYNC] ✅ {$account['account_name']}: " . count($adsets ?? []) . " adsets, " . count($ads ?? []) . " ads");
                
                // 4. Insights detalhados com breakdowns
                $this->syncDetailedInsights($account, $options);
                $results['insights']['synced']++;
                
                // 5. Audiências personalizadas
                $audiences = $this->syncCustomAudiences($account);
                $results['custom_audiences']['synced'] += count($audiences);
                
                // 6. Pixels
                $pixels = $this->syncPixels($account);
                $results['pixels']['synced'] += count($pixels);
                
            } catch (Exception $e) {
                error_log("[METASYNC] ❌ Erro na conta {$account['account_name']}: " . $e->getMessage());
                $results['campaigns']['errors'][] = [
                    'account' => $account['account_name'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $results['duration'] = round(microtime(true) - $startTime, 2);
        
        // ✅ Log de conclusão
        error_log("[METASYNC] ========================================");
        error_log("[METASYNC] ✅ Sincronização concluída!");
        error_log("[METASYNC] Campanhas: {$results['campaigns']['synced']}");
        error_log("[METASYNC] AdSets: {$results['adsets']['synced']}");
        error_log("[METASYNC] Ads: {$results['ads']['synced']}");
        error_log("[METASYNC] Audiências: {$results['custom_audiences']['synced']}");
        error_log("[METASYNC] Pixels: {$results['pixels']['synced']}");
        error_log("[METASYNC] Duração: {$results['duration']}s");
        error_log("[METASYNC] Erros: " . count($results['campaigns']['errors']));
        error_log("[METASYNC] ========================================");
        
        return $results;
    }
    
    /**
     * ========================================
     * SINCRONIZA CAMPANHAS - TODOS OS 50+ CAMPOS
     * ========================================
     */
    private function syncCampaigns($account, $options = []) {
        $campaigns = [];
        $accountId = str_replace('act_', '', $account['account_id']);
        $accessToken = $account['access_token'];
        
        // ✅ TODOS OS CAMPOS
        $campaignFields = implode(',', array_keys(MetaAdsDataStructure::CAMPAIGN_FIELDS));
        
        error_log("[SYNC] Buscando campos: $campaignFields");
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/act_{$accountId}/campaigns";
        
        $params = [
            'fields' => $campaignFields,
            'access_token' => $accessToken,
            'limit' => $this->batchSize
        ];
        
        if (!empty($options['status'])) {
            $params['effective_status'] = json_encode($options['status']);
        }
        
        $hasNextPage = true;
        $after = null;
        
        while ($hasNextPage) {
            if ($after) {
                $params['after'] = $after;
            }
            
            $response = $this->makeApiCall($url . '?' . http_build_query($params));
            
            if (!empty($response['data'])) {
                foreach ($response['data'] as $campaign) {
                    error_log("[SYNC] Campanha {$campaign['name']}: status={$campaign['status']}, effective_status={$campaign['effective_status']}");
                    
                    $processedCampaign = $this->processCampaignData($campaign, $account['id']);
                    $this->saveCampaign($processedCampaign);
                    $campaigns[] = $processedCampaign;
                    
                    // Busca insights
                    $insights = $this->getCampaignInsights($campaign['id'], $accessToken, $options);
                    $this->saveCampaignInsights($campaign['id'], $insights);
                }
            }
            
            if (!empty($response['paging']['next'])) {
                $after = $response['paging']['cursors']['after'] ?? null;
                $hasNextPage = !empty($after);
            } else {
                $hasNextPage = false;
            }
            
            usleep($this->rateLimitDelay);
        }
        
        return $campaigns;
    }
    
    /**
     * ========================================
     * BUSCA INSIGHTS - TODOS OS 80+ CAMPOS
     * ========================================
     */
    private function getCampaignInsights($campaignId, $accessToken, $options = []) {
        // ✅ TODOS OS CAMPOS
        $insightFields = implode(',', array_keys(MetaAdsDataStructure::INSIGHTS_FIELDS));
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$campaignId}/insights";
        
        $params = [
            'fields' => $insightFields . ',actions,action_values,video_play_actions,video_avg_time_watched_actions,video_continuous_2_sec_watched_actions,cost_per_action_type,cost_per_unique_action_type,cost_per_conversion,unique_actions,conversions,conversion_values,catalog_segment_value,catalog_segment_actions,outbound_clicks,outbound_clicks_ctr,unique_outbound_clicks,unique_outbound_clicks_ctr,website_ctr,website_purchase_roas,dda_results',
            'access_token' => $accessToken,
            'level' => 'campaign',
            'use_unified_attribution_setting' => 'true'
        ];
        
        // ✅ VALIDAÇÃO: Define período corretamente
        if (!empty($options['date_preset'])) {
            $params['date_preset'] = $options['date_preset'];
            error_log("[METASYNC] Usando date_preset: {$options['date_preset']}");
        } elseif (!empty($options['time_range']) && is_array($options['time_range'])) {
            // ✅ VALIDA se time_range tem os campos necessários
            if (isset($options['time_range']['since']) && isset($options['time_range']['until'])) {
                $params['time_range'] = json_encode($options['time_range']);
                error_log("[METASYNC] Usando time_range: " . json_encode($options['time_range']));
            } else {
                // Se time_range inválido, usa maximum
                $params['date_preset'] = 'maximum';
                error_log("[METASYNC] ⚠️ time_range inválido, usando 'maximum'");
            }
        } else {
            $params['date_preset'] = 'maximum';
            error_log("[METASYNC] Usando date_preset padrão: maximum");
        }
        
        if (!empty($options['breakdowns'])) {
            $params['breakdowns'] = implode(',', $options['breakdowns']);
        }
        
        $response = $this->makeApiCall($url . '?' . http_build_query($params));
        
        return !empty($response['data'][0]) ? $response['data'][0] : [];
    }
    
    /**
     * ========================================
     * PROCESSA DADOS DA CAMPANHA - TODOS OS CAMPOS
     * ========================================
     */
    private function processCampaignData($campaign, $accountId) {
        $actualStatus = $this->determineActualStatus($campaign);
        
        // ✅ Detecta ASC e CBO
        $isASC = $this->detectASC($campaign);
        $usesCBO = !empty($campaign['campaign_budget_optimization']);
        
        $processed = [
            'ad_account_id' => $accountId,
            'campaign_id' => $campaign['id'],
            'campaign_name' => $campaign['name'] ?? 'Sem nome',
            
            // Status
            'status' => $actualStatus,
            'effective_status' => $campaign['effective_status'] ?? null,
            'configured_status' => $campaign['configured_status'] ?? null,
            
            // Objetivo
            'objective' => $campaign['objective'] ?? null,
            'buying_type' => $campaign['buying_type'] ?? null,
            'can_use_spend_cap' => $campaign['can_use_spend_cap'] ?? false,
            
            // Orçamento
            'daily_budget' => isset($campaign['daily_budget']) ? floatval($campaign['daily_budget']) / 100 : 0,
            'lifetime_budget' => isset($campaign['lifetime_budget']) ? floatval($campaign['lifetime_budget']) / 100 : 0,
            'spend_cap' => isset($campaign['spend_cap']) ? floatval($campaign['spend_cap']) / 100 : 0,
            'budget_remaining' => isset($campaign['budget_remaining']) ? floatval($campaign['budget_remaining']) / 100 : 0,
            
            // ✅ NOVOS: CBO e Limites
            'campaign_budget_optimization' => $usesCBO ? 1 : 0,
            'daily_min_spend_target' => isset($campaign['daily_min_spend_target']) ? floatval($campaign['daily_min_spend_target']) / 100 : 0,
            'daily_spend_cap' => isset($campaign['daily_spend_cap']) ? floatval($campaign['daily_spend_cap']) / 100 : 0,
            'lifetime_min_spend_target' => isset($campaign['lifetime_min_spend_target']) ? floatval($campaign['lifetime_min_spend_target']) / 100 : 0,
            'lifetime_spend_cap' => isset($campaign['lifetime_spend_cap']) ? floatval($campaign['lifetime_spend_cap']) / 100 : 0,
            
            // ✅ NOVO: ASC Detection
            'is_asc' => $isASC ? 1 : 0,
            
            // Lance
            'bid_strategy' => $campaign['bid_strategy'] ?? null,
            'bid_amount' => isset($campaign['bid_amount']) ? floatval($campaign['bid_amount']) / 100 : 0,
            'bid_constraints' => !empty($campaign['bid_constraints']) ? json_encode($campaign['bid_constraints']) : null,
            
            // Configurações
            'pacing_type' => !empty($campaign['pacing_type']) ? json_encode($campaign['pacing_type']) : null,
            'promoted_object' => !empty($campaign['promoted_object']) ? json_encode($campaign['promoted_object']) : null,
            
            // Categorias especiais
            'special_ad_categories' => !empty($campaign['special_ad_categories']) ? json_encode($campaign['special_ad_categories']) : null,
            'special_ad_category' => $campaign['special_ad_category'] ?? null,
            'special_ad_category_country' => !empty($campaign['special_ad_category_country']) ? json_encode($campaign['special_ad_category_country']) : null,
            
            // SKAdNetwork
            'is_skadnetwork_attribution' => $campaign['is_skadnetwork_attribution'] ?? false,
            'smart_promotion_type' => $campaign['smart_promotion_type'] ?? null,
            'source_campaign_id' => $campaign['source_campaign_id'] ?? null,
            
            // ✅ NOVOS: Problemas e Recomendações
            'issues_info' => !empty($campaign['issues_info']) ? json_encode($campaign['issues_info']) : null,
            'recommendations' => !empty($campaign['recommendations']) ? json_encode($campaign['recommendations']) : null,
            
            // ✅ NOVOS: Labels e Organização
            'adlabels' => !empty($campaign['adlabels']) ? json_encode($campaign['adlabels']) : null,
            'campaign_group_id' => $campaign['campaign_group_id'] ?? null,
            'topline_id' => $campaign['topline_id'] ?? null,
            
            // ✅ NOVOS: Outros campos
            'budget_rebalance_flag' => $campaign['budget_rebalance_flag'] ?? false,
            'can_create_brand_lift_study' => $campaign['can_create_brand_lift_study'] ?? false,
            'has_secondary_skadnetwork_reporting' => $campaign['has_secondary_skadnetwork_reporting'] ?? false,
            'is_budget_schedule_enabled' => $campaign['is_budget_schedule_enabled'] ?? false,
            'iterative_split_test_configs' => !empty($campaign['iterative_split_test_configs']) ? json_encode($campaign['iterative_split_test_configs']) : null,
            'last_budget_toggling_time' => isset($campaign['last_budget_toggling_time']) ? date('Y-m-d H:i:s', strtotime($campaign['last_budget_toggling_time'])) : null,
            'upstream_events' => !empty($campaign['upstream_events']) ? json_encode($campaign['upstream_events']) : null,
            
            // Datas
            'start_time' => isset($campaign['start_time']) ? date('Y-m-d H:i:s', strtotime($campaign['start_time'])) : null,
            'stop_time' => isset($campaign['stop_time']) ? date('Y-m-d H:i:s', strtotime($campaign['stop_time'])) : null,
            'created_time' => isset($campaign['created_time']) ? date('Y-m-d H:i:s', strtotime($campaign['created_time'])) : null,
            'updated_time' => isset($campaign['updated_time']) ? date('Y-m-d H:i:s', strtotime($campaign['updated_time'])) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
        
        error_log("[SYNC] Campanha {$campaign['name']}: CBO={$usesCBO}, ASC={$isASC}, Status={$actualStatus}");
        
        return $processed;
    }
    
    /**
     * ✅ Detecta se é Advantage Shopping Campaign
     */
    private function detectASC($campaign) {
        $name = strtolower($campaign['name'] ?? '');
        $objective = $campaign['objective'] ?? '';
        
        if ($objective === 'OUTCOME_SALES') {
            return true;
        }
        
        $ascKeywords = ['advantage', 'asc', 'shopping', 'advantage+', 'advantage shopping'];
        foreach ($ascKeywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * ✅ Determina status real
     */
    private function determineActualStatus($campaign) {
        if (!empty($campaign['effective_status'])) {
            return $this->normalizeStatus($campaign['effective_status']);
        }
        
        if (!empty($campaign['configured_status'])) {
            return $this->normalizeStatus($campaign['configured_status']);
        }
        
        if (!empty($campaign['status'])) {
            return $this->normalizeStatus($campaign['status']);
        }
        
        return 'paused';
    }
    
    /**
     * ✅ Normaliza status
     */
    private function normalizeStatus($status) {
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
     * ✅ Salva campanha
     */
    private function saveCampaign($campaignData) {
        $exists = $this->db->fetch("
            SELECT id FROM campaigns 
            WHERE campaign_id = :campaign_id AND user_id = :user_id
        ", [
            'campaign_id' => $campaignData['campaign_id'],
            'user_id' => $this->userId
        ]);
        
        $campaignData['user_id'] = $this->userId;
        
        if ($exists) {
            $updateData = $campaignData;
            unset($updateData['user_id']);
            
            error_log("[SYNC] Atualizando campanha ID {$exists['id']}: novo status = {$updateData['status']}");
            
            $this->db->update('campaigns', $updateData, 'id = :id', ['id' => $exists['id']]);
            return $exists['id'];
        } else {
            error_log("[SYNC] Inserindo nova campanha: {$campaignData['campaign_name']} com status = {$campaignData['status']}");
            
            return $this->db->insert('campaigns', $campaignData);
        }
    }
    
    /**
     * ========================================
     * SALVA INSIGHTS - TODOS OS 80+ CAMPOS
     * ========================================
     */
    private function saveCampaignInsights($campaignId, $insights) {
        if (empty($insights)) return;
        
        $campaign = $this->db->fetch("
            SELECT id FROM campaigns 
            WHERE campaign_id = :campaign_id AND user_id = :user_id
        ", [
            'campaign_id' => $campaignId,
            'user_id' => $this->userId
        ]);
        
        if (!$campaign) return;
        
        $insightData = [
            'campaign_id' => $campaign['id'],
            'date_start' => $insights['date_start'] ?? date('Y-m-d'),
            'date_stop' => $insights['date_stop'] ?? date('Y-m-d')
        ];
        
        // ✅ Mapeia TODOS os campos
        foreach (MetaAdsDataStructure::INSIGHTS_FIELDS as $field => $config) {
            if (isset($insights[$field])) {
                $value = $insights[$field];
                
                switch ($config['type']) {
                    case 'currency':
                        $value = floatval($value);
                        break;
                    case 'integer':
                        $value = intval($value);
                        break;
                    case 'percentage':
                    case 'float':
                        $value = floatval($value);
                        break;
                    case 'array':
                    case 'object':
                        $value = is_string($value) ? $value : json_encode($value);
                        break;
                }
                
                $insightData[$field] = $value;
            }
        }
        
        // ✅ Processa actions
        if (!empty($insights['actions'])) {
            $actions = MetaAdsDataStructure::processActions($insights['actions']);
            foreach ($actions as $actionKey => $actionData) {
                if (array_key_exists($actionKey, MetaAdsDataStructure::INSIGHTS_FIELDS)) {
                    $insightData[$actionKey] = $actionData['value'];
                }
            }
        }
        
        // ✅ Processa action_values
        if (!empty($insights['action_values'])) {
            $actionValues = MetaAdsDataStructure::processActionValues($insights['action_values']);
            foreach ($actionValues as $key => $value) {
                if (array_key_exists($key, MetaAdsDataStructure::INSIGHTS_FIELDS)) {
                    $insightData[$key] = $value;
                }
            }
        }
        
        // ✅ Calcula métricas customizadas
        $customMetrics = MetaAdsDataStructure::calculateCustomMetrics($insightData);
        $insightData = array_merge($insightData, $customMetrics);
        
        $exists = $this->db->fetch("
            SELECT id FROM campaign_insights 
            WHERE campaign_id = :campaign_id 
            AND date_start = :date_start 
            AND date_stop = :date_stop
        ", [
            'campaign_id' => $campaign['id'],
            'date_start' => $insightData['date_start'],
            'date_stop' => $insightData['date_stop']
        ]);
        
        if ($exists) {
            $this->db->update('campaign_insights', $insightData, 'id = :id', ['id' => $exists['id']]);
        } else {
            $this->db->insert('campaign_insights', $insightData);
        }
    }
    
    /**
     * ========================================
     * SINCRONIZA ADSETS - TODOS OS 35+ CAMPOS
     * ========================================
     */
    private function syncAdSets($account, $campaignId, $options = []) {
        $adsets = [];
        $accessToken = $account['access_token'];
        
        $adsetFields = implode(',', array_keys(MetaAdsDataStructure::ADSET_FIELDS));
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$campaignId}/adsets";
        
        $params = [
            'fields' => $adsetFields,
            'access_token' => $accessToken,
            'limit' => $this->batchSize
        ];
        
        $response = $this->makeApiCall($url . '?' . http_build_query($params));
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $adset) {
                $processedAdSet = $this->processAdSetData($adset, $campaignId);
                $this->saveAdSet($processedAdSet);
                $adsets[] = $processedAdSet;
                
                $insights = $this->getAdSetInsights($adset['id'], $accessToken, $options);
                $this->saveAdSetInsights($adset['id'], $insights);
            }
        }
        
        return $adsets;
    }
    
    /**
     * ✅ Processa AdSet
     */
    private function processAdSetData($adset, $campaignId) {
        $actualStatus = $this->determineActualStatus($adset);
        
        $processed = [
            'campaign_id' => $campaignId,
            'adset_id' => $adset['id'],
            'adset_name' => $adset['name'] ?? 'Sem nome',
            'status' => $actualStatus,
            'effective_status' => $adset['effective_status'] ?? null,
            'optimization_goal' => $adset['optimization_goal'] ?? null,
            'optimization_sub_event' => $adset['optimization_sub_event'] ?? null,
            'billing_event' => $adset['billing_event'] ?? null,
            'bid_amount' => isset($adset['bid_amount']) ? floatval($adset['bid_amount']) / 100 : 0,
            'bid_strategy' => $adset['bid_strategy'] ?? null,
            'bid_constraints' => !empty($adset['bid_constraints']) ? json_encode($adset['bid_constraints']) : null,
            'bid_info' => !empty($adset['bid_info']) ? json_encode($adset['bid_info']) : null,
            'daily_budget' => isset($adset['daily_budget']) ? floatval($adset['daily_budget']) / 100 : 0,
            'lifetime_budget' => isset($adset['lifetime_budget']) ? floatval($adset['lifetime_budget']) / 100 : 0,
            'budget_remaining' => isset($adset['budget_remaining']) ? floatval($adset['budget_remaining']) / 100 : 0,
            'daily_min_spend_target' => isset($adset['daily_min_spend_target']) ? floatval($adset['daily_min_spend_target']) / 100 : 0,
            'daily_spend_cap' => isset($adset['daily_spend_cap']) ? floatval($adset['daily_spend_cap']) / 100 : 0,
            'lifetime_min_spend_target' => isset($adset['lifetime_min_spend_target']) ? floatval($adset['lifetime_min_spend_target']) / 100 : 0,
            'lifetime_spend_cap' => isset($adset['lifetime_spend_cap']) ? floatval($adset['lifetime_spend_cap']) / 100 : 0,
            'targeting' => !empty($adset['targeting']) ? json_encode($adset['targeting']) : null,
            'promoted_object' => !empty($adset['promoted_object']) ? json_encode($adset['promoted_object']) : null,
            'attribution_spec' => !empty($adset['attribution_spec']) ? json_encode($adset['attribution_spec']) : null,
            'destination_type' => $adset['destination_type'] ?? null,
            'multi_optimization_goal_weight' => $adset['multi_optimization_goal_weight'] ?? null,
            'pacing_type' => !empty($adset['pacing_type']) ? json_encode($adset['pacing_type']) : null,
            'recurring_budget_semantics' => $adset['recurring_budget_semantics'] ?? false,
            'rf_prediction_id' => $adset['rf_prediction_id'] ?? null,
            'time_based_ad_rotation_id_blocks' => !empty($adset['time_based_ad_rotation_id_blocks']) ? json_encode($adset['time_based_ad_rotation_id_blocks']) : null,
            'learning_stage_info' => !empty($adset['learning_stage_info']) ? json_encode($adset['learning_stage_info']) : null,
            'issues_info' => !empty($adset['issues_info']) ? json_encode($adset['issues_info']) : null,
            'recommendations' => !empty($adset['recommendations']) ? json_encode($adset['recommendations']) : null,
            'start_time' => isset($adset['start_time']) ? date('Y-m-d H:i:s', strtotime($adset['start_time'])) : null,
            'end_time' => isset($adset['end_time']) ? date('Y-m-d H:i:s', strtotime($adset['end_time'])) : null,
            'created_time' => isset($adset['created_time']) ? date('Y-m-d H:i:s', strtotime($adset['created_time'])) : null,
            'updated_time' => isset($adset['updated_time']) ? date('Y-m-d H:i:s', strtotime($adset['updated_time'])) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
        
        return $processed;
    }
    
    /**
     * ✅ Salva AdSet
     */
    private function saveAdSet($adsetData) {
        $exists = $this->db->fetch("
            SELECT id FROM adsets WHERE adset_id = :adset_id
        ", ['adset_id' => $adsetData['adset_id']]);
        
        $adsetData['user_id'] = $this->userId;
        
        if ($exists) {
            $updateData = $adsetData;
            unset($updateData['user_id']);
            $this->db->update('adsets', $updateData, 'id = :id', ['id' => $exists['id']]);
            return $exists['id'];
        } else {
            return $this->db->insert('adsets', $adsetData);
        }
    }
    
    /**
     * ✅ Insights de AdSet
     */
    private function getAdSetInsights($adsetId, $accessToken, $options = []) {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$adsetId}/insights";
        
        $params = [
            'fields' => implode(',', array_keys(MetaAdsDataStructure::INSIGHTS_FIELDS)) . ',actions,action_values',
            'access_token' => $accessToken,
            'date_preset' => $options['date_preset'] ?? 'maximum'
        ];
        
        $response = $this->makeApiCall($url . '?' . http_build_query($params));
        
        return !empty($response['data'][0]) ? $response['data'][0] : [];
    }
    
    /**
     * ✅ Salva insights do AdSet
     */
    private function saveAdSetInsights($adsetId, $insights) {
        if (empty($insights)) return;
        
        $adset = $this->db->fetch("
            SELECT id FROM adsets WHERE adset_id = :adset_id
        ", ['adset_id' => $adsetId]);
        
        if (!$adset) return;
        
        $updateData = [
            'spent' => floatval($insights['spend'] ?? 0),
            'impressions' => intval($insights['impressions'] ?? 0),
            'clicks' => intval($insights['clicks'] ?? 0),
            'conversions' => intval($insights['purchase'] ?? 0),
            'reach' => intval($insights['reach'] ?? 0),
            'frequency' => floatval($insights['frequency'] ?? 0),
            'ctr' => floatval($insights['ctr'] ?? 0),
            'cpc' => floatval($insights['cpc'] ?? 0),
            'cpm' => floatval($insights['cpm'] ?? 0)
        ];
        
        $this->db->update('adsets', $updateData, 'id = :id', ['id' => $adset['id']]);
    }
    
    /**
     * ========================================
     * SINCRONIZA ADS - TODOS OS 20+ CAMPOS
     * ========================================
     */
    private function syncAds($account, $adsetId, $options = []) {
        $ads = [];
        $accessToken = $account['access_token'];
        
        $adFields = implode(',', array_keys(MetaAdsDataStructure::AD_FIELDS));
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$adsetId}/ads";
        
        $params = [
            'fields' => $adFields,
            'access_token' => $accessToken,
            'limit' => $this->batchSize
        ];
        
        $response = $this->makeApiCall($url . '?' . http_build_query($params));
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $ad) {
                $processedAd = $this->processAdData($ad, $adsetId);
                $this->saveAd($processedAd);
                $ads[] = $processedAd;
                
                $insights = $this->getAdInsights($ad['id'], $accessToken, $options);
                $this->saveAdInsights($ad['id'], $insights);
                
                // ✅ Busca detalhes do creative
                if (!empty($ad['creative']['id'])) {
                    $creative = $this->getCreativeDetails($ad['creative']['id'], $accessToken);
                    $this->saveCreative($ad['id'], $creative);
                }
            }
        }
        
        return $ads;
    }
    
    /**
     * ✅ Processa Ad
     */
    private function processAdData($ad, $adsetId) {
        $actualStatus = $this->determineActualStatus($ad);
        
        $processed = [
            'adset_id' => $adsetId,
            'ad_id' => $ad['id'],
            'ad_name' => $ad['name'] ?? 'Sem nome',
            'status' => $actualStatus,
            'effective_status' => $ad['effective_status'] ?? null,
            'creative_id' => isset($ad['creative']['id']) ? $ad['creative']['id'] : null,
            'creative_data' => !empty($ad['creative']) ? json_encode($ad['creative']) : null,
            'preview_shareable_link' => $ad['preview_shareable_link'] ?? null,
            'bid_amount' => isset($ad['bid_amount']) ? floatval($ad['bid_amount']) / 100 : 0,
            'bid_type' => $ad['bid_type'] ?? null,
            'bid_info' => !empty($ad['bid_info']) ? json_encode($ad['bid_info']) : null,
            'conversion_specs' => !empty($ad['conversion_specs']) ? json_encode($ad['conversion_specs']) : null,
            'tracking_specs' => !empty($ad['tracking_specs']) ? json_encode($ad['tracking_specs']) : null,
            'recommendations' => !empty($ad['recommendations']) ? json_encode($ad['recommendations']) : null,
            'source_ad_id' => $ad['source_ad_id'] ?? null,
            'adlabels' => !empty($ad['adlabels']) ? json_encode($ad['adlabels']) : null,
            'issues_info' => !empty($ad['issues_info']) ? json_encode($ad['issues_info']) : null,
            'engagement_audience' => $ad['engagement_audience'] ?? false,
            'last_updated_by_app_id' => $ad['last_updated_by_app_id'] ?? null,
            'created_time' => isset($ad['created_time']) ? date('Y-m-d H:i:s', strtotime($ad['created_time'])) : null,
            'updated_time' => isset($ad['updated_time']) ? date('Y-m-d H:i:s', strtotime($ad['updated_time'])) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
        
        return $processed;
    }
    
    /**
     * ✅ Salva Ad
     */
    private function saveAd($adData) {
        $campaign = $this->db->fetch("
            SELECT c.id FROM campaigns c
            JOIN adsets a ON a.campaign_id = c.id
            WHERE a.id = :adset_id
        ", ['adset_id' => $adData['adset_id']]);
        
        if ($campaign) {
            $adData['campaign_id'] = $campaign['id'];
        }
        
        $exists = $this->db->fetch("
            SELECT id FROM ads WHERE ad_id = :ad_id
        ", ['ad_id' => $adData['ad_id']]);
        
        $adData['user_id'] = $this->userId;
        
        if ($exists) {
            $updateData = $adData;
            unset($updateData['user_id']);
            $this->db->update('ads', $updateData, 'id = :id', ['id' => $exists['id']]);
            return $exists['id'];
        } else {
            return $this->db->insert('ads', $adData);
        }
    }
    
    /**
     * ✅ Insights de Ad
     */
    private function getAdInsights($adId, $accessToken, $options = []) {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$adId}/insights";
        
        $params = [
            'fields' => implode(',', array_keys(MetaAdsDataStructure::INSIGHTS_FIELDS)) . ',actions,action_values',
            'access_token' => $accessToken,
            'date_preset' => $options['date_preset'] ?? 'maximum'
        ];
        
        $response = $this->makeApiCall($url . '?' . http_build_query($params));
        
        return !empty($response['data'][0]) ? $response['data'][0] : [];
    }
    
    /**
     * ✅ Salva insights do Ad
     */
    private function saveAdInsights($adId, $insights) {
        if (empty($insights)) return;
        
        $ad = $this->db->fetch("
            SELECT id FROM ads WHERE ad_id = :ad_id
        ", ['ad_id' => $adId]);
        
        if (!$ad) return;
        
        $updateData = [
            'spent' => floatval($insights['spend'] ?? 0),
            'impressions' => intval($insights['impressions'] ?? 0),
            'clicks' => intval($insights['clicks'] ?? 0),
            'conversions' => intval($insights['purchase'] ?? 0),
            'reach' => intval($insights['reach'] ?? 0),
            'frequency' => floatval($insights['frequency'] ?? 0),
            'ctr' => floatval($insights['ctr'] ?? 0),
            'cpc' => floatval($insights['cpc'] ?? 0),
            'cpm' => floatval($insights['cpm'] ?? 0)
        ];
        
        $this->db->update('ads', $updateData, 'id = :id', ['id' => $ad['id']]);
    }
    
    /**
     * ========================================
     * ✅ DETALHES DO CREATIVE
     * ========================================
     */
    private function getCreativeDetails($creativeId, $accessToken) {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$creativeId}";
        
        $params = [
            'fields' => 'id,name,body,title,object_story_spec,thumbnail_url,image_url,video_id',
            'access_token' => $accessToken
        ];
        
        try {
            $response = $this->makeApiCall($url . '?' . http_build_query($params));
            return $response;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * ✅ Salva creative
     */
    private function saveCreative($adId, $creative) {
        if (empty($creative)) return;
        
        // Já salvo no campo creative_data da tabela ads
        // Pode implementar tabela separada se necessário
    }
    
    /**
     * ========================================
     * ✅ INSIGHTS DETALHADOS COM BREAKDOWNS
     * ========================================
     */
    private function syncDetailedInsights($account, $options = []) {
        $accountId = str_replace('act_', '', $account['account_id']);
        $accessToken = $account['access_token'];
        
        $breakdowns = [
            'age,gender',
            'country',
            'region',
            'dma',
            'impression_device',
            'platform_position',
            'publisher_platform',
            'device_platform',
            'product_id'
        ];
        
        foreach ($breakdowns as $breakdown) {
            $url = "https://graph.facebook.com/{$this->apiVersion}/act_{$accountId}/insights";
            
            $params = [
                'fields' => implode(',', array_keys(MetaAdsDataStructure::INSIGHTS_FIELDS)),
                'access_token' => $accessToken,
                'level' => 'account',
                'breakdowns' => $breakdown,
                'date_preset' => $options['date_preset'] ?? 'last_30d',
                'limit' => 500
            ];
            
            try {
                $response = $this->makeApiCall($url . '?' . http_build_query($params));
                
                if (!empty($response['data'])) {
                    foreach ($response['data'] as $insight) {
                        $this->saveBreakdownInsight($account['id'], $breakdown, $insight);
                    }
                }
            } catch (Exception $e) {
                error_log("Erro no breakdown {$breakdown}: " . $e->getMessage());
            }
            
            usleep($this->rateLimitDelay * 2);
        }
    }
    
    /**
     * ✅ Salva insight com breakdown
     */
    private function saveBreakdownInsight($accountId, $breakdown, $insight) {
        $data = [
            'account_id' => $accountId,
            'breakdown_type' => $breakdown,
            'breakdown_value' => json_encode($insight),
            'date_start' => $insight['date_start'] ?? date('Y-m-d'),
            'date_stop' => $insight['date_stop'] ?? date('Y-m-d'),
            'insights_data' => json_encode($insight)
        ];
        
        $this->db->insert('insights_breakdowns', $data);
    }
    
    /**
     * ========================================
     * ✅ CUSTOM AUDIENCES
     * ========================================
     */
    private function syncCustomAudiences($account) {
        $audiences = [];
        $accountId = str_replace('act_', '', $account['account_id']);
        $accessToken = $account['access_token'];
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/act_{$accountId}/customaudiences";
        
        $params = [
            'fields' => 'id,name,description,subtype,approximate_count,customer_file_source,delivery_status,operation_status,permission_for_actions,lookalike_spec,retention_days,rule,seed_audience',
            'access_token' => $accessToken,
            'limit' => 500
        ];
        
        try {
            $response = $this->makeApiCall($url . '?' . http_build_query($params));
            
            if (!empty($response['data'])) {
                foreach ($response['data'] as $audience) {
                    $this->saveCustomAudience($account['id'], $audience);
                    $audiences[] = $audience;
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao sincronizar audiências: " . $e->getMessage());
        }
        
        return $audiences;
    }
    
    /**
     * ✅ Salva audiência customizada
     */
    private function saveCustomAudience($accountId, $audience) {
        $data = [
            'account_id' => $accountId,
            'audience_id' => $audience['id'],
            'name' => $audience['name'] ?? null,
            'description' => $audience['description'] ?? null,
            'subtype' => $audience['subtype'] ?? null,
            'approximate_count' => intval($audience['approximate_count'] ?? 0),
            'customer_file_source' => $audience['customer_file_source'] ?? null,
            'delivery_status' => !empty($audience['delivery_status']) ? json_encode($audience['delivery_status']) : null,
            'operation_status' => !empty($audience['operation_status']) ? json_encode($audience['operation_status']) : null,
            'permission_for_actions' => !empty($audience['permission_for_actions']) ? json_encode($audience['permission_for_actions']) : null,
            'lookalike_spec' => !empty($audience['lookalike_spec']) ? json_encode($audience['lookalike_spec']) : null,
            'retention_days' => intval($audience['retention_days'] ?? 0),
            'rule' => !empty($audience['rule']) ? json_encode($audience['rule']) : null,
            'seed_audience' => $audience['seed_audience'] ?? null
        ];
        
        $exists = $this->db->fetch("
            SELECT id FROM custom_audiences 
            WHERE audience_id = :audience_id AND account_id = :account_id
        ", [
            'audience_id' => $audience['id'],
            'account_id' => $accountId
        ]);
        
        if ($exists) {
            $this->db->update('custom_audiences', $data, 'id = :id', ['id' => $exists['id']]);
        } else {
            $this->db->insert('custom_audiences', $data);
        }
    }
    
    /**
     * ========================================
     * ✅ PIXELS
     * ========================================
     */
    private function syncPixels($account) {
        $pixels = [];
        $accountId = str_replace('act_', '', $account['account_id']);
        $accessToken = $account['access_token'];
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/act_{$accountId}/adspixels";
        
        $params = [
            'fields' => 'id,name,code,last_fired_time,is_created_by_business,is_unavailable,owner_ad_account,owner_business',
            'access_token' => $accessToken
        ];
        
        try {
            $response = $this->makeApiCall($url . '?' . http_build_query($params));
            
            if (!empty($response['data'])) {
                foreach ($response['data'] as $pixel) {
                    $this->savePixel($account['id'], $pixel);
                    $pixels[] = $pixel;
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao sincronizar pixels: " . $e->getMessage());
        }
        
        return $pixels;
    }
    
    /**
     * ✅ Salva pixel
     */
    private function savePixel($accountId, $pixel) {
        $data = [
            'account_id' => $accountId,
            'pixel_id' => $pixel['id'],
            'name' => $pixel['name'] ?? null,
            'code' => $pixel['code'] ?? null,
            'last_fired_time' => isset($pixel['last_fired_time']) ? date('Y-m-d H:i:s', strtotime($pixel['last_fired_time'])) : null,
            'is_created_by_business' => $pixel['is_created_by_business'] ?? false,
            'is_unavailable' => $pixel['is_unavailable'] ?? false,
            'owner_ad_account' => $pixel['owner_ad_account'] ?? null,
            'owner_business' => !empty($pixel['owner_business']) ? json_encode($pixel['owner_business']) : null
        ];
        
        $exists = $this->db->fetch("
            SELECT id FROM pixels 
            WHERE pixel_id = :pixel_id AND account_id = :account_id
        ", [
            'pixel_id' => $pixel['id'],
            'account_id' => $accountId
        ]);
        
        if ($exists) {
            $this->db->update('pixels', $data, 'id = :id', ['id' => $exists['id']]);
        } else {
            $this->db->insert('pixels', $data);
        }
    }
    
    /**
     * ========================================
     * ✅ API CALL COM RETRY E LOGS DETALHADOS
     * ========================================
     */
    private function makeApiCall($url, $method = 'GET', $data = null) {
        $attempts = 0;
        $lastError = null;
        
        while ($attempts < $this->maxRetries) {
            $attempts++;
            
            // ✅ Log da tentativa
            if ($attempts > 1) {
                error_log("[METASYNC] 🔄 Tentativa {$attempts}/{$this->maxRetries}");
            }
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // ✅ Sucesso
            if ($httpCode === 200) {
                return json_decode($response, true);
            }
            
            // ✅ Rate limit ou servidor ocupado - retry
            if ($httpCode === 429 || $httpCode === 503) {
                $waitTime = $this->retryDelay * $attempts;
                error_log("[METASYNC] ⏳ Rate limit (HTTP {$httpCode}), aguardando " . ($waitTime/1000000) . "s");
                usleep($waitTime);
                continue;
            }
            
            // ✅ Erro da API
            if ($httpCode >= 400) {
                $error = json_decode($response, true);
                $lastError = $error['error']['message'] ?? "HTTP Error $httpCode";
                
                // ✅ Log detalhado do erro
                error_log("[METASYNC] ❌ Erro API (HTTP {$httpCode}): {$lastError}");
                
                // Se for erro de permissão ou não encontrado, não faz retry
                if ($httpCode === 403 || $httpCode === 404) {
                    throw new Exception($lastError);
                }
            }
            
            // ✅ Erro de conexão
            if (!empty($curlError)) {
                $lastError = "cURL Error: {$curlError}";
                error_log("[METASYNC] ❌ {$lastError}");
            }
            
            usleep($this->retryDelay);
        }
        
        // ✅ Falhou após todas as tentativas
        $finalError = $lastError ?? 'Max retries reached';
        error_log("[METASYNC] ❌ Falha após {$this->maxRetries} tentativas: {$finalError}");
        throw new Exception($finalError);
    }
}