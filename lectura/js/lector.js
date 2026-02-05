// Variables globales para el control de velocidad
let rateInput = null;
let rateValue = null;

/**
 * Inicializa el lector de texto, configurando variables globales,
 * manejadores de eventos y la lógica de paginación y lectura automática.
 */
function initLector() {
    // Hacer estas variables globales para asegurar consistencia entre funciones
    if (typeof window.currentIndex === 'undefined') window.currentIndex = 0;
    if (typeof window.currentPage === 'undefined') window.currentPage = 0;
    if (typeof window.autoReading === 'undefined') window.autoReading = false;
    if (typeof window.isCurrentlyReading === 'undefined') window.isCurrentlyReading = false;
    if (typeof window.lastReadParagraphIndex === 'undefined') window.lastReadParagraphIndex = 0;
    if (typeof window.lastReadPageIndex === 'undefined') window.lastReadPageIndex = 0;
    
    // ELIMINADAS variables locales que causaban sombras y desincronización
    window.useResponsiveVoiceForParagraphs = false;
    
    // Variables para el tiempo de lectura
    let readingStartTime = null;
    let readingLastSaveTime = null;
    let readingUpdateInterval = null;
    let isCurrentlyReading = false;

    // Helper para cancelar cualquier proveedor de TTS activo
    /**
     * Cancela cualquier proceso de síntesis de voz activo de todos los proveedores conocidos.
     */
    function cancelAllTTS() {
        try { if (typeof window.detenerLecturaResponsiveVoice === 'function') window.detenerLecturaResponsiveVoice(); } catch (e) {}
        try { if (window.speechSynthesis) window.speechSynthesis.cancel(); } catch (e) {}
        try { if (window.eSpeakAPI && window.eSpeakAPI.cancel) window.eSpeakAPI.cancel(); } catch (e) {}
    }

    // Función centralizada para guardar tiempo de lectura
    /**
     * Guarda el tiempo de lectura acumulado para un texto específico.
     *
     * Envía una petición AJAX a `save_reading_time.php` para registrar la duración.
     *
     * @param {number} duration - La duración de la lectura en segundos.
     */
    function saveReadingTime(duration) {
        let textId = document.querySelector('#text.reading-area')?.getAttribute('data-text-id') || 
                     document.querySelector('.reading-area')?.getAttribute('data-text-id') ||
                     document.querySelector('[data-text-id]')?.getAttribute('data-text-id') ||
                     document.querySelector('#pages-container')?.getAttribute('data-text-id');
        
        if (textId && duration > 0) {
            fetch('lectura/ajax/save_reading_time.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'duration=' + duration + '&text_id=' + textId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && typeof window.updateCalendarNow === 'function') {
                    window.updateCalendarNow();
                }
            })
            .catch(() => {});
        }
    }

    // Función para actualizar tiempo en tiempo real
    /**
     * Actualiza el tiempo de lectura en tiempo real, guardando la duración
     * cada 30 segundos si la lectura está activa.
     */
    function updateReadingTimeRealTime() {
        if (readingLastSaveTime && isCurrentlyReading) {
            const currentTime = Date.now();
            const delta = Math.floor((currentTime - readingLastSaveTime) / 1000);
            if (delta >= 30) {
                saveReadingTime(delta);
                readingLastSaveTime = currentTime;
            }
        }
    }
    
    // Inicializar elementos con verificación
    rateInput = document.getElementById('rate');
    rateValue = document.getElementById('rate-value');
    
    if (!rateInput) {
        setTimeout(() => {
            rateInput = document.getElementById('rate');
            rateValue = document.getElementById('rate-value');
            if (rateInput && rateValue) {
                initializeRateControl();
            }
        }, 1000);
    } else {
        initializeRateControl();
    }
    
    /**
     * Inicializa los controles de velocidad de lectura (slider y display de porcentaje).
     *
     * Configura los event listeners para el slider de velocidad, permitiendo ajustar
     * la velocidad de lectura y mostrando el valor en porcentaje.
     */
    function initializeRateControl() {
        if (rateInput && rateValue) {
            const initialValue = parseFloat(rateInput.value);
            const percentageDisplay = Math.min(100, Math.round(initialValue * 100 + 10));
            rateValue.textContent = percentageDisplay + '%';

            if (!rateInput.hasAttribute('data-listener')) {
                rateInput.addEventListener('mousedown', (e) => {
                    rateInput.setAttribute('data-dragging', 'true');
                    handleSliderMouseMove(e);
                });
                
                rateInput.addEventListener('mousemove', (e) => {
                    if (rateInput.hasAttribute('data-dragging')) {
                        handleSliderMouseMove(e);
                    }
                });
                
                rateInput.addEventListener('mouseup', () => {
                    rateInput.removeAttribute('data-dragging');
                });
                
                rateInput.addEventListener('input', (e) => {
                    const value = parseFloat(e.target.value);
                    const percentageDisplay = Math.min(100, Math.round(value * 100 + 10));
                    rateValue.textContent = percentageDisplay + '%';
                });
                
                rateInput.setAttribute('data-listener', 'true');
            }
            
                    /**
             * Maneja el movimiento del ratón sobre el slider de velocidad para actualizar su valor.
             *
             * @param {MouseEvent} e - El objeto de evento del ratón.
             */
            function handleSliderMouseMove(e) {
                const rect = rateInput.getBoundingClientRect();
                const isVertical = rect.height > rect.width;
                let percentage;
                
                if (isVertical) {
                    const clickY = e.clientY - rect.top;
                    percentage = 1 - Math.max(0, Math.min(1, clickY / rect.height));
                } else {
                    const clickX = e.clientX - rect.left;
                    percentage = Math.max(0, Math.min(1, clickX / rect.width));
                }
                
                const min = parseFloat(rateInput.min);
                const max = parseFloat(rateInput.max);
                const newValue = min + (percentage * (max - min));
                const step = parseFloat(rateInput.step);
                const roundedValue = Math.round(newValue / step) * step;
                
                rateInput.value = roundedValue;
                const percentageDisplay = Math.min(100, Math.round(roundedValue * 100 + 10));
                rateValue.textContent = percentageDisplay + '%';
            }
        }
    }

    let prevBtn, nextBtn, pageNumber, totalPagesSpan;
    window.virtualPages = [];

    /**
     * Realiza la paginación dinámica del contenido del texto.
     *
     * Divide el texto en "páginas" virtuales basándose en la altura disponible de la ventana,
     * ajustando el contenido para que quepa sin desbordarse.
     *
     * @global {Array<Array<HTMLElement>>} window.virtualPages - Almacena los wrappers de párrafo para cada página virtual.
     */
    window.paginateDynamically = function() {
        const container = document.getElementById('pages-container');
        const viewport = document.getElementById('dynamic-content-viewport');
        if (!container || !viewport) return;

        const wrappers = Array.from(viewport.querySelectorAll('.paragraph-wrapper'));
        if (wrappers.length === 0) return;

        // Limpiar páginas virtuales previas
        window.virtualPages = [];
        
        // Altura disponible: Altura de la ventana menos encabezado y controles (aprox)
        const headerHeight = document.querySelector('.encabezado-lectura')?.offsetHeight || 0;
        const paginationControls = document.getElementById('pagination-controls'); // Renombrar para evitar conflicto
        const controlsHeight = paginationControls ? paginationControls.offsetHeight : 0; // Medir la altura real de los controles
        const availableHeight = window.innerHeight - headerHeight - controlsHeight;

        let currentPageWrappers = [];
        let currentHeight = 0;

        wrappers.forEach((wrapper, index) => {
            // Mostrar temporalmente para medir
            wrapper.style.display = 'block';
            const height = wrapper.offsetHeight;
            
            if (currentHeight + height > availableHeight && currentPageWrappers.length > 0) {
                window.virtualPages.push(currentPageWrappers);
                currentPageWrappers = [];
                currentHeight = 0;
            }
            
            currentPageWrappers.push(wrapper);
            currentHeight += height;
            
            // Ocultar después de medir
            wrapper.style.display = 'none';
        });

        if (currentPageWrappers.length > 0) {
            window.virtualPages.push(currentPageWrappers);
        }

        const totalPages = window.virtualPages.length;
        if (paginationControls) { // Usar la variable renombrada
            paginationControls.style.display = 'flex'; // Mostrar siempre para que el botón Play sea visible
            const totalPagesSpan = document.getElementById('total-pages');
            if (totalPagesSpan) totalPagesSpan.textContent = totalPages;
            
            // Ocultar botones de navegación si solo hay una página
            if (prevBtn) prevBtn.style.visibility = totalPages > 1 ? 'visible' : 'hidden';
            if (nextBtn) nextBtn.style.visibility = totalPages > 1 ? 'visible' : 'hidden';
        }

        // Asegurar que la página actual es válida tras repaginar
        if (window.currentPage >= totalPages) {
            window.currentPage = Math.max(0, totalPages - 1);
        }

        updatePageDisplay();
    };

    /**
     * Inicializa los controles de paginación (botones de anterior/siguiente, número de página)
     * y el control de visibilidad del selector de velocidad.
     *
     * Configura los event listeners para la navegación entre páginas y la repaginación al redimensionar la ventana.
     */
    window.initializePaginationControls = function() {
        prevBtn = document.getElementById("prev-page");
        nextBtn = document.getElementById("next-page");
        pageNumber = document.getElementById("page-number");
        totalPagesSpan = document.getElementById("total-pages");

        // Control de visibilidad del selector de velocidad
        const speedBtn = document.getElementById('speed-btn');
        const speedSelector = document.getElementById('speed-selector');
        if (speedBtn && speedSelector && !speedBtn.hasAttribute('data-listener-toggle')) {
            speedBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isVisible = speedSelector.style.display === 'block';
                speedSelector.style.display = isVisible ? 'none' : 'block';
            });
            
            // Cerrar al hacer clic fuera
            document.addEventListener('click', (e) => {
                if (!speedSelector.contains(e.target) && e.target !== speedBtn) {
                    speedSelector.style.display = 'none';
                }
            });
            
            speedBtn.setAttribute('data-listener-toggle', 'true');
        }

        if (prevBtn && !prevBtn.hasAttribute('data-listener')) {
            prevBtn.addEventListener("click", () => {
                if (window.currentPage > 0) {
                    window.currentPage--;
                    window.currentIndex = 0;
                    window._resumeIndexPending = 0;
                    updatePageDisplay();
                }
            });
            prevBtn.setAttribute('data-listener', 'true');
        }

        if (nextBtn && !nextBtn.hasAttribute('data-listener')) {
            nextBtn.addEventListener("click", () => {
                if (window.currentPage < window.virtualPages.length - 1) {
                    window.currentPage++;
                    window.currentIndex = 0;
                    window._resumeIndexPending = 0;
                    updatePageDisplay();
                    
                    if (window.currentPage === window.virtualPages.length - 1) {
                        onPageReadByTTS(window.currentPage);
                    }
                }
            });
            nextBtn.setAttribute('data-listener', 'true');
        }

        // Repaginar al cambiar el tamaño de la ventana
        window.removeEventListener('resize', window.paginateDynamically);
        window.addEventListener('resize', window.paginateDynamically);
        
        window.paginateDynamically();
    };

    /**
     * Actualiza la visualización de la página actual.
     *
     * Oculta todos los párrafos y muestra solo los que pertenecen a la página actual.
     * Actualiza los botones de navegación y el número de página.
     * Si la lectura automática está activa, inicia la lectura del primer párrafo de la página.
     */
    function updatePageDisplay() {
        if (!window.virtualPages || !window.virtualPages.length) return;
        const currentPageIdx = window.currentPage || 0;
        
        // Ocultar todos los párrafos
        document.querySelectorAll('.paragraph-wrapper').forEach(w => w.style.display = 'none');
        
        // Mostrar solo los de la página actual
        const currentWrappers = window.virtualPages[currentPageIdx];
        if (currentWrappers) {
            currentWrappers.forEach(w => w.style.display = 'block');
        }
        
        if (pageNumber) pageNumber.textContent = currentPageIdx + 1;
        if (prevBtn) prevBtn.disabled = currentPageIdx === 0;
        if (nextBtn) nextBtn.disabled = currentPageIdx === window.virtualPages.length - 1;
        
        assignWordClickHandlers();

        // Scroll to the current paragraph if it's not fully visible, considering fixed header and controls
        if (currentWrappers && currentWrappers[window.currentIndex]) {
            const paragraphElement = currentWrappers[window.currentIndex];
            const headerHeight = document.querySelector('.encabezado-lectura')?.offsetHeight || 0;
            const controlsHeight = document.getElementById('pagination-controls')?.offsetHeight || 0;
            const rect = paragraphElement.getBoundingClientRect();

            if (rect.top < headerHeight || rect.bottom > window.innerHeight - controlsHeight) {
                paragraphElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Adjust scroll position to account for fixed header
                window.scrollBy(0, -headerHeight);
            }
        }

        if (window.autoReading) {
            if (window.speechSynthesis) window.speechSynthesis.cancel();
            
            const continueAutoReading = () => {
                let nextIdx = 0;
                if (typeof window._resumeIndexPending === 'number' && window._resumeIndexPending >= 0) {
                    nextIdx = window._resumeIndexPending;
                    window._resumeIndexPending = null;
                }
                window.currentIndex = nextIdx;
                
                setTimeout(() => {
                    readAndTranslate(nextIdx).catch(() => {});
                }, 300);
            };

            if (!window._limitAceptado) {
                fetch('dePago/ajax_check_limit.php?active_reading=1')
                .then(res => res.json())
                .then(data => {
                    if (data && typeof data.can_translate !== 'undefined') {
                        window.translationLimitReached = !data.can_translate;
                    }
                    if (window.LimitModal && window.LimitModal.checkResponse(data)) {
                        if (window.pauseReading) window.pauseReading('limit-reached');
                        return;
                    }
                    continueAutoReading();
                })
                .catch(() => continueAutoReading());
            } else {
                continueAutoReading();
            }
        }
        updateReadingProgressBar();
    }

    /**
     * Actualiza la barra de progreso de lectura y el porcentaje mostrado.
     *
     * Calcula el progreso basándose en las palabras de las páginas ya leídas.
     */
    function updateReadingProgressBar() {
        let pagesContainer = document.getElementById('pages-container');
        if (!pagesContainer) return;
        let totalWords = parseInt(pagesContainer.getAttribute('data-total-words') || 1);
        let readPages = Array.isArray(window.readPages) ? window.readPages : [];
        let wordsRead = 0;
        
        if (window.readingProgressPercent === 100) {
            wordsRead = totalWords;
        } else if (readPages.length > 0) {
            readPages.forEach(idx => {
                if (window.virtualPages && window.virtualPages[idx]) {
                    window.virtualPages[idx].forEach(wrapper => {
                        let p = wrapper.querySelector('p.paragraph');
                        if (p) {
                            wordsRead += (p.innerText.match(/\b\w+\b/g) || []).length;
                        }
                    });
                }
            });
        }
        
        let percent = Math.round((wordsRead / totalWords) * 100);
        if (percent > 100) percent = 100;
        
        let inner = document.querySelector('.encabezado-lectura .progreso');
        let textEl = document.querySelector('.encabezado-lectura .porcentaje');
        if (inner) inner.style.width = percent + '%';
        if (textEl) textEl.textContent = percent + '%';
    }

    setTimeout(() => {
        window.initializePaginationControls();
        updatePageDisplay();
    }, 100);

    /**
     * Alterna el modo de lectura doble (leer cada párrafo dos veces).
     *
     * Actualiza el estado del botón correspondiente en el menú flotante.
     *
     * @global {boolean} window.doubleReadingMode - Indica si el modo de lectura doble está activo.
     */
    window.doubleReadingMode = false;
    window.readCurrentParagraphTwice = function() {
        window.doubleReadingMode = !window.doubleReadingMode;
        const btn = document.querySelector('.submenu-button.double-read');
        if (btn) {
            btn.innerHTML = window.doubleReadingMode ? '🔊 Repetición: ON' : '🔊 Leer dos veces';
            btn.classList.toggle('active', window.doubleReadingMode);
        }
    };
    let doubleReadCurrentIndex = null;
    let isReadingInProgress = false;
    let onEndHandled = false;
    let currentReadingIndex = -1;
    let speakSessionId = 0;
    let activeSpeakSessionId = 0;
    
    /**
     * Lee un párrafo y, opcionalmente, lo traduce.
     *
     * Esta es la función central de lectura automática. Gestiona la síntesis de voz,
     * la traducción de párrafos, el avance entre párrafos y páginas, y el modo de lectura doble.
     *
     * @param {number} index - El índice del párrafo a leer en la página actual.
     * @param {number} [startWord=0] - El índice de la palabra desde la que empezar a leer dentro del párrafo.
     */
    async function readAndTranslate(index, startWord = 0) {
        if (isReadingInProgress && currentReadingIndex !== index) {
            isReadingInProgress = false;
            onEndHandled = false;
        }
        if (isReadingInProgress) return;
        if (currentReadingIndex === index && onEndHandled) return;
        
        // Actualizar índices globales para persistencia
        window.currentIndex = index;
        window.lastReadParagraphIndex = index;
        window.lastReadPageIndex = window.currentPage;

        // Incrementar ID de sesión para invalidar cualquier proceso anterior
        activeSpeakSessionId = ++speakSessionId;
        window.activeSpeakSessionId = activeSpeakSessionId;
        
        // Guardar progreso en cada párrafo para asegurar persistencia total
        if (typeof onPageReadByTTS === 'function') {
            onPageReadByTTS(window.currentPage);
        }

        cancelAllTTS();
        if (index < 0) return;
        
        const currentPageIdx = window.currentPage || 0;
        const currentWrappers = window.virtualPages[currentPageIdx];
        if (!currentWrappers) return;
        
        const paragraphs = currentWrappers.map(w => w.querySelector('p.paragraph'));
        const translationBoxes = currentWrappers.map(w => w.querySelector('.translation'));
        
        if (index >= paragraphs.length) {
            if (window.autoReading && window.currentPage < window.virtualPages.length - 1) {
                window.currentPage++;
                window.currentIndex = 0;
                isReadingInProgress = false;
                window._resumeIndexPending = 0;
                updatePageDisplay();
                return;
            }
            isReadingInProgress = false;
            return;
        }
        
        isReadingInProgress = true;
        onEndHandled = false;
        currentReadingIndex = index;
        
        const timeoutSessionId = activeSpeakSessionId;
        try { if (window.ReadingControl && window.ReadingControl.safetyTimeout) { clearTimeout(window.ReadingControl.safetyTimeout); } } catch(e) {}
        let safetyTimeout = setTimeout(() => {
            if (timeoutSessionId !== activeSpeakSessionId || !window.autoReading || currentReadingIndex !== index) return;
            if (!onEndHandled && isReadingInProgress) {
                onEndHandled = true;
                isReadingInProgress = false;
                if (window.autoReading) {
                    readAndTranslate(index + 1).catch(() => {});
                }
            }
        }, 30000);
        try { if (window.ReadingControl) window.ReadingControl.safetyTimeout = safetyTimeout; } catch(e) {}

        const shouldChangePage = (index >= paragraphs.length);
        
        if (shouldChangePage) {
            if (window.currentPage < window.virtualPages.length - 1) {
                window.currentPage++;
                window.currentIndex = 0;
                isReadingInProgress = false;
                window._resumeIndexPending = 0;
                updatePageDisplay();
                return;
            } else {
                window.autoReading = false;
                autoReading = false;
                window.cleanupReadingStates();
                if (typeof updateFloatingButton === 'function') updateFloatingButton();
                if (window.userLoggedIn && typeof window.showLoadingRedirectModal === 'function') {
                    window.showLoadingRedirectModal('Lectura finalizada', 'Redirigiendo...', 'index.php?tab=practice', 2000);
                }
            }
            isReadingInProgress = false;
            return;
        }

        const text = paragraphs[index].innerText.trim();
        const rate = rateInput ? parseFloat(rateInput.value) : 1;
        const box = translationBoxes[index];
        
        document.querySelectorAll('.paragraph').forEach(p => p.classList.remove('currently-reading'));
        paragraphs[index].classList.add('currently-reading');

        if (box) {
            // Siempre intentar traducir si está vacío, la visibilidad se controla por CSS (.hide-translations)
            if (box.innerText.trim() === '') {
                const textId = document.querySelector('#pages-container')?.dataset?.textId;
                if (textId) {
                    translateAndSaveParagraph(text, box, textId);
                } else {
                    translateParagraphOnly(text, box);
                }
            }
        }

        const paragraphElement = paragraphs[index];
        const headerHeight = document.querySelector('.encabezado-lectura')?.offsetHeight || 0;
        const controlsHeight = document.getElementById('pagination-controls')?.offsetHeight || 0;
        const rect = paragraphElement.getBoundingClientRect();

        if (rect.top < headerHeight || rect.bottom > window.innerHeight - controlsHeight) {
            paragraphElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Adjust scroll position to account for fixed header
            window.scrollBy(0, -headerHeight);
        }

        cancelAllTTS();

        let repeatCount = 0;
        /**
         * Función interna para manejar la síntesis de voz y la repetición en modo de lectura doble.
         *
         * Utiliza `SpeechSynthesisUtterance` para leer el texto, gestiona los eventos `onend` y `onerror`,
         * y controla la lógica de repetición y avance al siguiente párrafo.
         *
         * @param {number} [startWordBoundary=0] - El índice de la palabra desde la que empezar a hablar.
         */
        async function speakAndMaybeRepeat(startWordBoundary = 0) {
            let speakText = text;
            let words = text.split(/\s+/);
            if (startWordBoundary > 0 && startWordBoundary < words.length) {
                speakText = words.slice(startWordBoundary).join(' ');
            }
            
            if (typeof window.getVoiceSystemReady === 'function') await window.getVoiceSystemReady();
            const localId = activeSpeakSessionId;
            
            const useRV = false; 
            if (useRV) {
                window.leerTextoConResponsiveVoice(speakText, rate, {
                    onend: async (event) => {
                        if (localId !== activeSpeakSessionId) return;
                        if (event && event.rvIndex !== undefined && event.rvTotal !== undefined && event.rvIndex < event.rvTotal - 1) return;
                        if (!window.doubleReadingMode) {
                            if (onEndHandled) return;
                            onEndHandled = true;
                        }
                        if (typeof safetyTimeout !== 'undefined') clearTimeout(safetyTimeout);
                        isReadingInProgress = false;
                        
                        if (window.doubleReadingMode && repeatCount < 1) {
                            repeatCount++;
                            await speakAndMaybeRepeat(startWordBoundary);
                            return;
                        }
                        if (window.autoReading) {
                            if (index + 1 >= paragraphs.length) {
                                onPageReadByTTS(window.currentPage);
                                if (window.currentPage < window.virtualPages.length - 1) {
                                    window.currentPage++;
                                    window.currentIndex = 0;
                                    isReadingInProgress = false;
                                    window._resumeIndexPending = 0;
                                    updatePageDisplay();
                                } else {
                                    window.autoReading = false;
                                    window.cleanupReadingStates();
                                    if (typeof updateFloatingButton === 'function') updateFloatingButton();
                                    if (window.userLoggedIn && typeof window.showLoadingRedirectModal === 'function') {
                                        window.showLoadingRedirectModal('Lectura finalizada', 'Redirigiendo...', 'index.php?tab=practice', 2000);
                                    }
                                }
                            } else {
                                readAndTranslate(index + 1).catch(() => {});
                            }
                        }
                    },
                    onerror: () => {
                        isReadingInProgress = false;
                        if (autoReading) readAndTranslate(index + 1).catch(() => {});
                    }
                });
            } else {
                try {
                    if (!window.speechSynthesis || !window.speechSynthesis.speak) {
                        if (autoReading) advanceToNextParagraphSafely(index, currentPage, paragraphs, totalPages);
                        return;
                    }

                    const utterance = new SpeechSynthesisUtterance(speakText);
                    utterance.rate = rate || 1.0;
                    utterance.lang = 'en-GB';

                    utterance.onboundary = function(event) {
                        if (event.name === 'word') {
                            window.lastReadWordIndex = event.charIndex === 0 ? startWordBoundary : startWordBoundary + (speakText.substr(0, event.charIndex).match(/\b\w+\b/g) || []).length;
                            window.lastReadParagraphIndex = index;
                        }
                    };

                    utterance.onend = async () => {
                        if (localId !== activeSpeakSessionId) return;
                        if (!window.doubleReadingMode) {
                            if (onEndHandled) return;
                            onEndHandled = true;
                        }
                        if (typeof safetyTimeout !== 'undefined') clearTimeout(safetyTimeout);
                        isReadingInProgress = false;
                        
                        if (window.doubleReadingMode && repeatCount < 1) {
                            repeatCount++;
                            await speakAndMaybeRepeat(startWordBoundary);
                        } else {
                            if (autoReading) {
                                if (index + 1 >= paragraphs.length) {
                                    onPageReadByTTS(window.currentPage);
                                    if (window.currentPage < window.virtualPages.length - 1) {
                                        window.currentPage++;
                                        window.currentIndex = 0;
                                        isReadingInProgress = false;
                                        window._resumeIndexPending = 0;
                                        updatePageDisplay();
                                    } else {
                                        autoReading = false;
                                        window.cleanupReadingStates();
                                        if (typeof updateFloatingButton === 'function') updateFloatingButton();
                                        if (window.userLoggedIn) {
                                            window.location.href = "index.php?tab=practice";
                                        }
                                    }
                                } else {
                                    readAndTranslate(index + 1);
                                }
                            }
                        }
                    };

                    utterance.onerror = (error) => {
                        if (localId !== activeSpeakSessionId) return;
                        if (typeof safetyTimeout !== 'undefined') clearTimeout(safetyTimeout);
                        isReadingInProgress = false;
                        if (autoReading) advanceToNextParagraphSafely(index, currentPage, paragraphs, totalPages);
                    };

                    window.speechSynthesis.speak(utterance);
                } catch (e) {
                    isReadingInProgress = false;
                    if (autoReading) advanceToNextParagraphSafely(index, currentPage, paragraphs, totalPages);
                }
            }
        }
        
        try {
            await speakAndMaybeRepeat(startWord);
        } catch (error) {
            isReadingInProgress = false;
        }
    }

    /**
     * Avanza al siguiente párrafo o página de forma segura en caso de error o finalización de la lectura.
     *
     * @param {number} currentIndex - El índice del párrafo actual.
     * @param {number} currentPageIdx - El índice de la página actual.
     * @param {Array<HTMLElement>} paragraphs - Un array de elementos de párrafo de la página actual.
     * @param {number} totalPages - El número total de páginas virtuales.
     */
    function advanceToNextParagraphSafely(currentIndex, currentPageIdx, paragraphs, totalPages) {
        const nextIndex = currentIndex + 1;
        if (nextIndex < paragraphs.length) {
            readAndTranslate(nextIndex);
        } else {
            onPageReadByTTS(currentPageIdx);
            if (window.currentPage < window.virtualPages.length - 1) {
                window.currentPage++;
                window.currentIndex = 0;
                isReadingInProgress = false;
                window._resumeIndexPending = 0;
                updatePageDisplay();
            } else {
                autoReading = false;
                window.cleanupReadingStates();
                if (typeof updateFloatingButton === 'function') updateFloatingButton();
                if (window.userLoggedIn && typeof window.showLoadingRedirectModal === 'function') {
                    window.showLoadingRedirectModal('Lectura finalizada', 'Redirigiendo...', 'index.php?tab=practice', 2000);
                }
            }
        }
    }

    /**
     * Incrementa el contador de uso de traducciones para un texto dado.
     *
     * Realiza una petición AJAX a `traduciones/ajax_increment_usage.php`.
     *
     * @param {string} text - El texto por el que se incrementará el uso.
     */
    window.incrementUsageOnly = async function(text) {
        if (!text || window.translationLimitReached) return;
        try {
            const response = await fetch('traduciones/ajax_increment_usage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'text=' + encodeURIComponent(text)
            });
            const data = await response.json();
            if (data && data.limit_reached) {
                window.translationLimitReached = true;
                if (window.LimitModal) window.LimitModal.checkResponse(data);
            }
        } catch (e) {}
    };

    /**
     * Traduce un párrafo y guarda la traducción en la base de datos.
     *
     * Utiliza el sistema de traducción híbrido y, si la traducción es exitosa,
     * la guarda en el caché de traducciones y en la base de datos.
     *
     * @param {string} text - El texto del párrafo a traducir.
     * @param {HTMLElement} box - El elemento DOM donde se mostrará la traducción.
     * @param {string} textId - El ID del texto al que pertenece el párrafo.
     */
    function translateAndSaveParagraph(text, box, textId) {
        if (window.translationLimitReached) { box.innerText = ''; return; }
        if (window.contentTranslationsCache && window.contentTranslationsCache[text]) {
            box.innerText = window.contentTranslationsCache[text];
            incrementUsageOnly(text);
            return;
        }
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);
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
            if (window.LimitModal && window.LimitModal.checkResponse(translationData)) {
                box.innerText = '';
                return;
            }
            if (translationData.translation) {
                box.innerText = translationData.translation;
                if (!window.contentTranslationsCache) window.contentTranslationsCache = {};
                window.contentTranslationsCache[text] = translationData.translation;
                
                setTimeout(() => {
                    const formData = new FormData();
                    formData.append('text_id', textId);
                    formData.append('content', text);
                    formData.append('translation', translationData.translation);
                    fetch('lectura/ajax/save_content_translation.php', { method: 'POST', body: formData });
                }, 1000);
            }
        })
        .catch(() => {
            clearTimeout(timeoutId);
        });
    }
    
    /**
     * Traduce un párrafo sin guardarlo en la base de datos.
     *
     * Utiliza el sistema de traducción híbrido y muestra la traducción en el elemento DOM.
     *
     * @param {string} text - El texto del párrafo a traducir.
     * @param {HTMLElement} box - El elemento DOM donde se mostrará la traducción.
     */
    function translateParagraphOnly(text, box) {
        if (window.translationLimitReached) { box.innerText = ''; return; }
        const isActiveReading = window.autoReading ? '1' : '0';
        fetch('traduciones/translate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'word=' + encodeURIComponent(text) + '&active_reading=' + isActiveReading
        })
        .then(res => res.json())
        .then(data => {
            if (window.LimitModal && window.LimitModal.checkResponse(data)) { box.innerText = ''; return; }
            if (data.translation) box.innerText = data.translation;
        })
        .catch(() => {});
    }

    /**
     * Guarda la traducción completa de todo el contenido de un texto.
     *
     * Concatena todos los párrafos del texto actual, los traduce y guarda
     * la traducción resultante en la base de datos.
     */
    window.saveCompleteContentTranslation = async function() {
        const textId = document.querySelector('#pages-container')?.dataset?.textId;
        if (!textId) return;
        const paragraphs = document.querySelectorAll('.paragraph');
        const fullContent = Array.from(paragraphs).map(p => p.innerText.trim()).join(' ').trim();
        if (!fullContent) return;
        
        try {
            const response = await fetch('traduciones/translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'word=' + encodeURIComponent(fullContent)
            });
            const data = await response.json();
            if (data.translation) {
                const formData = new FormData();
                formData.append('text_id', textId);
                formData.append('content', fullContent);
                formData.append('translation', data.translation);
                await fetch('lectura/ajax/save_content_translation.php', { method: 'POST', body: formData });
            }
        } catch (error) {}
    };

    /**
     * Limpia el estado de hover, ocultando cualquier tooltip y eliminando razones de pausa relacionadas.
     */
    window.clearHoverState = function() {
        if (typeof hideHoverTooltip === 'function') hideHoverTooltip();
        if (window.ReadingPauseReasons) window.ReadingPauseReasons.delete('word-hover');
        window._hoverPaused = false;
    };

    let wordClickTimer = null;
    /**
     * Asigna los manejadores de eventos de clic a todas las palabras clickeables.
     *
     * Elimina los listeners previos para evitar duplicados y añade el manejador `handleWordClick`.
     */
    function assignWordClickHandlers() {
        document.querySelectorAll('.clickable-word').forEach(span => {
            span.classList.add('word-clickable');
            if (!span.hasAttribute('tabindex')) span.setAttribute('tabindex', '0');
            span.removeEventListener('click', handleWordClick);
            span.addEventListener('click', handleWordClick);
        });
    }

    /**
     * Maneja el evento de clic en una palabra clickeable.
     *
     * Detecta si es un clic simple o doble. Un clic simple pausa la lectura y muestra
     * una traducción temporal. Un doble clic abre el sidebar de explicaciones.
     *
     * @param {Event} event - El objeto de evento del clic.
     */
    function handleWordClick(event) {
        event.preventDefault();
        const el = this;
        const word = el.textContent.trim();
        if (!word) return;

        if (wordClickTimer) {
            // DOBLE CLIC: Abrir explainSidebar
            clearTimeout(wordClickTimer);
            wordClickTimer = null;
            handleWordDoubleClick(el, word);
        } else {
            // CLIC SIMPLE: Pausa + Traducción temporal + Reanudación
            wordClickTimer = setTimeout(() => {
                wordClickTimer = null;
                handleWordSingleClick(el, word);
            }, 250);
        }
    }

    /**
     * Maneja un clic simple en una palabra.
     *
     * Pausa la lectura (si está activa), resalta la palabra, muestra un tooltip
     * con su traducción y programa la reanudación de la lectura.
     *
     * @param {HTMLElement} el - El elemento DOM de la palabra clickeada.
     * @param {string} word - La palabra clickeada.
     */
    function handleWordSingleClick(el, word) {
        // Pausar lectura si está activa
        const wasReading = window.isCurrentlyReading || window.autoReading;
        if (wasReading) {
            window.pauseReading('word-click');
            window._clickPaused = true;
        }

        clearWordHighlight();
        highlightWord(el, word);

        const showTranslation = (tr) => {
            showHoverTooltip(el, word, tr);
            // Programar desaparición y reanudación
            if (window._tooltipTimeout) clearTimeout(window._tooltipTimeout);
            window._tooltipTimeout = setTimeout(() => {
                hideHoverTooltip();
                clearWordHighlight();
                if (window._clickPaused) {
                    window.resumeReading({ reason: 'word-click' });
                    window._clickPaused = false;
                }
            }, 3000);
        };

        if (el.dataset.translation) {
            showTranslation(el.dataset.translation);
        } else {
            fetch('traduciones/translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'word=' + encodeURIComponent(word)
            })
            .then(res => res.json())
            .then(data => {
                const tr = data?.translation || 'Sin traducción';
                el.dataset.translation = tr;
                showTranslation(tr);
                if (typeof saveTranslatedWord === 'function') {
                    saveTranslatedWord(word, tr, findSentenceContainingWord(el, word));
                }
            });
        }
    }

    /**
     * Maneja un doble clic en una palabra.
     *
     * Cancela cualquier pausa por clic simple, resalta la palabra y abre el sidebar
     * de explicaciones para mostrar información detallada de la palabra.
     *
     * @param {HTMLElement} el - El elemento DOM de la palabra clickeada.
     * @param {string} word - La palabra clickeada.
     */
    function handleWordDoubleClick(el, word) {
        // Si había una pausa por clic simple, cancelarla para que el sidebar tome el control
        if (window._tooltipTimeout) clearTimeout(window._tooltipTimeout);
        window._clickPaused = false;

        clearWordHighlight();
        highlightWord(el, word);
        if (window.explainSidebar?.showExplanation) window.explainSidebar.showExplanation(word, el);
        
        fetch('traduciones/translate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'word=' + encodeURIComponent(word)
        })
        .then(res => res.json())
        .then(data => {
            if (data.translation && typeof saveTranslatedWord === 'function') {
                saveTranslatedWord(word, data.translation, findSentenceContainingWord(el, word));
            }
        });
    }

    /**
     * Encuentra la oración que contiene una palabra específica dentro de un párrafo.
     *
     * @param {HTMLElement} element - El elemento DOM de la palabra.
     * @param {string} word - La palabra a buscar.
     * @returns {string} La oración que contiene la palabra, o la palabra misma si no se encuentra una oración.
     */
    function findSentenceContainingWord(element, word) {
        let paragraph = element.closest('p') || element.closest('.paragraph');
        if (!paragraph) return word + '.';

        const paragraphText = paragraph.textContent;
        const lowerCaseParagraphText = paragraphText.toLowerCase();
        const lowerCaseWord = word.toLowerCase();

        let wordIndex = lowerCaseParagraphText.indexOf(lowerCaseWord);
        if (wordIndex === -1) return word + '.'; // Si la palabra no se encuentra, devolver solo la palabra.

        // Buscar el inicio de la frase
        let sentenceStartIndex = -1;
        // Busca . ! ? seguido de espacio y mayúscula, o el inicio del párrafo
        const sentenceStartRegex = /(?<=[.!?])\s*([A-ZÁÉÍÓÚÜÑ])/g;
        let match;
        while ((match = sentenceStartRegex.exec(paragraphText)) !== null) {
            if (match.index + match[0].length <= wordIndex) {
                sentenceStartIndex = match.index + match[0].length - match[1].length; // Ajustar para no incluir la mayúscula
            } else {
                break;
            }
        }
        if (sentenceStartIndex === -1) {
            sentenceStartIndex = 0; // Si no se encuentra un inicio de frase, asumir el inicio del párrafo
        }

        // Buscar el final de la frase
        let sentenceEndIndex = paragraphText.length;
        // Busca . ! ? seguido de espacio o fin de cadena
        const sentenceEndRegex = /[.!?](?=\s|$)/g;
        while ((match = sentenceEndRegex.exec(paragraphText)) !== null) {
            if (match.index >= wordIndex + word.length) {
                sentenceEndIndex = match.index + match[0].length;
                break;
            }
        }

        let sentence = paragraphText.substring(sentenceStartIndex, sentenceEndIndex).trim();

        // Si la frase extraída es muy corta o no contiene la palabra, intentar una aproximación más simple
        if (sentence.length < word.length || !sentence.toLowerCase().includes(lowerCaseWord)) {
            const sentences = paragraphText.split(/(?<=[.!?])\s+/).filter(s => s.trim().length > 0);
            for (let s of sentences) {
                if (s.toLowerCase().includes(lowerCaseWord)) return s.trim();
            }
        }

        return sentence;
    }

    /**
     * Resalta visualmente una palabra en el texto.
     *
     * Añade la clase 'word-highlighted' al elemento y almacena la palabra destacada globalmente.
     *
     * @param {HTMLElement} element - El elemento DOM de la palabra a resaltar.
     */
    function highlightWord(element) {
        window.currentHighlightedWord = { element, word: element.textContent.trim() };
        element.classList.add('word-highlighted');
    }

    /**
     * Elimina el resaltado de cualquier palabra previamente destacada.
     */
    function clearWordHighlight() {
        document.querySelector('.word-highlighted')?.classList.remove('word-highlighted');
        window.currentHighlightedWord = null;
    }

    /**
     * Muestra un tooltip simple con la palabra y su traducción al pasar el ratón.
     *
     * @param {HTMLElement} element - El elemento DOM sobre el que se muestra el tooltip.
     * @param {string} word - La palabra original.
     * @param {string} translation - La traducción de la palabra.
     */
    function showHoverTooltip(element, word, translation) {
        hideHoverTooltip();
        const tooltip = document.createElement('div');
        tooltip.className = 'simple-tooltip hover';
        tooltip.innerHTML = `<strong>${word}</strong> → ${translation}`;
        tooltip.style.cssText = `position: absolute; background: rgba(0,0,0,0.8); color: white; padding: 8px 12px; border-radius: 6px; z-index: 999999; pointer-events: none;`;
        document.body.appendChild(tooltip);
        const rect = element.getBoundingClientRect();
        tooltip.style.top = (rect.bottom + window.scrollY + 5) + 'px';
        tooltip.style.left = (rect.left + window.scrollX) + 'px';
    }

    /**
     * Oculta cualquier tooltip de hover visible.
     */
    function hideHoverTooltip() {
        document.querySelector('.simple-tooltip.hover')?.remove();
    }

    /**
     * Inicializa o reinicia los estados de lectura globales.
     *
     * Establece todas las banderas de lectura a `false` y limpia cualquier intervalo de actualización.
     */
    window.initializeReadingStates = function() {
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = false;
        isReadingInProgress = false;
        autoReading = false;
        if (readingUpdateInterval) clearInterval(readingUpdateInterval);
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };
    
    /**
     * Limpia y restablece todos los estados de lectura al finalizar o detener la lectura.
     *
     * Guarda el tiempo de lectura restante, restablece las banderas de lectura,
     * limpia intervalos y muestra el encabezado.
     */
    window.cleanupReadingStates = function() {
        if (readingLastSaveTime) saveReadingTime(Math.floor((Date.now() - readingLastSaveTime) / 1000));
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = false;
        isReadingInProgress = false;
        autoReading = false;
        if (readingUpdateInterval) clearInterval(readingUpdateInterval);
        if (typeof window.showHeader === 'function') window.showHeader();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };
    
    /**
     * Inicia la lectura automática del texto.
     *
     * Oculta el encabezado, repagina dinámicamente, verifica los límites de traducción,
     * inicializa los estados de lectura y comienza a leer desde el último punto guardado.
     */
    window.startReading = async function() {
        document.querySelector('.encabezado-lectura')?.classList.add('hidden');
        // Repaginar inmediatamente para aprovechar el espacio del encabezado oculto
        if (typeof window.paginateDynamically === 'function') {
            window.paginateDynamically();
        }
        if (!window._limitAceptado) {
            try {
                const res = await fetch('dePago/ajax_check_limit.php?active_reading=0');
                const data = await res.json();
                if (data && !data.can_translate) {
                    window.LimitModal?.show(data.next_reset, true);
                    return;
                }
            } catch (e) {}
        }
        activeSpeakSessionId = ++speakSessionId;
        cancelAllTTS();
        isReadingInProgress = false;
        onEndHandled = false;
        window.isCurrentlyReading = true;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = true;
        readingStartTime = Date.now();
        readingLastSaveTime = Date.now();
        if (!readingUpdateInterval) readingUpdateInterval = setInterval(updateReadingTimeRealTime, 30000);
        if (typeof window.hideHeader === 'function') window.hideHeader();
        
        // Asegurar que retomamos desde el último punto guardado
        if (typeof window.lastReadPageIndex === 'number') window.currentPage = window.lastReadPageIndex;
        window.currentIndex = window.lastReadParagraphIndex || 0;
        
        window.autoReading = true;
        autoReading = true;
        window._resumeIndexPending = window.currentIndex;
        updatePageDisplay();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };

    /**
     * Pausa la síntesis de voz.
     *
     * Muestra el encabezado, repagina el contenido, establece el estado de pausa
     * y guarda el tiempo de lectura acumulado.
     */
    window.pauseSpeech = function() {
        document.querySelector('.encabezado-lectura')?.classList.remove('hidden');
        // Repaginar para ajustar el texto al espacio con encabezado visible
        if (typeof window.paginateDynamically === 'function') {
            window.paginateDynamically();
        }
        window.isCurrentlyPaused = true;
        isReadingInProgress = false;
        onEndHandled = false;
        if (readingLastSaveTime) saveReadingTime(Math.floor((Date.now() - readingLastSaveTime) / 1000));
        readingLastSaveTime = null;
        if (typeof window.showHeader === 'function') window.showHeader();
        cancelAllTTS();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };

    /**
     * Reanuda la síntesis de voz.
     *
     * Oculta el encabezado, restablece el estado de lectura y reanuda la lectura
     * desde el último punto.
     */
    window.resumeSpeech = function() {
        document.querySelector('.encabezado-lectura')?.classList.add('hidden');
        // Si no estaba pausado, iniciar normalmente (que ya maneja el índice guardado)
        if (!window.isCurrentlyPaused) { window.startReading(); return; }
        
        cancelAllTTS();
        if (isReadingInProgress) return;
        
        window.isCurrentlyPaused = false;
        isCurrentlyReading = true;
        readingLastSaveTime = Date.now();
        autoReading = true;
        if (typeof window.hideHeader === 'function') window.hideHeader();
        
        // Usar siempre el índice guardado para consistencia
        const resumeIdx = (typeof window.lastReadParagraphIndex !== 'undefined') ? window.lastReadParagraphIndex : 0;
        readAndTranslate(resumeIdx);
        
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };

    /**
     * Pausa la lectura, deteniendo la síntesis de voz y actualizando los estados.
     *
     * @param {string} [reason=''] - La razón por la que se pausa la lectura (ej. 'word-click', 'explain').
     */
    window.pauseReading = function(reason = '') {
        window.isCurrentlyPaused = true;
        // Solo cambiar el estado visual si NO es una pausa temporal por clic o hover
        if (reason !== 'word-click' && reason !== 'word-hover') {
            window.isCurrentlyReading = false;
            window.autoReading = false;
            isCurrentlyReading = false;
            autoReading = false;
        }
        isReadingInProgress = false;
        onEndHandled = false;
        if (window.speechSynthesis) window.speechSynthesis.pause();
        cancelAllTTS();
        // Limpiar timeout de seguridad al pausar para evitar retrasos
        try { if (window.ReadingControl && window.ReadingControl.safetyTimeout) { clearTimeout(window.ReadingControl.safetyTimeout); window.ReadingControl.safetyTimeout = null; } } catch(e) {}
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };

    /**
     * Reanuda la lectura desde el punto donde se pausó.
     *
     * Restablece los estados de lectura y reanuda la síntesis de voz o inicia la lectura
     * desde el último párrafo si no estaba pausada por `speechSynthesis`.
     *
     * @param {object} [options={}] - Opciones para la reanudación (ej. `reason`, `force`).
     */
    window.resumeReading = function(options = {}) {
        window.isCurrentlyPaused = false;
        window.isCurrentlyReading = true;
        window.autoReading = true;
        isReadingInProgress = false;
        onEndHandled = false;
        isCurrentlyReading = true;
        autoReading = true;
        
        if (window.speechSynthesis && window.speechSynthesis.paused) {
            window.speechSynthesis.resume();
        } else {
            // Usar siempre el índice guardado para consistencia - SIN DELAY para reanudación instantánea
            const resumeIdx = (typeof window.lastReadParagraphIndex !== 'undefined') ? window.lastReadParagraphIndex : 0;
            readAndTranslate(resumeIdx);
        }
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };

    /**
     * Detiene completamente la lectura.
     *
     * Restablece todos los estados de lectura, cancela la síntesis de voz,
     * limpia temporizadores y restablece la interfaz de usuario.
     */
    window.stopReading = function() {
        // 1. DESACTIVAR ESTADOS INMEDIATAMENTE (Antes de cualquier otra operación)
        window.autoReading = false;
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = false;
        isReadingInProgress = false;
        onEndHandled = false;

        // 2. INVALIDAR SESIÓN DE HABLA
        speakSessionId++;
        activeSpeakSessionId = speakSessionId;
        window.activeSpeakSessionId = activeSpeakSessionId;

        // 3. CANCELAR TTS INMEDIATAMENTE
        cancelAllTTS();

        // 4. LIMPIAR TEMPORIZADORES DE INTERACCIÓN
        if (window._tooltipTimeout) {
            clearTimeout(window._tooltipTimeout);
            window._tooltipTimeout = null;
        }
        if (wordClickTimer) {
            clearTimeout(wordClickTimer);
            wordClickTimer = null;
        }
        window._clickPaused = false;
        window._hoverPaused = false;
        hideHoverTooltip();
        clearWordHighlight();

        // 5. UI Y PAGINACIÓN (Esto puede disparar eventos, por eso los estados ya deben estar en false)
        document.querySelector('.encabezado-lectura')?.classList.remove('hidden');
        if (typeof window.paginateDynamically === 'function') {
            window.paginateDynamically();
        }
        
        if (readingLastSaveTime) saveReadingTime(Math.floor((Date.now() - readingLastSaveTime) / 1000));
        readingLastSaveTime = null;
        if (readingUpdateInterval) clearInterval(readingUpdateInterval);
        if (typeof window.showHeader === 'function') window.showHeader();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };

    /**
     * Habilita la paginación en modo de pantalla completa (limitando el número de párrafos visibles).
     *
     * Añade una etiqueta `<style>` al `<head>` para aplicar estilos CSS que limitan la visibilidad de los párrafos.
     */
    window.enableFullscreenPagination = function() {
        const style = document.createElement('style');
        style.id = 'fullscreen-pagination-style';
        style.textContent = `.page p.paragraph:nth-child(n+13) { display: none !important; } .page p.translation:nth-child(n+14) { display: none !important; }`;
        document.head.appendChild(style);
    };
    
    /**
     * Deshabilita la paginación en modo de pantalla completa.
     *
     * Elimina la etiqueta `<style>` que se añadió para limitar la visibilidad de los párrafos.
     */
    window.disableFullscreenPagination = function() {
        document.getElementById('fullscreen-pagination-style')?.remove();
    };

    document.addEventListener('click', function(e) {
        if ((document.fullscreenElement || document.webkitFullscreenElement) && e.target.classList.contains('clickable-word')) {
            const word = e.target.textContent.trim();
            if (word && window.explainSidebar?.showExplanation) window.explainSidebar.showExplanation(word, e.target);
        }
    });

    const handleFSChange = () => {
        setTimeout(() => { if (typeof window.assignWordClickHandlers === 'function') window.assignWordClickHandlers(); }, 500);
    };
    document.addEventListener('fullscreenchange', handleFSChange);
    document.addEventListener('webkitfullscreenchange', handleFSChange);

    /**
     * Muestra todas las traducciones de los párrafos del texto.
     *
     * Verifica los límites de traducción, obtiene las traducciones (primero del caché/BD, luego de la API)
     * y las muestra en los elementos de traducción correspondientes.
     */
    window.showAllTranslations = async function() {
        if (window.translationLimitReached) { window.LimitModal?.show(null, true); return; }
        const btn = document.getElementById('show-all-translations-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Traduciendo...'; }
        const textId = document.querySelector('#pages-container')?.dataset?.textId;
        const paragraphs = document.querySelectorAll('.paragraph');
        const translationDivs = document.querySelectorAll('.translation');
        
        let cachedTranslations = null;
        if (textId) {
            try {
                const res = await fetch(`lectura/ajax/get_content_translation.php?text_id=${textId}&active_reading=${window.autoReading ? '1' : '0'}`);
                const data = await res.json();
                if (data.success) cachedTranslations = data.translation;
            } catch (e) {}
        }
        
        for (let i = 0; i < paragraphs.length; i++) {
            const p = paragraphs[i];
            const tDiv = translationDivs[i];
            if (!tDiv || tDiv.innerText.trim() !== '') continue;
            const text = p.innerText.trim();
            let translation = null;
            if (cachedTranslations && Array.isArray(cachedTranslations)) {
                const item = cachedTranslations.find(it => it.content?.trim() === text);
                if (item) { translation = item.translation; incrementUsageOnly(text); }
            }
            if (!translation) {
                try {
                    const res = await fetch('traduciones/translate.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'word=' + encodeURIComponent(text) });
                    const data = await res.json();
                    if (data.translation) {
                        translation = data.translation;
                        if (textId) {
                            const fd = new FormData(); fd.append('text_id', textId); fd.append('content', text); fd.append('translation', translation);
                            fetch('lectura/ajax/save_content_translation.php', { method: 'POST', body: fd });
                        }
                    }
                } catch (e) {}
            }
            if (translation) tDiv.innerText = translation;
        }
        if (btn) { btn.disabled = false; btn.textContent = '🗑️ Quitar traducciones'; btn.onclick = (e) => { e.stopPropagation(); window.removeAllTranslations(); }; }
    };

    /**
     * Elimina todas las traducciones mostradas en los párrafos del texto.
     *
     * Restablece el texto de los elementos de traducción y el estado del botón "Mostrar todas las traducciones".
     */
    window.removeAllTranslations = function() {
        document.querySelectorAll('.translation').forEach(div => { div.innerText = ''; div.className = 'translation'; });
        const btn = document.getElementById('show-all-translations-btn');
        if (btn) { btn.textContent = '📖 Mostrar todas las traducciones'; btn.onclick = (e) => { e.stopPropagation(); window.showAllTranslations(); }; }
    };

    window.readPages = [];
    /**
     * Carga el progreso de lectura guardado para el texto actual.
     *
     * Obtiene las páginas leídas y el porcentaje de progreso desde `ajax_progress_content.php`
     * y actualiza la barra de progreso.
     */
    function loadReadingProgress() {
        const textId = document.querySelector('.reading-area')?.getAttribute('data-text-id');
        if (!textId) return;
        fetch('lectura/ajax/ajax_progress_content.php?text_id=' + encodeURIComponent(textId))
            .then(res => res.json())
            .then(data => {
                if (data && data.pages_read) window.readPages = data.pages_read;
                window.readingProgressPercent = data.percent || 0;
                updateReadingProgressBar();
            }).catch(() => {});
    }

    /**
     * Guarda el progreso de lectura actual del usuario.
     *
     * Envía el porcentaje de lectura y las páginas leídas a `ajax_progress_content.php`.
     *
     * @param {number} percent - El porcentaje de lectura completado.
     * @param {number} [finish=0] - Indica si la lectura se ha completado (1) o no (0).
     */
    function saveReadingProgress(percent, finish = 0) {
        const textId = document.querySelector('.reading-area')?.getAttribute('data-text-id') || 
                       document.querySelector('#pages-container')?.getAttribute('data-text-id');
        if (!textId) return;
        let body = 'text_id=' + encodeURIComponent(textId) + '&percent=' + encodeURIComponent(percent) + '&pages_read=' + encodeURIComponent(JSON.stringify(window.readPages || []));
        if (finish) body += '&finish=1';
        
        fetch('lectura/ajax/ajax_progress_content.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, 
            body 
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log('Progreso guardado correctamente');
            }
        })
        .catch(err => console.error('Error guardando progreso:', err));
    }

    /**
     * Registra que una página ha sido leída por el sistema TTS.
     *
     * Añade el índice de la página al array `window.readPages` si no está ya presente
     * y actualiza la barra de progreso. Si todas las páginas han sido leídas, guarda el progreso como 100%.
     *
     * @param {number} pageIdx - El índice de la página que ha sido leída.
     */
    function onPageReadByTTS(pageIdx) {
        if (!window.readPages.includes(pageIdx)) {
            window.readPages.push(pageIdx);
            updateReadingProgressBar();
            const totalPages = window.virtualPages.length;
            if (window.readPages.length === totalPages) saveReadingProgress(100, 1);
            else {
                const totalWords = parseInt(document.getElementById('pages-container')?.getAttribute('data-total-words') || 1);
                let wordsRead = 0;
                window.readPages.forEach(idx => {
                    if (window.virtualPages[idx]) {
                        window.virtualPages[idx].forEach(wrapper => {
                            let p = wrapper.querySelector('p.paragraph');
                            if (p) wordsRead += (p.innerText.match(/\b\w+\b/g) || []).length;
                        });
                    }
                });
                saveReadingProgress(Math.round((wordsRead / totalWords) * 100));
            }
        }
    }

    setTimeout(loadReadingProgress, 200);

    if (!window._clickToggleInitialized) {
        document.addEventListener('click', function(e) {
            if (!window.userLoggedIn) return;

            // Desactivar clic en pantalla para play/pause en dispositivos pequeños (< 768px)
            if (window.innerWidth < 768) return;

            const container = document.getElementById('pages-container');
            if (!container || !container.contains(e.target)) return;
            const interactive = ['.clickable-word', 'button', 'a', 'input', 'select', 'textarea', '.tab-navigation', '#floating-menu', '.explain-sidebar', '.simple-tooltip'];
            for (const s of interactive) if (e.target.closest(s)) return;
            
            // Solo toggle si NO estamos pinchando en el botón flotante (que ya tiene su propio listener)
            if (e.target.closest('#floating-btn')) return;
            
            if (typeof window.toggleFloatingPlayPause === 'function') window.toggleFloatingPlayPause();
        });
        window._clickToggleInitialized = true;
    }

    updatePageDisplay();
}

window.initLector = initLector;
