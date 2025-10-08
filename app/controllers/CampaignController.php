<?php
/**
 * UTMTrack - Controller de Campanhas
 * Gerencia visualização e sincronização de campanhas do Meta Ads
 * 
 * Arquivo: app/controllers/CampaignController.php
 */

class CampaignController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Lista todas as campanhas do usuário
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca campanhas
        $campaigns = $this->db->fetchAll("
            SELECT 
                c.*,
                aa.account_name,
                aa.platform
            FROM campaigns c
            JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.user_id = :user_id
            ORDER BY c.last_sync DESC, c.spent DESC
            LIMIT 100
        ", ['user_id' => $userId]);
        
        // Estatísticas gerais
        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_campaigns,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
                SUM(spent) as total_spent,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions
            FROM campaigns
            WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        
        // Calcula métricas agregadas
        $stats['ctr'] = $stats['total_impressions'] > 0 
            ? round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2) 
            : 0;
            
        $stats['avg_cpc'] = $stats['total_clicks'] > 0 
            ? round($stats['total_spent'] / $stats['total_clicks'], 2) 
            : 0;
        
        $this->render('campaigns/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'campaigns' => $campaigns,
            'stats' => $stats,
            'pageTitle' => 'Todas as Campanhas'
        ]);
    }
    
    /**
     * Lista campanhas de uma conta específica do Meta
     */
    public function meta() {
        $userId = $this->auth->id();
        $accountId = $this->get('account');
        
        if (empty($accountId)) {
            $this->redirect('index.php?page=integracoes-meta-contas&error=' . urlencode('Conta não especificada'));
            return;
        }
        
        // Busca conta
        $account = $this->db->fetch("
            SELECT * FROM ad_accounts 
            WHERE id = :id AND user_id = :user_id AND platform = 'meta'
        ", [
            'id' => $accountId,
            'user_id' => $userId
        ]);
        
        if (!$account) {
            $this->redirect('index.php?page=integracoes-meta-contas&error=' . urlencode('Conta não encontrada'));
            return;
        }
        
        // Busca campanhas da conta
        $campaigns = $this->db->fetchAll("
            SELECT * FROM campaigns 
            WHERE ad_account_id = :account_id 
            AND user_id = :user_id
            ORDER BY spent DESC, campaign_name
        ", [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);
        
        // Estatísticas da conta
        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_campaigns,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
                SUM(spent) as total_spent,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions
            FROM campaigns
            WHERE ad_account_id = :account_id 
            AND user_id = :user_id
        ", [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);
        
        // Calcula métricas
        $stats['ctr'] = $stats['total_impressions'] > 0 
            ? round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2) 
            : 0;
            
        $stats['avg_cpc'] = $stats['total_clicks'] > 0 
            ? round($stats['total_spent'] / $stats['total_clicks'], 2) 
            : 0;
        
        $this->render('campaigns/meta', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'account' => $account,
            'campaigns' => $campaigns,
            'stats' => $stats,
            'pageTitle' => 'Campanhas - ' . $account['account_name']
        ]);
    }
    
    /**
     * Visualiza detalhes de uma campanha específica
     */
    public function show() {
        $userId = $this->auth->id();
        $campaignId = $this->get('id');
        
        if (empty($campaignId)) {
            $this->redirect('index.php?page=campanhas&error=' . urlencode('Campanha não especificada'));
            return;
        }
        
        // Busca campanha
        $campaign = $this->db->fetch("
            SELECT 
                c.*,
                aa.account_name,
                aa.account_id as ad_account_identifier,
                aa.platform
            FROM campaigns c
            JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.id = :id AND c.user_id = :user_id
        ", [
            'id' => $campaignId,
            'user_id' => $userId
        ]);
        
        if (!$campaign) {
            $this->redirect('index.php?page=campanhas&error=' . urlencode('Campanha não encontrada'));
            return;
        }
        
        // Busca vendas relacionadas à campanha
        $sales = $this->db->fetchAll("
            SELECT 
                s.*,
                p.name as product_name
            FROM sales s
            LEFT JOIN products p ON p.id = s.product_id
            WHERE s.campaign_id = :campaign_id 
            AND s.user_id = :user_id
            ORDER BY s.created_at DESC
            LIMIT 50
        ", [
            'campaign_id' => $campaignId,
            'user_id' => $userId
        ]);
        
        // Métricas de conversão
        $conversionMetrics = $this->db->fetch("
            SELECT 
                COUNT(*) as total_sales,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_sales,
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = 'approved' THEN amount ELSE NULL END) as avg_ticket
            FROM sales
            WHERE campaign_id = :campaign_id 
            AND user_id = :user_id
        ", [
            'campaign_id' => $campaignId,
            'user_id' => $userId
        ]);
        
        // Calcula ROAS
        $roas = $campaign['spent'] > 0 && $conversionMetrics['total_revenue'] > 0
            ? round($conversionMetrics['total_revenue'] / $campaign['spent'], 2)
            : 0;
        
        // Calcula taxa de conversão
        $conversionRate = $campaign['clicks'] > 0 
            ? round(($conversionMetrics['approved_sales'] / $campaign['clicks']) * 100, 2)
            : 0;
        
        // Calcula CPA (Cost Per Acquisition)
        $cpa = $conversionMetrics['approved_sales'] > 0
            ? round($campaign['spent'] / $conversionMetrics['approved_sales'], 2)
            : 0;
        
        $this->render('campaigns/show', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'campaign' => $campaign,
            'sales' => $sales,
            'metrics' => $conversionMetrics,
            'roas' => $roas,
            'conversionRate' => $conversionRate,
            'cpa' => $cpa,
            'pageTitle' => 'Detalhes - ' . $campaign['campaign_name']
        ]);
    }
    
    /**
     * Sincroniza campanhas de uma conta manualmente
     */
    public function sync() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $accountId = $this->post('account_id');
        
        if (empty($accountId)) {
            $this->json(['success' => false, 'message' => 'Conta não especificada'], 400);
            return;
        }
        
        // Busca conta
        $account = $this->db->fetch("
            SELECT * FROM ad_accounts 
            WHERE id = :id AND user_id = :user_id AND platform = 'meta'
        ", [
            'id' => $accountId,
            'user_id' => $userId
        ]);
        
        if (!$account) {
            $this->json(['success' => false, 'message' => 'Conta não encontrada'], 404);
            return;
        }
        
        if (empty($account['access_token'])) {
            $this->json(['success' => false, 'message' => 'Token de acesso não encontrado. Reconecte a conta.'], 400);
            return;
        }
        
        try {
            // Busca campanhas da API do Meta
            $campaigns = $this->fetchMetaCampaigns($account['account_id'], $account['access_token']);
            
            if (empty($campaigns)) {
                $this->json(['success' => false, 'message' => 'Nenhuma campanha encontrada'], 400);
                return;
            }
            
            $imported = 0;
            $updated = 0;
            
            foreach ($campaigns as $campaign) {
                // Verifica se campanha já existe
                $exists = $this->db->fetch("
                    SELECT id FROM campaigns 
                    WHERE campaign_id = :campaign_id 
                    AND ad_account_id = :account_id
                ", [
                    'campaign_id' => $campaign['id'],
                    'account_id' => $accountId
                ]);
                
                // Prepara dados
                $data = [
                    'campaign_name' => $campaign['name'],
                    'status' => $this->mapCampaignStatus($campaign['status']),
                    'objective' => $campaign['objective'] ?? null,
                    'budget' => isset($campaign['daily_budget']) ? $campaign['daily_budget'] / 100 : 0,
                    'spent' => isset($campaign['spend']) ? $campaign['spend'] / 100 : 0,
                    'impressions' => $campaign['impressions'] ?? 0,
                    'clicks' => $campaign['clicks'] ?? 0,
                    'conversions' => $this->extractConversions($campaign),
                    'last_sync' => date('Y-m-d H:i:s')
                ];
                
                if ($exists) {
                    // Atualiza campanha existente
                    $this->db->update('campaigns', $data, 'id = :id', ['id' => $exists['id']]);
                    $updated++;
                } else {
                    // Insere nova campanha
                    $data['user_id'] = $userId;
                    $data['ad_account_id'] = $accountId;
                    $data['campaign_id'] = $campaign['id'];
                    
                    $this->db->insert('campaigns', $data);
                    $imported++;
                }
            }
            
            // Atualiza last_sync da conta
            $this->db->update('ad_accounts',
                ['last_sync' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $accountId]
            );
            
            $this->json([
                'success' => true,
                'message' => "Sincronização concluída! {$imported} nova(s) campanha(s) importada(s), {$updated} atualizada(s).",
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($campaigns)
            ]);
            
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Erro ao sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Exporta campanhas para CSV
     */
    public function export() {
        $userId = $this->auth->id();
        $accountId = $this->get('account');
        
        // Busca campanhas
        if ($accountId) {
            $campaigns = $this->db->fetchAll("
                SELECT 
                    c.*,
                    aa.account_name
                FROM campaigns c
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.ad_account_id = :account_id 
                AND c.user_id = :user_id
                ORDER BY c.spent DESC
            ", [
                'account_id' => $accountId,
                'user_id' => $userId
            ]);
        } else {
            $campaigns = $this->db->fetchAll("
                SELECT 
                    c.*,
                    aa.account_name
                FROM campaigns c
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.user_id = :user_id
                ORDER BY c.spent DESC
            ", ['user_id' => $userId]);
        }
        
        // Headers para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="campanhas-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalhos
        fputcsv($output, [
            'Conta',
            'ID Campanha',
            'Nome',
            'Status',
            'Objetivo',
            'Orçamento',
            'Gasto',
            'Impressões',
            'Cliques',
            'CTR (%)',
            'CPC',
            'Conversões',
            'Última Sync'
        ], ';');
        
        // Dados
        foreach ($campaigns as $campaign) {
            $ctr = $campaign['impressions'] > 0 
                ? round(($campaign['clicks'] / $campaign['impressions']) * 100, 2) 
                : 0;
                
            $cpc = $campaign['clicks'] > 0 
                ? round($campaign['spent'] / $campaign['clicks'], 2) 
                : 0;
            
            fputcsv($output, [
                $campaign['account_name'],
                $campaign['campaign_id'],
                $campaign['campaign_name'],
                $campaign['status'],
                $campaign['objective'] ?? '-',
                'R$ ' . number_format($campaign['budget'], 2, ',', '.'),
                'R$ ' . number_format($campaign['spent'], 2, ',', '.'),
                number_format($campaign['impressions'], 0, ',', '.'),
                number_format($campaign['clicks'], 0, ',', '.'),
                number_format($ctr, 2, ',', '.'),
                'R$ ' . number_format($cpc, 2, ',', '.'),
                number_format($campaign['conversions'], 0, ',', '.'),
                date('d/m/Y H:i', strtotime($campaign['last_sync']))
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Busca campanhas da API do Meta
     * @private
     */
    private function fetchMetaCampaigns($accountId, $accessToken) {
        // Remove "act_" se existir
        $accountId = str_replace('act_', '', $accountId);
        
        // Campos que queremos buscar
        $fields = [
            'id',
            'name',
            'status',
            'objective',
            'daily_budget',
            'lifetime_budget',
            'spend',
            'impressions',
            'clicks',
            'actions',
            'created_time',
            'updated_time'
        ];
        
        // Monta URL da API
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
            'fields' => implode(',', $fields),
            'access_token' => $accessToken,
            'limit' => 100
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Erro na API Meta: HTTP {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['data'])) {
            throw new Exception('Resposta inválida da API Meta');
        }
        
        return $data['data'];
    }
    
    /**
     * Mapeia status do Meta para status do sistema
     * @private
     */
    private function mapCampaignStatus($metaStatus) {
        $statusMap = [
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'DELETED' => 'deleted',
            'ARCHIVED' => 'deleted'
        ];
        
        return $statusMap[strtoupper($metaStatus)] ?? 'paused';
    }
    
    /**
     * Extrai número de conversões do array de actions
     * @private
     */
    private function extractConversions($campaign) {
        if (!isset($campaign['actions']) || !is_array($campaign['actions'])) {
            return 0;
        }
        
        $conversions = 0;
        
        foreach ($campaign['actions'] as $action) {
            if (in_array($action['action_type'], ['purchase', 'lead', 'complete_registration', 'subscribe'])) {
                $conversions += intval($action['value']);
            }
        }
        
        return $conversions;
    }
}