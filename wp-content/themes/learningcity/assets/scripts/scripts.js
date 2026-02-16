// ✅ Run GSAP config once (register plugins / setup)
import "./config/gsap-config.js";

// Import Modules
import { initAOS } from "./modules/aos.js";
import { initFancyBox } from "./modules/fancybox.js";
import { initSwipers } from "./modules/swiper.js";
import { initInterface } from "./modules/interface.js";
import { initAccordion } from "./modules/accordion.js";
import { initModals } from "./modules/modal.js";
import { initScrollButton } from "./modules/scroll-button.js";
import { initSvgInjections } from "./utils/svg-icons.js";
import { scrollbarX } from "./utils/scrollbar.js";
import { initCopyLink } from "./utils/copy-link.js";
import { hideFloating } from "./utils/floating.js";
import { expandCard } from "./modules/expandCard.js";
import { chart, startClock, renderBarsWithGsap } from "./modules/chart.js";
import { initAnimations, initStickySection } from "./modules/animations.js";

// ✅ helper กันพัง
const safe = (name, fn) => {
  try {
    fn?.();
  } catch (e) {
    console.error(`[${name}] failed`, e);
  }
};

document.addEventListener("DOMContentLoaded", () => {
  // expose for non-module scripts that update DOM via AJAX
  window.initSvgInjections = initSvgInjections;
  window.initSwipers = initSwipers;

  const yearElement = document.getElementById("current-year");
  if (yearElement) yearElement.textContent = new Date().getFullYear();

  window.addEventListener("load", () => safe("initFancyBox", initFancyBox));
  safe("initSvgInjections", initSvgInjections);
  safe("initModals", initModals);
  safe("initScrollButton", initScrollButton);
  safe("initSwipers", initSwipers);
  safe("initInterface", initInterface);
  safe("initAccordion", initAccordion);
  safe("scrollbarX", scrollbarX);
  safe("initCopyLink", initCopyLink);
  safe("initAOS", initAOS);
  safe("hideFloating", hideFloating);
  safe("expandCard", expandCard);

  // ✅ Sticky stack section
  if (document.querySelector(".stack_wrapper") && document.querySelector(".stack_section")) {
    safe("initStickySection", initStickySection);
  }

  // ✅ Chart (ECharts)
  if (document.getElementById("chart")) {
    safe("chart", chart);
  }

  // ✅ Clock
  if (document.querySelector(".hour-hand") || document.querySelector(".minute-hand") || document.querySelector(".second-hand")) {
    safe("startClock", startClock);
  }

  // ✅ Progress bars + counters (ดึงค่าจาก WP แล้วค่อยเล่น GSAP)
  if (document.querySelector(".progress") || document.querySelector(".counter-hours")) {
    safe("renderBarsWithGsap", renderBarsWithGsap);
  }

  // ✅ Animations (ถ้ามี data-animate ค่อยรัน)
  if (document.querySelector('[data-animate]')) {
    safe("initAnimations", initAnimations);
  }
});
