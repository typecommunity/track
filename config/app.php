<?php
/**
 * UTMTrack - Configuração da Aplicação
 * Arquivo: config/app.php
 */

// Detecta automaticamente o caminho base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = str_replace('/public/index.php', '', $scriptName);
$basePath = str_replace('/admin/index.php', '', $basePath);

return [
    'name' => 'UTMTrack',
    'version' => '1.0.0',
    'base_url' => $protocol . '://' . $host . $basePath . '/public',
    'root_path' => dirname(__DIR__),
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
    'currency' => 'BRL',
    
    // Segurança
    'session_lifetime' => 7200, // 2 horas em segundos
    'password_min_length' => 6,
    'max_login_attempts' => 5,
    
    // Upload
    'upload_max_size' => 5242880, // 5MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif'],
    
    // Paginação
    'per_page' => 20,
    
    // Debug (desabilitar em produção)
    'debug' => true,
    'display_errors' => true,
];