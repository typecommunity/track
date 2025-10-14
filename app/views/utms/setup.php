<!-- Header -->
<div style="text-align: center; max-width: 800px; margin: 0 auto 40px;">
    <h1 style="color: white; margin: 0 0 15px 0; font-size: 42px;">
        🚀 Bem-vindo ao UTMTrack!
    </h1>
    <p style="color: #94a3b8; font-size: 18px; line-height: 1.8;">
        Configure seu pixel do Facebook em menos de 2 minutos
    </p>
</div>

<!-- Se já tem pixels -->
<?php if (!empty($existingPixels)): ?>
<div style="max-width: 700px; margin: 0 auto 30px; background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 12px; padding: 20px;">
    <h3 style="color: #a5b4fc; margin: 0 0 15px 0;">ℹ️ Você já tem <?= count($existingPixels) ?> pixel(s) configurado(s)</h3>
    <p style="color: #94a3b8; margin: 0 0 15px 0;">
        Você pode adicionar um novo ou gerenciar os existentes:
    </p>
    <div style="display: flex; gap: 10px;">
        <a href="index.php?page=utms" style="background: #334155; color: white; text-decoration: none; display: inline-block; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
            Ver Dashboard
        </a>
        <a href="index.php?page=utms&action=settings" style="background: #334155; color: white; text-decoration: none; display: inline-block; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
            Gerenciar Pixels
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Formulário -->
<div style="max-width: 700px; margin: 0 auto 30px; background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden;">
    <div style="padding: 20px; border-bottom: 1px solid #334155;">
        <h2 style="color: white; margin: 0; font-size: 20px;">1️⃣ Configure seu Pixel do Facebook</h2>
    </div>
    
    <form id="setupForm" style="padding: 30px;">
        <!-- Pixel ID -->
        <div style="margin-bottom: 25px;">
            <label style="display: block; color: white; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                ID do Pixel do Facebook *
            </label>
            <input type="text" 
                   id="pixel_id" 
                   name="pixel_id" 
                   placeholder="Ex: 123456789012345"
                   required
                   style="width: 100%; padding: 12px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 14px; box-sizing: border-box;">
            <small style="color: #94a3b8; margin-top: 8px; display: block;">
                Encontre no 
                <a href="https://business.facebook.com/events_manager" target="_blank" style="color: #667eea; text-decoration: none;">
                    Gerenciador de Eventos
                </a> 
            </small>
        </div>
        
        <!-- Nome -->
        <div style="margin-bottom: 25px;">
            <label style="display: block; color: white; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                Nome do Pixel (opcional)
            </label>
            <input type="text" 
                   id="pixel_name" 
                   name="pixel_name" 
                   placeholder="Ex: Pixel Principal"
                   style="width: 100%; padding: 12px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 14px; box-sizing: border-box;">
        </div>
        
        <!-- Token -->
        <div style="margin-bottom: 25px;">
            <label style="display: block; color: white; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                Token de Acesso *
            </label>
            <input type="text" 
                   id="access_token" 
                   name="access_token" 
                   placeholder="Seu token de acesso"
                   required
                   style="width: 100%; padding: 12px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 14px; box-sizing: border-box;">
            <small style="color: #94a3b8; margin-top: 8px; display: block;">
                <a href="https://developers.facebook.com/tools/accesstoken/" target="_blank" style="color: #667eea; text-decoration: none;">
                    Obter token aqui
                </a>
            </small>
        </div>
        
        <!-- Aviso -->
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 15px; margin-bottom: 25px;">
            <p style="color: #fca5a5; margin: 0; font-size: 13px;">
                ⚠️ Use um token de longa duração para evitar expiração
            </p>
        </div>
        
        <!-- Botão Submit -->
        <button type="submit" 
                id="submitBtn"
                style="width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; transition: transform 0.2s;">
            🚀 Configurar e Continuar
        </button>
        
        <!-- Loading -->
        <div id="loading" style="display: none; text-align: center; margin-top: 15px; color: #94a3b8;">
            <p style="margin: 0;">⏳ Testando conexão com Facebook...</p>
        </div>
        
        <!-- Error -->
        <div id="errorDiv" style="display: none; margin-top: 15px; padding: 15px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px;">
            <p style="color: #fca5a5; margin: 0;" id="errorMsg"></p>
        </div>
        
        <!-- Success -->
        <div id="successDiv" style="display: none; margin-top: 15px; padding: 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px;">
            <p style="color: #10b981; margin: 0;">✅ Pixel configurado! Redirecionando...</p>
        </div>
    </form>
</div>

<!-- Ajuda -->
<div style="max-width: 700px; margin: 0 auto; background: rgba(102, 126, 234, 0.05); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 12px; padding: 20px;">
    <h3 style="color: #a5b4fc; margin: 0 0 15px 0;">💡 Onde Encontrar?</h3>
    
    <div style="margin-bottom: 15px;">
        <h4 style="color: white; margin: 0 0 8px 0; font-size: 15px;">🎯 ID do Pixel</h4>
        <ol style="color: #94a3b8; margin: 0; padding-left: 20px; line-height: 1.6; font-size: 14px;">
            <li>Acesse <a href="https://business.facebook.com/events_manager" target="_blank" style="color: #667eea;">Gerenciador de Eventos</a></li>
            <li>Selecione sua fonte de dados</li>
            <li>Copie o ID que aparece no topo</li>
        </ol>
    </div>
    
    <div>
        <h4 style="color: white; margin: 0 0 8px 0; font-size: 15px;">🔑 Token de Acesso</h4>
        <ol style="color: #94a3b8; margin: 0; padding-left: 20px; line-height: 1.6; font-size: 14px;">
            <li>Acesse <a href="https://developers.facebook.com/tools/accesstoken/" target="_blank" style="color: #667eea;">Graph API Explorer</a></li>
            <li>Selecione sua aplicação</li>
            <li>Copie o token gerado</li>
        </ol>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    console.log('Setup script loaded');
    
    const form = document.getElementById('setupForm');
    const submitBtn = document.getElementById('submitBtn');
    const loading = document.getElementById('loading');
    const errorDiv = document.getElementById('errorDiv');
    const errorMsg = document.getElementById('errorMsg');
    const successDiv = document.getElementById('successDiv');
    
    if (!form) {
        console.error('Form not found!');
        return;
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // Limpa mensagens
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        
        // Mostra loading
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.6';
        loading.style.display = 'block';
        
        // Coleta dados
        const pixelId = document.getElementById('pixel_id').value.trim();
        const pixelName = document.getElementById('pixel_name').value.trim();
        const accessToken = document.getElementById('access_token').value.trim();
        
        console.log('Dados:', { pixelId, pixelName, accessToken: accessToken.substring(0, 20) + '...' });
        
        // Cria FormData
        const formData = new FormData();
        formData.append('pixel_id', pixelId);
        formData.append('pixel_name', pixelName);
        formData.append('access_token', accessToken);
        
        // Envia
        fetch('index.php?page=utms&action=savePixel', {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(function(data) {
            console.log('Response data:', data);
            
            loading.style.display = 'none';
            
            if (data.success) {
                // Sucesso
                successDiv.style.display = 'block';
                
                // Redireciona após 2 segundos
                setTimeout(function() {
                    window.location.href = 'index.php?page=utms&action=scripts';
                }, 2000);
            } else {
                // Erro
                throw new Error(data.message || 'Erro desconhecido');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            
            loading.style.display = 'none';
            errorDiv.style.display = 'block';
            errorMsg.textContent = '❌ ' + error.message;
            
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
        });
    });
    
    // Hover effect no botão
    submitBtn.addEventListener('mouseenter', function() {
        if (!this.disabled) {
            this.style.transform = 'translateY(-2px)';
        }
    });
    
    submitBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
})();
</script>

<style>
/* Estilos para links */
a {
    color: #667eea;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

/* Focus nos inputs */
input:focus {
    outline: none;
    border-color: #667eea !important;
}

/* Placeholder */
input::placeholder {
    color: #64748b;
}
</style>