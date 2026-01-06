// Variables globales para el control de velocidad
let rateInput = null;
let rateValue = null;

// ===== Debug de lectura (activable) ===== d
window.READING_DEBUG = window.READING_DEBUG || false;
window.ReadingLog = window.ReadingLog || [];
window.enableReadingDebug = function(){ window.READING_DEBUG = true; };
window.disableReadingDebug = function(){ window.READING_DEBUG = false; };
window.dumpReadingState = function(){
  try {
    return {
      isCurrentlyReading: window.isCurrentlyReading,
      isCurrentlyPaused: window.isCurrentlyPaused,
      autoReading: typeof autoReading !== 'undefined' ? autoReading : undefined,
      isReadingInProgress: typeof isReadingInProgress !== 'undefined' ? isReadingInProgress : undefined,
      currentIndex: typeof currentIndex !== 'undefined' ? currentIndex : undefined,
      currentReadingIndex: typeof currentReadingIndex !== 'undefined' ? currentReadingIndex : undefined,
      lastReadParagraphIndex: window.lastReadParagraphIndex,
      lastReadWordIndex: window.lastReadWordIndex,
      currentPage: typeof currentPage !== 'undefined' ? currentPage : undefined,
      activeSpeakSessionId: typeof activeSpeakSessionId !== 'undefined' ? activeSpeakSessionId : undefined,
      pauseReasons: Array.from(window.ReadingPauseReasons || []),
    };
  } catch(e) { return { error: String(e) }; }
};
function readingLog(event, data) {
  try {
    const entry = { t: Date.now(), event, ...data };
    window.ReadingLog.push(entry);
    // Limitar tamaño del log
    if (window.ReadingLog.length > 500) window.ReadingLog.splice(0, window.ReadingLog.length - 500);
  } catch(e) {}
}

function initLector() {
    // Hacer estas variables globales para asegurar consistencia entre funciones
    if (typeof window.currentIndex === 'undefined') window.currentIndex = 0;
    if (typeof window.currentPage === 'undefined') window.currentPage = 0;
    if (typeof window.autoReading === 'undefined') window.autoReading = false;
    
    let currentIndex = window.currentIndex;
    let currentPage = window.currentPage;
    let autoReading = window.autoReading;
    // Por defecto, usar SpeechSynthesis nativo para lectura por párrafos (evita saltos internos de RV)
    if (typeof window.useResponsiveVoiceForParagraphs === 'undefined') {
        window.useResponsiveVoiceForParagraphs = false;
    }
    // Variables para el tiempo de lectura
    let readingStartTime = null;
    let readingUpdateInterval = null; // Para actualizar en tiempo real
    let totalReadingTime = 0; // Tiempo total acumulado
    let isCurrentlyReading = false; // Estado de lectura

    // Helper para cancelar cualquier proveedor de TTS activo
    function cancelAllTTS() {
        try { if (typeof window.detenerLecturaResponsiveVoice === 'function') window.detenerLecturaResponsiveVoice(); } catch (e) {}
        try { if (window.speechSynthesis) window.speechSynthesis.cancel(); } catch (e) {}
        try { if (window.eSpeakAPI && window.eSpeakAPI.cancel) window.eSpeakAPI.cancel(); } catch (e) {}
    }

    // Función centralizada para guardar tiempo de lectura
    function saveReadingTime(duration) {
        // Obtener el ID del texto actual
        let textId = document.querySelector('#text.reading-area')?.getAttribute('data-text-id');
        if (!textId) {
            textId = document.querySelector('.reading-area')?.getAttribute('data-text-id');
        }
        if (!textId) {
            textId = document.querySelector('[data-text-id]')?.getAttribute('data-text-id');
        }
        
        if (textId && duration > 0) {
            fetch('save_reading_time.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'duration=' + duration + '&text_id=' + textId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar calendario
                    if (typeof window.updateCalendarNow === 'function') {
                        window.updateCalendarNow();
                    }
                }
            })
            .catch(error => {
                // Error silencioso al guardar tiempo de lectura
            });
        }
    }

    // Función para actualizar tiempo en tiempo real
    function updateReadingTimeRealTime() {
        if (readingStartTime && isCurrentlyReading) {
            const currentTime = Date.now();
            const sessionTime = Math.floor((currentTime - readingStartTime) / 1000);
            const totalTime = totalReadingTime + sessionTime;
            
            // Solo actualizar si han pasado al menos 30 segundos desde la última actualización
            if (sessionTime >= 30) {
                saveReadingTime(totalTime);
            }
        }
    }
    
    // Inicializar elementos con verificación
    rateInput = document.getElementById('rate');
    rateValue = document.getElementById('rate-value');
    
    // Si no están disponibles, buscarlos después
    if (!rateInput) {
        setTimeout(() => {
            rateInput = document.getElementById('rate');
            rateValue = document.getElementById('rate-value');
            if (rateInput && rateValue) {
                // Mostrar el valor inicial como porcentaje
                const initialValue = parseFloat(rateInput.value);
                const percentageDisplay = Math.min(100, Math.round(initialValue * 100 + 10));
                rateValue.textContent = percentageDisplay + '%';
                initializeRateControl();
            }
        }, 1000);
    } else {
        // Mostrar el valor inicial como porcentaje
        const initialValue = parseFloat(rateInput.value);
        const percentageDisplay = Math.min(100, Math.round(initialValue * 100 + 10));
        rateValue.textContent = percentageDisplay + '%';
        initializeRateControl();
    }
    
    function initializeRateControl() {
        if (rateInput && rateValue) {
            // Verificar si ya tiene un listener para evitar duplicados
            if (!rateInput.hasAttribute('data-listener')) {
                // Crear funcionalidad personalizada para el slider
                rateInput.addEventListener('mousedown', (e) => {
                    handleSliderMouseDown(e);
                });
                
                rateInput.addEventListener('mousemove', (e) => {
                    if (rateInput.hasAttribute('data-dragging')) {
                        handleSliderMouseMove(e);
                    }
                });
                
                rateInput.addEventListener('mouseup', (e) => {
                    rateInput.removeAttribute('data-dragging');
                });
                
                // También mantener el listener original por si acaso
                rateInput.addEventListener('input', (e) => {
                    const value = parseFloat(e.target.value);
                    const percentageDisplay = Math.min(100, Math.round(value * 100 + 10));
                    rateValue.textContent = percentageDisplay + '%';
                });
                
                rateInput.setAttribute('data-listener', 'true');
            }
            
            // Funciones para manejar el slider personalizado
            function handleSliderMouseDown(e) {
                rateInput.setAttribute('data-dragging', 'true');
                handleSliderMouseMove(e);
            }
            
            function handleSliderMouseMove(e) {
                const rect = rateInput.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const width = rect.width;
                const percentage = Math.max(0, Math.min(1, clickX / width));
                
                // Calcular el valor basado en el rango (0.5 a 1.0)
                const min = parseFloat(rateInput.min);
                const max = parseFloat(rateInput.max);
                const newValue = min + (percentage * (max - min));
                
                // Redondear al step más cercano (0.1)
                const step = parseFloat(rateInput.step);
                const roundedValue = Math.round(newValue / step) * step;
                
                // Actualizar el valor del input y el display con porcentaje
                rateInput.value = roundedValue;
                const percentageDisplay = Math.min(100, Math.round(roundedValue * 100 + 10));
                rateValue.textContent = percentageDisplay + '%';
            }
        }
    }

    let pages = document.querySelectorAll(".page");
    let totalPages = pages.length;
    let prevBtn, nextBtn, pageNumber;

    window.initializePaginationControls = function() {
        pages = document.querySelectorAll(".page");
        totalPages = pages.length;
        prevBtn = document.getElementById("prev-page");
        nextBtn = document.getElementById("next-page");
        pageNumber = document.getElementById("page-number");

        if (prevBtn && !prevBtn.hasAttribute('data-listener')) {
            prevBtn.addEventListener("click", () => {
                if (window.currentPage > 0) {
                    window.currentPage--;
                    window.currentIndex = 0; // Reiniciar lectura al primer párrafo de la nueva página
                    currentPage = window.currentPage;
                    currentIndex = window.currentIndex;
                    updatePageDisplay();
                    
                    // Si estaba leyendo, continuar en la nueva página
                    if (window.autoReading) {
                        setTimeout(() => {
                            readAndTranslate(0).catch(err => {
                            });
                        }, 300);
                    }
                }
            });
            prevBtn.setAttribute('data-listener', 'true');
        }

        if (nextBtn && !nextBtn.hasAttribute('data-listener')) {
            nextBtn.addEventListener("click", () => {
                if (window.currentPage < totalPages - 1) {
                    window.currentPage++;
                    window.currentIndex = 0; // Reiniciar lectura al primer párrafo de la nueva página
                    currentPage = window.currentPage;
                    currentIndex = window.currentIndex;
                    updatePageDisplay();
                    
                    // Si estaba leyendo, continuar en la nueva página
                    if (window.autoReading) {
                        setTimeout(() => {
                            readAndTranslate(0).catch(err => {
                            });
                        }, 300);
                    }
                }
            });
            nextBtn.setAttribute('data-listener', 'true');
        }
    };

    function updatePageDisplay() {
        if (!pages.length) return;
        
        // Sincronizar con la variable global si existe
        if (typeof window.currentPage === 'number') {
            currentPage = window.currentPage;
        }
        
        pages.forEach((page, idx) => {
            if (idx === currentPage) {
                page.classList.add('active');
            } else {
                page.classList.remove('active');
            }
        });
        
        if (pageNumber) pageNumber.textContent = currentPage + 1;
        if (prevBtn) prevBtn.disabled = currentPage === 0;
        if (nextBtn) nextBtn.disabled = currentPage === totalPages - 1;
        
        assignWordClickHandlers();

        // --- NUEVO: Si la lectura automática está activa, sincronizar con la nueva página ---
        if (window.autoReading || autoReading) {
            if (window.speechSynthesis) window.speechSynthesis.cancel();
            // Si hay un índice de reanudación pendiente, usarlo una única vez
            let nextIdx = 0;
            if (typeof window._resumeIndexPending === 'number' && window._resumeIndexPending >= 0) {
                nextIdx = window._resumeIndexPending;
                window._resumeIndexPending = null; // consumir
            }
            window.currentIndex = nextIdx;
            currentIndex = nextIdx;
            setTimeout(() => {
                readAndTranslate(nextIdx).catch(err => {
                });
            }, 300);
        }

        updateReadingProgressBar();
    }

    // === BARRA DE PROGRESO DE LECTURA ===
    function updateReadingProgressBar() {
        let pagesContainer = document.getElementById('pages-container');
        if (!pagesContainer) return;
        let totalPages = pagesContainer.getAttribute('data-total-pages') || 1;
        totalPages = parseInt(totalPages);
        let totalWords = pagesContainer.getAttribute('data-total-words') || 1;
        totalWords = parseInt(totalWords);
        let pages = pagesContainer.querySelectorAll('.page');
        let readPages = Array.isArray(window.readPages) ? window.readPages : [];
        let wordsRead = 0;
        if (window.readingProgressPercent === 100) {
            wordsRead = totalWords;
        } else if (readPages.length > 0) {
            readPages.forEach(idx => {
                if (pages[idx]) {
                    let paragraphs = pages[idx].querySelectorAll('p.paragraph');
                    paragraphs.forEach(p => {
                        wordsRead += (p.innerText.match(/\b\w+\b/g) || []).length;
                    });
                }
            });
        } else {
            wordsRead = 0;
        }
        let percent = Math.round((wordsRead / totalWords) * 100);
        if (window.readingProgressPercent === 100) percent = 100;
        if (percent > 100) percent = 100;
        if (readPages.length === 0 && percent !== 0) percent = 0;
        let progressContainer = document.getElementById('reading-progress-container');
        if (!progressContainer) {
            progressContainer = document.createElement('div');
            progressContainer.id = 'reading-progress-container';
            progressContainer.style.cssText = 'display: flex; align-items: center; gap: 15px; margin: 20px auto; width: fit-content;';
            
            // Crear barra de progreso más pequeña
            let bar = document.createElement('div');
            bar.id = 'reading-progress-bar';
            bar.style.cssText = 'width: 200px; background: #e5e7eb; border-radius: 4px; overflow: hidden;';
            let inner = document.createElement('div');
            inner.id = 'reading-progress-inner';
            inner.style.cssText = 'height: 100%; width: 0%; background: linear-gradient(90deg, #3B82F6, #60A5FA); transition: width 0.4s; border-radius: 4px;';
            bar.appendChild(inner);
            
            progressContainer.appendChild(bar);
            pagesContainer.parentNode.insertBefore(progressContainer, pagesContainer);
        }
        let inner = document.getElementById('reading-progress-inner');
        if (inner) {
            inner.style.width = percent + '%';
            inner.textContent = percent > 0 ? percent + '%' : '';
            inner.style.color = '#fff';
            inner.style.fontWeight = 'bold';
            inner.style.textAlign = 'center';
            inner.style.fontSize = '12px';
            inner.style.lineHeight = '12px';
        }
    }

    // Inicializar controles cuando se carga el DOM
    setTimeout(() => {
        window.initializePaginationControls();
        updatePageDisplay();
    }, 100);

    window.doubleReadingMode = false;
    let doubleReadCurrentIndex = null; // Para rastrear el índice durante la lectura doble
    let doubleReadButton = null;

    // Variable para evitar múltiples llamadas simultáneas
    let isReadingInProgress = false;
    // CORRECCIÓN: Variable para evitar múltiples eventos onend
    let onEndHandled = false;
    let currentReadingIndex = -1;
    // Control de sesiones de habla para ignorar callbacks obsoletos
    let speakSessionId = 0;
    let activeSpeakSessionId = 0;
    
    async function readAndTranslate(index, startWord = 0) {
        // CORRECCIÓN: Si intentamos leer un índice diferente, limpiar el flag anterior
        if (isReadingInProgress && currentReadingIndex !== index) {
            isReadingInProgress = false;
            onEndHandled = false;
        }
        
        // CORRECCIÓN: Protección mejorada contra múltiples llamadas simultáneas
        if (isReadingInProgress) {
            return;
        }
        
        // CORRECCIÓN: Verificar si ya estamos procesando este índice específico
        if (currentReadingIndex === index && onEndHandled) {
            return;
        }
        
        // Invalidate cualquier callback de una sesión anterior y cancelar TTS
        activeSpeakSessionId = ++speakSessionId;
        cancelAllTTS();
        
        // Protección contra índices negativos
        if (index < 0) {
            return;
        }
        
        // En fullscreen, buscar dentro del elemento fullscreen
        // Usar siempre el sistema normal de páginas
        // Sincronizar currentPage con window.currentPage antes de obtener la página
        if (typeof window.currentPage === 'number') {
            currentPage = window.currentPage;
        }
        const pageEl = pages[currentPage];
        if (!pageEl) {
            return;
        }
        
        const paragraphs = pageEl.querySelectorAll("p.paragraph");
        const translationBoxes = pageEl.querySelectorAll(".translation");
        
        // Verificar que el índice sea válido
        if (index >= paragraphs.length) {
            // Si estamos en modo de lectura automática, intentar avanzar a la siguiente página
            if (window.autoReading && window.currentPage < totalPages - 1) {
                window.currentPage++;
                window.currentIndex = 0;
                currentPage = window.currentPage;
                currentIndex = window.currentIndex;
                isReadingInProgress = false; // Liberar el flag ANTES de cambiar de página
                window._resumeIndexPending = 0; // Indicar a updatePageDisplay que reanude desde el inicio de la nueva página
                updatePageDisplay();
                // El readAndTranslate(0) será llamado por updatePageDisplay a través de _resumeIndexPending
                return; // Detener la ejecución actual para evitar llamadas duplicadas
            }
            isReadingInProgress = false; // Liberar el flag
            return;
        }
        
        if (index >= translationBoxes.length) {
            isReadingInProgress = false; // Liberar el flag
            return;
        }
        
        // CORRECCIÓN: Marcar que la lectura está en progreso y resetear flags
        isReadingInProgress = true;
        onEndHandled = false;
        currentReadingIndex = index;
        readingLog('read_start', { index, page: currentPage, session: activeSpeakSessionId });
        
        // CORRECCIÓN: Timeout de seguridad para casos donde onend no se dispare
        const timeoutSessionId = activeSpeakSessionId; // ligar a la sesión actual
        // Cancelar watchdog anterior si existiera y registrar el nuevo
        try { if (window.ReadingControl && window.ReadingControl.safetyTimeout) { clearTimeout(window.ReadingControl.safetyTimeout); } } catch(e) {}
        let safetyTimeout = setTimeout(() => {
            // Ignorar si cambió la sesión, si ya no estamos auto-leyendo o si ya cambiamos de índice
            if (timeoutSessionId !== activeSpeakSessionId) {
                return;
            }
            if (!window.autoReading) {
                return;
            }
            if (!autoReading) {
                return;
            }
            if (currentReadingIndex !== index) {
                return;
            }

            if (!onEndHandled && isReadingInProgress) {
                readingLog('safety_fire', { index, page: window.currentPage, session: activeSpeakSessionId });
                onEndHandled = true;
                isReadingInProgress = false;
                if (window.autoReading || autoReading) {
                    readAndTranslate(index + 1).catch(err => {
                    });
                }
            }
        }, 30000); // 30 segundos de timeout
        try { if (window.ReadingControl) window.ReadingControl.safetyTimeout = safetyTimeout; } catch(e) {}

        // En fullscreen, verificar si hemos leído 6 líneas o si terminamos la página
        const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;
        const maxLinesInFullscreen = 6;
        const shouldChangePage = isFullscreen ? 
        (index >= maxLinesInFullscreen || index >= paragraphs.length) : 
        (index >= paragraphs.length);
        
        if (shouldChangePage) {
            if (window.currentPage < totalPages - 1) {
                window.currentPage++;
                window.currentIndex = 0;
                currentPage = window.currentPage;
                currentIndex = window.currentIndex;
                isReadingInProgress = false; // Liberar el flag ANTES de cambiar de página
                window._resumeIndexPending = 0; // Indicar a updatePageDisplay que reanude desde el inicio de la nueva página
                updatePageDisplay();
                // El readAndTranslate(0) será llamado por updatePageDisplay a través de _resumeIndexPending
                return; // Detener la ejecución actual para evitar llamadas duplicadas
            } else {
                // Lectura completamente finalizada
                window.autoReading = false;
                autoReading = false;
                
                // El tiempo se guardará en stopReading, no aquí para evitar duplicaciones
                
                // Limpiar intervalo de actualización en tiempo real
                if (readingUpdateInterval) {
                    clearInterval(readingUpdateInterval);
                    readingUpdateInterval = null;
                }
                
                // CORRECCIÓN: Limpiar estados al finalizar lectura
                window.cleanupReadingStates();
                
                // Actualizar botón flotante
                if (typeof updateFloatingButton === 'function') {
                    updateFloatingButton();
                }
                
                // Mostrar modal de finalización usando la función genérica
                window.showLoadingRedirectModal(
                    'Lectura finalizada',
                    'Redirigiendo...',
                    'index.php?tab=practice',
                    2000
                );
            }
            isReadingInProgress = false; // Liberar el flag
            return;
        }

        const text = paragraphs[index].innerText.trim();
        
        // Verificar que tenemos un control de velocidad válido
        if (!rateInput) {
            rateInput = document.getElementById('rate');
        }
        const rate = rateInput ? parseFloat(rateInput.value) : 1;
        const box = translationBoxes[index];
        
        // Guardar posición actual para función de continuar
        if (typeof window.lastReadParagraphIndex !== 'undefined') {
            window.lastReadParagraphIndex = index;
            window.lastReadPageIndex = currentPage;
        }

        // Limpiar resaltado anterior
        document.querySelectorAll('.paragraph').forEach(p => {
            p.style.backgroundColor = '';
            p.style.border = '';
            p.style.borderRadius = '';
            p.style.padding = '';
        });
        
        // Resaltar párrafo actual
        paragraphs[index].style.backgroundColor = '#f0f9ff';
        paragraphs[index].style.border = '2px solid #93c5fd';
        paragraphs[index].style.borderRadius = '5px';
        paragraphs[index].style.padding = '5px';

        // Mostrar traducción SOLO del párrafo actual (línea a línea)
        if (box) {
            const showTranslations = (typeof window.translationsVisible === 'undefined') ? true : !!window.translationsVisible;
            if (showTranslations) {
                // Solo traducir si es el párrafo actual o si ya tiene traducción
                if (box.innerText.trim() === '') {
                    const textId = document.querySelector('#pages-container')?.dataset?.textId;
                    if (textId) {
                        // Traducir y guardar de forma asíncrona (sin bloquear la lectura)
                        translateAndSaveParagraph(text, box, textId);
                    } else {
                        // No hay textId, traducir sin guardar
                        translateParagraphOnly(text, box);
                    }
                }
            } else {
                // Si las traducciones están ocultas, asegurar que no se muestre ninguna
                box.innerText = '';
            }
        }

        // Solo hacer scroll si el párrafo y su traducción no están completamente visibles
        const rect = paragraphs[index].getBoundingClientRect();
        const translationRect = box ? box.getBoundingClientRect() : null;
        const windowHeight = window.innerHeight;
        
        // Considerar tanto el párrafo como su traducción
        const topLimit = rect.top;
        const bottomLimit = translationRect ? translationRect.bottom : rect.bottom;
        
        // Hacer scroll con más margen para que no se corte la traducción
        if (topLimit < 50 || bottomLimit > windowHeight - 50) {
            paragraphs[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // CANCELAR cualquier speech anterior antes de crear uno nuevo
        if (speechSynthesis.speaking) {
            speechSynthesis.cancel();
        }
        
        // Cancelar eSpeak si está disponible
        if (window.eSpeakAPI && window.eSpeakAPI.cancel) {
            window.eSpeakAPI.cancel();
        }

        let repeatCount = 0;
        async function speakAndMaybeRepeat(startWordBoundary = 0) {
            // NUEVO: Si hay un startWordBoundary, recortar el texto
            let speakText = text;
            let words = text.split(/\s+/);
            if (startWordBoundary > 0 && startWordBoundary < words.length) {
                speakText = words.slice(startWordBoundary).join(' ');
            }
            
            // Esperar a que el sistema de voz esté listo
            if (typeof window.getVoiceSystemReady === 'function') {
                await window.getVoiceSystemReady();
            }
            
            // Capturar sesión actual
            const localId = activeSpeakSessionId;
            // Forzar proveedor nativo para lectura por párrafos (más estable)
            window.useResponsiveVoiceForParagraphs = false;
            const useRV = false; // Mantener en false para usar SpeechSynthesis nativo
            if (useRV) {
                const success = window.leerTextoConResponsiveVoice(speakText, rate, {
                                                             onend: async (event) => {
                        if (localId !== activeSpeakSessionId) {
                            return;
                        }
                        
                        // CORRECCIÓN: Manejo inteligente de fragmentos y duplicados
                        if (event && event.rvIndex !== undefined && event.rvTotal !== undefined) {
                            // Si es un fragmento intermedio, ignorarlo
                            if (event.rvIndex < event.rvTotal - 1) {
                                return;
                            }
                        }
                        
                        // CORRECCIÓN: Solo aplicar protección contra duplicados si NO estamos en modo lectura doble
                        if (!window.doubleReadingMode) {
                            // CORRECCIÓN: Evitar múltiples llamadas al evento onend solo en lectura normal
                            if (onEndHandled) {
                                return;
                            }
                            onEndHandled = true;
                        }
                        
                        // CORRECCIÓN: Limpiar timeout de seguridad
                        if (typeof safetyTimeout !== 'undefined') {
                            clearTimeout(safetyTimeout);
                        }
                        
                        // Liberar el flag INMEDIATAMENTE al principio
                        isReadingInProgress = false;
                        
                        if (window.doubleReadingMode && repeatCount < 1) {
                            repeatCount++;
                            await speakAndMaybeRepeat(startWordBoundary);
                            return;
                        }
                        if (window.doubleReadingMode) {
                            doubleReadCurrentIndex = index;
                        }
                        if (autoReading) {
                            lastReadWordIndex = 0; // Reset al avanzar de párrafo
                            if (index + 1 >= paragraphs.length) {
                                // Terminó la página
                                onPageReadByTTS(currentPage);
                                if (window.currentPage < totalPages - 1) {
                                    window.currentPage++;
                                    window.currentIndex = 0;
                                    currentPage = window.currentPage;
                                    currentIndex = window.currentIndex;
                                    isReadingInProgress = false; // Liberar el flag ANTES de cambiar de página
                                    window._resumeIndexPending = 0; // Indicar a updatePageDisplay que reanude desde el inicio de la nueva página
                                    updatePageDisplay();
                                    // El readAndTranslate(0) será llamado por updatePageDisplay a través de _resumeIndexPending
                                    return; // Detener la ejecución actual
                                } else {
                                    autoReading = false;
                                    // CORRECCIÓN: Limpiar estados al finalizar lectura
                                    window.cleanupReadingStates();
                                    if (typeof updateFloatingButton === 'function') {
                                        updateFloatingButton();
                                    }
                                    window.showLoadingRedirectModal(
                                        'Lectura finalizada',
                                        'Redirigiendo...',
                                        'index.php?tab=practice',
                                        2000
                                    );
                                }
                            } else {
                                readAndTranslate(index + 1).catch(err => {
                                });
                            }
                        }
                    },
                    onerror: (error) => {
                        if (localId !== activeSpeakSessionId) {
                            return;
                        }
                        // Silenciar errores esperables por pausas/cambios
                        if (error && (error.error === 'interrupted' || error.name === 'interrupted' || error.error === 'canceled' || error.name === 'canceled')) {
                            isReadingInProgress = false;
                            return; // No avanzar de párrafo si fue cancelado/pausado
                        }
                        
                        // Para otros errores: fallback controlado al TTS nativo
                        isReadingInProgress = false;
                        try {
                            if (window.speechSynthesis && window.SpeechSynthesisUtterance) {
                                const fallbackUtterance = new SpeechSynthesisUtterance(speakText);
                                fallbackUtterance.rate = rate || 1.0;
                                fallbackUtterance.pitch = 1.0;
                                fallbackUtterance.volume = 1.0;
                                fallbackUtterance.lang = 'en-GB';
                                fallbackUtterance.onend = () => {
                                    if (localId !== activeSpeakSessionId) { return; }
                                    if (autoReading) { 
                                        readAndTranslate(index + 1).catch(err => {
                                        });
                                    }
                                };
                                window.speechSynthesis.speak(fallbackUtterance);
                                return;
                            }
                        } catch (e) {
                        }
                        if (autoReading) { 
                            readAndTranslate(index + 1).catch(err => {
                            });
                        }
                    }
                });
                if (!success) {
                    if (autoReading) { 
                        readAndTranslate(index + 1).catch(err => {
                        });
                    }
                }
            } else {
                if (window.speechSynthesis) {
                }
                
                try {
                    // Verificar que SpeechSynthesis esté disponible
                    if (!window.speechSynthesis || !window.speechSynthesis.speak) {
                        if (autoReading) {
                            advanceToNextParagraphSafely(index, currentPage, paragraphs, totalPages);
                        }
                        return;
                    }

                    const fallbackUtterance = new SpeechSynthesisUtterance(speakText);
                    fallbackUtterance.rate = rate || 1.0;
                    fallbackUtterance.pitch = 1.0;
                    fallbackUtterance.volume = 1.0;
                    fallbackUtterance.lang = 'en-GB';

                    fallbackUtterance.onboundary = function(event) {
                        if (event.name === 'word') {
                            lastReadWordIndex = event.charIndex === 0 ? startWordBoundary : startWordBoundary + countWords(speakText.substr(0, event.charIndex));
                            lastReadParagraphIndex = index;
                        }
                    };

                    fallbackUtterance.onend = async () => {
                        if (localId !== activeSpeakSessionId) { return; }
                        readingLog('onend', { index, page: currentPage, session: activeSpeakSessionId });
                        
                        // CORRECCIÓN: Solo aplicar protección contra duplicados si NO estamos en modo lectura doble
                        if (!window.doubleReadingMode) {
                            // CORRECCIÓN: Evitar múltiples llamadas al evento onend solo en lectura normal
                            if (onEndHandled) {
                                return;
                            }
                            onEndHandled = true;
                        }
                        
                        // CORRECCIÓN: Limpiar timeout de seguridad
                        if (typeof safetyTimeout !== 'undefined') {
                            clearTimeout(safetyTimeout);
                        }
                        
                        // Liberar el flag INMEDIATAMENTE al principio
                        isReadingInProgress = false;
                        
                        if (window.doubleReadingMode && repeatCount < 1) {
                            repeatCount++;
                            await speakAndMaybeRepeat(startWordBoundary);
                        } else {
                            if (window.doubleReadingMode) {
                                doubleReadCurrentIndex = index;
                            }
                            if (autoReading) {
                                lastReadWordIndex = 0;
                                if (index + 1 >= paragraphs.length) {
                                    onPageReadByTTS(currentPage);
                                    if (window.currentPage < totalPages - 1) {
                                        window.currentPage++;
                                        window.currentIndex = 0;
                                        currentPage = window.currentPage;
                                        currentIndex = window.currentIndex;
                                        isReadingInProgress = false; // Liberar el flag ANTES de cambiar de página
                                        window._resumeIndexPending = 0; // Indicar a updatePageDisplay que reanude desde el inicio de la nueva página
                                        updatePageDisplay();
                                        // El readAndTranslate(0) será llamado por updatePageDisplay a través de _resumeIndexPending
                                        return; // Detener la ejecución actual
                                    } else {
                                        autoReading = false;
                                        // CORRECCIÓN: Limpiar estados al finalizar lectura
                                        window.cleanupReadingStates();
                                        if (typeof updateFloatingButton === 'function') {
                                            updateFloatingButton();
                                        }
                                        const endMsg = document.createElement('div');
                                        const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;
                                        endMsg.style.cssText = `
                                            position: fixed;
                                            top: 50%;
                                            left: 50%;
                                            transform: translate(-50%, -50%);
                                            background: #F5F7FA;
                                            color: #1D3557;
                                            padding: 25px 35px;
                                            border: 1px solid #A8DADC;
                                            border-radius: 8px;
                                            font-size: 16px;
                                            text-align: center;
                                            z-index: ${isFullscreen ? '2147483647' : '999999'};
                                            box-shadow: 0 2px 10px rgba(29, 53, 87, 0.15);
                                            min-width: 200px;
                                        `;
                                        endMsg.innerHTML = `
                                            <div style="margin-bottom: 15px;">
                                                <div style="width: 24px; height: 24px; border: 2px solid #A8DADC; border-top: 2px solid #457B9D; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                                            </div>
                                            <div style="font-weight: 500; color: #1D3557; margin-bottom: 8px;">Lectura finalizada</div>
                                            <div style="font-size: 14px; color: #457B9D;">Redirigiendo...</div>
                                        `;
                                        if (!document.getElementById('spin-animation')) {
                                            const style = document.createElement('style');
                                            style.id = 'spin-animation';
                                            style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
                                            document.head.appendChild(style);
                                        }
                                        document.body.appendChild(endMsg);
                                        setTimeout(() => {
                                            window.location.href = "index.php?tab=practice";
                                        }, 2000);
                                    }
                                } else {
                                    readAndTranslate(index + 1);
                                }
                            }
                        }
                    };

                    // Agregar manejador de error para el fallback
                    fallbackUtterance.onerror = (error) => {
                        if (localId !== activeSpeakSessionId) { return; }
                        const errType = (error && (error.error || error.name)) || 'unknown';
                        readingLog('onerror', { index, page: currentPage, session: activeSpeakSessionId, err: errType });
                        // Limpiar timeout de seguridad si existe
                        if (typeof safetyTimeout !== 'undefined') {
                            clearTimeout(safetyTimeout);
                        }
                        // Liberar el flag en caso de error
                        isReadingInProgress = false;
                        // Si el error fue por interrupción/cancelación
                        const interrupted = error && (error.error === 'interrupted' || error.name === 'interrupted' || error.error === 'canceled' || error.name === 'canceled');
                        if (interrupted) {
                            // Si hay razones de pausa activas, no avanzar; la reanudación se encargará
                            const hasPauseReasons = window.ReadingPauseReasons && window.ReadingPauseReasons.size > 0;
                            if (hasPauseReasons) {
                                return;
                            }
                            // Si no hay razones activas y estamos en auto lectura, avanzar al siguiente párrafo
                            if (autoReading) {
                                setTimeout(() => { readAndTranslate(index + 1); }, 100);
                            }
                            return;
                        }
                        // Para otros errores, avanzar de forma segura
                        if (autoReading) {
                            advanceToNextParagraphSafely(index, currentPage, paragraphs, totalPages);
                        }
                    };

                    window.speechSynthesis.speak(fallbackUtterance);
                } catch (e) {
                    // Liberar el flag en caso de error
                    isReadingInProgress = false;
                    // Si falla, continuar con el siguiente párrafo de forma segura
                    if (autoReading) {
                        advanceToNextParagraphSafely(index, currentPage, paragraphs, totalPages);
                    }
                }
            }
        }
        
        // Llamar a la función de lectura (unificada)
        try {
            await speakAndMaybeRepeat(startWord);
        } catch (error) {
            // Liberar el flag en caso de error
            isReadingInProgress = false;
        }
    }

    // Función para encontrar la traducción correspondiente a un párrafo específico
    function findParagraphTranslation(paragraphText, fullTranslation) {
        // Por ahora, traducir el párrafo individual
        // En el futuro se puede implementar una lógica para extraer la traducción correspondiente
        return null;
    }

    // Función para contar palabras en un texto
    function countWords(text) {
        return text.trim().split(/\s+/).length;
    }

    // Función auxiliar para manejar el avance seguro al siguiente párrafo
    function advanceToNextParagraphSafely(currentIndex, currentPage, paragraphs, totalPages) {
        const nextIndex = currentIndex + 1;
        if (nextIndex < paragraphs.length) {
            readAndTranslate(nextIndex);
        } else {
            // Terminó la página
            onPageReadByTTS(currentPage);
            if (window.currentPage < totalPages - 1) {
                window.currentPage++;
                window.currentIndex = 0;
                currentPage = window.currentPage;
                currentIndex = window.currentIndex;
                isReadingInProgress = false; // Liberar el flag ANTES de cambiar de página
                window._resumeIndexPending = 0; // Indicar a updatePageDisplay que reanude desde el inicio de la nueva página
                updatePageDisplay();
                // El readAndTranslate(0) será llamado por updatePageDisplay a través de _resumeIndexPending
            } else {
                // Lectura completamente finalizada
                autoReading = false;
                // CORRECCIÓN: Limpiar estados al finalizar lectura
                window.cleanupReadingStates();
                if (typeof updateFloatingButton === 'function') {
                    updateFloatingButton();
                }
                window.showLoadingRedirectModal(
                    'Lectura finalizada',
                    'Redirigiendo...',
                    'index.php?tab=practice',
                    2000
                );
            }
        }
    }

    // Función para traducir y guardar un párrafo individual
    function translateAndSaveParagraph(text, box, textId) {
        // Verificar si ya tenemos la traducción en caché
        if (window.contentTranslationsCache && window.contentTranslationsCache[text]) {
            // Usar traducción del caché instantáneamente
            box.innerText = window.contentTranslationsCache[text];
            return;
        }
        
        // Si no está en caché, traducir con API (sin mostrar "Traduciendo...")
        // Usar fetch con timeout para evitar bloqueos
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos timeout
        
        const isActiveReading = window.autoReading ? '1' : '0';
        fetch('traduciones/translate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'word=' + encodeURIComponent(text) + '&active_reading=' + isActiveReading,
            signal: controller.signal
        })
        .then(res => {
            clearTimeout(timeoutId);
            return res.json();
        })
        .then(translationData => {
            // Verificar límite de traducciones
            if (window.LimitModal && window.LimitModal.checkResponse(translationData)) {
                box.innerText = '';
                return;
            }

            if (translationData.translation) {
                // Mostrar la traducción inmediatamente
                box.innerText = translationData.translation;
                
                // Guardar en caché local
                if (!window.contentTranslationsCache) {
                    window.contentTranslationsCache = {};
                }
                window.contentTranslationsCache[text] = translationData.translation;
                
                // Guardar la traducción de forma asíncrona (sin bloquear la lectura)
                // Usar un delay más largo para no interferir con la lectura
                setTimeout(() => {
                    const formData = new FormData();
                    formData.append('text_id', textId);
                    formData.append('content', text);
                    formData.append('translation', translationData.translation);
                    
                    fetch('traduciones/save_content_translation.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(saveData => {
                    })
                    .catch(error => {
                    });
                }, 1000); // Delay más largo para no interferir con la lectura
            } else {
                box.innerText = 'Traducción no encontrada.';
            }
        })
        .catch((error) => {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                box.innerText = 'Timeout en traducción.';
            } else {
                box.innerText = 'Error en la traducción.';
            }
        });
    }
    
    // Función para traducir solo un párrafo (sin guardar)
    function translateParagraphOnly(text, box) {
        const isActiveReading = window.autoReading ? '1' : '0';
        fetch('traduciones/translate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'word=' + encodeURIComponent(text) + '&active_reading=' + isActiveReading
        })
        .then(res => res.json())
        .then(data => {
            // Verificar límite de traducciones
            if (window.LimitModal && window.LimitModal.checkResponse(data)) {
                box.innerText = '';
                return;
            }

            if (data.translation) {
                box.innerText = data.translation;
            } else {
                box.innerText = 'Traducción no encontrada.';
            }
        })
        .catch(() => {
            box.innerText = 'Error en la traducción.';
        });
    }

    // Función para guardar la traducción del contenido completo
    window.saveCompleteContentTranslation = async function() {
        const textId = document.querySelector('#pages-container')?.dataset?.textId;
        if (!textId) return;
        
        // Obtener todo el contenido del texto
        const paragraphs = document.querySelectorAll('.paragraph');
        const fullContent = Array.from(paragraphs).map(p => p.innerText.trim()).join(' ').trim();
        
        if (!fullContent) return;
        
        try {
            // Traducir el contenido completo
            const response = await fetch('traduciones/translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'word=' + encodeURIComponent(fullContent)
            });
            
            const data = await response.json();
            
            if (data.translation) {
                // Guardar la traducción completa
                const formData = new FormData();
                formData.append('text_id', textId);
                formData.append('content', fullContent);
                formData.append('translation', data.translation);
                
                const saveResponse = await fetch('traduciones/save_content_translation.php', {
                    method: 'POST',
                    body: formData
                });
                
                const saveData = await saveResponse.json();
            }
        } catch (error) {
        }
    };

    // Hover intent (retrasa la traducción unos ms para evitar falsos positivos al pasar el cursor)
    let hoverIntentTimer = null;
    let hoverIntentEl = null;
    const HOVER_INTENT_DELAY = 250; // ms
    // Exponer limpiador de estado de hover para coordinar con el sidebar
    window._hoverPaused = false;
    window.clearHoverState = function() {
        try {
            if (hoverIntentTimer) { clearTimeout(hoverIntentTimer); hoverIntentTimer = null; }
            hoverIntentEl = null;
            if (typeof hideHoverTooltip === 'function') hideHoverTooltip();
            if (window.ReadingPauseReasons) window.ReadingPauseReasons.delete('word-hover');
            window._hoverPaused = false;
        } catch(e) {}
    };

    function assignWordClickHandlers() {
        document.querySelectorAll('.clickable-word').forEach(span => {
            // Agregar clase para compatibilidad con selección múltiple
            span.classList.add('word-clickable');
            // Hacer la palabra focuseable por teclado
            if (!span.hasAttribute('tabindex')) span.setAttribute('tabindex', '0');
            
            // Mantener el evento de clic para palabras individuales
            span.removeEventListener('click', handleWordClick);
            span.addEventListener('click', handleWordClick);

            // NUEVO: Hover/Focus para mostrar traducción sin clic (con retardo)
            span.removeEventListener('mouseenter', handleWordEnter);
            span.removeEventListener('mouseleave', handleWordLeave);
            span.removeEventListener('focus', handleWordEnter, true);
            span.removeEventListener('blur', handleWordLeave, true);
            span.addEventListener('mouseenter', handleWordEnter);
            span.addEventListener('mouseleave', handleWordLeave);
            span.addEventListener('focus', handleWordEnter, true);
            span.addEventListener('blur', handleWordLeave, true);
        });
    }

    function handleWordEnter(event) {
        // No activar hover si el sidebar de explicación está abierto
        const sidebarOpen = !!(document.getElementById('explainSidebar') && document.getElementById('explainSidebar').classList.contains('open'));
        if (sidebarOpen) return;

        const el = event.currentTarget;
        const word = el.textContent ? el.textContent.trim() : '';
        if (!word) return;

        // Preparar hover intent: no pausar ni traducir inmediatamente
        if (hoverIntentTimer) {
            clearTimeout(hoverIntentTimer);
            hoverIntentTimer = null;
        }
        hoverIntentEl = el;
        hoverIntentTimer = setTimeout(() => {
            // Confirmar que seguimos sobre el mismo elemento (o con foco)
            if (hoverIntentEl !== el) return;

            // Pausar lectura automática al entrar (solo si no está ya pausado por hover ni abierto el panel)
            const alreadyPausedByHover = window.ReadingPauseReasons && window.ReadingPauseReasons.has('word-hover');
            const panelOpenReason = window.ReadingPauseReasons && window.ReadingPauseReasons.has('explain');
            if (!alreadyPausedByHover && !panelOpenReason && window.pauseReading && !window._hoverPaused && window.isCurrentlyReading && !window.isCurrentlyPaused) {
                window.pauseReading('word-hover');
                window._hoverPaused = true;
            }

            // Resaltar la palabra
            clearWordHighlight();
            highlightWord(el, word);

            // Usar caché si existe
            if (el.dataset.translation) {
                showHoverTooltip(el, word, el.dataset.translation);
                return;
            }
            // Traducir y mostrar
            fetch('traduciones/translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'word=' + encodeURIComponent(word)
            })
            .then(res => res.json())
            .then(data => {
                // Verificar límite de traducciones
                if (window.LimitModal && window.LimitModal.checkResponse(data)) {
                    return;
                }

                const tr = data && data.translation ? data.translation : 'Sin traducción';
                el.dataset.translation = tr;
                showHoverTooltip(el, word, tr);
                // Guardar si aplica
                if (typeof saveTranslatedWord === 'function') {
                    const sentence = findSentenceContainingWord(el, word);
                    try { saveTranslatedWord(word, tr, sentence); } catch (e) {}
                }
            })
            .catch(() => {
                showHoverTooltip(el, word, 'Error en la traducción');
            });
        }, HOVER_INTENT_DELAY);
    }

    function handleWordLeave(event) {
        const el = event.currentTarget;
        // Cancelar hover intent si no se llegó a mostrar nada
        if (hoverIntentEl === el && hoverIntentTimer) {
            clearTimeout(hoverIntentTimer);
            hoverIntentTimer = null;
            hoverIntentEl = null;
        }
        
        // Si el sidebar está abierto, no reanudar al salir del hover
        const sidebarOpen = !!(document.getElementById('explainSidebar') && document.getElementById('explainSidebar').classList.contains('open'));
        if (sidebarOpen) {
            hideHoverTooltip();
            clearWordHighlight();
            return;
        }

        hideHoverTooltip();
        clearWordHighlight();
        // Reanudar si veníamos de hover
        if (window.resumeReading && window.ReadingPauseReasons && window.ReadingPauseReasons.has('word-hover')) {
            // Forzar reanudación con un ligero retardo para evitar carreras con nuevos hovers
            setTimeout(() => {
                try { window.resumeReading({ reason: 'word-hover', force: false }); window._hoverPaused = false; } catch (e) {}
            }, 100);
        } else {
            window._hoverPaused = false;
        }
    }

    // Tooltip persistente para hover/focus
    function showHoverTooltip(element, word, translation) {
        hideHoverTooltip();
        const tooltip = document.createElement('div');
        tooltip.className = 'simple-tooltip hover';
        tooltip.innerHTML = `<strong>${word}</strong> → ${translation}`;
        tooltip.style.cssText = `
            position: absolute;
            background: rgba(0, 0, 0, 0.51);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 15px;
            z-index: 999999;
            pointer-events: none;
            font-family: inherit;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            max-width: 320px;
            word-wrap: break-word;
            transition: opacity 0.2s;
        `;
        document.body.appendChild(tooltip);
        positionTooltipUnderWord(element, tooltip);
    }

    function hideHoverTooltip() {
        const existing = document.querySelector('.simple-tooltip.hover');
        if (existing) existing.remove();
    }

    function positionTooltipUnderWord(element, tooltip) {
        if (!element || !tooltip) return;
        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const scrollY = window.scrollY || window.pageYOffset;
        const scrollX = window.scrollX || window.pageXOffset;
        tooltip.style.top = (rect.bottom + 6 + scrollY) + 'px';
        tooltip.style.left = (rect.left + rect.width/2 - tooltipRect.width/2 + scrollX) + 'px';
    }
    
    function handleWordClick(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const word = this.textContent.trim();
    if (!word) return;
    
    // Limpiar destacado anterior y destacar la palabra actual
    clearWordHighlight();
    highlightWord(this, word);
    
    // Abrir el sidebar de explicación directamente con la palabra
    if (window.explainSidebar && typeof window.explainSidebar.showExplanation === 'function') {
        try { window.explainSidebar.showExplanation(word, this); } catch (e) {}
    }
    
    // Traducir y guardar la palabra (sin mostrar tooltip)
    fetch('traduciones/translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'word=' + encodeURIComponent(word)
    })
    .then(res => res.json())
    .then(data => {
        // Verificar límite de traducciones
        if (window.LimitModal && window.LimitModal.checkResponse(data)) {
            return;
        }

        const tr = data && data.translation ? data.translation : null;
        if (tr && typeof saveTranslatedWord === 'function') {
            // Encontrar la frase donde está la palabra
            const sentence = findSentenceContainingWord(this, word);
            try { saveTranslatedWord(word, tr, sentence); } catch (e) {}
        }
    })
    .catch(() => {
        // Silencioso: si falla la traducción, el sidebar ya muestra info de diccionario
    });
    }
    
    // Función para encontrar la frase que contiene la palabra
    function findSentenceContainingWord(element, word) {
        // Buscar el párrafo que contiene la palabra
        let paragraph = element.closest('p');
        if (!paragraph) {
            paragraph = element.closest('.paragraph');
        }
        
        if (paragraph) {
            // Obtener el texto completo del párrafo
            let fullText = paragraph.textContent || paragraph.innerText;
            
            // Limpiar el texto
            fullText = fullText.trim();
            
            // Dividir en oraciones (por punto, signo de exclamación o interrogación)
            const sentences = fullText.split(/[.!?]+/).filter(s => s.trim().length > 0);
            
            // Buscar la oración que contiene la palabra
            for (let sentence of sentences) {
                if (sentence.toLowerCase().includes(word.toLowerCase())) {
                    return sentence.trim() + '.';
                }
            }
            
            // Si no encuentra la oración específica, devolver todo el párrafo
            return fullText.length > 200 ? fullText.substring(0, 200) + '...' : fullText;
        }
        
        return `The ${word} is important.`; // Fallback
    }

    // Tooltip simple
    function showSimpleTooltip(element, word, translation) {
        const existing = document.querySelector('.simple-tooltip');
        if (existing) existing.remove();
        
        const tooltip = document.createElement('div');
        tooltip.className = 'simple-tooltip';
        tooltip.innerHTML = `<strong>${word}</strong> → ${translation}`;
        
        tooltip.style.cssText = `
            position: absolute;
            background: rgba(0, 0, 0, 0.51);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 15px;
            z-index: 999999;
            pointer-events: none;
            font-family: inherit;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            max-width: 320px;
            word-wrap: break-word;
            transition: opacity 0.2s;
        `;
        
        document.body.appendChild(tooltip);

        // Reanudar al primer clic/puntero en pantalla cerrando el tooltip
        let resumeOnClick = function() {
            if (tooltip) {
                tooltip.remove();
            }
            // No reanudar si el sidebar de explicación está abierto
            const sidebarOpen = !!(document.getElementById('explainSidebar') && document.getElementById('explainSidebar').classList.contains('open'));
            if (sidebarOpen) {
                document.removeEventListener('click', resumeOnClick, true);
                document.removeEventListener('pointerdown', resumeOnClick, true);
                document.removeEventListener('touchstart', resumeOnClick, true);
                return;
            }
            // Reanudar solo si la lectura estaba activa antes de pausar por clic
            if (window.resumeReading && (window.ReadingControl && window.ReadingControl.wasAutoReading)) {
                window.resumeReading();
            }
            document.removeEventListener('click', resumeOnClick, true);
            document.removeEventListener('pointerdown', resumeOnClick, true);
            document.removeEventListener('touchstart', resumeOnClick, true);
        };
        setTimeout(() => {
            document.addEventListener('click', resumeOnClick, true);
            document.addEventListener('pointerdown', resumeOnClick, true);
            document.addEventListener('touchstart', resumeOnClick, true);
        }, 0);
        
        // Posicionar justo debajo y centrado respecto a la palabra
        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const scrollY = window.scrollY || window.pageYOffset;
        const scrollX = window.scrollX || window.pageXOffset;
        tooltip.style.top = (rect.bottom + 6 + scrollY) + 'px'; // 6px debajo de la palabra
        tooltip.style.left = (rect.left + rect.width/2 - tooltipRect.width/2 + scrollX) + 'px';
        
        setTimeout(() => {
            tooltip.style.opacity = '0';
            setTimeout(() => {
                if (tooltip) tooltip.remove();
                // No reanudar mientras el sidebar de explicación esté abierto
                const sidebarOpen = !!(document.getElementById('explainSidebar') && document.getElementById('explainSidebar').classList.contains('open'));
                if (sidebarOpen) return;
                // Reanudar lectura automáticamente; si fue por palabra, forzar
                if (window.resumeReading && window.ReadingControl) {
                    if (window.ReadingControl.pausedBy === 'word-click') {
                        window.resumeReading(true);
                    } else if (window.ReadingControl.wasAutoReading) {
                        window.resumeReading();
                    }
                }
            }, 200);
        }, 4000); // 4 segundos
    }
    
    // Función para destacar una palabra
    function highlightWord(element, word) {
        // Guardar referencia a la palabra destacada
        window.currentHighlightedWord = {
            element: element,
            word: word
        };
        
        // Aplicar estilo de destacado
        element.style.backgroundColor = '#3B82F6';
        element.style.color = 'white';
        element.style.borderRadius = '4px';
        element.style.padding = '2px 4px';
        element.style.fontWeight = 'bold';
        element.style.transition = 'all 0.3s ease';
        
        // Añadir clase para identificación
        element.classList.add('word-highlighted');
    }
    
    // Función para limpiar el destacado de palabras
    function clearWordHighlight() {
        // Limpiar destacado anterior
        const previousHighlighted = document.querySelector('.word-highlighted');
        if (previousHighlighted) {
            previousHighlighted.style.backgroundColor = '';
            previousHighlighted.style.color = '';
            previousHighlighted.style.borderRadius = '';
            previousHighlighted.style.padding = '';
            previousHighlighted.style.fontWeight = '';
            previousHighlighted.classList.remove('word-highlighted');
        }
        
        // Limpiar referencia global
        window.currentHighlightedWord = null;
    }
    
    // Hacer función global para pantalla completa
    window.assignWordClickHandlers = assignWordClickHandlers;
    window.clearWordHighlight = clearWordHighlight;
    window.highlightWord = highlightWord;
    
    // Funciones auxiliares para resaltado y scroll
    function clearCurrentHighlight() {
        document.querySelectorAll('p').forEach(p => {
            p.classList.remove('currently-reading');
            p.style.backgroundColor = '';
            p.style.border = '';
            p.style.borderRadius = '';
            p.style.padding = '';
        });
    }

    function scrollToCurrentParagraph(paragraph) {
        const rect = paragraph.getBoundingClientRect();
        const threshold = window.innerHeight * 0.75; // 75% de la pantalla
        
        // Si el párrafo está en el último 25% de la pantalla, hacer scroll
        if (rect.bottom > threshold) {
            paragraph.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Inicializa vista
    updatePageDisplay();

    // Mostrar botón flotante si hay texto cargado
    if (document.querySelectorAll('.paragraph').length > 0) {
        if (typeof showFloatingButton === 'function') {
            showFloatingButton();
        }
        
        // Inicializar elementos necesarios para la lectura
        initializeTextReading();
    }
    
    function initializeTextReading() {
        // Asegurar que los elementos necesarios estén disponibles
        if (!rateInput) {
            const rateElement = document.getElementById('rate');
            if (rateElement) {
                rateInput = rateElement;
                rateValue = document.getElementById('rate-value');
                if (rateValue) {
                    // Mostrar el valor inicial como porcentaje
                    const initialValue = parseFloat(rateInput.value);
                    const percentageDisplay = Math.min(100, Math.round(initialValue * 100 + 10));
                    rateValue.textContent = percentageDisplay + '%';
                    
                    // Verificar si ya tiene un listener para evitar duplicados
                    if (!rateInput.hasAttribute('data-listener')) {
                        rateInput.addEventListener('input', (e) => {
                            const value = parseFloat(e.target.value);
                            const percentageDisplay = Math.min(100, Math.round(value * 100 + 10));
                            rateValue.textContent = percentageDisplay + '%';
                        });
                        rateInput.setAttribute('data-listener', 'true');
                    }
                }
            }
        }
    }

    // CORRECCIÓN: Función para inicializar estados de lectura
    window.initializeReadingStates = function() {
        // Asegurar que los estados estén en valores por defecto seguros
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = false;
        isReadingInProgress = false;
        autoReading = false;
        
        // Limpiar cualquier timer pendiente
        if (readingUpdateInterval) {
            clearInterval(readingUpdateInterval);
            readingUpdateInterval = null;
        }
        
        // Resetear tiempo de lectura
        readingStartTime = null;
        totalReadingTime = 0;
        
        // Actualizar el botón flotante
        if (typeof window.updateFloatingButton === 'function') {
            window.updateFloatingButton();
        }
    };
    
    // CORRECCIÓN: Función para limpiar estados al finalizar lectura
    window.cleanupReadingStates = function() {
        // Guardar tiempo de lectura si hay una sesión activa
        if (readingStartTime) {
            const currentTime = Date.now();
            const sessionTime = Math.floor((currentTime - readingStartTime) / 1000);
            totalReadingTime += sessionTime;
            saveReadingTime(totalReadingTime);
        }
        
        // Limpiar todos los estados
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = false;
        isReadingInProgress = false;
        autoReading = false;
        
        // Limpiar timers
        if (readingUpdateInterval) {
            clearInterval(readingUpdateInterval);
            readingUpdateInterval = null;
        }
        
        // Resetear tiempo
        readingStartTime = null;
        totalReadingTime = 0;
        
        // Mostrar header
        if (typeof window.showHeader === 'function') {
            window.showHeader();
        }
        
        // Actualizar botón flotante
        if (typeof window.updateFloatingButton === 'function') {
            window.updateFloatingButton();
        }
    };
    
    // Centralizar el control del header en las funciones de lectura
    window.startReading = async function() {
        // VERIFICACIÓN PROACTIVA DE LÍMITE
        // Solo verificamos si NO ha sido aceptado ya en esta sesión/texto
        if (!window._limitAceptado) {
            try {
                const response = await fetch('dePago/ajax_check_limit.php?active_reading=0');
                const data = await response.json();
                
                if (data && !data.can_translate) {
                    if (window.LimitModal) {
                        window.LimitModal.show(data.next_reset, true); // Forzar modal en acción explícita
                    }
                    return; // Detener inicio de lectura hasta que acepte
                }
            } catch (e) {
                console.error("Error verificando límite:", e);
            }
        }

        // Invalidar sesión anterior y cancelar cualquier lectura activa
        activeSpeakSessionId = ++speakSessionId;
        cancelAllTTS();
        
        // Asegurar que no quede ningún flag colgado de lecturas anteriores
        isReadingInProgress = false;
        onEndHandled = false;
        currentReadingIndex = -1;
        
        window.isCurrentlyReading = true;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = true;
        if (typeof window.updateFloatingButton === 'function') {
            window.updateFloatingButton();
        }
        
        // Iniciar tiempo de lectura solo si no estaba leyendo antes
        if (!readingStartTime) {
            readingStartTime = Date.now();
        }
        
        // Iniciar actualización en tiempo real cada 30 segundos
        if (!readingUpdateInterval) {
            readingUpdateInterval = setInterval(updateReadingTimeRealTime, 30000);
        }
        
        if (typeof window.hideHeader === 'function') {
            window.hideHeader();
        }
        
        // Si hay página guardada válida, posicionarse en esa página antes de pintar
        if (typeof window.lastReadPageIndex === 'number' && window.lastReadPageIndex >= 0) {
            const pagesCount = document.querySelectorAll('.page').length;
            if (pagesCount > 0) {
                const targetPage = Math.max(0, Math.min(window.lastReadPageIndex, pagesCount - 1));
                window.currentPage = targetPage;
                currentPage = targetPage;
            }
        }

        // CORRECCIÓN: Solo resetear currentIndex si no hay una posición guardada válida
        if (typeof window.lastReadParagraphIndex !== 'number' || window.lastReadParagraphIndex < 0) {
            window.currentIndex = 0;
            currentIndex = 0;
        } else {
            window.currentIndex = window.lastReadParagraphIndex;
            currentIndex = window.lastReadParagraphIndex;
        }
        
        // Cargar caché de traducciones antes de empezar a leer
        window.loadContentTranslationsCache();
        
        // Activar autoReading y configurar _resumeIndexPending antes de updatePageDisplay
        window.autoReading = true;
        autoReading = true;
        window._resumeIndexPending = currentIndex; // Indicar a updatePageDisplay que reanude desde currentIndex
        updatePageDisplay();
        // El readAndTranslate será llamado por updatePageDisplay a través de _resumeIndexPending
        if (typeof window.updateFloatingButton === 'function') {
            window.updateFloatingButton();
        }
    };

    window.pauseSpeech = function() {
        // CORRECCIÓN: Mantener el estado de lectura como true pero marcarlo como pausado
        window.isCurrentlyPaused = true;
        window.isCurrentlyReading = true; // Mantener como true para permitir reanudar
        
        // CORRECCIÓN: Limpiar flags de lectura pero mantener posición
        isReadingInProgress = false;
        onEndHandled = false;
        // No resetear currentReadingIndex para mantener la posición
        
        // Acumular tiempo hasta ahora (antes de marcar como no-leyendo)
        if (readingStartTime) {
            const currentTime = Date.now();
            const sessionTime = Math.floor((currentTime - readingStartTime) / 1000);
            totalReadingTime += sessionTime;
            readingStartTime = null; // Resetear para la próxima sesión
        }
        
        if (typeof window.showHeader === 'function') {
            window.showHeader();
        }
        
        // Invalidar sesión y cancelar cualquier TTS en curso
        activeSpeakSessionId = ++speakSessionId;
        cancelAllTTS();
        
        // CORRECCIÓN: Limpiar flags de lectura para evitar estados inconsistentes
        isReadingInProgress = false;
        onEndHandled = false;
        
        if (typeof window.updateFloatingButton === 'function') {
            window.updateFloatingButton();
        }
    };

    window.resumeSpeech = function() {
        // CORRECCIÓN: Verificar que realmente estemos pausados antes de reanudar
        if (!window.isCurrentlyPaused) {
            window.startReading();
            return;
        }
        
        // CORRECCIÓN: Cancelar cualquier lectura activa antes de reanudar
        if (window.speechSynthesis && window.speechSynthesis.speaking) {
            window.speechSynthesis.cancel();
        }
        
        // CORRECCIÓN: Cancelar ResponsiveVoice si está activo
        if (typeof window.detenerLecturaResponsiveVoice === 'function') {
            try {
                window.detenerLecturaResponsiveVoice();
            } catch(e) {
            }
        }
        
        // CORRECCIÓN: Verificar si ya hay una lectura en progreso
        if (isReadingInProgress) {
            return;
        }
        
        window.isCurrentlyPaused = false;
        window.isCurrentlyReading = true;
        isCurrentlyReading = true;
        
        // CORRECCIÓN: Limpiar flags pero mantener posición
        isReadingInProgress = false;
        onEndHandled = false;
        
        // Reiniciar el tiempo de lectura
        readingStartTime = Date.now();
        
        // CORRECCIÓN: Usar la posición más reciente disponible
        let resumeIndex = currentIndex;
        let resumeWord = 0;
        
        // Priorizar currentReadingIndex si está disponible (posición más precisa)
        if (currentReadingIndex >= 0) {
            resumeIndex = currentReadingIndex;
        } else if (typeof window.lastReadParagraphIndex === 'number' && window.lastReadParagraphIndex >= 0) {
            resumeIndex = window.lastReadParagraphIndex;
            resumeWord = (typeof window.lastReadWordIndex === 'number') ? window.lastReadWordIndex : 0;
        }
        
        // Asegurar que autoReading esté activo
        autoReading = true;
        
        if (typeof window.hideHeader === 'function') {
            window.hideHeader();
        }
        
        // Llamar a readAndTranslate con la posición correcta
        readAndTranslate(resumeIndex, resumeWord);
        
        if (typeof window.updateFloatingButton === 'function') {
            window.updateFloatingButton();
        }
    };

	    // Control reutilizable de pausa/reanudación para integrarse con tooltips, sidebars, etc.
	    window.ReadingControl = { pausedBy: null, wasAutoReading: false, retryTimer: null, safetyTimeout: null };
	    // Gestor de pausas por razón
	    if (!window.ReadingPauseReasons) {
	        window.ReadingPauseReasons = new Set();
	    }

	        window.pauseReading = function(by = 'unknown') {
        // Registrar razón de pausa
        try { window.ReadingPauseReasons.add(by); } catch(e) {}
        readingLog('pause', { by, reasons: Array.from(window.ReadingPauseReasons || []) });

        // Cancelar watchdog de seguridad si existe
        try { if (window.ReadingControl && window.ReadingControl.safetyTimeout) { clearTimeout(window.ReadingControl.safetyTimeout); window.ReadingControl.safetyTimeout = null; } } catch(e) {}

        // Pausar (no cancelar) para permitir reanudación consistente
        if (typeof window.pausarLecturaResponsiveVoice === 'function') {
            try { window.pausarLecturaResponsiveVoice(); } catch(e) {}
        } else if (window.speechSynthesis && (window.speechSynthesis.speaking || !window.speechSynthesis.paused)) {
            try { window.speechSynthesis.pause(); } catch(e) {}
        }

        // Liberar el flag para permitir relanzar lectura y recordar estado previo
        isReadingInProgress = false;
        // Recordar si la lectura automática estaba activa
        window.ReadingControl.wasAutoReading = !!(window.AppState && window.AppState.isCurrentlyReading) || !!isCurrentlyReading || !!autoReading;
        window.ReadingControl.pausedBy = by;

        // Flags de estado
        autoReading = false;
        isCurrentlyReading = false;
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = true;
        if (window.AppState) {
            window.AppState.isCurrentlyReading = false;
            window.AppState.isCurrentlyPaused = true;
        }
    };

    window.resumeReading = function(arg = false) {
        // Nuevo API: arg puede ser boolean (force) o { reason, force }
        let force = false;
        let reason = null;
        if (typeof arg === 'object' && arg !== null) {
            reason = arg.reason || null;
            force = !!arg.force;
        } else {
            force = !!arg;
        }

        // Si se indicó razón, eliminarla del set
        if (reason && window.ReadingPauseReasons && window.ReadingPauseReasons.has(reason)) {
            window.ReadingPauseReasons.delete(reason);
        }
        // Si no es force y aún quedan razones activas, no reanudar
        if (!force && window.ReadingPauseReasons && window.ReadingPauseReasons.size > 0) {
            const reasons = Array.from(window.ReadingPauseReasons || []);
            readingLog('resume_blocked', { reason, reasons });
            return;
        }

        readingLog('resume_called', { reason, force, reasons: Array.from(window.ReadingPauseReasons || []) });

        // Solo reanudar automáticamente si la pausa fue por tooltip/sidebar y estaba leyendo antes
        if (!force) {
            const by = window.ReadingControl.pausedBy;
            if (by !== 'explain' && by !== 'word-click' && by !== 'word-hover') return;
            if (!window.ReadingControl.wasAutoReading) return;
        }

        // Reanudar ResponsiveVoice si está disponible
        if (typeof window.reanudarLecturaResponsiveVoice === 'function') {
            // Si venimos de pausa por palabra o el motor está pausado, reanudar el motor y salir
            if (window.isCurrentlyPaused) {
                window.autoReading = true;
                autoReading = true;
                window.reanudarLecturaResponsiveVoice();
                window.isCurrentlyPaused = false;
                isCurrentlyReading = true;
                isReadingInProgress = false;
                if (typeof window.hideHeader === 'function') window.hideHeader();
                if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
                // Reset de control
                window.ReadingControl.pausedBy = null;
                window.ReadingControl.wasAutoReading = false;
                if (window.ReadingControl.retryTimer) { clearTimeout(window.ReadingControl.retryTimer); window.ReadingControl.retryTimer = null; }
                return;
            }
        }

        // PRIORIDAD: Si el motor nativo está en pausa, reanudarlo primero (antes que speaking)
        if (window.speechSynthesis && window.speechSynthesis.paused) {
            try { window.speechSynthesis.resume(); } catch(e) {}
            window.autoReading = true;
            autoReading = true;
            window.isCurrentlyPaused = false;
            isCurrentlyReading = true;
            isReadingInProgress = false;
            readingLog('resume_engine_resume', {});
            if (typeof window.hideHeader === 'function') window.hideHeader();
            if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
            if (window.ReadingControl) { window.ReadingControl.pausedBy = null; window.ReadingControl.wasAutoReading = false; if (window.ReadingControl.retryTimer) { clearTimeout(window.ReadingControl.retryTimer); window.ReadingControl.retryTimer = null; } }
            return;
        }

        // Esperar a que termine cualquier pronunciación aislada
        if (window.speechSynthesis && window.speechSynthesis.speaking) {
            if (window.ReadingControl && window.ReadingControl.retryTimer) clearTimeout(window.ReadingControl.retryTimer);
            if (window.ReadingControl) window.ReadingControl.retryTimer = setTimeout(() => window.resumeReading({ reason, force }), 300);
            readingLog('resume_wait_speaking', {});
            return;
        }

        // Reanudar directamente desde donde se quedó
        // Usar el índice del último párrafo leído si está disponible
        let resumeIndex = window.currentIndex || currentIndex;
        if (typeof window.lastReadParagraphIndex === 'number') {
        resumeIndex = window.lastReadParagraphIndex;
        }
        // Si conocemos la última palabra leída, continuar desde ahí
        const resumeWord = (typeof window.lastReadWordIndex === 'number') ? window.lastReadWordIndex : 0;
        
        // Reactivar el modo automático
        window.autoReading = true;
        autoReading = true;
        isCurrentlyReading = true;
        window.isCurrentlyReading = true;
         window.isCurrentlyPaused = false;
        if (window.AppState) {
            window.AppState.isCurrentlyReading = true;
            window.AppState.isCurrentlyPaused = false;
        }
        
        // Continuar desde el índice y palabra correctos si están disponibles
        readingLog('resume_restart', { resumeIndex, resumeWord });
        readAndTranslate(resumeIndex, resumeWord);
        
        // Reset
        if (window.ReadingControl) {
            window.ReadingControl.pausedBy = null;
            window.ReadingControl.wasAutoReading = false;
            if (window.ReadingControl.retryTimer) { clearTimeout(window.ReadingControl.retryTimer); window.ReadingControl.retryTimer = null; }
        }
    };

    window.stopReading = function() {
        window.autoReading = false;
        autoReading = false;
        // Invalidar la sesión actual y cancelar TTS
        activeSpeakSessionId = ++speakSessionId;
        cancelAllTTS();

        // Asegurar flags de control para permitir reinicio inmediato
        isReadingInProgress = false;
        onEndHandled = false;
        
        clearCurrentHighlight();
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = false;
        if (window.AppState) {
            window.AppState.isCurrentlyReading = false;
            window.AppState.isCurrentlyPaused = false;
        }
        
        // Acumular tiempo final y guardar
        if (readingStartTime) {
            const currentTime = Date.now();
            const sessionTime = Math.floor((currentTime - readingStartTime) / 1000);
            totalReadingTime += sessionTime;
            
            // Guardar el tiempo total acumulado
            if (totalReadingTime > 0) {
                saveReadingTime(totalReadingTime);
            }
            
            // Resetear variables
            readingStartTime = null;
            totalReadingTime = 0;
        }
        
        // Limpiar intervalo
        if (readingUpdateInterval) {
            clearInterval(readingUpdateInterval);
            readingUpdateInterval = null;
        }
        
        if (typeof window.showHeader === 'function') {
            window.showHeader();
        }
        if (typeof window.updateFloatingButton === 'function') {
            window.updateFloatingButton();
        }
    };
    
    // Función para iniciar lectura desde un índice específico
    window.startReadingFromIndex = function(startIndex) {
        if (autoReading) return;
        
        autoReading = true;
        currentIndex = startIndex;
        
        const paragraphs = document.querySelectorAll('.page[style=""] p.paragraph');
        if (paragraphs.length === 0) return;
        
        // Notificar al header que se inició la lectura
        if (typeof window.onReadingStart === 'function') {
            window.onReadingStart();
        }
        
        readAndTranslate(currentIndex);
    };
    
    // Funciones simples para pantalla completa  
    window.enableFullscreenPagination = function() {
        // En fullscreen, usar CSS para mostrar solo 6 párrafos por página
        const style = document.createElement('style');
        style.id = 'fullscreen-pagination-style';
        style.textContent = `
            .page p.paragraph:nth-child(n+13) {
                display: none !important;
            }
            .page p.translation:nth-child(n+14) {
                display: none !important;
            }
        `;
        document.head.appendChild(style);
    };
    
    window.disableFullscreenPagination = function() {
        // Quitar el CSS de fullscreen
        const style = document.getElementById('fullscreen-pagination-style');
        if (style) {
            style.remove();
        }
    };
    
    // Mostrar todas las traducciones del texto con caché
    window.showAllTranslations = async function() {
        const btn = document.getElementById('show-all-translations-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Traduciendo...';
        }
        
        const textId = document.querySelector('#pages-container')?.dataset?.textId;
        const paragraphs = document.querySelectorAll('.paragraph');
        const translationDivs = document.querySelectorAll('.translation');
        
        // Obtener traducciones guardadas en BD si existe textId
        let cachedTranslations = null;
        if (textId) {
            try {
                const isActiveReading = window.autoReading ? '1' : '0';
                const response = await fetch(`traduciones/get_content_translation.php?text_id=${textId}&active_reading=${isActiveReading}`);
                const data = await response.json();

                // Verificar límite de traducciones
                if (window.LimitModal && window.LimitModal.checkResponse(data)) {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = '📖 Mostrar todas las traducciones';
                    }
                    return;
                }

                if (data.success && data.translation) {
                    cachedTranslations = data.translation;
                }
            } catch (error) {
                // Si falla, continuar sin caché
            }
        }
        
        for (let i = 0; i < paragraphs.length; i++) {
            const p = paragraphs[i];
            const tDiv = translationDivs[i];
            if (!tDiv) continue;
            const text = p.innerText.trim();
            if (!text) continue;
            
            // Solo traduce si está vacío
            if (tDiv.innerText.trim() === '') {
                let translation = null;
                
                // 1. Buscar en caché primero
                if (cachedTranslations) {
                    if (Array.isArray(cachedTranslations)) {
                        // Formato JSON - buscar por contenido
                        const cachedItem = cachedTranslations.find(item => 
                            item.content && item.content.trim() === text
                        );
                        if (cachedItem) {
                            translation = cachedItem.translation;
                        }
                    }
                }
                
                // 2. Si no hay en caché, usar API
                if (!translation) {
                    try {
                        const isActiveReading = window.autoReading ? '1' : '0';
                        const res = await fetch('traduciones/translate.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'word=' + encodeURIComponent(text) + '&active_reading=' + isActiveReading
                        });
                        const data = await res.json();

                        // Verificar límite de traducciones
                        if (window.LimitModal && window.LimitModal.checkResponse(data)) {
                            if (btn) {
                                btn.disabled = false;
                                btn.textContent = '📖 Mostrar todas las traducciones';
                            }
                            return;
                        }

                        if (data.translation) {
                            translation = data.translation;
                            
                            // 3. Guardar en BD para futuras consultas
                            if (textId) {
                                try {
                                    const formData = new FormData();
                                    formData.append('text_id', textId);
                                    formData.append('content', text);
                                    formData.append('translation', translation);
                                    
                                    await fetch('traduciones/save_content_translation.php', {
                                        method: 'POST',
                                        body: formData
                                    });
                                } catch (saveError) {
                                }
                            }
                        }
                    } catch (error) {
                        translation = 'Error en la traducción.';
                    }
                }
                
                // 4. Mostrar traducción
                if (translation) {
                    tDiv.innerText = translation;
                } else {
                    tDiv.innerText = 'Traducción no encontrada.';
                }
            }
        }
        
        if (btn) {
            btn.disabled = false;
            // Cambiar a modo "Quitar traducciones"
            btn.textContent = '🗑️ Quitar traducciones';
            window.allTranslationsShown = true;
            // Al hacer clic, borrar traducciones y volver al estado inicial
            btn.onclick = function(e) {
                e.stopPropagation();
                if (typeof window.removeAllTranslations === 'function') {
                    window.removeAllTranslations();
                }
            };
        }
    }

    // Quitar todas las traducciones mostradas y volver a modo inicial
    window.removeAllTranslations = function() {
        const translationDivs = document.querySelectorAll('.translation');
        translationDivs.forEach(div => {
            div.innerText = '';
            // Limpiar estilo/clase por si acaso
            div.style.cssText = '';
            div.className = 'translation';
            if (div.dataset && div.dataset.originalContent) {
                delete div.dataset.originalContent;
            }
        });
        const btn = document.getElementById('show-all-translations-btn');
        if (btn) {
            btn.textContent = '📖 Mostrar todas las traducciones';
            window.allTranslationsShown = false;
            btn.onclick = function(e) {
                e.stopPropagation();
                window.showAllTranslations();
            };
        }
    }

    // --- NUEVO: Leer todo el texto dos veces desde el punto actual ---
    doubleReadButton = document.querySelector('.double-read');
    if (doubleReadButton) {
        doubleReadButton.onclick = function(e) {
            e.stopPropagation();
            if (!window.doubleReadingMode) {
                window.doubleReadingMode = true;
                doubleReadButton.textContent = '🔁 Leyendo dos veces (click para normal)';
                doubleReadCurrentIndex = window.currentIndex || currentIndex;
                if (!window.autoReading && !autoReading) {
                    window.autoReading = true;
                    autoReading = true;
                    readAndTranslate(window.currentIndex || currentIndex);
                }
            } else {
                window.doubleReadingMode = false;
                doubleReadButton.textContent = '🔊 Leer dos veces';
                // Si estaba leyendo, continuar en modo normal desde el siguiente párrafo
                if (window.autoReading || autoReading) {
                    speechSynthesis.cancel();
                    setTimeout(() => {
                        // Si estábamos en doble, avanzar al siguiente párrafo
                        if (doubleReadCurrentIndex !== null) {
                            readAndTranslate(doubleReadCurrentIndex + 1);
                            doubleReadCurrentIndex = null;
                        } else {
                            readAndTranslate(window.currentIndex || currentIndex);
                        }
                    }, 200);
                }
            }
        };
        doubleReadButton.textContent = '🔊 Leer dos veces';
    }

    // === PROGRESO DE LECTURA REAL ===
    window.readPages = [];
    window.readingProgressLoaded = false;
    window.readingProgressPercent = 0;

    // Cargar progreso guardado al cargar el texto
    function loadReadingProgress() {
        const textId = document.querySelector('.reading-area')?.getAttribute('data-text-id');
        if (!textId) return;
        const basePath = (window.location.pathname || '').replace(/[^\/]+$/, '');
        const url = basePath + 'ajax_progress_content.php?text_id=' + encodeURIComponent(textId);
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000);
        fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal })
            .then(res => res.ok ? res.json() : Promise.reject(new Error('HTTP ' + res.status)))
            .then(data => {
                if (data && data.pages_read && Array.isArray(data.pages_read)) {
                    window.readPages = data.pages_read;
                } else {
                    window.readPages = [];
                }
                window.readingProgressPercent = data.percent || 0;
                window.readingProgressLoaded = true;
                updateReadingProgressBar();
            })
            .catch(() => {
                // Silenciar errores de red en producción para no interrumpir la lectura
            })
            .finally(() => clearTimeout(timeoutId));
    }

    // Guardar progreso al backend
    function saveReadingProgress(percent) {
        const textId = document.querySelector('.reading-area')?.getAttribute('data-text-id');
        if (!textId) return;

        const basePath = (window.location.pathname || '').replace(/[^\/]+$/, '');
        const url = basePath + 'ajax_progress_content.php';
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000);
        const body = 'text_id=' + encodeURIComponent(textId) + '&percent=' + encodeURIComponent(percent) + '&pages_read=' + encodeURIComponent(JSON.stringify(window.readPages || []));
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body,
            signal: controller.signal
        })
        .catch(() => {
            // Evitar que un fallo de red rompa el flujo de lectura en producción
        })
        .finally(() => clearTimeout(timeoutId));
    }

    // Hook: al terminar la lectura automática de una página
    function onPageReadByTTS(pageIdx) {
        if (!window.readPages.includes(pageIdx)) {
            window.readPages.push(pageIdx);
            updateReadingProgressBar();
            // Si ya se leyeron todas las páginas, guardar 100%
            let pagesContainer = document.getElementById('pages-container');
            let totalPages = pagesContainer ? parseInt(pagesContainer.getAttribute('data-total-pages')) : 1;
            if (window.readPages.length === totalPages) {
                window.readingProgressPercent = 100;
                saveReadingProgress(100);
            } else {
                let pages = pagesContainer.querySelectorAll('.page');
                let totalWords = parseInt(pagesContainer.getAttribute('data-total-words'));
                let wordsRead = 0;
                window.readPages.forEach(idx => {
                    if (pages[idx]) {
                        let paragraphs = pages[idx].querySelectorAll('p.paragraph');
                        paragraphs.forEach(p => {
                            wordsRead += (p.innerText.match(/\b\w+\b/g) || []).length;
                        });
                    }
                });
                let percent = Math.round((wordsRead / totalWords) * 100);
                saveReadingProgress(percent);
            }
        }
    }

    // Al cargar la página, cargar progreso
    setTimeout(loadReadingProgress, 200);

    Object.defineProperty(window, 'isCurrentlyReading', {
        set: function(val) {
            this._isCurrentlyReading = val;
        },
        get: function() {
            return this._isCurrentlyReading;
        },
        configurable: true
    });
    window._isCurrentlyReading = false;

    Object.defineProperty(window, 'isCurrentlyPaused', {
        set: function(val) {
            this._isCurrentlyPaused = val;
        },
        get: function() {
            return this._isCurrentlyPaused;
        },
        configurable: true
    });
    window._isCurrentlyPaused = false;

    if (typeof window.onReadingStop === 'function') {
        const originalOnReadingStop = window.onReadingStop;
        window.onReadingStop = function() {
            originalOnReadingStop();
        };
    }

    // Función para cargar traducciones guardadas al cargar la página
    window.loadSavedContentTranslations = async function() {
        // Ya no cargamos todas las traducciones al inicio
        // Las traducciones aparecerán una por una durante la lectura
    };

    // Cargar traducciones guardadas al cargar la página
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.loadSavedContentTranslations);
    } else {
        window.loadSavedContentTranslations();
    }

    // Función para cargar todas las traducciones guardadas en caché
    window.loadContentTranslationsCache = async function() {
        const textId = document.querySelector('#pages-container')?.dataset?.textId;
        if (!textId) return;
        
        try {
            const isActiveReading = window.autoReading ? '1' : '0';
            const response = await fetch(`traduciones/get_content_translation.php?text_id=${textId}&active_reading=${isActiveReading}`);
            const data = await response.json();
            
            if (data.success && data.translation) {
                if (data.format === 'json' && Array.isArray(data.translation)) {
                    // Crear caché local con todas las traducciones
                    window.contentTranslationsCache = {};
                    data.translation.forEach(item => {
                        if (item.content && item.translation) {
                            window.contentTranslationsCache[item.content] = item.translation;
                        }
                    });
                }
            }
        } catch (error) {
        }
    };
}

// Asegurar inicialización cuando el DOM esté listo, solo si el usuario está logueado
function conditionalInitLector() {
    if (window.userLoggedIn) {
        initLector();
        // CORRECCIÓN: Inicializar estados después de cargar el lector
        if (typeof window.initializeReadingStates === 'function') {
            window.initializeReadingStates();
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', conditionalInitLector);
} else {
    conditionalInitLector();
}

// Exponer explícitamente en window para acceso desde consola
window.initLector = initLector;
