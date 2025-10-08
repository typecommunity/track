<?php
/**
 * UTMTrack - Front Controller
 * Arquivo: public/index.php
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

// Cria instância do router
$router = new Router();

// Processa a requisição
try {
    $router->dispatch();
} catch (Exception $e) {
    // Se houver erro, mostra mensagem amigável
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro - UTMTrack</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #0f172a;
                color: #e2e8f0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-container {
                background: #1e293b;
                border: 1px solid #334155;
                border-radius: 20px;
                padding: 50px;
                text-align: center;
                max-width: 600px;
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
                color: #94a3b8;
                font-size: 16px;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .error-details {
                background: #0f172a;
                border: 1px solid #334155;
                border-radius: 10px;
                padding: 20px;
                text-align: left;
                font-family: monospace;
                font-size: 13px;
                margin-bottom: 30px;
                color: #fca5a5;
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
        <div class="error-container">
            <div class="icon">⚠️</div>
            <h1>Ops! Algo deu errado</h1>
            <p>Ocorreu um erro ao processar sua solicitação.</p>
            
            <?php if ($config['debug']): ?>
            <div class="error-details">
                <?= htmlspecialchars($e->getMessage()) ?>
            </div>
            <?php endif; ?>
            
            <a href="index.php?page=login" class="btn">Voltar ao Login</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}