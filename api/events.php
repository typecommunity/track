<?php
/**
 * UTMTrack - API de Eventos
 * Recebe eventos do script de rastreamento
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../core/Database.php';

try {
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON');
    }
    
    // Validate required fields
    if (empty($data['user_id']) || empty($data['event_type'])) {
        throw new Exception('Missing required fields');
    }
    
    $db = Database::getInstance();
    
    // Get or create UTM record
    $utmId = null;
    if (!empty($data['utm_params'])) {
        $utmParams = $data['utm_params'];
        
        // Check if UTM already exists
        $existingUtm = $db->fetch("
            SELECT id FROM utms 
            WHERE user_id = :user_id
            AND utm_source = :source
            AND utm_medium = :medium
            AND utm_campaign = :campaign
            ORDER BY created_at DESC
            LIMIT 1
        ", [
            'user_id' => $data['user_id'],
            'source' => $utmParams['utm_source'] ?? '',
            'medium' => $utmParams['utm_medium'] ?? '',
            'campaign' => $utmParams['utm_campaign'] ?? ''
        ]);
        
        if ($existingUtm) {
            $utmId = $existingUtm['id'];
            
            // Increment clicks
            $db->query("
                UPDATE utms 
                SET clicks = clicks + 1 
                WHERE id = :id
            ", ['id' => $utmId]);
        } else {
            // Create new UTM record
            $utmId = $db->insert('utms', [
                'user_id' => $data['user_id'],
                'utm_source' => $utmParams['utm_source'] ?? null,
                'utm_medium' => $utmParams['utm_medium'] ?? null,
                'utm_campaign' => $utmParams['utm_campaign'] ?? null,
                'utm_content' => $utmParams['utm_content'] ?? null,
                'utm_term' => $utmParams['utm_term'] ?? null,
                'clicks' => 1
            ]);
        }
    }
    
    // Generate session ID if not exists
    $sessionId = $data['session_id'] ?? md5($data['user_id'] . time() . rand());
    
    // Save event
    $eventId = $db->insert('funnel_events', [
        'user_id' => $data['user_id'],
        'utm_id' => $utmId,
        'session_id' => $sessionId,
        'event_type' => $data['event_type'],
        'event_data' => json_encode($data['data'] ?? []),
        'page_url' => $data['page_url'] ?? null,
        'referrer' => $data['referrer'] ?? null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Success response
    echo json_encode([
        'success' => true,
        'event_id' => $eventId,
        'utm_id' => $utmId,
        'session_id' => $sessionId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}