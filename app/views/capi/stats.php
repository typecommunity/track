<!-- Voltar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=capi" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar para CAPI
    </a>
</div>

<h1 style="color: white; margin-bottom: 10px;">📊 Estatísticas CAPI Detalhadas</h1>
<p style="color: #94a3b8; margin-bottom: 30px;">
    Análise completa dos eventos enviados para o Facebook nos últimos 30 dias
</p>

<!-- Filtro de Pixel -->
<?php if (!empty($pixels) && count($pixels) > 1): ?>
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 15px; align-items: end;">
        <input type="hidden" name="page" value="capi">
        <input type="hidden" name="action" value="stats">
        
        <div class="form-group" style="flex: 1; margin: 0;">
            <label>Filtrar por Pixel</label>
            <select name="pixel_id" class="form-control">
                <option value="">Todos os Pixels</option>
                <?php foreach ($pixels as $pixel): ?>
                <option value="<?= $pixel['id'] ?>" <?= $selectedPixelId == $pixel['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pixel['pixel_name']) ?> (<?= $pixel['pixel_id'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>
</div>
<?php endif; ?>

<!-- Estatísticas por Evento -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">📈 Estatísticas por Tipo de Evento</h2>
    </div>
    
    <?php if (empty($eventStats)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <div class="empty-state-title">Nenhum evento registrado</div>
        <p>Eventos aparecerão aqui assim que forem enviados</p>
    </div>
    <?php else: ?>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Tipo de Evento</th>
                    <th style="text-align: center;">Total</th>
                    <th style="text-align: center;">Enviados</th>
                    <th style="text-align: center;">Falhas</th>
                    <th style="text-align: center;">Pendentes</th>
                    <th style="text-align: center;">Taxa de Sucesso</th>
                    <th style="width: 200px;">Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalEvents = array_sum(array_column($eventStats, 'total'));
                foreach ($eventStats as $stat): 
                    $successRate = $stat['total'] > 0 ? ($stat['sent'] / $stat['total']) * 100 : 0;
                    $eventPercentage = $totalEvents > 0 ? ($stat['total'] / $totalEvents) * 100 : 0;
                ?>
                <tr>
                    <td>
                        <strong style="color: white;"><?= htmlspecialchars($stat['event_name']) ?></strong>
                    </td>
                    <td style="text-align: center; color: #94a3b8;">
                        <?= number_format($stat['total'], 0, ',', '.') ?>
                        <small style="color: #64748b;">(<?= number_format($eventPercentage, 1) ?>%)</small>
                    </td>
                    <td style="text-align: center;">
                        <span style="color: #10b981; font-weight: 600;">
                            <?= number_format($stat['sent'], 0, ',', '.') ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($stat['failed'] > 0): ?>
                        <span style="color: #ef4444; font-weight: 600;">
                            <?= number_format($stat['failed'], 0, ',', '.') ?>
                        </span>
                        <?php else: ?>
                        <span style="color: #64748b;">0</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($stat['pending'] > 0): ?>
                        <span style="color: #f59e0b; font-weight: 600;">
                            <?= number_format($stat['pending'], 0, ',', '.') ?>
                        </span>
                        <?php else: ?>
                        <span style="color: #64748b;">0</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <span style="color: <?= $successRate >= 90 ? '#10b981' : ($successRate >= 70 ? '#f59e0b' : '#ef4444') ?>; font-weight: 600;">
                            <?= number_format($successRate, 1) ?>%
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="flex: 1; height: 8px; background: #334155; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: linear-gradient(90deg, #10b981, #059669); width: <?= $successRate ?>%; transition: width 0.3s;"></div>
                            </div>
                            <span style="font-size: 11px; color: #64748b; min-width: 35px;">
                                <?= number_format($successRate, 0) ?>%
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid #334155; background: rgba(102, 126, 234, 0.05);">
                    <td><strong style="color: white;">TOTAL</strong></td>
                    <td style="text-align: center;">
                        <strong style="color: white;">
                            <?= number_format(array_sum(array_column($eventStats, 'total')), 0, ',', '.') ?>
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        <strong style="color: #10b981;">
                            <?= number_format(array_sum(array_column($eventStats, 'sent')), 0, ',', '.') ?>
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        <strong style="color: #ef4444;">
                            <?= number_format(array_sum(array_column($eventStats, 'failed')), 0, ',', '.') ?>
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        <strong style="color: #f59e0b;">
                            <?= number_format(array_sum(array_column($eventStats, 'pending')), 0, ',', '.') ?>
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        <?php 
                        $totalAll = array_sum(array_column($eventStats, 'total'));
                        $totalSent = array_sum(array_column($eventStats, 'sent'));
                        $overallSuccessRate = $totalAll > 0 ? ($totalSent / $totalAll) * 100 : 0;
                        ?>
                        <strong style="color: <?= $overallSuccessRate >= 90 ? '#10b981' : ($overallSuccessRate >= 70 ? '#f59e0b' : '#ef4444') ?>;">
                            <?= number_format($overallSuccessRate, 1) ?>%
                        </strong>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php endif; ?>
</div>

<!-- Gráfico de Eventos por Dia -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📅 Eventos por Dia (Últimos 30 dias)</h2>
    </div>
    
    <?php if (empty($dailyEvents)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📅</div>
        <div class="empty-state-title">Nenhum evento registrado</div>
    </div>
    <?php else: ?>
    
    <div style="padding: 20px;">
        <canvas id="dailyEventsChart" style="max-height: 400px;"></canvas>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('dailyEventsChart').getContext('2d');
    
    const dailyData = <?= json_encode($dailyEvents) ?>;
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
            }),
            datasets: [
                {
                    label: 'Total de Eventos',
                    data: dailyData.map(d => d.total),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Eventos Enviados',
                    data: dailyData.map(d => d.sent),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#e2e8f0',
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#e2e8f0',
                    bodyColor: '#94a3b8',
                    borderColor: '#334155',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#94a3b8',
                        precision: 0
                    },
                    grid: {
                        color: '#334155'
                    }
                },
                x: {
                    ticks: {
                        color: '#94a3b8',
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: {
                        color: '#334155'
                    }
                }
            }
        }
    });
    </script>
    
    <?php endif; ?>
</div>

<!-- Cards de Métricas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
    <?php
    $totalAll = array_sum(array_column($eventStats, 'total'));
    $totalSent = array_sum(array_column($eventStats, 'sent'));
    $totalFailed = array_sum(array_column($eventStats, 'failed'));
    $totalPending = array_sum(array_column($eventStats, 'pending'));
    $overallSuccessRate = $totalAll > 0 ? ($totalSent / $totalAll) * 100 : 0;
    ?>
    
    <div class="card">
        <div class="metric-label">📊 Total de Eventos</div>
        <div class="metric-value"><?= number_format($totalAll, 0, ',', '.') ?></div>
        <div class="metric-info">Últimos 30 dias</div>
    </div>
    
    <div class="card" style="border-color: #10b981;">
        <div class="metric-label">✅ Taxa de Sucesso</div>
        <div class="metric-value" style="color: <?= $overallSuccessRate >= 90 ? '#10b981' : ($overallSuccessRate >= 70 ? '#f59e0b' : '#ef4444') ?>;">
            <?= number_format($overallSuccessRate, 1) ?>%
        </div>
        <div class="metric-info">
            <?= number_format($totalSent, 0, ',', '.') ?> eventos enviados
        </div>
    </div>
    
    <?php if ($totalFailed > 0): ?>
    <div class="card" style="border-color: #ef4444;">
        <div class="metric-label">❌ Falhas</div>
        <div class="metric-value" style="color: #ef4444;">
            <?= number_format($totalFailed, 0, ',', '.') ?>
        </div>
        <div class="metric-info">
            <?= number_format(($totalFailed / $totalAll) * 100, 1) ?>% do total
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($totalPending > 0): ?>
    <div class="card" style="border-color: #f59e0b;">
        <div class="metric-label">⏳ Pendentes</div>
        <div class="metric-value" style="color: #f59e0b;">
            <?= number_format($totalPending, 0, ',', '.') ?>
        </div>
        <div class="metric-info">Aguardando envio</div>
    </div>
    <?php endif; ?>
    
    <div class="card" style="border-color: #667eea;">
        <div class="metric-label">📈 Média Diária</div>
        <div class="metric-value" style="color: #667eea;">
            <?php
            $daysWithEvents = count($dailyEvents);
            $avgPerDay = $daysWithEvents > 0 ? $totalAll / $daysWithEvents : 0;
            echo number_format($avgPerDay, 0, ',', '.');
            ?>
        </div>
        <div class="metric-info">Eventos por dia</div>
    </div>
</div>

<!-- Alertas -->
<?php if ($overallSuccessRate < 80 && $totalAll > 10): ?>
<div class="card" style="margin-top: 30px; background: rgba(239, 68, 68, 0.1); border-color: #ef4444;">
    <h3 style="color: #fca5a5; margin: 0 0 10px 0;">⚠️ Alerta de Performance</h3>
    <p style="color: #94a3b8; margin: 0; line-height: 1.6;">
        A taxa de sucesso está abaixo de 80%. Verifique os logs para identificar possíveis problemas:
    </p>
    <ul style="color: #94a3b8; margin: 10px 0 0 20px; line-height: 1.8;">
        <li>Token de acesso expirado</li>
        <li>Problemas de conectividade com Facebook</li>
        <li>Dados inválidos nos eventos</li>
        <li>Rate limit atingido</li>
    </ul>
    <div style="margin-top: 15px;">
        <a href="index.php?page=capi&action=logs" class="btn" style="background: #ef4444; color: white;">
            Ver Logs Detalhados
        </a>
    </div>
</div>
<?php endif; ?>

<?php if ($totalPending > 100): ?>
<div class="card" style="margin-top: 20px; background: rgba(245, 158, 11, 0.1); border-color: #f59e0b;">
    <h3 style="color: #fbbf24; margin: 0 0 10px 0;">⚠️ Muitos Eventos Pendentes</h3>
    <p style="color: #94a3b8; margin: 0;">
        Há <?= number_format($totalPending, 0, ',', '.') ?> eventos pendentes. 
        Verifique se o cron job está rodando corretamente.
    </p>
</div>
<?php endif; ?>

<style>
.metric-card {
    position: relative;
    overflow: hidden;
}

.metric-label {
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 8px;
    font-weight: 500;
}

.metric-value {
    color: white;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
}

.metric-info {
    color: #64748b;
    font-size: 12px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    background: #1e293b;
    color: #94a3b8;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    border-bottom: 2px solid #334155;
}

table td {
    padding: 12px;
    color: #94a3b8;
    border-bottom: 1px solid #334155;
}

table tbody tr:hover {
    background: rgba(102, 126, 234, 0.05);
}
</style>