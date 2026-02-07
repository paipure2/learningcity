export function scrollbarX() {
  const elements = document.querySelectorAll(".scrollbar-x");
  if (!elements.length) return;

  elements.forEach((scrollContainer) => {
    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;

    const onMouseDown = (e) => {
      isDown = true;
      scrollContainer.classList.add("is-dragging");
      startX = e.pageX - scrollContainer.offsetLeft;
      scrollLeft = scrollContainer.scrollLeft;
    };

    const onMouseUp = () => {
      isDown = false;
      scrollContainer.classList.remove("is-dragging");
    };

    const onMouseMove = (e) => {
      if (!isDown) return;
      e.preventDefault();

      const x = e.pageX - scrollContainer.offsetLeft;
      const walk = x - startX;
      scrollContainer.scrollLeft = scrollLeft - walk;
    };

    scrollContainer.addEventListener("mousedown", onMouseDown);
    scrollContainer.addEventListener("mouseleave", onMouseUp);
    scrollContainer.addEventListener("mouseup", onMouseUp);
    scrollContainer.addEventListener("mousemove", onMouseMove);
  });
}
