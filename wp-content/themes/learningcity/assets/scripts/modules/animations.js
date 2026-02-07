// Import GSAP from global config (plugins already registered)
import { gsap, ScrollTrigger } from "../config/gsap-config.js";

export function initAnimations() {
  // Set default ease
  gsap.defaults({
    ease: "power2.out",
    duration: 0.8,
  });

  gsap.from(".progress", {
    duration: 0.8,
    width: 0,
    ease: "back.out",
    stagger: 0.2,
  });

  gsap.from(".counter-hours", {
    duration: 1,
    textContent: 0,
    snap: { textContent: 1 },
    ease: "power4.out",
    modifiers: {
      textContent: (value) =>
        Number(value).toLocaleString(undefined, {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }),
    },
  });

  // Initialize different animation types
  initFadeInAnimations();
  initSlideInAnimations();
  initScaleAnimations();
  initTextAnimations();
  initParallaxAnimations();
  initParallaxSection();

  // console.log("GSAP animations initialized");
}

/**
 * Fade in animations
 */
export function initFadeInAnimations() {
  const fadeElements = document.querySelectorAll('[data-animate="fade-in"]');

  fadeElements.forEach((element) => {
    gsap.fromTo(
      element,
      {
        opacity: 0,
        y: 30,
      },
      {
        opacity: 1,
        y: 0,
        duration: 0.8,
        stagger: 0.2,
        scrollTrigger: {
          trigger: element,
          start: "top 85%",
          end: "bottom 15%",
          //   toggleActions: "play none none reverse",
        },
      }
    );
  });
}

/**
 * Slide in animations
 */
export function initSlideInAnimations() {
  // Slide from left
  const slideLeftElements = document.querySelectorAll(
    '[data-animate="slide-left"]'
  );
  slideLeftElements.forEach((element) => {
    gsap.fromTo(
      element,
      {
        opacity: 0,
        x: -100,
      },
      {
        opacity: 1,
        x: 0,
        duration: 1,
        stagger: 0.25,
        scrollTrigger: {
          trigger: element,
          start: "top 85%",
          //   toggleActions: "play none none reverse",
        },
      }
    );
  });

  // Slide from right
  const slideRightElements = document.querySelectorAll(
    '[data-animate="slide-right"]'
  );
  slideRightElements.forEach((element) => {
    gsap.fromTo(
      element,
      {
        opacity: 0,
        x: 100,
      },
      {
        opacity: 1,
        x: 0,
        duration: 1,
        scrollTrigger: {
          trigger: element,
          start: "top 85%",
          toggleActions: "play none none reverse",
        },
      }
    );
  });
}

/**
 * Scale animations
 */
export function initScaleAnimations() {
  const scaleElements = document.querySelectorAll('[data-animate="scale-in"]');

  scaleElements.forEach((element) => {
    gsap.fromTo(
      element,
      {
        opacity: 0,
        scale: 0.8,
      },
      {
        opacity: 1,
        scale: 1,
        duration: 1,
        scrollTrigger: {
          trigger: element,
          start: "top 85%",
          toggleActions: "play none none reverse",
        },
      }
    );
  });
}

/**
 * Text animations (split text)
 */
export function initTextAnimations() {
  const textElements = document.querySelectorAll(
    '[data-animate="text-reveal"]'
  );

  textElements.forEach((element) => {
    // Split text into spans
    const text = element.textContent;
    element.innerHTML = text
      .split("")
      .map((char) => (char === " " ? " " : `<span>${char}</span>`))
      .join("");

    const spans = element.querySelectorAll("span");

    gsap.fromTo(
      spans,
      {
        opacity: 0,
        y: 50,
      },
      {
        opacity: 1,
        y: 0,
        duration: 0.05,
        stagger: 0.02,
        scrollTrigger: {
          trigger: element,
          start: "top 85%",
          toggleActions: "play none none reverse",
        },
      }
    );
  });
}

/**
 * Parallax animations
 */
export function initParallaxSection() {
  gsap.utils.toArray(".section-parallax section").forEach((section, i) => {
    ScrollTrigger.create({
      trigger: section,
      start: "top top",
      scrub: 1,
      pin: true,
      pinSpacing: false,
    });
  });
}
/**
 * Parallax animations
 */
export function initParallaxAnimations() {}

/**
 * Animate elements on hover
 */
export function initHoverAnimations() {
  const hoverElements = document.querySelectorAll('[data-hover="scale"]');

  hoverElements.forEach((element) => {
    element.addEventListener("mouseenter", () => {
      gsap.to(element, {
        scale: 1.05,
        duration: 0.3,
      });
    });

    element.addEventListener("mouseleave", () => {
      gsap.to(element, {
        scale: 1,
        duration: 0.3,
      });
    });
  });
}

/**
 * Page transition animations
 */
export function initPageTransitions() {
  // Animate page load
  gsap.fromTo(
    ".main",
    {
      opacity: 0,
    },
    {
      opacity: 1,
      duration: 0.5,
      delay: 0.2,
    }
  );

  // Animate internal links
  const internalLinks = document.querySelectorAll(
    'a[href^="index.html"], a[href^="./"], a[href^="../"]'
  );

  internalLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      if (e.ctrlKey || e.metaKey || e.shiftKey || link.target === "_blank")
        return;

      e.preventDefault();
      const href = link.href;

      gsap.to(".main", {
        opacity: 0,
        duration: 0.3,
        onComplete: () => {
          window.location.href = href;
        },
      });
    });
  });
}

export function initStickySection() {
  const sections = gsap.utils.toArray(".stack_section");
  const time = 2;
  const totalCards = 5;
  const firstCardVH = 0.4; // 40vh
  const offset = window.innerHeight * ((1 - firstCardVH) / (totalCards - 1));

  // 👉 set initial offset (ทุกตัว รวมตัวแรก)
  gsap.set(sections, {
    y: (index) => offset * index,
    transformOrigin: "center top",
  });

  // 👉 เลือกเฉพาะการ์ดตัวที่ 1
  const animatedSectionsFirst = sections[0];
  // 👉 เลือกเฉพาะการ์ดตัวที่ 2 เป็นต้นไป
  const animatedSections = sections.slice(1);
  const descFirst = animatedSectionsFirst.querySelector(".desc");

  gsap.to(descFirst, {
    duration: 1,
    autoAlpha: 0,
    ease: "power2.out",
    scrollTrigger: {
      trigger: animatedSectionsFirst,
      start: "top top",
      end: "30% top",
      scrub: 0.1,
      // markers: true,
    },
  });

  const ani_desc = gsap.timeline({
    scrollTrigger: {
      trigger: ".stack_wrapper",
      start: "top top",
      end: () => `+=${document.querySelector(".stack_wrapper").offsetHeight}`,
      scrub: 0.1,
      pin: true,
      // markers: true,
    },
  });

  ani_desc.from(animatedSections, {
    duration: time,
    y: (index) => window.innerHeight / 4 + offset * (index + 1),
    stagger: time,
    transformOrigin: "center top",
  });

  animatedSections.forEach((section, index) => {
    const desc = section.querySelector(".desc");
    if (!desc) return;

    const position = time * index;
    const isLastCard = index === animatedSections.length - 1;

    ani_desc.from(
      desc,
      {
        duration: 1,
        autoAlpha: 0,
        ease: "power2.out",
      },
      position + 0.15
    );
    if (!isLastCard) {
      ani_desc.to(
        desc,
        {
          duration: 1,
          autoAlpha: 0,
          ease: "power2.out",
        },
        position + 2
      );
    }
  });
}

/**
 * Initialize all animations
 */
export function initAllAnimations() {
  initAnimations();
  initHoverAnimations();
  initPageTransitions();
  initStickySection();
}
