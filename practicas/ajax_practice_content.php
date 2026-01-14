<?php
session_start();
require_once '../db/connection.php';

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
// Inicializar práctica inmediatamente usando el sistema centralizado
setTimeout(() => {
    if (typeof window.loadPracticeMode === 'function') {
        window.loadPracticeMode();
    } else {
        console.error("El sistema de prácticas no está cargado correctamente.");
        document.getElementById('practice-content').innerHTML = `
            <div style="text-align: center; padding: 40px; color: #ff8a00;">
                <p>Error: El sistema de prácticas no está disponible. Por favor, recarga la página.</p>
                <button onclick="window.location.reload()" class="nav-btn primary" style="margin-top: 20px;">
                    Recargar página
                </button>
            </div>
        `;
    }
}, 100);
</script>
