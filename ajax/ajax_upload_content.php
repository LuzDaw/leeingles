<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Obtener categorías para el formulario de subir texto
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = [];
if ($categories_result) {
    while ($cat = $categories_result->fetch_assoc()) {
        $categories[] = $cat;
    }
    $categories_result->close();
}

$conn->close();
?>

<link rel="stylesheet" href="css/upload-form.css">

<style>
/* Estilos para las categorías bilingües */
.category-option {
    display: flex;
    align-items: center;
    gap: 0;
}

.category-english {
    font-weight: 600;
    color: #2563eb;
    background: none;
    border-radius: 0;
    padding: 0;
    margin-right: 0;
}

.category-separator {
    color: #a0aec0;
    font-weight: 400;
    margin: 0 4px;
}

.category-spanish {
    color: #eaa827;
    background: none;
    font-style: italic;
    border-radius: 0;
    padding: 0;
    margin-left: 0;
}

/* Estilo para el select de categorías */
#category_select {
    font-size: 15px;
    line-height: 1.5;
}

#category_select option {
    padding: 10px 18px;
    border-bottom: 1px solid #f3f4f6;
}

#category_select option:hover {
    background-color: #f1f5f9;
}

/* Clase para ocultar el dropdown */
.select-hide {
    display: none !important;
}

/* Estilos adicionales para el select personalizado */
.custom-select {
    user-select: none;
}

.select-selected {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 6px;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.select-selected:hover {
    background-color: #f1f5f9;
}

.select-items {
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.07);
    background: #fff;
    border: 1px solid #e5e7eb;
    border-top: none;
    margin-bottom: 10px;
}

.select-option {
    padding: 12px 18px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    gap: 0;
}

.select-option:last-child {
    border-bottom: none;
}

.select-option:hover {
    background-color: #f1f5f9;
}
</style>

<div class="tab-header">
    <h2>Subir nuevo texto</h2>
</div>

<div id="upload-messages"></div>

<!-- Contenedor principal de la pestaña de subir texto -->
<div class="upload-main-container">
  <!-- Columna izquierda: Formulario de subida de texto -->
  <div class="upload-form-container" id="upload-form-container" style="display: flex !important;">
    <!-- Formulario de subida de texto -->
    <form action="ajax/ajax_upload_text.php" method="post" id="upload-text-form">
      <div class="upload-form-group">
        <label for="title-input" class="upload-label">Título:</label>
        <input type="text" name="title" id="title-input" class="upload-input">
        <small class="upload-hint">El título se generará automáticamente </small>
      </div>
      <div class="upload-form-group">
        <label for="content-input" class="upload-label">Contenido:</label>
        <textarea name="content" id="content-input" class="upload-textarea" rows="4"></textarea>
      </div>
      <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
      <div class="upload-form-group">
        <label class="upload-label" style="display: flex; align-items: center; gap: 8px;">
          <input type="checkbox" name="is_public" id="is_public">
          <span style="font-weight: bold;">Texto público</span>
        </label>
      </div>
      <div id="category_section" style="display: none; margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Categoría:</label>
        
        <!-- Select personalizado para categorías -->
        <div class="custom-select" style="position: relative; width: 100%;">
            <div class="select-selected" id="select-selected" style="
                padding: 8px 12px; 
                border: 1px solid #ddd; 
                border-radius: 4px; 
                background: white; 
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: space-between;
            ">
                <span style="color: #6b7280;">-- Selecciona categoría --</span>
                <span style="color: #6b7280;">▼</span>
            </div>
            
            <div class="select-items select-hide" id="select-items" style="
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                z-index: 99;
                background: white;
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 4px 4px;
                max-height: 200px;
                overflow-y: auto;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            ">
                <?php foreach ($categories as $cat): ?>
                    <?php
                    // Separar el nombre en inglés y español
                    $parts = explode(' - ', $cat['name']);
                    $english = $parts[0] ?? '';
                    $spanish = $parts[1] ?? '';
                    
                    // Si no hay traducción, usar el nombre completo como inglés
                    if (empty($spanish)) {
                        $english = $cat['name'];
                        $spanish = '';
                    }
                    ?>
                    <div class="select-option" data-value="<?= $cat['id'] ?>" data-english="<?= htmlspecialchars($english) ?>" data-spanish="<?= htmlspecialchars($spanish) ?>" style="
                        padding: 10px 12px;
                        cursor: pointer;
                        border-bottom: 1px solid #f3f4f6;
                        transition: background-color 0.2s;
                    ">
                        <span class="category-english" style="font-weight: 600; color: #1e40af;"><?= htmlspecialchars($english) ?></span>
                        <?php if (!empty($spanish)): ?>
                            <span class="category-separator" style="color: #6b7280; margin: 0 4px;">-</span>
                            <span class="category-spanish" style="color: #eaa827; font-style: italic;"><?= htmlspecialchars($spanish) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Input oculto para el formulario -->
            <input type="hidden" name="category_id" id="category_id_input" value="0">
        </div>
      </div>
      <?php endif; ?>
      <button type="submit" class="nav-btn primary">Subir texto</button>
    </form>
  </div>
  <!-- Columna derecha: Publicidad -->
  <div class="upload-ads-container">
    <div class="ad-block ad-block-top">
      <!-- Publicidad superior -->
      <span>Anuncio superior</span>
    </div>
    <div class="ad-block ad-block-bottom">
      <!-- Publicidad inferior -->
      <span>Anuncio inferior</span>
    </div>
  </div>
</div>

<script>
// Función para inicializar cuando EventUtils esté disponible
function initializeUploadForm() {
    // Mostrar/ocultar sección de categoría cuando se marca texto público
    const isPublicCheckbox = document.getElementById('is_public');
    if (isPublicCheckbox) {
        isPublicCheckbox.addEventListener('change', function() {
            const categorySection = document.getElementById('category_section');
            const categoryInput = document.getElementById('category_id_input');
            const selectSelected = document.getElementById('select-selected');
            
            if (this.checked) {
                categorySection.style.display = 'block';
            } else {
                categorySection.style.display = 'none';
                // Resetear el valor del select cuando se desmarca texto público
                categoryInput.value = '0';
                if (selectSelected) {
                    const displayText = selectSelected.querySelector('span:first-child');
                    displayText.textContent = '-- Selecciona categoría --';
                    displayText.style.color = '#6b7280';
                }
            }
        });
    }
}

// Intentar inicializar inmediatamente o esperar a que EventUtils esté disponible
if (typeof EventUtils !== 'undefined') {
    EventUtils.onDOMReady(initializeUploadForm);
} else {
    // Fallback: esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeUploadForm);
    } else {
        initializeUploadForm();
    }
}

// Funcionalidad del select personalizado - ejecutar inmediatamente
function initializeCustomSelect() {
    const selectSelected = document.getElementById('select-selected');
    const selectItems = document.getElementById('select-items');
    const categoryInput = document.getElementById('category_id_input');
    const selectOptions = document.querySelectorAll('.select-option');

    if (!selectSelected || !selectItems) return;

    // Toggle del dropdown
    selectSelected.addEventListener('click', function(e) {
        e.stopPropagation();
        selectItems.classList.toggle('select-hide');
        
        // Cambiar la flecha
        const arrow = this.querySelector('span:last-child');
        if (selectItems.classList.contains('select-hide')) {
            arrow.textContent = '▼';
        } else {
            arrow.textContent = '▲';
        }
    });

    // Seleccionar opción
    selectOptions.forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const english = this.getAttribute('data-english');
            const spanish = this.getAttribute('data-spanish');
            
            // Actualizar el input oculto
            categoryInput.value = value;
            
            // Actualizar el texto mostrado
            const displayText = selectSelected.querySelector('span:first-child');
            if (value === '0') {
                displayText.textContent = '-- Selecciona categoría --';
                displayText.style.color = '#6b7280';
            } else {
                if (spanish) {
                    displayText.innerHTML = `<span class="category-english" style="font-weight: 600; color: #1e40af;">${english}</span><span class="category-separator" style="color: #6b7280; margin: 0 4px;">-</span><span class="category-spanish" style="color: #eaa827; font-style: italic;">${spanish}</span>`;
                } else {
                    displayText.innerHTML = `<span class="category-english" style="font-weight: 600; color: #1e40af;">${english}</span>`;
                }
            }
            
            // Cerrar dropdown
            selectItems.classList.add('select-hide');
            const arrow = selectSelected.querySelector('span:last-child');
            arrow.textContent = '▼';
            
            // Remover selección previa
            selectOptions.forEach(opt => opt.style.backgroundColor = '');
            this.style.backgroundColor = '#f3f4f6';
        });
        
        // Efectos hover
        option.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8fafc';
        });
        
        option.addEventListener('mouseleave', function() {
            if (!this.classList.contains('selected')) {
                this.style.backgroundColor = '';
            }
        });
    });

    // Cerrar dropdown al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!selectSelected.contains(e.target) && !selectItems.contains(e.target)) {
            selectItems.classList.add('select-hide');
            const arrow = selectSelected.querySelector('span:last-child');
            arrow.textContent = '▼';
        }
    });
}

// Ejecutar inicialización inmediatamente
initializeCustomSelect();

// Manejar envío del formulario con AJAX
document.getElementById('upload-text-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messagesDiv = document.getElementById('upload-messages');
    
    // Mostrar mensaje de carga
    messagesDiv.innerHTML = '<div style="color: #0066cc; padding: 10px; background: #e6f3ff; border-radius: 4px;">Subiendo texto...</div>';
    
    fetch('ajax/ajax_upload_text.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server response was not JSON:', text);
            throw new Error('Respuesta del servidor inválida');
        }
    })
    .then(data => {
        if (data.success) {
            messagesDiv.innerHTML = '<div style="color: #1e40af; padding: 15px; background: #d1fae5; border-radius: 8px; border: 1px solid #10b981; font-weight: bold; margin-bottom: 20px;">✅ ¡Texto subido exitosamente! Redirigiendo...</div>';
            
            // Resetear formulario de forma segura
            const form = document.getElementById('upload-text-form');
            if (form) form.reset();

            const categorySection = document.getElementById('category_section');
            if (categorySection) categorySection.style.display = 'none';
            
            // Resetear el select personalizado de forma segura
            const selectSelected = document.getElementById('select-selected');
            if (selectSelected) {
                const displayText = selectSelected.querySelector('span:first-child');
                if (displayText) {
                    displayText.textContent = '-- Selecciona categoría --';
                    displayText.style.color = '#6b7280';
                }
            }

            const categoryIdInput = document.getElementById('category_id_input');
            if (categoryIdInput) categoryIdInput.value = '0';
            
            // Redirigir inmediatamente a la pestaña de textos
            setTimeout(() => {
                if (typeof loadTabContent === 'function') {
                    loadTabContent('my-texts');
                } else {
                    window.location.href = 'index.php?tab=texts';
                }
            }, 1000);
        } else {
            messagesDiv.innerHTML = `<div style="color: #b91c1c; padding: 15px; background: #fef2f2; border-radius: 8px; border: 1px solid #f87171; margin-bottom: 20px;">❌ ${data.message || 'Error al subir el texto'}</div>`;
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        messagesDiv.innerHTML = `<div style="color: #b91c1c; padding: 15px; background: #fef2f2; border-radius: 8px; border: 1px solid #f87171; margin-bottom: 20px;">❌ Error: ${error.message || 'No se pudo conectar con el servidor'}</div>`;
    });
});
</script>
