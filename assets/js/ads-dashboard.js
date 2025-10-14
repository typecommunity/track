/**
 * ========================================
 * CAMINHO: /utmtrack/assets/js/ads-dashboard.js
 * ========================================
 * 
 * UTMTrack - JavaScript Dashboard de Anúncios
 * Baseado em campaigns-dashboard.js v14.1
 * 
 * @version 14.1 ADAPTADO
 */

// ========================================
// VARIÁVEIS GLOBAIS
// ========================================

const allColumns = {
    'nome': { label: 'Nome do Anúncio', default: true, minWidth: 250, sortable: true, editable: true },
    'status': { label: 'Status', default: true, minWidth: 100, sortable: true, editable: true },
    'vendas': { label: 'Vendas', default: true, minWidth: 100, sortable: true, editable: false },
    'cpa': { label: 'CPA', default: true, minWidth: 120, sortable: true, editable: false },
    'gastos': { label: 'Gastos', default: true, minWidth: 130, sortable: true, editable: false },
    'faturamento': { label: 'Faturamento', default: true, minWidth: 140, sortable: true, editable: false },
    'lucro': { label: 'Lucro', default: true, minWidth: 130, sortable: true, editable: false },
    'roas': { label: 'ROAS', default: true, minWidth: 100, sortable: true, editable: false },
    'margem': { label: 'Margem (%)', default: true, minWidth: 120, sortable: true, editable: false },
    'roi': { label: 'ROI', default: true, minWidth: 100, sortable: true, editable: false },
    'cpc': { label: 'CPC', default: false, minWidth: 120, sortable: true, editable: false },
    'ctr': { label: 'CTR', default: false, minWidth: 100, sortable: true, editable: false },
    'cpm': { label: 'CPM', default: false, minWidth: 120, sortable: true, editable: false },
    'impressoes': { label: 'Impressões', default: false, minWidth: 130, sortable: true, editable: false },
    'cliques': { label: 'Cliques', default: false, minWidth: 110, sortable: true, editable: false },
    'conversoes': { label: 'Conversões', default: false, minWidth: 130, sortable: true, editable: false },
    'alcance': { label: 'Alcance', default: false, minWidth: 130, sortable: true, editable: false },
    'frequencia': { label: 'Frequência', default: false, minWidth: 120, sortable: true, editable: false },
    'conta': { label: 'Conta', default: false, minWidth: 150, sortable: true, editable: false },
    'ultima_sync': { label: 'Última Sync', default: false, minWidth: 150, sortable: true, editable: false }
};

let selectedColumnsList = window.userColumnsConfig || 
    Object.keys(allColumns).filter(key => allColumns[key].default);

let columnWidths = {};
let draggedColumn = null;
let dragStartIndex = -1;
let resizingColumn = null;
let startX = 0;
let startWidth = 0;

// ========================================
// TOAST NOTIFICATIONS
// ========================================

function showToast(message, type = 'success') {
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
// INLINE EDITING
// ========================================

function makeEditableName(element, adId) {
    if (element.classList.contains('editing')) return;
    
    const row = element.closest('tr');
    const metaAdId = row ? row.getAttribute('data-ad-id') : '';
    const currentValue = element.getAttribute('data-value');
    
    element.classList.add('editing');
    element.innerHTML = `<input 
        type="text" 
        value="${currentValue}" 
        onblur="saveField(this, ${adId}, 'ad_name', 'text', '${metaAdId}')"
        onkeypress="if(event.key==='Enter') this.blur()"
        placeholder="Digite o nome do anúncio..."
    >`;
    
    const input = element.querySelector('input');
    input.focus();
    input.select();
}

async function saveField(input, adId, field, type, metaAdId = '') {
    const newValue = input.value.trim();
    const parent = input.parentElement;
    
    if (!newValue) {
        parent.classList.remove('editing');
        parent.innerHTML = parent.getAttribute('data-value');
        showToast('❌ Valor não pode estar vazio', 'error');
        return;
    }
    
    parent.innerHTML = '<span class="saving-indicator">💾 Salvando...</span>';
    
    try {
        const response = await fetch(
            `${window.location.origin}/utmtrack/index.php?page=anuncios&ajax_action=update_field`, 
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ad_id: adId,
                    field: field,
                    value: newValue
                }),
                credentials: 'same-origin'
            }
        );
        
        const result = await response.json();
        
        if (result.success) {
            parent.classList.remove('editing');
            parent.setAttribute('data-value', newValue);
            parent.innerHTML = newValue;
            
            showToast(result.message, result.meta_updated ? 'success' : 'warning');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        parent.classList.remove('editing');
        parent.innerHTML = parent.getAttribute('data-value');
        showToast('❌ ' + error.message, 'error');
        console.error('❌ Erro ao salvar:', error);
    }
}

// ========================================
// TOGGLE STATUS
// ========================================

async function toggleAdStatus(checkbox, adId, metaAdId) {
    const newStatus = checkbox.checked ? 'ACTIVE' : 'PAUSED';
    const newStatusLower = checkbox.checked ? 'active' : 'paused';
    const originalChecked = !checkbox.checked;
    
    checkbox.disabled = true;
    
    try {
        const response = await fetch(
            window.location.origin + '/utmtrack/index.php?page=anuncios&ajax_action=update_status', 
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ad_id: adId,
                    meta_ad_id: metaAdId,
                    status: newStatus
                }),
                credentials: 'same-origin'
            }
        );
        
        const result = await response.json();
        
        if (result.success) {
            const row = checkbox.closest('tr');
            if (row) {
                row.setAttribute('data-status', newStatusLower);
            }
            
            showToast(
                result.meta_updated ? 
                '✅ Status atualizado no Meta Ads!' : 
                '✅ Status atualizado localmente', 
                result.meta_updated ? 'success' : 'warning'
            );
        } else {
            checkbox.checked = originalChecked;
            throw new Error(result.message);
        }
    } catch (error) {
        checkbox.checked = originalChecked;
        showToast('❌ ' + error.message, 'error');
        console.error('❌ Erro ao atualizar status:', error);
    } finally {
        checkbox.disabled = false;
    }
}

// ========================================
// SINCRONIZAÇÃO
// ========================================

async function syncAllAds() {
    const btn = event?.target;
    const originalHTML = btn ? btn.innerHTML : null;
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<svg class="icon spinning" style="animation: spin 1s linear infinite;" viewBox="0 0 24 24"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83" stroke="currentColor" stroke-width="2" fill="none"></path></svg> Sincronizando...';
    }
    
    try {
        const response = await fetch(
            window.location.origin + '/utmtrack/index.php?page=anuncios&ajax_action=sync_all',
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin'
            }
        );
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
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
// FILTROS
// ========================================

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
    
    console.log(`🔍 Filtros aplicados: ${visibleCount} anúncios visíveis`);
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
    
    selectedColumnsList.forEach((key, index) => {
        const div = document.createElement('div');
        div.className = 'selected-column';
        div.setAttribute('draggable', 'true');
        div.setAttribute('data-column', key);
        div.setAttribute('data-index', index);
        div.innerHTML = `
            <span class="drag-handle">⋮⋮</span>
            <span>${allColumns[key].label}</span>
            <button onclick="removeColumn('${key}')" class="remove-btn">×</button>
        `;
        
        div.addEventListener('dragstart', handleDragStart);
        div.addEventListener('dragover', handleDragOver);
        div.addEventListener('drop', handleDrop);
        div.addEventListener('dragend', handleDragEnd);
        div.addEventListener('dragenter', handleDragEnter);
        div.addEventListener('dragleave', handleDragLeave);
        
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
    if (index > -1) {
        selectedColumnsList.splice(index, 1);
    }
    renderColumnsModal();
}

// ========================================
// DRAG & DROP
// ========================================

function handleDragStart(e) {
    draggedColumn = this;
    dragStartIndex = parseInt(this.getAttribute('data-index'));
    this.style.opacity = '0.4';
    e.dataTransfer.effectAllowed = 'move';
    this.classList.add('dragging');
}

function handleDragOver(e) {
    if (e.preventDefault) e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDragEnter(e) {
    const targetColumn = e.target.closest('.selected-column');
    if (targetColumn && targetColumn !== draggedColumn) {
        targetColumn.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    const targetColumn = e.target.closest('.selected-column');
    if (targetColumn) {
        targetColumn.classList.remove('drag-over');
    }
}

function handleDrop(e) {
    if (e.stopPropagation) e.stopPropagation();
    
    const targetColumn = e.target.closest('.selected-column');
    
    if (targetColumn && draggedColumn && draggedColumn !== targetColumn) {
        const draggedKey = draggedColumn.getAttribute('data-column');
        const targetKey = targetColumn.getAttribute('data-column');
        
        const draggedIndex = selectedColumnsList.indexOf(draggedKey);
        const targetIndex = selectedColumnsList.indexOf(targetKey);
        
        selectedColumnsList.splice(draggedIndex, 1);
        selectedColumnsList.splice(targetIndex, 0, draggedKey);
        
        saveColumnsOrder();
        renderColumnsModal();
    }
    
    document.querySelectorAll('.selected-column').forEach(col => {
        col.classList.remove('drag-over');
    });
    
    return false;
}

function handleDragEnd(e) {
    this.style.opacity = '1';
    this.classList.remove('dragging');
    
    document.querySelectorAll('.selected-column').forEach(col => {
        col.classList.remove('drag-over');
    });
}

async function saveColumns() {
    try {
        saveColumnsOrder();
        
        const response = await fetch(
            window.location.origin + '/utmtrack/index.php?page=anuncios&ajax_action=save_columns', 
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
// RENDERIZAÇÃO
// ========================================

function renderTable() {
    const header = document.getElementById('tableHeader');
    const tbody = document.getElementById('tableBody');
    
    if (!header || !tbody) return;
    
    header.innerHTML = '';
    
    selectedColumnsList.forEach(col => {
        if (!allColumns[col]) return;
        
        const th = document.createElement('th');
        th.setAttribute('data-column', col);
        th.style.width = (columnWidths[col] || allColumns[col].minWidth) + 'px';
        th.innerHTML = `
            <div class="th-content">
                <span class="th-drag-icon">⋮⋮</span>
                <span>${allColumns[col].label}</span>
            </div>
            <div class="resize-handle"></div>
        `;
        header.appendChild(th);
    });
    
    tbody.querySelectorAll('tr').forEach(row => {
        const cells = Array.from(row.querySelectorAll('td[data-column]'));
        
        selectedColumnsList.forEach(col => {
            const cell = cells.find(c => c.getAttribute('data-column') === col);
            if (cell) {
                row.appendChild(cell);
                cell.style.width = (columnWidths[col] || allColumns[col].minWidth) + 'px';
                cell.style.display = '';
            }
        });
        
        cells.forEach(cell => {
            const colName = cell.getAttribute('data-column');
            if (!selectedColumnsList.includes(colName)) {
                cell.style.display = 'none';
            }
        });
    });
    
    initColumnResize();
}

// ========================================
// RESIZE
// ========================================

function initColumnResize() {
    document.querySelectorAll('.resize-handle').forEach(handle => {
        handle.addEventListener('mousedown', onResizeStart);
    });
}

function onResizeStart(e) {
    resizingColumn = e.target.parentElement;
    startX = e.clientX;
    startWidth = resizingColumn.offsetWidth;
    
    e.target.classList.add('resizing');
    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
    
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}

function onMouseMove(e) {
    if (!resizingColumn) return;
    
    const diff = e.clientX - startX;
    const col = resizingColumn.getAttribute('data-column');
    const newWidth = Math.max(allColumns[col].minWidth, startWidth + diff);
    
    resizingColumn.style.width = newWidth + 'px';
    
    document.querySelectorAll(`td[data-column="${col}"]`).forEach(cell => {
        cell.style.width = newWidth + 'px';
    });
}

function onMouseUp(e) {
    if (!resizingColumn) return;
    
    const resizeHandle = resizingColumn.querySelector('.resize-handle');
    if (resizeHandle) resizeHandle.classList.remove('resizing');
    
    const col = resizingColumn.getAttribute('data-column');
    columnWidths[col] = resizingColumn.offsetWidth;
    saveColumnWidths();
    
    resizingColumn = null;
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
    
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
}

// ========================================
// PERSISTÊNCIA
// ========================================

function saveColumnWidths() {
    localStorage.setItem('utmtrack_adColumnWidths', JSON.stringify(columnWidths));
}

function loadColumnWidths() {
    const saved = localStorage.getItem('utmtrack_adColumnWidths');
    if (saved) {
        try {
            columnWidths = JSON.parse(saved);
        } catch (e) {
            columnWidths = {};
        }
    }
}

function saveColumnsOrder() {
    localStorage.setItem('utmtrack_adColumnsOrder', JSON.stringify(selectedColumnsList));
}

function loadColumnsOrder() {
    const saved = localStorage.getItem('utmtrack_adColumnsOrder');
    if (saved) {
        try {
            selectedColumnsList = JSON.parse(saved);
        } catch (e) {
            // Mantém o padrão
        }
    }
}

// ========================================
// INICIALIZAÇÃO
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Dashboard Anúncios v14.1 carregado');
    
    loadColumnWidths();
    loadColumnsOrder();
    renderTable();
    
    console.log('✅ Dashboard inicializado');
});

// Exporta para window
window.toggleAdStatus = toggleAdStatus;
window.makeEditableName = makeEditableName;
window.saveField = saveField;
window.syncAllAds = syncAllAds;
window.filterTable = filterTable;
window.filterColumns = filterColumns;
window.openColumnsModal = openColumnsModal;
window.closeColumnsModal = closeColumnsModal;
window.toggleColumn = toggleColumn;
window.removeColumn = removeColumn;
window.saveColumns = saveColumns;