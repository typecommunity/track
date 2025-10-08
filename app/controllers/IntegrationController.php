<?php
/**
 * UTMTrack - Controller de Integrações
 * Gerencia integrações com Meta Ads, Google Ads, etc
 */

class IntegrationController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    // Adicionar ao Router.php:
    // 'integracoes' => ['IntegrationController', 'index'],
    // 'integracoes-meta' => ['IntegrationController', 'meta'],
    // 'integracoes-meta-salvar' => ['IntegrationController', 'metaSave'],
    // 'integracoes-meta-conectar' => ['IntegrationController', 'metaConnect'],
    // 'integracoes-meta-contas' => ['IntegrationController', 'metaAccounts'],
    // 'integracoes-meta-toggle' => ['IntegrationController', 'metaToggleAccount'],
    // 'integracoes-meta-sync' => ['IntegrationController', 'metaSync'],
    // 'integracoes-meta-remover' => ['IntegrationController', 'metaRemove'],
    
    /**
     * Página principal de integrações
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Verifica se tem credenciais Meta configuradas
        $metaConfig = $this->db->fetch("
            SELECT * FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        // Conta quantas contas estão conectadas
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
    
    /**
     * Configuração Meta Ads
     */
    public function meta() {
        $userId = $this->auth->id();
        
        // Busca configuração existente
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
    
    /**
     * Salvar credenciais Meta
     */
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
        
        // Verifica se já existe configuração
        $exists = $this->db->fetch("
            SELECT id FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        if ($exists) {
            // Atualiza
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
            // Insere
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
    }
    
    /**
     * Iniciar conexão OAuth Meta
     */
    public function metaConnect() {
        $userId = $this->auth->id();
        
        // Busca credenciais
        $config = $this->db->fetch("
            SELECT * FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        if (!$config) {
            $this->redirect('index.php?page=integracoes-meta&error=config');
            return;
        }
        
        // Gera state para segurança
        $state = bin2hex(random_bytes(16));
        $_SESSION['meta_oauth_state'] = $state;
        $_SESSION['meta_oauth_user_id'] = $userId;
        
        // URL de callback
        $redirectUri = $this->config['base_url'] . '/../api/meta_oauth.php';
        
        // Permissões necessárias
        $permissions = [
            'ads_read',
            'ads_management',
            'business_management',
            'pages_show_list',
            'pages_read_engagement'
        ];
        
        // Monta URL do Facebook OAuth
        $oauthUrl = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query([
            'client_id' => $config['app_id'],
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => implode(',', $permissions),
            'response_type' => 'code'
        ]);
        
        // Retorna opções
        $this->render('integrations/meta_connect', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'oauthUrl' => $oauthUrl,
            'pageTitle' => 'Conectar Meta Ads'
        ]);
    }
    
    /**
     * Gerenciar contas Meta
     */
    public function metaAccounts() {
        $userId = $this->auth->id();
        
        // Busca configuração
        $config = $this->db->fetch("
            SELECT * FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        if (!$config || $config['status'] !== 'connected') {
            $this->redirect('index.php?page=integracoes-meta');
            return;
        }
        
        // Busca todas as contas
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
    
    /**
     * Ativar/Desativar conta
     */
    public function metaToggleAccount() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $accountId = $this->post('account_id');
        $status = $this->post('status'); // 'active' ou 'inactive'
        
        if (empty($accountId) || !in_array($status, ['active', 'inactive'])) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        // Verifica se conta pertence ao usuário
        $account = $this->db->fetch("
            SELECT id FROM ad_accounts 
            WHERE id = :id AND user_id = :user_id AND platform = 'meta'
        ", [
            'id' => $accountId,
            'user_id' => $userId
        ]);
        
        if (!$account) {
            $this->json(['success' => false, 'message' => 'Conta não encontrada'], 404);
            return;
        }
        
        // Atualiza status
        $this->db->update('ad_accounts',
            ['status' => $status],
            'id = :id',
            ['id' => $accountId]
        );
        
        $this->json([
            'success' => true,
            'message' => $status === 'active' ? 'Conta ativada!' : 'Conta desativada!'
        ]);
    }
    
    /**
     * Sincronizar contas manualmente
     */
    public function metaSync() {
        $userId = $this->auth->id();
        
        // Busca configuração
        $config = $this->db->fetch("
            SELECT * FROM integration_configs 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        if (!$config || empty($config['access_token'])) {
            $this->json(['success' => false, 'message' => 'Meta Ads não conectado'], 400);
            return;
        }
        
        try {
            // Busca contas da API do Facebook
            $accounts = $this->fetchMetaAccounts($config['access_token']);
            
            if (empty($accounts)) {
                $this->json(['success' => false, 'message' => 'Nenhuma conta encontrada'], 400);
                return;
            }
            
            $imported = 0;
            
            foreach ($accounts as $account) {
                // Verifica se conta já existe
                $exists = $this->db->fetch("
                    SELECT id FROM ad_accounts 
                    WHERE user_id = :user_id 
                    AND platform = 'meta' 
                    AND account_id = :account_id
                ", [
                    'user_id' => $userId,
                    'account_id' => $account['id']
                ]);
                
                if ($exists) {
                    // Atualiza
                    $this->db->update('ad_accounts',
                        [
                            'account_name' => $account['name'],
                            'status' => 'active'
                        ],
                        'id = :id',
                        ['id' => $exists['id']]
                    );
                } else {
                    // Insere nova
                    $this->db->insert('ad_accounts', [
                        'user_id' => $userId,
                        'platform' => 'meta',
                        'account_id' => $account['id'],
                        'account_name' => $account['name'],
                        'status' => 'active'
                    ]);
                    $imported++;
                }
            }
            
            $this->json([
                'success' => true,
                'message' => "Sincronizado com sucesso! {$imported} novas contas importadas.",
                'total' => count($accounts),
                'imported' => $imported
            ]);
            
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Erro ao sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Busca contas do Meta Ads via API
     */
    private function fetchMetaAccounts($accessToken) {
        $url = 'https://graph.facebook.com/v18.0/me/adaccounts?fields=id,name,account_status&access_token=' . $accessToken;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Erro ao buscar contas: HTTP ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['data'])) {
            throw new Exception('Resposta inválida da API');
        }
        
        return $data['data'];
    }
    
    /**
     * Remover integração Meta
     */
    public function metaRemove() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        // Remove configuração
        $this->db->delete('integration_configs', 
            'user_id = :user_id AND platform = :platform',
            ['user_id' => $userId, 'platform' => 'meta']
        );
        
        // Desativa todas as contas
        $this->db->update('ad_accounts',
            ['status' => 'inactive'],
            'user_id = :user_id AND platform = :platform',
            ['user_id' => $userId, 'platform' => 'meta']
        );
        
        $this->json([
            'success' => true,
            'message' => 'Integração removida com sucesso!'
        ]);
    }
}