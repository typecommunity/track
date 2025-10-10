<?php
/**
 * UTMTrack - Controller de Anúncios (Ads)
 * Gerencia visualização e sincronização de Anúncios do Meta Ads
 * 
 * Arquivo: app/controllers/AdController.php
 * @version 2.0
 */

class AdController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Lista todos os anúncios do usuário
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca anúncios com métricas
        $ads = $this->db->fetchAll("
            SELECT 
                a.*,
                ads.adset_name,
                c.campaign_name,
                aa.account_name,
                aa.last_sync as account_last_sync,
                
                -- Métricas calculadas
                CASE WHEN a.impressions > 0 THEN ROUND((a.clicks / a.impressions) * 100, 2) ELSE 0 END as calculated_ctr,
                CASE WHEN a.clicks > 0 THEN ROUND(a.spent / a.clicks, 2) ELSE 0 END as calculated_cpc,
                CASE WHEN a.impressions > 0 THEN ROUND((a.spent / a.impressions) * 1000, 2) ELSE 0 END as calculated_cpm,
                CASE WHEN a.conversions > 0 THEN ROUND(a.spent / a.conversions, 2) ELSE 0 END as calculated_cpa
                
            FROM ads a
            LEFT JOIN adsets ads ON ads.id = a.adset_id
            LEFT JOIN campaigns c ON c.id = a.campaign_id
            LEFT JOIN ad_accounts aa ON c.ad_account_id = aa.id
            WHERE a.user_id = :user_id
            ORDER BY a.spent DESC, a.ad_name
            LIMIT 1000
        ", ['user_id' => $userId]);
        
        // Calcula tempo desde última sync
        foreach ($ads as &$ad) {
            if ($ad['last_sync']) {
                $lastSync = new DateTime($ad['last_sync']);
                $now = new DateTime();
                $diff = $now->diff($lastSync);
                
                if ($diff->days > 0) {
                    $ad['sync_time'] = $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
                } elseif ($diff->h > 0) {
                    $ad['sync_time'] = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                } elseif ($diff->i > 0) {
                    $ad['sync_time'] = $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
                } else {
                    $ad['sync_time'] = 'agora';
                }
            } else {
                $ad['sync_time'] = 'nunca';
            }
        }
        
        // Estatísticas
        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_ads,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_ads,
                COALESCE(SUM(spent), 0) as total_spent,
                COALESCE(SUM(impressions), 0) as total_impressions,
                COALESCE(SUM(clicks), 0) as total_clicks,
                COALESCE(SUM(conversions), 0) as total_conversions,
                COALESCE(AVG(ctr), 0) as avg_ctr,
                COALESCE(AVG(cpc), 0) as avg_cpc,
                COALESCE(AVG(cpm), 0) as avg_cpm
            FROM ads
            WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        
        // Busca configuração de colunas
        $userColumns = null;
        try {
            $userColumnsData = $this->db->fetch("
                SELECT columns_config 
                FROM user_ad_columns 
                WHERE user_id = :user_id
            ", ['user_id' => $userId]);
            
            if ($userColumnsData && !empty($userColumnsData['columns_config'])) {
                $userColumns = json_decode($userColumnsData['columns_config'], true);
            }
        } catch (Exception $e) {
            // Tabela ainda não existe
        }
        
        $this->render('campaigns/ads', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'ads' => $ads ?? [],
            'stats' => $stats ?? [],
            'userColumns' => $userColumns,
            'pageTitle' => 'Anúncios'
        ]);
    }
    
    /**
     * Sincroniza TODOS os anúncios das contas ativas
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
                // Busca ads com insights
                $ads = $this->fetchMetaAdsWithInsights(
                    $account['account_id'],
                    $account['access_token']
                );
                
                if (empty($ads)) {
                    continue;
                }
                
                $accountsSynced++;
                
                foreach ($ads as $ad) {
                    // Busca campanha relacionada
                    $campaign = $this->db->fetch("
                        SELECT id FROM campaigns 
                        WHERE campaign_id = :campaign_id AND user_id = :user_id
                    ", [
                        'campaign_id' => $ad['campaign_id'],
                        'user_id' => $userId
                    ]);
                    
                    if (!$campaign) continue;
                    
                    // Busca adset se existir
                    $adset = null;
                    if (!empty($ad['adset_id'])) {
                        $adset = $this->db->fetch("
                            SELECT id FROM adsets 
                            WHERE adset_id = :adset_id
                        ", ['adset_id' => $ad['adset_id']]);
                    }
                    
                    // Verifica se já existe
                    $exists = $this->db->fetch("
                        SELECT id FROM ads 
                        WHERE ad_id = :ad_id
                    ", ['ad_id' => $ad['id']]);
                    
                    // Prepara dados
                    $data = [
                        'ad_name' => $ad['name'] ?? 'Sem nome',
                        'status' => $this->mapStatus($ad['status'] ?? 'PAUSED'),
                        'creative_id' => $ad['creative']['id'] ?? null,
                        'spent' => floatval($ad['spend'] ?? 0),
                        'impressions' => intval($ad['impressions'] ?? 0),
                        'clicks' => intval($ad['clicks'] ?? 0),
                        'reach' => intval($ad['reach'] ?? 0),
                        'frequency' => floatval($ad['frequency'] ?? 0),
                        'ctr' => floatval($ad['ctr'] ?? 0),
                        'cpc' => floatval($ad['cpc'] ?? 0),
                        'cpm' => floatval($ad['cpm'] ?? 0),
                        'conversions' => intval($ad['conversions'] ?? 0),
                        'preview_url' => $ad['preview_shareable_link'] ?? null,
                        'last_sync' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($exists) {
                        $this->db->update('ads', $data, 'id = :id', ['id' => $exists['id']]);
                        $totalUpdated++;
                    } else {
                        $data['user_id'] = $userId;
                        $data['campaign_id'] = $campaign['id'];
                        $data['adset_id'] = $adset ? $adset['id'] : null;
                        $data['ad_id'] = $ad['id'];
                        $this->db->insert('ads', $data);
                        $totalImported++;
                    }
                }
                
                usleep(500000); // 500ms delay
                
            } catch (Exception $e) {
                $errors[] = "Erro na conta {$account['account_name']}: " . $e->getMessage();
                error_log("Erro ao sincronizar ads: " . $e->getMessage());
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
     * Sincroniza ads de uma conta específica
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
            $ads = $this->fetchMetaAdsWithInsights(
                $account['account_id'],
                $account['access_token']
            );
            
            if (empty($ads)) {
                $this->json([
                    'success' => true,
                    'message' => 'Nenhum anúncio encontrado',
                    'imported' => 0,
                    'updated' => 0
                ]);
                return;
            }
            
            $imported = 0;
            $updated = 0;
            
            foreach ($ads as $ad) {
                $campaign = $this->db->fetch("
                    SELECT id FROM campaigns 
                    WHERE campaign_id = :campaign_id AND user_id = :user_id
                ", [
                    'campaign_id' => $ad['campaign_id'],
                    'user_id' => $userId
                ]);
                
                if (!$campaign) continue;
                
                $adset = null;
                if (!empty($ad['adset_id'])) {
                    $adset = $this->db->fetch("
                        SELECT id FROM adsets 
                        WHERE adset_id = :adset_id
                    ", ['adset_id' => $ad['adset_id']]);
                }
                
                $exists = $this->db->fetch("
                    SELECT id FROM ads 
                    WHERE ad_id = :ad_id
                ", ['ad_id' => $ad['id']]);
                
                $data = [
                    'ad_name' => $ad['name'] ?? 'Sem nome',
                    'status' => $this->mapStatus($ad['status'] ?? 'PAUSED'),
                    'spent' => floatval($ad['spend'] ?? 0),
                    'impressions' => intval($ad['impressions'] ?? 0),
                    'clicks' => intval($ad['clicks'] ?? 0),
                    'ctr' => floatval($ad['ctr'] ?? 0),
                    'cpc' => floatval($ad['cpc'] ?? 0),
                    'cpm' => floatval($ad['cpm'] ?? 0),
                    'conversions' => intval($ad['conversions'] ?? 0),
                    'last_sync' => date('Y-m-d H:i:s')
                ];
                
                if ($exists) {
                    $this->db->update('ads', $data, 'id = :id', ['id' => $exists['id']]);
                    $updated++;
                } else {
                    $data['user_id'] = $userId;
                    $data['campaign_id'] = $campaign['id'];
                    $data['adset_id'] = $adset ? $adset['id'] : null;
                    $data['ad_id'] = $ad['id'];
                    $this->db->insert('ads', $data);
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
        
        $adId = $data['ad_id'] ?? null;
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;
        
        if (empty($adId) || empty($field)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        $allowedFields = ['status', 'ad_name'];
        
        if (!in_array($field, $allowedFields)) {
            $this->json(['success' => false, 'message' => 'Campo não editável'], 400);
            return;
        }
        
        try {
            // Busca ad e token
            $ad = $this->db->fetch("
                SELECT a.*, aa.access_token
                FROM ads a
                JOIN campaigns c ON c.id = a.campaign_id
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE a.id = :id AND a.user_id = :user_id
            ", ['id' => $adId, 'user_id' => $userId]);
            
            if (!$ad) {
                $this->json(['success' => false, 'message' => 'Anúncio não encontrado'], 404);
                return;
            }
            
            // Tenta atualizar no Facebook
            $metaUpdateSuccess = false;
            $metaUpdateMessage = '';
            
            if ($field === 'status') {
                try {
                    $metaUpdateSuccess = $this->updateAdOnMeta(
                        $ad['ad_id'],
                        $ad['access_token'],
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
            $this->db->update('ads',
                [$field => $value],
                'id = :id',
                ['id' => $adId]
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
                SELECT id FROM user_ad_columns WHERE user_id = :user_id
            ", ['user_id' => $userId]);
            
            $columnsJson = json_encode($columns);
            
            if ($exists) {
                $this->db->update('user_ad_columns',
                    ['columns_config' => $columnsJson],
                    'user_id = :user_id',
                    ['user_id' => $userId]
                );
            } else {
                $this->db->insert('user_ad_columns', [
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
     * Visualiza preview do anúncio
     */
    public function preview() {
        $userId = $this->auth->id();
        $adId = $this->get('id');
        
        if (empty($adId)) {
            $this->redirect('index.php?page=anuncios&error=' . urlencode('Anúncio não especificado'));
            return;
        }
        
        $ad = $this->db->fetch("
            SELECT 
                a.*,
                c.campaign_name,
                ads.adset_name,
                aa.account_name,
                aa.access_token
            FROM ads a
            LEFT JOIN campaigns c ON c.id = a.campaign_id
            LEFT JOIN adsets ads ON ads.id = a.adset_id
            LEFT JOIN ad_accounts aa ON c.ad_account_id = aa.id
            WHERE a.id = :id AND a.user_id = :user_id
        ", [
            'id' => $adId,
            'user_id' => $userId
        ]);
        
        if (!$ad) {
            $this->redirect('index.php?page=anuncios&error=' . urlencode('Anúncio não encontrado'));
            return;
        }
        
        // Se tem preview_url, usa ela
        if (empty($ad['preview_url']) && !empty($ad['access_token'])) {
            // Tenta buscar preview do Facebook
            try {
                $previewUrl = $this->fetchAdPreview($ad['ad_id'], $ad['access_token']);
                if ($previewUrl) {
                    // Salva para futuras consultas
                    $this->db->update('ads',
                        ['preview_url' => $previewUrl],
                        'id = :id',
                        ['id' => $adId]
                    );
                    $ad['preview_url'] = $previewUrl;
                }
            } catch (Exception $e) {
                // Ignora erro
            }
        }
        
        $this->render('campaigns/ad_preview', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'ad' => $ad,
            'pageTitle' => 'Preview - ' . $ad['ad_name']
        ]);
    }
    
    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================
    
    /**
     * Busca ads com insights do Meta
     * @private
     */
    private function fetchMetaAdsWithInsights($accountId, $accessToken) {
        $accountId = str_replace('act_', '', $accountId);
        
        // Busca dados básicos
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/ads?' . http_build_query([
            'fields' => 'id,name,status,campaign_id,adset_id,creative,preview_shareable_link',
            'access_token' => $accessToken,
            'limit' => 300
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
        
        $ads = $data['data'];
        
        // Busca insights para cada ad
        foreach ($ads as $index => $ad) {
            $ads[$index]['impressions'] = 0;
            $ads[$index]['clicks'] = 0;
            $ads[$index]['spend'] = 0;
            $ads[$index]['reach'] = 0;
            $ads[$index]['frequency'] = 0;
            $ads[$index]['ctr'] = 0;
            $ads[$index]['cpc'] = 0;
            $ads[$index]['cpm'] = 0;
            $ads[$index]['conversions'] = 0;
            
            $insightsUrl = 'https://graph.facebook.com/v18.0/' . $ad['id'] . '/insights?' . http_build_query([
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
                    $ads[$index]['impressions'] = intval($insights['impressions'] ?? 0);
                    $ads[$index]['clicks'] = intval($insights['clicks'] ?? 0);
                    $ads[$index]['spend'] = floatval($insights['spend'] ?? 0);
                    $ads[$index]['reach'] = intval($insights['reach'] ?? 0);
                    $ads[$index]['frequency'] = floatval($insights['frequency'] ?? 0);
                    $ads[$index]['ctr'] = floatval($insights['ctr'] ?? 0);
                    $ads[$index]['cpc'] = floatval($insights['cpc'] ?? 0);
                    $ads[$index]['cpm'] = floatval($insights['cpm'] ?? 0);
                    
                    // Conta conversões
                    if (isset($insights['actions'])) {
                        foreach ($insights['actions'] as $action) {
                            if (in_array($action['action_type'], ['purchase', 'omni_purchase'])) {
                                $ads[$index]['conversions'] += intval($action['value'] ?? 0);
                            }
                        }
                    }
                }
            }
            
            usleep(200000); // 200ms delay
        }
        
        return $ads;
    }
    
    /**
     * Busca preview do ad
     * @private
     */
    private function fetchAdPreview($adId, $accessToken) {
        $url = 'https://graph.facebook.com/v18.0/' . $adId . '?' . http_build_query([
            'fields' => 'preview_shareable_link',
            'access_token' => $accessToken
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['preview_shareable_link'] ?? null;
        }
        
        return null;
    }
    
    /**
     * Atualiza ad no Facebook
     * @private
     */
    private function updateAdOnMeta($adId, $accessToken, $field, $value) {
        $fieldMap = [
            'ad_name' => 'name',
            'status' => 'status'
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
        
        $url = 'https://graph.facebook.com/v18.0/' . $adId;
        
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