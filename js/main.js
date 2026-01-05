// Funciones específicas para la página principal
// Las funciones comunes están en common-functions.js

EventUtils.onDOMReady(() => {
  const textContainer = DOMUtils.getElement("text");
  const translationBox = DOMUtils.getElement("translation-box");
  const selectedWordEl = DOMUtils.getElement("selected-word");
  const translatedWordEl = DOMUtils.getElement("translated-word");
  const saveBtn = DOMUtils.getElement("save-btn");

  let selectedWord = "";
  let translatedWord = "";

  // Guardar palabra
  EventUtils.addOptionalListener("save-btn", "click", async () => {
    try {
      const response = await HTTPUtils.post("traduciones/save_word.php", {
        word: selectedWord,
        translation: translatedWord
      });

    } catch (error) {
      
    }
  });
});


// ¿Qué debes tener en tu HTML para que esto funcione?
// El HTML en index.php ya incluye:

// Un div con id="text" para las palabras.

// Un div con id="translation-box" donde se muestra la traducción.

// Elementos con id="selected-word" y id="translated-word".

// Un botón con id="save-btn".
