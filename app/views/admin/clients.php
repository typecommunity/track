<div class="content-card">
    <h2 class="card-title">👥 Gerenciar Clientes</h2>
    
    <?php if (empty($clients)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">👤</div>
        <div class="empty-state-title">Nenhum cliente cadastrado</div>
        <p>Os clientes aparecerão aqui quando se registrarem</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Empresa</th>
                <th>Contas</th>
                <th>Vendas</th>
                <th>Receita</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
            <tr>
                <td><?= htmlspecialchars($client['name']) ?></td>
                <td><?= htmlspecialchars($client['email']) ?></td>
                <td><?= htmlspecialchars($client['company_name'] ?? '-') ?></td>
                <td><?= $client['ad_accounts'] ?? 0 ?></td>
                <td><?= $client['total_sales'] ?? 0 ?></td>
                <td>R$ <?= number_format($client['revenue'] ?? 0, 2, ',', '.') ?></td>
                <td>
                    <span class="badge badge-<?= $client['status'] === 'active' ? 'success' : 'warning' ?>">
                        <?= ucfirst($client['status']) ?>
                    </span>
                </td>
                <td>
                    <button class="btn" style="padding: 6px 12px; font-size: 12px;">Ver</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>