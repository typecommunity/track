<?php
/**
 * UTMTrack - Controller de UTMs com CAPI Integrado
 * VERSÃO CORRIGIDA - Com suporte a actions
 * Arquivo: app/controllers/UtmController.php
 */

class UtmController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Página principal de UTMs/CAPI
     * ROTEADOR DE ACTIONS
     */
    public function index() {
        $action = $_GET['action'] ?? null;
        
        // Roteamento de actions
        switch ($action) {
            case 'setup':
                return $this->setup();
            case 'savePixel':
                return $this->savePixel();
            case 'scripts':
                return $this->scripts();
            case 'stats':
                return $this->stats();
            case 'logs':
                return $this->logs();
            case 'settings':
                return $this->settings();
            case 'updateSettings':
                return $this->updateSettings();
            case 'deletePixel':
                return $this->deletePixel();
            case 'toggleCapi':
                return $this->toggleCapi();
            case 'toggleEvent':
                return $this->toggleEvent();
            case 'updateToken':
                return $this->updateToken();
            case 'updatePixelName':
                return $this->updatePixelName();
            default:
                return $this->dashboard();
        }
    }
    
    /**
     * Dashboard principal
     */
    private function dashboard() {
        $userId = $this->auth->id();
        
        // Busca pixels configurados (qualquer um, mesmo sem capi_enabled)
        $pixels = $this->db->fetchAll("
            SELECT * FROM pixels 
            WHERE user_id = :user_id 
            AND platform = 'meta'
            ORDER BY created_at DESC
        ", ['user_id' => $userId]);
        
        // Se não tem pixel, redireciona para configuração
        if (empty($pixels)) {
            header('Location: index.php?page=utms&action=setup');
            exit;
        }
        
        // Adiciona estatísticas para cada pixel
        foreach ($pixels as &$pixel) {
            $pixelStats = $this->db->fetch("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM capi_events 
                WHERE pixel_id = :pixel_id 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ", ['pixel_id' => $pixel['id']]);
            
            $pixel['stats'] = $pixelStats ?: ['total' => 0, 'sent' => 0, 'failed' => 0];
        }
        
        // Estatísticas dos últimos 30 dias
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
        
        // Se stats é null, inicializa com zeros
        if (!$stats) {
            $stats = [
                'total_events' => 0,
                'events_sent' => 0,
                'events_failed' => 0,
                'events_pending' => 0
            ];
        }
        
        // Eventos por tipo
        $eventsByType = $this->db->fetchAll("
            SELECT 
                event_name,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent
            FROM capi_events
            WHERE user_id = :user_id
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY event_name
            ORDER BY total DESC
            LIMIT 10
        ", ['user_id' => $userId]);
        
        // Eventos por dia (últimos 7 dias)
        $eventsByDay = $this->db->fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent
            FROM capi_events
            WHERE user_id = :user_id
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", ['user_id' => $userId]);
        
        $this->render('utms/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pixels' => $pixels,
            'stats' => $stats,
            'eventsByType' => $eventsByType,
            'eventsByDay' => $eventsByDay,
            'pageTitle' => 'UTMTrack - Rastreamento Automático'
        ]);
    }
    
    /**
     * Página de configuração inicial
     */
    private function setup() {
        $userId = $this->auth->id();
        
        // Busca pixels existentes
        $existingPixels = $this->db->fetchAll("
            SELECT * FROM pixels 
            WHERE user_id = :user_id AND platform = 'meta'
        ", ['user_id' => $userId]);
        
        $this->render('utms/setup', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'existingPixels' => $existingPixels,
            'pageTitle' => 'Configurar Rastreamento'
        ]);
    }
    
    /**
     * Salvar configuração do pixel
     */
    private function savePixel() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelId = $this->sanitize($this->post('pixel_id'));
        $pixelName = $this->sanitize($this->post('pixel_name'));
        $accessToken = $this->post('access_token');
        
        if (empty($pixelId) || empty($accessToken)) {
            $this->json(['success' => false, 'message' => 'Pixel ID e Token são obrigatórios'], 400);
            return;
        }
        
        try {
            // Testa conexão com Facebook
            require_once dirname(__DIR__, 2) . '/core/FacebookCapi.php';
            $capi = new FacebookCapi($pixelId, $accessToken);
            $testResult = $capi->testConnection();
            
            if (!$testResult['success']) {
                $this->json([
                    'success' => false, 
                    'message' => 'Falha ao conectar: ' . $testResult['error']
                ], 400);
                return;
            }
            
            // Salva pixel
            $savedPixelId = $this->db->insert('pixels', [
                'user_id' => $userId,
                'platform' => 'meta',
                'pixel_id' => $pixelId,
                'pixel_name' => $pixelName ?: "Pixel {$pixelId}",
                'access_token' => $accessToken,
                'capi_enabled' => 1,
                'status' => 'active'
            ]);
            
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
                'send_page_view' => 1,
                'send_view_content' => 1,
                'send_initiate_checkout' => 1,
                'send_add_to_cart' => 1,
                'send_purchase' => 1,
                'send_lead' => 1
            ]);
            
            $this->json([
                'success' => true,
                'message' => 'Pixel configurado com sucesso!',
                'pixel_id' => $savedPixelId
            ]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Página do script de instalação
     */
    private function scripts() {
        $userId = $this->auth->id();
        
        $pixels = $this->db->fetchAll("
            SELECT * FROM pixels 
            WHERE user_id = :user_id 
            AND platform = 'meta'
            AND capi_enabled = 1
            ORDER BY created_at DESC
        ", ['user_id' => $userId]);
        
        if (empty($pixels)) {
            header('Location: index.php?page=utms&action=setup');
            exit;
        }
        
        $this->render('utms/scripts', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pixels' => $pixels,
            'user_id' => $userId,
            'pageTitle' => 'Script de Instalação'
        ]);
    }
    
    /**
     * Estatísticas detalhadas
     */
    private function stats() {
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
        
        // Eventos por dia
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
        
        $this->render('utms/stats', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'eventStats' => $eventStats,
            'dailyEvents' => $dailyEvents,
            'pixels' => $pixels,
            'selectedPixelId' => $pixelId,
            'pageTitle' => 'Estatísticas Detalhadas'
        ]);
    }
    
    /**
     * Logs do sistema
     */
    private function logs() {
        $userId = $this->auth->id();
        $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
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
        
        foreach ($logs as &$log) {
            $log['context_data'] = json_decode($log['context_data'], true);
        }
        
        $total = $this->db->fetch("
            SELECT COUNT(*) as total FROM capi_logs 
            WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        
        $totalPages = ceil($total['total'] / $perPage);
        
        $this->render('utms/logs', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'pageTitle' => 'Logs do Sistema'
        ]);
    }
    
    /**
     * Configurações do pixel
     */
    private function settings() {
        $userId = $this->auth->id();
        
        $pixels = $this->db->fetchAll("
            SELECT p.*, c.*
            FROM pixels p
            LEFT JOIN capi_configs c ON p.id = c.pixel_id
            WHERE p.user_id = :user_id 
            AND p.platform = 'meta'
            ORDER BY p.created_at DESC
        ", ['user_id' => $userId]);
        
        foreach ($pixels as &$pixel) {
            if (!empty($pixel['auto_events'])) {
                $pixel['auto_events'] = json_decode($pixel['auto_events'], true);
            }
        }
        
        $this->render('utms/settings', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pixels' => $pixels,
            'pageTitle' => 'Configurações'
        ]);
    }
    
    /**
     * Atualizar configurações
     */
    private function updateSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelId = $this->post('pixel_id');
        $settings = $this->post('settings');
        
        // Verifica propriedade
        $pixel = $this->db->fetch("
            SELECT * FROM pixels 
            WHERE id = :id AND user_id = :user_id
        ", ['id' => $pixelId, 'user_id' => $userId]);
        
        if (!$pixel) {
            $this->json(['success' => false, 'message' => 'Pixel não encontrado'], 404);
            return;
        }
        
        // Atualiza configurações
        $this->db->update('capi_configs', [
            'auto_events' => json_encode($settings['auto_events'] ?? []),
            'send_page_view' => $settings['send_page_view'] ?? 1,
            'send_view_content' => $settings['send_view_content'] ?? 1,
            'send_initiate_checkout' => $settings['send_initiate_checkout'] ?? 1,
            'send_add_to_cart' => $settings['send_add_to_cart'] ?? 1,
            'send_purchase' => $settings['send_purchase'] ?? 1,
            'send_lead' => $settings['send_lead'] ?? 1
        ], 'pixel_id = :pixel_id', ['pixel_id' => $pixelId]);
        
        $this->json(['success' => true, 'message' => 'Configurações atualizadas']);
    }
    
    /**
     * Deletar pixel
     */
    private function deletePixel() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelId = $this->post('pixel_id');
        
        $pixel = $this->db->fetch("
            SELECT * FROM pixels 
            WHERE id = :id AND user_id = :user_id
        ", ['id' => $pixelId, 'user_id' => $userId]);
        
        if (!$pixel) {
            $this->json(['success' => false, 'message' => 'Pixel não encontrado'], 404);
            return;
        }
        
        $this->db->delete('pixels', 'id = :id', ['id' => $pixelId]);
        
        $this->json(['success' => true, 'message' => 'Pixel deletado com sucesso']);
    }
    
    /**
     * Toggle CAPI enabled
     */
    private function toggleCapi() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelId = $this->post('pixel_id');
        $enabled = $this->post('capi_enabled');
        
        $pixel = $this->db->fetch("
            SELECT * FROM pixels 
            WHERE id = :id AND user_id = :user_id
        ", ['id' => $pixelId, 'user_id' => $userId]);
        
        if (!$pixel) {
            $this->json(['success' => false, 'message' => 'Pixel não encontrado'], 404);
            return;
        }
        
        $this->db->update('pixels', [
            'capi_enabled' => $enabled ? 1 : 0
        ], 'id = :id', ['id' => $pixelId]);
        
        $this->json(['success' => true]);
    }
    
    /**
     * Toggle evento automático
     */
    private function toggleEvent() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelId = $this->post('pixel_id');
        $eventName = $this->post('event_name');
        $enabled = $this->post('enabled');
        
        $pixel = $this->db->fetch("
            SELECT * FROM pixels 
            WHERE id = :id AND user_id = :user_id
        ", ['id' => $pixelId, 'user_id' => $userId]);
        
        if (!$pixel) {
            $this->json(['success' => false, 'message' => 'Pixel não encontrado'], 404);
            return;
        }
        
        // Busca configuração atual
        $config = $this->db->fetch("
            SELECT * FROM capi_configs 
            WHERE pixel_id = :pixel_id
        ", ['pixel_id' => $pixelId]);
        
        if ($config) {
            $autoEvents = json_decode($config['auto_events'], true);
            $autoEvents[$eventName] = (bool)$enabled;
            
            $this->db->update('capi_configs', [
                'auto_events' => json_encode($autoEvents)
            ], 'pixel_id = :pixel_id', ['pixel_id' => $pixelId]);
        }
        
        $this->json(['success' => true]);
    }
    
    /**
     * Atualizar token
     */
    private function updateToken() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelId = $this->post('pixel_id');
        $accessToken = $this->post('access_token');
        $testEventCode = $this->post('test_event_code');
        
        $pixel = $this->db->fetch("
            SELECT * FROM pixels 
            WHERE id = :id AND user_id = :user_id
        ", ['id' => $pixelId, 'user_id' => $userId]);
        
        if (!$pixel) {
            $this->json(['success' => false, 'message' => 'Pixel não encontrado'], 404);
            return;
        }
        
        // Testa novo token
        try {
            require_once dirname(__DIR__, 2) . '/core/FacebookCapi.php';
            $capi = new FacebookCapi($pixel['pixel_id'], $accessToken, $testEventCode);
            $testResult = $capi->testConnection();
            
            if (!$testResult['success']) {
                $this->json([
                    'success' => false, 
                    'message' => 'Token inválido: ' . $testResult['error']
                ], 400);
                return;
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            return;
        }
        
        // Atualiza token
        $this->db->update('pixels', [
            'access_token' => $accessToken,
            'test_event_code' => $testEventCode
        ], 'id = :id', ['id' => $pixelId]);
        
        $this->json(['success' => true, 'message' => 'Token atualizado com sucesso']);
    }
    
    /**
     * Atualizar nome do pixel
     */
    private function updatePixelName() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $pixelId = $this->post('pixel_id');
        $pixelName = $this->sanitize($this->post('pixel_name'));
        
        if (empty($pixelName)) {
            $this->json(['success' => false, 'message' => 'Nome do pixel é obrigatório'], 400);
            return;
        }
        
        // Verifica propriedade
        $pixel = $this->db->fetch("
            SELECT * FROM pixels 
            WHERE id = :id AND user_id = :user_id
        ", ['id' => $pixelId, 'user_id' => $userId]);
        
        if (!$pixel) {
            $this->json(['success' => false, 'message' => 'Pixel não encontrado'], 404);
            return;
        }
        
        // Atualiza nome
        $this->db->update('pixels', [
            'pixel_name' => $pixelName
        ], 'id = :id', ['id' => $pixelId]);
        
        $this->json(['success' => true, 'message' => 'Nome do pixel atualizado com sucesso']);
    }
}