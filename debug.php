<?php
/**
 * ========================================
 * UTMTRACK - DEBUG AUTOMÁTICO V2.1
 * ========================================
 * COMO USAR:
 * 1. Faça upload deste arquivo para /public/debug-utmtrack.php
 * 2. Acesse: https://seusite.com/utmtrack/public/debug-utmtrack.php
 * 3. Envie o resultado para análise
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORREÇÃO: Detecta estrutura correta
// O arquivo pode estar em:
// 1. /utmtrack/debug.php → baseDir = __DIR__
// 2. /utmtrack/public/debug.php → baseDir = dirname(__DIR__)
$currentDir = __DIR__;

// Verifica se core/Database.php existe no diretório atual
if (file_exists($currentDir . '/core/Database.php')) {
    $baseDir = $currentDir; // Arquivo na raiz do utmtrack
} else {
    $baseDir = dirname($currentDir); // Arquivo em subpasta
}

// Debug da estrutura
$structureInfo = [
    'current_file' => __FILE__,
    'current_dir' => $currentDir,
    'base_dir' => $baseDir,
    'detected_structure' => file_exists($baseDir . '/core/Database.php') ? 'CORRETA' : 'INCORRETA'
];

$results = [];
$errors = [];

// CSS para deixar bonito
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTMTrack - Debug Automático</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0e1a 0%, #1a1f2e 100%);
            color: #e4e6eb;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #3b82f6;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
        .test {
            background: rgba(26, 31, 46, 0.8);
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .test h2 {
            color: #3b82f6;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        .success { 
            border-left-color: #10b981; 
            background: rgba(16, 185, 129, 0.1);
        }
        .success h2 { color: #10b981; }
        .error { 
            border-left-color: #ef4444; 
            background: rgba(239, 68, 68, 0.1);
        }
        .error h2 { color: #ef4444; }
        .warning { 
            border-left-color: #f59e0b; 
            background: rgba(245, 158, 11, 0.1);
        }
        .warning h2 { color: #f59e0b; }
        .code {
            background: #0a0e1a;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 10px 0;
            border: 1px solid #2a2f3e;
            font-size: 0.9em;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge-success { background: #10b981; color: white; }
        .badge-error { background: #ef4444; color: white; }
        .badge-warning { background: #f59e0b; color: white; }
        .summary {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid #3b82f6;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        .summary h2 { color: #3b82f6; margin-bottom: 15px; }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        .stat {
            text-align: center;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
        }
        .stat-label {
            color: #8b92a4;
            font-size: 0.9em;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .copy-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin: 10px 5px;
            font-family: inherit;
        }
        .copy-btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 UTMTrack - Debug Automático v2.1</h1>
        
        <?php
        // ========================================
        // TESTE 1: Arquivos Principais
        // ========================================
        $test1 = [
            'name' => 'Arquivos Principais',
            'status' => 'success',
            'checks' => []
        ];
        
        $files = [
            'Database' => $baseDir . '/core/Database.php',
            'Controller Base' => $baseDir . '/core/Controller.php',
            'Config Database' => $baseDir . '/config/database.php',
            'Campaign Controller' => $baseDir . '/app/controllers/CampaignController.php',
            'Campaign Controller V2' => $baseDir . '/app/controllers/CampaignControllerV2.php',
            'MetaAdsSync' => $baseDir . '/core/MetaAdsSync.php',
            'Index View' => $baseDir . '/app/views/campaigns/index.php',
            'JS Dashboard' => $baseDir . '/assets/js/utmtrack-dashboard-v2.js',
            'CSS Dashboard' => $baseDir . '/assets/css/utmtrack-dashboard-v2.css'
        ];
        
        foreach ($files as $name => $path) {
            $exists = file_exists($path);
            $test1['checks'][] = [
                'name' => $name,
                'status' => $exists ? 'success' : 'error',
                'message' => $exists ? "✅ Encontrado: $path" : "❌ NÃO encontrado: $path"
            ];
            if (!$exists) $test1['status'] = 'error';
        }
        
        $results[] = $test1;
        
        // ========================================
        // TESTE 2: Banco de Dados
        // ========================================
        $test2 = [
            'name' => 'Conexão com Banco de Dados',
            'status' => 'error',
            'checks' => []
        ];
        
        try {
            // Tenta carregar config do lugar correto
            $configFile = $baseDir . '/config/database.php';
            
            if (!file_exists($configFile)) {
                $configFile = $baseDir . '/core/Config.php';
            }
            
            if (file_exists($configFile)) {
                require_once $configFile;
                
                // Detecta tipo de config
                if (file_exists($baseDir . '/config/database.php')) {
                    // Config em array
                    $dbConfig = require $baseDir . '/config/database.php';
                    $config = $dbConfig;
                    
                    $test2['checks'][] = [
                        'name' => 'Config',
                        'status' => 'success',
                        'message' => "✅ Arquivo config/database.php carregado"
                    ];
                } else {
                    // Config em classe
                    $config = Config::get('database');
                    
                    $test2['checks'][] = [
                        'name' => 'Config',
                        'status' => 'success',
                        'message' => "✅ Arquivo core/Config.php carregado"
                    ];
                }
                
                // Tenta conectar
                $host = $config['host'] ?? 'localhost';
                $database = $config['database'] ?? $config['dbname'] ?? '';
                $username = $config['username'] ?? $config['user'] ?? '';
                $password = $config['password'] ?? '';
                
                $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                $test2['checks'][] = [
                    'name' => 'Conexão',
                    'status' => 'success',
                    'message' => "✅ Conectado ao banco: {$database}"
                ];
                
                // Verifica tabela user_preferences
                $stmt = $pdo->query("SHOW TABLES LIKE 'user_preferences'");
                if ($stmt->rowCount() > 0) {
                    $test2['checks'][] = [
                        'name' => 'Tabela user_preferences',
                        'status' => 'success',
                        'message' => "✅ Tabela existe"
                    ];
                    
                    // Verifica estrutura
                    $stmt = $pdo->query("DESCRIBE user_preferences");
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $requiredColumns = ['id', 'user_id', 'preference_key', 'preference_value'];
                    $hasAll = true;
                    foreach ($requiredColumns as $col) {
                        if (!in_array($col, $columns)) {
                            $hasAll = false;
                            $test2['checks'][] = [
                                'name' => "Coluna $col",
                                'status' => 'error',
                                'message' => "❌ Coluna faltando: $col"
                            ];
                        }
                    }
                    
                    if ($hasAll) {
                        $test2['checks'][] = [
                            'name' => 'Estrutura da tabela',
                            'status' => 'success',
                            'message' => "✅ Todas as colunas necessárias existem: " . implode(', ', $columns)
                        ];
                    }
                    
                    // Verifica dados
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_preferences WHERE preference_key = 'campaign_columns'");
                    $count = $stmt->fetch()['total'];
                    
                    $test2['checks'][] = [
                        'name' => 'Dados salvos',
                        'status' => $count > 0 ? 'success' : 'warning',
                        'message' => $count > 0 ? "✅ Existem $count preferência(s) salva(s)" : "⚠️ Nenhuma preferência salva ainda"
                    ];
                    
                    // Mostra último registro
                    if ($count > 0) {
                        $stmt = $pdo->query("SELECT * FROM user_preferences WHERE preference_key = 'campaign_columns' ORDER BY id DESC LIMIT 1");
                        $lastPref = $stmt->fetch();
                        $test2['checks'][] = [
                            'name' => 'Última preferência',
                            'status' => 'success',
                            'message' => "📊 User ID: {$lastPref['user_id']} | Colunas: " . substr($lastPref['preference_value'], 0, 100)
                        ];
                    }
                    
                } else {
                    $test2['checks'][] = [
                        'name' => 'Tabela user_preferences',
                        'status' => 'error',
                        'message' => "❌ Tabela NÃO existe! Execute o SQL de criação."
                    ];
                }
                
                $test2['status'] = 'success';
                
            } else {
                $test2['checks'][] = [
                    'name' => 'Config',
                    'status' => 'error',
                    'message' => "❌ Arquivo Config.php não encontrado"
                ];
            }
            
        } catch (Exception $e) {
            $test2['checks'][] = [
                'name' => 'Erro',
                'status' => 'error',
                'message' => "❌ Erro: " . $e->getMessage()
            ];
        }
        
        $results[] = $test2;
        
        // ========================================
        // TESTE 3: Sessão
        // ========================================
        $test3 = [
            'name' => 'Sessão do Usuário',
            'status' => 'success',
            'checks' => []
        ];
        
        if (isset($_SESSION['user_id'])) {
            $test3['checks'][] = [
                'name' => 'User ID',
                'status' => 'success',
                'message' => "✅ Logado como User ID: " . $_SESSION['user_id']
            ];
        } else {
            $test3['checks'][] = [
                'name' => 'User ID',
                'status' => 'error',
                'message' => "❌ NÃO está logado! Faça login primeiro."
            ];
            $test3['status'] = 'error';
        }
        
        $test3['checks'][] = [
            'name' => 'Sessão ativa',
            'status' => 'success',
            'message' => "✅ Session ID: " . session_id()
        ];
        
        $results[] = $test3;
        
        // ========================================
        // TESTE 4: AJAX Endpoint
        // ========================================
        $test4 = [
            'name' => 'Endpoint AJAX',
            'status' => 'success',
            'checks' => []
        ];
        
        $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                      "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        
        $test4['checks'][] = [
            'name' => 'URL Atual',
            'status' => 'success',
            'message' => "📍 $currentUrl"
        ];
        
        // Verifica index.php na view
        if (isset($pdo)) {
            $viewFile = $baseDir . '/app/views/campaigns/index.php';
            if (file_exists($viewFile)) {
                $content = file_get_contents($viewFile);
                
                // Verifica se tem handler AJAX
                if (strpos($content, "isset(\$_GET['ajax_action'])") !== false) {
                    $test4['checks'][] = [
                        'name' => 'Handler AJAX na View',
                        'status' => 'success',
                        'message' => "✅ Handler AJAX encontrado no index.php"
                    ];
                } else {
                    $test4['checks'][] = [
                        'name' => 'Handler AJAX na View',
                        'status' => 'warning',
                        'message' => "⚠️ Handler AJAX NÃO encontrado no index.php"
                    ];
                }
                
                // Verifica se carrega userColumns
                if (strpos($content, '$userColumns') !== false) {
                    $test4['checks'][] = [
                        'name' => 'Variável $userColumns',
                        'status' => 'success',
                        'message' => "✅ Variável \$userColumns encontrada"
                    ];
                } else {
                    $test4['checks'][] = [
                        'name' => 'Variável $userColumns',
                        'status' => 'error',
                        'message' => "❌ Variável \$userColumns NÃO encontrada!"
                    ];
                    $test4['status'] = 'error';
                }
            }
        }
        
        $results[] = $test4;
        
        // ========================================
        // TESTE 5: Teste de Salvamento Real
        // ========================================
        if (isset($pdo) && isset($_SESSION['user_id'])) {
            $test5 = [
                'name' => 'Teste de Salvamento',
                'status' => 'success',
                'checks' => []
            ];
            
            try {
                $userId = $_SESSION['user_id'];
                $testColumns = json_encode(['checkbox', 'nome', 'status', 'vendas', 'faturamento', 'roas']);
                
                // Tenta inserir
                $stmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, preference_key, preference_value)
                    VALUES (:user_id, :key, :value)
                    ON DUPLICATE KEY UPDATE preference_value = :value
                ");
                
                $stmt->execute([
                    'user_id' => $userId,
                    'key' => 'campaign_columns_test',
                    'value' => $testColumns
                ]);
                
                $test5['checks'][] = [
                    'name' => 'INSERT',
                    'status' => 'success',
                    'message' => "✅ Conseguiu inserir dados de teste"
                ];
                
                // Tenta recuperar
                $stmt = $pdo->prepare("
                    SELECT preference_value 
                    FROM user_preferences 
                    WHERE user_id = :user_id AND preference_key = :key
                ");
                $stmt->execute([
                    'user_id' => $userId,
                    'key' => 'campaign_columns_test'
                ]);
                
                $result = $stmt->fetch();
                
                if ($result && $result['preference_value'] === $testColumns) {
                    $test5['checks'][] = [
                        'name' => 'SELECT',
                        'status' => 'success',
                        'message' => "✅ Conseguiu recuperar dados: " . $result['preference_value']
                    ];
                } else {
                    $test5['checks'][] = [
                        'name' => 'SELECT',
                        'status' => 'error',
                        'message' => "❌ Dados recuperados não batem!"
                    ];
                    $test5['status'] = 'error';
                }
                
                // Limpa teste
                $pdo->prepare("DELETE FROM user_preferences WHERE preference_key = 'campaign_columns_test'")->execute();
                
            } catch (Exception $e) {
                $test5['checks'][] = [
                    'name' => 'Erro',
                    'status' => 'error',
                    'message' => "❌ " . $e->getMessage()
                ];
                $test5['status'] = 'error';
            }
            
            $results[] = $test5;
        }
        
        // ========================================
        // CALCULA ESTATÍSTICAS
        // ========================================
        $totalTests = 0;
        $successTests = 0;
        $errorTests = 0;
        $warningTests = 0;
        
        foreach ($results as $test) {
            foreach ($test['checks'] as $check) {
                $totalTests++;
                if ($check['status'] === 'success') $successTests++;
                if ($check['status'] === 'error') $errorTests++;
                if ($check['status'] === 'warning') $warningTests++;
            }
        }
        
        $overallStatus = $errorTests > 0 ? 'error' : ($warningTests > 0 ? 'warning' : 'success');
        ?>
        
        <!-- RESUMO -->
        <div class="summary">
            <h2>📊 Resumo do Diagnóstico</h2>
            
            <!-- INFO DA ESTRUTURA -->
            <div class="code" style="margin-bottom: 20px; text-align: left;">
                <strong>🗂️ Estrutura Detectada:</strong><br>
                Arquivo atual: <?= $structureInfo['current_file'] ?><br>
                Diretório base: <?= $structureInfo['base_dir'] ?><br>
                Status: <span class="badge badge-<?= $structureInfo['detected_structure'] === 'CORRETA' ? 'success' : 'error' ?>">
                    <?= $structureInfo['detected_structure'] ?>
                </span>
            </div>
            
            <div class="stats">
                <div class="stat">
                    <div class="stat-number" style="color: #10b981;"><?= $successTests ?></div>
                    <div class="stat-label">Testes OK</div>
                </div>
                <div class="stat">
                    <div class="stat-number" style="color: #ef4444;"><?= $errorTests ?></div>
                    <div class="stat-label">Erros</div>
                </div>
                <div class="stat">
                    <div class="stat-number" style="color: #f59e0b;"><?= $warningTests ?></div>
                    <div class="stat-label">Avisos</div>
                </div>
            </div>
            <div style="margin-top: 20px; font-size: 1.2em;">
                Status Geral: 
                <?php if ($overallStatus === 'success'): ?>
                    <span class="badge badge-success">✅ TUDO OK</span>
                <?php elseif ($overallStatus === 'warning'): ?>
                    <span class="badge badge-warning">⚠️ COM AVISOS</span>
                <?php else: ?>
                    <span class="badge badge-error">❌ COM ERROS</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- RESULTADOS DETALHADOS -->
        <?php foreach ($results as $test): ?>
        <div class="test <?= $test['status'] ?>">
            <h2>
                <?php if ($test['status'] === 'success'): ?>
                    ✅
                <?php elseif ($test['status'] === 'warning'): ?>
                    ⚠️
                <?php else: ?>
                    ❌
                <?php endif; ?>
                <?= $test['name'] ?>
                <span class="badge badge-<?= $test['status'] ?>">
                    <?= strtoupper($test['status']) ?>
                </span>
            </h2>
            
            <?php foreach ($test['checks'] as $check): ?>
            <div class="code">
                <strong><?= $check['name'] ?>:</strong><br>
                <?= $check['message'] ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        
        <!-- AÇÕES -->
        <div class="test">
            <h2>🎯 Próximos Passos</h2>
            <div class="code">
                <?php if ($errorTests === 0): ?>
                    <strong style="color: #10b981;">✅ Sistema parece estar OK!</strong><br><br>
                    Se as colunas ainda não funcionam, o problema pode ser:<br>
                    1. Cache do navegador (Ctrl+Shift+R)<br>
                    2. JavaScript não está carregando<br>
                    3. AJAX indo para URL errada<br><br>
                    <strong>👉 Cole a URL desta página + os resultados no chat!</strong>
                <?php else: ?>
                    <strong style="color: #ef4444;">❌ Encontrados <?= $errorTests ?> erro(s)</strong><br><br>
                    Corrija os erros acima e execute novamente.<br><br>
                    <strong>👉 Envie print desta página para análise!</strong>
                <?php endif; ?>
            </div>
            
            <button class="copy-btn" onclick="copyResults()">📋 Copiar Todos os Resultados</button>
            <button class="copy-btn" onclick="window.location.reload()">🔄 Executar Novamente</button>
        </div>
        
        <div style="text-align: center; margin-top: 30px; color: #8b92a4;">
            <p>UTMTrack Debug Automático v2.1</p>
            <p>Gerado em: <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
    
    <script>
        function copyResults() {
            const text = document.body.innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ Resultados copiados! Cole no chat.');
            });
        }
        
        console.log('%c🔍 DEBUG COMPLETO EXECUTADO', 'color: #10b981; font-size: 20px; font-weight: bold;');
        console.log('Resultados:', <?= json_encode($results) ?>);
    </script>
</body>
</html>