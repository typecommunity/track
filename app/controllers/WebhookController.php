<?php
/**
 * UTMTrack - Controller de Webhooks
 * Arquivo: app/controllers/WebhookController.php
 */

class WebhookController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Lista webhooks
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca webhooks do usuário
        $webhooks = $this->db->fetchAll("
            SELECT 
                w.*,
                COUNT(wl.id) as total_requests,
                SUM(CASE WHEN wl.status = 'success' THEN 1 ELSE 0 END) as successful_requests
            FROM webhooks w
            LEFT JOIN webhook_logs wl ON wl.webhook_id = w.id
            WHERE w.user_id = :user_id
            GROUP BY w.id
            ORDER BY w.created_at DESC
        ", ['user_id' => $userId]);
        
        // Estatísticas
        $stats = $this->db->fetch("
            SELECT 
                COUNT(DISTINCT w.id) as total_webhooks,
                COUNT(wl.id) as total_requests,
                SUM(CASE WHEN wl.status = 'success' THEN 1 ELSE 0 END) as successful_requests,
                SUM(CASE WHEN wl.status = 'error' THEN 1 ELSE 0 END) as failed_requests
            FROM webhooks w
            LEFT JOIN webhook_logs wl ON wl.webhook_id = w.id
            WHERE w.user_id = :user_id
        ", ['user_id' => $userId]);
        
        $this->render('webhooks/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'webhooks' => $webhooks,
            'stats' => $stats,
            'pageTitle' => 'Webhooks'
        ]);
    }
    
    /**
     * Criar webhook
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        $data = [
            'name' => $this->sanitize($this->post('name')),
            'platform' => $this->sanitize($this->post('platform')),
            'product_id' => $this->post('product_id') ?: null,
            'secret_key' => bin2hex(random_bytes(16)), // Gera chave secreta
            'status' => 'active'
        ];
        
        // Valida
        $errors = $this->validate($data, [
            'name' => 'required|min:3',
            'platform' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->json(['success' => false, 'message' => 'Preencha todos os campos', 'errors' => $errors], 400);
            return;
        }
        
        // Insere
        $webhookId = $this->db->insert('webhooks', array_merge($data, [
            'user_id' => $userId
        ]));
        
        // Gera URL do webhook
        $webhookUrl = $this->config['base_url'] . '/../api/webhook.php?id=' . $webhookId . '&key=' . $data['secret_key'];
        
        $this->json([
            'success' => true,
            'webhook_id' => $webhookId,
            'webhook_url' => $webhookUrl,
            'secret_key' => $data['secret_key'],
            'message' => 'Webhook criado com sucesso!'
        ]);
    }
    
    /**
     * Atualizar webhook
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $webhookId = $this->post('webhook_id');
        
        if (empty($webhookId)) {
            $this->json(['success' => false, 'message' => 'ID do webhook não informado'], 400);
            return;
        }
        
        // Verifica se webhook pertence ao usuário
        $webhook = $this->db->fetch("
            SELECT id FROM webhooks 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $webhookId,
            'user_id' => $userId
        ]);
        
        if (!$webhook) {
            $this->json(['success' => false, 'message' => 'Webhook não encontrado'], 404);
            return;
        }
        
        $data = [
            'name' => $this->sanitize($this->post('name')),
            'platform' => $this->sanitize($this->post('platform')),
            'product_id' => $this->post('product_id') ?: null,
            'status' => $this->post('status', 'active')
        ];
        
        // Atualiza
        $this->db->update('webhooks', 
            $data,
            'id = :id',
            ['id' => $webhookId]
        );
        
        $this->json([
            'success' => true,
            'message' => 'Webhook atualizado com sucesso!'
        ]);
    }
    
    /**
     * Deletar webhook
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $webhookId = $this->post('webhook_id');
        
        if (empty($webhookId)) {
            $this->json(['success' => false, 'message' => 'ID do webhook não informado'], 400);
            return;
        }
        
        // Verifica se webhook pertence ao usuário
        $webhook = $this->db->fetch("
            SELECT id FROM webhooks 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $webhookId,
            'user_id' => $userId
        ]);
        
        if (!$webhook) {
            $this->json(['success' => false, 'message' => 'Webhook não encontrado'], 404);
            return;
        }
        
        // Deleta logs primeiro
        $this->db->delete('webhook_logs', 'webhook_id = :id', ['id' => $webhookId]);
        
        // Deleta webhook
        $this->db->delete('webhooks', 'id = :id', ['id' => $webhookId]);
        
        $this->json([
            'success' => true,
            'message' => 'Webhook deletado com sucesso!'
        ]);
    }
    
    /**
     * Buscar webhook por ID
     */
    public function get() {
        $userId = $this->auth->id();
        $webhookId = $this->get('id');
        
        if (empty($webhookId)) {
            $this->json(['success' => false, 'message' => 'ID não informado'], 400);
            return;
        }
        
        $webhook = $this->db->fetch("
            SELECT * FROM webhooks 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $webhookId,
            'user_id' => $userId
        ]);
        
        if (!$webhook) {
            $this->json(['success' => false, 'message' => 'Webhook não encontrado'], 404);
            return;
        }
        
        // Gera URL
        $webhook['url'] = $this->config['base_url'] . '/../api/webhook.php?id=' . $webhook['id'] . '&key=' . $webhook['secret_key'];
        
        $this->json([
            'success' => true,
            'webhook' => $webhook
        ]);
    }
    
    /**
     * Ver logs do webhook
     */
    public function logs() {
        $userId = $this->auth->id();
        $webhookId = $this->get('id');
        
        if (empty($webhookId)) {
            $this->redirect('index.php?page=webhooks');
            return;
        }
        
        // Verifica se webhook pertence ao usuário
        $webhook = $this->db->fetch("
            SELECT * FROM webhooks 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $webhookId,
            'user_id' => $userId
        ]);
        
        if (!$webhook) {
            $this->redirect('index.php?page=webhooks');
            return;
        }
        
        // Busca logs
        $logs = $this->db->fetchAll("
            SELECT * FROM webhook_logs 
            WHERE webhook_id = :webhook_id
            ORDER BY created_at DESC
            LIMIT 100
        ", ['webhook_id' => $webhookId]);
        
        $this->render('webhooks/logs', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'webhook' => $webhook,
            'logs' => $logs,
            'pageTitle' => 'Logs do Webhook'
        ]);
    }
    
    /**
     * Testar webhook
     */
    public function test() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $webhookId = $this->post('webhook_id');
        
        // Busca webhook
        $webhook = $this->db->fetch("
            SELECT * FROM webhooks 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $webhookId,
            'user_id' => $userId
        ]);
        
        if (!$webhook) {
            $this->json(['success' => false, 'message' => 'Webhook não encontrado'], 404);
            return;
        }
        
        // Dados de teste baseados na plataforma
        $testData = $this->getTestData($webhook['platform']);
        
        // Simula envio
        $webhookUrl = $this->config['base_url'] . '/../api/webhook.php?id=' . $webhook['id'] . '&key=' . $webhook['secret_key'];
        
        $this->json([
            'success' => true,
            'message' => 'Use a URL abaixo para testar',
            'webhook_url' => $webhookUrl,
            'test_data' => $testData
        ]);
    }
    
    /**
     * Dados de teste por plataforma
     */
    private function getTestData($platform) {
        $baseData = [
            'transaction_id' => 'TEST-' . time(),
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'teste@email.com',
            'amount' => 197.00,
            'status' => 'approved',
            'payment_method' => 'pix'
        ];
        
        switch ($platform) {
            case 'hotmart':
                return array_merge($baseData, ['event' => 'PURCHASE_COMPLETE']);
            case 'kiwify':
                return array_merge($baseData, ['order_status' => 'paid']);
            case 'eduzz':
                return array_merge($baseData, ['sales_status' => 4]);
            default:
                return $baseData;
        }
    }
}