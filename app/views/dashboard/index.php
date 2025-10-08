<style>
/* Dashboard Styles */
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

/* Metrics Grid */
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

.metric-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.metric-trend.up {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.metric-trend.down {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

/* Tables Card */
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

/* Empty State */
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

/* Responsivo */
@media (max-width: 768px) {
    .filters-form {
        grid-template-columns: 1fr;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .data-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
}
</style>

<!-- Filtros Modernos -->
<div class="dashboard-filters">
    <form method="GET" action="" class="filters-form">
        <input type="hidden" name="page" value="dashboard">
        
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
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">
                <svg style="width: 14px; height: 14px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 20V10M12 20V4M6 20v-6"></path>
                </svg>
                Conta de Anúncio
            </label>
            <select name="account" class="filter-select">
                <option value="">Todas as contas</option>
            </select>
        </div>
        
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
            </select>
        </div>
        
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
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">
                <svg style="width: 14px; height: 14px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                Produto
            </label>
            <select name="product" class="filter-select">
                <option value="">Todos os produtos</option>
            </select>
        </div>
        
        <button type="submit" class="update-btn">
            <svg style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
            </svg>
            Atualizar
        </button>
    </form>
</div>

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
            <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" title="Receita total menos custos e taxas">
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
            <?= $metrics['total_sales'] ?> vendas aprovadas
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
        <div class="metric-info">Com anúncios</div>
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
    
    <!-- Vendas Pendentes -->
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
    
    <!-- Reembolsos -->
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
    
    <!-- Impostos -->
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
    
    <!-- Custos -->
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
</div>

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
        <p class="empty-state-text">Suas vendas aparecerão aqui quando forem registradas</p>
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
        <p class="empty-state-text">Configure seus produtos para começar a rastrear vendas</p>
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