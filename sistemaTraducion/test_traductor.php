<?php
// sistemaTraducion/test_traductor.php
// Interfaz de usuario para probar el flujo de traducción.
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test del Sistema de Traducción</title>
    <style>
        /* ... (estilos sin cambios) ... */
        pre { background-color: #2d3748; color: #f7fafc; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>

    <h1>Test de Diagnóstico del Sistema de Traducción</h1>
    <p>
        Esta página ejecuta una comprobación en todas las capas de caché y muestra los resultados brutos
        para diagnosticar problemas en el flujo de datos.
    </p>

    <form id="translate-form">
        <input type="text" id="word-input" placeholder="Introduce una palabra en inglés..." required>
        <button type="submit">Traducir</button>
    </form>

    <h2>Resultado:</h2>
    <div id="results">
        <p>Esperando una palabra para traducir...</p>
    </div>

    <script>
        document.getElementById('translate-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const word = document.getElementById('word-input').value.trim();
            if (!word) return;

            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<p>Traduciendo...</p>';

            fetch('test_endpoint.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'word=' + encodeURIComponent(word)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error || !data.translation) {
                    resultsDiv.innerHTML = `<p class="error"><strong>Error:</strong> ${data.error || 'No se pudo obtener la traducción.'}</p>`;
                    return;
                }

                resultsDiv.innerHTML = `
                    <div class="result-item">
                        <strong>Palabra:</strong> ${data.word}
                    </div>
                    <div class="result-item">
                        <strong>Traducción:</strong> ${data.translation}
                    </div>
                    <div class="result-item">
                        <strong>Fuente:</strong>
                        <span class="source source-${data.source}">${data.source.replace('_', ' ')}</span>
                    </div>
                `;
            })
            .catch(error => {
                resultsDiv.innerHTML = `<p class="error"><strong>Error de conexión:</strong> ${error.message}</p>`;
            });
        });
    </script>

</body>
</html>
