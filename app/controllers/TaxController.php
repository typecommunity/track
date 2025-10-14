<?php
/**
 * UTMTrack - Controller de Taxas e Impostos
 * Arquivo: app/controllers/TaxController.php
 */

class TaxController extends Controller {
    
    /**
     * Página principal de taxas e impostos
     */
    public function index() {
        // Verifica autenticação
        if (!$this->auth->check()) {
            $this->redirect('index.php?page=login');
            return;
        }
        
        $userId = $this->auth->user()['id'];
        
        // Busca impostos e taxas separadamente
        $impostos = $this->getImpostos($userId);
        $taxas = $this->getTaxas($userId);
        
        // Busca produtos para edição de custos
        $products = $this->getProducts($userId);
        
        // Renderiza view
        $this->render('taxes/index', [
            'pageTitle' => 'Taxas e Impostos',
            'config' => $this->config,
            'user' => $this->auth->user(),
            'impostos' => $impostos,
            'taxes' => $taxas,
            'products' => $products
        ]);
    }
    
    /**
     * Cria novo imposto (AJAX)
     */
    public function storeImposto() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        
        // Validação
        $rate = floatval($_POST['rate'] ?? 0);
        $calculationRule = $_POST['calculation_rule'] ?? 'revenue';
        
        if ($rate <= 0) {
            return $this->json(['success' => false, 'message' => 'Alíquota deve ser maior que zero']);
        }
        
        // Verifica se já existe um imposto cadastrado
        $existingImposto = $this->db->fetch(
            "SELECT id FROM taxes WHERE user_id = ? AND category = 'imposto'",
            [$userId]
        );
        
        if ($existingImposto) {
            return $this->json(['success' => false, 'message' => 'Você já possui um imposto cadastrado. Edite o existente.']);
        }
        
        // Insere no banco
        $data = [
            'user_id' => $userId,
            'name' => 'Imposto',
            'rate' => $rate,
            'calculation_rule' => $calculationRule,
            'category' => 'imposto',
            'payment_method' => 'all',
            'type' => 'percentage',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $id = $this->db->insert('taxes', $data);
            
            return $this->json([
                'success' => true,
                'message' => 'Imposto cadastrado com sucesso!',
                'imposto' => array_merge($data, ['id' => $id])
            ]);
        } catch (Exception $e) {
            error_log('Erro ao criar imposto: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao cadastrar imposto'], 500);
        }
    }
    
    /**
     * Atualiza imposto (AJAX)
     */
    public function updateImposto() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $id = intval($_POST['id'] ?? 0);
        
        // Verifica se imposto existe e pertence ao usuário
        $imposto = $this->db->fetch(
            "SELECT * FROM taxes WHERE id = ? AND user_id = ? AND category = 'imposto'",
            [$id, $userId]
        );
        
        if (!$imposto) {
            return $this->json(['success' => false, 'message' => 'Imposto não encontrado']);
        }
        
        // Validação
        $rate = floatval($_POST['rate'] ?? $imposto['rate']);
        $calculationRule = $_POST['calculation_rule'] ?? $imposto['calculation_rule'];
        
        if ($rate <= 0) {
            return $this->json(['success' => false, 'message' => 'Alíquota deve ser maior que zero']);
        }
        
        // Atualiza no banco
        $data = [
            'rate' => $rate,
            'calculation_rule' => $calculationRule,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $this->db->update('taxes', $data, 'id = ? AND user_id = ?', [$id, $userId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Imposto atualizado com sucesso!'
            ]);
        } catch (Exception $e) {
            error_log('Erro ao atualizar imposto: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao atualizar imposto'], 500);
        }
    }
    
    /**
     * Remove imposto (AJAX)
     */
    public function deleteImposto() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $id = intval($_POST['id'] ?? 0);
        
        // Verifica se imposto existe e pertence ao usuário
        $imposto = $this->db->fetch(
            "SELECT * FROM taxes WHERE id = ? AND user_id = ? AND category = 'imposto'",
            [$id, $userId]
        );
        
        if (!$imposto) {
            return $this->json(['success' => false, 'message' => 'Imposto não encontrado']);
        }
        
        try {
            $this->db->delete('taxes', 'id = ? AND user_id = ?', [$id, $userId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Imposto removido com sucesso!'
            ]);
        } catch (Exception $e) {
            error_log('Erro ao remover imposto: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao remover imposto'], 500);
        }
    }
    
    /**
     * Cria nova taxa (AJAX)
     */
    public function store() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        
        // Validação
        $name = $this->sanitize($_POST['name'] ?? '');
        $rate = floatval($_POST['rate'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'all';
        $type = $_POST['type'] ?? 'percentage';
        $calculationRule = $_POST['calculation_rule'] ?? 'revenue';
        
        if (empty($name)) {
            return $this->json(['success' => false, 'message' => 'Nome é obrigatório']);
        }
        
        if ($rate <= 0) {
            return $this->json(['success' => false, 'message' => 'Taxa deve ser maior que zero']);
        }
        
        // Insere no banco
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'rate' => $rate,
            'payment_method' => $paymentMethod,
            'type' => $type,
            'calculation_rule' => $calculationRule,
            'category' => 'taxa',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $id = $this->db->insert('taxes', $data);
            
            return $this->json([
                'success' => true,
                'message' => 'Taxa cadastrada com sucesso!',
                'tax' => array_merge($data, ['id' => $id])
            ]);
        } catch (Exception $e) {
            error_log('Erro ao criar taxa: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao cadastrar taxa'], 500);
        }
    }
    
    /**
     * Atualiza taxa (AJAX)
     */
    public function update() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $id = intval($_POST['id'] ?? 0);
        
        // Verifica se taxa existe e pertence ao usuário
        $tax = $this->db->fetch(
            "SELECT * FROM taxes WHERE id = ? AND user_id = ? AND category = 'taxa'",
            [$id, $userId]
        );
        
        if (!$tax) {
            return $this->json(['success' => false, 'message' => 'Taxa não encontrada']);
        }
        
        // Validação
        $name = $this->sanitize($_POST['name'] ?? $tax['name']);
        $rate = floatval($_POST['rate'] ?? $tax['rate']);
        $paymentMethod = $_POST['payment_method'] ?? $tax['payment_method'];
        $type = $_POST['type'] ?? $tax['type'];
        $calculationRule = $_POST['calculation_rule'] ?? $tax['calculation_rule'];
        
        if (empty($name)) {
            return $this->json(['success' => false, 'message' => 'Nome é obrigatório']);
        }
        
        if ($rate <= 0) {
            return $this->json(['success' => false, 'message' => 'Taxa deve ser maior que zero']);
        }
        
        // Atualiza no banco
        $data = [
            'name' => $name,
            'rate' => $rate,
            'payment_method' => $paymentMethod,
            'type' => $type,
            'calculation_rule' => $calculationRule,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $this->db->update('taxes', $data, 'id = ? AND user_id = ?', [$id, $userId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Taxa atualizada com sucesso!'
            ]);
        } catch (Exception $e) {
            error_log('Erro ao atualizar taxa: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao atualizar taxa'], 500);
        }
    }
    
    /**
     * Remove taxa (AJAX)
     */
    public function delete() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $id = intval($_POST['id'] ?? 0);
        
        // Verifica se taxa existe e pertence ao usuário
        $tax = $this->db->fetch(
            "SELECT * FROM taxes WHERE id = ? AND user_id = ? AND category = 'taxa'",
            [$id, $userId]
        );
        
        if (!$tax) {
            return $this->json(['success' => false, 'message' => 'Taxa não encontrada']);
        }
        
        try {
            $this->db->delete('taxes', 'id = ? AND user_id = ?', [$id, $userId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Taxa removida com sucesso!'
            ]);
        } catch (Exception $e) {
            error_log('Erro ao remover taxa: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao remover taxa'], 500);
        }
    }
    
    /**
     * Busca uma taxa específica (AJAX)
     */
    public function getTax() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $id = intval($this->get('id', 0));
        
        $tax = $this->db->fetch(
            "SELECT * FROM taxes WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
        
        if (!$tax) {
            return $this->json(['success' => false, 'message' => 'Taxa não encontrada']);
        }
        
        return $this->json([
            'success' => true,
            'tax' => $tax
        ]);
    }
    
    /**
     * Busca um imposto específico (AJAX)
     */
    public function getImposto() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $id = intval($this->get('id', 0));
        
        $imposto = $this->db->fetch(
            "SELECT * FROM taxes WHERE id = ? AND user_id = ? AND category = 'imposto'",
            [$id, $userId]
        );
        
        if (!$imposto) {
            return $this->json(['success' => false, 'message' => 'Imposto não encontrado']);
        }
        
        return $this->json([
            'success' => true,
            'imposto' => $imposto
        ]);
    }
    
    /**
     * Atualiza custos de produtos (AJAX)
     */
    public function updateProductCosts() {
        if (!$this->auth->check()) {
            return $this->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }
        
        $userId = $this->auth->user()['id'];
        $products = $_POST['products'] ?? [];
        
        if (empty($products)) {
            return $this->json(['success' => false, 'message' => 'Nenhum produto enviado']);
        }
        
        try {
            $this->db->beginTransaction();
            
            foreach ($products as $productData) {
                $productId = intval($productData['id'] ?? 0);
                $cost = floatval($productData['cost'] ?? 0);
                
                // Verifica se produto pertence ao usuário
                $product = $this->db->fetch(
                    "SELECT id FROM products WHERE id = ? AND user_id = ?",
                    [$productId, $userId]
                );
                
                if ($product) {
                    $this->db->update(
                        'products',
                        ['cost' => $cost, 'updated_at' => date('Y-m-d H:i:s')],
                        'id = ? AND user_id = ?',
                        [$productId, $userId]
                    );
                }
            }
            
            $this->db->commit();
            
            return $this->json([
                'success' => true,
                'message' => 'Custos atualizados com sucesso!'
            ]);
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erro ao atualizar custos: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Erro ao atualizar custos'], 500);
        }
    }
    
    /**
     * Busca impostos do usuário
     */
    private function getImpostos($userId) {
        $sql = "SELECT * FROM taxes 
                WHERE user_id = ? AND category = 'imposto' 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    /**
     * Busca taxas do usuário
     */
    private function getTaxas($userId) {
        $sql = "SELECT * FROM taxes 
                WHERE user_id = ? AND category = 'taxa' 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    /**
     * Busca produtos do usuário
     */
    private function getProducts($userId) {
        $sql = "SELECT id, name, sku, price, cost, status FROM products 
                WHERE user_id = ? AND status = 'active' 
                ORDER BY name";
        return $this->db->fetchAll($sql, [$userId]);
    }
}