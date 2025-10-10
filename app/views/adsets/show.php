<!-- 
    UTMTrack - Detalhes do Conjunto de Anúncios
    Arquivo: app/views/adsets/show.php
-->

<style>
.performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.performance-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 20px;
}

.performance-label {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 8px;
}

.performance-value {
    font-size: 28px;
    font-weight: 700;
    color: white;
    margin-bottom: 8px;
}

.performance-info {
    font-size: 12px;
    color: #64748b;
}

.adset-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.adset-info h1 {
    color: white;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 12px;
}

.adset-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    color: #94a3b8;
    font-size: 13px;
}

.adset-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.status-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.status-badge.paused {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

.status-badge.deleted {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 20px;
}

.breadcrumb a {
    color: #667eea;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.ads-table {
    width: 100%;
    border-collapse: collapse;
}

.ads-table th,
.ads-table td {
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #1e293b;
}

.ads-table th {
    background: #0f172a;
    color: #e2e8f0;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
}

.ads-table td {
    color: #cbd5e1;
    font-size: 14px;
}

.ads-table tr:hover {
    background: rgba(100, 116, 139, 0.05);
    cursor: pointer;
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

<!-- Header -->
<div class="adset-header">
    <div class="adset-info">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
            <a href="index.php?page=conjuntos&campaign=<?= $adset['campaign_db_id'] ?>" class="btn" style="background: #334155; color: white; text-decoration: none;">
                ← Voltar
            </a>
            <span class="status-badge <?= $adset['status'] ?>">
                <?php
                $statusLabels = [
                    'active' => '✓ Ativo',
                    'paused' => '⏸ Pausado',
                    'deleted' => '🗑 Deletado'
                ];
                echo $statusLabels[$adset['status']] ?? $adset['status'];
                ?>
            </span>
        </div>
        
        <h1><?= htmlspecialchars($adset['adset_name']) ?></h1>
        
        <div class="adset-meta">
            <div class="adset-meta-item">
                <strong>ID:</strong> <?= htmlspecialchars($adset['adset_id']) ?>
            </div>
            <div class="adset-meta-item">
                <strong>Campanha:</strong> <?= htmlspecialchars($adset['campaign_name']) ?>
            </div>
            <div class="adset-meta-item">
                <strong>Conta:</strong> <?= htmlspecialchars($adset['account_name']) ?>
            </div>
            <?php if ($adset['optimization_goal']): ?>
            <div class="adset-meta-item">
                <strong>Objetivo:</strong> <?= htmlspecialchars($adset['optimization_goal']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <button onclick="window.location.href='index.php?page=anuncios&adset=<?= $adset['id'] ?>'" class="btn" style="background: #667eea; color: white;">
            📝 Ver Anúncios
        </button>
        <button onclick="window.print()" class="btn" style="background: #10b981; color: white;">
            🖨️ Imprimir
        </button>
    </div>
</div>

<!-- Métricas de Performance -->
<div class="performance-grid">
    <!-- Orçamento Diário -->
    <div class="performance-card">
        <div class="performance-label">💰 Orçamento Diário</div>
        <div class="performance-value">
            R$ <?= number_format($adset['daily_budget'], 2, ',', '.') ?>
        </div>
        <?php if ($adset['lifetime_budget'] > 0): ?>
        <div class="performance-info">
            Vitalício: R$ <?= number_format($adset['lifetime_budget'], 2, ',', '.') ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Gasto -->
    <div class="performance-card">
        <div class="performance-label">💵 Gasto Total</div>
        <div class="performance-value">
            R$ <?= number_format($adset['spent'], 2, ',', '.') ?>
        </div>
        <div class="performance-info">
            <?php 
            $percentBudget = $adset['daily_budget'] > 0 ? ($adset['spent'] / $adset['daily_budget']) * 100 : 0;
            echo number_format($percentBudget, 1, ',', '.') . '% do orçamento';
            ?>
        </div>
    </div>
    
    <!-- Impressões -->
    <div class="performance-card">
        <div class="performance-label">👁️ Impressões</div>
        <div class="performance-value">
            <?= number_format($adset['impressions'], 0, ',', '.') ?>
        </div>
        <div class="performance-info">
            CPM: R$ <?= $adset['impressions'] > 0 ? number_format(($adset['spent'] / $adset['impressions']) * 1000, 2, ',', '.') : '0,00' ?>
        </div>
    </div>
    
    <!-- Cliques -->
    <div class="performance-card">
        <div class="performance-label">👆 Cliques</div>
        <div class="performance-value">
            <?= number_format($adset['clicks'], 0, ',', '.') ?>
        </div>
        <div class="performance-info">
            CTR: <?= $adset['impressions'] > 0 ? number_format(($adset['clicks'] / $adset['impressions']) * 100, 2, ',', '.') : 0 ?>%
        </div>
    </div>
    
    <!-- CPC -->
    <div class="performance-card">
        <div class="performance-label">💵 CPC Médio</div>
        <div class="performance-value">
            R$ <?= $adset['clicks'] > 0 ? number_format($adset['spent'] / $adset['clicks'], 2, ',', '.') : '0,00' ?>
        </div>
    </div>
    
    <!-- Conversões -->
    <div class="performance-card">
        <div class="performance-label">🎯 Conversões</div>
        <div class="performance-value">
            <?= number_format($adset['conversions'], 0, ',', '.') ?>
        </div>
        <div class="performance-info">
            <?php 
            $convRate = $adset['clicks'] > 0 ? ($adset['conversions'] / $adset['clicks']) * 100 : 0;
            echo 'Taxa: ' . number_format($convRate, 2, ',', '.') . '%';
            ?>
        </div>
    </div>
</div>

<!-- Anúncios do Conjunto -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📝 Anúncios deste Conjunto</h2>
        <div style="color: #94a3b8; font-size: 13px;">
            <?= count($ads) ?> anúncio(s)
        </div>
    </div>
    
    <?php if (empty($ads)): ?>
    <div class="empty-state" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 64px; margin-bottom: 16px;">📝</div>
        <div style="font-size: 20px; color: #e2e8f0; margin-bottom: 8px; font-weight: 600;">
            Nenhum anúncio encontrado
        </div>
        <p style="color: #64748b;">Os anúncios aparecerão aqui após a sincronização</p>
        <button 
            onclick="window.location.href='index.php?page=anuncios&adset=<?= $adset['id'] ?>'" 
            class="btn btn-primary" 
            style="margin-top: 20px;"
        >
            📝 Ver Anúncios
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ads as $ad): 
                    $ctr = $ad['impressions'] > 0 ? ($ad['clicks'] / $ad['impressions']) * 100 : 0;
                    $cpc = $ad['clicks'] > 0 ? $ad['spent'] / $ad['clicks'] : 0;
                ?>
                <tr onclick="window.location.href='index.php?page=anuncio-detalhes&id=<?= $ad['id'] ?>'">
                    <td>
                        <div style="font-weight: 600; color: white;">
                            <?= htmlspecialchars($ad['ad_name']) ?>
                        </div>
                        <div style="font-size: 11px; color: #64748b; font-family: monospace;">
                            ID: <?= htmlspecialchars($ad['ad_id']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $ad['status'] === 'active' ? 'success' : ($ad['status'] === 'paused' ? 'warning' : 'danger') ?>" style="
                            display: inline-block;
                            padding: 4px 12px;
                            border-radius: 12px;
                            font-size: 11px;
                            font-weight: 600;
                        ">
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
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
.badge-success {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.badge-warning {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

.badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}
</style>