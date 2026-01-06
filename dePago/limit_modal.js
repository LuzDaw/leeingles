/**
 * Lógica para el modal de límite de traducciones
 * Ubicación: dePago/limit_modal.js
 */

const LimitModal = {
    modalId: 'limit-modal',
    resetDateId: 'limit-reset-date',
    closeBtnId: 'close-limit-modal',

    /**
     * Inicializa los eventos del modal
     */
    init: function() {
        const closeBtn = document.getElementById(this.closeBtnId);
        if (closeBtn) {
            closeBtn.onclick = () => this.hide();
        }

        // Cerrar al hacer clic fuera del contenido
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.onclick = (e) => {
                if (e.target === modal) this.hide();
            };
        }
    },

    /**
     * Muestra el modal con la fecha de reinicio
     * @param {string} nextReset - Fecha formateada del próximo reinicio
     */
    show: function(nextReset) {
        const modal = document.getElementById(this.modalId);
        const resetDateEl = document.getElementById(this.resetDateId);

        if (modal) {
            if (resetDateEl && nextReset) {
                // Formatear fecha si viene en formato Y-m-d H:i:s
                try {
                    const date = new Date(nextReset.replace(/-/g, "/"));
                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                    resetDateEl.textContent = date.toLocaleDateString('es-ES', options);
                } catch (e) {
                    resetDateEl.textContent = nextReset;
                }
            }
            modal.style.display = 'flex';
            
            // Pausar lectura si está activa
            if (window.pauseReading) {
                window.pauseReading('limit-reached');
            }
        }
    },

    /**
     * Oculta el modal
     */
    hide: function() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    },

    /**
     * Verifica si la respuesta de una API indica que se ha alcanzado el límite
     * @param {Object} data - Respuesta JSON de la API
     * @returns {boolean} True si se alcanzó el límite
     */
    checkResponse: function(data) {
        if (data && data.limit_reached) {
            this.show(data.next_reset);
            return true;
        }
        return false;
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    LimitModal.init();
    // Exponer globalmente
    window.LimitModal = LimitModal;
});
