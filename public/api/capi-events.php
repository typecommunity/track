<?php
/**
 * API Endpoint para receber eventos CAPI do frontend
 * Arquivo: public/api/capi-events.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/FacebookCapi.php';

try {
    // Lê dados do request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validações básicas
    if (empty($data['pixel_id'])) {
        throw new Exception('Pixel ID is required');
    }
    
    if (empty($data['event_name'])) {
        throw new Exception('Event name is required');
    }
    
    $db = Database::getInstance();
    
    // Busca informações do pixel
    $pixel = $db->fetch("
        SELECT p.*, c.* 
        FROM pixels p
        LEFT JOIN capi_configs c ON p.id = c.pixel_id
        WHERE p.pixel_id = :pixel_id 
        AND p.capi_enabled = 1
        AND p.status = 'active'
        LIMIT 1
    ", ['pixel_id' => $data['pixel_id']]);
    
    if (!$pixel) {
        throw new Exception('Pixel not found or CAPI not enabled');
    }
    
    // Verifica se o evento está habilitado nas configurações
    $eventName = $data['event_name'];
    $sendEventField = 'send_' . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $eventName));
    
    if (isset($pixel[$sendEventField]) && !$pixel[$sendEventField]) {
        // Evento desabilitado, retorna sucesso mas não envia
        echo json_encode([
            'success' => true,
            'message' => 'Event disabled in configuration',
            'sent' => false
        ]);
        exit;
    }
    
    // Prepara dados do usuário
    $userData = $data['user_data'] ?? [];
    
    // Adiciona dados automáticos
    $userData['client_ip_address'] = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 
                                      $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
                                      $_SERVER['REMOTE_ADDR'];
    $userData['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Processa cookies do Facebook
    if (!empty($_COOKIE['_fbc'])) {
        $userData['fbc'] = $_COOKIE['_fbc'];
    } elseif (!empty($data['fbclid'])) {
        // Constrói fbc a partir do fbclid
        $userData['fbc'] = 'fb.1.' . time() . '.' . $data['fbclid'];
    }
    
    if (!empty($_COOKIE['_fbp'])) {
        $userData['fbp'] = $_COOKIE['_fbp'];
    }
    
    // Adiciona fbclid se disponível
    if (!empty($data['fbclid'])) {
        $userData['fbclid'] = $data['fbclid'];
    }
    
    // Adiciona UTMs
    if (!empty($data['utm_params'])) {
        $userData['utm_source'] = $data['utm_params']['utm_source'] ?? null;
        $userData['utm_medium'] = $data['utm_params']['utm_medium'] ?? null;
        $userData['utm_campaign'] = $data['utm_params']['utm_campaign'] ?? null;
        $userData['utm_content'] = $data['utm_params']['utm_content'] ?? null;
        $userData['utm_term'] = $data['utm_params']['utm_term'] ?? null;
    }
    
    // Prepara custom data
    $customData = $data['custom_data'] ?? [];
    
    // Adiciona currency padrão se não fornecido
    if (!empty($customData['value']) && empty($customData['currency'])) {
        $customData['currency'] = 'BRL';
    }
    
    // Prepara evento para envio
    $eventData = [
        'event_name' => $eventName,
        'event_time' => $data['event_time'] ?? time(),
        'event_id' => $data['event_id'] ?? uniqid('evt_', true),
        'event_source_url' => $data['event_source_url'] ?? $_SERVER['HTTP_REFERER'] ?? null,
        'action_source' => 'website',
        'user_data' => $userData,
        'custom_data' => $customData
    ];
    
    // Inicializa Facebook CAPI
    $capi = new FacebookCapi(
        $pixel['pixel_id'],
        $pixel['access_token'],
        $pixel['test_event_code']
    );
    
    // Envia evento
    $result = $capi->sendEvent($eventData);
    
    // Retorna resposta
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Event sent successfully',
            'event_id' => $eventData['event_id'],
            'events_received' => $result['events_received'] ?? 1
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'message' => 'Failed to send event to Facebook'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}