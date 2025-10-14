/**
 * ========================================
 * CAMINHO: /utmtrack/assets/js/campaigns-dashboard.js
 * ========================================
 * 
 * UTMTrack - JavaScript Dashboard COMPLETO
 * Versão 14.1 FINAL - EXPANDIDA E DETALHADA
 * 
 * FUNCIONALIDADES:
 * ✅ Sync META ADS completo (Nome, Status, Orçamento)
 * ✅ Filtros por período funcionais com auto-sync
 * ✅ Ordem de colunas com drag & drop + memória persistente
 * ✅ Resize de colunas com memória
 * ✅ Filtros de busca e status
 * ✅ Edição inline de campos
 * ✅ Toast notifications
 * ✅ Debug info detalhado
 * ✅ Modal de personalização de colunas
 * ✅ LocalStorage para preferências do usuário
 */

// ========================================
// VARIÁVEIS GLOBAIS
// ========================================

// Período selecionado
let currentPeriod = 'maximum';
let currentDatePreset = 'maximum';
let customDateRange = { 
    start: null, 
    end: null 
};

// Configuração completa de todas as colunas disponíveis
const allColumns = {
    'nome': { 
        label: 'Nome da Campanha', 
        default: true, 
        minWidth: 250,
        sortable: true,
        editable: true
    },
    'status': { 
        label: 'Status', 
        default: true, 
        minWidth: 100,
        sortable: true,
        editable: true
    },
    'orcamento': { 
        label: 'Orçamento', 
        default: true, 
        minWidth: 130,
        sortable: true,
        editable: true
    },
    'vendas': { 
        label: 'Vendas', 
        default: true, 
        minWidth: 100,
        sortable: true,
        editable: false
    },
    'cpa': { 
        label: 'CPA', 
        default: true, 
        minWidth: 120,
        sortable: true,
        editable: false
    },
    'gastos': { 
        label: 'Gastos', 
        default: true, 
        minWidth: 130,
        sortable: true,
        editable: false
    },
    'faturamento': { 
        label: 'Faturamento', 
        default: true, 
        minWidth: 140,
        sortable: true,
        editable: false
    },
    'lucro': { 
        label: 'Lucro', 
        default: true, 
        minWidth: 130,
        sortable: true,
        editable: false
    },
    'roas': { 
        label: 'ROAS', 
        default: true, 
        minWidth: 100,
        sortable: true,
        editable: false
    },
    'margem': { 
        label: 'Margem (%)', 
        default: true, 
        minWidth: 120,
        sortable: true,
        editable: false
    },
    'roi': { 
        label: 'ROI', 
        default: true, 
        minWidth: 100,
        sortable: true,
        editable: false
    },
    'ic': { 
        label: 'IC', 
        default: false, 
        minWidth: 100,
        sortable: true,
        editable: false
    },
    'cpi': { 
        label: 'CPI', 
        default: false, 
        minWidth: 120,
        sortable: true,
        editable: false
    },
    'cpc': { 
        label: 'CPC', 
        default: false, 
        minWidth: 120,
        sortable: true,
        editable: false
    },
    'ctr': { 
        label: 'CTR', 
        default: false, 
        minWidth: 100,
        sortable: true,
        editable: false
    },
    'cpm': { 
        label: 'CPM', 
        default: false, 
        minWidth: 120,
        sortable: true,
        editable: false
    },
    'impressoes': { 
        label: 'Impressões', 
        default: false, 
        minWidth: 130,
        sortable: true,
        editable: false
    },
    'cliques': { 
        label: 'Cliques', 
        default: false, 
        minWidth: 110,
        sortable: true,
        editable: false
    },
    'conversoes': { 
        label: 'Conversões', 
        default: false, 
        minWidth: 130,
        sortable: true,
        editable: false
    },
    'conta': { 
        label: 'Conta', 
        default: false, 
        minWidth: 150,
        sortable: true,
        editable: false
    },
    'ultima_sync': { 
        label: 'Última Sync', 
        default: false, 
        minWidth: 150,
        sortable: true,
        editable: false
    }
};

// Lista de colunas selecionadas (carregada do config ou default)
let selectedColumnsList = window.userColumnsConfig || 
    Object.keys(allColumns).filter(key => allColumns[key].default);

// Larguras customizadas das colunas
let columnWidths = {};

// Variáveis para drag & drop
let draggedColumn = null;
let dragStartIndex = -1;

// Variáveis para resize de colunas
let resizingColumn = null;
let startX = 0;
let startWidth = 0;

// ========================================
// TOAST NOTIFICATIONS
// ========================================

/**
 * Mostra uma notificação toast
 * @param {string} message - Mensagem a ser exibida
 * @param {string} type - Tipo: success, error, warning, info
 */
function showToast(message, type = 'success') {
    // Remove toasts anteriores para evitar acúmulo
    document.querySelectorAll('.toast').forEach(t => t.remove());
    
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    
    // Auto-remove após 3 segundos
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ========================================
// 🔥 INLINE EDITING - COM SYNC META ADS
// ========================================

/**
 * Torna o nome da campanha editável
 * @param {HTMLElement} element - Elemento clicado
 * @param {number} campaignId - ID da campanha (banco local)
 */
function makeEditableName(element, campaignId) {
    // Previne múltiplas edições simultâneas
    if (element.classList.contains('editing')) {
        return;
    }
    
    // Busca o ID da campanha no Meta (necessário para sync)
    const row = element.closest('tr');
    const metaCampaignId = row ? row.getAttribute('data-campaign-id') : '';
    
    const currentValue = element.getAttribute('data-value');
    
    // Marca como em edição
    element.classList.add('editing');
    
    // Substitui por input
    element.innerHTML = `<input 
        type="text" 
        value="${currentValue}" 
        onblur="saveField(this, ${campaignId}, 'campaign_name', 'text', '${metaCampaignId}')"
        onkeypress="if(event.key==='Enter') this.blur()"
        placeholder="Digite o nome da campanha..."
    >`;
    
    // Foco automático e seleção do texto
    const input = element.querySelector('input');
    input.focus();
    input.select();
}

/**
 * Torna um campo genérico editável
 * @param {HTMLElement} element - Elemento clicado
 * @param {number} campaignId - ID da campanha (banco local)
 * @param {string} field - Nome do campo (budget, status, etc)
 * @param {string} type - Tipo do input (text, currency, number)
 * @param {string} metaCampaignId - ID da campanha no Meta Ads
 */
function makeEditable(element, campaignId, field, type = 'text', metaCampaignId = '') {
    if (element.classList.contains('editing')) {
        return;
    }
    
    const currentValue = element.getAttribute('data-value');
    element.classList.add('editing');
    
    // Define o tipo de input baseado no tipo do campo
    const inputType = type === 'currency' ? 'number' : 'text';
    const stepAttr = type === 'currency' ? 'step="0.01"' : '';
    const minAttr = type === 'currency' ? 'min="0"' : '';
    
    element.innerHTML = `<input 
        type="${inputType}" 
        value="${currentValue}" 
        ${stepAttr}
        ${minAttr}
        onblur="saveField(this, ${campaignId}, '${field}', '${type}', '${metaCampaignId}')"
        onkeypress="if(event.key==='Enter') this.blur()"
        placeholder="Digite o valor..."
    >`;
    
    const input = element.querySelector('input');
    input.focus();
    input.select();
}

/**
 * Salva o campo editado (local + Meta Ads)
 * @param {HTMLInputElement} input - Input com o valor
 * @param {number} campaignId - ID da campanha (banco local)
 * @param {string} field - Nome do campo
 * @param {string} type - Tipo do campo
 * @param {string} metaCampaignId - ID da campanha no Meta
 */
async function saveField(input, campaignId, field, type, metaCampaignId = '') {
    const newValue = input.value.trim();
    const parent = input.parentElement;
    
    // Validação básica
    if (!newValue) {
        parent.classList.remove('editing');
        parent.innerHTML = parent.getAttribute('data-value');
        showToast('❌ Valor não pode estar vazio', 'error');
        return;
    }
    
    // Validação numérica para currency
    if (type === 'currency' && (isNaN(newValue) || parseFloat(newValue) < 0)) {
        parent.classList.remove('editing');
        parent.innerHTML = parent.getAttribute('data-value');
        showToast('❌ Valor inválido. Digite um número positivo', 'error');
        return;
    }
    
    // Mostra indicador de salvamento
    parent.innerHTML = '<span class="saving-indicator">💾 Salvando...</span>';
    
    try {
        let action, requestBody;
        
        // Define a ação baseada no campo
        if (field === 'campaign_name') {
            // ATUALIZAR NOME - SYNC COM META ADS
            action = 'update_field';
            requestBody = {
                campaign_id: campaignId,
                field: 'campaign_name',
                value: newValue
            };
        } 
        else if (field === 'budget') {
            // ATUALIZAR ORÇAMENTO - SYNC COM META ADS
            action = 'update_budget';
            requestBody = {
                campaign_id: campaignId,
                meta_campaign_id: metaCampaignId,
                value: parseFloat(newValue)
            };
            
            console.log('📤 Enviando orçamento:', requestBody);
        }
        else {
            // CAMPO GENÉRICO
            action = 'update_field';
            requestBody = {
                campaign_id: campaignId,
                field: field,
                value: newValue
            };
        }
        
        // Faz a requisição para o backend
        const response = await fetch(
            `${window.location.origin}/utmtrack/ajax_sync.php?action=${action}`, 
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody),
                credentials: 'same-origin'
            }
        );
        
        const result = await response.json();
        
        console.log('📥 Resposta do servidor:', result);
        
        if (result.success) {
            // Atualiza o valor no DOM
            parent.classList.remove('editing');
            parent.setAttribute('data-value', newValue);
            
            // Formata o valor de acordo com o tipo
            if (type === 'currency') {
                parent.innerHTML = 'R$ ' + parseFloat(newValue).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } else {
                parent.innerHTML = newValue;
            }
            
            // Mostra informações de debug se disponível
            if (result.debug_info) {
                console.log('🔍 Debug Info:', result.debug_info);
                
                // Se o Meta não atualizou, loga o erro
                if (!result.meta_updated && result.debug_info.meta_error) {
                    console.warn('⚠️ Meta Ads não atualizado:', result.debug_info.meta_error);
                }
            }
            
            // Mostra mensagem apropriada
            if (result.meta_updated) {
                showToast(result.message, 'success');
            } else {
                // Aviso se não sincronizou com o Meta
                showToast(result.message, 'warning');
            }
            
            // Log para debug mode
            if (window.debugMode) {
                console.log('✅ Campo atualizado:', {
                    field,
                    value: newValue,
                    meta_updated: result.meta_updated,
                    debug: result.debug_info
                });
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        // Restaura o valor original em caso de erro
        parent.classList.remove('editing');
        parent.innerHTML = parent.getAttribute('data-value');
        
        showToast('❌ ' + error.message, 'error');
        console.error('❌ Erro ao salvar:', error);
    }
}

// ========================================
// TOGGLE STATUS
// ========================================

/**
 * Alterna o status da campanha (ativo/pausado)
 * @param {HTMLInputElement} checkbox - Checkbox do toggle
 * @param {number} campaignId - ID da campanha (banco local)
 * @param {string} metaCampaignId - ID da campanha no Meta
 */
async function toggleCampaignStatus(checkbox, campaignId, metaCampaignId) {
    // Status para o Meta (maiúsculo)
    const newStatus = checkbox.checked ? 'ACTIVE' : 'PAUSED';
    // Status para o banco local (minúsculo)
    const newStatusLower = checkbox.checked ? 'active' : 'paused';
    // Backup para rollback em caso de erro
    const originalChecked = !checkbox.checked;
    
    // Desabilita durante o processo
    checkbox.disabled = true;
    
    try {
        const response = await fetch(
            window.location.origin + '/utmtrack/ajax_sync.php?action=update_status', 
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    campaign_id: campaignId,
                    meta_campaign_id: metaCampaignId,
                    status: newStatus
                }),
                credentials: 'same-origin'
            }
        );
        
        const result = await response.json();
        
        if (result.success) {
            // Atualiza o atributo data-status da linha
            const row = checkbox.closest('tr');
            if (row) {
                row.setAttribute('data-status', newStatusLower);
            }
            
            // Feedback diferenciado
            showToast(
                result.meta_updated ? 
                '✅ Status atualizado no Meta Ads!' : 
                '✅ Status atualizado localmente', 
                result.meta_updated ? 'success' : 'warning'
            );
            
            console.log('✅ Status atualizado:', {
                campaign_id: campaignId,
                new_status: newStatus,
                meta_updated: result.meta_updated
            });
        } else {
            // Rollback em caso de erro
            checkbox.checked = originalChecked;
            throw new Error(result.message);
        }
    } catch (error) {
        // Rollback em caso de erro
        checkbox.checked = originalChecked;
        showToast('❌ ' + error.message, 'error');
        console.error('❌ Erro ao atualizar status:', error);
    } finally {
        // Re-habilita o checkbox
        checkbox.disabled = false;
    }
}

// ========================================
// 🔥 PERÍODO - COM AUTO SYNC
// ========================================

/**
 * Muda o período e sincroniza automaticamente
 * @param {string} period - Período selecionado
 * @param {HTMLElement} button - Botão clicado
 */
function changePeriod(period, button) {
    // Remove active de todos os botões
    document.querySelectorAll('.period-tab').forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');
    
    // Atualiza variáveis globais
    currentPeriod = period;
    currentDatePreset = period;
    
    if (period !== 'custom') {
        // Esconde o seletor de período customizado
        document.getElementById('customDateRange').style.display = 'none';
        
        // Feedback ao usuário
        showToast('🔄 Sincronizando com período: ' + getPeriodLabel(period), 'info');
        
        // Aguarda um pouco para o toast ser visível
        setTimeout(() => {
            syncAllCampaigns();
        }, 500);
    }
}

/**
 * Retorna o label amigável do período
 * @param {string} period - Código do período
 * @returns {string} Label formatado
 */
function getPeriodLabel(period) {
    const labels = {
        'today': 'Hoje',
        'yesterday': 'Ontem',
        'last_7d': 'Últimos 7 dias',
        'last_30d': 'Últimos 30 dias',
        'this_month': 'Este mês',
        'last_month': 'Mês passado',
        'maximum': 'Máximo'
    };
    return labels[period] || period;
}

/**
 * Mostra/esconde o seletor de período customizado
 * @param {HTMLElement} button - Botão clicado
 */
function toggleCustomPeriod(button) {
    const customRange = document.getElementById('customDateRange');
    
    if (customRange.style.display === 'none' || !customRange.style.display) {
        customRange.style.display = 'flex';
        document.querySelectorAll('.period-tab').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        currentPeriod = 'custom';
    } else {
        customRange.style.display = 'none';
        button.classList.remove('active');
    }
}

/**
 * Aplica o período customizado e sincroniza
 */
function applyCustomPeriod() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    
    // Validações
    if (!start || !end) {
        showToast('⚠ Selecione as datas de início e fim', 'warning');
        return;
    }
    
    if (new Date(start) > new Date(end)) {
        showToast('⚠ Data inicial deve ser menor que a final', 'warning');
        return;
    }
    
    // Atualiza variáveis
    customDateRange = { start, end };
    currentPeriod = 'custom';
    
    // Feedback e sincronização
    showToast('🔄 Sincronizando período personalizado...', 'info');
    setTimeout(() => {
        syncAllCampaigns();
    }, 500);
}

// ========================================
// SINCRONIZAÇÃO COM META ADS
// ========================================

/**
 * Sincroniza todas as campanhas com o Meta Ads
 */
async function syncAllCampaigns() {
    const btn = event?.target;
    const originalHTML = btn ? btn.innerHTML : null;
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<svg class="icon spinning" style="animation: spin 1s linear infinite;" viewBox="0 0 24 24"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83" stroke="currentColor" stroke-width="2" fill="none"></path></svg> Sincronizando...';
    }
    
    try {
        const requestBody = {
            period: currentPeriod,
            date_preset: currentDatePreset
        };
        
        // Adiciona datas customizadas se aplicável
        if (currentPeriod === 'custom' && customDateRange.start && customDateRange.end) {
            requestBody.start_date = customDateRange.start;
            requestBody.end_date = customDateRange.end;
        }
        
        console.log('🔄 Iniciando sincronização:', requestBody);
        
        const response = await fetch(
            window.location.origin + '/utmtrack/ajax_sync.php?action=sync_all',
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody),
                credentials: 'same-origin'
            }
        );
        
        const result = await response.json();
        
        console.log('✅ Sincronização concluída:', result);
        
        if (result.success) {
            showToast(result.message, 'success');
            // Recarrega a página após 1.5s
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showToast('❌ ' + error.message, 'error');
        console.error('❌ Erro na sincronização:', error);
    } finally {
        if (btn && originalHTML) {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    }
}

// ========================================
// FILTROS DE BUSCA E STATUS
// ========================================

/**
 * Filtra a tabela por nome e status
 */
function filterTable() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    
    if (!searchInput || !statusFilter) return;
    
    const search = searchInput.value.toLowerCase().trim();
    const status = statusFilter.value;
    
    const rows = document.querySelectorAll('#tableBody tr[data-name]');
    
    let visibleCount = 0;
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        
        const matchName = !search || name.includes(search);
        const matchStatus = !status || rowStatus === status;
        
        if (matchName && matchStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    console.log(`🔍 Filtros aplicados: ${visibleCount} campanhas visíveis`);
}

/**
 * Filtra as colunas disponíveis no modal
 */
function filterColumns() {
    const search = document.getElementById('columnSearch').value.toLowerCase();
    document.querySelectorAll('.column-option').forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(search) ? 'flex' : 'none';
    });
}

// ========================================
// 🔥 MODAL DE COLUNAS - COM DRAG & DROP
// ========================================

/**
 * Abre o modal de personalização de colunas
 */
function openColumnsModal() {
    document.getElementById('columnsModal').classList.add('active');
    renderColumnsModal();
}

/**
 * Fecha o modal de colunas
 */
function closeColumnsModal() {
    document.getElementById('columnsModal').classList.remove('active');
}

/**
 * Renderiza o conteúdo do modal de colunas
 */
function renderColumnsModal() {
    const available = document.getElementById('availableColumns');
    const selected = document.getElementById('selectedColumns');
    
    available.innerHTML = '';
    selected.innerHTML = '';
    
    // Renderiza colunas disponíveis (checkboxes)
    Object.keys(allColumns).forEach(key => {
        const div = document.createElement('div');
        div.className = 'column-option';
        div.innerHTML = `
            <input 
                type="checkbox" 
                class="column-checkbox" 
                id="col_${key}"
                ${selectedColumnsList.includes(key) ? 'checked' : ''}
                onchange="toggleColumn('${key}')"
            >
            <label for="col_${key}">${allColumns[key].label}</label>
        `;
        available.appendChild(div);
    });
    
    // Renderiza colunas selecionadas (drag & drop)
    selectedColumnsList.forEach((key, index) => {
        const div = document.createElement('div');
        div.className = 'selected-column';
        div.setAttribute('draggable', 'true');
        div.setAttribute('data-column', key);
        div.setAttribute('data-index', index);
        div.innerHTML = `
            <span class="drag-handle" title="Arraste para reordenar">⋮⋮</span>
            <span>${allColumns[key].label}</span>
            <button onclick="removeColumn('${key}')" class="remove-btn" title="Remover">×</button>
        `;
        
        // Event listeners para drag & drop
        div.addEventListener('dragstart', handleDragStart);
        div.addEventListener('dragover', handleDragOver);
        div.addEventListener('drop', handleDrop);
        div.addEventListener('dragend', handleDragEnd);
        div.addEventListener('dragenter', handleDragEnter);
        div.addEventListener('dragleave', handleDragLeave);
        
        selected.appendChild(div);
    });
}

/**
 * Adiciona/remove coluna da seleção
 * @param {string} key - Chave da coluna
 */
function toggleColumn(key) {
    const index = selectedColumnsList.indexOf(key);
    if (index > -1) {
        selectedColumnsList.splice(index, 1);
    } else {
        selectedColumnsList.push(key);
    }
    renderColumnsModal();
}

/**
 * Remove coluna da seleção
 * @param {string} key - Chave da coluna
 */
function removeColumn(key) {
    const index = selectedColumnsList.indexOf(key);
    if (index > -1) {
        selectedColumnsList.splice(index, 1);
    }
    renderColumnsModal();
}

// ========================================
// 🔥 DRAG & DROP DE COLUNAS
// ========================================

/**
 * Inicia o drag
 */
function handleDragStart(e) {
    draggedColumn = this;
    dragStartIndex = parseInt(this.getAttribute('data-index'));
    this.style.opacity = '0.4';
    e.dataTransfer.effectAllowed = 'move';
    this.classList.add('dragging');
    
    console.log('🎯 Drag iniciado:', this.getAttribute('data-column'));
}

/**
 * Permite o drop
 */
function handleDragOver(e) {
    if (e.preventDefault) e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    return false;
}

/**
 * Visual feedback ao entrar na área de drop
 */
function handleDragEnter(e) {
    const targetColumn = e.target.closest('.selected-column');
    if (targetColumn && targetColumn !== draggedColumn) {
        targetColumn.classList.add('drag-over');
    }
}

/**
 * Remove visual feedback ao sair da área de drop
 */
function handleDragLeave(e) {
    const targetColumn = e.target.closest('.selected-column');
    if (targetColumn) {
        targetColumn.classList.remove('drag-over');
    }
}

/**
 * Executa o drop (reordena as colunas)
 */
function handleDrop(e) {
    if (e.stopPropagation) e.stopPropagation();
    
    const targetColumn = e.target.closest('.selected-column');
    
    if (targetColumn && draggedColumn && draggedColumn !== targetColumn) {
        const draggedKey = draggedColumn.getAttribute('data-column');
        const targetKey = targetColumn.getAttribute('data-column');
        
        const draggedIndex = selectedColumnsList.indexOf(draggedKey);
        const targetIndex = selectedColumnsList.indexOf(targetKey);
        
        // Reordena o array
        selectedColumnsList.splice(draggedIndex, 1);
        selectedColumnsList.splice(targetIndex, 0, draggedKey);
        
        // Salva a nova ordem
        saveColumnsOrder();
        
        // Re-renderiza
        renderColumnsModal();
        
        console.log('✅ Nova ordem das colunas:', selectedColumnsList);
    }
    
    // Remove feedback visual de todos
    document.querySelectorAll('.selected-column').forEach(col => {
        col.classList.remove('drag-over');
    });
    
    return false;
}

/**
 * Finaliza o drag
 */
function handleDragEnd(e) {
    this.style.opacity = '1';
    this.classList.remove('dragging');
    
    // Remove feedback visual de todos
    document.querySelectorAll('.selected-column').forEach(col => {
        col.classList.remove('drag-over');
    });
    
    console.log('🎯 Drag finalizado');
}

/**
 * Salva a configuração de colunas no servidor
 */
async function saveColumns() {
    try {
        // Salva no localStorage primeiro
        saveColumnsOrder();
        
        // Depois salva no servidor
        const response = await fetch(
            window.location.origin + '/utmtrack/index.php?ajax_action=save_columns', 
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ columns: selectedColumnsList }),
                credentials: 'same-origin'
            }
        );
        
        const result = await response.json();
        
        if (result.success) {
            closeColumnsModal();
            showToast('✅ ' + result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + result.message, 'error');
        }
    } catch (error) {
        showToast('❌ Erro: ' + error.message, 'error');
        console.error('❌ Erro ao salvar colunas:', error);
    }
}

// ========================================
// RENDERIZAÇÃO DA TABELA
// ========================================

/**
 * Renderiza a tabela com as colunas na ordem correta
 */
function renderTable() {
    const header = document.getElementById('tableHeader');
    const tbody = document.getElementById('tableBody');
    
    if (!header || !tbody) {
        console.warn('⚠️ Elementos de tabela não encontrados');
        return;
    }
    
    // Limpa o cabeçalho
    header.innerHTML = '';
    
    // Renderiza cabeçalho NA ORDEM CORRETA
    selectedColumnsList.forEach(col => {
        if (!allColumns[col]) {
            console.warn(`⚠️ Coluna '${col}' não encontrada na configuração`);
            return;
        }
        
        const th = document.createElement('th');
        th.setAttribute('data-column', col);
        th.style.width = (columnWidths[col] || allColumns[col].minWidth) + 'px';
        th.innerHTML = `
            <div class="th-content">
                <span class="th-drag-icon" title="Arraste para reordenar">⋮⋮</span>
                <span>${allColumns[col].label}</span>
            </div>
            <div class="resize-handle" title="Arraste para redimensionar"></div>
        `;
        header.appendChild(th);
    });
    
    // Reordena e mostra/esconde células
    tbody.querySelectorAll('tr').forEach(row => {
        const cells = Array.from(row.querySelectorAll('td[data-column]'));
        
        // Reordena as células baseado em selectedColumnsList
        selectedColumnsList.forEach(col => {
            const cell = cells.find(c => c.getAttribute('data-column') === col);
            if (cell) {
                row.appendChild(cell); // Move para o final na ordem correta
                cell.style.width = (columnWidths[col] || allColumns[col].minWidth) + 'px';
                cell.style.display = '';
            }
        });
        
        // Esconde colunas não selecionadas
        cells.forEach(cell => {
            const colName = cell.getAttribute('data-column');
            if (!selectedColumnsList.includes(colName)) {
                cell.style.display = 'none';
            }
        });
    });
    
    // Inicializa o resize de colunas
    initColumnResize();
    
    console.log('✅ Tabela renderizada com', selectedColumnsList.length, 'colunas');
}

// ========================================
// RESIZE DE COLUNAS
// ========================================

/**
 * Inicializa o sistema de resize de colunas
 */
function initColumnResize() {
    document.querySelectorAll('.resize-handle').forEach(handle => {
        handle.addEventListener('mousedown', onResizeStart);
    });
}

/**
 * Inicia o resize
 */
function onResizeStart(e) {
    resizingColumn = e.target.parentElement;
    startX = e.clientX;
    startWidth = resizingColumn.offsetWidth;
    
    e.target.classList.add('resizing');
    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
    
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
    
    console.log('📏 Resize iniciado:', resizingColumn.getAttribute('data-column'));
}

/**
 * Processa o movimento durante o resize
 */
function onMouseMove(e) {
    if (!resizingColumn) return;
    
    const diff = e.clientX - startX;
    const col = resizingColumn.getAttribute('data-column');
    const newWidth = Math.max(allColumns[col].minWidth, startWidth + diff);
    
    resizingColumn.style.width = newWidth + 'px';
    
    // Atualiza células correspondentes
    document.querySelectorAll(`td[data-column="${col}"]`).forEach(cell => {
        cell.style.width = newWidth + 'px';
    });
}

/**
 * Finaliza o resize
 */
function onMouseUp(e) {
    if (!resizingColumn) return;
    
    const resizeHandle = resizingColumn.querySelector('.resize-handle');
    if (resizeHandle) resizeHandle.classList.remove('resizing');
    
    const col = resizingColumn.getAttribute('data-column');
    columnWidths[col] = resizingColumn.offsetWidth;
    saveColumnWidths();
    
    console.log('📏 Resize finalizado:', col, '→', columnWidths[col] + 'px');
    
    resizingColumn = null;
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
    
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
}

// ========================================
// 🔥 PERSISTÊNCIA - LOCALSTORAGE
// ========================================

/**
 * Salva as larguras das colunas no localStorage
 */
function saveColumnWidths() {
    localStorage.setItem('utmtrack_campaignColumnWidths', JSON.stringify(columnWidths));
    console.log('💾 Larguras das colunas salvas:', columnWidths);
}

/**
 * Carrega as larguras das colunas do localStorage
 */
function loadColumnWidths() {
    const saved = localStorage.getItem('utmtrack_campaignColumnWidths');
    if (saved) {
        try {
            columnWidths = JSON.parse(saved);
            console.log('📂 Larguras das colunas carregadas:', columnWidths);
        } catch (e) {
            console.error('❌ Erro ao carregar larguras:', e);
            columnWidths = {};
        }
    }
}

/**
 * Salva a ordem das colunas no localStorage
 */
function saveColumnsOrder() {
    localStorage.setItem('utmtrack_campaignColumnsOrder', JSON.stringify(selectedColumnsList));
    console.log('💾 Ordem das colunas salva:', selectedColumnsList);
}

/**
 * Carrega a ordem das colunas do localStorage
 */
function loadColumnsOrder() {
    const saved = localStorage.getItem('utmtrack_campaignColumnsOrder');
    if (saved) {
        try {
            selectedColumnsList = JSON.parse(saved);
            console.log('📂 Ordem das colunas carregada:', selectedColumnsList);
        } catch (e) {
            console.error('❌ Erro ao carregar ordem:', e);
            // Mantém o padrão
        }
    }
}

// ========================================
// INICIALIZAÇÃO
// ========================================

/**
 * Inicializa o dashboard quando o DOM estiver pronto
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Dashboard UTMTrack v14.1 carregado');
    console.log('📊 Total campanhas:', document.querySelectorAll('#tableBody tr[data-id]').length);
    console.log('🐛 Debug mode:', window.debugMode ? 'ATIVADO' : 'desativado');
    
    // Carrega configurações salvas
    loadColumnWidths();
    loadColumnsOrder();
    
    // Renderiza tabela com a ordem correta
    renderTable();
    
    // Define período padrão
    const maxBtn = document.querySelector('[data-period="maximum"]');
    if (maxBtn) {
        maxBtn.classList.add('active');
        currentPeriod = 'maximum';
        currentDatePreset = 'maximum';
    }
    
    console.log('✅ Dashboard inicializado com sucesso');
    console.log('📋 Colunas ativas:', selectedColumnsList);
});

// ========================================
// EXPORTA FUNÇÕES GLOBAIS (WINDOW SCOPE)
// ========================================

window.toggleCampaignStatus = toggleCampaignStatus;
window.makeEditableName = makeEditableName;
window.makeEditable = makeEditable;
window.saveField = saveField;
window.changePeriod = changePeriod;
window.toggleCustomPeriod = toggleCustomPeriod;
window.applyCustomPeriod = applyCustomPeriod;
window.syncAllCampaigns = syncAllCampaigns;
window.filterTable = filterTable;
window.filterColumns = filterColumns;
window.openColumnsModal = openColumnsModal;
window.closeColumnsModal = closeColumnsModal;
window.toggleColumn = toggleColumn;
window.removeColumn = removeColumn;
window.saveColumns = saveColumns;

// ========================================
// CSS DINÂMICO PARA ANIMAÇÕES
// ========================================

const style = document.createElement('style');
style.textContent = `
/* Animação de rotação para o ícone de sincronização */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Estado de drag das colunas */
.selected-column.dragging {
    opacity: 0.4;
    transform: scale(0.95);
    transition: transform 0.2s;
}

/* Indicador visual de drop */
.selected-column.drag-over {
    border-top: 3px solid #667eea;
    background: rgba(102, 126, 234, 0.1);
}

/* Cursor durante o drag */
.selected-column[draggable="true"] {
    cursor: move;
}

/* Efeito de hover no handle de drag */
.drag-handle:hover {
    color: #667eea;
}

/* Animação de toast */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
`;
document.head.appendChild(style);

console.log('🎨 Estilos dinâmicos carregados');