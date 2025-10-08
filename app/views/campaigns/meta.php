<!-- Barra de Navegação -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 15px;">
        <a href="index.php?page=integracoes-meta-contas" class="btn" style="background: #334155; color: white; text-decoration: none;">
            ← Voltar
        </a>
        
        <?php if (isset($account)): ?>
        <div>
            <h2 style="color: white; font-size: 20px; font-weight: 600; margin-bottom: 4px;">
                <?= htmlspecialchars($account['account_name']) ?>
            </h2>
            <p style="color: #94a3b8; font-size: 13px;">
                ID: <?= htmlspecialchars($account['account_id']) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <button onclick="syncCampaigns()" class="btn" style="background: #10b981; color: white;">
            🔄 Sincronizar
        </button>
        <button onclick="exportCampaigns()" class="btn" style="background: #667eea; color: white;">
            📥 Exportar
        </button>
    </div>
</div>

<!-- Métricas Resumidas -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
    <div class="metric-card">
        <div class="metric-label">📊 Total de Campanhas</div>
        <div class="metric-value"><?= number_format($stats['total_campaigns'] ?? 0, 0, ',', '.') ?></div>
        <div class="metric-info">
            <?= $stats['active_campaigns'] ?? 0 ?> ativas
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">💰 Gasto Total</div>
        <div class="metric-value">R$ <?= number_format($stats['total_spent'] ?? 0, 2, ',', '.') ?></div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">👁️ Impressões</div>
        <div class="metric-value"><?= number_format($stats['total_impressions'] ?? 0, 0, ',', '.') ?></div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">👆 Cliques</div>
        <div class="metric-value"><?= number_format($stats['total_clicks'] ?? 0, 0, ',', '.') ?></div>
        <div class="metric-info">
            CTR: <?= $stats['ctr'] ?? 0 ?>%
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">🎯 Conversões</div>
        <div class="metric-value"><?= number_format($stats['total_conversions'] ?? 0, 0, ',', '.') ?></div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">💵 CPC Médio</div>
        <div class="metric-value">R$ <?= number_format($stats['avg_cpc'] ?? 0, 2, ',', '.') ?></div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; gap: 15px; align-items: flex-end;">
        <div style="flex: 1;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0; font-size: 13px;">
                Buscar Campanha
            </label>
            <input 
                type="text" 
                id="searchCampaign" 
                placeholder="Nome ou ID da campanha..."
                onkeyup="filterCampaigns()"
                style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
            >
        </div>
        
        <div style="width: 200px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0; font-size: 13px;">
                Status
            </label>
            <select 
                id="filterStatus" 
                onchange="filterCampaigns()"
                style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
            >
                <option value="">Todos</option>
                <option value="active">Ativas</option>
                <option value="paused">Pausadas</option>
                <option value="deleted">Deletadas</option>
            </select>
        </div>
        
        <div style="width: 200px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0; font-size: 13px;">
                Ordenar Por
            </label>
            <select 
                id="sortBy" 
                onchange="sortCampaigns()"
                style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
            >
                <option value="spent_desc">Gasto (maior)</option>
                <option value="spent_asc">Gasto (menor)</option>
                <option value="name_asc">Nome (A-Z)</option>
                <option value="name_desc">Nome (Z-A)</option>
                <option value="impressions_desc">Impressões (maior)</option>
                <option value="clicks_desc">Cliques (maior)</option>
            </select>
        </div>
    </div>
</div>

<!-- Lista de Campanhas -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📊 Campanhas</h2>
        <div style="color: #94a3b8; font-size: 13px;">
            Última atualização: <?= isset($account['last_sync']) ? date('d/m/Y H:i', strtotime($account['last_sync'])) : 'Nunca' ?>
        </div>
    </div>
    
    <?php if (empty($campaigns)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <div class="empty-state-title">Nenhuma campanha encontrada</div>
        <p>Sincronize a conta para importar as campanhas do Meta Ads</p>
        <button onclick="syncCampaigns()" class="btn btn-primary" style="margin-top: 20px;">
            🔄 Sincronizar Agora
        </button>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table id="campaignsTable">
            <thead>
                <tr>
                    <th style="min-width: 200px;">Campanha</th>
                    <th>Status</th>
                    <th>Objetivo</th>
                    <th>Orçamento</th>
                    <th>Gasto</th>
                    <th>Impressões</th>
                    <th>Cliques</th>
                    <th>CTR</th>
                    <th>CPC</th>
                    <th>Conversões</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $campaign): 
                    $ctr = $campaign['impressions'] > 0 ? ($campaign['clicks'] / $campaign['impressions']) * 100 : 0;
                    $cpc = $campaign['clicks'] > 0 ? $campaign['spent'] / $campaign['clicks'] : 0;
                ?>
                <tr data-status="<?= $campaign['status'] ?>" data-name="<?= strtolower($campaign['campaign_name']) ?>">
                    <td>
                        <div style="font-weight: 600; color: white; margin-bottom: 4px;">
                            <?= htmlspecialchars($campaign['campaign_name']) ?>
                        </div>
                        <div style="font-size: 11px; color: #64748b; font-family: monospace;">
                            ID: <?= htmlspecialchars($campaign['campaign_id']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $campaign['status'] === 'active' ? 'success' : ($campaign['status'] === 'paused' ? 'warning' : 'danger') ?>" style="
                            display: inline-block;
                            padding: 4px 12px;
                            border-radius: 12px;
                            font-size: 11px;
                            font-weight: 600;
                        ">
                            <?php
                            $statusLabels = [
                                'active' => '✓ Ativa',
                                'paused' => '⏸ Pausada',
                                'deleted' => '🗑 Deletada'
                            ];
                            echo $statusLabels[$campaign['status']] ?? $campaign['status'];
                            ?>
                        </span>
                    </td>
                    <td style="font-size: 12px;">
                        <?= htmlspecialchars($campaign['objective'] ?? '-') ?>
                    </td>
                    <td>R$ <?= number_format($campaign['budget'], 2, ',', '.') ?></td>
                    <td style="font-weight: 600;">R$ <?= number_format($campaign['spent'], 2, ',', '.') ?></td>
                    <td><?= number_format($campaign['impressions'], 0, ',', '.') ?></td>
                    <td><?= number_format($campaign['clicks'], 0, ',', '.') ?></td>
                    <td><?= number_format($ctr, 2, ',', '.') ?>%</td>
                    <td>R$ <?= number_format($cpc, 2, ',', '.') ?></td>
                    <td><?= number_format($campaign['conversions'], 0, ',', '.') ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <button 
                                onclick='viewDetails(<?= $campaign['id'] ?>)' 
                                class="btn" 
                                style="padding: 6px 12px; font-size: 12px; background: #667eea; color: white;"
                                title="Ver Detalhes"
                            >
                                👁️
                            </button>
                            <button 
                                onclick='editCampaign(<?= $campaign['id'] ?>)' 
                                class="btn" 
                                style="padding: 6px 12px; font-size: 12px; background: #10b981; color: white;"
                                title="Editar"
                            >
                                ✏️
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
.badge-success {
    background: #10b98120;
    color: #10b981;
}

.badge-warning {
    background: #f59e0b20;
    color: #f59e0b;
}

.badge-danger {
    background: #ef444420;
    color: #ef4444;
}
</style>

<script>
// Sincronizar campanhas
async function syncCampaigns() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Sincronizando...';
    btn.disabled = true;
    
    try {
        const accountId = <?= $account['id'] ?? 0 ?>;
        const response = await fetch(`index.php?page=campanhas-sync&account=${accountId}`, {
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

// Filtrar campanhas
function filterCampaigns() {
    const searchTerm = document.getElementById('searchCampaign').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('#campaignsTable tbody tr');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const status = row.getAttribute('data-status');
        
        const matchesSearch = !searchTerm || name.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        
        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
}

// Ordenar campanhas
function sortCampaigns() {
    const sortBy = document.getElementById('sortBy').value;
    const table = document.getElementById('campaignsTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let aVal, bVal;
        
        switch(sortBy) {
            case 'spent_desc':
                aVal = parseFloat(a.cells[4].textContent.replace(/[^\d,]/g, '').replace(',', '.'));
                bVal = parseFloat(b.cells[4].textContent.replace(/[^\d,]/g, '').replace(',', '.'));
                return bVal - aVal;
            
            case 'spent_asc':
                aVal = parseFloat(a.cells[4].textContent.replace(/[^\d,]/g, '').replace(',', '.'));
                bVal = parseFloat(b.cells[4].textContent.replace(/[^\d,]/g, '').replace(',', '.'));
                return aVal - bVal;
            
            case 'name_asc':
                return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
            
            case 'name_desc':
                return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
            
            case 'impressions_desc':
                aVal = parseInt(a.cells[5].textContent.replace(/\D/g, ''));
                bVal = parseInt(b.cells[5].textContent.replace(/\D/g, ''));
                return bVal - aVal;
            
            case 'clicks_desc':
                aVal = parseInt(a.cells[6].textContent.replace(/\D/g, ''));
                bVal = parseInt(b.cells[6].textContent.replace(/\D/g, ''));
                return bVal - aVal;
        }
        
        return 0;
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Ver detalhes
function viewDetails(campaignId) {
    window.location.href = `index.php?page=campanha-detalhes&id=${campaignId}`;
}

// Editar campanha
function editCampaign(campaignId) {
    alert('Edição de campanhas em desenvolvimento...');
}

// Exportar campanhas
function exportCampaigns() {
    const accountId = <?= $account['id'] ?? 0 ?>;
    window.location.href = `index.php?page=campanhas-export&account=${accountId}`;
}
</script>