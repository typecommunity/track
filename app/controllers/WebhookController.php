<?php
/**
 * UTMTrack - Controller de Webhooks ATUALIZADO
 * Sistema Universal de Webhooks
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
        
        // Coleta eventos
        $events = $this->post('events', '[]');
        if (is_string($events)) {
            $events = json_decode($events, true) ?? [];
        }
        
        // Coleta métodos de pagamento
        $paymentMethods = $this->post('payment_methods', '[]');
        if (is_string($paymentMethods)) {
            $paymentMethods = json_decode($paymentMethods, true) ?? [];
        }
        
        // Valida métodos de pagamento (apenas os 3 permitidos)
        $validPaymentMethods = ['credit_card', 'pix', 'boleto'];
        $paymentMethods = array_values(array_intersect($paymentMethods, $validPaymentMethods));
        
        $data = [
            'name' => $this->sanitize($this->post('name')),
            'platform' => 'custom', // Sempre custom agora
            'product_id' => $this->post('product_id') ?: null,
            'secret_key' => bin2hex(random_bytes(16)),
            'events' => json_encode($events),
            'payment_methods' => json_encode($paymentMethods),
            'status' => 'active'
        ];
        
        // Valida
        $errors = $this->validate($data, [
            'name' => 'required|min:3'
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
        $webhookUrl = $this->config['base_url'] . '/api/webhook.php?id=' . $webhookId . '&key=' . $data['secret_key'];
        
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
        
        // Coleta eventos
        $events = $this->post('events', '[]');
        if (is_string($events)) {
            $events = json_decode($events, true) ?? [];
        }
        
        // Coleta métodos de pagamento
        $paymentMethods = $this->post('payment_methods', '[]');
        if (is_string($paymentMethods)) {
            $paymentMethods = json_decode($paymentMethods, true) ?? [];
        }
        
        // Valida métodos de pagamento (apenas os 3 permitidos)
        $validPaymentMethods = ['credit_card', 'pix', 'boleto'];
        $paymentMethods = array_values(array_intersect($paymentMethods, $validPaymentMethods));
        
        $data = [
            'name' => $this->sanitize($this->post('name')),
            'platform' => 'custom', // Sempre custom
            'product_id' => $this->post('product_id') ?: null,
            'events' => json_encode($events),
            'payment_methods' => json_encode($paymentMethods),
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
     * CORRIGIDO: Renomeado de get() para getWebhook()
     */
    public function getWebhook() {
        $userId = $this->auth->id();
        $webhookId = parent::get('id');
        
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
        
        // Decodifica eventos e métodos de pagamento
        $webhook['events'] = json_decode($webhook['events'] ?? '[]', true);
        $webhook['payment_methods'] = json_decode($webhook['payment_methods'] ?? '[]', true);
        
        // Gera URL
        $webhook['url'] = $this->config['base_url'] . '/api/webhook.php?id=' . $webhook['id'] . '&key=' . $webhook['secret_key'];
        
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
        $webhookId = parent::get('id');
        
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
        
        // Decodifica eventos e métodos
        $webhook['events'] = json_decode($webhook['events'] ?? '[]', true);
        $webhook['payment_methods'] = json_decode($webhook['payment_methods'] ?? '[]', true);
        
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
        
        // Dados de teste
        $testData = [
            'event' => 'purchase_approved',
            'transaction_id' => 'TEST-' . time(),
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'teste@email.com',
            'amount' => 197.00,
            'payment_method' => 'pix',
            'status' => 'approved',
            'product_name' => 'Produto Teste'
        ];
        
        // URL do webhook
        $webhookUrl = $this->config['base_url'] . '/api/webhook.php?id=' . $webhook['id'] . '&key=' . $webhook['secret_key'];
        
        $this->json([
            'success' => true,
            'message' => 'Use a URL abaixo para testar',
            'webhook_url' => $webhookUrl,
            'test_data' => $testData
        ]);
    }
    
    /**
     * Regenerar chave secreta
     */
    public function regenerateKey() {
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
        
        // Gera nova chave
        $newKey = bin2hex(random_bytes(16));
        
        // Atualiza
        $this->db->update('webhooks', 
            ['secret_key' => $newKey],
            'id = :id',
            ['id' => $webhookId]
        );
        
        // Nova URL
        $webhookUrl = $this->config['base_url'] . '/api/webhook.php?id=' . $webhookId . '&key=' . $newKey;
        
        $this->json([
            'success' => true,
            'message' => 'Chave regenerada com sucesso!',
            'secret_key' => $newKey,
            'webhook_url' => $webhookUrl
        ]);
    }
}