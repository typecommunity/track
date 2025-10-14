<style>
/* Força tema escuro - Despesas */
.expenses-container * {
    box-sizing: border-box;
}

.expenses-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
}

.expenses-header-content h1 {
    font-size: 32px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.expenses-header-content h1::before {
    content: '💳';
    font-size: 28px;
}

.expenses-header-content p {
    color: #94a3b8;
    margin: 0;
    font-size: 14px;
}

.expenses-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.expense-stat-card {
    background: #2d3748;
    border: 1px solid #374151;
    border-radius: 12px;
    padding: 24px;
    transition: all 0.2s;
}

.expense-stat-card:hover {
    border-color: #3b82f6;
    transform: translateY(-2px);
}

.expense-stat-label {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.expense-stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #e2e8f0;
}

.expense-stat-value.negative {
    color: #ef4444;
}

.expenses-card {
    background: #1e2738;
    border: 1px solid #374151;
    border-radius: 12px;
    overflow: hidden;
}

.expenses-card-header {
    padding: 24px 28px;
    border-bottom: 1px solid #374151;
}

.expenses-card-title {
    font-size: 18px;
    font-weight: 600;
    color: #e2e8f0;
    margin: 0;
}

.expenses-filters-bar {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 120px;
    gap: 16px;
    padding: 24px 28px;
    background: #2d3748;
    border-bottom: 1px solid #374151;
}

.expenses-filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.expenses-filter-label {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.expenses-filter-input,
.expenses-filter-select {
    padding: 10px 14px;
    border: 1px solid #374151;
    border-radius: 8px;
    font-size: 14px;
    background: #1e2738;
    color: #e2e8f0;
}

.expenses-filter-input:focus,
.expenses-filter-select:focus {
    outline: none;
    border-color: #3b82f6;
}

.expenses-table {
    width: 100%;
    border-collapse: collapse;
}

.expenses-table thead {
    background: #2d3748;
}

.expenses-table th {
    padding: 14px 28px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #374151;
}

.expenses-table td {
    padding: 20px 28px;
    border-bottom: 1px solid #374151;
    font-size: 14px;
    color: #e2e8f0;
}

.expenses-table tbody tr {
    transition: background 0.2s;
}

.expenses-table tbody tr:hover {
    background: rgba(59, 130, 246, 0.05);
}

.expense-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.expense-badge-unico {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
}

.expense-badge-recorrente {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

.expense-empty {
    text-align: center;
    padding: 60px 20px;
}

.expense-empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.expense-empty-title {
    font-size: 20px;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 10px;
}

.expense-empty-text {
    font-size: 14px;
    color: #94a3b8;
    margin-bottom: 20px;
}

.expense-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    animation: fadeIn 0.2s;
}

.expense-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.expense-modal-content {
    background: #1e2738;
    border: 1px solid #374151;
    border-radius: 12px;
    padding: 30px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.expense-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.expense-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
}

.expense-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #94a3b8;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.expense-modal-close:hover {
    color: #e2e8f0;
}

.expense-form-group {
    margin-bottom: 20px;
}

.expense-form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 8px;
}

.expense-form-input,
.expense-form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #374151;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: #2d3748;
    color: #e2e8f0;
}

.expense-form-input:focus,
.expense-form-select:focus {
    outline: none;
    border-color: #3b82f6;
}

.expense-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 25px;
}

/* ============= MODAL DE ALERTAS CUSTOMIZADO ============= */
.alert-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.75);
    z-index: 99999;
    animation: fadeIn 0.2s ease-out;
}

.alert-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.alert-modal {
    background: #1e2738;
    border: 1px solid #374151;
    border-radius: 16px;
    padding: 0;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    animation: slideUp 0.3s ease-out;
    overflow: hidden;
}

.alert-header {
    padding: 24px 28px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border-bottom: 1px solid #374151;
}

.alert-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.alert-icon.success {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.alert-icon.error {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.alert-icon.warning {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

.alert-icon.info {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.alert-title {
    font-size: 20px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
}

.alert-body {
    padding: 24px 28px;
}

.alert-message {
    font-size: 15px;
    line-height: 1.6;
    color: #94a3b8;
    margin: 0;
}

.alert-footer {
    padding: 20px 28px;
    background: rgba(30, 41, 59, 0.5);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.alert-btn {
    padding: 10px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    min-width: 100px;
}

.alert-btn-primary {
    background: #3b82f6;
    color: white;
}

.alert-btn-primary:hover {
    background: #2563eb;
}

.alert-btn-secondary {
    background: #374151;
    color: #e2e8f0;
}

.alert-btn-secondary:hover {
    background: #4b5563;
}

.alert-btn-danger {
    background: #ef4444;
    color: white;
}

.alert-btn-danger:hover {
    background: #dc2626;
}

/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 100000;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 400px;
}

.toast {
    background: #1e2738;
    border: 1px solid #374151;
    border-left: 4px solid #3b82f6;
    border-radius: 8px;
    padding: 16px 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: slideInRight 0.3s ease-out;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
}

.toast.success { border-left-color: #22c55e; }
.toast.error { border-left-color: #ef4444; }
.toast.warning { border-left-color: #fbbf24; }

.toast-icon { font-size: 20px; }
.toast.success .toast-icon { color: #22c55e; }
.toast.error .toast-icon { color: #ef4444; }
.toast.warning .toast-icon { color: #fbbf24; }
.toast.info .toast-icon { color: #3b82f6; }

.toast-message {
    flex: 1;
    color: #e2e8f0;
    font-size: 14px;
    line-height: 1.5;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

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
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

@media (max-width: 768px) {
    .expenses-stats {
        grid-template-columns: 1fr;
    }
    
    .expenses-filters-bar {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Container de Toasts -->
<div class="toast-container" id="toastContainer"></div>

<!-- Modal de Alerta Customizado -->
<div class="alert-overlay" id="alertOverlay">
    <div class="alert-modal">
        <div class="alert-header">
            <div class="alert-icon" id="alertIcon">✓</div>
            <h3 class="alert-title" id="alertTitle">Título</h3>
        </div>
        <div class="alert-body">
            <p class="alert-message" id="alertMessage">Mensagem</p>
        </div>
        <div class="alert-footer" id="alertFooter">
            <button class="alert-btn alert-btn-primary" id="alertOkBtn">OK</button>
        </div>
    </div>
</div>

<div class="expenses-container">
    <div class="expenses-header">
        <div class="expenses-header-content">
            <h1>Despesas</h1>
            <p>Use essa tela para adicionar despesas personalizadas.</p>
        </div>
        <button class="btn btn-primary" onclick="openExpenseModal()">
            + Adicionar gasto
        </button>
    </div>
    
    <!-- Stats Cards -->
    <div class="expenses-stats">
        <div class="expense-stat-card">
            <div class="expense-stat-label">Total de Despesas</div>
            <div class="expense-stat-value negative">R$ <?= number_format($totals['total'], 2, ',', '.') ?></div>
        </div>
        <div class="expense-stat-card">
            <div class="expense-stat-label">Despesas Únicas</div>
            <div class="expense-stat-value">R$ <?= number_format($totals['unico'], 2, ',', '.') ?></div>
        </div>
        <div class="expense-stat-card">
            <div class="expense-stat-label">Despesas Recorrentes</div>
            <div class="expense-stat-value">R$ <?= number_format($totals['recorrente'], 2, ',', '.') ?></div>
        </div>
        <div class="expense-stat-card">
            <div class="expense-stat-label">Quantidade</div>
            <div class="expense-stat-value"><?= $totals['count'] ?></div>
        </div>
    </div>
    
    <!-- Main Card -->
    <div class="expenses-card">
        <div class="expenses-card-header">
            <h2 class="expenses-card-title">Lista de Despesas</h2>
        </div>
        
        <!-- Filters -->
        <form class="expenses-filters-bar" method="GET" action="index.php">
            <input type="hidden" name="page" value="despesas">
            
            <div class="expenses-filter-group">
                <label class="expenses-filter-label">Descrição</label>
                <input type="text" name="description" class="expenses-filter-input" placeholder="Descrição">
            </div>
            
            <div class="expenses-filter-group">
                <label class="expenses-filter-label">Categoria</label>
                <select name="category" class="expenses-filter-select">
                    <option value="">Qualquer</option>
                    <option value="Tráfego" <?= ($selectedCategory ?? '') === 'Tráfego' ? 'selected' : '' ?>>Tráfego</option>
                    <option value="Ferramentas" <?= ($selectedCategory ?? '') === 'Ferramentas' ? 'selected' : '' ?>>Ferramentas</option>
                    <option value="Funcionários" <?= ($selectedCategory ?? '') === 'Funcionários' ? 'selected' : '' ?>>Funcionários</option>
                    <option value="Contabilidade" <?= ($selectedCategory ?? '') === 'Contabilidade' ? 'selected' : '' ?>>Contabilidade</option>
                    <option value="Outros" <?= ($selectedCategory ?? '') === 'Outros' ? 'selected' : '' ?>>Outros</option>
                </select>
            </div>
            
            <div class="expenses-filter-group">
                <label class="expenses-filter-label">Período</label>
                <select name="period" class="expenses-filter-select">
                    <option value="month" <?= ($period ?? 'month') === 'month' ? 'selected' : '' ?>>Esse mês</option>
                    <option value="week" <?= ($period ?? '') === 'week' ? 'selected' : '' ?>>Últimos 7 dias</option>
                    <option value="today" <?= ($period ?? '') === 'today' ? 'selected' : '' ?>>Hoje</option>
                    <option value="year" <?= ($period ?? '') === 'year' ? 'selected' : '' ?>>Esse ano</option>
                    <option value="all" <?= ($period ?? '') === 'all' ? 'selected' : '' ?>>Todas</option>
                </select>
            </div>
            
            <div class="expenses-filter-group">
                <label class="expenses-filter-label" style="opacity: 0;">.</label>
                <button type="submit" class="btn btn-secondary">Filtrar</button>
            </div>
        </form>
        
        <!-- Table -->
        <?php if (empty($expenses)): ?>
        <div class="expense-empty">
            <div class="expense-empty-icon">📊</div>
            <div class="expense-empty-title">Nenhuma despesa cadastrada</div>
            <div class="expense-empty-text">Comece adicionando sua primeira despesa personalizada</div>
            <button class="btn btn-primary" onclick="openExpenseModal()">+ Adicionar Despesa</button>
        </div>
        <?php else: ?>
        <table class="expenses-table">
            <thead>
                <tr>
                    <th>DATA</th>
                    <th>TIPO</th>
                    <th>CATEGORIA</th>
                    <th>DESCRIÇÃO</th>
                    <th>VALOR</th>
                    <th>MAIS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($expense['expense_date'])) ?></td>
                    <td>
                        <span class="expense-badge expense-badge-<?= $expense['expense_type'] ?>">
                            <?= $expense['expense_type'] === 'unico' ? 'Único' : 'Recorrente' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($expense['category'] ?? '-') ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($expense['description']) ?></td>
                    <td style="font-weight: 700; color: #ef4444;">R$ <?= number_format($expense['amount'], 2, ',', '.') ?></td>
                    <td>
                        <button class="btn btn-secondary" style="padding: 8px 16px; margin-right: 5px; font-size: 13px;" 
                                onclick="editExpense(<?= $expense['id'] ?>)">Editar</button>
                        <button class="btn btn-danger" style="padding: 8px 16px; font-size: 13px;" 
                                onclick="confirmDeleteExpense(<?= $expense['id'] ?>)">Excluir</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Adicionar/Editar Despesa -->
<div id="expenseModal" class="expense-modal">
    <div class="expense-modal-content">
        <div class="expense-modal-header">
            <h2 class="expense-modal-title" id="modalTitle">Adicionar Gasto</h2>
            <button class="expense-modal-close" onclick="closeExpenseModal()">&times;</button>
        </div>
        
        <form id="expenseForm">
            <input type="hidden" id="expense_id" name="id">
            
            <div class="expense-form-group">
                <label class="expense-form-label">Descrição</label>
                <input type="text" id="description" name="description" class="expense-form-input" 
                       placeholder="Ex: Servidor AWS" required>
            </div>
            
            <div class="expense-form-group">
                <label class="expense-form-label">Tipo de Gasto</label>
                <select id="expense_type" name="expense_type" class="expense-form-select" required>
                    <option value="unico">Único</option>
                    <option value="recorrente">Recorrente</option>
                </select>
            </div>
            
            <div class="expense-form-group">
                <label class="expense-form-label">Categoria</label>
                <select id="category" name="category" class="expense-form-select" required>
                    <option value="">Selecione uma opção</option>
                    <option value="Tráfego">Tráfego</option>
                    <option value="Ferramentas">Ferramentas</option>
                    <option value="Funcionários">Funcionários</option>
                    <option value="Contabilidade">Contabilidade</option>
                    <option value="Outros">Outros</option>
                </select>
            </div>
            
            <div class="expense-form-group">
                <label class="expense-form-label">Data</label>
                <input type="date" id="expense_date" name="expense_date" class="expense-form-input" 
                       value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="expense-form-group">
                <label class="expense-form-label">Valor</label>
                <input type="number" id="amount" name="amount" class="expense-form-input" 
                       placeholder="0,00" step="0.01" min="0.01" required>
            </div>
            
            <div class="expense-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeExpenseModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Adicionar Gasto</button>
            </div>
        </form>
    </div>
</div>

<script>
// ============= SISTEMA DE ALERTAS CUSTOMIZADO =============
const AlertSystem = {
    overlay: null,
    
    init() {
        this.overlay = document.getElementById('alertOverlay');
        
        this.overlay?.addEventListener('click', (e) => {
            if (e.target === this.overlay) this.close();
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.overlay?.classList.contains('active')) {
                this.close();
            }
        });
    },
    
    show(message, type = 'info', title = null, buttons = null) {
        const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
        const titles = { success: 'Sucesso', error: 'Erro', warning: 'Atenção', info: 'Informação' };
        
        const iconElement = document.getElementById('alertIcon');
        const titleElement = document.getElementById('alertTitle');
        const messageElement = document.getElementById('alertMessage');
        const footerElement = document.getElementById('alertFooter');
        
        iconElement.textContent = icons[type] || icons.info;
        iconElement.className = `alert-icon ${type}`;
        titleElement.textContent = title || titles[type];
        messageElement.textContent = message;
        
        if (buttons) {
            footerElement.innerHTML = '';
            buttons.forEach(btn => {
                const button = document.createElement('button');
                button.className = `alert-btn alert-btn-${btn.style || 'primary'}`;
                button.textContent = btn.text;
                button.onclick = () => {
                    if (btn.onClick) btn.onClick();
                    this.close();
                };
                footerElement.appendChild(button);
            });
        } else {
            footerElement.innerHTML = '<button class="alert-btn alert-btn-primary" onclick="AlertSystem.close()">OK</button>';
        }
        
        this.overlay.classList.add('active');
    },
    
    close() {
        this.overlay?.classList.remove('active');
    }
};

const ToastSystem = {
    container: null,
    
    init() {
        this.container = document.getElementById('toastContainer');
    },
    
    show(message, type = 'info', duration = 3000) {
        const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type]}</span>
            <span class="toast-message">${message}</span>
        `;
        
        this.container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
};

function showAlert(message, type = 'info', title = null) {
    AlertSystem.show(message, type, title);
}

function showConfirm(message, onConfirm, onCancel = null, title = 'Confirmação') {
    AlertSystem.show(message, 'warning', title, [
        { text: 'Cancelar', style: 'secondary', onClick: onCancel },
        { text: 'Confirmar', style: 'danger', onClick: onConfirm }
    ]);
}

function showToast(message, type = 'info', duration = 3000) {
    ToastSystem.show(message, type, duration);
}

// Inicializa os sistemas
AlertSystem.init();
ToastSystem.init();

// ============= GERENCIAMENTO DE DESPESAS =============
let isEditing = false;

function openExpenseModal() {
    isEditing = false;
    document.getElementById('modalTitle').textContent = 'Adicionar Gasto';
    document.getElementById('submitBtn').textContent = 'Adicionar Gasto';
    document.getElementById('expenseForm').reset();
    document.getElementById('expense_date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('expenseModal').classList.add('active');
}

function closeExpenseModal() {
    document.getElementById('expenseModal').classList.remove('active');
}

function editExpense(id) {
    isEditing = true;
    fetch(`index.php?page=expense-get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const expense = data.expense;
                document.getElementById('expense_id').value = expense.id;
                document.getElementById('description').value = expense.description;
                document.getElementById('category').value = expense.category || '';
                document.getElementById('amount').value = expense.amount;
                document.getElementById('expense_type').value = expense.expense_type;
                document.getElementById('expense_date').value = expense.expense_date;
                
                document.getElementById('modalTitle').textContent = 'Editar Despesa';
                document.getElementById('submitBtn').textContent = 'Salvar Alterações';
                document.getElementById('expenseModal').classList.add('active');
            } else {
                showAlert(data.message || 'Erro ao carregar despesa', 'error');
            }
        })
        .catch(err => {
            console.error('Erro:', err);
            showAlert('Erro ao processar requisição', 'error');
        });
}

function confirmDeleteExpense(id) {
    showConfirm(
        'Deseja realmente excluir esta despesa? Esta ação não pode ser desfeita.',
        () => deleteExpense(id),
        null,
        'Confirmar Exclusão'
    );
}

function deleteExpense(id) {
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('index.php?page=expense-delete', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Erro ao excluir despesa', 'error');
        }
    })
    .catch(err => {
        console.error('Erro:', err);
        showAlert('Erro ao processar requisição', 'error');
    });
}

document.getElementById('expenseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const action = isEditing ? 'expense-update' : 'expense-store';
    
    fetch(`index.php?page=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeExpenseModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Erro ao salvar despesa', 'error');
        }
    })
    .catch(err => {
        console.error('Erro:', err);
        showAlert('Erro ao processar requisição', 'error');
    });
});

// Fechar modal de despesa ao clicar fora
document.getElementById('expenseModal').addEventListener('click', function(e) {
    if (e.target === this) closeExpenseModal();
});

// Fechar modais com tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeExpenseModal();
    }
});
</script>