<!-- Voltar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=capi" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar para CAPI
    </a>
</div>

<h1 style="color: white; margin-bottom: 10px;">📋 Logs CAPI</h1>
<p style="color: #94a3b8; margin-bottom: 30px;">
    Histórico detalhado de eventos, erros e atividades do sistema CAPI
</p>

<!-- Filtros -->
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
        <input type="hidden" name="page" value="capi">
        <input type="hidden" name="action" value="logs">
        
        <div class="form-group" style="margin: 0;">
            <label>Tipo de Log</label>
            <select name="log_type" class="form-control">
                <option value="">Todos</option>
                <option value="info" <?= ($_GET['log_type'] ?? '') === 'info' ? 'selected' : '' ?>>Info</option>
                <option value="warning" <?= ($_GET['log_type'] ?? '') === 'warning' ? 'selected' : '' ?>>Warning</option>
                <option value="error" <?= ($_GET['log_type'] ?? '') === 'error' ? 'selected' : '' ?>>Error</option>
                <option value="debug" <?= ($_GET['log_type'] ?? '') === 'debug' ? 'selected' : '' ?>>Debug</option>
            </select>
        </div>
        
        <div class="form-group" style="margin: 0;">
            <label>Pesquisar</label>
            <input type="text" name="search" class="form-control" 
                   placeholder="Buscar na mensagem..." 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">Filtrar</button>
        
        <a href="index.php?page=capi&action=logs" class="btn" style="background: #334155; color: white; text-decoration: none;">
            Limpar Filtros
        </a>
    </form>
</div>

<!-- Tabela de Logs -->
<div class="card">
    <?php if (empty($logs)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📋</div>
        <div class="empty-state-title">Nenhum log encontrado</div>
        <p>Logs aparecerão aqui conforme o sistema opera</p>
    </div>
    <?php else: ?>
    
    <div style="overflow-x: auto;">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 100px;">Tipo</th>
                    <th style="width: 150px;">Pixel</th>
                    <th style="width: 140px;">Data/Hora</th>
                    <th>Mensagem</th>
                    <th style="width: 100px; text-align: center;">Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <?php
                        $badges = [
                            'info' => ['color' => '#667eea', 'icon' => 'ℹ️', 'text' => 'Info'],
                            'warning' => ['color' => '#f59e0b', 'icon' => '⚠️', 'text' => 'Warning'],
                            'error' => ['color' => '#ef4444', 'icon' => '❌', 'text' => 'Error'],
                            'debug' => ['color' => '#8b5cf6', 'icon' => '🐛', 'text' => 'Debug']
                        ];
                        $badge = $badges[$log['log_type']] ?? $badges['info'];
                        ?>
                        <span class="badge" style="background: <?= $badge['color'] ?>; display: inline-flex; align-items: center; gap: 4px;">
                            <span><?= $badge['icon'] ?></span>
                            <span><?= $badge['text'] ?></span>
                        </span>
                    </td>
                    
                    <td style="color: #94a3b8; font-size: 13px;">
                        <?= htmlspecialchars($log['pixel_name']) ?>
                    </td>
                    
                    <td style="color: #94a3b8; font-size: 13px;">
                        <?php
                        $date = new DateTime($log['created_at']);
                        echo $date->format('d/m/Y H:i:s');
                        ?>
                    </td>
                    
                    <td style="color: #e2e8f0;">
                        <?= htmlspecialchars($log['message']) ?>
                        
                        <?php if (!empty($log['event_id'])): ?>
                        <div style="margin-top: 4px;">
                            <code style="background: #1e293b; padding: 2px 6px; border-radius: 4px; font-size: 11px; color: #94a3b8;">
                                Event ID: <?= htmlspecialchars($log['event_id']) ?>
                            </code>
                        </div>
                        <?php endif; ?>
                    </td>
                    
                    <td style="text-align: center;">
                        <?php if (!empty($log['context_data'])): ?>
                        <button onclick="showLogDetails(<?= htmlspecialchars(json_encode($log)) ?>)" 
                                class="btn" 
                                style="background: #334155; color: white; padding: 4px 12px; font-size: 12px;">
                            Ver Mais
                        </button>
                        <?php else: ?>
                        <span style="color: #64748b; font-size: 12px;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
    <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
        <?php if ($currentPage > 1): ?>
        <a href="?page=capi&action=logs&page=<?= $currentPage - 1 ?>" 
           class="btn" 
           style="background: #334155; color: white; text-decoration: none;">
            ← Anterior
        </a>
        <?php endif; ?>
        
        <span style="color: #94a3b8;">
            Página <?= $currentPage ?> de <?= $totalPages ?>
        </span>
        
        <?php if ($currentPage < $totalPages): ?>
        <a href="?page=capi&action=logs&page=<?= $currentPage + 1 ?>" 
           class="btn" 
           style="background: #334155; color: white; text-decoration: none;">
            Próxima →
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<!-- Modal de Detalhes do Log -->
<div id="logDetailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Detalhes do Log</h3>
            <span class="modal-close" onclick="closeLogDetails()">&times;</span>
        </div>
        
        <div style="padding: 20px;">
            <div class="log-detail-section">
                <h4>📋 Informações Básicas</h4>
                <table class="detail-table">
                    <tr>
                        <td><strong>Tipo:</strong></td>
                        <td id="detailType"></td>
                    </tr>
                    <tr>
                        <td><strong>Pixel:</strong></td>
                        <td id="detailPixel"></td>
                    </tr>
                    <tr>
                        <td><strong>Data/Hora:</strong></td>
                        <td id="detailDate"></td>
                    </tr>
                    <tr>
                        <td><strong>Event ID:</strong></td>
                        <td id="detailEventId"></td>
                    </tr>
                </table>
            </div>
            
            <div class="log-detail-section">
                <h4>💬 Mensagem</h4>
                <div id="detailMessage" style="background: #1e293b; padding: 15px; border-radius: 8px; color: #e2e8f0;"></div>
            </div>
            
            <div class="log-detail-section" id="contextSection" style="display: none;">
                <h4>🔍 Dados de Contexto</h4>
                <pre id="detailContext" style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 15px; color: #10b981; overflow-x: auto; font-size: 12px; font-family: 'Courier New', monospace;"></pre>
            </div>
        </div>
        
        <div style="padding: 0 20px 20px 20px; display: flex; justify-content: flex-end;">
            <button onclick="closeLogDetails()" class="btn" style="background: #334155; color: white;">
                Fechar
            </button>
        </div>
    </div>
</div>

<script>
function showLogDetails(log) {
    // Formata data
    const date = new Date(log.created_at);
    const formattedDate = date.toLocaleString('pt-BR');
    
    // Tipo badge
    const badges = {
        'info': { color: '#667eea', icon: 'ℹ️', text: 'Info' },
        'warning': { color: '#f59e0b', icon: '⚠️', text: 'Warning' },
        'error': { color: '#ef4444', icon: '❌', text: 'Error' },
        'debug': { color: '#8b5cf6', icon: '🐛', text: 'Debug' }
    };
    const badge = badges[log.log_type] || badges['info'];
    
    // Preenche modal
    document.getElementById('detailType').innerHTML = `
        <span class="badge" style="background: ${badge.color};">
            ${badge.icon} ${badge.text}
        </span>
    `;
    document.getElementById('detailPixel').textContent = log.pixel_name;
    document.getElementById('detailDate').textContent = formattedDate;
    document.getElementById('detailEventId').innerHTML = log.event_id 
        ? `<code style="background: #1e293b; padding: 2px 8px; border-radius: 4px;">${log.event_id}</code>`
        : '-';
    document.getElementById('detailMessage').textContent = log.message;
    
    // Context data
    if (log.context_data && Object.keys(log.context_data).length > 0) {
        document.getElementById('contextSection').style.display = 'block';
        document.getElementById('detailContext').textContent = JSON.stringify(log.context_data, null, 2);
    } else {
        document.getElementById('contextSection').style.display = 'none';
    }
    
    // Mostra modal
    document.getElementById('logDetailsModal').style.display = 'flex';
}

function closeLogDetails() {
    document.getElementById('logDetailsModal').style.display = 'none';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('logDetailsModal');
    if (event.target === modal) {
        closeLogDetails();
    }
}
</script>

<style>
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    color: white;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    background: #1e293b;
    color: #94a3b8;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    border-bottom: 2px solid #334155;
}

table td {
    padding: 12px;
    border-bottom: 1px solid #334155;
}

table tbody tr:hover {
    background: rgba(102, 126, 234, 0.05);
}

.log-detail-section {
    margin-bottom: 25px;
}

.log-detail-section h4 {
    color: #a5b4fc;
    margin: 0 0 15px 0;
    font-size: 16px;
}

.detail-table {
    width: 100%;
}

.detail-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #334155;
    color: #94a3b8;
}

.detail-table td:first-child {
    width: 150px;
    color: #64748b;
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
</style>