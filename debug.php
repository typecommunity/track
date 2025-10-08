<?php
/**
 * UTMTrack - Script para Criar Usuários
 * Salve como: /utmtrack/create_users.php
 * Acesse: http://ataweb.com.br/utmtrack/create_users.php
 * DELETE ESTE ARQUIVO APÓS USAR!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carrega Database
require_once __DIR__ . '/core/Database.php';

try {
    $db = Database::getInstance();
    
    echo "<html>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>Criar Usuários - UTMTrack</title>";
    echo "<style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #667eea; 
            margin-bottom: 30px;
            text-align: center;
        }
        .result {
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
        }
        .success {
            background: #f0fdf4;
            border: 2px solid #10b981;
            color: #065f46;
        }
        .error {
            background: #fef2f2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        .info {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            color: #1e40af;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        .credential {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: monospace;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
        }
        .warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            color: #92400e;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            font-weight: bold;
        }
    </style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='container'>";
    echo "<h1>🎯 Criar Usuários - UTMTrack</h1>";
    
    // Verifica se usuários já existem
    $adminExists = $db->fetch("SELECT id FROM users WHERE email = 'admin@admin.com'");
    $userExists = $db->fetch("SELECT id FROM users WHERE email = 'teste@teste.com'");
    
    $created = 0;
    $errors = 0;
    
    // Cria Admin
    if ($adminExists) {
        echo "<div class='result error'>";
        echo "⚠️ Admin (admin@admin.com) já existe!";
        echo "</div>";
    } else {
        $adminPassword = password_hash('123456', PASSWORD_DEFAULT);
        
        try {
            $adminId = $db->insert('users', [
                'name' => 'Administrador',
                'email' => 'admin@admin.com',
                'password' => $adminPassword,
                'role' => 'admin',
                'status' => 'active'
            ]);
            
            echo "<div class='result success'>";
            echo "✓ Admin criado com sucesso!<br>";
            echo "<div class='credential'>";
            echo "Email: admin@admin.com<br>";
            echo "Senha: 123456<br>";
            echo "ID: {$adminId}";
            echo "</div>";
            echo "</div>";
            $created++;
        } catch (Exception $e) {
            echo "<div class='result error'>";
            echo "✗ Erro ao criar admin: " . $e->getMessage();
            echo "</div>";
            $errors++;
        }
    }
    
    // Cria Usuário Teste
    if ($userExists) {
        echo "<div class='result error'>";
        echo "⚠️ Usuário teste (teste@teste.com) já existe!";
        echo "</div>";
    } else {
        $userPassword = password_hash('123456', PASSWORD_DEFAULT);
        
        try {
            $userId = $db->insert('users', [
                'name' => 'Usuário Teste',
                'email' => 'teste@teste.com',
                'password' => $userPassword,
                'role' => 'client',
                'status' => 'active'
            ]);
            
            echo "<div class='result success'>";
            echo "✓ Usuário teste criado com sucesso!<br>";
            echo "<div class='credential'>";
            echo "Email: teste@teste.com<br>";
            echo "Senha: 123456<br>";
            echo "ID: {$userId}";
            echo "</div>";
            echo "</div>";
            $created++;
        } catch (Exception $e) {
            echo "<div class='result error'>";
            echo "✗ Erro ao criar usuário: " . $e->getMessage();
            echo "</div>";
            $errors++;
        }
    }
    
    // Resumo
    echo "<div class='info'>";
    echo "<strong>📊 Resumo:</strong><br>";
    echo "Usuários criados: {$created}<br>";
    echo "Erros: {$errors}<br>";
    echo "</div>";
    
    // Links
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='public/index.php?page=login' class='btn'>🔐 Ir para Login</a><br>";
    echo "<a href='admin/index.php' class='btn' style='background: #dc2626; margin-top: 10px;'>👑 Painel Admin</a>";
    echo "</div>";
    
    // Aviso
    echo "<div class='warning'>";
    echo "⚠️ IMPORTANTE: DELETE este arquivo após usar!<br>";
    echo "Arquivo: /utmtrack/create_users.php<br><br>";
    echo "Comando SSH: <code style='background: white; padding: 5px; border-radius: 4px;'>rm /home/ataweb.com.br/public_html/utmtrack/create_users.php</code>";
    echo "</div>";
    
    echo "</div>";
    echo "</body>";
    echo "</html>";
    
} catch (Exception $e) {
    echo "<html><body style='font-family: monospace; background: #0f172a; color: #ef4444; padding: 40px;'>";
    echo "<h1>Erro Fatal</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</body></html>";
}