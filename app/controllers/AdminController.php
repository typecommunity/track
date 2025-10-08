<?php
/**
 * UTMTrack - Controller do Administrador
 * Arquivo: app/controllers/AdminController.php
 */

class AdminController extends Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Verifica se é admin
        if (!$this->auth->isAdmin()) {
            $this->redirect('../public/index.php?page=login');
        }
    }
    
    /**
     * Dashboard do Admin
     */
    public function dashboard() {
        // Busca estatísticas
        $stats = $this->getStats();
        
        $this->render('admin/dashboard', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'stats' => $stats,
            'pageTitle' => 'Dashboard Admin'
        ], 'layouts/admin_header');
    }
    
    /**
     * Gerenciar Clientes
     */
    public function clients() {
        // Busca todos os clientes
        $clients = $this->db->fetchAll("
            SELECT u.*, c.company_name, c.phone, c.city, c.state,
                   COUNT(DISTINCT aa.id) as ad_accounts,
                   COUNT(DISTINCT s.id) as total_sales,
                   SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as revenue
            FROM users u
            LEFT JOIN clients c ON c.user_id = u.id
            LEFT JOIN ad_accounts aa ON aa.user_id = u.id
            LEFT JOIN sales s ON s.user_id = u.id
            WHERE u.role = 'client'
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        
        $this->render('admin/clients', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'clients' => $clients,
            'pageTitle' => 'Gerenciar Clientes'
        ], 'layouts/admin_header');
    }
    
    /**
     * Configurações do Sistema
     */
    public function settings() {
        // Se for POST, salva configurações
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveSettings();
            return;
        }
        
        // Busca configurações
        $settings = $this->db->fetchAll("SELECT * FROM system_settings");
        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting['setting_key']] = $setting['setting_value'];
        }
        
        $this->render('admin/settings', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'settings' => $settingsArray,
            'pageTitle' => 'Configurações do Sistema'
        ], 'layouts/admin_header');
    }
    
    /**
     * Salva configurações
     */
    private function saveSettings() {
        $data = $this->post();
        
        foreach ($data as $key => $value) {
            $exists = $this->db->fetch(
                "SELECT id FROM system_settings WHERE setting_key = :key",
                ['key' => $key]
            );
            
            if ($exists) {
                $this->db->update('system_settings',
                    ['setting_value' => $value],
                    'setting_key = :key',
                    ['key' => $key]
                );
            } else {
                $this->db->insert('system_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value
                ]);
            }
        }
        
        $this->redirect('index.php?page=settings&success=1');
    }
    
    /**
     * Busca estatísticas gerais
     */
    private function getStats() {
        // Total de clientes
        $totalClients = $this->db->fetch("
            SELECT COUNT(*) as total FROM users WHERE role = 'client'
        ")['total'];
        
        // Total de contas de anúncio
        $totalAdAccounts = $this->db->fetch("
            SELECT COUNT(*) as total FROM ad_accounts WHERE status = 'active'
        ")['total'];
        
        // Total de vendas (mês atual)
        $totalSalesMonth = $this->db->fetch("
            SELECT 
                COUNT(*) as total,
                SUM(amount) as revenue
            FROM sales 
            WHERE status = 'approved'
            AND MONTH(created_at) = MONTH(CURRENT_DATE())
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
        ");
        
        // Novos clientes (últimos 30 dias)
        $newClients = $this->db->fetch("
            SELECT COUNT(*) as total 
            FROM users 
            WHERE role = 'client'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")['total'];
        
        return [
            'total_clients' => $totalClients,
            'total_ad_accounts' => $totalAdAccounts,
            'total_sales_month' => $totalSalesMonth['total'] ?? 0,
            'revenue_month' => $totalSalesMonth['revenue'] ?? 0,
            'new_clients' => $newClients
        ];
    }
}