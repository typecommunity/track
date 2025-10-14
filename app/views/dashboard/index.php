<style>
/* Dashboard Inline Styles */
.dashboard-filters {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.filters-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-select {
    padding: 12px 16px;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 10px;
    color: #e2e8f0;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-select:hover {
    border-color: #667eea;
    background: #1e293b;
}

.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.update-btn {
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.update-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
}

.debug-btn {
    padding: 12px 24px;
    background: #ef4444;
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.debug-btn:hover {
    background: #dc2626;
    transform: translateY(-2px);
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 16px;
    padding: 24px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
}

.metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    border-color: #667eea;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.metric-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.metric-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-icon {
    width: 20px;
    height: 20px;
    color: #667eea;
}

.info-icon {
    width: 16px;
    height: 16px;
    color: #64748b;
    cursor: help;
    transition: color 0.2s;
}

.info-icon:hover {
    color: #94a3b8;
}

.metric-value {
    font-size: 32px;
    font-weight: 700;
    color: white;
    margin-bottom: 8px;
    line-height: 1;
}

.metric-value.positive {
    color: #10b981;
}

.metric-value.negative {
    color: #ef4444;
}

.metric-info {
    font-size: 13px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.data-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.data-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 28px;
    border-bottom: 1px solid #334155;
}

.data-card-title {
    font-size: 18px;
    font-weight: 700;
    color: white;
    display: flex;
    align-items: center;
    gap: 10px;
}

.data-card-title svg {
    width: 24px;
    height: 24px;
    color: #667eea;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: #0f172a;
}

.data-table th {
    text-align: left;
    padding: 16px 28px;
    color: #94a3b8;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #334155;
}

.data-table td {
    padding: 20px 28px;
    color: #cbd5e1;
    font-size: 14px;
    border-bottom: 1px solid rgba(51, 65, 85, 0.5);
}

.data-table tbody tr {
    transition: background 0.2s;
}

.data-table tbody tr:hover {
    background: rgba(100, 116, 139, 0.05);
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

.payment-method {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
}

.payment-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.payment-icon.pix {
    background: rgba(0, 184, 148, 0.1);
    color: #00b894;
}

.payment-icon.card {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.payment-icon.boleto {
    background: rgba(251, 191, 36, 0.1);
    color: #fbbf24;
}

.payment-icon.other {
    background: rgba(148, 163, 184, 0.1);
    color: #94a3b8;
}

.empty-state {
    text-align: center;
    padding: 60px 30px;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}

.empty-state-title {
    font-size: 18px;
    font-weight: 700;
    color: #e2e8f0;
    margin-bottom: 8px;
}

.empty-state-text {
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
}

.alert-warning {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.alert-icon {
    font-size: 32px;
}

.alert-content {
    flex: 1;
}

.alert-title {
    color: #1e293b;
    margin: 0 0 8px 0;
    font-weight: 700;
    font-size: 16px;
}

.alert-text {
    color: #374151;
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
}

.platform-badge {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
}

@media (max-width: 768px) {
    .filters-form {
        grid-template-columns: 1fr;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .alert-warning {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<!-- Filtros Modernos -->
<div class="dashboard-filters">
    <form method="GET" action="" class="filters-form">
        <input type="hidden" name="page" value="dashboard">
        
        <!-- Período -->
        <div class="filter-group">
            <label class="filter-label">
                <svg style="width: 14px; height: 14px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Período
            </label>
            <select name="period" class="filter-select">
                <option value="today" <?= ($metrics['period'] ?? 'today') === 'today' ? 'selected' : '' ?>>Hoje</option>
                <option value="yesterday" <?= ($metrics['period'] ?? '') === 'yesterday' ? 'selected' : '' ?>>Ontem</option>
                <option value="week" <?= ($metrics['period'] ?? '') === 'week' ? 'selected' : '' ?>>Últimos 7 dias</option>
                <option value="month" <?= ($metrics['period'] ?? '') === 'month' ? 'selected' : '' ?>>Este mês</option>
                <option value="maximum" <?= ($metrics['period'] ?? '') === 'maximum' ? 'selected' : '' ?>>Máximo (todos os dados)</option>
            </select>
        </div>
        
        <!-- Conta de Anúncio -->
        <div class="filter-group">
            <label class="filter-label">
                <svg style="width: 14px; height: 14px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 20V10M12 20V4M6 20v-6"></path>
                </svg>
                Conta de Anúncio
            </label>
            <select name="account" class="filter-select">
                <option value="">Todas as contas</option>
                <?php if (!empty($filterOptions['accounts'])): ?>
                    <?php foreach ($filterOptions['accounts'] as $account): ?>
                        <option value="<?= $account['id'] ?>" <?= ($metrics['account'] ?? '') == $account['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($account['account_name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        
        <!-- Fonte -->
        <div class="filter-group">
            <label class="filter-label">
                <svg style="width: 14px; height: 14px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                Fonte
            </label>
            <select name="source" class="filter-select">
                <option value="">Todas as fontes</option>
                <?php if (!empty($filterOptions['sources'])): ?>
                    <?php foreach ($filterOptions['sources'] as $source): ?>
                        <option value="<?= htmlspecialchars($source['utm_source']) ?>" <?= ($metrics['source'] ?? '') == $source['utm_source'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($source['utm_source']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        
        <!-- Plataforma -->
        <div class="filter-group">
            <label class="filter-label">
                <svg style="width: 14px; height: 14px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                Plataforma
            </label>
            <select name="platform" class="filter-select">
                <option value="">Todas</option>
                <?php if (!empty($filterOptions['platforms'])): ?>
                    <?php foreach ($filterOptions['platforms'] as $platform): ?>
                        <?php
                        $platformLabels = [
                            'meta' => 'Meta Ads',
                            'google' => 'Google Ads',
                            'tiktok' => 'TikTok Ads',
                            'kwai' => 'Kwai Ads'
                        ];
                        $label = $platformLabels[$platform['platform']] ?? ucfirst($platform['platform']);
                        ?>
                        <option value="<?= $platform['platform'] ?>" <?= ($metrics['platform'] ?? '') == $platform['platform'] ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        
        <!-- Produto -->
        <div class="filter-group">
            <label class="filter-label">
                <svg style="width: 14px; height: 14px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                Produto
            </label>
            <select name="product" class="filter-select">
                <option value="">Todos os produtos</option>
                <?php if (!empty($filterOptions['products'])): ?>
                    <?php foreach ($filterOptions['products'] as $product): ?>
                        <option value="<?= $product['id'] ?>" <?= ($metrics['product'] ?? '') == $product['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($product['name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        
        <button type="submit" class="update-btn">
            <svg style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
            </svg>
            Atualizar
        </button>
        
        <!-- Botão Debug - Descomente para usar -->
        <!--
        <button type="button" onclick="window.location.href='?page=dashboard&period=<?= $metrics['period'] ?? 'today' ?>&debug=1'" class="debug-btn">
            🔍 Debug
        </button>
        -->
    </form>
</div>

<!-- Alerta se usando dados do Meta - Descomente para usar -->
<!--
<?php if (!empty($metrics['usando_meta'])): ?>
<div class="alert-warning">
    <div class="alert-icon">⚠️</div>
    <div class="alert-content">
        <h4 class="alert-title">Usando dados do Meta Ads</h4>
        <p class="alert-text">
            Não foram encontradas vendas registradas no sistema para este período. As métricas exibidas são baseadas nas <strong>conversões reportadas pelo Meta Ads</strong> (<?= number_format($metrics['conversions_meta'], 0, ',', '.') ?> conversões, R$ <?= number_format($metrics['net_revenue'], 2, ',', '.') ?>).
            <br><br>
            💡 <strong>Dica:</strong> Configure webhooks para registrar vendas automaticamente e ter dados mais precisos. <a href="?page=help&action=webhooks" style="color: #1e293b; text-decoration: underline; font-weight: 600;">Ver como configurar →</a>
        </p>
    </div>
</div>
<?php endif; ?>
-->

<!-- Métricas Principais -->
<div class="metrics-grid">
    <!-- Faturamento Líquido -->
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Faturamento
            </div>
            <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" title="<?= !empty($metrics['usando_meta']) ? 'Valor de conversões do Meta Ads' : 'Receita total menos custos e taxas' ?>">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 16v-4M12 8h.01"></path>
            </svg>
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['net_revenue'], 2, ',', '.') ?>
        </div>
        <div class="metric-info">
            <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
            </svg>
            <?= $metrics['total_sales'] ?> <?= !empty($metrics['usando_meta']) ? 'conversões' : 'vendas aprovadas' ?>
        </div>
    </div>
    
    <!-- Gastos -->
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 20V10M12 20V4M6 20v-6"></path>
                </svg>
                Gastos
            </div>
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['ad_spend'], 2, ',', '.') ?>
        </div>
        <div class="metric-info"><?= $metrics['total_campaigns'] ?? 0 ?> campanha<?= ($metrics['total_campaigns'] ?? 0) != 1 ? 's' : '' ?> ativa<?= ($metrics['total_campaigns'] ?? 0) != 1 ? 's' : '' ?></div>
    </div>
    
    <!-- ROAS -->
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="20" x2="12" y2="10"></line>
                    <line x1="18" y1="20" x2="18" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="16"></line>
                </svg>
                ROAS
            </div>
            <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" title="Return on Ad Spend">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 16v-4M12 8h.01"></path>
            </svg>
        </div>
        <div class="metric-value <?= $metrics['roas'] >= 3 ? 'positive' : ($metrics['roas'] >= 1.5 ? '' : 'negative') ?>">
            <?= $metrics['roas'] > 0 ? number_format($metrics['roas'], 2, ',', '.') . 'x' : 'N/A' ?>
        </div>
        <div class="metric-info">
            <?= $metrics['roas'] >= 3 ? '✓ Excelente' : ($metrics['roas'] >= 1.5 ? 'OK' : 'Atenção') ?>
        </div>
    </div>
    
    <!-- Lucro -->
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Lucro
            </div>
        </div>
        <div class="metric-value <?= $metrics['profit'] >= 0 ? 'positive' : 'negative' ?>">
            R$ <?= number_format($metrics['profit'], 2, ',', '.') ?>
        </div>
        <div class="metric-info">
            <?= $metrics['profit'] >= 0 ? '↑ Positivo' : '↓ Negativo' ?>
        </div>
    </div>
    
    <!-- ROI -->
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                ROI
            </div>
            <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" title="Return on Investment">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 16v-4M12 8h.01"></path>
            </svg>
        </div>
        <div class="metric-value <?= $metrics['roi'] > 0 ? 'positive' : 'negative' ?>">
            <?= $metrics['roi'] > 0 ? number_format($metrics['roi'], 2, ',', '.') . '%' : 'N/A' ?>
        </div>
        <div class="metric-info">Retorno sobre investimento</div>
    </div>
    
    <!-- Margem -->
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3v18h18"></path>
                    <path d="M18 17V9M13 17v-3M8 17v-6"></path>
                </svg>
                Margem
            </div>
        </div>
        <div class="metric-value">
            <?= number_format($metrics['margin'], 2, ',', '.') ?>%
        </div>
        <div class="metric-info">Margem de lucro</div>
    </div>
    
    <!-- Impressões -->
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                Impressões
            </div>
        </div>
        <div class="metric-value" style="font-size: 28px;">
            <?= number_format($metrics['impressions'] ?? 0, 0, ',', '.') ?>
        </div>
        <div class="metric-info">Visualizações dos anúncios</div>
    </div>
    
    <!-- Cliques -->
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 3v18m0 0l-7-7m7 7l7-7"></path>
                </svg>
                Cliques
            </div>
        </div>
        <div class="metric-value" style="font-size: 28px;">
            <?= number_format($metrics['clicks'] ?? 0, 0, ',', '.') ?>
        </div>
        <div class="metric-info">CTR: <?= number_format($metrics['ctr'] ?? 0, 2, ',', '.') ?>%</div>
    </div>
    
    <!-- CPC -->
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                </svg>
                CPC
            </div>
            <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" title="Custo Por Clique">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 16v-4M12 8h.01"></path>
            </svg>
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['cpc'] ?? 0, 2, ',', '.') ?>
        </div>
        <div class="metric-info">Custo por clique</div>
    </div>
    
    <!-- Checkouts (se houver) -->
    <?php if (!empty($metrics['checkouts_meta']) && $metrics['checkouts_meta'] > 0): ?>
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                Checkouts
            </div>
        </div>
        <div class="metric-value" style="color: #fbbf24;">
            <?= number_format($metrics['checkouts_meta'], 0, ',', '.') ?>
        </div>
        <div class="metric-info">Iniciaram compra (Meta)</div>
    </div>
    <?php endif; ?>
    
    <!-- Vendas Pendentes (só se houver vendas reais) -->
    <?php if (empty($metrics['usando_meta']) && !empty($metrics['pending_sales']) && $metrics['pending_sales'] > 0): ?>
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Pendentes
            </div>
        </div>
        <div class="metric-value" style="color: #fbbf24;">
            R$ <?= number_format($metrics['pending_sales'], 2, ',', '.') ?>
        </div>
        <div class="metric-info">Aguardando aprovação</div>
    </div>
    <?php endif; ?>
    
    <!-- Reembolsos (só se houver vendas reais) -->
    <?php if (empty($metrics['usando_meta']) && !empty($metrics['refunded_sales']) && $metrics['refunded_sales'] > 0): ?>
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
                Reembolsos
            </div>
        </div>
        <div class="metric-value" style="color: #ef4444;">
            R$ <?= number_format($metrics['refunded_sales'], 2, ',', '.') ?>
        </div>
        <div class="metric-info">Vendas reembolsadas</div>
    </div>
    <?php endif; ?>
    
    <!-- Impostos (só se houver) -->
    <?php if (empty($metrics['usando_meta']) && !empty($metrics['tax']) && $metrics['tax'] > 0): ?>
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 20h20M5 20V10l7-7 7 7v10M9 20v-6h6v6"></path>
                </svg>
                Impostos
            </div>
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['tax'], 2, ',', '.') ?>
        </div>
        <div class="metric-info">Taxas e impostos</div>
    </div>
    <?php endif; ?>
    
    <!-- Custos (só se houver) -->
    <?php if (empty($metrics['usando_meta']) && !empty($metrics['cost']) && $metrics['cost'] > 0): ?>
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">
                <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                Custos
            </div>
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['cost'], 2, ',', '.') ?>
        </div>
        <div class="metric-info">Custo dos produtos</div>
    </div>
    <?php endif; ?>
</div>

<!-- Top Campanhas -->
<?php if (!empty($topCampaigns) && count($topCampaigns) > 0): ?>
<div class="data-card">
    <div class="data-card-header">
        <h2 class="data-card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            Top Campanhas por Investimento
        </h2>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Campanha</th>
                <th>Plataforma</th>
                <th>Investido</th>
                <th>Impressões</th>
                <th>Cliques</th>
                <th>Conversões</th>
                <th>Receita</th>
                <th>ROAS</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topCampaigns as $campaign): ?>
            <tr>
                <td style="font-weight: 600; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                    title="<?= htmlspecialchars($campaign['campaign_name']) ?>">
                    <?= htmlspecialchars($campaign['campaign_name']) ?>
                </td>
                <td>
                    <span class="platform-badge">
                        <?= $campaign['platform'] === 'meta' ? 'Meta Ads' : ucfirst($campaign['platform']) ?>
                    </span>
                </td>
                <td style="font-weight: 600;">R$ <?= number_format($campaign['spent'], 2, ',', '.') ?></td>
                <td><?= number_format($campaign['impressions'] ?? 0, 0, ',', '.') ?></td>
                <td><?= number_format($campaign['clicks'] ?? 0, 0, ',', '.') ?></td>
                <td><?= number_format($campaign['conversions'] ?? 0, 0, ',', '.') ?></td>
                <td style="font-weight: 700; color: #10b981;">
                    R$ <?= number_format($campaign['real_revenue'] > 0 ? $campaign['real_revenue'] : ($campaign['purchase_value'] ?? 0), 2, ',', '.') ?>
                </td>
                <td>
                    <span style="font-weight: 700; color: <?= $campaign['roas'] >= 3 ? '#10b981' : ($campaign['roas'] >= 1.5 ? '#fbbf24' : '#ef4444') ?>">
                        <?= $campaign['roas'] > 0 ? number_format($campaign['roas'], 2, ',', '.') . 'x' : '-' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Vendas por Pagamento -->
<div class="data-card">
    <div class="data-card-header">
        <h2 class="data-card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            Vendas por Método de Pagamento
        </h2>
    </div>
    
    <?php if (empty($salesByPayment)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
        </div>
        <div class="empty-state-title">Nenhuma venda ainda</div>
        <p class="empty-state-text">
            <?php if (!empty($metrics['usando_meta'])): ?>
                Configure webhooks para começar a registrar vendas automaticamente
            <?php else: ?>
                Suas vendas aparecerão aqui quando forem registradas
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Método de Pagamento</th>
                <th>Total de Vendas</th>
                <th>Receita Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $paymentConfig = [
                'pix' => ['label' => 'Pix', 'class' => 'pix', 'icon' => '💰'],
                'credit_card' => ['label' => 'Cartão de Crédito', 'class' => 'card', 'icon' => '💳'],
                'boleto' => ['label' => 'Boleto', 'class' => 'boleto', 'icon' => '📄'],
                'other' => ['label' => 'Outros', 'class' => 'other', 'icon' => '📋']
            ];
            foreach ($salesByPayment as $payment): 
                $config = $paymentConfig[$payment['payment_method']] ?? $paymentConfig['other'];
            ?>
            <tr>
                <td>
                    <div class="payment-method">
                        <div class="payment-icon <?= $config['class'] ?>">
                            <?= $config['icon'] ?>
                        </div>
                        <?= $config['label'] ?>
                    </div>
                </td>
                <td style="font-weight: 600;"><?= number_format($payment['total'], 0, ',', '.') ?></td>
                <td style="font-weight: 700; color: #10b981;">R$ <?= number_format($payment['revenue'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Vendas por Produto -->
<div class="data-card">
    <div class="data-card-header">
        <h2 class="data-card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            Top Produtos
        </h2>
    </div>
    
    <?php if (empty($salesByProduct)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            </svg>
        </div>
        <div class="empty-state-title">Nenhum produto vendido</div>
        <p class="empty-state-text">
            <?php if (!empty($metrics['usando_meta'])): ?>
                Configure webhooks para começar a registrar vendas por produto
            <?php else: ?>
                Configure seus produtos para começar a rastrear vendas
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Vendas</th>
                <th>Receita</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($salesByProduct as $product): ?>
            <tr>
                <td style="font-weight: 600;"><?= htmlspecialchars($product['product_name'] ?? 'Sem nome') ?></td>
                <td><?= number_format($product['total'], 0, ',', '.') ?></td>
                <td style="font-weight: 700; color: #10b981;">R$ <?= number_format($product['revenue'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>