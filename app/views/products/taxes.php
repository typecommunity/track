<div class="card">
    <h2 class="card-title">💰 Taxas e Impostos</h2>
    
    <?php if (empty($taxes)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">💳</div>
        <div class="empty-state-title">Nenhuma taxa cadastrada</div>
        <p>Configure as taxas dos seus produtos e métodos de pagamento</p>
        <button class="btn btn-primary" style="margin-top: 20px;">
            Adicionar Taxa
        </button>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Taxa</th>
                <th>Método</th>
                <th>Tipo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($taxes as $tax): ?>
            <tr>
                <td><?= htmlspecialchars($tax['name']) ?></td>
                <td><?= number_format($tax['rate'], 2, ',', '.') ?><?= $tax['type'] === 'percentage' ? '%' : '' ?></td>
                <td><?= ucfirst(str_replace('_', ' ', $tax['payment_method'])) ?></td>
                <td><?= $tax['type'] === 'percentage' ? 'Percentual' : 'Fixo' ?></td>
                <td>
                    <button class="btn" style="padding: 6px 12px; font-size: 12px;">Editar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card" style="margin-top: 20px;">
    <h2 class="card-title">🏷️ Custo de Produtos</h2>
    
    <?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📦</div>
        <div class="empty-state-title">Configure os custos dos produtos</div>
        <p>Adicione produtos primeiro para configurar seus custos</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Preço de Venda</th>
                <th>Custo</th>
                <th>Margem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): 
                $margin = $product['price'] > 0 ? (($product['price'] - $product['cost']) / $product['price']) * 100 : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td>R$ <?= number_format($product['price'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($product['cost'], 2, ',', '.') ?></td>
                <td><?= number_format($margin, 2, ',', '.') ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>