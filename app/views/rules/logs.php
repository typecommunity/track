<?php
/**
 * UTMTrack - View de Logs de Regras
 * Arquivo: app/views/rules/logs.php
 */
?>

<div class="main-content">
    <div class="content-header" style="margin-bottom: 30px;">
        <div>
            <a href="index.php?page=regras" style="color: #667eea; text-decoration: none; font-size: 14px;">
                ← Voltar para Regras
            </a>
            <h1 style="font-size: 28px; font-weight: 700; color: #e2e8f0; margin: 10px 0 8px 0;">
                📊 Logs da Regra
            </h1>
            <p style="color: #94a3b8; font-size: 14px; margin: 0;">
                Histórico de execuções da regra "<?= htmlspecialchars($rule['name']) ?>"
            </p>
        </div>
    </div>

    <!-- Informações da Regra -->
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h2 class="card-title">ℹ️ Informações da Regra</h2>
        </div>
        
        <div style="padding: 25px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Nome</div>
                    <div style="color: #e2e8f0; font-weight: 600;"><?= htmlspecialchars($rule['name']) ?></div>
                </div>
                
                <div>
                    <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Tipo</div>
                    <div style="color: #e2e8f0; font-weight: 600;">
                        <?php
                        $types = [
                            'campaign' => '📢 Campanha',
                            'adset' => '🎯 Conjunto',
                            'ad' => '📝 Anúncio'
                        ];
                        echo $types[$rule['target_type']] ?? $rule['target_type'];
                        ?>
                    </div>
                </div>
                
                <div>
                    <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Ação</div>
                    <div style="color: #e2e8f0; font-weight: 600;">
                        <?php
                        $actions = [
                            'pause' => '⏸ Pausar',
                            'activate' => '▶ Ativar',
                            'increase_budget' => '⬆ Aumentar Orçamento',
                            'decrease_budget' => '⬇ Diminuir Orçamento'
                        ];
                        echo $actions[$rule['action']] ?? $rule['action'];
                        ?>
                    </div>
                </div>
                
                <div>
                    <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Condição</div>
                    <div style="color: #e2e8f0; font-weight: 600;">
                        <?php
                        $conditions = $rule['conditions'];
                        $metrics = [
                            'roas' => 'ROAS',
                            'roi' => 'ROI',
                            'conversions' => 'Conversões',
                            'spend' => 'Gasto',
                            'cpa' => 'CPA'
                        ];
                        $operators = [
                            'less_than' => '<',
                            'greater_than' => '>',
                            'equals' => '=',
                            'less_or_equal' => '≤',
                            'greater_or_equal' => '≥'
                        ];
                        echo ($metrics[$conditions['metric']] ?? $conditions['metric']) . ' ' . 
                             ($operators[$conditions['operator']] ?? $conditions['operator']) . ' ' . 
                             number_format($conditions['value'], 2, ',', '.');
                        ?>
                    </div>
                </div>
                
                <div>
                    <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Frequência</div>
                    <div style="color: #e2e8f0; font-weight: 600;">
                        <?php
                        $frequencies = [
                            '15min' => 'A cada 15min',
                            '30min' => 'A cada 30min',
                            '1hour' => 'A cada 1h',
                            '6hours' => 'A cada 6h',
                            '12hours' => 'A cada 12h',
                            '24hours' => 'A cada 24h'
                        ];
                        echo $frequencies[$rule['frequency']] ?? $rule['frequency'];
                        ?>
                    </div>
                </div>
                
                <div>
                    <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Status</div>
                    <div style="color: #e2e8f0; font-weight: 600;">
                        <?php if ($rule['status'] === 'active'): ?>
                            <span style="color: #10b981;">✅ Ativa</span>
                        <?php else: ?>
                            <span style="color: #ef4444;">⏸ Pausada</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Logs -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📜 Histórico de Execuções</h2>
            <div style="color: #94a3b8; font-size: 13px;">
                Últimas 100 execuções
            </div>
        </div>
        
        <?php if (empty($logs)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <div class="empty-state-title">Nenhuma execução registrada</div>
            <p>Esta regra ainda não foi executada</p>
        </div>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Campanha</th>
                        <th>Ação</th>
                        <th>Resultado</th>
                        <th>Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="white-space: nowrap;">
                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                        </td>
                        <td style="font-weight: 600;">
                            <?= htmlspecialchars($log['campaign_name'] ?? 'N/A') ?>
                        </td>
                        <td>
                            <?php
                            $actions = [
                                'pause' => '<span style="color: #ef4444;">⏸ Pausar</span>',
                                'activate' => '<span style="color: #10b981;">▶ Ativar</span>',
                                'increase_budget' => '<span style="color: #3b82f6;">⬆ Aumentar</span>',
                                'decrease_budget' => '<span style="color: #f59e0b;">⬇ Diminuir</span>'
                            ];
                            echo $actions[$log['action_taken']] ?? htmlspecialchars($log['action_taken']);
                            ?>
                        </td>
                        <td>
                            <?php if ($log['result'] === 'success'): ?>
                                <span style="
                                    background: #10b98120;
                                    color: #10b981;
                                    padding: 4px 12px;
                                    border-radius: 12px;
                                    font-size: 12px;
                                    font-weight: 600;
                                ">
                                    ✓ Sucesso
                                </span>
                            <?php else: ?>
                                <span style="
                                    background: #ef444420;
                                    color: #ef4444;
                                    padding: 4px 12px;
                                    border-radius: 12px;
                                    font-size: 12px;
                                    font-weight: 600;
                                ">
                                    ✗ Falha
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="color: #94a3b8; font-size: 13px;">
                            <?= htmlspecialchars($log['message'] ?? '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>