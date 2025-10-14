<?php
/**
 * ========================================
 * CAMINHO: /utmtrack/app/controllers/AdController.php
 * ========================================
 * 
 * UTMTrack - Ad Controller COMPLETO
 * Gerencia Anúncios com Sync Meta Ads
 * 
 * @version 11.0 FINAL CORRIGIDO
 * @tested 2025-10-14
 * @author UTMTrack Development Team
 */

class AdController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Dashboard principal de anúncios
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca anúncios do banco com todas as métricas
        $ads = $this->db->fetchAll("
            SELECT 
                a.*,
                ads_set.adset_name,
                ads_set.adset_id as adset_meta_id,
                c.campaign_name,
                c.campaign_id as campaign_meta_id,
                aa.account_name,
                aa.platform,
                
                -- Vendas reais do sistema
                COALESCE((SELECT COUNT(*) FROM sales s 
                          WHERE s.ad_id = a.id AND s.status = 'approved'), 0) as real_sales,
                COALESCE((SELECT SUM(amount) FROM sales s 
                          WHERE s.ad_id = a.id AND s.status = 'approved'), 0) as real_revenue,
                COALESCE((SELECT SUM(s.amount - COALESCE(s.product_cost, 0)) FROM sales s 
                          WHERE s.ad_id = a.id AND s.status = 'approved'), 0) as real_profit
            FROM ads a
            LEFT JOIN adsets ads_set ON ads_set.id = a.adset_id
            LEFT JOIN campaigns c ON c.id = a.campaign_id
            LEFT JOIN ad_accounts aa ON aa.id = c.ad_account_id
            WHERE a.user_id = :user_id
            ORDER BY a.last_sync DESC, a.spent DESC
            LIMIT 500
        ", ['user_id' => $userId]);
        
        $ads = is_array($ads) ? $ads : [];
        
        // Calcula métricas derivadas
        foreach ($ads as &$ad) {
            $spent = floatval($ad['spent'] ?? 0);
            $revenue = floatval($ad['real_revenue'] ?? 0);
            $profit = floatval($ad['real_profit'] ?? 0);
            $sales = intval($ad['real_sales'] ?? 0);
            
            $ad['live_roas'] = ($spent > 0 && $revenue > 0) ? round($revenue / $spent, 2) : 0;
            $ad['live_roi'] = ($spent > 0 && $profit > 0) ? round(($profit / $spent) * 100, 2) : 0;
            $ad['live_margin'] = ($revenue > 0) ? round(($profit / $revenue) * 100, 2) : 0;
            $ad['live_cpa'] = ($sales > 0) ? round($spent / $sales, 2) : 0;
            
            // Calcula métricas se não vieram da API
            if (empty($ad['ctr']) && $ad['impressions'] > 0) {
                $ad['ctr'] = round(($ad['clicks'] / $ad['impressions']) * 100, 2);
            }
            if (empty($ad['cpc']) && $ad['clicks'] > 0) {
                $ad['cpc'] = round($spent / $ad['clicks'], 2);
            }
            if (empty($ad['cpm']) && $ad['impressions'] > 0) {
                $ad['cpm'] = round(($spent / $ad['impressions']) * 1000, 2);
            }
            
            // Tempo desde última sync
            if (!empty($ad['last_sync'])) {
                $diff = (new DateTime())->diff(new DateTime($ad['last_sync']));
                if ($diff->days > 0) {
                    $ad['sync_time'] = $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
                } elseif ($diff->h > 0) {
                    $ad['sync_time'] = $diff->h . 'h';
                } elseif ($diff->i > 0) {
                    $ad['sync_time'] = $diff->i . 'min';
                } else {
                    $ad['sync_time'] = 'agora';
                }
            } else {
                $ad['sync_time'] = 'nunca';
            }
        }
        
        // Estatísticas agregadas
        $stats = [
            'total_ads' => count($ads),
            'active_ads' => 0,
            'total_spent' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
            'total_sales' => 0,
            'total_revenue' => 0,
            'total_profit' => 0,
        ];
        
        foreach ($ads as $a) {
            if ($a['status'] === 'active') $stats['active_ads']++;
            $stats['total_spent'] += floatval($a['spent'] ?? 0);
            $stats['total_impressions'] += intval($a['impressions'] ?? 0);
            $stats['total_clicks'] += intval($a['clicks'] ?? 0);
            $stats['total_conversions'] += intval($a['conversions'] ?? 0);
            $stats['total_sales'] += intval($a['real_sales'] ?? 0);
            $stats['total_revenue'] += floatval($a['real_revenue'] ?? 0);
            $stats['total_profit'] += floatval($a['real_profit'] ?? 0);
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
                FROM user_ad_columns 
                WHERE user_id = :user_id
            ", ['user_id' => $userId]);
            
            if ($userColumnsData && !empty($userColumnsData['columns_config'])) {
                $userColumns = json_decode($userColumnsData['columns_config'], true);
            }
        } catch (Exception $e) {
            // Tabela não existe ainda
        }
        
        // Renderiza a view
        $this->render('ads/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'ads' => $ads,
            'stats' => $stats,
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
                $ads = $this->fetchAdsFromMeta(
                    $account['account_id'], 
                    $account['access_token']
                );
                
                if (empty($ads)) continue;
                
                foreach ($ads as $ad) {
                    try {
                        // Busca campanha relacionada
                        $campaign = $this->db->fetch("
                            SELECT id FROM campaigns 
                            WHERE campaign_id = :campaign_id AND user_id = :user_id
                        ", [
                            'campaign_id' => $ad['campaign_id'],
                            'user_id' => $userId
                        ]);
                        
                        if (!$campaign) continue;
                        
                        // Busca ad set relacionado (se existir)
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
                        
                        $data = $this->prepareAdData($ad, $campaign['id'], $adset ? $adset['id'] : null);
                        
                        if ($exists) {
                            $this->db->update('ads', $data, 'id = :id', ['id' => $exists['id']]);
                            $totalUpdated++;
                        } else {
                            $data['user_id'] = $userId;
                            $data['ad_id'] = $ad['id'];
                            $this->db->insert('ads', $data);
                            $totalImported++;
                        }
                    } catch (Exception $e) {
                        $totalErrors++;
                        error_log("Erro ad {$ad['id']}: " . $e->getMessage());
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
        
        $adId = $data['ad_id'] ?? null;
        $metaAdId = $data['meta_ad_id'] ?? null;
        $status = $data['status'] ?? null;
        
        if (empty($adId) || empty($metaAdId) || empty($status)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $ad = $this->db->fetch("
                SELECT a.*, c.ad_account_id, aa.access_token
                FROM ads a
                JOIN campaigns c ON c.id = a.campaign_id
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE a.id = :id AND a.user_id = :user_id
            ", ['id' => $adId, 'user_id' => $userId]);
            
            if (!$ad) {
                $this->json(['success' => false, 'message' => 'Anúncio não encontrado'], 404);
                return;
            }
            
            $this->updateFieldOnMeta($metaAdId, $ad['access_token'], 'status', $status);
            
            $this->db->update('ads', ['status' => $status], 'id = :id', ['id' => $adId]);
            
            $this->json(['success' => true, 'message' => '✅ Status atualizado!', 'meta_updated' => true]);
            
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
        
        $adId = $data['ad_id'] ?? null;
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;
        
        $allowedFields = ['status', 'ad_name'];
        
        if (empty($adId) || empty($field) || !in_array($field, $allowedFields)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        try {
            $ad = $this->db->fetch("
                SELECT a.*, c.ad_account_id, aa.access_token
                FROM ads a
                JOIN campaigns c ON c.id = a.campaign_id
                JOIN ad_accounts aa ON aa.id = c.ad_account_id
                WHERE a.id = :id AND a.user_id = :user_id
            ", ['id' => $adId, 'user_id' => $userId]);
            
            if (!$ad) {
                $this->json(['success' => false, 'message' => 'Anúncio não encontrado'], 404);
                return;
            }
            
            $metaUpdated = false;
            if (in_array($field, ['status', 'ad_name'])) {
                try {
                    $this->updateFieldOnMeta($ad['ad_id'], $ad['access_token'], $field, $value);
                    $metaUpdated = true;
                } catch (Exception $e) {
                    error_log("Erro Meta: " . $e->getMessage());
                }
            }
            
            $this->db->update('ads', [$field => $value], 'id = :id', ['id' => $adId]);
            
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
    
    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================
    
    /**
     * Busca anúncios da API Meta Ads com insights
     */
    private function fetchAdsFromMeta($accountId, $accessToken) {
        $accountId = str_replace('act_', '', $accountId);
        
        // Busca anúncios
        $url = 'https://graph.facebook.com/v18.0/act_' . $accountId . '/ads?' . http_build_query([
            'fields' => 'id,name,status,campaign_id,adset_id,creative,preview_shareable_link,created_time',
            'access_token' => $accessToken,
            'limit' => 300
        ]);
        
        $response = $this->curlGet($url);
        $data = json_decode($response, true);
        
        if (!isset($data['data'])) {
            throw new Exception('Resposta inválida da API Meta');
        }
        
        $ads = $data['data'];
        
        // Busca insights para cada anúncio
        foreach ($ads as $index => $ad) {
            // Define período de insights (da criação até hoje)
            $since = date('Y-m-d', strtotime($ad['created_time'] ?? '-30 days'));
            $until = date('Y-m-d');
            
            $insightsUrl = 'https://graph.facebook.com/v18.0/' . $ad['id'] . '/insights?' . http_build_query([
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
            $ads[$index]['impressions'] = 0;
            $ads[$index]['clicks'] = 0;
            $ads[$index]['spend'] = 0;
            $ads[$index]['reach'] = 0;
            $ads[$index]['frequency'] = 0;
            $ads[$index]['ctr'] = 0;
            $ads[$index]['cpc'] = 0;
            $ads[$index]['cpm'] = 0;
            $ads[$index]['conversions'] = 0;
            
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
                
                // Processa conversões
                if (isset($insights['actions'])) {
                    foreach ($insights['actions'] as $action) {
                        if (in_array($action['action_type'], ['purchase', 'omni_purchase'])) {
                            $ads[$index]['conversions'] += intval($action['value'] ?? 0);
                        }
                    }
                }
            }
            
            usleep(200000);
        }
        
        return $ads;
    }
    
    /**
     * Prepara dados do anúncio para salvar no banco
     */
    private function prepareAdData($ad, $campaignId, $adsetId) {
        $statusMap = [
            'ACTIVE' => 'active',
            'PAUSED' => 'paused',
            'DELETED' => 'deleted',
            'ARCHIVED' => 'deleted'
        ];
        
        return [
            'campaign_id' => $campaignId,
            'adset_id' => $adsetId,
            'ad_name' => $ad['name'] ?? 'Sem nome',
            'status' => $statusMap[strtoupper($ad['status'] ?? 'PAUSED')] ?? 'paused',
            'creative_id' => $ad['creative']['id'] ?? null,
            'preview_url' => $ad['preview_shareable_link'] ?? null,
            'spent' => floatval($ad['spend'] ?? 0),
            'impressions' => intval($ad['impressions'] ?? 0),
            'clicks' => intval($ad['clicks'] ?? 0),
            'conversions' => intval($ad['conversions'] ?? 0),
            'reach' => intval($ad['reach'] ?? 0),
            'frequency' => floatval($ad['frequency'] ?? 0),
            'ctr' => floatval($ad['ctr'] ?? 0),
            'cpc' => floatval($ad['cpc'] ?? 0),
            'cpm' => floatval($ad['cpm'] ?? 0),
            'created_at' => isset($ad['created_time']) ? date('Y-m-d H:i:s', strtotime($ad['created_time'])) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Atualiza campo no Meta Ads
     */
    private function updateFieldOnMeta($adId, $accessToken, $field, $value) {
        $fieldMap = [
            'ad_name' => 'name',
            'status' => 'status'
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