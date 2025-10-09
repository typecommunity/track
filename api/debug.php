<?php
/**
 * Teste de URL - Coloque este arquivo em: /utmtrack/api/test_url.php
 * Acesse: https://ataweb.com.br/utmtrack/api/test_url.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste URL OAuth</title>
    <style>
        body {
            font-family: monospace;
            background: #0f172a;
            color: white;
            padding: 40px;
            line-height: 1.8;
        }
        .box {
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .url {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            padding: 16px;
            border-radius: 8px;
            color: #10b981;
            font-size: 16px;
            word-break: break-all;
            margin: 10px 0;
        }
        .label {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 8px;
        }
        h1 {
            color: #10b981;
        }
        button {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>🔍 Teste de URL OAuth Callback</h1>
    
    <div class="box">
        <h2>📍 Informações do Servidor</h2>
        
        <div class="label">REQUEST_SCHEME:</div>
        <div class="url"><?= $_SERVER['REQUEST_SCHEME'] ?? 'Não definido' ?></div>
        
        <div class="label">HTTP_HOST:</div>
        <div class="url"><?= $_SERVER['HTTP_HOST'] ?? 'Não definido' ?></div>
        
        <div class="label">SCRIPT_NAME:</div>
        <div class="url"><?= $_SERVER['SCRIPT_NAME'] ?? 'Não definido' ?></div>
        
        <div class="label">SCRIPT_FILENAME:</div>
        <div class="url"><?= $_SERVER['SCRIPT_FILENAME'] ?? 'Não definido' ?></div>
    </div>
    
    <div class="box">
        <h2>✅ URL que o meta_oauth.php vai gerar:</h2>
        
        <?php
        // Simula exatamente o que o meta_oauth.php faz
        $redirectUri = rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']);
        
        // Substitui test_url.php por meta_oauth.php
        $oauthUri = str_replace('test_url.php', 'meta_oauth.php', $redirectUri);
        ?>
        
        <div class="url" id="oauthUrl"><?= $oauthUri ?></div>
        <button onclick="copyUrl()">📋 Copiar URL</button>
    </div>
    
    <div class="box">
        <h2>📝 Instruções:</h2>
        <ol style="color: #cbd5e1;">
            <li>Copie a URL acima (use o botão)</li>
            <li>Cole EXATAMENTE no Facebook: <strong>Facebook Login → Settings → Valid OAuth Redirect URIs</strong></li>
            <li>Salve no Facebook</li>
            <li>Aguarde 2-3 minutos</li>
            <li>Tente conectar novamente</li>
        </ol>
    </div>
    
    <div class="box" style="background: rgba(239, 68, 68, 0.1); border-color: #ef4444;">
        <h2 style="color: #ef4444;">⚠️ Importante:</h2>
        <p style="color: #fca5a5;">
            A URL que você colar no Facebook deve ser <strong>IDÊNTICA</strong> à URL acima.
            Qualquer diferença (mesmo uma letra) causará erro "invalid redirect URI".
        </p>
    </div>
    
    <script>
        function copyUrl() {
            const url = document.getElementById('oauthUrl').textContent;
            navigator.clipboard.writeText(url).then(() => {
                alert('✓ URL copiada!\n\n' + url);
            });
        }
    </script>
</body>
</html>