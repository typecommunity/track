<?php
/**
 * CAMINHO: /utmtrack/app/views/adsets/index.php
 * Dashboard de Conjuntos de Anúncios
 */

// PROCESSA REQUISIÇÕES AJAX
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
    require_once dirname(__DIR__) . '/controllers/AdSetController.php';
    
    $controller = new AdSetController();
    
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

// Valores padrão
$adsets = $adsets ?? [];
$stats = $stats ?? [];
$userColumns = $userColumns ?? null;

// Colunas visíveis
$visibleColumns = $userColumns ?? ['nome', 'status', 'orcamento', 'vendas', 'cpa', 'gastos', 'faturamento', 'lucro', 'roas', 'margem', 'roi'];

// Assets
$projectRoot = 'https://ataweb.com.br/utmtrack';
$cssPath = $projectRoot . '/assets/css/campaigns-dashboard.css?v=14.1';
$jsPath = $projectRoot . '/assets/js/adsets-dashboard.js?v=14.1';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTMTrack - Conjuntos de Anúncios</title>
    <link rel="stylesheet" href="<?= $cssPath ?>">
    <script>
        window.userColumnsConfig = <?= json_encode($visibleColumns) ?>;
        window.baseUrl = '<?= $projectRoot ?>';
    </script>
</head>
<body>

<!-- TABS DE NAVEGAÇÃO -->
<div class="tabs-container">
    <button class="tab-button" onclick="window.location.href='index.php?page=integracoes-meta-contas'">
        <svg class="icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        Contas
    </button>
    <button class="tab-button" onclick="window.location.href='index.php?page=campanhas'">
        <svg class="icon" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        Campanhas
    </button>
    <button class="tab-button active">
        <svg class="icon" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
        Conjuntos
    </button>
    <button class="tab-button" onclick="window.location.href='index.php?page=anuncios'">
        <svg class="icon" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        Anúncios
    </button>
</div>

<!-- TOOLBAR -->
<div class="toolbar">
    <div class="toolbar-left">
        <div class="status-indicator">Sistema Online</div>
    </div>
    <div class="toolbar-right">
        <span style="color: var(--text-secondary); font-size: 13px;">
            <?= $stats['total_adsets'] ?? 0 ?> conjuntos | <?= $stats['active_adsets'] ?? 0 ?> ativos
        </span>
        <button onclick="syncAllAdSets()" class="btn btn-primary" style="padding: 8px 20px;">
            <svg class="icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Atualizar Tudo
        </button>
    </div>
</div>

<!-- FILTROS -->
<div class="filters-bar">
    <input type="text" class="filter-input" id="searchInput" placeholder="Filtrar por nome..." onkeyup="filterTable()">
    <select class="filter-select" id="statusFilter" onchange="filterTable()">
        <option value="">Todos os Status</option>
        <option value="active">Ativados</option>
        <option value="paused">Desativados</option>
    </select>
    <button onclick="openColumnsModal()" class="btn btn-secondary" style="padding: 10px 16px;">
        <svg class="icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line></svg>
        Personalizar Colunas
    </button>
</div>

<!-- TABELA -->
<div class="table-wrapper">
    <div class="table-container">
        <table class="campaigns-table">
            <thead>
                <tr id="tableHeader"></tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($adsets)): ?>
                <tr>
                    <td colspan="25" style="border: none;">
                        <div class="empty-state">
                            <div class="empty-state-icon">📊</div>
                            <div class="empty-state-title">Nenhum conjunto encontrado</div>
                            <p class="empty-state-description">Sincronize suas campanhas para ver os conjuntos</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($adsets as $a): ?>
                    <tr data-id="<?= $a['id'] ?>" 
                        data-adset-id="<?= htmlspecialchars($a['adset_id']) ?>"
                        data-name="<?= strtolower($a['adset_name']) ?>" 
                        data-status="<?= $a['status'] ?>">
                        
                        <!-- NOME -->
                        <td data-column="nome">
                            <div class="campaign-name-cell" 
                                 onclick="makeEditableName(this, <?= $a['id'] ?>)"
                                 data-value="<?= htmlspecialchars($a['adset_name']) ?>">
                                <?= htmlspecialchars($a['adset_name']) ?>
                            </div>
                            <div class="campaign-id">
                                Campanha: <?= htmlspecialchars($a['campaign_name']) ?>
                            </div>
                        </td>
                        
                        <!-- STATUS TOGGLE -->
                        <td data-column="status">
                            <div class="status-with-toggle">
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           <?= $a['status'] === 'active' ? 'checked' : '' ?>
                                           onchange="toggleAdSetStatus(this, <?= $a['id'] ?>, '<?= htmlspecialchars($a['adset_id']) ?>')">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </td>
                        
                        <!-- ORÇAMENTO -->
                        <td data-column="orcamento">
                            <div class="editable-field" 
                                 onclick="makeEditable(this, <?= $a['id'] ?>, 'daily_budget', 'currency', '<?= htmlspecialchars($a['adset_id']) ?>')"
                                 data-value="<?= $a['daily_budget'] ?>">
                                R$ <?= number_format($a['daily_budget'], 2, ',', '.') ?>
                            </div>
                        </td>
                        
                        <!-- VENDAS -->
                        <td data-column="vendas"><?= number_format($a['real_sales'] ?? 0, 0, ',', '.') ?></td>
                        
                        <!-- CPA -->
                        <td data-column="cpa">R$ <?= number_format($a['live_cpa'] ?? 0, 2, ',', '.') ?></td>
                        
                        <!-- GASTOS -->
                        <td data-column="gastos"><strong>R$ <?= number_format($a['spent'], 2, ',', '.') ?></strong></td>
                        
                        <!-- FATURAMENTO -->
                        <td data-column="faturamento" class="<?= ($a['real_revenue'] ?? 0) > 0 ? 'metric-positive' : 'metric-neutral' ?>">
                            R$ <?= number_format($a['real_revenue'] ?? 0, 2, ',', '.') ?>
                        </td>
                        
                        <!-- LUCRO -->
                        <td data-column="lucro" class="<?= ($a['real_profit'] ?? 0) > 0 ? 'metric-positive' : (($a['real_profit'] ?? 0) < 0 ? 'metric-negative' : 'metric-neutral') ?>">
                            R$ <?= number_format($a['real_profit'] ?? 0, 2, ',', '.') ?>
                        </td>
                        
                        <!-- ROAS -->
                        <td data-column="roas"><?= number_format($a['live_roas'] ?? 0, 2, ',', '.') ?>x</td>
                        
                        <!-- MARGEM -->
                        <td data-column="margem"><?= number_format($a['live_margin'] ?? 0, 2, ',', '.') ?>%</td>
                        
                        <!-- ROI -->
                        <td data-column="roi"><?= number_format($a['live_roi'] ?? 0, 2, ',', '.') ?>%</td>
                        
                        <!-- MÉTRICAS EXTRAS -->
                        <td data-column="cpc">R$ <?= number_format($a['cpc'] ?? 0, 2, ',', '.') ?></td>
                        <td data-column="ctr"><?= number_format($a['ctr'] ?? 0, 2, ',', '.') ?>%</td>
                        <td data-column="cpm">R$ <?= number_format($a['cpm'] ?? 0, 2, ',', '.') ?></td>
                        <td data-column="impressoes"><?= number_format($a['impressions'], 0, ',', '.') ?></td>
                        <td data-column="cliques"><?= number_format($a['clicks'], 0, ',', '.') ?></td>
                        <td data-column="conversoes"><?= number_format($a['conversions'], 0, ',', '.') ?></td>
                        <td data-column="conta"><?= htmlspecialchars($a['account_name']) ?></td>
                        <td data-column="ultima_sync"><?= date('d/m/Y H:i', strtotime($a['last_sync'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL COLUNAS (mesmo código do campaigns/index.php) -->
<div class="modal-overlay" id="columnsModal">
    <div class="columns-modal">
        <div class="columns-modal-header">
            <div class="columns-modal-title">Personalizar Colunas</div>
            <div class="columns-modal-subtitle">Escolha e organize as colunas</div>
        </div>
        <div class="columns-modal-left">
            <input type="text" class="columns-search" placeholder="Buscar coluna..." id="columnSearch" onkeyup="filterColumns()">
            <div id="availableColumns"></div>
        </div>
        <div class="columns-modal-right">
            <div style="margin-bottom: 12px; color: var(--text-secondary); font-size: 13px; font-weight: 600;">
                COLUNAS SELECIONADAS
            </div>
            <div id="selectedColumns"></div>
        </div>
        <div class="columns-modal-footer">
            <button class="btn btn-secondary" onclick="closeColumnsModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveColumns()">Salvar</button>
        </div>
    </div>
</div>

<script src="<?= $jsPath ?>"></script>
</body>
</html>