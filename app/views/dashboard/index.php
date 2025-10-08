<!-- Filtros -->
<div class="filters-bar">
    <form method="GET" action="" style="display: flex; gap: 15px; align-items: flex-end; width: 100%;">
        <input type="hidden" name="page" value="dashboard">
        
        <div class="filter-group">
            <label class="filter-label">Período de Visualização</label>
            <select name="period">
                <option value="today" <?= ($metrics['period'] ?? 'today') === 'today' ? 'selected' : '' ?>>Hoje</option>
                <option value="yesterday" <?= ($metrics['period'] ?? '') === 'yesterday' ? 'selected' : '' ?>>Ontem</option>
                <option value="week" <?= ($metrics['period'] ?? '') === 'week' ? 'selected' : '' ?>>Últimos 7 dias</option>
                <option value="month" <?= ($metrics['period'] ?? '') === 'month' ? 'selected' : '' ?>>Este mês</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">Conta de Anúncio</label>
            <select name="account">
                <option value="">Qualquer</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">Fonte de Tráfego</label>
            <select name="source">
                <option value="">Qualquer</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">Plataforma</label>
            <select name="platform">
                <option value="">Qualquer</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label class="filter-label">Produto</label>
            <select name="product">
                <option value="">Qualquer</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary update-btn">
            Atualizar
        </button>
    </form>
</div>

<!-- Métricas Principais -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-label">
            💰 Faturamento Líquido
            <span class="info-icon" title="Receita total menos custos e taxas">ℹ️</span>
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['net_revenue'], 2, ',', '.') ?>
        </div>
        <div class="metric-info">
            <?= $metrics['total_sales'] ?> vendas aprovadas
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            📊 Gastos com anúncios
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['ad_spend'], 2, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            📈 ROAS
            <span class="info-icon" title="Return on Ad Spend">ℹ️</span>
        </div>
        <div class="metric-value">
            <?= $metrics['roas'] > 0 ? number_format($metrics['roas'], 2, ',', '.') : 'N/A' ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            💵 Lucro
        </div>
        <div class="metric-value" style="color: <?= $metrics['profit'] >= 0 ? '#10b981' : '#ef4444' ?>">
            R$ <?= number_format($metrics['profit'], 2, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            ⏳ Vendas Pendentes
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['pending_sales'], 2, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            📈 ROI
            <span class="info-icon" title="Return on Investment">ℹ️</span>
        </div>
        <div class="metric-value">
            <?= $metrics['roi'] > 0 ? number_format($metrics['roi'], 2, ',', '.') . '%' : 'N/A' ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            🔄 Vendas Reembolsadas
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['refunded_sales'], 2, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            📊 Margem
        </div>
        <div class="metric-value">
            <?= number_format($metrics['margin'], 2, ',', '.') ?>%
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            💳 Impostos
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['tax'], 2, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            🏷️ Custos de Produto
        </div>
        <div class="metric-value">
            R$ <?= number_format($metrics['cost'], 2, ',', '.') ?>
        </div>
    </div>
</div>

<!-- Vendas por Pagamento -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">💳 Vendas por Pagamento</h2>
    </div>
    
    <?php if (empty($salesByPayment)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <div class="empty-state-title">Nenhuma venda ainda</div>
        <p>Suas vendas aparecerão aqui quando forem registradas</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Método</th>
                <th>Vendas</th>
                <th>Receita</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $paymentLabels = [
                'pix' => '💰 Pix',
                'credit_card' => '💳 Cartão',
                'boleto' => '📄 Boleto',
                'other' => '📋 Outros'
            ];
            foreach ($salesByPayment as $payment): 
            ?>
            <tr>
                <td><?= $paymentLabels[$payment['payment_method']] ?? $payment['payment_method'] ?></td>
                <td><?= number_format($payment['total'], 0, ',', '.') ?></td>
                <td>R$ <?= number_format($payment['revenue'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Vendas por Produto -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏷️ Vendas por Produto</h2>
    </div>
    
    <?php if (empty($salesByProduct)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🏷️</div>
        <div class="empty-state-title">Nenhum produto vendido ainda</div>
        <p>Configure seus produtos para começar a rastrear vendas</p>
    </div>
    <?php else: ?>
    <table>
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
                <td><?= htmlspecialchars($product['product_name'] ?? 'Sem nome') ?></td>
                <td><?= number_format($product['total'], 0, ',', '.') ?></td>
                <td>R$ <?= number_format($product['revenue'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>