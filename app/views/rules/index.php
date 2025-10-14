<?php
/**
 * UTMTrack - View de Regras Automatizadas
 * Arquivo: app/views/rules/index.php
 * 
 * VERSÃO MELHORADA - Outubro 2025
 * - Adicionadas TODAS as métricas da UTMfy
 * - Botões redesenhados sem emojis
 */

// Debug
if (!isset($adAccounts)) {
    error_log("ERRO: \$adAccounts não está definido na view!");
    $adAccounts = [];
} else {
    error_log("DEBUG: \$adAccounts tem " . count($adAccounts) . " contas");
}
?>

<div class="main-content">
    <div class="content-header" style="margin-bottom: 30px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: #e2e8f0; margin: 0 0 8px 0;">
                Regras Automatizadas
            </h1>
            <p style="color: #94a3b8; font-size: 14px; margin: 0;">
                Automatize ações em campanhas baseadas em métricas de performance
            </p>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 30px;">
        <div class="metric-card">
            <div class="metric-label">Total de Regras</div>
            <div class="metric-value">
                <?= number_format($stats['total_rules'] ?? 0, 0, ',', '.') ?>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-label">Regras Ativas</div>
            <div class="metric-value" style="color: #10b981;">
                <?= number_format($stats['active_rules'] ?? 0, 0, ',', '.') ?>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-label">Total de Execuções</div>
            <div class="metric-value">
                <?= number_format($stats['total_executions'] ?? 0, 0, ',', '.') ?>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-label">Taxa de Sucesso</div>
            <div class="metric-value">
                <?php 
                $successRate = ($stats['total_executions'] ?? 0) > 0 
                    ? (($stats['successful_executions'] ?? 0) / $stats['total_executions']) * 100 
                    : 0;
                ?>
                <span style="color: <?= $successRate >= 80 ? '#10b981' : ($successRate >= 50 ? '#f59e0b' : '#ef4444') ?>">
                    <?= number_format($successRate, 0) ?>%
                </span>
            </div>
        </div>
    </div>

    <!-- Lista de Regras -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Minhas Regras Automatizadas</h2>
            <button onclick="openCreateModal()" class="btn btn-primary">
                Nova Regra
            </button>
        </div>
        
        <?php if (empty($rules)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🤖</div>
            <div class="empty-state-title">Nenhuma regra configurada</div>
            <p>Crie regras para automatizar ações em suas campanhas baseadas em métricas</p>
            <button onclick="openCreateModal()" class="btn btn-primary" style="margin-top: 20px;">
                Criar Primeira Regra
            </button>
        </div>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Ação</th>
                        <th>Condição</th>
                        <th>Frequência</th>
                        <th>Execuções</th>
                        <th>Última Execução</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($rule['name']) ?></td>
                        <td>
                            <?php
                            $types = [
                                'campaign' => 'Campanha',
                                'adset' => 'Conjunto',
                                'ad' => 'Anúncio'
                            ];
                            echo $types[$rule['target_type']] ?? $rule['target_type'];
                            ?>
                        </td>
                        <td>
                            <?php
                            $actions = [
                                'pause' => '<span style="color: #ef4444; font-weight: 600;">Pausar</span>',
                                'activate' => '<span style="color: #10b981; font-weight: 600;">Ativar</span>',
                                'increase_budget' => '<span style="color: #3b82f6; font-weight: 600;">Aumentar</span>',
                                'decrease_budget' => '<span style="color: #f59e0b; font-weight: 600;">Diminuir</span>'
                            ];
                            echo $actions[$rule['action']] ?? $rule['action'];
                            ?>
                        </td>
                        <td>
                            <?php
                            $conditions = $rule['conditions'];
                            $metrics = [
                                'spend' => 'Gasto',
                                'cpa' => 'CPA',
                                'roi' => 'ROI',
                                'roas' => 'ROAS',
                                'profit' => 'Lucro',
                                'margin' => 'Margem',
                                'cpc' => 'CPC',
                                'budget' => 'Orçamento',
                                'cpi' => 'CPI',
                                'sales' => 'Vendas',
                                'initiate_checkout' => 'ICs',
                                'ctr' => 'CTR',
                                'cpm' => 'CPM',
                                'clicks' => 'Cliques',
                                'conversions' => 'Conversões',
                                'cost_per_conversion' => 'Custo/Conv.',
                                'cpl' => 'CPL',
                                'cpv' => 'CPV',
                                'page_views' => 'Vis. Pág.'
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
                        </td>
                        <td>
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
                        </td>
                        <td>
                            <?= number_format($rule['total_executions'] ?? 0, 0, ',', '.') ?>
                            <span style="color: #10b981; font-size: 11px;">
                                (<?= number_format($rule['successful_executions'] ?? 0, 0, ',', '.') ?> OK)
                            </span>
                        </td>
                        <td>
                            <?php if ($rule['last_execution_date']): ?>
                                <?= date('d/m/Y H:i', strtotime($rule['last_execution_date'])) ?>
                            <?php else: ?>
                                <span style="color: #64748b;">Nunca</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <label class="switch">
                                <input 
                                    type="checkbox" 
                                    <?= $rule['status'] === 'active' ? 'checked' : '' ?>
                                    onchange="toggleRule(<?= $rule['id'] ?>)"
                                >
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button 
                                    onclick='viewLogs(<?= $rule['id'] ?>)' 
                                    class="action-btn logs-btn"
                                    title="Ver Logs"
                                >
                                    Logs
                                </button>
                                <button 
                                    onclick='editRule(<?= $rule['id'] ?>)' 
                                    class="action-btn edit-btn"
                                    title="Editar"
                                >
                                    Editar
                                </button>
                                <button 
                                    onclick='deleteRule(<?= $rule['id'] ?>, "<?= addslashes($rule['name']) ?>")' 
                                    class="action-btn delete-btn"
                                    title="Deletar"
                                >
                                    Deletar
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Criar/Editar Regra -->
<div id="ruleModal" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 20px;
">
    <div style="
        background: #1e293b;
        border-radius: 20px;
        padding: 40px;
        max-width: 800px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        border: 1px solid #334155;
        margin: auto;
    ">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 id="modalTitle" style="color: white; font-size: 24px;">Nova Regra</h2>
            <button onclick="closeModal()" style="
                background: none;
                border: none;
                color: #94a3b8;
                font-size: 28px;
                cursor: pointer;
                padding: 0;
                width: 40px;
                height: 40px;
            ">×</button>
        </div>
        
        <form id="ruleForm" onsubmit="saveRule(event)">
            <input type="hidden" id="rule_id" name="rule_id">
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Nome da Regra *
                </label>
                <input 
                    type="text" 
                    id="rule_name" 
                    name="name"
                    required
                    placeholder="Ex: Pausar campanha se ROAS < 2"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                        Produto (Opcional)
                    </label>
                    <select 
                        id="rule_product" 
                        name="product_id"
                        style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                    >
                        <option value="">Todos os produtos</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                        Conta de Anúncio *
                    </label>
                    <select 
                        id="rule_ad_account" 
                        name="ad_account_id"
                        required
                        style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                    >
                        <option value="">Selecione uma conta</option>
                        <?php foreach ($adAccounts as $account): ?>
                        <option value="<?= $account['id'] ?>">
                            [<?= strtoupper($account['platform']) ?>] <?= htmlspecialchars($account['account_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                        Aplicar em *
                    </label>
                    <select 
                        id="rule_target_type" 
                        name="target_type"
                        required
                        style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                    >
                        <option value="">Selecione...</option>
                        <option value="campaign">Campanha</option>
                        <option value="adset">Conjunto de Anúncios</option>
                        <option value="ad">Anúncio</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                        Ação *
                    </label>
                    <select 
                        id="rule_action" 
                        name="action"
                        required
                        style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                    >
                        <option value="">Selecione...</option>
                        <option value="pause">Pausar</option>
                        <option value="activate">Ativar</option>
                        <option value="increase_budget">Aumentar Orçamento</option>
                        <option value="decrease_budget">Diminuir Orçamento</option>
                    </select>
                </div>
            </div>
            
            <div style="background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
                <h3 style="color: #e2e8f0; margin-bottom: 15px; font-size: 16px;">Condições</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0; font-size: 13px;">
                            Métrica *
                        </label>
                        <select 
                            id="condition_metric" 
                            name="condition_metric"
                            required
                            style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                        >
                            <option value="">Selecione...</option>
                            <option value="spend">Gasto</option>
                            <option value="cpa">CPA</option>
                            <option value="roi">ROI</option>
                            <option value="roas">ROAS</option>
                            <option value="profit">Lucro</option>
                            <option value="margin">Margem de Lucro</option>
                            <option value="cpc">CPC</option>
                            <option value="budget">Orçamento</option>
                            <option value="cpi">CPI</option>
                            <option value="sales">Vendas</option>
                            <option value="initiate_checkout">ICs (Iniciar Compra)</option>
                            <option value="ctr">CTR</option>
                            <option value="cpm">CPM</option>
                            <option value="clicks">Cliques</option>
                            <option value="conversions">Conversões</option>
                            <option value="cost_per_conversion">Custo por Conversa</option>
                            <option value="cpl">CPL</option>
                            <option value="cpv">CPV</option>
                            <option value="page_views">Visualizações de Página</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0; font-size: 13px;">
                            Operador *
                        </label>
                        <select 
                            id="condition_operator" 
                            name="condition_operator"
                            required
                            style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                        >
                            <option value="">Selecione...</option>
                            <option value="less_than">< Menor que</option>
                            <option value="greater_than">> Maior que</option>
                            <option value="equals">= Igual a</option>
                            <option value="less_or_equal">≤ Menor ou igual</option>
                            <option value="greater_or_equal">≥ Maior ou igual</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0; font-size: 13px;">
                            Valor *
                        </label>
                        <input 
                            type="number" 
                            id="condition_value" 
                            name="condition_value"
                            step="0.01"
                            required
                            placeholder="Ex: 2.0"
                            style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                        >
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0; font-size: 13px;">
                        Período de Cálculo
                    </label>
                    <select 
                        id="condition_period" 
                        name="condition_period"
                        style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                    >
                        <option value="1hour">Última 1 hora</option>
                        <option value="6hours">Últimas 6 horas</option>
                        <option value="12hours">Últimas 12 horas</option>
                        <option value="24hours" selected>Últimas 24 horas</option>
                        <option value="7days">Últimos 7 dias</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                        Frequência de Verificação
                    </label>
                    <select 
                        id="rule_frequency" 
                        name="frequency"
                        style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                    >
                        <option value="15min">A cada 15 minutos</option>
                        <option value="30min">A cada 30 minutos</option>
                        <option value="1hour" selected>A cada 1 hora</option>
                        <option value="6hours">A cada 6 horas</option>
                        <option value="12hours">A cada 12 horas</option>
                        <option value="24hours">A cada 24 horas</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                        Máx. Execuções por Dia
                    </label>
                    <input 
                        type="number" 
                        id="rule_max_executions" 
                        name="max_executions_per_day"
                        value="10"
                        min="1"
                        max="100"
                        style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                    >
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeModal()" class="btn" style="background: #334155; color: white;">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Salvar Regra
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Estilos -->
<style>
/* Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #334155;
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #10b981;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

/* Action Buttons */
.action-btn {
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.logs-btn {
    background: #10b981;
    color: white;
}

.logs-btn:hover {
    background: #059669;
    transform: translateY(-1px);
}

.edit-btn {
    background: #667eea;
    color: white;
}

.edit-btn:hover {
    background: #5568d3;
    transform: translateY(-1px);
}

.delete-btn {
    background: #ef4444;
    color: white;
}

.delete-btn:hover {
    background: #dc2626;
    transform: translateY(-1px);
}
</style>

<script>
// Abrir modal de criação
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Nova Regra';
    document.getElementById('ruleForm').reset();
    document.getElementById('rule_id').value = '';
    document.getElementById('ruleModal').style.display = 'flex';
}

// Editar regra
async function editRule(ruleId) {
    try {
        const response = await fetch(`index.php?page=regra-get&id=${ruleId}`);
        const result = await response.json();
        
        if (result.success) {
            const rule = result.rule;
            
            document.getElementById('modalTitle').textContent = 'Editar Regra';
            document.getElementById('rule_id').value = rule.id;
            document.getElementById('rule_name').value = rule.name;
            document.getElementById('rule_product').value = rule.product_id || '';
            document.getElementById('rule_ad_account').value = rule.ad_account_id || '';
            document.getElementById('rule_target_type').value = rule.target_type;
            document.getElementById('rule_action').value = rule.action;
            document.getElementById('condition_metric').value = rule.conditions.metric;
            document.getElementById('condition_operator').value = rule.conditions.operator;
            document.getElementById('condition_value').value = rule.conditions.value;
            document.getElementById('condition_period').value = rule.conditions.period;
            document.getElementById('rule_frequency').value = rule.frequency;
            document.getElementById('rule_max_executions').value = rule.max_executions_per_day;
            
            document.getElementById('ruleModal').style.display = 'flex';
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao carregar regra: ' + error.message);
    }
}

// Ver logs
function viewLogs(ruleId) {
    window.location.href = `index.php?page=regra-logs&id=${ruleId}`;
}

// Salvar regra
async function saveRule(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const ruleId = formData.get('rule_id');
    const url = ruleId ? 'index.php?page=regra-update' : 'index.php?page=regra-create';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            closeModal();
            window.location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao salvar regra: ' + error.message);
    }
}

// Deletar regra
async function deleteRule(ruleId, ruleName) {
    if (!confirm(`Tem certeza que deseja deletar a regra "${ruleName}"?\n\nTodos os logs serão deletados também.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('rule_id', ruleId);
        
        const response = await fetch('index.php?page=regra-delete', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            window.location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao deletar regra: ' + error.message);
    }
}

// Alternar status (ativar/desativar)
async function toggleRule(ruleId) {
    try {
        const formData = new FormData();
        formData.append('rule_id', ruleId);
        
        const response = await fetch('index.php?page=regra-toggle', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Mostra notificação temporária
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #10b981;
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                z-index: 10000;
                font-weight: 600;
            `;
            notification.textContent = '✓ ' + result.message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 2000);
        } else {
            alert('Erro: ' + result.message);
            // Reverte o switch em caso de erro
            event.target.checked = !event.target.checked;
        }
    } catch (error) {
        alert('Erro ao alterar status: ' + error.message);
        event.target.checked = !event.target.checked;
    }
}

// Fechar modal
function closeModal() {
    document.getElementById('ruleModal').style.display = 'none';
}

// Fechar modal ao clicar fora
document.getElementById('ruleModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>