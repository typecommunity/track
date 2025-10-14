<?php
/**
 * CapiController - Controller para gerenciar Facebook CAPI
 * Arquivo: app/controllers/CapiController.php
 */

require_once dirname(__DIR__, 2) . '/core/FacebookCapi.php';

class CapiController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Página de configuração CAPI
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca pixels do usuário
        $pixels = $this->db->fetchAll("
            SELECT * FROM pixels 
            WHERE user_id = :user_id 
            AND platform = 'meta'
            ORDER BY created_at DESC
        ", ['user_id' => $userId]);
        
        // Busca configurações CAPI
        $capiConfigs = [];
        foreach ($pixels as $pixel) {
            $config = $this->db->fetch("
                SELECT * FROM capi_configs 
                WHERE pixel_id = :pixel_id
            ", ['pixel_id' => $pixel['id']]);
            
            if ($config) {
                $config['auto_events'] = json_decode($config['auto_events'], true);
                $config['custom_data_fields'] = json_decode($config['custom_data_fields'], true);
                $capiConfigs[$pixel['id']] = $config;
            }
        }
        
        // Estatísticas CAPI
        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_events,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as events_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as events_failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as events_pending
            FROM capi_events 
            WHERE user_id = :user_id
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", ['user_id' => $userId]);
        
        $this->render('capi/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pixels' => $pixels,
            'capiConfigs' => $capiConfigs,
            'stats' => $stats,
            'pageTitle' => 'Facebook CAPI'
        ]);
    }
    
    /**
     * Criar/Atualizar pixel
     */
    public function savePixel() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelDbId = $this->post('pixel_db_id');
        $pixelId = $this->sanitize($this->post('pixel_id'));
        $pixelName = $this->sanitize($this->post('pixel_name'));
        $accessToken = $this->post('access_token');
        $testEventCode = $this->sanitize($this->post('test_event_code'));
        
        // Validações
        if (empty($pixelId) || empty($pixelName) || empty($accessToken)) {
            $this->json(['success' => false, 'message' => 'Campos obrigatórios não preenchidos'], 400);
            return;
        }
        
        try {
            // Testa conexão com Facebook
            $capi = new FacebookCapi($pixelId, $accessToken, $testEventCode);
            $testResult = $capi->testConnection();
            
            if (!$testResult['success']) {
                $this->json([
                    'success' => false, 
                    'message' => 'Falha ao conectar com Facebook: ' . $testResult['error']
                ], 400);
                return;
            }
            
            $data = [
                'user_id' => $userId,
                'platform' => 'meta',
                'pixel_id' => $pixelId,
                'pixel_name' => $pixelName,
                'access_token' => $accessToken,
                'test_event_code' => $testEventCode,
                'capi_enabled' => 1,
                'status' => 'active'
            ];
            
            if ($pixelDbId) {
                // Atualiza pixel existente
                $this->db->update('pixels', $data, 'id = :id AND user_id = :user_id', [
                    'id' => $pixelDbId,
                    'user_id' => $userId
                ]);
                $savedPixelId = $pixelDbId;
            } else {
                // Cria novo pixel
                $savedPixelId = $this->db->insert('pixels', $data);
                
                // Cria configuração CAPI padrão
                $this->db->insert('capi_configs', [
                    'user_id' => $userId,
                    'pixel_id' => $savedPixelId,
                    'auto_events' => json_encode([
                        'PageView' => true,
                        'ViewContent' => true,
                        'InitiateCheckout' => true,
                        'AddToCart' => true,
                        'Purchase' => true,
                        'Lead' => true
                    ]),
                    'hash_user_data' => 1,
                    'send_page_view' => 1,
                    'send_view_content' => 1,
                    'send_initiate_checkout' => 1,
                    'send_add_to_cart' => 1,
                    'send_purchase' => 1,
                    'send_lead' => 1
                ]);
            }
            
            $this->json([
                'success' => true,
                'message' => 'Pixel salvo com sucesso',
                'pixel_id' => $savedPixelId
            ]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualizar configurações CAPI
     */
    public function updateConfig() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelId = $this->post('pixel_id');
        $config = $this->post('config');
        
        // Verifica se pixel pertence ao usuário
        $pixel = $this->db->fetch("
            SELECT * FROM pixels 
            WHERE id = :id AND user_id = :user_id
        ", ['id' => $pixelId, 'user_id' => $userId]);
        
        if (!$pixel) {
            $this->json(['success' => false, 'message' => 'Pixel não encontrado'], 404);
            return;
        }
        
        // Atualiza configuração
        $this->db->update('capi_configs', [
            'auto_events' => json_encode($config['auto_events'] ?? []),
            'hash_user_data' => $config['hash_user_data'] ?? 1,
            'send_page_view' => $config['send_page_view'] ?? 1,
            'send_view_content' => $config['send_view_content'] ?? 1,
            'send_initiate_checkout' => $config['send_initiate_checkout'] ?? 1,
            'send_add_to_cart' => $config['send_add_to_cart'] ?? 1,
            'send_purchase' => $config['send_purchase'] ?? 1,
            'send_lead' => $config['send_lead'] ?? 1,
            'custom_data_fields' => json_encode($config['custom_data_fields'] ?? [])
        ], 'pixel_id = :pixel_id', ['pixel_id' => $pixelId]);
        
        $this->json(['success' => true, 'message' => 'Configuração atualizada']);
    }
    
    /**
     * Deletar pixel
     */
    public function deletePixel() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelId = $this->post('pixel_id');
        
        // Verifica se pixel pertence ao usuário
        $pixel = $this->db->fetch("
            SELECT * FROM pixels 
            WHERE id = :id AND user_id = :user_id
        ", ['id' => $pixelId, 'user_id' => $userId]);
        
        if (!$pixel) {
            $this->json(['success' => false, 'message' => 'Pixel não encontrado'], 404);
            return;
        }
        
        // Deleta pixel (cascade vai deletar configs e eventos)
        $this->db->delete('pixels', 'id = :id', ['id' => $pixelId]);
        
        $this->json(['success' => true, 'message' => 'Pixel deletado com sucesso']);
    }
    
    /**
     * Estatísticas detalhadas
     */
    public function stats() {
        $userId = $this->auth->id();
        $pixelId = $_GET['pixel_id'] ?? null;
        
        $whereClause = "user_id = :user_id";
        $params = ['user_id' => $userId];
        
        if ($pixelId) {
            $whereClause .= " AND pixel_id = :pixel_id";
            $params['pixel_id'] = $pixelId;
        }
        
        // Estatísticas por evento
        $eventStats = $this->db->fetchAll("
            SELECT 
                event_name,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM capi_events
            WHERE {$whereClause}
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY event_name
            ORDER BY total DESC
        ", $params);
        
        // Eventos por dia (últimos 30 dias)
        $dailyEvents = $this->db->fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent
            FROM capi_events
            WHERE {$whereClause}
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $params);
        
        // Pixels
        $pixels = $this->db->fetchAll("
            SELECT * FROM pixels 
            WHERE user_id = :user_id 
            AND platform = 'meta'
            ORDER BY pixel_name
        ", ['user_id' => $userId]);
        
        $this->render('capi/stats', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'eventStats' => $eventStats,
            'dailyEvents' => $dailyEvents,
            'pixels' => $pixels,
            'selectedPixelId' => $pixelId,
            'pageTitle' => 'Estatísticas CAPI'
        ]);
    }
    
    /**
     * Logs CAPI
     */
    public function logs() {
        $userId = $this->auth->id();
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        $logs = $this->db->fetchAll("
            SELECT l.*, p.pixel_name
            FROM capi_logs l
            JOIN pixels p ON l.pixel_id = p.id
            WHERE l.user_id = :user_id
            ORDER BY l.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", ['user_id' => $userId]);
        
        // Decode JSON context
        foreach ($logs as &$log) {
            $log['context_data'] = json_decode($log['context_data'], true);
        }
        
        $total = $this->db->fetch("
            SELECT COUNT(*) as total FROM capi_logs 
            WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        
        $totalPages = ceil($total['total'] / $perPage);
        
        $this->render('capi/logs', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'pageTitle' => 'Logs CAPI'
        ]);
    }
    
    /**
     * Gerar script de rastreamento
     */
    public function script() {
        $userId = $this->auth->id();
        $pixelId = $_GET['pixel_id'] ?? null;
        
        if (!$pixelId) {
            $this->json(['success' => false, 'message' => 'Pixel ID não fornecido'], 400);
            return;
        }
        
        // Verifica se pixel pertence ao usuário
        $pixel = $this->db->fetch("
            SELECT * FROM pixels 
            WHERE id = :id AND user_id = :user_id
        ", ['id' => $pixelId, 'user_id' => $userId]);
        
        if (!$pixel) {
            $this->json(['success' => false, 'message' => 'Pixel não encontrado'], 404);
            return;
        }
        
        // Busca configurações
        $config = $this->db->fetch("
            SELECT * FROM capi_configs 
            WHERE pixel_id = :pixel_id
        ", ['pixel_id' => $pixelId]);
        
        $this->render('capi/script', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pixel' => $pixel,
            'capiConfig' => $config,
            'pageTitle' => 'Script CAPI'
        ]);
    }
}