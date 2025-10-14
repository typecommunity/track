<?php
/**
 * FacebookCapi - Classe para integração com Facebook Conversions API
 * Arquivo: core/FacebookCapi.php
 */

class FacebookCapi {
    
    private $db;
    private $pixelId;
    private $accessToken;
    private $testEventCode;
    private $apiVersion = 'v21.0';
    private $endpoint;
    
    const HASH_ALGORITHM = 'sha256';
    
    public function __construct($pixelId, $accessToken, $testEventCode = null) {
        $this->db = Database::getInstance();
        $this->pixelId = $pixelId;
        $this->accessToken = $accessToken;
        $this->testEventCode = $testEventCode;
        $this->endpoint = "https://graph.facebook.com/{$this->apiVersion}/{$pixelId}/events";
    }
    
    /**
     * Envia evento para o Facebook CAPI
     */
    public function sendEvent($eventData) {
        try {
            // Prepara dados do evento
            $event = $this->prepareEvent($eventData);
            
            // Salva evento no banco (antes de enviar)
            $eventId = $this->saveEventToDatabase($event, 'pending');
            
            // Envia para Facebook
            $response = $this->sendToFacebook([$event]);
            
            // Atualiza status no banco
            if ($response['success']) {
                $this->updateEventStatus($eventId, 'sent', $response);
                $this->logInfo("Evento enviado com sucesso", [
                    'event_id' => $event['event_id'],
                    'event_name' => $event['event_name']
                ]);
            } else {
                $this->updateEventStatus($eventId, 'failed', $response);
                $this->logError("Falha ao enviar evento", [
                    'event_id' => $event['event_id'],
                    'error' => $response['error']
                ]);
            }
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError("Exceção ao enviar evento", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Envia múltiplos eventos em lote
     */
    public function sendBatchEvents($eventsData) {
        try {
            $events = [];
            $eventIds = [];
            
            foreach ($eventsData as $eventData) {
                $event = $this->prepareEvent($eventData);
                $events[] = $event;
                
                // Salva cada evento no banco
                $eventIds[] = $this->saveEventToDatabase($event, 'pending');
            }
            
            // Envia lote para Facebook
            $response = $this->sendToFacebook($events);
            
            // Atualiza status de todos os eventos
            foreach ($eventIds as $eventId) {
                $status = $response['success'] ? 'sent' : 'failed';
                $this->updateEventStatus($eventId, $status, $response);
            }
            
            return $response;
            
        } catch (Exception $e) {
            $this->logError("Exceção ao enviar lote de eventos", [
                'message' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Prepara evento no formato do Facebook CAPI
     */
    private function prepareEvent($data) {
        $event = [
            'event_name' => $data['event_name'],
            'event_time' => $data['event_time'] ?? time(),
            'event_id' => $data['event_id'] ?? $this->generateEventId(),
            'event_source_url' => $data['event_source_url'] ?? null,
            'action_source' => $data['action_source'] ?? 'website',
            'user_data' => $this->prepareUserData($data['user_data'] ?? []),
            'custom_data' => $this->prepareCustomData($data['custom_data'] ?? [])
        ];
        
        // Adiciona opt_out se fornecido
        if (isset($data['opt_out'])) {
            $event['opt_out'] = (bool) $data['opt_out'];
        }
        
        return $event;
    }
    
    /**
     * Prepara dados do usuário (com hash)
     */
    private function prepareUserData($userData) {
        $prepared = [];
        
        // Campos que devem ser hasheados
        $hashFields = ['em', 'ph', 'fn', 'ln', 'ct', 'st', 'zp', 'country', 'db', 'ge'];
        
        foreach ($userData as $key => $value) {
            if (empty($value)) continue;
            
            // Normaliza o valor antes de hashear
            $normalizedValue = $this->normalizeValue($key, $value);
            
            // Hashea campos sensíveis
            if (in_array($key, $hashFields)) {
                $prepared[$key] = $this->hashValue($normalizedValue);
            } else {
                $prepared[$key] = $normalizedValue;
            }
        }
        
        // Adiciona cookies do Facebook se disponíveis
        if (!empty($userData['fbc'])) {
            $prepared['fbc'] = $userData['fbc'];
        }
        if (!empty($userData['fbp'])) {
            $prepared['fbp'] = $userData['fbp'];
        }
        
        // Adiciona Click ID se disponível
        if (!empty($userData['fbclid'])) {
            $prepared['fbclid'] = $userData['fbclid'];
        }
        
        // Adiciona IP e User Agent (não hasheados)
        if (!empty($userData['client_ip_address'])) {
            $prepared['client_ip_address'] = $userData['client_ip_address'];
        }
        if (!empty($userData['client_user_agent'])) {
            $prepared['client_user_agent'] = $userData['client_user_agent'];
        }
        
        return $prepared;
    }
    
    /**
     * Prepara dados customizados do evento
     */
    private function prepareCustomData($customData) {
        $prepared = [];
        
        // Campos padrão de custom_data
        $standardFields = [
            'value', 'currency', 'content_name', 'content_category',
            'content_ids', 'content_type', 'contents', 'num_items',
            'predicted_ltv', 'status', 'search_string'
        ];
        
        foreach ($standardFields as $field) {
            if (isset($customData[$field]) && !empty($customData[$field])) {
                $prepared[$field] = $customData[$field];
            }
        }
        
        // Adiciona campos customizados extras
        foreach ($customData as $key => $value) {
            if (!in_array($key, $standardFields) && !empty($value)) {
                $prepared[$key] = $value;
            }
        }
        
        return $prepared;
    }
    
    /**
     * Normaliza valor antes de hashear
     */
    private function normalizeValue($field, $value) {
        $value = trim(strtolower($value));
        
        switch ($field) {
            case 'em': // Email
                return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
                
            case 'ph': // Phone
                return preg_replace('/[^0-9]/', '', $value);
                
            case 'fn': // First Name
            case 'ln': // Last Name
            case 'ct': // City
                return preg_replace('/[^a-z]/', '', $value);
                
            case 'zp': // Zip Code
                return preg_replace('/[^0-9a-z]/', '', $value);
                
            case 'country': // Country (ISO 2 letters)
                return substr($value, 0, 2);
                
            case 'st': // State (2 letters for US)
                return substr($value, 0, 2);
                
            case 'ge': // Gender (m/f)
                return in_array($value[0], ['m', 'f']) ? $value[0] : '';
                
            case 'db': // Date of Birth (YYYYMMDD)
                return preg_replace('/[^0-9]/', '', $value);
                
            default:
                return $value;
        }
    }
    
    /**
     * Hashea valor usando SHA256
     */
    private function hashValue($value) {
        return hash(self::HASH_ALGORITHM, $value);
    }
    
    /**
     * Envia eventos para o Facebook
     */
    private function sendToFacebook($events) {
        $payload = [
            'data' => $events,
            'access_token' => $this->accessToken
        ];
        
        // Adiciona test_event_code se estiver em modo de teste
        if ($this->testEventCode) {
            $payload['test_event_code'] = $this->testEventCode;
        }
        
        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return [
                'success' => false,
                'error' => $curlError,
                'http_code' => $httpCode
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'response' => $responseData,
                'http_code' => $httpCode,
                'events_received' => $responseData['events_received'] ?? 0,
                'messages' => $responseData['messages'] ?? []
            ];
        } else {
            return [
                'success' => false,
                'error' => $responseData['error']['message'] ?? 'Unknown error',
                'response' => $responseData,
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Gera ID único para evento
     */
    private function generateEventId() {
        return uniqid('evt_', true) . '_' . bin2hex(random_bytes(8));
    }
    
    /**
     * Salva evento no banco de dados
     */
    private function saveEventToDatabase($event, $status = 'pending') {
        $pixel = $this->getPixelInfo();
        
        return $this->db->insert('capi_events', [
            'user_id' => $pixel['user_id'],
            'pixel_id' => $pixel['id'],
            'event_name' => $event['event_name'],
            'event_id' => $event['event_id'],
            'event_time' => $event['event_time'],
            'event_source_url' => $event['event_source_url'],
            'user_data' => json_encode($event['user_data']),
            'custom_data' => json_encode($event['custom_data']),
            'action_source' => $event['action_source'],
            'utm_source' => $event['user_data']['utm_source'] ?? null,
            'utm_medium' => $event['user_data']['utm_medium'] ?? null,
            'utm_campaign' => $event['user_data']['utm_campaign'] ?? null,
            'utm_content' => $event['user_data']['utm_content'] ?? null,
            'utm_term' => $event['user_data']['utm_term'] ?? null,
            'fbclid' => $event['user_data']['fbclid'] ?? null,
            'fbc' => $event['user_data']['fbc'] ?? null,
            'fbp' => $event['user_data']['fbp'] ?? null,
            'request_ip' => $event['user_data']['client_ip_address'] ?? null,
            'user_agent' => $event['user_data']['client_user_agent'] ?? null,
            'status' => $status
        ]);
    }
    
    /**
     * Atualiza status do evento
     */
    private function updateEventStatus($eventId, $status, $response) {
        $data = [
            'status' => $status,
            'response_data' => json_encode($response),
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        if (!$response['success']) {
            $data['error_message'] = $response['error'] ?? 'Unknown error';
        }
        
        $this->db->update('capi_events', $data, 'id = :id', ['id' => $eventId]);
    }
    
    /**
     * Busca informações do pixel
     */
    private function getPixelInfo() {
        static $pixelInfo = null;
        
        if ($pixelInfo === null) {
            $pixelInfo = $this->db->fetch("
                SELECT * FROM pixels 
                WHERE pixel_id = :pixel_id 
                LIMIT 1
            ", ['pixel_id' => $this->pixelId]);
        }
        
        return $pixelInfo;
    }
    
    /**
     * Log de informação
     */
    private function logInfo($message, $context = []) {
        $this->log('info', $message, $context);
    }
    
    /**
     * Log de erro
     */
    private function logError($message, $context = []) {
        $this->log('error', $message, $context);
    }
    
    /**
     * Log geral
     */
    private function log($type, $message, $context = []) {
        $pixel = $this->getPixelInfo();
        
        if (!$pixel) return;
        
        $this->db->insert('capi_logs', [
            'user_id' => $pixel['user_id'],
            'pixel_id' => $pixel['id'],
            'event_id' => $context['event_id'] ?? null,
            'log_type' => $type,
            'message' => $message,
            'context_data' => json_encode($context)
        ]);
    }
    
    /**
     * Testa conexão com o Facebook CAPI
     */
    public function testConnection() {
        $testEvent = [
            'event_name' => 'PageView',
            'event_time' => time(),
            'event_id' => $this->generateEventId(),
            'event_source_url' => 'https://example.com/test',
            'action_source' => 'website',
            'user_data' => [
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Test Agent'
            ],
            'custom_data' => []
        ];
        
        return $this->sendToFacebook([$testEvent]);
    }
}