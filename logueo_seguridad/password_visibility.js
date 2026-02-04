/**
 * Función para alternar la visibilidad de la contraseña.
 * Añade un icono de ojo junto al campo de contraseña que permite
 * al usuario mostrar u ocultar la contraseña.
 * 
 * @param {string} passwordInputId - El ID del campo de entrada de la contraseña.
 * @param {string} toggleIconId - El ID del icono para alternar la visibilidad.
 */
function setupPasswordVisibilityToggle(passwordInputId, toggleIconId) {
    const passwordInput = document.getElementById(passwordInputId);
    const toggleIcon = document.getElementById(toggleIconId);

    if (passwordInput && toggleIcon) {
        // Evitar duplicar el listener si ya se ha configurado
        if (toggleIcon.dataset.initialized === 'true') return;
        
        toggleIcon.style.cursor = 'pointer';
        toggleIcon.innerHTML = '&#128065;'; // Icono de ojo cerrado

        toggleIcon.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.innerHTML = '&#128064;'; // Icono de ojo abierto
            } else {
                passwordInput.type = 'password';
                toggleIcon.innerHTML = '&#128065;'; // Icono de ojo cerrado
            }
        });
        
        toggleIcon.dataset.initialized = 'true';
    }
}

// Exportar la función para que pueda ser utilizada en otros módulos si es necesario
// Exportar la función para que pueda ser utilizada en otros módulos si es necesario
// (Comentado para evitar errores en entornos no-Node.js si no es estrictamente necesario)
/*
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        setupPasswordVisibilityToggle: setupPasswordVisibilityToggle
    };
}
*/
