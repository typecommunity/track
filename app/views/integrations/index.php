<style>
/* Integration Cards Grid */
.integrations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

/* Integration Card */
.integration-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.integration-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.3s;
}

.integration-card:hover {
    transform: translateY(-8px);
    border-color: #667eea;
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
}

.integration-card:hover::before {
    opacity: 1;
}

/* Header */
.integration-header {
    padding: 28px;
    border-bottom: 1px solid #334155;
    display: flex;
    align-items: center;
    gap: 20px;
}

.integration-logo {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.integration-logo::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.2), transparent);
}

.integration-logo svg {
    position: relative;
    z-index: 1;
}

.integration-info {
    flex: 1;
}

.integration-name {
    font-size: 20px;
    font-weight: 700;
    color: white;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.integration-description {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 500;
}

/* Body */
.integration-body {
    padding: 28px;
    min-height: 160px;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 20px;
}

.status-badge.connected {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.05));
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-badge.configured {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.05));
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.status-badge.disconnected {
    background: linear-gradient(135deg, rgba(148, 163, 184, 0.2), rgba(148, 163, 184, 0.05));
    color: #94a3b8;
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.status-badge.soon {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(139, 92, 246, 0.05));
    color: #a78bfa;
    border: 1px solid rgba(139, 92, 246, 0.3);
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(0.95); }
}

.integration-text {
    color: #cbd5e1;
    line-height: 1.7;
    font-size: 14px;
}

/* Stats Grid */
.integration-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-top: 20px;
}

.stat-item {
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s;
}

.stat-item:hover {
    border-color: #667eea;
    transform: translateY(-2px);
}

.stat-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    font-weight: 600;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: white;
    line-height: 1;
}

/* Footer */
.integration-footer {
    padding: 20px 28px;
    border-top: 1px solid #334155;
    display: flex;
    gap: 12px;
    background: rgba(15, 23, 42, 0.5);
}

.integration-btn {
    flex: 1;
    padding: 12px 20px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.integration-btn svg {
    width: 18px;
    height: 18px;
}

.integration-btn.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.integration-btn.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.integration-btn.secondary {
    background: #334155;
    color: #e2e8f0;
    border: 1px solid #475569;
}

.integration-btn.secondary:hover {
    background: #475569;
    border-color: #64748b;
}

.integration-btn:disabled {
    background: #1e293b;
    color: #64748b;
    cursor: not-allowed;
    opacity: 0.5;
}

.integration-btn:disabled:hover {
    transform: none;
    box-shadow: none;
}

/* Video Tutorial Section */
.video-section {
    margin-top: 40px;
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.05));
    border: 1px solid rgba(139, 92, 246, 0.3);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
}

.video-title {
    font-size: 32px;
    font-weight: 700;
    color: white;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
}

.video-title-icon {
    font-size: 40px;
}

.video-description {
    color: #94a3b8;
    font-size: 16px;
    margin-bottom: 28px;
    line-height: 1.6;
}

.video-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 32px;
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    font-weight: 600;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 8px 24px rgba(139, 92, 246, 0.4);
}

.video-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(139, 92, 246, 0.6);
}

/* Responsive */
@media (max-width: 768px) {
    .integrations-grid {
        grid-template-columns: 1fr;
    }
    
    .integration-stats {
        grid-template-columns: 1fr;
    }
    
    .video-title {
        font-size: 24px;
    }
    
    .video-section {
        padding: 24px;
    }
}
</style>

<!-- Integration Cards -->
<div class="integrations-grid">
    
    <!-- Meta Ads -->
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-logo" style="background: linear-gradient(135deg, #1877f2, #0c63e4);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="white">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </div>
            <div class="integration-info">
                <h3 class="integration-name">
                    Meta Ads
                    <?php if ($metaConfig && $metaConfig['status'] === 'connected'): ?>
                    <svg style="width: 20px; height: 20px; color: #10b981;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?php endif; ?>
                </h3>
                <p class="integration-description">Facebook & Instagram Ads</p>
            </div>
        </div>
        
        <div class="integration-body">
            <?php if ($metaConfig && $metaConfig['status'] === 'connected'): ?>
                <div class="status-badge connected">
                    <span class="status-dot"></span>
                    Conectado e Sincronizado
                </div>
                <div class="integration-stats">
                    <div class="stat-item">
                        <div class="stat-label">Contas Ativas</div>
                        <div class="stat-value"><?= $metaAccounts ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Última Sync</div>
                        <div class="stat-value" id="last-sync-time" style="font-size: 16px;">
                            <?= date('d/m H:i', strtotime($metaConfig['updated_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($metaConfig && $metaConfig['status'] === 'configured'): ?>
                <div class="status-badge configured">
                    <span class="status-dot"></span>
                    Configurado
                </div>
                <p class="integration-text">
                    Suas credenciais foram configuradas com sucesso. 
                    Conecte suas contas de anúncio para começar a sincronizar campanhas.
                </p>
            <?php else: ?>
                <div class="status-badge disconnected">
                    <span class="status-dot"></span>
                    Não Configurado
                </div>
                <p class="integration-text">
                    Configure suas credenciais do Meta Business para começar a importar 
                    campanhas e métricas automaticamente.
                </p>
            <?php endif; ?>
        </div>
        
        <div class="integration-footer">
            <?php if ($metaConfig && $metaConfig['status'] === 'connected'): ?>
                <a href="index.php?page=integracoes-meta-contas" class="integration-btn secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m5.2-14.2l-4.2 4.2m0 6l4.2 4.2M23 12h-6m-6 0H1m14.2 5.2l-4.2-4.2m0-6l-4.2-4.2"></path>
                    </svg>
                    Gerenciar
                </a>
                <button onclick="syncMeta()" class="integration-btn primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                    </svg>
                    Sincronizar
                </button>
            <?php elseif ($metaConfig && $metaConfig['status'] === 'configured'): ?>
                <a href="index.php?page=integracoes-meta" class="integration-btn secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                    </svg>
                    Configurações
                </a>
                <a href="index.php?page=integracoes-meta-conectar" class="integration-btn primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                    </svg>
                    Conectar
                </a>
            <?php else: ?>
                <a href="index.php?page=integracoes-meta" class="integration-btn primary" style="flex: initial; width: 100%;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v6M12 12v10M8 8l4 4 4-4M8 16l4 4 4-4"></path>
                    </svg>
                    Começar Configuração
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Google Ads -->
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-logo" style="background: linear-gradient(135deg, #4285f4, #34a853);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="white">
                    <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/>
                </svg>
            </div>
            <div class="integration-info">
                <h3 class="integration-name">Google Ads</h3>
                <p class="integration-description">Search & Display Ads</p>
            </div>
        </div>
        
        <div class="integration-body">
            <div class="status-badge soon">
                <span class="status-dot"></span>
                Em Desenvolvimento
            </div>
            <p class="integration-text">
                A integração com Google Ads está em desenvolvimento e estará 
                disponível em breve para você gerenciar suas campanhas de busca e display.
            </p>
        </div>
        
        <div class="integration-footer">
            <button class="integration-btn secondary" disabled style="width: 100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Disponível em Breve
            </button>
        </div>
    </div>
    
    <!-- Kwai Ads -->
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-logo" style="background: linear-gradient(135deg, #ff6d00, #ff8c00);">
                <span style="font-size: 32px;">🎥</span>
            </div>
            <div class="integration-info">
                <h3 class="integration-name">Kwai Ads</h3>
                <p class="integration-description">Short Video Ads</p>
            </div>
        </div>
        
        <div class="integration-body">
            <div class="status-badge soon">
                <span class="status-dot"></span>
                Em Desenvolvimento
            </div>
            <p class="integration-text">
                A integração com Kwai Ads está sendo desenvolvida para você 
                gerenciar suas campanhas de vídeos curtos diretamente no UTMTrack.
            </p>
        </div>
        
        <div class="integration-footer">
            <button class="integration-btn secondary" disabled style="width: 100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Disponível em Breve
            </button>
        </div>
    </div>
    
</div>

<!-- Video Tutorial -->
<div class="video-section">
    <h2 class="video-title">
        <span class="video-title-icon">🚀</span>
        Guia Completo de Configuração do Meta Ads
    </h2>
    <p class="video-description">
        Siga este tutorial passo a passo em vídeo para criar seu app no Meta Developers e integrar com o UTMTrack de forma simples e rápida
    </p>
    <a href="#" class="video-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M8 5v14l11-7z"/>
        </svg>
        Assistir Tutorial Completo
    </a>
</div>

<script>
async function syncMeta() {
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    
    btn.innerHTML = `
        <svg style="width: 18px; height: 18px; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
        </svg>
        Sincronizando...
    `;
    btn.disabled = true;
    
    try {
        const response = await fetch('index.php?page=integracoes-meta-sync', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            
            // Atualizar o horário da última sincronização
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const formattedTime = `${day}/${month} ${hours}:${minutes}`;
            
            const lastSyncElement = document.getElementById('last-sync-time');
            if (lastSyncElement) {
                lastSyncElement.textContent = formattedTime;
                
                // Adicionar animação de atualização
                lastSyncElement.style.animation = 'none';
                setTimeout(() => {
                    lastSyncElement.style.animation = 'pulse 0.5s ease';
                }, 10);
            }
            
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        } else {
            showNotification('error', result.message);
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    } catch (error) {
        showNotification('error', 'Erro ao sincronizar: ' + error.message);
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    `;
    
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        notification.innerHTML = '✓ ' + message;
    } else {
        notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        notification.innerHTML = '✗ ' + message;
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

const style = document.createElement('style');
style.textContent = `
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
`;
document.head.appendChild(style);
</script>