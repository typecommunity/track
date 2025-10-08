<!-- Estatísticas -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 30px;">
    <div class="metric-card">
        <div class="metric-label">
            📦 Total de Produtos
        </div>
        <div class="metric-value">
            <?= number_format($stats['total_products'] ?? 0, 0, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            ✅ Produtos Ativos
        </div>
        <div class="metric-value">
            <?= number_format($stats['active_products'] ?? 0, 0, ',', '.') ?>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-label">
            💰 Preço Médio
        </div>
        <div class="metric-value">
            R$ <?= number_format($stats['avg_price'] ?? 0, 2, ',', '.') ?>
        </div>
    </div>
</div>

<!-- Lista de Produtos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📦 Meus Produtos</h2>
        <div style="display: flex; gap: 10px;">
            <input 
                type="text" 
                id="searchProduct" 
                placeholder="Buscar produto..."
                onkeyup="filterProducts()"
                style="padding: 10px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px; width: 250px;"
            >
            <button onclick="openCreateModal()" class="btn btn-primary">
                ➕ Novo Produto
            </button>
        </div>
    </div>
    
    <?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📦</div>
        <div class="empty-state-title">Nenhum produto cadastrado</div>
        <p>Adicione seus produtos para começar a rastrear vendas</p>
        <button onclick="openCreateModal()" class="btn btn-primary" style="margin-top: 20px;">
            ➕ Criar Primeiro Produto
        </button>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table id="productsTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>SKU</th>
                    <th>Preço</th>
                    <th>Custo</th>
                    <th>Margem</th>
                    <th>Vendas</th>
                    <th>Receita</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['sku'] ?? '-') ?></td>
                    <td>R$ <?= number_format($product['price'], 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($product['cost'], 2, ',', '.') ?></td>
                    <td>
                        <span style="color: <?= $product['margin'] >= 30 ? '#10b981' : ($product['margin'] >= 15 ? '#f59e0b' : '#ef4444') ?>">
                            <?= number_format($product['margin'], 1, ',', '.') ?>%
                        </span>
                    </td>
                    <td><?= number_format($product['total_sales'] ?? 0, 0, ',', '.') ?></td>
                    <td>R$ <?= number_format($product['total_revenue'] ?? 0, 2, ',', '.') ?></td>
                    <td>
                        <span class="badge badge-<?= $product['status'] === 'active' ? 'success' : 'warning' ?>" style="
                            display: inline-block;
                            padding: 4px 12px;
                            border-radius: 12px;
                            font-size: 12px;
                            font-weight: 600;
                        ">
                            <?= $product['status'] === 'active' ? '✓ Ativo' : '⏸ Inativo' ?>
                        </span>
                    </td>
                    <td>
                        <button 
                            onclick='editProduct(<?= $product['id'] ?>)' 
                            class="btn" 
                            style="padding: 6px 12px; font-size: 12px; background: #667eea; color: white;"
                            title="Editar"
                        >
                            ✏️
                        </button>
                        <button 
                            onclick='deleteProduct(<?= $product['id'] ?>, "<?= addslashes($product['name']) ?>")' 
                            class="btn" 
                            style="padding: 6px 12px; font-size: 12px; background: #ef4444; color: white;"
                            title="Deletar"
                        >
                            🗑️
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Criar/Editar Produto -->
<div id="productModal" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
">
    <div style="
        background: #1e293b;
        border-radius: 20px;
        padding: 40px;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        border: 1px solid #334155;
    ">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 id="modalTitle" style="color: white; font-size: 24px;">➕ Novo Produto</h2>
            <button onclick="closeModal()" style="
                background: none;
                border: none;
                color: #94a3b8;
                font-size: 28px;
                cursor: pointer;
                padding: 0;
                width: 40px;
                height: 40px;
            ">×</button>
        </div>
        
        <form id="productForm" onsubmit="saveProduct(event)">
            <input type="hidden" id="product_id" name="product_id">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Nome do Produto *
                </label>
                <input 
                    type="text" 
                    id="product_name" 
                    name="name"
                    required
                    placeholder="Ex: Curso de Marketing Digital"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    SKU (Código)
                </label>
                <input 
                    type="text" 
                    id="product_sku" 
                    name="sku"
                    placeholder="Ex: CURSO-001"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                        Preço de Venda * (R$)
                    </label>
                    <input 
                        type="number" 
                        id="product_price" 
                        name="price"
                        step="0.01"
                        min="0"
                        required
                        placeholder="197.00"
                        style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                    >
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                        Custo (R$)
                    </label>
                    <input 
                        type="number" 
                        id="product_cost" 
                        name="cost"
                        step="0.01"
                        min="0"
                        value="0"
                        placeholder="0.00"
                        style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                    >
                </div>
            </div>
            
            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #e2e8f0;">
                    Status
                </label>
                <select 
                    id="product_status" 
                    name="status"
                    style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px;"
                >
                    <option value="active">✓ Ativo</option>
                    <option value="inactive">⏸ Inativo</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeModal()" class="btn" style="background: #334155; color: white;">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    💾 Salvar Produto
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.badge-success {
    background: #10b98120;
    color: #10b981;
}

.badge-warning {
    background: #f59e0b20;
    color: #f59e0b;
}
</style>

<script>
// Abrir modal de criação
function openCreateModal() {
    document.getElementById('modalTitle').textContent = '➕ Novo Produto';
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('productModal').style.display = 'flex';
}

// Editar produto
async function editProduct(productId) {
    try {
        const response = await fetch(`index.php?page=product-get&id=${productId}`);
        const result = await response.json();
        
        if (result.success) {
            const product = result.product;
            
            document.getElementById('modalTitle').textContent = '✏️ Editar Produto';
            document.getElementById('product_id').value = product.id;
            document.getElementById('product_name').value = product.name;
            document.getElementById('product_sku').value = product.sku || '';
            document.getElementById('product_price').value = product.price;
            document.getElementById('product_cost').value = product.cost;
            document.getElementById('product_status').value = product.status;
            
            document.getElementById('productModal').style.display = 'flex';
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao carregar produto: ' + error.message);
    }
}

// Salvar produto
async function saveProduct(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const productId = formData.get('product_id');
    const url = productId ? 'index.php?page=product-update' : 'index.php?page=product-create';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            closeModal();
            window.location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao salvar produto: ' + error.message);
    }
}

// Deletar produto
async function deleteProduct(productId, productName) {
    if (!confirm(`Tem certeza que deseja deletar o produto "${productName}"?`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        
        const response = await fetch('index.php?page=product-delete', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✓ ' + result.message);
            window.location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao deletar produto: ' + error.message);
    }
}

// Fechar modal
function closeModal() {
    document.getElementById('productModal').style.display = 'none';
}

// Filtrar produtos
function filterProducts() {
    const input = document.getElementById('searchProduct');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('productsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Fechar modal ao clicar fora
document.getElementById('productModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>