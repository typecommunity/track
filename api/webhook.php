<?php
/**
 * UTMTrack - API Receptor de Webhooks
 * Recebe vendas de plataformas externas
 * URL: /api/webhook.php?id={webhook_id}&key={secret_key}
 */

header('Content-Type: application/json');

// Load dependencies
require_once __DIR__ . '/../core/Database.php';

// Função para logar
function logWebhook($webhookId, $status, $requestBody, $responseBody = null, $errorMessage = null) {
    try {
        $db = Database::getInstance();
        $db->insert('webhook_logs', [
            'webhook_id' => $webhookId,
            'request_headers' => json_encode(getallheaders()),
            'request_body' => $requestBody,
            'response_body' => $responseBody,
            'status' => $status,
            'error_message' => $errorMessage,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        error_log('Error logging webhook: ' . $e->getMessage());
    }
}

try {
    // Get webhook ID and secret key
    $webhookId = $_GET['id'] ?? null;
    $secretKey = $_GET['key'] ?? null;
    
    if (!$webhookId || !$secretKey) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing webhook ID or secret key']);
        exit;
    }
    
    // Load database
    $db = Database::getInstance();
    
    // Verify webhook exists and is active
    $webhook = $db->fetch("
        SELECT w.*, u.id as user_id 
        FROM webhooks w
        JOIN users u ON u.id = w.user_id
        WHERE w.id = :id 
        AND w.secret_key = :key 
        AND w.status = 'active'
    ", [
        'id' => $webhookId,
        'key' => $secretKey
    ]);
    
    if (!$webhook) {
        http_response_code(404);
        $error = json_encode(['error' => 'Webhook not found or inactive']);
        echo $error;
        logWebhook($webhookId, 'error', file_get_contents('php://input'), $error, 'Webhook not found');
        exit;
    }
    
    // Get request data
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    
    if (!$data) {
        // Try form data
        $data = $_POST;
    }
    
    if (empty($data)) {
        http_response_code(400);
        $error = json_encode(['error' => 'No data received']);
        echo $error;
        logWebhook($webhookId, 'error', $rawData, $error, 'Empty data');
        exit;
    }
    
    // Process based on platform
    $saleData = processWebhookData($webhook['platform'], $data);
    
    if (!$saleData) {
        http_response_code(400);
        $error = json_encode(['error' => 'Invalid data format for platform: ' . $webhook['platform']]);
        echo $error;
        logWebhook($webhookId, 'error', $rawData, $error, 'Invalid format');
        exit;
    }
    
    // Check if transaction already exists
    $existing = $db->fetch("
        SELECT id FROM sales 
        WHERE transaction_id = :transaction_id 
        AND user_id = :user_id
    ", [
        'transaction_id' => $saleData['transaction_id'],
        'user_id' => $webhook['user_id']
    ]);
    
    if ($existing) {
        // Update existing sale
        $db->update('sales',
            [
                'status' => $saleData['status'],
                'amount' => $saleData['amount']
            ],
            'id = :id',
            ['id' => $existing['id']]
        );
        
        $saleId = $existing['id'];
        $action = 'updated';
    } else {
        // Insert new sale
        $saleId = $db->insert('sales', [
            'user_id' => $webhook['user_id'],
            'product_id' => $webhook['product_id'],
            'transaction_id' => $saleData['transaction_id'],
            'customer_name' => $saleData['customer_name'],
            'customer_email' => $saleData['customer_email'],
            'amount' => $saleData['amount'],
            'payment_method' => $saleData['payment_method'],
            'status' => $saleData['status'],
            'conversion_date' => date('Y-m-d H:i:s')
        ]);
        
        $action = 'created';
    }
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Sale ' . $action . ' successfully',
        'sale_id' => $saleId,
        'transaction_id' => $saleData['transaction_id']
    ];
    
    echo json_encode($response);
    logWebhook($webhookId, 'success', $rawData, json_encode($response));
    
} catch (Exception $e) {
    http_response_code(500);
    $error = json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
    echo $error;
    
    if (isset($webhookId)) {
        logWebhook($webhookId, 'error', file_get_contents('php://input'), $error, $e->getMessage());
    }
}

/**
 * Process webhook data based on platform
 */
function processWebhookData($platform, $data) {
    switch ($platform) {
        case 'hotmart':
            return processHotmart($data);
        case 'kiwify':
            return processKiwify($data);
        case 'eduzz':
            return processEduzz($data);
        case 'perfectpay':
            return processPerfectPay($data);
        case 'monetizze':
            return processMonetizze($data);
        case 'custom':
            return processCustom($data);
        default:
            return null;
    }
}

function processHotmart($data) {
    if (!isset($data['event']) || $data['event'] !== 'PURCHASE_COMPLETE') {
        return null;
    }
    
    $purchase = $data['data']['purchase'] ?? [];
    
    return [
        'transaction_id' => $purchase['transaction'] ?? uniqid(),
        'customer_name' => $purchase['buyer']['name'] ?? 'N/A',
        'customer_email' => $purchase['buyer']['email'] ?? 'N/A',
        'amount' => $purchase['price']['value'] ?? 0,
        'payment_method' => strtolower($purchase['payment']['type'] ?? 'other'),
        'status' => $purchase['status'] === 'approved' ? 'approved' : 'pending'
    ];
}

function processKiwify($data) {
    if (!isset($data['order_status']) || $data['order_status'] !== 'paid') {
        return null;
    }
    
    return [
        'transaction_id' => $data['order_id'] ?? uniqid(),
        'customer_name' => $data['Customer']['full_name'] ?? 'N/A',
        'customer_email' => $data['Customer']['email'] ?? 'N/A',
        'amount' => $data['order_amount'] ?? 0,
        'payment_method' => 'credit_card',
        'status' => 'approved'
    ];
}

function processEduzz($data) {
    // Status 4 = Pago
    if (!isset($data['sales_status']) || $data['sales_status'] != 4) {
        return null;
    }
    
    return [
        'transaction_id' => $data['sales_code'] ?? uniqid(),
        'customer_name' => $data['client_name'] ?? 'N/A',
        'customer_email' => $data['client_email'] ?? 'N/A',
        'amount' => $data['sale_value'] ?? 0,
        'payment_method' => 'other',
        'status' => 'approved'
    ];
}

function processPerfectPay($data) {
    return [
        'transaction_id' => $data['transaction_id'] ?? $data['id'] ?? uniqid(),
        'customer_name' => $data['customer_name'] ?? $data['name'] ?? 'N/A',
        'customer_email' => $data['customer_email'] ?? $data['email'] ?? 'N/A',
        'amount' => $data['amount'] ?? $data['value'] ?? 0,
        'payment_method' => strtolower($data['payment_method'] ?? 'other'),
        'status' => $data['status'] === 'approved' ? 'approved' : 'pending'
    ];
}

function processMonetizze($data) {
    return [
        'transaction_id' => $data['transacao'] ?? uniqid(),
        'customer_name' => $data['comprador']['nome'] ?? 'N/A',
        'customer_email' => $data['comprador']['email'] ?? 'N/A',
        'amount' => $data['valor'] ?? 0,
        'payment_method' => 'other',
        'status' => $data['status'] == 2 ? 'approved' : 'pending'
    ];
}

function processCustom($data) {
    // Generic processor for custom webhooks
    return [
        'transaction_id' => $data['transaction_id'] ?? $data['id'] ?? uniqid(),
        'customer_name' => $data['customer_name'] ?? $data['name'] ?? 'N/A',
        'customer_email' => $data['customer_email'] ?? $data['email'] ?? 'N/A',
        'amount' => $data['amount'] ?? $data['value'] ?? $data['price'] ?? 0,
        'payment_method' => $data['payment_method'] ?? 'other',
        'status' => $data['status'] ?? 'approved'
    ];
}