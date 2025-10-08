<?php
/**
 * UTMTrack - Painel Administrativo
 * Arquivo: admin/index.php
 */

// Configurações de erro (desabilitar em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define timezone
date_default_timezone_set('America/Sao_Paulo');

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega classes do core
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Router.php';

// Carrega configurações
$config = require __DIR__ . '/../config/app.php';

// Cria instância de autenticação
$auth = new Auth();

// Verifica se é admin
if (!$auth->isAdmin()) {
    // Se não estiver logado, redireciona para login
    if (!$auth->check()) {
        header('Location: ../public/index.php?page=login');
        exit;
    }
    
    // Se estiver logado mas não for admin, mostra erro
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acesso Negado - UTMTrack</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
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
                padding: 50px;
                text-align: center;
                max-width: 500px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .icon {
                font-size: 80px;
                margin-bottom: 20px;
            }
            h1 {
                color: #ef4444;
                font-size: 32px;
                margin-bottom: 15px;
            }
            p {
                color: #6b7280;
                font-size: 16px;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 14px 30px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
                transition: all 0.3s;
            }
            .btn:hover {
                background: #5568d3;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">🚫</div>
            <h1>Acesso Negado</h1>
            <p>Você não tem permissão para acessar o painel administrativo. Esta área é restrita apenas para administradores do sistema.</p>
            <a href="../public/index.php?page=dashboard" class="btn">Voltar ao Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Carrega controller do admin
require_once __DIR__ . '/../app/controllers/AdminController.php';
$adminController = new AdminController();

// Processa página
$page = $_GET['page'] ?? 'dashboard';

// Remove possíveis tentativas de path traversal
$page = basename($page);

switch ($page) {
    case 'dashboard':
        $adminController->dashboard();
        break;
    
    case 'clientes':
    case 'clients':
        $adminController->clients();
        break;
    
    case 'configuracoes':
    case 'settings':
        $adminController->settings();
        break;
    
    default:
        $adminController->dashboard();
        break;
}