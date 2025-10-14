<!-- Voltar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=utms" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar
    </a>
</div>

<h1 style="color: white; margin-bottom: 10px;">📜 Script de Instalação</h1>
<p style="color: #94a3b8; margin-bottom: 30px;">
    Copie e cole este script no <strong>&lt;head&gt;</strong> de todas as páginas do seu site. 
    O sistema vai rastrear automaticamente todos os eventos e enviar para o Facebook!
</p>

<!-- Script Principal -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">🚀 Script Principal (Copie Este!)</h2>
    </div>
    
    <p style="color: #94a3b8; margin: 0 0 20px 0; padding: 0 20px;">
        Este é o ÚNICO script que você precisa. Ele rastreia TUDO automaticamente:
        PageView, Lead, Checkout, Purchase e muito mais!
    </p>
    
    <div style="position: relative; padding: 0 20px 20px 20px;">
        <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; color: #e2e8f0; font-size: 13px; line-height: 1.8; overflow-x: auto; font-family: 'Courier New', monospace;" id="mainScript">&lt;!-- UTMTrack - Rastreamento Automático --&gt;
&lt;script 
  src="<?= $config['base_url'] ?>/js/capi-tracker.js"
  data-pixel-id="<?= $pixels[0]['pixel_id'] ?>"
  data-api-url="<?= $config['base_url'] ?>/api/capi-events.php"
  async defer&gt;
&lt;/script&gt;</pre>
        
        <button 
            onclick="copyScript('mainScript')" 
            class="btn btn-primary" 
            style="position: absolute; top: 15px; right: 35px;">
            📋 Copiar Script
        </button>
    </div>
</div>

<!-- Onde Instalar -->
<div class="card" style="margin-bottom: 30px; background: rgba(102, 126, 234, 0.05); border-color: rgba(102, 126, 234, 0.3);">
    <h3 style="color: #a5b4fc; margin: 0 0 15px 0;">📍 Onde Instalar</h3>
    
    <p style="color: #94a3b8; line-height: 1.8;">
        Cole o script dentro da tag <code style="background: #1e293b; padding: 2px 8px; border-radius: 4px; color: #10b981;">&lt;head&gt;</code> 
        de <strong style="color: white;">TODAS as páginas</strong> do seu site:
    </p>
    
    <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; color: #e2e8f0; font-size: 13px; line-height: 1.8; overflow-x: auto; font-family: 'Courier New', monospace; margin: 15px 0;">&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;title&gt;Minha Página&lt;/title&gt;
    
    <span style="background: rgba(102, 126, 234, 0.2); padding: 2px 4px; border-radius: 4px;">&lt;!-- COLE O SCRIPT AQUI ⬇️ --&gt;</span>
    &lt;!-- UTMTrack - Rastreamento Automático --&gt;
    &lt;script 
      src="<?= $config['base_url'] ?>/js/capi-tracker.js"
      data-pixel-id="<?= $pixels[0]['pixel_id'] ?>"
      data-api-url="<?= $config['base_url'] ?>/api/capi-events.php"
      async defer&gt;
    &lt;/script&gt;
    <span style="background: rgba(102, 126, 234, 0.2); padding: 2px 4px; border-radius: 4px;">&lt;!-- ATÉ AQUI ⬆️ --&gt;</span>
    
&lt;/head&gt;
&lt;body&gt;
    &lt;!-- Seu conteúdo aqui --&gt;
&lt;/body&gt;
&lt;/html&gt;</pre>
</div>

<!-- O Que o Script Rastreia -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">✅ O Que o Script Rastreia Automaticamente</h2>
    </div>
    
    <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div style="display: flex; align-items: start; gap: 12px;">
                <div style="font-size: 24px;">👁️</div>
                <div>
                    <strong style="color: white; display: block; margin-bottom: 4px;">PageView</strong>
                    <span style="color: #94a3b8; font-size: 14px;">Toda visualização de página</span>
                </div>
            </div>
            
            <div style="display: flex; align-items: start; gap: 12px;">
                <div style="font-size: 24px;">📧</div>
                <div>
                    <strong style="color: white; display: block; margin-bottom: 4px;">Lead</strong>
                    <span style="color: #94a3b8; font-size: 14px;">Captura de email em formulários</span>
                </div>
            </div>
            
            <div style="display: flex; align-items: start; gap: 12px;">
                <div style="font-size: 24px;">🛒</div>
                <div>
                    <strong style="color: white; display: block; margin-bottom: 4px;">InitiateCheckout</strong>
                    <span style="color: #94a3b8; font-size: 14px;">Início do processo de compra</span>
                </div>
            </div>
            
            <div style="display: flex; align-items: start; gap: 12px;">
                <div style="font-size: 24px;">💰</div>
                <div>
                    <strong style="color: white; display: block; margin-bottom: 4px;">Purchase</strong>
                    <span style="color: #94a3b8; font-size: 14px;">Compra finalizada</span>
                </div>
            </div>
            
            <div style="display: flex; align-items: start; gap: 12px;">
                <div style="font-size: 24px;">🎯</div>
                <div>
                    <strong style="color: white; display: block; margin-bottom: 4px;">ViewContent</strong>
                    <span style="color: #94a3b8; font-size: 14px;">Visualização de conteúdo</span>
                </div>
            </div>
            
            <div style="display: flex; align-items: start; gap: 12px;">
                <div style="font-size: 24px;">➕</div>
                <div>
                    <strong style="color: white; display: block; margin-bottom: 4px;">AddToCart</strong>
                    <span style="color: #94a3b8; font-size: 14px;">Adicionar ao carrinho</span>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; border-radius: 4px;">
            <p style="color: #10b981; margin: 0; font-weight: 600;">
                ✨ Tudo 100% automático! Você não precisa configurar NADA!
            </p>
        </div>
    </div>
</div>

<!-- Eventos Customizados -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">🎨 Eventos Customizados (Opcional)</h2>
    </div>
    
    <p style="color: #94a3b8; margin: 0 0 20px 0; padding: 0 20px;">
        Se quiser rastrear eventos específicos, use estas funções JavaScript:
    </p>
    
    <div style="padding: 0 20px 20px 20px;">
        <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; color: #e2e8f0; font-size: 13px; line-height: 1.8; overflow-x: auto; font-family: 'Courier New', monospace;">&lt;script&gt;
// 💰 Rastrear compra na página de obrigado
window.utmtrackCapi.trackPurchase(197.00, 'BRL', 'TRANS_123');

// 📧 Rastrear lead
window.utmtrackCapi.trackLead();

// 🛒 Rastrear início de checkout
window.utmtrackCapi.trackInitiateCheckout(197.00, 'BRL');

// ➕ Rastrear adicionar ao carrinho
window.utmtrackCapi.trackAddToCart(97.00, 'BRL', 'Produto X');

// 🎯 Evento customizado
window.utmtrackCapi.track('MeuEvento', {
    value: 100,
    currency: 'BRL',
    content_name: 'Nome do Produto'
});
&lt;/script&gt;</pre>
    </div>
</div>

<!-- Exemplo Prático -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">💡 Exemplo: Página de Obrigado</h2>
    </div>
    
    <p style="color: #94a3b8; margin: 0 0 20px 0; padding: 0 20px;">
        Na sua página de "obrigado" (após a compra), adicione:
    </p>
    
    <div style="padding: 0 20px 20px 20px;">
        <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; color: #e2e8f0; font-size: 13px; line-height: 1.8; overflow-x: auto; font-family: 'Courier New', monospace;">&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;Compra Finalizada - Obrigado!&lt;/title&gt;
    
    &lt;!-- Script UTMTrack (mesmo de sempre) --&gt;
    &lt;script 
      src="<?= $config['base_url'] ?>/js/capi-tracker.js"
      data-pixel-id="<?= $pixels[0]['pixel_id'] ?>"
      data-api-url="<?= $config['base_url'] ?>/api/capi-events.php"
      async defer&gt;
    &lt;/script&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1&gt;✅ Compra Finalizada!&lt;/h1&gt;
    
    &lt;script&gt;
    // Envia evento de Purchase
    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const transactionId = urlParams.get('transaction_id');
        const value = parseFloat(urlParams.get('value')) || 197.00;
        
        // Rastreia a compra
        window.utmtrackCapi.trackPurchase(value, 'BRL', transactionId);
    });
    &lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;</pre>
    </div>
</div>

<!-- Modo Debug -->
<div class="card" style="margin-bottom: 30px; background: rgba(139, 92, 246, 0.05); border-color: rgba(139, 92, 246, 0.3);">
    <h3 style="color: #c4b5fd; margin: 0 0 15px 0;">🐛 Modo Debug (Desenvolvimento)</h3>
    
    <p style="color: #94a3b8; margin: 0 0 15px 0;">
        Para ver logs no console durante testes, adicione <code style="background: #1e293b; padding: 2px 8px; border-radius: 4px;">data-debug</code>:
    </p>
    
    <pre style="background: #0f172a; border: 1px solid #8b5cf6; border-radius: 8px; padding: 20px; color: #e2e8f0; font-size: 13px; line-height: 1.8; overflow-x: auto; font-family: 'Courier New', monospace;">&lt;script 
  src="<?= $config['base_url'] ?>/js/capi-tracker.js"
  data-pixel-id="<?= $pixels[0]['pixel_id'] ?>"
  data-api-url="<?= $config['base_url'] ?>/api/capi-events.php"
  <span style="background: rgba(139, 92, 246, 0.3); padding: 2px 4px; border-radius: 4px;">data-debug</span>
  async defer&gt;
&lt;/script&gt;</pre>

    <p style="color: #94a3b8; margin: 15px 0 0 0;">
        <strong>⚠️ Importante:</strong> Remova <code style="background: #1e293b; padding: 2px 8px; border-radius: 4px;">data-debug</code> 
        ao colocar em produção!
    </p>
</div>

<!-- Checklist -->
<div class="card" style="background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.3);">
    <h3 style="color: #6ee7b7; margin: 0 0 20px 0;">✅ Checklist de Instalação</h3>
    
    <div style="display: grid; gap: 12px;">
        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; color: #94a3b8;">
            <input type="checkbox" style="margin-top: 4px; width: 18px; height: 18px;">
            <span>Copiei o script principal</span>
        </label>
        
        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; color: #94a3b8;">
            <input type="checkbox" style="margin-top: 4px; width: 18px; height: 18px;">
            <span>Colei o script no &lt;head&gt; de TODAS as páginas</span>
        </label>
        
        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; color: #94a3b8;">
            <input type="checkbox" style="margin-top: 4px; width: 18px; height: 18px;">
            <span>Testei o script abrindo o console (F12) com data-debug</span>
        </label>
        
        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; color: #94a3b8;">
            <input type="checkbox" style="margin-top: 4px; width: 18px; height: 18px;">
            <span>Verifiquei eventos no Test Events do Facebook</span>
        </label>
        
        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; color: #94a3b8;">
            <input type="checkbox" style="margin-top: 4px; width: 18px; height: 18px;">
            <span>Adicionei Purchase na página de obrigado</span>
        </label>
        
        <label style="display: flex; align-items: start; gap: 12px; cursor: pointer; color: #94a3b8;">
            <input type="checkbox" style="margin-top: 4px; width: 18px; height: 18px;">
            <span>Removi data-debug antes de colocar em produção</span>
        </label>
    </div>
    
    <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid rgba(16, 185, 129, 0.2);">
        <p style="color: #10b981; margin: 0; font-weight: 600; font-size: 16px;">
            🎉 Pronto! Aguarde 24-48h para ver os primeiros resultados no Facebook!
        </p>
    </div>
</div>

<!-- Links Úteis -->
<div style="margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap;">
    <a href="https://developers.facebook.com/docs/marketing-api/conversions-api" target="_blank" 
       class="btn" style="background: #334155; color: white; text-decoration: none;">
        📚 Documentação Facebook CAPI
    </a>
    
    <a href="https://business.facebook.com/events_manager" target="_blank" 
       class="btn" style="background: #334155; color: white; text-decoration: none;">
        🎯 Gerenciador de Eventos
    </a>
    
    <a href="index.php?page=utms" 
       class="btn btn-primary" style="text-decoration: none;">
        📊 Ver Dashboard
    </a>
</div>

<script>
function copyScript(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    // Feedback visual
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '✓ Copiado!';
    button.style.background = '#10b981';
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.style.background = '';
    }, 2000);
}
</script>