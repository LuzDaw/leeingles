// ============================================
// FUNCIONES DE PRÁCTICA Y EJERCICIOS
// ============================================

// Función global para configurar voz en inglés offline
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
window.practiceAlwaysShowTranslation = false;

// === CONTADOR DE TIEMPO DE PRÁCTICA ===
window.practiceStartTime = null;
window.practiceLastSaveTime = null;
window.practiceUpdateInterval = null;

window.savePracticeTime = function(seconds, isFinal = false) {
    if (seconds <= 0) return;
    const mode = window.practiceCurrentMode || 'selection';
    const body = 'duration=' + seconds + '&mode=' + encodeURIComponent(mode);
    fetch('practicas/save_practice_time.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body,
        keepalive: true
    }).catch(err => console.error("Error guardando tiempo de práctica:", err));
};

window.startPracticeTimer = function() {
    window.stopPracticeTimer();
    window.practiceStartTime = Date.now();
    window.practiceLastSaveTime = Date.now();
    window.practiceUpdateInterval = setInterval(() => {
        const now = Date.now();
        const delta = Math.floor((now - window.practiceLastSaveTime) / 1000);
        if (delta >= 10) {
            window.savePracticeTime(delta);
            window.practiceLastSaveTime = now;
        }
    }, 10000);
};

window.stopPracticeTimer = function() {
    if (window.practiceUpdateInterval) {
        clearInterval(window.practiceUpdateInterval);
        window.practiceUpdateInterval = null;
    }
    if (window.practiceLastSaveTime) {
        const now = Date.now();
        const delta = Math.floor((now - window.practiceLastSaveTime) / 1000);
        if (delta > 0) window.savePracticeTime(delta, true);
        window.practiceLastSaveTime = null;
    }
};

// Cargar modo práctica
window.loadPracticeMode = async function() {
    showPracticeModeSelector();
}

function showPracticeModeSelector() {
    const practiceHTML = `
        <div class="mode-selector">
            <button class="mode-btn active" onclick="setPracticeMode('selection')">📝 Selección múltiple</button>
            <button class="mode-btn" onclick="setPracticeMode('writing')">✍️ Escribir palabra</button>
            <button class="mode-btn" onclick="setPracticeMode('sentences')">📖 Escribir frases</button>
        </div>
        <div class="progress"><div class="progress-bar" id="practice-progress-bar" style="width: 0%"></div></div>
        <div class="exercise-card" id="practice-exercise-card"></div>
        <div class="practice-stats">
            <div class="stat-item"><div class="stat-number" id="practice-current-question">0</div><div class="stat-label" id="label-current">Pregunta</div></div>
            <div class="stat-item"><div class="stat-number" id="practice-total-questions">0</div><div class="stat-label" id="label-total">Total</div></div>
            <div class="stat-item"><div class="stat-number" id="practice-correct-count">0</div><div class="stat-label" id="label-correct">Correctas</div></div>
            <div class="stat-item"><div class="stat-number" id="practice-incorrect-count">0</div><div class="stat-label" id="label-incorrect">Incorrectas</div></div>
        </div>
    `;
    document.getElementById('practice-content').innerHTML = practiceHTML;
    window.practiceCurrentMode = 'selection';
    loadSentencePractice();
}

window.setPracticeMode = function(mode) {
    window.practiceAlwaysShowTranslation = false;
    window.practiceCurrentMode = mode;
    document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
    if (typeof event !== 'undefined' && event.target) event.target.classList.add('active');
    
    // Actualizar etiquetas según el modo
    const labelCurrent = document.getElementById('label-current');
    if (labelCurrent) {
        labelCurrent.textContent = mode === 'sentences' ? 'Frase' : 'Pregunta';
    }
    
    loadSentencePractice();
}

window.loadPracticeQuestion = function() {
    // El header ahora se mantiene visible en las pestañas de práctica
    
    const randomIndex = Math.floor(Math.random() * window.practiceRemainingWords.length);
    const currentWord = window.practiceRemainingWords[randomIndex];
    window.practiceCurrentWordIndex = randomIndex;
    
    if (window.practiceCurrentMode === 'selection' || window.practiceCurrentMode === 'writing') {
        if (!currentWord.context || currentWord.context.trim() === '') {
            window.practiceCurrentSentenceData = {
                en: `The word "${currentWord.word}" is important.`,
                es: `La palabra "${currentWord.word}" es importante.`
            };
        } else {
            let contextWithHole = currentWord.context;
            const cleanWord = currentWord.word.replace(/[.,!?;:]/g, '').trim();
            const regex = new RegExp(`\\b${cleanWord.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
            contextWithHole = currentWord.context.replace(regex, '____');
            
            window.practiceCurrentSentenceData = {
                en: contextWithHole,
                es: '',
                original_en: currentWord.context,
                translation: currentWord.translation,
                word: currentWord.word,
                needsTranslation: true
            };
        }
    } else {
        window.practiceCurrentSentenceData = generatePracticeSentence(currentWord.word);
    }
    
    window.practiceAnswered = false;
    const textTitle = currentWord.text_title || 'este texto';
    const instruction = window.practiceCurrentMode === 'selection' ? 
        `Elige la palabra correcta del texto <span class="text-title-highlight">"${textTitle}"</span>:` : 
        `Escribe la palabra correcta del texto <span class="text-title-highlight">"${textTitle}"</span>:`;

    let html = `
        <div class="practice-instruction">${instruction}</div>
        <div class="practice-sentence" id="english-sentence-container">
            <span id="english-sentence">${makeWordsClickable(window.practiceCurrentSentenceData.en)}</span>
            <div style="display:inline-block; position:relative; vertical-align:middle;">
                <button class="speak-sentence-btn" id="speak-sentence-btn" title="Escuchar frase" style="background:none; border:none; cursor:pointer; margin-left:8px; font-size:1.2em; vertical-align:middle;">🔊</button>
                <input type="range" min="0" max="2" step="1" value="1" id="speak-speed-slider" style="display:none; position:absolute; left:40px; top:50%; transform:translateY(-50%); width:70px; z-index:10; background:#eee; border-radius:6px; height:4px;">
                <div style="position:absolute; left:40px; top:28px; width:70px; display:none; z-index:11; pointer-events:none; font-size:11px; color:#888; text-align:center;" id="speak-speed-labels">
                    <span>50%</span><span>75%</span><span>100%</span>
                </div>
            </div>
        </div>
        <div class="spanish-translation hidden" id="spanish-translation"></div>
        <div class="translation-help-container" style="display:flex; align-items:center; justify-content:flex-end; width:100%; margin-top:8px; gap:6px;">
            <button class="translation-help-btn" id="show-translation-btn" onclick="showPracticeTranslation()" style="padding:1px 12px; font-size:0.8em; height:22px;">📖 Ver traducción</button>
            <span id="always-visible-eye" style="font-size:1.25em; color:#2563eb; cursor:pointer; padding:2px 6px;">👁️</span>
        </div>
    `;
    
    if (window.practiceCurrentMode === 'selection') {
        const distractors = generatePracticeDistractors(currentWord.word);
        const allOptions = [...distractors, currentWord.word].sort(() => Math.random() - 0.5);
        html += '<div class="practice-options">';
        allOptions.forEach(option => {
            html += `<button class="option-btn" onclick="selectPracticeOption('${option.replace(/'/g, "\\'")}', '${currentWord.word.replace(/'/g, "\\'")}')">${option}</button>`;
        });
        html += '</div>';
    } else {
        html += `<input type="text" placeholder="Escribe la palabra..." style="width:25%; padding:12px; border:2px solid #e5e7eb; border-radius:8px; margin:15px auto; display:block;" data-practice-input="true" data-correct-word="${currentWord.word}">
                 <div id="word-hint" style="display:none; font-size:12px; color:#999; text-align:center; font-style:italic;"></div>`;
    }

    html += `<div class="practice-controls">
                <button class="option-btn hint-btn" onclick="showPracticeHint('${currentWord.word.replace(/'/g, "\\'")}')">💡 Pista</button>
                <button class="option-btn next-btn" onclick="nextPracticeQuestion()" style="display:none;">Siguiente</button>
             </div>`;

    document.getElementById('practice-exercise-card').innerHTML = html;
    setTimeout(assignPracticeWordClickHandlers, 10);
    setupVoiceEvents();
    
    if (window.practiceCurrentMode === 'writing') {
        const input = document.querySelector('[data-practice-input="true"]');
        if (input) {
            input.focus();
            window.currentWordErrors = 0;
            input.addEventListener('keypress', e => { if (e.key === 'Enter') checkPracticeWriteAnswer(currentWord.word); });
            input.addEventListener('input', () => checkWordInput(currentWord.word));
        }
    }

    const eye = document.getElementById('always-visible-eye');
    if (eye) {
        eye.onclick = () => {
            window.practiceAlwaysShowTranslation = !window.practiceAlwaysShowTranslation;
            eye.style.color = window.practiceAlwaysShowTranslation ? '#e48415e5' : '#2563eb';
            if (window.practiceAlwaysShowTranslation) showPracticeTranslation();
            else document.getElementById('spanish-translation').classList.add('hidden');
        };
    }
    if (window.practiceAlwaysShowTranslation) showPracticeTranslation();
    
    updatePracticeStats();
}

function normalizeWord(word) {
    return word.toLowerCase().replace(/[.,!?;:'"`~@#$%^&*()_+\-=\[\]{}|\\;:"'<>?\/]/g, '');
}

function getSmartHint(userText, correctWord) {
    let correctLength = 0;
    for (let i = 0; i < userText.length && i < correctWord.length; i++) {
        if (userText[i].toLowerCase() === correctWord[i].toLowerCase()) correctLength++;
        else break;
    }
    return correctLength < correctWord.length ? correctWord.substring(0, correctLength + 1) : correctWord;
}

window.checkWordInput = function(correctWord) {
    const input = document.querySelector('[data-practice-input="true"]');
    const wordHint = document.getElementById('word-hint');
    if (!input) return;
    const userText = input.value;
    const normalizedUser = normalizeWord(userText);
    const normalizedCorrect = normalizeWord(correctWord);

    if (normalizedUser === normalizedCorrect) {
        if (wordHint) wordHint.style.display = 'none';
        input.disabled = true;
        showWordSuccessFeedback(input);
        return;
    }

    // Lógica de error progresivo
    if (normalizedUser.length > 0 && !normalizedCorrect.startsWith(normalizedUser)) {
        window.currentWordErrors = (window.currentWordErrors || 0) + 1;
        playErrorSound();
        
        if (window.currentWordErrors >= 2) {
            window.practiceIncorrectAnswers++;
            updatePracticeStats();
            input.value = getSmartHint(userText, correctWord);
            window.currentWordErrors = 0;
        } else {
            input.value = userText.substring(0, userText.length - 1);
        }
        if (wordHint) {
            wordHint.textContent = correctWord;
            wordHint.style.display = 'block';
        }
    }
};

function showWordSuccessFeedback(inputElement) {
    const currentWord = window.practiceRemainingWords[window.practiceCurrentWordIndex];
    window.practiceCorrectAnswers++;
    window.practiceRemainingWords.splice(window.practiceCurrentWordIndex, 1);
    playSuccessSound();
    
    const englishSentence = document.getElementById('english-sentence');
    if (englishSentence && window.practiceCurrentSentenceData) {
        const sentenceWithWord = window.practiceCurrentSentenceData.original_en || window.practiceCurrentSentenceData.en.replace(/____+/g, currentWord.word);
        renderPracticeSentence(sentenceWithWord, currentWord.word);
    }
    
    inputElement.style.display = 'none';
    const nextButton = document.querySelector('.practice-controls .next-btn');
    if (nextButton) nextButton.style.display = 'inline-flex';
    updatePracticeStats();
    showTranslationAfterAnswer();
}

function generatePracticeSentence(word) {
    const practiceWord = window.practiceWords.find(w => w.word === word);
    const context = practiceWord ? practiceWord.context : `The ${word} is important.`;
    const regex = new RegExp(`\\b${word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
    return {
        en: context.replace(regex, '___'),
        es: '',
        original_en: context,
        word: word,
        translation: practiceWord ? practiceWord.translation : '',
        needsTranslation: true
    };
}

function makeWordsClickable(text, highlightWord = null) {
    const words = text.match(/\w+|[.,!?;:()"'-]+|\s+/g);
    if (!words) return text;
    return words.map(word => {
        if (word === '___' || word === '____') return `<span class="practice-gap">${word}</span>`;
        if (/^\w+$/.test(word)) return `<span class="practice-word${highlightWord && normalizeWord(word) === normalizeWord(highlightWord) ? ' highlighted-word' : ''}">${word}</span>`;
        return word;
    }).join('');
}

function translatePracticeSentence(originalSentence, wordTranslation) {
    fetch('/traduciones/translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'word=' + encodeURIComponent(originalSentence)
    })
    .then(res => res.json())
    .then(data => {
        const div = document.getElementById('spanish-translation');
        if (div && data.translation) {
            div.innerHTML = data.translation + (wordTranslation ? ` <span class="highlighted-word">(${wordTranslation})</span>` : '');
            div.classList.remove('hidden');
        }
    });
}

function generatePracticeDistractors(correctWord) {
    const allWords = window.practiceWords.filter(w => w.word !== correctWord).map(w => w.word);
    const common = ['house', 'book', 'time', 'water', 'good', 'work', 'think', 'know', 'want', 'say'];
    let distractors = allWords.sort(() => Math.random() - 0.5).slice(0, 3);
    while (distractors.length < 3) {
        const w = common[Math.floor(Math.random() * common.length)];
        if (!distractors.includes(w) && w !== correctWord) distractors.push(w);
    }
    return distractors;
}

function playSuccessSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.setValueAtTime(440, ctx.currentTime);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        osc.start(); osc.stop(ctx.currentTime + 0.2);
    } catch (e) {}
}

function playErrorSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.setValueAtTime(220, ctx.currentTime);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        osc.start(); osc.stop(ctx.currentTime + 0.2);
    } catch (e) {}
}

window.selectPracticeOption = function(selected, correct) {
    if (window.practiceAnswered) return;
    window.practiceAnswered = true;
    const isCorrect = normalizeWord(selected) === normalizeWord(correct);
    
    document.querySelectorAll('.option-btn').forEach(btn => {
        if (normalizeWord(btn.textContent) === normalizeWord(correct)) btn.classList.add('correct');
        else if (normalizeWord(btn.textContent) === normalizeWord(selected)) btn.classList.add('incorrect');
    });

    const nextBtn = document.querySelector('.practice-controls .next-btn');
    if (nextBtn) nextBtn.style.display = 'inline-flex';
    
    // Rellenar el hueco con la palabra correcta
    if (window.practiceCurrentSentenceData) {
        const sentenceWithWord = window.practiceCurrentSentenceData.original_en || window.practiceCurrentSentenceData.en.replace(/____+/g, correct);
        renderPracticeSentence(sentenceWithWord, correct);
    }

    if (isCorrect) {
        window.practiceCorrectAnswers++;
        window.practiceRemainingWords.splice(window.practiceCurrentWordIndex, 1);
        playSuccessSound();
    } else {
        window.practiceIncorrectAnswers++;
        const word = window.practiceRemainingWords.splice(window.practiceCurrentWordIndex, 1)[0];
        window.practiceRemainingWords.push(word);
        playErrorSound();
    }
    
    showTranslationAfterAnswer();
    updatePracticeStats();
    if (window.practiceRemainingWords.length === 0) showPracticeResults();
}

window.checkPracticeWriteAnswer = function(correct) {
    if (window.practiceAnswered) return;
    const input = document.querySelector('[data-practice-input="true"]');
    if (!input || !input.value.trim()) return;
    
    const isCorrect = normalizeWord(input.value) === normalizeWord(correct);
    if (!isCorrect) {
        window.practiceIncorrectAnswers++;
        updatePracticeStats();
        playErrorSound();
        return; // No bloqueamos el input si falla al pulsar Enter, dejamos que siga intentando o use pista
    }

    window.practiceAnswered = true;
    input.disabled = true;
    
    // Rellenar el hueco con la palabra correcta
    if (window.practiceCurrentSentenceData) {
        const sentenceWithWord = window.practiceCurrentSentenceData.original_en || window.practiceCurrentSentenceData.en.replace(/____+/g, correct);
        renderPracticeSentence(sentenceWithWord, correct);
    }

    const nextBtn = document.querySelector('.practice-controls .next-btn');
    if (nextBtn) nextBtn.style.display = 'inline-flex';

    window.practiceCorrectAnswers++;
    window.practiceRemainingWords.splice(window.practiceCurrentWordIndex, 1);
    playSuccessSound();
    
    showTranslationAfterAnswer();
    updatePracticeStats();
    if (window.practiceRemainingWords.length === 0) showPracticeResults();
}

window.showPracticeTranslation = function() {
    const div = document.getElementById('spanish-translation');
    if (!div || !window.practiceCurrentSentenceData) return;
    if (!div.innerHTML.trim()) {
        translatePracticeSentence(window.practiceCurrentSentenceData.original_en || window.practiceCurrentSentenceData.en, window.practiceCurrentSentenceData.translation);
    }
    div.classList.remove('hidden');
    const btn = document.getElementById('show-translation-btn');
    if (btn) btn.style.display = 'none';
};

function showTranslationAfterAnswer() {
    const div = document.getElementById('spanish-translation');
    if (div) {
        div.classList.remove('hidden');
        if (!div.innerHTML.trim()) showPracticeTranslation();
    }
}

window.nextPracticeQuestion = function() {
    if (window.practiceRemainingWords.length === 0) showPracticeResults();
    else loadPracticeQuestion();
}

function updatePracticeStats() {
    const total = window.practiceWords.length;
    const correct = window.practiceCorrectAnswers;
    const incorrect = window.practiceIncorrectAnswers;
    
    // Calcular pregunta actual de forma consistente
    let current = 0;
    if (window.practiceCurrentMode === 'sentences') {
        current = window.currentSentenceIndex + 1;
    } else {
        current = total - window.practiceRemainingWords.length;
        if (!window.practiceAnswered && current < total) current++;
    }
    
    const elCurrent = document.getElementById('practice-current-question');
    const elTotal = document.getElementById('practice-total-questions');
    const elCorrect = document.getElementById('practice-correct-count');
    const elIncorrect = document.getElementById('practice-incorrect-count');
    
    if (elCurrent) elCurrent.textContent = Math.min(current, total);
    if (elTotal) elTotal.textContent = total;
    if (elCorrect) elCorrect.textContent = correct;
    if (elIncorrect) elIncorrect.textContent = incorrect;
    
    const progressBar = document.getElementById('practice-progress-bar');
    if (progressBar) {
        progressBar.style.width = (total > 0 ? (correct / total) * 100 : 0) + '%';
    }
}

function showPracticeResults() {
    savePracticeProgress(window.practiceCurrentMode, window.practiceWords.length, window.practiceCorrectAnswers, window.practiceIncorrectAnswers);
    window.stopPracticeTimer();
    
    document.getElementById('practice-exercise-card').innerHTML = `
        <div class="practice-results">
            <h3>🎉 ¡Ejercicio completado!</h3>
            <div class="practice-score">${window.practiceCorrectAnswers} palabras aprendidas</div>
            <div style="margin-top:30px;">
                <button class="nav-btn2" id="practice-next-btn" onclick="window.location.href='index.php?tab=practice'">Seguir practicando</button>
            </div>
        </div>
    `;
}

window.restartPracticeExercise = function() {
    window.practiceRemainingWords = [...window.practiceWords].sort(() => Math.random() - 0.5);
    window.practiceCurrentQuestionIndex = 0;
    window.practiceCorrectAnswers = 0;
    window.practiceIncorrectAnswers = 0;
    window.practiceAnswered = false;
    updatePracticeStats();
    loadPracticeQuestion();
}

window.showPracticeHint = function(word) {
    const hint = word.substring(0, 2);
    
    // 1. Mostrar en el hueco (gap) de la frase
    const gap = document.querySelector('.practice-gap');
    if (gap) {
        const originalText = gap.textContent;
        gap.textContent = hint + '...';
        gap.classList.add('highlighted-word');
        setTimeout(() => {
            gap.textContent = originalText;
            gap.classList.remove('highlighted-word');
        }, 3000);
    }

    // 2. Si estamos en modo escritura, insertar en el input
    if (window.practiceCurrentMode === 'writing') {
        const input = document.querySelector('[data-practice-input="true"]');
        if (input && !input.disabled) {
            const smartHint = getSmartHint(input.value, word);
            input.value = smartHint;
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        }
    }
    
    // 3. Feedback visual en el botón
    const hintBtn = document.querySelector('.hint-btn');
    if (hintBtn) {
        hintBtn.innerHTML = `💡 Pista: ${hint}...`;
        setTimeout(() => { hintBtn.innerHTML = `💡 Pista`; }, 3000);
    }
}

window.currentSentences = [];
window.currentSentenceIndex = 0;

async function loadSentencePractice() {
    const container = document.getElementById('practice-exercise-card');
    try {
        const form = new URLSearchParams(); form.set('action', 'list');
        const res = await fetch('ajax/ajax_user_texts.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.success && data.texts && data.texts.length > 0) {
            showTextSelector(data.texts);
        } else {
            // Mostrar estado vacío si no hay textos
            if (container) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                        <div style="font-size: 3.5rem; margin-bottom: 15px; opacity: 0.5;">📚</div>
                        <h3 style="margin-bottom: 10px; color: #374151;">No hay textos en tu lista</h3>
                        <p style="margin-bottom: 25px;">¡Comienza subiendo un texto o explora los públicos!</p>
                        <button type="button" onclick="loadTabContent('upload')" class="nav-btn primary" style="padding: 12px 30px;">
                            ⬆ Carga tu primer texto
                        </button>
                    </div>
                `;
            }
        }
    } catch (e) { 
        console.error(e);
        if (container) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <div style="font-size: 3.5rem; margin-bottom: 15px; opacity: 0.5;">📚</div>
                    <h3 style="margin-bottom: 10px; color: #374151;">No hay textos en tu lista</h3>
                    <p style="margin-bottom: 25px;">¡Comienza subiendo un texto o explora los públicos!</p>
                    <button type="button" onclick="loadTabContent('upload')" class="nav-btn primary" style="padding: 12px 30px;">
                        ⬆ Carga tu primer texto
                    </button>
                </div>
            `;
        }
    }
}

function showTextSelector(texts) {
    let options = texts.map(t => `<option value="${t.id}">${t.title} (${t.saved_word_count || 0} palabras)</option>`).join('');
    document.getElementById('practice-exercise-card').innerHTML = `
        <div class="text-selector-container">
            <h3>Elige un texto para practicar:</h3>
            <select id="text-selector" class="text-select" onchange="startSentencePractice()">
                <option value="">Selecciona...</option>${options}
            </select>
        </div>
    `;
}

window.startSentencePractice = async function() {
    const id = document.getElementById('text-selector').value;
    if (!id) return;
    const res = await fetch(`ajax/ajax_saved_words_content.php?get_words_by_text=1&text_id=${id}`);
    const data = await res.json();
    if (data.success && data.words && data.words.length > 0) {
        window.practiceWords = data.words;
        window.practiceRemainingWords = [...data.words];
        window.practiceCorrectAnswers = 0;
        window.practiceIncorrectAnswers = 0;
        if (window.practiceCurrentMode === 'sentences') {
            window.currentSentences = data.words.map(w => generatePracticeSentence(w.word));
            window.currentSentenceIndex = 0;
            loadSentenceQuestion();
        } else {
            updatePracticeStats();
            loadPracticeQuestion();
        }
        window.startPracticeTimer();
    }
}

function loadSentenceQuestion() {
    if (window.currentSentenceIndex >= window.currentSentences.length) { showSentenceResults(); return; }
    const s = window.currentSentences[window.currentSentenceIndex];
    const correct = s.original_en || s.en;
    
    document.getElementById('practice-exercise-card').innerHTML = `
        <div class="sentence-practice-container">
            <div class="practice-instruction">Escribe la frase en inglés:</div>
            <div class="spanish-sentence">${s.es || 'Cargando traducción...'}</div>
            <div class="translation-help-container" style="display:flex; align-items:center; justify-content:flex-end; width:100%; margin-top:8px; gap:6px;">
                <button class="translation-help-btn" id="show-english-btn" onclick="showEnglishSentence()" style="padding:1px 12px; font-size:0.8em; height:22px;">Mostrar en inglés</button>
                <span id="always-visible-eye-sentences" style="font-size:1.25em; color:#2563eb; cursor:pointer; padding:2px 6px;">👁️</span>
            </div>
            <div id="english-reference" class="english-sentence hidden"></div>
            <input type="text" class="sentence-input" id="sentence-input" placeholder="Escribe en inglés..." autocomplete="off">
            <div id="word-hint" class="word-hint"></div>
            <div class="sentence-controls">
                <button class="option-btn next-btn" onclick="nextSentenceQuestion()" style="display:none;">Siguiente</button>
            </div>
        </div>
    `;
    if (s.needsTranslation) translatePracticeSentence(correct);
    window.initForcedDictationInput(correct);

    const eye = document.getElementById('always-visible-eye-sentences');
    if (eye) {
        eye.onclick = () => {
            window.practiceAlwaysShowTranslation = !window.practiceAlwaysShowTranslation;
            eye.style.color = window.practiceAlwaysShowTranslation ? '#e48415e5' : '#2563eb';
            if (window.practiceAlwaysShowTranslation) showEnglishSentence();
            else document.getElementById('english-reference').classList.add('hidden');
        };
    }
    if (window.practiceAlwaysShowTranslation) showEnglishSentence();
    
    updatePracticeStats();
}

window.showEnglishSentence = function() {
    const ref = document.getElementById('english-reference');
    const s = window.currentSentences[window.currentSentenceIndex];
    if (ref && s) {
        ref.innerHTML = makeWordsClickable(s.original_en || s.en);
        ref.classList.remove('hidden');
        assignPracticeWordClickHandlers();
    }
}

window.nextSentenceQuestion = function() {
    window.currentSentenceIndex++;
    loadSentenceQuestion();
}

function showSentenceResults() {
    savePracticeProgress('sentences', window.currentSentences.length, window.practiceCorrectAnswers, window.practiceIncorrectAnswers);
    window.stopPracticeTimer();
    document.getElementById('practice-exercise-card').innerHTML = `
        <div style="text-align:center; padding:40px;">
            <h3>¡Práctica completada!</h3>
            <button onclick="setPracticeMode('sentences')" class="option-btn">Practicar otro</button>
        </div>
    `;
}

function handlePracticeWordClick(event) {
    event.preventDefault(); event.stopPropagation();
    translateAndShowTooltip(this, this.textContent.trim());
}

function showPracticeTooltip(element, word, translation) {
    const existing = document.querySelector('.practice-tooltip');
    if (existing) existing.remove();
    const tooltip = document.createElement('div');
    tooltip.className = 'practice-tooltip';
    tooltip.innerHTML = `<strong>${word}</strong> → ${translation}`;
    tooltip.style.cssText = `position:absolute; background:rgba(0,0,0,0.9); color:white; padding:10px; border-radius:8px; z-index:9999; pointer-events:none;`;
    document.body.appendChild(tooltip);
    const rect = element.getBoundingClientRect();
    tooltip.style.top = (rect.bottom + window.scrollY + 5) + 'px';
    tooltip.style.left = (rect.left + rect.width/2 - tooltip.offsetWidth/2 + window.scrollX) + 'px';
    setTimeout(() => tooltip.remove(), 3000);
}

function assignPracticeWordClickHandlers() {
    document.querySelectorAll('.practice-word').forEach(span => {
        span.onclick = handlePracticeWordClick;
    });
}

window.initForcedDictationInput = function(correctText) {
    const input = document.getElementById('sentence-input');
    const wordHint = document.getElementById('word-hint');
    if (!input) return;
    
    let errorCount = 0;
    input.value = '';
    input.focus();

    input.addEventListener('keydown', function(e) {
        if (e.key === "Backspace") {
            errorCount = 0;
            if (wordHint) wordHint.textContent = '';
            return;
        }
        
        if (e.key.length === 1) {
            const val = input.value;
            const nextChar = correctText[val.length];
            
            if (e.key === nextChar) {
                errorCount = 0;
                if (wordHint) wordHint.textContent = '';
                if (val + e.key === correctText) {
                    handleSentenceCompletion(input);
                }
            } else {
                e.preventDefault();
                errorCount++;
                if (wordHint && errorCount >= 2) {
                    const remaining = correctText.substring(val.length);
                    const match = remaining.match(/^[^\s]+/);
                    wordHint.textContent = match ? match[0] : nextChar;
                }
                if (errorCount >= 3) {
                    input.value = val + nextChar;
                    errorCount = 0;
                    if (wordHint) wordHint.textContent = '';
                    if (input.value === correctText) {
                        handleSentenceCompletion(input);
                    }
                }
                playErrorSound();
                window.practiceIncorrectAnswers++;
                updatePracticeStats();
            }
        } else if (["ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown", "Home", "End"].includes(e.key)) {
            e.preventDefault();
        }
    });
};

function handleSentenceCompletion(input) {
    window.practiceCorrectAnswers++;
    playSuccessSound();
    input.disabled = true;
    const nextBtn = document.querySelector('.next-btn');
    if (nextBtn) nextBtn.style.display = 'inline-flex';
    
    const feedback = document.createElement('div');
    feedback.className = 'practice-feedback-toast success';
    feedback.textContent = '¡Correcto!';
    feedback.style.cssText = 'position:fixed; top:30px; left:50%; transform:translateX(-50%); z-index:9999; padding:6px 10px; border-radius:6px; font-weight:bold; color:#fff; background:#e48415e5;';
    document.body.appendChild(feedback);
    setTimeout(() => feedback.remove(), 1500);
    updatePracticeStats();
}

function renderPracticeSentence(sentence, highlightWord) {
    const div = document.getElementById('english-sentence');
    if (div) div.innerHTML = makeWordsClickable(sentence, highlightWord);
    assignPracticeWordClickHandlers();
}

function savePracticeProgress(mode, total, correct, incorrect) {
    const fd = new FormData();
    fd.append('mode', mode); fd.append('total_words', total);
    fd.append('correct_answers', correct); fd.append('incorrect_answers', incorrect);
    fetch('practicas/save_practice_progress.php', { method: 'POST', body: fd });
}

function setupVoiceEvents() {
    const speedMap = [0.5, 0.75, 1];
    document.addEventListener('click', async function(e) {
        if (e.target.closest && e.target.closest('#speak-sentence-btn')) {
            e.stopPropagation();
            const slider = document.getElementById('speak-speed-slider');
            const text = window.practiceCurrentSentenceData.original_en || window.practiceCurrentSentenceData.en.replace(/____+/g, window.practiceCurrentSentenceData.word || '');
            const speed = slider ? (speedMap[parseInt(slider.value)] || 1) : 1;
            
            if (window.speechSynthesis && window.speechSynthesis.speaking) window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            utter.lang = 'en-US';
            utter.rate = speed;
            window.speechSynthesis.speak(utter);
        }
    });

    const btn = document.getElementById('speak-sentence-btn');
    const slider = document.getElementById('speak-speed-slider');
    const labels = document.getElementById('speak-speed-labels');
    if (btn && slider && labels) {
        btn.onmouseenter = () => { slider.style.display = 'block'; labels.style.display = 'block'; };
        const hide = () => { slider.style.display = 'none'; labels.style.display = 'none'; };
        btn.onmouseleave = (e) => { if (e.relatedTarget !== slider) hide(); };
        slider.onmouseleave = hide;
    }
}

// Manejador centralizado para la tecla Enter
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const nextBtn = document.querySelector('.next-btn:not([style*="display: none"]), #practice-next-btn');
        if (nextBtn && (nextBtn.offsetWidth > 0 || nextBtn.offsetHeight > 0)) {
            e.preventDefault(); e.stopPropagation();
            nextBtn.click();
        }
    }
});

window.addEventListener('beforeunload', () => window.stopPracticeTimer());
