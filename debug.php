<?php
/**
 * ========================================
 * SUPER DEBUG: FLUXO COMPLETO DO DASHBOARD
 * ========================================
 * Mostra EXATAMENTE onde está o problema
 * 
 * LOCAL: /utmtrack/super-debug-dashboard.php
 * ACESSE: https://ataweb.com.br/utmtrack/super-debug-dashboard.php
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>🔍 Super Debug - Dashboard UTMTrack</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #7c3aed 100%);
            color: #fff;
            padding: 30px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0,0,0,0.4);
            padding: 40px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        h1 {
            font-size: 36px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        h2 {
            font-size: 24px;
            margin: 30px 0 15px;
            color: #60a5fa;
            border-bottom: 2px solid rgba(96, 165, 250, 0.3);
            padding-bottom: 10px;
        }
        h3 {
            font-size: 18px;
            margin: 20px 0 10px;
            color: #4ade80;
        }
        .step {
            background: rgba(255,255,255,0.05);
            padding: 25px;
            margin: 20px 0;
            border-radius: 10px;
            border-left: 4px solid #4ade80;
        }
        .success { 
            color: #4ade80; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            margin: 10px 0;
        }
        .error { 
            color: #f87171; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            margin: 10px 0;
        }
        .warning { 
            color: #fbbf24;
            display: flex; 
            align-items: center; 
            gap: 10px;
            margin: 10px 0;
        }
        pre {
            background: rgba(0,0,0,0.6);
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.8;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 15px 0;
        }
        .keyword { color: #569cd6; }
        .string { color: #ce9178; }
        .number { color: #b5cea8; }
        .comment { color: #6a9955; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        th {
            background: rgba(0,0,0,0.4);
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 222, 128, 0.4);
        }
        .icon { font-size: 20px; }
        .highlight { 
            background: rgba(250, 204, 21, 0.2);
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
        }
        .badge.error { background: rgba(248, 113, 113, 0.2); color: #f87171; }
        .badge.warning { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
    </style>
</head>
<body>
<div class="container">

<h1>🔍 Super Debug - Dashboard UTMTrack</h1>
<p style="color: rgba(255,255,255,0.8); margin-bottom: 30px;">
    Diagnóstico completo do fluxo de dados do dashboard
</p>

<?php
// ========================================
// 1. VERIFICAÇÕES INICIAIS
// ========================================
echo "<div class='step'>";
echo "<h2>1️⃣ Verificações Iniciais</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'><span class='icon'>❌</span><span>Usuário não autenticado</span></div>";
    echo "<a href='/utmtrack/index.php?page=login' class='btn'>Fazer Login</a>";
    echo "</div></div></body></html>";
    exit;
}

$userId = $_SESSION['user_id'];
echo "<div class='success'><span class='icon'>✅</span><span>Usuário autenticado: ID <span class='highlight'>{$userId}</span></span></div>";

try {
    require_once __DIR__ . '/core/Database.php';
    $db = Database::getInstance();
    echo "<div class='success'><span class='icon'>✅</span><span>Conexão com banco estabelecida</span></div>";
} catch (Exception $e) {
    echo "<div class='error'><span class='icon'>❌</span><span>Erro ao conectar: " . $e->getMessage() . "</span></div>";
    exit;
}

// Verifica se controller existe
$controllerPath = __DIR__ . '/app/controllers/CampaignController.php';
$controllerExists = file_exists($controllerPath);
echo "<div class='" . ($controllerExists ? 'success' : 'error') . "'>";
echo "<span class='icon'>" . ($controllerExists ? '✅' : '❌') . "</span>";
echo "<span>CampaignController: " . ($controllerExists ? 'Encontrado' : 'NÃO encontrado') . "</span>";
echo "</div>";

// Verifica se view existe
$viewPath = __DIR__ . '/app/views/campaigns/index.php';
$viewExists = file_exists($viewPath);
echo "<div class='" . ($viewExists ? 'success' : 'error') . "'>";
echo "<span class='icon'>" . ($viewExists ? '✅' : '❌') . "</span>";
echo "<span>View campaigns/index: " . ($viewExists ? 'Encontrada' : 'NÃO encontrada') . "</span>";
echo "</div>";

// Verifica JavaScript
$jsPath = __DIR__ . '/assets/js/utmtrack-dashboard-v2.js';
$jsExists = file_exists($jsPath);
echo "<div class='" . ($jsExists ? 'success' : 'warning') . "'>";
echo "<span class='icon'>" . ($jsExists ? '✅' : '⚠️') . "</span>";
echo "<span>JavaScript dashboard: " . ($jsExists ? 'Encontrado' : 'NÃO encontrado') . "</span>";
echo "</div>";

echo "</div>";

// ========================================
// 2. TESTE DE QUERY DO CONTROLLER
// ========================================
echo "<div class='step'>";
echo "<h2>2️⃣ Query Exata do Controller</h2>";

echo "<p>Esta é a MESMA query que o CampaignController usa:</p>";

$query = "
    SELECT 
        c.id,
        c.user_id,
        c.ad_account_id,
        c.campaign_id,
        c.campaign_name,
        c.status,
        c.effective_status,
        c.objective,
        c.buying_type,
        c.daily_budget,
        c.lifetime_budget,
        c.campaign_budget_optimization,
        c.is_asc,
        aa.account_name,
        ci.impressions,
        ci.clicks,
        ci.spend,
        ci.reach,
        ci.frequency,
        ci.ctr,
        ci.cpc,
        ci.cpm,
        ci.purchase,
        ci.purchase_value,
        ci.roas,
        ci.roi,
        ci.cpa
    FROM campaigns c
    LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
    LEFT JOIN campaign_insights ci ON ci.campaign_id = c.id
    WHERE c.user_id = :user_id
    ORDER BY c.created_at DESC
";

try {
    $campaigns = $db->fetchAll($query, ['user_id' => $userId]);
    
    echo "<div class='success'>";
    echo "<span class='icon'>✅</span>";
    echo "<span>Query executada: <span class='highlight'>" . count($campaigns) . "</span> campanhas retornadas</span>";
    echo "</div>";
    
    if (empty($campaigns)) {
        echo "<div class='error'>";
        echo "<span class='icon'>❌</span>";
        echo "<span><strong>PROBLEMA CRÍTICO:</strong> Query não retornou nenhuma campanha!</span>";
        echo "</div>";
        echo "<p>Isso significa que o controller NÃO vai passar dados para a view.</p>";
    } else {
        echo "<h3>📊 Dados Retornados (primeiras 3 campanhas):</h3>";
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Status</th><th>Orçamento</th><th>Gastos</th><th>Vendas</th><th>ROAS</th></tr>";
        
        foreach (array_slice($campaigns, 0, 3) as $c) {
            echo "<tr>";
            echo "<td>{$c['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($c['campaign_name'], 0, 30)) . "</td>";
            echo "<td><span class='badge'>{$c['status']}</span></td>";
            echo "<td>R$ " . number_format(floatval($c['daily_budget']), 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format(floatval($c['spend'] ?? 0), 2, ',', '.') . "</td>";
            echo "<td>" . ($c['purchase'] ?? 0) . "</td>";
            echo "<td>" . number_format(floatval($c['roas'] ?? 0), 2, ',', '.') . "x</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>📝 Estrutura de Dados Completa (1ª campanha):</h3>";
        echo "<pre>" . json_encode($campaigns[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<span class='icon'>❌</span>";
    echo "<span>Erro na query: " . $e->getMessage() . "</span>";
    echo "</div>";
}

echo "</div>";

// ========================================
// 3. SIMULAÇÃO DO CONTROLLER
// ========================================
echo "<div class='step'>";
echo "<h2>3️⃣ Simulação do Controller</h2>";

echo "<p>Vamos simular o que o CampaignController faz:</p>";

$campaigns = $db->fetchAll($query, ['user_id' => $userId]);

// Calcula stats
$stats = [
    'total_campaigns' => count($campaigns),
    'active_campaigns' => 0,
    'total_spend' => 0,
    'total_revenue' => 0,
    'avg_roas' => 0
];

foreach ($campaigns as $c) {
    if ($c['status'] === 'active') {
        $stats['active_campaigns']++;
    }
    $stats['total_spend'] += floatval($c['spend'] ?? 0);
    $stats['total_revenue'] += floatval($c['purchase_value'] ?? 0);
}

if ($stats['total_spend'] > 0) {
    $stats['avg_roas'] = round($stats['total_revenue'] / $stats['total_spend'], 2);
}

echo "<h3>📈 Estatísticas Calculadas:</h3>";
echo "<table>";
echo "<tr><th>Métrica</th><th>Valor</th></tr>";
echo "<tr><td>Total de Campanhas</td><td><span class='highlight'>{$stats['total_campaigns']}</span></td></tr>";
echo "<tr><td>Campanhas Ativas</td><td><span class='highlight'>{$stats['active_campaigns']}</span></td></tr>";
echo "<tr><td>Total Gasto</td><td>R$ " . number_format($stats['total_spend'], 2, ',', '.') . "</td></tr>";
echo "<tr><td>Total Receita</td><td>R$ " . number_format($stats['total_revenue'], 2, ',', '.') . "</td></tr>";
echo "<tr><td>ROAS Médio</td><td>{$stats['avg_roas']}x</td></tr>";
echo "</table>";

if ($stats['total_campaigns'] > 0) {
    echo "<div class='success'>";
    echo "<span class='icon'>✅</span>";
    echo "<span>Controller consegue processar os dados corretamente</span>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<span class='icon'>❌</span>";
    echo "<span><strong>PROBLEMA:</strong> Sem dados para processar</span>";
    echo "</div>";
}

echo "</div>";

// ========================================
// 4. VERIFICAÇÃO DO HTML RENDERIZADO
// ========================================
echo "<div class='step'>";
echo "<h2>4️⃣ Como a View Deve Renderizar</h2>";

echo "<p>A view <code>campaigns/index.php</code> deve receber as variáveis:</p>";

echo "<div class='code-block'>";
echo "<span class='keyword'>\$campaigns</span> = <span class='comment'>// Array com " . count($campaigns) . " campanhas</span><br>";
echo "<span class='keyword'>\$stats</span> = <span class='comment'>// Array com estatísticas</span><br>";
echo "<span class='keyword'>\$userColumns</span> = <span class='comment'>// Colunas personalizadas do usuário</span><br>";
echo "</div>";

echo "<h3>📋 Exemplo de Como Renderizar UMA Linha:</h3>";

if (!empty($campaigns)) {
    $c = $campaigns[0];
    
    echo "<div class='code-block'>";
    echo "<span class='keyword'>&lt;tr</span> <br>";
    echo "    data-id=<span class='string'>\"" . $c['id'] . "\"</span><br>";
    echo "    data-campaign-id=<span class='string'>\"" . htmlspecialchars($c['campaign_id']) . "\"</span><br>";
    echo "    data-name=<span class='string'>\"" . strtolower($c['campaign_name']) . "\"</span><br>";
    echo "    data-status=<span class='string'>\"" . $c['status'] . "\"</span><br>";
    echo "<span class='keyword'>&gt;</span><br>";
    echo "    <span class='keyword'>&lt;td&gt;</span>" . htmlspecialchars(substr($c['campaign_name'], 0, 30)) . "<span class='keyword'>&lt;/td&gt;</span><br>";
    echo "    <span class='keyword'>&lt;td&gt;</span>" . $c['status'] . "<span class='keyword'>&lt;/td&gt;</span><br>";
    echo "    <span class='keyword'>&lt;td&gt;</span>R$ " . number_format(floatval($c['spend'] ?? 0), 2, ',', '.') . "<span class='keyword'>&lt;/td&gt;</span><br>";
    echo "<span class='keyword'>&lt;/tr&gt;</span>";
    echo "</div>";
}

echo "</div>";

// ========================================
// 5. TESTE DE ACESSO À VIEW
// ========================================
echo "<div class='step'>";
echo "<h2>5️⃣ Teste de Acesso ao Dashboard</h2>";

echo "<p>Vamos verificar se conseguimos acessar a página de campanhas:</p>";

$dashboardUrl = '/utmtrack/index.php?page=campanhas';
$fullUrl = 'https://ataweb.com.br' . $dashboardUrl;

echo "<div class='success'>";
echo "<span class='icon'>🔗</span>";
echo "<span>URL do Dashboard: <a href='{$dashboardUrl}' target='_blank' style='color: #4ade80;'>{$fullUrl}</a></span>";
echo "</div>";

// Tenta simular o que acontece quando acessa
echo "<h3>🔄 Fluxo Esperado:</h3>";
echo "<ol style='line-height: 2;'>";
echo "<li>✅ Usuário acessa <code>index.php?page=campanhas</code></li>";
echo "<li>✅ Sistema carrega <code>CampaignController</code></li>";
echo "<li>✅ Controller executa método <code>index()</code></li>";
echo "<li>✅ Controller busca " . count($campaigns) . " campanhas do banco</li>";
echo "<li>✅ Controller calcula estatísticas</li>";
echo "<li>✅ Controller renderiza view <code>campaigns/index.php</code></li>";
echo "<li>✅ View gera HTML com " . count($campaigns) . " linhas <code>&lt;tr&gt;</code></li>";
echo "<li>✅ JavaScript carrega e lê os dados do DOM</li>";
echo "<li>✅ Dashboard exibe as campanhas</li>";
echo "</ol>";

echo "</div>";

// ========================================
// 6. CHECKLIST DE PROBLEMAS COMUNS
// ========================================
echo "<div class='step'>";
echo "<h2>6️⃣ Checklist de Problemas Comuns</h2>";

$checks = [];

// Check 1: Dados no banco
$checks[] = [
    'status' => count($campaigns) > 0,
    'label' => 'Dados existem no banco',
    'fix' => 'Execute sincronização no dashboard'
];

// Check 2: Controller existe
$checks[] = [
    'status' => $controllerExists,
    'label' => 'CampaignController existe',
    'fix' => 'Verifique o arquivo em /app/controllers/'
];

// Check 3: View existe
$checks[] = [
    'status' => $viewExists,
    'label' => 'View campaigns/index existe',
    'fix' => 'Verifique o arquivo em /app/views/campaigns/'
];

// Check 4: Rota configurada
$routerPath = __DIR__ . '/index.php';
$routerContent = file_exists($routerPath) ? file_get_contents($routerPath) : '';
$hasRoute = strpos($routerContent, 'campanhas') !== false || strpos($routerContent, 'campaigns') !== false;
$checks[] = [
    'status' => $hasRoute,
    'label' => 'Rota "campanhas" configurada',
    'fix' => 'Configure a rota no index.php principal'
];

// Check 5: JavaScript existe
$checks[] = [
    'status' => $jsExists,
    'label' => 'JavaScript do dashboard existe',
    'fix' => 'Crie o arquivo em /assets/js/'
];

echo "<table>";
echo "<tr><th>Verificação</th><th>Status</th><th>Ação se Falhar</th></tr>";

foreach ($checks as $check) {
    $statusIcon = $check['status'] ? '✅' : '❌';
    $statusClass = $check['status'] ? 'success' : 'error';
    
    echo "<tr>";
    echo "<td>{$check['label']}</td>";
    echo "<td><span class='{$statusClass}'>{$statusIcon}</span></td>";
    echo "<td>" . ($check['status'] ? '-' : $check['fix']) . "</td>";
    echo "</tr>";
}

echo "</table>";

$allPassed = array_reduce($checks, function($carry, $check) {
    return $carry && $check['status'];
}, true);

if ($allPassed) {
    echo "<div class='success' style='font-size: 18px; margin-top: 20px;'>";
    echo "<span class='icon'>🎉</span>";
    echo "<span><strong>Todos os checks passaram!</strong> O problema deve ser na renderização da view.</span>";
    echo "</div>";
} else {
    echo "<div class='error' style='font-size: 18px; margin-top: 20px;'>";
    echo "<span class='icon'>⚠️</span>";
    echo "<span><strong>Alguns checks falharam.</strong> Corrija os itens marcados acima.</span>";
    echo "</div>";
}

echo "</div>";

// ========================================
// 7. CÓDIGO DE EXEMPLO PARA A VIEW
// ========================================
echo "<div class='step'>";
echo "<h2>7️⃣ Código Correto para a View</h2>";

echo "<p>Cole este código no seu <code>app/views/campaigns/index.php</code> no loop de campanhas:</p>";

echo "<pre style='background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; font-size: 12px;'>";
echo htmlspecialchars('<?php if (!empty($campaigns)): ?>
    <?php foreach ($campaigns as $c): ?>
    <tr 
        data-id="<?= $c[\'id\'] ?>"
        data-campaign-id="<?= htmlspecialchars($c[\'campaign_id\']) ?>"
        data-name="<?= strtolower($c[\'campaign_name\']) ?>"
        data-status="<?= $c[\'status\'] ?>"
        data-account="<?= htmlspecialchars($c[\'account_name\'] ?? \'\') ?>"
    >
        <td><?= htmlspecialchars($c[\'campaign_name\']) ?></td>
        <td><?= $c[\'status\'] ?></td>
        <td>R$ <?= number_format($c[\'daily_budget\'], 2, \',\', \'.\') ?></td>
        <td>R$ <?= number_format($c[\'spend\'] ?? 0, 2, \',\', \'.\') ?></td>
        <td><?= $c[\'purchase\'] ?? 0 ?></td>
        <td><?= number_format($c[\'roas\'] ?? 0, 2, \',\', \'.\') ?>x</td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="10">Nenhuma campanha encontrada</td></tr>
<?php endif; ?>');
echo "</pre>";

echo "</div>";

// ========================================
// PRÓXIMOS PASSOS
// ========================================
echo "<div style='margin-top: 40px; padding: 30px; background: rgba(74, 222, 128, 0.1); border-radius: 10px; border: 2px solid rgba(74, 222, 128, 0.3);'>";
echo "<h2 style='color: #4ade80; margin-top: 0;'>🎯 Próximos Passos</h2>";

if (count($campaigns) > 0) {
    echo "<p style='font-size: 16px;'>Você tem <strong>" . count($campaigns) . " campanhas</strong> prontas para exibir!</p>";
    echo "<ol style='line-height: 2.5; font-size: 15px;'>";
    echo "<li>📂 Abra o arquivo: <code>app/views/campaigns/index.php</code></li>";
    echo "<li>🔍 Procure por: <code>&lt;tbody id=\"tableBody\"&gt;</code></li>";
    echo "<li>✏️ Verifique se o loop <code>&lt;?php foreach (\$campaigns as \$c): ?&gt;</code> existe</li>";
    echo "<li>🔧 Se não existir, adicione o código de exemplo acima</li>";
    echo "<li>💾 Salve o arquivo</li>";
    echo "<li>🔄 Limpe o cache: <kbd>Ctrl+Shift+Del</kbd></li>";
    echo "<li>📊 Acesse: <a href='{$dashboardUrl}' style='color: #4ade80; font-weight: 600;'>Dashboard de Campanhas</a></li>";
    echo "<li>🎉 As " . count($campaigns) . " campanhas devem aparecer!</li>";
    echo "</ol>";
} else {
    echo "<p style='font-size: 16px;'>⚠️ Não há campanhas para exibir. Execute uma sincronização primeiro.</p>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='{$dashboardUrl}' class='btn'>📊 Abrir Dashboard</a>";
echo "<a href='/utmtrack/debug-campaigns.php' class='btn' style='background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);'>🔍 Debug Simples</a>";
echo "</div>";

echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>