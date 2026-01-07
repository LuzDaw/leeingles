/**
 * Lógica para el modal de límite de traducciones
 * Ubicación: dePago/limit_modal.js
 */

const LimitModal = {
    modalId: 'limit-modal',
    resetDateId: 'limit-reset-date',
    closeBtnId: 'close-limit-modal',
    closeXId: 'close-limit-modal-x',

    /**
     * Inicializa los eventos del modal
     */
    init: function() {
        const closeBtn = document.getElementById(this.closeBtnId);
        const closeX = document.getElementById(this.closeXId);

        const handleAccept = () => {
            this.hide();
            // Al aceptar o cerrar, marcamos como aceptado para permitir lectura
            window._limitAceptado = true;
            if (window.startReading) window.startReading();
        };

        if (closeBtn) closeBtn.onclick = handleAccept;
        if (closeX) closeX.onclick = handleAccept;

        // Cerrar al hacer clic fuera del contenido
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.onclick = (e) => {
                if (e.target === modal) handleAccept();
            };
        }
    },

    /**
     * Muestra el modal con la fecha de reinicio
     * @param {string} nextReset - Fecha formateada del próximo reinicio
     * @param {boolean} force - Si es true, ignora el control de sesión
     */
    show: function(nextReset, force = false) {
        // Control de sesión: solo mostrar una vez por sesión a menos que se fuerce (Play o margen agotado)
        if (!force && sessionStorage.getItem('limit_modal_shown')) {
            console.log("LimitModal: Ya mostrado en esta sesión, omitiendo.");
            return;
        }

        const modal = document.getElementById(this.modalId);
        const resetDateEl = document.getElementById(this.resetDateId);

        if (modal) {
            // Marcar como mostrado en esta sesión
            sessionStorage.setItem('limit_modal_shown', 'true');

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
            // Marcar globalmente que se ha alcanzado el límite
            window.translationLimitReached = true;
            
            // Si el uso es >= 350 (margen de cortesía agotado), forzar el modal siempre
            const force = data.usage >= (data.grace_limit || 350);
            this.show(data.next_reset, force);
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
