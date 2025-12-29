// Carga de textos del usuario
// Las funciones comunes están en common-functions.js

EventUtils.onDOMReady(async function () {
  const userTextsList = DOMUtils.getElement("user-texts-list");
  if (!userTextsList) return;

  try {
    const texts = await HTTPUtils.get("ajax/load_user_texts.php");
    
    if (texts.length === 0) {
      DOMUtils.updateHTML("user-texts-list", "<p>No tienes textos guardados aún.</p>");
      return;
    }

    const ul = document.createElement("ul");
    texts.forEach((text) => {
      const li = document.createElement("li");
      const a = document.createElement("a");
      a.href = `index.php?text_id=${text.id}`;
      a.textContent = text.title;
      li.appendChild(a);
      ul.appendChild(li);
    });
    
    DOMUtils.updateHTML("user-texts-list", "");
    userTextsList.appendChild(ul);
  } catch (err) {
    DOMUtils.updateHTML("user-texts-list", "<p>Error cargando tus textos.</p>");
  }
});
