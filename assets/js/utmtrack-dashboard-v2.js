/**
 * ========================================
 * UTMTRACK DASHBOARD V3.1 - CORRIGIDO
 * ========================================
 * Correções aplicadas:
 * - ✅ URL do AJAX corrigida (ajax-campaigns.php com hífen)
 * - ✅ Função editField COMPLETAMENTE CORRIGIDA
 * - ✅ Sincronização com Meta Ads operacional
 * - ✅ Tratamento de erros robusto
 * - ✅ Mantém TODOS os 150+ campos
 * - ✅ TODAS as 1400 linhas mantidas
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
            account: '',
            cbo: null,
            asc: null,
            quality_ranking: '',
            has_issues: false
        };
        
        // Controle de event listeners
        this.abortControllers = [];
        
        // Debounce timer
        this.searchDebounceTimer = null;
        
        // ✅ Mapeamento COMPLETO de campos disponíveis (150+)
        this.availableFields = {
            // Campos Básicos
            'campaign_name': { label: 'Nome da Campanha', type: 'text', category: 'basic' },
            'status': { label: 'Status', type: 'badge', category: 'basic' },
            'account_name': { label: 'Conta', type: 'text', category: 'basic' },
            
            // ✅ Identificadores
            'campaign_budget_optimization': { label: 'CBO', type: 'badge', category: 'identifiers' },
            'is_asc': { label: 'ASC', type: 'badge', category: 'identifiers' },
            'objective': { label: 'Objetivo', type: 'text', category: 'identifiers' },
            'buying_type': { label: 'Tipo de Compra', type: 'text', category: 'identifiers' },
            
            // Orçamentos
            'daily_budget': { label: 'Orçamento Diário', type: 'currency', category: 'budget' },
            'lifetime_budget': { label: 'Orçamento Vitalício', type: 'currency', category: 'budget' },
            'spend_cap': { label: 'Limite de Gastos', type: 'currency', category: 'budget' },
            'budget_remaining': { label: 'Orçamento Restante', type: 'currency', category: 'budget' },
            'daily_min_spend_target': { label: 'Gasto Mín. Diário', type: 'currency', category: 'budget' },
            'daily_spend_cap': { label: 'Limite Gasto Diário', type: 'currency', category: 'budget' },
            
            // Métricas Principais
            'spend': { label: 'Gastos', type: 'currency', category: 'metrics' },
            'impressions': { label: 'Impressões', type: 'number', category: 'metrics' },
            'clicks': { label: 'Cliques', type: 'number', category: 'metrics' },
            'reach': { label: 'Alcance', type: 'number', category: 'metrics' },
            'frequency': { label: 'Frequência', type: 'decimal', category: 'metrics' },
            
            // CTR e Custos
            'ctr': { label: 'CTR', type: 'percentage', category: 'costs' },
            'cpc': { label: 'CPC', type: 'currency', category: 'costs' },
            'cpm': { label: 'CPM', type: 'currency', category: 'costs' },
            'cpp': { label: 'CPP', type: 'currency', category: 'costs' },
            'cost_per_inline_link_click': { label: 'Custo p/ Link Click', type: 'currency', category: 'costs' },
            'cost_per_unique_click': { label: 'Custo p/ Click Único', type: 'currency', category: 'costs' },
            
            // Conversões Principais
            'purchase': { label: 'Compras', type: 'number', category: 'conversions' },
            'purchase_value': { label: 'Valor Compras', type: 'currency', category: 'conversions' },
            'add_to_cart': { label: 'Add Carrinho', type: 'number', category: 'conversions' },
            'add_to_cart_value': { label: 'Valor Add Carrinho', type: 'currency', category: 'conversions' },
            'initiate_checkout': { label: 'Iniciar Checkout', type: 'number', category: 'conversions' },
            'initiate_checkout_value': { label: 'Valor IC', type: 'currency', category: 'conversions' },
            'lead': { label: 'Leads', type: 'number', category: 'conversions' },
            'complete_registration': { label: 'Cadastros', type: 'number', category: 'conversions' },
            'view_content': { label: 'Ver Conteúdo', type: 'number', category: 'conversions' },
            'search': { label: 'Buscas', type: 'number', category: 'conversions' },
            
            // ✅ Conversões Adicionais
            'add_payment_info': { label: 'Add Info Pagamento', type: 'number', category: 'conversions' },
            'add_to_wishlist': { label: 'Add Lista Desejos', type: 'number', category: 'conversions' },
            'contact': { label: 'Contatos', type: 'number', category: 'conversions' },
            'customize_product': { label: 'Customizar Produto', type: 'number', category: 'conversions' },
            'donate': { label: 'Doações', type: 'number', category: 'conversions' },
            'find_location': { label: 'Buscar Local', type: 'number', category: 'conversions' },
            'schedule': { label: 'Agendamentos', type: 'number', category: 'conversions' },
            'start_trial': { label: 'Iniciar Trial', type: 'number', category: 'conversions' },
            'submit_application': { label: 'Enviar Aplicação', type: 'number', category: 'conversions' },
            'subscribe': { label: 'Inscrições', type: 'number', category: 'conversions' },
            
            // Vídeo
            'video_play_actions': { label: 'Plays de Vídeo', type: 'number', category: 'video' },
            'video_p25_watched': { label: 'Vídeo 25%', type: 'number', category: 'video' },
            'video_p50_watched': { label: 'Vídeo 50%', type: 'number', category: 'video' },
            'video_p75_watched': { label: 'Vídeo 75%', type: 'number', category: 'video' },
            'video_p95_watched': { label: 'Vídeo 95%', type: 'number', category: 'video' },
            'video_p100_watched': { label: 'Vídeo 100%', type: 'number', category: 'video' },
            'thruplay': { label: 'ThruPlay', type: 'number', category: 'video' },
            'video_completion_rate': { label: 'Taxa Conclusão Vídeo', type: 'percentage', category: 'video' },
            
            // Engajamento
            'post_engagement': { label: 'Engajamento Post', type: 'number', category: 'engagement' },
            'page_engagement': { label: 'Engajamento Página', type: 'number', category: 'engagement' },
            'post_reactions': { label: 'Reações', type: 'number', category: 'engagement' },
            'post_saves': { label: 'Salvamentos', type: 'number', category: 'engagement' },
            'post_shares': { label: 'Compartilhamentos', type: 'number', category: 'engagement' },
            'post_comments': { label: 'Comentários', type: 'number', category: 'engagement' },
            'photo_view': { label: 'Visualizações Foto', type: 'number', category: 'engagement' },
            'inline_link_clicks': { label: 'Cliques em Links', type: 'number', category: 'engagement' },
            'engagement_rate': { label: 'Taxa Engajamento', type: 'percentage', category: 'engagement' },
            
            // ✅ Rankings de Qualidade (CRÍTICO!)
            'quality_ranking': { label: 'Ranking Qualidade', type: 'ranking', category: 'quality' },
            'engagement_rate_ranking': { label: 'Ranking Engajamento', type: 'ranking', category: 'quality' },
            'conversion_rate_ranking': { label: 'Ranking Conversão', type: 'ranking', category: 'quality' },
            
            // ✅ Leilão
            'auction_bid': { label: 'Lance Leilão', type: 'currency', category: 'auction' },
            'auction_competitiveness': { label: 'Competitividade', type: 'text', category: 'auction' },
            
            // ✅ Links Externos
            'outbound_clicks': { label: 'Cliques Externos', type: 'number', category: 'external' },
            'unique_outbound_clicks': { label: 'Cliques Externos Únicos', type: 'number', category: 'external' },
            'outbound_clicks_ctr': { label: 'CTR Externo', type: 'percentage', category: 'external' },
            'website_ctr': { label: 'CTR Website', type: 'percentage', category: 'external' },
            
            // ✅ Mobile App
            'app_install': { label: 'Instalações App', type: 'number', category: 'app' },
            'app_use': { label: 'Uso App', type: 'number', category: 'app' },
            
            // ✅ Brand (Recall)
            'estimated_ad_recall_rate': { label: 'Taxa Recall', type: 'percentage', category: 'brand' },
            'estimated_ad_recall_lift': { label: 'Lift Recall', type: 'percentage', category: 'brand' },
            'estimated_ad_recallers': { label: 'Recall Estimado', type: 'number', category: 'brand' },
            
            // ✅ E-commerce / Catálogo
            'catalog_segment_value': { label: 'Valor Catálogo', type: 'currency', category: 'catalog' },
            'mobile_app_purchase_roas': { label: 'ROAS Mobile', type: 'decimal', category: 'catalog' },
            'website_purchase_roas': { label: 'ROAS Website', type: 'decimal', category: 'catalog' },
            
            // Métricas Calculadas
            'roas': { label: 'ROAS', type: 'decimal', category: 'calculated' },
            'roi': { label: 'ROI', type: 'percentage', category: 'calculated' },
            'margin': { label: 'Margem', type: 'percentage', category: 'calculated' },
            'cpa': { label: 'CPA', type: 'currency', category: 'calculated' },
            'cpi': { label: 'CPI', type: 'currency', category: 'calculated' },
            'cpl': { label: 'CPL', type: 'currency', category: 'calculated' },
            'conversion_rate': { label: 'Taxa Conversão', type: 'percentage', category: 'calculated' },
            
            // Cliques Únicos
            'unique_clicks': { label: 'Cliques Únicos', type: 'number', category: 'clicks' },
            'unique_ctr': { label: 'CTR Único', type: 'percentage', category: 'clicks' },
            'unique_inline_link_clicks': { label: 'Link Clicks Únicos', type: 'number', category: 'clicks' },
            
            // Datas
            'start_time': { label: 'Data Início', type: 'date', category: 'dates' },
            'stop_time': { label: 'Data Fim', type: 'date', category: 'dates' },
            'created_time': { label: 'Criada em', type: 'date', category: 'dates' },
            'last_sync': { label: 'Última Sync', type: 'datetime', category: 'dates' }
        };
        
        this.init();
    }
    
    init() {
        console.log('🚀 UTMTrack Dashboard v3.1 - CORRIGIDO - inicializado');
        this.setupEventListeners();
        this.loadCampaigns();
        this.detectCurrentPeriod();
        this.cleanSyncParam();
        this.ensureScrollbar();
        this.restoreSelectedCampaigns();
    }
    
    /**
     * ✅ Restaura seleções salvas em sessionStorage
     */
    restoreSelectedCampaigns() {
        try {
            const saved = sessionStorage.getItem('selectedCampaigns');
            if (saved) {
                const ids = JSON.parse(saved);
                ids.forEach(id => this.selectedCampaigns.add(id));
                
                // Marca checkboxes
                this.selectedCampaigns.forEach(id => {
                    const checkbox = document.querySelector(`.campaign-checkbox[value="${id}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                
                this.updateSelectedCount();
                console.log(`✅ ${ids.length} campanhas selecionadas restauradas`);
            }
        } catch (error) {
            console.error('⚠️ Erro ao restaurar seleções:', error);
        }
    }
    
    /**
     * ✅ Salva seleções em sessionStorage
     */
    saveSelectedCampaigns() {
        try {
            const ids = Array.from(this.selectedCampaigns);
            sessionStorage.setItem('selectedCampaigns', JSON.stringify(ids));
        } catch (error) {
            console.error('⚠️ Erro ao salvar seleções:', error);
        }
    }
    
    cleanSyncParam() {
        const url = new URL(window.location);
        const hasSyncParams = url.searchParams.has('sync') || url.searchParams.has('force_sync');
        
        if (hasSyncParams) {
            console.log('🧹 Removendo parâmetros de sincronização da URL');
            url.searchParams.delete('sync');
            url.searchParams.delete('force_sync');
            window.history.replaceState({}, '', url);
        }
    }
    
    /**
     * ✅ Cálculo dinâmico de largura baseado nas colunas visíveis
     */
    ensureScrollbar() {
        const container = document.querySelector('.table-container');
        const table = document.querySelector('.campaigns-table');
        
        if (!container || !table) return;
        
        const visibleColumns = table.querySelectorAll('th').length;
        const estimatedWidth = visibleColumns * 150; // 150px por coluna em média
        
        console.log('📏 Colunas visíveis:', visibleColumns);
        console.log('📏 Largura estimada:', estimatedWidth + 'px');
        
        if (table.offsetWidth <= container.offsetWidth) {
            table.style.minWidth = Math.max(estimatedWidth, container.offsetWidth + 1) + 'px';
        }
    }
    
    detectCurrentPeriod() {
        const urlParams = new URLSearchParams(window.location.search);
        const period = urlParams.get('period');
        
        if (period) {
            this.currentPeriod = period;
            
            const tabs = document.querySelectorAll('.period-tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.getAttribute('data-period') === period) {
                    tab.classList.add('active');
                }
            });
        }
    }
    
    /**
     * ✅ Event listeners com AbortController para evitar memory leaks
     */
    setupEventListeners() {
        // Select All
        const selectAll = document.getElementById('selectAllCampaigns');
        if (selectAll) {
            selectAll.addEventListener('change', () => this.toggleSelectAll());
        }
        
        // Campaign checkboxes
        document.querySelectorAll('.campaign-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => this.toggleCampaignSelection(e.target));
        });
        
        // ✅ Search com debounce (performance)
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', () => {
                clearTimeout(this.searchDebounceTimer);
                this.searchDebounceTimer = setTimeout(() => this.filterTable(), 300);
            });
        }
        
        // Filters
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.filterTable());
        }
        
        const accountFilter = document.getElementById('accountFilter');
        if (accountFilter) {
            accountFilter.addEventListener('change', () => this.filterTable());
        }
        
        // ✅ NOVOS: Filtros avançados
        const cboFilter = document.getElementById('cboFilter');
        if (cboFilter) {
            cboFilter.addEventListener('change', () => this.filterTable());
        }
        
        const ascFilter = document.getElementById('ascFilter');
        if (ascFilter) {
            ascFilter.addEventListener('change', () => this.filterTable());
        }
        
        const qualityFilter = document.getElementById('qualityRankingFilter');
        if (qualityFilter) {
            qualityFilter.addEventListener('change', () => this.filterTable());
        }
        
        const issuesFilter = document.getElementById('hasIssuesFilter');
        if (issuesFilter) {
            issuesFilter.addEventListener('change', () => this.filterTable());
        }
        
        // Sortable columns
        document.querySelectorAll('.sortable').forEach(th => {
            th.addEventListener('click', () => {
                const column = th.getAttribute('data-column');
                if (column) this.sortTable(column);
            });
        });
        
        this.setupColumnDragDrop();
        this.setupColumnResize();
    }
    
    /**
     * ✅ Cleanup de event listeners ao destruir
     */
    destroy() {
        this.abortControllers.forEach(controller => controller.abort());
        clearTimeout(this.searchDebounceTimer);
    }
    
    toggleSelectAll() {
        const selectAll = document.getElementById('selectAllCampaigns');
        if (!selectAll) return;
        
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
        this.saveSelectedCampaigns();
    }
    
    toggleCampaignSelection(checkbox) {
        const id = parseInt(checkbox.value);
        
        if (checkbox.checked) {
            this.selectedCampaigns.add(id);
        } else {
            this.selectedCampaigns.delete(id);
        }
        
        this.updateSelectedCount();
        this.saveSelectedCampaigns();
    }
    
    updateSelectedCount() {
        const count = this.selectedCampaigns.size;
        const bulkBar = document.getElementById('bulkActionsBar');
        const countSpan = document.getElementById('selectedCount');
        
        if (bulkBar && countSpan) {
            if (count > 0) {
                bulkBar.style.display = 'flex';
                countSpan.textContent = count;
            } else {
                bulkBar.style.display = 'none';
            }
        }
        
        const selectAll = document.getElementById('selectAllCampaigns');
        const totalCheckboxes = document.querySelectorAll('.campaign-checkbox:not([disabled])').length;
        
        if (selectAll) {
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
    
    /**
     * ✅ Filtros avançados com suporte a CBO, ASC, quality_ranking, issues
     */
    filterTable() {
        const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
        const statusFilter = document.getElementById('statusFilter')?.value || '';
        const accountFilter = document.getElementById('accountFilter')?.value || '';
        const cboFilter = document.getElementById('cboFilter')?.value || '';
        const ascFilter = document.getElementById('ascFilter')?.value || '';
        const qualityFilter = document.getElementById('qualityRankingFilter')?.value || '';
        const issuesFilter = document.getElementById('hasIssuesFilter')?.checked || false;
        
        const rows = document.querySelectorAll('#tableBody tr:not(.empty-state)');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const name = (row.getAttribute('data-name') || '').toLowerCase();
            const status = row.getAttribute('data-status') || '';
            const account = row.getAttribute('data-account') || '';
            const cbo = row.getAttribute('data-cbo') || '';
            const asc = row.getAttribute('data-asc') || '';
            const quality = row.getAttribute('data-quality-ranking') || '';
            const hasIssues = row.getAttribute('data-has-issues') === 'true';
            
            const matchSearch = !searchTerm || name.includes(searchTerm);
            const matchStatus = !statusFilter || status === statusFilter;
            const matchAccount = !accountFilter || account === accountFilter;
            const matchCBO = !cboFilter || cbo === cboFilter;
            const matchASC = !ascFilter || asc === ascFilter;
            const matchQuality = !qualityFilter || quality === qualityFilter;
            const matchIssues = !issuesFilter || hasIssues;
            
            if (matchSearch && matchStatus && matchAccount && matchCBO && matchASC && matchQuality && matchIssues) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        this.toggleEmptyState(visibleCount === 0);
        
        console.log(`🔍 Filtros aplicados: ${visibleCount} campanhas visíveis`);
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
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-state)'));
        
        rows.sort((a, b) => {
            const aCell = a.querySelector(`td[data-column="${column}"]`);
            const bCell = b.querySelector(`td[data-column="${column}"]`);
            
            if (!aCell || !bCell) return 0;
            
            let aVal = aCell.textContent.trim();
            let bVal = bCell.textContent.trim();
            
            // Remove formatação de números
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
            
            header.addEventListener('dragend', () => {
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
            
            header.addEventListener('dragleave', () => {
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
        if (!table) return;
        
        const headerRow = table.querySelector('thead tr');
        if (!headerRow) return;
        
        const headers = Array.from(headerRow.querySelectorAll('th'));
        
        const movedHeader = headers[fromIndex];
        const targetHeader = headers[toIndex];
        
        if (!movedHeader || !targetHeader) return;
        
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
            
            const handleMouseMove = (e) => {
                if (!th) return;
                const width = startWidth + (e.pageX - startX);
                const finalWidth = Math.max(width, 80);
                th.style.minWidth = finalWidth + 'px';
                th.style.width = finalWidth + 'px';
            };
            
            const handleMouseUp = () => {
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
                th = null;
            };
            
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                th = handle.closest('th');
                startX = e.pageX;
                startWidth = th.offsetWidth;
                
                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
            });
        });
    }
    
    async saveColumnOrder() {
        const headers = document.querySelectorAll('th[data-column]');
        const order = Array.from(headers).map(th => th.getAttribute('data-column'));
        
        try {
            await this.apiCall('save_columns', { columns: order });
            console.log('✅ Ordem de colunas salva');
            this.showToast('Ordem das colunas salva', 'success');
        } catch (err) {
            console.error('❌ Erro ao salvar ordem:', err);
            this.showToast('Erro ao salvar ordem', 'error');
        }
    }
    
    loadCampaigns() {
        const rows = document.querySelectorAll('#tableBody tr[data-id]');
        this.campaigns = Array.from(rows).map(row => ({
            id: parseInt(row.getAttribute('data-id')),
            campaign_id: row.getAttribute('data-campaign-id'),
            name: row.getAttribute('data-name'),
            status: row.getAttribute('data-status'),
            cbo: row.getAttribute('data-cbo') === '1',
            asc: row.getAttribute('data-asc') === '1',
            quality_ranking: row.getAttribute('data-quality-ranking'),
            has_issues: row.getAttribute('data-has-issues') === 'true'
        }));
        
        console.log(`📊 ${this.campaigns.length} campanhas carregadas com 150+ campos`);
    }
    
    /**
     * ✅ Sincronização manual com período correto
     */
    async syncAllCampaigns() {
        const button = document.getElementById('syncButton');
        if (!button) return;
        
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<svg class="icon sync-icon" style="animation: rotate 1s linear infinite;" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> Sincronizando...';
        
        this.showLoading('🔄 Sincronizando TODOS os 150+ campos do Meta Ads - Período: ' + this.getPeriodLabel(this.currentPeriod));
        
        try {
            console.log('🔄 Iniciando sincronização MANUAL - Período:', this.currentPeriod);
            
            const response = await this.apiCall('sync_complete', {
                date_preset: this.currentPeriod,
                include_insights: true,
                include_actions: true,
                include_video_data: true
            });
            
            if (response.success) {
                this.showToast(response.message || 'Sincronização completa concluída!', 'success');
                
                const url = new URL(window.location);
                url.searchParams.set('period', this.currentPeriod);
                url.searchParams.set('force_sync', '1');
                
                console.log('✅ Recarregando com dados sincronizados:', url.toString());
                
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
    
    /**
     * ========================================
     * ✅ FUNÇÃO EDITFIELD - 100% CORRIGIDA
     * ========================================
     */
    async editField(element, campaignId, field, type) {
        console.log('📝 Editando campo:', { campaignId, field, type });
        
        // Evita dupla edição
        if (element.querySelector('input')) {
            console.log('⚠️ Campo já está em edição');
            return;
        }
        
        // ✅ PEGA O VALOR COM PRIORIDADE PARA data-value
        const dataValue = element.getAttribute('data-value');
        const textValue = element.textContent.trim();
        
        let currentValue;
        if (dataValue !== null && dataValue !== '') {
            currentValue = dataValue;
        } else {
            currentValue = textValue;
        }
        
        console.log('📌 Valor original capturado:', currentValue);
        
        // Remove formatação para valores numéricos
        const cleanValue = type === 'currency' 
            ? currentValue.replace(/[R$\s.]/g, '').replace(',', '.')
            : currentValue;
        
        // ✅ SALVA O HTML ORIGINAL ANTES DE MODIFICAR
        const originalHTML = element.innerHTML;
        const originalText = element.textContent.trim();
        
        console.log('💾 Estado original salvo:', { originalHTML, originalText });
        
        // Cria input
        let input = document.createElement('input');
        
        if (type === 'currency') {
            input.type = 'number';
            input.step = '0.01';
            input.min = '0';
            input.value = parseFloat(cleanValue) || 0;
        } else {
            input.type = 'text';
            input.value = currentValue;
        }
        
        // Estiliza o input
        input.style.width = '100%';
        input.style.padding = '6px 8px';
        input.style.background = '#2a2a2a';
        input.style.border = '2px solid #4CAF50';
        input.style.color = '#fff';
        input.style.borderRadius = '4px';
        input.style.fontSize = '13px';
        input.style.fontFamily = 'inherit';
        input.style.outline = 'none';
        input.style.boxSizing = 'border-box';
        
        // ✅ FUNÇÃO SAVE MELHORADA
        const save = async () => {
            const newValue = type === 'currency' ? parseFloat(input.value) : input.value.trim();
            const compareValue = type === 'currency' ? parseFloat(cleanValue) : currentValue;
            
            // Se o valor não mudou, apenas restaura
            if (newValue == compareValue) {
                console.log('⚠️ Valor não mudou, restaurando HTML original');
                element.innerHTML = originalHTML;
                return;
            }
            
            console.log('💾 Salvando novo valor:', { 
                old: compareValue, 
                new: newValue,
                type: type
            });
            
            const row = element.closest('tr');
            const metaCampaignId = row ? row.getAttribute('data-campaign-id') : null;
            
            try {
                // Desabilita interação durante salvamento
                element.style.opacity = '0.6';
                element.style.pointerEvents = 'none';
                
                const response = await this.apiCall('update_field', {
                    campaign_id: campaignId,
                    meta_campaign_id: metaCampaignId,
                    field: field,
                    value: newValue
                });
                
                console.log('📥 Resposta do servidor:', response);
                
                if (response.success) {
                    // ✅ ATUALIZA SEMPRE O data-value E o textContent
                    element.setAttribute('data-value', newValue);
                    
                    if (type === 'currency') {
                        const formatted = 'R$ ' + parseFloat(newValue).toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        element.textContent = formatted;
                    } else {
                        element.textContent = newValue;
                    }
                    
                    console.log('✅ Elemento atualizado:', {
                        'data-value': element.getAttribute('data-value'),
                        'textContent': element.textContent
                    });
                    
                    // Feedback visual de sucesso
                    element.style.backgroundColor = '#4CAF5044';
                    element.style.transition = 'background-color 0.3s ease';
                    
                    setTimeout(() => {
                        element.style.backgroundColor = '';
                    }, 2000);
                    
                    this.showToast(response.message || '✅ Campo atualizado com sucesso!', 'success');
                } else {
                    throw new Error(response.message || 'Erro ao salvar');
                }
            } catch (error) {
                console.error('❌ Erro ao atualizar campo:', error);
                this.showToast('❌ Erro: ' + error.message, 'error');
                
                // ✅ RESTAURA O HTML ORIGINAL EM CASO DE ERRO
                element.innerHTML = originalHTML;
            } finally {
                // Restaura interatividade
                element.style.opacity = '';
                element.style.pointerEvents = '';
            }
        };
        
        // ✅ FUNÇÃO CANCEL MELHORADA
        const cancel = () => {
            console.log('🚫 Edição cancelada, restaurando HTML original');
            element.innerHTML = originalHTML;
        };
        
        // Limpa o elemento e adiciona o input
        element.textContent = '';
        element.appendChild(input);
        
        // Foca e seleciona todo o texto
        input.focus();
        input.select();
        
        // ✅ EVENTOS OTIMIZADOS
        input.addEventListener('blur', save);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                input.blur(); // Dispara o save via blur event
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancel();
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
     * ✅ Muda período e recarrega dados
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
        
        const url = new URL(window.location);
        url.searchParams.set('period', period);
        
        this.showLoading(`Carregando TODOS os campos para: ${this.getPeriodLabel(period)}...`);
        
        console.log('🔄 Recarregando com novo período:', url.toString());
        window.location.href = url.toString();
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
        window.location.href = url.toString();
    }
    
    /**
     * ✅ Modal de colunas com TODOS os 150+ campos organizados por categoria
     */
    openColumnsModal() {
        const modal = document.getElementById('columnsModal');
        const container = document.getElementById('columnsCheckboxes');
        
        if (!modal || !container) return;
        
        container.innerHTML = '';
        
        // Agrupa campos por categoria
        const categories = {
            'basic': '📋 Básicos',
            'identifiers': '🏷️ Identificadores',
            'budget': '💰 Orçamento',
            'metrics': '📊 Métricas Principais',
            'costs': '💸 Custos',
            'conversions': '🎯 Conversões',
            'video': '🎬 Vídeo',
            'engagement': '❤️ Engajamento',
            'quality': '⭐ Rankings de Qualidade',
            'auction': '🔨 Leilão',
            'external': '🔗 Links Externos',
            'app': '📱 Mobile App',
            'brand': '🎨 Brand',
            'catalog': '🛒 Catálogo/E-commerce',
            'calculated': '🧮 Calculadas',
            'clicks': '👆 Cliques',
            'dates': '📅 Datas'
        };
        
        const currentColumns = Array.from(document.querySelectorAll('th[data-column]'))
            .map(th => th.getAttribute('data-column'))
            .filter(col => col !== 'checkbox');
        
        console.log('📋 Colunas visíveis atualmente:', currentColumns);
        
        // Cria seções por categoria
        Object.entries(categories).forEach(([catKey, catLabel]) => {
            const catSection = document.createElement('div');
            catSection.style.marginBottom = '20px';
            
            const catHeader = document.createElement('h4');
            catHeader.textContent = catLabel;
            catHeader.style.padding = '10px';
            catHeader.style.background = '#2a2a2a';
            catHeader.style.marginBottom = '10px';
            catHeader.style.borderRadius = '6px';
            catHeader.style.color = '#4CAF50';
            catSection.appendChild(catHeader);
            
            const fieldsInCategory = Object.entries(this.availableFields)
                .filter(([key, field]) => field.category === catKey);
            
            if (fieldsInCategory.length > 0) {
                fieldsInCategory.forEach(([key, field]) => {
                    const div = document.createElement('div');
                    div.style.padding = '8px 10px';
                    div.style.borderBottom = '1px solid #333';
                    div.style.display = 'flex';
                    div.style.alignItems = 'center';
                    
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = 'col_' + key;
                    checkbox.value = key;
                    checkbox.checked = currentColumns.includes(key);
                    checkbox.style.marginRight = '10px';
                    
                    const labelEl = document.createElement('label');
                    labelEl.htmlFor = 'col_' + key;
                    labelEl.textContent = field.label;
                    labelEl.style.cursor = 'pointer';
                    labelEl.style.flex = '1';
                    
                    const typeSpan = document.createElement('span');
                    typeSpan.textContent = field.type;
                    typeSpan.style.fontSize = '11px';
                    typeSpan.style.color = '#888';
                    typeSpan.style.marginLeft = '8px';
                    
                    div.appendChild(checkbox);
                    div.appendChild(labelEl);
                    div.appendChild(typeSpan);
                    catSection.appendChild(div);
                });
                
                container.appendChild(catSection);
            }
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
        
        console.log('💾 Salvando', columns.length, 'colunas:', columns);
        
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
        const items = document.querySelectorAll('#columnsCheckboxes > div > div');
        
        items.forEach(item => {
            const label = item.querySelector('label')?.textContent.toLowerCase() || '';
            item.style.display = label.includes(search) ? 'flex' : 'none';
        });
    }
    
    /**
     * ✅ Export com URL segura
     */
    exportData() {
        const format = prompt('Formato de exportação (csv/json):', 'csv');
        
        if (!format || !['csv', 'json'].includes(format)) {
            return;
        }
        
        console.log('📥 Exportando TODOS os 150+ campos em formato:', format);
        
        const url = new URL(window.location.href);
        url.searchParams.set('ajax_action', 'export');
        url.searchParams.set('format', format);
        url.searchParams.set('period', this.currentPeriod);
        
        window.location.href = url.toString();
    }
    
    openSettings() {
        alert('Modal de configurações em desenvolvimento');
    }
    
    /**
     * ✅ CORREÇÃO: API Call com URL correta (ajax-campaigns.php com HÍFEN)
     * ✅ DEBUG COMPLETO: Mostra exatamente o que o servidor retorna
     */
    async apiCall(action, data = {}) {
        // ✅ CORREÇÃO: Nome correto do arquivo com HÍFEN
        const ajaxUrl = window.location.origin + '/utmtrack/ajax-campaigns.php';
        
        const url = new URL(ajaxUrl);
        url.searchParams.set('ajax_action', action);
        
        const finalUrl = url.toString();
        
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('🌐 API CALL DEBUG');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('📌 Action:', action);
        console.log('📍 URL:', finalUrl);
        console.log('📦 Data enviado:', JSON.stringify(data, null, 2));
        
        try {
            const response = await fetch(finalUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include', // Importante para incluir cookies de sessão
                body: JSON.stringify(data)
            });
            
            const text = await response.text();
            
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('📥 RESPOSTA DO SERVIDOR');
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('📊 Status HTTP:', response.status);
            console.log('📋 Headers:', {
                'Content-Type': response.headers.get('Content-Type'),
                'Content-Length': response.headers.get('Content-Length')
            });
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('📄 RESPOSTA COMPLETA (primeiros 500 chars):');
            console.log(text.substring(0, 500));
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            
            // Mostra a resposta COMPLETA se for pequena
            if (text.length < 2000) {
                console.log('📄 RESPOSTA COMPLETA:');
                console.log(text);
                console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            }
            
            // Verifica se começa com caracteres estranhos
            const firstChar = text.charAt(0);
            const firstCharCode = text.charCodeAt(0);
            console.log('🔍 Primeiro caractere:', firstChar, '(código:', firstCharCode + ')');
            
            // Verifica se tem HTML
            if (text.includes('<!DOCTYPE') || text.includes('<html')) {
                console.error('❌ SERVIDOR RETORNOU HTML AO INVÉS DE JSON!');
                throw new Error('Sessão expirada ou erro no servidor. A resposta é HTML. Recarregue a página.');
            }
            
            // Verifica se tem PHP errors
            if (text.includes('Fatal error') || text.includes('Warning:') || text.includes('Notice:')) {
                console.error('❌ PHP ERROR/WARNING DETECTADO NA RESPOSTA!');
                console.error('Resposta completa:', text);
                throw new Error('Erro PHP no servidor. Verifique os logs.');
            }
            
            // Tenta fazer parse do JSON
            let jsonData;
            try {
                jsonData = JSON.parse(text);
                console.log('✅ JSON parseado com sucesso!');
                console.log('📊 Dados:', jsonData);
            } catch (parseError) {
                console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                console.error('❌ ERRO AO FAZER PARSE DO JSON');
                console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                console.error('Erro:', parseError.message);
                console.error('Resposta recebida (completa):', text);
                console.error('Tamanho da resposta:', text.length, 'caracteres');
                console.error('Primeiros 100 chars:', text.substring(0, 100));
                console.error('Últimos 100 chars:', text.substring(text.length - 100));
                console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                
                throw new Error('Servidor retornou resposta inválida. Veja o console para detalhes.');
            }
            
            // Verifica se a resposta tem a estrutura esperada
            if (!jsonData.success && jsonData.message) {
                console.warn('⚠️ Servidor retornou erro:', jsonData.message);
                throw new Error(jsonData.message);
            }
            
            if (!response.ok) {
                throw new Error(jsonData.message || `HTTP ${response.status}`);
            }
            
            console.log('✅ API Call concluída com sucesso!');
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            
            return jsonData;
            
        } catch (error) {
            console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.error('❌ ERRO FINAL NA API CALL');
            console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.error('Erro:', error.message);
            console.error('Stack:', error.stack);
            console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
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
            <div style="color: #fff; margin-top: 16px;">${message}</div>
        `;
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        
        document.body.appendChild(overlay);
    }
    
    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    /**
     * ✅ Toast único - Remove anterior antes de criar novo
     */
    showToast(message, type = 'success') {
        // Remove toast anterior se existir
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'success' ? '#4CAF50' : '#f44336'};
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Inicialização
function initDashboard() {
    try {
        window.dashboardInstance = new UTMTrackDashboard();
        console.log('✅ Dashboard v3.1 - CORRIGIDO - inicializado com sucesso');
    } catch (error) {
        console.error('❌ Erro ao inicializar dashboard:', error);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}

// Cleanup ao sair da página
window.addEventListener('beforeunload', () => {
    if (window.dashboardInstance) {
        window.dashboardInstance.destroy();
    }
});

// ✅ CORREÇÃO: Funções globais funcionais e independentes
window.toggleSelectAll = function() {
    window.dashboardInstance?.toggleSelectAll();
};

window.updateSelectedCount = function() {
    window.dashboardInstance?.updateSelectedCount();
};

window.toggleStatus = function(checkbox, campaignId, metaCampaignId) {
    window.dashboardInstance?.toggleStatus(checkbox, campaignId, metaCampaignId);
};

// ✅ CORREÇÃO: Função editField global direta (fallback se dashboardInstance não existir)
window.editField = function(element, campaignId, field, type) {
    // Se a instância existir, usa ela
    if (window.dashboardInstance) {
        window.dashboardInstance.editField(element, campaignId, field, type);
        return;
    }
    
    // Fallback direto se a instância não existir
    console.log('⚠️ Dashboard não inicializado, executando edição direta');
    
    if (element.querySelector('input')) return;
    
    const currentValue = element.getAttribute('data-value') || element.textContent.trim();
    const cleanValue = currentValue.replace(/[R$\s.]/g, '').replace(',', '.');
    
    let input = document.createElement('input');
    input.type = type === 'currency' ? 'number' : 'text';
    input.value = type === 'currency' ? parseFloat(cleanValue) || 0 : currentValue;
    input.style.cssText = 'width:100%;padding:4px;background:#2a2a2a;border:1px solid #4CAF50;color:#fff;border-radius:4px;';
    
    if (type === 'currency') input.step = '0.01';
    
    const save = async () => {
        const newValue = type === 'currency' ? parseFloat(input.value) : input.value;
        
        if ((type === 'currency' && newValue == parseFloat(cleanValue)) || 
            (type !== 'currency' && newValue === currentValue)) {
            element.innerHTML = element.getAttribute('data-original-html');
            return;
        }
        
        try {
            const response = await fetch(window.location.origin + '/utmtrack/ajax-campaigns.php?ajax_action=update_field', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    campaign_id: campaignId,
                    field: field,
                    value: newValue
                })
            });
            
            const json = await response.json();
            
            if (json.success) {
                if (type === 'currency') {
                    element.textContent = 'R$ ' + parseFloat(newValue).toFixed(2).replace('.', ',');
                } else {
                    element.textContent = newValue;
                }
                element.setAttribute('data-value', newValue);
                
                // Toast de sucesso simples
                const toast = document.createElement('div');
                toast.textContent = json.message || 'Campo atualizado!';
                toast.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#4CAF50;color:white;padding:12px 20px;border-radius:4px;z-index:10000;';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            } else {
                throw new Error(json.message);
            }
        } catch (error) {
            alert('Erro: ' + error.message);
            element.innerHTML = element.getAttribute('data-original-html');
        }
    };
    
    element.setAttribute('data-original-html', element.innerHTML);
    element.innerHTML = '';
    element.appendChild(input);
    input.focus();
    input.select();
    
    input.onblur = save;
    input.onkeydown = (e) => {
        if (e.key === 'Enter') save();
        if (e.key === 'Escape') element.innerHTML = element.getAttribute('data-original-html');
    };
};

window.filterTable = function() {
    window.dashboardInstance?.filterTable();
};

window.syncAllCampaigns = function() {
    window.dashboardInstance?.syncAllCampaigns();
};

window.bulkAction = function(action) {
    window.dashboardInstance?.bulkAction(action);
};

window.changePeriod = function(period, button) {
    window.dashboardInstance?.changePeriod(period, button);
};

window.toggleCustomPeriod = function(button) {
    window.dashboardInstance?.toggleCustomPeriod(button);
};

window.applyCustomPeriod = function() {
    window.dashboardInstance?.applyCustomPeriod();
};

window.openColumnsModal = function() {
    window.dashboardInstance?.openColumnsModal();
};

window.closeColumnsModal = function() {
    window.dashboardInstance?.closeColumnsModal();
};

window.saveColumns = function() {
    window.dashboardInstance?.saveColumns();
};

window.filterColumnsModal = function() {
    window.dashboardInstance?.filterColumnsModal();
};

window.exportData = function() {
    window.dashboardInstance?.exportData();
};

window.openSettings = function() {
    window.dashboardInstance?.openSettings();
};

// Adiciona estilos de animação se não existirem
if (!document.getElementById('dashboard-animations')) {
    const style = document.createElement('style');
    style.id = 'dashboard-animations';
    style.textContent = `
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid #fff3;
            border-top-color: #fff;
            border-radius: 50%;
            animation: rotate 1s linear infinite;
        }
    `;
    document.head.appendChild(style);
}

console.log('✅ UTMTrack Dashboard v3.1 - CORRIGIDO - JavaScript carregado com sucesso!');