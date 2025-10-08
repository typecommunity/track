<!-- Botão Voltar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=utms" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar para UTMs
    </a>
</div>

<!-- Estatísticas Gerais -->
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
    
    <div class="metric-card">
        <div class="metric-label">
            📊 Média de Cliques
        </div>
        <div class="metric-value">
            <?= number_format($stats['avg_clicks'] ?? 0, 1, ',', '.') ?>
        </div>
        <div class="metric-info">
            por UTM
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            🏆 Máximo de Cliques
        </div>
        <div class="metric-value">
            <?= number_format($stats['max_clicks'] ?? 0, 0, ',', '.') ?>
        </div>
        <div class="metric-info">
            em uma única UTM
        </div>
    </div>
</div>

<!-- Top Campanhas -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">🎯 Top 10 Campanhas</h2>
    </div>
    
    <?php if (empty($topCampaigns)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <div class="empty-state-title">Nenhuma campanha ainda</div>
        <p>Crie UTMs com campanhas para ver estatísticas aqui</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Campanha</th>
                <th>Total de UTMs</th>
                <th>Total de Cliques</th>
                <th>Média de Cliques</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $position = 1;
            foreach ($topCampaigns as $campaign): 
                $avgClicks = $campaign['total_utms'] > 0 ? $campaign['total_clicks'] / $campaign['total_utms'] : 0;
            ?>
            <tr>
                <td style="font-weight: bold; color: #667eea;">#<?= $position++ ?></td>
                <td><?= htmlspecialchars($campaign['utm_campaign']) ?></td>
                <td><?= number_format($campaign['total_utms'], 0, ',', '.') ?></td>
                <td><?= number_format($campaign['total_clicks'], 0, ',', '.') ?></td>
                <td><?= number_format($avgClicks, 1, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Top Sources -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📱 Top 10 Fontes (Sources)</h2>
    </div>
    
    <?php if (empty($topSources)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <div class="empty-state-title">Nenhuma fonte ainda</div>
        <p>Crie UTMs com fontes para ver estatísticas aqui</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fonte</th>
                <th>Total de UTMs</th>
                <th>Total de Cliques</th>
                <th>Média de Cliques</th>
                <th>Performance</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $position = 1;
            $maxClicks = $topSources[0]['total_clicks'] ?? 1;
            foreach ($topSources as $source): 
                $avgClicks = $source['total_utms'] > 0 ? $source['total_clicks'] / $source['total_utms'] : 0;
                $performance = $maxClicks > 0 ? ($source['total_clicks'] / $maxClicks) * 100 : 0;
            ?>
            <tr>
                <td style="font-weight: bold; color: #667eea;">#<?= $position++ ?></td>
                <td><?= htmlspecialchars($source['utm_source']) ?></td>
                <td><?= number_format($source['total_utms'], 0, ',', '.') ?></td>
                <td><?= number_format($source['total_clicks'], 0, ',', '.') ?></td>
                <td><?= number_format($avgClicks, 1, ',', '.') ?></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; height: 8px; background: #334155; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); width: <?= $performance ?>%; transition: width 0.3s;"></div>
                        </div>
                        <span style="font-size: 12px; color: #94a3b8; min-width: 45px;">
                            <?= number_format($performance, 0) ?>%
                        </span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<style>
.metric-card {
    position: relative;
    overflow: hidden;
}

.metric-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
    pointer-events: none;
}
</style>