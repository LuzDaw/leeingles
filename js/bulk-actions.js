/**
 * Funciones para acciones en lote (Bulk Actions) en index.php
 */

window.toggleDropdown = function(event) {
    if (event) event.stopPropagation();
    // Buscar el contenedor .dropdown más cercano al elemento pulsado
    const dropdown = event ? event.target.closest('.dropdown') : document.querySelector('.dropdown');
    if (dropdown) {
        dropdown.classList.toggle("show");
    }
};

window.updateBulkActions = function() {
    const checkboxes = document.querySelectorAll('input[name="selected_texts[]"]:checked');
    const dropdownBtn = document.getElementById('dropdownBtn');
    
    if (!dropdownBtn) return;

    if (checkboxes.length > 0) {
        dropdownBtn.disabled = false;
        dropdownBtn.textContent = `Acciones (${checkboxes.length}) ▼`;
        dropdownBtn.style.background = '#4A90E2';
        dropdownBtn.style.color = 'white';
        dropdownBtn.style.opacity = '1';
        dropdownBtn.style.cursor = 'pointer';
    } else {
        dropdownBtn.disabled = false;
        dropdownBtn.textContent = 'Acciones en lote ▼';
        dropdownBtn.style.background = '#f3f4f6';
        dropdownBtn.style.color = '#6b7280';
        dropdownBtn.style.opacity = '0.7';
        dropdownBtn.style.cursor = 'default';
    }
};

window.selectAllTexts = function() {
    const checkboxes = document.querySelectorAll('input[name="selected_texts[]"]');
    checkboxes.forEach(cb => cb.checked = true);
    updateBulkActions();
};

window.unselectAllTexts = function() {
    const checkboxes = document.querySelectorAll('input[name="selected_texts[]"]');
    checkboxes.forEach(cb => cb.checked = false);
    updateBulkActions();
};

window.performBulkAction = function(action) {
    const checkboxes = document.querySelectorAll('input[name="selected_texts[]"]:checked');

    if (checkboxes.length === 0) {
        alert('Por favor, selecciona al menos un texto.');
        return;
    }

    if (action === 'print') {
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);
        const printUrl = '/actions/print_texts.php?ids=' + selectedIds.join(',');
        window.open(printUrl, '_blank');
        return;
    }

    let confirmMessage = '';
    if (action === 'delete') {
        confirmMessage = `¿Estás seguro de que quieres eliminar ${checkboxes.length} texto(s)?`;
    } else if (action === 'make_public') {
        confirmMessage = `¿Estás seguro de que quieres hacer públicos ${checkboxes.length} texto(s)?`;
    }

    if (confirm(confirmMessage)) {
        const formData = new FormData();
        formData.append('action', action);
        
        checkboxes.forEach(checkbox => {
            formData.append('selected_texts[]', checkbox.value);
        });

        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            messagesContainer.innerHTML = '<div style="background: #e6f3ff; color: #0066cc; padding: 10px; border-radius: 4px; margin-bottom: 20px;">Procesando...</div>';
        }

        fetch('ajax/ajax_my_texts_content.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (messagesContainer) {
                    messagesContainer.innerHTML = `<div style="background: #d1fae5; color: #eaa827; padding: 10px; border-radius: 4px; margin-bottom: 20px;">✅ ${data.message}</div>`;
                }
                setTimeout(() => {
                    if (typeof window.loadTabContent === 'function') {
                        loadTabContent('my-texts');
                    }
                }, 1500);
            } else {
                if (messagesContainer) {
                    messagesContainer.innerHTML = `<div style="background: #fef2f2; color: #ff8a00; padding: 10px; border-radius: 4px; margin-bottom: 20px;">❌ ${data.message}</div>`;
                }
            }
        })
        .catch(error => {
            if (messagesContainer) {
                messagesContainer.innerHTML = '<div style="background: #fef2f2; color: #ff8a00; padding: 10px; border-radius: 4px; margin-bottom: 20px;">❌ Error de conexión. Por favor, intenta de nuevo.</div>';
            }
        });
    }
};

window.selectAllWords = function() {
    const checkboxes = document.querySelectorAll('input[name="selected_words[]"]');
    checkboxes.forEach(cb => cb.checked = true);
    updateBulkActionsWords();
};

window.unselectAllWords = function() {
    const checkboxes = document.querySelectorAll('input[name="selected_words[]"]');
    checkboxes.forEach(cb => cb.checked = false);
    updateBulkActionsWords();
};

window.updateBulkActionsWords = function() {
    const checkboxes = document.querySelectorAll('input[name="selected_words[]"]:checked');
    const dropdownBtn = document.getElementById('dropdownBtn');
    
    if (!dropdownBtn) return;

    if (checkboxes.length > 0) {
        dropdownBtn.disabled = false;
        dropdownBtn.textContent = `Acciones (${checkboxes.length}) ▼`;
        dropdownBtn.style.background = '#4A90E2';
        dropdownBtn.style.color = 'white';
        dropdownBtn.style.opacity = '1';
        dropdownBtn.style.cursor = 'pointer';
    } else {
        dropdownBtn.disabled = false;
        dropdownBtn.textContent = 'Acciones en lote ▼';
        dropdownBtn.style.background = '#f3f4f6';
        dropdownBtn.style.color = '#6b7280';
        dropdownBtn.style.opacity = '0.7';
        dropdownBtn.style.cursor = 'default';
    }
};

window.toggleGroup = function(checkbox, groupId) {
    const group = document.getElementById(groupId);
    if (group) {
        const groupCheckboxes = group.querySelectorAll('input[name="selected_words[]"]');
        groupCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
    }
    updateBulkActionsWords();
};

window.performBulkActionWords = function(action) {
    const checkboxes = document.querySelectorAll('input[name="selected_words[]"]:checked');

    if (checkboxes.length === 0) {
        alert('Por favor, selecciona al menos una palabra.');
        return;
    }

    if (action === 'delete') {
        if (confirm(`¿Estás seguro de que quieres eliminar ${checkboxes.length} palabra(s)?`)) {
            const formData = new FormData();
            formData.append('action', action);
            
            checkboxes.forEach(checkbox => {
                formData.append('selected_words[]', checkbox.value);
            });

            const messagesContainer = document.querySelector('.tab-content-wrapper');
            if (messagesContainer) {
                const loadingDiv = document.createElement('div');
                loadingDiv.id = 'loading-message';
                loadingDiv.style.cssText = 'background: #e6f3ff; color: #0066cc; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;';
                loadingDiv.textContent = 'Procesando...';
                messagesContainer.insertBefore(loadingDiv, messagesContainer.firstChild);
            }

            fetch('ajax/ajax_saved_words_content.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                if (typeof window.loadTabContent === 'function') {
                    loadTabContent('saved-words');
                }
            })
            .catch(error => {
                const loadingDiv = document.getElementById('loading-message');
                if (loadingDiv) {
                    loadingDiv.style.cssText = 'background: #fef2f2; color: #ff8a00; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;';
                    loadingDiv.textContent = '❌ Error de conexión. Por favor, intenta de nuevo.';
                }
            });
        }
    }
};

window.initializeTabEvents = function() {
    document.addEventListener('click', function(event) {
        // Cerrar todos los dropdowns si se hace clic fuera de ellos
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    });
};
