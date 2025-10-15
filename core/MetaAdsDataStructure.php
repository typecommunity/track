<?php
/**
 * ========================================
 * ESTRUTURA COMPLETA DE DADOS META ADS V3.0
 * ========================================
 * TODOS OS CAMPOS DISPONÍVEIS NO META ADS API v18.0
 * 150+ campos de Campaigns, Insights, AdSets e Ads
 */

class MetaAdsDataStructure {
    
    /**
     * ========================================
     * CAMPOS DE CAMPANHAS - COMPLETO
     * ========================================
     * Todos os campos disponíveis na API do Meta
     */
    const CAMPAIGN_FIELDS = [
        // Identificação
        'id' => ['label' => 'ID da Campanha', 'type' => 'string'],
        'name' => ['label' => 'Nome da Campanha', 'type' => 'string', 'editable' => true],
        'account_id' => ['label' => 'ID da Conta', 'type' => 'string'],
        
        // Status
        'status' => ['label' => 'Status', 'type' => 'enum', 'editable' => true],
        'effective_status' => ['label' => 'Status Efetivo', 'type' => 'enum'],
        'configured_status' => ['label' => 'Status Configurado', 'type' => 'enum'],
        
        // Objetivo e Tipo
        'objective' => ['label' => 'Objetivo', 'type' => 'enum'],
        'buying_type' => ['label' => 'Tipo de Compra', 'type' => 'string'],
        'can_use_spend_cap' => ['label' => 'Pode Usar Limite', 'type' => 'boolean'],
        
        // Orçamento
        'daily_budget' => ['label' => 'Orçamento Diário', 'type' => 'currency', 'editable' => true],
        'lifetime_budget' => ['label' => 'Orçamento Vitalício', 'type' => 'currency', 'editable' => true],
        'spend_cap' => ['label' => 'Limite de Gastos', 'type' => 'currency', 'editable' => true],
        'budget_remaining' => ['label' => 'Orçamento Restante', 'type' => 'currency'],
        'budget_rebalance_flag' => ['label' => 'Flag Rebalanceamento', 'type' => 'boolean'],
        
        // 🆕 CBO e ASC
        'campaign_budget_optimization' => ['label' => 'Campaign Budget Optimization', 'type' => 'boolean'],
        'daily_min_spend_target' => ['label' => 'Meta Mínima Diária', 'type' => 'currency'],
        'daily_spend_cap' => ['label' => 'Limite Diário', 'type' => 'currency'],
        'lifetime_min_spend_target' => ['label' => 'Meta Mínima Vitalícia', 'type' => 'currency'],
        'lifetime_spend_cap' => ['label' => 'Limite Vitalício', 'type' => 'currency'],
        
        // Datas
        'created_time' => ['label' => 'Data de Criação', 'type' => 'datetime'],
        'start_time' => ['label' => 'Data de Início', 'type' => 'datetime', 'editable' => true],
        'stop_time' => ['label' => 'Data de Término', 'type' => 'datetime', 'editable' => true],
        'updated_time' => ['label' => 'Última Atualização', 'type' => 'datetime'],
        
        // Estratégia de Lance
        'bid_strategy' => ['label' => 'Estratégia de Lance', 'type' => 'enum', 'editable' => true],
        'bid_amount' => ['label' => 'Valor do Lance', 'type' => 'currency'],
        'bid_constraints' => ['label' => 'Restrições de Lance', 'type' => 'object'],
        
        // Configurações Avançadas
        'pacing_type' => ['label' => 'Tipo de Ritmo', 'type' => 'array'],
        'promoted_object' => ['label' => 'Objeto Promovido', 'type' => 'object'],
        'special_ad_categories' => ['label' => 'Categorias Especiais', 'type' => 'array'],
        'special_ad_category' => ['label' => 'Categoria Especial', 'type' => 'enum'],
        'special_ad_category_country' => ['label' => 'País Categoria Especial', 'type' => 'array'],
        
        // Apple (SKAdNetwork)
        'is_skadnetwork_attribution' => ['label' => 'Atribuição SKAdNetwork', 'type' => 'boolean'],
        'smart_promotion_type' => ['label' => 'Tipo Promoção Inteligente', 'type' => 'enum'],
        'source_campaign_id' => ['label' => 'ID Campanha Origem', 'type' => 'string'],
        'source_campaign' => ['label' => 'Campanha Origem', 'type' => 'object'],
        
        // 🆕 Informações Críticas
        'issues_info' => ['label' => 'Informações de Problemas', 'type' => 'array'],
        'recommendations' => ['label' => 'Recomendações', 'type' => 'array'],
        
        // 🆕 Labels e Organização
        'adlabels' => ['label' => 'Etiquetas', 'type' => 'array'],
        'campaign_group_id' => ['label' => 'ID Grupo de Campanhas', 'type' => 'string'],
        
        // 🆕 Orçamento Compartilhado
        'topline_id' => ['label' => 'ID Orçamento Compartilhado', 'type' => 'string'],
        
        // Outros
        'can_create_brand_lift_study' => ['label' => 'Pode Criar Estudo Brand Lift', 'type' => 'boolean'],
        'has_secondary_skadnetwork_reporting' => ['label' => 'Relatório SKAdNetwork Secundário', 'type' => 'boolean'],
        'is_budget_schedule_enabled' => ['label' => 'Agendamento de Orçamento Ativo', 'type' => 'boolean'],
        'iterative_split_test_configs' => ['label' => 'Configs Teste Split', 'type' => 'array'],
        'last_budget_toggling_time' => ['label' => 'Última Mudança Orçamento', 'type' => 'datetime'],
        'upstream_events' => ['label' => 'Eventos Upstream', 'type' => 'object']
    ];
    
    /**
     * ========================================
     * CAMPOS DE INSIGHTS - COMPLETO
     * ========================================
     * Todas as métricas disponíveis
     */
    const INSIGHTS_FIELDS = [
        // ========================================
        // MÉTRICAS BÁSICAS
        // ========================================
        'impressions' => ['label' => 'Impressões', 'type' => 'integer'],
        'clicks' => ['label' => 'Cliques', 'type' => 'integer'],
        'spend' => ['label' => 'Gastos', 'type' => 'currency'],
        'reach' => ['label' => 'Alcance', 'type' => 'integer'],
        'frequency' => ['label' => 'Frequência', 'type' => 'float'],
        'unique_clicks' => ['label' => 'Cliques Únicos', 'type' => 'integer'],
        'unique_impressions' => ['label' => 'Impressões Únicas', 'type' => 'integer'],
        
        // ========================================
        // TAXAS E CUSTOS
        // ========================================
        'ctr' => ['label' => 'CTR', 'type' => 'percentage'],
        'cpc' => ['label' => 'CPC', 'type' => 'currency'],
        'cpm' => ['label' => 'CPM', 'type' => 'currency'],
        'cpp' => ['label' => 'CPP (Custo por Pessoa)', 'type' => 'currency'],
        'cost_per_unique_click' => ['label' => 'Custo por Clique Único', 'type' => 'currency'],
        'cost_per_inline_link_click' => ['label' => 'Custo por Clique em Link', 'type' => 'currency'],
        'cost_per_inline_post_engagement' => ['label' => 'Custo por Engajamento', 'type' => 'currency'],
        'cost_per_unique_inline_link_click' => ['label' => 'Custo por Clique Único em Link', 'type' => 'currency'],
        'cost_per_unique_action_type' => ['label' => 'Custo por Ação Única', 'type' => 'array'],
        'cost_per_action_type' => ['label' => 'Custo por Tipo de Ação', 'type' => 'array'],
        'cost_per_conversion' => ['label' => 'Custo por Conversão', 'type' => 'array'],
        'cost_per_dda_countby_convs' => ['label' => 'Custo por Conversão DDA', 'type' => 'currency'],
        'cost_per_outbound_click' => ['label' => 'Custo por Clique Externo', 'type' => 'array'],
        'cost_per_thruplay' => ['label' => 'Custo por ThruPlay', 'type' => 'array'],
        
        // ========================================
        // CONVERSÕES E AÇÕES
        // ========================================
        'actions' => ['label' => 'Ações', 'type' => 'array'],
        'action_values' => ['label' => 'Valores de Ações', 'type' => 'array'],
        'conversions' => ['label' => 'Conversões', 'type' => 'array'],
        'conversion_values' => ['label' => 'Valores de Conversões', 'type' => 'array'],
        'unique_actions' => ['label' => 'Ações Únicas', 'type' => 'array'],
        
        // Conversões Específicas (para facilitar acesso)
        'purchase' => ['label' => 'Compras', 'type' => 'integer'],
        'purchase_value' => ['label' => 'Valor de Compras', 'type' => 'currency'],
        'add_to_cart' => ['label' => 'Adicionar ao Carrinho', 'type' => 'integer'],
        'add_to_cart_value' => ['label' => 'Valor Add Carrinho', 'type' => 'currency'],
        'initiate_checkout' => ['label' => 'Iniciar Checkout', 'type' => 'integer'],
        'initiate_checkout_value' => ['label' => 'Valor IC', 'type' => 'currency'],
        'lead' => ['label' => 'Leads', 'type' => 'integer'],
        'complete_registration' => ['label' => 'Registros Completos', 'type' => 'integer'],
        'view_content' => ['label' => 'Ver Conteúdo', 'type' => 'integer'],
        'search' => ['label' => 'Buscas', 'type' => 'integer'],
        
        // ========================================
        // 🆕 MÉTRICAS DE VÍDEO - COMPLETO
        // ========================================
        'video_play_actions' => ['label' => 'Reproduções de Vídeo', 'type' => 'array'],
        'video_avg_time_watched_actions' => ['label' => 'Tempo Médio Assistido', 'type' => 'array'],
        'video_continuous_2_sec_watched_actions' => ['label' => '2s Contínuos', 'type' => 'array'],
        'video_p25_watched_actions' => ['label' => 'Vídeo 25%', 'type' => 'array'],
        'video_p50_watched_actions' => ['label' => 'Vídeo 50%', 'type' => 'array'],
        'video_p75_watched_actions' => ['label' => 'Vídeo 75%', 'type' => 'array'],
        'video_p95_watched_actions' => ['label' => 'Vídeo 95%', 'type' => 'array'],
        'video_p100_watched_actions' => ['label' => 'Vídeo 100%', 'type' => 'array'],
        'video_thruplay_watched_actions' => ['label' => 'ThruPlay', 'type' => 'array'],
        'video_15s_watched_actions' => ['label' => 'Vídeos 15s', 'type' => 'array'],
        'video_30s_watched_actions' => ['label' => 'Vídeos 30s', 'type' => 'array'],
        
        // ========================================
        // 🆕 ENGAJAMENTO - COMPLETO
        // ========================================
        'inline_link_clicks' => ['label' => 'Cliques em Links', 'type' => 'integer'],
        'inline_link_click_ctr' => ['label' => 'CTR de Links', 'type' => 'percentage'],
        'inline_post_engagement' => ['label' => 'Engajamento no Post', 'type' => 'integer'],
        'post_engagement' => ['label' => 'Engajamento Total', 'type' => 'integer'],
        'page_engagement' => ['label' => 'Engajamento Página', 'type' => 'integer'],
        'post_reactions' => ['label' => 'Reações', 'type' => 'integer'],
        'post_saves' => ['label' => 'Salvamentos', 'type' => 'integer'],
        'post_shares' => ['label' => 'Compartilhamentos', 'type' => 'integer'],
        'post_comments' => ['label' => 'Comentários', 'type' => 'integer'],
        'photo_view' => ['label' => 'Visualizações Foto', 'type' => 'integer'],
        
        // ========================================
        // 🆕 LINKS E CLIQUES EXTERNOS
        // ========================================
        'outbound_clicks' => ['label' => 'Cliques Externos', 'type' => 'array'],
        'outbound_clicks_ctr' => ['label' => 'CTR Cliques Externos', 'type' => 'array'],
        'unique_outbound_clicks' => ['label' => 'Cliques Externos Únicos', 'type' => 'array'],
        'unique_outbound_clicks_ctr' => ['label' => 'CTR Únicos Externos', 'type' => 'array'],
        'unique_inline_link_clicks' => ['label' => 'Cliques Únicos em Links', 'type' => 'integer'],
        'unique_inline_link_click_ctr' => ['label' => 'CTR Único Links', 'type' => 'percentage'],
        'unique_link_clicks_ctr' => ['label' => 'CTR Link Único', 'type' => 'percentage'],
        
        // ========================================
        // 🆕 QUALIDADE E RANKINGS - CRÍTICO
        // ========================================
        'quality_ranking' => ['label' => 'Ranking de Qualidade', 'type' => 'enum'],
        'engagement_rate_ranking' => ['label' => 'Ranking Engajamento', 'type' => 'enum'],
        'conversion_rate_ranking' => ['label' => 'Ranking Conversão', 'type' => 'enum'],
        
        // ========================================
        // 🆕 LEILÃO E LANCES
        // ========================================
        'auction_bid' => ['label' => 'Lance do Leilão', 'type' => 'currency'],
        'auction_competitiveness' => ['label' => 'Competitividade', 'type' => 'enum'],
        'auction_max_competitor_bid' => ['label' => 'Lance Máx Concorrente', 'type' => 'currency'],
        
        // ========================================
        // SOCIAL E CONTEXTO
        // ========================================
        'social_spend' => ['label' => 'Gastos Sociais', 'type' => 'currency'],
        'instant_experience_clicks_to_open' => ['label' => 'Cliques IX', 'type' => 'integer'],
        'instant_experience_clicks_to_start' => ['label' => 'Início IX', 'type' => 'integer'],
        'instant_experience_outbound_clicks' => ['label' => 'Cliques Externos IX', 'type' => 'array'],
        
        // ========================================
        // 🆕 MOBILE APP
        // ========================================
        'mobile_app_install' => ['label' => 'Instalações App', 'type' => 'integer'],
        'app_install' => ['label' => 'Instalações', 'type' => 'integer'],
        'app_use' => ['label' => 'Uso do App', 'type' => 'integer'],
        'app_custom_event' => ['label' => 'Evento Personalizado', 'type' => 'integer'],
        
        // ========================================
        // 🆕 ESTIMATIVAS E BRAND
        // ========================================
        'estimated_ad_recall_rate' => ['label' => 'Taxa Lembrança Est.', 'type' => 'percentage'],
        'estimated_ad_recall_lift' => ['label' => 'Lift Lembrança Est.', 'type' => 'integer'],
        'estimated_ad_recall_lift_rate' => ['label' => 'Taxa Lift Est.', 'type' => 'percentage'],
        'estimated_ad_recallers' => ['label' => 'Pessoas Lembrança Est.', 'type' => 'integer'],
        
        // ========================================
        // 🆕 WEBSITE E CONVERSÕES WEB
        // ========================================
        'website_ctr' => ['label' => 'CTR Website', 'type' => 'array'],
        'website_purchase_roas' => ['label' => 'ROAS Website', 'type' => 'array'],
        
        // ========================================
        // 🆕 CATÁLOGO (E-COMMERCE)
        // ========================================
        'catalog_segment_value' => ['label' => 'Valor Segmento Catálogo', 'type' => 'array'],
        'catalog_segment_actions' => ['label' => 'Ações Segmento Catálogo', 'type' => 'array'],
        'catalog_segment_click_through_rate' => ['label' => 'CTR Catálogo', 'type' => 'array'],
        
        // ========================================
        // CANVAS (INSTANT EXPERIENCE)
        // ========================================
        'canvas_avg_view_percent' => ['label' => 'Média Visualização Canvas', 'type' => 'percentage'],
        'canvas_avg_view_time' => ['label' => 'Tempo Médio Canvas', 'type' => 'float'],
        
        // ========================================
        // FULL VIEW
        // ========================================
        'full_view_impressions' => ['label' => 'Impressões Full View', 'type' => 'integer'],
        'full_view_reach' => ['label' => 'Alcance Full View', 'type' => 'integer'],
        
        // ========================================
        // 🆕 DDA (DATA-DRIVEN ATTRIBUTION)
        // ========================================
        'dda_results' => ['label' => 'Resultados DDA', 'type' => 'array'],
        
        // ========================================
        // PERÍODOS
        // ========================================
        'date_start' => ['label' => 'Data Início', 'type' => 'date'],
        'date_stop' => ['label' => 'Data Fim', 'type' => 'date'],
        
        // ========================================
        // MÉTRICAS CALCULADAS (nosso sistema)
        // ========================================
        'roas' => ['label' => 'ROAS', 'type' => 'float'],
        'roi' => ['label' => 'ROI', 'type' => 'percentage'],
        'margin' => ['label' => 'Margem', 'type' => 'percentage'],
        'cpa' => ['label' => 'CPA', 'type' => 'currency'],
        'cpi' => ['label' => 'Custo por IC', 'type' => 'currency'],
        'conversion_rate' => ['label' => 'Taxa Conversão', 'type' => 'percentage']
    ];
    
    /**
     * ========================================
     * CAMPOS DE ADSETS - COMPLETO
     * ========================================
     */
    const ADSET_FIELDS = [
        'id' => ['label' => 'ID', 'type' => 'string'],
        'name' => ['label' => 'Nome', 'type' => 'string'],
        'status' => ['label' => 'Status', 'type' => 'enum'],
        'effective_status' => ['label' => 'Status Efetivo', 'type' => 'enum'],
        'campaign_id' => ['label' => 'ID Campanha', 'type' => 'string'],
        'account_id' => ['label' => 'ID Conta', 'type' => 'string'],
        
        // Orçamento
        'daily_budget' => ['label' => 'Orçamento Diário', 'type' => 'currency'],
        'lifetime_budget' => ['label' => 'Orçamento Vitalício', 'type' => 'currency'],
        'budget_remaining' => ['label' => 'Restante', 'type' => 'currency'],
        'daily_min_spend_target' => ['label' => 'Meta Mín Diária', 'type' => 'currency'],
        'daily_spend_cap' => ['label' => 'Limite Diário', 'type' => 'currency'],
        'lifetime_min_spend_target' => ['label' => 'Meta Mín Vitalícia', 'type' => 'currency'],
        'lifetime_spend_cap' => ['label' => 'Limite Vitalício', 'type' => 'currency'],
        
        // Otimização
        'optimization_goal' => ['label' => 'Meta Otimização', 'type' => 'enum'],
        'optimization_sub_event' => ['label' => 'Sub-evento', 'type' => 'string'],
        'billing_event' => ['label' => 'Evento Cobrança', 'type' => 'enum'],
        'bid_amount' => ['label' => 'Lance', 'type' => 'currency'],
        'bid_strategy' => ['label' => 'Estratégia Lance', 'type' => 'enum'],
        'bid_constraints' => ['label' => 'Restrições', 'type' => 'object'],
        'bid_info' => ['label' => 'Info Lance', 'type' => 'object'],
        
        // Segmentação
        'targeting' => ['label' => 'Segmentação', 'type' => 'object'],
        'promoted_object' => ['label' => 'Objeto Promovido', 'type' => 'object'],
        'attribution_spec' => ['label' => 'Atribuição', 'type' => 'array'],
        'destination_type' => ['label' => 'Tipo Destino', 'type' => 'enum'],
        
        // Configurações
        'pacing_type' => ['label' => 'Tipo Ritmo', 'type' => 'array'],
        'multi_optimization_goal_weight' => ['label' => 'Peso Multi-otimização', 'type' => 'string'],
        'recurring_budget_semantics' => ['label' => 'Orçamento Recorrente', 'type' => 'boolean'],
        'rf_prediction_id' => ['label' => 'ID Predição RF', 'type' => 'string'],
        'time_based_ad_rotation_id_blocks' => ['label' => 'Rotação Anúncios', 'type' => 'array'],
        
        // 🆕 Aprendizado e Problemas
        'learning_stage_info' => ['label' => 'Info Aprendizado', 'type' => 'object'],
        'issues_info' => ['label' => 'Problemas', 'type' => 'array'],
        'recommendations' => ['label' => 'Recomendações', 'type' => 'array'],
        
        // Datas
        'created_time' => ['label' => 'Criado', 'type' => 'datetime'],
        'updated_time' => ['label' => 'Atualizado', 'type' => 'datetime'],
        'start_time' => ['label' => 'Início', 'type' => 'datetime'],
        'end_time' => ['label' => 'Fim', 'type' => 'datetime']
    ];
    
    /**
     * ========================================
     * CAMPOS DE ADS - COMPLETO
     * ========================================
     */
    const AD_FIELDS = [
        'id' => ['label' => 'ID', 'type' => 'string'],
        'name' => ['label' => 'Nome', 'type' => 'string'],
        'status' => ['label' => 'Status', 'type' => 'enum'],
        'effective_status' => ['label' => 'Status Efetivo', 'type' => 'enum'],
        'campaign_id' => ['label' => 'ID Campanha', 'type' => 'string'],
        'adset_id' => ['label' => 'ID AdSet', 'type' => 'string'],
        'account_id' => ['label' => 'ID Conta', 'type' => 'string'],
        
        // Creative
        'creative' => ['label' => 'Creative', 'type' => 'object'],
        'preview_shareable_link' => ['label' => 'Link Preview', 'type' => 'string'],
        
        // Lance
        'bid_amount' => ['label' => 'Lance', 'type' => 'currency'],
        'bid_type' => ['label' => 'Tipo Lance', 'type' => 'enum'],
        'bid_info' => ['label' => 'Info Lance', 'type' => 'object'],
        
        // Conversões
        'conversion_specs' => ['label' => 'Specs Conversão', 'type' => 'array'],
        'tracking_specs' => ['label' => 'Specs Tracking', 'type' => 'array'],
        
        // 🆕 Recomendações e Labels
        'recommendations' => ['label' => 'Recomendações', 'type' => 'array'],
        'adlabels' => ['label' => 'Labels', 'type' => 'array'],
        'issues_info' => ['label' => 'Problemas', 'type' => 'array'],
        
        // Outros
        'source_ad_id' => ['label' => 'ID Origem', 'type' => 'string'],
        'engagement_audience' => ['label' => 'Audiência Engajamento', 'type' => 'boolean'],
        'last_updated_by_app_id' => ['label' => 'App Atualização', 'type' => 'string'],
        
        // Datas
        'created_time' => ['label' => 'Criado', 'type' => 'datetime'],
        'updated_time' => ['label' => 'Atualizado', 'type' => 'datetime']
    ];
    
    /**
     * Mapeia tipos de ações do Meta para nomes de campos
     */
    const ACTION_TYPES = [
        'purchase' => 'purchase',
        'add_to_cart' => 'add_to_cart',
        'initiate_checkout' => 'initiate_checkout',
        'lead' => 'lead',
        'complete_registration' => 'complete_registration',
        'view_content' => 'view_content',
        'search' => 'search',
        'app_install' => 'app_install',
        'app_use' => 'app_use',
        'link_click' => 'link_click',
        'page_engagement' => 'page_engagement',
        'post_engagement' => 'post_engagement',
        'photo_view' => 'photo_view',
        'video_view' => 'video_view',
        'landing_page_view' => 'landing_page_view',
        'omni_purchase' => 'omni_purchase',
        'omni_add_to_cart' => 'omni_add_to_cart',
        'omni_initiated_checkout' => 'omni_initiated_checkout',
        'omni_view_content' => 'omni_view_content'
    ];
    
    /**
     * Processa actions do Meta Ads
     */
    public static function processActions($actions) {
        $processed = [];
        
        if (!is_array($actions)) {
            return $processed;
        }
        
        foreach ($actions as $action) {
            $type = $action['action_type'] ?? '';
            $value = floatval($action['value'] ?? 0);
            
            // Normaliza nome
            $fieldName = str_replace('.', '_', $type);
            $fieldName = str_replace('offsite_conversion_fb_pixel_', '', $fieldName);
            $fieldName = str_replace('omni_', '', $fieldName);
            
            $processed[$fieldName] = [
                'value' => $value,
                'type' => $type
            ];
        }
        
        return $processed;
    }
    
    /**
     * Processa action_values do Meta Ads
     */
    public static function processActionValues($actionValues) {
        $processed = [];
        
        if (!is_array($actionValues)) {
            return $processed;
        }
        
        foreach ($actionValues as $actionValue) {
            $type = $actionValue['action_type'] ?? '';
            $value = floatval($actionValue['value'] ?? 0);
            
            $fieldName = str_replace('.', '_', $type) . '_value';
            $fieldName = str_replace('offsite_conversion_fb_pixel_', '', $fieldName);
            
            $processed[$fieldName] = $value;
        }
        
        return $processed;
    }
    
    /**
     * Calcula métricas customizadas
     */
    public static function calculateCustomMetrics($data) {
        $metrics = [];
        
        // ROAS
        if (isset($data['spend']) && isset($data['purchase_value']) && $data['spend'] > 0) {
            $metrics['roas'] = round($data['purchase_value'] / $data['spend'], 2);
        }
        
        // ROI
        if (isset($data['spend']) && isset($data['purchase_value']) && $data['spend'] > 0) {
            $metrics['roi'] = round((($data['purchase_value'] - $data['spend']) / $data['spend']) * 100, 2);
        }
        
        // Margem
        if (isset($data['purchase_value']) && $data['purchase_value'] > 0 && isset($data['spend'])) {
            $metrics['margin'] = round((($data['purchase_value'] - $data['spend']) / $data['purchase_value']) * 100, 2);
        }
        
        // CPA (Custo por Compra)
        if (isset($data['purchase']) && $data['purchase'] > 0 && isset($data['spend'])) {
            $metrics['cpa'] = round($data['spend'] / $data['purchase'], 2);
        }
        
        // CPI (Custo por IC)
        if (isset($data['initiate_checkout']) && $data['initiate_checkout'] > 0 && isset($data['spend'])) {
            $metrics['cpi'] = round($data['spend'] / $data['initiate_checkout'], 2);
        }
        
        // Taxa de Conversão
        if (isset($data['clicks']) && $data['clicks'] > 0 && isset($data['purchase'])) {
            $metrics['conversion_rate'] = round(($data['purchase'] / $data['clicks']) * 100, 2);
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
            'sortable_columns' => array_merge(
                array_keys(self::CAMPAIGN_FIELDS),
                array_keys(self::INSIGHTS_FIELDS)
            ),
            'editable_columns' => array_filter(array_keys(self::CAMPAIGN_FIELDS), function($key) {
                return isset(self::CAMPAIGN_FIELDS[$key]['editable']) && self::CAMPAIGN_FIELDS[$key]['editable'];
            })
        ];
    }
    
    /**
     * Retorna todos os campos organizados por categoria
     */
    public static function getAllFieldsByCategory() {
        return [
            'identification' => [
                'label' => 'Identificação',
                'fields' => ['id', 'name', 'account_id']
            ],
            'status' => [
                'label' => 'Status',
                'fields' => ['status', 'effective_status', 'configured_status']
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
                'fields' => ['purchase', 'purchase_value', 'add_to_cart', 'initiate_checkout', 'lead']
            ],
            'quality' => [
                'label' => 'Qualidade',
                'fields' => ['quality_ranking', 'engagement_rate_ranking', 'conversion_rate_ranking']
            ],
            'video' => [
                'label' => 'Vídeo',
                'fields' => ['video_play_actions', 'video_p25_watched_actions', 'video_p50_watched_actions', 'video_p75_watched_actions', 'video_p100_watched_actions']
            ],
            'roi_metrics' => [
                'label' => 'ROI',
                'fields' => ['roas', 'roi', 'margin', 'cpa', 'cpi']
            ]
        ];
    }
}