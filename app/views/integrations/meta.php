<!-- Botão Voltar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=integracoes" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar para Integrações
    </a>
</div>

<div style="display: grid; grid-template-columns: 1fr 400px; gap: 30px;">
    
    <!-- Formulário de Configuração -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">⚙️ Configurar Meta Ads</h2>
        </div>
        
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 15px; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 12px; color: #10b981;">
                <span style="font-size: 24px;">ℹ️</span>
                <div>
                    <strong>Sistema SaaS</strong><br>
                    <span style="font-size: 13px; color: #94a3b8;">
                        Cada cliente deve configurar seu próprio App Meta. Suas credenciais são privadas e seguras.
                    </span>
                </div>
            </div>
        </div>
        
        <form id="metaConfigForm">
            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    App ID do Meta *
                </label>
                <input 
                    type="text" 
                    name="app_id" 
                    id="app_id"
                    value="<?= htmlspecialchars($metaConfig['app_id'] ?? '') ?>"
                    placeholder="123456789012345"
                    required
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px; font-family: monospace;"
                >
                <small style="color: #64748b; font-size: 12px; display: block; margin-top: 6px;">
                    Encontre no painel do Meta for Developers
                </small>
            </div>
            
            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    App Secret do Meta *
                </label>
                <input 
                    type="password" 
                    name="app_secret" 
                    id="app_secret"
                    value="<?= htmlspecialchars($metaConfig['app_secret'] ?? '') ?>"
                    placeholder="********************************"
                    required
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px; font-family: monospace;"
                >
                <small style="color: #64748b; font-size: 12px; display: block; margin-top: 6px;">
                    Mantenha esta chave em segredo
                </small>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    💾 Salvar Configurações
                </button>
                
                <?php if ($metaConfig): ?>
                <button type="button" onclick="testConnection()" class="btn" style="background: #10b981; color: white;">
                    🧪 Testar Conexão
                </button>
                <?php endif; ?>
            </div>
        </form>
        
        <?php if ($metaConfig && $metaConfig['status'] === 'configured'): ?>
        <div style="margin-top: 30px; padding: 20px; background: #0f172a; border: 1px solid #334155; border-radius: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="color: #e2e8f0; font-size: 16px;">✓ Credenciais Configuradas</h3>
                <button onclick="removeIntegration()" class="btn" style="padding: 8px 16px; background: #ef4444; color: white; font-size: 13px;">
                    🗑️ Remover
                </button>
            </div>
            <p style="color: #94a3b8; font-size: 14px; margin-bottom: 20px;">
                Suas credenciais estão salvas. Próximo passo: conectar suas contas de anúncio.
            </p>
            <a href="index.php?page=integracoes-meta-conectar" class="btn btn-primary" style="display: inline-block; text-decoration: none;">
                🔗 Conectar Contas Meta Ads
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($metaConfig && $metaConfig['status'] === 'connected'): ?>
        <div style="margin-top: 30px; padding: 20px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <span style="font-size: 28px;">✓</span>
                <div>
                    <h3 style="color: #10b981; font-size: 16px; margin-bottom: 4px;">Integração Ativa</h3>
                    <p style="color: #94a3b8; font-size: 13px;">Última sincronização: <?= date('d/m/Y H:i', strtotime($metaConfig['updated_at'])) ?></p>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="index.php?page=integracoes-meta-contas" class="btn" style="flex: 1; background: #334155; color: white; text-decoration: none; text-align: center;">
                    ⚙️ Gerenciar Contas
                </a>
                <button onclick="syncNow()" class="btn btn-primary" style="flex: 1;">
                    🔄 Sincronizar Agora
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Guia Passo a Passo -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📖 Como Configurar</h2>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 25px;">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4 class="step-title">Acesse o Meta for Developers</h4>
                    <p class="step-description">
                        Vá para <a href="https://developers.facebook.com" target="_blank" style="color: #667eea;">developers.facebook.com</a>
                    </p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4 class="step-title">Crie um Novo App</h4>
                    <p class="step-description">
                        Clique em "Meus Apps" → "Criar App" → Tipo: "Business"
                    </p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4 class="step-title">Adicione o Marketing API</h4>
                    <p class="step-description">
                        No painel do app, adicione o produto "Marketing API"
                    </p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h4 class="step-title">Configure o Redirect URI</h4>
                    <p class="step-description">
                        Em "Configurações" → "Básico", adicione este URI:
                    </p>
                    <div style="background: #0f172a; padding: 12px; border-radius: 6px; margin-top: 8px; font-family: monospace; font-size: 11px; color: #10b981; word-break: break-all;">
                        <?= $config['base_url'] ?>/../api/meta_oauth.php
                    </div>
                    <button onclick="copyRedirectUri()" class="btn" style="width: 100%; margin-top: 8px; background: #334155; color: white; font-size: 12px; padding: 8px;">
                        📋 Copiar URI
                    </button>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">5</div>
                <div class="step-content">
                    <h4 class="step-title">Copie as Credenciais</h4>
                    <p class="step-description">
                        Copie o App ID e o App Secret e cole no formulário ao lado
                    </p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 8px;">
            <p style="color: #a5b4fc; font-size: 13px; line-height: 1.6;">
                <strong>💡 Dica:</strong> Coloque o app em modo de desenvolvimento inicialmente. Depois de testar, solicite a revisão do Meta para produção.
            </p>
        </div>
    </div>
    
</div>

<style>
.step {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}

.step-content {
    flex: 1;
}

.step-title {
    color: #e2e8f0;
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 6px;
}

.step-description {
    color: #94a3b8;
    font-size: 13px;
    line-height: 1.6;
}
</style>

<script>
// Salvar configurações
document.getElementById('metaConfigForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Salvando...';
    btn.disabled = true;
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('index.php?page=integracoes-meta-salvar', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            window.location.reload();
        } else {
            alert('✗ ' + result.message);
        }
    } catch (error) {
        alert('Erro ao salvar: ' + error.message);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

// Testar conexão
async function testConnection() {
    alert('⏳ Teste de conexão em desenvolvimento...');
}

// Copiar redirect URI
function copyRedirectUri() {
    const uri = '<?= $config['base_url'] ?>/../api/meta_oauth.php';
    navigator.clipboard.writeText(uri);
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '✓ Copiado!';
    btn.style.background = '#10b981';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = '';
    }, 2000);
}

// Remover integração
async function removeIntegration() {
    if (!confirm('Tem certeza que deseja remover a integração Meta Ads?\n\nTodas as contas conectadas serão desativadas.')) {
        return;
    }
    
    try {
        const response = await fetch('index.php?page=integracoes-meta-remover', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            window.location.href = 'index.php?page=integracoes';
        } else {
            alert('✗ ' + result.message);
        }
    } catch (error) {
        alert('Erro ao remover: ' + error.message);
    }
}

// Sincronizar agora
async function syncNow() {
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