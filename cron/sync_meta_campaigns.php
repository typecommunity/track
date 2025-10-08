<?php
/**
 * UTMTrack - Sincronizador Automático de Campanhas Meta Ads
 * 
 * CONFIGURAR NO CRON:
 * Executar a cada hora:
 * 0 * * * * /usr/bin/php /home/ataweb.com.br/public_html/utmtrack/app/cron/sync_meta_campaigns.php
 * 
 * Ou a cada 30 minutos:

 */

// Evita execução via browser
if (php_sapi_name() !== 'cli') {
    die('Este script só pode ser executado via linha de comando (CLI)');
}

// Carrega dependências
require_once dirname(__DIR__, 2) . '/core/Database.php';

// Função para logar
function log_sync($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] [{$level}] {$message}\n";
    
    // Salva no arquivo de log
    $logFile = dirname(__DIR__, 2) . '/logs/sync_meta.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, "[{$timestamp}] [{$level}] {$message}\n", FILE_APPEND);
}

try {
    log_sync('========== INICIANDO SINCRONIZAÇÃO META ADS ==========');
    
    $db = Database::getInstance();
    
    // Busca todas as contas ATIVAS com tokens válidos
    $accounts = $db->fetchAll("
        SELECT 
            aa.*,
            u.email as user_email,
            u.name as user_name
        FROM ad_accounts aa
        JOIN users u ON u.id = aa.user_id
        WHERE aa.platform = 'meta'
        AND aa.status = 'active'
        AND aa.access_token IS NOT NULL
        AND (aa.token_expires_at IS NULL OR aa.token_expires_at > NOW())
        ORDER BY aa.last_sync ASC
    ");
    
    if (empty($accounts)) {
        log_sync('Nenhuma conta ativa encontrada para sincronizar', 'INFO');
        exit(0);
    }
    
    log_sync('Encontradas ' . count($accounts) . ' conta(s) para sincronizar', 'INFO');
    
    $totalSynced = 0;
    $totalCampaigns = 0;
    $totalErrors = 0;
    
    foreach ($accounts as $account) {
        log_sync("Sincronizando conta: {$account['account_name']} (ID: {$account['account_id']})", 'INFO');
        
        try {
            // Busca campanhas da API do Meta
            $campaigns = fetchMetaCampaigns($account['account_id'], $account['access_token']);
            
            if (empty($campaigns)) {
                log_sync("Nenhuma campanha encontrada na conta {$account['account_name']}", 'WARN');
                continue;
            }
            
            log_sync("Encontradas " . count($campaigns) . " campanha(s)", 'INFO');
            
            $imported = 0;
            $updated = 0;
            
            foreach ($campaigns as $campaign) {
                // Verifica se campanha já existe
                $existing = $db->fetch("
                    SELECT id FROM campaigns 
                    WHERE user_id = :user_id 
                    AND campaign_id = :campaign_id
                ", [
                    'user_id' => $account['user_id'],
                    'campaign_id' => $campaign['id']
                ]);
                
                // Prepara dados
                $campaignData = [
                    'campaign_name' => $campaign['name'] ?? 'Sem nome',
                    'status' => mapCampaignStatus($campaign['status'] ?? 'ACTIVE'),
                    'objective' => $campaign['objective'] ?? null,
                    'budget' => isset($campaign['daily_budget']) ? ($campaign['daily_budget'] / 100) : 0,
                    'spent' => isset($campaign['spend']) ? floatval($campaign['spend']) : 0,
                    'impressions' => $campaign['impressions'] ?? 0,
                    'clicks' => $campaign['clicks'] ?? 0,
                    'conversions' => $campaign['actions'] ?? 0,
                    'last_sync' => date('Y-m-d H:i:s')
                ];
                
                if ($existing) {
                    // Atualiza
                    $db->update('campaigns',
                        $campaignData,
                        'id = :id',
                        ['id' => $existing['id']]
                    );
                    $updated++;
                } else {
                    // Insere nova
                    $db->insert('campaigns', array_merge($campaignData, [
                        'user_id' => $account['user_id'],
                        'ad_account_id' => $account['id'],
                        'campaign_id' => $campaign['id']
                    ]));
                    $imported++;
                }
            }
            
            log_sync("Conta {$account['account_name']}: {$imported} novas, {$updated} atualizadas", 'SUCCESS');
            
            // Atualiza last_sync da conta
            $db->update('ad_accounts',
                ['last_sync' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $account['id']]
            );
            
            $totalSynced++;
            $totalCampaigns += ($imported + $updated);
            
        } catch (Exception $e) {
            log_sync("Erro na conta {$account['account_name']}: " . $e->getMessage(), 'ERROR');
            $totalErrors++;
            
            // Marca conta com erro se token inválido
            if (strpos($e->getMessage(), 'token') !== false || strpos($e->getMessage(), 'expired') !== false) {
                $db->update('ad_accounts',
                    ['status' => 'error'],
                    'id = :id',
                    ['id' => $account['id']]
                );
            }
        }
        
        // Delay entre contas para evitar rate limit
        sleep(2);
    }
    
    log_sync('========== SINCRONIZAÇÃO CONCLUÍDA ==========', 'SUCCESS');
    log_sync("Contas sincronizadas: {$totalSynced}/{" . count($accounts) . "}", 'INFO');
    log_sync("Campanhas processadas: {$totalCampaigns}", 'INFO');
    log_sync("Erros: {$totalErrors}", $totalErrors > 0 ? 'WARN' : 'INFO');
    
    exit(0);
    
} catch (Exception $e) {
    log_sync('ERRO FATAL: ' . $e->getMessage(), 'ERROR');
    log_sync('Stack trace: ' . $e->getTraceAsString(), 'ERROR');
    exit(1);
}

/**
 * Busca campanhas do Meta Ads via API
 */
function fetchMetaCampaigns($accountId, $accessToken) {
    // Remove "act_" se existir
    $accountId = str_replace('act_', '', $accountId);
    
    // Campos que queremos buscar
    $fields = [
        'id',
        'name',
        'status',
        'objective',
        'daily_budget',
        'lifetime_budget',
        'spend',
        'impressions',
        'clicks',
        'actions',
        'created_time',
        'updated_time'
    ];
    
    // Monta URL da API
    $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
        'fields' => implode(',', $fields),
        'access_token' => $accessToken,
        'limit' => 100 // Máximo por página
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Apenas para desenvolvimento
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Erro na API Meta: HTTP {$httpCode} - Response: {$response}");
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['data'])) {
        throw new Exception('Resposta inválida da API Meta: ' . $response);
    }
    
    return $data['data'];
}

/**
 * Mapeia status do Meta para status do sistema
 */
function mapCampaignStatus($metaStatus) {
    $statusMap = [
        'ACTIVE' => 'active',
        'PAUSED' => 'paused',
        'DELETED' => 'deleted',
        'ARCHIVED' => 'deleted'
    ];
    
    return $statusMap[strtoupper($metaStatus)] ?? 'paused';
}