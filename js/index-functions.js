/**
 * Funciones auxiliares para index.php
 */
 
const API_BASE = (window.APP && window.APP.BASE_URL) ? (window.APP.BASE_URL.replace(/\/+$/,'') + '/') : '';

// Crear tooltip flotante para traducciones
window.createTooltip = function() {
    if (!document.getElementById('word-tooltip')) {
        const tooltip = document.createElement('div');
        tooltip.id = 'word-tooltip';
        tooltip.style.cssText = `
            position: fixed;
            background-color: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            z-index: 10000;
            display: none;
            max-width: 200px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            pointer-events: none;
            border: 1px solid #555;
        `;
        document.body.appendChild(tooltip);
    }
};

window.showTooltip = function(element, text) {
    createTooltip();
    const tooltip = document.getElementById('word-tooltip');
    const rect = element.getBoundingClientRect();

    let wasReading = false;
    tooltip.textContent = text;
    tooltip.style.display = 'block';

    // Calcular posición centrada encima del elemento
    const tooltipWidth = tooltip.offsetWidth;
    const left = rect.left + (rect.width / 2) - (tooltipWidth / 2);
    const top = rect.top - tooltip.offsetHeight - 10;

    tooltip.style.left = Math.max(10, left) + 'px';
    tooltip.style.top = Math.max(10, top) + 'px';

    let mouseLeaveTimeout;

    element.addEventListener('mouseleave', function handleMouseLeave() {
        mouseLeaveTimeout = setTimeout(() => {
            tooltip.style.display = 'none';
            if (wasReading && typeof window.resumeSpeech === 'function') {
                window.resumeSpeech();
            }
            element.removeEventListener('mouseleave', handleMouseLeave);
        }, 100);
    });

    element.addEventListener('mouseenter', function handleMouseEnter() {
        clearTimeout(mouseLeaveTimeout);
        element.removeEventListener('mouseenter', handleMouseEnter);
    });

    setTimeout(() => {
        if (tooltip.style.display !== 'none') {
            tooltip.style.display = 'none';
            if (wasReading && typeof window.resumeSpeech === 'function') {
                window.resumeSpeech();
            }
        }
    }, 6000);
};

window.hideTooltip = function() {
    const tooltip = document.getElementById('word-tooltip');
    if (tooltip) {
        tooltip.style.display = 'none';
    }
};

// Función mejorada de impresión con todo el texto y traducciones
window.printFullTextWithTranslations = async function() {
    const pages = document.querySelectorAll('.page');
    if (pages.length === 0) {
        window.print();
        return;
    }

    // El título se obtiene del DOM si es posible
    const textTitle = document.querySelector('.title-english')?.textContent || "Texto";

    let printContent = '<div style="font-family: Arial, sans-serif; line-height: 1.8; max-width: 800px; margin: 0 auto;">';
    printContent += `<h1 style="text-align: center; margin-bottom: 10px; font-size: 24px;">LeeInglés</h1>`;
    printContent += `<h2 style="text-align: center; margin-bottom: 40px; font-size: 18px; color: #666;">${textTitle}</h2>`;

    const loadingWindow = window.open('', '_blank');
    loadingWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head><title>Generando impresión...</title></head>
        <body style="font-family: Arial; text-align: center; padding: 50px;">
            <h2>Generando traducciones para impresión...</h2>
            <p>Por favor espera mientras preparamos el documento.</p>
        </body>
        </html>
    `);

    for (let pageIndex = 0; pageIndex < pages.length; pageIndex++) {
        const page = pages[pageIndex];
        const paragraphs = page.querySelectorAll('.paragraph');

        for (let idx = 0; idx < paragraphs.length; idx++) {
            const paragraph = paragraphs[idx];
            const text = paragraph.textContent.trim();

            if (text) {
                printContent += `<p style="margin-bottom: 5px; font-size: 16px; font-weight: normal;">${text}</p>`;

                try {
                    const response = await fetch(API_BASE + 'traduciones/translate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'word=' + encodeURIComponent(text)
                    });
                    const data = await response.json();

                    if (data.translation) {
                        printContent += `<p style="font-style: italic; color: #666; margin-bottom: 25px; font-size: 14px;">${data.translation}</p>`;
                    } else {
                        printContent += `<p style="font-style: italic; color: #666; margin-bottom: 25px; font-size: 14px;">[Sin traducción disponible]</p>`;
                    }
                } catch (error) {
                    printContent += `<p style="font-style: italic; color: #666; margin-bottom: 25px; font-size: 14px;">[Error al obtener traducción]</p>`;
                }
            }
        }

        if (pageIndex < pages.length - 1) {
            printContent += '<div style="page-break-after: always;"></div>';
        }
    }

    printContent += '</div>';
    loadingWindow.close();

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>LeeInglés - ${textTitle}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.8; margin: 40px 20px; color: #333; }
                @media print { body { margin: 20px; } .page-break { page-break-after: always; } }
            </style>
        </head>
        <body>${printContent}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
};

// Función para requerir login para subir texto
window.requireLoginForUpload = function() {
    const authModal = document.getElementById('authModal');
    if (!authModal) return;
    
    const loginForm = authModal.querySelector('#login-form');
    
    const existingMessage = authModal.querySelector('.upload-info-message');
    if (!existingMessage && loginForm) {
        const infoMessage = document.createElement('div');
        infoMessage.className = 'upload-info-message';
        infoMessage.style.cssText = 'background: #e6f3ff; color: #0066cc; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 0.9rem;';
        infoMessage.innerHTML = '✨ Crea una cuenta gratuita para subir tus propios textos y practicar vocabulario personalizado';
        loginForm.parentNode.insertBefore(infoMessage, loginForm);
    }
    
    showLoginModal();
};

// Función para traducir contextos de palabras guardadas (Optimizada para evitar saturación)
window.translateAllContextsForSavedWords = async function() {
    const contexts = document.querySelectorAll('.word-context');
    
    // Procesar de uno en uno para no saturar la sesión ni el servidor
    for (const span of contexts) {
        const context = span.getAttribute('data-context');
        const translationDiv = span.nextElementSibling;
        
        if (context && translationDiv && translationDiv.classList.contains('context-translation') && !translationDiv.textContent) {
            try {
                const response = await fetch(API_BASE + 'traduciones/translate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'text=' + encodeURIComponent(context) + '&target_lang=es'
                });
                const data = await response.json();
                
                if (data && data.translation) {
                    translationDiv.textContent = data.translation;
                } else {
                    translationDiv.textContent = '[No se pudo traducir]';
                }
            } catch (error) {
                translationDiv.textContent = '[Error de traducción]';
            }
            
            // Pequeña pausa entre peticiones para dejar respirar al servidor
            await new Promise(resolve => setTimeout(resolve, 100));
        }
    }
};

// Función para cargar textos públicos
window.loadPublicTexts = function() {
    fetch('index.php?show_public_texts=1')
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const textContent = doc.getElementById('text');
            if (textContent) {
                document.getElementById('text').innerHTML = textContent.innerHTML;
            }
        })
        .catch(error => {
            const textContainer = document.getElementById('text');
            if (textContainer) textContainer.innerHTML = '<p>Error cargando textos públicos.</p>';
        });
};


// Función para recuperar contraseña
window.showForgotPassword = function() {
    const email = prompt('Introduce tu email para recuperar la contraseña:');
    if (email && email.includes('@')) {
        alert('Se ha enviado un enlace de recuperación a ' + email + '\n(Funcionalidad en desarrollo)');
    } else if (email) {
        alert('Por favor introduce un email válido');
    }
};
