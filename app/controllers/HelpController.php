<?php
/**
 * UTMTrack - Controller de Ajuda
 * Arquivo: app/controllers/HelpController.php
 */

class HelpController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->auth->middleware();
    }
    
    /**
     * Página principal de ajuda
     */
    public function index() {
        $this->render('help/index', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pageTitle' => 'Central de Ajuda'
        ]);
    }
    
    /**
     * Página de configuração de crons
     */
    public function crons() {
        // Detecta o caminho completo do servidor baseado na base_url
        $basePath = dirname(dirname(__DIR__));
        $phpPath = '/usr/bin/php'; // Path padrão, pode ser ajustado
        
        // Extrai informações da base_url para gerar o caminho do servidor
        $baseUrl = $this->config['base_url'] ?? 'http://localhost/utmtrack/public';
        $urlParts = parse_url($baseUrl);
        $domain = $urlParts['host'] ?? 'localhost';
        $path = isset($urlParts['path']) ? rtrim($urlParts['path'], '/public') : '';
        $serverPath = '/home/' . $domain . '/public_html' . $path;
        
        // Verifica se os arquivos de cron existem
        $cronFiles = [
            'sync_meta' => [
                'path' => $basePath . '/cron/sync_meta_campaigns.php',
                'exists' => file_exists($basePath . '/cron/sync_meta_campaigns.php')
            ],
            'sync_complete' => [
                'path' => $basePath . '/cron/sync_meta_complete.php',
                'exists' => file_exists($basePath . '/cron/sync_meta_complete.php')
            ],
            'execute_rules' => [
                'path' => $basePath . '/cron/execute_rules.php',
                'exists' => file_exists($basePath . '/cron/execute_rules.php')
            ]
        ];
        
        $this->render('help/crons', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pageTitle' => 'Configuração de Cron Jobs',
            'basePath' => $basePath,
            'serverPath' => $serverPath,
            'phpPath' => $phpPath,
            'cronFiles' => $cronFiles
        ]);
    }
    
    /**
     * Página de webhooks
     */
    public function webhooks() {
        $this->render('help/webhooks', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pageTitle' => 'Como Configurar Webhooks'
        ]);
    }
    
    /**
     * Página de integração Meta Ads
     */
    public function metaAds() {
        $this->render('help/meta-ads', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pageTitle' => 'Integração Meta Ads'
        ]);
    }
    
    /**
     * FAQ
     */
    public function faq() {
        $faqs = [
            [
                'category' => 'Geral',
                'items' => [
                    [
                        'question' => 'Como funciona o UTMTrack?',
                        'answer' => 'O UTMTrack é uma plataforma que permite rastrear suas campanhas de marketing, integrar com Meta Ads, automatizar ações e analisar resultados de forma centralizada.'
                    ],
                    [
                        'question' => 'Preciso de conhecimentos técnicos?',
                        'answer' => 'Não! A interface é intuitiva e amigável. Para funcionalidades avançadas como cron jobs, fornecemos guias passo a passo.'
                    ]
                ]
            ],
            [
                'category' => 'Cron Jobs',
                'items' => [
                    [
                        'question' => 'O que são cron jobs?',
                        'answer' => 'Cron jobs são tarefas automatizadas que executam em intervalos regulares, como sincronizar suas campanhas do Meta Ads ou executar regras automatizadas.'
                    ],
                    [
                        'question' => 'Como configuro os cron jobs?',
                        'answer' => 'Acesse Ajuda → Configuração de Cron Jobs para ver o guia completo com comandos prontos para copiar.'
                    ]
                ]
            ],
            [
                'category' => 'Integrações',
                'items' => [
                    [
                        'question' => 'Quais plataformas posso integrar?',
                        'answer' => 'Atualmente: Meta Ads (Facebook/Instagram), Hotmart, Kiwify, Eduzz, Perfect Pay e Monetizze via webhooks.'
                    ],
                    [
                        'question' => 'Os dados são sincronizados automaticamente?',
                        'answer' => 'Sim! Após configurar os cron jobs, suas campanhas e métricas são sincronizadas automaticamente.'
                    ]
                ]
            ]
        ];
        
        $this->render('help/faq', [
            'config' => $this->config,
            'user' => $this->auth->user(),
            'pageTitle' => 'Perguntas Frequentes',
            'faqs' => $faqs
        ]);
    }
}