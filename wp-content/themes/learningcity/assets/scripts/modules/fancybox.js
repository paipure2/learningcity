// modules/fancybox.js  (เวอร์ชันสำหรับ CDN UMD)

function getFancybox() {
  return window.Fancybox; // ✅ จาก fancybox.umd.js
}

function fancyBox(selector, thumbs) {
  const Fancybox = getFancybox();
  if (!Fancybox) {
    console.warn("Fancybox not loaded (window.Fancybox is undefined)");
    return;
  }

  Fancybox.bind(selector, {
    Thumbs: thumbs ? false : false, // คุณตั้งใจปิด thumbs อยู่แล้ว
    Toolbar: {
      display: {
        left: [],
        middle: [],
        right: ["close"],
      },
    },
  });
}

function resizeFancyboxCaption(slide) {
  const captionEl = slide.captionEl;
  const wrapperEl = slide.panzoomRef?.getWrapper();
  if (!captionEl || !wrapperEl) return;

  captionEl.style.visibility = "hidden";
  captionEl.style.width = "";
  void captionEl.offsetWidth;

  captionEl.style.width = `${Math.max(200, wrapperEl.getBoundingClientRect().width)}px`;
  captionEl.style.visibility = "";
}

export function initFancyBox() {
  const Fancybox = getFancybox();
  if (!Fancybox) {
    console.warn("Fancybox not loaded (window.Fancybox is undefined)");
    return;
  }

  // bind global events (ทำครั้งเดียวพอ)
  if (!window.__fancyboxBound) {
    window.__fancyboxBound = true;

    Fancybox.bind("[data-fancybox]", {
      on: {
        "Carousel.lazyLoad:loaded": (f, c, slide) => resizeFancyboxCaption(slide),
        "Carousel.attachSlideEl": (f, c, slide) => resizeFancyboxCaption(slide),
        "Carousel.panzoom:refresh": (f, c, slide) => resizeFancyboxCaption(slide),
      },
    });
  }

  // bind เฉพาะ gallery
  fancyBox('[data-fancybox="gallery-vibe"]', true);
  fancyBox('[data-fancybox="gallery-testimonial"]', false);
}
