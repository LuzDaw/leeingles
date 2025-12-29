// Fullscreen translation handler
document.addEventListener('DOMContentLoaded', () => {
    let isInFullscreen = false;
    
    function handleFullscreenChange() {
        const isNowFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement);
        
        if (isNowFullscreen && !isInFullscreen) {
            // Entró en pantalla completa
            isInFullscreen = true;
    
            setTimeout(() => {
                if (window.assignWordClickHandlers) {
                    window.assignWordClickHandlers();
                }
            }, 1500);
        } else if (!isNowFullscreen && isInFullscreen) {
            // Salió de pantalla completa
            isInFullscreen = false;
    
            setTimeout(() => {
                if (window.assignWordClickHandlers) {
                    window.assignWordClickHandlers();
                }
            }, 500);
        }
    }

    // Escuchar todos los eventos de pantalla completa
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);
});
