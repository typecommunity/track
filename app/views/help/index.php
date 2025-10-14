<?php
/**
 * UTMTrack - Central de Ajuda
 * Arquivo: app/views/help/index.php
 */

$user = $user ?? null;
$config = $config ?? [];
?>

<!-- Content usando toda a largura da página -->
<div style="padding: 40px; max-width: 100%; background: #0f172a; min-height: 100vh;">
    <!-- Header -->
    <div style="margin-bottom: 50px;">
        <h1 style="color: white; font-size: 42px; margin-bottom: 15px; font-weight: 800;">
            💡 Central de Ajuda
        </h1>
        <p style="color: #94a3b8; font-size: 18px;">
            Tudo o que você precisa para configurar e usar o UTMTrack
        </p>
    </div>

    <!-- Cards de Ajuda -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; margin-bottom: 50px;">
        <a href="index.php?page=help-crons" style="text-decoration: none; display: block;">
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; padding: 40px; border: 1px solid #334155; transition: all 0.3s; cursor: pointer; height: 100%;">
                <div style="font-size: 64px; margin-bottom: 25px;">⏰</div>
                <h3 style="color: #e2e8f0; font-size: 24px; font-weight: 700; margin-bottom: 15px;">Configuração de Cron Jobs</h3>
                <p style="color: #94a3b8; font-size: 15px; line-height: 1.7;">
                    Aprenda a configurar tarefas automatizadas para sincronização de campanhas e execução de regras.
                </p>
            </div>
        </a>

        <a href="index.php?page=help-webhooks" style="text-decoration: none; display: block;">
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; padding: 40px; border: 1px solid #334155; transition: all 0.3s; cursor: pointer; height: 100%;">
                <div style="font-size: 64px; margin-bottom: 25px;">🔗</div>
                <h3 style="color: #e2e8f0; font-size: 24px; font-weight: 700; margin-bottom: 15px;">Como Configurar Webhooks</h3>
                <p style="color: #94a3b8; font-size: 15px; line-height: 1.7;">
                    Guia completo para integrar plataformas de pagamento como Hotmart, Kiwify, Eduzz e outras.
                </p>
            </div>
        </a>

        <a href="index.php?page=help-meta-ads" style="text-decoration: none; display: block;">
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; padding: 40px; border: 1px solid #334155; transition: all 0.3s; cursor: pointer; height: 100%;">
                <div style="font-size: 64px; margin-bottom: 25px;">📱</div>
                <h3 style="color: #e2e8f0; font-size: 24px; font-weight: 700; margin-bottom: 15px;">Integração Meta Ads</h3>
                <p style="color: #94a3b8; font-size: 15px; line-height: 1.7;">
                    Conecte sua conta do Facebook e Instagram Ads para importar campanhas e métricas automaticamente.
                </p>
            </div>
        </a>

        <a href="index.php?page=regras" style="text-decoration: none; display: block;">
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; padding: 40px; border: 1px solid #334155; transition: all 0.3s; cursor: pointer; height: 100%;">
                <div style="font-size: 64px; margin-bottom: 25px;">🤖</div>
                <h3 style="color: #e2e8f0; font-size: 24px; font-weight: 700; margin-bottom: 15px;">Regras Automatizadas</h3>
                <p style="color: #94a3b8; font-size: 15px; line-height: 1.7;">
                    Crie regras para automatizar ações nas suas campanhas baseado em métricas como ROAS, ROI e CPA.
                </p>
            </div>
        </a>

        <a href="index.php?page=help-faq" style="text-decoration: none; display: block;">
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; padding: 40px; border: 1px solid #334155; transition: all 0.3s; cursor: pointer; height: 100%;">
                <div style="font-size: 64px; margin-bottom: 25px;">❓</div>
                <h3 style="color: #e2e8f0; font-size: 24px; font-weight: 700; margin-bottom: 15px;">Perguntas Frequentes</h3>
                <p style="color: #94a3b8; font-size: 15px; line-height: 1.7;">
                    Respostas para as dúvidas mais comuns sobre o uso da plataforma.
                </p>
            </div>
        </a>

        <a href="mailto:alberto@ataweb.ppg.br" style="text-decoration: none; display: block;">
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; padding: 40px; border: 1px solid #334155; transition: all 0.3s; cursor: pointer; height: 100%;">
                <div style="font-size: 64px; margin-bottom: 25px;">📧</div>
                <h3 style="color: #e2e8f0; font-size: 24px; font-weight: 700; margin-bottom: 15px;">Suporte</h3>
                <p style="color: #94a3b8; font-size: 15px; line-height: 1.7;">
                    Precisa de ajuda? Entre em contato com nossa equipe de suporte.
                </p>
            </div>
        </a>
    </div>

    <!-- Links Rápidos -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 40px; margin-bottom: 50px;">
        <h3 style="color: white; font-size: 26px; font-weight: 700; margin-bottom: 30px;">🔥 Primeiros Passos</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
            <a href="index.php?page=integracoes" style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; text-decoration: none; color: white; transition: all 0.3s;">
                <span style="font-size: 24px;">1️⃣</span>
                <span style="font-size: 16px; font-weight: 600;">Conectar Meta Ads</span>
            </a>
            <a href="index.php?page=help-crons" style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; text-decoration: none; color: white; transition: all 0.3s;">
                <span style="font-size: 24px;">2️⃣</span>
                <span style="font-size: 16px; font-weight: 600;">Configurar Cron Jobs</span>
            </a>
            <a href="index.php?page=webhooks" style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; text-decoration: none; color: white; transition: all 0.3s;">
                <span style="font-size: 24px;">3️⃣</span>
                <span style="font-size: 16px; font-weight: 600;">Criar Webhooks</span>
            </a>
            <a href="index.php?page=regras" style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; text-decoration: none; color: white; transition: all 0.3s;">
                <span style="font-size: 24px;">4️⃣</span>
                <span style="font-size: 16px; font-weight: 600;">Criar Regras</span>
            </a>
        </div>
    </div>

    <!-- Informações do Sistema -->
    <div style="background: #1e293b; border-radius: 20px; padding: 40px; border: 1px solid #334155;">
        <h3 style="color: #e2e8f0; font-size: 22px; font-weight: 700; margin-bottom: 30px;">ℹ️ Informações do Sistema</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div>
                <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Versão</div>
                <div style="color: #e2e8f0; font-size: 20px; font-weight: 600;"><?= $config['version'] ?? '1.0.0' ?></div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">PHP</div>
                <div style="color: #e2e8f0; font-size: 20px; font-weight: 600;"><?= PHP_VERSION ?></div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Timezone</div>
                <div style="color: #e2e8f0; font-size: 20px; font-weight: 600;"><?= $config['timezone'] ?? date_default_timezone_get() ?></div>
            </div>
        </div>
    </div>
</div>

<style>
/* Hover effects */
a div[style*="border: 1px solid #334155"]:hover {
    transform: translateY(-5px);
    border-color: #667eea !important;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
}

a[style*="background: rgba(255, 255, 255, 0.1)"]:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    transform: translateX(5px);
}
</style>