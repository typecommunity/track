<?php
/**
 * UTMTrack - Sincronizador COMPLETO Meta Ads
 * Sincroniza: Campanhas, Conjuntos (AdSets) e Anúncios (Ads)
 * 
 * Arquivo: cron/sync_meta_complete.php
 * 
 * CONFIGURAR NO CRON (executar a cada hora):
 * 0 * * * * /usr/bin/php /caminho/para/cron/sync_meta_complete.php
 */

// Evita execução via browser
if (php_sapi_name() !== 'cli') {
    die('Este script só pode ser executado via linha de comando (CLI)');
}

// Carrega dependências
require_once dirname(__DIR__) . '/core/Database.php';

// Função para logar
function log_sync($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] [{$level}] {$message}\n";
    
    $logFile = dirname(__DIR__) . '/logs/sync_meta.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, "[{$timestamp}] [{$level}] {$message}\n", FILE_APPEND);
}

try {
    log_sync('========== INICIANDO SINCRONIZAÇÃO COMPLETA META ADS ==========');
    
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
    
    $totals = [
        'accounts' => 0,
        'campaigns' => 0,
        'adsets' => 0,
        'ads' => 0,
        'errors' => 0
    ];
    
    foreach ($accounts as $account) {
        log_sync("====================================", 'INFO');
        log_sync("Conta: {$account['account_name']} (ID: {$account['account_id']})", 'INFO');
        
        try {
            // PASSO 1: Sincroniza Campanhas
            log_sync("  [1/3] Buscando campanhas...", 'INFO');
            $campaigns = fetchMetaCampaigns($account['account_id'], $account['access_token']);
            log_sync("  Encontradas " . count($campaigns) . " campanha(s)", 'INFO');
            
            foreach ($campaigns as $campaign) {
                try {
                    // Verifica se campanha já existe
                    $existingCampaign = $db->fetch("
                        SELECT id FROM campaigns 
                        WHERE campaign_id = :campaign_id AND ad_account_id = :account_id
                    ", [
                        'campaign_id' => $campaign['id'],
                        'account_id' => $account['id']
                    ]);
                    
                    $campaignData = [
                        'campaign_name' => $campaign['name'],
                        'status' => mapStatus($campaign['status']),
                        'objective' => $campaign['objective'] ?? null,
                        'budget' => isset($campaign['daily_budget']) ? $campaign['daily_budget'] / 100 : 0,
                        'spent' => isset($campaign['spend']) ? $campaign['spend'] / 100 : 0,
                        'impressions' => $campaign['impressions'] ?? 0,
                        'clicks' => $campaign['clicks'] ?? 0,
                        'conversions' => extractConversions($campaign),
                        'last_sync' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($existingCampaign) {
                        $db->update('campaigns', $campaignData, 'id = :id', ['id' => $existingCampaign['id']]);
                        $campaignDbId = $existingCampaign['id'];
                    } else {
                        $campaignData['user_id'] = $account['user_id'];
                        $campaignData['ad_account_id'] = $account['id'];
                        $campaignData['campaign_id'] = $campaign['id'];
                        $campaignDbId = $db->insert('campaigns', $campaignData);
                        $totals['campaigns']++;
                    }
                    
                    // PASSO 2: Sincroniza AdSets da campanha
                    log_sync("    → Buscando conjuntos da campanha {$campaign['name']}...", 'INFO');
                    $adsets = fetchMetaAdSets($campaign['id'], $account['access_token']);
                    log_sync("      Encontrados " . count($adsets) . " conjunto(s)", 'INFO');
                    
                    foreach ($adsets as $adset) {
                        try {
                            $existingAdSet = $db->fetch("
                                SELECT id FROM adsets 
                                WHERE adset_id = :adset_id AND campaign_id = :campaign_id
                            ", [
                                'adset_id' => $adset['id'],
                                'campaign_id' => $campaignDbId
                            ]);
                            
                            $adsetData = [
                                'adset_name' => $adset['name'],
                                'status' => mapStatus($adset['status']),
                                'optimization_goal' => $adset['optimization_goal'] ?? null,
                                'billing_event' => $adset['billing_event'] ?? null,
                                'daily_budget' => isset($adset['daily_budget']) ? $adset['daily_budget'] / 100 : 0,
                                'lifetime_budget' => isset($adset['lifetime_budget']) ? $adset['lifetime_budget'] / 100 : 0,
                                'spent' => isset($adset['spend']) ? $adset['spend'] / 100 : 0,
                                'impressions' => $adset['impressions'] ?? 0,
                                'clicks' => $adset['clicks'] ?? 0,
                                'conversions' => extractConversions($adset),
                                'last_sync' => date('Y-m-d H:i:s')
                            ];
                            
                            if ($existingAdSet) {
                                $db->update('adsets', $adsetData, 'id = :id', ['id' => $existingAdSet['id']]);
                                $adsetDbId = $existingAdSet['id'];
                            } else {
                                $adsetData['user_id'] = $account['user_id'];
                                $adsetData['campaign_id'] = $campaignDbId;
                                $adsetData['adset_id'] = $adset['id'];
                                $adsetDbId = $db->insert('adsets', $adsetData);
                                $totals['adsets']++;
                            }
                            
                            // PASSO 3: Sincroniza Ads do conjunto
                            log_sync("      ↳ Buscando anúncios do conjunto {$adset['name']}...", 'INFO');
                            $ads = fetchMetaAds($adset['id'], $account['access_token']);
                            log_sync("        Encontrados " . count($ads) . " anúncio(s)", 'INFO');
                            
                            foreach ($ads as $ad) {
                                try {
                                    $existingAd = $db->fetch("
                                        SELECT id FROM ads 
                                        WHERE ad_id = :ad_id AND adset_id = :adset_id
                                    ", [
                                        'ad_id' => $ad['id'],
                                        'adset_id' => $adsetDbId
                                    ]);
                                    
                                    $adData = [
                                        'ad_name' => $ad['name'],
                                        'status' => mapStatus($ad['status']),
                                        'creative_id' => $ad['creative']['id'] ?? null,
                                        'spent' => isset($ad['spend']) ? $ad['spend'] / 100 : 0,
                                        'impressions' => $ad['impressions'] ?? 0,
                                        'clicks' => $ad['clicks'] ?? 0,
                                        'conversions' => extractConversions($ad),
                                        'reach' => $ad['reach'] ?? 0,
                                        'frequency' => $ad['frequency'] ?? 0,
                                        'last_sync' => date('Y-m-d H:i:s')
                                    ];
                                    
                                    if ($existingAd) {
                                        $db->update('ads', $adData, 'id = :id', ['id' => $existingAd['id']]);
                                    } else {
                                        $adData['user_id'] = $account['user_id'];
                                        $adData['campaign_id'] = $campaignDbId;
                                        $adData['adset_id'] = $adsetDbId;
                                        $adData['ad_id'] = $ad['id'];
                                        $db->insert('ads', $adData);
                                        $totals['ads']++;
                                    }
                                    
                                } catch (Exception $e) {
                                    log_sync("        ERRO ao processar anúncio {$ad['name']}: " . $e->getMessage(), 'ERROR');
                                }
                            }
                            
                            // Delay para evitar rate limit
                            usleep(500000); // 0.5 segundos
                            
                        } catch (Exception $e) {
                            log_sync("      ERRO ao processar conjunto {$adset['name']}: " . $e->getMessage(), 'ERROR');
                        }
                    }
                    
                    // Delay entre campanhas
                    sleep(1);
                    
                } catch (Exception $e) {
                    log_sync("    ERRO ao processar campanha {$campaign['name']}: " . $e->getMessage(), 'ERROR');
                    $totals['errors']++;
                }
            }
            
            // Atualiza last_sync da conta
            $db->update('ad_accounts',
                ['last_sync' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $account['id']]
            );
            
            $totals['accounts']++;
            log_sync("Conta sincronizada com sucesso!", 'SUCCESS');
            
        } catch (Exception $e) {
            log_sync("ERRO ao processar conta: " . $e->getMessage(), 'ERROR');
            $totals['errors']++;
            
            // Marca conta com erro se token inválido
            if (strpos($e->getMessage(), 'token') !== false || strpos($e->getMessage(), 'expired') !== false) {
                $db->update('ad_accounts',
                    ['status' => 'error'],
                    'id = :id',
                    ['id' => $account['id']]
                );
            }
        }
        
        // Delay entre contas
        sleep(2);
    }
    
    log_sync('========== SINCRONIZAÇÃO CONCLUÍDA ==========', 'SUCCESS');
    log_sync("Contas sincronizadas: {$totals['accounts']}/" . count($accounts), 'INFO');
    log_sync("Campanhas processadas: {$totals['campaigns']}", 'INFO');
    log_sync("Conjuntos processados: {$totals['adsets']}", 'INFO');
    log_sync("Anúncios processados: {$totals['ads']}", 'INFO');
    log_sync("Erros: {$totals['errors']}", $totals['errors'] > 0 ? 'WARN' : 'INFO');
    
    exit(0);
    
} catch (Exception $e) {
    log_sync('ERRO FATAL: ' . $e->getMessage(), 'ERROR');
    log_sync('Stack trace: ' . $e->getTraceAsString(), 'ERROR');
    exit(1);
}

// ========== FUNÇÕES AUXILIARES ==========

function fetchMetaCampaigns($accountId, $accessToken) {
    $accountId = str_replace('act_', '', $accountId);
    $fields = ['id', 'name', 'status', 'objective', 'daily_budget', 'lifetime_budget', 'spend', 'impressions', 'clicks', 'actions'];
    
    $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
        'fields' => implode(',', $fields),
        'access_token' => $accessToken,
        'limit' => 100
    ]);
    
    return fetchFromMeta($url);
}

function fetchMetaAdSets($campaignId, $accessToken) {
    $fields = ['id', 'name', 'status', 'optimization_goal', 'billing_event', 'daily_budget', 'lifetime_budget', 'spend', 'impressions', 'clicks', 'actions'];
    
    $url = 'https://graph.facebook.com/v18.0/' . $campaignId . '/adsets?' . http_build_query([
        'fields' => implode(',', $fields),
        'access_token' => $accessToken,
        'limit' => 100
    ]);
    
    return fetchFromMeta($url);
}

function fetchMetaAds($adsetId, $accessToken) {
    $fields = ['id', 'name', 'status', 'creative', 'spend', 'impressions', 'clicks', 'actions', 'reach', 'frequency'];
    
    $url = 'https://graph.facebook.com/v18.0/' . $adsetId . '/ads?' . http_build_query([
        'fields' => implode(',', $fields),
        'access_token' => $accessToken,
        'limit' => 100
    ]);
    
    return fetchFromMeta($url);
}

function fetchFromMeta($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Erro na API Meta: HTTP {$httpCode}");
    }
    
    $data = json_decode($response, true);
    return $data['data'] ?? [];
}

function mapStatus($status) {
    $map = ['ACTIVE' => 'active', 'PAUSED' => 'paused', 'DELETED' => 'deleted', 'ARCHIVED' => 'deleted'];
    return $map[strtoupper($status)] ?? 'paused';
}

function extractConversions($item) {
    if (!isset($item['actions']) || !is_array($item['actions'])) return 0;
    $conversions = 0;
    foreach ($item['actions'] as $action) {
        if (in_array($action['action_type'], ['purchase', 'lead', 'complete_registration', 'subscribe'])) {
            $conversions += intval($action['value']);
        }
    }
    return $conversions;
}