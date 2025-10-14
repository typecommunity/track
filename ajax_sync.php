<?php
/**
 * ========================================
 * CAMINHO: /utmtrack/ajax_sync.php
 * ========================================
 * 
 * Versão CORRIGIDA - Funciona com o Database atual
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
    // ATUALIZAR CAMPO (NOME, ETC)
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
        
        // Verifica se a campanha existe e pertence ao usuário
        $campaign = $db->fetch("
            SELECT id FROM campaigns 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $campaignId,
            'user_id' => $userId
        ]);
        
        if (!$campaign) {
            throw new Exception('Campanha não encontrada ou sem permissão');
        }
        
        // Faz o update (ignora o retorno pois está bugado)
        $db->update('campaigns',
            [$field => $value],
            'id = :id AND user_id = :user_id',
            ['id' => $campaignId, 'user_id' => $userId]
        );
        
        // Verifica se atualizou consultando novamente
        $updated = $db->fetch("
            SELECT {$field} FROM campaigns WHERE id = :id
        ", ['id' => $campaignId]);
        
        if ($updated[$field] == $value) {
            $response = [
                'success' => true,
                'message' => 'Atualizado com sucesso',
                'meta_updated' => false
            ];
        } else {
            throw new Exception('Falha ao atualizar no banco');
        }
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
        if ($metaCampaignId && $campaign['access_token']) {
            try {
                $metaUpdated = updateMetaStatus(
                    $metaCampaignId, 
                    $newStatus, 
                    $campaign['access_token']
                );
            } catch (Exception $e) {
                // Meta falhou mas DB foi atualizado
            }
        }
        
        $response = [
            'success' => true,
            'message' => $metaUpdated ? 'Status atualizado no Meta Ads' : 'Status atualizado localmente',
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
        
        // Atualiza no banco
        $db->update('campaigns',
            ['budget' => $newBudget],
            'id = :id',
            ['id' => $campaignId]
        );
        
        // Tenta atualizar no Meta
        $metaUpdated = false;
        if ($metaCampaignId && $campaign['access_token'] && $newBudget > 0) {
            try {
                $metaUpdated = updateMetaBudget(
                    $metaCampaignId, 
                    $newBudget * 100, 
                    $campaign['access_token']
                );
            } catch (Exception $e) {
                // Meta falhou mas DB foi atualizado
            }
        }
        
        $response = [
            'success' => true,
            'message' => $metaUpdated ? 'Orçamento atualizado no Meta Ads' : 'Orçamento atualizado localmente',
            'meta_updated' => $metaUpdated
        ];
    }
    
    // ==========================================
    // SINCRONIZAR (importar da versão 3 que você me enviou)
    // ==========================================
    elseif ($action === 'sync_all') {
        $period = $requestData['period'] ?? 'maximum';
        $datePreset = $requestData['date_preset'] ?? 'maximum';
        $startDate = $requestData['start_date'] ?? null;
        $endDate = $requestData['end_date'] ?? null;
        
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
        
        $totalImported = 0;
        $totalUpdated = 0;
        
        foreach ($accounts as $account) {
            $accountId = str_replace('act_', '', $account['account_id']);
            $accessToken = $account['access_token'];
            
            // Busca campanhas
            $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
                'fields' => 'id,name,status,objective,daily_budget,created_time',
                'access_token' => $accessToken,
                'limit' => 100
            ]);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $apiResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) continue;
            
            $data = json_decode($apiResponse, true);
            $campaigns = $data['data'] ?? [];
            
            foreach ($campaigns as $campaign) {
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
                
                $ch = curl_init($insightsUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                
                $insightsResponse = curl_exec($ch);
                curl_close($ch);
                
                $insightsData = json_decode($insightsResponse, true);
                $insights = $insightsData['data'][0] ?? [];
                
                // Prepara dados
                $campaignData = [
                    'campaign_name' => $campaign['name'],
                    'status' => strtolower($campaign['status']) === 'active' ? 'active' : 'paused',
                    'objective' => $campaign['objective'] ?? null,
                    'budget' => isset($campaign['daily_budget']) ? floatval($campaign['daily_budget']) / 100 : 0,
                    'spent' => floatval($insights['spend'] ?? 0),
                    'impressions' => intval($insights['impressions'] ?? 0),
                    'clicks' => intval($insights['clicks'] ?? 0),
                    'ctr' => floatval($insights['ctr'] ?? 0),
                    'cpc' => floatval($insights['cpc'] ?? 0),
                    'cpm' => floatval($insights['cpm'] ?? 0),
                    'conversions' => 0,
                    'initiate_checkout' => 0,
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
                
                // Salva
                $exists = $db->fetch("
                    SELECT id FROM campaigns 
                    WHERE campaign_id = :campaign_id AND user_id = :user_id
                ", [
                    'campaign_id' => $campaign['id'],
                    'user_id' => $userId
                ]);
                
                if ($exists) {
                    $db->update('campaigns', $campaignData, 'id = :id', ['id' => $exists['id']]);
                    $totalUpdated++;
                } else {
                    $campaignData['user_id'] = $userId;
                    $campaignData['ad_account_id'] = $account['id'];
                    $campaignData['campaign_id'] = $campaign['id'];
                    $db->insert('campaigns', $campaignData);
                    $totalImported++;
                }
                
                usleep(200000);
            }
            
            $db->update('ad_accounts',
                ['last_sync' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $account['id']]
            );
        }
        
        $response = [
            'success' => true,
            'message' => "✅ {$totalImported} nova(s), {$totalUpdated} atualizada(s)",
            'imported' => $totalImported,
            'updated' => $totalUpdated
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
// FUNÇÕES AUXILIARES
// ==========================================

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
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

function updateMetaBudget($campaignId, $budget, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'daily_budget' => $budget,
        'access_token' => $accessToken
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}