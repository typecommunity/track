<?php
/**
 * ========================================
 * SINCRONIZAÇÃO VIA CONTA (SEMPRE FUNCIONA)
 * ========================================
 * Busca insights agregados pela conta, não por campanha individual
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    die('❌ Faça login: <a href="../public/index.php?page=login">Login</a>');
}

$baseDir = '/home/ataweb.com.br/public_html/utmtrack';
$userId = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sincronização via Conta</title>
    <style>
        body { 
            font-family: monospace; 
            background: #0a0e1a; 
            color: #e4e6eb; 
            padding: 20px;
            line-height: 1.8;
        }
        h1, h2 { color: #3b82f6; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #6b7280; }
        pre { 
            background: #141824; 
            padding: 15px; 
            border-radius: 8px;
            overflow-x: auto;
            font-size: 11px;
        }
        .section { 
            margin: 20px 0; 
            padding: 20px; 
            background: #141824; 
            border-radius: 12px; 
            border: 1px solid #2a2f3e; 
        }
        .campaign {
            background: #1a1f2e;
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
            border-left: 3px solid #10b981;
        }
    </style>
</head>
<body>

<h1>🔄 Sincronização via Conta</h1>

<?php

// ========================================
// 1. SETUP
// ========================================
echo '<div class="section">';
echo '<h2>1️⃣ Configurando</h2>';

try {
    require_once $baseDir . '/core/Database.php';
    $db = Database::getInstance();
    echo '<p class="success">✅ Banco conectado</p>';
} catch (Exception $e) {
    echo '<p class="error">❌ Erro: ' . htmlspecialchars($e->getMessage()) . '</p>';
    die('</div></body></html>');
}

$account = $db->fetch("
    SELECT * FROM ad_accounts
    WHERE user_id = :user_id
    AND platform = 'meta'
    AND status = 'active'
    AND access_token IS NOT NULL
    ORDER BY id DESC
    LIMIT 1
", ['user_id' => $userId]);

if (!$account) {
    echo '<p class="error">❌ Nenhuma conta ativa</p>';
    die('</div></body></html>');
}

echo '<p class="success">✅ Conta: ' . htmlspecialchars($account['account_name']) . '</p>';
echo '<p class="info">Account ID: act_' . $account['account_id'] . '</p>';

echo '</div>';

// ========================================
// 2. BUSCA INSIGHTS PELA CONTA
// ========================================
echo '<div class="section">';
echo '<h2>2️⃣ Buscando Insights Agregados</h2>';

echo '<p class="info">📡 Buscando TODOS os insights da conta de uma vez...</p>';

// Busca insights de TODAS as campanhas pela conta
$url = "https://graph.facebook.com/v18.0/act_{$account['account_id']}/insights";
$params = [
    'level' => 'campaign',
    'fields' => 'campaign_id,campaign_name,impressions,clicks,spend,reach,frequency,ctr,cpc,cpm,cpp,actions,action_values',
    'date_preset' => 'maximum', // Período máximo disponível
    'limit' => 100,
    'access_token' => $account['access_token']
];

$ch = curl_init($url . '?' . http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo '<p><strong>HTTP Code:</strong> <span class="' . ($httpCode === 200 ? 'success' : 'error') . '">' . $httpCode . '</span></p>';

if ($httpCode !== 200) {
    echo '<p class="error">❌ Erro na requisição</p>';
    echo '<pre>' . htmlspecialchars($response) . '</pre>';
    die('</div></body></html>');
}

$data = json_decode($response, true);

if (!isset($data['data']) || empty($data['data'])) {
    echo '<p class="warning">⚠️ Nenhum insight encontrado</p>';
    echo '<p>Isso significa que as campanhas não têm dados (nunca foram veiculadas ou não têm gastos).</p>';
    echo '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
    die('</div></body></html>');
}

$apiInsights = $data['data'];
echo '<p class="success">✅ Encontrados <strong>' . count($apiInsights) . '</strong> insights na API!</p>';

echo '</div>';

// ========================================
// 3. PROCESSA E SALVA
// ========================================
echo '<div class="section">';
echo '<h2>3️⃣ Salvando no Banco</h2>';

$synced = 0;
$notFound = 0;

foreach ($apiInsights as $insights) {
    $metaCampaignId = $insights['campaign_id'] ?? null;
    $campaignName = $insights['campaign_name'] ?? 'Sem nome';
    
    if (!$metaCampaignId) continue;
    
    echo '<div class="campaign">';
    echo '<strong>' . htmlspecialchars($campaignName) . '</strong><br>';
    echo '<span class="info">ID: ' . $metaCampaignId . '</span><br>';
    
    // Busca campanha no banco
    $campaign = $db->fetch("
        SELECT * FROM campaigns
        WHERE campaign_id = :campaign_id
        AND user_id = :user_id
    ", [
        'campaign_id' => $metaCampaignId,
        'user_id' => $userId
    ]);
    
    if (!$campaign) {
        echo '<span class="warning">⚠️ Campanha não existe no banco local (será ignorada)</span><br>';
        $notFound++;
        echo '</div>';
        continue;
    }
    
    // Extrai conversões
    $purchase = 0;
    $purchaseValue = 0;
    $addToCart = 0;
    $initiateCheckout = 0;
    $lead = 0;
    
    if (isset($insights['actions'])) {
        foreach ($insights['actions'] as $action) {
            switch ($action['action_type']) {
                case 'purchase':
                case 'offsite_conversion.fb_pixel_purchase':
                case 'omni_purchase':
                    $purchase += intval($action['value'] ?? 0);
                    break;
                case 'add_to_cart':
                case 'offsite_conversion.fb_pixel_add_to_cart':
                    $addToCart += intval($action['value'] ?? 0);
                    break;
                case 'initiate_checkout':
                case 'offsite_conversion.fb_pixel_initiate_checkout':
                    $initiateCheckout += intval($action['value'] ?? 0);
                    break;
                case 'lead':
                case 'offsite_conversion.fb_pixel_lead':
                    $lead += intval($action['value'] ?? 0);
                    break;
            }
        }
    }
    
    if (isset($insights['action_values'])) {
        foreach ($insights['action_values'] as $action) {
            if (in_array($action['action_type'], ['purchase', 'offsite_conversion.fb_pixel_purchase', 'omni_purchase'])) {
                $purchaseValue += floatval($action['value'] ?? 0);
            }
        }
    }
    
    $spend = floatval($insights['spend'] ?? 0);
    $impressions = intval($insights['impressions'] ?? 0);
    $clicks = intval($insights['clicks'] ?? 0);
    
    // Calcula métricas
    $roas = ($spend > 0 && $purchaseValue > 0) ? round($purchaseValue / $spend, 2) : 0;
    $roi = ($spend > 0 && $purchaseValue > 0) ? round((($purchaseValue - $spend) / $spend) * 100, 2) : 0;
    $cpa = ($purchase > 0 && $spend > 0) ? round($spend / $purchase, 2) : 0;
    
    // Período (últimos 2 anos até hoje)
    $dateStart = date('Y-m-d', strtotime('-2 years'));
    $dateStop = date('Y-m-d');
    
    // Salva
    try {
        $db->query("
            INSERT INTO campaign_insights (
                campaign_id,
                date_start,
                date_stop,
                impressions,
                clicks,
                spend,
                reach,
                frequency,
                ctr,
                cpc,
                cpm,
                cpp,
                purchase,
                purchase_value,
                add_to_cart,
                initiate_checkout,
                lead,
                roas,
                roi,
                cpa
            ) VALUES (
                :campaign_id, :date_start, :date_stop,
                :impressions, :clicks, :spend, :reach, :frequency,
                :ctr, :cpc, :cpm, :cpp,
                :purchase, :purchase_value, :add_to_cart, :initiate_checkout, :lead,
                :roas, :roi, :cpa
            )
            ON DUPLICATE KEY UPDATE
                impressions = VALUES(impressions),
                clicks = VALUES(clicks),
                spend = VALUES(spend),
                reach = VALUES(reach),
                frequency = VALUES(frequency),
                ctr = VALUES(ctr),
                cpc = VALUES(cpc),
                cpm = VALUES(cpm),
                cpp = VALUES(cpp),
                purchase = VALUES(purchase),
                purchase_value = VALUES(purchase_value),
                add_to_cart = VALUES(add_to_cart),
                initiate_checkout = VALUES(initiate_checkout),
                lead = VALUES(lead),
                roas = VALUES(roas),
                roi = VALUES(roi),
                cpa = VALUES(cpa)
        ", [
            'campaign_id' => $campaign['id'],
            'date_start' => $dateStart,
            'date_stop' => $dateStop,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'spend' => $spend,
            'reach' => intval($insights['reach'] ?? 0),
            'frequency' => floatval($insights['frequency'] ?? 0),
            'ctr' => floatval($insights['ctr'] ?? 0),
            'cpc' => floatval($insights['cpc'] ?? 0),
            'cpm' => floatval($insights['cpm'] ?? 0),
            'cpp' => floatval($insights['cpp'] ?? 0),
            'purchase' => $purchase,
            'purchase_value' => $purchaseValue,
            'add_to_cart' => $addToCart,
            'initiate_checkout' => $initiateCheckout,
            'lead' => $lead,
            'roas' => $roas,
            'roi' => $roi,
            'cpa' => $cpa
        ]);
        
        echo '<span class="success">✅ Sincronizado!</span><br>';
        echo '📊 Impressões: ' . number_format($impressions, 0, ',', '.') . ' | ';
        echo '💰 Gastos: R$ ' . number_format($spend, 2, ',', '.') . '<br>';
        
        if ($purchase > 0 || $purchaseValue > 0) {
            echo '🛒 Compras: ' . $purchase . ' | ';
            echo '💵 Faturamento: R$ ' . number_format($purchaseValue, 2, ',', '.') . '<br>';
            
            if ($roas > 0) {
                echo '📈 ROAS: ' . $roas . 'x | ROI: ' . $roi . '% | CPA: R$ ' . number_format($cpa, 2, ',', '.') . '<br>';
            }
        }
        
        $synced++;
        
    } catch (Exception $e) {
        echo '<span class="error">❌ Erro ao salvar: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
    }
    
    echo '</div>';
}

echo '</div>';

// ========================================
// 4. RESULTADO
// ========================================
echo '<div class="section">';
echo '<h2>4️⃣ Resultado Final</h2>';

echo '<p class="success">✅ <strong>Sincronizados:</strong> ' . $synced . ' campanhas</p>';

if ($notFound > 0) {
    echo '<p class="info">ℹ️ <strong>Não encontradas no banco:</strong> ' . $notFound . '</p>';
}

$totalInsights = $db->fetch("
    SELECT COUNT(*) as total 
    FROM campaign_insights ci
    JOIN campaigns c ON c.id = ci.campaign_id
    WHERE c.user_id = :user_id
", ['user_id' => $userId])['total'] ?? 0;

echo '<p><strong>Total de insights no banco:</strong> ' . $totalInsights . '</p>';

if ($totalInsights > 0) {
    echo '<hr>';
    echo '<p class="success">🎉 <strong>PRONTO!</strong> Agora você tem dados no dashboard!</p>';
    echo '<a href="../public/index.php?page=campanhas" style="display: inline-block; padding: 15px 30px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; margin: 15px 0; font-weight: bold; font-size: 16px;">📊 VER DASHBOARD</a>';
} else {
    echo '<p class="error">❌ Nenhum insight foi salvo</p>';
    echo '<p>Isso significa que suas campanhas não têm dados (nunca veicularam ou não têm gastos).</p>';
}

echo '</div>';

?>

<hr>
<p style="text-align: center; color: #8b92a4;">UTMTrack v3.0 - Sincronização Definitiva</p>

</body>
</html>