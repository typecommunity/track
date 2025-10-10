<!-- 
    UTMTrack - Dashboard de Campanhas PROFISSIONAL
    Versão 5.0 - Completo e Corrigido
    Arquivo: app/views/campaigns/index.php
-->

<?php
// Garante que as variáveis estejam definidas
if (!isset($campaigns)) $campaigns = [];
if (!isset($stats)) $stats = [];
if (!isset($userColumns)) $userColumns = null;
if (!isset($user)) $user = [];
if (!isset($config)) $config = [];

// Valores padrão para stats
$stats = array_merge([
    'total_campaigns' => 0,
    'active_campaigns' => 0,
    'total_spent' => 0,
    'total_impressions' => 0,
    'total_clicks' => 0,
    'total_conversions' => 0,
    'total_sales' => 0,
    'total_revenue' => 0,
    'total_profit' => 0,
    'ctr' => 0,
    'avg_cpc' => 0,
    'avg_roas' => 0,
    'avg_roi' => 0
], $stats);

// CORREÇÃO: Recalcula estatísticas baseado nas campanhas existentes
if (is_array($campaigns) && count($campaigns) > 0) {
    $stats['total_campaigns'] = count($campaigns);
    $stats['active_campaigns'] = 0;
    $stats['total_spent'] = 0;
    $stats['total_revenue'] = 0;
    $stats['total_profit'] = 0;
    $stats['total_sales'] = 0;
    $stats['total_impressions'] = 0;
    $stats['total_clicks'] = 0;
    $stats['total_conversions'] = 0;
    
    foreach ($campaigns as $campaign) {
        // Conta campanhas ativas
        if (isset($campaign['status']) && $campaign['status'] === 'active') {
            $stats['active_campaigns']++;
        }
        
        // Soma totais
        $stats['total_spent'] += floatval($campaign['spent'] ?? 0);
        $stats['total_revenue'] += floatval($campaign['real_revenue'] ?? 0);
        $stats['total_profit'] += floatval($campaign['real_profit'] ?? 0);
        $stats['total_sales'] += intval($campaign['real_sales'] ?? 0);
        $stats['total_impressions'] += intval($campaign['impressions'] ?? 0);
        $stats['total_clicks'] += intval($campaign['clicks'] ?? 0);
        $stats['total_conversions'] += intval($campaign['conversions'] ?? 0);
    }
    
    // Calcula médias
    if ($stats['total_spent'] > 0) {
        $stats['avg_roas'] = $stats['total_revenue'] / $stats['total_spent'];
        $stats['avg_roi'] = (($stats['total_revenue'] - $stats['total_spent']) / $stats['total_spent']) * 100;
    }
    if ($stats['total_impressions'] > 0) {
        $stats['ctr'] = ($stats['total_clicks'] / $stats['total_impressions']) * 100;
    }
    if ($stats['total_clicks'] > 0) {
        $stats['avg_cpc'] = $stats['total_spent'] / $stats['total_clicks'];
    }
}

// Define colunas visíveis (usa configuração do usuário ou padrão)
$visibleColumns = $userColumns ?? ['nome', 'status', 'orcamento', 'vendas', 'cpa', 'gastos', 'faturamento', 'lucro', 'roas', 'margem', 'roi'];

// Determina o caminho base correto para os assets
$projectRoot = 'https://ataweb.com.br/utmtrack';

// Paths dos assets
$cssPath = $projectRoot . '/assets/css/campaigns-dashboard.css?v=5.0';
$jsPath = $projectRoot . '/assets/js/campaigns-dashboard.js?v=5.0';

// DEBUG MODE - altere para true se precisar debugar
$debugMode = false;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTMTrack - Dashboard de Campanhas</title>
    
    <?php if ($debugMode): ?>
    <!-- DEBUG INFO -->
    <div style="background: #ff0000; color: white; padding: 10px; font-family: monospace; font-size: 12px; position: fixed; top: 0; left: 0; right: 0; z-index: 9999;">
        <strong>DEBUG MODE ATIVO</strong><br>
        Total Campanhas Array: <?= count($campaigns) ?><br>
        Total Campanhas Stats: <?= $stats['total_campaigns'] ?><br>
        Campanhas Ativas: <?= $stats['active_campaigns'] ?><br>
        CSS Path: <?= $cssPath ?><br>
        JS Path: <?= $jsPath ?>
    </div>
    <?php endif; ?>
    
    <!-- CSS EXTERNO -->
    <link rel="stylesheet" href="<?= $cssPath ?>">
    
    <!-- CSS de Fallback + Correção da Borda -->
    <style>
        /* Remove bordas brancas */
        .table-wrapper { 
            border: none !important;
            background: var(--bg-secondary);
            border-radius: 12px;
            overflow: hidden;
        }
        .table-container {
            border: none !important;
        }
        .campaigns-table {
            border: none !important;
        }
        .campaigns-table th,
        .campaigns-table td {
            border-color: #334155 !important;
        }
    </style>
    
    <!-- Passa configuração para o JavaScript -->
    <script>
        window.userColumnsConfig = <?= json_encode($visibleColumns) ?>;
        window.debugMode = <?= $debugMode ? 'true' : 'false' ?>;
        window.baseUrl = '<?= $projectRoot ?>';
    </script>
</head>
<body>

<!-- TABS DE NAVEGAÇÃO -->
<div class="tabs-container">
    <button class="tab-button" onclick="window.location.href='index.php?page=integracoes-meta-contas'">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        Contas
    </button>
    <button class="tab-button active">
        <svg class="icon" viewBox="0 0 24 24">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
        Campanhas
    </button>
    <button class="tab-button" onclick="window.location.href='index.php?page=conjuntos'">
        <svg class="icon" viewBox="0 0 24 24">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="3" y1="9" x2="21" y2="9"></line>
            <line x1="9" y1="21" x2="9" y2="9"></line>
        </svg>
        Conjuntos
    </button>
    <button class="tab-button" onclick="window.location.href='index.php?page=anuncios'">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        Anúncios
    </button>
</div>

<!-- TOOLBAR -->
<div class="toolbar">
    <div class="toolbar-left">
        <div class="status-indicator">Sistema Online</div>
        <button class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;" title="Configurações">
            <svg class="icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
            </svg>
        </button>
        <button class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;" title="Exportar" 
                onclick="window.location.href='index.php?page=campanhas-export'">
            <svg class="icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>
    
    <div class="toolbar-right">
        <span style="color: var(--text-secondary); font-size: 13px;">
            <?= $stats['total_campaigns'] ?> campanhas | <?= $stats['active_campaigns'] ?> ativas
        </span>
        <button onclick="syncAllCampaigns()" class="btn btn-primary" style="padding: 8px 20px;">
            <svg class="icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            Atualizar Tudo
        </button>
    </div>
</div>

<!-- FILTROS -->
<div class="filters-bar">
    <input 
        type="text" 
        class="filter-input" 
        id="searchInput"
        placeholder="Filtrar por nome da campanha..."
        onkeyup="filterTable()"
    >
    
    <select class="filter-select" id="statusFilter" onchange="filterTable()">
        <option value="">Todos os Status</option>
        <option value="active">Ativadas</option>
        <option value="paused">Desativadas</option>
    </select>
    
    <select class="filter-select" id="periodFilter">
        <option>Hoje</option>
        <option>Últimos 7 dias</option>
        <option selected>Últimos 30 dias</option>
        <option>Este mês</option>
    </select>
    
    <button onclick="openColumnsModal()" class="btn btn-secondary" style="padding: 10px 16px;">
        <svg class="icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24">
            <line x1="4" y1="21" x2="4" y2="14"></line>
            <line x1="4" y1="10" x2="4" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12" y2="3"></line>
            <line x1="20" y1="21" x2="20" y2="16"></line>
            <line x1="20" y1="12" x2="20" y2="3"></line>
            <line x1="1" y1="14" x2="7" y2="14"></line>
            <line x1="9" y1="8" x2="15" y2="8"></line>
            <line x1="17" y1="16" x2="23" y2="16"></line>
        </svg>
        Personalizar Colunas
    </button>
</div>

<!-- TABELA COM SCROLL HORIZONTAL -->
<div class="table-wrapper">
    <div class="table-container" id="tableContainer">
        <table class="campaigns-table" id="campaignsTable">
            <thead>
                <tr id="tableHeader"></tr>
            </thead>
            <tbody id="tableBody">
                <?php if (!is_array($campaigns) || count($campaigns) === 0): ?>
                <tr>
                    <td colspan="25" style="border: none;">
                        <div class="empty-state">
                            <div class="empty-state-icon">📊</div>
                            <div class="empty-state-title">Nenhuma campanha encontrada</div>
                            <p class="empty-state-description">
                                Conecte suas contas e sincronize para ver suas campanhas
                            </p>
                            <button onclick="window.location.href='index.php?page=integracoes-meta-contas'" 
                                    class="btn btn-primary mt-4">
                                Conectar Conta
                            </button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $c): ?>
                    <?php 
                        // Garante que todos os campos existam
                        $c = array_merge([
                            'id' => 0,
                            'campaign_id' => '',
                            'campaign_name' => 'Sem nome',
                            'status' => 'paused',
                            'budget' => 0,
                            'spent' => 0,
                            'impressions' => 0,
                            'clicks' => 0,
                            'conversions' => 0,
                            'ctr' => 0,
                            'cpc' => 0,
                            'cpm' => 0,
                            'real_sales' => 0,
                            'real_revenue' => 0,
                            'real_profit' => 0,
                            'live_roas' => 0,
                            'live_roi' => 0,
                            'live_margin' => 0,
                            'live_cpa' => 0,
                            'initiate_checkout' => 0,
                            'account_name' => '',
                            'last_sync' => date('Y-m-d H:i:s')
                        ], $c);
                    ?>
                    <tr 
                        data-id="<?= $c['id'] ?>"
                        data-campaign-id="<?= htmlspecialchars($c['campaign_id']) ?>"
                        data-name="<?= strtolower($c['campaign_name']) ?>"
                        data-status="<?= $c['status'] ?>"
                        data-account-id="<?= $c['ad_account_id'] ?? '' ?>"
                    >
                        <td data-column="nome">
                            <div class="campaign-name-cell" 
                                 onclick="makeEditableName(this, <?= $c['id'] ?>)"
                                 data-value="<?= htmlspecialchars($c['campaign_name']) ?>">
                                <?= htmlspecialchars($c['campaign_name']) ?>
                            </div>
                            <div class="campaign-id">ID: <?= htmlspecialchars($c['campaign_id']) ?></div>
                        </td>
                        
                        <td data-column="status">
                            <span class="status-badge <?= $c['status'] ?>" 
                                  onclick="toggleStatus(this, <?= $c['id'] ?>, '<?= htmlspecialchars($c['campaign_id']) ?>')">
                                <?= $c['status'] === 'active' ? '✓ Ativada' : '⏸ Desativada' ?>
                            </span>
                        </td>
                        
                        <td data-column="orcamento">
                            <div class="editable-field" 
                                 onclick="makeEditable(this, <?= $c['id'] ?>, 'budget', 'currency', '<?= htmlspecialchars($c['campaign_id']) ?>')"
                                 data-value="<?= $c['budget'] ?>">
                                R$ <?= number_format($c['budget'], 2, ',', '.') ?>
                            </div>
                        </td>
                        
                        <td data-column="vendas"><?= number_format($c['real_sales'], 0, ',', '.') ?></td>
                        
                        <td data-column="cpa" class="<?= $c['live_cpa'] > 0 ? '' : 'metric-neutral' ?>">
                            R$ <?= number_format($c['live_cpa'], 2, ',', '.') ?>
                        </td>
                        
                        <td data-column="gastos">
                            <strong>R$ <?= number_format($c['spent'], 2, ',', '.') ?></strong>
                        </td>
                        
                        <td data-column="faturamento" class="<?= $c['real_revenue'] > 0 ? 'metric-positive' : 'metric-neutral' ?>">
                            R$ <?= number_format($c['real_revenue'], 2, ',', '.') ?>
                        </td>
                        
                        <td data-column="lucro" class="<?= $c['real_profit'] > 0 ? 'metric-positive' : ($c['real_profit'] < 0 ? 'metric-negative' : 'metric-neutral') ?>">
                            R$ <?= number_format($c['real_profit'], 2, ',', '.') ?>
                        </td>
                        
                        <td data-column="roas" class="<?= $c['live_roas'] >= 2 ? 'metric-positive' : ($c['live_roas'] >= 1 ? '' : 'metric-negative') ?>">
                            <?= number_format($c['live_roas'], 2, ',', '.') ?>x
                        </td>
                        
                        <td data-column="margem" class="<?= $c['live_margin'] > 0 ? 'metric-positive' : 'metric-neutral' ?>">
                            <?= number_format($c['live_margin'], 2, ',', '.') ?>%
                        </td>
                        
                        <td data-column="roi" class="<?= $c['live_roi'] > 0 ? 'metric-positive' : ($c['live_roi'] < 0 ? 'metric-negative' : 'metric-neutral') ?>">
                            <?= number_format($c['live_roi'], 2, ',', '.') ?>%
                        </td>
                        
                        <!-- Colunas extras (ocultas por padrão) -->
                        <td data-column="ic"><?= number_format($c['initiate_checkout'], 0, ',', '.') ?></td>
                        <td data-column="cpi">R$ <?= $c['initiate_checkout'] > 0 ? number_format($c['spent'] / $c['initiate_checkout'], 2, ',', '.') : '0,00' ?></td>
                        <td data-column="cpc">R$ <?= number_format($c['cpc'] ?? 0, 2, ',', '.') ?></td>
                        <td data-column="ctr"><?= number_format($c['ctr'] ?? 0, 2, ',', '.') ?>%</td>
                        <td data-column="cpm">R$ <?= number_format($c['cpm'] ?? 0, 2, ',', '.') ?></td>
                        <td data-column="impressoes"><?= number_format($c['impressions'], 0, ',', '.') ?></td>
                        <td data-column="cliques"><?= number_format($c['clicks'], 0, ',', '.') ?></td>
                        <td data-column="conversoes"><?= number_format($c['conversions'], 0, ',', '.') ?></td>
                        <td data-column="conta"><?= htmlspecialchars($c['account_name']) ?></td>
                        <td data-column="ultima_sync"><?= date('d/m/Y H:i', strtotime($c['last_sync'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PERSONALIZAÇÃO DE COLUNAS -->
<div class="modal-overlay" id="columnsModal">
    <div class="columns-modal">
        <div class="columns-modal-header">
            <div class="columns-modal-title">
                <svg class="icon" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24">
                    <line x1="4" y1="21" x2="4" y2="14"></line>
                    <line x1="4" y1="10" x2="4" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12" y2="3"></line>
                    <line x1="20" y1="21" x2="20" y2="16"></line>
                    <line x1="20" y1="12" x2="20" y2="3"></line>
                </svg>
                Personalizar as colunas
            </div>
            <div class="columns-modal-subtitle">Escolha e organize as colunas (arraste para reordenar)</div>
        </div>
        
        <div class="columns-modal-left">
            <input 
                type="text" 
                class="columns-search" 
                placeholder="Buscar coluna..."
                id="columnSearch"
                onkeyup="filterColumns()"
            >
            <div id="availableColumns"></div>
        </div>
        
        <div class="columns-modal-right">
            <div style="margin-bottom: 12px; color: var(--text-secondary); font-size: 13px; font-weight: 600;">
                COLUNAS SELECIONADAS (arraste para reordenar)
            </div>
            <div id="selectedColumns"></div>
        </div>
        
        <div class="columns-modal-footer">
            <button class="btn btn-secondary" onclick="closeColumnsModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveColumns()">
                <svg class="icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Salvar
            </button>
        </div>
    </div>
</div>

<!-- JAVASCRIPT EXTERNO -->
<script src="<?= $jsPath ?>"></script>

<!-- Script de Sincronização com Meta Ads -->
<script>
// Sobrescreve a função toggleStatus para sincronizar com Meta Ads
async function toggleStatus(element, campaignId, metaCampaignId) {
    const currentStatus = element.closest('tr').getAttribute('data-status');
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';
    
    element.innerHTML = '<span style="font-size: 11px;">⏳ Atualizando...</span>';
    
    try {
        const response = await fetch('index.php?page=campanhas-update-meta-status', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                campaign_id: campaignId,
                meta_campaign_id: metaCampaignId,
                status: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            element.className = 'status-badge ' + newStatus;
            element.innerHTML = newStatus === 'active' ? '✓ Ativada' : '⏸ Desativada';
            element.closest('tr').setAttribute('data-status', newStatus);
            
            // Mostra mensagem se Meta foi atualizado
            if (result.meta_updated) {
                element.style.background = 'rgba(16, 185, 129, 0.3)';
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:12px 20px;border-radius:8px;z-index:9999;';
                toast.innerHTML = '✅ Campanha atualizada no Meta Ads!';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        } else {
            throw new Error(result.message || 'Erro ao atualizar');
        }
    } catch (error) {
        alert('❌ Erro: ' + error.message);
        // Restaura botão original
        element.className = 'status-badge ' + currentStatus;
        element.innerHTML = currentStatus === 'active' ? '✓ Ativada' : '⏸ Desativada';
    }
}

// Sobrescreve função para atualizar orçamento no Meta
function makeEditable(element, campaignId, field, type = 'text', metaCampaignId = '') {
    if (element.classList.contains('editing')) return;
    
    const currentValue = element.getAttribute('data-value');
    element.classList.add('editing');
    element.innerHTML = `<input type="${type === 'currency' ? 'number' : 'text'}" 
                                value="${currentValue}" 
                                step="0.01"
                                onblur="saveFieldWithMeta(this, ${campaignId}, '${field}', '${type}', '${metaCampaignId}')"
                                onkeypress="if(event.key==='Enter') this.blur()">`;
    element.querySelector('input').focus();
    element.querySelector('input').select();
}

async function saveFieldWithMeta(input, campaignId, field, type, metaCampaignId) {
    const newValue = input.value;
    const parent = input.parentElement;
    
    parent.innerHTML = '<span class="saving-indicator">💾 Salvando...</span>';
    
    try {
        const endpoint = field === 'budget' && metaCampaignId 
            ? 'index.php?page=campanhas-update-meta-budget' 
            : 'index.php?page=campanhas-update-field';
            
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                campaign_id: campaignId,
                meta_campaign_id: metaCampaignId,
                field: field,
                value: newValue
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            parent.classList.remove('editing');
            parent.setAttribute('data-value', newValue);
            
            if (type === 'currency') {
                parent.innerHTML = 'R$ ' + parseFloat(newValue).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } else {
                parent.innerHTML = newValue;
            }
            
            // Feedback visual
            parent.style.background = 'rgba(16, 185, 129, 0.2)';
            setTimeout(() => {
                parent.style.background = '';
            }, 1000);
            
            if (result.meta_updated) {
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:12px 20px;border-radius:8px;z-index:9999;';
                toast.innerHTML = '✅ Orçamento atualizado no Meta Ads!';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        alert('❌ Erro ao salvar: ' + error.message);
        parent.classList.remove('editing');
        parent.setAttribute('onclick', `makeEditable(this, ${campaignId}, '${field}', '${type}', '${metaCampaignId}')`);
        
        if (type === 'currency') {
            parent.innerHTML = 'R$ ' + parseFloat(parent.getAttribute('data-value')).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } else {
            parent.innerHTML = parent.getAttribute('data-value');
        }
    }
}

// Debug no console
if (window.debugMode) {
    console.log('🔍 Debug Info:');
    console.log('Total Campanhas:', <?= $stats['total_campaigns'] ?>);
    console.log('Campanhas Ativas:', <?= $stats['active_campaigns'] ?>);
    console.log('Integração Meta Ads: Ativa ✅');
}
</script>

</body>
</html>