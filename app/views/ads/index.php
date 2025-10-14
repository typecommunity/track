<?php
/**
 * CAMINHO: /utmtrack/app/views/ads/index.php
 * Dashboard de Anúncios
 * (MESMO PADRÃO - adaptar nomes de adsets para ads)
 */

// AJAX HANDLERS (mesma lógica)
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
    require_once dirname(__DIR__) . '/controllers/AdController.php';
    
    $controller = new AdController();
    
    switch ($_GET['ajax_action']) {
        case 'sync_all':
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $controller->syncAll();
            exit;
        case 'update_status':
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $controller->updateMetaStatus();
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
$ads = $ads ?? [];
$stats = $stats ?? [];
$userColumns = $userColumns ?? null;

// Colunas visíveis
$visibleColumns = $userColumns ?? ['nome', 'status', 'vendas', 'cpa', 'gastos', 'faturamento', 'lucro', 'roas', 'margem', 'roi'];

// Assets
$projectRoot = 'https://ataweb.com.br/utmtrack';
$cssPath = $projectRoot . '/assets/css/campaigns-dashboard.css?v=14.1';
$jsPath = $projectRoot . '/assets/js/ads-dashboard.js?v=14.1';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTMTrack - Anúncios</title>
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
    <button class="tab-button" onclick="window.location.href='index.php?page=conjuntos'">
        <svg class="icon" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
        Conjuntos
    </button>
    <button class="tab-button active">
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
            <?= $stats['total_ads'] ?? 0 ?> anúncios | <?= $stats['active_ads'] ?? 0 ?> ativos
        </span>
        <button onclick="syncAllAds()" class="btn btn-primary" style="padding: 8px 20px;">
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
        Personalizar Colunas
    </button>
</div>

<!-- TABELA (mesma estrutura, adaptar campos) -->
<div class="table-wrapper">
    <div class="table-container">
        <table class="campaigns-table">
            <thead>
                <tr id="tableHeader"></tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($ads)): ?>
                <tr>
                    <td colspan="25" style="border: none;">
                        <div class="empty-state">
                            <div class="empty-state-icon">📝</div>
                            <div class="empty-state-title">Nenhum anúncio encontrado</div>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($ads as $ad): ?>
                    <tr data-id="<?= $ad['id'] ?>" 
                        data-ad-id="<?= htmlspecialchars($ad['ad_id']) ?>"
                        data-name="<?= strtolower($ad['ad_name']) ?>" 
                        data-status="<?= $ad['status'] ?>">
                        
                        <!-- NOME -->
                        <td data-column="nome">
                            <div class="campaign-name-cell" 
                                 onclick="makeEditableName(this, <?= $ad['id'] ?>)"
                                 data-value="<?= htmlspecialchars($ad['ad_name']) ?>">
                                <?= htmlspecialchars($ad['ad_name']) ?>
                            </div>
                            <div class="campaign-id">
                                Conjunto: <?= htmlspecialchars($ad['adset_name']) ?>
                            </div>
                        </td>
                        
                        <!-- STATUS TOGGLE -->
                        <td data-column="status">
                            <div class="status-with-toggle">
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           <?= $ad['status'] === 'active' ? 'checked' : '' ?>
                                           onchange="toggleAdStatus(this, <?= $ad['id'] ?>, '<?= htmlspecialchars($ad['ad_id']) ?>')">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </td>
                        
                        <!-- DEMAIS COLUNAS (mesma estrutura) -->
                        <td data-column="vendas"><?= number_format($ad['real_sales'] ?? 0, 0, ',', '.') ?></td>
                        <td data-column="cpa">R$ <?= number_format($ad['live_cpa'] ?? 0, 2, ',', '.') ?></td>
                        <td data-column="gastos"><strong>R$ <?= number_format($ad['spent'], 2, ',', '.') ?></strong></td>
                        <td data-column="faturamento">R$ <?= number_format($ad['real_revenue'] ?? 0, 2, ',', '.') ?></td>
                        <td data-column="lucro">R$ <?= number_format($ad['real_profit'] ?? 0, 2, ',', '.') ?></td>
                        <td data-column="roas"><?= number_format($ad['live_roas'] ?? 0, 2, ',', '.') ?>x</td>
                        <td data-column="margem"><?= number_format($ad['live_margin'] ?? 0, 2, ',', '.') ?>%</td>
                        <td data-column="roi"><?= number_format($ad['live_roi'] ?? 0, 2, ',', '.') ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL (mesmo código) -->
<div class="modal-overlay" id="columnsModal">
    <!-- Mesmo HTML do modal de campanhas -->
</div>

<script src="<?= $jsPath ?>"></script>
</body>
</html>