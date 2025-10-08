<!-- Botão Voltar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=integracoes" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar
    </a>
</div>

<!-- Modal de Conexão -->
<div style="max-width: 600px; margin: 50px auto;">
    <div class="card">
        <div style="text-align: center; padding: 40px 30px;">
            <!-- Ícone -->
            <div style="margin-bottom: 25px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #1877f2, #0c63e4); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="white">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
            </div>
            
            <!-- Título -->
            <h1 style="color: white; font-size: 28px; font-weight: 700; margin-bottom: 12px;">
                🔗 Conectar Meta Ads
            </h1>
            <p style="color: #94a3b8; font-size: 16px; margin-bottom: 40px; line-height: 1.6;">
                Escolha como deseja conectar suas contas de anúncio
            </p>
            
            <!-- Opções -->
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <!-- Opção 1: Continuar neste navegador -->
                <a href="<?= htmlspecialchars($oauthUrl) ?>" class="connection-option" style="text-decoration: none;">
                    <div class="option-icon" style="background: #667eea;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3"/>
                        </svg>
                    </div>
                    <div class="option-content">
                        <h3 class="option-title">✅ Continuar neste navegador</h3>
                        <p class="option-description">
                            Recomendado para a maioria dos usuários
                        </p>
                    </div>
                    <div class="option-arrow">→</div>
                </a>
                
                <!-- Opção 2: Copiar link para multilogin -->
                <button onclick="copyMultiloginLink()" class="connection-option" style="border: none; cursor: pointer; text-align: left;">
                    <div class="option-icon" style="background: #10b981;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                    </div>
                    <div class="option-content">
                        <h3 class="option-title">📋 Copiar link para navegador multilogin</h3>
                        <p class="option-description">
                            Use se trabalha com múltiplas contas
                        </p>
                    </div>
                    <div class="option-arrow">📋</div>
                </button>
            </div>
            
            <!-- Informações de Permissões -->
            <div style="margin-top: 40px; padding: 20px; background: #0f172a; border: 1px solid #334155; border-radius: 12px; text-align: left;">
                <h4 style="color: #e2e8f0; font-size: 14px; font-weight: 600; margin-bottom: 12px;">
                    🔒 Permissões Solicitadas
                </h4>
                <ul style="color: #94a3b8; font-size: 13px; line-height: 2; list-style: none; padding: 0;">
                    <li>✓ Ver suas contas de anúncio</li>
                    <li>✓ Ver campanhas e estatísticas</li>
                    <li>✓ Gerenciar campanhas (se desejado)</li>
                </ul>
                <p style="color: #64748b; font-size: 12px; margin-top: 12px; line-height: 1.5;">
                    💡 Você pode revogar o acesso a qualquer momento através do Facebook.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Input escondido para copiar -->
<input type="hidden" id="multiloginUrl" value="<?= htmlspecialchars($oauthUrl) ?>">

<style>
.connection-option {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #1e293b;
    border: 2px solid #334155;
    border-radius: 12px;
    transition: all 0.3s;
    width: 100%;
}

.connection-option:hover {
    border-color: #667eea;
    background: #334155;
    transform: translateY(-2px);
}

.option-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.option-content {
    flex: 1;
    text-align: left;
}

.option-title {
    color: white;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
}

.option-description {
    color: #94a3b8;
    font-size: 13px;
}

.option-arrow {
    font-size: 24px;
    color: #667eea;
    flex-shrink: 0;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeIn 0.5s ease-out;
}
</style>

<script>
// Copiar link para multilogin
function copyMultiloginLink() {
    const url = document.getElementById('multiloginUrl').value;
    
    // Copia para área de transferência
    navigator.clipboard.writeText(url).then(() => {
        // Feedback visual
        const btn = event.target.closest('.connection-option');
        const originalBorder = btn.style.borderColor;
        const originalBg = btn.style.background;
        
        btn.style.borderColor = '#10b981';
        btn.style.background = 'rgba(16, 185, 129, 0.1)';
        
        // Mostra mensagem
        alert('✓ Link copiado para área de transferência!\n\nCole no seu navegador multilogin para conectar.');
        
        // Restaura estilo
        setTimeout(() => {
            btn.style.borderColor = originalBorder;
            btn.style.background = originalBg;
        }, 2000);
    }).catch(err => {
        alert('Erro ao copiar link: ' + err);
    });
}

// Mostrar modal de carregamento se clicar na opção 1
document.querySelector('a.connection-option').addEventListener('click', function(e) {
    // Mostra loading
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    overlay.innerHTML = `
        <div style="text-align: center; color: white;">
            <div style="width: 60px; height: 60px; border: 4px solid #334155; border-top-color: #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
            <p style="font-size: 18px; font-weight: 600;">Redirecionando para o Facebook...</p>
            <p style="font-size: 14px; color: #94a3b8; margin-top: 10px;">Aguarde um momento</p>
        </div>
    `;
    
    document.body.appendChild(overlay);
});
</script>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>