<!-- Barra de Navegação -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <a href="index.php?page=integracoes" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar para Integrações
    </a>
    
    <div style="display: flex; gap: 10px;">
        <button onclick="showDisconnectModal()" class="btn" style="background: #ef4444; color: white;">
            🔌 Desconectar Integração
        </button>
        <button onclick="syncAccounts()" class="btn" style="background: #10b981; color: white;">
            🔄 Sincronizar Contas
        </button>
        <a href="index.php?page=integracoes-meta" class="btn" style="background: #334155; color: white; text-decoration: none;">
            ⚙️ Configurações
        </a>
    </div>
</div>

<!-- Modal de Desconexão -->
<div id="disconnectModal" class="modal">
    <div class="modal-overlay" onclick="closeDisconnectModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <h2 class="modal-title">Desconectar Integração Meta Ads?</h2>
            <p class="modal-subtitle">Esta ação não pode ser desfeita</p>
        </div>
        
        <div class="modal-body">
            <div class="warning-list">
                <div class="warning-item">
                    <div class="warning-icon">❌</div>
                    <div class="warning-text">
                        <strong>Todas as contas serão removidas</strong>
                        <span>Você perderá acesso às contas de anúncio conectadas</span>
                    </div>
                </div>
                
                <div class="warning-item">
                    <div class="warning-icon">🔒</div>
                    <div class="warning-text">
                        <strong>Access token será apagado</strong>
                        <span>A autenticação com o Facebook será revogada</span>
                    </div>
                </div>
                
                <div class="warning-item">
                    <div class="warning-icon">⏸️</div>
                    <div class="warning-text">
                        <strong>Sincronização será desativada</strong>
                        <span>Os dados não serão mais atualizados automaticamente</span>
                    </div>
                </div>
                
                <div class="warning-item">
                    <div class="warning-icon">🔄</div>
                    <div class="warning-text">
                        <strong>Será necessário reconectar do zero</strong>
                        <span>Você precisará refazer toda a configuração OAuth</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button onclick="closeDisconnectModal()" class="btn-modal btn-cancel">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                Cancelar
            </button>
            <button onclick="confirmDisconnect()" class="btn-modal btn-danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
                Sim, Desconectar
            </button>
        </div>
    </div>
</div>

<!-- Mensagens -->
<?php if (isset($_GET['success'])): ?>
<div style="padding: 15px; background: #10b98120; border: 1px solid #10b981; border-radius: 8px; color: #10b981; margin-bottom: 20px;">
    ✓ <?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div style="padding: 15px; background: #ef444420; border: 1px solid #ef4444; border-radius: 8px; color: #ef4444; margin-bottom: 20px;">
    ✗ <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<!-- Card de Gerenciamento -->
<div class="card">
    <div class="card-header" style="border-bottom: 1px solid #334155; padding-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #1877f2, #0c63e4); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="35" height="35" viewBox="0 0 24 24" fill="white">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </div>
            <div>
                <h2 style="color: white; font-size: 24px; font-weight: 700; margin-bottom: 4px;">
                    Meta Ads
                </h2>
                <p style="color: #94a3b8; font-size: 14px;">
                    Gerenciar Contas de Anúncio
                </p>
            </div>
        </div>
    </div>
    
    <!-- Adicionar Perfil -->
    <div style="padding: 20px; border-bottom: 1px solid #334155; background: #0f172a;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="color: #e2e8f0; font-size: 16px; font-weight: 600; margin-bottom: 4px;">
                    Conecte seus perfis por aqui
                </h3>
                <p style="color: #64748b; font-size: 13px;">
                    Adicione novos perfis e contas de anúncio
                </p>
            </div>
            <a href="index.php?page=integracoes-meta-conectar" class="btn btn-primary">
                ➕ Adicionar Perfil
            </a>
        </div>
    </div>
    
    <!-- Lista de Contas -->
    <div style="padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: #e2e8f0; font-size: 18px; font-weight: 600;">
                Contas de Anúncio (Meta)
            </h3>
            
            <?php if (!empty($accounts)): ?>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button onclick="toggleAll(true)" class="btn" style="padding: 8px 16px; background: #10b981; color: white; font-size: 13px;">
                    ✓ Ativar Todas
                </button>
                <button onclick="toggleAll(false)" class="btn" style="padding: 8px 16px; background: #64748b; color: white; font-size: 13px;">
                    ⏸ Desativar Todas
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($accounts)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📱</div>
            <div class="empty-state-title">Nenhuma conta conectada</div>
            <p>Conecte seus perfis Meta Ads para começar a importar campanhas</p>
            <a href="index.php?page=integracoes-meta-conectar" class="btn btn-primary" style="margin-top: 20px; text-decoration: none;">
                🔗 Conectar Primeira Conta
            </a>
        </div>
        <?php else: ?>
        
        <!-- Tabela de Contas -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($accounts as $account): ?>
            <div class="account-row <?= $account['status'] === 'active' ? 'active' : 'inactive' ?>">
                <div class="account-info">
                    <div class="account-icon">
                        <?php if ($account['status'] === 'active'): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                        <?php endif; ?>
                    </div>
                    
                    <div class="account-details">
                        <div class="account-name">
                            <?= htmlspecialchars($account['account_name']) ?>
                        </div>
                        <div class="account-meta">
                            ID: <?= htmlspecialchars($account['account_id']) ?> •
                            <span class="status-text"><?= $account['status'] === 'active' ? '✅ Ativa' : '⏸ Desabilitada' ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="account-actions">
                    <!-- Toggle Switch - CORRIGIDO -->
                    <label class="toggle-switch">
                        <input 
                            type="checkbox" 
                            data-account-id="<?= $account['id'] ?>"
                            <?= $account['status'] === 'active' ? 'checked' : '' ?>
                            class="account-toggle"
                        >
                        <span class="toggle-slider"></span>
                    </label>
                    
                    <!-- Menu de Opções -->
                    <div class="dropdown">
                        <button class="dropdown-toggle">⋮</button>
                        <div class="dropdown-menu">
                            <a href="javascript:void(0)" onclick="viewCampaigns(<?= $account['id'] ?>)">
                                📊 Ver Campanhas
                            </a>
                            <a href="javascript:void(0)" onclick="syncAccount(<?= $account['id'] ?>)">
                                🔄 Sincronizar
                            </a>
                            <a href="javascript:void(0)" onclick="disconnectAccount(<?= $account['id'] ?>)" style="color: #ef4444;">
                                🗑️ Desconectar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<!-- Informações -->
<div class="card" style="margin-top: 20px; background: rgba(102, 126, 234, 0.05); border-color: rgba(102, 126, 234, 0.3);">
    <h3 style="color: #a5b4fc; margin-bottom: 15px;">💡 Dicas</h3>
    <ul style="color: #94a3b8; line-height: 2; padding-left: 20px;">
        <li>Ative apenas as contas que deseja sincronizar</li>
        <li>A sincronização ocorre automaticamente a cada hora</li>
        <li>Você pode adicionar múltiplos perfis do Facebook</li>
        <li>Os dados são atualizados em tempo real</li>
    </ul>
</div>

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    animation: fadeIn 0.3s ease;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    position: relative;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 2px solid #334155;
    border-radius: 24px;
    max-width: 540px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    animation: slideUp 0.3s ease;
    overflow: hidden;
}

.modal-header {
    text-align: center;
    padding: 40px 32px 24px;
    border-bottom: 1px solid #334155;
}

.modal-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.05));
    border: 2px solid rgba(239, 68, 68, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

.modal-icon svg {
    color: #ef4444;
}

.modal-title {
    font-size: 24px;
    font-weight: 700;
    color: white;
    margin-bottom: 8px;
}

.modal-subtitle {
    font-size: 14px;
    color: #94a3b8;
}

.modal-body {
    padding: 32px;
}

.warning-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.warning-item {
    display: flex;
    gap: 16px;
    padding: 16px;
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid #334155;
    border-radius: 12px;
    transition: all 0.2s;
}

.warning-item:hover {
    border-color: #475569;
    transform: translateX(4px);
}

.warning-icon {
    font-size: 28px;
    flex-shrink: 0;
}

.warning-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.warning-text strong {
    color: #e2e8f0;
    font-size: 15px;
    font-weight: 600;
}

.warning-text span {
    color: #94a3b8;
    font-size: 13px;
    line-height: 1.5;
}

.modal-footer {
    padding: 24px 32px;
    background: rgba(15, 23, 42, 0.5);
    border-top: 1px solid #334155;
    display: flex;
    gap: 12px;
}

.btn-modal {
    flex: 1;
    padding: 14px 24px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-cancel {
    background: #334155;
    color: #e2e8f0;
    border: 1px solid #475569;
}

.btn-cancel:hover {
    background: #475569;
    transform: translateY(-2px);
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-danger:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.6);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(400px); opacity: 0; }
}

/* Account Row Styles */
.account-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 20px;
    background: #0f172a;
    border: 2px solid #334155;
    border-radius: 12px;
    transition: all 0.3s;
}

.account-row:hover {
    border-color: #667eea;
    transform: translateX(4px);
}

.account-row.active {
    border-color: #10b98130;
    background: rgba(16, 185, 129, 0.05);
}

.account-info {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
}

.account-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.account-row.active .account-icon {
    background: #10b98120;
    color: #10b981;
}

.account-row.inactive .account-icon {
    background: #64748b20;
    color: #64748b;
}

.account-details {
    flex: 1;
}

.account-name {
    color: white;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
}

.account-meta {
    color: #64748b;
    font-size: 12px;
}

.account-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
    cursor: pointer;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #334155;
    border-radius: 28px;
    transition: 0.3s;
}

.toggle-slider:before {
    content: "";
    position: absolute;
    height: 20px;
    width: 20px;
    left: 4px;
    bottom: 4px;
    background: white;
    border-radius: 50%;
    transition: 0.3s;
}

.toggle-switch input:checked + .toggle-slider {
    background: #10b981;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

/* Dropdown Menu */
.dropdown {
    position: relative;
}

.dropdown-toggle {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 20px;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 6px;
    transition: all 0.2s;
}

.dropdown-toggle:hover {
    background: #334155;
}

.dropdown-menu {
    position: absolute;
    right: 0;
    top: 100%;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 8px;
    padding: 8px 0;
    min-width: 180px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    z-index: 1000;
    display: none;
}

.dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-menu a {
    display: block;
    padding: 10px 16px;
    color: #e2e8f0;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.dropdown-menu a:hover {
    background: #334155;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.empty-state-title {
    font-size: 20px;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 10px;
}

.empty-state p {
    color: #94a3b8;
    font-size: 14px;
}
</style>

<script>
// Event delegation para todos os toggles - CORRIGIDO
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.account-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            const accountId = parseInt(this.getAttribute('data-account-id'));
            const isActive = this.checked;
            const status = isActive ? 'active' : 'inactive';
            
            try {
                const formData = new FormData();
                formData.append('account_id', accountId);
                formData.append('status', status);
                
                const response = await fetch('index.php?page=integracoes-meta-toggle', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', result.message);
                    
                    // Atualiza o visual do card
                    const accountRow = this.closest('.account-row');
                    if (accountRow) {
                        accountRow.className = 'account-row ' + (isActive ? 'active' : 'inactive');
                        
                        // Atualiza o status text
                        const statusText = accountRow.querySelector('.status-text');
                        if (statusText) {
                            statusText.textContent = isActive ? '✅ Ativa' : '⏸ Desabilitada';
                        }
                    }
                } else {
                    showNotification('error', result.message);
                    this.checked = !isActive;
                }
            } catch (error) {
                showNotification('error', 'Erro ao atualizar conta: ' + error.message);
                this.checked = !isActive;
            }
        });
    });
});

// Mostrar modal de desconexão
function showDisconnectModal() {
    const modal = document.getElementById('disconnectModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Fechar modal de desconexão
function closeDisconnectModal() {
    const modal = document.getElementById('disconnectModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Fechar modal ao pressionar ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDisconnectModal();
    }
});

// Confirmar desconexão
async function confirmDisconnect() {
    const modal = document.getElementById('disconnectModal');
    const dangerBtn = modal.querySelector('.btn-danger');
    const originalHTML = dangerBtn.innerHTML;
    
    dangerBtn.innerHTML = '<svg style="width: 18px; height: 18px; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg> Desconectando...';
    dangerBtn.disabled = true;
    
    try {
        const response = await fetch('index.php?page=integracoes-meta-remover', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            setTimeout(() => {
                window.location.href = 'index.php?page=integracoes-meta';
            }, 1500);
        } else {
            showNotification('error', result.message);
            dangerBtn.innerHTML = originalHTML;
            dangerBtn.disabled = false;
        }
    } catch (error) {
        showNotification('error', 'Erro ao desconectar: ' + error.message);
        dangerBtn.innerHTML = originalHTML;
        dangerBtn.disabled = false;
    }
}

// Sistema de notificações
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 24px;
        right: 24px;
        padding: 16px 24px;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        font-size: 15px;
        z-index: 10001;
        animation: slideIn 0.3s ease;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        max-width: 400px;
        display: flex;
        align-items: center;
        gap: 12px;
    `;
    
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        notification.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span>${message}</span>
        `;
    } else {
        notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        notification.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span>${message}</span>
        `;
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Ativar/Desativar todas - CORRIGIDO
function toggleAll(activate) {
    document.querySelectorAll('.account-toggle').forEach(checkbox => {
        if (checkbox.checked !== activate) {
            checkbox.checked = activate;
            checkbox.dispatchEvent(new Event('change'));
        }
    });
}

// Sincronizar contas
async function syncAccounts() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Sincronizando...';
    btn.disabled = true;
    
    try {
        const response = await fetch('index.php?page=integracoes-meta-sync', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('error', result.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    } catch (error) {
        showNotification('error', 'Erro ao sincronizar: ' + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Ver campanhas
function viewCampaigns(accountId) {
    window.location.href = 'index.php?page=meta-campanhas&account=' + accountId;
}

// Sincronizar conta individual
async function syncAccount(accountId) {
    alert('⏳ Sincronizando conta...');
    // TODO: Implementar sincronização individual
}

// Desconectar conta individual
async function disconnectAccount(accountId) {
    if (!confirm('Tem certeza que deseja desconectar esta conta?\n\nAs campanhas não serão mais sincronizadas.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('account_id', accountId);
        
        const response = await fetch('index.php?page=integracoes-meta-disconnect-account', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', 'Conta desconectada com sucesso!');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Erro ao desconectar conta: ' + error.message);
    }
}
</script>