function recreateSwiper(element, options) {
  if (!element) return null;
  if (element.swiper) {
    element.swiper.destroy(true, true);
  }
  return new Swiper(element, options);
}

function swiperHighlight() {
  const element = document.querySelector(".sec-highlight .swiper");
  if (!element) return;

  recreateSwiper(element, {
    slidesPerView: 1.3,
    spaceBetween: 20,
    speed: 500,
    breakpoints: {
      640: {
        slidesPerView: 2.15,
      },
      1024: {
        slidesPerView: "auto",
      },
    },
    pagination: {
      el: ".sec-highlight .swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".sec-highlight .swiper-button-next",
      prevEl: ".sec-highlight .swiper-button-prev",
    },
  });
}

function swiperCategory() {
  const element = document.querySelector(".sec-category .swiper");
  if (!element) return;

  recreateSwiper(element, {
    observer: true,
    observeParents: true,
    slidesPerView: 3.5,
    spaceBetween: 12,
    speed: 500,
    grid: {
      rows: 2,
    },
    breakpoints: {
      768: {
        slidesPerView: 4.15,
        spaceBetween: 20,
        grid: {
          rows: 2,
        },
      },
      1024: {
        slidesPerView: 6,
        spaceBetween: 20,
        grid: {
          rows: 2,
        },
      },
    },
    pagination: {
      el: ".sec-category .swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".sec-category .swiper-button-next",
      prevEl: ".sec-category .swiper-button-prev",
    },
  });
}

function swiperCategoryHighlight() {
  const elements = document.querySelectorAll(".sec-category-highlight .swiper");
  if (!elements.length) return;

  elements.forEach((element) => {
    recreateSwiper(element, {
      observer: true,
      observeParents: true,
      slidesPerView: 3.5,
      spaceBetween: 5,
      speed: 500,
      breakpoints: {
        768: {
          slidesPerView: 4.15,
          spaceBetween: 5,
        },
        1024: {
          slidesPerView: 6,
          spaceBetween: 5,
        },
      },
    });
  });
}

function swiperCategoryOther() {
  const swipers = document.querySelectorAll(".sec-category-other .swiper");
  if (!swipers.length) return;

  swipers.forEach((element) => {
    const section = element.closest(".sec-category-other");
    const paginationEl = section.querySelector(".swiper-pagination");
    const nextEl = section.querySelector(".swiper-button-next");
    const prevEl = section.querySelector(".swiper-button-prev");

    recreateSwiper(element, {
      observer: true,
      observeParents: true,
      slidesPerView: 2.45,
      spaceBetween: 20,
      speed: 500,

      breakpoints: {
        640: { slidesPerView: 2.65, spaceBetween: 20 },
        1024: { slidesPerView: 5, spaceBetween: 20 },
        1280: { slidesPerView: 5, spaceBetween: 20 },
      },

      pagination: {
        el: paginationEl,
        clickable: true,
      },
      navigation: {
        nextEl,
        prevEl,
      },
    });
  });
}


function swiperPartner() {
  const element = document.querySelector(".sec-partner .swiper");
  if (!element) return;

  recreateSwiper(element, {
    observer: true,
    observeParents: true,
    slidesPerView: 2.45,
    spaceBetween: 20,
    speed: 500,
    breakpoints: {
      640: {
        slidesPerView: 2.65,
        spaceBetween: 20,
      },
      1024: {
        slidesPerView: 4.15,
        spaceBetween: 20,
      },
      1280: {
        slidesPerView: 4,
        spaceBetween: 30,
      },
    },
    pagination: {
      el: ".sec-partner .swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".sec-partner .swiper-button-next",
      prevEl: ".sec-partner .swiper-button-prev",
    },
  });
}

function swiperCourse() {
  const element = document.querySelector(".sec-course .swiper");
  if (!element) return;

  recreateSwiper(element, {
    observer: true,
    observeParents: true,
    slidesPerView: 1.05,
    spaceBetween: 12,
    speed: 500,
    grid: {
      rows: 3,
    },
    breakpoints: {
      640: {
        slidesPerView: 1.25,
        spaceBetween: 20,
        grid: {
          rows: 3,
        },
      },
      1024: {
        slidesPerView: 2,
        spaceBetween: 20,
        grid: {
          rows: 3,
        },
      },
      1280: {
        slidesPerView: 2,
        spaceBetween: 30,
        grid: {
          rows: 3,
        },
      },
    },
    pagination: {
      el: ".sec-course .swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".sec-course .swiper-button-next",
      prevEl: ".sec-course .swiper-button-prev",
    },
  });
}

function swiperGalleryVibe() {
  const element = document.querySelector(".swiper-gallery-vibe");
  if (!element) return;

  recreateSwiper(element, {
    observer: true,
    observeParents: true,
    slidesPerView: 2.5,
    spaceBetween: 8,
    speed: 500,
    breakpoints: {
      640: {
        slidesPerView: 3.15,
        spaceBetween: 8,
      },
    },
    pagination: {
      el: ".gallery-vibe-control .swiper-pagination",
      clickable: true,
    },
  });
}

function swiperLogoLoop() {
  const element = document.querySelector(".swiper-logo-loop");
  if (!element) return;
  const wrapper = element.querySelector(".swiper-wrapper");
  if (!wrapper) return;
  const slides = Array.from(wrapper.children);
  const times = 2;

  if (slides.length < 15 && element.dataset.loopCloned !== "1") {
    for (let i = 0; i < times; i++) {
      slides.forEach((slide) => {
        wrapper.appendChild(slide.cloneNode(true));
      });
    }
    element.dataset.loopCloned = "1";
  }

  requestAnimationFrame(() => {
    recreateSwiper(element, {
      observer: true,
      observeParents: true,
      slidesPerView: "auto",
      spaceBetween: 6,
      loop: true,
      speed: 2000,
      simulateTouch: false,
      autoplay: {
        delay: 0,
      },
      breakpoints: {
        1024: {
          slidesPerView: "auto",
          spaceBetween: 12,
        },
      },
    });
  });
}

function swiperCategoryIndex() {
  const element = document.querySelector(".swiper-category-index");
  if (!element) return;

  recreateSwiper(element, {
    observer: true,
    observeParents: true,
    slidesPerView: "auto",
    spaceBetween: 12,
    speed: 500,
    grid: {
      rows: 2,
    },
    breakpoints: {
      768: {
        slidesPerView: "auto",
        spaceBetween: 20,
        grid: {
          rows: 2,
        },
      },
    },
    pagination: {
      el: ".swiper-category-index .swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".swiper-category-index .swiper-button-next",
      prevEl: ".swiper-category-index .swiper-button-prev",
    },
  });
}

function swiperTestimonial(selector, duration, reverse) {
  const el = document.querySelector(selector);
  if (!el) return;

  const wrapper = el.querySelector(".swiper-wrapper");
  if (!wrapper) return;
  const slides = Array.from(wrapper.children);
  const times = 2;
  const speed = duration * 1000;

  if (slides.length < 10 && el.dataset.loopCloned !== "1") {
    for (let i = 0; i < times; i++) {
      slides.forEach((slide) => {
        wrapper.appendChild(slide.cloneNode(true));
      });
    }
    el.dataset.loopCloned = "1";
  }

  requestAnimationFrame(() => {
    recreateSwiper(el, {
      observer: true,
      observeParents: true,
      slidesPerView: "auto",
      spaceBetween: 12,
      loop: true,
      speed: speed,
      simulateTouch: false,
      autoplay: {
        delay: 0,
        reverseDirection: reverse,
      },
      breakpoints: {
        1024: {
          spaceBetween: 24,
        },
      },
    });
  });
}

function swiperActivity(element) {
  const el = document.querySelector(element);
  if (!el) return;

  recreateSwiper(el, {
    observer: true,
    observeParents: true,
    slidesPerView: 2.05,
    spaceBetween: 12,
    speed: 500,
    breakpoints: {
      768: {
        slidesPerView: 4,
        spaceBetween: 8,
      },
      1024: {
        slidesPerView: 4,
        spaceBetween: 16,
      },
    },
  });
}

export function initSwipers() {
  const tasks = [
    () => swiperHighlight(),
    () => swiperCategory(),
    () => swiperCategoryOther(),
    () => swiperCategoryHighlight(),
    () => swiperPartner(),
    () => swiperCourse(),
    () => swiperGalleryVibe(),
    () => swiperLogoLoop(),
    () => swiperCategoryIndex(),
    () => swiperTestimonial(".swiper-testimonial.top", 10, true),
    () => swiperTestimonial(".swiper-testimonial.bottom", 12, false),
    () => swiperActivity(".swiper-activity-head"),
    () => swiperActivity(".swiper-activity-body"),
  ];

  tasks.forEach((run) => {
    try {
      run();
    } catch (err) {
      console.error("[swiper:init]", err);
    }
  });
}
