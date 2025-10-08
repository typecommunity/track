<!-- Scripts de Rastreamento -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">📜 Script de Rastreamento UTM</h2>
    </div>
    
    <p style="color: #94a3b8; margin-bottom: 20px; line-height: 1.6;">
        Adicione este script nas suas páginas (landing pages, checkout, obrigado) para capturar os parâmetros UTM automaticamente.
    </p>
    
    <div style="position: relative;">
        <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; color: #e2e8f0; font-size: 13px; line-height: 1.6; overflow-x: auto; font-family: 'Courier New', monospace;"><code>&lt;!-- UTMTrack - Script de Rastreamento --&gt;
&lt;script&gt;
(function() {
    // Captura parâmetros UTM da URL
    function getUTMParams() {
        const params = new URLSearchParams(window.location.search);
        const utmParams = {
            utm_source: params.get('utm_source'),
            utm_medium: params.get('utm_medium'),
            utm_campaign: params.get('utm_campaign'),
            utm_content: params.get('utm_content'),
            utm_term: params.get('utm_term')
        };
        
        // Remove parâmetros vazios
        Object.keys(utmParams).forEach(key => {
            if (!utmParams[key]) delete utmParams[key];
        });
        
        return utmParams;
    }
    
    // Salva UTMs no localStorage
    function saveUTMs(utms) {
        if (Object.keys(utms).length > 0) {
            localStorage.setItem('utmtrack_params', JSON.stringify(utms));
            localStorage.setItem('utmtrack_timestamp', Date.now());
        }
    }
    
    // Recupera UTMs salvos
    function getSavedUTMs() {
        const saved = localStorage.getItem('utmtrack_params');
        const timestamp = localStorage.getItem('utmtrack_timestamp');
        
        // Expira após 30 dias
        if (saved && timestamp && (Date.now() - timestamp < 30 * 24 * 60 * 60 * 1000)) {
            return JSON.parse(saved);
        }
        return null;
    }
    
    // Envia evento para o servidor
    function trackEvent(eventType, data) {
        const utms = getSavedUTMs() || getUTMParams();
        
        fetch('<?= $config['base_url'] ?>/../api/events.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: '<?= $user_id ?>',
                event_type: eventType,
                utm_params: utms,
                page_url: window.location.href,
                referrer: document.referrer,
                data: data
            })
        });
    }
    
    // Inicialização
    const utms = getUTMParams();
    if (Object.keys(utms).length > 0) {
        saveUTMs(utms);
    }
    
    // Rastreia visualização de página
    trackEvent('page_view', {
        title: document.title,
        path: window.location.pathname
    });
    
    // Expõe função global para rastreamento manual
    window.utmtrack = {
        trackEvent: trackEvent,
        getUTMs: getSavedUTMs
    };
})();
&lt;/script&gt;</code></pre>
        
        <button 
            onclick="copyScript('script1')" 
            class="btn btn-primary" 
            style="position: absolute; top: 15px; right: 15px;"
        >
            📋 Copiar Script
        </button>
    </div>
</div>

<!-- Script de Eventos do Funil -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">🎯 Rastreamento de Eventos</h2>
    </div>
    
    <p style="color: #94a3b8; margin-bottom: 20px; line-height: 1.6;">
        Use estas funções para rastrear eventos importantes no funil de vendas.
    </p>
    
    <div style="position: relative;">
        <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; color: #e2e8f0; font-size: 13px; line-height: 1.6; overflow-x: auto; font-family: 'Courier New', monospace;"><code>&lt;!-- Rastrear Iniciou Checkout --&gt;
&lt;script&gt;
// Quando o usuário clicar no botão "Comprar"
document.getElementById('btnComprar').addEventListener('click', function() {
    utmtrack.trackEvent('initiate_checkout', {
        product: 'Nome do Produto',
        value: 197.00
    });
});
&lt;/script&gt;

&lt;!-- Rastrear Compra Finalizada --&gt;
&lt;script&gt;
// Na página de obrigado/confirmação
utmtrack.trackEvent('purchase', {
    transaction_id: '<?= $_GET['transaction_id'] ?? '' ?>',
    product: 'Nome do Produto',
    value: 197.00,
    payment_method: 'pix'
});
&lt;/script&gt;

&lt;!-- Rastrear Lead --&gt;
&lt;script&gt;
// Quando capturar email
document.getElementById('formEmail').addEventListener('submit', function(e) {
    utmtrack.trackEvent('lead', {
        email: document.getElementById('email').value
    });
});
&lt;/script&gt;</code></pre>
        
        <button 
            onclick="copyScript('script2')" 
            class="btn btn-primary" 
            style="position: absolute; top: 15px; right: 15px;"
        >
            📋 Copiar Exemplos
        </button>
    </div>
</div>

<!-- Códigos de UTM para Plataformas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
    <!-- Facebook/Meta -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📱 Meta Ads (Facebook/Instagram)</h3>
        </div>
        
        <p style="color: #94a3b8; font-size: 14px; margin-bottom: 15px;">
            Use este padrão nas suas URLs de anúncios:
        </p>
        
        <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 15px; color: #10b981; font-size: 12px; overflow-x: auto; font-family: monospace;">?utm_source=facebook
&utm_medium=cpc
&utm_campaign={{campaign.name}}
&utm_content={{adset.name}}
&utm_term={{ad.name}}</pre>
        
        <button onclick="copyText('facebook_utm')" class="btn" style="width: 100%; margin-top: 15px; background: #334155; color: white;">
            📋 Copiar Padrão Meta
        </button>
    </div>
    
    <!-- Google Ads -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🔍 Google Ads</h3>
        </div>
        
        <p style="color: #94a3b8; font-size: 14px; margin-bottom: 15px;">
            Use este padrão nas suas URLs de anúncios:
        </p>
        
        <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 15px; color: #10b981; font-size: 12px; overflow-x: auto; font-family: monospace;">?utm_source=google
&utm_medium=cpc
&utm_campaign={campaign}
&utm_content={adgroup}
&utm_term={keyword}</pre>
        
        <button onclick="copyText('google_utm')" class="btn" style="width: 100%; margin-top: 15px; background: #334155; color: white;">
            📋 Copiar Padrão Google
        </button>
    </div>
    
    <!-- Kwai -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🎥 Kwai Ads</h3>
        </div>
        
        <p style="color: #94a3b8; font-size: 14px; margin-bottom: 15px;">
            Use este padrão nas suas URLs de anúncios:
        </p>
        
        <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 15px; color: #10b981; font-size: 12px; overflow-x: auto; font-family: monospace;">?utm_source=kwai
&utm_medium=cpc
&utm_campaign=__CAMPAIGN_NAME__
&utm_content=__ADGROUP_NAME__
&utm_term=__CREATIVE_ID__</pre>
        
        <button onclick="copyText('kwai_utm')" class="btn" style="width: 100%; margin-top: 15px; background: #334155; color: white;">
            📋 Copiar Padrão Kwai
        </button>
    </div>
</div>

<!-- Instruções -->
<div class="card" style="margin-top: 30px; background: rgba(102, 126, 234, 0.05); border-color: rgba(102, 126, 234, 0.3);">
    <h3 style="color: #a5b4fc; margin-bottom: 15px;">💡 Como Usar</h3>
    <ol style="color: #94a3b8; line-height: 2; padding-left: 25px;">
        <li>Copie o <strong>Script de Rastreamento</strong> e adicione em todas as páginas do seu funil</li>
        <li>Use o <strong>Gerador de UTM</strong> para criar suas URLs de campanha</li>
        <li>Adicione os <strong>padrões de UTM</strong> nas configurações das suas plataformas de anúncios</li>
        <li>Implemente o <strong>rastreamento de eventos</strong> nas páginas de conversão</li>
        <li>Acompanhe os resultados no <strong>Dashboard</strong></li>
    </ol>
</div>

<script>
function copyScript(id) {
    const pre = event.target.closest('.card').querySelector('pre');
    const text = pre.textContent;
    
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '✓ Copiado!';
    btn.style.background = '#10b981';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = '';
    }, 2000);
}

function copyText(type) {
    let text = '';
    
    if (type === 'facebook_utm') {
        text = '?utm_source=facebook&utm_medium=cpc&utm_campaign={{campaign.name}}&utm_content={{adset.name}}&utm_term={{ad.name}}';
    } else if (type === 'google_utm') {
        text = '?utm_source=google&utm_medium=cpc&utm_campaign={campaign}&utm_content={adgroup}&utm_term={keyword}';
    } else if (type === 'kwai_utm') {
        text = '?utm_source=kwai&utm_medium=cpc&utm_campaign=__CAMPAIGN_NAME__&utm_content=__ADGROUP_NAME__&utm_term=__CREATIVE_ID__';
    }
    
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '✓ Copiado!';
    btn.style.background = '#10b981';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = '';
    }, 2000);
}
</script>