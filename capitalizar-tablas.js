document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll("table tbody td").forEach(function (cell) {
    const textNodes = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
    let textNode;

    while ((textNode = textNodes.nextNode())) {
      const characters = Array.from(textNode.textContent);
      const firstLetter = characters.findIndex(
        (character) =>
          character.toLocaleUpperCase("es") !==
          character.toLocaleLowerCase("es")
      );

      if (firstLetter !== -1) {
        characters[firstLetter] =
          characters[firstLetter].toLocaleUpperCase("es");
        textNode.textContent = characters.join("");
        break;
      }
    }
  });
});
