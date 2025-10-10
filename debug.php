<?php
/**
 * Script de Sincronização Manual - CORRIGIDO
 * Salve como: test_sync.php na raiz
 */

require_once __DIR__ . '/core/Database.php';
session_start();

$userId = $_SESSION['user_id'] ?? 3;

echo "<h1>🔄 Teste de Sincronização - Meta Ads</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #0f172a; color: #e2e8f0; }
    h1, h2 { color: #667eea; }
    pre { background: #1e293b; padding: 15px; border-radius: 8px; border: 1px solid #334155; overflow-x: auto; max-height: 300px; }
    .success { color: #10b981; }
    .error { color: #ef4444; }
    .warning { color: #f59e0b; }
    .info { color: #3b82f6; }
    .section { margin: 20px 0; padding: 20px; background: #1e293b; border-radius: 12px; border: 1px solid #334155; }
    button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; margin: 5px; }
    button:hover { background: #5568d3; }
    button:disabled { background: #334155; cursor: not-allowed; }
    .log { font-size: 12px; line-height: 1.8; }
</style>";

$db = Database::getInstance();

// Função para buscar campanhas com métricas
function fetchMetaCampaignsWithInsights($accountId, $accessToken) {
    // Remove "act_" se existir
    $accountId = str_replace('act_', '', $accountId);
    
    // PASSO 1: Busca campanhas básicas (sem métricas)
    $basicFields = [
        'id',
        'name',
        'status',
        'objective',
        'daily_budget',
        'lifetime_budget'
    ];
    
    $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
        'fields' => implode(',', $basicFields),
        'access_token' => $accessToken,
        'limit' => 100
    ]);
    
    echo "<p class='info'>📡 Buscando campanhas básicas...</p>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "<p class='error'>❌ Erro ao buscar campanhas: HTTP {$httpCode}</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        return [];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['data'])) {
        echo "<p class='error'>❌ Resposta inválida</p>";
        return [];
    }
    
    $campaigns = $data['data'];
    echo "<p class='success'>✅ Encontradas " . count($campaigns) . " campanha(s)</p>";
    
    // PASSO 2: Busca insights (métricas) para cada campanha
    echo "<p class='info'>📊 Buscando métricas...</p>";
    
    foreach ($campaigns as $index => $campaign) {
        $insightsUrl = 'https://graph.facebook.com/v18.0/' . $campaign['id'] . '/insights?' . http_build_query([
            'fields' => 'impressions,clicks,spend,actions',
            'access_token' => $accessToken,
            'time_range' => json_encode(['since' => date('Y-m-d', strtotime('-90 days')), 'until' => date('Y-m-d')])
        ]);
        
        $ch = curl_init($insightsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $insightsResponse = curl_exec($ch);
        $insightsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Adiciona métricas padrão
        $campaigns[$index]['impressions'] = 0;
        $campaigns[$index]['clicks'] = 0;
        $campaigns[$index]['spend'] = 0;
        $campaigns[$index]['actions'] = [];
        
        if ($insightsCode === 200) {
            $insightsData = json_decode($insightsResponse, true);
            
            if (isset($insightsData['data'][0])) {
                $insights = $insightsData['data'][0];
                $campaigns[$index]['impressions'] = $insights['impressions'] ?? 0;
                $campaigns[$index]['clicks'] = $insights['clicks'] ?? 0;
                $campaigns[$index]['spend'] = $insights['spend'] ?? 0;
                $campaigns[$index]['actions'] = $insights['actions'] ?? [];
                
                echo "<p class='info' style='margin-left: 20px;'>📈 {$campaign['name']}: " . 
                     number_format($insights['impressions'] ?? 0) . " impressões, " .
                     number_format($insights['clicks'] ?? 0) . " cliques</p>";
            } else {
                echo "<p class='warning' style='margin-left: 20px;'>⚠️ {$campaign['name']}: Sem métricas disponíveis</p>";
            }
        } else {
            echo "<p class='warning' style='margin-left: 20px;'>⚠️ {$campaign['name']}: Erro ao buscar métricas (HTTP {$insightsCode})</p>";
        }
        
        // Aguarda um pouco para não sobrecarregar a API
        usleep(200000); // 200ms
    }
    
    return $campaigns;
}

// Ação de sincronizar
if (isset($_POST['sync_account'])) {
    $accountId = (int)$_POST['account_id'];
    
    echo "<div class='section'>";
    echo "<h2>📊 Sincronizando Conta ID: {$accountId}</h2>";
    
    // Busca conta
    $account = $db->fetch("
        SELECT * FROM ad_accounts 
        WHERE id = :id AND user_id = :user_id
    ", ['id' => $accountId, 'user_id' => $userId]);
    
    if (!$account) {
        echo "<p class='error'>❌ Conta não encontrada!</p>";
    } else {
        echo "<p class='info'>📱 Conta: <strong>{$account['account_name']}</strong></p>";
        echo "<p class='info'>🆔 Account ID: <strong>{$account['account_id']}</strong></p>";
        echo "<p class='info'>⚡ Status: <strong>{$account['status']}</strong></p>";
        
        if (empty($account['access_token'])) {
            echo "<p class='error'>❌ Token não encontrado! Reconecte a conta.</p>";
        } else {
            echo "<p class='success'>✅ Token encontrado</p>";
            
            try {
                echo "<div class='log'>";
                
                // Busca campanhas com insights
                $campaigns = fetchMetaCampaignsWithInsights($account['account_id'], $account['access_token']);
                
                if (empty($campaigns)) {
                    echo "<p class='warning'>⚠️ Esta conta não possui campanhas no Meta Ads</p>";
                } else {
                    $imported = 0;
                    $updated = 0;
                    
                    echo "<br><p class='info'>💾 Salvando no banco de dados...</p>";
                    
                    foreach ($campaigns as $campaign) {
                        // Verifica se existe
                        $exists = $db->fetch("
                            SELECT id FROM campaigns 
                            WHERE campaign_id = :campaign_id 
                            AND ad_account_id = :account_id
                        ", [
                            'campaign_id' => $campaign['id'],
                            'account_id' => $account['id']
                        ]);
                        
                        // Mapeia status
                        $statusMap = [
                            'ACTIVE' => 'active',
                            'PAUSED' => 'paused',
                            'DELETED' => 'deleted',
                            'ARCHIVED' => 'deleted'
                        ];
                        
                        $status = $statusMap[strtoupper($campaign['status'] ?? 'PAUSED')] ?? 'paused';
                        
                        // Extrai conversões
                        $conversions = 0;
                        if (isset($campaign['actions']) && is_array($campaign['actions'])) {
                            foreach ($campaign['actions'] as $action) {
                                if (in_array($action['action_type'], ['purchase', 'lead', 'complete_registration', 'subscribe'])) {
                                    $conversions += intval($action['value']);
                                }
                            }
                        }
                        
                        $data = [
                            'campaign_name' => $campaign['name'],
                            'status' => $status,
                            'objective' => $campaign['objective'] ?? null,
                            'budget' => isset($campaign['daily_budget']) ? floatval($campaign['daily_budget']) / 100 : 0,
                            'spent' => isset($campaign['spend']) ? floatval($campaign['spend']) : 0,
                            'impressions' => intval($campaign['impressions'] ?? 0),
                            'clicks' => intval($campaign['clicks'] ?? 0),
                            'conversions' => $conversions,
                            'last_sync' => date('Y-m-d H:i:s')
                        ];
                        
                        if ($exists) {
                            $db->update('campaigns', $data, 'id = :id', ['id' => $exists['id']]);
                            $updated++;
                            echo "<p class='info' style='margin-left: 20px;'>🔄 Atualizada: {$campaign['name']}</p>";
                        } else {
                            $data['user_id'] = $userId;
                            $data['ad_account_id'] = $account['id'];
                            $data['campaign_id'] = $campaign['id'];
                            
                            $db->insert('campaigns', $data);
                            $imported++;
                            echo "<p class='success' style='margin-left: 20px;'>➕ Importada: {$campaign['name']}</p>";
                        }
                    }
                    
                    // Atualiza last_sync da conta
                    $db->update('ad_accounts',
                        ['last_sync' => date('Y-m-d H:i:s')],
                        'id = :id',
                        ['id' => $account['id']]
                    );
                    
                    echo "<br>";
                    echo "<p class='success'><strong>🎉 Sincronização concluída com sucesso!</strong></p>";
                    echo "<p class='success'>➕ {$imported} nova(s) campanha(s) importada(s)</p>";
                    echo "<p class='info'>🔄 {$updated} campanha(s) atualizada(s)</p>";
                    echo "<br>";
                    echo "<p><a href='index.php?page=campanhas' target='_blank' style='color: #667eea;'>📊 Ver Todas as Campanhas</a></p>";
                    echo "<p><a href='index.php?page=campanhas-meta&account={$account['id']}' target='_blank' style='color: #667eea;'>📊 Ver Campanhas desta Conta</a></p>";
                }
                
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            }
        }
    }
    echo "</div>";
}

// Lista contas
echo "<div class='section'>";
echo "<h2>📱 Contas Disponíveis</h2>";

$accounts = $db->fetchAll("
    SELECT * FROM ad_accounts 
    WHERE user_id = :user_id AND platform = 'meta'
    ORDER BY status DESC, account_name
", ['user_id' => $userId]);

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #0f172a;'>";
echo "<th style='padding: 10px; text-align: left; border: 1px solid #334155;'>Conta</th>";
echo "<th style='padding: 10px; text-align: left; border: 1px solid #334155;'>Status</th>";
echo "<th style='padding: 10px; text-align: left; border: 1px solid #334155;'>Última Sync</th>";
echo "<th style='padding: 10px; text-align: left; border: 1px solid #334155;'>Ação</th>";
echo "</tr>";

foreach ($accounts as $acc) {
    $statusColor = $acc['status'] === 'active' ? 'success' : 'error';
    $statusText = $acc['status'] === 'active' ? '✅ Ativa' : '❌ Inativa';
    
    echo "<tr>";
    echo "<td style='padding: 10px; border: 1px solid #334155;'>{$acc['account_name']}</td>";
    echo "<td style='padding: 10px; border: 1px solid #334155;' class='{$statusColor}'>{$statusText}</td>";
    echo "<td style='padding: 10px; border: 1px solid #334155;'>" . ($acc['last_sync'] ?? 'Nunca') . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #334155;'>";
    echo "<form method='POST' style='display: inline;'>";
    echo "<input type='hidden' name='account_id' value='{$acc['id']}'>";
    echo "<button type='submit' name='sync_account'>🔄 Sincronizar</button>";
    echo "</form>";
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>💡 Próximos Passos</h2>";
echo "<ol>";
echo "<li><strong>Clique em 'Sincronizar'</strong> na conta ATAWEB acima</li>";
echo "<li>Aguarde o processo (pode demorar 30-60 segundos)</li>";
echo "<li>Se der erro, copie e me envie a mensagem</li>";
echo "<li>Se funcionar, você verá as campanhas importadas!</li>";
echo "<li>Depois acesse <a href='index.php?page=campanhas' style='color: #667eea;'>Todas as Campanhas</a></li>";
echo "</ol>";
echo "</div>";
?>