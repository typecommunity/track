<?php
/**
 * UTMTrack - Controller de Conjuntos de Anúncios (Ad Sets)
 * Gerencia visualização e sincronização de Ad Sets do Meta Ads
 * 
 * Arquivo: app/controllers/AdSetController.php
 * @version 2.0
 */

class AdSetController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Lista todos os conjuntos do usuário
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca conjuntos com métricas
        $adsets = $this->db->fetchAll("
            SELECT 
                a.*,
                c.campaign_name,
                aa.account_name,
                aa.last_sync as account_last_sync,
                
                -- Métricas calculadas
                CASE WHEN a.impressions > 0 THEN ROUND((a.clicks / a.impressions) * 100, 2) ELSE 0 END as calculated_ctr,
                CASE WHEN a.clicks > 0 THEN ROUND(a.spent / a.clicks, 2) ELSE 0 END as calculated_cpc,
                CASE WHEN a.impressions > 0 THEN ROUND((a.spent / a.impressions) * 1000, 2) ELSE 0 END as calculated_cpm
                
            FROM adsets a
            LEFT JOIN campaigns c ON c.id = a.campaign_id
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE a.user_id = :user_id
            ORDER BY a.spent DESC, a.adset_name
            LIMIT 1000
        ", ['user_id' => $userId]);
        
        // Calcula tempo desde última sync
        foreach ($adsets as &$adset) {
            if ($adset['last_sync']) {
                $lastSync = new DateTime($adset['last_sync']);
                $now = new DateTime();
                $diff = $now->diff($lastSync);
                
                if ($diff->days > 0) {
                    $adset['sync_time'] = $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
                } elseif ($diff->h > 0) {
                    $adset['sync_time'] = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                } elseif ($diff->i > 0) {
                    $adset['sync_time'] = $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
                } else {
                    $adset['sync_time'] = 'agora';
                }
            } else {
                $adset['sync_time'] = 'nunca';
            }
        }
        
        // Estatísticas
        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_adsets,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_adsets,
                COALESCE(SUM(spent), 0) as total_spent,
                COALESCE(SUM(impressions), 0) as total_impressions,
                COALESCE(SUM(clicks), 0) as total_clicks,
                COALESCE(SUM(conversions), 0) as total_conversions
            FROM adsets
            WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        
        // Busca configuração de colunas
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
            // Tabela ainda não existe
        }
        
        $this->render('campaigns/adsets', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'adsets' => $adsets ?? [],
            'stats' => $stats ?? [],
            'userColumns' => $userColumns,
            'pageTitle' => 'Conjuntos de Anúncios'
        ]);
    }
    
    /**
     * Sincroniza TODOS os ad sets das contas ativas
     */
    public function syncAll() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        // Busca todas as contas ativas
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
        $accountsSynced = 0;
        $errors = [];
        
        foreach ($accounts as $account) {
            try {
                // Busca ad sets com insights
                $adsets = $this->fetchMetaAdSetsWithInsights(
                    $account['account_id'],
                    $account['access_token']
                );
                
                if (empty($adsets)) {
                    continue;
                }
                
                $accountsSynced++;
                
                foreach ($adsets as $adset) {
                    // Busca campanha relacionada
                    $campaign = $this->db->fetch("
                        SELECT id FROM campaigns 
                        WHERE campaign_id = :campaign_id AND user_id = :user_id
                    ", [
                        'campaign_id' => $adset['campaign_id'],
                        'user_id' => $userId
                    ]);
                    
                    if (!$campaign) {
                        // Se a campanha não existe, pula
                        continue;
                    }
                    
                    // Verifica se já existe
                    $exists = $this->db->fetch("
                        SELECT id FROM adsets 
                        WHERE adset_id = :adset_id
                    ", ['adset_id' => $adset['id']]);
                    
                    // Prepara dados
                    $data = [
                        'adset_name' => $adset['name'] ?? 'Sem nome',
                        'status' => $this->mapStatus($adset['status'] ?? 'PAUSED'),
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
                        'last_sync' => date('Y-m-d H:i:s')
                    ];
                    
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
                }
                
                usleep(500000); // 500ms delay
                
            } catch (Exception $e) {
                $errors[] = "Erro na conta {$account['account_name']}: " . $e->getMessage();
                error_log("Erro ao sincronizar ad sets: " . $e->getMessage());
            }
        }
        
        $message = "✅ Sincronização concluída! {$accountsSynced} conta(s), {$totalImported} novo(s), {$totalUpdated} atualizado(s)";
        
        if (!empty($errors)) {
            $message .= " ⚠️ Alguns erros: " . implode(', ', array_slice($errors, 0, 2));
        }
        
        $this->json([
            'success' => true,
            'message' => $message,
            'imported' => $totalImported,
            'updated' => $totalUpdated,
            'accounts_synced' => $accountsSynced,
            'errors' => $errors
        ]);
    }
    
    /**
     * Sincroniza ad sets de uma conta específica
     */
    public function sync() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $accountId = $this->post('account_id');
        
        if (empty($accountId)) {
            // Se não tem account_id, sincroniza tudo
            return $this->syncAll();
        }
        
        $account = $this->db->fetch("
            SELECT * FROM ad_accounts 
            WHERE id = :id AND user_id = :user_id AND platform = 'meta'
        ", [
            'id' => $accountId,
            'user_id' => $userId
        ]);
        
        if (!$account || empty($account['access_token'])) {
            $this->json(['success' => false, 'message' => 'Conta ou token inválido'], 400);
            return;
        }
        
        try {
            $adsets = $this->fetchMetaAdSetsWithInsights(
                $account['account_id'],
                $account['access_token']
            );
            
            if (empty($adsets)) {
                $this->json([
                    'success' => true,
                    'message' => 'Nenhum conjunto encontrado',
                    'imported' => 0,
                    'updated' => 0
                ]);
                return;
            }
            
            $imported = 0;
            $updated = 0;
            
            foreach ($adsets as $adset) {
                $campaign = $this->db->fetch("
                    SELECT id FROM campaigns 
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
                
                $data = [
                    'adset_name' => $adset['name'] ?? 'Sem nome',
                    'status' => $this->mapStatus($adset['status'] ?? 'PAUSED'),
                    'daily_budget' => isset($adset['daily_budget']) ? floatval($adset['daily_budget']) / 100 : 0,
                    'spent' => floatval($adset['spend'] ?? 0),
                    'impressions' => intval($adset['impressions'] ?? 0),
                    'clicks' => intval($adset['clicks'] ?? 0),
                    'ctr' => floatval($adset['ctr'] ?? 0),
                    'cpc' => floatval($adset['cpc'] ?? 0),
                    'cpm' => floatval($adset['cpm'] ?? 0),
                    'conversions' => intval($adset['conversions'] ?? 0),
                    'last_sync' => date('Y-m-d H:i:s')
                ];
                
                if ($exists) {
                    $this->db->update('adsets', $data, 'id = :id', ['id' => $exists['id']]);
                    $updated++;
                } else {
                    $data['user_id'] = $userId;
                    $data['campaign_id'] = $campaign['id'];
                    $data['adset_id'] = $adset['id'];
                    $this->db->insert('adsets', $data);
                    $imported++;
                }
            }
            
            $this->json([
                'success' => true,
                'message' => "✅ {$imported} novo(s), {$updated} atualizado(s)",
                'imported' => $imported,
                'updated' => $updated
            ]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualiza campo inline (local + Facebook)
     */
    public function updateField() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        $adsetId = $data['adset_id'] ?? null;
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;
        
        if (empty($adsetId) || empty($field)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        $allowedFields = ['daily_budget', 'status', 'adset_name', 'bid_amount'];
        
        if (!in_array($field, $allowedFields)) {
            $this->json(['success' => false, 'message' => 'Campo não editável'], 400);
            return;
        }
        
        try {
            // Busca adset e token
            $adset = $this->db->fetch("
                SELECT a.*, aa.access_token
                FROM adsets a
                JOIN campaigns c ON c.id = a.campaign_id
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE a.id = :id AND a.user_id = :user_id
            ", ['id' => $adsetId, 'user_id' => $userId]);
            
            if (!$adset) {
                $this->json(['success' => false, 'message' => 'Conjunto não encontrado'], 404);
                return;
            }
            
            // Tenta atualizar no Facebook
            $metaUpdateSuccess = false;
            $metaUpdateMessage = '';
            
            if ($field === 'daily_budget' || $field === 'status' || $field === 'bid_amount') {
                try {
                    $metaUpdateSuccess = $this->updateAdSetOnMeta(
                        $adset['adset_id'],
                        $adset['access_token'],
                        $field,
                        $value
                    );
                    
                    if ($metaUpdateSuccess) {
                        $metaUpdateMessage = ' ✓ Facebook atualizado';
                    }
                } catch (Exception $e) {
                    $metaUpdateMessage = ' ⚠️ Facebook não atualizado';
                }
            }
            
            // Atualiza localmente
            $this->db->update('adsets',
                [$field => $value],
                'id = :id',
                ['id' => $adsetId]
            );
            
            $this->json([
                'success' => true,
                'message' => 'Campo atualizado!' . $metaUpdateMessage,
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
     * Busca ad sets com insights do Meta
     * @private
     */
    private function fetchMetaAdSetsWithInsights($accountId, $accessToken) {
        $accountId = str_replace('act_', '', $accountId);
        
        // Busca dados básicos
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/adsets?' . http_build_query([
            'fields' => 'id,name,status,campaign_id,daily_budget,lifetime_budget,bid_amount,optimization_goal,billing_event,start_time,end_time',
            'access_token' => $accessToken,
            'limit' => 200
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Erro API Meta: HTTP {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['data'])) {
            throw new Exception('Resposta inválida da API Meta');
        }
        
        $adsets = $data['data'];
        
        // Busca insights para cada ad set
        foreach ($adsets as $index => $adset) {
            $adsets[$index]['impressions'] = 0;
            $adsets[$index]['clicks'] = 0;
            $adsets[$index]['spend'] = 0;
            $adsets[$index]['reach'] = 0;
            $adsets[$index]['frequency'] = 0;
            $adsets[$index]['ctr'] = 0;
            $adsets[$index]['cpc'] = 0;
            $adsets[$index]['cpm'] = 0;
            $adsets[$index]['conversions'] = 0;
            
            $insightsUrl = 'https://graph.facebook.com/v18.0/' . $adset['id'] . '/insights?' . http_build_query([
                'fields' => 'impressions,clicks,spend,reach,frequency,ctr,cpc,cpm,actions',
                'access_token' => $accessToken,
                'time_range' => json_encode([
                    'since' => date('Y-m-d', strtotime('-90 days')),
                    'until' => date('Y-m-d')
                ])
            ]);
            
            $ch = curl_init($insightsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $insightsResponse = curl_exec($ch);
            $insightsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($insightsCode === 200) {
                $insightsData = json_decode($insightsResponse, true);
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
                    
                    // Conta conversões
                    if (isset($insights['actions'])) {
                        foreach ($insights['actions'] as $action) {
                            if (in_array($action['action_type'], ['purchase', 'omni_purchase'])) {
                                $adsets[$index]['conversions'] += intval($action['value'] ?? 0);
                            }
                        }
                    }
                }
            }
            
            usleep(200000); // 200ms delay
        }
        
        return $adsets;
    }
    
    /**
     * Atualiza ad set no Facebook
     * @private
     */
    private function updateAdSetOnMeta($adsetId, $accessToken, $field, $value) {
        $fieldMap = [
            'adset_name' => 'name',
            'status' => 'status',
            'daily_budget' => 'daily_budget',
            'bid_amount' => 'bid_amount'
        ];
        
        if (!isset($fieldMap[$field])) {
            throw new Exception("Campo {$field} não suportado");
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
        
        if ($field === 'daily_budget' || $field === 'bid_amount') {
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
        
        return true;
    }
    
    /**
     * Mapeia status
     * @private
     */
    private function mapStatus($metaStatus) {
        $map = [
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'DELETED' => 'deleted',
            'ARCHIVED' => 'deleted'
        ];
        return $map[strtoupper($metaStatus)] ?? 'paused';
    }
}