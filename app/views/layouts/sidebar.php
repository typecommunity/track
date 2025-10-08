<style>
/* ============================================
   SIDEBAR
   ============================================ */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 260px;
    height: 100vh;
    background: #1e293b;
    border-right: 1px solid #334155;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
    transition: width 0.3s ease;
}

.sidebar.collapsed {
    width: 70px;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 80px;
}

.sidebar-logo-container {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.sidebar-logo {
    color: #667eea;
    font-size: 28px;
    font-weight: 700;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar.collapsed .sidebar-logo-text {
    display: none;
}

.sidebar-version {
    color: #64748b;
    font-size: 12px;
    opacity: 1;
    transition: opacity 0.2s ease;
}

.sidebar.collapsed .sidebar-version {
    display: none;
}

/* Botão Toggle DENTRO do Header */
.sidebar-toggle {
    width: 36px;
    height: 36px;
    background: transparent;
    border: 1px solid #334155;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #cbd5e1;
}

.sidebar-toggle:hover {
    background: #334155;
    color: white;
    border-color: #667eea;
}

.sidebar-toggle svg {
    width: 20px;
    height: 20px;
}

.sidebar.collapsed .sidebar-toggle svg {
    transform: rotate(180deg);
}

/* Navigation */
.sidebar-nav {
    padding: 20px;
}

.sidebar.collapsed .sidebar-nav {
    padding: 20px 10px;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #cbd5e1;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s;
    margin-bottom: 6px;
    position: relative;
    white-space: nowrap;
}

.sidebar.collapsed .menu-item {
    padding: 12px;
    justify-content: center;
}

.menu-item:hover {
    background: #334155;
}

.menu-item.active {
    background: #667eea;
    color: white;
    font-weight: 600;
}

.menu-icon {
    width: 20px;
    height: 20px;
    min-width: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.menu-icon svg {
    width: 100%;
    height: 100%;
}

.menu-label {
    opacity: 1;
    transition: opacity 0.2s ease;
}

.sidebar.collapsed .menu-label {
    opacity: 0;
    width: 0;
    overflow: hidden;
}

.menu-tooltip {
    position: absolute;
    left: 60px;
    background: #0f172a;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
    z-index: 1002;
    border: 1px solid #334155;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.menu-tooltip::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
    border: 6px solid transparent;
    border-right-color: #334155;
}

.sidebar.collapsed .menu-item:hover .menu-tooltip {
    opacity: 1;
}

.menu-divider {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #334155;
}

/* ============================================
   MAIN WRAPPER - ALINHAMENTO PERFEITO
   ============================================ */
.main-wrapper {
    margin-left: 260px;
    min-height: 100vh;
    background: #0f172a;
    transition: margin-left 0.3s ease;
}

.sidebar.collapsed ~ .main-wrapper {
    margin-left: 70px;
}

/* ============================================
   TOP BAR
   ============================================ */
.top-bar {
    background: #1e293b;
    border-bottom: 1px solid #334155;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: 80px;
    position: sticky;
    top: 0;
    z-index: 900;
}

.top-bar-left {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: white;
    margin: 0;
}

/* Breadcrumbs */
.breadcrumbs {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.breadcrumb-item {
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumb-item:hover {
    color: #e2e8f0;
}

.breadcrumb-item.active {
    color: #667eea;
    font-weight: 600;
}

.breadcrumb-separator {
    color: #475569;
    display: flex;
    align-items: center;
}

.breadcrumb-separator svg {
    width: 14px;
    height: 14px;
}

/* Top Bar Right */
.top-bar-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    gap: 8px;
}

.icon-btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 1px solid #334155;
    background: transparent;
    color: #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.icon-btn:hover {
    background: #334155;
    color: white;
    transform: translateY(-1px);
}

.icon-btn svg {
    width: 20px;
    height: 20px;
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    border: 2px solid #1e293b;
}

/* User Menu */
.user-menu-wrapper {
    position: relative;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid #334155;
    transition: all 0.2s;
}

.user-menu:hover {
    background: #334155;
    border-color: #475569;
}

.user-avatar {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.user-name {
    font-weight: 600;
    font-size: 14px;
    color: white;
    line-height: 1;
}

.user-role {
    font-size: 11px;
    color: #64748b;
    line-height: 1;
}

.dropdown-arrow {
    color: #64748b;
    transition: transform 0.2s;
}

.user-menu:hover .dropdown-arrow {
    color: #94a3b8;
}

/* User Dropdown */
.user-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    min-width: 260px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 1000;
}

.user-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    display: flex;
    gap: 12px;
    padding: 16px;
    align-items: center;
}

.dropdown-user-avatar {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

.dropdown-user-name {
    font-weight: 600;
    font-size: 14px;
    color: white;
    margin-bottom: 4px;
}

.dropdown-user-email {
    font-size: 12px;
    color: #64748b;
}

.dropdown-divider {
    height: 1px;
    background: #334155;
    margin: 8px 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #cbd5e1;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 14px;
}

.dropdown-item:hover {
    background: #334155;
    color: white;
}

.dropdown-item svg {
    width: 18px;
    height: 18px;
    color: #64748b;
}

.dropdown-item:hover svg {
    color: #94a3b8;
}

.dropdown-item.text-danger {
    color: #f87171;
}

.dropdown-item.text-danger:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.dropdown-item.text-danger svg {
    color: #f87171;
}

/* Content Area */
.content-area {
    padding: 30px;
    min-height: calc(100vh - 80px);
}

/* Responsivo */
@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }
    
    .main-wrapper {
        margin-left: 70px;
    }
    
    .top-bar {
        padding: 16px 20px;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .breadcrumbs {
        display: none;
    }
    
    .user-info {
        display: none;
    }
}
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-container">
            <div class="sidebar-logo">
                🎯 
                <span class="sidebar-logo-text">UTMTrack</span>
            </div>
        </div>
        
        <button class="sidebar-toggle" onclick="toggleSidebar()" title="Recolher Menu (Ctrl+B)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
    </div>
    
    <p class="sidebar-version" style="text-align: center; padding: 0 20px 10px; margin: 0;">v1.0.0</p>
    
    <nav class="sidebar-nav">
        <ul>
            <?php 
            $currentPage = $_GET['page'] ?? 'dashboard';
            $menuItems = [
                [
                    'label' => 'Resumo',
                    'page' => 'dashboard',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>'
                ],
                [
                    'label' => 'Integrações',
                    'page' => 'integracoes',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m-6-6H1m6 0h6m6 0h6m-6 0h-6"></path><circle cx="12" cy="5" r="2"></circle><circle cx="12" cy="19" r="2"></circle><circle cx="5" cy="12" r="2"></circle><circle cx="19" cy="12" r="2"></circle></svg>'
                ],
                [
                    'label' => 'Campanhas',
                    'page' => 'campanhas',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10M12 20V4M6 20v-6"></path></svg>'
                ],
                [
                    'label' => 'UTMs',
                    'page' => 'utms',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>'
                ],
                [
                    'label' => 'Produtos',
                    'page' => 'produtos',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>'
                ],
                [
                    'label' => 'Webhooks',
                    'page' => 'webhooks',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>'
                ],
                [
                    'label' => 'Regras',
                    'page' => 'regras',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>'
                ],
                [
                    'label' => 'Taxas',
                    'page' => 'taxas',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>'
                ],
                [
                    'label' => 'Despesas',
                    'page' => 'despesas',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>'
                ],
                [
                    'label' => 'Relatórios',
                    'page' => 'relatorios',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>'
                ],
            ];
            
            foreach ($menuItems as $item):
                $isActive = $currentPage === $item['page'] || 
                           (strpos($currentPage, $item['page']) === 0 && $item['page'] !== 'dashboard');
            ?>
            <li>
                <a href="index.php?page=<?= $item['page'] ?>" class="menu-item <?= $isActive ? 'active' : '' ?>">
                    <span class="menu-icon">
                        <?= $item['icon'] ?>
                    </span>
                    <span class="menu-label"><?= $item['label'] ?></span>
                    <span class="menu-tooltip"><?= $item['label'] ?></span>
                </a>
            </li>
            <?php endforeach; ?>
            
            <li class="menu-divider">
                <a href="index.php?page=logout" class="menu-item">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </span>
                    <span class="menu-label">Sair</span>
                    <span class="menu-tooltip">Sair</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<div class="main-wrapper">
    <div class="top-bar">
        <div class="top-bar-left">
            <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
            
            <?php
            $breadcrumbs = [];
            $currentPage = $_GET['page'] ?? 'dashboard';
            
            switch($currentPage) {
                case 'dashboard':
                    $breadcrumbs = [['label' => 'Resumo', 'url' => '']];
                    break;
                case 'integracoes':
                    $breadcrumbs = [['label' => 'Integrações', 'url' => '']];
                    break;
                case 'integracoes-meta':
                    $breadcrumbs = [
                        ['label' => 'Integrações', 'url' => 'index.php?page=integracoes'],
                        ['label' => 'Meta Ads', 'url' => '']
                    ];
                    break;
                case 'campanhas':
                    $breadcrumbs = [['label' => 'Campanhas', 'url' => '']];
                    break;
                default:
                    $breadcrumbs = [['label' => ucfirst($currentPage), 'url' => '']];
            }
            ?>
            
            <?php if (count($breadcrumbs) > 0): ?>
            <div class="breadcrumbs">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php if ($crumb['url']): ?>
                        <a href="<?= $crumb['url'] ?>" class="breadcrumb-item">
                            <?= $crumb['label'] ?>
                        </a>
                        <span class="breadcrumb-separator">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </span>
                    <?php else: ?>
                        <span class="breadcrumb-item active"><?= $crumb['label'] ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="top-bar-right">
            <div class="quick-actions">
                <button class="icon-btn" onclick="window.location.reload()" title="Atualizar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                    </svg>
                </button>
                
                <button class="icon-btn" onclick="toggleNotifications()" title="Notificações">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span class="notification-badge">3</span>
                </button>
            </div>
            
            <div class="user-menu-wrapper">
                <div class="user-menu" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['name'], 0, 2)) ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                        <div class="user-role">
                            <?= $user['role'] === 'admin' ? 'Administrador' : 'Cliente' ?>
                        </div>
                    </div>
                    <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
                
                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-user-avatar">
                            <?= strtoupper(substr($user['name'], 0, 2)) ?>
                        </div>
                        <div>
                            <div class="dropdown-user-name"><?= htmlspecialchars($user['name']) ?></div>
                            <div class="dropdown-user-email"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                    </div>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="index.php?page=perfil" class="dropdown-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Meu Perfil
                    </a>
                    
                    <a href="index.php?page=configuracoes" class="dropdown-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m5.2-14.2l-4.2 4.2m0 6l4.2 4.2M23 12h-6m-6 0H1m14.2 5.2l-4.2-4.2m0-6l-4.2-4.2"/>
                        </svg>
                        Configurações
                    </a>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="index.php?page=logout" class="dropdown-item text-danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content-area">

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
}

document.addEventListener('DOMContentLoaded', function() {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        document.getElementById('sidebar').classList.add('collapsed');
    }
});

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        toggleSidebar();
    }
});

function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    const userMenu = document.querySelector('.user-menu-wrapper');
    const dropdown = document.getElementById('userDropdown');
    
    if (!userMenu.contains(e.target) && dropdown.classList.contains('show')) {
        dropdown.classList.remove('show');
    }
});

function toggleNotifications() {
    alert('Você tem 3 notificações:\n\n1. Campanha "Black Friday" atingiu ROAS de 5.2x\n2. Nova venda: R$ 297,00\n3. Sincronização Meta Ads concluída');
}
</script>