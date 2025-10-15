/**
 * ========================================
 * UTMTRACK DASHBOARD V2.1 - JAVASCRIPT CORRIGIDO
 * ========================================
 * Caminho: /assets/js/utmtrack-dashboard-v2.js
 * CORREÇÕES: Loop de sync, checkbox indeterminate, scrollbar
 */

class UTMTrackDashboard {
    constructor() {
        this.selectedCampaigns = new Set();
        this.campaigns = [];
        this.currentSort = { column: null, direction: 'asc' };
        this.currentPeriod = 'maximum';
        this.filters = {
            search: '',
            status: '',
            account: ''
        };
        
        this.init();
    }
    
    init() {
        console.log('🚀 UTMTrack Dashboard v2.1 inicializado');
        this.setupEventListeners();
        this.loadCampaigns();
        this.detectCurrentPeriod();
        
        // CORREÇÃO: Remove o parâmetro sync da URL para evitar loop
        this.cleanSyncParam();
        
        // CORREÇÃO: Força scrollbar a aparecer
        this.ensureScrollbar();
    }
    
    /**
     * CORREÇÃO: Remove parâmetro sync=1 da URL após carregar
     */
    cleanSyncParam() {
        const url = new URL(window.location);
        if (url.searchParams.has('sync')) {
            console.log('🧹 Removendo parâmetro sync da URL para evitar loop');
            url.searchParams.delete('sync');
            // Substitui URL sem recarregar a página
            window.history.replaceState({}, '', url);
        }
    }
    
    /**
     * CORREÇÃO: Garante que scrollbar apareça
     */
    ensureScrollbar() {
        const container = document.querySelector('.table-container');
        const table = document.querySelector('.campaigns-table');
        
        if (container && table) {
            console.log('📏 Container width:', container.offsetWidth);
            console.log('📏 Table width:', table.offsetWidth);
            
            if (table.offsetWidth > container.offsetWidth) {
                console.log('✅ Scrollbar deve estar visível');
            } else {
                console.log('⚠️ Tabela não é larga o suficiente, forçando largura');
                table.style.minWidth = '2400px';
            }
        }
    }
    
    /**
     * Detecta o período atual da URL
     */
    detectCurrentPeriod() {
        const urlParams = new URLSearchParams(window.location.search);
        const period = urlParams.get('period');
        
        if (period) {
            this.currentPeriod = period;
            
            // Marca o botão correto como ativo
            document.querySelectorAll('.period-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.getAttribute('data-period') === period) {
                    tab.classList.add('active');
                }
            });
        }
    }
    
    setupEventListeners() {
        const selectAll = document.getElementById('selectAllCampaigns');
        if (selectAll) {
            selectAll.addEventListener('change', () => this.toggleSelectAll());
        }
        
        document.querySelectorAll('.campaign-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => this.toggleCampaignSelection(e.target));
        });
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', () => this.filterTable());
        }
        
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.filterTable());
        }
        
        const accountFilter = document.getElementById('accountFilter');
        if (accountFilter) {
            accountFilter.addEventListener('change', () => this.filterTable());
        }
        
        document.querySelectorAll('.sortable').forEach(th => {
            th.addEventListener('click', () => {
                const column = th.getAttribute('data-column');
                this.sortTable(column);
            });
        });
        
        this.setupColumnDragDrop();
        this.setupColumnResize();
    }
    
    toggleSelectAll() {
        const selectAll = document.getElementById('selectAllCampaigns');
        const checkboxes = document.querySelectorAll('.campaign-checkbox:not([disabled])');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            const id = parseInt(checkbox.value);
            
            if (selectAll.checked) {
                this.selectedCampaigns.add(id);
            } else {
                this.selectedCampaigns.delete(id);
            }
        });
        
        this.updateSelectedCount();
    }
    
    toggleCampaignSelection(checkbox) {
        const id = parseInt(checkbox.value);
        
        if (checkbox.checked) {
            this.selectedCampaigns.add(id);
        } else {
            this.selectedCampaigns.delete(id);
        }
        
        this.updateSelectedCount();
    }
    
    updateSelectedCount() {
        const count = this.selectedCampaigns.size;
        const bulkBar = document.getElementById('bulkActionsBar');
        const countSpan = document.getElementById('selectedCount');
        
        if (count > 0) {
            bulkBar.style.display = 'flex';
            countSpan.textContent = count;
        } else {
            bulkBar.style.display = 'none';
        }
        
        const selectAll = document.getElementById('selectAllCampaigns');
        const totalCheckboxes = document.querySelectorAll('.campaign-checkbox:not([disabled])').length;
        
        if (selectAll) {
            // CORREÇÃO: Seta indeterminate via JavaScript (não CSS)
            if (count === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (count === totalCheckboxes) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }
    }
    
    filterTable() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const accountFilter = document.getElementById('accountFilter').value;
        
        const rows = document.querySelectorAll('#tableBody tr:not(.empty-state)');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const status = row.getAttribute('data-status') || '';
            const account = row.getAttribute('data-account') || '';
            
            const matchSearch = !searchTerm || name.includes(searchTerm);
            const matchStatus = !statusFilter || status === statusFilter;
            const matchAccount = !accountFilter || account === accountFilter;
            
            if (matchSearch && matchStatus && matchAccount) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        this.toggleEmptyState(visibleCount === 0);
    }
    
    toggleEmptyState(show) {
        const emptyRow = document.querySelector('#tableBody .empty-state');
        if (emptyRow) {
            emptyRow.style.display = show ? '' : 'none';
        }
    }
    
    sortTable(column) {
        if (this.currentSort.column === column) {
            this.currentSort.direction = this.currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            this.currentSort.column = column;
            this.currentSort.direction = 'asc';
        }
        
        const tbody = document.getElementById('tableBody');
        const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-state)'));
        
        rows.sort((a, b) => {
            const aCell = a.querySelector(`td[data-column="${column}"]`);
            const bCell = b.querySelector(`td[data-column="${column}"]`);
            
            if (!aCell || !bCell) return 0;
            
            let aVal = aCell.textContent.trim();
            let bVal = bCell.textContent.trim();
            
            aVal = aVal.replace(/[R$%\s.]/g, '').replace(',', '.');
            bVal = bVal.replace(/[R$%\s.]/g, '').replace(',', '.');
            
            const aNum = parseFloat(aVal);
            const bNum = parseFloat(bVal);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return this.currentSort.direction === 'asc' ? aNum - bNum : bNum - aNum;
            }
            
            return this.currentSort.direction === 'asc' 
                ? aVal.localeCompare(bVal) 
                : bVal.localeCompare(aVal);
        });
        
        rows.forEach(row => tbody.appendChild(row));
        this.updateSortIcons(column);
    }
    
    updateSortIcons(column) {
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.textContent = '⇅';
            icon.style.opacity = '0.5';
        });
        
        const activeHeader = document.querySelector(`th[data-column="${column}"] .sort-icon`);
        if (activeHeader) {
            activeHeader.textContent = this.currentSort.direction === 'asc' ? '↑' : '↓';
            activeHeader.style.opacity = '1';
        }
    }
    
    setupColumnDragDrop() {
        const headers = document.querySelectorAll('th[data-column]');
        let draggedColumn = null;
        let draggedIndex = -1;
        
        headers.forEach((header, index) => {
            const dragHandle = header.querySelector('.drag-handle');
            if (!dragHandle) return;
            
            dragHandle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                header.setAttribute('draggable', 'true');
            });
            
            header.addEventListener('dragstart', (e) => {
                draggedColumn = header;
                draggedIndex = index;
                e.dataTransfer.effectAllowed = 'move';
                header.style.opacity = '0.5';
                header.classList.add('dragging');
            });
            
            header.addEventListener('dragend', (e) => {
                header.style.opacity = '1';
                header.setAttribute('draggable', 'false');
                header.classList.remove('dragging');
                
                document.querySelectorAll('th').forEach(th => {
                    th.classList.remove('drag-over-left', 'drag-over-right');
                });
            });
            
            header.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (draggedColumn === header) return;
                
                const rect = header.getBoundingClientRect();
                const midpoint = rect.left + rect.width / 2;
                
                header.classList.remove('drag-over-left', 'drag-over-right');
                
                if (e.clientX < midpoint) {
                    header.classList.add('drag-over-left');
                } else {
                    header.classList.add('drag-over-right');
                }
            });
            
            header.addEventListener('dragleave', (e) => {
                header.classList.remove('drag-over-left', 'drag-over-right');
            });
            
            header.addEventListener('drop', (e) => {
                e.preventDefault();
                header.classList.remove('drag-over-left', 'drag-over-right');
                
                if (draggedColumn && draggedColumn !== header) {
                    const allHeaders = Array.from(headers);
                    const targetIndex = allHeaders.indexOf(header);
                    
                    const rect = header.getBoundingClientRect();
                    const midpoint = rect.left + rect.width / 2;
                    const dropBefore = e.clientX < midpoint;
                    
                    this.reorderColumns(draggedIndex, targetIndex, dropBefore);
                }
            });
        });
    }
    
    reorderColumns(fromIndex, toIndex, dropBefore) {
        const table = document.getElementById('campaignsTable');
        const headerRow = table.querySelector('thead tr');
        const headers = Array.from(headerRow.querySelectorAll('th'));
        
        const movedHeader = headers[fromIndex];
        const targetHeader = headers[toIndex];
        
        if (dropBefore) {
            headerRow.insertBefore(movedHeader, targetHeader);
        } else {
            headerRow.insertBefore(movedHeader, targetHeader.nextSibling);
        }
        
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            if (cells.length === 0) return;
            
            const movedCell = cells[fromIndex];
            const targetCell = cells[toIndex];
            
            if (movedCell && targetCell) {
                if (dropBefore) {
                    row.insertBefore(movedCell, targetCell);
                } else {
                    row.insertBefore(movedCell, targetCell.nextSibling);
                }
            }
        });
        
        this.saveColumnOrder();
    }
    
    setupColumnResize() {
        const resizeHandles = document.querySelectorAll('.resize-handle');
        
        resizeHandles.forEach(handle => {
            let startX, startWidth, th;
            
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                th = handle.closest('th');
                startX = e.pageX;
                startWidth = th.offsetWidth;
                
                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
            });
            
            function handleMouseMove(e) {
                if (!th) return;
                const width = startWidth + (e.pageX - startX);
                th.style.minWidth = Math.max(width, 80) + 'px';
                th.style.width = Math.max(width, 80) + 'px';
            }
            
            function handleMouseUp() {
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
                th = null;
            }
        });
    }
    
    saveColumnOrder() {
        const headers = document.querySelectorAll('th[data-column]');
        const order = Array.from(headers).map(th => th.getAttribute('data-column'));
        
        this.apiCall('save_columns', { columns: order })
            .then(() => {
                console.log('✅ Ordem de colunas salva');
                this.showToast('Ordem das colunas salva', 'success');
            })
            .catch(err => console.error('❌ Erro ao salvar ordem:', err));
    }
    
    loadCampaigns() {
        const rows = document.querySelectorAll('#tableBody tr[data-id]');
        this.campaigns = Array.from(rows).map(row => ({
            id: parseInt(row.getAttribute('data-id')),
            campaign_id: row.getAttribute('data-campaign-id'),
            name: row.getAttribute('data-name'),
            status: row.getAttribute('data-status')
        }));
        
        console.log(`📊 ${this.campaigns.length} campanhas carregadas`);
    }
    
    async syncAllCampaigns() {
        const button = document.getElementById('syncButton');
        if (!button) return;
        
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<svg class="icon sync-icon" style="animation: rotate 1s linear infinite;" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> Sincronizando...';
        
        this.showLoading('🔄 Sincronizando dados do Meta Ads com período: ' + this.getPeriodLabel(this.currentPeriod));
        
        try {
            const response = await this.apiCall('sync_complete', {
                date_preset: this.currentPeriod,
                include_insights: true,
                include_actions: true
            });
            
            if (response.success) {
                this.showToast(response.message || 'Sincronização concluída!', 'success');
                
                // CORREÇÃO: NÃO adiciona sync=1 na URL
                const url = new URL(window.location);
                url.searchParams.set('period', this.currentPeriod);
                // NÃO adiciona sync=1 aqui
                
                setTimeout(() => {
                    window.location.href = url.toString();
                }, 1500);
            } else {
                throw new Error(response.message || 'Erro na sincronização');
            }
        } catch (error) {
            console.error('❌ Erro na sincronização:', error);
            this.showToast('Erro ao sincronizar: ' + error.message, 'error');
        } finally {
            this.hideLoading();
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }
    
    async toggleStatus(checkbox, campaignId, metaCampaignId) {
        const newStatus = checkbox.checked ? 'ACTIVE' : 'PAUSED';
        const originalState = !checkbox.checked;
        
        checkbox.disabled = true;
        
        try {
            const response = await this.apiCall('update_status', {
                campaign_id: campaignId,
                meta_campaign_id: metaCampaignId,
                status: newStatus
            });
            
            if (response.success) {
                this.showToast(response.message, 'success');
                
                const row = checkbox.closest('tr');
                if (row) {
                    row.setAttribute('data-status', newStatus.toLowerCase());
                }
            } else {
                throw new Error(response.message || 'Erro ao atualizar status');
            }
        } catch (error) {
            console.error('❌ Erro ao alterar status:', error);
            this.showToast('Erro: ' + error.message, 'error');
            checkbox.checked = originalState;
        } finally {
            checkbox.disabled = false;
        }
    }
    
    async editField(element, campaignId, field, type) {
        const currentValue = element.getAttribute('data-value') || element.textContent.trim();
        const cleanValue = currentValue.replace(/[R$\s.]/g, '').replace(',', '.');
        
        let input;
        
        if (type === 'currency') {
            input = document.createElement('input');
            input.type = 'number';
            input.step = '0.01';
            input.value = cleanValue;
        } else {
            input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
        }
        
        input.style.width = '100%';
        input.style.padding = '4px';
        input.style.background = 'var(--bg-tertiary)';
        input.style.border = '1px solid var(--accent)';
        input.style.color = 'var(--text-primary)';
        input.style.borderRadius = '4px';
        
        const save = async () => {
            const newValue = type === 'currency' ? parseFloat(input.value) : input.value;
            
            if (newValue == cleanValue || newValue == currentValue) {
                element.innerHTML = element.getAttribute('data-original-html');
                return;
            }
            
            const row = element.closest('tr');
            const metaCampaignId = row ? row.getAttribute('data-campaign-id') : null;
            
            try {
                const response = await this.apiCall('update_field', {
                    campaign_id: campaignId,
                    meta_campaign_id: metaCampaignId,
                    field: field,
                    value: newValue
                });
                
                if (response.success) {
                    if (type === 'currency') {
                        element.textContent = 'R$ ' + parseFloat(newValue).toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    } else {
                        element.textContent = newValue;
                    }
                    
                    element.setAttribute('data-value', newValue);
                    this.showToast(response.message, 'success');
                } else {
                    throw new Error(response.message);
                }
            } catch (error) {
                console.error('❌ Erro ao atualizar campo:', error);
                this.showToast('Erro: ' + error.message, 'error');
                element.innerHTML = element.getAttribute('data-original-html');
            }
        };
        
        element.setAttribute('data-original-html', element.innerHTML);
        
        element.innerHTML = '';
        element.appendChild(input);
        input.focus();
        input.select();
        
        input.addEventListener('blur', save);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                save();
            } else if (e.key === 'Escape') {
                element.innerHTML = element.getAttribute('data-original-html');
            }
        });
    }
    
    async bulkAction(action) {
        if (this.selectedCampaigns.size === 0) {
            this.showToast('Nenhuma campanha selecionada', 'error');
            return;
        }
        
        const campaigns = Array.from(this.selectedCampaigns);
        const actionNames = {
            'activate': 'ativar',
            'pause': 'pausar',
            'delete': 'excluir'
        };
        
        if (!confirm(`Deseja ${actionNames[action]} ${campaigns.length} campanha(s)?`)) {
            return;
        }
        
        this.showLoading(`Processando ${campaigns.length} campanha(s)...`);
        
        try {
            const response = await this.apiCall('bulk_action', {
                bulk_action: action,
                campaign_ids: campaigns
            });
            
            if (response.success) {
                this.showToast(response.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('❌ Erro em ação em massa:', error);
            this.showToast('Erro: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * CORREÇÃO: Muda período SEM adicionar sync=1
     */
    async changePeriod(period, button) {
        console.log('📅 Mudando período para:', period);
        
        document.querySelectorAll('.period-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        button.classList.add('active');
        
        const customRange = document.getElementById('customDateRange');
        if (customRange) {
            if (period === 'custom') {
                customRange.style.display = 'flex';
                return;
            } else {
                customRange.style.display = 'none';
            }
        }
        
        this.currentPeriod = period;
        
        // CORREÇÃO: NÃO adiciona sync=1 aqui
        const url = new URL(window.location);
        url.searchParams.set('period', period);
        // Removido: url.searchParams.set('sync', '1');
        
        this.showLoading(`Carregando dados do período: ${this.getPeriodLabel(period)}...`);
        
        setTimeout(() => {
            window.location.href = url.toString();
        }, 500);
    }
    
    getPeriodLabel(period) {
        const labels = {
            'today': 'Hoje',
            'yesterday': 'Ontem',
            'last_7d': 'Últimos 7 dias',
            'last_30d': 'Últimos 30 dias',
            'this_month': 'Este mês',
            'last_month': 'Mês passado',
            'maximum': 'Período máximo'
        };
        return labels[period] || period;
    }
    
    toggleCustomPeriod(button) {
        const customRange = document.getElementById('customDateRange');
        
        if (customRange) {
            if (customRange.style.display === 'none' || !customRange.style.display) {
                customRange.style.display = 'flex';
                
                document.querySelectorAll('.period-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                button.classList.add('active');
            } else {
                customRange.style.display = 'none';
            }
        }
    }
    
    async applyCustomPeriod() {
        const startDate = document.getElementById('startDate')?.value;
        const endDate = document.getElementById('endDate')?.value;
        
        if (!startDate || !endDate) {
            this.showToast('Selecione as datas', 'error');
            return;
        }
        
        console.log('📅 Aplicando período customizado:', startDate, 'até', endDate);
        
        const url = new URL(window.location);
        url.searchParams.set('period', 'custom');
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);
        
        this.showLoading('Carregando dados do período customizado...');
        
        setTimeout(() => {
            window.location.href = url.toString();
        }, 500);
    }
    
    openColumnsModal() {
        const modal = document.getElementById('columnsModal');
        const container = document.getElementById('columnsCheckboxes');
        
        if (!modal || !container) return;
        
        container.innerHTML = '';
        
        const allColumns = {
            'nome': 'Nome da Campanha',
            'status': 'Status',
            'orcamento': 'Orçamento',
            'gastos': 'Gastos',
            'impressoes': 'Impressões',
            'cliques': 'Cliques',
            'ctr': 'CTR',
            'cpc': 'CPC',
            'cpm': 'CPM',
            'vendas': 'Compras',
            'faturamento': 'Faturamento',
            'lucro': 'Lucro',
            'roas': 'ROAS',
            'roi': 'ROI',
            'margem': 'Margem',
            'cpa': 'CPA',
            'ic': 'Iniciar Checkout',
            'cpi': 'Custo por IC',
            'add_carrinho': 'Add ao Carrinho',
            'ver_conteudo': 'Ver Conteúdo',
            'leads': 'Leads',
            'conversoes': 'Conversões',
            'alcance': 'Alcance',
            'frequencia': 'Frequência',
            'conta': 'Conta',
            'objetivo': 'Objetivo',
            'ultima_sync': 'Última Sincronização'
        };
        
        const currentColumns = Array.from(document.querySelectorAll('th[data-column]'))
            .map(th => th.getAttribute('data-column'))
            .filter(col => col !== 'checkbox');
        
        console.log('📋 Colunas visíveis atualmente:', currentColumns);
        
        Object.entries(allColumns).forEach(([key, label]) => {
            const div = document.createElement('div');
            div.style.padding = '8px';
            div.style.borderBottom = '1px solid var(--border)';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = 'col_' + key;
            checkbox.value = key;
            checkbox.checked = currentColumns.includes(key);
            checkbox.style.marginRight = '8px';
            
            const labelEl = document.createElement('label');
            labelEl.htmlFor = 'col_' + key;
            labelEl.textContent = label;
            labelEl.style.cursor = 'pointer';
            
            div.appendChild(checkbox);
            div.appendChild(labelEl);
            container.appendChild(div);
        });
        
        modal.style.display = 'flex';
    }
    
    closeColumnsModal() {
        const modal = document.getElementById('columnsModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    async saveColumns() {
        const allCheckboxes = document.querySelectorAll('#columnsCheckboxes input[type="checkbox"]');
        const columns = ['checkbox'];
        
        allCheckboxes.forEach(cb => {
            if (cb.checked) {
                columns.push(cb.value);
            }
        });
        
        console.log('💾 Salvando colunas:', columns);
        
        try {
            const response = await this.apiCall('save_columns', { columns });
            
            if (response.success) {
                this.showToast('Colunas salvas! Recarregando página...', 'success');
                this.closeColumnsModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error(response.message || 'Erro ao salvar');
            }
        } catch (error) {
            console.error('❌ Erro ao salvar colunas:', error);
            this.showToast('Erro ao salvar: ' + error.message, 'error');
        }
    }
    
    filterColumnsModal() {
        const search = document.getElementById('columnSearch')?.value.toLowerCase() || '';
        const items = document.querySelectorAll('#columnsCheckboxes > div');
        
        items.forEach(item => {
            const label = item.querySelector('label')?.textContent.toLowerCase() || '';
            item.style.display = label.includes(search) ? '' : 'none';
        });
    }
    
    exportData() {
        const format = prompt('Formato de exportação (csv/json):', 'csv');
        
        if (!format || !['csv', 'json'].includes(format)) {
            return;
        }
        
        window.location.href = `${window.currentPage}&ajax_action=export&format=${format}`;
    }
    
    openSettings() {
        alert('Modal de configurações em desenvolvimento');
    }
    
    async apiCall(action, data = {}) {
        // CORREÇÃO: Chama o arquivo AJAX correto
        const ajaxUrl = window.baseUrl + '/public/ajax-campaigns.php?ajax_action=' + action;
        
        console.log('🌐 API Call:', action, '→', ajaxUrl);
        
        try {
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const text = await response.text();
            
            let jsonData;
            try {
                jsonData = JSON.parse(text);
            } catch (parseError) {
                console.error('❌ Erro ao fazer parse do JSON:', parseError);
                console.error('Resposta recebida:', text.substring(0, 1000));
                
                if (text.includes('<!DOCTYPE') || text.includes('<html')) {
                    throw new Error('Sessão expirada ou erro no servidor. Recarregue a página.');
                }
                
                throw new Error('Resposta inválida do servidor');
            }
            
            if (!jsonData.success && jsonData.message) {
                throw new Error(jsonData.message);
            }
            
            if (!response.ok) {
                throw new Error(jsonData.message || `HTTP ${response.status}`);
            }
            
            return jsonData;
            
        } catch (error) {
            console.error('❌ Erro na API Call:', error);
            throw error;
        }
    }
    
    showLoading(message = 'Carregando...') {
        const existing = document.getElementById('loadingOverlay');
        if (existing) {
            existing.remove();
        }
        
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="spinner"></div>
            <div style="color: var(--text-primary); margin-top: 16px;">${message}</div>
        `;
        
        document.body.appendChild(overlay);
    }
    
    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Inicialização
function initDashboard() {
    try {
        window.dashboardInstance = new UTMTrackDashboard();
        console.log('✅ Dashboard inicializado com sucesso');
    } catch (error) {
        console.error('❌ Erro ao inicializar dashboard:', error);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}

// Funções globais
window.toggleSelectAll = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.toggleSelectAll();
    }
};

window.updateSelectedCount = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.updateSelectedCount();
    }
};

window.toggleStatus = function(checkbox, campaignId, metaCampaignId) {
    if (window.dashboardInstance) {
        window.dashboardInstance.toggleStatus(checkbox, campaignId, metaCampaignId);
    }
};

window.editField = function(element, campaignId, field, type) {
    if (window.dashboardInstance) {
        window.dashboardInstance.editField(element, campaignId, field, type);
    }
};

window.filterTable = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.filterTable();
    }
};

window.syncAllCampaigns = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.syncAllCampaigns();
    }
};

window.bulkAction = function(action) {
    if (window.dashboardInstance) {
        window.dashboardInstance.bulkAction(action);
    }
};

window.changePeriod = function(period, button) {
    if (window.dashboardInstance) {
        window.dashboardInstance.changePeriod(period, button);
    }
};

window.toggleCustomPeriod = function(button) {
    if (window.dashboardInstance) {
        window.dashboardInstance.toggleCustomPeriod(button);
    }
};

window.applyCustomPeriod = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.applyCustomPeriod();
    }
};

window.openColumnsModal = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.openColumnsModal();
    }
};

window.closeColumnsModal = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.closeColumnsModal();
    }
};

window.saveColumns = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.saveColumns();
    }
};

window.filterColumnsModal = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.filterColumnsModal();
    }
};

window.exportData = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.exportData();
    }
};

window.openSettings = function() {
    if (window.dashboardInstance) {
        window.dashboardInstance.openSettings();
    }
};

console.log('✅ UTMTrack Dashboard v2.1 - JavaScript carregado');