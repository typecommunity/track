<?php
/**
 * ========================================
 * SINCRONIZADOR COMPLETO META ADS V2.0
 * ========================================
 * Sincronização total com todos os dados do Meta Ads
 */

require_once 'MetaAdsDataStructure.php';

class MetaAdsSync {
    
    private $db;
    private $userId;
    private $accounts = [];
    private $batchSize = 50;
    private $apiVersion = 'v18.0';
    private $rateLimitDelay = 200000; // microseconds
    private $maxRetries = 3;
    private $retryDelay = 1000000; // microseconds
    
    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
        $this->loadAccounts();
    }
    
    /**
     * Carrega contas ativas do usuário
     */
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
     * Sincronização completa - Campanhas, AdSets, Ads e todas as métricas
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
        
        foreach ($this->accounts as $account) {
            try {
                // 1. Sincroniza estrutura básica
                $campaigns = $this->syncCampaigns($account, $options);
                $results['campaigns']['synced'] += count($campaigns);
                
                // 2. Sincroniza AdSets para cada campanha
                foreach ($campaigns as $campaign) {
                    $adsets = $this->syncAdSets($account, $campaign['id'], $options);
                    $results['adsets']['synced'] += count($adsets);
                    
                    // 3. Sincroniza Ads para cada AdSet
                    foreach ($adsets as $adset) {
                        $ads = $this->syncAds($account, $adset['id'], $options);
                        $results['ads']['synced'] += count($ads);
                    }
                }
                
                // 4. Sincroniza insights detalhados
                $this->syncDetailedInsights($account, $options);
                $results['insights']['synced']++;
                
                // 5. Sincroniza audiências personalizadas
                $audiences = $this->syncCustomAudiences($account);
                $results['custom_audiences']['synced'] += count($audiences);
                
                // 6. Sincroniza pixels
                $pixels = $this->syncPixels($account);
                $results['pixels']['synced'] += count($pixels);
                
            } catch (Exception $e) {
                $results['campaigns']['errors'][] = [
                    'account' => $account['account_name'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $results['duration'] = round(microtime(true) - $startTime, 2);
        
        return $results;
    }
    
    /**
     * Sincroniza campanhas com TODOS os campos disponíveis
     */
    private function syncCampaigns($account, $options = []) {
        $campaigns = [];
        $accountId = str_replace('act_', '', $account['account_id']);
        $accessToken = $account['access_token'];
        
        // Campos completos da campanha
        $campaignFields = implode(',', array_keys(MetaAdsDataStructure::CAMPAIGN_FIELDS));
        
        // URL com paginação
        $url = "https://graph.facebook.com/{$this->apiVersion}/act_{$accountId}/campaigns";
        
        $params = [
            'fields' => $campaignFields,
            'access_token' => $accessToken,
            'limit' => $this->batchSize
        ];
        
        // Adiciona filtros se especificados
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
                    // Processa e salva campanha
                    $processedCampaign = $this->processCampaignData($campaign, $account['id']);
                    $this->saveCampaign($processedCampaign);
                    $campaigns[] = $processedCampaign;
                    
                    // Busca insights detalhados
                    $insights = $this->getCampaignInsights($campaign['id'], $accessToken, $options);
                    $this->saveCampaignInsights($campaign['id'], $insights);
                }
            }
            
            // Verifica próxima página
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
     * Busca insights completos da campanha
     */
    private function getCampaignInsights($campaignId, $accessToken, $options = []) {
        // Todos os campos de insights disponíveis
        $insightFields = implode(',', array_keys(MetaAdsDataStructure::INSIGHTS_FIELDS));
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$campaignId}/insights";
        
        $params = [
            'fields' => $insightFields . ',actions,action_values,video_play_actions,website_purchase_roas,catalog_segment_value,catalog_segment_actions',
            'access_token' => $accessToken,
            'level' => 'campaign',
            'use_unified_attribution_setting' => 'true'
        ];
        
        // Define período
        if (!empty($options['date_preset'])) {
            $params['date_preset'] = $options['date_preset'];
        } elseif (!empty($options['time_range'])) {
            $params['time_range'] = json_encode($options['time_range']);
        } else {
            $params['date_preset'] = 'maximum';
        }
        
        // Adiciona breakdowns se solicitado
        if (!empty($options['breakdowns'])) {
            $params['breakdowns'] = implode(',', $options['breakdowns']);
        }
        
        $response = $this->makeApiCall($url . '?' . http_build_query($params));
        
        return !empty($response['data'][0]) ? $response['data'][0] : [];
    }
    
    /**
     * Sincroniza AdSets com campos completos
     */
    private function syncAdSets($account, $campaignId, $options = []) {
        $adsets = [];
        $accessToken = $account['access_token'];
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$campaignId}/adsets";
        
        $params = [
            'fields' => 'id,name,status,effective_status,campaign_id,account_id,created_time,updated_time,
                        daily_budget,lifetime_budget,budget_remaining,billing_event,optimization_goal,
                        bid_amount,bid_strategy,bid_constraints,pacing_type,promoted_object,
                        targeting,start_time,end_time,attribution_spec,destination_type,
                        multi_optimization_goal_weight,optimization_sub_event,
                        recurring_budget_semantics,rf_prediction_id,time_based_ad_rotation_id_blocks',
            'access_token' => $accessToken,
            'limit' => $this->batchSize
        ];
        
        $response = $this->makeApiCall($url . '?' . http_build_query($params));
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $adset) {
                // Processa e salva AdSet
                $processedAdSet = $this->processAdSetData($adset, $campaignId);
                $this->saveAdSet($processedAdSet);
                $adsets[] = $processedAdSet;
                
                // Busca insights do AdSet
                $insights = $this->getAdSetInsights($adset['id'], $accessToken, $options);
                $this->saveAdSetInsights($adset['id'], $insights);
            }
        }
        
        return $adsets;
    }
    
    /**
     * Sincroniza Ads com campos completos
     */
    private function syncAds($account, $adsetId, $options = []) {
        $ads = [];
        $accessToken = $account['access_token'];
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$adsetId}/ads";
        
        $params = [
            'fields' => 'id,name,status,effective_status,campaign_id,adset_id,account_id,
                        created_time,updated_time,creative,bid_amount,bid_type,bid_info,
                        conversion_specs,tracking_specs,recommendations,source_ad_id,
                        preview_shareable_link,adlabels,engagement_audience,
                        last_updated_by_app_id',
            'access_token' => $accessToken,
            'limit' => $this->batchSize
        ];
        
        $response = $this->makeApiCall($url . '?' . http_build_query($params));
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $ad) {
                // Processa e salva Ad
                $processedAd = $this->processAdData($ad, $adsetId);
                $this->saveAd($processedAd);
                $ads[] = $processedAd;
                
                // Busca insights do Ad
                $insights = $this->getAdInsights($ad['id'], $accessToken, $options);
                $this->saveAdInsights($ad['id'], $insights);
                
                // Busca creative details se disponível
                if (!empty($ad['creative']['id'])) {
                    $creative = $this->getCreativeDetails($ad['creative']['id'], $accessToken);
                    $this->saveCreative($ad['id'], $creative);
                }
            }
        }
        
        return $ads;
    }
    
    /**
     * Busca insights de AdSet
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
     * Busca insights de Ad
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
     * Busca detalhes do creative
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
     * Sincroniza insights detalhados com breakdowns
     */
    private function syncDetailedInsights($account, $options = []) {
        $accountId = str_replace('act_', '', $account['account_id']);
        $accessToken = $account['access_token'];
        
        // Breakdowns disponíveis
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
                // Log erro mas continua com outros breakdowns
                error_log("Erro no breakdown {$breakdown}: " . $e->getMessage());
            }
            
            usleep($this->rateLimitDelay * 2); // Delay maior para breakdowns
        }
    }
    
    /**
     * Sincroniza audiências personalizadas
     */
    private function syncCustomAudiences($account) {
        $audiences = [];
        $accountId = str_replace('act_', '', $account['account_id']);
        $accessToken = $account['access_token'];
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/act_{$accountId}/customaudiences";
        
        $params = [
            'fields' => 'id,name,description,subtype,approximate_count,customer_file_source,
                        delivery_status,operation_status,permission_for_actions,
                        lookalike_spec,retention_days,rule,seed_audience',
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
     * Sincroniza pixels
     */
    private function syncPixels($account) {
        $pixels = [];
        $accountId = str_replace('act_', '', $account['account_id']);
        $accessToken = $account['access_token'];
        
        $url = "https://graph.facebook.com/{$this->apiVersion}/act_{$accountId}/adspixels";
        
        $params = [
            'fields' => 'id,name,code,last_fired_time,is_created_by_business,is_unavailable,
                        owner_ad_account,owner_business',
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
     * Processa dados da campanha
     */
    private function processCampaignData($campaign, $accountId) {
        $processed = [
            'ad_account_id' => $accountId,
            'campaign_id' => $campaign['id'],
            'campaign_name' => $campaign['name'] ?? 'Sem nome',
            'status' => strtolower($campaign['status'] ?? 'paused'),
            'effective_status' => $campaign['effective_status'] ?? null,
            'configured_status' => $campaign['configured_status'] ?? null,
            'objective' => $campaign['objective'] ?? null,
            'buying_type' => $campaign['buying_type'] ?? null,
            'can_use_spend_cap' => $campaign['can_use_spend_cap'] ?? false,
            'daily_budget' => isset($campaign['daily_budget']) ? floatval($campaign['daily_budget']) / 100 : 0,
            'lifetime_budget' => isset($campaign['lifetime_budget']) ? floatval($campaign['lifetime_budget']) / 100 : 0,
            'spend_cap' => isset($campaign['spend_cap']) ? floatval($campaign['spend_cap']) / 100 : 0,
            'budget_remaining' => isset($campaign['budget_remaining']) ? floatval($campaign['budget_remaining']) / 100 : 0,
            'bid_strategy' => $campaign['bid_strategy'] ?? null,
            'pacing_type' => !empty($campaign['pacing_type']) ? json_encode($campaign['pacing_type']) : null,
            'promoted_object' => !empty($campaign['promoted_object']) ? json_encode($campaign['promoted_object']) : null,
            'special_ad_categories' => !empty($campaign['special_ad_categories']) ? json_encode($campaign['special_ad_categories']) : null,
            'special_ad_category' => $campaign['special_ad_category'] ?? null,
            'special_ad_category_country' => !empty($campaign['special_ad_category_country']) ? json_encode($campaign['special_ad_category_country']) : null,
            'is_skadnetwork_attribution' => $campaign['is_skadnetwork_attribution'] ?? false,
            'smart_promotion_type' => $campaign['smart_promotion_type'] ?? null,
            'source_campaign_id' => $campaign['source_campaign_id'] ?? null,
            'start_time' => isset($campaign['start_time']) ? date('Y-m-d H:i:s', strtotime($campaign['start_time'])) : null,
            'stop_time' => isset($campaign['stop_time']) ? date('Y-m-d H:i:s', strtotime($campaign['stop_time'])) : null,
            'created_time' => isset($campaign['created_time']) ? date('Y-m-d H:i:s', strtotime($campaign['created_time'])) : null,
            'updated_time' => isset($campaign['updated_time']) ? date('Y-m-d H:i:s', strtotime($campaign['updated_time'])) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
        
        return $processed;
    }
    
    /**
     * Processa dados do AdSet
     */
    private function processAdSetData($adset, $campaignId) {
        $processed = [
            'campaign_id' => $campaignId,
            'adset_id' => $adset['id'],
            'adset_name' => $adset['name'] ?? 'Sem nome',
            'status' => strtolower($adset['status'] ?? 'paused'),
            'effective_status' => $adset['effective_status'] ?? null,
            'optimization_goal' => $adset['optimization_goal'] ?? null,
            'billing_event' => $adset['billing_event'] ?? null,
            'bid_amount' => isset($adset['bid_amount']) ? floatval($adset['bid_amount']) / 100 : 0,
            'bid_strategy' => $adset['bid_strategy'] ?? null,
            'bid_constraints' => !empty($adset['bid_constraints']) ? json_encode($adset['bid_constraints']) : null,
            'daily_budget' => isset($adset['daily_budget']) ? floatval($adset['daily_budget']) / 100 : 0,
            'lifetime_budget' => isset($adset['lifetime_budget']) ? floatval($adset['lifetime_budget']) / 100 : 0,
            'budget_remaining' => isset($adset['budget_remaining']) ? floatval($adset['budget_remaining']) / 100 : 0,
            'targeting' => !empty($adset['targeting']) ? json_encode($adset['targeting']) : null,
            'promoted_object' => !empty($adset['promoted_object']) ? json_encode($adset['promoted_object']) : null,
            'attribution_spec' => !empty($adset['attribution_spec']) ? json_encode($adset['attribution_spec']) : null,
            'destination_type' => $adset['destination_type'] ?? null,
            'multi_optimization_goal_weight' => $adset['multi_optimization_goal_weight'] ?? null,
            'optimization_sub_event' => $adset['optimization_sub_event'] ?? null,
            'pacing_type' => !empty($adset['pacing_type']) ? json_encode($adset['pacing_type']) : null,
            'recurring_budget_semantics' => $adset['recurring_budget_semantics'] ?? false,
            'rf_prediction_id' => $adset['rf_prediction_id'] ?? null,
            'time_based_ad_rotation_id_blocks' => !empty($adset['time_based_ad_rotation_id_blocks']) ? json_encode($adset['time_based_ad_rotation_id_blocks']) : null,
            'start_time' => isset($adset['start_time']) ? date('Y-m-d H:i:s', strtotime($adset['start_time'])) : null,
            'end_time' => isset($adset['end_time']) ? date('Y-m-d H:i:s', strtotime($adset['end_time'])) : null,
            'created_time' => isset($adset['created_time']) ? date('Y-m-d H:i:s', strtotime($adset['created_time'])) : null,
            'updated_time' => isset($adset['updated_time']) ? date('Y-m-d H:i:s', strtotime($adset['updated_time'])) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
        
        return $processed;
    }
    
    /**
     * Processa dados do Ad
     */
    private function processAdData($ad, $adsetId) {
        $processed = [
            'adset_id' => $adsetId,
            'ad_id' => $ad['id'],
            'ad_name' => $ad['name'] ?? 'Sem nome',
            'status' => strtolower($ad['status'] ?? 'paused'),
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
            'engagement_audience' => $ad['engagement_audience'] ?? false,
            'last_updated_by_app_id' => $ad['last_updated_by_app_id'] ?? null,
            'created_time' => isset($ad['created_time']) ? date('Y-m-d H:i:s', strtotime($ad['created_time'])) : null,
            'updated_time' => isset($ad['updated_time']) ? date('Y-m-d H:i:s', strtotime($ad['updated_time'])) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
        
        return $processed;
    }
    
    /**
     * Salva campanha no banco
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
            unset($campaignData['user_id']);
            $this->db->update('campaigns', $campaignData, 'id = :id', ['id' => $exists['id']]);
            return $exists['id'];
        } else {
            return $this->db->insert('campaigns', $campaignData);
        }
    }
    
    /**
     * Salva insights da campanha
     */
    private function saveCampaignInsights($campaignId, $insights) {
        if (empty($insights)) return;
        
        // Busca ID interno da campanha
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
        
        // Mapeia campos de insights
        foreach (MetaAdsDataStructure::INSIGHTS_FIELDS as $field => $config) {
            if (isset($insights[$field])) {
                $value = $insights[$field];
                
                // Converte valores baseado no tipo
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
                }
                
                $insightData[$field] = $value;
            }
        }
        
        // Processa actions
        if (!empty($insights['actions'])) {
            $actions = MetaAdsDataStructure::processActions($insights['actions']);
            foreach ($actions as $actionKey => $actionData) {
                if (isset($insightData[$actionKey])) {
                    $insightData[$actionKey] = $actionData['value'];
                }
            }
        }
        
        // Calcula métricas customizadas
        $customMetrics = MetaAdsDataStructure::calculateCustomMetrics($insightData);
        $insightData = array_merge($insightData, $customMetrics);
        
        // Verifica se já existe
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
     * Salva AdSet
     */
    private function saveAdSet($adsetData) {
        $exists = $this->db->fetch("
            SELECT id FROM adsets WHERE adset_id = :adset_id
        ", ['adset_id' => $adsetData['adset_id']]);
        
        $adsetData['user_id'] = $this->userId;
        
        if ($exists) {
            unset($adsetData['user_id']);
            $this->db->update('adsets', $adsetData, 'id = :id', ['id' => $exists['id']]);
            return $exists['id'];
        } else {
            return $this->db->insert('adsets', $adsetData);
        }
    }
    
    /**
     * Salva insights do AdSet
     */
    private function saveAdSetInsights($adsetId, $insights) {
        if (empty($insights)) return;
        
        $adset = $this->db->fetch("
            SELECT id FROM adsets WHERE adset_id = :adset_id
        ", ['adset_id' => $adsetId]);
        
        if (!$adset) return;
        
        // Atualiza métricas básicas no próprio adset
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
     * Salva Ad
     */
    private function saveAd($adData) {
        // Busca campaign_id interno
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
            unset($adData['user_id']);
            $this->db->update('ads', $adData, 'id = :id', ['id' => $exists['id']]);
            return $exists['id'];
        } else {
            return $this->db->insert('ads', $adData);
        }
    }
    
    /**
     * Salva insights do Ad
     */
    private function saveAdInsights($adId, $insights) {
        if (empty($insights)) return;
        
        $ad = $this->db->fetch("
            SELECT id FROM ads WHERE ad_id = :ad_id
        ", ['ad_id' => $adId]);
        
        if (!$ad) return;
        
        // Atualiza métricas básicas no próprio ad
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
     * Salva creative
     */
    private function saveCreative($adId, $creative) {
        if (empty($creative)) return;
        
        // Implementar salvamento de creative se necessário
        // Por enquanto, já salvamos no campo creative_data da tabela ads
    }
    
    /**
     * Salva insight com breakdown
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
     * Salva audiência customizada
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
     * Salva pixel
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
     * Faz chamada à API com retry
     */
    private function makeApiCall($url, $method = 'GET', $data = null) {
        $attempts = 0;
        $lastError = null;
        
        while ($attempts < $this->maxRetries) {
            $attempts++;
            
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
            curl_close($ch);
            
            if ($httpCode === 200) {
                return json_decode($response, true);
            }
            
            // Rate limit - aguarda e tenta novamente
            if ($httpCode === 429 || $httpCode === 503) {
                usleep($this->retryDelay * $attempts);
                continue;
            }
            
            // Erro definitivo
            if ($httpCode >= 400) {
                $error = json_decode($response, true);
                $lastError = $error['error']['message'] ?? "HTTP Error $httpCode";
                
                // Se for erro de permissão ou não encontrado, não faz retry
                if ($httpCode === 403 || $httpCode === 404) {
                    throw new Exception($lastError);
                }
            }
            
            usleep($this->retryDelay);
        }
        
        throw new Exception($lastError ?? 'Max retries reached');
    }
}