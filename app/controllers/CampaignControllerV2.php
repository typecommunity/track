<?php
/**
 * ========================================
 * CAMPAIGN CONTROLLER V2.1 - CORRIGIDO
 * ========================================
 * CORREÇÕES:
 * - Sincronização automática por período
 * - Carregamento de preferências de colunas
 */

require_once 'MetaAdsDataStructure.php';
require_once 'MetaAdsSync.php';

class CampaignControllerV2 extends Controller {
    
    private $metaSync;
    private $dataStructure;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->metaSync = new MetaAdsSync($this->db, $this->userId);
        $this->dataStructure = new MetaAdsDataStructure();
    }
    
    /**
     * Dashboard principal com sincronização automática por período
     */
    public function index() {
        // CORREÇÃO 1: Detecta período da URL
        $requestedPeriod = $_GET['period'] ?? 'maximum';
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        // CORREÇÃO 2: Busca último período sincronizado
        $lastSyncPeriod = $this->getLastSyncPeriod();
        
        $syncResults = null;
        $shouldAutoSync = false;
        
        // CORREÇÃO 3: Verifica se precisa sincronizar automaticamente
        // Sincroniza se:
        // 1. Período mudou OU
        // 2. Nunca sincronizou este período OU  
        // 3. Última sync foi há mais de 1 hora
        if ($requestedPeriod !== $lastSyncPeriod || 
            $this->shouldResync($requestedPeriod)) {
            $shouldAutoSync = true;
        }
        
        // CORREÇÃO 4: Executa sincronização automática se necessário
        if ($shouldAutoSync) {
            try {
                error_log("[CONTROLLER] Sincronizando automaticamente - Período: {$requestedPeriod}");
                
                $syncOptions = [
                    'include_insights' => true,
                    'include_actions' => true,
                    'include_video_data' => true
                ];
                
                // Configura período
                if ($requestedPeriod === 'custom' && $startDate && $endDate) {
                    $syncOptions['date_preset'] = null;
                    $syncOptions['time_range'] = [
                        'since' => $startDate,
                        'until' => $endDate
                    ];
                } else {
                    $syncOptions['date_preset'] = $requestedPeriod;
                }
                
                // Executa sincronização
                $syncResults = $this->metaSync->syncAll($syncOptions);
                
                // Salva último período sincronizado
                $this->saveLastSyncPeriod($requestedPeriod);
                
                error_log("[CONTROLLER] ✅ Sincronização concluída: " . 
                          $syncResults['campaigns']['synced'] . " campanhas | " . 
                          $syncResults['duration'] . "s");
                
            } catch (Exception $e) {
                error_log("[CONTROLLER] ❌ Erro na sincronização: " . $e->getMessage());
            }
        }
        
        // CORREÇÃO 5: Carrega preferências de colunas do usuário
        $userColumns = $this->getUserColumns();
        
        // Carrega campanhas com todos os dados
        $campaigns = $this->getCampaignsWithFullData();
        
        // Estatísticas
        $stats = $this->calculateStats($campaigns);
        
        // Configuração de colunas
        $availableColumns = MetaAdsDataStructure::getAllFieldsByCategory();
        $tableConfig = MetaAdsDataStructure::getTableConfiguration();
        
        // CORREÇÃO 6: Renderiza a view COM userColumns
        $this->render('campaigns/index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'userColumns' => $userColumns, // ← CORREÇÃO: Passa para a view
            'availableColumns' => $availableColumns,
            'tableConfig' => $tableConfig,
            'metaFields' => array_merge(
                MetaAdsDataStructure::CAMPAIGN_FIELDS ?? [],
                MetaAdsDataStructure::INSIGHTS_FIELDS ?? []
            ),
            'currentPeriod' => $requestedPeriod,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'syncResults' => $syncResults,
            'config' => [], // Compatibilidade
            'user' => ['id' => $this->userId] // Compatibilidade
        ]);
    }
    
    /**
     * CORREÇÃO: Busca último período sincronizado
     */
    private function getLastSyncPeriod() {
        $result = $this->db->fetch("
            SELECT preference_value 
            FROM user_preferences 
            WHERE user_id = :user_id 
            AND preference_key = 'last_sync_period'
        ", ['user_id' => $this->userId]);
        
        return $result ? $result['preference_value'] : null;
    }
    
    /**
     * CORREÇÃO: Salva último período sincronizado
     */
    private function saveLastSyncPeriod($period) {
        $this->db->query("
            INSERT INTO user_preferences (user_id, preference_key, preference_value)
            VALUES (:user_id, 'last_sync_period', :period)
            ON DUPLICATE KEY UPDATE 
                preference_value = :period,
                updated_at = NOW()
        ", [
            'user_id' => $this->userId,
            'period' => $period
        ]);
        
        // Também salva timestamp
        $this->db->query("
            INSERT INTO user_preferences (user_id, preference_key, preference_value)
            VALUES (:user_id, 'last_sync_time', :time)
            ON DUPLICATE KEY UPDATE 
                preference_value = :time,
                updated_at = NOW()
        ", [
            'user_id' => $this->userId,
            'time' => time()
        ]);
    }
    
    /**
     * CORREÇÃO: Verifica se precisa resincronizar (1 hora)
     */
    private function shouldResync($period) {
        $result = $this->db->fetch("
            SELECT preference_value 
            FROM user_preferences 
            WHERE user_id = :user_id 
            AND preference_key = 'last_sync_time'
        ", ['user_id' => $this->userId]);
        
        if (!$result) {
            return true; // Nunca sincronizou
        }
        
        $lastSyncTime = intval($result['preference_value']);
        $hourAgo = time() - 3600; // 1 hora
        
        return $lastSyncTime < $hourAgo;
    }
    
    /**
     * CORREÇÃO: Busca colunas salvas pelo usuário
     */
    private function getUserColumns() {
        $result = $this->db->fetch("
            SELECT preference_value 
            FROM user_preferences 
            WHERE user_id = :user_id 
            AND preference_key = 'campaign_columns'
        ", ['user_id' => $this->userId]);
        
        if ($result && !empty($result['preference_value'])) {
            $columns = json_decode($result['preference_value'], true);
            if (is_array($columns) && count($columns) > 0) {
                error_log("[CONTROLLER] ✅ Colunas do usuário carregadas: " . count($columns) . " colunas");
                return $columns;
            }
        }
        
        error_log("[CONTROLLER] ⚠️ Usando colunas padrão (sem preferências salvas)");
        return null; // Retorna null para usar padrão
    }
    
    /**
     * Busca campanhas com dados completos
     */
    private function getCampaignsWithFullData($filters = []) {
        $query = "
            SELECT 
                c.*,
                aa.account_name,
                aa.account_id as meta_account_id,
                aa.access_token,
                -- Insights básicos
                ci.impressions,
                ci.clicks,
                ci.spend,
                ci.reach,
                ci.frequency,
                ci.ctr,
                ci.cpc,
                ci.cpm,
                ci.cpp,
                -- Conversões
                ci.purchase,
                ci.purchase_value,
                ci.add_to_cart,
                ci.add_to_cart_value,
                ci.initiate_checkout,
                ci.initiate_checkout_value,
                ci.lead,
                ci.complete_registration,
                ci.view_content,
                ci.search,
                -- Vídeo
                ci.video_play_actions,
                ci.video_p25_watched,
                ci.video_p50_watched,
                ci.video_p75_watched,
                ci.video_p100_watched,
                ci.thruplay,
                -- Engajamento
                ci.post_engagement,
                ci.page_engagement,
                ci.post_reactions,
                ci.post_saves,
                ci.post_shares,
                ci.post_comments,
                ci.inline_link_clicks,
                ci.inline_link_click_ctr,
                -- Qualidade
                ci.quality_ranking,
                ci.engagement_rate_ranking,
                ci.conversion_rate_ranking,
                -- App
                ci.app_install,
                ci.app_use,
                -- Calculados
                ci.roas,
                ci.roi,
                ci.margin,
                ci.cpa,
                ci.conversion_rate
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            LEFT JOIN campaign_insights ci ON ci.campaign_id = c.id
            WHERE c.user_id = :user_id
        ";
        
        $params = ['user_id' => $this->userId];
        
        // Aplica filtros
        if (!empty($filters['status'])) {
            $query .= " AND c.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['objective'])) {
            $query .= " AND c.objective = :objective";
            $params['objective'] = $filters['objective'];
        }
        
        if (!empty($filters['account_id'])) {
            $query .= " AND c.ad_account_id = :account_id";
            $params['account_id'] = $filters['account_id'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (c.campaign_name LIKE :search OR c.campaign_id LIKE :search_id)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search_id'] = '%' . $filters['search'] . '%';
        }
        
        // Ordenação
        $query .= " ORDER BY c.created_at DESC";
        
        $campaigns = $this->db->fetchAll($query, $params);
        
        // Processa métricas calculadas
        foreach ($campaigns as &$campaign) {
            $campaign = $this->calculateCampaignMetrics($campaign);
        }
        
        return $campaigns;
    }
    
    /**
     * Calcula métricas da campanha
     */
    private function calculateCampaignMetrics($campaign) {
        // ROAS
        if ($campaign['spend'] > 0 && $campaign['purchase_value'] > 0) {
            $campaign['roas'] = round($campaign['purchase_value'] / $campaign['spend'], 2);
            $campaign['roi'] = round((($campaign['purchase_value'] - $campaign['spend']) / $campaign['spend']) * 100, 2);
            $campaign['margin'] = round((($campaign['purchase_value'] - $campaign['spend']) / $campaign['purchase_value']) * 100, 2);
        } else {
            $campaign['roas'] = 0;
            $campaign['roi'] = 0;
            $campaign['margin'] = 0;
        }
        
        // CPA
        if ($campaign['purchase'] > 0) {
            $campaign['cpa'] = round($campaign['spend'] / $campaign['purchase'], 2);
        } else {
            $campaign['cpa'] = 0;
        }
        
        // CPI (Custo por IC)
        if ($campaign['initiate_checkout'] > 0) {
            $campaign['cpi'] = round($campaign['spend'] / $campaign['initiate_checkout'], 2);
        } else {
            $campaign['cpi'] = 0;
        }
        
        // CPL
        if ($campaign['lead'] > 0) {
            $campaign['cpl'] = round($campaign['spend'] / $campaign['lead'], 2);
        } else {
            $campaign['cpl'] = 0;
        }
        
        // Taxa de conversão
        if ($campaign['clicks'] > 0) {
            $campaign['conversion_rate'] = round(($campaign['purchase'] / $campaign['clicks']) * 100, 2);
        } else {
            $campaign['conversion_rate'] = 0;
        }
        
        return $campaign;
    }
    
    /**
     * Sincronização completa via AJAX
     */
    public function syncComplete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método não permitido');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $options = [
            'date_preset' => $data['date_preset'] ?? 'maximum',
            'custom_range' => $data['custom_range'] ?? null,
            'breakdowns' => $data['breakdowns'] ?? [],
            'include_insights' => $data['include_insights'] ?? true,
            'include_actions' => $data['include_actions'] ?? true,
            'include_video_data' => $data['include_video_data'] ?? true,
            'include_demographics' => $data['include_demographics'] ?? false
        ];
        
        try {
            // Executa sincronização completa
            $results = $this->metaSync->syncAll($options);
            
            // Salva período sincronizado
            $this->saveLastSyncPeriod($options['date_preset']);
            
            // Busca campanhas atualizadas
            $campaigns = $this->getCampaignsWithFullData();
            
            // Calcula estatísticas
            $stats = $this->calculateStats($campaigns);
            
            $this->jsonResponse([
                'success' => true,
                'campaigns' => $campaigns,
                'stats' => $stats,
                'sync_results' => $results,
                'message' => $this->buildSyncMessage($results)
            ]);
            
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }
    
    /**
     * Atualiza campo específico
     */
    public function updateField() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método não permitido');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $campaignId = intval($data['campaign_id'] ?? 0);
        $field = $data['field'] ?? '';
        $value = $data['value'] ?? null;
        
        // Validação
        $allowedFields = ['campaign_name', 'daily_budget', 'lifetime_budget', 'spend_cap', 
                         'status', 'bid_strategy', 'start_time', 'stop_time'];
        
        if (!in_array($field, $allowedFields)) {
            $this->jsonError('Campo não permitido para edição');
        }
        
        // Busca campanha
        $campaign = $this->db->fetch("
            SELECT c.*, aa.access_token 
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.id = :id AND c.user_id = :user_id
        ", [
            'id' => $campaignId,
            'user_id' => $this->userId
        ]);
        
        if (!$campaign) {
            $this->jsonError('Campanha não encontrada');
        }
        
        // Atualiza localmente
        $this->db->update('campaigns', 
            [$field => $value],
            'id = :id',
            ['id' => $campaignId]
        );
        
        // Tenta atualizar no Meta
        $metaUpdated = false;
        $metaError = null;
        
        if ($campaign['campaign_id'] && $campaign['access_token']) {
            try {
                $metaUpdated = $this->updateInMeta(
                    $campaign['campaign_id'],
                    $field,
                    $value,
                    $campaign['access_token']
                );
            } catch (Exception $e) {
                $metaError = $e->getMessage();
            }
        }
        
        $this->jsonResponse([
            'success' => true,
            'meta_updated' => $metaUpdated,
            'message' => $metaUpdated 
                ? 'Atualizado no Meta Ads'
                : 'Atualizado localmente' . ($metaError ? " (Erro Meta: $metaError)" : '')
        ]);
    }
    
    /**
     * Atualiza no Meta Ads
     */
    private function updateInMeta($campaignId, $field, $value, $accessToken) {
        $url = "https://graph.facebook.com/v18.0/{$campaignId}";
        
        // Mapeia campos para API do Meta
        $fieldMap = [
            'campaign_name' => 'name',
            'daily_budget' => 'daily_budget',
            'lifetime_budget' => 'lifetime_budget',
            'spend_cap' => 'spend_cap',
            'status' => 'status',
            'bid_strategy' => 'bid_strategy',
            'start_time' => 'start_time',
            'stop_time' => 'stop_time'
        ];
        
        $metaField = $fieldMap[$field] ?? $field;
        
        // Prepara valor baseado no tipo
        if (in_array($field, ['daily_budget', 'lifetime_budget', 'spend_cap'])) {
            $value = intval($value * 100); // Converte para centavos
        }
        
        $postData = [
            $metaField => $value,
            'access_token' => $accessToken
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            throw new Exception($error['error']['message'] ?? "HTTP $httpCode");
        }
        
        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true;
    }
    
    /**
     * Ações em massa
     */
    public function bulkAction() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método não permitido');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $action = $data['bulk_action'] ?? '';
        $campaignIds = $data['campaign_ids'] ?? [];
        
        if (empty($campaignIds)) {
            $this->jsonError('Nenhuma campanha selecionada');
        }
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($campaignIds as $campaignId) {
            try {
                switch ($action) {
                    case 'activate':
                        $this->updateCampaignStatus($campaignId, 'ACTIVE');
                        break;
                    case 'pause':
                        $this->updateCampaignStatus($campaignId, 'PAUSED');
                        break;
                    case 'delete':
                        $this->updateCampaignStatus($campaignId, 'DELETED');
                        break;
                    default:
                        throw new Exception('Ação inválida');
                }
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'campaign_id' => $campaignId,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $this->jsonResponse([
            'success' => $results['failed'] === 0,
            'results' => $results,
            'message' => "{$results['success']} campanhas processadas" . 
                        ($results['failed'] > 0 ? ", {$results['failed']} falharam" : "")
        ]);
    }
    
    /**
     * Atualiza status da campanha
     */
    private function updateCampaignStatus($campaignId, $status) {
        $campaign = $this->db->fetch("
            SELECT c.*, aa.access_token 
            FROM campaigns c
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE c.id = :id AND c.user_id = :user_id
        ", [
            'id' => $campaignId,
            'user_id' => $this->userId
        ]);
        
        if (!$campaign) {
            throw new Exception('Campanha não encontrada');
        }
        
        // Atualiza localmente
        $this->db->update('campaigns', 
            ['status' => strtolower($status)],
            'id = :id',
            ['id' => $campaignId]
        );
        
        // Atualiza no Meta se possível
        if ($campaign['campaign_id'] && $campaign['access_token']) {
            $this->updateInMeta(
                $campaign['campaign_id'],
                'status',
                $status,
                $campaign['access_token']
            );
        }
    }
    
    /**
     * CORREÇÃO: Salva configuração de colunas
     */
    public function saveColumns() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método não permitido');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $columns = $data['columns'] ?? [];
        
        if (empty($columns)) {
            $this->jsonError('Colunas inválidas');
        }
        
        error_log("[CONTROLLER] 💾 Salvando colunas: " . implode(', ', $columns));
        
        // Salva preferência do usuário
        $this->db->query("
            INSERT INTO user_preferences (user_id, preference_key, preference_value, updated_at)
            VALUES (:user_id, 'campaign_columns', :value, NOW())
            ON DUPLICATE KEY UPDATE 
                preference_value = :value,
                updated_at = NOW()
        ", [
            'user_id' => $this->userId,
            'value' => json_encode($columns)
        ]);
        
        // Verifica se salvou
        $saved = $this->db->fetch("
            SELECT preference_value 
            FROM user_preferences 
            WHERE user_id = :user_id 
            AND preference_key = 'campaign_columns'
        ", ['user_id' => $this->userId]);
        
        if ($saved) {
            error_log("[CONTROLLER] ✅ Colunas salvas com sucesso no banco");
        } else {
            error_log("[CONTROLLER] ❌ ERRO: Colunas NÃO foram salvas!");
        }
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Colunas salvas com sucesso',
            'columns' => $columns
        ]);
    }
    
    /**
     * Exporta dados
     */
    public function export() {
        $format = $_GET['format'] ?? 'csv';
        $filters = $_GET['filters'] ?? [];
        
        $campaigns = $this->getCampaignsWithFullData($filters);
        
        switch ($format) {
            case 'csv':
                $this->exportCsv($campaigns);
                break;
            case 'json':
                $this->exportJson($campaigns);
                break;
            default:
                $this->jsonError('Formato não suportado');
        }
    }
    
    /**
     * Exporta CSV
     */
    private function exportCsv($campaigns) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="campaigns_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($campaigns)) {
            // Headers
            $headers = array_keys($campaigns[0]);
            fputcsv($output, $headers);
            
            // Dados
            foreach ($campaigns as $campaign) {
                fputcsv($output, $campaign);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exporta JSON
     */
    private function exportJson($campaigns) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="campaigns_' . date('Y-m-d_H-i-s') . '.json"');
        echo json_encode($campaigns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Helpers
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    private function jsonError($message, $code = 400) {
        http_response_code($code);
        $this->jsonResponse([
            'success' => false,
            'message' => $message
        ]);
    }
    
    /**
     * Calcula estatísticas
     */
    private function calculateStats($campaigns) {
        $stats = [
            'total_campaigns' => count($campaigns),
            'active_campaigns' => 0,
            'total_spend' => 0,
            'total_revenue' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_purchases' => 0,
            'avg_roas' => 0,
            'avg_roi' => 0
        ];
        
        foreach ($campaigns as $campaign) {
            if ($campaign['status'] === 'active') {
                $stats['active_campaigns']++;
            }
            $stats['total_spend'] += $campaign['spend'] ?? 0;
            $stats['total_revenue'] += $campaign['purchase_value'] ?? 0;
            $stats['total_impressions'] += $campaign['impressions'] ?? 0;
            $stats['total_clicks'] += $campaign['clicks'] ?? 0;
            $stats['total_purchases'] += $campaign['purchase'] ?? 0;
        }
        
        if ($stats['total_spend'] > 0) {
            $stats['avg_roas'] = round($stats['total_revenue'] / $stats['total_spend'], 2);
            $stats['avg_roi'] = round((($stats['total_revenue'] - $stats['total_spend']) / $stats['total_spend']) * 100, 2);
        }
        
        return $stats;
    }
    
    /**
     * Monta mensagem de sincronização
     */
    private function buildSyncMessage($results) {
        $parts = [];
        
        if (isset($results['campaigns']['synced']) && $results['campaigns']['synced'] > 0) {
            $parts[] = "{$results['campaigns']['synced']} campanhas";
        }
        if (isset($results['adsets']['synced']) && $results['adsets']['synced'] > 0) {
            $parts[] = "{$results['adsets']['synced']} conjuntos";
        }
        if (isset($results['ads']['synced']) && $results['ads']['synced'] > 0) {
            $parts[] = "{$results['ads']['synced']} anúncios";
        }
        
        $message = !empty($parts) ? 
            "Sincronizados: " . implode(', ', $parts) : 
            "Nenhum dado novo para sincronizar";
        
        if (isset($results['campaigns']['errors']) && !empty($results['campaigns']['errors'])) {
            $message .= " | " . count($results['campaigns']['errors']) . " erro(s)";
        }
        
        if (isset($results['duration'])) {
            $message .= " | {$results['duration']}s";
        }
        
        return $message;
    }
}