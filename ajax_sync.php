<?php
/**
 * ========================================
 * CAMINHO: /utmtrack/ajax_sync.php
 * ========================================
 * 
 * Versão 3.0 - Sincroniza TUDO com Meta Ads
 * ✅ Campanhas (nome, status, orçamento)
 * ✅ AdSets (NOVO)
 * ✅ Ads (NOVO)
 * ✅ Campo created_at (CORRIGIDO)
 * 
 * @version 3.0
 * @date 2025-10-14
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Cache-Control: no-cache');

$response = [
    'success' => false,
    'message' => 'Requisição inválida'
];

try {
    // Verifica autenticação
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Não autorizado - faça login novamente');
    }
    
    $userId = $_SESSION['user_id'];
    
    // Carrega Database
    $baseDir = __DIR__;
    require_once $baseDir . '/core/Database.php';
    
    $db = Database::getInstance();
    
    $action = $_GET['action'] ?? null;
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    // ==========================================
    // ATUALIZAR CAMPO (NOME, ETC) - COM SYNC META
    // ==========================================
    if ($action === 'update_field') {
        $campaignId = intval($requestData['campaign_id'] ?? 0);
        $field = $requestData['field'] ?? null;
        $value = $requestData['value'] ?? null;
        
        if (!$campaignId || !$field || $value === null) {
            throw new Exception('Parâmetros inválidos');
        }
        
        // Whitelist de campos
        $allowedFields = ['campaign_name', 'budget', 'status'];
        if (!in_array($field, $allowedFields)) {
            throw new Exception("Campo '{$field}' não permitido");
        }
        
        // Busca a campanha com o access_token
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
            throw new Exception('Campanha não encontrada ou sem permissão');
        }
        
        // Atualiza no banco local
        $db->update('campaigns',
            [$field => $value],
            'id = :id AND user_id = :user_id',
            ['id' => $campaignId, 'user_id' => $userId]
        );
        
        // Verifica se atualizou
        $updated = $db->fetch("
            SELECT {$field} FROM campaigns WHERE id = :id
        ", ['id' => $campaignId]);
        
        if ($updated[$field] != $value) {
            throw new Exception('Falha ao atualizar no banco local');
        }
        
        // 🔥 SINCRONIZA COM META ADS
        $metaUpdated = false;
        
        if ($field === 'campaign_name' && $campaign['campaign_id'] && $campaign['access_token']) {
            try {
                $metaUpdated = updateMetaName(
                    $campaign['campaign_id'], 
                    $value, 
                    $campaign['access_token']
                );
            } catch (Exception $e) {
                error_log("Erro ao atualizar nome no Meta: " . $e->getMessage());
            }
        }
        
        $response = [
            'success' => true,
            'message' => $metaUpdated 
                ? '✅ Nome atualizado no Meta Ads!' 
                : '✅ Atualizado localmente (Meta não sincronizado)',
            'meta_updated' => $metaUpdated
        ];
    }
    
    // ==========================================
    // ATUALIZAR STATUS
    // ==========================================
    elseif ($action === 'update_status') {
        $campaignId = intval($requestData['campaign_id'] ?? 0);
        $metaCampaignId = $requestData['meta_campaign_id'] ?? null;
        $newStatus = $requestData['status'] ?? null;
        
        if (!$campaignId || !$newStatus) {
            throw new Exception('Parâmetros inválidos');
        }
        
        $statusLower = strtolower($newStatus);
        
        // Verifica permissão
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
        
        // Atualiza no banco
        $db->update('campaigns',
            ['status' => $statusLower],
            'id = :id',
            ['id' => $campaignId]
        );
        
        // Tenta atualizar no Meta
        $metaUpdated = false;
        $metaError = '';
        
        if ($metaCampaignId && $campaign['access_token']) {
            try {
                $result = updateMetaStatus(
                    $metaCampaignId, 
                    $newStatus, 
                    $campaign['access_token']
                );
                
                $metaUpdated = $result['success'];
                $metaError = $result['error'] ?? '';
            } catch (Exception $e) {
                $metaError = $e->getMessage();
                error_log("Erro ao atualizar status no Meta: " . $metaError);
            }
        }
        
        $response = [
            'success' => true,
            'message' => $metaUpdated 
                ? '✅ Status atualizado no Meta Ads!' 
                : '✅ Status atualizado localmente' . ($metaError ? " (Erro Meta: {$metaError})" : ''),
            'meta_updated' => $metaUpdated,
            'new_status' => $statusLower
        ];
    }
    
    // ==========================================
    // ATUALIZAR ORÇAMENTO
    // ==========================================
    elseif ($action === 'update_budget') {
        $campaignId = intval($requestData['campaign_id'] ?? 0);
        $metaCampaignId = $requestData['meta_campaign_id'] ?? null;
        $newBudget = floatval($requestData['value'] ?? 0);
        
        if (!$campaignId || $newBudget < 0) {
            throw new Exception('Parâmetros inválidos');
        }
        
        // Verifica permissão
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
        
        // Atualiza no banco local PRIMEIRO
        $db->update('campaigns',
            ['budget' => $newBudget],
            'id = :id',
            ['id' => $campaignId]
        );
        
        // Tenta atualizar no Meta
        $metaUpdated = false;
        $metaError = '';
        
        if ($metaCampaignId && $campaign['access_token']) {
            try {
                // Converte para centavos
                $budgetInCents = intval($newBudget * 100);
                
                $result = updateMetaBudget(
                    $metaCampaignId, 
                    $budgetInCents, 
                    $campaign['access_token']
                );
                
                $metaUpdated = $result['success'];
                $metaError = $result['error'] ?? '';
                
            } catch (Exception $e) {
                $metaError = $e->getMessage();
                error_log("Erro ao atualizar orçamento no Meta: " . $metaError);
            }
        } else {
            $metaError = 'Token ou ID do Meta ausente';
        }
        
        $response = [
            'success' => true,
            'message' => $metaUpdated 
                ? '✅ Orçamento atualizado no Meta Ads!' 
                : '✅ Orçamento atualizado localmente' . ($metaError ? " (Erro Meta: {$metaError})" : ''),
            'meta_updated' => $metaUpdated
        ];
    }
    
    // ==========================================
    // 🔥 SINCRONIZAR - CAMPANHAS, ADSETS E ADS
    // ==========================================
    elseif ($action === 'sync_all') {
        $period = $requestData['period'] ?? 'maximum';
        $datePreset = $requestData['date_preset'] ?? 'maximum';
        $startDate = $requestData['start_date'] ?? null;
        $endDate = $requestData['end_date'] ?? null;
        $syncType = $requestData['sync_type'] ?? 'campaigns'; // campaigns, adsets, ads, all
        
        $accounts = $db->fetchAll("
            SELECT * FROM ad_accounts 
            WHERE user_id = :user_id 
            AND platform = 'meta' 
            AND status = 'active'
            AND access_token IS NOT NULL
        ", ['user_id' => $userId]);
        
        if (empty($accounts)) {
            throw new Exception('Nenhuma conta Meta Ads ativa encontrada');
        }
        
        $stats = [
            'campaigns' => ['imported' => 0, 'updated' => 0],
            'adsets' => ['imported' => 0, 'updated' => 0],
            'ads' => ['imported' => 0, 'updated' => 0],
            'errors' => []
        ];
        
        foreach ($accounts as $account) {
            // SYNC CAMPANHAS
            if (in_array($syncType, ['campaigns', 'all'])) {
                $result = syncCampaigns($account, $userId, $db, $period, $datePreset, $startDate, $endDate);
                $stats['campaigns']['imported'] += $result['imported'];
                $stats['campaigns']['updated'] += $result['updated'];
                $stats['errors'] = array_merge($stats['errors'], $result['errors']);
            }
            
            // SYNC ADSETS
            if (in_array($syncType, ['adsets', 'all'])) {
                $result = syncAdSets($account, $userId, $db);
                $stats['adsets']['imported'] += $result['imported'];
                $stats['adsets']['updated'] += $result['updated'];
                $stats['errors'] = array_merge($stats['errors'], $result['errors']);
            }
            
            // SYNC ADS
            if (in_array($syncType, ['ads', 'all'])) {
                $result = syncAds($account, $userId, $db);
                $stats['ads']['imported'] += $result['imported'];
                $stats['ads']['updated'] += $result['updated'];
                $stats['errors'] = array_merge($stats['errors'], $result['errors']);
            }
            
            // Atualiza última sincronização
            $db->update('ad_accounts',
                ['last_sync' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $account['id']]
            );
        }
        
        // Monta mensagem
        $messages = [];
        if ($stats['campaigns']['imported'] > 0 || $stats['campaigns']['updated'] > 0) {
            $messages[] = "📊 Campanhas: {$stats['campaigns']['imported']} novas, {$stats['campaigns']['updated']} atualizadas";
        }
        if ($stats['adsets']['imported'] > 0 || $stats['adsets']['updated'] > 0) {
            $messages[] = "🎯 AdSets: {$stats['adsets']['imported']} novos, {$stats['adsets']['updated']} atualizados";
        }
        if ($stats['ads']['imported'] > 0 || $stats['ads']['updated'] > 0) {
            $messages[] = "📱 Ads: {$stats['ads']['imported']} novos, {$stats['ads']['updated']} atualizados";
        }
        
        $message = !empty($messages) ? implode(' | ', $messages) : 'Nenhum dado sincronizado';
        
        if (!empty($stats['errors'])) {
            $message .= " | ⚠️ " . count($stats['errors']) . " erro(s)";
        }
        
        $response = [
            'success' => true,
            'message' => $message,
            'stats' => $stats
        ];
    }
    
    else {
        throw new Exception("Ação '{$action}' não reconhecida");
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

// ==========================================
// FUNÇÕES DE SINCRONIZAÇÃO
// ==========================================

/**
 * Sincroniza campanhas
 */
function syncCampaigns($account, $userId, $db, $period, $datePreset, $startDate, $endDate) {
    $imported = 0;
    $updated = 0;
    $errors = [];
    
    $accountId = str_replace('act_', '', $account['account_id']);
    $accessToken = $account['access_token'];
    
    try {
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
            'fields' => 'id,name,status,objective,daily_budget,lifetime_budget,created_time,updated_time',
            'access_token' => $accessToken,
            'limit' => 100
        ]);
        
        $campaigns = curlGetJson($url);
        
        foreach ($campaigns['data'] ?? [] as $campaign) {
            try {
                // Busca insights
                $insightsUrl = buildInsightsUrl(
                    $campaign['id'], 
                    $accessToken, 
                    $period, 
                    $datePreset,
                    $startDate, 
                    $endDate,
                    $campaign['created_time']
                );
                
                $insightsData = curlGetJson($insightsUrl, 15);
                $insights = $insightsData['data'][0] ?? [];
                
                // Determina orçamento
                $budget = 0;
                if (isset($campaign['daily_budget'])) {
                    $budget = floatval($campaign['daily_budget']) / 100;
                } elseif (isset($campaign['lifetime_budget'])) {
                    $budget = floatval($campaign['lifetime_budget']) / 100;
                }
                
                // Prepara dados
                $campaignData = [
                    'campaign_name' => $campaign['name'],
                    'status' => strtolower($campaign['status']) === 'active' ? 'active' : 'paused',
                    'objective' => $campaign['objective'] ?? null,
                    'budget' => $budget,
                    'spent' => floatval($insights['spend'] ?? 0),
                    'impressions' => intval($insights['impressions'] ?? 0),
                    'clicks' => intval($insights['clicks'] ?? 0),
                    'ctr' => floatval($insights['ctr'] ?? 0),
                    'cpc' => floatval($insights['cpc'] ?? 0),
                    'cpm' => floatval($insights['cpm'] ?? 0),
                    'conversions' => 0,
                    'initiate_checkout' => 0,
                    'created_at' => isset($campaign['created_time']) ? date('Y-m-d H:i:s', strtotime($campaign['created_time'])) : null,
                    'last_sync' => date('Y-m-d H:i:s')
                ];
                
                // Processa ações
                if (isset($insights['actions'])) {
                    foreach ($insights['actions'] as $action) {
                        $type = $action['action_type'] ?? '';
                        $value = intval($action['value'] ?? 0);
                        
                        if (in_array($type, ['purchase', 'omni_purchase'])) {
                            $campaignData['conversions'] += $value;
                        } elseif (in_array($type, ['initiate_checkout', 'omni_initiated_checkout'])) {
                            $campaignData['initiate_checkout'] = $value;
                        }
                    }
                }
                
                // Salva ou atualiza
                $exists = $db->fetch("
                    SELECT id FROM campaigns 
                    WHERE campaign_id = :campaign_id AND user_id = :user_id
                ", [
                    'campaign_id' => $campaign['id'],
                    'user_id' => $userId
                ]);
                
                if ($exists) {
                    $db->update('campaigns', $campaignData, 'id = :id', ['id' => $exists['id']]);
                    $updated++;
                } else {
                    $campaignData['user_id'] = $userId;
                    $campaignData['ad_account_id'] = $account['id'];
                    $campaignData['campaign_id'] = $campaign['id'];
                    $db->insert('campaigns', $campaignData);
                    $imported++;
                }
                
                usleep(200000);
                
            } catch (Exception $e) {
                $errors[] = "Erro campanha {$campaign['name']}: " . $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erro conta {$account['account_name']}: " . $e->getMessage();
    }
    
    return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
}

/**
 * 🔥 NOVO: Sincroniza AdSets
 */
function syncAdSets($account, $userId, $db) {
    $imported = 0;
    $updated = 0;
    $errors = [];
    
    $accountId = str_replace('act_', '', $account['account_id']);
    $accessToken = $account['access_token'];
    
    try {
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/adsets?' . http_build_query([
            'fields' => 'id,name,status,campaign_id,optimization_goal,billing_event,bid_amount,daily_budget,lifetime_budget,start_time,end_time,created_time',
            'access_token' => $accessToken,
            'limit' => 300
        ]);
        
        $adsets = curlGetJson($url);
        
        foreach ($adsets['data'] ?? [] as $adset) {
            try {
                // Busca campanha relacionada
                $campaign = $db->fetch("
                    SELECT id FROM campaigns 
                    WHERE campaign_id = :campaign_id AND user_id = :user_id
                ", [
                    'campaign_id' => $adset['campaign_id'],
                    'user_id' => $userId
                ]);
                
                if (!$campaign) continue;
                
                // Busca insights
                $since = date('Y-m-d', strtotime($adset['created_time'] ?? '-30 days'));
                $until = date('Y-m-d');
                
                $insightsUrl = 'https://graph.facebook.com/v18.0/' . $adset['id'] . '/insights?' . http_build_query([
                    'fields' => 'impressions,clicks,spend,reach,frequency,ctr,cpc,cpm,actions',
                    'access_token' => $accessToken,
                    'time_range' => json_encode(['since' => $since, 'until' => $until])
                ]);
                
                $insightsData = curlGetJson($insightsUrl, 15);
                $insights = $insightsData['data'][0] ?? [];
                
                // Prepara dados
                $statusMap = ['ACTIVE' => 'active', 'PAUSED' => 'paused', 'DELETED' => 'deleted', 'ARCHIVED' => 'deleted'];
                
                $adsetData = [
                    'adset_name' => $adset['name'] ?? 'Sem nome',
                    'status' => $statusMap[strtoupper($adset['status'] ?? 'PAUSED')] ?? 'paused',
                    'optimization_goal' => $adset['optimization_goal'] ?? null,
                    'billing_event' => $adset['billing_event'] ?? null,
                    'bid_amount' => isset($adset['bid_amount']) ? floatval($adset['bid_amount']) / 100 : 0,
                    'daily_budget' => isset($adset['daily_budget']) ? floatval($adset['daily_budget']) / 100 : 0,
                    'lifetime_budget' => isset($adset['lifetime_budget']) ? floatval($adset['lifetime_budget']) / 100 : 0,
                    'spent' => floatval($insights['spend'] ?? 0),
                    'impressions' => intval($insights['impressions'] ?? 0),
                    'clicks' => intval($insights['clicks'] ?? 0),
                    'reach' => intval($insights['reach'] ?? 0),
                    'frequency' => floatval($insights['frequency'] ?? 0),
                    'ctr' => floatval($insights['ctr'] ?? 0),
                    'cpc' => floatval($insights['cpc'] ?? 0),
                    'cpm' => floatval($insights['cpm'] ?? 0),
                    'conversions' => 0,
                    'start_time' => isset($adset['start_time']) ? date('Y-m-d H:i:s', strtotime($adset['start_time'])) : null,
                    'end_time' => isset($adset['end_time']) ? date('Y-m-d H:i:s', strtotime($adset['end_time'])) : null,
                    'created_at' => isset($adset['created_time']) ? date('Y-m-d H:i:s', strtotime($adset['created_time'])) : null,
                    'last_sync' => date('Y-m-d H:i:s')
                ];
                
                // Processa conversões
                if (isset($insights['actions'])) {
                    foreach ($insights['actions'] as $action) {
                        if (in_array($action['action_type'], ['purchase', 'omni_purchase'])) {
                            $adsetData['conversions'] += intval($action['value'] ?? 0);
                        }
                    }
                }
                
                // Salva ou atualiza
                $exists = $db->fetch("
                    SELECT id FROM adsets 
                    WHERE adset_id = :adset_id
                ", ['adset_id' => $adset['id']]);
                
                if ($exists) {
                    $db->update('adsets', $adsetData, 'id = :id', ['id' => $exists['id']]);
                    $updated++;
                } else {
                    $adsetData['user_id'] = $userId;
                    $adsetData['campaign_id'] = $campaign['id'];
                    $adsetData['adset_id'] = $adset['id'];
                    $db->insert('adsets', $adsetData);
                    $imported++;
                }
                
                usleep(200000);
                
            } catch (Exception $e) {
                $errors[] = "Erro adset {$adset['name']}: " . $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erro conta {$account['account_name']}: " . $e->getMessage();
    }
    
    return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
}

/**
 * 🔥 NOVO: Sincroniza Ads
 */
function syncAds($account, $userId, $db) {
    $imported = 0;
    $updated = 0;
    $errors = [];
    
    $accountId = str_replace('act_', '', $account['account_id']);
    $accessToken = $account['access_token'];
    
    try {
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/ads?' . http_build_query([
            'fields' => 'id,name,status,campaign_id,adset_id,creative,preview_shareable_link,created_time',
            'access_token' => $accessToken,
            'limit' => 300
        ]);
        
        $ads = curlGetJson($url);
        
        foreach ($ads['data'] ?? [] as $ad) {
            try {
                // Busca campanha
                $campaign = $db->fetch("
                    SELECT id FROM campaigns 
                    WHERE campaign_id = :campaign_id AND user_id = :user_id
                ", [
                    'campaign_id' => $ad['campaign_id'],
                    'user_id' => $userId
                ]);
                
                if (!$campaign) continue;
                
                // Busca adset
                $adset = null;
                if (!empty($ad['adset_id'])) {
                    $adset = $db->fetch("
                        SELECT id FROM adsets 
                        WHERE adset_id = :adset_id
                    ", ['adset_id' => $ad['adset_id']]);
                }
                
                // Busca insights
                $since = date('Y-m-d', strtotime($ad['created_time'] ?? '-30 days'));
                $until = date('Y-m-d');
                
                $insightsUrl = 'https://graph.facebook.com/v18.0/' . $ad['id'] . '/insights?' . http_build_query([
                    'fields' => 'impressions,clicks,spend,reach,frequency,ctr,cpc,cpm,actions',
                    'access_token' => $accessToken,
                    'time_range' => json_encode(['since' => $since, 'until' => $until])
                ]);
                
                $insightsData = curlGetJson($insightsUrl, 15);
                $insights = $insightsData['data'][0] ?? [];
                
                // Prepara dados
                $statusMap = ['ACTIVE' => 'active', 'PAUSED' => 'paused', 'DELETED' => 'deleted', 'ARCHIVED' => 'deleted'];
                
                $adData = [
                    'ad_name' => $ad['name'] ?? 'Sem nome',
                    'status' => $statusMap[strtoupper($ad['status'] ?? 'PAUSED')] ?? 'paused',
                    'creative_id' => $ad['creative']['id'] ?? null,
                    'preview_url' => $ad['preview_shareable_link'] ?? null,
                    'spent' => floatval($insights['spend'] ?? 0),
                    'impressions' => intval($insights['impressions'] ?? 0),
                    'clicks' => intval($insights['clicks'] ?? 0),
                    'reach' => intval($insights['reach'] ?? 0),
                    'frequency' => floatval($insights['frequency'] ?? 0),
                    'ctr' => floatval($insights['ctr'] ?? 0),
                    'cpc' => floatval($insights['cpc'] ?? 0),
                    'cpm' => floatval($insights['cpm'] ?? 0),
                    'conversions' => 0,
                    'created_at' => isset($ad['created_time']) ? date('Y-m-d H:i:s', strtotime($ad['created_time'])) : null,
                    'last_sync' => date('Y-m-d H:i:s')
                ];
                
                // Processa conversões
                if (isset($insights['actions'])) {
                    foreach ($insights['actions'] as $action) {
                        if (in_array($action['action_type'], ['purchase', 'omni_purchase'])) {
                            $adData['conversions'] += intval($action['value'] ?? 0);
                        }
                    }
                }
                
                // Salva ou atualiza
                $exists = $db->fetch("
                    SELECT id FROM ads 
                    WHERE ad_id = :ad_id
                ", ['ad_id' => $ad['id']]);
                
                if ($exists) {
                    $db->update('ads', $adData, 'id = :id', ['id' => $exists['id']]);
                    $updated++;
                } else {
                    $adData['user_id'] = $userId;
                    $adData['campaign_id'] = $campaign['id'];
                    $adData['adset_id'] = $adset ? $adset['id'] : null;
                    $adData['ad_id'] = $ad['id'];
                    $db->insert('ads', $adData);
                    $imported++;
                }
                
                usleep(200000);
                
            } catch (Exception $e) {
                $errors[] = "Erro ad {$ad['name']}: " . $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erro conta {$account['account_name']}: " . $e->getMessage();
    }
    
    return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
}

// ==========================================
// FUNÇÕES AUXILIARES - META ADS API
// ==========================================

/**
 * Helper cURL com JSON decode
 */
function curlGetJson($url, $timeout = 30) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP {$httpCode}: {$response}");
    }
    
    return json_decode($response, true);
}

/**
 * Atualiza nome da campanha no Meta Ads
 */
function updateMetaName($campaignId, $newName, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    $postData = http_build_query([
        'name' => $newName,
        'access_token' => $accessToken
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true;
    }
    
    return false;
}

/**
 * Atualiza status da campanha no Meta Ads
 */
function updateMetaStatus($campaignId, $status, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'status' => $status,
        'access_token' => $accessToken
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        $success = isset($result['success']) && $result['success'] === true;
        return [
            'success' => $success,
            'error' => $success ? '' : 'Resposta inválida do Meta'
        ];
    }
    
    $errorData = json_decode($response, true);
    $errorMsg = $errorData['error']['message'] ?? "HTTP {$httpCode}";
    
    return [
        'success' => false,
        'error' => $errorMsg
    ];
}

/**
 * Atualiza orçamento da campanha no Meta Ads
 */
function updateMetaBudget($campaignId, $budgetInCents, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    $postData = http_build_query([
        'daily_budget' => $budgetInCents,
        'access_token' => $accessToken
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        $success = isset($result['success']) && $result['success'] === true;
        
        return [
            'success' => $success,
            'error' => $success ? '' : 'Resposta inválida do Meta'
        ];
    }
    
    $errorData = json_decode($response, true);
    $errorMsg = $errorData['error']['message'] ?? "HTTP {$httpCode}";
    
    return [
        'success' => false,
        'error' => $errorMsg
    ];
}

/**
 * Constrói URL dos insights com período
 */
function buildInsightsUrl($campaignId, $accessToken, $period, $datePreset, $startDate, $endDate, $createdTime) {
    $baseUrl = 'https://graph.facebook.com/v18.0/' . $campaignId . '/insights';
    
    $params = [
        'fields' => 'impressions,clicks,spend,ctr,cpc,cpm,actions',
        'access_token' => $accessToken
    ];
    
    if ($period === 'custom' && $startDate && $endDate) {
        $params['time_range'] = json_encode([
            'since' => $startDate,
            'until' => $endDate
        ]);
    } elseif ($datePreset === 'maximum') {
        $since = date('Y-m-d', strtotime($createdTime));
        $until = date('Y-m-d');
        $params['time_range'] = json_encode([
            'since' => $since,
            'until' => $until
        ]);
    } else {
        $params['date_preset'] = $datePreset;
    }
    
    return $baseUrl . '?' . http_build_query($params);
}