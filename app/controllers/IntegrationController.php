<?php
/**
 * UTMTrack - Controller de Integrações
 * Gerencia integrações com Meta Ads, Google Ads, etc
 * 
 * @version 3.0 - VERSÃO LIVE COM PERMISSÕES COMPLETAS
 */

class IntegrationController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    // ========================================================================
    // PÁGINAS PRINCIPAIS
    // ========================================================================
    
    public function index() {
        $userId = $this->auth->id();
        
        $metaConfig = $this->db->fetch("
            SELECT * FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        $metaAccounts = $this->db->fetch("
            SELECT COUNT(*) as total FROM ad_accounts 
            WHERE user_id = :user_id AND platform = 'meta' AND status = 'active'
        ", ['user_id' => $userId])['total'] ?? 0;
        
        $this->render('integrations/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'metaConfig' => $metaConfig,
            'metaAccounts' => $metaAccounts,
            'pageTitle' => 'Integrações'
        ]);
    }
    
    public function meta() {
        $userId = $this->auth->id();
        
        $config = $this->db->fetch("
            SELECT * FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        $this->render('integrations/meta', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'metaConfig' => $config,
            'pageTitle' => 'Configurar Meta Ads'
        ]);
    }
    
    public function metaAccounts() {
        $userId = $this->auth->id();
        
        $config = $this->db->fetch("
            SELECT * FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        if (!$config || $config['status'] !== 'connected') {
            $this->redirect('index.php?page=integracoes-meta&error=' . urlencode('Meta Ads não está conectado'));
            return;
        }
        
        $accounts = $this->db->fetchAll("
            SELECT * FROM ad_accounts 
            WHERE user_id = :user_id AND platform = 'meta'
            ORDER BY account_name
        ", ['user_id' => $userId]);
        
        $this->render('integrations/meta_accounts', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'accounts' => $accounts,
            'pageTitle' => 'Contas Meta Ads'
        ]);
    }
    
    // ========================================================================
    // CONFIGURAÇÃO E OAUTH
    // ========================================================================
    
    public function metaSave() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $appId = $this->sanitize($this->post('app_id'));
        $appSecret = $this->sanitize($this->post('app_secret'));
        
        if (empty($appId) || empty($appSecret)) {
            $this->json(['success' => false, 'message' => 'Preencha todos os campos'], 400);
            return;
        }
        
        $exists = $this->db->fetch("
            SELECT id FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        try {
            if ($exists) {
                $this->db->update('integration_configs',
                    [
                        'app_id' => $appId,
                        'app_secret' => $appSecret,
                        'status' => 'configured'
                    ],
                    'id = :id',
                    ['id' => $exists['id']]
                );
            } else {
                $this->db->insert('integration_configs', [
                    'user_id' => $userId,
                    'platform' => 'meta',
                    'app_id' => $appId,
                    'app_secret' => $appSecret,
                    'status' => 'configured'
                ]);
            }
            
            $this->json([
                'success' => true,
                'message' => 'Credenciais salvas com sucesso!'
            ]);
            
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Erro ao salvar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Iniciar conexão OAuth Meta
     * VERSÃO LIVE - COM PERMISSÕES COMPLETAS
     */
    public function metaConnect() {
        $userId = $this->auth->id();
        
        $config = $this->db->fetch("
            SELECT * FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        if (!$config) {
            $this->redirect('index.php?page=integracoes-meta&error=' . urlencode('Configure as credenciais primeiro'));
            return;
        }
        
        $state = bin2hex(random_bytes(16));
        $_SESSION['meta_oauth_state'] = $state;
        $_SESSION['meta_oauth_user_id'] = $userId;
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $this->config['base_url'];
        $parsedUrl = parse_url($baseUrl);
        $path = $parsedUrl['path'] ?? '';
        $cleanPath = preg_replace('#/public$#', '', $path);
        $redirectUri = $protocol . '://' . $host . $cleanPath . '/api/meta_oauth.php';
        
        // ====================================================================
        // PERMISSÕES COMPLETAS - APP EM PRODUÇÃO
        // ====================================================================
        
        $permissions = [
            'ads_management',
            'ads_read',
            'business_management'
        ];
        
        $oauthUrl = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query([
            'client_id' => $config['app_id'],
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => implode(',', $permissions),
            'response_type' => 'code'
        ]);
        
        $this->render('integrations/meta_connect', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'oauthUrl' => $oauthUrl,
            'redirectUri' => $redirectUri,
            'permissions' => $permissions,
            'pageTitle' => 'Conectar Meta Ads'
        ]);
    }
    
    // ========================================================================
    // GERENCIAMENTO DE CONTAS
    // ========================================================================
    
    public function metaToggleAccount() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $accountId = (int) $this->post('account_id');
        $status = $this->post('status');
        
        if (empty($accountId) || !in_array($status, ['active', 'inactive'])) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        $account = $this->db->fetch("
            SELECT id FROM ad_accounts 
            WHERE id = :id AND user_id = :user_id AND platform = 'meta'
        ", ['id' => $accountId, 'user_id' => $userId]);
        
        if (!$account) {
            $this->json(['success' => false, 'message' => 'Conta não encontrada'], 404);
            return;
        }
        
        try {
            $this->db->update('ad_accounts',
                ['status' => $status],
                'id = :id',
                ['id' => $accountId]
            );
            
            $this->json([
                'success' => true,
                'message' => $status === 'active' ? 'Conta ativada!' : 'Conta desativada!'
            ]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Sincronizar contas via API do Facebook
     */
    public function metaSync() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        $config = $this->db->fetch("
            SELECT * FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        if (!$config || empty($config['access_token'])) {
            $this->json(['success' => false, 'message' => 'Meta Ads não conectado'], 400);
            return;
        }
        
        try {
            $accounts = $this->fetchMetaAccounts($config['access_token']);
            
            if (empty($accounts)) {
                $this->json([
                    'success' => false, 
                    'message' => 'Nenhuma conta encontrada. Verifique se você tem acesso a contas de anúncio no Business Manager.'
                ], 400);
                return;
            }
            
            $imported = 0;
            $updated = 0;
            
            foreach ($accounts as $account) {
                $accountId = str_replace('act_', '', $account['id']);
                
                $exists = $this->db->fetch("
                    SELECT id FROM ad_accounts 
                    WHERE user_id = :user_id AND platform = 'meta' AND account_id = :account_id
                ", ['user_id' => $userId, 'account_id' => $accountId]);
                
                if ($exists) {
                    $this->db->update('ad_accounts',
                        [
                            'account_name' => $account['name'],
                            'status' => 'active',
                            'access_token' => $config['access_token'],
                            'token_expires_at' => $config['token_expires_at']
                        ],
                        'id = :id',
                        ['id' => $exists['id']]
                    );
                    $updated++;
                } else {
                    $this->db->insert('ad_accounts', [
                        'user_id' => $userId,
                        'platform' => 'meta',
                        'account_id' => $accountId,
                        'account_name' => $account['name'],
                        'access_token' => $config['access_token'],
                        'token_expires_at' => $config['token_expires_at'],
                        'status' => 'active'
                    ]);
                    $imported++;
                }
            }
            
            $message = "Sucesso! ";
            if ($imported > 0) $message .= "{$imported} nova(s). ";
            if ($updated > 0) $message .= "{$updated} atualizada(s).";
            
            $this->json([
                'success' => true,
                'message' => $message,
                'total' => count($accounts),
                'imported' => $imported,
                'updated' => $updated
            ]);
            
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Erro ao sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function metaDisconnectAccount() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $accountId = (int) $this->post('account_id');
        
        if (empty($accountId)) {
            $this->json(['success' => false, 'message' => 'ID não fornecido'], 400);
            return;
        }
        
        $account = $this->db->fetch("
            SELECT id FROM ad_accounts 
            WHERE id = :id AND user_id = :user_id AND platform = 'meta'
        ", ['id' => $accountId, 'user_id' => $userId]);
        
        if (!$account) {
            $this->json(['success' => false, 'message' => 'Conta não encontrada'], 404);
            return;
        }
        
        try {
            $this->db->delete('ad_accounts', 'id = :id', ['id' => $accountId]);
            $this->json(['success' => true, 'message' => 'Conta desconectada!']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }
    
    public function metaRemove() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        try {
            $this->db->delete('integration_configs', 
                'user_id = :user_id AND platform = :platform',
                ['user_id' => $userId, 'platform' => 'meta']
            );
            
            $this->db->delete('ad_accounts',
                'user_id = :user_id AND platform = :platform',
                ['user_id' => $userId, 'platform' => 'meta']
            );
            
            $this->json(['success' => true, 'message' => 'Integração removida!']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }
    
    // ========================================================================
    // API FACEBOOK
    // ========================================================================
    
    /**
     * Busca contas via API do Facebook
     */
    private function fetchMetaAccounts($accessToken) {
        $url = 'https://graph.facebook.com/v18.0/me/adaccounts?' . http_build_query([
            'fields' => 'id,name,account_status,currency',
            'access_token' => $accessToken
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('Erro de conexão: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'HTTP ' . $httpCode;
            
            // Log detalhado do erro para debug
            error_log('Meta API Error: ' . $errorMsg . ' | Response: ' . $response);
            
            if (strpos($errorMsg, 'permissions') !== false || strpos($errorMsg, 'OAuthException') !== false) {
                throw new Exception('Sem permissões para acessar contas de anúncio. Verifique se o Use Case está aprovado e as permissões estão ativas.');
            }
            
            throw new Exception('Erro da API Facebook: ' . $errorMsg);
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception('Erro: ' . $data['error']['message']);
        }
        
        if (!isset($data['data'])) {
            throw new Exception('Resposta inválida da API');
        }
        
        return $data['data'];
    }
}
?>