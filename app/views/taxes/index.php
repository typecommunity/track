<?php
/**
 * View: Taxas e Impostos
 * Arquivo: app/views/taxes/index.php
 */
?>

<style>
/* Taxas e Impostos - Tema Escuro */
.taxes-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.tax-section {
    background: #1e2738;
    border: 1px solid #374151;
    border-radius: 12px;
    overflow: hidden;
}

.tax-section-header {
    padding: 24px 28px;
    border-bottom: 1px solid #374151;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tax-section-title {
    font-size: 18px;
    font-weight: 600;
    color: #e2e8f0;
    margin: 0;
}

.tax-section-content {
    padding: 24px 28px;
}

.tax-section-description {
    color: #94a3b8;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 16px;
}

.tax-item {
    background: #2d3748;
    border: 1px solid #374151;
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 12px;
}

.tax-item:hover {
    border-color: #3b82f6;
}

.tax-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.tax-item-name {
    font-size: 16px;
    font-weight: 600;
    color: #e2e8f0;
}

.tax-item-menu {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px 8px;
    font-size: 20px;
}

.tax-item-menu:hover {
    color: #e2e8f0;
}

.tax-item-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 13px;
    color: #94a3b8;
}

.products-grid {
    display: grid;
    gap: 12px;
}

.product-cost-item {
    background: #2d3748;
    border: 1px solid #374151;
    border-radius: 8px;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-cost-name {
    font-size: 14px;
    font-weight: 500;
    color: #e2e8f0;
}

.product-cost-value {
    font-size: 14px;
    font-weight: 600;
    color: #94a3b8;
}

.tax-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
}

.tax-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.tax-modal-content {
    background: #1e2738;
    border: 1px solid #374151;
    border-radius: 12px;
    padding: 30px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.tax-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.tax-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
}

.tax-modal-close {
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

.tax-modal-close:hover {
    color: #e2e8f0;
}

.tax-info-box {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #94a3b8;
    line-height: 1.5;
}

.tax-form-group {
    margin-bottom: 20px;
}

.tax-form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 8px;
}

.tax-form-help {
    width: 16px;
    height: 16px;
    background: rgba(148, 163, 184, 0.2);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #94a3b8;
    cursor: help;
}

.tax-form-input,
.tax-form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #374151;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: #2d3748;
    color: #e2e8f0;
}

.tax-form-input:focus,
.tax-form-select:focus {
    outline: none;
    border-color: #3b82f6;
}

.tax-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 25px;
}

.products-full-width {
    grid-column: 1 / -1;
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #374151;
    color: #e2e8f0;
}

.btn-secondary:hover {
    background: #4b5563;
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

/* Animações */
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

@media (max-width: 1024px) {
    .taxes-container {
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

<div class="taxes-container">
    <!-- IMPOSTOS -->
    <div class="tax-section">
        <div class="tax-section-header">
            <h2 class="tax-section-title">Imposto</h2>
            <button class="btn btn-secondary" onclick="openImpostoModal()">
                Adicionar Imposto
            </button>
        </div>
        <div class="tax-section-content">
            <p class="tax-section-description">
                Configure o imposto dos seus produtos:
            </p>
            
            <?php if (empty($impostos)): ?>
                <p style="color: #64748b; text-align: center; padding: 20px 0;">
                    Nenhum imposto cadastrado
                </p>
            <?php else: ?>
                <?php foreach ($impostos as $imposto): ?>
                <div class="tax-item">
                    <div class="tax-item-header">
                        <div class="tax-item-name">Imposto</div>
                        <button class="tax-item-menu" onclick="showImpostoMenu(<?= $imposto['id'] ?>)">⋮</button>
                    </div>
                    <div class="tax-item-details">
                        <div>Alíquota: <?= number_format($imposto['rate'], 2, ',', '.') ?>%</div>
                        <div>Regra: <?= $imposto['calculation_rule'] === 'revenue' ? 'Valor de Faturamento' : 'Valor de Comissão' ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- TAXAS -->
    <div class="tax-section">
        <div class="tax-section-header">
            <h2 class="tax-section-title">Taxas</h2>
            <button class="btn btn-secondary" onclick="openTaxModal()">
                Adicionar Taxa
            </button>
        </div>
        <div class="tax-section-content">
            <p class="tax-section-description">
                Configure taxas adicionais:
            </p>
            
            <?php if (empty($taxes)): ?>
                <p style="color: #64748b; text-align: center; padding: 20px 0;">
                    Nenhuma taxa cadastrada
                </p>
            <?php else: ?>
                <?php foreach ($taxes as $tax): ?>
                <div class="tax-item">
                    <div class="tax-item-header">
                        <div class="tax-item-name"><?= htmlspecialchars($tax['name']) ?></div>
                        <button class="tax-item-menu" onclick="showTaxMenu(<?= $tax['id'] ?>)">⋮</button>
                    </div>
                    <div class="tax-item-details">
                        <div>Taxa: <?= number_format($tax['rate'], 2, ',', '.') ?><?= $tax['type'] === 'percentage' ? '%' : '' ?></div>
                        <div>Forma de Pagamento: <?= $tax['payment_method'] === 'all' ? 'Todas' : ucfirst(str_replace('_', ' ', $tax['payment_method'])) ?></div>
                        <div>Regra: <?= $tax['calculation_rule'] === 'revenue' ? 'Valor de Faturamento' : 'Valor de Comissão' ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- CUSTO DE PRODUTOS -->
<div class="tax-section products-full-width">
    <div class="tax-section-header">
        <h2 class="tax-section-title">Custo de Produtos</h2>
        <button class="btn btn-secondary" onclick="openCostsModal()">
            Editar Custos
        </button>
    </div>
    <div class="tax-section-content">
        <p class="tax-section-description">
            Configure o custo dos seus produtos:
        </p>
        
        <?php if (!empty($products)): ?>
            <div style="background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;">
                <div style="font-weight: 600; color: #fbbf24; margin-bottom: 4px;">Sincronização dos produtos</div>
                <div style="font-size: 13px; color: #94a3b8; line-height: 1.5;">
                    Para que os produtos apareçam para seleção, é necessário que pelo menos uma venda ou processamento de pedido tenha sido realizado com o devido produto.
                </div>
            </div>
        <?php endif; ?>
        
        <div class="products-grid">
            <?php if (empty($products)): ?>
                <p style="color: #64748b; text-align: center; padding: 20px 0;">
                    Nenhum produto disponível
                </p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                <div class="product-cost-item">
                    <div class="product-cost-name"><?= htmlspecialchars($product['name']) ?></div>
                    <div class="product-cost-value">R$ <?= number_format($product['cost'], 2, ',', '.') ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Adicionar Imposto -->
<div id="impostoModal" class="tax-modal">
    <div class="tax-modal-content" style="max-width: 480px;">
        <div class="tax-modal-header">
            <h2 class="tax-modal-title">Adicionar Imposto</h2>
            <button class="tax-modal-close" onclick="closeImpostoModal()">&times;</button>
        </div>
        
        <div class="tax-info-box">
            Selecione 'Valor de Faturamento' para aplicar a taxa baseada no valor total da venda.
            Selecione 'Valor de Comissão' para aplicar a taxa baseada apenas no valor recebido.
        </div>
        
        <form id="impostoForm">
            <input type="hidden" id="imposto_id" name="id">
            
            <div class="tax-form-group">
                <label class="tax-form-label">
                    Regra de Cálculo
                    <span class="tax-form-help" title="Define como o imposto será calculado">?</span>
                </label>
                <select id="imposto_calculation_rule" name="calculation_rule" class="tax-form-select" required>
                    <option value="revenue">Valor de Faturamento</option>
                    <option value="commission">Valor de Comissão</option>
                </select>
            </div>
            
            <div class="tax-form-group">
                <label class="tax-form-label">Alíquota</label>
                <input type="number" id="imposto_rate" name="rate" class="tax-form-input" 
                       placeholder="0,00%" step="0.01" min="0.01" required>
            </div>
            
            <div class="tax-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeImpostoModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="impostoSubmitBtn">Adicionar Imposto</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Adicionar Taxa -->
<div id="taxModal" class="tax-modal">
    <div class="tax-modal-content">
        <div class="tax-modal-header">
            <h2 class="tax-modal-title">Adicionar Taxa</h2>
            <button class="tax-modal-close" onclick="closeTaxModal()">&times;</button>
        </div>
        
        <div class="tax-info-box">
            Selecione 'Valor de Faturamento' para aplicar a taxa baseada no valor total da venda.
            Selecione 'Valor de Comissão' para aplicar a taxa baseada apenas no valor recebido.
        </div>
        
        <form id="taxForm">
            <input type="hidden" id="tax_id" name="id">
            
            <div class="tax-form-group">
                <label class="tax-form-label">Nome</label>
                <input type="text" id="tax_name" name="name" class="tax-form-input" 
                       placeholder="Taxa adicional" required>
            </div>
            
            <div class="tax-form-group">
                <label class="tax-form-label">
                    Regra de Cálculo
                    <span class="tax-form-help" title="Define como a taxa será calculada">?</span>
                </label>
                <select id="calculation_rule" name="calculation_rule" class="tax-form-select" required>
                    <option value="revenue">Valor de Faturamento</option>
                    <option value="commission">Valor de Comissão</option>
                </select>
            </div>
            
            <div class="tax-form-group">
                <label class="tax-form-label">
                    Forma de Pagamento
                    <span class="tax-form-help" title="Selecione em quais formas de pagamento a taxa será aplicada">?</span>
                </label>
                <select id="payment_method" name="payment_method" class="tax-form-select" required>
                    <option value="all">Todas</option>
                    <option value="pix">PIX</option>
                    <option value="credit_card">Cartão de Crédito</option>
                    <option value="boleto">Boleto</option>
                </select>
            </div>
            
            <div class="tax-form-group">
                <label class="tax-form-label">Tipo de Taxa</label>
                <select id="tax_type" name="type" class="tax-form-select" required>
                    <option value="percentage">Porcentagem</option>
                    <option value="fixed">Valor Fixo</option>
                </select>
            </div>
            
            <div class="tax-form-group">
                <label class="tax-form-label">Taxa por venda</label>
                <input type="number" id="tax_rate" name="rate" class="tax-form-input" 
                       placeholder="0,00" step="0.01" min="0.01" required>
            </div>
            
            <div class="tax-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeTaxModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Adicionar Taxa</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Custos -->
<div id="costsModal" class="tax-modal">
    <div class="tax-modal-content" style="max-width: 600px;">
        <div class="tax-modal-header">
            <h2 class="tax-modal-title">Editar Custos de Produtos</h2>
            <button class="tax-modal-close" onclick="closeCostsModal()">&times;</button>
        </div>
        
        <form id="costsForm">
            <?php foreach ($products as $product): ?>
            <div class="tax-form-group">
                <label class="tax-form-label"><?= htmlspecialchars($product['name']) ?></label>
                <input type="hidden" name="products[<?= $product['id'] ?>][id]" value="<?= $product['id'] ?>">
                <input type="number" 
                       name="products[<?= $product['id'] ?>][cost]" 
                       class="tax-form-input" 
                       value="<?= $product['cost'] ?>"
                       step="0.01" 
                       min="0"
                       placeholder="0,00">
            </div>
            <?php endforeach; ?>
            
            <div class="tax-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCostsModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Custos</button>
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

// ============= VARIÁVEIS GLOBAIS =============
let isEditingImposto = false;
let isEditingTax = false;

// ============= IMPOSTOS =============

function openImpostoModal() {
    isEditingImposto = false;
    document.getElementById('impostoForm').reset();
    document.getElementById('imposto_id').value = '';
    document.getElementById('impostoSubmitBtn').textContent = 'Adicionar Imposto';
    document.getElementById('impostoModal').classList.add('active');
}

function closeImpostoModal() {
    document.getElementById('impostoModal').classList.remove('active');
}

function showImpostoMenu(id) {
    showConfirm(
        'Escolha a ação que deseja realizar:',
        () => editImposto(id),
        null,
        'Opções do Imposto'
    );
    
    // Customiza os botões do modal
    const footerElement = document.getElementById('alertFooter');
    footerElement.innerHTML = '';
    
    const btnEdit = document.createElement('button');
    btnEdit.className = 'alert-btn alert-btn-primary';
    btnEdit.textContent = 'Editar';
    btnEdit.onclick = () => {
        AlertSystem.close();
        editImposto(id);
    };
    
    const btnDelete = document.createElement('button');
    btnDelete.className = 'alert-btn alert-btn-danger';
    btnDelete.textContent = 'Excluir';
    btnDelete.onclick = () => {
        AlertSystem.close();
        showConfirm(
            'Deseja realmente excluir este imposto? Esta ação não pode ser desfeita.',
            () => deleteImposto(id)
        );
    };
    
    const btnCancel = document.createElement('button');
    btnCancel.className = 'alert-btn alert-btn-secondary';
    btnCancel.textContent = 'Cancelar';
    btnCancel.onclick = () => AlertSystem.close();
    
    footerElement.appendChild(btnCancel);
    footerElement.appendChild(btnEdit);
    footerElement.appendChild(btnDelete);
}

function editImposto(id) {
    isEditingImposto = true;
    
    fetch(`index.php?page=imposto-get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const imposto = data.imposto;
                document.getElementById('imposto_id').value = imposto.id;
                document.getElementById('imposto_calculation_rule').value = imposto.calculation_rule;
                document.getElementById('imposto_rate').value = imposto.rate;
                document.getElementById('impostoSubmitBtn').textContent = 'Salvar Alterações';
                document.getElementById('impostoModal').classList.add('active');
            } else {
                showAlert(data.message || 'Erro ao carregar imposto', 'error');
            }
        })
        .catch(err => {
            console.error('Erro:', err);
            showAlert('Erro ao processar requisição', 'error');
        });
}

function deleteImposto(id) {
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('index.php?page=imposto-delete', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Erro ao excluir imposto', 'error');
        }
    })
    .catch(err => {
        console.error('Erro:', err);
        showAlert('Erro ao processar requisição', 'error');
    });
}

document.getElementById('impostoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const action = isEditingImposto ? 'imposto-update' : 'imposto-store';
    
    fetch(`index.php?page=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeImpostoModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Erro ao salvar imposto', 'error');
        }
    })
    .catch(err => {
        console.error('Erro:', err);
        showAlert('Erro ao processar requisição', 'error');
    });
});

// ============= TAXAS =============

function openTaxModal() {
    isEditingTax = false;
    document.getElementById('taxForm').reset();
    document.getElementById('tax_id').value = '';
    document.getElementById('taxModal').classList.add('active');
}

function closeTaxModal() {
    document.getElementById('taxModal').classList.remove('active');
}

function showTaxMenu(id) {
    showConfirm(
        'Escolha a ação que deseja realizar:',
        () => editTax(id),
        null,
        'Opções da Taxa'
    );
    
    // Customiza os botões do modal
    const footerElement = document.getElementById('alertFooter');
    footerElement.innerHTML = '';
    
    const btnEdit = document.createElement('button');
    btnEdit.className = 'alert-btn alert-btn-primary';
    btnEdit.textContent = 'Editar';
    btnEdit.onclick = () => {
        AlertSystem.close();
        editTax(id);
    };
    
    const btnDelete = document.createElement('button');
    btnDelete.className = 'alert-btn alert-btn-danger';
    btnDelete.textContent = 'Excluir';
    btnDelete.onclick = () => {
        AlertSystem.close();
        showConfirm(
            'Deseja realmente excluir esta taxa? Esta ação não pode ser desfeita.',
            () => deleteTax(id)
        );
    };
    
    const btnCancel = document.createElement('button');
    btnCancel.className = 'alert-btn alert-btn-secondary';
    btnCancel.textContent = 'Cancelar';
    btnCancel.onclick = () => AlertSystem.close();
    
    footerElement.appendChild(btnCancel);
    footerElement.appendChild(btnEdit);
    footerElement.appendChild(btnDelete);
}

function editTax(id) {
    isEditingTax = true;
    
    fetch(`index.php?page=tax-get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const tax = data.tax;
                document.getElementById('tax_id').value = tax.id;
                document.getElementById('tax_name').value = tax.name;
                document.getElementById('calculation_rule').value = tax.calculation_rule || 'revenue';
                document.getElementById('payment_method').value = tax.payment_method;
                document.getElementById('tax_type').value = tax.type;
                document.getElementById('tax_rate').value = tax.rate;
                document.getElementById('taxModal').classList.add('active');
            } else {
                showAlert(data.message || 'Erro ao carregar taxa', 'error');
            }
        })
        .catch(err => {
            console.error('Erro:', err);
            showAlert('Erro ao processar requisição', 'error');
        });
}

function deleteTax(id) {
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('index.php?page=tax-delete', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Erro ao excluir taxa', 'error');
        }
    })
    .catch(err => {
        console.error('Erro:', err);
        showAlert('Erro ao processar requisição', 'error');
    });
}

document.getElementById('taxForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const action = isEditingTax ? 'tax-update' : 'tax-store';
    
    fetch(`index.php?page=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeTaxModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Erro ao salvar taxa', 'error');
        }
    })
    .catch(err => {
        console.error('Erro:', err);
        showAlert('Erro ao processar requisição', 'error');
    });
});

// ============= CUSTOS =============

function openCostsModal() {
    document.getElementById('costsModal').classList.add('active');
}

function closeCostsModal() {
    document.getElementById('costsModal').classList.remove('active');
}

document.getElementById('costsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('index.php?page=tax-update-costs', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeCostsModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Erro ao salvar custos', 'error');
        }
    })
    .catch(err => {
        console.error('Erro:', err);
        showAlert('Erro ao processar requisição', 'error');
    });
});

// ============= FECHAR MODAIS =============

document.querySelectorAll('.tax-modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeImpostoModal();
            closeTaxModal();
            closeCostsModal();
        }
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImpostoModal();
        closeTaxModal();
        closeCostsModal();
    }
});
</script>