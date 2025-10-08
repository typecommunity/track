<?php
/**
 * UTMTrack - Script de Verificação da Instalação Meta Ads
 * 
 * INSTRUÇÕES:
 * 1. Coloque este arquivo na raiz: /utmtrack/verificar_instalacao.php
 * 2. Acesse: http://ataweb.com.br/utmtrack/verificar_instalacao.php
 * 3. Verifique se todos os testes passaram
 * 4. DELETE este arquivo após usar!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carrega Database
require_once __DIR__ . '/core/Database.php';

// Array de resultados
$results = [];
$totalTests = 0;
$passedTests = 0;

// Função para registrar teste
function test($name, $condition, $errorMsg = '', $successMsg = '') {
    global $results, $totalTests, $passedTests;
    
    $totalTests++;
    
    if ($condition) {
        $passedTests++;
        $results[] = [
            'status' => 'success',
            'name' => $name,
            'message' => $successMsg ?: 'OK'
        ];
    } else {
        $results[] = [
            'status' => 'error',
            'name' => $name,
            'message' => $errorMsg ?: 'Falhou'
        ];
    }
}

// Começa os testes
echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Verificação de Instalação - UTMTrack</title>";
echo "<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 40px 20px;
    }
    .container {
        max-width: 1000px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    h1 {
        color: #667eea;
        font-size: 32px;
        margin-bottom: 10px;
        text-align: center;
    }
    .subtitle {
        text-align: center;
        color: #6b7280;
        margin-bottom: 40px;
        font-size: 16px;
    }
    .progress {
        background: #f3f4f6;
        height: 30px;
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 30px;
        position: relative;
    }
    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #059669);
        transition: width 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
    }
    .test-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 20px;
        margin-bottom: 10px;
        border-radius: 10px;
        border: 2px solid;
    }
    .test-item.success {
        background: #f0fdf4;
        border-color: #10b981;
    }
    .test-item.error {
        background: #fef2f2;
        border-color: #ef4444;
    }
    .test-icon {
        font-size: 24px;
        flex-shrink: 0;
    }
    .test-content {
        flex: 1;
    }
    .test-name {
        font-weight: 600;
        font-size: 15px;
        margin-bottom: 4px;
    }
    .test-item.success .test-name { color: #065f46; }
    .test-item.error .test-name { color: #991b1b; }
    .test-message {
        font-size: 13px;
        color: #6b7280;
    }
    .summary {
        margin-top: 40px;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
    }
    .summary.success {
        background: #f0fdf4;
        border: 2px solid #10b981;
    }
    .summary.warning {
        background: #fef3c7;
        border: 2px solid #f59e0b;
    }
    .summary.error {
        background: #fef2f2;
        border: 2px solid #ef4444;
    }
    .summary h2 {
        font-size: 24px;
        margin-bottom: 10px;
    }
    .summary.success h2 { color: #065f46; }
    .summary.warning h2 { color: #92400e; }
    .summary.error h2 { color: #991b1b; }
    .btn {
        display: inline-block;
        padding: 12px 30px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 600;
        margin-top: 20px;
        transition: all 0.3s;
    }
    .btn:hover {
        background: #5568d3;
        transform: translateY(-2px);
    }
    .warning-box {
        background: #fef3c7;
        border: 2px solid #f59e0b;
        border-radius: 10px;
        padding: 20px;
        margin-top: 30px;
        color: #92400e;
    }
    .warning-box strong {
        display: block;
        font-size: 16px;
        margin-bottom: 10px;
    }
    code {
        background: #f3f4f6;
        padding: 3px 8px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 13px;
    }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>🔍 Verificação de Instalação</h1>";
echo "<p class='subtitle'>Integração Meta Ads - UTMTrack</p>";

// TESTE 1: Verificar Database
try {
    $db = Database::getInstance();
    test(
        'Conexão com Banco de Dados',
        $db !== null,
        'Não foi possível conectar ao banco',
        'Conectado com sucesso'
    );
} catch (Exception $e) {
    test(
        'Conexão com Banco de Dados',
        false,
        'Erro: ' . $e->getMessage()
    );
}

// TESTE 2: Verificar tabela integration_configs
try {
    $tableExists = $db->fetch("SHOW TABLES LIKE 'integration_configs'");
    test(
        'Tabela integration_configs',
        $tableExists !== false,
        'Tabela não existe. Execute o SQL de criação.',
        'Tabela criada corretamente'
    );
} catch (Exception $e) {
    test('Tabela integration_configs', false, 'Erro ao verificar: ' . $e->getMessage());
}

// TESTE 3: Verificar campos em ad_accounts
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM ad_accounts LIKE 'access_token'");
    test(
        'Campos adicionais em ad_accounts',
        count($columns) > 0,
        'Campo access_token não existe. Execute o ALTER TABLE.',
        'Campos access_token e token_expires_at existem'
    );
} catch (Exception $e) {
    test('Campos em ad_accounts', false, 'Erro ao verificar: ' . $e->getMessage());
}

// TESTE 4: Verificar IntegrationController
$controllerPath = __DIR__ . '/app/controllers/IntegrationController.php';
test(
    'IntegrationController.php',
    file_exists($controllerPath),
    'Arquivo não encontrado em: app/controllers/',
    'Arquivo existe'
);

// TESTE 5: Verificar views
$viewsToCheck = [
    'index.php' => '/app/views/integrations/index.php',
    'meta.php' => '/app/views/integrations/meta.php',
    'meta_connect.php' => '/app/views/integrations/meta_connect.php',
    'meta_accounts.php' => '/app/views/integrations/meta_accounts.php'
];

$allViewsExist = true;
$missingViews = [];

foreach ($viewsToCheck as $name => $path) {
    if (!file_exists(__DIR__ . $path)) {
        $allViewsExist = false;
        $missingViews[] = $name;
    }
}

test(
    'Views de Integração',
    $allViewsExist,
    'Arquivos faltando: ' . implode(', ', $missingViews),
    'Todos os 4 arquivos de view existem'
);

// TESTE 6: Verificar meta_oauth.php
$oauthPath = __DIR__ . '/api/meta_oauth.php';
test(
    'API OAuth Handler',
    file_exists($oauthPath),
    'Arquivo api/meta_oauth.php não encontrado',
    'Handler OAuth existe'
);

// TESTE 7: Verificar Router.php atualizado
$routerContent = file_get_contents(__DIR__ . '/core/Router.php');
$hasIntegrationRoutes = strpos($routerContent, 'integracoes-meta') !== false;
test(
    'Rotas de Integração no Router',
    $hasIntegrationRoutes,
    'Router.php não contém as novas rotas. Substitua o arquivo.',
    'Router.php atualizado com as rotas'
);

// TESTE 8: Verificar sidebar atualizado
$sidebarPath = __DIR__ . '/app/views/layouts/sidebar.php';
if (file_exists($sidebarPath)) {
    $sidebarContent = file_get_contents($sidebarPath);
    $hasIntegrationMenu = strpos($sidebarContent, 'integracoes') !== false;
    test(
        'Menu Integrações no Sidebar',
        $hasIntegrationMenu,
        'Sidebar não contém menu Integrações. Atualize o arquivo.',
        'Menu Integrações adicionado'
    );
} else {
    test('Menu Integrações no Sidebar', false, 'Arquivo sidebar.php não encontrado');
}

// TESTE 9: Verificar permissões de escrita
$writableCheck = is_writable(__DIR__ . '/api');
test(
    'Permissões de Escrita',
    $writableCheck,
    'Diretório /api não tem permissão de escrita',
    'Permissões corretas'
);

// TESTE 10: Verificar módulos PHP necessários
$hasCurl = function_exists('curl_init');
$hasJson = function_exists('json_encode');
$hasOpenSSL = extension_loaded('openssl');

test(
    'Extensões PHP (cURL)',
    $hasCurl,
    'Extensão cURL não está habilitada',
    'cURL disponível'
);

test(
    'Extensões PHP (JSON)',
    $hasJson,
    'Extensão JSON não está habilitada',
    'JSON disponível'
);

test(
    'Extensões PHP (OpenSSL)',
    $hasOpenSSL,
    'Extensão OpenSSL não está habilitada',
    'OpenSSL disponível'
);

// Calcula porcentagem
$percentage = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;

// Mostra barra de progresso
echo "<div class='progress'>";
echo "<div class='progress-bar' style='width: {$percentage}%'>";
echo "{$passedTests}/{$totalTests} testes passaram";
echo "</div>";
echo "</div>";

// Mostra resultados
foreach ($results as $result) {
    echo "<div class='test-item {$result['status']}'>";
    echo "<div class='test-icon'>" . ($result['status'] === 'success' ? '✅' : '❌') . "</div>";
    echo "<div class='test-content'>";
    echo "<div class='test-name'>{$result['name']}</div>";
    echo "<div class='test-message'>{$result['message']}</div>";
    echo "</div>";
    echo "</div>";
}

// Resumo final
echo "<div class='summary " . ($percentage === 100 ? 'success' : ($percentage >= 80 ? 'warning' : 'error')) . "'>";

if ($percentage === 100) {
    echo "<h2>🎉 Instalação Completa!</h2>";
    echo "<p>Todos os testes passaram. O sistema está pronto para usar.</p>";
    echo "<a href='public/index.php?page=integracoes' class='btn'>Acessar Integrações</a>";
} elseif ($percentage >= 80) {
    echo "<h2>⚠️ Quase Lá!</h2>";
    echo "<p>Alguns testes falharam, mas o sistema deve funcionar. Corrija os erros acima.</p>";
} else {
    echo "<h2>❌ Instalação Incompleta</h2>";
    echo "<p>Vários testes falharam. Revise o guia de instalação e corrija os erros.</p>";
}

echo "<p style='margin-top: 20px; font-size: 14px; color: #6b7280;'>";
echo "Testes executados: {$totalTests} | Sucessos: {$passedTests} | Falhas: " . ($totalTests - $passedTests);
echo "</p>";
echo "</div>";

// Aviso para deletar o arquivo
echo "<div class='warning-box'>";
echo "<strong>⚠️ IMPORTANTE: Delete este arquivo após a verificação!</strong>";
echo "<p style='margin-top: 10px;'>Este script expõe informações sensíveis do sistema.</p>";
echo "<p style='margin-top: 5px;'>Comando SSH: <code>rm " . __FILE__ . "</code></p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";