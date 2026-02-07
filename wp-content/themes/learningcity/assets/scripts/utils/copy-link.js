export function initCopyLink() {
  const buttons = document.querySelectorAll("[data-copy-url]");
  if (!buttons.length) return;

  buttons.forEach((btn) => {
    const textEl = btn.querySelector(".btn-text");
    const defaultText = textEl.textContent;

    btn.addEventListener("click", async () => {
      try {
        const path = btn.dataset.copyUrl;

        // แปลงเป็น absolute URL
        const url = new URL(path, window.location.origin).href;

        await navigator.clipboard.writeText(url);

        textEl.textContent = "คัดลอกแล้ว!";
        btn.classList.add("is-copied");

        setTimeout(() => {
          textEl.textContent = defaultText;
          btn.classList.remove("is-copied");
        }, 2000);
      } catch (err) {
        console.error("Copy failed", err);
      }
    });
  });
}
