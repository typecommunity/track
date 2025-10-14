/**
 * ========================================
 * CAMINHO: /utmtrack/assets/js/campaigns-dashboard.js
 * ========================================
 * 
 * UTMTrack - JavaScript Dashboard COMPLETO
 * Versão 12.0 - Com Drag & Drop + Resize
 */

// Variável global do período
let currentPeriod = 'maximum';
let customDateRange = { start: null, end: null };

// Configuração de colunas
const allColumns = {
    'nome': { label: 'Nome da Campanha', default: true, minWidth: 250 },
    'status': { label: 'Status', default: true, minWidth: 100 },
    'orcamento': { label: 'Orçamento', default: true, minWidth: 130 },
    'vendas': { label: 'Vendas', default: true, minWidth: 100 },
    'cpa': { label: 'CPA', default: true, minWidth: 120 },
    'gastos': { label: 'Gastos', default: true, minWidth: 130 },
    'faturamento': { label: 'Faturamento', default: true, minWidth: 140 },
    'lucro': { label: 'Lucro', default: true, minWidth: 130 },
    'roas': { label: 'ROAS', default: true, minWidth: 100 },
    'margem': { label: 'Margem (%)', default: true, minWidth: 120 },
    'roi': { label: 'ROI', default: true, minWidth: 100 },
    'ic': { label: 'IC', default: false, minWidth: 100 },
    'cpi': { label: 'CPI', default: false, minWidth: 120 },
    'cpc': { label: 'CPC', default: false, minWidth: 120 },
    'ctr': { label: 'CTR', default: false, minWidth: 100 },
    'cpm': { label: 'CPM', default: false, minWidth: 120 },
    'impressoes': { label: 'Impressões', default: false, minWidth: 130 },
    'cliques': { label: 'Cliques', default: false, minWidth: 110 },
    'conversoes': { label: 'Conversões', default: false, minWidth: 130 },
    'conta': { label: 'Conta', default: false, minWidth: 150 },
    'ultima_sync': { label: 'Última Sync', default: false, minWidth: 150 }
};

let selectedColumnsList = window.userColumnsConfig || 
    Object.keys(allColumns).filter(key => allColumns[key].default);

let columnWidths = {};

// ========================================
// FUNÇÕES DE LARGURA
// ========================================
function loadColumnWidths() {
    const saved = localStorage.getItem('campaignColumnWidths');
    if (saved) {
        columnWidths = JSON.parse(saved);
    } else {
        Object.keys(allColumns).forEach(key => {
            columnWidths[key] = allColumns[key].minWidth;
        });
    }
}

function saveColumnWidths() {
    localStorage.setItem('campaignColumnWidths', JSON.stringify(columnWidths));
}

// ========================================
// RENDERIZAÇÃO DA TABELA
// ========================================
function renderTable() {
    const header = document.getElementById('tableHeader');
    if (!header) return;
    
    header.innerHTML = '';
    
    selectedColumnsList.forEach(col => {
        if (allColumns[col]) {
            const th = document.createElement('th');
            th.setAttribute('data-column', col);
            const width = columnWidths[col] || allColumns[col].minWidth;
            th.style.width = width + 'px';
            th.style.minWidth = allColumns[col].minWidth + 'px';
            th.style.position = 'relative';
            
            // Conteúdo do header
            const thContent = document.createElement('div');
            thContent.className = 'th-content';
            thContent.innerHTML = `
                <span class="th-drag-icon">⋮⋮</span>
                <span>${allColumns[col].label}</span>
            `;
            
            // Resize handle
            const resizeHandle = document.createElement('div');
            resizeHandle.className = 'resize-handle';
            
            th.appendChild(thContent);
            th.appendChild(resizeHandle);
            header.appendChild(th);
        }
    });
    
    // Atualiza células do body
    const rows = document.querySelectorAll('#tableBody tr');
    rows.forEach(row => {
        if (!row.hasAttribute('data-id')) return;
        
        Object.keys(allColumns).forEach(col => {
            const cell = row.querySelector(`td[data-column="${col}"]`);
            if (cell) {
                if (selectedColumnsList.includes(col)) {
                    cell.style.display = '';
                    const width = columnWidths[col] || allColumns[col].minWidth;
                    cell.style.width = width + 'px';
                    cell.style.minWidth = allColumns[col].minWidth + 'px';
                } else {
                    cell.style.display = 'none';
                }
            }
        });
    });
    
    // Inicializa drag & drop e resize
    initDragDropHeaders();
    initColumnResize();
}

// ========================================
// DRAG & DROP DE COLUNAS
// ========================================
let draggedColumn = null;

function initDragDropHeaders() {
    const headers = document.querySelectorAll('#tableHeader th');
    
    headers.forEach(th => {
        th.draggable = true;
        
        th.addEventListener('dragstart', (e) => {
            draggedColumn = th.getAttribute('data-column');
            th.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        
        th.addEventListener('dragend', (e) => {
            th.classList.remove('dragging');
            headers.forEach(h => h.classList.remove('drag-over'));
        });
        
        th.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            const afterElement = getDragAfterElement(e.currentTarget.parentElement, e.clientX);
            if (afterElement == null) {
                th.classList.add('drag-over');
            }
        });
        
        th.addEventListener('dragleave', (e) => {
            th.classList.remove('drag-over');
        });
        
        th.addEventListener('drop', (e) => {
            e.preventDefault();
            th.classList.remove('drag-over');
            
            const targetColumn = th.getAttribute('data-column');
            if (draggedColumn && draggedColumn !== targetColumn) {
                const dragIndex = selectedColumnsList.indexOf(draggedColumn);
                const targetIndex = selectedColumnsList.indexOf(targetColumn);
                
                // Remove da posição atual
                selectedColumnsList.splice(dragIndex, 1);
                // Insere na nova posição
                selectedColumnsList.splice(targetIndex, 0, draggedColumn);
                
                // Re-renderiza
                renderTable();
                saveColumnsOrder();
            }
        });
    });
}

function getDragAfterElement(container, x) {
    const draggableElements = [...container.querySelectorAll('th:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = x - box.left - box.width / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// ========================================
// RESIZE DE COLUNAS
// ========================================
let resizingColumn = null;
let startX = 0;
let startWidth = 0;

function initColumnResize() {
    const headers = document.querySelectorAll('#tableHeader th');
    
    headers.forEach(th => {
        const resizeHandle = th.querySelector('.resize-handle');
        if (!resizeHandle) return;
        
        resizeHandle.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            resizingColumn = th;
            startX = e.clientX;
            startWidth = th.offsetWidth;
            
            resizeHandle.classList.add('resizing');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
            
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    });
}

function onMouseMove(e) {
    if (!resizingColumn) return;
    
    const diff = e.clientX - startX;
    const newWidth = Math.max(
        allColumns[resizingColumn.getAttribute('data-column')].minWidth,
        startWidth + diff
    );
    
    resizingColumn.style.width = newWidth + 'px';
    
    // Atualiza células correspondentes
    const col = resizingColumn.getAttribute('data-column');
    document.querySelectorAll(`td[data-column="${col}"]`).forEach(cell => {
        cell.style.width = newWidth + 'px';
    });
}

function onMouseUp(e) {
    if (!resizingColumn) return;
    
    const resizeHandle = resizingColumn.querySelector('.resize-handle');
    if (resizeHandle) {
        resizeHandle.classList.remove('resizing');
    }
    
    const col = resizingColumn.getAttribute('data-column');
    columnWidths[col] = resizingColumn.offsetWidth;
    saveColumnWidths();
    
    resizingColumn = null;
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
    
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
}

// Salva ordem das colunas
function saveColumnsOrder() {
    localStorage.setItem('campaignColumnsOrder', JSON.stringify(selectedColumnsList));
}

// Carrega ordem das colunas
function loadColumnsOrder() {
    const saved = localStorage.getItem('campaignColumnsOrder');
    if (saved) {
        selectedColumnsList = JSON.parse(saved);
    }
}

// ========================================
// TOGGLE STATUS - FUNÇÃO PRINCIPAL
// ========================================
async function toggleCampaignStatus(checkbox, campaignId, metaCampaignId) {
    const newStatus = checkbox.checked ? 'ACTIVE' : 'PAUSED';
    const newStatusLower = checkbox.checked ? 'active' : 'paused';
    const originalChecked = !checkbox.checked;
    
    checkbox.disabled = true;
    
    try {
        const response = await fetch(window.location.origin + '/utmtrack/ajax_sync.php?action=update_status', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                campaign_id: campaignId,
                meta_campaign_id: metaCampaignId,
                status: newStatus
            }),
            credentials: 'same-origin'
        });
        
        const result = await response.json();
        
        if (result.success) {
            const row = checkbox.closest('tr');
            if (row) {
                row.setAttribute('data-status', newStatusLower);
            }
            
            showToast(result.meta_updated ? 
                '✅ Status atualizado no Meta Ads!' : 
                '✅ Status atualizado localmente', 
                'success'
            );
        } else {
            checkbox.checked = originalChecked;
            throw new Error(result.message);
        }
    } catch (error) {
        checkbox.checked = originalChecked;
        showToast('❌ ' + error.message, 'error');
    } finally {
        checkbox.disabled = false;
    }
}

// ========================================
// INLINE EDITING
// ========================================
function makeEditableName(element, campaignId) {
    if (element.classList.contains('editing')) return;
    
    const currentValue = element.getAttribute('data-value');
    element.classList.add('editing');
    element.innerHTML = `<input type="text" value="${currentValue}" 
                                onblur="saveField(this, ${campaignId}, 'campaign_name', 'text')"
                                onkeypress="if(event.key==='Enter') this.blur()">`;
    element.querySelector('input').focus();
    element.querySelector('input').select();
}

function makeEditable(element, campaignId, field, type = 'text', metaCampaignId = '') {
    if (element.classList.contains('editing')) return;
    
    const currentValue = element.getAttribute('data-value');
    element.classList.add('editing');
    element.innerHTML = `<input type="${type === 'currency' ? 'number' : 'text'}" 
                                value="${currentValue}" 
                                step="0.01"
                                onblur="saveField(this, ${campaignId}, '${field}', '${type}', '${metaCampaignId}')"
                                onkeypress="if(event.key==='Enter') this.blur()">`;
    element.querySelector('input').focus();
    element.querySelector('input').select();
}

async function saveField(input, campaignId, field, type, metaCampaignId = '') {
    const newValue = input.value;
    const parent = input.parentElement;
    
    parent.innerHTML = '<span class="saving-indicator">💾 Salvando...</span>';
    
    try {
        const action = field === 'budget' && metaCampaignId ? 'update_budget' : 'update_field';
        const url = window.location.origin + `/utmtrack/ajax_sync.php?action=${action}`;
        
        const response = await fetch(url, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                campaign_id: campaignId,
                meta_campaign_id: metaCampaignId,
                field: field,
                value: newValue
            }),
            credentials: 'same-origin'
        });
        
        const result = await response.json();
        
        if (result.success) {
            parent.classList.remove('editing');
            parent.setAttribute('data-value', newValue);
            
            if (type === 'currency') {
                parent.innerHTML = 'R$ ' + parseFloat(newValue).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } else {
                parent.innerHTML = newValue;
                
                // Se é o nome da campanha, atualiza também o data-name da row
                if (field === 'campaign_name') {
                    const row = parent.closest('tr');
                    if (row) {
                        row.setAttribute('data-name', newValue.toLowerCase());
                    }
                }
            }
            
            if (result.meta_updated) {
                showToast('✅ Atualizado no Meta Ads!', 'success');
            } else {
                showToast('✅ Salvo com sucesso!', 'success');
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showToast('❌ ' + error.message, 'error');
        parent.classList.remove('editing');
        
        if (type === 'currency') {
            parent.innerHTML = 'R$ ' + parseFloat(parent.getAttribute('data-value')).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } else {
            parent.innerHTML = parent.getAttribute('data-value');
        }
    }
}

// ========================================
// FUNÇÕES DE PERÍODO
// ========================================
function changePeriod(period, button) {
    currentPeriod = period;
    
    document.querySelectorAll('.period-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    button.classList.add('active');
    
    if (period !== 'custom') {
        document.getElementById('customDateRange').style.display = 'none';
    }
    
    console.log('📅 Período:', period);
}

function toggleCustomPeriod(button) {
    const customRange = document.getElementById('customDateRange');
    
    document.querySelectorAll('.period-tab').forEach(btn => {
        if (btn !== button) btn.classList.remove('active');
    });
    
    button.classList.add('active');
    customRange.style.display = customRange.style.display === 'none' ? 'flex' : 'none';
}

function applyCustomPeriod() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        showToast('Selecione as datas', 'warning');
        return;
    }
    
    customDateRange = {
        start: startDate,
        end: endDate
    };
    
    currentPeriod = 'custom';
    syncAllCampaigns();
}

// ========================================
// SINCRONIZAÇÃO - COM PERÍODO
// ========================================
async function syncAllCampaigns() {
    console.log('🔄 Sincronizando período:', currentPeriod);
    
    const button = event?.target || document.querySelector('button[onclick*="syncAllCampaigns"]');
    const originalHTML = button ? button.innerHTML : '';
    
    if (button) {
        button.disabled = true;
        button.innerHTML = '⏳ Sincronizando...';
    }
    
    try {
        const payload = {
            action: 'sync_all',
            period: currentPeriod
        };
        
        if (currentPeriod === 'custom' && customDateRange.start && customDateRange.end) {
            payload.date_preset = 'custom';
            payload.start_date = customDateRange.start;
            payload.end_date = customDateRange.end;
        } else {
            const presets = {
                'today': 'today',
                'yesterday': 'yesterday',
                'last_7d': 'last_7d',
                'last_30d': 'last_30d',
                'this_month': 'this_month',
                'last_month': 'last_month',
                'maximum': 'maximum'
            };
            payload.date_preset = presets[currentPeriod] || 'maximum';
        }
        
        console.log('📤 Payload:', payload);
        
        const baseUrl = window.location.origin + '/utmtrack/';
        const response = await fetch(baseUrl + 'ajax_sync.php?action=sync_all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        });
        
        console.log('📡 Status:', response.status);
        
        const responseText = await response.text();
        console.log('📄 Response:', responseText.substring(0, 500));
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ Parse error:', e);
            throw new Error('Resposta inválida do servidor');
        }
        
        if (result && result.success) {
            showToast('✅ ' + result.message, 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            throw new Error(result?.message || 'Erro desconhecido');
        }
        
    } catch (error) {
        console.error('❌ Erro:', error);
        showToast('❌ ' + error.message, 'error');
    } finally {
        if (button) {
            button.disabled = false;
            button.innerHTML = originalHTML || '🔄 Atualizar Tudo';
        }
    }
}

// ========================================
// TOAST
// ========================================
function showToast(message, type = 'success') {
    // Remove toasts anteriores
    document.querySelectorAll('.toast').forEach(t => t.remove());
    
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ========================================
// FILTROS
// ========================================
function filterTable() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    
    if (!searchInput || !statusFilter) return;
    
    const search = searchInput.value.toLowerCase().trim();
    const status = statusFilter.value;
    
    const rows = document.querySelectorAll('#tableBody tr[data-name]');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        
        const matchName = !search || name.includes(search);
        const matchStatus = !status || rowStatus === status;
        
        row.style.display = (matchName && matchStatus) ? '' : 'none';
    });
}

function filterColumns() {
    const search = document.getElementById('columnSearch').value.toLowerCase();
    document.querySelectorAll('.column-option').forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(search) ? 'flex' : 'none';
    });
}

// ========================================
// MODAL DE COLUNAS
// ========================================
function openColumnsModal() {
    document.getElementById('columnsModal').classList.add('active');
    renderColumnsModal();
}

function closeColumnsModal() {
    document.getElementById('columnsModal').classList.remove('active');
}

function renderColumnsModal() {
    const available = document.getElementById('availableColumns');
    const selected = document.getElementById('selectedColumns');
    
    available.innerHTML = '';
    selected.innerHTML = '';
    
    // Colunas disponíveis
    Object.keys(allColumns).forEach(key => {
        const div = document.createElement('div');
        div.className = 'column-option';
        div.innerHTML = `
            <input type="checkbox" class="column-checkbox" id="col_${key}"
                   ${selectedColumnsList.includes(key) ? 'checked' : ''}
                   onchange="toggleColumn('${key}')">
            <label class="column-label" for="col_${key}">${allColumns[key].label}</label>
        `;
        available.appendChild(div);
    });
    
    // Colunas selecionadas (drag & drop)
    selectedColumnsList.forEach(key => {
        const div = document.createElement('div');
        div.className = 'selected-column';
        div.draggable = true;
        div.setAttribute('data-column', key);
        div.innerHTML = `
            <span class="selected-column-name">☰ ${allColumns[key].label}</span>
            <span class="selected-column-remove" onclick="removeColumn('${key}')">✕</span>
        `;
        
        // Drag events para reordenar
        div.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            div.classList.add('dragging');
        });
        
        div.addEventListener('dragend', () => {
            div.classList.remove('dragging');
        });
        
        div.addEventListener('dragover', (e) => {
            e.preventDefault();
        });
        
        div.addEventListener('drop', (e) => {
            e.preventDefault();
            const dragging = selected.querySelector('.dragging');
            if (dragging && dragging !== div) {
                const dragKey = dragging.getAttribute('data-column');
                const targetKey = div.getAttribute('data-column');
                
                const dragIndex = selectedColumnsList.indexOf(dragKey);
                const targetIndex = selectedColumnsList.indexOf(targetKey);
                
                selectedColumnsList.splice(dragIndex, 1);
                selectedColumnsList.splice(targetIndex, 0, dragKey);
                
                renderColumnsModal();
            }
        });
        
        selected.appendChild(div);
    });
}

function toggleColumn(key) {
    const index = selectedColumnsList.indexOf(key);
    if (index > -1) {
        selectedColumnsList.splice(index, 1);
    } else {
        selectedColumnsList.push(key);
    }
    renderColumnsModal();
}

function removeColumn(key) {
    const index = selectedColumnsList.indexOf(key);
    if (index > -1) selectedColumnsList.splice(index, 1);
    document.getElementById(`col_${key}`).checked = false;
    renderColumnsModal();
}

async function saveColumns() {
    try {
        const response = await fetch('index.php?page=campanhas-save-columns', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ columns: selectedColumnsList })
        });
        
        const result = await response.json();
        
        if (result.success) {
            saveColumnsOrder();
            renderTable();
            closeColumnsModal();
            showToast('✅ ' + result.message, 'success');
        } else {
            showToast('❌ ' + result.message, 'error');
        }
    } catch (error) {
        showToast('❌ Erro: ' + error.message, 'error');
    }
}

// ========================================
// INICIALIZAÇÃO
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Dashboard carregado');
    console.log('📊 Total campanhas:', document.querySelectorAll('#tableBody tr[data-id]').length);
    
    loadColumnWidths();
    loadColumnsOrder();
    renderTable();
    
    const maxBtn = document.querySelector('[data-period="maximum"]');
    if (maxBtn) {
        maxBtn.classList.add('active');
        currentPeriod = 'maximum';
    }
});

// Exporta funções globais
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