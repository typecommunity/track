<style>
/* Container Principal */
.config-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Header da Página */
.page-header {
    margin-bottom: 32px;
}

.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #334155;
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    margin-bottom: 24px;
}

.back-button:hover {
    background: #475569;
    transform: translateX(-4px);
}

.page-title {
    font-size: 32px;
    font-weight: 700;
    color: white;
    margin-bottom: 8px;
}

.page-subtitle {
    font-size: 16px;
    color: #94a3b8;
}

/* Grid Layout */
.config-grid {
    display: grid;
    grid-template-columns: 1fr 450px;
    gap: 32px;
    margin-bottom: 40px;
}

/* Cards */
.config-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 20px;
    padding: 32px;
    transition: all 0.3s;
}

.config-card:hover {
    border-color: #475569;
}

.card-title {
    font-size: 24px;
    font-weight: 700;
    color: white;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Alert Info */
.alert-info {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 32px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.alert-icon {
    font-size: 32px;
    flex-shrink: 0;
}

.alert-content h4 {
    color: #10b981;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 6px;
}

.alert-content p {
    color: #cbd5e1;
    font-size: 14px;
    line-height: 1.6;
}

/* Form */
.form-group {
    margin-bottom: 28px;
}

.form-label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    font-size: 15px;
    color: #e2e8f0;
}

.form-label .required {
    color: #ef4444;
    margin-left: 4px;
}

.form-input {
    width: 100%;
    padding: 14px 16px;
    background: #0f172a;
    border: 2px solid #334155;
    border-radius: 10px;
    color: #e2e8f0;
    font-size: 15px;
    font-family: 'Courier New', monospace;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    background: #1e293b;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-input::placeholder {
    color: #475569;
}

.form-help {
    display: block;
    margin-top: 8px;
    font-size: 13px;
    color: #64748b;
}

.form-help a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.form-help a:hover {
    color: #818cf8;
}

/* Buttons */
.btn-group {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 14px 24px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    text-decoration: none;
}

.btn-primary {
    flex: 1;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-secondary {
    background: #10b981;
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-secondary:hover {
    background: #059669;
    transform: translateY(-2px);
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn:disabled:hover {
    transform: none;
}

/* Status Card */
.status-card {
    margin-top: 32px;
    padding: 24px;
    border-radius: 16px;
    border: 2px solid;
}

.status-card.success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
    border-color: rgba(16, 185, 129, 0.3);
}

.status-card.configured {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
    border-color: rgba(245, 158, 11, 0.3);
}

.status-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.status-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-icon {
    font-size: 32px;
}

.status-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
}

.status-subtitle {
    font-size: 13px;
    color: #94a3b8;
}

.status-card.success .status-title {
    color: #10b981;
}

.status-card.configured .status-title {
    color: #f59e0b;
}

.status-description {
    color: #cbd5e1;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 20px;
}

/* Setup Guide */
.setup-guide {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.guide-step {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid #334155;
    border-radius: 12px;
    transition: all 0.2s;
}

.guide-step:hover {
    background: rgba(15, 23, 42, 0.9);
    border-color: #475569;
    transform: translateX(4px);
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.step-content {
    flex: 1;
}

.step-title {
    color: #e2e8f0;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 8px;
}

.step-description {
    color: #94a3b8;
    font-size: 14px;
    line-height: 1.6;
}

.step-description a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.step-description a:hover {
    color: #818cf8;
    text-decoration: underline;
}

/* URI Display */
.uri-display {
    background: #0f172a;
    border: 2px solid #334155;
    border-radius: 10px;
    padding: 16px;
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.uri-text {
    flex: 1;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: #10b981;
    word-break: break-all;
    line-height: 1.6;
}

.uri-copy-btn {
    padding: 10px 16px;
    background: #334155;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}

.uri-copy-btn:hover {
    background: #475569;
}

.uri-copy-btn.copied {
    background: #10b981;
}

/* Tip Box */
.tip-box {
    margin-top: 32px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.05));
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 12px;
}

.tip-box p {
    color: #cbd5e1;
    font-size: 14px;
    line-height: 1.7;
}

.tip-box strong {
    color: #a5b4fc;
}

/* Responsive */
@media (max-width: 1200px) {
    .config-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-primary {
        width: 100%;
    }
}
</style>

<div class="config-container">
    
    <!-- Header -->
    <div class="page-header">
        <a href="index.php?page=integracoes" class="back-button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Voltar para Integrações
        </a>
        
        <h1 class="page-title">⚙️ Configuração do Meta Ads</h1>
        <p class="page-subtitle">Configure suas credenciais do Meta Business para integração automática</p>
    </div>
    
    <!-- Grid Principal -->
    <div class="config-grid">
        
        <!-- Coluna Esquerda - Formulário -->
        <div>
            <div class="config-card">
                <h2 class="card-title">
                    🔑 Credenciais do App Meta
                </h2>
                
                <div class="alert-info">
                    <span class="alert-icon">💡</span>
                    <div class="alert-content">
                        <h4>Sistema SaaS - Privacidade Garantida</h4>
                        <p>
                            Cada cliente configura seu próprio App Meta. Suas credenciais são criptografadas 
                            e armazenadas com segurança apenas na sua conta.
                        </p>
                    </div>
                </div>
                
                <form id="metaConfigForm">
                    <div class="form-group">
                        <label class="form-label">
                            App ID do Meta
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="app_id" 
                            id="app_id"
                            class="form-input"
                            value="<?= htmlspecialchars($metaConfig['app_id'] ?? '') ?>"
                            placeholder="123456789012345"
                            required
                        >
                        <span class="form-help">
                            Encontre em 
                            <a href="https://developers.facebook.com/apps/" target="_blank">
                                Meta for Developers → Meus Apps → Configurações → Básico
                            </a>
                        </span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            App Secret do Meta
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            name="app_secret" 
                            id="app_secret"
                            class="form-input"
                            value="<?= htmlspecialchars($metaConfig['app_secret'] ?? '') ?>"
                            placeholder="********************************"
                            required
                        >
                        <span class="form-help">
                            Clique em "Mostrar" para visualizar o App Secret no Meta Developers
                        </span>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            Salvar Configurações
                        </button>
                        
                        <?php if ($metaConfig): ?>
                        <button type="button" onclick="testConnection()" class="btn btn-secondary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Testar Conexão
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Status Cards -->
            <?php if ($metaConfig && $metaConfig['status'] === 'configured'): ?>
            <div class="status-card configured">
                <div class="status-header">
                    <div class="status-info">
                        <span class="status-icon">⚙️</span>
                        <div>
                            <h3 class="status-title">Credenciais Configuradas</h3>
                            <p class="status-subtitle">Pronto para conectar contas</p>
                        </div>
                    </div>
                    <button onclick="removeIntegration()" class="btn btn-danger" style="padding: 10px 16px; font-size: 13px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Remover
                    </button>
                </div>
                <p class="status-description">
                    Suas credenciais estão salvas e validadas. Próximo passo: conectar suas contas 
                    de anúncio do Meta através do OAuth seguro.
                </p>
                <a href="index.php?page=integracoes-meta-conectar" class="btn btn-primary" style="text-decoration: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                    Conectar Contas Meta Ads
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($metaConfig && $metaConfig['status'] === 'connected'): ?>
            <div class="status-card success">
                <div class="status-header">
                    <div class="status-info">
                        <span class="status-icon">✅</span>
                        <div>
                            <h3 class="status-title">Integração Ativa e Sincronizada</h3>
                            <p class="status-subtitle">
                                Última sincronização: <?= date('d/m/Y \à\s H:i', strtotime($metaConfig['updated_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <p class="status-description">
                    Sua integração está funcionando perfeitamente! As campanhas e métricas são 
                    sincronizadas automaticamente a cada hora.
                </p>
                <div class="btn-group">
                    <a href="index.php?page=integracoes-meta-contas" class="btn btn-secondary" style="text-decoration: none;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m5.2-14.2l-4.2 4.2m0 6l4.2 4.2M23 12h-6m-6 0H1m14.2 5.2l-4.2-4.2m0-6l-4.2-4.2"/>
                        </svg>
                        Gerenciar Contas
                    </a>
                    <button onclick="syncNow()" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                        </svg>
                        Sincronizar Agora
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Coluna Direita - Guia -->
        <div>
            <div class="config-card">
                <h2 class="card-title">
                    📖 Guia Passo a Passo
                </h2>
                
                <div class="setup-guide">
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4 class="step-title">Acesse o Meta for Developers</h4>
                            <p class="step-description">
                                Vá para 
                                <a href="https://developers.facebook.com/apps/" target="_blank">
                                    developers.facebook.com/apps
                                </a>
                                e faça login
                            </p>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4 class="step-title">Crie um Novo App</h4>
                            <p class="step-description">
                                Clique em "Create App" → Selecione "Business" como tipo de app → 
                                Preencha nome e email
                            </p>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4 class="step-title">Selecione o Caso de Uso</h4>
                            <p class="step-description">
                                Em "Use cases", selecione 
                                <strong>"Create & manage app ads with Meta Ads Manager"</strong>
                            </p>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4 class="step-title">Configure o Redirect URI</h4>
                            <p class="step-description">
                                No menu <strong>Facebook Login → Settings</strong>, adicione este URI:
                            </p>
                            <div class="uri-display">
                                <div class="uri-text" id="redirectUri">
                                    <?php
                                    // Gera a URL correta do callback OAuth (está em /api, não em /public)
                                    // FIX: REQUEST_SCHEME pode não estar disponível
                                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                    $host = $_SERVER['HTTP_HOST'];
                                    
                                    // Remove /public do caminho e adiciona /api
                                    $basePath = str_replace('/public', '', dirname($_SERVER['SCRIPT_NAME']));
                                    $callbackUrl = $protocol . '://' . $host . $basePath . '/api/meta_oauth.php';
                                    
                                    echo $callbackUrl;
                                    ?>
                                </div>
                                <button onclick="copyRedirectUri()" class="uri-copy-btn" id="copyBtn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                    </svg>
                                    Copiar
                                </button>
                            </div>
                            <div style="margin-top: 12px; padding: 12px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px;">
                                <p style="color: #fca5a5; font-size: 13px; line-height: 1.6; margin: 0;">
                                    <strong>⚠️ Importante:</strong> Esta URL deve ser <strong>EXATAMENTE</strong> igual à cadastrada no Facebook. 
                                    Copie usando o botão "Copiar" para evitar erros de digitação.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h4 class="step-title">Copie as Credenciais</h4>
                            <p class="step-description">
                                Em <strong>App Settings → Basic</strong>, copie o <strong>App ID</strong> 
                                e o <strong>App Secret</strong> e cole no formulário ao lado
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="tip-box">
                    <p>
                        <strong>💡 Dica Importante:</strong> Mantenha seu app em "Development Mode" 
                        enquanto testa. Depois de validar, solicite a App Review do Meta para 
                        colocar em produção e acessar todas as funcionalidades.
                    </p>
                </div>
            </div>
        </div>
        
    </div>
    
</div>

<script>
// Salvar configurações
document.getElementById('metaConfigForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = e.target.querySelector('button[type="submit"]');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<svg style="width: 18px; height: 18px; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg> Salvando...';
    btn.disabled = true;
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('index.php?page=integracoes-meta-salvar', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Erro ao salvar: ' + error.message);
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }
});

// Testar conexão
async function testConnection() {
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<svg style="width: 18px; height: 18px; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg> Testando...';
    btn.disabled = true;
    
    try {
        const response = await fetch('index.php?page=integracoes-meta-testar', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', '✓ Conexão OK! Credenciais válidas.');
        } else {
            showNotification('error', '✗ ' + (result.message || 'Erro ao testar conexão'));
        }
    } catch (error) {
        showNotification('error', 'Erro ao testar: ' + error.message);
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }
}

// Copiar redirect URI
function copyRedirectUri() {
    const uri = document.getElementById('redirectUri').textContent.trim();
    const btn = document.getElementById('copyBtn');
    
    navigator.clipboard.writeText(uri).then(() => {
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Copiado!';
        btn.classList.add('copied');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('copied');
        }, 2000);
    }).catch(err => {
        showNotification('error', 'Erro ao copiar: ' + err.message);
    });
}

// Remover integração
async function removeIntegration() {
    if (!confirm('⚠️ Tem certeza que deseja remover a integração Meta Ads?\n\nTodas as contas conectadas serão desativadas e os dados de sincronização serão perdidos.')) {
        return;
    }
    
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<svg style="width: 16px; height: 16px; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>';
    btn.disabled = true;
    
    try {
        const response = await fetch('index.php?page=integracoes-meta-remover', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            setTimeout(() => window.location.href = 'index.php?page=integracoes', 1000);
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Erro ao remover: ' + error.message);
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }
}

// Sincronizar agora
async function syncNow() {
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<svg style="width: 18px; height: 18px; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg> Sincronizando...';
    btn.disabled = true;
    
    try {
        const response = await fetch('index.php?page=integracoes-meta-sync', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification('error', result.message);
        }
    } catch (error) {
        showNotification('error', 'Erro ao sincronizar: ' + error.message);
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
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
        z-index: 9999;
        animation: slideIn 0.3s ease;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        max-width: 400px;
    `;
    
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <span>${message}</span>
            </div>
        `;
    } else {
        notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <span>${message}</span>
            </div>
        `;
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Adicionar animações CSS
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