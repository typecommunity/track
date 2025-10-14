<!-- Voltar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=dashboard" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar
    </a>
</div>

<h1 style="color: white; margin-bottom: 10px;">🚀 Facebook CAPI (Conversions API)</h1>
<p style="color: #94a3b8; margin-bottom: 30px;">
    Configure e gerencie seus pixels do Facebook com CAPI automatizado. O sistema envia eventos automaticamente para o Facebook, 
    aumentando a precisão do rastreamento e contornando bloqueadores de anúncios.
</p>

<!-- Estatísticas -->
<?php if ($stats && $stats['total_events'] > 0): ?>
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
    <div class="metric-card">
        <div class="metric-label">📊 Total de Eventos</div>
        <div class="metric-value"><?= number_format($stats['total_events'] ?? 0, 0, ',', '.') ?></div>
        <div class="metric-info">Últimos 30 dias</div>
    </div>
    
    <div class="metric-card" style="border-color: #10b981;">
        <div class="metric-label">✅ Enviados</div>
        <div class="metric-value" style="color: #10b981;"><?= number_format($stats['events_sent'] ?? 0, 0, ',', '.') ?></div>
        <div class="metric-info">
            <?php 
            $successRate = $stats['total_events'] > 0 ? ($stats['events_sent'] / $stats['total_events']) * 100 : 0;
            echo number_format($successRate, 1) . '% de sucesso';
            ?>
        </div>
    </div>
    
    <?php if ($stats['events_failed'] > 0): ?>
    <div class="metric-card" style="border-color: #ef4444;">
        <div class="metric-label">❌ Falhas</div>
        <div class="metric-value" style="color: #ef4444;"><?= number_format($stats['events_failed'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <?php endif; ?>
    
    <?php if ($stats['events_pending'] > 0): ?>
    <div class="metric-card" style="border-color: #f59e0b;">
        <div class="metric-label">⏳ Pendentes</div>
        <div class="metric-value" style="color: #f59e0b;"><?= number_format($stats['events_pending'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Botão Adicionar Pixel -->
<div style="margin-bottom: 20px;">
    <button onclick="openPixelModal()" class="btn btn-primary">
        ➕ Adicionar Pixel do Facebook
    </button>
    
    <a href="index.php?page=capi&action=stats" class="btn" style="background: #334155; color: white; text-decoration: none;">
        📊 Ver Estatísticas Detalhadas
    </a>
    
    <a href="index.php?page=capi&action=logs" class="btn" style="background: #334155; color: white; text-decoration: none;">
        📋 Ver Logs
    </a>
</div>

<!-- Lista de Pixels -->
<?php if (empty($pixels)): ?>
<div class="card">
    <div class="empty-state">
        <div class="empty-state-icon">🎯</div>
        <div class="empty-state-title">Nenhum pixel configurado</div>
        <p>Adicione seu primeiro pixel do Facebook para começar a usar o CAPI</p>
        <button onclick="openPixelModal()" class="btn btn-primary" style="margin-top: 15px;">
            Adicionar Pixel
        </button>
    </div>
</div>
<?php else: ?>

<?php foreach ($pixels as $pixel): ?>
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
        <div>
            <h3 style="color: white; margin: 0 0 5px 0;">
                <?= htmlspecialchars($pixel['pixel_name']) ?>
            </h3>
            <p style="color: #94a3b8; margin: 0; font-size: 14px;">
                ID: <code style="background: #1e293b; padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($pixel['pixel_id']) ?></code>
            </p>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <span class="badge" style="background: <?= $pixel['capi_enabled'] ? '#10b981' : '#ef4444' ?>;">
                <?= $pixel['capi_enabled'] ? '✓ CAPI Ativo' : '✗ CAPI Inativo' ?>
            </span>
            <span class="badge" style="background: <?= $pixel['status'] === 'active' ? '#667eea' : '#6b7280' ?>;">
                <?= $pixel['status'] === 'active' ? 'Ativo' : 'Inativo' ?>
            </span>
        </div>
    </div>
    
    <!-- Configurações do Pixel -->
    <?php 
    $config = $capiConfigs[$pixel['id']] ?? null;
    if ($config): 
        $autoEvents = $config['auto_events'] ?? [];
    ?>
    <div style="background: #1e293b; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
        <h4 style="color: white; margin: 0 0 10px 0; font-size: 14px;">⚙️ Eventos Automáticos</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
            <?php foreach ($autoEvents as $eventName => $enabled): ?>
            <label style="display: flex; align-items: center; gap: 8px; color: #94a3b8; cursor: pointer;">
                <input type="checkbox" 
                       <?= $enabled ? 'checked' : '' ?>
                       onchange="toggleEvent('<?= $pixel['id'] ?>', '<?= $eventName ?>', this.checked)"
                       style="width: 16px; height: 16px;">
                <span style="font-size: 13px;"><?= $eventName ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Ações -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button onclick="showScript('<?= $pixel['pixel_id'] ?>')" class="btn btn-primary">
            📜 Ver Script de Instalação
        </button>
        
        <button onclick="editPixel(<?= htmlspecialchars(json_encode($pixel)) ?>)" class="btn" style="background: #334155; color: white;">
            ✏️ Editar
        </button>
        
        <button onclick="testPixel('<?= $pixel['id'] ?>')" class="btn" style="background: #334155; color: white;">
            🧪 Testar Conexão
        </button>
        
        <button onclick="deletePixel('<?= $pixel['id'] ?>', '<?= htmlspecialchars($pixel['pixel_name']) ?>')" class="btn" style="background: #ef4444; color: white;">
            🗑️ Deletar
        </button>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Modal para Adicionar/Editar Pixel -->
<div id="pixelModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Adicionar Pixel do Facebook</h3>
            <span class="modal-close" onclick="closePixelModal()">&times;</span>
        </div>
        
        <form id="pixelForm" onsubmit="savePixel(event)">
            <input type="hidden" id="pixel_db_id" name="pixel_db_id">
            
            <div class="form-group">
                <label>ID do Pixel do Facebook *</label>
                <input type="text" id="pixel_id" name="pixel_id" required 
                       placeholder="Ex: 123456789012345">
                <small style="color: #94a3b8;">Encontre no Gerenciador de Eventos do Facebook</small>
            </div>
            
            <div class="form-group">
                <label>Nome do Pixel *</label>
                <input type="text" id="pixel_name" name="pixel_name" required 
                       placeholder="Ex: Pixel Principal">
            </div>
            
            <div class="form-group">
                <label>Token de Acesso *</label>
                <input type="text" id="access_token" name="access_token" required 
                       placeholder="Seu token de acesso do Facebook">
                <small style="color: #94a3b8;">
                    <a href="https://developers.facebook.com/tools/accesstoken/" target="_blank" style="color: #667eea;">
                        Obtenha seu token aqui
                    </a>
                </small>
            </div>
            
            <div class="form-group">
                <label>Código de Teste (opcional)</label>
                <input type="text" id="test_event_code" name="test_event_code" 
                       placeholder="TEST12345">
                <small style="color: #94a3b8;">Use apenas em ambiente de testes</small>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closePixelModal()" class="btn" style="background: #334155; color: white;">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Salvar Pixel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para mostrar Script -->
<div id="scriptModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3>Script de Instalação CAPI</h3>
            <span class="modal-close" onclick="closeScriptModal()">&times;</span>
        </div>
        
        <p style="color: #94a3b8; margin-bottom: 20px;">
            Copie e cole este código no <strong>&lt;head&gt;</strong> de todas as páginas do seu site:
        </p>
        
        <div style="position: relative;">
            <pre style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; color: #e2e8f0; font-size: 13px; line-height: 1.6; overflow-x: auto; font-family: 'Courier New', monospace;" id="scriptCode"></pre>
            
            <button onclick="copyScript()" class="btn btn-primary" style="position: absolute; top: 15px; right: 15px;">
                📋 Copiar Script
            </button>
        </div>
        
        <div style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 8px; padding: 15px; margin-top: 20px;">
            <h4 style="color: #a5b4fc; margin: 0 0 10px 0;">💡 Como usar</h4>
            <ol style="color: #94a3b8; line-height: 1.8; margin: 0; padding-left: 20px;">
                <li>Copie o script acima</li>
                <li>Cole dentro da tag <code>&lt;head&gt;</code> do seu site</li>
                <li>O script irá rastrear automaticamente: PageView, Lead, InitiateCheckout, Purchase</li>
                <li>Para eventos customizados, use: <code>window.utmtrackCapi.track('EventName', {value: 100})</code></li>
            </ol>
        </div>
        
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 15px; margin-top: 15px;">
            <h4 style="color: #fca5a5; margin: 0 0 10px 0;">⚠️ Importante</h4>
            <ul style="color: #94a3b8; line-height: 1.8; margin: 0; padding-left: 20px;">
                <li>Certifique-se de que o script está em TODAS as páginas</li>
                <li>Teste usando o Test Events do Facebook</li>
                <li>Aguarde até 48h para ver resultados no Facebook</li>
            </ul>
        </div>
    </div>
</div>

<script>
// Abrir modal de pixel
function openPixelModal() {
    document.getElementById('pixelModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Adicionar Pixel do Facebook';
    document.getElementById('pixelForm').reset();
    document.getElementById('pixel_db_id').value = '';
}

// Fechar modal de pixel
function closePixelModal() {
    document.getElementById('pixelModal').style.display = 'none';
}

// Editar pixel
function editPixel(pixel) {
    document.getElementById('pixelModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Editar Pixel';
    document.getElementById('pixel_db_id').value = pixel.id;
    document.getElementById('pixel_id').value = pixel.pixel_id;
    document.getElementById('pixel_name').value = pixel.pixel_name;
    document.getElementById('access_token').value = pixel.access_token || '';
    document.getElementById('test_event_code').value = pixel.test_event_code || '';
}

// Salvar pixel
async function savePixel(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('index.php?page=capi&action=savePixel', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Pixel salvo com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao salvar pixel: ' + error.message);
    }
}

// Deletar pixel
async function deletePixel(pixelId, pixelName) {
    if (!confirm(`Tem certeza que deseja deletar o pixel "${pixelName}"?\n\nTodos os eventos relacionados também serão deletados.`)) {
        return;
    }
    
    try {
        const response = await fetch('index.php?page=capi&action=deletePixel', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ pixel_id: pixelId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Pixel deletado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao deletar pixel: ' + error.message);
    }
}

// Mostrar script
function showScript(pixelId) {
    const baseUrl = '<?= $config['base_url'] ?>';
    const scriptCode = `<!-- UTMTrack CAPI - Script de Rastreamento Automatizado -->
<script 
  src="${baseUrl}/js/capi-tracker.js"
  data-pixel-id="${pixelId}"
  data-api-url="${baseUrl}/api/capi-events.php"
  async defer>
</script>`;
    
    document.getElementById('scriptCode').textContent = scriptCode;
    document.getElementById('scriptModal').style.display = 'flex';
}

// Fechar modal de script
function closeScriptModal() {
    document.getElementById('scriptModal').style.display = 'none';
}

// Copiar script
function copyScript() {
    const code = document.getElementById('scriptCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        alert('Script copiado para a área de transferência!');
    });
}

// Toggle evento
async function toggleEvent(pixelId, eventName, enabled) {
    try {
        const response = await fetch('index.php?page=capi&action=updateConfig', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                pixel_id: pixelId,
                config: {
                    auto_events: {
                        [eventName]: enabled
                    }
                }
            })
        });
        
        const result = await response.json();
        if (!result.success) {
            alert('Erro ao atualizar configuração');
        }
    } catch (error) {
        alert('Erro: ' + error.message);
    }
}

// Testar pixel
async function testPixel(pixelId) {
    alert('Enviando evento de teste... Verifique o Test Events do Facebook.');
    // TODO: Implementar teste
}

// Fechar modais ao clicar fora
window.onclick = function(event) {
    const pixelModal = document.getElementById('pixelModal');
    const scriptModal = document.getElementById('scriptModal');
    
    if (event.target === pixelModal) {
        closePixelModal();
    }
    if (event.target === scriptModal) {
        closeScriptModal();
    }
}
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #1e293b;
    border-radius: 12px;
    padding: 0;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #334155;
}

.modal-header h3 {
    color: white;
    margin: 0;
}

.modal-close {
    color: #94a3b8;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.modal-close:hover {
    color: white;
}

.modal-content form {
    padding: 20px;
}

.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}
</style>