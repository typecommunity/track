<?php
/**
 * UTMTrack - Controller do Dashboard
 * ATUALIZADO: Integra dados reais do Meta Ads
 */

class DashboardController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Dashboard Principal
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca métricas do usuário (Meta Ads + Vendas Reais)
        $metrics = $this->getMetrics($userId);
        
        // Busca opções de filtro
        $filterOptions = $this->getFilterOptions($userId);
        
        // Busca vendas por pagamento
        $salesByPayment = $this->getSalesByPayment($userId, $metrics);
        
        // Busca vendas por produto
        $salesByProduct = $this->getSalesByProduct($userId, $metrics);
        
        // NOVO: Top campanhas
        $topCampaigns = $this->getTopCampaigns($userId, $metrics);
        
        $this->render('dashboard/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'metrics' => $metrics,
            'filterOptions' => $filterOptions,
            'salesByPayment' => $salesByPayment,
            'salesByProduct' => $salesByProduct,
            'topCampaigns' => $topCampaigns
        ]);
    }
    
    /**
     * Busca métricas do dashboard (Meta Ads + Vendas Reais)
     */
    private function getMetrics($userId) {
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
            case 'maximum':
                $startDate = '2020-01-01 00:00:00';
                $endDate = date('Y-m-d 23:59:59');
                break;
            default:
                $startDate = date('Y-m-d 00:00:00');
                $endDate = date('Y-m-d 23:59:59');
        }
        
        $debug = $this->get('debug', false);
        
        // Filtros
        $accountFilter = $this->get('account', '');
        $sourceFilter = $this->get('source', '');
        $platformFilter = $this->get('platform', '');
        $productFilter = $this->get('product', '');
        
        // =================================================================
        // 1. BUSCA DADOS DAS CAMPANHAS DO META ADS (Gastos + Conversões)
        // =================================================================
        $campaignQuery = "
            SELECT 
                SUM(c.spent) as total_spent,
                SUM(c.impressions) as total_impressions,
                SUM(c.clicks) as total_clicks,
                SUM(c.conversions) as total_conversions,
                SUM(c.purchase_value) as total_purchase_value,
                SUM(c.initiate_checkout) as total_checkouts,
                COUNT(DISTINCT c.id) as total_campaigns
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.user_id = :user_id
            AND c.status != 'deleted'
        ";
        
        $campaignParams = ['user_id' => $userId];
        
        // Aplica filtros
        if (!empty($accountFilter)) {
            $campaignQuery .= " AND c.ad_account_id = :account_id";
            $campaignParams['account_id'] = $accountFilter;
        }
        
        if (!empty($platformFilter)) {
            $campaignQuery .= " AND aa.platform = :platform";
            $campaignParams['platform'] = $platformFilter;
        }
        
        if ($debug) {
            echo "<div style='background: #1e293b; color: #e2e8f0; padding: 20px; margin: 20px; border-radius: 8px; font-family: monospace;'>";
            echo "<h3 style='color: #667eea;'>🔍 DEBUG - CAMPANHAS META ADS</h3>";
            echo "<pre style='background: #0f172a; padding: 10px; border-radius: 4px;'>";
            echo htmlspecialchars($campaignQuery);
            echo "\n\nParâmetros:\n";
            print_r($campaignParams);
            echo "</pre>";
        }
        
        $metaData = $this->db->fetch($campaignQuery, $campaignParams);
        
        if ($debug) {
            echo "<h4 style='color: #10b981;'>Resultado Meta Ads:</h4>";
            echo "<pre style='background: #0f172a; padding: 10px; border-radius: 4px;'>";
            print_r($metaData);
            echo "</pre>";
        }
        
        // =================================================================
        // 2. BUSCA VENDAS REAIS DO SISTEMA (da tabela sales)
        // =================================================================
        $salesQuery = "
            SELECT 
                COUNT(*) as total_sales,
                SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as revenue,
                SUM(CASE WHEN s.status = 'approved' THEN s.cost ELSE 0 END) as cost,
                SUM(CASE WHEN s.status = 'approved' THEN s.tax ELSE 0 END) as tax,
                SUM(CASE WHEN s.status = 'pending' THEN s.amount ELSE 0 END) as pending,
                SUM(CASE WHEN s.status = 'refunded' THEN s.amount ELSE 0 END) as refunded
            FROM sales s
            LEFT JOIN utms u ON u.id = s.utm_id
            LEFT JOIN campaigns c ON c.id = s.campaign_id
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE s.user_id = :user_id
            AND s.created_at BETWEEN :start_date AND :end_date
        ";
        
        $salesParams = [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        
        // Aplica filtros
        if (!empty($accountFilter)) {
            $salesQuery .= " AND c.ad_account_id = :account_id";
            $salesParams['account_id'] = $accountFilter;
        }
        
        if (!empty($sourceFilter)) {
            $salesQuery .= " AND u.utm_source = :source";
            $salesParams['source'] = $sourceFilter;
        }
        
        if (!empty($platformFilter)) {
            $salesQuery .= " AND aa.platform = :platform";
            $salesParams['platform'] = $platformFilter;
        }
        
        if (!empty($productFilter)) {
            $salesQuery .= " AND s.product_id = :product_id";
            $salesParams['product_id'] = $productFilter;
        }
        
        if ($debug) {
            echo "<hr><h3 style='color: #667eea;'>📊 DEBUG - VENDAS SISTEMA</h3>";
            echo "<pre style='background: #0f172a; padding: 10px; border-radius: 4px;'>";
            echo htmlspecialchars($salesQuery);
            echo "\n\nParâmetros:\n";
            print_r($salesParams);
            echo "</pre>";
        }
        
        $salesData = $this->db->fetch($salesQuery, $salesParams);
        
        if ($debug) {
            echo "<h4 style='color: #10b981;'>Resultado Vendas:</h4>";
            echo "<pre style='background: #0f172a; padding: 10px; border-radius: 4px;'>";
            print_r($salesData);
            echo "</pre>";
        }
        
        // =================================================================
        // 3. COMBINA DADOS DO META ADS + VENDAS REAIS
        // =================================================================
        
        $totalSpent = floatval($metaData['total_spent'] ?? 0);
        
        // Prioriza vendas reais do sistema, mas usa dados do Meta se não houver
        $totalRevenue = floatval($salesData['revenue'] ?? 0);
        $totalCost = floatval($salesData['cost'] ?? 0);
        $totalTax = floatval($salesData['tax'] ?? 0);
        $totalSales = intval($salesData['total_sales'] ?? 0);
        
        // Se não houver vendas no sistema, usa conversões do Meta
        $usandoDadosMeta = false;
        if ($totalSales === 0 && !empty($metaData['total_conversions'])) {
            $totalRevenue = floatval($metaData['total_purchase_value'] ?? 0);
            $totalSales = intval($metaData['total_conversions'] ?? 0);
            $usandoDadosMeta = true;
        }
        
        // Cálculos
        $netRevenue = $totalRevenue - $totalCost - $totalTax;
        $profit = $netRevenue - $totalSpent;
        $roas = $totalSpent > 0 ? $totalRevenue / $totalSpent : 0;
        $roi = $totalSpent > 0 ? (($profit / $totalSpent) * 100) : 0;
        $margin = $totalRevenue > 0 ? (($profit / $totalRevenue) * 100) : 0;
        $ctr = ($metaData['total_impressions'] ?? 0) > 0 
            ? (($metaData['total_clicks'] ?? 0) / $metaData['total_impressions']) * 100 
            : 0;
        $cpc = ($metaData['total_clicks'] ?? 0) > 0 
            ? $totalSpent / $metaData['total_clicks'] 
            : 0;
        
        if ($debug) {
            echo "<hr><h3 style='color: #667eea;'>🧮 CÁLCULOS FINAIS</h3>";
            echo "<pre style='background: #0f172a; padding: 10px; border-radius: 4px;'>";
            echo "Usando dados do: " . ($usandoDadosMeta ? "META ADS" : "SISTEMA (Vendas Reais)") . "\n\n";
            echo "Total Spent (Ads): R$ " . number_format($totalSpent, 2, ',', '.') . "\n";
            echo "Total Revenue: R$ " . number_format($totalRevenue, 2, ',', '.') . "\n";
            echo "Total Cost: R$ " . number_format($totalCost, 2, ',', '.') . "\n";
            echo "Total Tax: R$ " . number_format($totalTax, 2, ',', '.') . "\n";
            echo "Net Revenue: R$ " . number_format($netRevenue, 2, ',', '.') . "\n";
            echo "Profit: R$ " . number_format($profit, 2, ',', '.') . "\n";
            echo "ROAS: " . number_format($roas, 2, ',', '.') . "x\n";
            echo "ROI: " . number_format($roi, 2, ',', '.') . "%\n";
            echo "Margin: " . number_format($margin, 2, ',', '.') . "%\n";
            echo "CTR: " . number_format($ctr, 2, ',', '.') . "%\n";
            echo "CPC: R$ " . number_format($cpc, 2, ',', '.') . "\n";
            echo "\nMétricas Meta Ads:\n";
            echo "Impressões: " . number_format($metaData['total_impressions'] ?? 0, 0, ',', '.') . "\n";
            echo "Cliques: " . number_format($metaData['total_clicks'] ?? 0, 0, ',', '.') . "\n";
            echo "Conversões (Meta): " . number_format($metaData['total_conversions'] ?? 0, 0, ',', '.') . "\n";
            echo "Checkouts (Meta): " . number_format($metaData['total_checkouts'] ?? 0, 0, ',', '.') . "\n";
            echo "</pre></div>";
        }
        
        return [
            'net_revenue' => $netRevenue,
            'ad_spend' => $totalSpent,
            'roas' => $roas,
            'profit' => $profit,
            'pending_sales' => $salesData['pending'] ?? 0,
            'refunded_sales' => $salesData['refunded'] ?? 0,
            'roi' => $roi,
            'margin' => $margin,
            'tax' => $totalTax,
            'cost' => $totalCost,
            'total_sales' => $totalSales,
            'ctr' => $ctr,
            'cpc' => $cpc,
            'impressions' => $metaData['total_impressions'] ?? 0,
            'clicks' => $metaData['total_clicks'] ?? 0,
            'conversions_meta' => $metaData['total_conversions'] ?? 0,
            'checkouts_meta' => $metaData['total_checkouts'] ?? 0,
            'total_campaigns' => $metaData['total_campaigns'] ?? 0,
            'usando_meta' => $usandoDadosMeta,
            'period' => $period,
            'account' => $accountFilter,
            'source' => $sourceFilter,
            'platform' => $platformFilter,
            'product' => $productFilter
        ];
    }
    
    /**
     * Top campanhas com melhor desempenho
     */
    private function getTopCampaigns($userId, $metrics) {
        $query = "
            SELECT 
                c.campaign_name,
                c.spent,
                c.impressions,
                c.clicks,
                c.conversions,
                c.purchase_value,
                aa.account_name,
                aa.platform,
                -- Vendas reais do sistema
                COALESCE((SELECT COUNT(*) FROM sales s 
                          WHERE s.campaign_id = c.id AND s.status = 'approved'), 0) as real_sales,
                COALESCE((SELECT SUM(amount) FROM sales s 
                          WHERE s.campaign_id = c.id AND s.status = 'approved'), 0) as real_revenue
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.user_id = :user_id
            AND c.status != 'deleted'
        ";
        
        $params = ['user_id' => $userId];
        
        // Aplica mesmos filtros
        if (!empty($metrics['account'])) {
            $query .= " AND c.ad_account_id = :account_id";
            $params['account_id'] = $metrics['account'];
        }
        
        if (!empty($metrics['platform'])) {
            $query .= " AND aa.platform = :platform";
            $params['platform'] = $metrics['platform'];
        }
        
        $query .= " ORDER BY c.spent DESC LIMIT 10";
        
        $campaigns = $this->db->fetchAll($query, $params);
        
        // Calcula ROAS para cada campanha
        foreach ($campaigns as &$campaign) {
            $spent = floatval($campaign['spent'] ?? 0);
            $revenue = floatval($campaign['real_revenue'] ?? 0);
            
            // Se não houver vendas reais, usa purchase_value do Meta
            if ($revenue == 0) {
                $revenue = floatval($campaign['purchase_value'] ?? 0);
            }
            
            $campaign['roas'] = $spent > 0 ? round($revenue / $spent, 2) : 0;
        }
        
        return $campaigns;
    }
    
    /**
     * Busca opções de filtro
     */
    private function getFilterOptions($userId) {
        $accounts = $this->db->fetchAll("
            SELECT DISTINCT id, account_name, platform
            FROM ad_accounts
            WHERE user_id = :user_id
            AND status = 'active'
            ORDER BY account_name
        ", ['user_id' => $userId]);
        
        $sources = $this->db->fetchAll("
            SELECT DISTINCT utm_source
            FROM utms
            WHERE user_id = :user_id
            AND utm_source IS NOT NULL
            ORDER BY utm_source
        ", ['user_id' => $userId]);
        
        $platforms = $this->db->fetchAll("
            SELECT DISTINCT platform
            FROM ad_accounts
            WHERE user_id = :user_id
            AND status = 'active'
            ORDER BY platform
        ", ['user_id' => $userId]);
        
        $products = $this->db->fetchAll("
            SELECT id, name
            FROM products
            WHERE user_id = :user_id
            AND status = 'active'
            ORDER BY name
        ", ['user_id' => $userId]);
        
        return [
            'accounts' => $accounts,
            'sources' => $sources,
            'platforms' => $platforms,
            'products' => $products
        ];
    }
    
    /**
     * Vendas por método de pagamento
     */
    private function getSalesByPayment($userId, $metrics) {
        $query = "
            SELECT 
                s.payment_method,
                COUNT(*) as total,
                SUM(s.amount) as revenue
            FROM sales s
            LEFT JOIN utms u ON u.id = s.utm_id
            LEFT JOIN campaigns c ON c.id = s.campaign_id
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE s.user_id = :user_id
            AND s.status = 'approved'
        ";
        
        $params = ['user_id' => $userId];
        
        if (!empty($metrics['account'])) {
            $query .= " AND c.ad_account_id = :account_id";
            $params['account_id'] = $metrics['account'];
        }
        
        if (!empty($metrics['source'])) {
            $query .= " AND u.utm_source = :source";
            $params['source'] = $metrics['source'];
        }
        
        if (!empty($metrics['platform'])) {
            $query .= " AND aa.platform = :platform";
            $params['platform'] = $metrics['platform'];
        }
        
        if (!empty($metrics['product'])) {
            $query .= " AND s.product_id = :product_id";
            $params['product_id'] = $metrics['product'];
        }
        
        $query .= " GROUP BY s.payment_method";
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Vendas por produto
     */
    private function getSalesByProduct($userId, $metrics) {
        $query = "
            SELECT 
                p.name as product_name,
                COUNT(s.id) as total,
                SUM(s.amount) as revenue
            FROM sales s
            LEFT JOIN products p ON p.id = s.product_id
            LEFT JOIN utms u ON u.id = s.utm_id
            LEFT JOIN campaigns c ON c.id = s.campaign_id
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE s.user_id = :user_id
            AND s.status = 'approved'
        ";
        
        $params = ['user_id' => $userId];
        
        if (!empty($metrics['account'])) {
            $query .= " AND c.ad_account_id = :account_id";
            $params['account_id'] = $metrics['account'];
        }
        
        if (!empty($metrics['source'])) {
            $query .= " AND u.utm_source = :source";
            $params['source'] = $metrics['source'];
        }
        
        if (!empty($metrics['platform'])) {
            $query .= " AND aa.platform = :platform";
            $params['platform'] = $metrics['platform'];
        }
        
        if (!empty($metrics['product'])) {
            $query .= " AND s.product_id = :product_id";
            $params['product_id'] = $metrics['product'];
        }
        
        $query .= " GROUP BY s.product_id ORDER BY revenue DESC LIMIT 10";
        
        return $this->db->fetchAll($query, $params);
    }
}