<!-- 
    UTMTrack - Lista de Anúncios
    Arquivo: app/views/ads/index.php
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

.ads-table {
    width: 100%;
    border-collapse: collapse;
}

.ads-table th,
.ads-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #1e293b;
}

.ads-table th {
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

.ads-table td {
    color: #cbd5e1;
    font-size: 14px;
}

.ads-table tbody tr {
    transition: background-color 0.2s;
}

.ads-table tbody tr:hover {
    background: rgba(100, 116, 139, 0.08);
    cursor: pointer;
}

.ad-name {
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
}

.ad-id {
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
    <a href="index.php?page=campanhas-meta&account=<?= $adset['ad_account_id'] ?? '' ?>">Campanhas</a>
    <span>›</span>
    <a href="index.php?page=conjuntos&campaign=<?= $adset['campaign_db_id'] ?>">Conjuntos</a>
    <span>›</span>
    <span><?= htmlspecialchars($adset['adset_name']) ?></span>
</div>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">📝 Anúncios</h1>
        <p style="color: #94a3b8; margin-top: 8px;">
            Conjunto: <strong style="color: white;"><?= htmlspecialchars($adset['adset_name']) ?></strong>
        </p>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <button onclick="syncAds()" class="btn" style="background: #10b981; color: white;">
            🔄 Sincronizar
        </button>
        <a href="index.php?page=conjunto-detalhes&id=<?= $adset['id'] ?>" class="btn" style="background: #334155; color: white; text-decoration: none;">
            ← Voltar
        </a>
    </div>
</div>

<!-- Métricas Resumidas -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-label">📝 Total de Anúncios</div>
        <div class="metric-value"><?= number_format($stats['total_ads'] ?? 0, 0, ',', '.') ?></div>
        <div class="metric-info">
            <?= $stats['active_ads'] ?? 0 ?> ativos
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
        <div class="metric-label">📊 Alcance Médio</div>
        <div class="metric-value"><?= number_format($stats['avg_reach'] ?? 0, 0, ',', '.') ?></div>
        <div class="metric-info">
            Frequência: <?= number_format($stats['avg_frequency'] ?? 0, 2, ',', '.') ?>
        </div>
    </div>
</div>

<!-- Tabela de Anúncios -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📋 Lista de Anúncios</h2>
        <div style="color: #94a3b8; font-size: 13px;">
            <?= count($ads) ?> anúncio(s)
        </div>
    </div>
    
    <?php if (empty($ads)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📝</div>
        <div class="empty-state-title">Nenhum anúncio encontrado</div>
        <p style="color: #64748b;">Sincronize o conjunto para importar os anúncios</p>
        <button onclick="syncAds()" class="btn btn-primary" style="margin-top: 20px;">
            🔄 Sincronizar Agora
        </button>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="ads-table">
            <thead>
                <tr>
                    <th>Anúncio</th>
                    <th>Status</th>
                    <th>Gasto</th>
                    <th>Impressões</th>
                    <th>Cliques</th>
                    <th>CTR</th>
                    <th>CPC</th>
                    <th>Conversões</th>
                    <th>Alcance</th>
                    <th>Frequência</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ads as $ad): 
                    $ctr = $ad['impressions'] > 0 ? ($ad['clicks'] / $ad['impressions']) * 100 : 0;
                    $cpc = $ad['clicks'] > 0 ? $ad['spent'] / $ad['clicks'] : 0;
                ?>
                <tr onclick="viewAdDetails(<?= $ad['id'] ?>)">
                    <td>
                        <div class="ad-name">
                            <?= htmlspecialchars($ad['ad_name']) ?>
                        </div>
                        <div class="ad-id">
                            ID: <?= htmlspecialchars($ad['ad_id']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge-<?= $ad['status'] === 'active' ? 'success' : ($ad['status'] === 'paused' ? 'warning' : 'danger') ?>">
                            <?php
                            $statusLabels = [
                                'active' => '✓ Ativo',
                                'paused' => '⏸ Pausado',
                                'deleted' => '🗑 Deletado'
                            ];
                            echo $statusLabels[$ad['status']] ?? $ad['status'];
                            ?>
                        </span>
                    </td>
                    <td style="font-weight: 600;">R$ <?= number_format($ad['spent'], 2, ',', '.') ?></td>
                    <td><?= number_format($ad['impressions'], 0, ',', '.') ?></td>
                    <td><?= number_format($ad['clicks'], 0, ',', '.') ?></td>
                    <td><?= number_format($ctr, 2, ',', '.') ?>%</td>
                    <td>R$ <?= number_format($cpc, 2, ',', '.') ?></td>
                    <td><?= number_format($ad['conversions'], 0, ',', '.') ?></td>
                    <td><?= number_format($ad['reach'], 0, ',', '.') ?></td>
                    <td><?= number_format($ad['frequency'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
// Sincronizar anúncios
async function syncAds() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Sincronizando...';
    btn.disabled = true;
    
    try {
        const response = await fetch('index.php?page=anuncios-sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'adset_id=<?= $adset['id'] ?>'
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

// Ver detalhes do anúncio
function viewAdDetails(adId) {
    window.location.href = `index.php?page=anuncio-detalhes&id=${adId}`;
}
</script>