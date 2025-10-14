<?php
/**
 * Script de Debug - Coloque na raiz do projeto
 * Acesse: http://seudominio.com/debug.php
 */

echo "<h1>Debug UTMTrack</h1>";

echo "<h2>1. Verificando Estrutura de Pastas</h2>";
$folders = [
    'core',
    'app/controllers',
    'app/views/utms',
    'public/api',
    'public/js',
    'cron'
];

foreach ($folders as $folder) {
    $exists = is_dir($folder);
    $icon = $exists ? '✅' : '❌';
    echo "<p>{$icon} {$folder}: " . ($exists ? 'OK' : 'NÃO EXISTE') . "</p>";
}

echo "<h2>2. Verificando Arquivos</h2>";
$files = [
    'core/Database.php',
    'core/FacebookCapi.php',
    'app/controllers/UtmController.php',
    'app/views/utms/index.php',
    'app/views/utms/setup.php',
    'app/views/utms/scripts.php',
    'public/api/capi-events.php',
    'public/js/capi-tracker.js',
    'cron/process-capi-events.php'
];

foreach ($files as $file) {
    $exists = file_exists($file);
    $icon = $exists ? '✅' : '❌';
    echo "<p>{$icon} {$file}: " . ($exists ? 'OK' : 'NÃO EXISTE') . "</p>";
}

echo "<h2>3. Verificando Banco de Dados</h2>";

// Tenta carregar config
$configFile = 'config/database.php';
if (file_exists($configFile)) {
    echo "<p>✅ config/database.php existe</p>";
    
    $dbConfig = require $configFile;
    
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        echo "<p>✅ Conexão com banco de dados: OK</p>";
        
        // Verifica tabelas
        $tables = ['pixels', 'capi_configs', 'capi_events', 'capi_logs', 'users'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            $exists = $stmt->rowCount() > 0;
            $icon = $exists ? '✅' : '❌';
            echo "<p>{$icon} Tabela '{$table}': " . ($exists ? 'OK' : 'NÃO EXISTE') . "</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p>❌ Erro na conexão: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ config/database.php não encontrado</p>";
}

echo "<h2>4. Verificando $config</h2>";

// Simula carregamento do Controller
if (file_exists('core/Controller.php')) {
    require_once 'core/Controller.php';
    
    class TestController extends Controller {
        public function getConfig() {
            return $this->config;
        }
    }
    
    try {
        $test = new TestController();
        $config = $test->getConfig();
        
        echo "<p>✅ \$config carregado:</p>";
        echo "<pre>";
        print_r($config);
        echo "</pre>";
        
        if (isset($config['base_url'])) {
            echo "<p>✅ base_url: {$config['base_url']}</p>";
        } else {
            echo "<p>❌ base_url não definido!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Erro ao carregar Controller: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ core/Controller.php não encontrado</p>";
}

echo "<h2>5. Links de Teste</h2>";
echo '<p><a href="index.php?page=utms">index.php?page=utms</a></p>';
echo '<p><a href="index.php?page=utms&action=setup">index.php?page=utms&action=setup</a></p>';
echo '<p><a href="index.php?page=utms&action=scripts">index.php?page=utms&action=scripts</a></p>';

echo "<h2>6. PHP Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Extensions:</p>";
echo "<ul>";
$extensions = ['pdo', 'pdo_mysql', 'curl', 'json'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $icon = $loaded ? '✅' : '❌';
    echo "<li>{$icon} {$ext}: " . ($loaded ? 'OK' : 'NÃO CARREGADO') . "</li>";
}
echo "</ul>";