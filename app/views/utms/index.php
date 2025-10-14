<!-- Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 style="color: white; margin: 0 0 10px 0;">🚀 UTMTrack - Rastreamento Automático</h1>
        <p style="color: #94a3b8; margin: 0;">
            Sistema completo de rastreamento de UTMs e eventos para Facebook
        </p>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <a href="index.php?page=utms&action=setup" class="btn" style="background: #10b981; color: white; text-decoration: none;">
            ➕ Adicionar Pixel
        </a>
        <a href="index.php?page=utms&action=scripts" class="btn btn-primary">
            📜 Ver Script
        </a>
    </div>
</div>

<!-- Gerenciamento de Pixels -->
<?php if (!empty($pixels)): ?>
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">🎯 Pixels Configurados (<?= count($pixels) ?>)</h2>
        <a href="index.php?page=utms&action=settings" style="color: #667eea; text-decoration: none; font-size: 14px;">
            ⚙️ Gerenciar Todos
        </a>
    </div>
    
    <div style="padding: 20px;">
        <div style="display: grid; gap: 15px;">
            <?php foreach ($pixels as $pixel): ?>
            <div class="pixel-card" style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px; transition: all 0.2s;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <h3 style="color: white; margin: 0; font-size: 18px;">
                                <?= htmlspecialchars($pixel['pixel_name']) ?>
                            </h3>
                            <?php if ($pixel['capi_enabled']): ?>
                            <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                ✓ CAPI ATIVO
                            </span>
                            <?php else: ?>
                            <span style="background: rgba(100, 116, 139, 0.2); color: #94a3b8; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                ⏸ PAUSADO
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div style="color: #94a3b8; font-size: 14px; margin-bottom: 4px;">
                            <strong>Pixel ID:</strong> <?= htmlspecialchars($pixel['pixel_id']) ?>
                        </div>
                        
                        <div style="color: #64748b; font-size: 13px;">
                            Criado em <?= date('d/m/Y', strtotime($pixel['created_at'])) ?>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 8px;">
                        <!-- Ver Script -->
                        <a href="index.php?page=utms&action=scripts#pixel_<?= $pixel['id'] ?>" 
                           class="btn" 
                           title="Ver Script de Instalação"
                           style="background: #667eea; color: white; padding: 8px 16px; font-size: 13px; text-decoration: none;">
                            📜 Script
                        </a>
                        
                        <!-- Editar -->
                        <button onclick="editPixel(<?= $pixel['id'] ?>, '<?= htmlspecialchars($pixel['pixel_name']) ?>', '<?= htmlspecialchars($pixel['pixel_id']) ?>')"
                                class="btn" 
                                title="Editar Pixel"
                                style="background: #334155; color: white; padding: 8px 16px; font-size: 13px;">
                            ✏️ Editar
                        </button>
                        
                        <!-- Excluir -->
                        <button onclick="confirmDeletePixel(<?= $pixel['id'] ?>, '<?= htmlspecialchars($pixel['pixel_name'], ENT_QUOTES) ?>')"
                                class="btn" 
                                title="Excluir Pixel"
                                style="background: #ef4444; color: white; padding: 8px 16px; font-size: 13px;">
                            🗑️ Excluir
                        </button>
                    </div>
                </div>
                
                <!-- Estatísticas Rápidas do Pixel -->
                <?php if (!empty($pixel['stats']) && $pixel['stats']['total'] > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; padding-top: 15px; border-top: 1px solid #334155;">
                    <div>
                        <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Eventos (7 dias)</div>
                        <div style="color: white; font-size: 20px; font-weight: 600;">
                            <?= number_format($pixel['stats']['total'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <div>
                        <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Enviados</div>
                        <div style="color: #10b981; font-size: 20px; font-weight: 600;">
                            <?= number_format($pixel['stats']['sent'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <div>
                        <div style="color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Taxa de Sucesso</div>
                        <div style="color: #667eea; font-size: 20px; font-weight: 600;">
                            <?= number_format(($pixel['stats']['sent'] / $pixel['stats']['total']) * 100, 1) ?>%
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="padding-top: 15px; border-top: 1px solid #334155; text-align: center; color: #64748b; font-size: 13px;">
                    Nenhum evento registrado nos últimos 7 dias
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Botão Adicionar Novo Pixel -->
        <div style="margin-top: 20px; text-align: center;">
            <a href="index.php?page=utms&action=setup" 
               class="btn" 
               style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px dashed #10b981; padding: 12px 24px; font-size: 14px; text-decoration: none;">
                ➕ Adicionar Novo Pixel
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Alerta se não tem eventos -->
<?php if ($stats['total_events'] == 0): ?>
<div class="card" style="margin-bottom: 30px; background: rgba(102, 126, 234, 0.1); border-color: #667eea;">
    <h3 style="color: #a5b4fc; margin: 0 0 15px 0;">👋 Bem-vindo ao UTMTrack!</h3>
    <p style="color: #94a3b8; margin: 0 0 15px 0; line-height: 1.6;">
        <?php if (empty($pixels)): ?>
            Configure seu primeiro pixel para começar a rastrear eventos!
        <?php else: ?>
            Seu pixel está configurado! Agora instale o script de rastreamento nas suas páginas.
        <?php endif; ?>
    </p>
    <div style="display: flex; gap: 10px;">
        <?php if (empty($pixels)): ?>
        <a href="index.php?page=utms&action=setup" class="btn btn-primary">
            🚀 Configurar Pixel
        </a>
        <?php else: ?>
        <a href="index.php?page=utms&action=scripts" class="btn btn-primary">
            📜 Ver Script de Instalação
        </a>
        <?php endif; ?>
        <a href="#como-funciona" class="btn" style="background: #334155; color: white; text-decoration: none;">
            💡 Como Funciona
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Estatísticas Principais -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
    <div class="metric-card">
        <div class="metric-label">📊 Total de Eventos</div>
        <div class="metric-value"><?= number_format($stats['total_events'] ?? 0, 0, ',', '.') ?></div>
        <div class="metric-info">Últimos 30 dias</div>
    </div>
    
    <div class="metric-card" style="border-color: #10b981;">
        <div class="metric-label">✅ Enviados com Sucesso</div>
        <div class="metric-value" style="color: #10b981;">
            <?= number_format($stats['events_sent'] ?? 0, 0, ',', '.') ?>
        </div>
        <div class="metric-info">
            <?php 
            $successRate = $stats['total_events'] > 0 
                ? ($stats['events_sent'] / $stats['total_events']) * 100 
                : 0;
            echo number_format($successRate, 1) . '% de sucesso';
            ?>
        </div>
    </div>
    
    <?php if ($stats['events_pending'] > 0): ?>
    <div class="metric-card" style="border-color: #f59e0b;">
        <div class="metric-label">⏳ Aguardando Envio</div>
        <div class="metric-value" style="color: #f59e0b;">
            <?= number_format($stats['events_pending'] ?? 0, 0, ',', '.') ?>
        </div>
        <div class="metric-info">Serão processados em breve</div>
    </div>
    <?php endif; ?>
    
    <?php if ($stats['events_failed'] > 0): ?>
    <div class="metric-card" style="border-color: #ef4444;">
        <div class="metric-label">❌ Falhas</div>
        <div class="metric-value" style="color: #ef4444;">
            <?= number_format($stats['events_failed'] ?? 0, 0, ',', '.') ?>
        </div>
        <div class="metric-info">
            <a href="index.php?page=utms&action=logs" style="color: #ef4444;">Ver detalhes</a>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="metric-card" style="border-color: #667eea;">
        <div class="metric-label">🎯 Pixels Ativos</div>
        <div class="metric-value" style="color: #667eea;">
            <?= count($pixels) ?>
        </div>
        <div class="metric-info">
            <?php 
            $activeCount = 0;
            foreach ($pixels as $p) {
                if ($p['capi_enabled']) $activeCount++;
            }
            echo $activeCount . ' com CAPI ativo';
            ?>
        </div>
    </div>
</div>

<!-- Gráfico de Eventos -->
<?php if (!empty($eventsByDay)): ?>
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2 class="card-title">📈 Eventos dos Últimos 7 Dias</h2>
    </div>
    
    <div style="padding: 20px;">
        <canvas id="eventsChart" style="max-height: 300px;"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('eventsChart').getContext('2d');
const eventData = <?= json_encode($eventsByDay) ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: eventData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
        }),
        datasets: [{
            label: 'Total de Eventos',
            data: eventData.map(d => d.total),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Eventos Enviados',
            data: eventData.map(d => d.sent),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: { color: '#e2e8f0', font: { size: 12 } }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#94a3b8', precision: 0 },
                grid: { color: '#334155' }
            },
            x: {
                ticks: { color: '#94a3b8' },
                grid: { color: '#334155' }
            }
        }
    }
});
</script>
<?php endif; ?>

<!-- Eventos por Tipo -->
<?php if (!empty($eventsByType)): ?>
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">🎯 Eventos Mais Enviados (Últimos 7 dias)</h2>
        <a href="index.php?page=utms&action=stats" style="color: #667eea; text-decoration: none; font-size: 14px;">
            Ver tudo →
        </a>
    </div>
    
    <div style="padding: 20px;">
        <?php foreach ($eventsByType as $event): 
            $percentage = $event['total'] > 0 ? ($event['sent'] / $event['total']) * 100 : 0;
        ?>
        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #e2e8f0; font-weight: 600;">
                    <?= htmlspecialchars($event['event_name']) ?>
                </span>
                <span style="color: #94a3b8; font-size: 14px;">
                    <?= number_format($event['total'], 0, ',', '.') ?> eventos
                    <span style="color: #10b981; margin-left: 10px;">
                        (<?= number_format($event['sent'], 0, ',', '.') ?> enviados)
                    </span>
                </span>
            </div>
            <div style="height: 8px; background: #334155; border-radius: 4px; overflow: hidden;">
                <div style="height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); width: <?= $percentage ?>%; transition: width 0.3s;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Como Funciona -->
<div class="card" id="como-funciona" style="background: rgba(102, 126, 234, 0.05); border-color: rgba(102, 126, 234, 0.3);">
    <h3 style="color: #a5b4fc; margin: 0 0 20px 0;">💡 Como Funciona</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div>
            <div style="font-size: 32px; margin-bottom: 10px;">1️⃣</div>
            <h4 style="color: white; margin: 0 0 8px 0;">Instale o Script</h4>
            <p style="color: #94a3b8; margin: 0; font-size: 14px; line-height: 1.6;">
                Copie e cole UM único script no &lt;head&gt; de todas as suas páginas. É só isso!
            </p>
        </div>
        
        <div>
            <div style="font-size: 32px; margin-bottom: 10px;">2️⃣</div>
            <h4 style="color: white; margin: 0 0 8px 0;">Rastreamento Automático</h4>
            <p style="color: #94a3b8; margin: 0; font-size: 14px; line-height: 1.6;">
                O sistema captura automaticamente: PageView, Lead, Checkout, Purchase e muito mais!
            </p>
        </div>
        
        <div>
            <div style="font-size: 32px; margin-bottom: 10px;">3️⃣</div>
            <h4 style="color: white; margin: 0 0 8px 0;">Envio para Facebook</h4>
            <p style="color: #94a3b8; margin: 0; font-size: 14px; line-height: 1.6;">
                Todos os eventos são enviados automaticamente para o Facebook CAPI. Sem bloqueios!
            </p>
        </div>
        
        <div>
            <div style="font-size: 32px; margin-bottom: 10px;">4️⃣</div>
            <h4 style="color: white; margin: 0 0 8px 0;">Otimização de Campanhas</h4>
            <p style="color: #94a3b8; margin: 0; font-size: 14px; line-height: 1.6;">
                Facebook recebe dados precisos e otimiza suas campanhas automaticamente. Mais conversões!
            </p>
        </div>
    </div>
    
    <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid rgba(102, 126, 234, 0.2);">
        <a href="index.php?page=utms&action=scripts" class="btn btn-primary">
            📜 Instalar Agora
        </a>
        <a href="https://developers.facebook.com/docs/marketing-api/conversions-api" target="_blank" 
           class="btn" style="background: #334155; color: white; text-decoration: none; margin-left: 10px;">
            📚 Documentação do Facebook
        </a>
    </div>
</div>

<!-- Ações Rápidas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
    <a href="index.php?page=utms&action=scripts" class="card" style="text-decoration: none; cursor: pointer; transition: transform 0.2s;" 
       onmouseover="this.style.transform='translateY(-4px)'" 
       onmouseout="this.style.transform='translateY(0)'">
        <div style="font-size: 40px; margin-bottom: 10px;">📜</div>
        <h4 style="color: white; margin: 0 0 8px 0;">Script de Instalação</h4>
        <p style="color: #94a3b8; margin: 0; font-size: 14px;">
            Copie o código para instalar no seu site
        </p>
    </a>
    
    <a href="index.php?page=utms&action=stats" class="card" style="text-decoration: none; cursor: pointer; transition: transform 0.2s;" 
       onmouseover="this.style.transform='translateY(-4px)'" 
       onmouseout="this.style.transform='translateY(0)'">
        <div style="font-size: 40px; margin-bottom: 10px;">📊</div>
        <h4 style="color: white; margin: 0 0 8px 0;">Estatísticas Detalhadas</h4>
        <p style="color: #94a3b8; margin: 0; font-size: 14px;">
            Análise completa dos eventos enviados
        </p>
    </a>
    
    <a href="index.php?page=utms&action=logs" class="card" style="text-decoration: none; cursor: pointer; transition: transform 0.2s;" 
       onmouseover="this.style.transform='translateY(-4px)'" 
       onmouseout="this.style.transform='translateY(0)'">
        <div style="font-size: 40px; margin-bottom: 10px;">📋</div>
        <h4 style="color: white; margin: 0 0 8px 0;">Logs do Sistema</h4>
        <p style="color: #94a3b8; margin: 0; font-size: 14px;">
            Histórico e debug de eventos
        </p>
    </a>
    
    <a href="index.php?page=utms&action=settings" class="card" style="text-decoration: none; cursor: pointer; transition: transform 0.2s;" 
       onmouseover="this.style.transform='translateY(-4px)'" 
       onmouseout="this.style.transform='translateY(0)'">
        <div style="font-size: 40px; margin-bottom: 10px;">⚙️</div>
        <h4 style="color: white; margin: 0 0 8px 0;">Configurações</h4>
        <p style="color: #94a3b8; margin: 0; font-size: 14px;">
            Gerencie pixels e eventos automáticos
        </p>
    </a>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; max-width: 500px; width: 90%; padding: 30px;">
        <h3 style="color: #ef4444; margin: 0 0 15px 0; font-size: 24px;">⚠️ Confirmar Exclusão</h3>
        
        <p style="color: #e2e8f0; margin: 0 0 10px 0; font-size: 16px;">
            Tem certeza que deseja excluir o pixel:
        </p>
        
        <p style="color: #667eea; margin: 0 0 20px 0; font-size: 18px; font-weight: 600;" id="deletePixelName">
        </p>
        
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 15px; margin-bottom: 25px;">
            <p style="color: #fca5a5; margin: 0; font-size: 14px; line-height: 1.6;">
                <strong>⚠️ ATENÇÃO:</strong> Esta ação é <strong>IRREVERSÍVEL</strong> e irá:
            </p>
            <ul style="color: #fca5a5; margin: 10px 0 0 0; padding-left: 20px; font-size: 13px;">
                <li>Remover todas as configurações do pixel</li>
                <li>Deletar o histórico de eventos CAPI</li>
                <li>Excluir todos os logs relacionados</li>
            </ul>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button onclick="closeDeleteModal()" class="btn" style="background: #334155; color: white; padding: 12px 24px;">
                ❌ Cancelar
            </button>
            <button onclick="executeDelete()" class="btn" style="background: #ef4444; color: white; padding: 12px 24px;">
                🗑️ Sim, Excluir Pixel
            </button>
        </div>
    </div>
</div>

<!-- Modal de Edição -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; max-width: 600px; width: 90%; padding: 30px;">
        <h3 style="color: white; margin: 0 0 20px 0; font-size: 24px;">✏️ Editar Pixel</h3>
        
        <form id="editForm" onsubmit="saveEdit(event)">
            <input type="hidden" id="editPixelId">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: white; font-weight: 600; margin-bottom: 8px;">
                    Nome do Pixel
                </label>
                <input type="text" 
                       id="editPixelName" 
                       required
                       style="width: 100%; padding: 12px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 14px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; color: white; font-weight: 600; margin-bottom: 8px;">
                    Pixel ID (apenas visualização)
                </label>
                <input type="text" 
                       id="editPixelIdDisplay" 
                       readonly
                       style="width: 100%; padding: 12px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #64748b; font-size: 14px; box-sizing: border-box; cursor: not-allowed;">
                <small style="color: #94a3b8; margin-top: 8px; display: block; font-size: 12px;">
                    O Pixel ID não pode ser alterado após a criação
                </small>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn" style="background: #334155; color: white; padding: 12px 24px;">
                    ❌ Cancelar
                </button>
                <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
                    💾 Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.metrics-grid {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.metric-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
    pointer-events: none;
}

.metric-label {
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 8px;
    font-weight: 500;
}

.metric-value {
    color: white;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
}

.metric-info {
    color: #64748b;
    font-size: 12px;
}

.pixel-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}
</style>

<script>
// Variável global para armazenar o ID do pixel a ser deletado
let pixelToDelete = null;

// Confirmar exclusão
function confirmDeletePixel(pixelId, pixelName) {
    pixelToDelete = pixelId;
    document.getElementById('deletePixelName').textContent = pixelName;
    document.getElementById('deleteModal').style.display = 'flex';
}

// Fechar modal de exclusão
function closeDeleteModal() {
    pixelToDelete = null;
    document.getElementById('deleteModal').style.display = 'none';
}

// Executar exclusão
async function executeDelete() {
    if (!pixelToDelete) return;
    
    const modal = document.getElementById('deleteModal');
    const btn = modal.querySelector('button[onclick="executeDelete()"]');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '⏳ Excluindo...';
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('pixel_id', pixelToDelete);
        
        const response = await fetch('index.php?page=utms&action=deletePixel', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('✅ Pixel excluído com sucesso!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            throw new Error(result.message || 'Erro ao excluir pixel');
        }
    } catch (error) {
        showNotification('❌ ' + error.message, 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Editar pixel
function editPixel(id, name, pixelId) {
    document.getElementById('editPixelId').value = id;
    document.getElementById('editPixelName').value = name;
    document.getElementById('editPixelIdDisplay').value = pixelId;
    document.getElementById('editModal').style.display = 'flex';
}

// Fechar modal de edição
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Salvar edição
async function saveEdit(e) {
    e.preventDefault();
    
    const pixelId = document.getElementById('editPixelId').value;
    const pixelName = document.getElementById('editPixelName').value;
    
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '⏳ Salvando...';
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('pixel_id', pixelId);
        formData.append('pixel_name', pixelName);
        
        const response = await fetch('index.php?page=utms&action=updatePixelName', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('✅ Pixel atualizado com sucesso!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            throw new Error(result.message || 'Erro ao atualizar pixel');
        }
    } catch (error) {
        showNotification('❌ ' + error.message, 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Fechar modais ao clicar fora
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// Fechar modais com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
        closeEditModal();
    }
});

// Notificações
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.textContent = message;
    
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        info: '#667eea'
    };
    
    notification.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #1e293b;
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        border-left: 4px solid ${colors[type]};
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Animações
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>