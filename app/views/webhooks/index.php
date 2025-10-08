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
                    <th>Plataforma</th>
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
                    <td>
                        <?php
                        $platforms = [
                            'hotmart' => '🔥 Hotmart',
                            'kiwify' => '🥝 Kiwify',
                            'eduzz' => '📚 Eduzz',
                            'perfectpay' => '💳 Perfect Pay',
                            'monetizze' => '💰 Monetizze',
                            'custom' => '⚙️ Custom'
                        ];
                        echo $platforms[$webhook['platform']] ?? $webhook['platform'];
                        ?>
                    </td>
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
                            style="padding: 6px 12px; font-size: 12px; background: #667eea; color: white;"
                            title="Ver URL"
                        >
                            👁️
                        </button>
                        <button 
                            onclick='viewLogs(<?= $webhook['id'] ?>)' 
                            class="btn" 
                            style="padding: 6px 12px; font-size: 12px; background: #10b981; color: white;"
                            title="Ver Logs"
                        >
                            📊
                        </button>
                        <button 
                            onclick='editWebhook(<?= $webhook['id'] ?>)' 
                            class="btn" 
                            style="padding: 6px 12px; font-size: 12px; background: #f59e0b; color: white;"
                            title="Editar"
                        >
                            ✏️
                        </button>
                        <button 
                            onclick='deleteWebhook(<?= $webhook['id'] ?>, "<?= addslashes($webhook['name']) ?>")' 
                            class="btn" 
                            style="padding: 6px 12px; font-size: 12px; background: #ef4444; color: white;"
                            title="Deletar"
                        >
                            🗑️
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
        max-width: 600px;
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
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Plataforma *
                </label>
                <select 
                    id="webhook_platform" 
                    name="platform"
                    required
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
                    <option value="">Selecione...</option>
                    <option value="hotmart">🔥 Hotmart</option>
                    <option value="kiwify">🥝 Kiwify</option>
                    <option value="eduzz">📚 Eduzz</option>
                    <option value="perfectpay">💳 Perfect Pay</option>
                    <option value="monetizze">💰 Monetizze</option>
                    <option value="custom">⚙️ Custom/Outro</option>
                </select>
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
            
            <div style="margin-bottom: 30px;" id="statusField" style="display: none;">
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

<style>
.badge-success {
    background: #10b98120;
    color: #10b981;
}

.badge-warning {
    background: #f59e0b20;
    color: #f59e0b;
}
</style>

<script>
// Abrir modal de criação
function openCreateModal() {
    document.getElementById('modalTitle').textContent = '➕ Novo Webhook';
    document.getElementById('webhookForm').reset();
    document.getElementById('webhook_id').value = '';
    document.getElementById('statusField').style.display = 'none';
    document.getElementById('webhookModal').style.display = 'flex';
}

// Editar webhook
async function editWebhook(webhookId) {
    try {
        const response = await fetch(`index.php?page=webhook-get&id=${webhookId}`);
        const result = await response.json();
        
        if (result.success) {
            const webhook = result.webhook;
            
            document.getElementById('modalTitle').textContent = '✏️ Editar Webhook';
            document.getElementById('webhook_id').value = webhook.id;
            document.getElementById('webhook_name').value = webhook.name;
            document.getElementById('webhook_platform').value = webhook.platform;
            document.getElementById('webhook_product').value = webhook.product_id || '';
            document.getElementById('webhook_status').value = webhook.status;
            document.getElementById('statusField').style.display = 'block';
            
            document.getElementById('webhookModal').style.display = 'flex';
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao carregar webhook: ' + error.message);
    }
}

// Ver URL do webhook
async function viewWebhook(webhookId) {
    try {
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

// Ver logs
function viewLogs(webhookId) {
    window.location.href = `index.php?page=webhook-logs&id=${webhookId}`;
}

// Salvar webhook
async function saveWebhook(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
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
            
            // Se for novo, mostra a URL
            if (!webhookId && result.webhook_url) {
                closeModal();
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

// Deletar webhook
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

// Copiar URL do webhook
function copyWebhookUrl() {
    const input = document.getElementById('webhookUrl');
    input.select();
    document.execCommand('copy');
    
    alert('✓ URL copiada para área de transferência!');
}

// Fechar modal
function closeModal() {
    document.getElementById('webhookModal').style.display = 'none';
}

// Fechar modal ao clicar fora
document.getElementById('webhookModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('urlModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
</script>