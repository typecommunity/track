<!-- Botão Voltar -->
<div style="margin-bottom: 20px;">
    <a href="index.php?page=regras" class="btn" style="background: #334155; color: white; text-decoration: none;">
        ← Voltar para Regras
    </a>
</div>

<!-- Info da Regra -->
<div class="card" style="margin-bottom: 30px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border-color: rgba(102, 126, 234, 0.3);">
    <h3 style="color: #e2e8f0; margin-bottom: 20px; font-size: 20px;">
        ⚙️ <?= htmlspecialchars($rule['name']) ?>
    </h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div>
            <div style="color: #94a3b8; font-size: 12px; margin-bottom: 5px;">Produto</div>
            <div style="color: #e2e8f0; font-weight: 600;">
                <?= $rule['product_name'] ? htmlspecialchars($rule['product_name']) : 'Todos' ?>
            </div>
        </div>
        
        <div>
            <div style="color: #94a3b8; font-size: 12px; margin-bottom: 5px;">Conta de Anúncio</div>
            <div style="color: #e2e8f0; font-weight: 600;">
                <?= $rule['account_name'] ? htmlspecialchars($rule['account_name']) : 'Todas' ?>
            </div>
        </div>
        
        <div>
            <div style="color: #94a3b8; font-size: 12px; margin-bottom: 5px;">Tipo</div>
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
            <div style="color: #94a3b8; font-size: 12px; margin-bottom: 5px;">Ação</div>
            <div style="color: #e2e8f0; font-weight: 600;">
                <?php
                $actions = [
                    'pause' => '⏸ Pausar',
                    'activate' => '▶ Ativar',
                    'increase_budget' => '⬆ Aumentar',
                    'decrease_budget' => '⬇ Diminuir'
                ];
                echo $actions[$rule['action']] ?? $rule['action'];
                ?>
            </div>
        </div>
        
        <div>
            <div style="color: #94a3b8; font-size: 12px; margin-bottom: 5px;">Status</div>
            <div style="font-weight: 600;">
                <?php if ($rule['status'] === 'active'): ?>
                    <span style="color: #10b981;">✓ Ativa</span>
                <?php else: ?>
                    <span style="color: #f59e0b;">⏸ Inativa</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Logs -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📊 Histórico de Execuções</h2>
        <div style="color: #94a3b8; font-size: 14px;">
            Últimas 100 execuções
        </div>
    </div>
    
    <?php if (empty($logs)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <div class="empty-state-title">Nenhuma execução ainda</div>
        <p>Esta regra ainda não foi executada</p>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Campanha</th>
                    <th>Ação Executada</th>
                    <th>Resultado</th>
                    <th>Mensagem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="font-weight: 600; white-space: nowrap;">
                        <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                    </td>
                    <td>
                        <?= $log['campaign_name'] ? htmlspecialchars($log['campaign_name']) : 
                            ($log['campaign_id'] ? 'Campanha #' . $log['campaign_id'] : '-') ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($log['action_taken']) ?>
                    </td>
                    <td>
                        <?php if ($log['result'] === 'success'): ?>
                            <span style="
                                display: inline-block;
                                padding: 4px 12px;
                                background: #10b98120;
                                color: #10b981;
                                border-radius: 12px;
                                font-size: 12px;
                                font-weight: 600;
                            ">
                                ✓ Sucesso
                            </span>
                        <?php else: ?>
                            <span style="
                                display: inline-block;
                                padding: 4px 12px;
                                background: #ef444420;
                                color: #ef4444;
                                border-radius: 12px;
                                font-size: 12px;
                                font-weight: 600;
                            ">
                                ✗ Falha
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['message']): ?>
                            <div style="
                                max-width: 400px;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                white-space: nowrap;
                                color: <?= $log['result'] === 'success' ? '#94a3b8' : '#fca5a5' ?>;
                            " title="<?= htmlspecialchars($log['message']) ?>">
                                <?= htmlspecialchars($log['message']) ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #64748b;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Resumo -->
    <div style="margin-top: 30px; padding: 20px; background: #0f172a; border-radius: 12px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <?php
        $totalLogs = count($logs);
        $successLogs = count(array_filter($logs, fn($log) => $log['result'] === 'success'));
        $failedLogs = $totalLogs - $successLogs;
        $successRate = $totalLogs > 0 ? ($successLogs / $totalLogs) * 100 : 0;
        ?>
        
        <div style="text-align: center;">
            <div style="font-size: 32px; font-weight: 700; color: #e2e8f0; margin-bottom: 5px;">
                <?= $totalLogs ?>
            </div>
            <div style="color: #94a3b8; font-size: 13px;">Total de Execuções</div>
        </div>
        
        <div style="text-align: center;">
            <div style="font-size: 32px; font-weight: 700; color: #10b981; margin-bottom: 5px;">
                <?= $successLogs ?>
            </div>
            <div style="color: #94a3b8; font-size: 13px;">Bem-sucedidas</div>
        </div>
        
        <div style="text-align: center;">
            <div style="font-size: 32px; font-weight: 700; color: #ef4444; margin-bottom: 5px;">
                <?= $failedLogs ?>
            </div>
            <div style="color: #94a3b8; font-size: 13px;">Falharam</div>
        </div>
        
        <div style="text-align: center;">
            <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px; color: <?= $successRate >= 80 ? '#10b981' : ($successRate >= 50 ? '#f59e0b' : '#ef4444') ?>">
                <?= number_format($successRate, 0) ?>%
            </div>
            <div style="color: #94a3b8; font-size: 13px;">Taxa de Sucesso</div>
        </div>
    </div>
    <?php endif; ?>
</div>