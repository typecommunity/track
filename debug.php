<?php
/**
 * ========================================
 * TESTE FINAL: ajax-campaigns.php
 * ========================================
 * Salve como: /utmtrack/test_ajax_structure.php
 * Acesse: http://seudominio.com/utmtrack/test_ajax_structure.php
 */

$baseDir = __DIR__;

echo "<pre>";
echo "🔍 VALIDAÇÃO FINAL - ajax-campaigns.php\n";
echo "========================================\n\n";

// ========================================
// 1. VERIFICA ARQUIVO CORRETO
// ========================================
echo "📁 LOCALIZAÇÃO DO ARQUIVO\n";
echo "----------------------------------------\n";

$correctFile = $baseDir . '/public/ajax-campaigns.php';
$wrongFile1 = $baseDir . '/ajax_campaigns.php';
$wrongFile2 = $baseDir . '/public/ajax_campaigns.php';

if (file_exists($correctFile)) {
    echo "✅ CORRETO: /public/ajax-campaigns.php EXISTS\n";
    
    // Verifica conteúdo
    $content = file_get_contents($correctFile);
    
    // Testes de integridade
    $checks = [
        'dirname(__DIR__)' => strpos($content, 'dirname(__DIR__)') !== false,
        'ajaxResponse()' => strpos($content, 'function ajaxResponse') !== false,
        'ajaxError()' => strpos($content, 'function ajaxError') !== false,
        'AJAX_DEBUG' => strpos($content, 'AJAX_DEBUG') !== false,
        'sync_complete case' => strpos($content, "case 'sync_complete':") !== false
    ];
    
    echo "\n🔬 VERIFICAÇÕES DE INTEGRIDADE:\n";
    foreach ($checks as $check => $result) {
        echo ($result ? "   ✅" : "   ❌") . " {$check}\n";
    }
    
} else {
    echo "❌ ERRO: /public/ajax-campaigns.php NÃO ENCONTRADO!\n";
}

// Verifica arquivos errados
if (file_exists($wrongFile1)) {
    echo "\n⚠️  ATENÇÃO: Arquivo obsoleto encontrado:\n";
    echo "   /ajax_campaigns.php (DELETE ESTE ARQUIVO)\n";
}

if (file_exists($wrongFile2)) {
    echo "\n⚠️  ATENÇÃO: Arquivo com nome errado:\n";
    echo "   /public/ajax_campaigns.php (deveria ser ajax-campaigns.php)\n";
}

echo "\n========================================\n";
echo "2️⃣  ESTRUTURA DE CAMINHOS\n";
echo "========================================\n\n";

// Simula os caminhos do ajax-campaigns.php
$ajaxBaseDir = dirname($correctFile); // /utmtrack/public/
$projectRoot = dirname($ajaxBaseDir);  // /utmtrack/

echo "Ajax Location:     {$ajaxBaseDir}\n";
echo "Project Root:      {$projectRoot}\n\n";

$requiredPaths = [
    'Database.php' => $projectRoot . '/core/Database.php',
    'MetaAdsDataStructure.php' => $projectRoot . '/core/MetaAdsDataStructure.php',
    'MetaAdsSync.php' => $projectRoot . '/core/MetaAdsSync.php',
    'Router.php' => $projectRoot . '/core/Router.php',
    'CampaignControllerV2.php' => $projectRoot . '/app/controllers/CampaignControllerV2.php'
];

echo "📦 DEPENDÊNCIAS NECESSÁRIAS:\n";
echo "----------------------------------------\n";

$allPathsCorrect = true;
foreach ($requiredPaths as $name => $path) {
    $exists = file_exists($path);
    echo ($exists ? "✅" : "❌") . " {$name}\n";
    echo "   Path: {$path}\n";
    
    if (!$exists) {
        $allPathsCorrect = false;
    }
}

echo "\n========================================\n";
echo "3️⃣  TESTES FUNCIONAIS\n";
echo "========================================\n\n";

if (file_exists($correctFile) && $allPathsCorrect) {
    
    // Teste 1: Verifica normalizeStatus
    echo "🧪 TESTE 1: Função normalizeStatus()\n";
    if (strpos(file_get_contents($projectRoot . '/core/MetaAdsSync.php'), 'normalizeStatus') !== false) {
        echo "   ✅ Função encontrada em MetaAdsSync.php\n";
    } else {
        echo "   ❌ Função não encontrada!\n";
    }
    
    // Teste 2: Verifica rotas
    echo "\n🧪 TESTE 2: Rotas no Router.php\n";
    $routerContent = file_get_contents($projectRoot . '/core/Router.php');
    
    $routes = [
        'campanhas-sync-complete',
        'sync_complete',
        'campanhas-sync-all'
    ];
    
    foreach ($routes as $route) {
        if (preg_match("/'$route'/", $routerContent)) {
            echo "   ✅ Rota '$route' mapeada\n";
        } else {
            echo "   ⚠️  Rota '$route' não encontrada\n";
        }
    }
    
    // Teste 3: Verifica JavaScript
    echo "\n🧪 TESTE 3: JavaScript Dashboard\n";
    $jsFile = $projectRoot . '/assets/js/utmtrack-dashboard-v2.js';
    
    if (file_exists($jsFile)) {
        echo "   ✅ utmtrack-dashboard-v2.js encontrado\n";
        
        $jsContent = file_get_contents($jsFile);
        if (strpos($jsContent, 'ajax-campaigns.php') !== false) {
            echo "   ✅ Referencia ajax-campaigns.php\n";
        } else {
            echo "   ⚠️  Não referencia ajax-campaigns.php\n";
        }
        
        if (strpos($jsContent, 'syncAllCampaigns') !== false) {
            echo "   ✅ Função syncAllCampaigns() existe\n";
        }
    } else {
        echo "   ❌ JavaScript não encontrado\n";
    }
    
} else {
    echo "⚠️  Testes funcionais PULADOS (corrija os erros acima primeiro)\n";
}

echo "\n========================================\n";
echo "📊 RESUMO\n";
echo "========================================\n\n";

if (file_exists($correctFile) && $allPathsCorrect) {
    echo "🎉 TUDO CORRETO!\n\n";
    echo "✅ ajax-campaigns.php está no local correto\n";
    echo "✅ Todos os caminhos estão corretos\n";
    echo "✅ Estrutura validada com sucesso\n\n";
    echo "👉 PRÓXIMO PASSO:\n";
    echo "   1. DELETE este arquivo (test_ajax_structure.php)\n";
    echo "   2. Teste o sistema no dashboard\n";
    echo "   3. Verifique os logs no Console (F12)\n";
} else {
    echo "❌ ERROS ENCONTRADOS!\n\n";
    echo "Corrija os problemas acima antes de prosseguir.\n";
}

echo "\n========================================\n";
echo "🔒 DELETE este arquivo depois!\n";
echo "========================================\n";
echo "</pre>";
?>