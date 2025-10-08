<?php
/**
 * UTMTrack - Controller de Produtos
 * Arquivo: app/controllers/ProductController.php
 */

class ProductController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Lista produtos
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca produtos do usuário
        $products = $this->db->fetchAll("
            SELECT 
                p.*,
                COUNT(DISTINCT s.id) as total_sales,
                SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as total_revenue
            FROM products p
            LEFT JOIN sales s ON s.product_id = p.id
            WHERE p.user_id = :user_id
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ", ['user_id' => $userId]);
        
        // Calcula margem para cada produto
        foreach ($products as &$product) {
            $product['margin'] = $product['price'] > 0 
                ? (($product['price'] - $product['cost']) / $product['price']) * 100 
                : 0;
        }
        
        // Estatísticas
        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
                AVG(price) as avg_price
            FROM products 
            WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        
        $this->render('products/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'products' => $products,
            'stats' => $stats,
            'pageTitle' => 'Produtos'
        ]);
    }
    
    /**
     * Criar produto
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        $data = [
            'name' => $this->sanitize($this->post('name')),
            'sku' => $this->sanitize($this->post('sku')),
            'price' => (float) $this->post('price', 0),
            'cost' => (float) $this->post('cost', 0),
            'status' => $this->post('status', 'active')
        ];
        
        // Valida
        $errors = $this->validate($data, [
            'name' => 'required|min:3',
            'price' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->json(['success' => false, 'message' => 'Preencha todos os campos', 'errors' => $errors], 400);
            return;
        }
        
        // Verifica se SKU já existe
        if (!empty($data['sku'])) {
            $exists = $this->db->fetch("
                SELECT id FROM products 
                WHERE user_id = :user_id AND sku = :sku
            ", [
                'user_id' => $userId,
                'sku' => $data['sku']
            ]);
            
            if ($exists) {
                $this->json(['success' => false, 'message' => 'SKU já cadastrado'], 400);
                return;
            }
        }
        
        // Insere
        $productId = $this->db->insert('products', array_merge($data, [
            'user_id' => $userId
        ]));
        
        $this->json([
            'success' => true,
            'product_id' => $productId,
            'message' => 'Produto criado com sucesso!'
        ]);
    }
    
    /**
     * Atualizar produto
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $productId = $this->post('product_id');
        
        if (empty($productId)) {
            $this->json(['success' => false, 'message' => 'ID do produto não informado'], 400);
            return;
        }
        
        // Verifica se produto pertence ao usuário
        $product = $this->db->fetch("
            SELECT id FROM products 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $productId,
            'user_id' => $userId
        ]);
        
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Produto não encontrado'], 404);
            return;
        }
        
        $data = [
            'name' => $this->sanitize($this->post('name')),
            'sku' => $this->sanitize($this->post('sku')),
            'price' => (float) $this->post('price', 0),
            'cost' => (float) $this->post('cost', 0),
            'status' => $this->post('status', 'active')
        ];
        
        // Valida
        $errors = $this->validate($data, [
            'name' => 'required|min:3',
            'price' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->json(['success' => false, 'message' => 'Preencha todos os campos', 'errors' => $errors], 400);
            return;
        }
        
        // Verifica se SKU já existe em outro produto
        if (!empty($data['sku'])) {
            $exists = $this->db->fetch("
                SELECT id FROM products 
                WHERE user_id = :user_id 
                AND sku = :sku 
                AND id != :product_id
            ", [
                'user_id' => $userId,
                'sku' => $data['sku'],
                'product_id' => $productId
            ]);
            
            if ($exists) {
                $this->json(['success' => false, 'message' => 'SKU já cadastrado em outro produto'], 400);
                return;
            }
        }
        
        // Atualiza
        $this->db->update('products', 
            $data,
            'id = :id',
            ['id' => $productId]
        );
        
        $this->json([
            'success' => true,
            'message' => 'Produto atualizado com sucesso!'
        ]);
    }
    
    /**
     * Deletar produto
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $productId = $this->post('product_id');
        
        if (empty($productId)) {
            $this->json(['success' => false, 'message' => 'ID do produto não informado'], 400);
            return;
        }
        
        // Verifica se produto pertence ao usuário
        $product = $this->db->fetch("
            SELECT id FROM products 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $productId,
            'user_id' => $userId
        ]);
        
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Produto não encontrado'], 404);
            return;
        }
        
        // Verifica se há vendas vinculadas
        $sales = $this->db->fetch("
            SELECT COUNT(*) as total FROM sales 
            WHERE product_id = :product_id
        ", ['product_id' => $productId]);
        
        if ($sales['total'] > 0) {
            $this->json([
                'success' => false, 
                'message' => 'Não é possível deletar. Este produto possui ' . $sales['total'] . ' venda(s) vinculada(s).'
            ], 400);
            return;
        }
        
        // Deleta
        $this->db->delete('products', 'id = :id', ['id' => $productId]);
        
        $this->json([
            'success' => true,
            'message' => 'Produto deletado com sucesso!'
        ]);
    }
    
    /**
     * Buscar produto por ID (para edição)
     */
    public function get() {
        $userId = $this->auth->id();
        $productId = $this->get('id');
        
        if (empty($productId)) {
            $this->json(['success' => false, 'message' => 'ID não informado'], 400);
            return;
        }
        
        $product = $this->db->fetch("
            SELECT * FROM products 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $productId,
            'user_id' => $userId
        ]);
        
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Produto não encontrado'], 404);
            return;
        }
        
        $this->json([
            'success' => true,
            'product' => $product
        ]);
    }
}