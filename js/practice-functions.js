// ============================================
// FUNCIONES DE PRÁCTICA Y EJERCICIOS
// ============================================

// Función global para configurar voz en inglés offline (mantenida para compatibilidad con fallback)
window.configureEnglishVoice = function(utterance) {
    utterance.lang = 'en-US';
};

// Variables globales de práctica
window.practiceWords = [];
window.practiceRemainingWords = [];
window.practiceCurrentMode = 'selection';
window.practiceCurrentQuestionIndex = 0;
window.practiceCorrectAnswers = 0;
window.practiceIncorrectAnswers = 0;
window.practiceAnswered = false;
window.practiceCurrentWordIndex = 0;
window.practiceCurrentSentenceData = {};
// Flag para mostrar siempre la traducción
window.practiceAlwaysShowTranslation = false;

// === CONTADOR DE TIEMPO DE PRÁCTICA ===
window.practiceStartTime = null;
window.practiceEndTime = null;
window.practiceDuration = null;

// Flag global para saber si estamos en pantalla de resultados
window.practiceResultsActive = false;

// Cargar modo práctica - mostrar selector de texto primero
window.loadPracticeMode = async function() {
    // Mostrar interfaz de selección de modo primero
    showPracticeModeSelector();
}

// Mostrar selector de modo de práctica
function showPracticeModeSelector() {
    const practiceHTML = `
        <div class="mode-selector">
            <button class="mode-btn active" onclick="setPracticeMode('selection')">
                📝 Selección múltiple
            </button>
            <button class="mode-btn" onclick="setPracticeMode('writing')">
                ✍️ Escribir palabra
            </button>
            <button class="mode-btn" onclick="setPracticeMode('sentences')">
                📖 Escribir frases
            </button>
        </div>

        <div class="progress">
            <div class="progress-bar" id="practice-progress-bar" style="width: 0%"></div>
        </div>

        <div class="exercise-card" id="practice-exercise-card">
            <!-- El ejercicio se cargará aquí dinámicamente -->
        </div>

        <div class="practice-stats">
            <div class="stat-item">
                <div class="stat-number" id="practice-current-question">0</div>
                <div class="stat-label">Pregunta</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="practice-total-questions">0</div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="practice-correct-count">0</div>
                <div class="stat-label">Correctas</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="practice-incorrect-count">0</div>
                <div class="stat-label">Incorrectas</div>
            </div>
        </div>
    `;
    
    document.getElementById('practice-content').innerHTML = practiceHTML;
    
    // Activar modo selección por defecto
    window.practiceCurrentMode = 'selection';
    loadSentencePractice();
}

// Inicializar práctica con palabras (solo variables, no interfaz)
function initializePractice(words) {
    // Inicializar variables de práctica
    window.practiceWords = [...words];
    window.practiceRemainingWords = [...words];
    window.practiceCurrentQuestionIndex = 0;
    window.practiceCorrectAnswers = 0;
    window.practiceIncorrectAnswers = 0;
    window.practiceAnswered = false;
    
    updatePracticeStats();
    loadPracticeQuestion();
    window.practiceStartTime = Date.now();
    window.practiceEndTime = null;
    window.practiceDuration = null;
}

// Establecer modo de práctica
window.setPracticeMode = function(mode) {
    window.practiceAlwaysShowTranslation = false;
    window.practiceCurrentMode = mode;
    document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    if (mode === 'sentences') {
        loadSentencePractice();
    } else if (mode === 'selection' || mode === 'writing') {
        loadSentencePractice();
    } else {
        loadSentencePractice();
    }
}

// Cargar pregunta de práctica
window.loadPracticeQuestion = function() {
    // Ocultar header durante el ejercicio
    const header = document.querySelector('header');
    if (header) {
        header.style.display = 'none';
    }

    // Seleccionar palabra aleatoria
    const randomIndex = Math.floor(Math.random() * window.practiceRemainingWords.length);
    const currentWord = window.practiceRemainingWords[randomIndex];
    window.practiceCurrentWordIndex = randomIndex;
    
    // Para palabras: usar el contexto completo CON HUECO
    if (window.practiceCurrentMode === 'selection' || window.practiceCurrentMode === 'writing') {
        // MODO PALABRAS: mostrar contexto completo con hueco donde va la palabra
        
        // Verificar si el contexto está vacío o no contiene la palabra
        if (!currentWord.context || currentWord.context.trim() === '') {
            window.practiceCurrentSentenceData = {
                en: `The word "${currentWord.word}" is important.`,
                es: `La palabra "${currentWord.word}" es importante.`
            };
        } else {
            // Intentar crear el hueco con diferentes estrategias
            let contextWithHole = currentWord.context;
            
            // Limpiar la palabra de caracteres especiales para la búsqueda
            const cleanWord = currentWord.word.replace(/[.,!?;:]/g, '').trim();
            const originalWord = currentWord.word;
            
            // Estrategia 1: Buscar la palabra exacta (con caracteres especiales)
            const regex1 = new RegExp(`\\b${originalWord.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
            if (regex1.test(currentWord.context)) {
                contextWithHole = currentWord.context.replace(regex1, '____');
            } else {
                // Estrategia 2: Buscar la palabra limpia con límites
                const regex2 = new RegExp(`\\b${cleanWord.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
                if (regex2.test(currentWord.context)) {
                    contextWithHole = currentWord.context.replace(regex2, '____');
                } else {
                    // Estrategia 3: Buscar la palabra sin límites
                    const regex3 = new RegExp(originalWord.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
                    if (regex3.test(currentWord.context)) {
                        contextWithHole = currentWord.context.replace(regex3, '____');
                    } else {
                        // Estrategia 4: Buscar la palabra limpia sin límites
                        const regex4 = new RegExp(cleanWord.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
                        if (regex4.test(currentWord.context)) {
                            contextWithHole = currentWord.context.replace(regex4, '____');
                        } else {
                            // Estrategia 5: Búsqueda manual más flexible
                            const wordLower = cleanWord.toLowerCase();
                            const contextLower = currentWord.context.toLowerCase();
                            const index = contextLower.indexOf(wordLower);
                            if (index !== -1) {
                                const before = currentWord.context.substring(0, index);
                                const after = currentWord.context.substring(index + cleanWord.length);
                                contextWithHole = before + '____' + after;
                            } else {
                                contextWithHole = currentWord.context;
                            }
                        }
                    }
                }
            }
            window.practiceCurrentSentenceData = {
                en: contextWithHole,
                es: '', // Dejar vacío para que se traduzca con la API
                original_en: currentWord.context, // Guardar la frase original en inglés
                translation: currentWord.translation, // Agregar la traducción de la palabra
                word: currentWord.word, // Agregar la palabra para referencia
                needsTranslation: true // Indicar que necesita traducción
            };
        }
    } else {
        // MODO FRASES: generar con hueco
        window.practiceCurrentSentenceData = generatePracticeSentence(currentWord.word);
    }
    
    window.practiceAnswered = false;

    // Obtener el título del texto de la palabra actual
    const textTitle = currentWord.text_title || 'este texto';
    const instruction = window.practiceCurrentMode === 'selection' ? 
        `Elige la palabra correcta del texto <span class="text-title-highlight">"${textTitle}"</span>:` : 
        `Escribe la palabra correcta del texto <span class="text-title-highlight">"${textTitle}"</span>:`;
    let html = `
        <div class="practice-instruction">
            ${instruction}
        </div>
        <div class="practice-sentence" id="english-sentence-container">
            <span id="english-sentence">${makeWordsClickable(window.practiceCurrentSentenceData.en)}</span>
            <div style="display:inline-block; position:relative; vertical-align:middle;">
                <button class="speak-sentence-btn" id="speak-sentence-btn" title="Escuchar frase" style="background: none; border: none; cursor: pointer; margin-left: 8px; font-size: 1.2em; vertical-align: middle;">
                    <span role="img" aria-label="Escuchar">🔊</span>
                </button>
                <input type="range" min="0" max="2" step="1" value="1" id="speak-speed-slider" style="display:none; position:absolute; left:40px; top:50%; transform:translateY(-50%); width:70px; z-index:10; background:#eee; border-radius:6px; height:4px;">
                <div style="position:absolute; left:40px; top:28px; width:70px; display:none; z-index:11; pointer-events:none; font-size:11px; color:#888; text-align:center;" id="speak-speed-labels">
                    <span>50%</span>
                    <span>75%</span>
                    <span>100%</span>
                </div>
            </div>
        </div>
        <div class="spanish-translation hidden" id="spanish-translation">
        </div>
        <div class="translation-help-container" style="position:relative; display:flex; align-items:center; justify-content:flex-end; width:100%; margin:8px 0 0 0; gap:6px;">
            <button class="translation-help-btn" id="show-translation-btn" onclick="showPracticeTranslation()" style="padding:1px 12px; font-size:0.80em; min-width:0; height:22px; display:inline-flex; align-items:center; white-space:nowrap;">📖 Ver traducción</button>
            <div style="position:relative; display:inline-flex; align-items:center;">
                <span id="always-visible-eye" style="font-size:1.25em; color:#2563eb; cursor:pointer; padding:2px 6px; border-radius:4px; transition:background 0.15s;" onmouseenter="(function(){var t=document.getElementById('always-visible-tooltip'); if(window.practiceAlwaysShowTranslation){t.textContent='Ocultar';}else{t.textContent='Dejar visible';} t.style.display='block';})()" onmouseleave="document.getElementById('always-visible-tooltip').style.display='none'">👁️</span>
                <span id="always-visible-tooltip" style="display:none; position:absolute;  top:100%; transform:translateX(-50%); background:#222; color:#fff; padding:4px 10px; border-radius:6px; font-size:0.92em; white-space:nowrap; box-shadow:0 2px 8px rgba(0,0,0,0.13); z-index:30; opacity:0.93; max-width:180px; word-break:break-word; text-align:center;">Dejar visible</span>
            </div>
        </div>
        <style>
        @media (max-width: 600px) {
            .translation-help-container { gap: 2px !important; }
            #show-translation-btn { font-size: 0.88em !important; padding: 1px 10px !important; height: 24px !important; white-space:nowrap !important; }
            #always-visible-eye { font-size: 1.1em !important; padding: 2px 2px !important; }
            #always-visible-tooltip {
                font-size: 0.90em !important;
                max-width: 96vw !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                white-space:nowrap !important;
                padding: 3px 6px !important;
            }
        }
        </style>
    `;
    
    if (window.practiceCurrentMode === 'selection') {
        const distractors = generatePracticeDistractors(currentWord.word);
        const allOptions = [...distractors, currentWord.word];
        
        // Mezclar opciones
        for (let i = allOptions.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [allOptions[i], allOptions[j]] = [allOptions[j], allOptions[i]];
        }

        html += '<div class="practice-options">';
        allOptions.forEach(option => {
            const safeOption = option.replace(/'/g, "\\'");
            const safeCorrect = currentWord.word.replace(/'/g, "\\'");
            html += `<button class="option-btn" onclick="playClickSound(); selectPracticeOption('${safeOption}', '${safeCorrect}')">${option}</button>`;
        });
        html += '</div>';
        html += '<div class="practice-controls">';
        const safeHint = currentWord.word.replace(/'/g, "\\'");
        html += `<button class="option-btn hint-btn" onclick="showPracticeHint('${safeHint}')">💡 Pista</button>`;
        html += `<button class="option-btn next-btn" onclick="nextPracticeQuestion()" style="display:none;">Siguiente</button>`;
        html += '</div>';
    } else {
        html += `
            <input type="text" placeholder="Escribe la palabra que falta..." style="width: 25%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px; margin: 15px auto; box-sizing: border-box; display: block; background: white;justify-content: center;
     color: #333; outline: none;" data-practice-input="true" data-correct-word="${currentWord.word}">
            <div id="word-hint" style="display: none; font-size: 12px; color: #999; opacity: 0.6; margin-top: 5px; text-align: center; font-style: italic;"></div>
            <div class="practice-controls">
                <button class="option-btn hint-btn" onclick="showPracticeHint('${currentWord.word}')">💡 Pista</button>
                <button class="option-btn next-btn" onclick="nextPracticeQuestion()" style="display:none;">Siguiente</button>
            </div>
        `;
    }

    const practiceCard = document.getElementById('practice-exercise-card');
    if (practiceCard) {
        practiceCard.innerHTML = html;
    }
    
    // Asignar event listeners a las palabras clickeables
    setTimeout(() => {
        assignPracticeWordClickHandlers();
    }, 10);
    
    const englishSentence = document.getElementById('english-sentence');
    if (englishSentence && !englishSentence._delegated) {
        englishSentence.addEventListener('click', function(event) {
            const target = event.target;
            if (target.classList.contains('practice-word')) {
                event.preventDefault();
                event.stopPropagation();
                const word = target.textContent.trim();
                if (!word || word === '___') return;
                fetch('translate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'word=' + encodeURIComponent(word)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.translation) {
                        showPracticeTooltip(target, word, data.translation);
                    } else {
                        showPracticeTooltip(target, word, 'No se encontró traducción');
                    }
                })
                .catch(() => {
                    showPracticeTooltip(target, word, 'Error en la traducción');
                });
            }
        });
        englishSentence._delegated = true;
    }
    
    // Resetear el estado del botón de traducción
    setTimeout(() => {
        const translationBtn = document.getElementById('show-translation-btn');
        const translationDiv = document.getElementById('spanish-translation');
        if (translationBtn && translationDiv) {
            translationBtn.textContent = '📖 Ver traducción';
            translationDiv.classList.add('hidden');
        }
    }, 10);
    
    if (window.practiceCurrentMode === 'writing') {
        const writeInput = document.querySelector('[data-practice-input="true"]');
        if (writeInput) {
            writeInput.focus();
            window.currentWordErrors = 0;
            const correctWord = writeInput.getAttribute('data-correct-word');
            
            writeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    checkPracticeWriteAnswer(correctWord);
                }
            });
            
            writeInput.addEventListener('input', function() {
                checkWordInput(correctWord);
            });
        }
    }
    
    setTimeout(() => {
        const practiceCard = document.getElementById('practice-exercise-card');
        if (practiceCard) {
            practiceCard.addEventListener('click', function(e) {
                if (!e.target.matches('button, input, .option-btn, .check-btn')) {
                    const header = document.querySelector('header');
                    if (header) {
                        header.style.display = '';
                    }
                }
            });
        }
    }, 100);

    if (window.practiceAlwaysShowTranslation) {
        setTimeout(() => {
            showPracticeTranslation();
            var btn = document.getElementById('show-translation-btn');
            if(btn) btn.style.display = 'none';
        }, 30);
    }
    
    if (!window._delegacionAltavozPractica) {
        document.addEventListener('click', async function(e) {
            if (e.target.closest && e.target.closest('#speak-sentence-btn')) {
                e.stopPropagation();
                let sentence = '';
                if (window.practiceCurrentSentenceData && window.practiceCurrentSentenceData.en) {
                    sentence = window.practiceCurrentSentenceData.en;
                }
                if (sentence) {
                    const finalSentence = sentence.replace(/____+/g, window.practiceCurrentSentenceData.word || '');
                    
                    if (typeof window.getVoiceSystemReady === 'function') {
                        try {
                            await window.getVoiceSystemReady();
                            
                            if (typeof window.leerTextoConResponsiveVoice === 'function') {
                                const success = window.leerTextoConResponsiveVoice(finalSentence, 1.0, {
                                    onerror: (error) => console.error('❌ Error en frase de práctica:', error)
                                });
                                
                                if (!success) {
                                    throw new Error('ResponsiveVoice falló');
                                }
                            } else {
                                throw new Error('ResponsiveVoice no disponible');
                            }
                        } catch (error) {
                            if (window.speechSynthesis && window.speechSynthesis.speaking) {
                                window.speechSynthesis.cancel();
                            }
                            const utter = new window.SpeechSynthesisUtterance(finalSentence);
                            utter.lang = 'en-US';
                            utter.rate = 1;
                            window.speechSynthesis.speak(utter);
                        }
                    } else {
                        if (window.speechSynthesis && window.speechSynthesis.speaking) {
                            window.speechSynthesis.cancel();
                        }
                        const utter = new window.SpeechSynthesisUtterance(finalSentence);
                        utter.lang = 'en-US';
                        utter.rate = 1;
                        window.speechSynthesis.speak(utter);
                    }
                }
            }
        });
        window._delegacionAltavozPractica = true;
    }
    // Configurar funcionalidad del icono del ojo
    setTimeout(function() {
        var eye = document.getElementById('always-visible-eye');
        if(eye) {
            eye.onclick = function() {
                window.practiceAlwaysShowTranslation = !window.practiceAlwaysShowTranslation;
                if(window.practiceAlwaysShowTranslation) {
                    eye.style.color = '#0ea900';
                    if(typeof showPracticeTranslation === 'function') showPracticeTranslation();
                } else {
                    eye.style.color = '#2563eb';
                    var div = document.getElementById('spanish-translation');
                    if(div && div.classList && !div.classList.contains('hidden')) {
                        div.classList.add('hidden');
                    }
                    var btn = document.getElementById('show-translation-btn');
                    if(btn) btn.style.display = '';
                }
            };
            if(window.practiceAlwaysShowTranslation) {
                eye.style.color = '#0ea900';
            } else {
                eye.style.color = '#2563eb';
            }
        }
    }, 0);
}

window.restartPracticeExercise = function() {
    window.practiceRemainingWords = [...window.practiceWords];
    window.practiceCurrentQuestionIndex = 0;
    window.practiceCorrectAnswers = 0;
    window.practiceIncorrectAnswers = 0;
    window.practiceAnswered = false;
    updatePracticeStats();
    loadPracticeQuestion();
};

window.playClickSound = function() {
};

function normalizeWord(word) {
    return word.toLowerCase().replace(/[.,!?;:'"`~@#$%^&*()_+\-=\[\]{}|\\;:"'<>?\/]/g, '');
}

function getSmartHint(userText, correctWord) {
    let correctLength = 0;
    for (let i = 0; i < userText.length && i < correctWord.length; i++) {
        if (userText[i].toLowerCase() === correctWord[i].toLowerCase()) {
            correctLength++;
        } else {
            break;
        }
    }
    
    if (correctLength < correctWord.length) {
        return correctWord.substring(0, correctLength + 1);
    } else {
        return correctWord;
    }
}

window.checkWordInput = function(correctWord) {
    const input = document.querySelector('[data-practice-input="true"]');
    const wordHint = document.getElementById('word-hint');
    const userText = input.value;
    const correctText = correctWord;
    
    const normalizedUserText = normalizeWord(userText);
    const normalizedCorrectText = normalizeWord(correctText);
    
    for (let i = 0; i < normalizedUserText.length; i++) {
        if (i >= normalizedCorrectText.length || 
            normalizedUserText[i].toLowerCase() !== normalizedCorrectText[i].toLowerCase()) {
            
            setTimeout(() => {
                const correctPart = userText.substring(0, i);
                input.value = correctPart;
                if (wordHint) {
                    wordHint.textContent = correctText;
                    wordHint.style.display = 'block';
                }
                input.focus();
                input.setSelectionRange(input.value.length, input.value.length);
            }, 100);
            playErrorSound();
            window.currentWordErrors++;
            
            if (window.currentWordErrors >= 2) {
                setTimeout(() => {
                    const smartHint = getSmartHint(input.value, correctText);
                    input.value = smartHint;
                    window.currentWordErrors = 0;
                    if (wordHint) {
                        wordHint.style.display = 'none';
                    }
                    input.focus();
                    input.setSelectionRange(input.value.length, input.value.length);
                }, 150);
            }
            return;
        }
    }
    if (normalizedUserText === normalizedCorrectText.substring(0, normalizedUserText.length)) {
        if (wordHint) {
            wordHint.style.display = 'none';
        }
        if (normalizedUserText === normalizedCorrectText) {
            if (!input.classList.contains('sentence-input') && !input.disabled) {
                input.disabled = true;
                showWordSuccessFeedback(input);

                const translationBtn = document.getElementById('show-translation-btn');
                const translationDiv = document.getElementById('spanish-translation');
                if (typeof showTranslationAfterAnswer === 'function') {
                    showTranslationAfterAnswer();
                } else if (translationDiv) {
                    translationDiv.classList.remove('hidden');
                }
                if (translationBtn) translationBtn.style.display = 'none';
            }
            if (input.classList.contains('sentence-input') && !input.disabled) {
                input.disabled = true;
                let feedbackDiv = document.createElement('div');
                feedbackDiv.className = 'practice-feedback-toast success';
                feedbackDiv.textContent = '¡Correcto!';
                feedbackDiv.style.position = 'fixed';
                feedbackDiv.style.top = '30px';
                feedbackDiv.style.left = '50%';
                feedbackDiv.style.transform = 'translateX(-50%)';
                feedbackDiv.style.zIndex = '9999';
                feedbackDiv.style.padding = '6px 10px';
                feedbackDiv.style.borderRadius = '6px';
                feedbackDiv.style.fontWeight = 'bold';
                feedbackDiv.style.fontSize = '15px';
                feedbackDiv.style.boxShadow = '0 2px 8px rgba(0,0,0,0.10)';
                feedbackDiv.style.color = '#fff';
                feedbackDiv.style.background = '#22c55e';
                feedbackDiv.style.opacity = '0.87';
                feedbackDiv.style.pointerEvents = 'none';
                document.body.appendChild(feedbackDiv);
                setTimeout(() => feedbackDiv.remove(), 1500);
                const showBtn = document.getElementById('show-english-btn');
                const englishDiv = document.getElementById('english-reference');
                if (showBtn && englishDiv) {
                    showBtn.style.display = 'none';
                    englishDiv.innerHTML = correctText;
                    englishDiv.classList.remove('hidden');
                }
                const nextButton = document.querySelector('.sentence-controls .next-btn');
                if (nextButton) nextButton.style.display = 'inline-flex';
                function nextSentenceOnEnter(e) {
                    if (e.key === 'Enter') {
                        window.removeEventListener('keydown', nextSentenceOnEnter);
                        window.nextSentenceQuestion();
                    }
                }
                window.addEventListener('keydown', nextSentenceOnEnter);
                if (nextButton) {
                    nextButton.onclick = function() {
                        window.removeEventListener('keydown', nextSentenceOnEnter);
                        window.nextSentenceQuestion();
                    };
                }
            }
        }
    }
};

function showWordSuccessFeedback(inputElement) {
    const currentWord = window.practiceRemainingWords[window.practiceCurrentWordIndex];
    
    window.practiceCorrectAnswers++;
    window.practiceRemainingWords.splice(window.practiceCurrentWordIndex, 1);
    
    const successDiv = document.createElement('div');
    const rect = inputElement.getBoundingClientRect();
    
    successDiv.style.cssText = `
        position: fixed;
        top: ${rect.top - 60}px;
        left: ${rect.left + (rect.width / 2)}px;
        transform: translateX(-50%);
        background: #22c55e;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        animation: fadeInUp 0.3s ease;
    `;
    
    successDiv.textContent = '¡Correcto!';
    document.body.appendChild(successDiv);
    playSuccessSound();
    
    const englishSentence = document.getElementById('english-sentence');
    if (englishSentence && window.practiceCurrentSentenceData) {
        const sentenceWithWord = window.practiceCurrentSentenceData.en.replace(
            /____+/g, currentWord.word
        );
        renderPracticeSentence(sentenceWithWord, currentWord.word);
    }
    
    inputElement.style.display = 'none';
    const hintBtn = document.querySelector('.practice-controls .hint-btn');
    if (hintBtn) hintBtn.style.display = 'none';
    
    const nextButton = document.querySelector('.practice-controls .next-btn');
    if (nextButton) nextButton.style.display = 'inline-flex';
    
    setTimeout(() => {
        if (successDiv.parentNode) {
            document.body.removeChild(successDiv);
        }
        showSimplifiedTranslation(currentWord);
    }, 2000);
    
    updatePracticeStats();
    if (typeof assignPracticeWordClickHandlers === 'function') {
        setTimeout(assignPracticeWordClickHandlers, 0);
    }
}

function generatePracticeSentence(word) {
    const practiceWord = window.practiceWords.find(w => w.word === word);
    if (!practiceWord) {
        return { en: `The ${word} is important.`, es: `El ${word} es importante.` };
    }
    const translation = practiceWord.translation;
    const context = practiceWord.context;
    if (context && context.trim().length > 0 && context !== `The ${word} is important.`) {
        const escapedWord = word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const wordBoundary = /^[a-zA-Z0-9]+$/.test(word) ? '\\b' : '';
        const sentenceWithGap = context.replace(new RegExp(`${wordBoundary}${escapedWord}${wordBoundary}`, 'gi'), '___');
        
        const result = {
            en: sentenceWithGap,
            es: '', // Dejar vacío para que se traduzca con la API
            original_en: context,
            word: word,
            translation: translation,
            needsTranslation: true // Indicar que necesita traducción
        };
        return result;
    }
    const templates = [
        {
            en: `I can see the ${word} from here.`,
            es: `Puedo ver ${translation} desde aquí.`
        },
        {
            en: `The ${word} is very important today.`,
            es: `${translation} es muy importante hoy.`
        },
        {
            en: `This ${word} helps me learn English.`,
            es: `Este ${translation} me ayuda a aprender inglés.`
        }
    ];
    const selectedTemplate = templates[Math.floor(Math.random() * templates.length)];
    const result = {
        en: selectedTemplate.en.replace(word, '___'),
        es: selectedTemplate.es,
        original_en: selectedTemplate.en,
        word: word,
        translation: translation
    };
    return result;
}

function makeWordsClickable(text, highlightWord = null) {
    const words = text.match(/\w+|[.,!?;:()"'-]+|\s+/g);
    let result = '';
    if (!words) return text;
    words.forEach(word => {
        if (word.trim() === '') {
            result += word;
        } else if (word === '___') {
            result += '<span class="practice-gap">___</span>';
        } else if (highlightWord && word.replace(/[.,]/g, '').toLowerCase() === highlightWord.toLowerCase()) {
            result += `<span class="practice-word highlighted-word">${word}</span>`;
        } else if (/^\w+$/.test(word)) {
            result += `<span class="practice-word">${word}</span>`;
        } else {
            result += word;
        }
    });
    return result;
}

window.handlePracticeWordClickInline = function(event, el) {
    event.preventDefault();
    event.stopPropagation();
    var word = el.textContent.trim();
    if (!word || word === '___') return;
    fetch('translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'word=' + encodeURIComponent(word)
    })
    .then(res => res.json())
    .then(data => {
        if (data.translation) {
            showPracticeTooltip(el, word, data.translation);
        } else {
            showPracticeTooltip(el, word, 'No se encontró traducción');
        }
    })
    .catch(() => {
        showPracticeTooltip(el, word, 'Error en la traducción');
    });
}

function translatePracticeSentence(originalSentence, wordTranslation) {
    fetch('translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'word=' + encodeURIComponent(originalSentence)
    })
    .then(res => res.json())
    .then(data => {
        const translationElement = document.getElementById('spanish-translation');
        if (data.translation && data.translation !== originalSentence) {
            let translatedSentence = data.translation;
            let highlightedTranslation = translatedSentence;
            let root = wordTranslation.slice(0, Math.max(3, wordTranslation.length - 2));
            if (root.length >= 3) {
                const regex = new RegExp(root + '\\w*', 'gi');
                highlightedTranslation = highlightedTranslation.replace(regex, match =>
                    (match && match.length > 0) ? `<span class="highlighted-word">${match}</span>` : match
                );
            } else {
                highlightedTranslation = highlightedTranslation.replace(
                    new RegExp(`\\b${wordTranslation}\\b`, 'gi'),
                    `<span class=\"highlighted-word\">${wordTranslation}</span>`
                );
            }
            if (!highlightedTranslation.includes('highlighted-word')) {
                highlightedTranslation += ` <span class=\"highlighted-word\">(${wordTranslation})</span>`;
            }
            if (translationElement) {
                translationElement.innerHTML = highlightedTranslation;
                translationElement.classList.remove('hidden');
            }
        } else {
            if (translationElement) {
                translationElement.innerHTML = `<span style=\"color: #dc2626;\">No se pudo traducir la frase. Palabra: <span class=\"highlighted-word\">${wordTranslation}</span></span>`;
                translationElement.classList.remove('hidden');
            }
        }
    })
    .catch((error) => {
        const translationElement = document.getElementById('spanish-translation');
        if (translationElement) {
            translationElement.innerHTML = '';
            translationElement.classList.remove('hidden');
        }
    });
}

function generatePracticeDistractors(correctWord) {
    const allWords = window.practiceWords.filter(w => w.word !== correctWord).map(w => w.word);
    const commonWords = ['house', 'book', 'time', 'water', 'good', 'work', 'think', 'know', 'want', 'say'];
    
    let distractors = [];
    
    const shuffledWords = [...allWords].sort(() => Math.random() - 0.5);
    for (let i = 0; i < Math.min(3, shuffledWords.length); i++) {
        distractors.push(shuffledWords[i]);
    }
    
    while (distractors.length < 3) {
        const commonWord = commonWords[Math.floor(Math.random() * commonWords.length)];
        if (!distractors.includes(commonWord) && commonWord !== correctWord) {
            distractors.push(commonWord);
        }
    }
    
    return distractors;
}

function playSuccessSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const now = audioContext.currentTime;
        const gainNode = audioContext.createGain();
        gainNode.gain.setValueAtTime(0.25, now);
        gainNode.gain.linearRampToValueAtTime(0.01, now + 0.35);
        gainNode.connect(audioContext.destination);

        const osc1 = audioContext.createOscillator();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(220, now);
        osc1.connect(gainNode);
        osc1.start(now);
        osc1.stop(now + 0.18);

        const osc2 = audioContext.createOscillator();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(140, now + 0.18);
        osc2.connect(gainNode);
        osc2.start(now + 0.18);
        osc2.stop(now + 0.35);
    } catch (error) {
    }
}

function playErrorSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(800, audioContext.currentTime + 0.1);
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (error) {
    }
}

window.selectPracticeOption = function(selected, correct) {
    if (window.practiceAnswered) {
        return;
    }
    window.practiceAnswered = true;
    const buttons = document.querySelectorAll('.option-btn');
    let selectedButton = null;

    const normalizedSelected = normalizeWord(selected);
    const normalizedCorrect = normalizeWord(correct);

    buttons.forEach(btn => {
        btn.onclick = null;
        const btnNormalized = normalizeWord(btn.textContent);
        if (btnNormalized === normalizedCorrect) {
            btn.classList.add('correct');
        } else if (btnNormalized === normalizedSelected && normalizedSelected !== normalizedCorrect) {
            btn.classList.add('incorrect');
        }
        if (btnNormalized === normalizedSelected) {
            selectedButton = btn;
        }
    });

    const hintButton = document.querySelector('.practice-controls .hint-btn');
    const nextButton = document.querySelector('.practice-controls .next-btn');
    if (hintButton) hintButton.style.display = 'none';
    if (nextButton) {
        nextButton.style.display = 'inline-flex';
        nextButton.onclick = function() {
            nextPracticeQuestion();
        };
    }

    const isCorrect = normalizedSelected === normalizedCorrect;

    const englishSentence = document.getElementById('english-sentence');
    if (englishSentence && window.practiceCurrentSentenceData) {
        let sentenceWithWord = window.practiceCurrentSentenceData.original_en || window.practiceCurrentSentenceData.en;
        sentenceWithWord = sentenceWithWord.replace(/____+/g, correct);
        englishSentence.innerHTML = makeWordsClickable(sentenceWithWord, correct);
        setTimeout(() => {
            assignPracticeWordClickHandlers();
            const speakBtn = document.getElementById('speak-sentence-btn');
            if (speakBtn) {
                speakBtn.onclick = function(e) {
                    e.stopPropagation();
                    let sentence = '';
                    if (window.practiceAnswered && window.practiceCurrentSentenceData.original_en) {
                        sentence = window.practiceCurrentSentenceData.original_en;
                    } else {
                        sentence = window.practiceCurrentSentenceData.en || '';
                    }
                    if (sentence) {
                        if (window.speechSynthesis && window.speechSynthesis.speaking) {
                            window.speechSynthesis.cancel();
                        }
                        const utter = new window.SpeechSynthesisUtterance(sentence.replace(/____+/g, window.practiceCurrentSentenceData.word || ''));
                        utter.lang = 'en-US';
                        utter.rate = 1;
                        window.speechSynthesis.speak(utter);
                    }
                };
            }
        }, 10);
    }

    setTimeout(() => {
        showTranslationAfterAnswer();
    }, 500);

    showQuickFeedback(selectedButton, isCorrect, correct);
}

window.checkPracticeWriteAnswer = function(correct) {
    if (window.practiceAnswered) return;
    
    const inputElement = document.querySelector('[data-practice-input="true"]');
    const userAnswer = inputElement.value.trim();
    
    if (!userAnswer) return;
    
    const normalizedUserAnswer = normalizeWord(userAnswer);
    const normalizedCorrect = normalizeWord(correct);
    const isCorrect = normalizedUserAnswer === normalizedCorrect;
    
    window.practiceAnswered = true;
    inputElement.disabled = true;
    
    const englishSentence = document.getElementById('english-sentence');
    if (englishSentence && window.practiceCurrentSentenceData) {
        let sentenceWithAnswer = window.practiceCurrentSentenceData.original_en;
        sentenceWithAnswer = sentenceWithAnswer.replace(/____+/g, correct);
        englishSentence.innerHTML = makeWordsClickable(sentenceWithAnswer, correct);
        setTimeout(() => {
            assignPracticeWordClickHandlers();
            const speakBtn = document.getElementById('speak-sentence-btn');
            if (speakBtn) {
                speakBtn.onclick = function(e) {
                    e.stopPropagation();
                    let sentence = '';
                    if (window.practiceAnswered && window.practiceCurrentSentenceData.original_en) {
                        sentence = window.practiceCurrentSentenceData.original_en;
                    } else {
                        sentence = window.practiceCurrentSentenceData.en || '';
                    }
                    if (sentence) {
                        if (window.speechSynthesis && window.speechSynthesis.speaking) {
                            window.speechSynthesis.cancel();
                        }
                        const utter = new window.SpeechSynthesisUtterance(sentence.replace(/____+/g, window.practiceCurrentSentenceData.word || ''));
                        utter.lang = 'en-US';
                        utter.rate = 1;
                        window.speechSynthesis.speak(utter);
                    }
                };
            }
        }, 10);
    }
    
    const hintButton = document.querySelector('.practice-controls .hint-btn');
    const verifyButton = document.querySelector('.practice-controls .verify-btn');
    const nextButton = document.querySelector('.practice-controls .next-btn');
    
    if (hintButton) hintButton.style.display = 'none';
    if (verifyButton) verifyButton.style.display = 'none';
    if (nextButton) nextButton.style.display = 'inline-flex';

    const showBtn = document.getElementById('show-translation-btn');
    const translationDiv = document.getElementById('spanish-translation');
    if (showBtn) showBtn.style.display = 'none';
    if (translationDiv) translationDiv.classList.remove('hidden');
    
    setTimeout(() => {
        showTranslationAfterAnswer();
    }, 500);
    
    showQuickFeedback(inputElement, isCorrect, correct);
    if (typeof assignPracticeWordClickHandlers === 'function') {
        setTimeout(assignPracticeWordClickHandlers, 0);
    }
}

window.showPracticeTranslation = function() {
    const translationBtn = document.getElementById('show-translation-btn');
    const translationDiv = document.getElementById('spanish-translation');
    if (!translationBtn || !translationDiv) return;
    if (!translationDiv.innerHTML.trim() && window.practiceCurrentSentenceData) {
        const wordTranslation = window.practiceCurrentSentenceData.translation;
        const currentWord = window.practiceCurrentSentenceData.word;
        if (window.practiceCurrentSentenceData.needsTranslation) {
            translatePracticeSentence(
                window.practiceCurrentSentenceData.original_en, 
                wordTranslation
            );
        } else if (window.practiceCurrentSentenceData.es) {
            const translation = window.practiceCurrentSentenceData.es;
            const escapedWordTranslation = wordTranslation.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            let highlightedTranslation = translation.replace(
                new RegExp(`\\b${escapedWordTranslation}\\b`, 'gi'),
                `<span class=\"highlighted-word\">${wordTranslation}</span>`
            );
            highlightedTranslation += ` <span class=\"highlighted-word\">(${wordTranslation})</span>`;
            translationDiv.innerHTML = highlightedTranslation;
        } else {
            const originalContext = window.practiceRemainingWords[window.practiceCurrentWordIndex].context;
            const sentenceToTranslate = originalContext || window.practiceCurrentSentenceData.en.replace(/____+/g, currentWord);
            translatePracticeSentence(
                sentenceToTranslate,
                wordTranslation
            );
        }
    }
    translationDiv.classList.remove('hidden');
    translationBtn.style.display = 'none';
};

function showTranslationAfterAnswer() {
    const translationDiv = document.getElementById('spanish-translation');
    if (!translationDiv) {
        return;
    }
    const showBtn = document.getElementById('show-translation-btn');
    if (showBtn) {
        showBtn.style.display = 'none';
    }
    translationDiv.classList.remove('hidden');
    if (window.practiceCurrentSentenceData) {
        const wordTranslation = window.practiceCurrentSentenceData.translation;
        const currentWord = window.practiceCurrentSentenceData.word;
        if (window.practiceCurrentSentenceData.needsTranslation) {
            translatePracticeSentence(
                window.practiceCurrentSentenceData.original_en, 
                wordTranslation
            );
        } else if (window.practiceCurrentSentenceData.es) {
            const translation = window.practiceCurrentSentenceData.es;
            const escapedWordTranslation = wordTranslation.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            let highlightedTranslation = translation.replace(
                new RegExp(`\\b${escapedWordTranslation}\\b`, 'gi'),
                `<span class=\"highlighted-word\">${wordTranslation}</span>`
            );
            highlightedTranslation += ` <span class=\"highlighted-word\">(${wordTranslation})</span>`;
            translationDiv.innerHTML = highlightedTranslation;
        } else {
            const originalContext = window.practiceRemainingWords[window.practiceCurrentWordIndex].context;
            const sentenceToTranslate = originalContext || window.practiceCurrentSentenceData.en.replace(/____+/g, currentWord);
            translatePracticeSentence(
                sentenceToTranslate,
                wordTranslation
            );
        }
    }
}

function showQuickFeedback(buttonElement, isCorrect, correctWord) {
    const currentWord = window.practiceRemainingWords[window.practiceCurrentWordIndex];
    if (isCorrect) {
        window.practiceCorrectAnswers++;
        window.practiceRemainingWords.splice(window.practiceCurrentWordIndex, 1);
    } else {
        window.practiceIncorrectAnswers++;
        const wordToRepeat = window.practiceRemainingWords.splice(window.practiceCurrentWordIndex, 1)[0];
        window.practiceRemainingWords.push(wordToRepeat);
    }
    
    if (isCorrect) {
        playSuccessSound();
    } else {
        playErrorSound();
    }
    
    let feedbackDiv = document.createElement('div');
    feedbackDiv.className = 'practice-feedback-toast ' + (isCorrect ? 'success' : 'error');
    feedbackDiv.textContent = isCorrect ? '¡Correcto!' : `Incorrecto.Correcto:  ${correctWord}`;
    feedbackDiv.style.position = 'absolute';
    feedbackDiv.style.zIndex = '9999';
    feedbackDiv.style.padding = '6px 10px';
    feedbackDiv.style.borderRadius = '6px';
    feedbackDiv.style.fontWeight = 'bold';
    feedbackDiv.style.fontSize = '15px';
    feedbackDiv.style.boxShadow = '0 2px 8px rgba(0,0,0,0.10)';
    feedbackDiv.style.color = '#fff';
    feedbackDiv.style.background = isCorrect ? '#22c55e' : '#ef4444';
    feedbackDiv.style.opacity = '0.87';
    feedbackDiv.style.pointerEvents = 'none';

    if (buttonElement && buttonElement.getBoundingClientRect) {
        const rect = buttonElement.getBoundingClientRect();
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const scrollLeft = window.scrollX || document.documentElement.scrollLeft;
        feedbackDiv.style.left = (rect.left + rect.width/2 + scrollLeft) + 'px';
        feedbackDiv.style.top = (rect.top + scrollTop - 8) + 'px';
        feedbackDiv.style.transform = 'translate(-50%, -100%)';
        document.body.appendChild(feedbackDiv);
    } else {
        feedbackDiv.style.position = 'fixed';
        feedbackDiv.style.top = '30px';
        feedbackDiv.style.left = '50%';
        feedbackDiv.style.transform = 'translateX(-50%)';
        document.body.appendChild(feedbackDiv);
    }
    setTimeout(() => feedbackDiv.remove(), 1500);

    showSimplifiedTranslation(currentWord);
    updatePracticeStats();
    if (window.practiceRemainingWords.length === 0) {
        showPracticeResults();
        return;
    }
}

function showSimplifiedTranslation(currentWord) {
    const spanishSentence = window.practiceCurrentSentenceData.es;
    const practiceCard = document.getElementById('practice-exercise-card');
    const existingTranslation = practiceCard.querySelector('.simplified-translation');
    
    if (existingTranslation) return;
    
    const simplifiedFeedback = spanishSentence ? `
        <div class="simplified-translation" style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; text-align: center;">
            <div style="font-size: 16px; line-height: 1.5; margin-bottom: 15px;">${spanishSentence}</div>
        </div>
    ` : '';
    
    if (simplifiedFeedback) {
        practiceCard.insertAdjacentHTML('beforeend', simplifiedFeedback);
    }
    
    document.addEventListener('keydown', function practiceEnterHandler(e) {
        if (e.key === 'Enter') {
            nextPracticeQuestion();
            document.removeEventListener('keydown', practiceEnterHandler);
        }
    });
}

window.nextPracticeQuestion = function() {
    window.practiceCurrentQuestionIndex++;
    if (window.practiceRemainingWords.length === 0) {
        showPracticeResults();
        return;
    }
    loadPracticeQuestion();
}

function updatePracticeStats() {
    const totalWords = window.practiceWords.length;
    const wordsCompleted = window.practiceCorrectAnswers;
    document.getElementById('practice-current-question').textContent = wordsCompleted;
    document.getElementById('practice-correct-count').textContent = window.practiceCorrectAnswers;
    document.getElementById('practice-incorrect-count').textContent = window.practiceIncorrectAnswers;
    const progress = totalWords > 0 ? (wordsCompleted / totalWords) * 100 : 0;
    document.getElementById('practice-progress-bar').style.width = progress + '%';
}

function showPracticeResults() {
    window.practiceResultsActive = true;
    const header = document.querySelector('header');
    if (header) {
        header.style.display = '';
    }
    savePracticeProgress(
        window.practiceCurrentMode,
        window.practiceWords.length,
        window.practiceCorrectAnswers,
        window.practiceIncorrectAnswers
    );
    window.practiceEndTime = Date.now();
    window.practiceDuration = Math.floor((window.practiceEndTime - window.practiceStartTime) / 1000);
    if (window.practiceDuration && window.practiceDuration > 0) {
        fetch('save_practice_time.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'duration=' + window.practiceDuration +
                  '&mode=' + encodeURIComponent(window.practiceCurrentMode)
        });
    }
    if (typeof playSuccessSound === 'function') {
        playSuccessSound();
    }
    const resultHtml = `
        <div class="practice-results">
            <h3>🎉 ¡Ejercicio completado!</h3>
            <div class="practice-score">
                ${window.practiceCorrectAnswers} palabras aprendidas
            </div>
            <p>¡Excelente trabajo! Has completado todas las palabras correctamente.</p>
            <div style="margin-top: 30px;">
                <button class="next-btn" id="practice-next-btn" onclick="window.location.href='index.php?tab=practice'" style="margin-right: 15px;">Seguir practicando</button>
                <a href="index.php?tab=progress" class="nav-btn" style="margin-right: 15px;">Ir a mi progreso</a>
                <a href="index.php?tab=my-texts" class="nav-btn">Ver mis textos</a>
            </div>
        </div>
    `;
    document.getElementById('practice-exercise-card').innerHTML = resultHtml;

    function nextOnEnter(e) {
        if (window.practiceResultsActive && e.key === 'Enter') {
            window.removeEventListener('keydown', nextOnEnter);
            window.practiceResultsActive = false;
            const nextBtn = document.getElementById('practice-next-btn');
            if (nextBtn) nextBtn.click();
        }
    }
    window.addEventListener('keydown', nextOnEnter);
    const nextBtn = document.getElementById('practice-next-btn');
    if (nextBtn) {
        nextBtn.onclick = function() {
            window.removeEventListener('keydown', nextOnEnter);
            window.practiceResultsActive = false;
            window.location.href = 'index.php?tab=practice';
        };
    }
    window.practiceAlwaysShowTranslation = false;
}

window.restartPracticeExercise = function() {
    window.practiceRemainingWords = [...window.practiceWords];
    
    for (let i = window.practiceRemainingWords.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [window.practiceRemainingWords[i], window.practiceRemainingWords[j]] = [window.practiceRemainingWords[j], window.practiceRemainingWords[i]];
    }
    
    window.practiceCurrentQuestionIndex = 0;
    window.practiceCorrectAnswers = 0;
    window.practiceIncorrectAnswers = 0;
    window.practiceAnswered = false;
    
    updatePracticeStats();
    loadPracticeQuestion();
}

window.showPracticeHint = function(word) {
    const practiceWord = window.practiceWords.find(w => w.word === word);
    if (practiceWord) {
        const writeInput = document.querySelector('[data-practice-input="true"]');
        
        if (writeInput && !writeInput.disabled) {
            const currentText = writeInput.value;
            const smartHint = getSmartHint(currentText, word);
            
            writeInput.value = smartHint;
            writeInput.focus();
            writeInput.setSelectionRange(writeInput.value.length, writeInput.value.length);
            
            const englishSentence = document.getElementById('english-sentence');
            if (englishSentence && window.practiceCurrentSentenceData) {
                const sentenceWithHint = window.practiceCurrentSentenceData.en.replace(
                    /____+/g, 
                    `<span class="highlighted-word" style="font-size: 1.2em; background: #ff6f0074; padding: 4px 8px; border-radius: 3px; animation: pulse 2s infinite;">${smartHint}...</span>`
                );
                englishSentence.innerHTML = sentenceWithHint;
                
                setTimeout(() => {
                    if (englishSentence && window.practiceCurrentSentenceData) {
                        englishSentence.innerHTML = window.practiceCurrentSentenceData.en;
                    }
                }, 3000);
            }
            
            return;
        }
        
        const hint = word.substring(0, 2);
        
        const englishSentence = document.getElementById('english-sentence');
        if (englishSentence && window.practiceCurrentSentenceData) {
            const sentenceWithHint = window.practiceCurrentSentenceData.en.replace(
                /____+/g, 
                `<span class="highlighted-word" style="font-size: 1.2em; background: #ff6f0074; padding: 4px 8px; border-radius: 3px; animation: pulse 2s infinite;">${hint}...</span>`
            );
            englishSentence.innerHTML = sentenceWithHint;
            
            setTimeout(() => {
                if (englishSentence && window.practiceCurrentSentenceData) {
                    englishSentence.innerHTML = window.practiceCurrentSentenceData.en;
                }
            }, 3000);
        }
        
        const hintElement = document.querySelector('.practice-controls .hint-btn');
        if (hintElement) {
            hintElement.innerHTML = `💡 Pista: ${hint}...`;
            hintElement.style.background = '#ff6f0074';
            hintElement.style.color = '#92400e';
            hintElement.style.fontWeight = 'bold';
            
            setTimeout(() => {
                hintElement.innerHTML = `💡 Pista`;
                hintElement.style.background = '';
                hintElement.style.color = '';
                hintElement.style.fontWeight = '';
            }, 3000);
        }
    }
}

window.sentenceTexts = [];
window.currentSentences = [];
window.currentSentenceIndex = 0;
window.sentenceErrors = 0;
window.sentenceCorrectAnswers = 0;
window.sentenceIncorrectAnswers = 0;

async function loadSentencePractice() {
    try {
        const basePath = (window.location.pathname || '').replace(/[^\/]+$/, '');
        const url = basePath + 'ajax_user_texts.php?t=' + Date.now();
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);
        const form = new URLSearchParams();
        form.set('action', 'list');
        let response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Cache-Control': 'no-store' },
            credentials: 'same-origin',
            cache: 'no-store',
            body: form.toString(),
            signal: controller.signal
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        let responseText = await response.text();
        if (!responseText.trim()) {
            const retryUrl = basePath + 'ajax_user_texts.php?t=' + Date.now();
            response = await fetch(retryUrl, { credentials: 'same-origin', cache: 'no-store' });
            responseText = await response.text();
            if (!responseText.trim()) {
                throw new Error('Respuesta vacía del servidor');
            }
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            throw new Error('Respuesta del servidor no es JSON válido');
        }
        
        if (data.success && data.texts && data.texts.length > 0) {
            showTextSelector(data.texts);
        } else {
            document.getElementById('practice-exercise-card').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <h3 style="color: #6b7280;">No hay textos disponibles</h3>
                    <p style="color: #9ca3af;">Necesitas subir algunos textos primero.</p>
                    <a href="index.php" class="nav-btn">Subir textos</a>
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('practice-exercise-card').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <h3 style="color: #dc2626;">Error al cargar los textos</h3>
                <p style="color: #9ca3af;">${error.message}</p>
                <button onclick="loadSentencePractice()" class="option-btn">Reintentar</button>
            </div>
        `;
    } finally {
        try { clearTimeout(timeoutId); } catch (e) {}
    }
}

function showTextSelector(texts) {
    const isWordMode = window.practiceCurrentMode === 'selection' || window.practiceCurrentMode === 'writing';
    const modeText = isWordMode ? 'palabras' : 'frases';
    const modeIcon = isWordMode ? '📝' : '📖';

    const ownTexts = texts.filter(text => text.text_type === 'own');
    const publicTexts = texts.filter(text => text.text_type === 'public');

    let optionsHtml = '<option value="">Selecciona un texto...</option>';
    if (ownTexts.length > 0) {
        optionsHtml += '<optgroup label="📚 Mis textos">';
        ownTexts.forEach(text => {
            const titleWithTranslation = text.title_translation ? 
                `${text.title} • ${text.title_translation}` : 
                text.title;
            const wordCount = text.saved_word_count || 0;
            optionsHtml += `<option value="${text.id}">${titleWithTranslation} (${wordCount} palabras)</option>`;
        });
        optionsHtml += '</optgroup>';
    }
    if (publicTexts.length > 0) {
        optionsHtml += '<optgroup label="🌍 Textos públicos">';
        publicTexts.forEach(text => {
            const titleWithTranslation = text.title_translation ? 
                `${text.title} • ${text.title_translation}` : 
                text.title;
            const wordCount = text.saved_word_count || 0;
            optionsHtml += `<option value="${text.id}">${titleWithTranslation} (${wordCount} palabras)</option>`;
        });
        optionsHtml += '</optgroup>';
    }

    const html = `
        <div class="text-selector-container">
            <h3>${modeIcon} Elige un texto para practicar ${modeText}:</h3>
            <select id="text-selector" class="text-select" onchange="startSentencePractice()">
                ${optionsHtml}
            </select>
            <div class="text-selector-info">
                <p>💡 <strong>Mis textos:</strong> Textos que has subido tú</p>
                <p>💡 <strong>Textos públicos:</strong> Textos de otros usuarios que has leído y guardado palabras</p>
            </div>
        </div>
    `;
    document.getElementById('practice-exercise-card').innerHTML = html;
}

function startWordPractice() {
    initializePractice(window.practiceWords);
}

window.startSentencePractice = async function() {
    const textSelector = document.getElementById('text-selector');
    const textId = textSelector ? textSelector.value : null;
    if (!textId) {
        alert('Por favor selecciona un texto');
        return;
    }
    const isWordMode = window.practiceCurrentMode === 'selection' || window.practiceCurrentMode === 'writing';
    if (isWordMode) {
        const textTitle = textSelector.options[textSelector.selectedIndex].text;

        document.getElementById('practice-exercise-card').innerHTML = `
            <div class="loading-container">
                <h3>⚡ Preparando ejercicio</h3>
                <p>Cargando palabras del texto "${textTitle}"<span class="loading-spinner"></span></p>
            </div>
        `;
        try {
            const response = await fetch(`ajax_saved_words_content.php?get_words_by_text=1&text_id=${textId}`);
            const data = await response.json();

            if (data.success && data.words && data.words.length > 0) {
                window.practiceWords = data.words;
                window.practiceRemainingWords = [...data.words];
                window.practiceCurrentQuestionIndex = 0;
                window.practiceCorrectAnswers = 0;
                window.practiceIncorrectAnswers = 0;
                window.practiceAnswered = false;
                document.getElementById('practice-total-questions').textContent = data.words.length;
                updatePracticeStats();
                loadPracticeQuestion();
            } else {
                document.getElementById('practice-exercise-card').innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: #6b7280;">No hay palabras guardadas</h3>
                        <p style="color: #9ca3af;">No has guardado palabras del texto "${textTitle}" aún.</p>
                        <p style="color: #9ca3af; font-size: 0.9em; margin-top: 10px;">Lee el texto y guarda algunas palabras para practicar.</p>
                        <button onclick="setPracticeMode('selection')" class="option-btn">Elegir otro texto</button>
                    </div>
                `;
            }
        } catch (error) {
            document.getElementById('practice-exercise-card').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <h3 style="color: #dc2626;">Error de conexión</h3>
                    <p>No se pudieron cargar las palabras del texto.</p>
                    <button onclick="setPracticeMode('selection')" class="option-btn">Intentar de nuevo</button>
                </div>
            `;
        }
        return;
    }
    document.getElementById('practice-exercise-card').innerHTML = `
        <div class="loading-container">
            <h3>⚡ Preparando ejercicio</h3>
            <p>Cargando palabras guardadas y generando frases<span class="loading-spinner"></span></p>
        </div>
    `;
    try {
        const response = await fetch(`ajax_saved_words_content.php?get_words_by_text=1&text_id=${textId}`);
        const data = await response.json();
        if (data.success && data.words && data.words.length > 0) {
            window.currentTextTitle = data.words[0].text_title || '';
            window.practiceWords = data.words;
            window.currentSentences = data.words.map(wordObj => generatePracticeSentence(wordObj.word));
            window.currentSentenceIndex = 0;
            window.sentenceErrors = 0;
            window.sentenceCorrectAnswers = 0;
            window.sentenceIncorrectAnswers = 0;
            loadSentenceQuestion();
        } else {
            document.getElementById('practice-exercise-card').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <h3 style="color: #dc2626;">No hay palabras guardadas</h3>
                    <p>No has guardado palabras del texto.</p>
                    <button onclick="setPracticeMode('sentences')" class="option-btn">Intentar de nuevo</button>
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('practice-exercise-card').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <h3 style="color: #dc2626;">Error de conexión</h3>
                <p>No se pudo conectar con el servidor.</p>
                <button onclick="setPracticeMode('sentences')" class="option-btn">Intentar de nuevo</button>
            </div>
        `;
    }
    return;
}

function loadSentenceQuestion() {
    if (window.currentSentenceIndex >= window.currentSentences.length) {
        showSentenceResults();
        return;
    }
    const sentence = window.currentSentences[window.currentSentenceIndex];
    const textTitle = window.currentTextTitle || '';
    const instruction = textTitle
        ? `Escribe la palabra correcta del texto <span class=\"text-title-highlight\">\"${textTitle}\"</span>`
        : 'Escribe la palabra correcta';
    const correctEnglish = sentence.original_en || sentence.en;
    
    let spanishSentenceContent = sentence.es;
    if (sentence.needsTranslation && sentence.original_en) {
        fetch('translate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'word=' + encodeURIComponent(sentence.original_en)
        })
        .then(res => res.json())
        .then(data => {
            if (data.translation) {
                const spanishSentenceDiv = document.querySelector('.spanish-sentence');
                if (spanishSentenceDiv) {
                    spanishSentenceDiv.textContent = data.translation;
                }
            }
        })
        .catch(() => {
        });
    }
    
    const html = `
        <div class="sentence-practice-container">
            <div class="practice-instruction">${instruction}</div>
            <div class="spanish-sentence">${spanishSentenceContent}</div>
            <div class="translation-help-container" style="position:relative; display:flex; align-items:center; justify-content:flex-end; width:100%; margin:8px 0 0 0; gap:6px;">
                <button class="translation-help-btn" id="show-english-btn" onclick="showEnglishSentence()" style="padding:1px 12px; font-size:0.80em; min-width:0; height:22px; display:inline-flex; align-items:center; white-space:nowrap;">Mostrar en inglés</button>
                <div style="position:relative; display:inline-flex; align-items:center;">
                    <span id="always-visible-eye-sentences" style="font-size:1.25em; color:#2563eb; cursor:pointer; padding:2px 6px; border-radius:4px; transition:background 0.15s;" onmouseenter="(function(){var t=document.getElementById('always-visible-tooltip-sentences'); if(window.practiceAlwaysShowTranslation){t.textContent='Ocultar';}else{t.textContent='Dejar visible';} t.style.display='block';})()" onmouseleave="document.getElementById('always-visible-tooltip-sentences').style.display='none'">👁️</span>
                    <span id="always-visible-tooltip-sentences" style="display:none; position:absolute;  top:100%; transform:translateX(-50%); background:#222; color:#fff; padding:4px 10px; border-radius:6px; font-size:0.92em; white-space:nowrap; box-shadow:0 2px 8px rgba(0,0,0,0.13); z-index:30; opacity:0.93; max-width:180px; word-break:break-word; text-align:center;">Dejar visible</span>
                </div>
            </div>
            <div class="english-sentence hidden" id="english-reference"></div>
            <div class="input-container">
                <input type="text" class="sentence-input" id="sentence-input" data-practice-input="true" placeholder="Escribe la frase en inglés..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-correct-english="${correctEnglish.replace(/"/g, '"')}">
                <div class="word-hint" id="word-hint"></div>
            </div>
            <div class="sentence-controls">
                <button class="option-btn hint-btn" onclick="showSentenceHint()">💡 Pista</button>
                <button class="option-btn next-btn" onclick="nextSentenceQuestion()" style="display:none;">Siguiente</button>
            </div>
        </div>
    `;
    document.getElementById('practice-exercise-card').innerHTML = html;
    updateStatsLabelsForSentences();
    window.initForcedDictationInput(correctEnglish);
    
    setTimeout(() => {
        const input = document.getElementById('sentence-input');
        if (input) {
            input.focus();
        }
    }, 100);
    
    if (window.practiceAlwaysShowTranslation) {
        setTimeout(() => { showEnglishSentence(); }, 30);
    }
    
    setTimeout(function() {
        var eye = document.getElementById('always-visible-eye-sentences');
        if(eye) {
            eye.onclick = function() {
                window.practiceAlwaysShowTranslation = !window.practiceAlwaysShowTranslation;
                if(window.practiceAlwaysShowTranslation) {
                    eye.style.color = '#0ea900';
                    if(typeof showEnglishSentence === 'function') showEnglishSentence();
                } else {
                    eye.style.color = '#2563eb';
                    var div = document.getElementById('english-reference');
                    if(div && div.classList && !div.classList.contains('hidden')) {
                        div.classList.add('hidden');
                    }
                    var btn = document.getElementById('show-english-btn');
                    if(btn) btn.style.display = '';
                    
                    var originalEye = document.getElementById('always-visible-eye-sentences');
                    if(originalEye) {
                        originalEye.style.display = '';
                    }
                }
            };
            if(window.practiceAlwaysShowTranslation) {
                eye.style.color = '#0ea900';
            } else {
                eye.style.color = '#2563eb';
            }
        }
    }, 0);
}

function updateStatsLabelsForSentences() {
    const stats = document.querySelectorAll('.practice-stats .stat-label');
    if (stats.length >= 4) {
        stats[0].textContent = 'Frases';
        stats[1].textContent = 'Total';
        stats[2].textContent = 'Hechas';
        stats[3].textContent = 'Por hacer';
    }
    
    if (window.currentSentences) {
        const totalSentences = window.currentSentences.length;
        const completedSentences = window.currentSentenceIndex || 0;
        const remainingSentences = totalSentences - completedSentences;
        
        document.getElementById('practice-current-question').textContent = completedSentences;
        document.getElementById('practice-total-questions').textContent = totalSentences;
        document.getElementById('practice-correct-count').textContent = completedSentences;
        document.getElementById('practice-incorrect-count').textContent = remainingSentences;
        
        const progress = totalSentences > 0 ? (completedSentences / totalSentences) * 100 : 0;
        document.getElementById('practice-progress-bar').style.width = progress + '%';
    }
}

window.showEnglishSentence = function() {
    const showBtn = document.getElementById('show-english-btn');
    const englishDiv = document.getElementById('english-reference');
    if (showBtn && englishDiv) {
        showBtn.style.display = 'none';
        let sentence = '';
        if (window.practiceCurrentMode === 'sentences' && window.currentSentences && window.currentSentences[window.currentSentenceIndex]) {
            sentence = window.currentSentences[window.currentSentenceIndex].original_en || window.currentSentences[window.currentSentenceIndex].en;
        } else {
            sentence = window.currentSentences[window.currentSentenceIndex].en;
        }
        englishDiv.innerHTML = `
            <span id=\"english-reference-text\">${makeWordsClickable(sentence)}</span>
            <div style=\"display:inline-block; position:relative; vertical-align:middle;\">
                <button class=\"speak-sentence-btn\" id=\"speak-english-reference-btn\" title=\"Escuchar frase\" style=\"background: none; border: none; cursor: pointer; margin-left: 8px; font-size: 1.2em; vertical-align: middle;\">
                    <span role=\"img\" aria-label=\"Escuchar\">🔊</span>
                </button>
                <input type=\"range\" min=\"0\" max=\"2\" step=\"1\" value=\"1\" id=\"speak-speed-slider\" style=\"display:none; position:absolute; left:40px; top:50%; transform:translateY(-50%); width:70px; z-index:10; background:#eee; border-radius:6px; height:4px;\">
                <div style=\"position:absolute; left:40px; top:28px; width:70px; display:none; z-index:11; pointer-events:none; font-size:11px; color:#888; text-align:center text-align:center;\" id=\"speak-speed-labels\">
                    <span>50%</span>
                    <span>75%</span>
                    <span>100%</span>
                </div>
            </div>
        `;
        englishDiv.classList.remove('hidden');
        
        var originalEye = document.getElementById('always-visible-eye-sentences');
        if(originalEye) {
            originalEye.style.display = 'none';
        }
        
        const eyeContainer = document.createElement('div');
        eyeContainer.style.cssText = 'margin-top: 10px; text-align: right;';
        eyeContainer.innerHTML = `
            <div style="position:relative; display:inline-flex; align-items:center;">
                <span id="always-visible-eye-sentences-moved" style="font-size:1.25em; color:#0ea900; cursor:pointer; padding:2px 6px; border-radius:4px; transition:background 0.15s;" onmouseenter="(function(){var t=document.getElementById('always-visible-tooltip-sentences-moved'); if(window.practiceAlwaysShowTranslation){t.textContent='Ocultar';}else{t.textContent='Dejar visible';} t.style.display='block';})()" onmouseleave="document.getElementById('always-visible-tooltip-sentences-moved').style.display='none'">👁️</span>
                <span id="always-visible-tooltip-sentences-moved" style="display:none; position:absolute; top:100%; left:50%; transform:translateX(-50%); background:#222; color:#fff; padding:4px 10px; border-radius:6px; font-size:0.92em; white-space:nowrap; box-shadow:0 2px 8px rgba(0,0,0,0.13); z-index:30; opacity:0.93; max-width:180px; word-break:break-word; text-align:center;">Ocultar</span>
            </div>
        `;
        englishDiv.parentNode.insertBefore(eyeContainer, englishDiv.nextSibling);
        
        setTimeout(() => {
            const words = englishDiv.querySelectorAll('.practice-word');
            words.forEach(span => {
                span.addEventListener('click', handlePracticeWordClick);
            });
            
            var eye = document.getElementById('always-visible-eye-sentences-moved');
            if(eye) {
                eye.onclick = function() {
                    window.practiceAlwaysShowTranslation = !window.practiceAlwaysShowTranslation;
                    if(window.practiceAlwaysShowTranslation) {
                        eye.style.color = '#0ea900';
                    } else {
                        eye.style.color = '#2563eb';
                        var div = document.getElementById('english-reference');
                        if(div && div.classList && !div.classList.contains('hidden')) {
                            div.classList.add('hidden');
                        }
                        var btn = document.getElementById('show-english-btn');
                        if(btn) btn.style.display = '';
                        
                        var originalEye = document.getElementById('always-visible-eye-sentences');
                        if(originalEye) {
                            originalEye.style.display = '';
                        }
                        
                        var movedEye = document.getElementById('always-visible-eye-sentences-moved');
                        if(movedEye && movedEye.parentNode) {
                            movedEye.parentNode.remove();
                        }
                    }
                };
                if(window.practiceAlwaysShowTranslation) {
                    eye.style.color = '#0ea900';
                } else {
                    eye.style.color = '#2563eb';
                }
            }
        }, 0);
    }
}

window.checkSentenceAnswer = function() {
    const input = document.getElementById('sentence-input');
    const userText = input.value.trim();
    const correctText = window.currentSentences[window.currentSentenceIndex].en.trim();
    
    const isCorrect = userText.toLowerCase() === correctText.toLowerCase();
    
    input.disabled = true;
    
    const hintBtn = document.querySelector('.hint-btn');
    const verifyBtn = document.querySelector('.verify-btn');
    const nextBtn = document.querySelector('.next-btn');
    const showBtn = document.getElementById('show-english-btn');
    
    if (hintBtn) hintBtn.style.display = 'none';
    if (verifyBtn) verifyBtn.style.display = 'none';
    if (nextBtn) nextBtn.style.display = 'inline-flex';
    if (showBtn) showBtn.style.display = 'none';
    
    const englishDiv = document.getElementById('english-reference');
    if (englishDiv) {
        showEnglishSentence();
        setTimeout(() => {
          const words = englishDiv.querySelectorAll('.practice-word');
          words.forEach(span => {
            span.addEventListener('click', handlePracticeWordClick);
          });
        }, 0);
    }
    
    let feedbackDiv = document.createElement('div');
    feedbackDiv.className = 'practice-feedback-toast ' + (isCorrect ? 'success' : 'error');
    feedbackDiv.textContent = isCorrect ? '¡Correcto!' : `Incorrecto. Correcto: ${correctText}`;
    feedbackDiv.style.position = 'fixed';
    feedbackDiv.style.top = '30px';
    feedbackDiv.style.left = '50%';
    feedbackDiv.style.transform = 'translateX(-50%)';
    feedbackDiv.style.zIndex = '9999';
    feedbackDiv.style.padding = '6px 10px';
    feedbackDiv.style.borderRadius = '6px';
    feedbackDiv.style.fontWeight = 'bold';
    feedbackDiv.style.fontSize = '15px';
    feedbackDiv.style.boxShadow = '0 2px 8px rgba(0,0,0,0.10)';
    feedbackDiv.style.color = '#fff';
    feedbackDiv.style.background = isCorrect ? '#22c55e' : '#ef4444';
    feedbackDiv.style.opacity = '0.87';
    feedbackDiv.style.pointerEvents = 'none';
    document.body.appendChild(feedbackDiv);
    setTimeout(() => feedbackDiv.remove(), 1500);
    if (isCorrect) {
        playSuccessSound();
    } else {
        playErrorSound();
    }
}

window.nextSentenceQuestion = function() {
    window.sentenceErrors = 0;
    const successMsg = document.getElementById('success-message');
    if (successMsg) {
        successMsg.remove();
    }
    if (window.currentSentenceIndex >= window.currentSentences.length) {
        showSentenceResults();
        return;
    }
    loadSentenceQuestion();
}

function showSentenceResults() {
    savePracticeProgress(
        'sentences',
        window.currentSentences.length,
        window.sentenceCorrectAnswers,
        window.sentenceIncorrectAnswers
    );
    const html = `
        <div style="text-align: center; padding: 40px;">
            <h3 style="color: #ff8a0087;">¡Práctica completada!</h3>
            <p>Has completado todas las frases del texto.</p>
            <p>Respuestas correctas: ${window.sentenceCorrectAnswers} de ${window.currentSentences.length}</p>
            <button onclick="setPracticeMode('sentences')" class="option-btn">Practicar otro texto</button>
        </div>
    `;
    document.getElementById('practice-exercise-card').innerHTML = html;
}

window.loadPracticeMode = loadPracticeMode;

function handlePracticeWordClick(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const word = this.textContent.trim();
    if (!word || word === '___') return;

    fetch('translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'word=' + encodeURIComponent(word)
    })
    .then(res => res.json())
    .then(data => {
        if (data.translation) {
            showPracticeTooltip(this, word, data.translation);
        } else {
            showPracticeTooltip(this, word, 'No se encontró traducción');
        }
    })
    .catch(() => {
        showPracticeTooltip(this, word, 'Error en la traducción');
    });
}
window.handlePracticeWordClick = handlePracticeWordClick;

function showPracticeTooltip(element, word, translation) {
    const existing = document.querySelector('.practice-tooltip');
    if (existing) existing.remove();
    
    const tooltip = document.createElement('div');
    tooltip.className = 'practice-tooltip';
    tooltip.innerHTML = `<strong>${word}</strong> → ${translation}`;
    
    tooltip.style.cssText = `
        position: absolute;
        background: rgba(0, 0, 0, 0.92);
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
    
    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    const scrollY = window.scrollY || window.pageYOffset;
    const scrollX = window.scrollX || window.pageXOffset;
    tooltip.style.top = (rect.bottom + 6 + scrollY) + 'px';
    tooltip.style.left = (rect.left + rect.width/2 - tooltipRect.width/2 + scrollX) + 'px';
    
    setTimeout(() => {
        tooltip.style.opacity = '0';
        setTimeout(() => tooltip && tooltip.remove(), 200);
    }, 3000);
}

function showPracticeTooltipWriting(element, word, translation) {
    const existing = document.querySelector('.practice-tooltip-writing');
    if (existing) existing.remove();
    
    const tooltip = document.createElement('div');
    tooltip.className = 'practice-tooltip-writing';
    tooltip.innerHTML = `<strong>${word}</strong> → ${translation}`;
    
    tooltip.style.cssText = `
        position: absolute;
        background: rgba(0, 0, 0, 0.92);
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
    
    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    const scrollY = window.scrollY || window.pageYOffset;
    const scrollX = window.scrollX || window.pageXOffset;
    tooltip.style.top = (rect.bottom + 6 + scrollY) + 'px';
    tooltip.style.left = (rect.left + rect.width/2 - tooltipRect.width/2 + scrollX) + 'px';
    
    setTimeout(() => {
        tooltip.style.opacity = '0';
        setTimeout(() => tooltip && tooltip.remove(), 200);
    }, 3000);
}

function assignPracticeWordClickHandlers() {
    const spans = document.querySelectorAll('.practice-word');
    spans.forEach(span => {
        span.removeEventListener('click', handlePracticeWordClick);
        span.addEventListener('click', handlePracticeWordClick);
    });
}

window.assignPracticeWordClickHandlers = assignPracticeWordClickHandlers;

function playCompletionSound() {
    const audio = new Audio('https://cdn.pixabay.com/audio/2022/07/26/audio_124bfae6c2.mp3');
    audio.play();
}

function savePracticeProgress(mode, totalWords, correct, incorrect) {
    let textId = null;
    if (window.practiceWords && window.practiceWords.length > 0 && window.practiceWords[0].text_id) {
        textId = window.practiceWords[0].text_id;
    }
    
    const formData = new FormData();
    formData.append('mode', mode);
    formData.append('total_words', totalWords);
    formData.append('correct_answers', correct);
    formData.append('incorrect_answers', incorrect);
    if (textId) {
        formData.append('text_id', textId);
    }
    
    fetch('save_practice_progress.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
    })
    .catch(error => {
    });
}

window.initForcedDictationInput = function(correctText) {
    const input = document.getElementById('sentence-input');
    const wordHint = document.getElementById('word-hint');
    let errorCount = 0;

    input.onkeydown = null;
    input.onpaste = null;
    input.onselect = null;
    input.onclick = null;

    input.value = '';
    if (wordHint) wordHint.textContent = '';

    input.addEventListener('keydown', function(e) {
        if (e.key === "Backspace") {
            errorCount = 0;
            if (wordHint) wordHint.textContent = '';
            return;
        }
        const valor = input.value;
        const nextChar = correctText[valor.length] || "";
        if (e.key.length === 1) {
            if (e.key === nextChar) {
                errorCount = 0;
                if (wordHint) wordHint.textContent = '';
                if (valor + e.key === correctText) {
                    window.sentenceCorrectAnswers = (window.sentenceCorrectAnswers || 0) + 1;
                    window.currentSentenceIndex = (window.currentSentenceIndex || 0) + 1;
                    updateStatsLabelsForSentences();
                    playSuccessSound();
                    setTimeout(() => {
                        input.value = correctText;
                        input.disabled = true;
                        if (wordHint) wordHint.textContent = '';
                        let feedbackDiv = document.createElement('div');
                        feedbackDiv.className = 'practice-feedback-toast success';
                        feedbackDiv.textContent = '¡Correcto!';
                        feedbackDiv.style.position = 'fixed';
                        feedbackDiv.style.top = '30px';
                        feedbackDiv.style.left = '50%';
                        feedbackDiv.style.transform = 'translateX(-50%)';
                        feedbackDiv.style.zIndex = '9999';
                        feedbackDiv.style.padding = '6px 10px';
                        feedbackDiv.style.borderRadius = '6px';
                        feedbackDiv.style.fontWeight = 'bold';
                        feedbackDiv.style.fontSize = '15px';
                        feedbackDiv.style.boxShadow = '0 2px 8px rgba(0,0,0,0.10)';
                        feedbackDiv.style.color = '#fff';
                        feedbackDiv.style.background = '#22c55e';
                        feedbackDiv.style.opacity = '0.87';
                        feedbackDiv.style.pointerEvents = 'none';
                        document.body.appendChild(feedbackDiv);
                        setTimeout(() => feedbackDiv.remove(), 1500);
                        const showBtn = document.getElementById('show-english-btn');
                        const englishDiv = document.getElementById('english-reference');
                        if (showBtn && englishDiv) {
                            showBtn.style.display = 'none';
                            englishDiv.innerHTML = `
                              <span id="english-reference-text">${makeWordsClickable(correctText)}</span>
                              <div style="display:inline-block; position:relative; vertical-align:middle;">
                                  <button class="speak-sentence-btn" id="speak-english-reference-btn" title="Escuchar frase" style="background: none; border: none; cursor: pointer; margin-left: 8px; font-size: 1.2em; vertical-align: middle;">
                                      <span role="img" aria-label="Escuchar">🔊</span>
                                  </button>
                                  <input type="range" min="0" max="2" step="1" value="1" id="speak-speed-slider" style="display:none; position:absolute; left:40px; top:50%; transform:translateY(-50%); width:70px; z-index:10; background:#eee; border-radius:6px; height:4px;">
                                  <div style="position:absolute; left:40px; top:28px; width:70px; display:none; z-index:11; pointer-events:none; font-size:11px; color:#888; text-align:center;" id="speak-speed-labels">
                                      <span>50%</span>
                                      <span>75%</span>
                                      <span>100%</span>
                                  </div>
                              </div>
                            `;
                            englishDiv.classList.remove('hidden');
                        }
                        const nextButton = document.querySelector('.sentence-controls .next-btn');
                        if (nextButton) nextButton.style.display = 'inline-flex';
                        function nextSentenceOnEnter(ev) {
                            if (ev.key === 'Enter') {
                                window.removeEventListener('keydown', nextSentenceOnEnter);
                                window.nextSentenceQuestion();
                            }
                        }
                        window.addEventListener('keydown', nextSentenceOnEnter);
                        if (nextButton) {
                            nextButton.onclick = function() {
                                window.removeEventListener('keydown', nextSentenceOnEnter);
                                window.nextSentenceQuestion();
                            };
                        }
                    }, 10);
                }
                return;
            } else {
                errorCount++;
                e.preventDefault();
                if (wordHint && nextChar && errorCount >= 2) {
                    const currentWordEnd = valor.lastIndexOf(' ') + 1;
                    const resto = correctText.substring(currentWordEnd);
                    const match = resto.match(/^[^\s]+/);
                    let palabraPista = match ? match[0] : nextChar;
                    wordHint.textContent = palabraPista;
                    wordHint.style.display = 'block';
                    wordHint.style.color = '#bbb';
                }
                if (errorCount >= 3) {
                    input.value = valor + nextChar;
                    errorCount = 0;
                    if (wordHint) wordHint.textContent = '';
                }
            }
        } else {
            e.preventDefault();
        }
        if (["ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown", "Home", "End"].includes(e.key)) {
            e.preventDefault();
        }
    });
    input.addEventListener('paste', e => e.preventDefault());
    input.addEventListener('select', e => {
        input.setSelectionRange(input.value.length, input.value.length);
    });
    input.addEventListener('click', e => {
        input.setSelectionRange(input.value.length, input.value.length);
    });
    input.focus();
    input.addEventListener('input', function(e) {
        let valor = input.value;
        let correcto = '';
        for (let i = 0; i < valor.length; i++) {
            if (valor[i] === correctText[i]) {
                correcto += valor[i];
            } else {
                break;
            }
        }
        if (input.value !== correcto) {
            input.value = correcto;
        }
    });
};

window.makeWordsClickable = makeWordsClickable;

function renderPracticeSentence(sentence, highlightWord) {
    const englishSentence = document.getElementById('english-sentence');
    englishSentence.innerHTML = '';
    const words = sentence.split(/(\s+)/);

    words.forEach(word => {
        if (word.trim() === '') {
            englishSentence.appendChild(document.createTextNode(word));
        } else {
            const span = document.createElement('span');
            span.className = 'practice-word' + (highlightWord && normalizeWord(word) === normalizeWord(highlightWord) ? ' highlighted-word' : '');
            span.textContent = word;
            span.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const word = this.textContent.trim();
                if (!word || word === '___') return;
                fetch('translate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'word=' + encodeURIComponent(word)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.translation) {
                        showPracticeTooltip(this, word, data.translation);
                    } else {
                        showPracticeTooltip(this, word, 'No se encontró traducción');
                    }
                })
                .catch(() => {
                    showPracticeTooltip(this, word, 'Error en la traducción');
                });
            });
            englishSentence.appendChild(span);
        }
    });
}

window.showSentenceHint = function() {
    const input = document.getElementById('sentence-input');
    const wordHint = document.getElementById('word-hint');
    const correctText = window.currentSentences[window.currentSentenceIndex].en.trim();
    if (!input || input.disabled) return;
    
    const valor = input.value;
    
    if (wordHint) {
        const nextChar = correctText[valor.length] || "";
        const currentWordEnd = valor.lastIndexOf(' ') + 1;
        const resto = correctText.substring(currentWordEnd);
        const match = resto.match(/^[^\s]+/);
        let palabraPista = match ? match[0] : nextChar;
        
        if (palabraPista) {
            wordHint.textContent = palabraPista;
            wordHint.style.display = 'block';
            wordHint.style.color = '#bbb';
        }
    }
};

if (!window._delegacionFraseReferencia) {
  let hideSliderTimeout = null;
  const speedMap = [0.5, 0.75, 1];
  function showSlider() {
    const slider = document.getElementById('speak-speed-slider');
    const labels = document.getElementById('speak-speed-labels');
    if (slider && labels) {
      slider.style.display = 'block';
      labels.style.display = 'block';
      slider.focus();
      clearTimeout(hideSliderTimeout);
    }
  }
  function hideSlider() {
    const slider = document.getElementById('speak-speed-slider');
    const labels = document.getElementById('speak-speed-labels');
    if (slider && labels) {
      slider.style.display = 'none';
      labels.style.display = 'none';
    }
  }
  function delayedHideSlider() {
    clearTimeout(hideSliderTimeout);
    hideSliderTimeout = setTimeout(hideSlider, 1000);
  }
  document.addEventListener('click', function(e) {
    if (e.target.classList && e.target.classList.contains('practice-word')) {
      handlePracticeWordClick.call(e.target, e);
    }
    if (e.target.closest && e.target.closest('#speak-english-reference-btn')) {
      e.stopPropagation();
      const slider = document.getElementById('speak-speed-slider');
      const text = document.getElementById('english-reference-text')?.innerText || '';
      
      if (typeof window.getVoiceSystemReady === 'function') {
        window.getVoiceSystemReady().then(async () => {
          try {
            if (typeof window.leerTextoConResponsiveVoice === 'function') {
              const speed = slider ? (speedMap[parseInt(slider.value)] || 1) : 1;
              const success = window.leerTextoConResponsiveVoice(text, speed, {
                onerror: (error) => console.error('❌ Error en frase de referencia:', error)
              });
              
              if (!success) {
                throw new Error('ResponsiveVoice falló');
              }
            } else {
              throw new Error('ResponsiveVoice no disponible');
            }
          } catch (error) {
            if (window.speechSynthesis && window.speechSynthesis.speaking) {
              window.speechSynthesis.cancel();
            }
            const utter = new window.SpeechSynthesisUtterance(text);
            utter.lang = 'en-US';
            utter.rate = slider ? (speedMap[parseInt(slider.value)] || 1) : 1;
            window.speechSynthesis.speak(utter);
          }
        });
      } else {
        if (window.speechSynthesis && window.speechSynthesis.speaking) {
          window.speechSynthesis.cancel();
        }
        const utter = new window.SpeechSynthesisUtterance(text);
        utter.lang = 'en-US';
        utter.rate = slider ? (speedMap[parseInt(slider.value)] || 1) : 1;
        window.speechSynthesis.speak(utter);
      }
      
      delayedHideSlider();
    }
  });
  document.addEventListener('mouseenter', function(e) {
    if (e.target && (e.target.id === 'speak-english-reference-btn' || e.target.id === 'speak-speed-slider')) {
      showSlider();
    }
  }, true);
  document.addEventListener('mouseleave', function(e) {
    if (e.target && (e.target.id === 'speak-english-reference-btn' || e.target.id === 'speak-speed-slider')) {
      delayedHideSlider();
    }
  }, true);
  document.addEventListener('blur', function(e) {
    if (e.target && e.target.id === 'speak-speed-slider') {
      delayedHideSlider();
    }
  }, true);
  document.addEventListener('input', function(e) {
    if (e.target && e.target.id === 'speak-speed-slider') {
      clearTimeout(hideSliderTimeout);
      const slider = e.target;
      const text = document.getElementById('english-reference-text')?.innerText || '';
      
      if (typeof window.getVoiceSystemReady === 'function') {
        window.getVoiceSystemReady().then(async () => {
          try {
            if (typeof window.leerTextoConResponsiveVoice === 'function') {
              const speed = speedMap[parseInt(slider.value)] || 1;
              const success = window.leerTextoConResponsiveVoice(text, speed, {
                onerror: (error) => console.error('❌ Error en cambio de velocidad:', error)
              });
              
              if (!success) {
                throw new Error('ResponsiveVoice falló');
              }
            } else {
              throw new Error('ResponsiveVoice no disponible');
            }
          } catch (error) {
            if (window.speechSynthesis && window.speechSynthesis.speaking) {
              window.speechSynthesis.cancel();
            }
            const utter = new window.SpeechSynthesisUtterance(text);
            utter.lang = 'en-US';
            utter.rate = speedMap[parseInt(slider.value)] || 1;
            window.speechSynthesis.speak(utter);
          }
        });
      } else {
        if (window.speechSynthesis && window.speechSynthesis.speaking) {
          window.speechSynthesis.cancel();
        }
        const utter = new window.SpeechSynthesisUtterance(text);
        utter.lang = 'en-US';
        utter.rate = speedMap[parseInt(slider.value)] || 1;
        window.speechSynthesis.speak(utter);
      }
      
      delayedHideSlider();
    }
  });
  window._delegacionFraseReferencia = true;
}
