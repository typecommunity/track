<?php
/**
 * UTMTrack - Sincroniza TODAS as contas Meta AGORA
 * 
 * COLOQUE NA RAIZ DO PROJETO
 * Acesse: http://seudominio.com/sync_all_accounts.php
 */

set_time_limit(300); // 5 minutos

require_once __DIR__ . '/core/Database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Sincronização Completa Meta</title>
    <style>
        body { 
            font-family: monospace; 
            background: #0f172a; 
            color: #e2e8f0; 
            padding: 20px;
        }
        .log { 
            background: #1e293b; 
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 20px; 
            margin-bottom: 20px;
            max-height: 600px;
            overflow-y: auto;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #fbbf24; }
        .info { color: #667eea; }
        .processing { color: #fbbf24; }
        h1 { color: #667eea; }
        .progress {
            background: #0f172a;
            border-radius: 8px;
            padding: 10px;
            margin: 20px 0;
        }
        .progress-bar {
            height: 30px;
            background: linear-gradient(90deg, #667eea, #10b981);
            border-radius: 6px;
            text-align: center;
            line-height: 30px;
            color: white;
            font-weight: bold;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
";

echo "<h1>🔄 Sincronização Completa - Meta Ads</h1>";

$db = Database::getInstance();

// Busca todas as contas ativas
$accounts = $db->fetchAll("
    SELECT * FROM ad_accounts 
    WHERE platform = 'meta' 
    AND access_token IS NOT NULL
    AND status = 'active'
    ORDER BY id ASC
");

$totalAccounts = count($accounts);
$processedAccounts = 0;
$totalImported = 0;
$totalUpdated = 0;
$totalErrors = 0;

echo "<div class='log'>";
echo "<p class='info'>📊 Iniciando sincronização de {$totalAccounts} conta(s)...</p>";
echo "<hr>";

foreach ($accounts as $account) {
    $processedAccounts++;
    $progress = round(($processedAccounts / $totalAccounts) * 100);
    
    echo "<p class='processing'>🔄 [{$processedAccounts}/{$totalAccounts}] Sincronizando: <strong>{$account['account_name']}</strong></p>";
    
    try {
        // Busca campanhas da API
        $accountId = str_replace('act_', '', $account['account_id']);
        
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
            'fields' => 'id,name,status,objective,daily_budget,lifetime_budget,spend,impressions,clicks,actions',
            'access_token' => $account['access_token'],
            'limit' => 100
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo "<p class='error'>   ❌ Erro HTTP {$httpCode}</p>";
            $totalErrors++;
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['data'])) {
            echo "<p class='error'>   ❌ Resposta inválida da API</p>";
            $totalErrors++;
            continue;
        }
        
        $campaigns = $data['data'];
        $campaignsCount = count($campaigns);
        
        if ($campaignsCount === 0) {
            echo "<p class='warning'>   ⚠️ Nenhuma campanha encontrada</p>";
            continue;
        }
        
        echo "<p class='info'>   📋 Encontradas {$campaignsCount} campanha(s)</p>";
        
        $imported = 0;
        $updated = 0;
        
        foreach ($campaigns as $campaign) {
            // Verifica se já existe
            $exists = $db->fetch("
                SELECT id FROM campaigns 
                WHERE campaign_id = :campaign_id 
                AND ad_account_id = :account_id
            ", [
                'campaign_id' => $campaign['id'],
                'account_id' => $account['id']
            ]);
            
            // Prepara dados
            $campaignData = [
                'campaign_name' => $campaign['name'] ?? 'Sem nome',
                'status' => mapStatus($campaign['status'] ?? 'PAUSED'),
                'objective' => $campaign['objective'] ?? null,
                'budget' => isset($campaign['daily_budget']) ? floatval($campaign['daily_budget']) / 100 : 0,
                'spent' => isset($campaign['spend']) ? floatval($campaign['spend']) : 0,
                'impressions' => isset($campaign['impressions']) ? intval($campaign['impressions']) : 0,
                'clicks' => isset($campaign['clicks']) ? intval($campaign['clicks']) : 0,
                'conversions' => extractConversions($campaign),
                'last_sync' => date('Y-m-d H:i:s')
            ];
            
            if ($exists) {
                $db->update('campaigns', $campaignData, 'id = :id', ['id' => $exists['id']]);
                $updated++;
            } else {
                $campaignData['user_id'] = $account['user_id'];
                $campaignData['ad_account_id'] = $account['id'];
                $campaignData['campaign_id'] = $campaign['id'];
                
                $db->insert('campaigns', $campaignData);
                $imported++;
            }
        }
        
        // Atualiza last_sync da conta
        $db->update('ad_accounts',
            ['last_sync' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $account['id']]
        );
        
        echo "<p class='success'>   ✅ Importadas: {$imported} | Atualizadas: {$updated}</p>";
        
        $totalImported += $imported;
        $totalUpdated += $updated;
        
    } catch (Exception $e) {
        echo "<p class='error'>   ❌ Erro: " . $e->getMessage() . "</p>";
        $totalErrors++;
    }
    
    echo "<hr>";
    
    // Delay para evitar rate limit
    sleep(1);
}

echo "</div>";

// Resumo final
echo "<div class='log'>";
echo "<h2 class='success'>🎉 SINCRONIZAÇÃO CONCLUÍDA!</h2>";
echo "<p><strong>Contas processadas:</strong> {$processedAccounts}/{$totalAccounts}</p>";
echo "<p><strong>Campanhas importadas:</strong> <span class='success'>{$totalImported}</span></p>";
echo "<p><strong>Campanhas atualizadas:</strong> <span class='info'>{$totalUpdated}</span></p>";
echo "<p><strong>Erros:</strong> <span class='" . ($totalErrors > 0 ? 'error' : 'success') . "'>{$totalErrors}</span></p>";

if ($totalImported > 0 || $totalUpdated > 0) {
    echo "<hr>";
    echo "<p class='success'>✅ Campanhas sincronizadas com sucesso!</p>";
    echo "<p><a href='index.php?page=campanhas' style='display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; margin-top: 10px;'>📊 Ver Campanhas</a></p>";
} else {
    echo "<hr>";
    echo "<p class='warning'>⚠️ Nenhuma campanha foi importada. Verifique se as contas têm campanhas ativas.</p>";
}

echo "</div>";

echo "</body></html>";

// Funções auxiliares
function mapStatus($status) {
    $map = ['ACTIVE' => 'active', 'PAUSED' => 'paused', 'DELETED' => 'deleted', 'ARCHIVED' => 'deleted'];
    return $map[strtoupper($status)] ?? 'paused';
}

function extractConversions($campaign) {
    if (!isset($campaign['actions']) || !is_array($campaign['actions'])) return 0;
    $conversions = 0;
    foreach ($campaign['actions'] as $action) {
        if (isset($action['action_type']) && in_array($action['action_type'], ['purchase', 'lead', 'complete_registration', 'subscribe', 'omni_purchase'])) {
            $conversions += isset($action['value']) ? intval($action['value']) : 0;
        }
    }
    return $conversions;
}
?>