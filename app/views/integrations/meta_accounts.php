<!-- Barra de Navegação -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <a href="index.php?page=integracoes" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar para Integrações
    </a>
    
    <div style="display: flex; gap: 10px;">
        <button onclick="syncAccounts()" class="btn" style="background: #10b981; color: white;">
            🔄 Sincronizar Contas
        </button>
        <a href="index.php?page=integracoes-meta" class="btn" style="background: #334155; color: white; text-decoration: none;">
            ⚙️ Configurações
        </a>
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
                            <?= $account['status'] === 'active' ? '✅ Ativa' : '⏸ Desabilitada' ?>
                        </div>
                    </div>
                </div>
                
                <div class="account-actions">
                    <!-- Toggle Switch -->
                    <label class="toggle-switch">
                        <input 
                            type="checkbox" 
                            <?= $account['status'] === 'active' ? 'checked' : '' ?>
                            onchange="toggleAccount(<?= $account['id'] ?>, this.checked)"
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
</style>

<script>
// Toggle individual
async function toggleAccount(accountId, isActive) {
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
        
        if (!result.success) {
            alert('✗ ' + result.message);
            // Reverte o toggle
            event.target.checked = !isActive;
        }
    } catch (error) {
        alert('Erro ao atualizar conta: ' + error.message);
        event.target.checked = !isActive;
    }
}

// Ativar/Desativar todas
function toggleAll(activate) {
    const checkboxes = document.querySelectorAll('.toggle-switch input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
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
            alert('✓ ' + result.message);
            window.location.reload();
        } else {
            alert('✗ ' + result.message);
        }
    } catch (error) {
        alert('Erro ao sincronizar: ' + error.message);
    } finally {
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

// Desconectar conta
async function disconnectAccount(accountId) {
    if (!confirm('Tem certeza que deseja desconectar esta conta?\n\nAs campanhas não serão mais sincronizadas.')) {
        return;
    }
    
    // TODO: Implementar desconexão
    alert('Funcionalidade em desenvolvimento');
}
</script>