// Variables globales para el control de velocidad
let rateInput = null;
let rateValue = null;

function initLector() {
    // Hacer estas variables globales para asegurar consistencia entre funciones
    if (typeof window.currentIndex === 'undefined') window.currentIndex = 0;
    if (typeof window.currentPage === 'undefined') window.currentPage = 0;
    if (typeof window.autoReading === 'undefined') window.autoReading = false;
    
    let currentIndex = window.currentIndex;
    let currentPage = window.currentPage;
    let autoReading = window.autoReading;
    window.useResponsiveVoiceForParagraphs = false;
    
    // Variables para el tiempo de lectura
    let readingStartTime = null;
    let readingLastSaveTime = null;
    let readingUpdateInterval = null;
    let isCurrentlyReading = false;

    // Helper para cancelar cualquier proveedor de TTS activo
    function cancelAllTTS() {
        try { if (typeof window.detenerLecturaResponsiveVoice === 'function') window.detenerLecturaResponsiveVoice(); } catch (e) {}
        try { if (window.speechSynthesis) window.speechSynthesis.cancel(); } catch (e) {}
        try { if (window.eSpeakAPI && window.eSpeakAPI.cancel) window.eSpeakAPI.cancel(); } catch (e) {}
    }

    // Función centralizada para guardar tiempo de lectura
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
            
            function handleSliderMouseMove(e) {
                const rect = rateInput.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const width = rect.width;
                const percentage = Math.max(0, Math.min(1, clickX / width));
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
                    window.currentIndex = 0;
                    window._resumeIndexPending = 0;
                    currentPage = window.currentPage;
                    currentIndex = window.currentIndex;
                    updatePageDisplay();
                }
            });
            prevBtn.setAttribute('data-listener', 'true');
        }

        if (nextBtn && !nextBtn.hasAttribute('data-listener')) {
            nextBtn.addEventListener("click", () => {
                if (window.currentPage < totalPages - 1) {
                    window.currentPage++;
                    window.currentIndex = 0;
                    window._resumeIndexPending = 0;
                    currentPage = window.currentPage;
                    currentIndex = window.currentIndex;
                    updatePageDisplay();
                }
            });
            nextBtn.setAttribute('data-listener', 'true');
        }
    };

    function updatePageDisplay() {
        if (!pages.length) return;
        if (typeof window.currentPage === 'number') currentPage = window.currentPage;
        
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

        if (window.autoReading || autoReading) {
            if (window.speechSynthesis) window.speechSynthesis.cancel();
            
            const continueAutoReading = () => {
                let nextIdx = 0;
                if (typeof window._resumeIndexPending === 'number' && window._resumeIndexPending >= 0) {
                    nextIdx = window._resumeIndexPending;
                    window._resumeIndexPending = null;
                }
                window.currentIndex = nextIdx;
                currentIndex = window.currentIndex;
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

    function updateReadingProgressBar() {
        let pagesContainer = document.getElementById('pages-container');
        if (!pagesContainer) return;
        let totalWords = parseInt(pagesContainer.getAttribute('data-total-words') || 1);
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

    window.doubleReadingMode = false;
    let doubleReadCurrentIndex = null;
    let isReadingInProgress = false;
    let onEndHandled = false;
    let currentReadingIndex = -1;
    let speakSessionId = 0;
    let activeSpeakSessionId = 0;
    
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
        if (typeof window.lastReadPageIndex !== 'undefined') {
            window.lastReadPageIndex = window.currentPage;
        }

        activeSpeakSessionId = ++speakSessionId;
        cancelAllTTS();
        if (index < 0) return;
        
        if (typeof window.currentPage === 'number') currentPage = window.currentPage;
        const pageEl = pages[currentPage];
        if (!pageEl) return;
        
        const paragraphs = pageEl.querySelectorAll("p.paragraph");
        const translationBoxes = pageEl.querySelectorAll(".translation");
        
        if (index >= paragraphs.length) {
            if (window.autoReading && window.currentPage < totalPages - 1) {
                window.currentPage++;
                window.currentIndex = 0;
                currentPage = window.currentPage;
                currentIndex = window.currentIndex;
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
            if (timeoutSessionId !== activeSpeakSessionId || !window.autoReading || !autoReading || currentReadingIndex !== index) return;
            if (!onEndHandled && isReadingInProgress) {
                onEndHandled = true;
                isReadingInProgress = false;
                if (window.autoReading || autoReading) {
                    readAndTranslate(index + 1).catch(() => {});
                }
            }
        }, 30000);
        try { if (window.ReadingControl) window.ReadingControl.safetyTimeout = safetyTimeout; } catch(e) {}

        const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;
        const maxLinesInFullscreen = 6;
        const shouldChangePage = isFullscreen ? (index >= maxLinesInFullscreen || index >= paragraphs.length) : (index >= paragraphs.length);
        
        if (shouldChangePage) {
            if (window.currentPage < totalPages - 1) {
                window.currentPage++;
                window.currentIndex = 0;
                currentPage = window.currentPage;
                currentIndex = window.currentIndex;
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
        
        if (typeof window.lastReadParagraphIndex !== 'undefined') {
            window.lastReadParagraphIndex = index;
            window.lastReadPageIndex = currentPage;
        }

        document.querySelectorAll('.paragraph').forEach(p => p.classList.remove('currently-reading'));
        paragraphs[index].classList.add('currently-reading');

        if (box) {
            const showTranslations = (typeof window.translationsVisible === 'undefined') ? true : !!window.translationsVisible;
            if (showTranslations && box.innerText.trim() === '') {
                const textId = document.querySelector('#pages-container')?.dataset?.textId;
                if (textId) {
                    translateAndSaveParagraph(text, box, textId);
                } else {
                    translateParagraphOnly(text, box);
                }
            } else if (!showTranslations) {
                box.innerText = '';
            }
        }

        const rect = paragraphs[index].getBoundingClientRect();
        if (rect.top < 50 || rect.bottom > window.innerHeight - 50) {
            paragraphs[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        cancelAllTTS();

        let repeatCount = 0;
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
                        if (autoReading) {
                            if (index + 1 >= paragraphs.length) {
                                onPageReadByTTS(currentPage);
                                if (window.currentPage < totalPages - 1) {
                                    window.currentPage++;
                                    window.currentIndex = 0;
                                    currentPage = window.currentPage;
                                    currentIndex = window.currentIndex;
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
                                    onPageReadByTTS(currentPage);
                                    if (window.currentPage < totalPages - 1) {
                                        window.currentPage++;
                                        window.currentIndex = 0;
                                        currentPage = window.currentPage;
                                        currentIndex = window.currentIndex;
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

    function advanceToNextParagraphSafely(currentIndex, currentPage, paragraphs, totalPages) {
        const nextIndex = currentIndex + 1;
        if (nextIndex < paragraphs.length) {
            readAndTranslate(nextIndex);
        } else {
            onPageReadByTTS(currentPage);
            if (window.currentPage < totalPages - 1) {
                window.currentPage++;
                window.currentIndex = 0;
                currentPage = window.currentPage;
                currentIndex = window.currentIndex;
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

    window.clearHoverState = function() {
        if (typeof hideHoverTooltip === 'function') hideHoverTooltip();
        if (window.ReadingPauseReasons) window.ReadingPauseReasons.delete('word-hover');
        window._hoverPaused = false;
    };

    function assignWordClickHandlers() {
        document.querySelectorAll('.clickable-word').forEach(span => {
            span.classList.add('word-clickable');
            if (!span.hasAttribute('tabindex')) span.setAttribute('tabindex', '0');
            span.removeEventListener('click', handleWordClick);
            span.addEventListener('click', handleWordClick);
            span.addEventListener('mouseenter', handleWordEnter);
            span.addEventListener('mouseleave', handleWordLeave);
        });
    }

    function handleWordEnter(event) {
        const sidebarOpen = !!(document.getElementById('explainSidebar')?.classList.contains('open'));
        if (sidebarOpen) return;
        const el = event.currentTarget;
        const word = el.textContent?.trim();
        if (!word) return;

        // Cancelar cualquier temporizador previo para evitar marcado agresivo
        if (window._hoverTimeout) clearTimeout(window._hoverTimeout);

        window._hoverTimeout = setTimeout(() => {
            if (window.pauseReading && !window._hoverPaused && (window.isCurrentlyReading || window.autoReading)) {
                window.pauseReading('word-hover');
                window._hoverPaused = true;
            }
            
            // Limpiar cualquier resaltado previo antes de marcar la nueva
            clearWordHighlight();
            highlightWord(el, word);
            
            if (el.dataset.translation) {
                showHoverTooltip(el, word, el.dataset.translation);
                return;
            }
            fetch('traduciones/translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'word=' + encodeURIComponent(word)
            })
            .then(res => res.json())
            .then(data => {
                const tr = data?.translation || 'Sin traducción';
                el.dataset.translation = tr;
                // Solo mostrar si el ratón sigue sobre el elemento
                if (el.matches(':hover')) {
                    showHoverTooltip(el, word, tr);
                }
                if (typeof saveTranslatedWord === 'function') {
                    saveTranslatedWord(word, tr, findSentenceContainingWord(el, word));
                }
            });
        }, 300); // Aumentado ligeramente a 300ms para mayor consistencia
    }

    function handleWordLeave() {
        // Cancelar temporizador pendiente inmediatamente
        if (window._hoverTimeout) {
            clearTimeout(window._hoverTimeout);
            window._hoverTimeout = null;
        }
        
        // Limpiar visualmente de inmediato
        hideHoverTooltip();
        clearWordHighlight();
        
        const sidebarOpen = !!(document.getElementById('explainSidebar')?.classList.contains('open'));
        if (sidebarOpen) return;
        
        // Reanudar lectura si estaba pausada por hover
        if (window._hoverPaused) {
            if (window.resumeReading) {
                window.resumeReading({ reason: 'word-hover', force: false });
            } else if (window.resumeSpeech) {
                window.resumeSpeech();
            }
            window._hoverPaused = false;
        }
    }

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

    function hideHoverTooltip() {
        document.querySelector('.simple-tooltip.hover')?.remove();
    }

    function handleWordClick(event) {
        event.preventDefault();
        const word = this.textContent.trim();
        if (!word) return;
        clearWordHighlight();
        highlightWord(this, word);
        if (window.explainSidebar?.showExplanation) window.explainSidebar.showExplanation(word, this);
        fetch('traduciones/translate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'word=' + encodeURIComponent(word)
        })
        .then(res => res.json())
        .then(data => {
            if (data.translation && typeof saveTranslatedWord === 'function') {
                saveTranslatedWord(word, data.translation, findSentenceContainingWord(this, word));
            }
        });
    }

    function findSentenceContainingWord(element, word) {
        let paragraph = element.closest('p') || element.closest('.paragraph');
        if (paragraph) {
            const sentences = paragraph.textContent.split(/[.!?]+/).filter(s => s.trim().length > 0);
            for (let s of sentences) {
                if (s.toLowerCase().includes(word.toLowerCase())) return s.trim() + '.';
            }
        }
        return word + '.';
    }

    function highlightWord(element) {
        window.currentHighlightedWord = { element, word: element.textContent.trim() };
        element.classList.add('word-highlighted');
    }

    function clearWordHighlight() {
        document.querySelector('.word-highlighted')?.classList.remove('word-highlighted');
        window.currentHighlightedWord = null;
    }

    window.initializeReadingStates = function() {
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = false;
        isReadingInProgress = false;
        autoReading = false;
        if (readingUpdateInterval) clearInterval(readingUpdateInterval);
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };
    
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
    
    window.startReading = async function() {
        document.querySelector('.encabezado-lectura')?.classList.add('hidden');
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

    window.pauseSpeech = function() {
        document.querySelector('.encabezado-lectura')?.classList.remove('hidden');
        window.isCurrentlyPaused = true;
        isReadingInProgress = false;
        onEndHandled = false;
        if (readingLastSaveTime) saveReadingTime(Math.floor((Date.now() - readingLastSaveTime) / 1000));
        readingLastSaveTime = null;
        if (typeof window.showHeader === 'function') window.showHeader();
        cancelAllTTS();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };

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

    window.pauseReading = function() {
        window.isCurrentlyPaused = true;
        isCurrentlyReading = false;
        autoReading = false;
        if (window.speechSynthesis) window.speechSynthesis.pause();
    };

    window.resumeReading = function() {
        window.isCurrentlyPaused = false;
        isCurrentlyReading = true;
        autoReading = true;
        
        if (window.speechSynthesis && window.speechSynthesis.paused) {
            window.speechSynthesis.resume();
        } else {
            // Usar siempre el índice guardado para consistencia
            const resumeIdx = (typeof window.lastReadParagraphIndex !== 'undefined') ? window.lastReadParagraphIndex : 0;
            readAndTranslate(resumeIdx);
        }
    };

    window.stopReading = function() {
        document.querySelector('.encabezado-lectura')?.classList.remove('hidden');
        window.autoReading = false;
        autoReading = false;
        cancelAllTTS();
        isReadingInProgress = false;
        onEndHandled = false;
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
        isCurrentlyReading = false;
        if (readingLastSaveTime) saveReadingTime(Math.floor((Date.now() - readingLastSaveTime) / 1000));
        readingLastSaveTime = null;
        if (readingUpdateInterval) clearInterval(readingUpdateInterval);
        if (typeof window.showHeader === 'function') window.showHeader();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    };

    window.enableFullscreenPagination = function() {
        const style = document.createElement('style');
        style.id = 'fullscreen-pagination-style';
        style.textContent = `.page p.paragraph:nth-child(n+13) { display: none !important; } .page p.translation:nth-child(n+14) { display: none !important; }`;
        document.head.appendChild(style);
    };
    
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

    window.removeAllTranslations = function() {
        document.querySelectorAll('.translation').forEach(div => { div.innerText = ''; div.className = 'translation'; });
        const btn = document.getElementById('show-all-translations-btn');
        if (btn) { btn.textContent = '📖 Mostrar todas las traducciones'; btn.onclick = (e) => { e.stopPropagation(); window.showAllTranslations(); }; }
    };

    window.readPages = [];
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

    function saveReadingProgress(percent) {
        const textId = document.querySelector('.reading-area')?.getAttribute('data-text-id');
        if (!textId) return;
        const body = 'text_id=' + encodeURIComponent(textId) + '&percent=' + encodeURIComponent(percent) + '&pages_read=' + encodeURIComponent(JSON.stringify(window.readPages || []));
        fetch('lectura/ajax/ajax_progress_content.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    }

    function onPageReadByTTS(pageIdx) {
        if (!window.readPages.includes(pageIdx)) {
            window.readPages.push(pageIdx);
            updateReadingProgressBar();
            const totalPages = parseInt(document.getElementById('pages-container')?.getAttribute('data-total-pages') || 1);
            if (window.readPages.length === totalPages) saveReadingProgress(100);
            else {
                const totalWords = parseInt(document.getElementById('pages-container')?.getAttribute('data-total-words') || 1);
                let wordsRead = 0;
                window.readPages.forEach(idx => {
                    document.querySelectorAll('.page')[idx]?.querySelectorAll('p.paragraph').forEach(p => { wordsRead += (p.innerText.match(/\b\w+\b/g) || []).length; });
                });
                saveReadingProgress(Math.round((wordsRead / totalWords) * 100));
            }
        }
    }

    setTimeout(loadReadingProgress, 200);

    if (!window._clickToggleInitialized) {
        document.addEventListener('click', function(e) {
            if (!window.userLoggedIn) return;
            const container = document.getElementById('pages-container');
            if (!container || !container.contains(e.target)) return;
            const interactive = ['.clickable-word', 'button', 'a', 'input', 'select', 'textarea', '.tab-navigation', '#floating-menu', '.explain-sidebar', '.simple-tooltip'];
            for (const s of interactive) if (e.target.closest(s)) return;
            if (typeof window.toggleFloatingPlayPause === 'function') window.toggleFloatingPlayPause();
        });
        window._clickToggleInitialized = true;
    }

    updatePageDisplay();
}

window.initLector = initLector;
