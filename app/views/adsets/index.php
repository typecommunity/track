<!-- 
    UTMTrack - Lista de Conjuntos de Anúncios
    Arquivo: app/views/adsets/index.php
-->

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: white;
    margin: 0;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 10px;
}

.breadcrumb a {
    color: #667eea;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 20px;
}

.metric-label {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 8px;
}

.metric-value {
    font-size: 28px;
    font-weight: 700;
    color: white;
    margin-bottom: 8px;
}

.metric-info {
    font-size: 12px;
    color: #64748b;
}

.adsets-table {
    width: 100%;
    border-collapse: collapse;
}

.adsets-table th,
.adsets-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #1e293b;
}

.adsets-table th {
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

.adsets-table td {
    color: #cbd5e1;
    font-size: 14px;
}

.adsets-table tbody tr {
    transition: background-color 0.2s;
}

.adsets-table tbody tr:hover {
    background: rgba(100, 116, 139, 0.08);
    cursor: pointer;
}

.adset-name {
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
}

.adset-id {
    font-size: 11px;
    color: #64748b;
    font-family: monospace;
}

.badge-success {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge-warning {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

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
    color: #e2e8f0;
    margin-bottom: 8px;
    font-weight: 600;
}
</style>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php?page=integracoes-meta-contas">Contas</a>
    <span>›</span>
    <a href="index.php?page=campanhas-meta&account=<?= $campaign['ad_account_id'] ?>">Campanhas</a>
    <span>›</span>
    <span><?= htmlspecialchars($campaign['campaign_name']) ?></span>
</div>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">🎯 Conjuntos de Anúncios</h1>
        <p style="color: #94a3b8; margin-top: 8px;">
            Campanha: <strong style="color: white;"><?= htmlspecialchars($campaign['campaign_name']) ?></strong>
        </p>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <button onclick="syncAdSets()" class="btn" style="background: #10b981; color: white;">
            🔄 Sincronizar
        </button>
        <a href="index.php?page=campanha-detalhes&id=<?= $campaign['id'] ?>" class="btn" style="background: #334155; color: white; text-decoration: none;">
            ← Voltar
        </a>
    </div>
</div>

<!-- Métricas Resumidas -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-label">📊 Total de Conjuntos</div>
        <div class="metric-value"><?= number_format($stats['total_adsets'] ?? 0, 0, ',', '.') ?></div>
        <div class="metric-info">
            <?= $stats['active_adsets'] ?? 0 ?> ativos
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

<!-- Tabela de Conjuntos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📋 Lista de Conjuntos</h2>
        <div style="color: #94a3b8; font-size: 13px;">
            <?= count($adsets) ?> conjunto(s)
        </div>
    </div>
    
    <?php if (empty($adsets)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎯</div>
        <div class="empty-state-title">Nenhum conjunto encontrado</div>
        <p style="color: #64748b;">Sincronize a campanha para importar os conjuntos de anúncios</p>
        <button onclick="syncAdSets()" class="btn btn-primary" style="margin-top: 20px;">
            🔄 Sincronizar Agora
        </button>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="adsets-table">
            <thead>
                <tr>
                    <th>Conjunto</th>
                    <th>Status</th>
                    <th>Objetivo</th>
                    <th>Orçamento Diário</th>
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
                <?php foreach ($adsets as $adset): 
                    $ctr = $adset['impressions'] > 0 ? ($adset['clicks'] / $adset['impressions']) * 100 : 0;
                    $cpc = $adset['clicks'] > 0 ? $adset['spent'] / $adset['clicks'] : 0;
                ?>
                <tr onclick="viewAdSetDetails(<?= $adset['id'] ?>)">
                    <td>
                        <div class="adset-name">
                            <?= htmlspecialchars($adset['adset_name']) ?>
                        </div>
                        <div class="adset-id">
                            ID: <?= htmlspecialchars($adset['adset_id']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge-<?= $adset['status'] === 'active' ? 'success' : ($adset['status'] === 'paused' ? 'warning' : 'danger') ?>">
                            <?php
                            $statusLabels = [
                                'active' => '✓ Ativo',
                                'paused' => '⏸ Pausado',
                                'deleted' => '🗑 Deletado'
                            ];
                            echo $statusLabels[$adset['status']] ?? $adset['status'];
                            ?>
                        </span>
                    </td>
                    <td style="font-size: 12px;">
                        <?= htmlspecialchars($adset['optimization_goal'] ?? '-') ?>
                    </td>
                    <td>R$ <?= number_format($adset['daily_budget'], 2, ',', '.') ?></td>
                    <td style="font-weight: 600;">R$ <?= number_format($adset['spent'], 2, ',', '.') ?></td>
                    <td><?= number_format($adset['impressions'], 0, ',', '.') ?></td>
                    <td><?= number_format($adset['clicks'], 0, ',', '.') ?></td>
                    <td><?= number_format($ctr, 2, ',', '.') ?>%</td>
                    <td>R$ <?= number_format($cpc, 2, ',', '.') ?></td>
                    <td><?= number_format($adset['conversions'], 0, ',', '.') ?></td>
                    <td>
                        <button 
                            onclick="event.stopPropagation(); viewAds(<?= $adset['id'] ?>);" 
                            class="btn" 
                            style="padding: 6px 12px; font-size: 12px; background: #667eea; color: white;"
                            title="Ver Anúncios"
                        >
                            📝 Anúncios
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
// Sincronizar conjuntos
async function syncAdSets() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Sincronizando...';
    btn.disabled = true;
    
    try {
        const response = await fetch('index.php?page=conjuntos-sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'campaign_id=<?= $campaign['id'] ?>'
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

// Ver detalhes do conjunto
function viewAdSetDetails(adsetId) {
    window.location.href = `index.php?page=conjunto-detalhes&id=${adsetId}`;
}

// Ver anúncios do conjunto
function viewAds(adsetId) {
    window.location.href = `index.php?page=anuncios&adset=${adsetId}`;
}
</script>