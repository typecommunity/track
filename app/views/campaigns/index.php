<!-- 
    ========================================
    CAMINHO: /utmtrack/app/views/campaigns/index.php
    ========================================
    
    UTMTrack - Dashboard de Campanhas
    Versão 11.0 FINAL - Toggle Verde + Edição Nome + Colunas Funcionais
-->

<?php
// PROCESSA REQUISIÇÕES AJAX DIRETAMENTE
if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        exit;
    }
    
    require_once dirname(__DIR__, 2) . '/core/Database.php';
    require_once dirname(__DIR__, 2) . '/core/Controller.php';
    require_once dirname(__DIR__, 2) . '/core/Auth.php';
    require_once dirname(__DIR__, 2) . '/core/Config.php';
    require_once dirname(__DIR__) . '/controllers/CampaignController.php';
    
    $controller = new CampaignController();
    
    switch ($_GET['ajax_action']) {
        case 'sync_all':
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $controller->syncAll();
            exit;
            
        case 'update_status':
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $controller->updateMetaStatus();
            exit;
            
        case 'update_budget':
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $controller->updateMetaBudget();
            exit;
            
        case 'update_field':
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $controller->updateField();
            exit;
            
        case 'save_columns':
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $controller->saveColumns();
            exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
    exit;
}

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

// Recalcula estatísticas
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
        if (isset($campaign['status']) && $campaign['status'] === 'active') {
            $stats['active_campaigns']++;
        }
        
        $stats['total_spent'] += floatval($campaign['spent'] ?? 0);
        $stats['total_revenue'] += floatval($campaign['real_revenue'] ?? 0);
        $stats['total_profit'] += floatval($campaign['real_profit'] ?? 0);
        $stats['total_sales'] += intval($campaign['real_sales'] ?? 0);
        $stats['total_impressions'] += intval($campaign['impressions'] ?? 0);
        $stats['total_clicks'] += intval($campaign['clicks'] ?? 0);
        $stats['total_conversions'] += intval($campaign['conversions'] ?? 0);
    }
    
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

// Define colunas visíveis
$visibleColumns = $userColumns ?? ['nome', 'status', 'orcamento', 'vendas', 'cpa', 'gastos', 'faturamento', 'lucro', 'roas', 'margem', 'roi'];

// Paths dos assets
$projectRoot = 'https://ataweb.com.br/utmtrack';
$cssPath = $projectRoot . '/assets/css/campaigns-dashboard.css?v=11.0';
$jsPath = $projectRoot . '/assets/js/campaigns-dashboard.js?v=11.0';
$debugMode = false;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTMTrack - Dashboard de Campanhas</title>
    
    <link rel="stylesheet" href="<?= $cssPath ?>">
    
    <script>
        window.userColumnsConfig = <?= json_encode($visibleColumns) ?>;
        window.debugMode = <?= $debugMode ? 'true' : 'false' ?>;
        window.baseUrl = '<?= $projectRoot ?>';
        window.currentPage = '<?= $_SERVER['REQUEST_URI'] ?>';
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

<!-- BARRA DE PERÍODO -->
<div style="background: var(--bg-primary); border-bottom: 1px solid var(--border); padding: 15px 30px;">
    <div style="display: flex; align-items: center; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <svg style="width: 16px; height: 16px; color: var(--text-secondary);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Período dos Dados
            </span>
        </div>
        
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button class="period-tab" data-period="today" onclick="changePeriod('today', this)">
                Hoje
            </button>
            <button class="period-tab" data-period="yesterday" onclick="changePeriod('yesterday', this)">
                Ontem
            </button>
            <button class="period-tab" data-period="last_7d" onclick="changePeriod('last_7d', this)">
                Últimos 7 dias
            </button>
            <button class="period-tab" data-period="last_30d" onclick="changePeriod('last_30d', this)">
                Últimos 30 dias
            </button>
            <button class="period-tab" data-period="this_month" onclick="changePeriod('this_month', this)">
                Este mês
            </button>
            <button class="period-tab" data-period="last_month" onclick="changePeriod('last_month', this)">
                Mês passado
            </button>
            <button class="period-tab active" data-period="maximum" onclick="changePeriod('maximum', this)">
                Máximo
            </button>
            <button class="period-tab" data-period="custom" onclick="toggleCustomPeriod(this)">
                <svg style="width: 14px; height: 14px; margin-right: 4px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Personalizado
            </button>
        </div>
    </div>
    
    <!-- Período personalizado -->
    <div class="custom-date-range" id="customDateRange" style="margin-top: 12px; display: none;">
        <input type="date" id="startDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" 
               style="padding: 8px 12px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 6px; color: var(--text-primary); font-size: 13px;">
        <span style="color: var(--text-secondary); font-size: 13px;">até</span>
        <input type="date" id="endDate" value="<?= date('Y-m-d') ?>" 
               style="padding: 8px 12px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 6px; color: var(--text-primary); font-size: 13px;">
        <button class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;" onclick="applyCustomPeriod()">
            Aplicar
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
    
    <button onclick="openColumnsModal()" class="btn btn-secondary" style="padding: 10px 16px;">
        <svg class="icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24">
            <line x1="4" y1="21" x2="4" y2="14"></line>
            <line x1="4" y1="10" x2="4" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12" y2="3"></line>
            <line x1="20" y1="21" x2="20" y2="16"></line>
            <line x1="20" y1="12" x2="20" y2="3"></line>
        </svg>
        Personalizar Colunas
    </button>
</div>

<!-- TABELA -->
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
                        <!-- NOME -->
                        <td data-column="nome">
                            <div class="campaign-name-cell" 
                                 onclick="makeEditableName(this, <?= $c['id'] ?>)"
                                 data-value="<?= htmlspecialchars($c['campaign_name']) ?>">
                                <?= htmlspecialchars($c['campaign_name']) ?>
                            </div>
                            <div class="campaign-id">ID: <?= htmlspecialchars($c['campaign_id']) ?></div>
                        </td>
                        
                        <!-- TOGGLE SWITCH VERDE MINIMALISTA -->
                        <td data-column="status">
                            <div class="status-with-toggle">
                                <label class="toggle-switch">
                                    <input 
                                        type="checkbox" 
                                        <?= $c['status'] === 'active' ? 'checked' : '' ?>
                                        onchange="toggleCampaignStatus(this, <?= $c['id'] ?>, '<?= htmlspecialchars($c['campaign_id']) ?>')"
                                        title="<?= $c['status'] === 'active' ? 'Clique para pausar' : 'Clique para ativar' ?>"
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </td>
                        
                        <!-- ORÇAMENTO -->
                        <td data-column="orcamento">
                            <div class="editable-field" 
                                 onclick="makeEditable(this, <?= $c['id'] ?>, 'budget', 'currency', '<?= htmlspecialchars($c['campaign_id']) ?>')"
                                 data-value="<?= $c['budget'] ?>">
                                R$ <?= number_format($c['budget'], 2, ',', '.') ?>
                            </div>
                        </td>
                        
                        <!-- VENDAS -->
                        <td data-column="vendas"><?= number_format($c['real_sales'], 0, ',', '.') ?></td>
                        
                        <!-- CPA -->
                        <td data-column="cpa" class="<?= $c['live_cpa'] > 0 ? '' : 'metric-neutral' ?>">
                            R$ <?= number_format($c['live_cpa'], 2, ',', '.') ?>
                        </td>
                        
                        <!-- GASTOS -->
                        <td data-column="gastos">
                            <strong>R$ <?= number_format($c['spent'], 2, ',', '.') ?></strong>
                        </td>
                        
                        <!-- FATURAMENTO -->
                        <td data-column="faturamento" class="<?= $c['real_revenue'] > 0 ? 'metric-positive' : 'metric-neutral' ?>">
                            R$ <?= number_format($c['real_revenue'], 2, ',', '.') ?>
                        </td>
                        
                        <!-- LUCRO -->
                        <td data-column="lucro" class="<?= $c['real_profit'] > 0 ? 'metric-positive' : ($c['real_profit'] < 0 ? 'metric-negative' : 'metric-neutral') ?>">
                            R$ <?= number_format($c['real_profit'], 2, ',', '.') ?>
                        </td>
                        
                        <!-- ROAS -->
                        <td data-column="roas" class="<?= $c['live_roas'] >= 2 ? 'metric-positive' : ($c['live_roas'] >= 1 ? '' : 'metric-negative') ?>">
                            <?= number_format($c['live_roas'], 2, ',', '.') ?>x
                        </td>
                        
                        <!-- MARGEM -->
                        <td data-column="margem" class="<?= $c['live_margin'] > 0 ? 'metric-positive' : 'metric-neutral' ?>">
                            <?= number_format($c['live_margin'], 2, ',', '.') ?>%
                        </td>
                        
                        <!-- ROI -->
                        <td data-column="roi" class="<?= $c['live_roi'] > 0 ? 'metric-positive' : ($c['live_roi'] < 0 ? 'metric-negative' : 'metric-neutral') ?>">
                            <?= number_format($c['live_roi'], 2, ',', '.') ?>%
                        </td>
                        
                        <!-- COLUNAS EXTRAS -->
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

<!-- MODAL COLUNAS -->
<div class="modal-overlay" id="columnsModal">
    <div class="columns-modal">
        <div class="columns-modal-header">
            <div class="columns-modal-title">
                <svg class="icon" style="width: 20px; height: 20px;" viewBox="0 0 24 24">
                    <line x1="4" y1="21" x2="4" y2="14"></line>
                    <line x1="4" y1="10" x2="4" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12" y2="3"></line>
                    <line x1="20" y1="21" x2="20" y2="16"></line>
                    <line x1="20" y1="12" x2="20" y2="3"></line>
                </svg>
                Personalizar Colunas
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

<!-- JAVASCRIPT EXTERNO (TODAS AS FUNÇÕES ESTÃO AQUI) -->
<script src="<?= $jsPath ?>"></script>

</body>
</html>