<?php
/**
 * ========================================
 * CAMINHO: /utmtrack/ajax_sync.php
 * ========================================
 * 
 * Versão FINAL V2 - Sincroniza TUDO com Meta Ads
 * ✅ Nome da campanha
 * ✅ Status (toggle)
 * ✅ Orçamento (CORRIGIDO)
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
        
        // 🔥 SINCRONIZA COM META ADS (se for campaign_name)
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
    // 🔥 ATUALIZAR ORÇAMENTO - CORRIGIDO
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
        
        // 🔥 Tenta atualizar no Meta (com melhor tratamento de erro)
        $metaUpdated = false;
        $metaError = '';
        
        if ($metaCampaignId && $campaign['access_token']) {
            try {
                // Converte para centavos (Meta Ads trabalha em centavos)
                $budgetInCents = intval($newBudget * 100);
                
                $result = updateMetaBudget(
                    $metaCampaignId, 
                    $budgetInCents, 
                    $campaign['access_token']
                );
                
                $metaUpdated = $result['success'];
                $metaError = $result['error'] ?? '';
                
                // Log detalhado
                error_log("Update Budget - Campaign: {$metaCampaignId}, Budget: {$budgetInCents}, Success: " . ($metaUpdated ? 'YES' : 'NO') . ", Error: {$metaError}");
                
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
            'meta_updated' => $metaUpdated,
            'debug_info' => [
                'campaign_id' => $campaignId,
                'meta_campaign_id' => $metaCampaignId,
                'budget_local' => $newBudget,
                'budget_cents' => intval($newBudget * 100),
                'has_token' => !empty($campaign['access_token']),
                'meta_error' => $metaError
            ]
        ];
    }
    
    // ==========================================
    // 🔥 SINCRONIZAR - VERSÃO COMPLETA E MELHORADA
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
        $errors = [];
        
        foreach ($accounts as $account) {
            $accountId = str_replace('act_', '', $account['account_id']);
            $accessToken = $account['access_token'];
            $accountName = $account['account_name'] ?? 'Sem nome';
            
            // 🔥 Busca campanhas com TODOS os campos necessários
            $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
                'fields' => 'id,name,status,effective_status,objective,daily_budget,lifetime_budget,created_time,updated_time',
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
            
            if ($httpCode !== 200) {
                $errors[] = "Erro ao buscar campanhas da conta {$accountName}: HTTP {$httpCode}";
                continue;
            }
            
            $data = json_decode($apiResponse, true);
            $campaigns = $data['data'] ?? [];
            
            foreach ($campaigns as $campaign) {
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
                    
                    $ch = curl_init($insightsUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    
                    $insightsResponse = curl_exec($ch);
                    curl_close($ch);
                    
                    $insightsData = json_decode($insightsResponse, true);
                    $insights = $insightsData['data'][0] ?? [];
                    
                    // 🔥 Determina o orçamento (daily ou lifetime)
                    $budget = 0;
                    if (isset($campaign['daily_budget'])) {
                        $budget = floatval($campaign['daily_budget']) / 100;
                    } elseif (isset($campaign['lifetime_budget'])) {
                        $budget = floatval($campaign['lifetime_budget']) / 100;
                    }
                    
                    // 🔥 Prepara dados COMPLETOS
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
                        'account_name' => $accountName, // 🔥 NOVO: Nome da conta
                        'last_sync' => date('Y-m-d H:i:s')
                    ];
                    
                    // 🔥 Processa TODAS as ações (não só purchase)
                    if (isset($insights['actions'])) {
                        foreach ($insights['actions'] as $action) {
                            $type = $action['action_type'] ?? '';
                            $value = intval($action['value'] ?? 0);
                            
                            // Conversões (compras)
                            if (in_array($type, ['purchase', 'omni_purchase'])) {
                                $campaignData['conversions'] += $value;
                            } 
                            // Initiate Checkout
                            elseif (in_array($type, ['initiate_checkout', 'omni_initiated_checkout'])) {
                                $campaignData['initiate_checkout'] = $value;
                            }
                            // 🔥 NOVO: Outros tipos de conversão (leads, cadastros, etc)
                            elseif (in_array($type, ['lead', 'complete_registration', 'subscribe'])) {
                                // Você pode adicionar um campo para esses tipos se necessário
                                // Por enquanto, somamos nas conversões gerais
                                $campaignData['conversions'] += $value;
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
                        $totalUpdated++;
                    } else {
                        $campaignData['user_id'] = $userId;
                        $campaignData['ad_account_id'] = $account['id'];
                        $campaignData['campaign_id'] = $campaign['id'];
                        $db->insert('campaigns', $campaignData);
                        $totalImported++;
                    }
                    
                    // Delay para não sobrecarregar a API
                    usleep(200000); // 200ms
                    
                } catch (Exception $e) {
                    $errors[] = "Erro ao processar campanha {$campaign['name']}: " . $e->getMessage();
                    continue;
                }
            }
            
            // Atualiza última sincronização da conta
            $db->update('ad_accounts',
                ['last_sync' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $account['id']]
            );
        }
        
        $message = "✅ {$totalImported} nova(s), {$totalUpdated} atualizada(s)";
        
        // Adiciona avisos se houve erros
        if (!empty($errors)) {
            $message .= " | ⚠️ " . count($errors) . " erro(s)";
        }
        
        $response = [
            'success' => true,
            'message' => $message,
            'imported' => $totalImported,
            'updated' => $totalUpdated,
            'errors' => $errors
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
// FUNÇÕES AUXILIARES - META ADS API
// ==========================================

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
    
    error_log("Meta API Error (updateMetaName): HTTP {$httpCode} - {$response}");
    return false;
}

/**
 * 🔥 Atualiza status da campanha no Meta Ads - COM MELHOR RETORNO
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
    
    error_log("Meta API Error (updateMetaStatus): {$errorMsg}");
    
    return [
        'success' => false,
        'error' => $errorMsg
    ];
}

/**
 * 🔥 Atualiza orçamento da campanha no Meta Ads - CORRIGIDO
 */
function updateMetaBudget($campaignId, $budgetInCents, $accessToken) {
    $url = "https://graph.facebook.com/v18.0/{$campaignId}";
    
    // 🔥 IMPORTANTE: Meta Ads trabalha com centavos
    $postData = http_build_query([
        'daily_budget' => $budgetInCents, // Já vem em centavos
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
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log detalhado
    error_log("Meta Budget Update - URL: {$url}, Budget: {$budgetInCents}, HTTP: {$httpCode}, Response: {$response}");
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        $success = isset($result['success']) && $result['success'] === true;
        
        return [
            'success' => $success,
            'error' => $success ? '' : 'Resposta inválida do Meta: ' . $response
        ];
    }
    
    // Extrai mensagem de erro do Meta
    $errorData = json_decode($response, true);
    $errorMsg = $errorData['error']['message'] ?? "HTTP {$httpCode}";
    
    if ($curlError) {
        $errorMsg .= " (CURL: {$curlError})";
    }
    
    error_log("Meta API Error (updateMetaBudget): {$errorMsg}");
    
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