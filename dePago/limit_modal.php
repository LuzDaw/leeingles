<!-- Modal de Límite de Traducciones Alcanzado -->
<div id="limit-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; padding: 18px; border-radius: 12px; max-width: 320px; width: 85%; text-align: center; box-shadow: 0 8px 20px rgba(0,0,0,0.2); position: relative; animation: modalFadeIn 0.3s ease-out;">
        
        <!-- Icono de aviso -->
        <div style="font-size: 32px; margin-bottom: 10px;">⏳</div>
        
        <h2 style="color: #333; margin-bottom: 10px; font-size: 18px;">Límite semanal alcanzado</h2>
        
        <p style="color: #666; line-height: 1.4; margin-bottom: 15px; font-size: 14px;">
            Has traducido más de <strong style="color: #d32f2f;">300 palabras</strong> esta semana. 
            Tu mes de prueba ha finalizado y has alcanzado el límite gratuito.
        </p>
        
        <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 15px; border-left: 3px solid #1976d2;">
            <p style="margin: 0; font-size: 13px; color: #444;">
                Reinicio de traducciones:<br>
                <strong id="limit-reset-date" style="color: #1976d2;">--/--/----</strong>
            </p>
        </div>
        
        <p style="color: #555; font-size: 13px; margin-bottom: 15px; line-height: 1.4;">
            Puedes <strong>seguir leyendo</strong> en inglés, pero las traducciones automáticas estarán desactivadas.
        </p>
        
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <button id="close-limit-modal" style="background: #1976d2; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; transition: background 0.2s;">
                Entendido, seguiré leyendo
            </button>
            
            <a href="index.php?tab=premium" style="color: #1976d2; text-decoration: none; font-size: 12px; font-weight: 500; margin-top: 2px;">
                Saber más sobre Premium
            </a>
        </div>
    </div>
</div>

<style>
@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

#limit-modal button:hover {
    background: #1565c0 !important;
}
</style>
