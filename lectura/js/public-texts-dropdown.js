// Cargar categorías de textos públicos al abrir el dropdown
/**
 * Alterna la visibilidad del menú desplegable de textos públicos y carga las categorías si es necesario.
 *
 * Realiza una petición AJAX a `textoPublic/categories.php` para obtener las categorías
 * y las renderiza en el contenido del desplegable.
 *
 * @param {Event} e - El objeto de evento del clic.
 */
function togglePublicTextsDropdown(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('publicTextsDropdown');
    const content = document.getElementById('publicCategoriesContent');
    dropdown.classList.toggle('show');
    if (content.innerHTML.includes('Cargando')) {
        fetch('textoPublic/categories.php?ajax=1')
            .then(res => res.json())
            .then(data => {
                if (Array.isArray(data)) {
                    // Agregar botón 'Mostrar todo' al principio
                    let allBtn = `<button type='button' onclick='loadAllPublicTexts()' style='font-weight:bold;color:#eaa827;'>🌍 Mostrar todo</button>`;
                    let cats = data.map(cat => `<button type='button' onclick='loadPublicTextsByCategory(${cat.id}, "${cat.name.replace(/'/g, "\\'")}")'>${cat.name}</button>`).join('');
                    content.innerHTML = allBtn + cats;
                } else {
                    content.innerHTML = '<div style="padding:10px; color:#ff8a00;">No hay categorías públicas.</div>';
                }
            })
            .catch(() => {
                content.innerHTML = '<div style="padding:10px; color:#ff8a00;">Error al cargar categorías.</div>';
            });
    }
}
// Cerrar dropdown al hacer click fuera
window.addEventListener('click', function(e) {
    const dropdown = document.getElementById('publicTextsDropdown');
    if (dropdown) dropdown.classList.remove('show');
});
// Función placeholder para cargar textos públicos por categoría
/**
 * Carga y muestra los textos públicos de una categoría específica.
 *
 * Realiza una petición AJAX a `textoPublic/public_texts.php` filtrando por categoría
 * y renderiza la lista de textos en el formulario principal.
 *
 * @param {number} catId - El ID de la categoría a cargar.
 * @param {string} catName - El nombre de la categoría (para mostrar en la interfaz).
 */
function loadPublicTextsByCategory(catId, catName) {
    const form = document.getElementById('bulkForm');
    if (!form) return;
    form.innerHTML = `<div style='padding:20px; text-align:center; color:#64748b;'>Cargando textos públicos de <b>${catName}</b>...</div>`;
    fetch(`textoPublic/public_texts.php?ajax=1&category_id=${catId}`)
        .then(res => res.json())
        .then(async data => {
            // Actualizar el número de textos encontrados en la cabecera
            const numSpan = document.querySelector('.bulk-actions-container span');
            if (numSpan) numSpan.textContent = data.texts.length;
            if (Array.isArray(data.texts) && data.texts.length > 0) {
                let html = '<h3 style="color:#374151; margin-bottom:10px;">Textos públicos de <span style="color:#3b82f6;">' + data.category + '</span></h3>';
                html += '<ul class="text-list">';
                
                data.texts.forEach(txt => {
                    html += '<li class="text-item">';
                    html += '<a href="index.php?public_text_id=' + txt.id + '" class="text-title">';
                    html += '<span class="title-english">' + txt.title + '</span>';
                    
                    if (txt.title_translation) {
                        html += '<span class="title-spanish" style="color:#eaa827; font-size:0.9em; margin-left:8px; font-weight:500;">• ' + txt.title_translation + '</span>';
                    } else {
                        html += '<span class="title-spanish" style="color:#6b7280; font-size:0.9em; margin-left:8px;"></span>';
                    }
                    
                    html += '</a>';
                    html += '<span class="text-date">' + txt.word_count + ' palabras</span>';
                    html += '</li>';
                });
                
                html += '</ul>';
                form.innerHTML = html;
            } else {
                form.innerHTML = `<div style='padding:20px; text-align:center; color:#ff8a00;'>No hay textos públicos en esta categoría.</div>`;
            }
        })
        .catch(() => {
            form.innerHTML = `<div style='padding:20px; text-align:center; color:#ff8a00;'>Error al cargar los textos públicos.</div>`;
        });
}



// Función para cargar todos los textos públicos
/**
 * Carga y muestra todos los textos públicos disponibles.
 *
 * Realiza una petición AJAX a `textoPublic/public_texts.php` sin filtrar por categoría
 * y renderiza la lista de textos en el formulario principal.
 */
function loadAllPublicTexts() {
    const form = document.getElementById('bulkForm');
    if (!form) return;
    form.innerHTML = `<div style='padding:20px; text-align:center; color:#64748b;'>Cargando todos los textos públicos...</div>`;
    fetch(`textoPublic/public_texts.php?ajax=1`)
        .then(res => res.json())
        .then(async data => {
            // Actualizar el número de textos encontrados en la cabecera
            const numSpan = document.querySelector('.bulk-actions-container span');
            if (numSpan) numSpan.textContent = data.texts.length;
            if (Array.isArray(data.texts) && data.texts.length > 0) {
                let html = '<h3 style="color:#374151; margin-bottom:10px;">Todos los textos públicos</h3>';
                html += '<ul class="text-list">';
                
                data.texts.forEach(txt => {
                    html += '<li class="text-item">';
                    html += '<a href="index.php?public_text_id=' + txt.id + '" class="text-title">';
                    html += '<span class="title-english">' + txt.title + '</span>';
                    
                    if (txt.title_translation) {
                        html += '<span class="title-spanish" style="color:#eaa827; font-size:0.9em; margin-left:8px; font-weight:500;">• ' + txt.title_translation + '</span>';
                    } else {
                        html += '<span class="title-spanish" style="color:#6b7280; font-size:0.9em; margin-left:8px;"></span>';
                    }
                    
                    html += '</a>';
                    html += '<span class="text-date">' + txt.word_count + ' palabras</span>';
                    html += '</li>';
                });
                
                html += '</ul>';
                form.innerHTML = html;
            } else {
                form.innerHTML = `<div style='padding:20px; text-align:20px; color:#ff8a00;'>No hay textos públicos disponibles.</div>`;
            }
        })
        .catch(() => {
            form.innerHTML = `<div style='padding:20px; text-align:center; color:#ff8a00;'>Error al cargar los textos públicos.</div>`;
        });
}
