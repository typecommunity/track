<?php
/**
 * UTMTrack - Controller de UTMs
 * Arquivo: app/controllers/UtmController.php
 */

class UtmController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Página de UTMs
     */
    public function index() {
        $userId = $this->auth->id();
        
        // Busca UTMs do usuário
        $utms = $this->db->fetchAll("
            SELECT * FROM utms 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 100
        ", ['user_id' => $userId]);
        
        // Estatísticas
        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_utms,
                SUM(clicks) as total_clicks
            FROM utms 
            WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        
        $this->render('utms/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'utms' => $utms,
            'stats' => $stats,
            'pageTitle' => 'UTMs'
        ]);
    }
    
    /**
     * Gerar nova UTM
     */
    public function generate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        
        $data = [
            'utm_source' => $this->sanitize($this->post('utm_source')),
            'utm_medium' => $this->sanitize($this->post('utm_medium')),
            'utm_campaign' => $this->sanitize($this->post('utm_campaign')),
            'utm_content' => $this->sanitize($this->post('utm_content')),
            'utm_term' => $this->sanitize($this->post('utm_term')),
        ];
        
        $baseUrl = $this->post('base_url');
        
        // Valida URL base
        if (empty($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $this->json(['success' => false, 'message' => 'URL inválida'], 400);
            return;
        }
        
        // Monta URL com UTMs
        $utmParams = [];
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $utmParams[] = $key . '=' . urlencode($value);
            }
        }
        
        $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
        $fullUrl = $baseUrl . $separator . implode('&', $utmParams);
        
        // Verifica se UTM já existe
        $existingUtm = $this->db->fetch("
            SELECT id FROM utms 
            WHERE user_id = :user_id
            AND utm_source = :source
            AND utm_medium = :medium
            AND utm_campaign = :campaign
            AND utm_content = :content
            AND utm_term = :term
        ", [
            'user_id' => $userId,
            'source' => $data['utm_source'],
            'medium' => $data['utm_medium'],
            'campaign' => $data['utm_campaign'],
            'content' => $data['utm_content'],
            'term' => $data['utm_term']
        ]);
        
        if ($existingUtm) {
            // Atualiza URL e retorna existente
            $this->db->update('utms',
                ['full_url' => $fullUrl],
                'id = :id',
                ['id' => $existingUtm['id']]
            );
            
            $this->json([
                'success' => true,
                'utm_id' => $existingUtm['id'],
                'url' => $fullUrl,
                'message' => 'UTM já existente, URL atualizada'
            ]);
            return;
        }
        
        // Salva no banco
        $utmId = $this->db->insert('utms', array_merge($data, [
            'user_id' => $userId,
            'full_url' => $fullUrl
        ]));
        
        $this->json([
            'success' => true,
            'utm_id' => $utmId,
            'url' => $fullUrl
        ]);
    }
    
    /**
     * Scripts de rastreamento
     */
    public function scripts() {
        $userId = $this->auth->id();
        
        $this->render('utms/scripts', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'user_id' => $userId,
            'pageTitle' => 'Scripts UTM'
        ]);
    }
    
    /**
     * Deletar UTM
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }
        
        $userId = $this->auth->id();
        $utmId = $this->post('utm_id');
        
        if (empty($utmId)) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }
        
        // Verifica se UTM pertence ao usuário
        $utm = $this->db->fetch("
            SELECT id FROM utms 
            WHERE id = :id AND user_id = :user_id
        ", [
            'id' => $utmId,
            'user_id' => $userId
        ]);
        
        if (!$utm) {
            $this->json(['success' => false, 'message' => 'UTM não encontrada'], 404);
            return;
        }
        
        // Deleta UTM
        $this->db->delete('utms', 'id = :id', ['id' => $utmId]);
        
        $this->json(['success' => true, 'message' => 'UTM deletada com sucesso']);
    }
    
    /**
     * Exportar UTMs
     */
    public function export() {
        $userId = $this->auth->id();
        
        // Busca todas as UTMs
        $utms = $this->db->fetchAll("
            SELECT 
                utm_source,
                utm_medium,
                utm_campaign,
                utm_content,
                utm_term,
                full_url,
                clicks,
                created_at
            FROM utms 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
        ", ['user_id' => $userId]);
        
        // Gera CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=utms_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Cabeçalhos
        fputcsv($output, [
            'Source',
            'Medium',
            'Campaign',
            'Content',
            'Term',
            'URL',
            'Cliques',
            'Criado em'
        ]);
        
        // Dados
        foreach ($utms as $utm) {
            fputcsv($output, [
                $utm['utm_source'],
                $utm['utm_medium'],
                $utm['utm_campaign'],
                $utm['utm_content'],
                $utm['utm_term'],
                $utm['full_url'],
                $utm['clicks'],
                $utm['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Estatísticas detalhadas
     */
    public function stats() {
        $userId = $this->auth->id();
        
        // Top 10 campanhas
        $topCampaigns = $this->db->fetchAll("
            SELECT 
                utm_campaign,
                COUNT(*) as total_utms,
                SUM(clicks) as total_clicks
            FROM utms 
            WHERE user_id = :user_id
            AND utm_campaign IS NOT NULL
            GROUP BY utm_campaign
            ORDER BY total_clicks DESC
            LIMIT 10
        ", ['user_id' => $userId]);
        
        // Top 10 sources
        $topSources = $this->db->fetchAll("
            SELECT 
                utm_source,
                COUNT(*) as total_utms,
                SUM(clicks) as total_clicks
            FROM utms 
            WHERE user_id = :user_id
            AND utm_source IS NOT NULL
            GROUP BY utm_source
            ORDER BY total_clicks DESC
            LIMIT 10
        ", ['user_id' => $userId]);
        
        // Estatísticas gerais
        $generalStats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_utms,
                SUM(clicks) as total_clicks,
                AVG(clicks) as avg_clicks,
                MAX(clicks) as max_clicks
            FROM utms 
            WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        
        $this->render('utms/stats', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'topCampaigns' => $topCampaigns,
            'topSources' => $topSources,
            'stats' => $generalStats,
            'pageTitle' => 'Estatísticas UTM'
        ]);
    }
}