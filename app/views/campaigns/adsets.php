<!-- 
    UTMTrack - Dashboard de Anúncios (Ads)
    Arquivo: app/views/campaigns/ads.php
-->

<style>
/* ========================================
   VARIÁVEIS E ESTILOS BASE
======================================== */
:root {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --text-primary: #e2e8f0;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --accent: #667eea;
    --accent-hover: #5568d3;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --border: #334155;
}

* {
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 14px;
}

/* ========================================
   TABS NAVIGATION
======================================== */
.tabs-container {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--border);
}

.tab-button {
    padding: 12px 24px;
    background: transparent;
    border: none;
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    position: relative;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-button:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #a5b4fc;
}

.tab-button.active {
    color: white;
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--accent);
}

.icon {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
}

/* ========================================
   STATS CARDS
======================================== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px;
}

.stat-label {
    font-size: 12px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: white;
}

.stat-subtext {
    font-size: 11px;
    color: var(--text-secondary);
    margin-top: 4px;
}

/* ========================================
   TOOLBAR
======================================== */
.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 16px;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 12px;
    flex-wrap: wrap;
    gap: 15px;
}

.toolbar-left, .toolbar-right {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

/* ========================================
   FILTERS
======================================== */
.filters-bar {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
    padding: 16px;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 12px;
}

@media (max-width: 968px) {
    .filters-bar {
        grid-template-columns: 1fr;
    }
}

.filter-input, .filter-select {
    padding: 10px 14px;
    background: var(--bg-primary);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
    transition: border-color 0.2s;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: var(--accent);
}

/* ========================================
   TABLE
======================================== */
.table-wrapper {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
}

.table-container {
    overflow-x: auto;
    overflow-y: auto;
    max-height: calc(100vh - 520px);
}

.ads-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.ads-table thead {
    background: var(--bg-primary);
    position: sticky;
    top: 0;
    z-index: 100;
}

.ads-table th {
    padding: 12px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
    user-select: none;
    cursor: pointer;
}

.ads-table th:hover {
    background: var(--bg-secondary);
    color: #a5b4fc;
}

.ads-table tbody tr {
    border-bottom: 1px solid rgba(51, 65, 85, 0.3);
    transition: background 0.2s;
}

.ads-table tbody tr:hover {
    background: rgba(100, 116, 139, 0.08);
}

.ads-table td {
    padding: 14px 16px;
    color: var(--text-primary);
}

/* Ad Name Cell */
.ad-name-cell {
    font-weight: 600;
    color: white;
}

.ad-id {
    font-size: 11px;
    color: var(--text-muted);
    font-family: monospace;
    margin-top: 4px;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.status-badge:hover {
    transform: scale(1.05);
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success);
}

.status-badge.paused {
    background: rgba(251, 191, 36, 0.15);
    color: var(--warning);
}

/* Preview Link */
.preview-link {
    color: var(--accent);
    text-decoration: none;
    font-size: 12px;
    padding: 4px 8px;
    border: 1px solid var(--accent);
    border-radius: 6px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.preview-link:hover {
    background: var(--accent);
    color: white;
}

/* Métricas Coloridas */
.metric-positive { color: var(--success); font-weight: 600; }
.metric-negative { color: var(--danger); font-weight: 600; }
.metric-neutral { color: var(--text-secondary); }

/* ========================================
   BUTTONS
======================================== */
.btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-secondary {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.btn-secondary:hover:not(:disabled) {
    background: #475569;
}

.btn-primary {
    background: var(--accent);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: var(--accent-hover);
    transform: translateY(-1px);
}

/* ========================================
   ANIMATIONS
======================================== */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-state-title {
    font-size: 20px;
    color: var(--text-secondary);
    margin-bottom: 8px;
    font-weight: 600;
}
</style>

<!-- TABS -->
<div class="tabs-container">
    <button class="tab-button" onclick="window.location.href='index.php?page=integracoes-meta-contas'">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        Contas
    </button>
    <button class="tab-button" onclick="window.location.href='index.php?page=campanhas'">
        <svg class="icon" viewBox="0 0 24 24">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
        Campanhas
    </button>
    <button class="tab-button" onclick="window.location.href='index.php?page=conjuntos'">
        <svg class="icon" viewBox="0 0 24 24">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="3" y1="9" x2="21" y2="9"></line>
            <line x1="9" y1="21" x2="9" y2="9"></line>
        </svg>
        Conjuntos
    </button>
    <button class="tab-button active">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        Anúncios
    </button>
</div>

<!-- STATS CARDS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total de Anúncios</div>
        <div class="stat-value"><?= number_format($stats['total_ads'] ?? 0) ?></div>
        <div class="stat-subtext"><?= $stats['active_ads'] ?? 0 ?> ativos</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Gasto Total</div>
        <div class="stat-value">R$ <?= number_format($stats['total_spent'] ?? 0, 0, ',', '.') ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">CTR Médio</div>
        <div class="stat-value"><?= number_format($stats['avg_ctr'] ?? 0, 2, ',', '.') ?>%</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">CPC Médio</div>
        <div class="stat-value">R$ <?= number_format($stats['avg_cpc'] ?? 0, 2, ',', '.') ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">CPM Médio</div>
        <div class="stat-value">R$ <?= number_format($stats['avg_cpm'] ?? 0, 2, ',', '.') ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Conversões</div>
        <div class="stat-value"><?= number_format($stats['total_conversions'] ?? 0) ?></div>
    </div>
</div>

<!-- TOOLBAR -->
<div class="toolbar">
    <div class="toolbar-left">
        <span style="color: var(--text-secondary); font-size: 13px;">
            <?= count($ads ?? []) ?> anúncios carregados
        </span>
    </div>
    
    <div class="toolbar-right">
        <button onclick="syncAllAds()" class="btn btn-primary" style="padding: 8px 20px;">
            <svg class="icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            Atualizar
        </button>
    </div>
</div>

<!-- FILTROS -->
<div class="filters-bar">
    <input 
        type="text" 
        class="filter-input" 
        id="searchInput"
        placeholder="Buscar por nome do anúncio..."
        onkeyup="filterTable()"
    >
    
    <select class="filter-select" id="statusFilter" onchange="filterTable()">
        <option value="">Todos os Status</option>
        <option value="active">Ativos</option>
        <option value="paused">Pausados</option>
    </select>
    
    <select class="filter-select" id="campaignFilter" onchange="filterTable()">
        <option value="">Todas as Campanhas</option>
        <?php 
        $campaigns = [];
        foreach ($ads ?? [] as $ad) {
            if (!empty($ad['campaign_name']) && !in_array($ad['campaign_name'], $campaigns)) {
                $campaigns[] = $ad['campaign_name'];
            }
        }
        sort($campaigns);
        foreach ($campaigns as $campaign): 
        ?>
        <option value="<?= htmlspecialchars($campaign) ?>"><?= htmlspecialchars($campaign) ?></option>
        <?php endforeach; ?>
    </select>
    
    <select class="filter-select" id="adsetFilter" onchange="filterTable()">
        <option value="">Todos os Conjuntos</option>
        <?php 
        $adsets = [];
        foreach ($ads ?? [] as $ad) {
            if (!empty($ad['adset_name']) && !in_array($ad['adset_name'], $adsets)) {
                $adsets[] = $ad['adset_name'];
            }
        }
        sort($adsets);
        foreach ($adsets as $adset): 
        ?>
        <option value="<?= htmlspecialchars($adset) ?>"><?= htmlspecialchars($adset) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<!-- TABELA -->
<div class="table-wrapper">
    <div class="table-container">
        <table class="ads-table" id="adsTable">
            <thead>
                <tr>
                    <th>Nome do Anúncio</th>
                    <th>Conjunto</th>
                    <th>Campanha</th>
                    <th>Status</th>
                    <th>Gastos</th>
                    <th>Impressões</th>
                    <th>Cliques</th>
                    <th>CTR</th>
                    <th>CPC</th>
                    <th>CPM</th>
                    <th>Conversões</th>
                    <th>CPA</th>
                    <th>Frequência</th>
                    <th>Última Sync</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ads)): ?>
                <tr>
                    <td colspan="15" style="border: none;">
                        <div class="empty-state">
                            <div class="empty-state-icon">📱</div>
                            <div class="empty-state-title">Nenhum anúncio encontrado</div>
                            <p style="color: var(--text-muted);">Sincronize suas campanhas para ver os anúncios</p>
                            <button onclick="syncAllAds()" class="btn btn-primary" style="margin-top: 20px;">
                                Sincronizar Anúncios
                            </button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($ads as $ad): ?>
                <tr 
                    data-id="<?= $ad['id'] ?>"
                    data-name="<?= strtolower($ad['ad_name']) ?>"
                    data-status="<?= $ad['status'] ?>"
                    data-campaign="<?= htmlspecialchars($ad['campaign_name'] ?? '') ?>"
                    data-adset="<?= htmlspecialchars($ad['adset_name'] ?? '') ?>"
                >
                    <td>
                        <div class="ad-name-cell">
                            <?= htmlspecialchars($ad['ad_name']) ?>
                        </div>
                        <div class="ad-id">ID: <?= htmlspecialchars($ad['ad_id']) ?></div>
                    </td>
                    
                    <td><?= htmlspecialchars($ad['adset_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($ad['campaign_name'] ?? '-') ?></td>
                    
                    <td>
                        <span class="status-badge <?= $ad['status'] ?>" onclick="toggleStatus(this, <?= $ad['id'] ?>)">
                            <?= $ad['status'] === 'active' ? '✓ Ativo' : '⏸ Pausado' ?>
                        </span>
                    </td>
                    
                    <td><strong>R$ <?= number_format($ad['spent'] ?? 0, 2, ',', '.') ?></strong></td>
                    <td><?= number_format($ad['impressions'] ?? 0, 0, ',', '.') ?></td>
                    <td><?= number_format($ad['clicks'] ?? 0, 0, ',', '.') ?></td>
                    
                    <td class="<?= ($ad['ctr'] ?? 0) > 2 ? 'metric-positive' : (($ad['ctr'] ?? 0) < 0.5 ? 'metric-negative' : '') ?>">
                        <?= number_format($ad['ctr'] ?? $ad['calculated_ctr'] ?? 0, 2, ',', '.') ?>%
                    </td>
                    
                    <td>R$ <?= number_format($ad['cpc'] ?? $ad['calculated_cpc'] ?? 0, 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($ad['cpm'] ?? $ad['calculated_cpm'] ?? 0, 2, ',', '.') ?></td>
                    
                    <td class="<?= ($ad['conversions'] ?? 0) > 0 ? 'metric-positive' : 'metric-neutral' ?>">
                        <?= number_format($ad['conversions'] ?? 0, 0, ',', '.') ?>
                    </td>
                    
                    <td>
                        <?php if (($ad['conversions'] ?? 0) > 0): ?>
                            R$ <?= number_format($ad['calculated_cpa'] ?? 0, 2, ',', '.') ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    
                    <td><?= number_format($ad['frequency'] ?? 0, 2, ',', '.') ?></td>
                    
                    <td>
                        <span style="color: var(--text-muted); font-size: 12px;">
                            <?= $ad['sync_time'] ?? 'nunca' ?>
                        </span>
                    </td>
                    
                    <td>
                        <?php if (!empty($ad['preview_url'])): ?>
                        <a href="<?= htmlspecialchars($ad['preview_url']) ?>" target="_blank" class="preview-link">
                            <svg class="icon" style="width: 14px; height: 14px;" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            Preview
                        </a>
                        <?php else: ?>
                        <button onclick="getPreview(<?= $ad['id'] ?>)" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;">
                            Ver
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Sincronizar todos os anúncios
async function syncAllAds() {
    if (!confirm('🔄 Sincronizar todos os anúncios? Isso pode demorar alguns minutos.')) return;
    
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="icon" style="width: 16px; height: 16px; animation: spin 1s linear infinite;" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"></circle></svg> Sincronizando...';
    
    try {
        const response = await fetch('index.php?page=anuncios-sync', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: ''
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ ${result.message}`);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        alert('❌ Erro: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

// Toggle status
async function toggleStatus(element, adId) {
    const currentStatus = element.closest('tr').getAttribute('data-status');
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';
    
    try {
        const response = await fetch('index.php?page=anuncios-update-field', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                ad_id: adId,
                field: 'status',
                value: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            element.className = 'status-badge ' + newStatus;
            element.innerHTML = newStatus === 'active' ? '✓ Ativo' : '⏸ Pausado';
            element.closest('tr').setAttribute('data-status', newStatus);
        }
    } catch (error) {
        alert('❌ Erro: ' + error.message);
    }
}

// Get preview
function getPreview(adId) {
    window.open('index.php?page=anuncio-preview&id=' + adId, '_blank');
}

// Filtros
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const campaign = document.getElementById('campaignFilter').value;
    const adset = document.getElementById('adsetFilter').value;
    
    const rows = document.querySelectorAll('#adsTable tbody tr[data-name]');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        const rowCampaign = row.getAttribute('data-campaign') || '';
        const rowAdset = row.getAttribute('data-adset') || '';
        
        const matchName = !search || name.includes(search);
        const matchStatus = !status || rowStatus === status;
        const matchCampaign = !campaign || rowCampaign === campaign;
        const matchAdset = !adset || rowAdset === adset;
        
        row.style.display = matchName && matchStatus && matchCampaign && matchAdset ? '' : 'none';
    });
}
</script>