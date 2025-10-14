<!-- Voltar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=utms" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar
    </a>
</div>

<h1 style="color: white; margin-bottom: 10px;">⚙️ Configurações</h1>
<p style="color: #94a3b8; margin-bottom: 30px;">
    Gerencie seus pixels do Facebook e configure quais eventos serão rastreados automaticamente
</p>

<!-- Botão Adicionar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=utms&action=setup" class="btn btn-primary" style="text-decoration: none;">
        ➕ Adicionar Novo Pixel
    </a>
</div>

<?php if (empty($pixels)): ?>
<!-- Nenhum Pixel -->
<div class="card">
    <div class="empty-state">
        <div class="empty-state-icon">🎯</div>
        <div class="empty-state-title">Nenhum pixel configurado</div>
        <p>Adicione seu primeiro pixel do Facebook para começar</p>
        <a href="index.php?page=utms&action=setup" class="btn btn-primary" style="margin-top: 15px; text-decoration: none;">
            Adicionar Pixel
        </a>
    </div>
</div>

<?php else: ?>

<!-- Lista de Pixels -->
<?php foreach ($pixels as $pixel): ?>
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
        <div>
            <h3 style="color: white; margin: 0 0 8px 0;">
                <?= htmlspecialchars($pixel['pixel_name'] ?: 'Pixel ' . $pixel['pixel_id']) ?>
            </h3>
            <p style="color: #94a3b8; margin: 0; font-size: 14px;">
                ID: <code style="background: #1e293b; padding: 2px 8px; border-radius: 4px;"><?= htmlspecialchars($pixel['pixel_id']) ?></code>
            </p>
        </div>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" 
                       <?= $pixel['capi_enabled'] ? 'checked' : '' ?>
                       onchange="toggleCapiEnabled('<?= $pixel['id'] ?>', this.checked)"
                       style="width: 18px; height: 18px;">
                <span style="color: #94a3b8; font-size: 14px;">CAPI Ativo</span>
            </label>
            
            <span class="badge" style="background: <?= $pixel['status'] === 'active' ? '#10b981' : '#6b7280' ?>;">
                <?= $pixel['status'] === 'active' ? 'Ativo' : 'Inativo' ?>
            </span>
        </div>
    </div>
    
    <!-- Configurações de Eventos -->
    <?php if (!empty($pixel['auto_events'])): ?>
    <div style="background: #1e293b; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h4 style="color: white; margin: 0 0 15px 0; font-size: 16px;">🎯 Eventos Automáticos</h4>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
            <?php foreach ($pixel['auto_events'] as $eventName => $enabled): ?>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; background: #0f172a; border-radius: 6px; border: 1px solid <?= $enabled ? '#667eea' : '#334155' ?>; transition: all 0.2s;">
                <input type="checkbox" 
                       <?= $enabled ? 'checked' : '' ?>
                       onchange="toggleEvent('<?= $pixel['id'] ?>', '<?= $eventName ?>', this.checked)"
                       style="width: 18px; height: 18px;">
                <div style="flex: 1;">
                    <div style="color: <?= $enabled ? '#e2e8f0' : '#64748b' ?>; font-weight: 600; font-size: 14px; margin-bottom: 2px;">
                        <?= htmlspecialchars($eventName) ?>
                    </div>
                    <div style="color: #64748b; font-size: 11px;">
                        <?php
                        $descriptions = [
                            'PageView' => 'Visualização de página',
                            'ViewContent' => 'Visualização de conteúdo',
                            'Lead' => 'Captura de email',
                            'InitiateCheckout' => 'Início de checkout',
                            'AddToCart' => 'Adicionar ao carrinho',
                            'Purchase' => 'Compra finalizada'
                        ];
                        echo $descriptions[$eventName] ?? '';
                        ?>
                    </div>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Informações do Token -->
    <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
        <h4 style="color: #fca5a5; margin: 0 0 8px 0; font-size: 14px;">🔑 Token de Acesso</h4>
        <p style="color: #94a3b8; margin: 0; font-size: 13px;">
            Token: <code style="background: #1e293b; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                <?= substr($pixel['access_token'] ?? '', 0, 20) ?>...
            </code>
        </p>
        <?php if (!empty($pixel['test_event_code'])): ?>
        <p style="color: #94a3b8; margin: 8px 0 0 0; font-size: 13px;">
            Test Code: <code style="background: #1e293b; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                <?= htmlspecialchars($pixel['test_event_code']) ?>
            </code>
        </p>
        <?php endif; ?>
    </div>
    
    <!-- Ações -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button onclick="editPixel(<?= $pixel['id'] ?>)" 
                class="btn" 
                style="background: #334155; color: white;">
            ✏️ Editar Token
        </button>
        
        <button onclick="testPixel('<?= $pixel['id'] ?>')" 
                class="btn" 
                style="background: #334155; color: white;">
            🧪 Testar Conexão
        </button>
        
        <button onclick="deletePixel('<?= $pixel['id'] ?>', '<?= htmlspecialchars($pixel['pixel_name'] ?: 'Pixel ' . $pixel['pixel_id']) ?>')" 
                class="btn" 
                style="background: #ef4444; color: white;">
            🗑️ Deletar
        </button>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Modal Editar Token -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Token de Acesso</h3>
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        
        <form id="editForm" onsubmit="updateToken(event)">
            <input type="hidden" id="edit_pixel_id" name="pixel_id">
            
            <div class="form-group">
                <label>Novo Token de Acesso *</label>
                <input type="text" id="edit_access_token" name="access_token" class="form-control" required>
                <small style="color: #94a3b8; margin-top: 8px; display: block;">
                    Gere um novo token em 
                    <a href="https://developers.facebook.com/tools/accesstoken/" target="_blank" style="color: #667eea;">
                        developers.facebook.com
                    </a>
                </small>
            </div>
            
            <div class="form-group">
                <label>Test Event Code (opcional)</label>
                <input type="text" id="edit_test_code" name="test_event_code" class="form-control">
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeEditModal()" class="btn" style="background: #334155; color: white;">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle CAPI enabled
async function toggleCapiEnabled(pixelId, enabled) {
    try {
        const formData = new FormData();
        formData.append('pixel_id', pixelId);
        formData.append('capi_enabled', enabled ? '1' : '0');
        
        const response = await fetch('index.php?page=utms&action=toggleCapi', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            alert('Erro ao atualizar: ' + result.message);
            location.reload();
        }
    } catch (error) {
        alert('Erro: ' + error.message);
        location.reload();
    }
}

// Toggle evento
async function toggleEvent(pixelId, eventName, enabled) {
    try {
        const formData = new FormData();
        formData.append('pixel_id', pixelId);
        formData.append('event_name', eventName);
        formData.append('enabled', enabled ? '1' : '0');
        
        const response = await fetch('index.php?page=utms&action=toggleEvent', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Atualiza borda do card
            event.target.closest('label').style.borderColor = enabled ? '#667eea' : '#334155';
            event.target.closest('label').querySelector('div > div').style.color = enabled ? '#e2e8f0' : '#64748b';
        } else {
            alert('Erro ao atualizar evento: ' + result.message);
            location.reload();
        }
    } catch (error) {
        alert('Erro: ' + error.message);
        location.reload();
    }
}

// Editar pixel
function editPixel(pixelId) {
    document.getElementById('edit_pixel_id').value = pixelId;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('editForm').reset();
}

// Atualizar token
async function updateToken(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('index.php?page=utms&action=updateToken', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Token atualizado com sucesso!');
            location.reload();
        } else {
            alert('❌ Erro: ' + result.message);
        }
    } catch (error) {
        alert('❌ Erro ao atualizar token: ' + error.message);
    }
}

// Testar pixel
async function testPixel(pixelId) {
    if (!confirm('Deseja testar a conexão com o Facebook?')) return;
    
    alert('🧪 Testando conexão... Isso pode levar alguns segundos.');
    
    // TODO: Implementar teste
    alert('Teste não implementado ainda. Verifique os logs do sistema.');
}

// Deletar pixel
async function deletePixel(pixelId, pixelName) {
    if (!confirm(`Tem certeza que deseja deletar "${pixelName}"?\n\nTodos os eventos relacionados serão deletados permanentemente!`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('pixel_id', pixelId);
        
        const response = await fetch('index.php?page=utms&action=deletePixel', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Pixel deletado com sucesso!');
            location.reload();
        } else {
            alert('❌ Erro: ' + result.message);
        }
    } catch (error) {
        alert('❌ Erro ao deletar pixel: ' + error.message);
    }
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}
</script>

<style>
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

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

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: white;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 8px;
    color: white;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
}
</style>