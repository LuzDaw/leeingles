(function(){
  'use strict';

  const Engine = {
    state: 'idle', // 'idle' | 'playing' | 'paused'
    currentIndex: 0,
    utterance: null,

    getRate() {
      const input = document.getElementById('rate');
      const v = input ? parseFloat(input.value) : NaN;
      return Number.isFinite(v) ? v : 0.9;
    },

    paragraphs() {
      const page = document.querySelector('.page.active');
      if (!page) return [];
      return Array.from(page.querySelectorAll('p.paragraph'));
    },

    clearHighlight() {
      document.querySelectorAll('p.paragraph.reading-current').forEach(p => p.classList.remove('reading-current'));
    },

    highlight(i) {
      const paras = this.paragraphs();
      if (paras[i]) paras[i].classList.add('reading-current');
      // Guardar posición global para continuar luego
      try {
        window.lastReadParagraphIndex = i;
        const pages = Array.from(document.querySelectorAll('.page'));
        const activePage = document.querySelector('.page.active');
        const pageIdx = pages.indexOf(activePage);
        if (pageIdx >= 0) window.lastReadPageIndex = pageIdx;
      } catch(e) {}
    },

    speak(text) {
      if (!window.speechSynthesis || !window.SpeechSynthesisUtterance) {
        console.error('SpeechSynthesis no disponible');
        this.stop();
        return;
      }
      try { window.speechSynthesis.cancel(); } catch(e) {}
      const utt = new SpeechSynthesisUtterance(text);
      utt.rate = this.getRate();
      utt.pitch = 1.0;
      utt.volume = 1.0;
      utt.lang = 'en-GB';
      utt.onend = () => {
        if (this.state !== 'playing') return;
        this.currentIndex++;
        this.next();
      };
      utt.onerror = (e) => {
        // Si fue "interrupted/canceled" por usuario, no avanzar automáticamente
        if (this.state !== 'playing') return;
        if (e && (e.error === 'interrupted' || e.error === 'canceled')) return;
        this.currentIndex++;
        this.next();
      };
      this.utterance = utt;
      window.speechSynthesis.speak(utt);
    },

    speakCurrent() {
      const paras = this.paragraphs();
      if (!paras.length) { this.stop(); return; }
      if (this.currentIndex < 0) this.currentIndex = 0;
      if (this.currentIndex >= paras.length) { this.next(); return; }
      const p = paras[this.currentIndex];
      const text = p.innerText.trim();
      this.clearHighlight();
      this.highlight(this.currentIndex);
      if (!text) { this.currentIndex++; this.next(); return; }
      this.speak(text);
    },

    next() {
      const paras = this.paragraphs();
      if (this.currentIndex < paras.length) {
        this.speakCurrent();
        return;
      }
      // Pasar de página si existe botón siguiente
      const nextBtn = document.getElementById('next-page');
      if (nextBtn && !nextBtn.disabled) {
        nextBtn.click();
        setTimeout(() => {
          this.currentIndex = 0;
          this.speakCurrent();
        }, 300);
      } else {
        this.stop();
      }
    },

    start(startIndex) {
      // Elegir índice de inicio: preferir parámetro, luego índice actual, luego último guardado, luego 0
      if (typeof startIndex === 'number') {
        this.currentIndex = startIndex;
      } else if (typeof this.currentIndex === 'number' && this.currentIndex >= 0) {
        // mantener
      } else if (typeof window.lastReadParagraphIndex === 'number') {
        this.currentIndex = window.lastReadParagraphIndex;
      } else {
        this.currentIndex = 0;
      }
      this.state = 'playing';
      // Sincronizar flags globales
      try { window.isCurrentlyReading = true; window.isCurrentlyPaused = false; } catch(e) {}
      try { if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton(); } catch(e) {}
      this.speakCurrent();
    },

    pause() {
      if (this.state !== 'playing') return;
      this.state = 'paused';
      try { window.isCurrentlyPaused = true; } catch(e) {}
      try { window.speechSynthesis.cancel(); } catch(e) {}
      try { if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton(); } catch(e) {}
    },

    resume() {
      if (this.state === 'paused') {
        this.state = 'playing';
        try { window.isCurrentlyPaused = false; window.isCurrentlyReading = true; } catch(e) {}
        try { if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton(); } catch(e) {}
        this.speakCurrent();
      } else if (this.state === 'idle') {
        this.start(this.currentIndex || 0);
      }
    },

    stop() {
      this.state = 'idle';
      try { window.speechSynthesis.cancel(); } catch(e) {}
      this.clearHighlight();
      // Sincronizar flags globales
      try { window.isCurrentlyReading = false; window.isCurrentlyPaused = false; } catch(e) {}
      try { if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton(); } catch(e) {}
    }
  };

  window.ReadingEngine = Engine;
  
  // Exponer controles SOLO si no existen (no pisar los de lector.js)
  if (typeof window.startReading !== 'function') {
    window.startReading = function() { Engine.start(0); };
  }
  if (typeof window.startReadingFromIndex !== 'function') {
  window.startReadingFromIndex = function(i) { Engine.start(typeof i === 'number' ? i : 0); };
  }
  if (typeof window.pauseSpeech !== 'function') {
  window.pauseSpeech = function() { Engine.pause(); };
  }
  if (typeof window.resumeSpeech !== 'function') {
  window.resumeSpeech = function() { Engine.resume(); };
  }
  if (typeof window.stopReading !== 'function') {
    window.stopReading = function() { Engine.stop(); };
  }
  
})();
