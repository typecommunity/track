/**
 * UTMTrack CAPI - Script de Rastreamento Automatizado
 * Similar à UTMify - Captura e envia eventos automaticamente para Facebook CAPI
 * 
 * Uso:
 * <script src="https://seudominio.com/js/capi-tracker.js" 
 *         data-pixel-id="SEU_PIXEL_ID"
 *         data-api-url="https://seudominio.com/api/capi-events.php"
 *         async defer></script>
 */

(function() {
    'use strict';
    
    // =====================
    // CONFIGURAÇÕES
    // =====================
    
    const config = {
        pixelId: null,
        apiUrl: null,
        debug: false,
        autoTrack: true,
        cookieExpDays: 30,
        sessionDuration: 30 * 60 * 1000, // 30 minutos
    };
    
    // Lê configurações do script tag
    const scriptTag = document.currentScript;
    if (scriptTag) {
        config.pixelId = scriptTag.getAttribute('data-pixel-id');
        config.apiUrl = scriptTag.getAttribute('data-api-url');
        config.debug = scriptTag.hasAttribute('data-debug');
        config.autoTrack = !scriptTag.hasAttribute('data-no-auto-track');
    }
    
    if (!config.pixelId || !config.apiUrl) {
        console.error('[UTMTrack CAPI] pixel-id e api-url são obrigatórios');
        return;
    }
    
    // =====================
    // UTILITÁRIOS
    // =====================
    
    const utils = {
        // Gera UUID v4
        generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },
        
        // Gera event ID único
        generateEventId() {
            return `evt_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        },
        
        // Get/Set Cookie
        getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        },
        
        setCookie(name, value, days) {
            const expires = new Date(Date.now() + days * 864e5).toUTCString();
            document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
        },
        
        // Get/Set LocalStorage
        getStorage(key) {
            try {
                return localStorage.getItem(key);
            } catch (e) {
                return null;
            }
        },
        
        setStorage(key, value) {
            try {
                localStorage.setItem(key, value);
            } catch (e) {
                // Silent fail
            }
        },
        
        // Log (apenas se debug ativado)
        log(...args) {
            if (config.debug) {
                console.log('[UTMTrack CAPI]', ...args);
            }
        },
        
        // Parse URL parameters
        getUrlParams() {
            const params = new URLSearchParams(window.location.search);
            const obj = {};
            for (const [key, value] of params.entries()) {
                obj[key] = value;
            }
            return obj;
        }
    };
    
    // =====================
    // GERENCIADOR DE UTMs
    // =====================
    
    const utmManager = {
        params: ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid', 'ttclid'],
        
        // Captura UTMs da URL
        capture() {
            const urlParams = utils.getUrlParams();
            const utms = {};
            let hasUtm = false;
            
            this.params.forEach(param => {
                if (urlParams[param]) {
                    utms[param] = urlParams[param];
                    hasUtm = true;
                }
            });
            
            if (hasUtm) {
                this.save(utms);
                utils.log('UTMs capturados:', utms);
            }
            
            return hasUtm ? utms : this.load();
        },
        
        // Salva UTMs
        save(utms) {
            utils.setStorage('utmtrack_params', JSON.stringify(utms));
            utils.setStorage('utmtrack_timestamp', Date.now().toString());
        },
        
        // Carrega UTMs salvos
        load() {
            const saved = utils.getStorage('utmtrack_params');
            const timestamp = utils.getStorage('utmtrack_timestamp');
            
            // Verifica expiração (30 dias)
            if (saved && timestamp) {
                const age = Date.now() - parseInt(timestamp);
                if (age < config.cookieExpDays * 24 * 60 * 60 * 1000) {
                    return JSON.parse(saved);
                }
            }
            
            return {};
        },
        
        // Obtém UTMs atuais
        get() {
            return this.load();
        }
    };
    
    // =====================
    // GERENCIADOR DE COOKIES FACEBOOK
    // =====================
    
    const fbCookies = {
        // Inicializa cookies do Facebook
        init() {
            // Cookie _fbp (Facebook Pixel)
            if (!utils.getCookie('_fbp')) {
                const fbp = `fb.1.${Date.now()}.${Math.random().toString().slice(2, 11)}`;
                utils.setCookie('_fbp', fbp, config.cookieExpDays);
                utils.log('Cookie _fbp criado:', fbp);
            }
            
            // Cookie _fbc (Facebook Click)
            const fbclid = utmManager.get().fbclid;
            if (fbclid && !utils.getCookie('_fbc')) {
                const fbc = `fb.1.${Date.now()}.${fbclid}`;
                utils.setCookie('_fbc', fbc, config.cookieExpDays);
                utils.log('Cookie _fbc criado:', fbc);
            }
        },
        
        // Obtém cookies
        get() {
            return {
                fbp: utils.getCookie('_fbp'),
                fbc: utils.getCookie('_fbc')
            };
        }
    };
    
    // =====================
    // GERENCIADOR DE SESSÃO
    // =====================
    
    const sessionManager = {
        // Obtém ou cria session ID
        getSessionId() {
            const sessionId = utils.getStorage('utmtrack_session_id');
            const sessionTime = utils.getStorage('utmtrack_session_time');
            
            // Verifica se sessão expirou
            if (sessionId && sessionTime) {
                const age = Date.now() - parseInt(sessionTime);
                if (age < config.sessionDuration) {
                    // Atualiza timestamp da sessão
                    this.updateSession(sessionId);
                    return sessionId;
                }
            }
            
            // Cria nova sessão
            return this.createSession();
        },
        
        // Cria nova sessão
        createSession() {
            const sessionId = utils.generateUUID();
            utils.setStorage('utmtrack_session_id', sessionId);
            utils.setStorage('utmtrack_session_time', Date.now().toString());
            utils.log('Nova sessão criada:', sessionId);
            return sessionId;
        },
        
        // Atualiza timestamp da sessão
        updateSession(sessionId) {
            utils.setStorage('utmtrack_session_time', Date.now().toString());
        }
    };
    
    // =====================
    // CAPTURA DE DADOS DO USUÁRIO
    // =====================
    
    const userDataCapture = {
        // Captura email de formulários
        captureEmail() {
            const emailInputs = document.querySelectorAll('input[type="email"], input[name*="email"], input[id*="email"]');
            for (const input of emailInputs) {
                if (input.value && this.validateEmail(input.value)) {
                    return input.value.toLowerCase().trim();
                }
            }
            return null;
        },
        
        // Captura telefone de formulários
        capturePhone() {
            const phoneInputs = document.querySelectorAll('input[type="tel"], input[name*="phone"], input[name*="telefone"], input[id*="phone"]');
            for (const input of phoneInputs) {
                if (input.value) {
                    return input.value.replace(/\D/g, '');
                }
            }
            return null;
        },
        
        // Captura nome
        captureName() {
            const nameInputs = document.querySelectorAll('input[name*="name"], input[name*="nome"], input[id*="name"]');
            for (const input of nameInputs) {
                if (input.value && input.value.length > 2) {
                    const name = input.value.trim();
                    const parts = name.split(' ');
                    return {
                        fn: parts[0]?.toLowerCase(),
                        ln: parts[parts.length - 1]?.toLowerCase()
                    };
                }
            }
            return { fn: null, ln: null };
        },
        
        // Valida email
        validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        
        // Obtém todos os dados do usuário
        getData() {
            const { fn, ln } = this.captureName();
            const fbCookiesData = fbCookies.get();
            
            return {
                em: this.captureEmail(),
                ph: this.capturePhone(),
                fn: fn,
                ln: ln,
                ...fbCookiesData
            };
        }
    };
    
    // =====================
    // ENVIADOR DE EVENTOS
    // =====================
    
    const eventSender = {
        // Fila de eventos pendentes
        queue: [],
        sending: false,
        
        // Envia evento
        async send(eventName, customData = {}) {
            const utms = utmManager.get();
            const userData = userDataCapture.getData();
            const sessionId = sessionManager.getSessionId();
            
            const eventData = {
                pixel_id: config.pixelId,
                event_name: eventName,
                event_id: utils.generateEventId(),
                event_time: Math.floor(Date.now() / 1000),
                event_source_url: window.location.href,
                user_data: userData,
                custom_data: customData,
                utm_params: utms,
                session_id: sessionId,
                fbclid: utms.fbclid || null
            };
            
            utils.log('Enviando evento:', eventName, eventData);
            
            try {
                const response = await fetch(config.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(eventData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    utils.log('Evento enviado com sucesso:', eventName);
                } else {
                    console.error('[UTMTrack CAPI] Erro ao enviar evento:', result.error);
                }
                
                return result;
                
            } catch (error) {
                console.error('[UTMTrack CAPI] Erro na requisição:', error);
                return { success: false, error: error.message };
            }
        },
        
        // Envia evento com retry
        async sendWithRetry(eventName, customData = {}, retries = 3) {
            for (let i = 0; i < retries; i++) {
                const result = await this.send(eventName, customData);
                if (result.success) {
                    return result;
                }
                
                // Aguarda antes de tentar novamente
                if (i < retries - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
                }
            }
            
            return { success: false, error: 'Max retries exceeded' };
        }
    };
    
    // =====================
    // RASTREAMENTO AUTOMÁTICO
    // =====================
    
    const autoTracking = {
        // Inicializa rastreamento automático
        init() {
            // PageView
            this.trackPageView();
            
            // Formulários
            this.setupFormTracking();
            
            // Botões de compra
            this.setupCheckoutTracking();
            
            // Links externos
            this.setupOutboundTracking();
        },
        
        // Rastreia PageView
        trackPageView() {
            eventSender.send('PageView', {
                content_name: document.title,
                content_category: this.getPageCategory()
            });
        },
        
        // Setup rastreamento de formulários
        setupFormTracking() {
            document.addEventListener('submit', (e) => {
                const form = e.target;
                
                // Detecta tipo de formulário
                const isLeadForm = this.isLeadForm(form);
                const isCheckoutForm = this.isCheckoutForm(form);
                
                if (isLeadForm) {
                    eventSender.send('Lead', {
                        content_name: form.name || 'Lead Form'
                    });
                } else if (isCheckoutForm) {
                    eventSender.send('InitiateCheckout', {
                        content_name: 'Checkout Form'
                    });
                }
            }, true);
        },
        
        // Setup rastreamento de checkout
        setupCheckoutTracking() {
            // Detecta botões de compra
            const buyButtons = document.querySelectorAll('[class*="buy"], [class*="comprar"], [id*="buy"], [id*="checkout"]');
            
            buyButtons.forEach(button => {
                button.addEventListener('click', () => {
                    eventSender.send('InitiateCheckout', {
                        content_name: button.textContent.trim()
                    });
                });
            });
        },
        
        // Setup rastreamento de links externos
        setupOutboundTracking() {
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (!link) return;
                
                const isExternal = link.hostname !== window.location.hostname;
                if (isExternal) {
                    utils.log('Link externo clicado:', link.href);
                }
            }, true);
        },
        
        // Verifica se é formulário de lead
        isLeadForm(form) {
            const formStr = form.innerHTML.toLowerCase();
            return formStr.includes('email') || formStr.includes('subscribe') || formStr.includes('newsletter');
        },
        
        // Verifica se é formulário de checkout
        isCheckoutForm(form) {
            const formStr = form.innerHTML.toLowerCase();
            return formStr.includes('checkout') || formStr.includes('payment') || formStr.includes('pagamento');
        },
        
        // Obtém categoria da página
        getPageCategory() {
            const path = window.location.pathname;
            if (path.includes('/produto') || path.includes('/product')) return 'product';
            if (path.includes('/checkout')) return 'checkout';
            if (path.includes('/obrigado') || path.includes('/thank')) return 'thank_you';
            return 'other';
        }
    };
    
    // =====================
    // API PÚBLICA
    // =====================
    
    window.utmtrackCapi = {
        // Envia evento customizado
        track(eventName, customData = {}) {
            return eventSender.send(eventName, customData);
        },
        
        // Envia Purchase
        trackPurchase(value, currency = 'BRL', transactionId = null) {
            return eventSender.send('Purchase', {
                value: parseFloat(value),
                currency: currency,
                transaction_id: transactionId || utils.generateUUID()
            });
        },
        
        // Envia Lead
        trackLead(value = null) {
            const customData = {};
            if (value) customData.value = parseFloat(value);
            return eventSender.send('Lead', customData);
        },
        
        // Envia AddToCart
        trackAddToCart(value, currency = 'BRL', contentName = null) {
            return eventSender.send('AddToCart', {
                value: parseFloat(value),
                currency: currency,
                content_name: contentName
            });
        },
        
        // Envia InitiateCheckout
        trackInitiateCheckout(value, currency = 'BRL') {
            return eventSender.send('InitiateCheckout', {
                value: parseFloat(value),
                currency: currency
            });
        },
        
        // Obtém UTMs atuais
        getUtms() {
            return utmManager.get();
        },
        
        // Obtém Session ID
        getSessionId() {
            return sessionManager.getSessionId();
        }
    };
    
    // =====================
    // INICIALIZAÇÃO
    // =====================
    
    function init() {
        utils.log('Inicializando UTMTrack CAPI...');
        
        // Captura UTMs
        utmManager.capture();
        
        // Inicializa cookies do Facebook
        fbCookies.init();
        
        // Inicializa sessão
        sessionManager.getSessionId();
        
        // Rastreamento automático
        if (config.autoTrack) {
            autoTracking.init();
        }
        
        utils.log('UTMTrack CAPI inicializado com sucesso!');
    }
    
    // Aguarda DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();