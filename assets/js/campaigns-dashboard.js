/**
 * UTMTrack - JavaScript do Dashboard de Campanhas
 * Arquivo: assets/js/campaigns-dashboard.js
 * Versão: 5.0
 */

// ========================================
// CONFIGURAÇÃO DE COLUNAS
// ========================================
const allColumns = {
    'nome': { label: 'Nome da Campanha', default: true, minWidth: 250 },
    'status': { label: 'Status', default: true, minWidth: 120 },
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

// Inicializa com configuração do usuário ou padrão
let selectedColumnsList = window.userColumnsConfig || 
    Object.keys(allColumns).filter(key => allColumns[key].default);

let columnWidths = {};

// ========================================
// GERENCIAMENTO DE LARGURAS DE COLUNAS
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
    
    if (!header) {
        console.error('Header da tabela não encontrado');
        return;
    }
    
    header.innerHTML = '';
    
    // Renderiza os headers
    selectedColumnsList.forEach(col => {
        if (allColumns[col]) {
            const th = document.createElement('th');
            const width = columnWidths[col] || allColumns[col].minWidth;
            th.style.width = width + 'px';
            th.setAttribute('draggable', 'true');
            th.innerHTML = allColumns[col].label;
            header.appendChild(th);
        }
    });
    
    // Mostra/oculta as células de dados
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
                } else {
                    cell.style.display = 'none';
                }
            }
        });
    });
}

// ========================================
// INLINE EDITING - NOME DA CAMPANHA
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

// ========================================
// INLINE EDITING - OUTROS CAMPOS
// ========================================
function makeEditable(element, campaignId, field, type = 'text') {
    if (element.classList.contains('editing')) return;
    
    const currentValue = element.getAttribute('data-value');
    element.classList.add('editing');
    element.innerHTML = `<input type="${type === 'currency' ? 'number' : 'text'}" 
                                value="${currentValue}" 
                                step="0.01"
                                onblur="saveField(this, ${campaignId}, '${field}', '${type}')"
                                onkeypress="if(event.key==='Enter') this.blur()">`;
    element.querySelector('input').focus();
    element.querySelector('input').select();
}

async function saveField(input, campaignId, field, type) {
    const newValue = input.value;
    const parent = input.parentElement;
    
    parent.innerHTML = '<span class="saving-indicator">💾 Salvando...</span>';
    
    try {
        const response = await fetch('index.php?page=campanhas-update-field', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                campaign_id: campaignId,
                field: field,
                value: newValue
            })
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
            }
            
            parent.style.background = 'rgba(16, 185, 129, 0.2)';
            setTimeout(() => {
                parent.style.background = '';
            }, 1000);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        alert('❌ Erro ao salvar: ' + error.message);
        parent.classList.remove('editing');
        if (field === 'campaign_name') {
            makeEditableName(parent, campaignId);
        } else {
            makeEditable(parent, campaignId, field, type);
        }
    }
}

async function toggleStatus(element, campaignId) {
    const currentStatus = element.closest('tr').getAttribute('data-status');
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';
    
    try {
        const response = await fetch('index.php?page=campanhas-update-field', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                campaign_id: campaignId,
                field: 'status',
                value: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            element.className = 'status-badge ' + newStatus;
            element.innerHTML = newStatus === 'active' ? '✓ Ativada' : '⏸ Desativada';
            element.closest('tr').setAttribute('data-status', newStatus);
            
            if (result.meta_updated) {
                element.style.background = 'rgba(16, 185, 129, 0.3)';
                setTimeout(() => {
                    element.style.background = '';
                }, 2000);
            }
        }
    } catch (error) {
        alert('❌ Erro: ' + error.message);
    }
}

// ========================================
// REDIMENSIONAMENTO DE COLUNAS
// ========================================
let isResizing = false;
let currentResizeColumn = null;
let startX = 0;
let startWidth = 0;

function initColumnResize() {
    const headers = document.querySelectorAll('#tableHeader th');
    
    headers.forEach((th, index) => {
        const resizeHandle = document.createElement('div');
        resizeHandle.className = 'resize-handle';
        th.appendChild(resizeHandle);
        
        resizeHandle.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            isResizing = true;
            currentResizeColumn = index;
            startX = e.pageX;
            startWidth = th.offsetWidth;
            
            resizeHandle.classList.add('resizing');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });
    });
    
    document.addEventListener('mousemove', (e) => {
        if (!isResizing) return;
        
        const diff = e.pageX - startX;
        const newWidth = Math.max(startWidth + diff, allColumns[selectedColumnsList[currentResizeColumn]].minWidth);
        
        const th = document.querySelectorAll('#tableHeader th')[currentResizeColumn];
        th.style.width = newWidth + 'px';
        
        const columnKey = selectedColumnsList[currentResizeColumn];
        const cells = document.querySelectorAll(`td[data-column="${columnKey}"]`);
        cells.forEach(cell => {
            cell.style.width = newWidth + 'px';
        });
        
        columnWidths[columnKey] = newWidth;
    });
    
    document.addEventListener('mouseup', () => {
        if (isResizing) {
            isResizing = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
            
            const handles = document.querySelectorAll('.resize-handle');
            handles.forEach(h => h.classList.remove('resizing'));
            
            saveColumnWidths();
        }
    });
}

// ========================================
// DRAG AND DROP - HEADERS
// ========================================
let draggedHeaderIndex = null;

function initDragDropHeaders() {
    const headers = document.querySelectorAll('#tableHeader th');
    
    headers.forEach((header, index) => {
        const existingHandle = header.querySelector('.resize-handle');
        if (existingHandle) {
            existingHandle.remove();
        }
        
        const content = document.createElement('div');
        content.className = 'th-content';
        content.innerHTML = `
            <span>${allColumns[selectedColumnsList[index]].label}</span>
            <span class="th-drag-icon">☰</span>
        `;
        header.innerHTML = '';
        header.appendChild(content);
        
        header.setAttribute('draggable', 'true');
        
        header.addEventListener('dragstart', (e) => {
            if (isResizing) {
                e.preventDefault();
                return;
            }
            draggedHeaderIndex = index;
            header.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        
        header.addEventListener('dragover', (e) => {
            if (isResizing) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            header.classList.add('drag-over');
        });
        
        header.addEventListener('dragleave', () => {
            header.classList.remove('drag-over');
        });
        
        header.addEventListener('drop', (e) => {
            if (isResizing) return;
            e.preventDefault();
            header.classList.remove('drag-over');
            
            if (draggedHeaderIndex !== null && draggedHeaderIndex !== index) {
                const draggedCol = selectedColumnsList[draggedHeaderIndex];
                selectedColumnsList.splice(draggedHeaderIndex, 1);
                selectedColumnsList.splice(index, 0, draggedCol);
                
                renderTable();
                initDragDropHeaders();
                initColumnResize();
                
                saveColumnsQuietly();
            }
        });
        
        header.addEventListener('dragend', () => {
            header.classList.remove('dragging');
            draggedHeaderIndex = null;
        });
    });
    
    initColumnResize();
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
    
    selectedColumnsList.forEach(key => {
        const div = document.createElement('div');
        div.className = 'selected-column';
        div.innerHTML = `
            <span class="selected-column-name">☰ ${allColumns[key].label}</span>
            <span class="selected-column-remove" onclick="removeColumn('${key}')">✕</span>
        `;
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
            renderTable();
            initDragDropHeaders();
            initColumnResize();
            closeColumnsModal();
            alert('✅ ' + result.message);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        alert('❌ Erro: ' + error.message);
    }
}

async function saveColumnsQuietly() {
    try {
        await fetch('index.php?page=campanhas-save-columns', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ columns: selectedColumnsList })
        });
    } catch (error) {
        console.error('Erro ao salvar ordem:', error);
    }
}

// ========================================
// FILTROS
// ========================================
function filterTable() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    
    if (!searchInput || !statusFilter) {
        console.error('Elementos de filtro não encontrados');
        return;
    }
    
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
}

function filterColumns() {
    const search = document.getElementById('columnSearch').value.toLowerCase();
    document.querySelectorAll('.column-option').forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(search) ? 'flex' : 'none';
    });
}

// ========================================
// SINCRONIZAÇÃO
// ========================================
async function syncAllCampaigns() {
    if (!confirm('🔄 Sincronizar todas as campanhas do Meta Ads?')) return;
    
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="icon" style="width: 16px; height: 16px; animation: spin 1s linear infinite;" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"></circle></svg> Sincronizando...';
    
    try {
        const response = await fetch('index.php?page=campanhas-sync-all', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'sync_all=1'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ ${result.message}\n\nImportadas: ${result.imported || 0}\nAtualizadas: ${result.updated || 0}`);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Erro na sincronização:', error);
        alert('❌ Erro ao sincronizar: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

// ========================================
// INICIALIZAÇÃO
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Carregado. Iniciando renderização...');
    
    const tbody = document.getElementById('tableBody');
    const dataRows = tbody ? tbody.querySelectorAll('tr[data-id]') : [];
    console.log('Linhas de dados encontradas:', dataRows.length);
    
    loadColumnWidths();
    renderTable();
    
    // Só inicializa drag/resize se houver headers
    setTimeout(() => {
        const headers = document.querySelectorAll('#tableHeader th');
        console.log('Headers criados:', headers.length);
        
        if (headers.length > 0) {
            initDragDropHeaders();
            initColumnResize();
        }
    }, 100);
});

// Event listener para fechar modal ao clicar fora
document.getElementById('columnsModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'columnsModal') closeColumnsModal();
});

// Exporta funções globais necessárias
window.makeEditableName = makeEditableName;
window.makeEditable = makeEditable;
window.saveField = saveField;
window.toggleStatus = toggleStatus;
window.filterTable = filterTable;
window.filterColumns = filterColumns;
window.openColumnsModal = openColumnsModal;
window.closeColumnsModal = closeColumnsModal;
window.toggleColumn = toggleColumn;
window.removeColumn = removeColumn;
window.saveColumns = saveColumns;
window.syncAllCampaigns = syncAllCampaigns;