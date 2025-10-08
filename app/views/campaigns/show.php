<!-- 
    UTMTrack - Detalhes da Campanha
    Arquivo: app/views/campaigns/show.php
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
    display: flex;
    align-items: center;
    gap: 6px;
}

.performance-value {
    font-size: 28px;
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

.campaign-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.campaign-info h1 {
    color: white;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 12px;
}

.campaign-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    color: #94a3b8;
    font-size: 13px;
}

.campaign-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.campaign-actions {
    display: flex;
    gap: 10px;
}

.sales-table {
    width: 100%;
    border-collapse: collapse;
}

.sales-table th,
.sales-table td {
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #1e293b;
}

.sales-table th {
    background: #0f172a;
    color: #e2e8f0;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sales-table td {
    color: #cbd5e1;
    font-size: 14px;
}

.sales-table tr:hover {
    background: rgba(100, 116, 139, 0.05);
}

.empty-sales {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-sales-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-sales-title {
    font-size: 18px;
    color: #94a3b8;
    margin-bottom: 8px;
    font-weight: 600;
}
</style>

<!-- Barra de Navegação -->
<div class="campaign-header">
    <div class="campaign-info">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
            <a href="index.php?page=campanhas-meta&account=<?= $campaign['ad_account_id'] ?>" class="btn" style="background: #334155; color: white; text-decoration: none;">
                ← Voltar
            </a>
            <span class="status-badge <?= $campaign['status'] ?>">
                <?php
                $statusLabels = [
                    'active' => '✓ Ativa',
                    'paused' => '⏸ Pausada',
                    'deleted' => '🗑 Deletada'
                ];
                echo $statusLabels[$campaign['status']] ?? $campaign['status'];
                ?>
            </span>
        </div>
        
        <h1><?= htmlspecialchars($campaign['campaign_name']) ?></h1>
        
        <div class="campaign-meta">
            <div class="campaign-meta-item">
                <strong>ID:</strong> <?= htmlspecialchars($campaign['campaign_id']) ?>
            </div>
            <div class="campaign-meta-item">
                <strong>Conta:</strong> <?= htmlspecialchars($campaign['account_name']) ?>
            </div>
            <?php if ($campaign['objective']): ?>
            <div class="campaign-meta-item">
                <strong>Objetivo:</strong> <?= htmlspecialchars($campaign['objective']) ?>
            </div>
            <?php endif; ?>
            <div class="campaign-meta-item">
                <strong>Última Sync:</strong> <?= date('d/m/Y H:i', strtotime($campaign['last_sync'])) ?>
            </div>
        </div>
    </div>
    
    <div class="campaign-actions">
        <button onclick="syncCampaign()" class="btn" style="background: #10b981; color: white;">
            🔄 Sincronizar
        </button>
        <button onclick="window.print()" class="btn" style="background: #667eea; color: white;">
            🖨️ Imprimir
        </button>
    </div>
</div>

<!-- Métricas de Performance -->
<div class="performance-grid">
    <!-- Gasto -->
    <div class="performance-card">
        <div class="performance-label">💰 Gasto Total</div>
        <div class="performance-value">
            R$ <?= number_format($campaign['spent'], 2, ',', '.') ?>
        </div>
        <div class="performance-info">
            Orçamento: R$ <?= number_format($campaign['budget'], 2, ',', '.') ?>
        </div>
    </div>
    
    <!-- Receita -->
    <div class="performance-card">
        <div class="performance-label">💵 Receita</div>
        <div class="performance-value">
            R$ <?= number_format($metrics['total_revenue'], 2, ',', '.') ?>
        </div>
        <div class="performance-info">
            <?= $metrics['approved_sales'] ?> vendas aprovadas
        </div>
    </div>
    
    <!-- ROAS -->
    <div class="performance-card">
        <div class="performance-label">📈 ROAS</div>
        <div class="performance-value <?= $roas >= 2 ? 'positive' : ($roas >= 1 ? '' : 'negative') ?>">
            <?= number_format($roas, 2, ',', '.') ?>x
        </div>
        <div class="performance-info">
            <?= $roas >= 2 ? '✓ Excelente' : ($roas >= 1 ? 'OK' : '⚠ Baixo') ?>
        </div>
    </div>
    
    <!-- CPA -->
    <div class="performance-card">
        <div class="performance-label">🎯 CPA</div>
        <div class="performance-value">
            R$ <?= number_format($cpa, 2, ',', '.') ?>
        </div>
        <div class="performance-info">
            Custo por aquisição
        </div>
    </div>
</div>

<!-- Métricas de Tráfego -->
<div class="performance-grid">
    <!-- Impressões -->
    <div class="performance-card">
        <div class="performance-label">👁️ Impressões</div>
        <div class="performance-value">
            <?= number_format($campaign['impressions'], 0, ',', '.') ?>
        </div>
    </div>
    
    <!-- Cliques -->
    <div class="performance-card">
        <div class="performance-label">👆 Cliques</div>
        <div class="performance-value">
            <?= number_format($campaign['clicks'], 0, ',', '.') ?>
        </div>
        <div class="performance-info">
            CTR: <?= $campaign['impressions'] > 0 ? number_format(($campaign['clicks'] / $campaign['impressions']) * 100, 2, ',', '.') : 0 ?>%
        </div>
    </div>
    
    <!-- CPC -->
    <div class="performance-card">
        <div class="performance-label">💵 CPC Médio</div>
        <div class="performance-value">
            R$ <?= $campaign['clicks'] > 0 ? number_format($campaign['spent'] / $campaign['clicks'], 2, ',', '.') : '0,00' ?>
        </div>
    </div>
    
    <!-- Taxa de Conversão -->
    <div class="performance-card">
        <div class="performance-label">🎯 Taxa de Conversão</div>
        <div class="performance-value">
            <?= number_format($conversionRate, 2, ',', '.') ?>%
        </div>
        <div class="performance-info">
            <?= $campaign['conversions'] ?> conversões
        </div>
    </div>
</div>

<!-- Vendas Relacionadas -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">💳 Vendas Relacionadas</h2>
        <div style="color: #94a3b8; font-size: 13px;">
            Últimas <?= count($sales) ?> vendas
        </div>
    </div>
    
    <?php if (empty($sales)): ?>
    <div class="empty-sales">
        <div class="empty-sales-icon">💰</div>
        <div class="empty-sales-title">Nenhuma venda registrada</div>
        <p>As vendas aparecerão aqui quando forem associadas a esta campanha</p>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="sales-table">
            <thead>
                <tr>
                    <th>ID Transação</th>
                    <th>Cliente</th>
                    <th>Produto</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                <tr>
                    <td>
                        <span style="font-family: monospace; font-size: 12px; color: #64748b;">
                            <?= htmlspecialchars($sale['transaction_id'] ?? '-') ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: white;">
                            <?= htmlspecialchars($sale['customer_name'] ?? 'N/A') ?>
                        </div>
                        <div style="font-size: 12px; color: #64748b;">
                            <?= htmlspecialchars($sale['customer_email'] ?? '') ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($sale['product_name'] ?? '-') ?></td>
                    <td style="font-weight: 600; color: #10b981;">
                        R$ <?= number_format($sale['amount'], 2, ',', '.') ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $sale['status'] === 'approved' ? 'success' : ($sale['status'] === 'pending' ? 'warning' : 'danger') ?>">
                            <?php
                            $statusLabels = [
                                'approved' => '✓ Aprovada',
                                'pending' => '⏱ Pendente',
                                'refunded' => '↩ Reembolsada',
                                'cancelled' => '✗ Cancelada'
                            ];
                            echo $statusLabels[$sale['status']] ?? $sale['status'];
                            ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
// Sincronizar campanha
function syncCampaign() {
    if (!confirm('Deseja sincronizar esta campanha agora?')) {
        return;
    }
    
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '🔄 Sincronizando...';
    
    fetch('index.php?page=campanhas-sync', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'account_id=<?= $campaign['ad_account_id'] ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erro ao sincronizar: ' + error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '🔄 Sincronizar';
    });
}
</script>