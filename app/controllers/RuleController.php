<?php
/**
 * UTMTrack - Controller de Regras Automatizadas
 * Sistema de automação de campanhas baseado em métricas
 * Arquivo: app/controllers/RuleController.php
 * 
 * VERSÃO COMPLETA - Outubro 2025
 * - Corrigido: coluna account_id
 * - Todas as métricas da UTMfy
 */

class RuleController extends Controller {
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->auth->check()) {
            $this->redirect('index.php?page=login');
        }
    }
    
    /**
     * Lista todas as regras do usuário
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca regras do usuário
        $rules = $this->db->fetchAll("
            SELECT 
                ar.*,
                p.name as product_name,
                aa.account_name,
                (SELECT COUNT(*) FROM automation_logs WHERE rule_id = ar.id) as total_executions,
                (SELECT COUNT(*) FROM automation_logs WHERE rule_id = ar.id AND result = 'success') as successful_executions,
                (SELECT MAX(created_at) FROM automation_logs WHERE rule_id = ar.id) as last_execution_date
            FROM automation_rules ar
            LEFT JOIN products p ON p.id = ar.product_id
            LEFT JOIN ad_accounts aa ON aa.id = ar.ad_account_id
            WHERE ar.user_id = :user_id
            ORDER BY ar.created_at DESC
        ", ['user_id' => $userId]);
        
        // Decodifica condições JSON
        foreach ($rules as &$rule) {
            $rule['conditions'] = json_decode($rule['conditions'], true);
        }
        
        // Estatísticas
        $stats = [
            'total_rules' => count($rules),
            'active_rules' => count(array_filter($rules, fn($r) => $r['status'] === 'active')),
            'total_executions' => array_sum(array_column($rules, 'total_executions')),
            'successful_executions' => array_sum(array_column($rules, 'successful_executions'))
        ];
        
        // Busca produtos e contas para o formulário
        $products = $this->db->fetchAll("
            SELECT id, name FROM products WHERE user_id = :user_id AND status = 'active'
        ", ['user_id' => $userId]);
        
        // ✅ CORRIGIDO: Busca TODAS as contas (ativas e inativas)
        $adAccounts = $this->db->fetchAll("
            SELECT id, account_name, platform, account_id 
            FROM ad_accounts 
            WHERE user_id = :user_id
            ORDER BY status = 'active' DESC, account_name ASC
        ", ['user_id' => $userId]);
        
        // Prepara dados para a view
        $data = [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'rules' => $rules,
            'stats' => $stats,
            'products' => $products,
            'adAccounts' => $adAccounts,
            'pageTitle' => 'Regras Automatizadas',
            'page' => 'regras'
        ];
        
        // Carrega views manualmente
        extract($data);
        
        require dirname(__DIR__) . '/views/layouts/header.php';
        require dirname(__DIR__) . '/views/layouts/sidebar.php';
        require dirname(__DIR__) . '/views/rules/index.php';
        require dirname(__DIR__) . '/views/layouts/footer.php';
    }
    
    /**
     * Cria nova regra
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        $name = $_POST['name'] ?? '';
        $productId = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
        $adAccountId = !empty($_POST['ad_account_id']) ? intval($_POST['ad_account_id']) : null;
        $targetType = $_POST['target_type'] ?? '';
        $action = $_POST['action'] ?? '';
        $frequency = $_POST['frequency'] ?? '1hour';
        $maxExecutions = intval($_POST['max_executions_per_day'] ?? 10);
        
        // Validações
        if (empty($name) || empty($targetType) || empty($action)) {
            $this->json(['success' => false, 'message' => 'Preencha todos os campos obrigatórios'], 400);
            return;
        }
        
        // Monta condições
        $conditions = [
            'metric' => $_POST['condition_metric'] ?? '',
            'operator' => $_POST['condition_operator'] ?? '',
            'value' => floatval($_POST['condition_value'] ?? 0),
            'period' => $_POST['condition_period'] ?? '24hours'
        ];
        
        if (empty($conditions['metric']) || empty($conditions['operator'])) {
            $this->json(['success' => false, 'message' => 'Configure as condições corretamente'], 400);
            return;
        }
        
        try {
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
                'message' => 'Regra criada com sucesso!',
                'rule_id' => $ruleId
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao criar regra: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao criar regra: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Busca dados de uma regra específica
     */
    public function getRule() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $ruleId = intval($_GET['id'] ?? 0);
        
        if (!$ruleId) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }
        
        try {
            $rule = $this->db->fetch("
                SELECT * FROM automation_rules 
                WHERE id = :id AND user_id = :user_id
            ", ['id' => $ruleId, 'user_id' => $userId]);
            
            if (!$rule) {
                $this->json(['success' => false, 'message' => 'Regra não encontrada'], 404);
                return;
            }
            
            $rule['conditions'] = json_decode($rule['conditions'], true);
            
            $this->json(['success' => true, 'rule' => $rule]);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar regra: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao buscar regra: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualiza regra existente
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $ruleId = intval($_POST['rule_id'] ?? 0);
        
        if (!$ruleId) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }
        
        // Verifica se regra existe e pertence ao usuário
        $existingRule = $this->db->fetch("
            SELECT id FROM automation_rules WHERE id = :id AND user_id = :user_id
        ", ['id' => $ruleId, 'user_id' => $userId]);
        
        if (!$existingRule) {
            $this->json(['success' => false, 'message' => 'Regra não encontrada'], 404);
            return;
        }
        
        $name = $_POST['name'] ?? '';
        $productId = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
        $adAccountId = !empty($_POST['ad_account_id']) ? intval($_POST['ad_account_id']) : null;
        $targetType = $_POST['target_type'] ?? '';
        $action = $_POST['action'] ?? '';
        $frequency = $_POST['frequency'] ?? '1hour';
        $maxExecutions = intval($_POST['max_executions_per_day'] ?? 10);
        
        $conditions = [
            'metric' => $_POST['condition_metric'] ?? '',
            'operator' => $_POST['condition_operator'] ?? '',
            'value' => floatval($_POST['condition_value'] ?? 0),
            'period' => $_POST['condition_period'] ?? '24hours'
        ];
        
        try {
            $this->db->update('automation_rules', [
                'name' => $name,
                'product_id' => $productId,
                'ad_account_id' => $adAccountId,
                'target_type' => $targetType,
                'action' => $action,
                'conditions' => json_encode($conditions),
                'frequency' => $frequency,
                'max_executions_per_day' => $maxExecutions
            ], 'id = :id', ['id' => $ruleId]);
            
            $this->json(['success' => true, 'message' => 'Regra atualizada com sucesso!']);
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar regra: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao atualizar regra: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Deleta uma regra
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $ruleId = intval($_POST['rule_id'] ?? 0);
        
        if (!$ruleId) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }
        
        try {
            // Verifica se regra existe
            $rule = $this->db->fetch("
                SELECT id FROM automation_rules WHERE id = :id AND user_id = :user_id
            ", ['id' => $ruleId, 'user_id' => $userId]);
            
            if (!$rule) {
                $this->json(['success' => false, 'message' => 'Regra não encontrada'], 404);
                return;
            }
            
            // Deleta logs primeiro (cascade)
            $this->db->delete('automation_logs', 'rule_id = :id', ['id' => $ruleId]);
            
            // Deleta regra
            $this->db->delete('automation_rules', 'id = :id', ['id' => $ruleId]);
            
            $this->json(['success' => true, 'message' => 'Regra deletada com sucesso!']);
            
        } catch (Exception $e) {
            error_log("Erro ao deletar regra: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao deletar regra: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Alterna status da regra (ativo/inativo)
     */
    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $ruleId = intval($_POST['rule_id'] ?? 0);
        
        if (!$ruleId) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }
        
        try {
            $rule = $this->db->fetch("
                SELECT id, status FROM automation_rules 
                WHERE id = :id AND user_id = :user_id
            ", ['id' => $ruleId, 'user_id' => $userId]);
            
            if (!$rule) {
                $this->json(['success' => false, 'message' => 'Regra não encontrada'], 404);
                return;
            }
            
            $newStatus = $rule['status'] === 'active' ? 'inactive' : 'active';
            
            $this->db->update('automation_rules', 
                ['status' => $newStatus], 
                'id = :id', 
                ['id' => $ruleId]
            );
            
            $message = $newStatus === 'active' ? 'Regra ativada!' : 'Regra pausada!';
            
            $this->json(['success' => true, 'message' => $message, 'new_status' => $newStatus]);
            
        } catch (Exception $e) {
            error_log("Erro ao alterar status: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao alterar status: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Exibe logs de uma regra
     */
    public function logs() {
        $userId = $this->auth->id();
        $ruleId = intval($_GET['id'] ?? 0);
        
        if (!$ruleId) {
            $this->redirect('index.php?page=regras');
            return;
        }
        
        // Verifica se regra existe e pertence ao usuário
        $rule = $this->db->fetch("
            SELECT ar.*, aa.account_name 
            FROM automation_rules ar
            LEFT JOIN ad_accounts aa ON aa.id = ar.ad_account_id
            WHERE ar.id = :id AND ar.user_id = :user_id
        ", ['id' => $ruleId, 'user_id' => $userId]);
        
        if (!$rule) {
            $this->redirect('index.php?page=regras');
            return;
        }
        
        $rule['conditions'] = json_decode($rule['conditions'], true);
        
        // Busca logs
        $logs = $this->db->fetchAll("
            SELECT al.*, c.campaign_name
            FROM automation_logs al
            LEFT JOIN campaigns c ON c.id = al.campaign_id
            WHERE al.rule_id = :rule_id
            ORDER BY al.created_at DESC
            LIMIT 100
        ", ['rule_id' => $ruleId]);
        
        // Prepara dados para a view
        $data = [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'rule' => $rule,
            'logs' => $logs,
            'pageTitle' => 'Logs da Regra',
            'page' => 'regras'
        ];
        
        // Carrega views manualmente
        extract($data);
        
        require dirname(__DIR__) . '/views/layouts/header.php';
        require dirname(__DIR__) . '/views/layouts/sidebar.php';
        require dirname(__DIR__) . '/views/rules/logs.php';
        require dirname(__DIR__) . '/views/layouts/footer.php';
    }
    
    /**
     * MÉTODO PRINCIPAL - Executa regras automatizadas
     * Este método deve ser chamado por um cron job
     */
    public function execute() {
        try {
            $now = new DateTime();
            
            // Busca todas as regras ativas
            $rules = $this->db->fetchAll("
                SELECT ar.*, aa.access_token, aa.account_id as meta_account_id
                FROM automation_rules ar
                JOIN ad_accounts aa ON aa.id = ar.ad_account_id
                WHERE ar.status = 'active' AND aa.status = 'active'
            ");
            
            $executed = 0;
            $errors = 0;
            
            foreach ($rules as $rule) {
                try {
                    // Verifica frequência
                    if (!$this->shouldExecuteNow($rule, $now)) {
                        continue;
                    }
                    
                    // Verifica limite de execuções diárias
                    if ($rule['executions_today'] >= $rule['max_executions_per_day']) {
                        continue;
                    }
                    
                    // Executa regra
                    $this->executeRule($rule);
                    $executed++;
                    
                } catch (Exception $e) {
                    $errors++;
                    error_log("Erro ao executar regra {$rule['id']}: " . $e->getMessage());
                }
            }
            
            // Reseta contador diário à meia-noite
            $this->resetDailyCounters();
            
            echo json_encode([
                'success' => true,
                'executed' => $executed,
                'errors' => $errors,
                'timestamp' => $now->format('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("Erro crítico no execute: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Verifica se regra deve ser executada agora
     */
    private function shouldExecuteNow($rule, $now) {
        if (!$rule['last_execution']) {
            return true;
        }
        
        $lastExec = new DateTime($rule['last_execution']);
        $diff = $now->getTimestamp() - $lastExec->getTimestamp();
        
        $intervals = [
            '15min' => 900,
            '30min' => 1800,
            '1hour' => 3600,
            '6hours' => 21600,
            '12hours' => 43200,
            '24hours' => 86400
        ];
        
        $requiredInterval = $intervals[$rule['frequency']] ?? 3600;
        
        return $diff >= $requiredInterval;
    }
    
    /**
     * Executa uma regra específica
     */
    private function executeRule($rule) {
        $conditions = json_decode($rule['conditions'], true);
        
        // Busca campanhas/adsets/ads que atendem os filtros
        $targets = $this->getTargets($rule);
        
        $actionsExecuted = 0;
        
        foreach ($targets as $target) {
            // Calcula métrica baseada no período
            $metricValue = $this->calculateMetric($target, $conditions['metric'], $conditions['period']);
            
            // Verifica se condição é atendida
            if ($this->evaluateCondition($metricValue, $conditions['operator'], $conditions['value'])) {
                // Executa ação
                $result = $this->executeAction($rule, $target);
                
                // Registra log
                $this->logExecution($rule['id'], $target['id'], $rule['action'], $result);
                
                $actionsExecuted++;
            }
        }
        
        // Atualiza contadores da regra
        $this->db->update('automation_rules', [
            'last_execution' => date('Y-m-d H:i:s'),
            'executions_today' => $rule['executions_today'] + 1
        ], 'id = :id', ['id' => $rule['id']]);
        
        return $actionsExecuted;
    }
    
    /**
     * Busca targets (campanhas/adsets/ads) baseado nos filtros
     */
    private function getTargets($rule) {
        $table = $rule['target_type'] === 'campaign' ? 'campaigns' : 
                 ($rule['target_type'] === 'adset' ? 'adsets' : 'ads');
        
        $sql = "SELECT * FROM {$table} WHERE user_id = :user_id";
        $params = ['user_id' => $rule['user_id']];
        
        if ($rule['ad_account_id']) {
            $sql .= " AND ad_account_id = :ad_account_id";
            $params['ad_account_id'] = $rule['ad_account_id'];
        }
        
        if ($rule['product_id']) {
            $sql .= " AND product_id = :product_id";
            $params['product_id'] = $rule['product_id'];
        }
        
        // Filtro por status baseado na ação
        if ($rule['action'] === 'pause') {
            $sql .= " AND status = 'active'";
        } elseif ($rule['action'] === 'activate') {
            $sql .= " AND status = 'paused'";
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Calcula valor da métrica para período especificado
     * COMPLETO - Todas as métricas da UTMfy
     */
    private function calculateMetric($target, $metric, $period) {
        // Mapeia período para horas
        $hours = [
            '1hour' => 1,
            '6hours' => 6,
            '12hours' => 12,
            '24hours' => 24,
            '7days' => 168
        ][$period] ?? 24;
        
        // Para simplificar, usa dados atuais da campanha
        // Em produção, você pode buscar dados históricos ou da API
        
        $spent = floatval($target['spent'] ?? 0);
        $purchaseValue = floatval($target['purchase_value'] ?? 0);
        $conversions = intval($target['conversions'] ?? 0);
        $clicks = intval($target['clicks'] ?? 0);
        $impressions = intval($target['impressions'] ?? 0);
        $initiateCheckout = intval($target['initiate_checkout'] ?? 0);
        $pageViews = intval($target['page_views'] ?? 0);
        
        switch ($metric) {
            // Gastos e Orçamento
            case 'spend':
                return $spent;
            case 'budget':
                return floatval($target['budget'] ?? 0);
            
            // ROAS e ROI
            case 'roas':
                return $spent > 0 ? $purchaseValue / $spent : 0;
            case 'roi':
                return $spent > 0 ? (($purchaseValue - $spent) / $spent) * 100 : 0;
            
            // Lucro e Margem
            case 'profit':
                return $purchaseValue - $spent;
            case 'margin':
                return $purchaseValue > 0 ? (($purchaseValue - $spent) / $purchaseValue) * 100 : 0;
            
            // Conversões e Vendas
            case 'conversions':
                return $conversions;
            case 'sales':
                return $conversions; // Alias
            case 'initiate_checkout':
                return $initiateCheckout;
            
            // Custos por Ação
            case 'cpa':
                return $conversions > 0 ? $spent / $conversions : 999999;
            case 'cpi':
                return $initiateCheckout > 0 ? $spent / $initiateCheckout : 999999;
            case 'cost_per_conversion':
                return $conversions > 0 ? $spent / $conversions : 999999;
            case 'cpl':
                // CPL = Custo por Lead (pode usar conversões ou outro campo)
                return $conversions > 0 ? $spent / $conversions : 999999;
            
            // Cliques e CTR
            case 'clicks':
                return $clicks;
            case 'ctr':
                return $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
            case 'cpc':
                return $clicks > 0 ? $spent / $clicks : 0;
            
            // Impressões e CPM
            case 'cpm':
                return $impressions > 0 ? ($spent / $impressions) * 1000 : 0;
            
            // Visualizações
            case 'page_views':
                return $pageViews;
            case 'cpv':
                return $pageViews > 0 ? $spent / $pageViews : 999999;
            
            default:
                return 0;
        }
    }
    
    /**
     * Avalia se condição é verdadeira
     */
    private function evaluateCondition($value, $operator, $threshold) {
        switch ($operator) {
            case 'less_than':
                return $value < $threshold;
            case 'greater_than':
                return $value > $threshold;
            case 'equals':
                return abs($value - $threshold) < 0.01;
            case 'less_or_equal':
                return $value <= $threshold;
            case 'greater_or_equal':
                return $value >= $threshold;
            default:
                return false;
        }
    }
    
    /**
     * Executa ação na campanha/adset/ad
     */
    private function executeAction($rule, $target) {
        try {
            $table = $rule['target_type'] === 'campaign' ? 'campaigns' : 
                     ($rule['target_type'] === 'adset' ? 'adsets' : 'ads');
            
            switch ($rule['action']) {
                case 'pause':
                    // Atualiza no banco
                    $this->db->update($table, ['status' => 'paused'], 'id = :id', ['id' => $target['id']]);
                    
                    // Atualiza no Meta Ads
                    $this->updateMetaStatus($target['campaign_id'], $rule['access_token'], 'PAUSED');
                    
                    return 'success';
                    
                case 'activate':
                    $this->db->update($table, ['status' => 'active'], 'id = :id', ['id' => $target['id']]);
                    $this->updateMetaStatus($target['campaign_id'], $rule['access_token'], 'ACTIVE');
                    
                    return 'success';
                    
                case 'increase_budget':
                    $newBudget = $target['budget'] * 1.2; // Aumenta 20%
                    $this->db->update($table, ['budget' => $newBudget], 'id = :id', ['id' => $target['id']]);
                    $this->updateMetaBudget($target['campaign_id'], $rule['access_token'], $newBudget);
                    
                    return 'success';
                    
                case 'decrease_budget':
                    $newBudget = $target['budget'] * 0.8; // Reduz 20%
                    $this->db->update($table, ['budget' => $newBudget], 'id = :id', ['id' => $target['id']]);
                    $this->updateMetaBudget($target['campaign_id'], $rule['access_token'], $newBudget);
                    
                    return 'success';
                    
                default:
                    return 'failed';
            }
        } catch (Exception $e) {
            error_log("Erro ao executar ação: " . $e->getMessage());
            return 'failed';
        }
    }
    
    /**
     * Atualiza status no Meta Ads
     */
    private function updateMetaStatus($campaignId, $accessToken, $status) {
        $url = 'https://graph.facebook.com/v18.0/' . $campaignId;
        $postData = [
            'status' => $status,
            'access_token' => $accessToken
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Erro ao atualizar status no Meta: HTTP {$httpCode}");
        }
    }
    
    /**
     * Atualiza orçamento no Meta Ads
     */
    private function updateMetaBudget($campaignId, $accessToken, $budget) {
        $url = 'https://graph.facebook.com/v18.0/' . $campaignId;
        $postData = [
            'daily_budget' => intval($budget * 100), // Meta usa centavos
            'access_token' => $accessToken
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Erro ao atualizar orçamento no Meta: HTTP {$httpCode}");
        }
    }
    
    /**
     * Registra execução no log
     */
    private function logExecution($ruleId, $campaignId, $action, $result) {
        $this->db->insert('automation_logs', [
            'rule_id' => $ruleId,
            'campaign_id' => $campaignId,
            'action_taken' => $action,
            'result' => $result,
            'message' => $result === 'success' ? 'Ação executada com sucesso' : 'Falha ao executar ação'
        ]);
    }
    
    /**
     * Reseta contadores diários à meia-noite
     */
    private function resetDailyCounters() {
        $now = new DateTime();
        if ($now->format('H:i') === '00:00') {
            $this->db->query("UPDATE automation_rules SET executions_today = 0");
        }
    }
}
?>