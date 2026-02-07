export function hideFloating() {
  const footer = document.querySelector("footer");
  const buttons = document.querySelectorAll(
    ".btn-category-floating, .btn-searh-floating, #searchbar-floating"
  );

  if (!footer || !buttons.length) return;

  const observer = new IntersectionObserver(
    ([entry]) => {
      buttons.forEach((btn) => {
        btn.classList.toggle("floating-hidden", entry.isIntersecting);
      });
    },
    {
      root: null,
      threshold: 0.1,
    }
  );

  observer.observe(footer);
}
