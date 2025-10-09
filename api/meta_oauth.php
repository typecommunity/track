<?php
/**
 * UTMTrack - Meta Ads OAuth Callback
 * Processa o retorno do Facebook OAuth
 */

session_start();

// Carrega dependências
require_once __DIR__ . '/../core/Database.php';

// Função para redirecionar com erro
function redirectError($message) {
    header('Location: ../public/index.php?page=integracoes-meta&error=' . urlencode($message));
    exit;
}

// Função para redirecionar com sucesso
function redirectSuccess($message) {
    header('Location: ../public/index.php?page=integracoes-meta-contas&success=' . urlencode($message));
    exit;
}

try {
    // Verifica se tem código de autorização
    if (!isset($_GET['code'])) {
        $error = $_GET['error_description'] ?? $_GET['error'] ?? 'Autorização cancelada';
        redirectError($error);
    }
    
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';
    
    // Verifica state para segurança
    if (empty($state) || !isset($_SESSION['meta_oauth_state']) || $state !== $_SESSION['meta_oauth_state']) {
        redirectError('Estado de segurança inválido');
    }
    
    // Pega user_id da sessão
    $userId = $_SESSION['meta_oauth_user_id'] ?? null;
    
    if (!$userId) {
        redirectError('Sessão expirada');
    }
    
    // Limpa state da sessão
    unset($_SESSION['meta_oauth_state']);
    unset($_SESSION['meta_oauth_user_id']);
    
    // Carrega configuração do usuário
    $db = Database::getInstance();
    
    $config = $db->fetch("
        SELECT * FROM integration_configs 
        WHERE user_id = :user_id AND platform = 'meta'
    ", ['user_id' => $userId]);
    
    if (!$config) {
        redirectError('Configuração não encontrada');
    }
    
    // URL de callback (mesma usada na autorização)
    // FIX: REQUEST_SCHEME pode não estar disponível em alguns servidores
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $redirectUri = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
    
    // Troca o código por access token
    $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query([
        'client_id' => $config['app_id'],
        'client_secret' => $config['app_secret'],
        'redirect_uri' => $redirectUri,
        'code' => $code
    ]);
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        redirectError('Erro ao obter token de acesso: HTTP ' . $httpCode);
    }
    
    $tokenData = json_decode($response, true);
    
    if (!isset($tokenData['access_token'])) {
        $errorMsg = $tokenData['error']['message'] ?? 'Token não retornado';
        redirectError($errorMsg);
    }
    
    $accessToken = $tokenData['access_token'];
    $expiresIn = $tokenData['expires_in'] ?? 5184000; // 60 dias padrão
    
    // Troca por long-lived token (60 dias)
    $longLivedUrl = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query([
        'grant_type' => 'fb_exchange_token',
        'client_id' => $config['app_id'],
        'client_secret' => $config['app_secret'],
        'fb_exchange_token' => $accessToken
    ]);
    
    $ch = curl_init($longLivedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $longLivedResponse = curl_exec($ch);
    curl_close($ch);
    
    $longLivedData = json_decode($longLivedResponse, true);
    
    if (isset($longLivedData['access_token'])) {
        $accessToken = $longLivedData['access_token'];
        $expiresIn = $longLivedData['expires_in'] ?? 5184000;
    }
    
    // Calcula data de expiração
    $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
    
    // Salva o token no banco
    $db->update('integration_configs',
        [
            'access_token' => $accessToken,
            'token_expires_at' => $expiresAt,
            'status' => 'connected'
        ],
        'id = :id',
        ['id' => $config['id']]
    );
    
    // Busca contas de anúncio do usuário
    $accountsUrl = 'https://graph.facebook.com/v18.0/me/adaccounts?fields=id,name,account_status&access_token=' . $accessToken;
    
    $ch = curl_init($accountsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $accountsResponse = curl_exec($ch);
    curl_close($ch);
    
    $accountsData = json_decode($accountsResponse, true);
    
    $imported = 0;
    
    if (isset($accountsData['data']) && is_array($accountsData['data'])) {
        foreach ($accountsData['data'] as $account) {
            // Remove "act_" do ID se existir
            $accountId = str_replace('act_', '', $account['id']);
            
            // Verifica se conta já existe
            $exists = $db->fetch("
                SELECT id FROM ad_accounts 
                WHERE user_id = :user_id 
                AND platform = 'meta' 
                AND account_id = :account_id
            ", [
                'user_id' => $userId,
                'account_id' => $accountId
            ]);
            
            if ($exists) {
                // Atualiza
                $db->update('ad_accounts',
                    [
                        'account_name' => $account['name'],
                        'status' => 'active',
                        'access_token' => $accessToken,
                        'token_expires_at' => $expiresAt
                    ],
                    'id = :id',
                    ['id' => $exists['id']]
                );
            } else {
                // Insere nova
                $db->insert('ad_accounts', [
                    'user_id' => $userId,
                    'platform' => 'meta',
                    'account_id' => $accountId,
                    'account_name' => $account['name'],
                    'access_token' => $accessToken,
                    'token_expires_at' => $expiresAt,
                    'status' => 'active'
                ]);
                $imported++;
            }
        }
    }
    
    // Redireciona para gerenciar contas
    $message = $imported > 0 
        ? "Conectado com sucesso! {$imported} nova(s) conta(s) importada(s)."
        : "Conectado com sucesso!";
    
    redirectSuccess($message);
    
} catch (Exception $e) {
    // Log do erro
    error_log('Meta OAuth Error: ' . $e->getMessage());
    
    // Redireciona com erro
    redirectError('Erro ao processar autorização: ' . $e->getMessage());
}