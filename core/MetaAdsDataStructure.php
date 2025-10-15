<?php
/**
 * ========================================
 * ESTRUTURA COMPLETA DE DADOS META ADS
 * ========================================
 * Todos os campos disponíveis no Meta Ads API v18.0
 */

class MetaAdsDataStructure {
    
    /**
     * Campos de Campanhas disponíveis no Meta Ads
     */
    const CAMPAIGN_FIELDS = [
        // Identificação
        'id' => ['label' => 'ID da Campanha', 'type' => 'string'],
        'name' => ['label' => 'Nome da Campanha', 'type' => 'string', 'editable' => true],
        'account_id' => ['label' => 'ID da Conta', 'type' => 'string'],
        
        // Status e Configuração
        'status' => ['label' => 'Status', 'type' => 'enum', 'editable' => true],
        'effective_status' => ['label' => 'Status Efetivo', 'type' => 'enum'],
        'configured_status' => ['label' => 'Status Configurado', 'type' => 'enum'],
        'objective' => ['label' => 'Objetivo', 'type' => 'enum'],
        'buying_type' => ['label' => 'Tipo de Compra', 'type' => 'string'],
        'can_use_spend_cap' => ['label' => 'Pode Usar Limite', 'type' => 'boolean'],
        
        // Orçamento
        'daily_budget' => ['label' => 'Orçamento Diário', 'type' => 'currency', 'editable' => true],
        'lifetime_budget' => ['label' => 'Orçamento Vitalício', 'type' => 'currency', 'editable' => true],
        'spend_cap' => ['label' => 'Limite de Gastos', 'type' => 'currency', 'editable' => true],
        'budget_remaining' => ['label' => 'Orçamento Restante', 'type' => 'currency'],
        
        // Datas
        'created_time' => ['label' => 'Data de Criação', 'type' => 'datetime'],
        'start_time' => ['label' => 'Data de Início', 'type' => 'datetime', 'editable' => true],
        'stop_time' => ['label' => 'Data de Término', 'type' => 'datetime', 'editable' => true],
        'updated_time' => ['label' => 'Última Atualização', 'type' => 'datetime'],
        
        // Configurações Avançadas
        'bid_strategy' => ['label' => 'Estratégia de Lance', 'type' => 'enum', 'editable' => true],
        'pacing_type' => ['label' => 'Tipo de Ritmo', 'type' => 'array'],
        'promoted_object' => ['label' => 'Objeto Promovido', 'type' => 'object'],
        'special_ad_categories' => ['label' => 'Categorias Especiais', 'type' => 'array'],
        'special_ad_category' => ['label' => 'Categoria Especial', 'type' => 'enum'],
        'special_ad_category_country' => ['label' => 'País Categoria Especial', 'type' => 'array'],
        
        // Configurações A4C
        'is_skadnetwork_attribution' => ['label' => 'Atribuição SKAdNetwork', 'type' => 'boolean'],
        'smart_promotion_type' => ['label' => 'Tipo Promoção Inteligente', 'type' => 'enum'],
        'source_campaign_id' => ['label' => 'ID Campanha Origem', 'type' => 'string']
    ];
    
    /**
     * Campos de Métricas (Insights) disponíveis
     */
    const INSIGHTS_FIELDS = [
        // Métricas Básicas
        'impressions' => ['label' => 'Impressões', 'type' => 'integer'],
        'clicks' => ['label' => 'Cliques', 'type' => 'integer'],
        'spend' => ['label' => 'Gastos', 'type' => 'currency'],
        'reach' => ['label' => 'Alcance', 'type' => 'integer'],
        'frequency' => ['label' => 'Frequência', 'type' => 'float'],
        'unique_clicks' => ['label' => 'Cliques Únicos', 'type' => 'integer'],
        'unique_impressions' => ['label' => 'Impressões Únicas', 'type' => 'integer'],
        
        // Taxas e Custos
        'ctr' => ['label' => 'CTR', 'type' => 'percentage'],
        'cpc' => ['label' => 'CPC', 'type' => 'currency'],
        'cpm' => ['label' => 'CPM', 'type' => 'currency'],
        'cpp' => ['label' => 'CPP', 'type' => 'currency'],
        'cost_per_unique_click' => ['label' => 'Custo por Clique Único', 'type' => 'currency'],
        'cost_per_action_type' => ['label' => 'Custo por Tipo de Ação', 'type' => 'array'],
        
        // Conversões e Ações
        'actions' => ['label' => 'Ações', 'type' => 'array'],
        'conversions' => ['label' => 'Conversões', 'type' => 'array'],
        'conversion_rate_ranking' => ['label' => 'Ranking Taxa de Conversão', 'type' => 'enum'],
        'conversion_values' => ['label' => 'Valores de Conversão', 'type' => 'array'],
        'cost_per_conversion' => ['label' => 'Custo por Conversão', 'type' => 'array'],
        
        // Ações Específicas de E-commerce
        'purchase' => ['label' => 'Compras', 'type' => 'integer'],
        'purchase_value' => ['label' => 'Valor das Compras', 'type' => 'currency'],
        'omni_purchase' => ['label' => 'Compras Omni', 'type' => 'integer'],
        'omni_purchase_value' => ['label' => 'Valor Compras Omni', 'type' => 'currency'],
        'add_to_cart' => ['label' => 'Add ao Carrinho', 'type' => 'integer'],
        'add_to_cart_value' => ['label' => 'Valor Add Carrinho', 'type' => 'currency'],
        'initiate_checkout' => ['label' => 'Iniciar Checkout', 'type' => 'integer'],
        'initiate_checkout_value' => ['label' => 'Valor Iniciar Checkout', 'type' => 'currency'],
        'add_payment_info' => ['label' => 'Add Info Pagamento', 'type' => 'integer'],
        'add_payment_info_value' => ['label' => 'Valor Info Pagamento', 'type' => 'currency'],
        'view_content' => ['label' => 'Visualizar Conteúdo', 'type' => 'integer'],
        'search' => ['label' => 'Buscas', 'type' => 'integer'],
        'lead' => ['label' => 'Leads', 'type' => 'integer'],
        'complete_registration' => ['label' => 'Cadastros Completos', 'type' => 'integer'],
        
        // Métricas de Vídeo
        'video_play_actions' => ['label' => 'Reproduções de Vídeo', 'type' => 'array'],
        'video_avg_time_watched_actions' => ['label' => 'Tempo Médio Assistido', 'type' => 'array'],
        'video_p25_watched_actions' => ['label' => 'Vídeos 25% Assistidos', 'type' => 'array'],
        'video_p50_watched_actions' => ['label' => 'Vídeos 50% Assistidos', 'type' => 'array'],
        'video_p75_watched_actions' => ['label' => 'Vídeos 75% Assistidos', 'type' => 'array'],
        'video_p95_watched_actions' => ['label' => 'Vídeos 95% Assistidos', 'type' => 'array'],
        'video_p100_watched_actions' => ['label' => 'Vídeos 100% Assistidos', 'type' => 'array'],
        'video_thruplay_watched_actions' => ['label' => 'ThruPlay', 'type' => 'array'],
        'video_15s_watched_actions' => ['label' => 'Vídeos 15s Assistidos', 'type' => 'array'],
        'video_30s_watched_actions' => ['label' => 'Vídeos 30s Assistidos', 'type' => 'array'],
        
        // Métricas de Engajamento
        'engagement_rate_ranking' => ['label' => 'Ranking Taxa Engajamento', 'type' => 'enum'],
        'inline_link_clicks' => ['label' => 'Cliques em Links', 'type' => 'integer'],
        'inline_link_click_ctr' => ['label' => 'CTR de Links', 'type' => 'percentage'],
        'inline_post_engagement' => ['label' => 'Engajamento no Post', 'type' => 'integer'],
        'instant_experience_clicks_to_open' => ['label' => 'Cliques Instant Experience', 'type' => 'integer'],
        'instant_experience_clicks_to_start' => ['label' => 'Início Instant Experience', 'type' => 'integer'],
        
        // Métricas de Qualidade
        'quality_ranking' => ['label' => 'Ranking de Qualidade', 'type' => 'enum'],
        'auction_bid' => ['label' => 'Lance do Leilão', 'type' => 'currency'],
        'auction_competitiveness' => ['label' => 'Competitividade do Leilão', 'type' => 'enum'],
        'auction_max_competitor_bid' => ['label' => 'Lance Máximo Concorrente', 'type' => 'currency'],
        
        // Métricas Sociais
        'social_spend' => ['label' => 'Gastos Sociais', 'type' => 'currency'],
        'unique_outbound_clicks' => ['label' => 'Cliques de Saída Únicos', 'type' => 'array'],
        'outbound_clicks' => ['label' => 'Cliques de Saída', 'type' => 'array'],
        'website_ctr' => ['label' => 'CTR do Website', 'type' => 'array'],
        
        // Métricas Mobile App
        'mobile_app_install' => ['label' => 'Instalações do App', 'type' => 'integer'],
        'app_install' => ['label' => 'Instalações', 'type' => 'integer'],
        'app_use' => ['label' => 'Uso do App', 'type' => 'integer'],
        
        // Métricas de Atribuição
        'estimated_ad_recall_rate' => ['label' => 'Taxa Est. Lembrança', 'type' => 'percentage'],
        'estimated_ad_recall_lift' => ['label' => 'Lift Est. Lembrança', 'type' => 'integer'],
        'estimated_ad_recall_rate_lower_bound' => ['label' => 'Taxa Lembrança Mín', 'type' => 'percentage'],
        'estimated_ad_recall_rate_upper_bound' => ['label' => 'Taxa Lembrança Máx', 'type' => 'percentage'],
        
        // Métricas Calculadas (Custom)
        'roas' => ['label' => 'ROAS', 'type' => 'float', 'calculated' => true],
        'roi' => ['label' => 'ROI', 'type' => 'percentage', 'calculated' => true],
        'margin' => ['label' => 'Margem', 'type' => 'percentage', 'calculated' => true],
        'cpa' => ['label' => 'CPA', 'type' => 'currency', 'calculated' => true],
        'real_revenue' => ['label' => 'Faturamento Real', 'type' => 'currency', 'calculated' => true],
        'real_profit' => ['label' => 'Lucro Real', 'type' => 'currency', 'calculated' => true],
        'real_sales' => ['label' => 'Vendas Reais', 'type' => 'integer', 'calculated' => true]
    ];
    
    /**
     * Mapeamento de Actions do Meta Ads
     */
    const ACTION_TYPES = [
        // Conversões Padrão
        'purchase' => 'Compra',
        'omni_purchase' => 'Compra Omnichannel',
        'add_to_cart' => 'Adicionar ao Carrinho',
        'omni_add_to_cart' => 'Add Carrinho Omni',
        'initiate_checkout' => 'Iniciar Checkout',
        'omni_initiated_checkout' => 'Iniciar Checkout Omni',
        'add_payment_info' => 'Add Info Pagamento',
        'add_to_wishlist' => 'Add Lista Desejos',
        'lead' => 'Lead',
        'complete_registration' => 'Cadastro Completo',
        'contact' => 'Contato',
        'customize_product' => 'Personalizar Produto',
        'donate' => 'Doação',
        'find_location' => 'Encontrar Local',
        'schedule' => 'Agendar',
        'search' => 'Busca',
        'start_trial' => 'Iniciar Teste',
        'submit_application' => 'Enviar Aplicação',
        'subscribe' => 'Inscrever',
        'view_content' => 'Ver Conteúdo',
        'omni_view_content' => 'Ver Conteúdo Omni',
        
        // Engajamento
        'page_engagement' => 'Engajamento na Página',
        'post_engagement' => 'Engajamento no Post',
        'comment' => 'Comentário',
        'like' => 'Curtida',
        'link_click' => 'Clique no Link',
        'onsite_conversion.post_save' => 'Salvar Post',
        'photo_view' => 'Visualização de Foto',
        'video_view' => 'Visualização de Vídeo',
        
        // App Events
        'app_custom_event' => 'Evento Personalizado App',
        'app_install' => 'Instalação do App',
        'app_use' => 'Uso do App',
        'game_plays' => 'Jogadas',
        
        // Offline
        'offline_conversion' => 'Conversão Offline',
        
        // Landing Page
        'landing_page_view' => 'Visualização Landing Page',
        
        // Outros
        'onsite_conversion' => 'Conversão no Site',
        'receive_offer' => 'Receber Oferta',
        'store_visit' => 'Visita à Loja'
    ];
    
    /**
     * Retorna todos os campos disponíveis organizados por categoria
     */
    public static function getAllFieldsByCategory() {
        return [
            'identification' => [
                'label' => 'Identificação',
                'fields' => ['id', 'name', 'account_id']
            ],
            'status' => [
                'label' => 'Status e Configuração',
                'fields' => ['status', 'effective_status', 'configured_status', 'objective']
            ],
            'budget' => [
                'label' => 'Orçamento',
                'fields' => ['daily_budget', 'lifetime_budget', 'spend_cap', 'budget_remaining', 'spend']
            ],
            'performance' => [
                'label' => 'Performance',
                'fields' => ['impressions', 'clicks', 'reach', 'frequency', 'ctr', 'cpc', 'cpm']
            ],
            'conversions' => [
                'label' => 'Conversões',
                'fields' => ['purchase', 'add_to_cart', 'initiate_checkout', 'lead', 'conversions']
            ],
            'engagement' => [
                'label' => 'Engajamento',
                'fields' => ['inline_link_clicks', 'inline_post_engagement', 'video_play_actions']
            ],
            'roi_metrics' => [
                'label' => 'Métricas de ROI',
                'fields' => ['roas', 'roi', 'margin', 'cpa', 'real_revenue', 'real_profit']
            ],
            'quality' => [
                'label' => 'Qualidade',
                'fields' => ['quality_ranking', 'engagement_rate_ranking', 'conversion_rate_ranking']
            ],
            'video' => [
                'label' => 'Métricas de Vídeo',
                'fields' => [
                    'video_play_actions',
                    'video_p25_watched_actions',
                    'video_p50_watched_actions',
                    'video_p75_watched_actions',
                    'video_p100_watched_actions',
                    'video_thruplay_watched_actions'
                ]
            ],
            'mobile' => [
                'label' => 'Mobile App',
                'fields' => ['app_install', 'app_use', 'mobile_app_install']
            ],
            'dates' => [
                'label' => 'Datas',
                'fields' => ['created_time', 'start_time', 'stop_time', 'updated_time']
            ]
        ];
    }
    
    /**
     * Processa actions do Meta Ads para métricas individuais
     */
    public static function processActions($actions) {
        $processed = [];
        
        if (!is_array($actions)) {
            return $processed;
        }
        
        foreach ($actions as $action) {
            $type = $action['action_type'] ?? '';
            $value = $action['value'] ?? 0;
            
            // Mapeia para nome amigável
            $fieldName = str_replace('.', '_', $type);
            
            if (isset(self::ACTION_TYPES[$type])) {
                $processed[$fieldName] = [
                    'label' => self::ACTION_TYPES[$type],
                    'value' => $value,
                    'type' => 'action'
                ];
            }
        }
        
        return $processed;
    }
    
    /**
     * Calcula métricas customizadas
     */
    public static function calculateCustomMetrics($data) {
        $metrics = [];
        
        // ROAS
        if (isset($data['purchase_value']) && isset($data['spend']) && $data['spend'] > 0) {
            $metrics['roas'] = round($data['purchase_value'] / $data['spend'], 2);
        }
        
        // ROI
        if (isset($data['purchase_value']) && isset($data['spend']) && $data['spend'] > 0) {
            $metrics['roi'] = round((($data['purchase_value'] - $data['spend']) / $data['spend']) * 100, 2);
        }
        
        // Margem
        if (isset($data['purchase_value']) && isset($data['spend']) && $data['purchase_value'] > 0) {
            $metrics['margin'] = round((($data['purchase_value'] - $data['spend']) / $data['purchase_value']) * 100, 2);
        }
        
        // CPA
        if (isset($data['purchase']) && isset($data['spend']) && $data['purchase'] > 0) {
            $metrics['cpa'] = round($data['spend'] / $data['purchase'], 2);
        }
        
        // CPI (Custo por Initiate Checkout)
        if (isset($data['initiate_checkout']) && isset($data['spend']) && $data['initiate_checkout'] > 0) {
            $metrics['cpi'] = round($data['spend'] / $data['initiate_checkout'], 2);
        }
        
        // CPL (Custo por Lead)
        if (isset($data['lead']) && isset($data['spend']) && $data['lead'] > 0) {
            $metrics['cpl'] = round($data['spend'] / $data['lead'], 2);
        }
        
        // CAC (Custo por Add ao Carrinho)
        if (isset($data['add_to_cart']) && isset($data['spend']) && $data['add_to_cart'] > 0) {
            $metrics['cac'] = round($data['spend'] / $data['add_to_cart'], 2);
        }
        
        // Taxa de Conversão
        if (isset($data['purchase']) && isset($data['clicks']) && $data['clicks'] > 0) {
            $metrics['conversion_rate'] = round(($data['purchase'] / $data['clicks']) * 100, 2);
        }
        
        // Taxa IC para Compra
        if (isset($data['purchase']) && isset($data['initiate_checkout']) && $data['initiate_checkout'] > 0) {
            $metrics['ic_to_purchase_rate'] = round(($data['purchase'] / $data['initiate_checkout']) * 100, 2);
        }
        
        // Taxa ATC para IC
        if (isset($data['initiate_checkout']) && isset($data['add_to_cart']) && $data['add_to_cart'] > 0) {
            $metrics['atc_to_ic_rate'] = round(($data['initiate_checkout'] / $data['add_to_cart']) * 100, 2);
        }
        
        // Taxa VC para ATC
        if (isset($data['add_to_cart']) && isset($data['view_content']) && $data['view_content'] > 0) {
            $metrics['vc_to_atc_rate'] = round(($data['add_to_cart'] / $data['view_content']) * 100, 2);
        }
        
        // Hook Rate (Vídeos assistidos 3s / Impressões)
        if (isset($data['video_play_actions']) && isset($data['impressions']) && $data['impressions'] > 0) {
            $metrics['hook_rate'] = round(($data['video_play_actions'] / $data['impressions']) * 100, 2);
        }
        
        // Hold Rate (Vídeos 75% / Impressões)
        if (isset($data['video_p75_watched_actions']) && isset($data['impressions']) && $data['impressions'] > 0) {
            $metrics['hold_rate'] = round(($data['video_p75_watched_actions'] / $data['impressions']) * 100, 2);
        }
        
        return $metrics;
    }
    
    /**
     * Retorna configuração para tabela
     */
    public static function getTableConfiguration() {
        return [
            'default_columns' => [
                'checkbox', 'name', 'status', 'daily_budget', 'spend', 
                'impressions', 'clicks', 'ctr', 'cpc', 'purchase', 
                'purchase_value', 'roas', 'roi', 'margin'
            ],
            'sortable_columns' => array_keys(self::CAMPAIGN_FIELDS) + array_keys(self::INSIGHTS_FIELDS),
            'editable_columns' => array_filter(array_keys(self::CAMPAIGN_FIELDS), function($key) {
                return isset(self::CAMPAIGN_FIELDS[$key]['editable']) && self::CAMPAIGN_FIELDS[$key]['editable'];
            }),
            'column_groups' => self::getAllFieldsByCategory(),
            'filters' => [
                'status' => ['active', 'paused', 'archived', 'deleted'],
                'objective' => ['OUTCOME_SALES', 'OUTCOME_LEADS', 'OUTCOME_ENGAGEMENT', 'OUTCOME_TRAFFIC', 'OUTCOME_AWARENESS', 'OUTCOME_APP_PROMOTION'],
                'date_preset' => ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'last_30d', 'last_90d', 'maximum'],
                'breakdown' => ['age', 'gender', 'country', 'region', 'placement', 'device_platform', 'publisher_platform']
            ]
        ];
    }
}