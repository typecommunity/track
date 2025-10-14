<?php
/**
 * UTMTrack - Controller de Despesas
 * Arquivo: app/controllers/ExpenseController.php
 */

class ExpenseController extends Controller {
    
    /**
     * Página principal de despesas
     */
    public function index() {
        // Verifica autenticação
        if (!$this->auth->check()) {
            $this->redirect('index.php?page=login');
            return;
        }
        
        $userId = $this->auth->user()['id'];
        
        // Pega filtros da URL usando método get() do Controller
        $period = $this->get('period', 'month');
        $category = $this->get('category', '');
        
        // Define período
        $dateFilter = $this->getDateFilter($period);
        
        // Busca despesas
        $expenses = $this->getExpenses($userId, $dateFilter, $category);
        
        // Busca categorias únicas
        $categories = $this->getCategories($userId);
        
        // Calcula totais
        $totals = $this->calculateTotals($expenses);
        
        // Renderiza view
        $this->render('expenses/index', [
            'pageTitle' => 'Despesas',
            'config' => $this->config,
            'user' => $this->auth->user(),
            'expenses' => $expenses,
            'categories' => $categories,
            'totals' => $totals,
            'period' => $period,
            'selectedCategory' => $category
        ]);
    }
    
    /**
     * Cria nova despesa (AJAX)
     */
    public function store() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        
        // Validação
        $description = $this->sanitize($_POST['description'] ?? '');
        $category = $this->sanitize($_POST['category'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $expenseType = $_POST['expense_type'] ?? 'unico';
        $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');
        
        if (empty($description)) {
            return $this->json(['success' => false, 'message' => 'Descrição é obrigatória']);
        }
        
        if ($amount <= 0) {
            return $this->json(['success' => false, 'message' => 'Valor deve ser maior que zero']);
        }
        
        // Insere no banco
        $data = [
            'user_id' => $userId,
            'description' => $description,
            'category' => $category,
            'amount' => $amount,
            'expense_type' => $expenseType,
            'expense_date' => $expenseDate,
            'status' => 'ativo'
        ];
        
        try {
            $id = $this->db->insert('expenses', $data);
            
            return $this->json([
                'success' => true,
                'message' => 'Despesa cadastrada com sucesso!',
                'expense' => array_merge($data, ['id' => $id])
            ]);
        } catch (Exception $e) {
            error_log('Erro ao criar despesa: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao cadastrar despesa'], 500);
        }
    }
    
    /**
     * Atualiza despesa (AJAX)
     */
    public function update() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $id = intval($_POST['id'] ?? 0);
        
        // Verifica se despesa existe e pertence ao usuário
        $expense = $this->db->fetch(
            "SELECT * FROM expenses WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
        
        if (!$expense) {
            return $this->json(['success' => false, 'message' => 'Despesa não encontrada']);
        }
        
        // Validação
        $description = $this->sanitize($_POST['description'] ?? $expense['description']);
        $category = $this->sanitize($_POST['category'] ?? $expense['category']);
        $amount = floatval($_POST['amount'] ?? $expense['amount']);
        $expenseType = $_POST['expense_type'] ?? $expense['expense_type'];
        $expenseDate = $_POST['expense_date'] ?? $expense['expense_date'];
        
        if (empty($description)) {
            return $this->json(['success' => false, 'message' => 'Descrição é obrigatória']);
        }
        
        if ($amount <= 0) {
            return $this->json(['success' => false, 'message' => 'Valor deve ser maior que zero']);
        }
        
        // Atualiza no banco
        $data = [
            'description' => $description,
            'category' => $category,
            'amount' => $amount,
            'expense_type' => $expenseType,
            'expense_date' => $expenseDate
        ];
        
        try {
            $this->db->update('expenses', $data, 'id = ? AND user_id = ?', [$id, $userId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Despesa atualizada com sucesso!'
            ]);
        } catch (Exception $e) {
            error_log('Erro ao atualizar despesa: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao atualizar despesa'], 500);
        }
    }
    
    /**
     * Busca uma despesa específica (AJAX)
     */
    public function getExpense() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $id = intval($this->get('id', 0)); // Usando método get() do Controller pai
        
        $expense = $this->db->fetch(
            "SELECT * FROM expenses WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
        
        if (!$expense) {
            return $this->json(['success' => false, 'message' => 'Despesa não encontrada']);
        }
        
        return $this->json([
            'success' => true,
            'expense' => $expense
        ]);
    }
    
    /**
     * Remove despesa (AJAX)
     */
    public function delete() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $id = intval($_POST['id'] ?? 0);
        
        // Verifica se despesa existe e pertence ao usuário
        $expense = $this->db->fetch(
            "SELECT * FROM expenses WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
        
        if (!$expense) {
            return $this->json(['success' => false, 'message' => 'Despesa não encontrada']);
        }
        
        try {
            $this->db->delete('expenses', 'id = ? AND user_id = ?', [$id, $userId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Despesa removida com sucesso!'
            ]);
        } catch (Exception $e) {
            error_log('Erro ao remover despesa: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao remover despesa'], 500);
        }
    }
    
    /**
     * Busca despesas do usuário
     */
    private function getExpenses($userId, $dateFilter, $category = '') {
        $sql = "SELECT * FROM expenses WHERE user_id = ? AND status = 'ativo'";
        $params = [$userId];
        
        // Filtro de data
        if ($dateFilter['start'] && $dateFilter['end']) {
            $sql .= " AND expense_date BETWEEN ? AND ?";
            $params[] = $dateFilter['start'];
            $params[] = $dateFilter['end'];
        }
        
        // Filtro de categoria
        if (!empty($category)) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY expense_date DESC, created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Busca categorias únicas do usuário
     */
    private function getCategories($userId) {
        $sql = "SELECT DISTINCT category FROM expenses 
                WHERE user_id = ? AND category IS NOT NULL AND category != '' 
                ORDER BY category";
        
        $result = $this->db->fetchAll($sql, [$userId]);
        
        return array_column($result, 'category');
    }
    
    /**
     * Calcula totais das despesas
     */
    private function calculateTotals($expenses) {
        $totalUnico = 0;
        $totalRecorrente = 0;
        $totalGeral = 0;
        
        foreach ($expenses as $expense) {
            $amount = floatval($expense['amount']);
            $totalGeral += $amount;
            
            if ($expense['expense_type'] === 'unico') {
                $totalUnico += $amount;
            } else {
                $totalRecorrente += $amount;
            }
        }
        
        return [
            'unico' => $totalUnico,
            'recorrente' => $totalRecorrente,
            'total' => $totalGeral,
            'count' => count($expenses)
        ];
    }
    
    /**
     * Define filtro de data baseado no período
     */
    private function getDateFilter($period) {
        $start = null;
        $end = date('Y-m-d');
        
        switch ($period) {
            case 'today':
                $start = date('Y-m-d');
                break;
            case 'week':
                $start = date('Y-m-d', strtotime('-7 days'));
                break;
            case 'month':
                $start = date('Y-m-01'); // Primeiro dia do mês
                break;
            case 'year':
                $start = date('Y-01-01'); // Primeiro dia do ano
                break;
            case 'all':
            default:
                $start = null;
                break;
        }
        
        return ['start' => $start, 'end' => $end];
    }
}