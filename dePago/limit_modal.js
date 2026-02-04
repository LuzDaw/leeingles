/**
 * Lógica para el modal de límite de traducciones
 * Ubicación: dePago/limit_modal.js
 */

/**
 * @file Lógica para el modal de límite de traducciones.
 * @namespace LimitModal
 */
const LimitModal = {
    /** @property {string} modalId - El ID del elemento del modal principal. */
    modalId: 'limit-modal',
    /** @property {string} resetDateId - El ID del elemento donde se muestra la fecha de reinicio del límite. */
    resetDateId: 'limit-reset-date',
    /** @property {string} closeBtnId - El ID del botón principal para cerrar el modal. */
    closeBtnId: 'close-limit-modal',
    /** @property {string} closeXId - El ID del botón 'X' para cerrar el modal. */
    closeXId: 'close-limit-modal-x',

    /**
     * Inicializa los eventos y manejadores del modal de límite de traducciones.
     * Configura los listeners para los botones de cierre y el clic fuera del modal.
     * Al cerrar, marca el límite como aceptado y, si es posible, inicia la lectura.
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
     * Muestra el modal de límite de traducciones.
     *
     * Controla si el modal ya se ha mostrado en la sesión actual para evitar repeticiones,
     * a menos que se fuerce su aparición (ej. por agotamiento del margen de cortesía).
     * Formatea la fecha de reinicio y pausa la lectura si está activa.
     *
     * @param {string} nextReset - La fecha y hora del próximo reinicio del límite, en formato 'YYYY-MM-DD H:i:s'.
     * @param {boolean} [force=false] - Si es `true`, el modal se mostrará incluso si ya se ha visto en la sesión.
     */
    show: function(nextReset, force = false) {
        // Control de sesión: solo mostrar una vez por sesión a menos que se fuerce (Play o margen agotado)
        if (!force && sessionStorage.getItem('limit_modal_shown')) {
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
     * Oculta el modal de límite de traducciones.
     */
    hide: function() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    },

    /**
     * Verifica si la respuesta de una API indica que se ha alcanzado el límite de traducciones.
     *
     * Si el límite se ha alcanzado, establece una bandera global y muestra el modal.
     * Forzará la aparición del modal si el uso supera el margen de cortesía.
     *
     * @param {object} data - El objeto de respuesta JSON de la API que contiene información sobre el límite.
     * @param {boolean} data.limit_reached - Indica si el límite ha sido alcanzado.
     * @param {number} data.usage - El uso actual de traducciones.
     * @param {number} data.grace_limit - El límite con margen de cortesía.
     * @param {string} data.next_reset - La fecha del próximo reinicio del límite.
     * @returns {boolean} `true` si se alcanzó el límite y el modal fue mostrado, `false` en caso contrario.
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
