<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= $config['name'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: #1e293b;
            padding: 20px;
            border-right: 1px solid #334155;
        }
        
        .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #334155;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 24px;
            font-weight: 700;
        }
        
        .logo .badge {
            display: inline-block;
            background: #dc2626;
            color: white;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 12px;
            margin-top: 5px;
            font-weight: 600;
        }
        
        .menu {
            list-style: none;
        }
        
        .menu li {
            margin-bottom: 5px;
        }
        
        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .menu a:hover {
            background: #334155;
            color: white;
        }
        
        .menu a.active {
            background: #667eea;
            color: white;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .logout-btn {
            padding: 10px 20px;
            background: #334155;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #475569;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 25px;
        }
        
        .stat-label {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: white;
        }
        
        .stat-change {
            display: inline-block;
            margin-top: 10px;
            font-size: 13px;
            padding: 4px 10px;
            border-radius: 20px;
        }
        
        .stat-change.positive {
            background: #10b98120;
            color: #10b981;
        }
        
        .content-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 30px;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #334155;
            color: #94a3b8;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        td {
            padding: 16px 12px;
            border-bottom: 1px solid #334155;
        }
        
        tr:hover {
            background: #334155;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #10b98120;
            color: #10b981;
        }
        
        .badge-warning {
            background: #f59e0b20;
            color: #f59e0b;
        }
        
        .badge-danger {
            background: #ef444420;
            color: #ef4444;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="logo">
                <h1>🎯 UTMTrack</h1>
                <span class="badge">ADMIN</span>
            </div>
            
            <ul class="menu">
                <li>
                    <a href="index.php?page=dashboard" class="<?= ($_GET['page'] ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>">
                        📊 Dashboard
                    </a>
                </li>
                <li>
                    <a href="index.php?page=clientes" class="<?= ($_GET['page'] ?? '') === 'clientes' ? 'active' : '' ?>">
                        👥 Clientes
                    </a>
                </li>
                <li>
                    <a href="index.php?page=configuracoes" class="<?= ($_GET['page'] ?? '') === 'configuracoes' ? 'active' : '' ?>">
                        ⚙️ Configurações
                    </a>
                </li>
                <li>
                    <a href="../public/index.php?page=logout">
                        🚪 Sair
                    </a>
                </li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="header">
                <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['name'], 0, 2)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($user['name']) ?></div>
                        <div style="font-size: 13px; color: #94a3b8;">Administrador</div>
                    </div>
                </div>
            </div>