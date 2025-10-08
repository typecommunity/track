<!-- Estatísticas -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 30px;">
    <div class="metric-card">
        <div class="metric-label">
            🔗 Total de UTMs
        </div>
        <div class="metric-value">
            <?= number_format($stats['total_utms'] ?? 0, 0, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            👆 Total de Cliques
        </div>
        <div class="metric-value">
            <?= number_format($stats['total_clicks'] ?? 0, 0, ',', '.') ?>
        </div>
    </div>
</div>

<!-- Gerador de UTM -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">🎯 Gerador de UTM</h2>
    </div>
    
    <form id="utmForm" style="display: grid; gap: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    URL Base *
                </label>
                <input 
                    type="url" 
                    name="base_url" 
                    id="base_url"
                    placeholder="https://seusite.com/produto"
                    required
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Source (Fonte) *
                </label>
                <input 
                    type="text" 
                    name="utm_source" 
                    id="utm_source"
                    placeholder="facebook, google, instagram"
                    required
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Medium (Mídia) *
                </label>
                <input 
                    type="text" 
                    name="utm_medium" 
                    id="utm_medium"
                    placeholder="cpc, email, banner"
                    required
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Campaign (Campanha) *
                </label>
                <input 
                    type="text" 
                    name="utm_campaign" 
                    id="utm_campaign"
                    placeholder="black_friday_2025"
                    required
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Content (Conteúdo)
                </label>
                <input 
                    type="text" 
                    name="utm_content" 
                    id="utm_content"
                    placeholder="banner_azul, texto_1"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Term (Termo)
                </label>
                <input 
                    type="text" 
                    name="utm_term" 
                    id="utm_term"
                    placeholder="palavra-chave"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
        </div>
        
        <div style="display: flex; gap: 15px;">
            <button type="submit" class="btn btn-primary">
                🎯 Gerar UTM
            </button>
            <button type="button" onclick="copyGeneratedUrl()" class="btn" style="background: #334155; color: white;">
                📋 Copiar URL
            </button>
            <a href="index.php?page=utms-scripts" class="btn" style="background: #0f172a; color: white; text-decoration: none;">
                📜 Ver Scripts
            </a>
        </div>
    </form>
    
    <!-- URL Gerada -->
    <div id="generatedUrlBox" style="display: none; margin-top: 30px; padding: 20px; background: #0f172a; border: 2px solid #667eea; border-radius: 12px;">
        <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #e2e8f0;">
            ✅ URL Gerada com Sucesso!
        </label>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input 
                type="text" 
                id="generatedUrl" 
                readonly
                style="flex: 1; padding: 12px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #10b981; font-size: 13px; font-family: monospace;"
            >
            <button onclick="copyGeneratedUrl()" class="btn btn-primary" style="padding: 12px 20px;">
                📋 Copiar
            </button>
        </div>
    </div>
</div>

<!-- Lista de UTMs -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📋 Histórico de UTMs</h2>
        <div>
            <input 
                type="text" 
                id="searchUtm" 
                placeholder="Buscar UTM..."
                onkeyup="filterUtms()"
                style="padding: 10px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px; width: 300px;"
            >
        </div>
    </div>
    
    <?php if (empty($utms)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🔗</div>
        <div class="empty-state-title">Nenhuma UTM gerada ainda</div>
        <p>Crie sua primeira UTM usando o gerador acima</p>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table id="utmsTable">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Medium</th>
                    <th>Campaign</th>
                    <th>Content</th>
                    <th>Cliques</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utms as $utm): ?>
                <tr>
                    <td><?= htmlspecialchars($utm['utm_source'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($utm['utm_medium'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($utm['utm_campaign'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($utm['utm_content'] ?? '-') ?></td>
                    <td><?= number_format($utm['clicks'] ?? 0, 0, ',', '.') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($utm['created_at'])) ?></td>
                    <td>
                        <button 
                            onclick='copyUrl(<?= json_encode($utm['full_url']) ?>)' 
                            class="btn" 
                            style="padding: 6px 12px; font-size: 12px; background: #334155; color: white;"
                            title="Copiar URL"
                        >
                            📋
                        </button>
                        <button 
                            onclick='viewUrl(<?= json_encode($utm['full_url']) ?>)' 
                            class="btn" 
                            style="padding: 6px 12px; font-size: 12px; background: #667eea; color: white;"
                            title="Ver URL"
                        >
                            👁️
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
// Gerar UTM
document.getElementById('utmForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('index.php?page=utm-generate', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('generatedUrl').value = result.url;
            document.getElementById('generatedUrlBox').style.display = 'block';
            
            // Scroll suave até a URL gerada
            document.getElementById('generatedUrlBox').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Recarrega página após 2 segundos para mostrar na lista
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao gerar UTM: ' + error.message);
    }
});

// Copiar URL gerada
function copyGeneratedUrl() {
    const input = document.getElementById('generatedUrl');
    input.select();
    document.execCommand('copy');
    
    // Feedback visual
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '✓ Copiado!';
    btn.style.background = '#10b981';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = '';
    }, 2000);
}

// Copiar URL da tabela
function copyUrl(url) {
    const textarea = document.createElement('textarea');
    textarea.value = url;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    alert('✓ URL copiada para área de transferência!');
}

// Ver URL
function viewUrl(url) {
    prompt('URL completa:', url);
}

// Filtrar UTMs
function filterUtms() {
    const input = document.getElementById('searchUtm');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('utmsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}
</script>