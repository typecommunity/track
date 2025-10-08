<!-- Cards de Integrações -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px;">
    
    <!-- Meta Ads -->
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-icon" style="background: linear-gradient(135deg, #1877f2, #0c63e4);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="white">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </div>
            <div>
                <h3 class="integration-title">Meta Ads</h3>
                <p class="integration-subtitle">Facebook & Instagram Ads</p>
            </div>
        </div>
        
        <div class="integration-body">
            <?php if ($metaConfig && $metaConfig['status'] === 'connected'): ?>
                <div class="integration-status connected">
                    <span class="status-dot"></span>
                    <span>✓ Conectado</span>
                </div>
                <div class="integration-info">
                    <div class="info-item">
                        <span class="info-label">Contas Ativas</span>
                        <span class="info-value"><?= $metaAccounts ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Última Sync</span>
                        <span class="info-value"><?= date('d/m H:i', strtotime($metaConfig['updated_at'])) ?></span>
                    </div>
                </div>
            <?php elseif ($metaConfig && $metaConfig['status'] === 'configured'): ?>
                <div class="integration-status configured">
                    <span class="status-dot"></span>
                    <span>⚙️ Configurado</span>
                </div>
                <p class="integration-description">
                    Credenciais configuradas. Conecte suas contas de anúncio agora.
                </p>
            <?php else: ?>
                <div class="integration-status disconnected">
                    <span class="status-dot"></span>
                    <span>Não conectado</span>
                </div>
                <p class="integration-description">
                    Conecte suas contas Meta Ads para importar campanhas automaticamente.
                </p>
            <?php endif; ?>
        </div>
        
        <div class="integration-footer">
            <?php if ($metaConfig && $metaConfig['status'] === 'connected'): ?>
                <a href="index.php?page=integracoes-meta-contas" class="btn btn-secondary">
                    ⚙️ Gerenciar Contas
                </a>
                <button onclick="syncMeta()" class="btn btn-primary">
                    🔄 Sincronizar
                </button>
            <?php elseif ($metaConfig && $metaConfig['status'] === 'configured'): ?>
                <a href="index.php?page=integracoes-meta" class="btn btn-secondary">
                    ⚙️ Configurações
                </a>
                <a href="index.php?page=integracoes-meta-conectar" class="btn btn-primary">
                    🔗 Conectar
                </a>
            <?php else: ?>
                <a href="index.php?page=integracoes-meta" class="btn btn-primary">
                    ⚙️ Configurar
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Google Ads -->
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-icon" style="background: linear-gradient(135deg, #4285f4, #34a853);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="white">
                    <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/>
                </svg>
            </div>
            <div>
                <h3 class="integration-title">Google Ads</h3>
                <p class="integration-subtitle">Search & Display Ads</p>
            </div>
        </div>
        
        <div class="integration-body">
            <div class="integration-status disconnected">
                <span class="status-dot"></span>
                <span>Em breve</span>
            </div>
            <p class="integration-description">
                Integração com Google Ads em desenvolvimento.
            </p>
        </div>
        
        <div class="integration-footer">
            <button class="btn btn-secondary" disabled>
                Em breve
            </button>
        </div>
    </div>
    
    <!-- Kwai Ads -->
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-icon" style="background: linear-gradient(135deg, #ff6d00, #ff8c00);">
                <span style="font-size: 24px;">🎥</span>
            </div>
            <div>
                <h3 class="integration-title">Kwai Ads</h3>
                <p class="integration-subtitle">Short Video Ads</p>
            </div>
        </div>
        
        <div class="integration-body">
            <div class="integration-status disconnected">
                <span class="status-dot"></span>
                <span>Em breve</span>
            </div>
            <p class="integration-description">
                Integração com Kwai Ads em desenvolvimento.
            </p>
        </div>
        
        <div class="integration-footer">
            <button class="btn btn-secondary" disabled>
                Em breve
            </button>
        </div>
    </div>
    
</div>

<!-- Informações -->
<div class="card" style="margin-top: 30px; background: rgba(102, 126, 234, 0.05); border-color: rgba(102, 126, 234, 0.3);">
    <h3 style="color: #a5b4fc; margin-bottom: 15px; font-size: 18px;">📘 Como Funciona</h3>
    <div style="color: #94a3b8; line-height: 1.8;">
        <p style="margin-bottom: 15px;">
            <strong style="color: #e2e8f0;">1. Configure suas credenciais:</strong><br>
            Cada cliente deve criar seu próprio App no Meta Business e configurar as credenciais aqui.
        </p>
        <p style="margin-bottom: 15px;">
            <strong style="color: #e2e8f0;">2. Conecte suas contas:</strong><br>
            Autorize o acesso às suas contas de anúncio através do OAuth do Facebook.
        </p>
        <p>
            <strong style="color: #e2e8f0;">3. Sincronize automaticamente:</strong><br>
            O sistema importará suas campanhas e métricas automaticamente a cada hora.
        </p>
    </div>
</div>

<style>
.integration-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s;
}

.integration-card:hover {
    transform: translateY(-4px);
    border-color: #667eea;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
}

.integration-header {
    padding: 25px;
    border-bottom: 1px solid #334155;
    display: flex;
    align-items: center;
    gap: 15px;
}

.integration-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.integration-title {
    font-size: 20px;
    font-weight: 700;
    color: white;
    margin-bottom: 4px;
}

.integration-subtitle {
    font-size: 13px;
    color: #94a3b8;
}

.integration-body {
    padding: 25px;
    min-height: 140px;
}

.integration-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 15px;
}

.integration-status.connected {
    background: #10b98120;
    color: #10b981;
}

.integration-status.configured {
    background: #f59e0b20;
    color: #f59e0b;
}

.integration-status.disconnected {
    background: #64748b20;
    color: #94a3b8;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.integration-description {
    color: #94a3b8;
    line-height: 1.6;
    font-size: 14px;
}

.integration-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 18px;
    font-weight: 700;
    color: white;
}

.integration-footer {
    padding: 20px 25px;
    border-top: 1px solid #334155;
    display: flex;
    gap: 10px;
}

.integration-footer .btn {
    flex: 1;
}

.btn-secondary {
    background: #334155;
    color: white;
}

.btn-secondary:hover {
    background: #475569;
}

.btn-secondary:disabled {
    background: #1e293b;
    color: #64748b;
    cursor: not-allowed;
    opacity: 0.5;
}
</style>

<script>
// Sincronizar Meta
async function syncMeta() {
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
</script>