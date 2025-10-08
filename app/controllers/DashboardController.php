<?php
/**
 * UTMTrack - Controller do Dashboard
 */

class DashboardController extends Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Verifica se está logado
        $this->auth->middleware();
    }
    
    /**
     * Dashboard Principal
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca métricas do usuário
        $metrics = $this->getMetrics($userId);
        
        // Busca vendas por pagamento
        $salesByPayment = $this->getSalesByPayment($userId);
        
        // Busca vendas por produto
        $salesByProduct = $this->getSalesByProduct($userId);
        
        $this->render('dashboard/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'metrics' => $metrics,
            'salesByPayment' => $salesByPayment,
            'salesByProduct' => $salesByProduct
        ]);
    }
    
    /**
     * Busca métricas do dashboard
     */
    private function getMetrics($userId) {
        // Período selecionado (hoje por padrão)
        $period = $this->get('period', 'today');
        
        // Define datas
        switch ($period) {
            case 'today':
                $startDate = date('Y-m-d 00:00:00');
                $endDate = date('Y-m-d 23:59:59');
                break;
            case 'yesterday':
                $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $endDate = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            case 'week':
                $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
                $endDate = date('Y-m-d 23:59:59');
                break;
            case 'month':
                $startDate = date('Y-m-01 00:00:00');
                $endDate = date('Y-m-d 23:59:59');
                break;
            default:
                $startDate = date('Y-m-d 00:00:00');
                $endDate = date('Y-m-d 23:59:59');
        }
        
        // Faturamento líquido e vendas
        $revenue = $this->db->fetch("
            SELECT 
                COUNT(*) as total_sales,
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as revenue,
                SUM(CASE WHEN status = 'approved' THEN cost ELSE 0 END) as cost,
                SUM(CASE WHEN status = 'approved' THEN tax ELSE 0 END) as tax,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as refunded
            FROM sales 
            WHERE user_id = :user_id
            AND created_at BETWEEN :start_date AND :end_date
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        // Gastos com anúncios
        $adSpend = $this->db->fetch("
            SELECT SUM(spent) as total_spent
            FROM campaigns
            WHERE user_id = :user_id
            AND updated_at BETWEEN :start_date AND :end_date
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        $totalRevenue = $revenue['revenue'] ?? 0;
        $totalCost = $revenue['cost'] ?? 0;
        $totalTax = $revenue['tax'] ?? 0;
        $totalSpent = $adSpend['total_spent'] ?? 0;
        
        // Cálculos
        $netRevenue = $totalRevenue - $totalCost - $totalTax;
        $profit = $netRevenue - $totalSpent;
        $roas = $totalSpent > 0 ? $totalRevenue / $totalSpent : 0;
        $roi = $totalSpent > 0 ? (($profit / $totalSpent) * 100) : 0;
        $margin = $totalRevenue > 0 ? (($profit / $totalRevenue) * 100) : 0;
        
        return [
            'net_revenue' => $netRevenue,
            'ad_spend' => $totalSpent,
            'roas' => $roas,
            'profit' => $profit,
            'pending_sales' => $revenue['pending'] ?? 0,
            'refunded_sales' => $revenue['refunded'] ?? 0,
            'roi' => $roi,
            'margin' => $margin,
            'tax' => $totalTax,
            'cost' => $totalCost,
            'total_sales' => $revenue['total_sales'] ?? 0,
            'period' => $period
        ];
    }
    
    /**
     * Vendas por método de pagamento
     */
    private function getSalesByPayment($userId) {
        return $this->db->fetchAll("
            SELECT 
                payment_method,
                COUNT(*) as total,
                SUM(amount) as revenue
            FROM sales
            WHERE user_id = :user_id
            AND status = 'approved'
            GROUP BY payment_method
        ", ['user_id' => $userId]);
    }
    
    /**
     * Vendas por produto
     */
    private function getSalesByProduct($userId) {
        return $this->db->fetchAll("
            SELECT 
                p.name as product_name,
                COUNT(s.id) as total,
                SUM(s.amount) as revenue
            FROM sales s
            LEFT JOIN products p ON p.id = s.product_id
            WHERE s.user_id = :user_id
            AND s.status = 'approved'
            GROUP BY s.product_id
            ORDER BY revenue DESC
            LIMIT 10
        ", ['user_id' => $userId]);
    }
}