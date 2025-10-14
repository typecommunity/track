<?php
/**
 * UTMTrack - API Receptor de Webhooks (Sistema Híbrido)
 * Recebe vendas de plataformas externas e cria produtos automaticamente
 * URL: /api/webhook.php?id={webhook_id}&key={secret_key}
 */

header('Content-Type: application/json');

// Load dependencies
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/controllers/ProductController.php';

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
    
    // ===========================================================
    // SISTEMA HÍBRIDO: Gerenciamento Inteligente de Produtos
    // ===========================================================
    
    $productId = null;
    $productSource = 'manual'; // Rastreamento da origem
    
    // 1. Verifica se webhook tem produto configurado manualmente
    if (!empty($webhook['product_id'])) {
        $product = $db->fetch("
            SELECT id FROM products 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $webhook['product_id'],
            'user_id' => $webhook['user_id']
        ]);
        
        if ($product) {
            $productId = $product['id'];
            $productSource = 'webhook_config';
        }
    }
    
    // 2. Se não tem produto configurado, tenta extrair dos dados da venda
    if (!$productId && !empty($saleData['product_name'])) {
        // Auto-cria ou busca produto pelo nome
        $productId = ProductController::getOrCreateProduct(
            $webhook['user_id'],
            $saleData['product_name'],
            [
                'price' => $saleData['amount'] ?? 0,
                'sku' => $saleData['product_sku'] ?? null,
                'source' => [
                    'platform' => $webhook['platform'],
                    'webhook_id' => $webhookId,
                    'transaction_id' => $saleData['transaction_id']
                ]
            ]
        );
        $productSource = 'auto_created';
    }
    
    // 3. Se ainda não tem produto, tenta buscar pela campanha vinculada
    if (!$productId && !empty($saleData['campaign_id'])) {
        $campaign = $db->fetch("
            SELECT campaign_name FROM campaigns 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $saleData['campaign_id'],
            'user_id' => $webhook['user_id']
        ]);
        
        if ($campaign) {
            // Cria produto com nome da campanha
            $productId = ProductController::getOrCreateProduct(
                $webhook['user_id'],
                $campaign['campaign_name'],
                [
                    'price' => $saleData['amount'] ?? 0,
                    'source' => [
                        'platform' => $webhook['platform'],
                        'campaign_id' => $saleData['campaign_id'],
                        'webhook_id' => $webhookId
                    ]
                ]
            );
            $productSource = 'auto_from_campaign';
        }
    }
    
    // 4. Último recurso: cria produto genérico
    if (!$productId) {
        $productId = ProductController::getOrCreateProduct(
            $webhook['user_id'],
            'Produto - ' . $webhook['platform'] . ' - ' . date('d/m/Y H:i'),
            [
                'price' => $saleData['amount'] ?? 0,
                'source' => [
                    'platform' => $webhook['platform'],
                    'webhook_id' => $webhookId,
                    'auto_generated' => true
                ]
            ]
        );
        $productSource = 'auto_generic';
    }
    
    // ===========================================================
    // Registra ou Atualiza a Venda
    // ===========================================================
    
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
                'amount' => $saleData['amount'],
                'product_id' => $productId
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
            'product_id' => $productId,
            'campaign_id' => $saleData['campaign_id'] ?? null,
            'transaction_id' => $saleData['transaction_id'],
            'customer_name' => $saleData['customer_name'],
            'customer_email' => $saleData['customer_email'],
            'amount' => $saleData['amount'],
            'payment_method' => $saleData['payment_method'],
            'status' => $saleData['status'],
            'conversion_date' => date('Y-m-d H:i:s'),
            'product_cost' => $saleData['product_cost'] ?? 0
        ]);
        
        $action = 'created';
    }
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Sale ' . $action . ' successfully',
        'sale_id' => $saleId,
        'product_id' => $productId,
        'product_source' => $productSource,
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
    if (!isset($data['event'])) {
        return null;
    }
    
    $purchase = $data['data']['purchase'] ?? [];
    $product = $purchase['product'] ?? [];
    
    return [
        'transaction_id' => $purchase['transaction'] ?? uniqid(),
        'customer_name' => $purchase['buyer']['name'] ?? 'N/A',
        'customer_email' => $purchase['buyer']['email'] ?? null,
        'amount' => $purchase['price']['value'] ?? 0,
        'payment_method' => $purchase['payment']['type'] ?? 'other',
        'status' => getHotmartStatus($data['event']),
        'product_name' => $product['name'] ?? null,
        'product_sku' => $product['id'] ?? null,
        'product_cost' => 0
    ];
}

function processKiwify($data) {
    return [
        'transaction_id' => $data['order_id'] ?? uniqid(),
        'customer_name' => $data['Customer']['full_name'] ?? 'N/A',
        'customer_email' => $data['Customer']['email'] ?? null,
        'amount' => $data['order_amount'] ?? 0,
        'payment_method' => $data['payment_method'] ?? 'other',
        'status' => getKiwifyStatus($data['order_status'] ?? ''),
        'product_name' => $data['Product']['product_name'] ?? null,
        'product_sku' => $data['Product']['product_id'] ?? null,
        'product_cost' => 0
    ];
}

function processEduzz($data) {
    return [
        'transaction_id' => $data['trans_cod'] ?? uniqid(),
        'customer_name' => $data['cli_name'] ?? 'N/A',
        'customer_email' => $data['cli_email'] ?? null,
        'amount' => $data['sale_price'] ?? 0,
        'payment_method' => 'other',
        'status' => getEduzzStatus($data['trans_status'] ?? ''),
        'product_name' => $data['product_name'] ?? null,
        'product_sku' => $data['product_id'] ?? null,
        'product_cost' => 0
    ];
}

function processPerfectPay($data) {
    return [
        'transaction_id' => $data['transaction'] ?? uniqid(),
        'customer_name' => $data['customer']['name'] ?? 'N/A',
        'customer_email' => $data['customer']['email'] ?? null,
        'amount' => $data['amount'] ?? 0,
        'payment_method' => $data['payment_method'] ?? 'other',
        'status' => getPerfectPayStatus($data['status'] ?? ''),
        'product_name' => $data['product']['name'] ?? null,
        'product_sku' => $data['product']['sku'] ?? null,
        'product_cost' => 0
    ];
}

function processMonetizze($data) {
    return [
        'transaction_id' => $data['transacao_codigo'] ?? uniqid(),
        'customer_name' => $data['comprador']['nome'] ?? 'N/A',
        'customer_email' => $data['comprador']['email'] ?? null,
        'amount' => $data['venda']['valor'] ?? 0,
        'payment_method' => 'other',
        'status' => getMonetizzeStatus($data['venda']['status'] ?? ''),
        'product_name' => $data['produto']['nome'] ?? null,
        'product_sku' => $data['produto']['codigo'] ?? null,
        'product_cost' => 0
    ];
}

function processCustom($data) {
    // Formato custom flexível
    return [
        'transaction_id' => $data['transaction_id'] ?? $data['id'] ?? uniqid(),
        'customer_name' => $data['customer_name'] ?? $data['name'] ?? 'N/A',
        'customer_email' => $data['customer_email'] ?? $data['email'] ?? null,
        'amount' => $data['amount'] ?? $data['value'] ?? $data['price'] ?? 0,
        'payment_method' => $data['payment_method'] ?? 'other',
        'status' => $data['status'] ?? 'pending',
        'product_name' => $data['product_name'] ?? $data['product'] ?? null,
        'product_sku' => $data['product_sku'] ?? $data['sku'] ?? null,
        'product_cost' => $data['product_cost'] ?? $data['cost'] ?? 0
    ];
}

// Status mapping functions
function getHotmartStatus($event) {
    $statusMap = [
        'PURCHASE_COMPLETE' => 'approved',
        'PURCHASE_APPROVED' => 'approved',
        'PURCHASE_REFUNDED' => 'refunded',
        'PURCHASE_CANCELED' => 'cancelled'
    ];
    return $statusMap[$event] ?? 'pending';
}

function getKiwifyStatus($status) {
    $statusMap = [
        'paid' => 'approved',
        'refunded' => 'refunded',
        'canceled' => 'cancelled'
    ];
    return $statusMap[$status] ?? 'pending';
}

function getEduzzStatus($status) {
    $statusMap = [
        '6' => 'approved', // Aprovado
        '7' => 'refunded', // Reembolsado
        '9' => 'cancelled' // Cancelado
    ];
    return $statusMap[$status] ?? 'pending';
}

function getPerfectPayStatus($status) {
    $statusMap = [
        'approved' => 'approved',
        'paid' => 'approved',
        'refunded' => 'refunded',
        'cancelled' => 'cancelled'
    ];
    return $statusMap[$status] ?? 'pending';
}

function getMonetizzeStatus($status) {
    $statusMap = [
        '2' => 'approved',
        '3' => 'refunded',
        '4' => 'cancelled'
    ];
    return $statusMap[$status] ?? 'pending';
}