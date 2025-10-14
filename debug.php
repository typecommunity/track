<?php
/**
 * ========================================
 * TESTE SUPER BÁSICO
 * CAMINHO: /utmtrack/test_basic.php
 * ========================================
 * 
 * Acesse: https://ataweb.com.br/utmtrack/test_basic.php
 */

// Ativa exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Teste Básico PHP</title>
    <style>
        body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 20px; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        pre { background: #1e293b; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
<h1>🧪 Teste Básico PHP - UTMTrack</h1>
";

// Teste 1: PHP Funcionando
echo "<h2 class='success'>✅ 1. PHP está funcionando</h2>";
echo "<p>Versão do PHP: <strong>" . PHP_VERSION . "</strong></p>";

// Teste 2: Sessão
echo "<h2>2. Teste de Sessão</h2>";
try {
    session_start();
    echo "<p class='success'>✅ Session iniciada</p>";
    echo "<p>Session ID: <strong>" . session_id() . "</strong></p>";
    echo "<p>Session Status: <strong>" . session_status() . "</strong></p>";
    
    if (isset($_SESSION['user_id'])) {
        echo "<p class='success'>✅ Usuário logado: <strong>" . $_SESSION['user_id'] . "</strong></p>";
    } else {
        echo "<p class='error'>❌ Usuário NÃO está logado</p>";
        echo "<p>Faça login em: <a href='/utmtrack/index.php' style='color: #667eea;'>index.php</a></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro na sessão: " . $e->getMessage() . "</p>";
}

// Teste 3: Caminhos de arquivo
echo "<h2>3. Teste de Caminhos</h2>";
echo "<p>__FILE__: <strong>" . __FILE__ . "</strong></p>";
echo "<p>__DIR__: <strong>" . __DIR__ . "</strong></p>";
echo "<p>DOCUMENT_ROOT: <strong>" . $_SERVER['DOCUMENT_ROOT'] . "</strong></p>";

$paths = [
    __DIR__ . '/core/Database.php',
    __DIR__ . '/Core/Database.php',
];

echo "<h3>Procurando Database.php:</h3>";
foreach ($paths as $path) {
    $exists = file_exists($path);
    echo "<p>";
    echo $exists ? "✅" : "❌";
    echo " <strong>" . $path . "</strong>";
    echo $exists ? " (EXISTE)" : " (NÃO EXISTE)";
    echo "</p>";
    
    if ($exists) {
        echo "<p class='success'>🎯 Arquivo encontrado!</p>";
        break;
    }
}

// Teste 4: Database
echo "<h2>4. Teste de Database</h2>";
try {
    $dbPath = null;
    foreach ($paths as $path) {
        if (file_exists($path)) {
            $dbPath = $path;
            break;
        }
    }
    
    if ($dbPath) {
        require_once $dbPath;
        echo "<p class='success'>✅ Database.php carregado: $dbPath</p>";
        
        // Tenta instanciar
        $db = Database::getInstance();
        echo "<p class='success'>✅ Database::getInstance() funcionou!</p>";
        
        // Testa query simples
        if (isset($_SESSION['user_id'])) {
            $campaigns = $db->fetchAll("SELECT id, campaign_name FROM campaigns WHERE user_id = :user_id LIMIT 3", [
                'user_id' => $_SESSION['user_id']
            ]);
            
            echo "<p class='success'>✅ Query executada! Encontradas <strong>" . count($campaigns) . "</strong> campanhas</p>";
            
            if (count($campaigns) > 0) {
                echo "<pre>";
                print_r($campaigns);
                echo "</pre>";
            }
        } else {
            echo "<p class='info'>ℹ️ Faça login para testar queries</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Database.php NÃO encontrado em nenhum caminho</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no Database: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Teste 5: JSON
echo "<h2>5. Teste de JSON</h2>";
$testArray = [
    'success' => true,
    'message' => 'Teste de JSON funcionando',
    'timestamp' => date('Y-m-d H:i:s')
];

echo "<p>Array PHP:</p>";
echo "<pre>" . print_r($testArray, true) . "</pre>";

$json = json_encode($testArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "<p>JSON codificado:</p>";
echo "<pre>" . htmlspecialchars($json) . "</pre>";

// Teste 6: Teste UPDATE (se logado)
if (isset($_SESSION['user_id']) && isset($db)) {
    echo "<h2>6. Teste de UPDATE</h2>";
    
    try {
        // Busca primeira campanha
        $campaign = $db->fetch("SELECT id, campaign_name FROM campaigns WHERE user_id = :user_id LIMIT 1", [
            'user_id' => $_SESSION['user_id']
        ]);
        
        if ($campaign) {
            echo "<p class='info'>Campanha encontrada: ID={$campaign['id']}, Nome={$campaign['campaign_name']}</p>";
            
            // Tenta atualizar (adiciona [TESTE] no nome)
            $newName = $campaign['campaign_name'] . ' [TESTE]';
            
            $affected = $db->update('campaigns',
                ['campaign_name' => $newName],
                'id = :id AND user_id = :user_id',
                ['id' => $campaign['id'], 'user_id' => $_SESSION['user_id']]
            );
            
            echo "<p class='success'>✅ UPDATE executado! Linhas afetadas: <strong>$affected</strong></p>";
            
            // Verifica se atualizou
            $updated = $db->fetch("SELECT id, campaign_name FROM campaigns WHERE id = :id", [
                'id' => $campaign['id']
            ]);
            
            echo "<p>Nome após update: <strong>{$updated['campaign_name']}</strong></p>";
            
            // Reverte
            $db->update('campaigns',
                ['campaign_name' => $campaign['campaign_name']],
                'id = :id',
                ['id' => $campaign['id']]
            );
            
            echo "<p class='success'>✅ Nome revertido para o original</p>";
            
        } else {
            echo "<p class='error'>❌ Nenhuma campanha encontrada para testar</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro no teste de UPDATE: " . $e->getMessage() . "</p>";
    }
}

echo "<hr><p style='color: #64748b;'>Fim dos testes</p>";
echo "</body></html>";