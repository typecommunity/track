<!-- Estatísticas -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 30px;">
    <div class="metric-card">
        <div class="metric-label">
            🔗 Total de Webhooks
        </div>
        <div class="metric-value">
            <?= number_format($stats['total_webhooks'] ?? 0, 0, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            📊 Total de Requisições
        </div>
        <div class="metric-value">
            <?= number_format($stats['total_requests'] ?? 0, 0, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            ✅ Requisições Bem-sucedidas
        </div>
        <div class="metric-value" style="color: #10b981;">
            <?= number_format($stats['successful_requests'] ?? 0, 0, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            ❌ Requisições com Erro
        </div>
        <div class="metric-value" style="color: #ef4444;">
            <?= number_format($stats['failed_requests'] ?? 0, 0, ',', '.') ?>
        </div>
    </div>
</div>

<!-- Lista de Webhooks -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🔗 Meus Webhooks</h2>
        <button onclick="openCreateModal()" class="btn btn-primary">
            ➕ Novo Webhook
        </button>
    </div>
    
    <?php if (empty($webhooks)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🔗</div>
        <div class="empty-state-title">Nenhum webhook configurado</div>
        <p>Crie webhooks para receber vendas automaticamente de plataformas externas</p>
        <button onclick="openCreateModal()" class="btn btn-primary" style="margin-top: 20px;">
            ➕ Criar Primeiro Webhook
        </button>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Produto</th>
                    <th>Requisições</th>
                    <th>Sucesso</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($webhooks as $webhook): 
                    $successRate = $webhook['total_requests'] > 0 
                        ? ($webhook['successful_requests'] / $webhook['total_requests']) * 100 
                        : 0;
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($webhook['name']) ?></td>
                    <td><?= $webhook['product_id'] ? 'Produto #' . $webhook['product_id'] : '-' ?></td>
                    <td><?= number_format($webhook['total_requests'] ?? 0, 0, ',', '.') ?></td>
                    <td>
                        <span style="color: <?= $successRate >= 80 ? '#10b981' : ($successRate >= 50 ? '#f59e0b' : '#ef4444') ?>">
                            <?= number_format($successRate, 0) ?>%
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $webhook['status'] === 'active' ? 'success' : 'warning' ?>" style="
                            display: inline-block;
                            padding: 4px 12px;
                            border-radius: 12px;
                            font-size: 12px;
                            font-weight: 600;
                        ">
                            <?= $webhook['status'] === 'active' ? '✓ Ativo' : '⏸ Inativo' ?>
                        </span>
                    </td>
                    <td>
                        <button 
                            onclick='viewWebhook(<?= $webhook['id'] ?>)' 
                            class="btn" 
                            style="padding: 8px 12px; font-size: 13px; background: #667eea; color: white;"
                        >
                            Ver URL
                        </button>
                        <button 
                            onclick='viewLogs(<?= $webhook['id'] ?>)' 
                            class="btn" 
                            style="padding: 8px 12px; font-size: 13px; background: #10b981; color: white;"
                        >
                            Logs
                        </button>
                        <button 
                            onclick='editWebhook(<?= $webhook['id'] ?>)' 
                            class="btn" 
                            style="padding: 8px 12px; font-size: 13px; background: #f59e0b; color: white;"
                        >
                            Editar
                        </button>
                        <button 
                            onclick='deleteWebhook(<?= $webhook['id'] ?>, "<?= addslashes($webhook['name']) ?>")' 
                            class="btn" 
                            style="padding: 8px 12px; font-size: 13px; background: #ef4444; color: white;"
                        >
                            Deletar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Criar/Editar Webhook -->
<div id="webhookModal" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
">
    <div style="
        background: #1e293b;
        border-radius: 20px;
        padding: 40px;
        max-width: 700px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        border: 1px solid #334155;
    ">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 id="modalTitle" style="color: white; font-size: 24px;">➕ Novo Webhook</h2>
            <button onclick="closeModal()" style="
                background: none;
                border: none;
                color: #94a3b8;
                font-size: 28px;
                cursor: pointer;
                padding: 0;
                width: 40px;
                height: 40px;
            ">×</button>
        </div>
        
        <form id="webhookForm" onsubmit="saveWebhook(event)">
            <input type="hidden" id="webhook_id" name="webhook_id">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Nome do Webhook *
                </label>
                <input 
                    type="text" 
                    id="webhook_name" 
                    name="name"
                    required
                    placeholder="Ex: Vendas Hotmart - Produto X"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
            
            <div style="margin-bottom: 20px;">
                <div style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <div style="font-weight: 600; color: #a5b4fc; margin-bottom: 8px; font-size: 15px;">📡 Webhooks</div>
                    <div style="font-size: 13px; color: #94a3b8; line-height: 1.6;">
                        Adicione webhooks para se conectar com as plataformas de venda
                    </div>
                </div>
                <input type="hidden" id="webhook_platform" name="webhook_platform" value="custom">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Produto (Opcional)
                </label>
                <select 
                    id="webhook_product" 
                    name="product_id"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
                    <option value="">Nenhum produto específico</option>
                    <?php
                    $products = $this->db->fetchAll("SELECT id, name FROM products WHERE user_id = :user_id ORDER BY name", ['user_id' => $user['id']]);
                    foreach ($products as $product):
                    ?>
                    <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Eventos Configuráveis -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 12px; font-weight: 600; color: #e2e8f0;">
                    Eventos (Opcional)
                </label>
                <div style="background: #0f172a; padding: 16px; border-radius: 8px; border: 1px solid #334155;">
                    <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                        <label class="event-checkbox">
                            <input type="checkbox" name="events[]" value="purchase_approved" id="event_purchase_approved">
                            <span>✅ Compra aprovada</span>
                        </label>
                        <label class="event-checkbox">
                            <input type="checkbox" name="events[]" value="pix_generated" id="event_pix_generated">
                            <span>⚡ Pix gerado</span>
                        </label>
                        <label class="event-checkbox">
                            <input type="checkbox" name="events[]" value="cart_abandoned" id="event_cart_abandoned">
                            <span>🛒 Carrinho abandonado</span>
                        </label>
                        <label class="event-checkbox">
                            <input type="checkbox" name="events[]" value="refund" id="event_refund">
                            <span>↩️ Reembolso</span>
                        </label>
                        <label class="event-checkbox">
                            <input type="checkbox" name="events[]" value="chargeback" id="event_chargeback">
                            <span>⚠️ Chargeback</span>
                        </label>
                    </div>
                </div>
                <div style="color: #94a3b8; font-size: 13px; margin-top: 8px; line-height: 1.5;">
                    💡 <strong>Dica:</strong> Deixe desmarcado para aceitar todos os eventos.
                </div>
            </div>
            
            <!-- Métodos de Pagamento - CORRIGIDO PARA BRASIL -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 12px; font-weight: 600; color: #e2e8f0;">
                    Métodos de Pagamento (Opcional)
                </label>
                <div style="background: #0f172a; padding: 16px; border-radius: 8px; border: 1px solid #334155;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                        <label class="event-checkbox">
                            <input type="checkbox" name="payment_methods[]" value="credit_card" id="payment_credit_card">
                            <span>💳 Cartão de Crédito</span>
                        </label>
                        <label class="event-checkbox">
                            <input type="checkbox" name="payment_methods[]" value="pix" id="payment_pix">
                            <span>⚡ PIX</span>
                        </label>
                        <label class="event-checkbox">
                            <input type="checkbox" name="payment_methods[]" value="boleto" id="payment_boleto">
                            <span>📄 Boleto</span>
                        </label>
                    </div>
                </div>
                <div style="color: #94a3b8; font-size: 13px; margin-top: 8px; line-height: 1.5;">
                    💡 <strong>Dica:</strong> Deixe desmarcado para aceitar todos os métodos de pagamento.
                </div>
            </div>
            
            <div id="statusField" style="margin-bottom: 30px; display: none;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Status
                </label>
                <select 
                    id="webhook_status" 
                    name="status"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
                    <option value="active">✓ Ativo</option>
                    <option value="inactive">⏸ Inativo</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeModal()" class="btn" style="background: #334155; color: white;">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    💾 Salvar Webhook
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ver URL -->
<div id="urlModal" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
">
    <div style="
        background: #1e293b;
        border-radius: 20px;
        padding: 40px;
        max-width: 700px;
        width: 90%;
        border: 1px solid #334155;
    ">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="color: white; font-size: 24px;">🔗 URL do Webhook</h2>
            <button onclick="document.getElementById('urlModal').style.display='none'" style="
                background: none;
                border: none;
                color: #94a3b8;
                font-size: 28px;
                cursor: pointer;
            ">×</button>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                Cole esta URL na plataforma de pagamento:
            </label>
            <div style="display: flex; gap: 10px;">
                <input 
                    type="text" 
                    id="webhookUrl" 
                    readonly
                    style="flex: 1; padding: 12px; background: #0f172a; border: 1px solid #667eea; border-radius: 8px; color: #10b981; font-size: 13px; font-family: monospace;"
                >
                <button onclick="copyWebhookUrl()" class="btn btn-primary">
                    📋 Copiar
                </button>
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <button onclick="regenerateSecretKey()" class="btn" style="background: #f59e0b; color: white; width: 100%;">
                🔄 Regenerar Chave Secreta
            </button>
        </div>
        
        <div style="background: #0f172a; padding: 20px; border-radius: 8px; border: 1px solid #334155;">
            <h3 style="color: #a5b4fc; margin-bottom: 15px; font-size: 16px;">📖 Como Configurar</h3>
            <ol style="color: #94a3b8; line-height: 2; padding-left: 20px;">
                <li>Copie a URL acima</li>
                <li>Acesse a plataforma de pagamento</li>
                <li>Procure por "Webhook" ou "Postback"</li>
                <li>Cole a URL e salve</li>
                <li>Teste o webhook enviando uma venda de teste</li>
            </ol>
        </div>
    </div>
</div>

<!-- Modal de Notificação -->
<div id="notificationModal" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    align-items: center;
    justify-content: center;
">
    <div style="
        background: #1e293b;
        border-radius: 20px;
        padding: 40px;
        max-width: 500px;
        width: 90%;
        border: 1px solid #334155;
        text-align: center;
    ">
        <div id="notificationIcon" style="font-size: 64px; margin-bottom: 20px;">✓</div>
        <h2 id="notificationTitle" style="color: white; font-size: 24px; margin-bottom: 15px;">Sucesso!</h2>
        <p id="notificationMessage" style="color: #94a3b8; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
            Operação realizada com sucesso!
        </p>
        <button onclick="closeNotification()" class="btn btn-primary" style="min-width: 120px;">
            OK
        </button>
    </div>
</div>

<!-- Modal de Confirmação -->
<div id="confirmModal" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    align-items: center;
    justify-content: center;
">
    <div style="
        background: #1e293b;
        border-radius: 20px;
        padding: 40px;
        max-width: 500px;
        width: 90%;
        border: 1px solid #334155;
        text-align: center;
    ">
        <div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>
        <h2 style="color: white; font-size: 24px; margin-bottom: 15px;">Confirmar Ação</h2>
        <p id="confirmMessage" style="color: #94a3b8; font-size: 16px; line-height: 1.6; margin-bottom: 30px; white-space: pre-line;">
            Tem certeza que deseja continuar?
        </p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <button onclick="closeConfirm(false)" class="btn" style="background: #334155; color: white; min-width: 100px;">
                Cancelar
            </button>
            <button onclick="closeConfirm(true)" class="btn" style="background: #ef4444; color: white; min-width: 100px;">
                Confirmar
            </button>
        </div>
    </div>
</div>

<style>
.badge-success {
    background: #10b98120;
    color: #10b981;
}

.badge-warning {
    background: #f59e0b20;
    color: #f59e0b;
}

.event-checkbox {
    display: flex;
    align-items: center;
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    gap: 10px;
}

.event-checkbox:hover {
    background: #1e293b;
}

.event-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    flex-shrink: 0;
}

.event-checkbox span,
.event-checkbox label {
    color: #e2e8f0;
    cursor: pointer;
    flex: 1;
    font-size: 14px;
}

/* Animações dos Modais */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

#notificationModal[style*="display: flex"],
#confirmModal[style*="display: flex"] {
    animation: fadeIn 0.3s ease;
}

#notificationModal > div,
#confirmModal > div {
    animation: slideUp 0.3s ease;
}

/* Scrollbar customizada */
#eventOptions::-webkit-scrollbar {
    width: 8px;
}

#eventOptions::-webkit-scrollbar-track {
    background: #1e293b;
    border-radius: 4px;
}

#eventOptions::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
}

#eventOptions::-webkit-scrollbar-thumb:hover {
    background: #475569;
}

/* Botões com hover */
.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.btn:active {
    transform: translateY(0);
}
</style>

<script>
let currentWebhookId = null;
let confirmCallback = null;

// Lista de métodos de pagamento válidos (compatível com banco de dados)
const VALID_PAYMENT_METHODS = ['credit_card', 'pix', 'boleto'];

// Eventos disponíveis no sistema
const AVAILABLE_EVENTS = [
    'purchase_approved',
    'pix_generated', 
    'cart_abandoned',
    'refund',
    'chargeback'
];

// Funções do Modal de Notificação
function showNotification(type, message) {
    const modal = document.getElementById('notificationModal');
    const icon = document.getElementById('notificationIcon');
    const title = document.getElementById('notificationTitle');
    const msg = document.getElementById('notificationMessage');
    
    if (type === 'success') {
        icon.textContent = '✓';
        icon.style.color = '#10b981';
        title.textContent = 'Sucesso!';
    } else if (type === 'error') {
        icon.textContent = '✗';
        icon.style.color = '#ef4444';
        title.textContent = 'Erro!';
    } else if (type === 'info') {
        icon.textContent = 'ℹ';
        icon.style.color = '#667eea';
        title.textContent = 'Informação';
    }
    
    msg.textContent = message;
    modal.style.display = 'flex';
}

function closeNotification() {
    document.getElementById('notificationModal').style.display = 'none';
}

// Funções do Modal de Confirmação
function showConfirm(message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const msg = document.getElementById('confirmMessage');
        
        msg.textContent = message;
        modal.style.display = 'flex';
        
        confirmCallback = resolve;
    });
}

function closeConfirm(result) {
    document.getElementById('confirmModal').style.display = 'none';
    if (confirmCallback) {
        confirmCallback(result);
        confirmCallback = null;
    }
}

// Abrir modal de criação
function openCreateModal() {
    document.getElementById('modalTitle').textContent = '➕ Novo Webhook';
    document.getElementById('webhookForm').reset();
    document.getElementById('webhook_id').value = '';
    document.getElementById('webhook_platform').value = 'custom';
    document.getElementById('statusField').style.display = 'none';
    
    // Limpa todos os checkboxes
    document.querySelectorAll('input[name="events[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="payment_methods[]"]').forEach(cb => cb.checked = false);
    
    currentWebhookId = null;
    document.getElementById('webhookModal').style.display = 'flex';
}

// Editar webhook
async function editWebhook(webhookId) {
    try {
        const response = await fetch(`index.php?page=webhook-get&id=${webhookId}`);
        const result = await response.json();
        
        if (result.success) {
            const webhook = result.webhook;
            currentWebhookId = webhook.id;
            
            document.getElementById('modalTitle').textContent = '✏️ Editar Webhook';
            document.getElementById('webhook_id').value = webhook.id;
            document.getElementById('webhook_name').value = webhook.name;
            document.getElementById('webhook_platform').value = 'custom';
            document.getElementById('webhook_product').value = webhook.product_id || '';
            document.getElementById('webhook_status').value = webhook.status;
            document.getElementById('statusField').style.display = 'block';
            
            // Limpa todos os checkboxes primeiro
            document.querySelectorAll('input[name="events[]"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('input[name="payment_methods[]"]').forEach(cb => cb.checked = false);
            
            // Marca eventos
            if (webhook.events && Array.isArray(webhook.events)) {
                webhook.events.forEach(eventValue => {
                    const checkbox = document.getElementById(`event_${eventValue}`);
                    if (checkbox) checkbox.checked = true;
                });
            }
            
            // Marca métodos de pagamento (apenas os válidos)
            if (webhook.payment_methods && Array.isArray(webhook.payment_methods)) {
                webhook.payment_methods.forEach(method => {
                    // Valida se é um método de pagamento aceito
                    if (VALID_PAYMENT_METHODS.includes(method)) {
                        const checkbox = document.getElementById(`payment_${method}`);
                        if (checkbox) checkbox.checked = true;
                    }
                });
            }
            
            document.getElementById('webhookModal').style.display = 'flex';
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Erro ao carregar webhook: ' + error.message);
    }
}

// Ver URL do webhook
async function viewWebhook(webhookId) {
    try {
        currentWebhookId = webhookId;
        const response = await fetch(`index.php?page=webhook-get&id=${webhookId}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('webhookUrl').value = result.webhook.url;
            document.getElementById('urlModal').style.display = 'flex';
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Erro ao carregar webhook: ' + error.message);
    }
}

// Ver logs
function viewLogs(webhookId) {
    window.location.href = `index.php?page=webhook-logs&id=${webhookId}`;
}

// Salvar webhook com validação
async function saveWebhook(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Coleta eventos
    const events = Array.from(document.querySelectorAll('input[name="events[]"]:checked')).map(cb => cb.value);
    
    // Coleta métodos de pagamento (apenas os válidos)
    const paymentMethods = Array.from(document.querySelectorAll('input[name="payment_methods[]"]:checked'))
        .map(cb => cb.value)
        .filter(method => VALID_PAYMENT_METHODS.includes(method));
    
    // Adiciona ao FormData
    formData.append('events', JSON.stringify(events));
    formData.append('payment_methods', JSON.stringify(paymentMethods));
    
    // Corrige nome do campo platform
    const platform = formData.get('webhook_platform');
    if (platform) {
        formData.set('platform', platform);
        formData.delete('webhook_platform');
    }
    
    const webhookId = formData.get('webhook_id');
    const url = webhookId ? 'index.php?page=webhook-update' : 'index.php?page=webhook-create';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (!webhookId && result.webhook_url) {
                closeModal();
                currentWebhookId = result.webhook_id;
                document.getElementById('webhookUrl').value = result.webhook_url;
                document.getElementById('urlModal').style.display = 'flex';
            } else {
                showNotification('success', result.message);
                setTimeout(() => {
                    closeNotification();
                    window.location.reload();
                }, 1500);
            }
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Erro ao salvar webhook: ' + error.message);
    }
}

// Deletar webhook
async function deleteWebhook(webhookId, webhookName) {
    const confirmed = await showConfirm(`Tem certeza que deseja deletar o webhook "${webhookName}"?\n\nTodos os logs serão deletados também.`);
    
    if (!confirmed) return;
    
    try {
        const formData = new FormData();
        formData.append('webhook_id', webhookId);
        
        const response = await fetch('index.php?page=webhook-delete', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            setTimeout(() => {
                closeNotification();
                window.location.reload();
            }, 1500);
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Erro ao deletar webhook: ' + error.message);
    }
}

// Regenerar chave secreta
async function regenerateSecretKey() {
    if (!currentWebhookId) {
        showNotification('error', 'ID do webhook não encontrado');
        return;
    }
    
    const confirmed = await showConfirm('Tem certeza que deseja regenerar a chave secreta?\n\nA URL antiga não funcionará mais e você precisará atualizar na plataforma de pagamento.');
    
    if (!confirmed) return;
    
    try {
        const formData = new FormData();
        formData.append('webhook_id', currentWebhookId);
        
        const response = await fetch('index.php?page=webhook-regenerate-key', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            document.getElementById('webhookUrl').value = result.webhook_url;
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Erro ao regenerar chave: ' + error.message);
    }
}

// Copiar URL do webhook
function copyWebhookUrl() {
    const input = document.getElementById('webhookUrl');
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        showNotification('success', 'URL copiada para área de transferência!');
    } catch (err) {
        navigator.clipboard.writeText(input.value).then(() => {
            showNotification('success', 'URL copiada para área de transferência!');
        }).catch(() => {
            showNotification('error', 'Erro ao copiar. Por favor, copie manualmente.');
        });
    }
}

// Fechar modal
function closeModal() {
    document.getElementById('webhookModal').style.display = 'none';
}

// Fechar modal ao clicar fora
document.getElementById('webhookModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('urlModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

document.getElementById('notificationModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeNotification();
});

// Fechar modais com tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('webhookModal').style.display === 'flex') closeModal();
        if (document.getElementById('urlModal').style.display === 'flex') document.getElementById('urlModal').style.display = 'none';
        if (document.getElementById('notificationModal').style.display === 'flex') closeNotification();
        if (document.getElementById('confirmModal').style.display === 'flex') closeConfirm(false);
    }
});
</script>