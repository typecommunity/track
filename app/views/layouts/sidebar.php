<aside class="sidebar" style="
    position: fixed;
    left: 0;
    top: 0;
    width: 260px;
    height: 100vh;
    background: #1e293b;
    border-right: 1px solid #334155;
    overflow-y: auto;
    z-index: 1000;
">
    <div style="padding: 30px 20px; border-bottom: 1px solid #334155;">
        <div style="text-align: center;">
            <h1 style="color: #667eea; font-size: 28px; font-weight: 700; margin-bottom: 8px;">
                🎯 UTMTrack
            </h1>
            <p style="color: #64748b; font-size: 12px;">v1.0.0</p>
        </div>
    </div>
    
    <nav style="padding: 20px;">
        <ul style="list-style: none;">
            <?php 
            $currentPage = $_GET['page'] ?? 'dashboard';
            $menuItems = [
                ['icon' => '📊', 'label' => 'Resumo', 'page' => 'dashboard'],
                ['icon' => '📱', 'label' => 'Meta', 'page' => 'meta'],
                ['icon' => '🔍', 'label' => 'Google', 'page' => 'google'],
                ['icon' => '🎥', 'label' => 'Kwai', 'page' => 'kwai'],
                ['icon' => '📋', 'label' => 'UTMs', 'page' => 'utms'],
                ['icon' => '🔗', 'label' => 'Integrações', 'page' => 'integracoes'],
                ['icon' => '⚙️', 'label' => 'Regras', 'page' => 'regras'],
                ['icon' => '💰', 'label' => 'Taxas', 'page' => 'taxas'],
                ['icon' => '💳', 'label' => 'Despesas', 'page' => 'despesas'],
                ['icon' => '📈', 'label' => 'Relatórios', 'page' => 'relatorios'],
            ];
            
            foreach ($menuItems as $item):
                $isActive = $currentPage === $item['page'];
            ?>
            <li style="margin-bottom: 6px;">
                <a href="index.php?page=<?= $item['page'] ?>" style="
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 16px;
                    color: <?= $isActive ? 'white' : '#cbd5e1' ?>;
                    text-decoration: none;
                    border-radius: 8px;
                    transition: all 0.2s;
                    background: <?= $isActive ? '#667eea' : 'transparent' ?>;
                    font-weight: <?= $isActive ? '600' : '400' ?>;
                " onmouseover="if(!this.classList.contains('active')) this.style.background='#334155'" 
                   onmouseout="if(!this.classList.contains('active')) this.style.background='transparent'"
                   class="<?= $isActive ? 'active' : '' ?>">
                    <span style="font-size: 18px;"><?= $item['icon'] ?></span>
                    <span><?= $item['label'] ?></span>
                </a>
            </li>
            <?php endforeach; ?>
            
            <li style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #334155;">
                <a href="index.php?page=logout" style="
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 16px;
                    color: #cbd5e1;
                    text-decoration: none;
                    border-radius: 8px;
                    transition: all 0.2s;
                " onmouseover="this.style.background='#334155'" 
                   onmouseout="this.style.background='transparent'">
                    <span style="font-size: 18px;">🚪</span>
                    <span>Sair</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<div class="main-wrapper">
    <div class="top-bar">
        <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
        
        <div class="top-bar-right">
            <div class="user-menu">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['name'], 0, 2)) ?>
                </div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div style="font-size: 11px; color: #64748b;">
                        <?= $user['role'] === 'admin' ? 'Administrador' : 'Cliente' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content-area">