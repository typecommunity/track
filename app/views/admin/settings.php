<div class="content-card">
    <h2 class="card-title">⚙️ Configurações do Sistema</h2>
    
    <form method="POST" action="">
        <div style="display: grid; gap: 20px; max-width: 600px;">
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Nome da Aplicação</label>
                <input type="text" name="app_name" value="<?= htmlspecialchars($settings['app_name'] ?? 'UTMTrack') ?>" 
                       style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Timezone</label>
                <select name="timezone" style="width: 100%; padding: 12px;">
                    <option value="America/Sao_Paulo">America/Sao_Paulo</option>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Moeda</label>
                <select name="currency" style="width: 100%; padding: 12px;">
                    <option value="BRL">BRL - Real Brasileiro</option>
                </select>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary">
                    Salvar Configurações
                </button>
            </div>
        </div>
    </form>
    
    <?php if (isset($_GET['success'])): ?>
    <div style="margin-top: 20px; padding: 14px; background: #10b98120; border: 1px solid #10b981; border-radius: 8px; color: #10b981;">
        ✓ Configurações salvas com sucesso!
    </div>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>