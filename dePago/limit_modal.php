<!-- Modal de Límite de Traducciones Alcanzado -->
<div id="limit-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; padding: 30px; border-radius: 15px; max-width: 450px; width: 90%; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); position: relative; animation: modalFadeIn 0.3s ease-out;">
        
        <!-- Icono de aviso -->
        <div style="font-size: 50px; margin-bottom: 15px;">⏳</div>
        
        <h2 style="color: #333; margin-bottom: 15px; font-size: 24px;">Límite semanal alcanzado</h2>
        
        <p style="color: #666; line-height: 1.6; margin-bottom: 20px; font-size: 16px;">
            Has traducido más de <strong style="color: #d32f2f;">300 palabras</strong> esta semana. 
            Tu mes de prueba ha finalizado y has alcanzado el límite de la versión gratuita.
        </p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 25px; border-left: 4px solid #1976d2;">
            <p style="margin: 0; font-size: 14px; color: #444;">
                Las traducciones se reactivarán el:<br>
                <strong id="limit-reset-date" style="color: #1976d2;">--/--/----</strong>
            </p>
        </div>
        
        <p style="color: #555; font-size: 15px; margin-bottom: 25px;">
            Puedes <strong>seguir leyendo</strong> el texto en inglés para practicar tu comprensión, pero las traducciones automáticas estarán desactivadas.
        </p>
        
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <button id="close-limit-modal" style="background: #1976d2; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; transition: background 0.2s;">
                Entendido, seguiré leyendo
            </button>
            
            <a href="index.php?tab=premium" style="color: #1976d2; text-decoration: none; font-size: 14px; font-weight: 500; margin-top: 5px;">
                Saber más sobre el plan Premium (Ilimitado)
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
