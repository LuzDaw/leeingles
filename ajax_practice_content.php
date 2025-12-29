<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn->close();
?>


<div id="practice-container">
    <div style="display: flex; justify-content: space-between; align-items: center; padding:22px; margin-top: -2px;">
        <h3>🎯 Practicar Vocabulario</h3>
    </div>
    <div id="practice-content">
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            <div>Cargando ejercicios...</div>
        </div>
    </div>
</div>

<script>
// Inicializar práctica inmediatamente
setTimeout(() => {
    if (typeof window.loadPracticeMode === 'function') {
        window.loadPracticeMode();
    } else {
        // Si no está cargada la función, crear un contenido básico de práctica
        loadBasicPractice();
    }
}, 100);

function loadBasicPractice() {
    // Cargar palabras guardadas del usuario para práctica
    fetch('ajax_practice_data.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('practice-content').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <p>Error: ${data.message || 'Error cargando datos'}</p>
                    </div>
                `;
                return;
            }

            if (!data.words || data.words.length === 0) {
                document.getElementById('practice-content').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #6b7280;">
                        <div style="font-size: 3rem; margin-bottom: 20px;">📚</div>
                        <h3 style="margin-bottom: 10px; color: #374151;">No hay palabras para practicar</h3>
                        <p style="margin-bottom: 30px;">Primero lee algunos textos y guarda palabras para poder practicar.</p>
                        <button onclick="loadTabContent('my-texts')" class="nav-btn primary">
                            📚 Ver mis textos
                        </button>
                    </div>
                `;
                return;
            }

            // Mostrar selector de modo de práctica
            showPracticeModeSelector();
        })
        .catch(error => {
            document.getElementById('practice-content').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc2626;">
                    <p>Error cargando los ejercicios. Por favor, intenta de nuevo.</p>
                </div>
            `;
        });
}

function showPracticeModeSelector() {
    document.getElementById('practice-content').innerHTML = `
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h3 style="margin-bottom: 10px;">Elige tu modo de práctica</h3>
                <p style="color: #6b7280;">Selecciona cómo quieres practicar tu vocabulario</p>
            </div>
            
            <div style="display: grid; gap: 20px; margin-bottom: 30px;">
                <div class="practice-mode-card" onclick="startPracticeMode('selection')" style="cursor: pointer; padding: 20px; border: 2px solid #e5e7eb; border-radius: 12px; text-align: center; transition: all 0.2s;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">🔤</div>
                    <h4 style="margin-bottom: 8px;">Selección múltiple</h4>
                    <p style="color: #6b7280; font-size: 14px;">Elige la traducción correcta de entre 4 opciones</p>
                </div>
                
                <div class="practice-mode-card" onclick="startPracticeMode('writing')" style="cursor: pointer; padding: 20px; border: 2px solid #e5e7eb; border-radius: 12px; text-align: center; transition: all 0.2s;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">✍️</div>
                    <h4 style="margin-bottom: 8px;">Escribir palabra</h4>
                    <p style="color: #6b7280; font-size: 14px;">Completa la frase escribiendo la palabra correcta</p>
                </div>
                
                <div class="practice-mode-card" onclick="startPracticeMode('sentences')" style="cursor: pointer; padding: 20px; border: 2px solid #e5e7eb; border-radius: 12px; text-align: center; transition: all 0.2s;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">📝</div>
                    <h4 style="margin-bottom: 8px;">Traducir frases</h4>
                    <p style="color: #6b7280; font-size: 14px;">Traduce frases completas del español al inglés</p>
                </div>
            </div>
        </div>
        
        <style>
        .practice-mode-card:hover {
            border-color: #3b82f6 !important;
            background-color: #f8fafc !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        </style>
    `;
}

function startPracticeMode(mode) {
    document.getElementById('practice-content').innerHTML = `
        <div style="text-align: center; padding: 40px; color: #6b7280;">
            <div style="font-size: 2rem; margin-bottom: 10px;">⏳</div>
            <p>Preparando ejercicios de ${mode === 'selection' ? 'selección múltiple' : mode === 'writing' ? 'escritura' : 'traducción'}...</p>
        </div>
    `;
    
    // Llamar a la función del sistema de práctica principal si está disponible
    if (typeof window.setPracticeMode === 'function') {
        window.setPracticeMode(mode);
    } else {
        // Fallback básico
        setTimeout(() => {
            document.getElementById('practice-content').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc2626;">
                    <p>Funcionalidad de práctica en desarrollo. Por favor, usa la página de práctica principal.</p>
                    <button onclick="window.location.href='index.php?practice=1'" class="nav-btn primary" style="margin-top: 20px;">
                        Ir a práctica completa
                    </button>
                </div>
            `;
        }, 1000);
    }
}
</script>
