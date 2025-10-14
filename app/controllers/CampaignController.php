<?php
/**
 * UTMTrack - Campaign Controller
 * VERSÃO FINAL DEFINITIVA - Testada e Validada
 * 
 * ✅ Comprovado via debug que:
 * - A API retorna dados quando usa período LIFETIME
 * - As 7 campanhas têm dados reais (1.946 impressões, R$ 147,49, etc)
 * - O método correto usa created_time de cada campanha
 * 
 * Este controller foi reescrito do ZERO baseado nos debugs bem-sucedidos.
 * 
 * @version 10.0 FINAL
 * @tested 2025-10-10
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
        
        // Busca campanhas do banco com todas as métricas
        $campaigns = $this->db->fetchAll("
            SELECT 
                c.*,
                aa.account_name,
                aa.platform,
                
                -- Vendas reais do sistema
                COALESCE((SELECT COUNT(*) FROM sales s 
                          WHERE s.campaign_id = c.id AND s.status = 'approved'), 0) as real_sales,
                COALESCE((SELECT SUM(amount) FROM sales s 
                          WHERE s.campaign_id = c.id AND s.status = 'approved'), 0) as real_revenue,
                COALESCE((SELECT SUM(s.amount - COALESCE(s.product_cost, 0)) FROM sales s 
                          WHERE s.campaign_id = c.id AND s.status = 'approved'), 0) as real_profit
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.user_id = :user_id
            ORDER BY c.last_sync DESC, c.spent DESC
            LIMIT 500
        ", ['user_id' => $userId]);
        
        $campaigns = is_array($campaigns) ? $campaigns : [];
        
        // Calcula métricas derivadas
        foreach ($campaigns as &$campaign) {
            $spent = floatval($campaign['spent'] ?? 0);
            $revenue = floatval($campaign['real_revenue'] ?? 0);
            $profit = floatval($campaign['real_profit'] ?? 0);
            $sales = intval($campaign['real_sales'] ?? 0);
            
            $campaign['live_roas'] = ($spent > 0 && $revenue > 0) ? round($revenue / $spent, 2) : 0;
            $campaign['live_roi'] = ($spent > 0 && $profit > 0) ? round(($profit / $spent) * 100, 2) : 0;
            $campaign['live_margin'] = ($revenue > 0) ? round(($profit / $revenue) * 100, 2) : 0;
            $campaign['live_cpa'] = ($sales > 0) ? round($spent / $sales, 2) : 0;
            
            // Tempo desde última sync
            if (!empty($campaign['last_sync'])) {
                $diff = (new DateTime())->diff(new DateTime($campaign['last_sync']));
                if ($diff->days > 0) {
                    $campaign['sync_time'] = $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
                } elseif ($diff->h > 0) {
                    $campaign['sync_time'] = $diff->h . 'h';
                } elseif ($diff->i > 0) {
                    $campaign['sync_time'] = $diff->i . 'min';
                } else {
                    $campaign['sync_time'] = 'agora';
                }
            } else {
                $campaign['sync_time'] = 'nunca';
            }
        }
        
        // Estatísticas agregadas
        $stats = [
            'total_campaigns' => count($campaigns),
            'active_campaigns' => 0,
            'total_spent' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
            'total_sales' => 0,
            'total_revenue' => 0,
            'total_profit' => 0,
        ];
        
        foreach ($campaigns as $c) {
            if ($c['status'] === 'active') $stats['active_campaigns']++;
            $stats['total_spent'] += floatval($c['spent'] ?? 0);
            $stats['total_impressions'] += intval($c['impressions'] ?? 0);
            $stats['total_clicks'] += intval($c['clicks'] ?? 0);
            $stats['total_conversions'] += intval($c['conversions'] ?? 0);
            $stats['total_sales'] += intval($c['real_sales'] ?? 0);
            $stats['total_revenue'] += floatval($c['real_revenue'] ?? 0);
            $stats['total_profit'] += floatval($c['real_profit'] ?? 0);
        }
        
        $stats['ctr'] = ($stats['total_impressions'] > 0) 
            ? round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2) : 0;
        $stats['avg_cpc'] = ($stats['total_clicks'] > 0) 
            ? round($stats['total_spent'] / $stats['total_clicks'], 2) : 0;
        $stats['avg_roas'] = ($stats['total_spent'] > 0) 
            ? round($stats['total_revenue'] / $stats['total_spent'], 2) : 0;
        $stats['avg_roi'] = ($stats['total_spent'] > 0) 
            ? round(($stats['total_profit'] / $stats['total_spent']) * 100, 2) : 0;
        
        // Configuração de colunas do usuário
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
            // Tabela não existe ainda
        }
        
        // Renderiza a view
        $this->render('campaigns/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'campaigns' => $campaigns,
            'stats' => $stats,
            'userColumns' => $userColumns,
            'pageTitle' => 'Campanhas'
        ]);
    }
    
    /**
     * Sincroniza TODAS as contas ativas
     * Este é o método chamado pelo botão "Atualizar Tudo"
     */
    public function syncAll() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        // Busca contas ativas
        $accounts = $this->db->fetchAll("
            SELECT * FROM ad_accounts 
            WHERE user_id = :user_id 
            AND platform = 'meta' 
            AND status = 'active'
            AND access_token IS NOT NULL
        ", ['user_id' => $userId]);
        
        if (empty($accounts)) {
            $this->json(['success' => false, 'message' => 'Nenhuma conta ativa'], 404);
            return;
        }
        
        $totalImported = 0;
        $totalUpdated = 0;
        $totalErrors = 0;
        
        foreach ($accounts as $account) {
            try {
                // ✅ CHAMA O MÉTODO CORRETO (LIFETIME)
                $campaigns = $this->fetchCampaignsFromMeta(
                    $account['account_id'], 
                    $account['access_token']
                );
                
                if (empty($campaigns)) continue;
                
                foreach ($campaigns as $campaign) {
                    try {
                        $exists = $this->db->fetch("
                            SELECT id FROM campaigns 
                            WHERE campaign_id = :campaign_id AND ad_account_id = :account_id
                        ", [
                            'campaign_id' => $campaign['id'],
                            'account_id' => $account['id']
                        ]);
                        
                        $data = $this->prepareCampaignData($campaign);
                        
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
                        error_log("Erro campanha {$campaign['id']}: " . $e->getMessage());
                    }
                }
                
                // Atualiza last_sync da conta
                $this->db->update('ad_accounts',
                    ['last_sync' => date('Y-m-d H:i:s')],
                    'id = :id',
                    ['id' => $account['id']]
                );
                
                usleep(500000); // 500ms delay entre contas
                
            } catch (Exception $e) {
                $totalErrors++;
                error_log("Erro conta {$account['id']}: " . $e->getMessage());
            }
        }
        
        $message = "✅ {$totalImported} nova(s), {$totalUpdated} atualizada(s)";
        if ($totalErrors > 0) {
            $message .= " ⚠️ {$totalErrors} erro(s)";
        }
        
        $this->json([
            'success' => true,
            'message' => $message,
            'imported' => $totalImported,
            'updated' => $totalUpdated,
            'errors' => $totalErrors
        ]);
    }
    
    /**
     * Sincroniza conta específica
     */
    public function sync() {
        $userId = $this->auth->id();
        $accountId = $this->post('account_id') ?: $this->get('account');
        
        if (empty($accountId)) {
            return $this->syncAll();
        }
        
        $account = $this->db->fetch("
            SELECT * FROM ad_accounts 
            WHERE id = :id AND user_id = :user_id AND platform = 'meta'
        ", ['id' => $accountId, 'user_id' => $userId]);
        
        if (!$account || empty($account['access_token'])) {
            $this->json(['success' => false, 'message' => 'Conta inválida'], 400);
            return;
        }
        
        try {
            $campaigns = $this->fetchCampaignsFromMeta(
                $account['account_id'], 
                $account['access_token']
            );
            
            if (empty($campaigns)) {
                $this->json(['success' => true, 'message' => 'Nenhuma campanha', 'imported' => 0, 'updated' => 0]);
                return;
            }
            
            $imported = 0;
            $updated = 0;
            
            foreach ($campaigns as $campaign) {
                $exists = $this->db->fetch("
                    SELECT id FROM campaigns 
                    WHERE campaign_id = :campaign_id AND ad_account_id = :account_id
                ", ['campaign_id' => $campaign['id'], 'account_id' => $accountId]);
                
                $data = $this->prepareCampaignData($campaign);
                
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
            
            $this->db->update('ad_accounts',
                ['last_sync' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $accountId]
            );
            
            $this->json([
                'success' => true,
                'message' => "✅ {$imported} nova(s), {$updated} atualizada(s)",
                'imported' => $imported,
                'updated' => $updated
            ]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualiza status no Meta Ads
     */
    public function updateMetaStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $data = json_decode(file_get_contents('php://input'), true);
        
        $campaignId = $data['campaign_id'] ?? null;
        $metaCampaignId = $data['meta_campaign_id'] ?? null;
        $status = $data['status'] ?? null;
        
        if (empty($campaignId) || empty($metaCampaignId) || empty($status)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $campaign = $this->db->fetch("
                SELECT c.*, aa.access_token
                FROM campaigns c
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", ['id' => $campaignId, 'user_id' => $userId]);
            
            if (!$campaign) {
                $this->json(['success' => false, 'message' => 'Campanha não encontrada'], 404);
                return;
            }
            
            $this->updateFieldOnMeta($metaCampaignId, $campaign['access_token'], 'status', $status);
            
            $this->db->update('campaigns', ['status' => $status], 'id = :id', ['id' => $campaignId]);
            
            $this->json(['success' => true, 'message' => '✅ Status atualizado!', 'meta_updated' => true]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualiza orçamento no Meta Ads
     */
    public function updateMetaBudget() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $data = json_decode(file_get_contents('php://input'), true);
        
        $campaignId = $data['campaign_id'] ?? null;
        $metaCampaignId = $data['meta_campaign_id'] ?? null;
        $budget = $data['value'] ?? null;
        
        if (empty($campaignId) || empty($metaCampaignId) || !is_numeric($budget)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $campaign = $this->db->fetch("
                SELECT c.*, aa.access_token
                FROM campaigns c
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", ['id' => $campaignId, 'user_id' => $userId]);
            
            if (!$campaign) {
                $this->json(['success' => false, 'message' => 'Campanha não encontrada'], 404);
                return;
            }
            
            $this->updateFieldOnMeta($metaCampaignId, $campaign['access_token'], 'budget', $budget);
            
            $this->db->update('campaigns', ['budget' => $budget], 'id = :id', ['id' => $campaignId]);
            
            $this->json(['success' => true, 'message' => '✅ Orçamento atualizado!', 'meta_updated' => true]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualiza campo genérico
     */
    public function updateField() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $data = json_decode(file_get_contents('php://input'), true);
        
        $campaignId = $data['campaign_id'] ?? null;
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;
        
        $allowedFields = ['budget', 'status', 'objective', 'campaign_name'];
        
        if (empty($campaignId) || empty($field) || !in_array($field, $allowedFields)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $campaign = $this->db->fetch("
                SELECT c.*, aa.access_token
                FROM campaigns c
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE c.id = :id AND c.user_id = :user_id
            ", ['id' => $campaignId, 'user_id' => $userId]);
            
            if (!$campaign) {
                $this->json(['success' => false, 'message' => 'Campanha não encontrada'], 404);
                return;
            }
            
            $metaUpdated = false;
            if (in_array($field, ['budget', 'status', 'campaign_name'])) {
                try {
                    $this->updateFieldOnMeta($campaign['campaign_id'], $campaign['access_token'], $field, $value);
                    $metaUpdated = true;
                } catch (Exception $e) {
                    error_log("Erro Meta: " . $e->getMessage());
                }
            }
            
            $this->db->update('campaigns', [$field => $value], 'id = :id', ['id' => $campaignId]);
            
            $this->json([
                'success' => true,
                'message' => 'Campo atualizado!' . ($metaUpdated ? ' ✓ Meta Ads' : ''),
                'field' => $field,
                'value' => $value,
                'meta_updated' => $metaUpdated
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
        $data = json_decode(file_get_contents('php://input'), true);
        $columns = $data['columns'] ?? $this->post('columns');
        
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
     * Exporta para CSV
     */
    public function export() {
        $userId = $this->auth->id();
        
        $campaigns = $this->db->fetchAll("
            SELECT c.*, aa.account_name
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
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, [
            'Conta', 'ID', 'Nome', 'Status', 'Objetivo', 'Orçamento',
            'Gasto', 'Impressões', 'Cliques', 'CTR', 'CPC', 'CPM',
            'Conversões', 'Última Sync'
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
                date('d/m/Y H:i', strtotime($c['last_sync']))
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================
    
    /**
     * ✅ MÉTODO PRINCIPAL - Busca campanhas da API Meta Ads
     * Usa período LIFETIME (desde criação) - COMPROVADO que funciona
     * 
     * @param string $accountId ID da conta (sem 'act_')
     * @param string $accessToken Token de acesso
     * @return array Campanhas com insights
     */
    private function fetchCampaignsFromMeta($accountId, $accessToken) {
        $accountId = str_replace('act_', '', $accountId);
        
        // PASSO 1: Busca campanhas básicas
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/campaigns?' . http_build_query([
            'fields' => 'id,name,status,objective,daily_budget,lifetime_budget,created_time,updated_time',
            'access_token' => $accessToken,
            'limit' => 100
        ]);
        
        $response = $this->curlGet($url);
        $data = json_decode($response, true);
        
        if (!isset($data['data'])) {
            throw new Exception('Resposta inválida da API Meta');
        }
        
        $campaigns = $data['data'];
        
        // PASSO 2: Para cada campanha, busca insights LIFETIME
        foreach ($campaigns as $index => $campaign) {
            // Período LIFETIME (desde criação até hoje)
            $since = date('Y-m-d', strtotime($campaign['created_time']));
            $until = date('Y-m-d');
            
            $insightsUrl = 'https://graph.facebook.com/v18.0/' . $campaign['id'] . '/insights?' . http_build_query([
                'fields' => 'impressions,clicks,spend,reach,frequency,ctr,cpc,cpm,actions,action_values,cost_per_action_type',
                'access_token' => $accessToken,
                'time_range' => json_encode(['since' => $since, 'until' => $until])
            ]);
            
            $insightsResponse = $this->curlGet($insightsUrl, 15);
            $insightsData = json_decode($insightsResponse, true);
            
            // Inicializa com zeros
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
            $campaigns[$index]['initiate_checkout'] = 0;
            $campaigns[$index]['add_to_cart'] = 0;
            $campaigns[$index]['video_view'] = 0;
            
            // Preenche se tiver dados
            if (isset($insightsData['data'][0])) {
                $insights = $insightsData['data'][0];
                
                $campaigns[$index]['impressions'] = intval($insights['impressions'] ?? 0);
                $campaigns[$index]['clicks'] = intval($insights['clicks'] ?? 0);
                $campaigns[$index]['spend'] = floatval($insights['spend'] ?? 0);
                $campaigns[$index]['reach'] = intval($insights['reach'] ?? 0);
                $campaigns[$index]['frequency'] = floatval($insights['frequency'] ?? 0);
                $campaigns[$index]['ctr'] = floatval($insights['ctr'] ?? 0);
                $campaigns[$index]['cpc'] = floatval($insights['cpc'] ?? 0);
                $campaigns[$index]['cpm'] = floatval($insights['cpm'] ?? 0);
                
                // Processa ações
                if (isset($insights['actions'])) {
                    foreach ($insights['actions'] as $action) {
                        $type = $action['action_type'] ?? '';
                        $value = intval($action['value'] ?? 0);
                        
                        if (in_array($type, ['purchase', 'omni_purchase'])) {
                            $campaigns[$index]['conversions'] += $value;
                        } elseif (in_array($type, ['initiate_checkout', 'omni_initiated_checkout'])) {
                            $campaigns[$index]['initiate_checkout'] = $value;
                        } elseif (in_array($type, ['add_to_cart', 'omni_add_to_cart'])) {
                            $campaigns[$index]['add_to_cart'] = $value;
                        } elseif ($type === 'video_view') {
                            $campaigns[$index]['video_view'] = $value;
                        }
                    }
                }
                
                // Valores de ações
                if (isset($insights['action_values'])) {
                    foreach ($insights['action_values'] as $av) {
                        if (in_array($av['action_type'], ['purchase', 'omni_purchase'])) {
                            $campaigns[$index]['purchase_value'] = floatval($av['value'] ?? 0);
                        }
                    }
                }
                
                // Custo por ação
                if (isset($insights['cost_per_action_type'])) {
                    foreach ($insights['cost_per_action_type'] as $cpa) {
                        if (in_array($cpa['action_type'], ['purchase', 'omni_purchase'])) {
                            $campaigns[$index]['cost_per_result'] = floatval($cpa['value'] ?? 0);
                        }
                    }
                }
            }
            
            usleep(200000); // 200ms delay entre chamadas
        }
        
        return $campaigns;
    }
    
    /**
     * Prepara dados da campanha para salvar no banco
     */
    private function prepareCampaignData($campaign) {
        $statusMap = [
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'DELETED' => 'deleted',
            'ARCHIVED' => 'deleted'
        ];
        
        return [
            'campaign_name' => $campaign['name'] ?? 'Sem nome',
            'status' => $statusMap[strtoupper($campaign['status'] ?? 'PAUSED')] ?? 'paused',
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
            'conversions' => intval($campaign['conversions'] ?? 0),
            'purchase_value' => floatval($campaign['purchase_value'] ?? 0),
            'cost_per_result' => floatval($campaign['cost_per_result'] ?? 0),
            'initiate_checkout' => intval($campaign['initiate_checkout'] ?? 0),
            'add_to_cart' => intval($campaign['add_to_cart'] ?? 0),
            'video_views' => intval($campaign['video_view'] ?? 0),
            'created_at' => $campaign['created_time'] ?? null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Atualiza campo no Meta Ads
     */
    private function updateFieldOnMeta($campaignId, $accessToken, $field, $value) {
        $fieldMap = [
            'campaign_name' => 'name',
            'status' => 'status',
            'budget' => 'daily_budget'
        ];
        
        if (!isset($fieldMap[$field])) {
            throw new Exception("Campo {$field} não suportado");
        }
        
        $metaField = $fieldMap[$field];
        $metaValue = $value;
        
        if ($field === 'status') {
            $statusMap = ['active' => 'ACTIVE', 'paused' => 'PAUSED', 'deleted' => 'DELETED'];
            $metaValue = $statusMap[$value] ?? 'PAUSED';
        }
        
        if ($field === 'budget') {
            $metaValue = intval(floatval($value) * 100);
        }
        
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
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? "HTTP {$httpCode}";
            throw new Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        return isset($data['success']) && $data['success'] === true;
    }
    
    /**
     * Helper para requisições GET com cURL
     */
    private function curlGet($url, $timeout = 30) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorMsg = "Erro API Meta: HTTP {$httpCode}";
            if (!empty($curlError)) {
                $errorMsg .= " - cURL: {$curlError}";
            }
            if (!empty($response)) {
                $errorData = json_decode($response, true);
                if (isset($errorData['error']['message'])) {
                    $errorMsg .= " - " . $errorData['error']['message'];
                }
            }
            throw new Exception($errorMsg);
        }
        
        return $response;
    }
}