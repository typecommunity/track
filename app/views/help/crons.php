<?php
/**
 * UTMTrack - Página de Configuração de Cron Jobs
 * Arquivo: app/views/help/crons.php
 */

$user = $user ?? null;
$config = $config ?? [];
$basePath = $basePath ?? dirname($_SERVER['DOCUMENT_ROOT']) . '/public_html/utmtrack';
$phpPath = $phpPath ?? '/usr/bin/php';
$cronFiles = $cronFiles ?? [];

// Extrai o domínio da base_url para usar nos comandos
$baseUrl = $config['base_url'] ?? 'http://localhost/utmtrack/public';
$urlParts = parse_url($baseUrl);
$domain = $urlParts['host'] ?? 'localhost';
$path = isset($urlParts['path']) ? rtrim($urlParts['path'], '/public') : '';
$serverPath = '/home/' . $domain . '/public_html' . $path;
?>

<!-- Content usando toda a largura da página -->
<div style="padding: 40px; max-width: 100%; background: #0f172a; min-height: 100vh;">
    <!-- Header -->
    <div style="margin-bottom: 50px;">
        <h1 style="color: white; font-size: 42px; margin-bottom: 15px; font-weight: 800;">
            ⏰ Configuração de Cron Jobs
        </h1>
        <p style="color: #94a3b8; font-size: 18px;">
            Configure tarefas automatizadas para sincronização e automação do sistema
        </p>
    </div>

    <style>
        .cron-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            border: 1px solid #334155;
        }
        .cron-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #334155;
        }
        .cron-icon {
            font-size: 48px;
        }
        .cron-title {
            color: #e2e8f0;
            font-size: 26px;
            font-weight: 700;
            margin: 0;
        }
        .cron-subtitle {
            color: #94a3b8;
            font-size: 15px;
            margin: 8px 0 0 0;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-left: auto;
        }
        .status-success {
            background: #10b98120;
            color: #10b981;
        }
        .status-warning {
            background: #f59e0b20;
            color: #f59e0b;
        }
        .command-box {
            background: #0f172a;
            border: 2px solid #667eea;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            position: relative;
        }
        .command-label {
            color: #a5b4fc;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            display: block;
        }
        .command-text {
            color: #10b981;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .copy-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .copy-button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .info-box {
            background: #0f172a;
            border-left: 4px solid #3b82f6;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .warning-box {
            background: #0f172a;
            border-left: 4px solid #f59e0b;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .step-list {
            counter-reset: step-counter;
            list-style: none;
            padding: 0;
        }
        .step-list li {
            counter-increment: step-counter;
            margin-bottom: 30px;
            padding-left: 60px;
            position: relative;
            color: #cbd5e1;
            line-height: 1.8;
        }
        .step-list li:before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        .frequency-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        .frequency-table th,
        .frequency-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        .frequency-table th {
            background: #0f172a;
            color: #a5b4fc;
            font-weight: 600;
            font-size: 14px;
        }
        .frequency-table td {
            color: #cbd5e1;
            font-size: 14px;
        }
        .frequency-table td:first-child {
            font-family: 'Courier New', monospace;
            color: #10b981;
        }
    </style>

    <!-- CRON 1: Sincronização Meta Ads -->
    <div class="cron-card">
        <div class="cron-header">
            <div class="cron-icon">🔄</div>
            <div style="flex: 1;">
                <h2 class="cron-title">Sincronização de Campanhas Meta Ads</h2>
                <p class="cron-subtitle">Sincroniza campanhas, métricas e dados do Facebook/Instagram Ads</p>
            </div>
            <span class="status-badge <?= $cronFiles['sync_meta']['exists'] ? 'status-success' : 'status-warning' ?>">
                <?= $cronFiles['sync_meta']['exists'] ? '✓ Arquivo OK' : '⚠ Arquivo não encontrado' ?>
            </span>
        </div>

        <div class="info-box">
            <p style="margin: 0; color: #cbd5e1;">
                <strong style="color: #3b82f6;">📌 Para que serve:</strong><br>
                Atualiza automaticamente suas campanhas do Meta Ads (Facebook/Instagram), trazendo dados de gastos, impressões, cliques, conversões e ROAS.
            </p>
        </div>

        <div class="command-box">
            <span class="command-label">💻 Comando para o Cron Job:</span>
            <button class="copy-button" onclick="copyCommand('cmd1')">📋 Copiar</button>
            <div class="command-text" id="cmd1">0 * * * * <?= $phpPath ?> <?= $serverPath ?>/cron/sync_meta_campaigns.php >> <?= $serverPath ?>/logs/sync_meta.log 2>&1</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 25px;">
            <div style="background: #0f172a; padding: 20px; border-radius: 12px;">
                <div style="color: #a5b4fc; font-size: 13px; margin-bottom: 8px;">FREQUÊNCIA RECOMENDADA</div>
                <div style="color: #10b981; font-size: 24px; font-weight: 700;">A cada 1 hora</div>
            </div>
            <div style="background: #0f172a; padding: 20px; border-radius: 12px;">
                <div style="color: #a5b4fc; font-size: 13px; margin-bottom: 8px;">TEMPO DE EXECUÇÃO</div>
                <div style="color: #10b981; font-size: 24px; font-weight: 700;">~30-60 segundos</div>
            </div>
        </div>
    </div>

    <!-- CRON 2: Sincronização Completa -->
    <div class="cron-card">
        <div class="cron-header">
            <div class="cron-icon">🚀</div>
            <div style="flex: 1;">
                <h2 class="cron-title">Sincronização Completa Meta Ads</h2>
                <p class="cron-subtitle">Sincroniza Campanhas + Conjuntos (AdSets) + Anúncios (Ads)</p>
            </div>
            <span class="status-badge <?= $cronFiles['sync_complete']['exists'] ? 'status-success' : 'status-warning' ?>">
                <?= $cronFiles['sync_complete']['exists'] ? '✓ Arquivo OK' : '⚠ Arquivo não encontrado' ?>
            </span>
        </div>

        <div class="warning-box">
            <p style="margin: 0; color: #cbd5e1;">
                <strong style="color: #f59e0b;">⚠️ Importante:</strong><br>
                Este cron é OPCIONAL e mais pesado. Use apenas se precisar de dados detalhados de conjuntos e anúncios. Recomendado executar a cada 6 horas.
            </p>
        </div>

        <div class="command-box">
            <span class="command-label">💻 Comando para o Cron Job:</span>
            <button class="copy-button" onclick="copyCommand('cmd2')">📋 Copiar</button>
            <div class="command-text" id="cmd2">0 */6 * * * <?= $phpPath ?> <?= $serverPath ?>/cron/sync_meta_complete.php >> <?= $serverPath ?>/logs/sync_complete.log 2>&1</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 25px;">
            <div style="background: #0f172a; padding: 20px; border-radius: 12px;">
                <div style="color: #a5b4fc; font-size: 13px; margin-bottom: 8px;">FREQUÊNCIA RECOMENDADA</div>
                <div style="color: #10b981; font-size: 24px; font-weight: 700;">A cada 6 horas</div>
            </div>
            <div style="background: #0f172a; padding: 20px; border-radius: 12px;">
                <div style="color: #a5b4fc; font-size: 13px; margin-bottom: 8px;">TEMPO DE EXECUÇÃO</div>
                <div style="color: #10b981; font-size: 24px; font-weight: 700;">~2-5 minutos</div>
            </div>
        </div>
    </div>

    <!-- CRON 3: Regras Automatizadas -->
    <div class="cron-card">
        <div class="cron-header">
            <div class="cron-icon">🤖</div>
            <div style="flex: 1;">
                <h2 class="cron-title">Execução de Regras Automatizadas</h2>
                <p class="cron-subtitle">Executa ações automáticas baseadas em métricas das campanhas</p>
            </div>
            <span class="status-badge <?= $cronFiles['execute_rules']['exists'] ? 'status-success' : 'status-warning' ?>">
                <?= $cronFiles['execute_rules']['exists'] ? '✓ Arquivo OK' : '⚠ Arquivo não encontrado' ?>
            </span>
        </div>

        <div class="info-box">
            <p style="margin: 0; color: #cbd5e1;">
                <strong style="color: #3b82f6;">📌 Para que serve:</strong><br>
                Executa suas regras automatizadas (pausar campanhas com ROAS baixo, aumentar budget de vencedoras, etc). Essencial se você usa o módulo de Regras.
            </p>
        </div>

        <div class="command-box">
            <span class="command-label">💻 Comando para o Cron Job:</span>
            <button class="copy-button" onclick="copyCommand('cmd3')">📋 Copiar</button>
            <div class="command-text" id="cmd3">*/15 * * * * <?= $phpPath ?> <?= $serverPath ?>/cron/execute_rules.php >> <?= $serverPath ?>/logs/rules.log 2>&1</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 25px;">
            <div style="background: #0f172a; padding: 20px; border-radius: 12px;">
                <div style="color: #a5b4fc; font-size: 13px; margin-bottom: 8px;">FREQUÊNCIA RECOMENDADA</div>
                <div style="color: #10b981; font-size: 24px; font-weight: 700;">A cada 15 minutos</div>
            </div>
            <div style="background: #0f172a; padding: 20px; border-radius: 12px;">
                <div style="color: #a5b4fc; font-size: 13px; margin-bottom: 8px;">TEMPO DE EXECUÇÃO</div>
                <div style="color: #10b981; font-size: 24px; font-weight: 700;">~5-15 segundos</div>
            </div>
        </div>
    </div>

    <!-- Como Configurar -->
    <div class="cron-card">
        <div class="cron-header">
            <div class="cron-icon">📖</div>
            <div>
                <h2 class="cron-title">Como Configurar no CyberPanel</h2>
                <p class="cron-subtitle">Passo a passo completo</p>
            </div>
        </div>

        <ol class="step-list">
            <li>
                <strong>Acesse o CyberPanel</strong><br>
                Entre no painel de controle do seu servidor
            </li>
            <li>
                <strong>Vá em Cron Jobs</strong><br>
                Procure por "Cron Jobs" ou "Gerenciar Cron" no menu
            </li>
            <li>
                <strong>Clique em "Add Cron" ou "Adicionar Cron Job"</strong><br>
                Adicione cada um dos 3 cron jobs acima
            </li>
            <li>
                <strong>Copie e cole os comandos</strong><br>
                Use os botões "📋 Copiar" acima para copiar cada comando
            </li>
            <li>
                <strong>Aguarde e monitore</strong><br>
                Os logs ficam salvos em <?= $serverPath ?>/logs/
            </li>
        </ol>
    </div>

    <!-- Tabela de Frequências -->
    <div class="cron-card">
        <div class="cron-header">
            <div class="cron-icon">⏱️</div>
            <div>
                <h2 class="cron-title">Entendendo as Frequências</h2>
                <p class="cron-subtitle">Sintaxe do cron explicada</p>
            </div>
        </div>

        <table class="frequency-table">
            <thead>
                <tr>
                    <th>Sintaxe</th>
                    <th>Quando Executa</th>
                    <th>Uso Recomendado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>*/15 * * * *</td>
                    <td>A cada 15 minutos</td>
                    <td>Regras automatizadas (resposta rápida)</td>
                </tr>
                <tr>
                    <td>0 * * * *</td>
                    <td>A cada 1 hora (minuto 0)</td>
                    <td>Sincronização de campanhas</td>
                </tr>
                <tr>
                    <td>0 */6 * * *</td>
                    <td>A cada 6 horas</td>
                    <td>Sincronização completa (pesada)</td>
                </tr>
                <tr>
                    <td>0 0 * * *</td>
                    <td>Diariamente à meia-noite</td>
                    <td>Relatórios e limpeza</td>
                </tr>
            </tbody>
        </table>

        <div class="info-box">
            <p style="margin: 0; color: #cbd5e1;">
                <strong style="color: #3b82f6;">💡 Formato do Cron:</strong><br>
                <code style="background: #0f172a; padding: 6px 12px; border-radius: 6px; color: #10b981; font-size: 14px;">minuto hora dia mês dia-da-semana comando</code>
            </p>
        </div>
    </div>

    <!-- Monitoramento -->
    <div class="cron-card">
        <div class="cron-header">
            <div class="cron-icon">📊</div>
            <div>
                <h2 class="cron-title">Monitorar Logs</h2>
                <p class="cron-subtitle">Verificar se os crons estão funcionando</p>
            </div>
        </div>

        <div class="command-box">
            <span class="command-label">Ver últimos logs de sincronização:</span>
            <button class="copy-button" onclick="copyCommand('log1')">📋 Copiar</button>
            <div class="command-text" id="log1">tail -50 <?= $serverPath ?>/logs/sync_meta.log</div>
        </div>

        <div class="command-box">
            <span class="command-label">Ver logs de regras:</span>
            <button class="copy-button" onclick="copyCommand('log2')">📋 Copiar</button>
            <div class="command-text" id="log2">tail -50 <?= $serverPath ?>/logs/rules.log</div>
        </div>

        <div class="command-box">
            <span class="command-label">Ver logs em tempo real:</span>
            <button class="copy-button" onclick="copyCommand('log3')">📋 Copiar</button>
            <div class="command-text" id="log3">tail -f <?= $serverPath ?>/logs/sync_meta.log</div>
        </div>
    </div>
</div>

<script>
function copyCommand(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        // Feedback visual
        const button = element.parentElement.querySelector('.copy-button');
        const originalText = button.textContent;
        button.textContent = '✓ Copiado!';
        button.style.background = '#10b981';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '#667eea';
        }, 2000);
    });
}
</script>