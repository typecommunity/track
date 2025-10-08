<!-- 
    UTMTrack - Todas as Campanhas
    Arquivo: app/views/campaigns/index.php
-->

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: white;
    margin: 0;
}

.campaigns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 24px;
}

.summary-label {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.summary-value {
    font-size: 32px;
    font-weight: 700;
    color: white;
    margin-bottom: 8px;
}

.summary-info {
    font-size: 12px;
    color: #64748b;
}

.filter-bar {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #e2e8f0;
    font-size: 13px;
}

.filter-input {
    width: 100%;
    padding: 12px;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 8px;
    color: #e2e8f0;
    font-size: 14px;
}

.campaigns-table {
    width: 100%;
    border-collapse: collapse;
}

.campaigns-table th,
.campaigns-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #1e293b;
}

.campaigns-table th {
    background: #0f172a;
    color: #e2e8f0;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: sticky;
    top: 0;
    z-index: 10;
}

.campaigns-table td {
    color: #cbd5e1;
    font-size: 14px;
}

.campaigns-table tbody tr {
    transition: background-color 0.2s;
}

.campaigns-table tbody tr:hover {
    background: rgba(100, 116, 139, 0.08);
    cursor: pointer;
}

.campaign-name-cell {
    max-width: 300px;
}

.campaign-name {
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
}

.campaign-id {
    font-size: 11px;
    color: #64748b;
    font-family: monospace;
}

.account-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(102, 126, 234, 0.15);
    color: #a5b4fc;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">📊 Todas as Campanhas</h1>
    <div style="display: flex; gap: 10px;">
        <a href="index.php?page=integracoes-meta-contas" class="btn" style="background: #667eea; color: white; text-decoration: none;">
            ⚙️ Gerenciar Contas
        </a>
        <button onclick="window.location.reload()" class="btn" style="background: #10b981; color: white;">
            🔄 Atualizar
        </button>
    </div>
</div>

<!-- Resumo Geral -->
<div class="campaigns-grid">
    <div class="summary-card">
        <div class="summary-label">📊 Total de Campanhas</div>
        <div class="summary-value"><?= number_format($stats['total_campaigns'] ?? 0, 0, ',', '.') ?></div>
        <div class="summary-info">
            <?= $stats['active_campaigns'] ?? 0 ?> ativas
        </div>
    </div>
    
    <div class="summary-card">
        <div class="summary-label">💰 Gasto Total</div>
        <div class="summary-value">R$ <?= number_format($stats['total_spent'] ?? 0, 2, ',', '.') ?></div>
        <div class="summary-info">
            Todas as contas
        </div>
    </div>
    
    <div class="summary-card">
        <div class="summary-label">👁️ Impressões</div>
        <div class="summary-value"><?= number_format($stats['total_impressions'] ?? 0, 0, ',', '.') ?></div>
        <div class="summary-info">
            CTR: <?= $stats['ctr'] ?? 0 ?>%
        </div>
    </div>
    
    <div class="summary-card">
        <div class="summary-label">👆 Cliques</div>
        <div class="summary-value"><?= number_format($stats['total_clicks'] ?? 0, 0, ',', '.') ?></div>
        <div class="summary-info">
            CPC: R$ <?= number_format($stats['avg_cpc'] ?? 0, 2, ',', '.') ?>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filter-bar">
    <div class="filter-group" style="flex: 2;">
        <label class="filter-label">🔍 Buscar Campanha</label>
        <input 
            type="text" 
            id="searchCampaign" 
            class="filter-input"
            placeholder="Nome ou ID da campanha..."
            onkeyup="filterCampaigns()"
        >
    </div>
    
    <div class="filter-group">
        <label class="filter-label">📁 Conta</label>
        <select id="filterAccount" class="filter-input" onchange="filterCampaigns()">
            <option value="">Todas as contas</option>
            <?php
            // Busca contas únicas
            $accounts = array_unique(array_column($campaigns, 'account_name'));
            foreach ($accounts as $account):
            ?>
            <option value="<?= htmlspecialchars($account) ?>"><?= htmlspecialchars($account) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="filter-group">
        <label class="filter-label">🎯 Status</label>
        <select id="filterStatus" class="filter-input" onchange="filterCampaigns()">
            <option value="">Todos</option>
            <option value="active">Ativas</option>
            <option value="paused">Pausadas</option>
            <option value="deleted">Deletadas</option>
        </select>
    </div>
    
    <div class="filter-group">
        <label class="filter-label">⚡ Ordenar Por</label>
        <select id="sortBy" class="filter-input" onchange="sortCampaigns()">
            <option value="spent_desc">Gasto (maior)</option>
            <option value="spent_asc">Gasto (menor)</option>
            <option value="name_asc">Nome (A-Z)</option>
            <option value="name_desc">Nome (Z-A)</option>
            <option value="impressions_desc">Impressões (maior)</option>
            <option value="clicks_desc">Cliques (maior)</option>
        </select>
    </div>
    
    <button onclick="exportAll()" class="btn" style="background: #667eea; color: white;">
        📥 Exportar
    </button>
</div>

<!-- Tabela de Campanhas -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📋 Lista de Campanhas</h2>
        <div id="resultsCount" style="color: #94a3b8; font-size: 13px;">
            <?= count($campaigns) ?> campanhas
        </div>
    </div>
    
    <?php if (empty($campaigns)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <div class="empty-state-title">Nenhuma campanha encontrada</div>
        <p>Conecte suas contas Meta Ads e sincronize para ver as campanhas aqui</p>
        <a href="index.php?page=integracoes-meta-contas" class="btn btn-primary" style="margin-top: 20px; text-decoration: none;">
            ⚙️ Gerenciar Contas
        </a>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="campaigns-table" id="campaignsTable">
            <thead>
                <tr>
                    <th>Campanha</th>
                    <th>Conta</th>
                    <th>Status</th>
                    <th>Gasto</th>
                    <th>Impressões</th>
                    <th>Cliques</th>
                    <th>CTR</th>
                    <th>CPC</th>
                    <th>Conversões</th>
                    <th>Última Sync</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $campaign): 
                    $ctr = $campaign['impressions'] > 0 ? ($campaign['clicks'] / $campaign['impressions']) * 100 : 0;
                    $cpc = $campaign['clicks'] > 0 ? $campaign['spent'] / $campaign['clicks'] : 0;
                ?>
                <tr 
                    data-status="<?= $campaign['status'] ?>" 
                    data-account="<?= htmlspecialchars($campaign['account_name']) ?>"
                    data-name="<?= strtolower($campaign['campaign_name']) ?>"
                    onclick="viewCampaign(<?= $campaign['id'] ?>)"
                >
                    <td class="campaign-name-cell">
                        <div class="campaign-name">
                            <?= htmlspecialchars($campaign['campaign_name']) ?>
                        </div>
                        <div class="campaign-id">
                            ID: <?= htmlspecialchars($campaign['campaign_id']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="account-badge">
                            <?= htmlspecialchars($campaign['account_name']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $campaign['status'] === 'active' ? 'success' : ($campaign['status'] === 'paused' ? 'warning' : 'danger') ?>">
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
                    <td style="font-weight: 600;">
                        R$ <?= number_format($campaign['spent'], 2, ',', '.') ?>
                    </td>
                    <td>
                        <?= number_format($campaign['impressions'], 0, ',', '.') ?>
                    </td>
                    <td>
                        <?= number_format($campaign['clicks'], 0, ',', '.') ?>
                    </td>
                    <td>
                        <?= number_format($ctr, 2, ',', '.') ?>%
                    </td>
                    <td>
                        R$ <?= number_format($cpc, 2, ',', '.') ?>
                    </td>
                    <td style="text-align: center;">
                        <?= number_format($campaign['conversions'], 0, ',', '.') ?>
                    </td>
                    <td style="font-size: 12px; color: #64748b;">
                        <?= date('d/m/Y H:i', strtotime($campaign['last_sync'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
// Filtrar campanhas
function filterCampaigns() {
    const search = document.getElementById('searchCampaign').value.toLowerCase();
    const accountFilter = document.getElementById('filterAccount').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    
    const rows = document.querySelectorAll('#campaignsTable tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const account = row.getAttribute('data-account').toLowerCase();
        const status = row.getAttribute('data-status');
        
        const matchesSearch = !search || name.includes(search);
        const matchesAccount = !accountFilter || account === accountFilter;
        const matchesStatus = !statusFilter || status === statusFilter;
        
        if (matchesSearch && matchesAccount && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    document.getElementById('resultsCount').textContent = `${visibleCount} campanha${visibleCount !== 1 ? 's' : ''}`;
}

// Ordenar campanhas
function sortCampaigns() {
    const sortBy = document.getElementById('sortBy').value;
    const tbody = document.querySelector('#campaignsTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let aVal, bVal;
        
        switch(sortBy) {
            case 'spent_desc':
                aVal = parseFloat(a.cells[3].textContent.replace(/[^\d,]/g, '').replace(',', '.'));
                bVal = parseFloat(b.cells[3].textContent.replace(/[^\d,]/g, '').replace(',', '.'));
                return bVal - aVal;
            
            case 'spent_asc':
                aVal = parseFloat(a.cells[3].textContent.replace(/[^\d,]/g, '').replace(',', '.'));
                bVal = parseFloat(b.cells[3].textContent.replace(/[^\d,]/g, '').replace(',', '.'));
                return aVal - bVal;
            
            case 'name_asc':
                return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
            
            case 'name_desc':
                return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
            
            case 'impressions_desc':
                aVal = parseInt(a.cells[4].textContent.replace(/\D/g, ''));
                bVal = parseInt(b.cells[4].textContent.replace(/\D/g, ''));
                return bVal - aVal;
            
            case 'clicks_desc':
                aVal = parseInt(a.cells[5].textContent.replace(/\D/g, ''));
                bVal = parseInt(b.cells[5].textContent.replace(/\D/g, ''));
                return bVal - aVal;
        }
        
        return 0;
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Ver detalhes da campanha
function viewCampaign(campaignId) {
    window.location.href = `index.php?page=campanha-detalhes&id=${campaignId}`;
}

// Exportar todas
function exportAll() {
    window.location.href = 'index.php?page=campanhas-export';
}
</script>