<?php
/**
 * UTMTrack - Controller de Relatórios
 * Arquivo: app/controllers/ReportController.php
 */

class ReportController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Dashboard de Relatórios
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Período
        $startDate = $this->get('start_date', date('Y-m-01'));
        $endDate = $this->get('end_date', date('Y-m-d'));
        
        // Dados para gráfico de vendas por dia
        $salesByDay = $this->getSalesByDay($userId, $startDate, $endDate);
        
        // Vendas por fonte
        $salesBySource = $this->getSalesBySource($userId, $startDate, $endDate);
        
        // Vendas por produto
        $salesByProduct = $this->getSalesByProduct($userId, $startDate, $endDate);
        
        // Funil de conversão
        $funnelData = $this->getFunnelData($userId, $startDate, $endDate);
        
        // Top campanhas
        $topCampaigns = $this->getTopCampaigns($userId, $startDate, $endDate);
        
        // Comparação com período anterior
        $comparison = $this->getComparison($userId, $startDate, $endDate);
        
        $this->render('reports/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'salesByDay' => $salesByDay,
            'salesBySource' => $salesBySource,
            'salesByProduct' => $salesByProduct,
            'funnelData' => $funnelData,
            'topCampaigns' => $topCampaigns,
            'comparison' => $comparison,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'pageTitle' => 'Relatórios'
        ]);
    }
    
    /**
     * Vendas por dia
     */
    private function getSalesByDay($userId, $startDate, $endDate) {
        return $this->db->fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_sales,
                SUM(amount) as revenue,
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_revenue
            FROM sales
            WHERE user_id = :user_id
            AND DATE(created_at) BETWEEN :start_date AND :end_date
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
    
    /**
     * Vendas por fonte (UTM Source)
     */
    private function getSalesBySource($userId, $startDate, $endDate) {
        return $this->db->fetchAll("
            SELECT 
                u.utm_source as source,
                COUNT(s.id) as total_sales,
                SUM(s.amount) as revenue
            FROM sales s
            LEFT JOIN utms u ON u.id = s.utm_id
            WHERE s.user_id = :user_id
            AND s.status = 'approved'
            AND DATE(s.created_at) BETWEEN :start_date AND :end_date
            AND u.utm_source IS NOT NULL
            GROUP BY u.utm_source
            ORDER BY revenue DESC
            LIMIT 10
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
    
    /**
     * Vendas por produto
     */
    private function getSalesByProduct($userId, $startDate, $endDate) {
        return $this->db->fetchAll("
            SELECT 
                p.name as product_name,
                COUNT(s.id) as total_sales,
                SUM(s.amount) as revenue,
                SUM(s.amount - s.cost) as profit
            FROM sales s
            LEFT JOIN products p ON p.id = s.product_id
            WHERE s.user_id = :user_id
            AND s.status = 'approved'
            AND DATE(s.created_at) BETWEEN :start_date AND :end_date
            GROUP BY s.product_id
            ORDER BY revenue DESC
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
    
    /**
     * Dados do funil de conversão
     */
    private function getFunnelData($userId, $startDate, $endDate) {
        // Page views
        $pageViews = $this->db->fetch("
            SELECT COUNT(*) as total
            FROM funnel_events
            WHERE user_id = :user_id
            AND event_type = 'page_view'
            AND DATE(created_at) BETWEEN :start_date AND :end_date
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ])['total'] ?? 0;
        
        // Initiate Checkout
        $initiateCheckout = $this->db->fetch("
            SELECT COUNT(*) as total
            FROM funnel_events
            WHERE user_id = :user_id
            AND event_type = 'initiate_checkout'
            AND DATE(created_at) BETWEEN :start_date AND :end_date
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ])['total'] ?? 0;
        
        // Purchases
        $purchases = $this->db->fetch("
            SELECT COUNT(*) as total
            FROM sales
            WHERE user_id = :user_id
            AND status = 'approved'
            AND DATE(created_at) BETWEEN :start_date AND :end_date
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ])['total'] ?? 0;
        
        return [
            'page_views' => $pageViews,
            'initiate_checkout' => $initiateCheckout,
            'purchases' => $purchases,
            'checkout_rate' => $pageViews > 0 ? ($initiateCheckout / $pageViews) * 100 : 0,
            'conversion_rate' => $initiateCheckout > 0 ? ($purchases / $initiateCheckout) * 100 : 0,
            'overall_rate' => $pageViews > 0 ? ($purchases / $pageViews) * 100 : 0
        ];
    }
    
    /**
     * Top campanhas
     */
    private function getTopCampaigns($userId, $startDate, $endDate) {
        return $this->db->fetchAll("
            SELECT 
                c.campaign_name,
                c.spent,
                COUNT(s.id) as total_sales,
                SUM(s.amount) as revenue,
                (SUM(s.amount) / NULLIF(c.spent, 0)) as roas
            FROM campaigns c
            LEFT JOIN sales s ON s.campaign_id = c.id AND s.status = 'approved'
            WHERE c.user_id = :user_id
            AND DATE(c.updated_at) BETWEEN :start_date AND :end_date
            GROUP BY c.id
            HAVING revenue > 0
            ORDER BY revenue DESC
            LIMIT 10
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
    
    /**
     * Comparação com período anterior
     */
    private function getComparison($userId, $startDate, $endDate) {
        // Calcula período anterior
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $days = $interval->days + 1;
        
        $previousStart = (clone $start)->modify("-{$days} days")->format('Y-m-d');
        $previousEnd = (clone $start)->modify('-1 day')->format('Y-m-d');
        
        // Período atual
        $current = $this->db->fetch("
            SELECT 
                COUNT(*) as total_sales,
                SUM(amount) as revenue,
                AVG(amount) as avg_ticket
            FROM sales
            WHERE user_id = :user_id
            AND status = 'approved'
            AND DATE(created_at) BETWEEN :start_date AND :end_date
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        // Período anterior
        $previous = $this->db->fetch("
            SELECT 
                COUNT(*) as total_sales,
                SUM(amount) as revenue,
                AVG(amount) as avg_ticket
            FROM sales
            WHERE user_id = :user_id
            AND status = 'approved'
            AND DATE(created_at) BETWEEN :start_date AND :end_date
        ", [
            'user_id' => $userId,
            'start_date' => $previousStart,
            'end_date' => $previousEnd
        ]);
        
        // Calcula variação
        $salesGrowth = $previous['total_sales'] > 0 
            ? (($current['total_sales'] - $previous['total_sales']) / $previous['total_sales']) * 100 
            : 0;
            
        $revenueGrowth = $previous['revenue'] > 0 
            ? (($current['revenue'] - $previous['revenue']) / $previous['revenue']) * 100 
            : 0;
            
        $ticketGrowth = $previous['avg_ticket'] > 0 
            ? (($current['avg_ticket'] - $previous['avg_ticket']) / $previous['avg_ticket']) * 100 
            : 0;
        
        return [
            'current' => $current,
            'previous' => $previous,
            'sales_growth' => $salesGrowth,
            'revenue_growth' => $revenueGrowth,
            'ticket_growth' => $ticketGrowth
        ];
    }
    
    /**
     * Exportar relatório
     */
    public function export() {
        $userId = $this->auth->id();
        $startDate = $this->get('start_date', date('Y-m-01'));
        $endDate = $this->get('end_date', date('Y-m-d'));
        
        // Busca dados
        $sales = $this->db->fetchAll("
            SELECT 
                s.*,
                p.name as product_name,
                u.utm_source,
                u.utm_medium,
                u.utm_campaign
            FROM sales s
            LEFT JOIN products p ON p.id = s.product_id
            LEFT JOIN utms u ON u.id = s.utm_id
            WHERE s.user_id = :user_id
            AND DATE(s.created_at) BETWEEN :start_date AND :end_date
            ORDER BY s.created_at DESC
        ", [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        // Gera CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio_vendas_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalhos
        fputcsv($output, [
            'Data',
            'ID Transação',
            'Cliente',
            'Email',
            'Produto',
            'Valor',
            'Método Pagamento',
            'Status',
            'UTM Source',
            'UTM Medium',
            'UTM Campaign'
        ]);
        
        // Dados
        foreach ($sales as $sale) {
            fputcsv($output, [
                date('d/m/Y H:i', strtotime($sale['created_at'])),
                $sale['transaction_id'],
                $sale['customer_name'],
                $sale['customer_email'],
                $sale['product_name'] ?? '-',
                'R$ ' . number_format($sale['amount'], 2, ',', '.'),
                $sale['payment_method'],
                $sale['status'],
                $sale['utm_source'] ?? '-',
                $sale['utm_medium'] ?? '-',
                $sale['utm_campaign'] ?? '-'
            ]);
        }
        
        fclose($output);
        exit;
    }
}