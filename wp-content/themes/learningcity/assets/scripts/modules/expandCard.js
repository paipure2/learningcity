export function expandCard() {
  const elements = document.querySelectorAll(".expand-item");

  if (!elements.length) return;

  elements.forEach((item) => {
    const desc = item.querySelector(".expand-desc");

    item.addEventListener("mouseenter", () => {
      elements.forEach((el) => (el.style.flexGrow = "1"));
      item.style.flexGrow = "3";

      if (desc) desc.classList.add("opacity-100");
    });

    item.addEventListener("mouseleave", () => {
      elements.forEach((el) => (el.style.flexGrow = "1"));

      if (desc) desc.classList.remove("opacity-100");
    });
  });
}
