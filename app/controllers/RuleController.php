<?php
/**
 * UTMTrack - Controller de Regras Automatizadas
 * Arquivo: app/controllers/RuleController.php
 */

class RuleController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Lista regras
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca regras do usuário
        $rules = $this->db->fetchAll("
            SELECT 
                r.*,
                p.name as product_name,
                aa.account_name,
                COUNT(al.id) as total_executions,
                SUM(CASE WHEN al.result = 'success' THEN 1 ELSE 0 END) as successful_executions,
                MAX(al.created_at) as last_execution_date
            FROM automation_rules r
            LEFT JOIN products p ON p.id = r.product_id
            LEFT JOIN ad_accounts aa ON aa.id = r.ad_account_id
            LEFT JOIN automation_logs al ON al.rule_id = r.id
            WHERE r.user_id = :user_id
            GROUP BY r.id
            ORDER BY r.created_at DESC
        ", ['user_id' => $userId]);
        
        // Decodifica conditions JSON
        foreach ($rules as &$rule) {
            $rule['conditions'] = json_decode($rule['conditions'], true);
        }
        
        // Estatísticas
        $stats = $this->db->fetch("
            SELECT 
                COUNT(DISTINCT r.id) as total_rules,
                SUM(CASE WHEN r.status = 'active' THEN 1 ELSE 0 END) as active_rules,
                COUNT(al.id) as total_executions,
                SUM(CASE WHEN al.result = 'success' THEN 1 ELSE 0 END) as successful_executions
            FROM automation_rules r
            LEFT JOIN automation_logs al ON al.rule_id = r.id
            WHERE r.user_id = :user_id
        ", ['user_id' => $userId]);
        
        // Busca produtos e contas para os selects
        $products = $this->db->fetchAll("
            SELECT id, name FROM products 
            WHERE user_id = :user_id AND status = 'active'
            ORDER BY name
        ", ['user_id' => $userId]);
        
        $adAccounts = $this->db->fetchAll("
            SELECT id, account_name, platform FROM ad_accounts 
            WHERE user_id = :user_id AND status = 'active'
            ORDER BY account_name
        ", ['user_id' => $userId]);
        
        $this->render('rules/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'rules' => $rules,
            'stats' => $stats,
            'products' => $products,
            'adAccounts' => $adAccounts,
            'pageTitle' => 'Regras Automatizadas'
        ]);
    }
    
    /**
     * Criar regra
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        // Recebe dados
        $name = $this->sanitize($this->post('name'));
        $productId = $this->post('product_id') ?: null;
        $adAccountId = $this->post('ad_account_id') ?: null;
        $targetType = $this->post('target_type');
        $action = $this->post('action');
        $frequency = $this->post('frequency', '1hour');
        $maxExecutions = (int) $this->post('max_executions_per_day', 10);
        
        // Processa condições
        $conditions = [
            'metric' => $this->post('condition_metric'),
            'operator' => $this->post('condition_operator'),
            'value' => $this->post('condition_value'),
            'period' => $this->post('condition_period', '24hours')
        ];
        
        // Valida
        if (empty($name) || empty($targetType) || empty($action)) {
            $this->json(['success' => false, 'message' => 'Preencha todos os campos obrigatórios'], 400);
            return;
        }
        
        if (empty($conditions['metric']) || empty($conditions['operator']) || empty($conditions['value'])) {
            $this->json(['success' => false, 'message' => 'Configure as condições da regra'], 400);
            return;
        }
        
        // Insere
        $ruleId = $this->db->insert('automation_rules', [
            'user_id' => $userId,
            'name' => $name,
            'product_id' => $productId,
            'ad_account_id' => $adAccountId,
            'target_type' => $targetType,
            'action' => $action,
            'conditions' => json_encode($conditions),
            'frequency' => $frequency,
            'max_executions_per_day' => $maxExecutions,
            'status' => 'active'
        ]);
        
        $this->json([
            'success' => true,
            'rule_id' => $ruleId,
            'message' => 'Regra criada com sucesso!'
        ]);
    }
    
    /**
     * Atualizar regra
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $ruleId = $this->post('rule_id');
        
        if (empty($ruleId)) {
            $this->json(['success' => false, 'message' => 'ID da regra não informado'], 400);
            return;
        }
        
        // Verifica se regra pertence ao usuário
        $rule = $this->db->fetch("
            SELECT id FROM automation_rules 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $ruleId,
            'user_id' => $userId
        ]);
        
        if (!$rule) {
            $this->json(['success' => false, 'message' => 'Regra não encontrada'], 404);
            return;
        }
        
        // Recebe dados
        $name = $this->sanitize($this->post('name'));
        $productId = $this->post('product_id') ?: null;
        $adAccountId = $this->post('ad_account_id') ?: null;
        $targetType = $this->post('target_type');
        $action = $this->post('action');
        $frequency = $this->post('frequency', '1hour');
        $maxExecutions = (int) $this->post('max_executions_per_day', 10);
        $status = $this->post('status', 'active');
        
        // Processa condições
        $conditions = [
            'metric' => $this->post('condition_metric'),
            'operator' => $this->post('condition_operator'),
            'value' => $this->post('condition_value'),
            'period' => $this->post('condition_period', '24hours')
        ];
        
        // Atualiza
        $this->db->update('automation_rules', 
            [
                'name' => $name,
                'product_id' => $productId,
                'ad_account_id' => $adAccountId,
                'target_type' => $targetType,
                'action' => $action,
                'conditions' => json_encode($conditions),
                'frequency' => $frequency,
                'max_executions_per_day' => $maxExecutions,
                'status' => $status
            ],
            'id = :id',
            ['id' => $ruleId]
        );
        
        $this->json([
            'success' => true,
            'message' => 'Regra atualizada com sucesso!'
        ]);
    }
    
    /**
     * Deletar regra
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $ruleId = $this->post('rule_id');
        
        if (empty($ruleId)) {
            $this->json(['success' => false, 'message' => 'ID da regra não informado'], 400);
            return;
        }
        
        // Verifica se regra pertence ao usuário
        $rule = $this->db->fetch("
            SELECT id FROM automation_rules 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $ruleId,
            'user_id' => $userId
        ]);
        
        if (!$rule) {
            $this->json(['success' => false, 'message' => 'Regra não encontrada'], 404);
            return;
        }
        
        // Deleta logs primeiro
        $this->db->delete('automation_logs', 'rule_id = :id', ['id' => $ruleId]);
        
        // Deleta regra
        $this->db->delete('automation_rules', 'id = :id', ['id' => $ruleId]);
        
        $this->json([
            'success' => true,
            'message' => 'Regra deletada com sucesso!'
        ]);
    }
    
    /**
     * Buscar regra por ID
     */
    public function getRule() {
        $userId = $this->auth->id();
        $ruleId = parent::get('id');
        
        if (empty($ruleId)) {
            $this->json(['success' => false, 'message' => 'ID não informado'], 400);
            return;
        }
        
        $rule = $this->db->fetch("
            SELECT * FROM automation_rules 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $ruleId,
            'user_id' => $userId
        ]);
        
        if (!$rule) {
            $this->json(['success' => false, 'message' => 'Regra não encontrada'], 404);
            return;
        }
        
        // Decodifica conditions
        $rule['conditions'] = json_decode($rule['conditions'], true);
        
        $this->json([
            'success' => true,
            'rule' => $rule
        ]);
    }
    
    /**
     * Ver logs da regra
     */
    public function logs() {
        $userId = $this->auth->id();
        $ruleId = parent::get('id');
        
        if (empty($ruleId)) {
            $this->redirect('index.php?page=regras');
            return;
        }
        
        // Verifica se regra pertence ao usuário
        $rule = $this->db->fetch("
            SELECT r.*, p.name as product_name, aa.account_name
            FROM automation_rules r
            LEFT JOIN products p ON p.id = r.product_id
            LEFT JOIN ad_accounts aa ON aa.id = r.ad_account_id
            WHERE r.id = :id AND r.user_id = :user_id
        ", [
            'id' => $ruleId,
            'user_id' => $userId
        ]);
        
        if (!$rule) {
            $this->redirect('index.php?page=regras');
            return;
        }
        
        // Busca logs
        $logs = $this->db->fetchAll("
            SELECT al.*, c.campaign_name
            FROM automation_logs al
            LEFT JOIN campaigns c ON c.id = al.campaign_id
            WHERE al.rule_id = :rule_id
            ORDER BY al.created_at DESC
            LIMIT 100
        ", ['rule_id' => $ruleId]);
        
        $this->render('rules/logs', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'rule' => $rule,
            'logs' => $logs,
            'pageTitle' => 'Logs da Regra'
        ]);
    }
    
    /**
     * Alternar status da regra (ativar/desativar)
     */
    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $ruleId = $this->post('rule_id');
        
        if (empty($ruleId)) {
            $this->json(['success' => false, 'message' => 'ID da regra não informado'], 400);
            return;
        }
        
        // Busca regra
        $rule = $this->db->fetch("
            SELECT id, status FROM automation_rules 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $ruleId,
            'user_id' => $userId
        ]);
        
        if (!$rule) {
            $this->json(['success' => false, 'message' => 'Regra não encontrada'], 404);
            return;
        }
        
        // Alterna status
        $newStatus = $rule['status'] === 'active' ? 'inactive' : 'active';
        
        $this->db->update('automation_rules',
            ['status' => $newStatus],
            'id = :id',
            ['id' => $ruleId]
        );
        
        $this->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus === 'active' ? 'Regra ativada!' : 'Regra desativada!'
        ]);
    }
}