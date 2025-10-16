<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UTMTrack</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #000000;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Three.js Canvas Background */
        #shader-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        /* Container */
        .login-container {
            background: rgba(10, 10, 10, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 24px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.8),
                0 0 100px rgba(102, 126, 234, 0.3),
                inset 0 0 60px rgba(102, 126, 234, 0.05);
            width: 100%;
            max-width: 480px;
            padding: 50px 40px;
            position: relative;
            z-index: 2;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Glow Effect */
        .login-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #667eea, #4f6cff, #667eea);
            background-size: 200% 200%;
            border-radius: 24px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .login-container:hover::before {
            opacity: 0.3;
            animation: borderGlow 3s infinite;
        }
        
        @keyframes borderGlow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Logo Section */
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: inline-block;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .logo h1 {
            background: linear-gradient(135deg, #667eea 0%, #4f6cff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -1px;
        }
        
        .logo p {
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Alert */
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        /* Form Groups */
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            color: #e2e8f0;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            transition: color 0.3s;
            pointer-events: none;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(0, 0, 0, 0.6);
            border: 2px solid #334155;
            border-radius: 12px;
            font-size: 15px;
            color: #e2e8f0;
            transition: all 0.3s;
        }
        
        .form-group input::placeholder {
            color: #64748b;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(0, 0, 0, 0.8);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }
        
        .form-group input:focus ~ .input-icon {
            color: #667eea;
        }
        
        /* Remember & Forgot */
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .custom-checkbox {
            position: relative;
            width: 20px;
            height: 20px;
        }
        
        .custom-checkbox input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            width: 0;
            height: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: rgba(0, 0, 0, 0.6);
            border: 2px solid #334155;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .custom-checkbox:hover .checkmark {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .custom-checkbox input:checked ~ .checkmark {
            background: linear-gradient(135deg, #667eea 0%, #4f6cff 100%);
            border-color: transparent;
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .custom-checkbox input:checked ~ .checkmark:after {
            display: block;
        }
        
        .remember-me label {
            font-size: 14px;
            color: #94a3b8;
            cursor: pointer;
            user-select: none;
        }
        
        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .forgot-password:hover {
            color: #4f6cff;
        }
        
        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #4f6cff 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login span {
            position: relative;
            z-index: 1;
        }
        
        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 30px;
            color: #94a3b8;
            font-size: 14px;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .register-link a:hover {
            color: #4f6cff;
        }
        
        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #64748b;
            margin: 30px 0;
            font-size: 13px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #334155;
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .login-container {
                padding: 40px 30px;
            }
            
            .logo h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div id="shader-canvas"></div>
    
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🎯</div>
            <h1>UTMTrack</h1>
            <p>Rastreamento inteligente de campanhas</p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">E-mail</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="seu@email.com"
                        required
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        placeholder="••••••••"
                        required
                    >
                </div>
            </div>
            
            <div class="remember-forgot">
                <div class="remember-me">
                    <div class="custom-checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="checkmark"></span>
                    </div>
                    <label for="remember">Lembrar-me</label>
                </div>
                <a href="#" class="forgot-password">Esqueceu a senha?</a>
            </div>
            
            <button type="submit" class="btn-login">
                <span>Entrar</span>
            </button>
        </form>
        
        <div class="divider">
            <span>ou</span>
        </div>
        
        <div class="register-link">
            Não tem uma conta? <a href="index.php?page=register">Cadastre-se gratuitamente</a>
        </div>
    </div>

    <script>
        // Dot Shader Background com Three.js
        const scene = new THREE.Scene();
        const camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);
        const renderer = new THREE.WebGLRenderer({ antialias: true });
        
        const container = document.getElementById('shader-canvas');
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(window.devicePixelRatio);
        container.appendChild(renderer.domElement);

        // Mouse trail texture
        const trailCanvas = document.createElement('canvas');
        trailCanvas.width = 512;
        trailCanvas.height = 512;
        const trailCtx = trailCanvas.getContext('2d');
        const trailTexture = new THREE.CanvasTexture(trailCanvas);
        
        let mouseTrail = [];
        let mouseX = 0.5;
        let mouseY = 0.5;

        // Shader Material
        const shaderMaterial = new THREE.ShaderMaterial({
            uniforms: {
                time: { value: 0 },
                resolution: { value: new THREE.Vector2(window.innerWidth, window.innerHeight) },
                dotColor: { value: new THREE.Color('#667eea') },
                bgColor: { value: new THREE.Color('#000000') },
                mouseTrail: { value: trailTexture },
                rotation: { value: 0 },
                gridSize: { value: 100 },
                dotOpacity: { value: 0.2 }
            },
            vertexShader: `
                void main() {
                    gl_Position = vec4(position.xy, 0.0, 1.0);
                }
            `,
            fragmentShader: `
                uniform float time;
                uniform vec2 resolution;
                uniform vec3 dotColor;
                uniform vec3 bgColor;
                uniform sampler2D mouseTrail;
                uniform float rotation;
                uniform float gridSize;
                uniform float dotOpacity;

                vec2 rotate(vec2 uv, float angle) {
                    float s = sin(angle);
                    float c = cos(angle);
                    mat2 rotationMatrix = mat2(c, -s, s, c);
                    return rotationMatrix * (uv - 0.5) + 0.5;
                }

                vec2 coverUv(vec2 uv) {
                    vec2 s = resolution.xy / max(resolution.x, resolution.y);
                    vec2 newUv = (uv - 0.5) * s + 0.5;
                    return clamp(newUv, 0.0, 1.0);
                }

                float sdfCircle(vec2 p, float r) {
                    return length(p - 0.5) - r;
                }

                void main() {
                    vec2 screenUv = gl_FragCoord.xy / resolution;
                    vec2 uv = coverUv(screenUv);
                    vec2 rotatedUv = rotate(uv, rotation);

                    // Create grid
                    vec2 gridUv = fract(rotatedUv * gridSize);
                    vec2 gridUvCenterInScreenCoords = rotate((floor(rotatedUv * gridSize) + 0.5) / gridSize, -rotation);

                    // Distance from center
                    float baseDot = sdfCircle(gridUv, 0.25);

                    // Screen mask
                    float screenMask = smoothstep(0.0, 1.0, 1.0 - uv.y);
                    vec2 centerDisplace = vec2(0.5, 0.6);
                    float circleMaskCenter = length(uv - centerDisplace);
                    float circleMaskFromCenter = smoothstep(0.3, 1.0, circleMaskCenter);
                    
                    float combinedMask = screenMask * circleMaskFromCenter;
                    float circleAnimatedMask = sin(time * 2.0 + circleMaskCenter * 10.0);

                    // Mouse trail effect
                    float mouseInfluence = texture2D(mouseTrail, gridUvCenterInScreenCoords).r;
                    float scaleInfluence = max(mouseInfluence * 0.5, circleAnimatedMask * 0.3);

                    // Dot size
                    float dotSize = min(pow(circleMaskCenter, 2.0) * 0.3, 0.3);
                    float sdfDot = sdfCircle(gridUv, dotSize * (1.0 + scaleInfluence * 0.5));
                    float smoothDot = smoothstep(0.05, 0.0, sdfDot);

                    float opacityInfluence = max(mouseInfluence * 50.0, circleAnimatedMask * 0.5);

                    // Final composition
                    vec3 composition = mix(bgColor, dotColor, smoothDot * combinedMask * dotOpacity * (1.0 + opacityInfluence));

                    gl_FragColor = vec4(composition, 1.0);
                }
            `
        });

        const geometry = new THREE.PlaneGeometry(2, 2);
        const mesh = new THREE.Mesh(geometry, shaderMaterial);
        scene.add(mesh);

        // Mouse tracking
        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX / window.innerWidth;
            mouseY = 1.0 - (e.clientY / window.innerHeight);
            
            mouseTrail.push({ x: mouseX, y: mouseY, age: 0 });
            if (mouseTrail.length > 50) mouseTrail.shift();
        });

        // Update trail texture
        function updateTrailTexture() {
            trailCtx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            trailCtx.fillRect(0, 0, 512, 512);
            
            mouseTrail.forEach((point, i) => {
                point.age++;
                const alpha = 1 - (point.age / 400);
                if (alpha > 0) {
                    const x = point.x * 512;
                    const y = point.y * 512;
                    const radius = 50;
                    
                    const gradient = trailCtx.createRadialGradient(x, y, 0, x, y, radius);
                    gradient.addColorStop(0, `rgba(255, 255, 255, ${alpha})`);
                    gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
                    
                    trailCtx.fillStyle = gradient;
                    trailCtx.fillRect(x - radius, y - radius, radius * 2, radius * 2);
                }
            });
            
            mouseTrail = mouseTrail.filter(p => p.age < 400);
            trailTexture.needsUpdate = true;
        }

        // Animation loop
        function animate() {
            requestAnimationFrame(animate);
            
            shaderMaterial.uniforms.time.value += 0.01;
            updateTrailTexture();
            
            renderer.render(scene, camera);
        }

        // Resize handler
        window.addEventListener('resize', () => {
            const width = window.innerWidth;
            const height = window.innerHeight;
            
            renderer.setSize(width, height);
            shaderMaterial.uniforms.resolution.value.set(width, height);
        });

        animate();
    </script>
</body>
</html>