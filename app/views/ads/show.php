<!-- 
    UTMTrack - Detalhes do Anúncio
    Arquivo: app/views/ads/show.php
-->

<style>
.performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
    display: flex;
    align-items: center;
    gap: 6px;
}

.performance-value {
    font-size: 32px;
    font-weight: 700;
    color: white;
    margin-bottom: 8px;
}

.performance-value.positive {
    color: #10b981;
}

.performance-value.negative {
    color: #ef4444;
}

.performance-info {
    font-size: 12px;
    color: #64748b;
}

.ad-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.ad-info h1 {
    color: white;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 12px;
}

.ad-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    color: #94a3b8;
    font-size: 13px;
}

.ad-meta-item {
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

.info-section {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-label {
    font-size: 12px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 16px;
    color: white;
    font-weight: 600;
}
</style>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php?page=integracoes-meta-contas">Contas</a>
    <span>›</span>
    <a href="index.php?page=campanhas-meta&account=<?= $ad['account_id'] ?? '' ?>">Campanhas</a>
    <span>›</span>
    <a href="index.php?page=conjuntos&campaign=<?= $ad['campaign_db_id'] ?>">Conjuntos</a>
    <span>›</span>
    <a href="index.php?page=anuncios&adset=<?= $ad['adset_db_id'] ?>">Anúncios</a>
    <span>›</span>
    <span><?= htmlspecialchars($ad['ad_name']) ?></span>
</div>

<!-- Header -->
<div class="ad-header">
    <div class="ad-info">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
            <a href="index.php?page=anuncios&adset=<?= $ad['adset_db_id'] ?>" class="btn" style="background: #334155; color: white; text-decoration: none;">
                ← Voltar
            </a>
            <span class="status-badge <?= $ad['status'] ?>">
                <?php
                $statusLabels = [
                    'active' => '✓ Ativo',
                    'paused' => '⏸ Pausado',
                    'deleted' => '🗑 Deletado'
                ];
                echo $statusLabels[$ad['status']] ?? $ad['status'];
                ?>
            </span>
        </div>
        
        <h1><?= htmlspecialchars($ad['ad_name']) ?></h1>
        
        <div class="ad-meta">
            <div class="ad-meta-item">
                <strong>ID:</strong> <?= htmlspecialchars($ad['ad_id']) ?>
            </div>
            <div class="ad-meta-item">
                <strong>Conjunto:</strong> <?= htmlspecialchars($ad['adset_name']) ?>
            </div>
            <div class="ad-meta-item">
                <strong>Campanha:</strong> <?= htmlspecialchars($ad['campaign_name']) ?>
            </div>
            <div class="ad-meta-item">
                <strong>Conta:</strong> <?= htmlspecialchars($ad['account_name']) ?>
            </div>
        </div>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn" style="background: #10b981; color: white;">
            🖨️ Imprimir
        </button>
    </div>
</div>

<!-- Métricas Principais -->
<div class="performance-grid">
    <!-- Gasto -->
    <div class="performance-card">
        <div class="performance-label">💰 Gasto Total</div>
        <div class="performance-value">
            R$ <?= number_format($ad['spent'], 2, ',', '.') ?>
        </div>
    </div>
    
    <!-- Impressões -->
    <div class="performance-card">
        <div class="performance-label">👁️ Impressões</div>
        <div class="performance-value">
            <?= number_format($ad['impressions'], 0, ',', '.') ?>
        </div>
        <div class="performance-info">
            CPM: R$ <?= number_format($metrics['cpm'], 2, ',', '.') ?>
        </div>
    </div>
    
    <!-- Cliques -->
    <div class="performance-card">
        <div class="performance-label">👆 Cliques</div>
        <div class="performance-value">
            <?= number_format($ad['clicks'], 0, ',', '.') ?>
        </div>
        <div class="performance-info">
            CTR: <?= number_format($metrics['ctr'], 2, ',', '.') ?>%
        </div>
    </div>
    
    <!-- CPC -->
    <div class="performance-card">
        <div class="performance-label">💵 CPC Médio</div>
        <div class="performance-value">
            R$ <?= number_format($metrics['cpc'], 2, ',', '.') ?>
        </div>
    </div>
</div>

<!-- Métricas de Conversão -->
<div class="performance-grid">
    <!-- Conversões -->
    <div class="performance-card">
        <div class="performance-label">🎯 Conversões</div>
        <div class="performance-value">
            <?= number_format($ad['conversions'], 0, ',', '.') ?>
        </div>
        <div class="performance-info">
            Taxa: <?= number_format($metrics['conversion_rate'], 2, ',', '.') ?>%
        </div>
    </div>
    
    <!-- Custo por Conversão -->
    <div class="performance-card">
        <div class="performance-label">💸 Custo por Conversão</div>
        <div class="performance-value <?= $metrics['cost_per_conversion'] > 0 ? '' : 'positive' ?>">
            R$ <?= number_format($metrics['cost_per_conversion'], 2, ',', '.') ?>
        </div>
    </div>
    
    <!-- Alcance -->
    <div class="performance-card">
        <div class="performance-label">📊 Alcance</div>
        <div class="performance-value">
            <?= number_format($ad['reach'], 0, ',', '.') ?>
        </div>
        <div class="performance-info">
            Pessoas únicas alcançadas
        </div>
    </div>
    
    <!-- Frequência -->
    <div class="performance-card">
        <div class="performance-label">🔁 Frequência</div>
        <div class="performance-value">
            <?= number_format($ad['frequency'], 2, ',', '.') ?>
        </div>
        <div class="performance-info">
            Vezes que cada pessoa viu
        </div>
    </div>
</div>

<!-- Informações Adicionais -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📋 Informações do Anúncio</h2>
    </div>
    
    <div style="padding: 24px;">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">ID do Anúncio</div>
                <div class="info-value" style="font-family: monospace; font-size: 14px;">
                    <?= htmlspecialchars($ad['ad_id']) ?>
                </div>
            </div>
            
            <?php if ($ad['creative_id']): ?>
            <div class="info-item">
                <div class="info-label">ID do Criativo</div>
                <div class="info-value" style="font-family: monospace; font-size: 14px;">
                    <?= htmlspecialchars($ad['creative_id']) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="status-badge <?= $ad['status'] ?>">
                        <?php
                        $statusLabels = [
                            'active' => '✓ Ativo',
                            'paused' => '⏸ Pausado',
                            'deleted' => '🗑 Deletado'
                        ];
                        echo $statusLabels[$ad['status']] ?? $ad['status'];
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Última Sincronização</div>
                <div class="info-value">
                    <?= date('d/m/Y H:i', strtotime($ad['last_sync'])) ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Data de Criação</div>
                <div class="info-value">
                    <?= date('d/m/Y H:i', strtotime($ad['created_at'])) ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Última Atualização</div>
                <div class="info-value">
                    <?= date('d/m/Y H:i', strtotime($ad['updated_at'])) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumo de Performance -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h2 class="card-title">📊 Resumo de Performance</h2>
    </div>
    
    <div style="padding: 24px;">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Investimento Total</div>
                <div class="info-value" style="color: #ef4444;">
                    R$ <?= number_format($ad['spent'], 2, ',', '.') ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Impressões Geradas</div>
                <div class="info-value" style="color: #667eea;">
                    <?= number_format($ad['impressions'], 0, ',', '.') ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Cliques Obtidos</div>
                <div class="info-value" style="color: #10b981;">
                    <?= number_format($ad['clicks'], 0, ',', '.') ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Taxa de Clique (CTR)</div>
                <div class="info-value" style="color: #fbbf24;">
                    <?= number_format($metrics['ctr'], 2, ',', '.') ?>%
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Custo por Clique (CPC)</div>
                <div class="info-value">
                    R$ <?= number_format($metrics['cpc'], 2, ',', '.') ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Conversões</div>
                <div class="info-value" style="color: #10b981;">
                    <?= number_format($ad['conversions'], 0, ',', '.') ?>
                </div>
            </div>
        </div>
        
        <?php if ($ad['conversions'] > 0): ?>
        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #334155;">
            <h3 style="color: #e2e8f0; font-size: 16px; margin-bottom: 16px;">
                💡 Análise Rápida
            </h3>
            <div style="color: #94a3b8; line-height: 1.6;">
                <?php
                $analysis = [];
                
                if ($metrics['ctr'] > 2) {
                    $analysis[] = "✅ <strong style='color: #10b981;'>CTR excelente</strong> - O anúncio está muito atrativo para o público.";
                } elseif ($metrics['ctr'] > 1) {
                    $analysis[] = "✓ <strong style='color: #fbbf24;'>CTR bom</strong> - Desempenho dentro da média.";
                } else {
                    $analysis[] = "⚠️ <strong style='color: #ef4444;'>CTR baixo</strong> - Considere otimizar o criativo ou segmentação.";
                }
                
                if ($metrics['conversion_rate'] > 5) {
                    $analysis[] = "✅ <strong style='color: #10b981;'>Alta taxa de conversão</strong> - Anúncio muito efetivo!";
                } elseif ($metrics['conversion_rate'] > 2) {
                    $analysis[] = "✓ <strong style='color: #fbbf24;'>Taxa de conversão satisfatória</strong>.";
                } else {
                    $analysis[] = "⚠️ <strong style='color: #ef4444;'>Taxa de conversão baixa</strong> - Revise a landing page.";
                }
                
                if ($ad['frequency'] > 3) {
                    $analysis[] = "⚠️ <strong style='color: #ef4444;'>Frequência alta</strong> - Público pode estar saturado.";
                }
                
                echo implode('<br>', $analysis);
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>