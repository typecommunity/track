<?php
/**
 * UTMTrack - Controller de Campanhas EVOLUÍDO
 * Com Insights Avançados do Meta Ads + Sincronização em Lote
 * 
 * Arquivo: app/controllers/CampaignController.php
 * @version 4.0
 */

class CampaignController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Dashboard principal de campanhas
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Debug: Verifica o user ID
        error_log("DEBUG CampaignController::index() - User ID: " . $userId);
        
        // Primeiro, vamos verificar quantas campanhas existem no banco
        $totalCampaigns = $this->db->fetch("
            SELECT COUNT(*) as total FROM campaigns WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        error_log("DEBUG - Total de campanhas no banco: " . ($totalCampaigns['total'] ?? 0));
        
        // Busca campanhas com todas as métricas
        $campaigns = $this->db->fetchAll("
            SELECT 
                c.*,
                COALESCE(aa.account_name, 'Conta não vinculada') as account_name,
                COALESCE(aa.platform, 'meta') as platform,
                c.last_sync,
                
                -- Métricas do Facebook (já vem preenchidas via sync)
                c.ctr,
                c.cpc,
                c.cpm,
                c.frequency,
                c.reach,
                c.cost_per_result,
                c.purchase_value,
                c.initiate_checkout,
                c.add_to_cart,
                c.video_views,
                
                -- Métricas calculadas localmente (baseadas em vendas)
                COALESCE((SELECT COUNT(*) 
                 FROM sales s 
                 WHERE s.campaign_id = c.id AND s.status = 'approved'), 0) as real_sales,
                
                COALESCE((SELECT SUM(amount) 
                 FROM sales s 
                 WHERE s.campaign_id = c.id AND s.status = 'approved'), 0) as real_revenue,
                
                COALESCE((SELECT SUM(s.amount - COALESCE(s.product_cost, 0)) 
                 FROM sales s 
                 WHERE s.campaign_id = c.id AND s.status = 'approved'), 0) as real_profit
                
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.user_id = :user_id
            ORDER BY c.last_sync DESC, c.spent DESC
            LIMIT 500
        ", ['user_id' => $userId]);
        
        // Debug: Verifica o resultado da query
        error_log("DEBUG - Campanhas retornadas pela query: " . (is_array($campaigns) ? count($campaigns) : 'null'));
        
        // Se não encontrou campanhas com o JOIN, tenta buscar sem o JOIN
        if (empty($campaigns)) {
            error_log("DEBUG - Tentando query sem JOIN...");
            $campaigns = $this->db->fetchAll("
                SELECT 
                    c.*,
                    '' as account_name,
                    'meta' as platform,
                    c.last_sync,
                    0 as real_sales,
                    0 as real_revenue,
                    0 as real_profit
                FROM campaigns c
                WHERE c.user_id = :user_id
                ORDER BY c.last_sync DESC, c.spent DESC
                LIMIT 500
            ", ['user_id' => $userId]);
            
            error_log("DEBUG - Campanhas sem JOIN: " . (is_array($campaigns) ? count($campaigns) : 'null'));
        }
        
        // Garante que campaigns seja um array
        if (!is_array($campaigns)) {
            $campaigns = [];
        }
        
        // Calcula ROAS e ROI baseado em dados reais
        foreach ($campaigns as &$campaign) {
            // ROAS baseado em revenue real do sistema
            $campaign['live_roas'] = $campaign['spent'] > 0 && $campaign['real_revenue'] > 0
                ? round($campaign['real_revenue'] / $campaign['spent'], 2)
                : 0;
            
            // ROI baseado em lucro real
            $campaign['live_roi'] = $campaign['spent'] > 0 && $campaign['real_profit'] > 0
                ? round(($campaign['real_profit'] / $campaign['spent']) * 100, 2)
                : 0;
            
            // Margem de lucro
            $campaign['live_margin'] = $campaign['real_revenue'] > 0
                ? round(($campaign['real_profit'] / $campaign['real_revenue']) * 100, 2)
                : 0;
            
            // CPA baseado em vendas reais
            $campaign['live_cpa'] = $campaign['real_sales'] > 0
                ? round($campaign['spent'] / $campaign['real_sales'], 2)
                : 0;
            
            // Tempo desde última sync
            if ($campaign['last_sync']) {
                $lastSync = new DateTime($campaign['last_sync']);
                $now = new DateTime();
                $diff = $now->diff($lastSync);
                
                if ($diff->days > 0) {
                    $campaign['sync_time'] = $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
                } elseif ($diff->h > 0) {
                    $campaign['sync_time'] = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                } elseif ($diff->i > 0) {
                    $campaign['sync_time'] = $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
                } else {
                    $campaign['sync_time'] = 'agora';
                }
            } else {
                $campaign['sync_time'] = 'nunca';
            }
        }
        
        // Estatísticas gerais
        $stats = $this->db->fetch("
            SELECT 
                COUNT(DISTINCT c.id) as total_campaigns,
                SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
                COALESCE(SUM(c.spent), 0) as total_spent,
                COALESCE(SUM(c.impressions), 0) as total_impressions,
                COALESCE(SUM(c.clicks), 0) as total_clicks,
                COALESCE(SUM(c.conversions), 0) as total_conversions,
                COALESCE((SELECT COUNT(*) FROM sales WHERE user_id = :user_id AND status = 'approved'), 0) as total_sales,
                COALESCE((SELECT SUM(amount) FROM sales WHERE user_id = :user_id AND status = 'approved'), 0) as total_revenue,
                COALESCE((SELECT SUM(amount - COALESCE(product_cost, 0)) FROM sales WHERE user_id = :user_id AND status = 'approved'), 0) as total_profit
            FROM campaigns c
            WHERE c.user_id = :user_id
        ", ['user_id' => $userId]);
        
        // Garante que $stats sempre seja um array válido
        if (!$stats) {
            $stats = [];
        }
        
        // Define valores padrão
        $defaults = [
            'total_campaigns' => 0,
            'active_campaigns' => 0,
            'total_spent' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
            'total_sales' => 0,
            'total_revenue' => 0,
            'total_profit' => 0
        ];
        
        // Merge com defaults
        foreach ($defaults as $key => $value) {
            if (!isset($stats[$key])) {
                $stats[$key] = $value;
            }
        }
        
        // Calcula métricas agregadas
        $stats['ctr'] = $stats['total_impressions'] > 0 
            ? round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2) 
            : 0;
            
        $stats['avg_cpc'] = $stats['total_clicks'] > 0 
            ? round($stats['total_spent'] / $stats['total_clicks'], 2) 
            : 0;
        
        $stats['avg_roas'] = $stats['total_spent'] > 0
            ? round($stats['total_revenue'] / $stats['total_spent'], 2)
            : 0;
        
        $stats['avg_roi'] = $stats['total_spent'] > 0
            ? round(($stats['total_profit'] / $stats['total_spent']) * 100, 2)
            : 0;
        
        // Busca configuração de colunas do usuário
        $userColumns = null;
        try {
            $userColumnsData = $this->db->fetch("
                SELECT columns_config 
                FROM user_campaign_columns 
                WHERE user_id = :user_id
            ", ['user_id' => $userId]);
            
            if ($userColumnsData && !empty($userColumnsData['columns_config'])) {
                $userColumns = json_decode($userColumnsData['columns_config'], true);
            }
        } catch (Exception $e) {
            // Tabela ainda não existe
        }
        
        $this->render('campaigns/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'campaigns' => $campaigns ?? [],
            'stats' => $stats,
            'userColumns' => $userColumns,
            'pageTitle' => 'Gerenciador de Campanhas'
        ]);
    }
    
    /**
     * NOVO: Sincroniza TODAS as contas ativas de uma vez
     */
    public function syncAll() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        // Busca todas as contas ativas do usuário
        $accounts = $this->db->fetchAll("
            SELECT * FROM ad_accounts 
            WHERE user_id = :user_id 
            AND platform = 'meta' 
            AND status = 'active'
            AND access_token IS NOT NULL
        ", ['user_id' => $userId]);
        
        if (empty($accounts)) {
            $this->json([
                'success' => false, 
                'message' => 'Nenhuma conta ativa encontrada'
            ], 404);
            return;
        }
        
        $totalImported = 0;
        $totalUpdated = 0;
        $totalErrors = 0;
        $accountsSynced = 0;
        
        // Processa cada conta
        foreach ($accounts as $account) {
            try {
                // Busca campanhas com insights avançados
                $campaigns = $this->fetchMetaCampaignsWithInsights(
                    $account['account_id'], 
                    $account['access_token']
                );
                
                if (empty($campaigns)) {
                    continue;
                }
                
                $accountsSynced++;
                
                foreach ($campaigns as $campaign) {
                    try {
                        // Verifica se existe
                        $exists = $this->db->fetch("
                            SELECT id FROM campaigns 
                            WHERE campaign_id = :campaign_id 
                            AND ad_account_id = :account_id
                        ", [
                            'campaign_id' => $campaign['id'],
                            'account_id' => $account['id']
                        ]);
                        
                        // Prepara dados com todos os insights
                        $data = [
                            'campaign_name' => $campaign['name'] ?? 'Sem nome',
                            'status' => $this->mapCampaignStatus($campaign['status'] ?? 'PAUSED'),
                            'objective' => $campaign['objective'] ?? null,
                            'budget' => isset($campaign['daily_budget']) ? floatval($campaign['daily_budget']) / 100 : 0,
                            'spent' => floatval($campaign['spend'] ?? 0),
                            'impressions' => intval($campaign['impressions'] ?? 0),
                            'clicks' => intval($campaign['clicks'] ?? 0),
                            'reach' => intval($campaign['reach'] ?? 0),
                            'frequency' => floatval($campaign['frequency'] ?? 0),
                            'ctr' => floatval($campaign['ctr'] ?? 0),
                            'cpc' => floatval($campaign['cpc'] ?? 0),
                            'cpm' => floatval($campaign['cpm'] ?? 0),
                            'cost_per_result' => floatval($campaign['cost_per_result'] ?? 0),
                            'conversions' => intval($campaign['conversions'] ?? 0),
                            'purchase_value' => floatval($campaign['purchase_value'] ?? 0),
                            'initiate_checkout' => intval($campaign['initiate_checkout'] ?? 0),
                            'add_to_cart' => intval($campaign['add_to_cart'] ?? 0),
                            'video_views' => intval($campaign['video_view'] ?? 0),
                            'video_views_75' => intval($campaign['video_p75_watched'] ?? 0),
                            'last_sync' => date('Y-m-d H:i:s')
                        ];
                        
                        if ($exists) {
                            $this->db->update('campaigns', $data, 'id = :id', ['id' => $exists['id']]);
                            $totalUpdated++;
                        } else {
                            $data['user_id'] = $userId;
                            $data['ad_account_id'] = $account['id'];
                            $data['campaign_id'] = $campaign['id'];
                            $this->db->insert('campaigns', $data);
                            $totalImported++;
                        }
                        
                    } catch (Exception $e) {
                        $totalErrors++;
                        error_log("Erro ao processar campanha {$campaign['name']}: " . $e->getMessage());
                    }
                }
                
                // Atualiza last_sync da conta
                $this->db->update('ad_accounts',
                    ['last_sync' => date('Y-m-d H:i:s')],
                    'id = :id',
                    ['id' => $account['id']]
                );
                
                // Delay entre contas para evitar rate limit
                usleep(500000); // 500ms
                
            } catch (Exception $e) {
                $totalErrors++;
                error_log("Erro ao sincronizar conta {$account['account_name']}: " . $e->getMessage());
            }
        }
        
        $message = "✅ Sincronização concluída! {$accountsSynced} conta(s), {$totalImported} nova(s), {$totalUpdated} atualizada(s)";
        
        if ($totalErrors > 0) {
            $message .= " ⚠️ {$totalErrors} erro(s)";
        }
        
        $this->json([
            'success' => true,
            'message' => $message,
            'imported' => $totalImported,
            'updated' => $totalUpdated,
            'errors' => $totalErrors,
            'accounts_synced' => $accountsSynced
        ]);
    }
    
    /**
     * Sincroniza campanhas de uma conta específica
     */
    public function sync() {
        $userId = $this->auth->id();
        $accountId = $this->post('account_id') ?: $this->get('account');
        
        if (empty($accountId)) {
            // Se não tem account_id, chama syncAll
            return $this->syncAll();
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
            $this->json(['success' => false, 'message' => 'Token de acesso não encontrado'], 400);
            return;
        }
        
        try {
            // Busca campanhas com insights
            $campaigns = $this->fetchMetaCampaignsWithInsights(
                $account['account_id'], 
                $account['access_token']
            );
            
            if (empty($campaigns)) {
                $this->json([
                    'success' => true, 
                    'message' => 'Nenhuma campanha encontrada',
                    'imported' => 0,
                    'updated' => 0
                ]);
                return;
            }
            
            $imported = 0;
            $updated = 0;
            
            foreach ($campaigns as $campaign) {
                // Verifica se existe
                $exists = $this->db->fetch("
                    SELECT id FROM campaigns 
                    WHERE campaign_id = :campaign_id 
                    AND ad_account_id = :account_id
                ", [
                    'campaign_id' => $campaign['id'],
                    'account_id' => $accountId
                ]);
                
                // Prepara dados com insights
                $data = [
                    'campaign_name' => $campaign['name'] ?? 'Sem nome',
                    'status' => $this->mapCampaignStatus($campaign['status'] ?? 'PAUSED'),
                    'objective' => $campaign['objective'] ?? null,
                    'budget' => isset($campaign['daily_budget']) ? floatval($campaign['daily_budget']) / 100 : 0,
                    'spent' => floatval($campaign['spend'] ?? 0),
                    'impressions' => intval($campaign['impressions'] ?? 0),
                    'clicks' => intval($campaign['clicks'] ?? 0),
                    'reach' => intval($campaign['reach'] ?? 0),
                    'frequency' => floatval($campaign['frequency'] ?? 0),
                    'ctr' => floatval($campaign['ctr'] ?? 0),
                    'cpc' => floatval($campaign['cpc'] ?? 0),
                    'cpm' => floatval($campaign['cpm'] ?? 0),
                    'cost_per_result' => floatval($campaign['cost_per_result'] ?? 0),
                    'conversions' => intval($campaign['conversions'] ?? 0),
                    'purchase_value' => floatval($campaign['purchase_value'] ?? 0),
                    'initiate_checkout' => intval($campaign['initiate_checkout'] ?? 0),
                    'add_to_cart' => intval($campaign['add_to_cart'] ?? 0),
                    'video_views' => intval($campaign['video_view'] ?? 0),
                    'last_sync' => date('Y-m-d H:i:s')
                ];
                
                if ($exists) {
                    $this->db->update('campaigns', $data, 'id = :id', ['id' => $exists['id']]);
                    $updated++;
                } else {
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
                'message' => "✅ Sincronização concluída! {$imported} nova(s), {$updated} atualizada(s)",
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($campaigns)
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao sincronizar campanhas Meta: " . $e->getMessage());
            
            $this->json([
                'success' => false,
                'message' => 'Erro ao sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualiza um campo específico da campanha (local + Facebook)
     */
    public function updateField() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        // Lê JSON do body
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        $campaignId = $data['campaign_id'] ?? null;
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;
        
        if (empty($campaignId) || empty($field)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        // Campos permitidos para edição
        $allowedFields = ['budget', 'status', 'objective', 'campaign_name'];
        
        if (!in_array($field, $allowedFields)) {
            $this->json(['success' => false, 'message' => 'Campo não editável'], 400);
            return;
        }
        
        try {
            // Busca campanha e token
            $campaign = $this->db->fetch("
                SELECT c.*, aa.access_token, aa.account_id as meta_account_id
                FROM campaigns c
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", [
                'id' => $campaignId,
                'user_id' => $userId
            ]);
            
            if (!$campaign) {
                $this->json(['success' => false, 'message' => 'Campanha não encontrada'], 404);
                return;
            }
            
            // Tenta atualizar no Facebook primeiro
            $metaUpdateSuccess = false;
            $metaUpdateMessage = '';
            
            if ($field === 'budget' || $field === 'status') {
                try {
                    $metaUpdateSuccess = $this->updateCampaignOnMeta(
                        $campaign['campaign_id'],
                        $campaign['access_token'],
                        $field,
                        $value
                    );
                    
                    if ($metaUpdateSuccess) {
                        $metaUpdateMessage = ' ✓ Facebook atualizado';
                    }
                } catch (Exception $e) {
                    // Continua mesmo se falhar no Facebook
                    error_log("Erro ao atualizar no Facebook: " . $e->getMessage());
                    $metaUpdateMessage = ' ⚠️ Facebook não atualizado';
                }
            }
            
            // Atualiza localmente
            $this->db->update('campaigns',
                [$field => $value],
                'id = :id',
                ['id' => $campaignId]
            );
            
            $this->json([
                'success' => true,
                'message' => 'Campo atualizado!' . $metaUpdateMessage,
                'field' => $field,
                'value' => $value,
                'meta_updated' => $metaUpdateSuccess
            ]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Salva configuração de colunas
     */
    public function saveColumns() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $columns = $jsonData['columns'] ?? $this->post('columns');
        
        if (empty($columns) || !is_array($columns)) {
            $this->json(['success' => false, 'message' => 'Colunas inválidas'], 400);
            return;
        }
        
        try {
            $exists = $this->db->fetch("
                SELECT id FROM user_campaign_columns WHERE user_id = :user_id
            ", ['user_id' => $userId]);
            
            $columnsJson = json_encode($columns);
            
            if ($exists) {
                $this->db->update('user_campaign_columns',
                    ['columns_config' => $columnsJson],
                    'user_id = :user_id',
                    ['user_id' => $userId]
                );
            } else {
                $this->db->insert('user_campaign_columns', [
                    'user_id' => $userId,
                    'columns_config' => $columnsJson
                ]);
            }
            
            $this->json(['success' => true, 'message' => 'Configuração salva!']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Visualiza detalhes de uma campanha
     */
    public function show() {
        $userId = $this->auth->id();
        $campaignId = $this->get('id');
        
        if (empty($campaignId)) {
            $this->redirect('index.php?page=campanhas&error=' . urlencode('Campanha não especificada'));
            return;
        }
        
        $campaign = $this->db->fetch("
            SELECT 
                c.*,
                aa.account_name,
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
        
        $sales = $this->db->fetchAll("
            SELECT * FROM sales 
            WHERE campaign_id = :campaign_id 
            AND user_id = :user_id
            ORDER BY created_at DESC
            LIMIT 100
        ", [
            'campaign_id' => $campaignId,
            'user_id' => $userId
        ]);
        
        $this->render('campaigns/show', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'campaign' => $campaign,
            'sales' => $sales ?? [],
            'pageTitle' => 'Detalhes - ' . $campaign['campaign_name']
        ]);
    }
    
    /**
     * Exporta campanhas para CSV
     */
    public function export() {
        $userId = $this->auth->id();
        
        $campaigns = $this->db->fetchAll("
            SELECT 
                c.*,
                aa.account_name
            FROM campaigns c
            JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.user_id = :user_id
            ORDER BY c.spent DESC
        ", ['user_id' => $userId]);
        
        if (empty($campaigns)) {
            die('Nenhuma campanha para exportar');
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="campanhas-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        fputcsv($output, [
            'Conta', 'ID', 'Nome', 'Status', 'Objetivo', 'Orçamento',
            'Gasto', 'Impressões', 'Cliques', 'CTR', 'CPC', 'CPM',
            'Conversões', 'CPA', 'ROAS', 'ROI', 'Última Sync'
        ], ';');
        
        foreach ($campaigns as $c) {
            fputcsv($output, [
                $c['account_name'],
                $c['campaign_id'],
                $c['campaign_name'],
                $c['status'],
                $c['objective'] ?? '-',
                'R$ ' . number_format($c['budget'], 2, ',', '.'),
                'R$ ' . number_format($c['spent'], 2, ',', '.'),
                number_format($c['impressions'], 0, ',', '.'),
                number_format($c['clicks'], 0, ',', '.'),
                number_format($c['ctr'], 2, ',', '.') . '%',
                'R$ ' . number_format($c['cpc'], 2, ',', '.'),
                'R$ ' . number_format($c['cpm'], 2, ',', '.'),
                number_format($c['conversions'], 0, ',', '.'),
                'R$ ' . number_format($c['cpa'] ?? 0, 2, ',', '.'),
                number_format($c['roas'] ?? 0, 2, ',', '.'),
                number_format($c['roi'] ?? 0, 2, ',', '.') . '%',
                isset($c['last_sync']) ? date('d/m/Y H:i', strtotime($c['last_sync'])) : '-'
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    // ========================================================================
    // MÉTODOS PRIVADOS - API META ADS
    // ========================================================================
    
    /**
     * Busca campanhas com INSIGHTS AVANÇADOS do Meta Ads
     * @private
     */
    private function fetchMetaCampaignsWithInsights($accountId, $accessToken) {
        $accountId = str_replace('act_', '', $accountId);
        
        // PASSO 1: Busca campanhas básicas
        $basicFields = [
            'id',
            'name',
            'status',
            'objective',
            'daily_budget',
            'lifetime_budget',
            'created_time',
            'updated_time'
        ];
        
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
            'fields' => implode(',', $basicFields),
            'access_token' => $accessToken,
            'limit' => 100
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorMsg = "Erro na API Meta: HTTP {$httpCode}";
            
            if (!empty($response)) {
                $errorData = json_decode($response, true);
                if (isset($errorData['error']['message'])) {
                    $errorMsg .= " - " . $errorData['error']['message'];
                }
            }
            
            if (!empty($curlError)) {
                $errorMsg .= " - cURL: {$curlError}";
            }
            
            throw new Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['data'])) {
            throw new Exception('Resposta inválida da API Meta');
        }
        
        $campaigns = $data['data'];
        
        // PASSO 2: Busca INSIGHTS DETALHADOS para cada campanha
        foreach ($campaigns as $index => $campaign) {
            // Inicializa campos
            $campaigns[$index]['impressions'] = 0;
            $campaigns[$index]['clicks'] = 0;
            $campaigns[$index]['spend'] = 0;
            $campaigns[$index]['reach'] = 0;
            $campaigns[$index]['frequency'] = 0;
            $campaigns[$index]['ctr'] = 0;
            $campaigns[$index]['cpc'] = 0;
            $campaigns[$index]['cpm'] = 0;
            $campaigns[$index]['conversions'] = 0;
            $campaigns[$index]['purchase_value'] = 0;
            $campaigns[$index]['cost_per_result'] = 0;
            
            // Campos de insights avançados que queremos
            $insightsFields = [
                'impressions',
                'clicks',
                'spend',
                'reach',
                'frequency',
                'ctr',     // Click Through Rate
                'cpc',     // Cost Per Click
                'cpm',     // Cost Per Mille (1000 impressões)
                'cost_per_action_type', // CPA por tipo
                'actions', // Todas as ações
                'action_values', // Valores das ações
                'video_p25_watched_actions',
                'video_p50_watched_actions',
                'video_p75_watched_actions',
                'video_p100_watched_actions'
            ];
            
            $insightsUrl = 'https://graph.facebook.com/v18.0/' . $campaign['id'] . '/insights?' . http_build_query([
                'fields' => implode(',', $insightsFields),
                'access_token' => $accessToken,
                'time_range' => json_encode([
                    'since' => date('Y-m-d', strtotime('-90 days')),
                    'until' => date('Y-m-d')
                ])
            ]);
            
            $ch = curl_init($insightsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $insightsResponse = curl_exec($ch);
            $insightsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($insightsCode === 200) {
                $insightsData = json_decode($insightsResponse, true);
                
                if (isset($insightsData['data'][0])) {
                    $insights = $insightsData['data'][0];
                    
                    // Métricas básicas
                    $campaigns[$index]['impressions'] = intval($insights['impressions'] ?? 0);
                    $campaigns[$index]['clicks'] = intval($insights['clicks'] ?? 0);
                    $campaigns[$index]['spend'] = floatval($insights['spend'] ?? 0);
                    $campaigns[$index]['reach'] = intval($insights['reach'] ?? 0);
                    $campaigns[$index]['frequency'] = floatval($insights['frequency'] ?? 0);
                    
                    // Métricas calculadas pelo Facebook
                    $campaigns[$index]['ctr'] = floatval($insights['ctr'] ?? 0);
                    $campaigns[$index]['cpc'] = floatval($insights['cpc'] ?? 0);
                    $campaigns[$index]['cpm'] = floatval($insights['cpm'] ?? 0);
                    
                    // Processa ações (conversões, IC, carrinho, etc)
                    if (isset($insights['actions']) && is_array($insights['actions'])) {
                        foreach ($insights['actions'] as $action) {
                            $type = $action['action_type'] ?? '';
                            $value = intval($action['value'] ?? 0);
                            
                            switch ($type) {
                                case 'purchase':
                                case 'omni_purchase':
                                    $campaigns[$index]['conversions'] += $value;
                                    break;
                                case 'initiate_checkout':
                                case 'omni_initiated_checkout':
                                    $campaigns[$index]['initiate_checkout'] = $value;
                                    break;
                                case 'add_to_cart':
                                case 'omni_add_to_cart':
                                    $campaigns[$index]['add_to_cart'] = $value;
                                    break;
                                case 'video_view':
                                    $campaigns[$index]['video_view'] = $value;
                                    break;
                            }
                        }
                    }
                    
                    // Valores de conversão (receita)
                    if (isset($insights['action_values']) && is_array($insights['action_values'])) {
                        foreach ($insights['action_values'] as $actionValue) {
                            if (in_array($actionValue['action_type'], ['purchase', 'omni_purchase'])) {
                                $campaigns[$index]['purchase_value'] = floatval($actionValue['value'] ?? 0);
                            }
                        }
                    }
                    
                    // Vídeos assistidos 75%
                    if (isset($insights['video_p75_watched_actions'])) {
                        foreach ($insights['video_p75_watched_actions'] as $video) {
                            $campaigns[$index]['video_p75_watched'] = intval($video['value'] ?? 0);
                        }
                    }
                    
                    // Custo por resultado principal
                    if (isset($insights['cost_per_action_type'])) {
                        foreach ($insights['cost_per_action_type'] as $cpa) {
                            if (in_array($cpa['action_type'], ['purchase', 'omni_purchase'])) {
                                $campaigns[$index]['cost_per_result'] = floatval($cpa['value'] ?? 0);
                            }
                        }
                    }
                }
            }
            
            // Delay para não sobrecarregar a API
            usleep(200000); // 200ms
        }
        
        return $campaigns;
    }
    
    /**
     * Atualiza campanha no Facebook
     * @private
     */
    private function updateCampaignOnMeta($campaignId, $accessToken, $field, $value) {
        // Mapeia campos
        $fieldMap = [
            'campaign_name' => 'name',
            'status' => 'status',
            'budget' => 'daily_budget'
        ];
        
        if (!isset($fieldMap[$field])) {
            throw new Exception("Campo {$field} não suportado para atualização no Meta");
        }
        
        $metaField = $fieldMap[$field];
        $metaValue = $value;
        
        // Ajusta valores
        if ($field === 'status') {
            $statusMap = [
                'active' => 'ACTIVE',
                'paused' => 'PAUSED',
                'deleted' => 'DELETED'
            ];
            $metaValue = $statusMap[$value] ?? 'PAUSED';
        }
        
        if ($field === 'budget') {
            // Converte para centavos
            $metaValue = intval(floatval($value) * 100);
        }
        
        // Faz a requisição POST
        $url = 'https://graph.facebook.com/v18.0/' . $campaignId;
        
        $postData = [
            $metaField => $metaValue,
            'access_token' => $accessToken
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorMsg = "Erro HTTP {$httpCode}";
            
            if (!empty($response)) {
                $errorData = json_decode($response, true);
                if (isset($errorData['error']['message'])) {
                    $errorMsg = $errorData['error']['message'];
                }
            }
            
            throw new Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        
        return isset($data['success']) && $data['success'] === true;
    }
    
    /**
     * Mapeia status
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
}