<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Filtros de Período -->
<div class="card" style="margin-bottom: 30px;">
    <form method="GET" action="" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="page" value="relatorios">
        
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                Data Inicial
            </label>
            <input 
                type="date" 
                name="start_date" 
                value="<?= $startDate ?>"
                style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0;"
            >
        </div>
        
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                Data Final
            </label>
            <input 
                type="date" 
                name="end_date" 
                value="<?= $endDate ?>"
                style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0;"
            >
        </div>
        
        <button type="submit" class="btn btn-primary">
            📊 Atualizar
        </button>
        
        <a href="index.php?page=report-export&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn" style="background: #10b981; color: white; text-decoration: none;">
            📥 Exportar CSV
        </a>
    </form>
</div>

<!-- Comparação com Período Anterior -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom: 30px;">
    <div class="metric-card">
        <div class="metric-label">
            💰 Receita Total
        </div>
        <div class="metric-value">
            R$ <?= number_format($comparison['current']['revenue'] ?? 0, 2, ',', '.') ?>
        </div>
        <div class="metric-info" style="color: <?= $comparison['revenue_growth'] >= 0 ? '#10b981' : '#ef4444' ?>">
            <?= $comparison['revenue_growth'] >= 0 ? '↑' : '↓' ?> 
            <?= number_format(abs($comparison['revenue_growth']), 1, ',', '.') ?>% vs período anterior
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            📦 Total de Vendas
        </div>
        <div class="metric-value">
            <?= number_format($comparison['current']['total_sales'] ?? 0, 0, ',', '.') ?>
        </div>
        <div class="metric-info" style="color: <?= $comparison['sales_growth'] >= 0 ? '#10b981' : '#ef4444' ?>">
            <?= $comparison['sales_growth'] >= 0 ? '↑' : '↓' ?> 
            <?= number_format(abs($comparison['sales_growth']), 1, ',', '.') ?>% vs período anterior
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            🎫 Ticket Médio
        </div>
        <div class="metric-value">
            R$ <?= number_format($comparison['current']['avg_ticket'] ?? 0, 2, ',', '.') ?>
        </div>
        <div class="metric-info" style="color: <?= $comparison['ticket_growth'] >= 0 ? '#10b981' : '#ef4444' ?>">
            <?= $comparison['ticket_growth'] >= 0 ? '↑' : '↓' ?> 
            <?= number_format(abs($comparison['ticket_growth']), 1, ',', '.') ?>% vs período anterior
        </div>
    </div>
</div>

<!-- Gráfico de Vendas por Dia -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">📈 Vendas ao Longo do Tempo</h2>
    </div>
    <div style="position: relative; height: 350px;">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<!-- Gráficos em Duas Colunas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <!-- Vendas por Fonte -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🎯 Vendas por Fonte</h2>
        </div>
        <div style="position: relative; height: 300px;">
            <canvas id="sourceChart"></canvas>
        </div>
    </div>
    
    <!-- Funil de Conversão -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🔥 Funil de Conversão</h2>
        </div>
        <div style="padding: 20px;">
            <div style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #94a3b8;">👁️ Visualizações</span>
                    <span style="font-weight: 600;"><?= number_format($funnelData['page_views'], 0, ',', '.') ?></span>
                </div>
                <div style="background: #0f172a; height: 12px; border-radius: 6px; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; width: 100%;"></div>
                </div>
            </div>
            
            <div style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #94a3b8;">🛒 Iniciaram Checkout</span>
                    <span style="font-weight: 600;"><?= number_format($funnelData['initiate_checkout'], 0, ',', '.') ?> (<?= number_format($funnelData['checkout_rate'], 1) ?>%)</span>
                </div>
                <div style="background: #0f172a; height: 12px; border-radius: 6px; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, #10b981, #059669); height: 100%; width: <?= $funnelData['checkout_rate'] ?>%;"></div>
                </div>
            </div>
            
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #94a3b8;">💳 Compraram</span>
                    <span style="font-weight: 600;"><?= number_format($funnelData['purchases'], 0, ',', '.') ?> (<?= number_format($funnelData['overall_rate'], 1) ?>%)</span>
                </div>
                <div style="background: #0f172a; height: 12px; border-radius: 6px; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, #f59e0b, #d97706); height: 100%; width: <?= $funnelData['overall_rate'] ?>%;"></div>
                </div>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #0f172a; border-radius: 8px;">
                <div style="text-align: center;">
                    <div style="font-size: 36px; font-weight: 700; color: #10b981; margin-bottom: 5px;">
                        <?= number_format($funnelData['conversion_rate'], 1) ?>%
                    </div>
                    <div style="color: #94a3b8; font-size: 13px;">Taxa de Conversão</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Campanhas -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏆 Top 10 Campanhas</h2>
    </div>
    
    <?php if (empty($topCampaigns)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <div class="empty-state-title">Nenhuma campanha com vendas</div>
        <p>Conecte suas contas de anúncios para ver as campanhas aqui</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Campanha</th>
                <th>Gasto</th>
                <th>Vendas</th>
                <th>Receita</th>
                <th>ROAS</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $position = 1;
            foreach ($topCampaigns as $campaign): 
            ?>
            <tr>
                <td style="font-weight: bold; color: #667eea;">#<?= $position++ ?></td>
                <td><?= htmlspecialchars($campaign['campaign_name']) ?></td>
                <td>R$ <?= number_format($campaign['spent'], 2, ',', '.') ?></td>
                <td><?= number_format($campaign['total_sales'], 0, ',', '.') ?></td>
                <td>R$ <?= number_format($campaign['revenue'], 2, ',', '.') ?></td>
                <td style="color: <?= $campaign['roas'] >= 3 ? '#10b981' : ($campaign['roas'] >= 2 ? '#f59e0b' : '#ef4444') ?>">
                    <?= number_format($campaign['roas'], 2, ',', '.') ?>x
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
// Dados para os gráficos
const salesData = <?= json_encode($salesByDay) ?>;
const sourceData = <?= json_encode($salesBySource) ?>;

// Gráfico de Vendas por Dia
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: salesData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
        }),
        datasets: [{
            label: 'Receita (R$)',
            data: salesData.map(d => parseFloat(d.approved_revenue)),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#e2e8f0'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#94a3b8',
                    callback: function(value) {
                        return 'R$ ' + value.toLocaleString('pt-BR');
                    }
                },
                grid: {
                    color: '#334155'
                }
            },
            x: {
                ticks: {
                    color: '#94a3b8'
                },
                grid: {
                    color: '#334155'
                }
            }
        }
    }
});

// Gráfico de Vendas por Fonte (Pizza)
const sourceCtx = document.getElementById('sourceChart').getContext('2d');
new Chart(sourceCtx, {
    type: 'doughnut',
    data: {
        labels: sourceData.map(d => d.source || 'Direto'),
        datasets: [{
            data: sourceData.map(d => parseFloat(d.revenue)),
            backgroundColor: [
                '#667eea',
                '#764ba2',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#3b82f6',
                '#8b5cf6',
                '#ec4899',
                '#14b8a6',
                '#f97316'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    color: '#e2e8f0',
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': R$ ' + context.parsed.toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                }
            }
        }
    }
});
</script>