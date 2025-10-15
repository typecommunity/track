<!-- Estatísticas -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 30px;">
    <div class="metric-card">
        <div class="metric-label">🔗 Total de Webhooks</div>
        <div class="metric-value"><?= number_format($stats['total_webhooks'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">📊 Total de Requisições</div>
        <div class="metric-value"><?= number_format($stats['total_requests'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">✅ Requisições Bem-sucedidas</div>
        <div class="metric-value" style="color: #10b981;"><?= number_format($stats['successful_requests'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">❌ Requisições com Erro</div>
        <div class="metric-value" style="color: #ef4444;"><?= number_format($stats['failed_requests'] ?? 0, 0, ',', '.') ?></div>
    </div>
</div>

<!-- Lista de Webhooks -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🔗 Meus Webhooks</h2>
        <button onclick="openCreateModal()" class="btn btn-primary">➕ Novo Webhook</button>
    </div>
    
    <?php if (empty($webhooks)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🔗</div>
        <div class="empty-state-title">Nenhum webhook configurado</div>
        <p>Crie webhooks para receber vendas automaticamente de qualquer checkout/plataforma</p>
        <button onclick="openCreateModal()" class="btn btn-primary" style="margin-top: 20px;">➕ Criar Primeiro Webhook</button>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Produtos</th>
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
                    $config = json_decode($webhook['config'] ?? '{}', true);
                    $products = $config['products'] ?? [];
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($webhook['name']) ?></td>
                    <td><?= htmlspecialchars($webhook['platform'] ?: 'Universal') ?></td>
                    <td><?= count($products) > 0 ? count($products) . ' produto(s)' : '-' ?></td>
                    <td><?= number_format($webhook['total_requests'] ?? 0, 0, ',', '.') ?></td>
                    <td>
                        <span style="color: <?= $successRate >= 80 ? '#10b981' : ($successRate >= 50 ? '#f59e0b' : '#ef4444') ?>">
                            <?= number_format($successRate, 0) ?>%
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $webhook['status'] === 'active' ? 'success' : 'warning' ?>">
                            <?= $webhook['status'] === 'active' ? '✓ Ativo' : '⏸ Inativo' ?>
                        </span>
                    </td>
                    <td>
                        <button onclick='viewWebhook(<?= $webhook['id'] ?>)' class="btn" style="padding: 6px 12px; font-size: 12px; background: #667eea; color: white;" title="Ver URL">👁️</button>
                        <button onclick='viewLogs(<?= $webhook['id'] ?>)' class="btn" style="padding: 6px 12px; font-size: 12px; background: #10b981; color: white;" title="Ver Logs">📊</button>
                        <button onclick='editWebhook(<?= $webhook['id'] ?>)' class="btn" style="padding: 6px 12px; font-size: 12px; background: #f59e0b; color: white;" title="Editar">✏️</button>
                        <button onclick='deleteWebhook(<?= $webhook['id'] ?>, "<?= addslashes($webhook['name']) ?>")' class="btn" style="padding: 6px 12px; font-size: 12px; background: #ef4444; color: white;" title="Deletar">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Criar/Editar Webhook -->
<div id="webhookModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; overflow-y: auto;">
    <div style="background: #1e293b; border-radius: 20px; padding: 40px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; border: 1px solid #334155; margin: 20px auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 id="modalTitle" style="color: white; font-size: 24px;">🔗 Configurar Webhook</h2>
            <button onclick="closeModal()" style="background: none; border: none; color: #94a3b8; font-size: 28px; cursor: pointer;">×</button>
        </div>
        
        <form id="webhookForm" onsubmit="saveWebhook(event)">
            <input type="hidden" id="webhook_id" name="webhook_id">
            
            <!-- Nome -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">Título *</label>
                <input type="text" id="webhook_name" name="name" required placeholder="Ex: Webhooks" style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;">
            </div>
            
            <!-- Tipo (opcional) -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">Identificação (Opcional)</label>
                <input 
                    type="text" 
                    id="webhook_platform" 
                    name="platform" 
                    placeholder="Ex: hotmart, meu-checkout, loja-x (apenas para organização)"
                    autocomplete="off"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;">
                <small style="color: #94a3b8; font-size: 12px;">Campo opcional para identificar a origem. Deixe vazio se preferir.</small>
            </div>
            
            <!-- Produtos Vinculados -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">Produtos Vinculados (Opcional)</label>
                <select id="webhook_products" name="products[]" multiple size="5" style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;">
                    <?php
                    $products = $this->db->fetchAll("SELECT id, name FROM products WHERE user_id = :user_id ORDER BY name", ['user_id' => $user['id']]);
                    foreach ($products as $product):
                    ?>
                    <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #94a3b8; font-size: 12px;">Segure Ctrl/Cmd para selecionar múltiplos. Se vazio, produtos serão criados automaticamente.</small>
            </div>
            
            <!-- Versão -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 12px; font-weight: 600; color: #e2e8f0;">Versão</label>
                <div style="display: flex; gap: 20px;">
                    <label style="display: flex; align-items: center; color: #e2e8f0; cursor: pointer;">
                        <input type="radio" name="version" value="1.0" checked style="margin-right: 8px;">
                        Versão 1.0
                    </label>
                    <label style="display: flex; align-items: center; color: #e2e8f0; cursor: pointer;">
                        <input type="radio" name="version" value="2.0" style="margin-right: 8px;">
                        Versão 2.0
                    </label>
                </div>
            </div>
            
            <!-- Métodos de Pagamento -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 12px; font-weight: 600; color: #e2e8f0;">Métodos de Pagamento</label>
                <div style="background: #0f172a; padding: 16px; border-radius: 8px; border: 1px solid #334155;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_methods[]" value="credit_card">
                        Cartão de Crédito
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_methods[]" value="boleto">
                        Boleto
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_methods[]" value="pix">
                        PIX
                    </label>
                </div>
                <small style="color: #94a3b8; font-size: 12px;">Se nenhum for selecionado, aceita todos</small>
            </div>
            
            <!-- Status de Pagamento -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 12px; font-weight: 600; color: #e2e8f0;">Status de Pagamento</label>
                <div style="background: #0f172a; padding: 16px; border-radius: 8px; border: 1px solid #334155;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_statuses[]" value="checkout_abandon">
                        Abandono de Checkout
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_statuses[]" value="paid" checked>
                        Pago
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_statuses[]" value="refunded">
                        Reembolsado
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_statuses[]" value="waiting">
                        Aguardando Pagamento
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_statuses[]" value="refused">
                        Recusado
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_statuses[]" value="chargeback">
                        Chargeback
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="payment_statuses[]" value="blocked">
                        Barrado pelo antifraude
                    </label>
                </div>
                <small style="color: #94a3b8; font-size: 12px;">Se nenhum for selecionado, aceita todos</small>
            </div>
            
            <!-- Evento de Assinatura -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 12px; font-weight: 600; color: #e2e8f0;">Evento de Assinatura</label>
                <div style="background: #0f172a; padding: 16px; border-radius: 8px; border: 1px solid #334155;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="subscription_events" value="1">
                        Produto do tipo assinatura
                    </label>
                </div>
            </div>
            
            <!-- Status -->
            <div id="statusField" style="margin-bottom: 30px; display: none;">
                <label style="display: block; margin-bottom: 12px; font-weight: 600; color: #e2e8f0;">Status da Integração</label>
                <label class="switch">
                    <input type="checkbox" id="webhook_status" name="status" value="active" checked>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="closeModal()" class="btn" style="background: #334155; color: white;">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Salvar Configuração</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ver URL -->
<div id="urlModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #1e293b; border-radius: 20px; padding: 40px; max-width: 700px; width: 90%; border: 1px solid #334155;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="color: white; font-size: 24px;">🔗 URL do Webhook</h2>
            <button onclick="document.getElementById('urlModal').style.display='none'" style="background: none; border: none; color: #94a3b8; font-size: 28px; cursor: pointer;">×</button>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">Cole esta URL no seu checkout/plataforma:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="webhookUrl" readonly style="flex: 1; padding: 12px; background: #0f172a; border: 1px solid #667eea; border-radius: 8px; color: #10b981; font-size: 13px; font-family: monospace;">
                <button onclick="copyWebhookUrl()" class="btn btn-primary">📋 Copiar</button>
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <button onclick="regenerateSecretKey()" class="btn" style="background: #f59e0b; color: white; width: 100%;">🔄 Regenerar Chave Secreta</button>
        </div>
        
        <div style="background: #0f172a; padding: 20px; border-radius: 8px; border: 1px solid #334155;">
            <h3 style="color: #a5b4fc; margin-bottom: 15px; font-size: 16px;">📖 Como Configurar</h3>
            <ol style="color: #94a3b8; line-height: 2; padding-left: 20px;">
                <li>Copie a URL acima</li>
                <li>Acesse o painel do seu checkout/plataforma de pagamento</li>
                <li>Procure por "Webhook", "Postback" ou "Notificações"</li>
                <li>Cole a URL e configure os eventos desejados</li>
                <li>Teste enviando uma venda de teste</li>
            </ol>
        </div>
    </div>
</div>

<style>
.badge-success { background: #10b98120; color: #10b981; }
.badge-warning { background: #f59e0b20; color: #f59e0b; }

.checkbox-label {
    display: flex;
    align-items: center;
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    color: #e2e8f0;
}
.checkbox-label:hover { background: #1e293b; }
.checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Toggle Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}
.switch input { opacity: 0; width: 0; height: 0; }
.slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #334155;
    transition: .4s;
    border-radius: 34px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .slider { background-color: #10b981; }
input:checked + .slider:before { transform: translateX(26px); }
</style>

<script>
let currentWebhookId = null;

function openCreateModal() {
    document.getElementById('modalTitle').textContent = '🔗 Configurar Webhook';
    document.getElementById('webhookForm').reset();
    document.getElementById('webhook_id').value = '';
    document.getElementById('statusField').style.display = 'none';
    // Marca "Pago" por padrão
    document.querySelector('input[name="payment_statuses[]"][value="paid"]').checked = true;
    currentWebhookId = null;
    document.getElementById('webhookModal').style.display = 'flex';
}

async function editWebhook(webhookId) {
    try {
        const response = await fetch(`index.php?page=webhook-get&id=${webhookId}`);
        const result = await response.json();
        
        if (result.success) {
            const webhook = result.webhook;
            const config = JSON.parse(webhook.config || '{}');
            currentWebhookId = webhook.id;
            
            document.getElementById('modalTitle').textContent = '✏️ Editar Webhook';
            document.getElementById('webhook_id').value = webhook.id;
            document.getElementById('webhook_name').value = webhook.name;
            document.getElementById('webhook_platform').value = webhook.platform || '';
            
            // Produtos
            if (config.products && config.products.length > 0) {
                const select = document.getElementById('webhook_products');
                Array.from(select.options).forEach(option => {
                    option.selected = config.products.includes(parseInt(option.value));
                });
            }
            
            // Métodos de pagamento
            document.querySelectorAll('input[name="payment_methods[]"]').forEach(cb => {
                cb.checked = config.payment_methods && config.payment_methods.includes(cb.value);
            });
            
            // Status de pagamento
            document.querySelectorAll('input[name="payment_statuses[]"]').forEach(cb => {
                cb.checked = config.payment_statuses && config.payment_statuses.includes(cb.value);
            });
            
            // Assinatura
            document.querySelector('input[name="subscription_events"]').checked = config.subscription_events;
            
            // Versão
            document.querySelector(`input[name="version"][value="${config.version || '1.0'}"]`).checked = true;
            
            // Status
            document.getElementById('webhook_status').checked = webhook.status === 'active';
            document.getElementById('statusField').style.display = 'block';
            
            document.getElementById('webhookModal').style.display = 'flex';
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao carregar webhook: ' + error.message);
    }
}

async function viewWebhook(webhookId) {
    try {
        currentWebhookId = webhookId;
        const response = await fetch(`index.php?page=webhook-get&id=${webhookId}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('webhookUrl').value = result.webhook.url;
            document.getElementById('urlModal').style.display = 'flex';
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao carregar webhook: ' + error.message);
    }
}

function viewLogs(webhookId) {
    window.location.href = `index.php?page=webhook-logs&id=${webhookId}`;
}

async function saveWebhook(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Coleta produtos selecionados
    const products = Array.from(document.getElementById('webhook_products').selectedOptions).map(o => o.value);
    formData.delete('products[]');
    formData.append('products', JSON.stringify(products));
    
    // Coleta métodos de pagamento
    const paymentMethods = Array.from(document.querySelectorAll('input[name="payment_methods[]"]:checked')).map(cb => cb.value);
    formData.delete('payment_methods[]');
    formData.append('payment_methods', JSON.stringify(paymentMethods));
    
    // Coleta status de pagamento
    const paymentStatuses = Array.from(document.querySelectorAll('input[name="payment_statuses[]"]:checked')).map(cb => cb.value);
    formData.delete('payment_statuses[]');
    formData.append('payment_statuses', JSON.stringify(paymentStatuses));
    
    // Status (toggle)
    const statusToggle = document.getElementById('webhook_status');
    formData.set('status', statusToggle && statusToggle.checked ? 'active' : 'inactive');
    
    const webhookId = formData.get('webhook_id');
    const url = webhookId ? 'index.php?page=webhook-update' : 'index.php?page=webhook-create';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            
            if (!webhookId && result.webhook_url) {
                closeModal();
                currentWebhookId = result.webhook_id;
                document.getElementById('webhookUrl').value = result.webhook_url;
                document.getElementById('urlModal').style.display = 'flex';
                
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
            } else {
                closeModal();
                window.location.reload();
            }
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao salvar webhook: ' + error.message);
    }
}

async function deleteWebhook(webhookId, webhookName) {
    if (!confirm(`Tem certeza que deseja deletar o webhook "${webhookName}"?\n\nTodos os logs serão deletados também.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('webhook_id', webhookId);
        
        const response = await fetch('index.php?page=webhook-delete', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            window.location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao deletar webhook: ' + error.message);
    }
}

async function regenerateSecretKey() {
    if (!currentWebhookId) {
        alert('Erro: ID do webhook não encontrado');
        return;
    }
    
    if (!confirm('Tem certeza que deseja regenerar a chave secreta?\n\nA URL antiga não funcionará mais e você precisará atualizar na plataforma de pagamento.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('webhook_id', currentWebhookId);
        
        const response = await fetch('index.php?page=webhook-regenerate-key', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            document.getElementById('webhookUrl').value = result.webhook_url;
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao regenerar chave: ' + error.message);
    }
}

function copyWebhookUrl() {
    const input = document.getElementById('webhookUrl');
    input.select();
    document.execCommand('copy');
    alert('✓ URL copiada para área de transferência!');
}

function closeModal() {
    document.getElementById('webhookModal').style.display = 'none';
}

document.getElementById('webhookModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('urlModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>