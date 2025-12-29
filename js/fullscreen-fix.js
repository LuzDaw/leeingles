// Event delegation simple para pantalla completa
document.addEventListener('click', function(e) {
    // Solo si está en pantalla completa Y es una palabra clickeable
    if ((document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement) && 
        e.target.classList.contains('clickable-word')) {
        
        const word = e.target.textContent.trim();
        if (!word) return;
        
        // Mostrar tooltip inmediatamente
        const existing = document.querySelector('.fs-tooltip');
        if (existing) existing.remove();
        
        const tooltip = document.createElement('div');
        tooltip.className = 'fs-tooltip';
        tooltip.innerHTML = `Traduciendo...`;
        tooltip.style.cssText = `
            position: fixed;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 999999;
            pointer-events: none;
        `;
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        tooltip.style.top = (rect.top - 40) + 'px';
        tooltip.style.left = (rect.left) + 'px';
        
        // Hacer traducción
        fetch('translate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'word=' + encodeURIComponent(word)
        })
        .then(res => res.json())
        .then(data => {
            tooltip.innerHTML = `<strong>${word}</strong> → ${data.translation || 'Error'}`;
        })
        .catch(() => {
            tooltip.innerHTML = `<strong>${word}</strong> → Error`;
        });
        
        setTimeout(() => tooltip && tooltip.remove(), 3000);
    }
});
