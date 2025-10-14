<?php
/**
 * ========================================
 * CAMINHO: /utmtrack/app/controllers/AdSetController.php
 * ========================================
 * 
 * UTMTrack - AdSet Controller COMPLETO
 * Gerencia Conjuntos de Anúncios com Sync Meta Ads
 * 
 * @version 11.0 FINAL
 * @tested 2025-10-14
 * @author UTMTrack Development Team
 */

class AdSetController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Dashboard principal de conjuntos de anúncios
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca conjuntos do banco com todas as métricas
        $adsets = $this->db->fetchAll("
            SELECT 
                a.*,
                c.campaign_name,
                c.campaign_id,
                aa.account_name,
                aa.platform,
                c.id as campaign_internal_id,
                c.ad_account_id
            FROM adsets a
            LEFT JOIN campaigns c ON c.id = a.campaign_id
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE a.user_id = :user_id
            ORDER BY a.last_sync DESC, a.spent DESC
            LIMIT 500
        ", ['user_id' => $userId]);
        
        $adsets = is_array($adsets) ? $adsets : [];
        
        // Total de adsets encontrados
        
        // Calcula métricas derivadas
        foreach ($adsets as &$adset) {
            $spent = floatval($adset['spent'] ?? 0);
            
            // Calcula CTR, CPC e CPM se não vieram da API
            if (empty($adset['ctr']) && $adset['impressions'] > 0) {
                $adset['ctr'] = round(($adset['clicks'] / $adset['impressions']) * 100, 2);
            }
            if (empty($adset['cpc']) && $adset['clicks'] > 0) {
                $adset['cpc'] = round($spent / $adset['clicks'], 2);
            }
            if (empty($adset['cpm']) && $adset['impressions'] > 0) {
                $adset['cpm'] = round(($spent / $adset['impressions']) * 1000, 2);
            }
            
            // Tempo desde última sync
            if (!empty($adset['last_sync'])) {
                $diff = (new DateTime())->diff(new DateTime($adset['last_sync']));
                if ($diff->days > 0) {
                    $adset['sync_time'] = $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
                } elseif ($diff->h > 0) {
                    $adset['sync_time'] = $diff->h . 'h';
                } elseif ($diff->i > 0) {
                    $adset['sync_time'] = $diff->i . 'min';
                } else {
                    $adset['sync_time'] = 'agora';
                }
            } else {
                $adset['sync_time'] = 'nunca';
            }
        }
        
        // Estatísticas agregadas
        $stats = [
            'total_adsets' => count($adsets),
            'active_adsets' => 0,
            'total_spent' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
        ];
        
        foreach ($adsets as $a) {
            if ($a['status'] === 'active') $stats['active_adsets']++;
            $stats['total_spent'] += floatval($a['spent'] ?? 0);
            $stats['total_impressions'] += intval($a['impressions'] ?? 0);
            $stats['total_clicks'] += intval($a['clicks'] ?? 0);
            $stats['total_conversions'] += intval($a['conversions'] ?? 0);
        }
        
        $stats['ctr'] = ($stats['total_impressions'] > 0) 
            ? round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2) : 0;
        $stats['avg_cpc'] = ($stats['total_clicks'] > 0) 
            ? round($stats['total_spent'] / $stats['total_clicks'], 2) : 0;
        
        // Busca primeira campanha para o breadcrumb (se existir)
        $campaign = !empty($adsets) ? [
            'id' => $adsets[0]['campaign_internal_id'] ?? 0,
            'campaign_name' => $adsets[0]['campaign_name'] ?? 'Todas as Campanhas',
            'ad_account_id' => $adsets[0]['ad_account_id'] ?? 0
        ] : [
            'id' => 0,
            'campaign_name' => 'Nenhuma Campanha',
            'ad_account_id' => 0
        ];
        
        // Configuração de colunas do usuário
        $userColumns = null;
        try {
            $userColumnsData = $this->db->fetch("
                SELECT columns_config 
                FROM user_adset_columns 
                WHERE user_id = :user_id
            ", ['user_id' => $userId]);
            
            if ($userColumnsData && !empty($userColumnsData['columns_config'])) {
                $userColumns = json_decode($userColumnsData['columns_config'], true);
            }
        } catch (Exception $e) {
            // Tabela não existe ainda
        }
        
        // Renderiza a view correta
        $this->render('adsets/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'adsets' => $adsets,
            'stats' => $stats,
            'campaign' => $campaign,
            'userColumns' => $userColumns,
            'pageTitle' => 'Conjuntos de Anúncios'
        ]);
    }
    
    /**
     * Sincroniza TODOS os conjuntos das contas ativas
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
                $adsets = $this->fetchAdSetsFromMeta(
                    $account['account_id'], 
                    $account['access_token']
                );
                
                if (empty($adsets)) continue;
                
                foreach ($adsets as $adset) {
                    try {
                        // Busca campanha relacionada
                        $campaign = $this->db->fetch("
                            SELECT id, campaign_id FROM campaigns 
                            WHERE campaign_id = :campaign_id AND user_id = :user_id
                        ", [
                            'campaign_id' => $adset['campaign_id'],
                            'user_id' => $userId
                        ]);
                        
                        if (!$campaign) continue;
                        
                        $exists = $this->db->fetch("
                            SELECT id FROM adsets 
                            WHERE adset_id = :adset_id
                        ", ['adset_id' => $adset['id']]);
                        
                        $data = $this->prepareAdSetData($adset, $campaign['id']);
                        
                        if ($exists) {
                            $this->db->update('adsets', $data, 'id = :id', ['id' => $exists['id']]);
                            $totalUpdated++;
                        } else {
                            $data['user_id'] = $userId;
                            $data['campaign_id'] = $campaign['id'];
                            $data['adset_id'] = $adset['id'];
                            $this->db->insert('adsets', $data);
                            $totalImported++;
                        }
                    } catch (Exception $e) {
                        $totalErrors++;
                        error_log("Erro adset {$adset['id']}: " . $e->getMessage());
                    }
                }
                
                usleep(500000);
                
            } catch (Exception $e) {
                $totalErrors++;
                error_log("Erro conta {$account['id']}: " . $e->getMessage());
            }
        }
        
        $message = "✅ {$totalImported} novo(s), {$totalUpdated} atualizado(s)";
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
     * Atualiza status no Meta Ads
     */
    public function updateMetaStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $data = json_decode(file_get_contents('php://input'), true);
        
        $adsetId = $data['adset_id'] ?? null;
        $metaAdSetId = $data['meta_adset_id'] ?? null;
        $status = $data['status'] ?? null;
        
        if (empty($adsetId) || empty($metaAdSetId) || empty($status)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $adset = $this->db->fetch("
                SELECT a.*, c.ad_account_id, aa.access_token
                FROM adsets a
                JOIN campaigns c ON c.id = a.campaign_id
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE a.id = :id AND a.user_id = :user_id
            ", ['id' => $adsetId, 'user_id' => $userId]);
            
            if (!$adset) {
                $this->json(['success' => false, 'message' => 'Conjunto não encontrado'], 404);
                return;
            }
            
            $this->updateFieldOnMeta($metaAdSetId, $adset['access_token'], 'status', $status);
            
            $this->db->update('adsets', ['status' => $status], 'id = :id', ['id' => $adsetId]);
            
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
        
        $adsetId = $data['adset_id'] ?? null;
        $metaAdSetId = $data['meta_adset_id'] ?? null;
        $budget = $data['budget'] ?? null;
        $budgetType = $data['budget_type'] ?? 'daily'; // daily ou lifetime
        
        if (empty($adsetId) || empty($metaAdSetId) || $budget === null) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $adset = $this->db->fetch("
                SELECT a.*, c.ad_account_id, aa.access_token
                FROM adsets a
                JOIN campaigns c ON c.id = a.campaign_id
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE a.id = :id AND a.user_id = :user_id
            ", ['id' => $adsetId, 'user_id' => $userId]);
            
            if (!$adset) {
                $this->json(['success' => false, 'message' => 'Conjunto não encontrado'], 404);
                return;
            }
            
            $field = $budgetType === 'lifetime' ? 'lifetime_budget' : 'daily_budget';
            $this->updateFieldOnMeta($metaAdSetId, $adset['access_token'], $field, $budget);
            
            $this->db->update('adsets', [$field => $budget], 'id = :id', ['id' => $adsetId]);
            
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
        
        $adsetId = $data['adset_id'] ?? null;
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;
        
        $allowedFields = ['status', 'adset_name', 'daily_budget', 'lifetime_budget'];
        
        if (empty($adsetId) || empty($field) || !in_array($field, $allowedFields)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $adset = $this->db->fetch("
                SELECT a.*, c.ad_account_id, aa.access_token
                FROM adsets a
                JOIN campaigns c ON c.id = a.campaign_id
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE a.id = :id AND a.user_id = :user_id
            ", ['id' => $adsetId, 'user_id' => $userId]);
            
            if (!$adset) {
                $this->json(['success' => false, 'message' => 'Conjunto não encontrado'], 404);
                return;
            }
            
            $metaUpdated = false;
            if (in_array($field, ['status', 'adset_name', 'daily_budget', 'lifetime_budget'])) {
                try {
                    $this->updateFieldOnMeta($adset['adset_id'], $adset['access_token'], $field, $value);
                    $metaUpdated = true;
                } catch (Exception $e) {
                    error_log("Erro Meta: " . $e->getMessage());
                }
            }
            
            $this->db->update('adsets', [$field => $value], 'id = :id', ['id' => $adsetId]);
            
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
        $columns = $data['columns'] ?? null;
        
        if (empty($columns) || !is_array($columns)) {
            $this->json(['success' => false, 'message' => 'Colunas inválidas'], 400);
            return;
        }
        
        try {
            $exists = $this->db->fetch("
                SELECT id FROM user_adset_columns WHERE user_id = :user_id
            ", ['user_id' => $userId]);
            
            $columnsJson = json_encode($columns);
            
            if ($exists) {
                $this->db->update('user_adset_columns',
                    ['columns_config' => $columnsJson],
                    'user_id = :user_id',
                    ['user_id' => $userId]
                );
            } else {
                $this->db->insert('user_adset_columns', [
                    'user_id' => $userId,
                    'columns_config' => $columnsJson
                ]);
            }
            
            $this->json(['success' => true, 'message' => 'Configuração salva!']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================
    
    /**
     * Busca conjuntos da API Meta Ads com insights
     */
    private function fetchAdSetsFromMeta($accountId, $accessToken) {
        $accountId = str_replace('act_', '', $accountId);
        
        // Busca conjuntos
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/adsets?' . http_build_query([
            'fields' => 'id,name,status,campaign_id,optimization_goal,billing_event,bid_amount,daily_budget,lifetime_budget,start_time,end_time,created_time',
            'access_token' => $accessToken,
            'limit' => 300
        ]);
        
        $response = $this->curlGet($url);
        $data = json_decode($response, true);
        
        if (!isset($data['data'])) {
            throw new Exception('Resposta inválida da API Meta');
        }
        
        $adsets = $data['data'];
        
        // Busca insights para cada conjunto
        foreach ($adsets as $index => $adset) {
            $since = date('Y-m-d', strtotime($adset['created_time'] ?? '-30 days'));
            $until = date('Y-m-d');
            
            $insightsUrl = 'https://graph.facebook.com/v18.0/' . $adset['id'] . '/insights?' . http_build_query([
                'fields' => 'impressions,clicks,spend,reach,frequency,ctr,cpc,cpm,actions',
                'access_token' => $accessToken,
                'time_range' => json_encode(['since' => $since, 'until' => $until])
            ]);
            
            try {
                $insightsResponse = $this->curlGet($insightsUrl, 15);
                $insightsData = json_decode($insightsResponse, true);
            } catch (Exception $e) {
                $insightsData = ['data' => []];
            }
            
            // Inicializa com zeros
            $adsets[$index]['impressions'] = 0;
            $adsets[$index]['clicks'] = 0;
            $adsets[$index]['spend'] = 0;
            $adsets[$index]['reach'] = 0;
            $adsets[$index]['frequency'] = 0;
            $adsets[$index]['ctr'] = 0;
            $adsets[$index]['cpc'] = 0;
            $adsets[$index]['cpm'] = 0;
            $adsets[$index]['conversions'] = 0;
            
            if (isset($insightsData['data'][0])) {
                $insights = $insightsData['data'][0];
                
                $adsets[$index]['impressions'] = intval($insights['impressions'] ?? 0);
                $adsets[$index]['clicks'] = intval($insights['clicks'] ?? 0);
                $adsets[$index]['spend'] = floatval($insights['spend'] ?? 0);
                $adsets[$index]['reach'] = intval($insights['reach'] ?? 0);
                $adsets[$index]['frequency'] = floatval($insights['frequency'] ?? 0);
                $adsets[$index]['ctr'] = floatval($insights['ctr'] ?? 0);
                $adsets[$index]['cpc'] = floatval($insights['cpc'] ?? 0);
                $adsets[$index]['cpm'] = floatval($insights['cpm'] ?? 0);
                
                // Processa conversões
                if (isset($insights['actions'])) {
                    foreach ($insights['actions'] as $action) {
                        if (in_array($action['action_type'], ['purchase', 'omni_purchase'])) {
                            $adsets[$index]['conversions'] += intval($action['value'] ?? 0);
                        }
                    }
                }
            }
            
            usleep(200000);
        }
        
        return $adsets;
    }
    
    /**
     * Prepara dados do conjunto para salvar no banco
     */
    private function prepareAdSetData($adset, $campaignId) {
        $statusMap = [
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'DELETED' => 'deleted',
            'ARCHIVED' => 'deleted'
        ];
        
        return [
            'campaign_id' => $campaignId,
            'adset_name' => $adset['name'] ?? 'Sem nome',
            'status' => $statusMap[strtoupper($adset['status'] ?? 'PAUSED')] ?? 'paused',
            'optimization_goal' => $adset['optimization_goal'] ?? null,
            'billing_event' => $adset['billing_event'] ?? null,
            'bid_amount' => isset($adset['bid_amount']) ? floatval($adset['bid_amount']) / 100 : 0,
            'daily_budget' => isset($adset['daily_budget']) ? floatval($adset['daily_budget']) / 100 : 0,
            'lifetime_budget' => isset($adset['lifetime_budget']) ? floatval($adset['lifetime_budget']) / 100 : 0,
            'spent' => floatval($adset['spend'] ?? 0),
            'impressions' => intval($adset['impressions'] ?? 0),
            'clicks' => intval($adset['clicks'] ?? 0),
            'reach' => intval($adset['reach'] ?? 0),
            'frequency' => floatval($adset['frequency'] ?? 0),
            'ctr' => floatval($adset['ctr'] ?? 0),
            'cpc' => floatval($adset['cpc'] ?? 0),
            'cpm' => floatval($adset['cpm'] ?? 0),
            'conversions' => intval($adset['conversions'] ?? 0),
            'start_time' => isset($adset['start_time']) ? date('Y-m-d H:i:s', strtotime($adset['start_time'])) : null,
            'end_time' => isset($adset['end_time']) ? date('Y-m-d H:i:s', strtotime($adset['end_time'])) : null,
            'created_at' => isset($adset['created_time']) ? date('Y-m-d H:i:s', strtotime($adset['created_time'])) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Atualiza campo no Meta Ads
     */
    private function updateFieldOnMeta($adsetId, $accessToken, $field, $value) {
        $fieldMap = [
            'adset_name' => 'name',
            'status' => 'status',
            'daily_budget' => 'daily_budget',
            'lifetime_budget' => 'lifetime_budget'
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
        
        // Orçamentos precisam ser em centavos
        if (in_array($field, ['daily_budget', 'lifetime_budget'])) {
            $metaValue = intval(floatval($value) * 100);
        }
        
        $url = 'https://graph.facebook.com/v18.0/' . $adsetId;
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