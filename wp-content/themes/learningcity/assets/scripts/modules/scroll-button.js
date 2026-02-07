/**
 * Initialize scroll button fade in/out effect
 * On mobile: Fixed at bottom, shows/hides based on scroll progress
 * On desktop: Absolute positioned, fades in/out when scrolling
 */
export function initScrollButton() {
  const scrollButtons = document.querySelectorAll(".btn-scroll");

  scrollButtons.forEach((button) => {
    const leftContent = button.closest(".left-content");
    if (!leftContent) return;

    let lastScrollTop = 0;
    const isMobile = window.innerWidth < 1024;

    // Determine scroll container based on screen size
    // Mobile: Use modal-content, Desktop: Use left-content
    const getScrollContainer = () => {
      if (window.innerWidth < 1024) {
        return leftContent.closest(".modal-content");
      }
      return leftContent;
    };

    let scrollContainer = getScrollContainer();

    // Set button position based on screen size
    if (isMobile) {
      button.classList.add("fixed");
      button.classList.remove("absolute");
    } else {
      button.classList.add("absolute");
      button.classList.remove("fixed");
    }

    const handleScroll = function () {
      const scrollTop = this.scrollTop;
      const scrollHeight = this.scrollHeight;
      const clientHeight = this.clientHeight;
      const scrollProgress = scrollTop / (scrollHeight - clientHeight);

      if (window.innerWidth < 1024) {
        // Mobile: Fade out when scrolling down, fade in when scrolling up
        // Also hide when reaching near bottom (90% scrolled)
        if (
          scrollProgress > 0.9 ||
          (scrollTop > lastScrollTop && scrollTop > 25)
        ) {
          button.classList.add("fade-out");
          button.style.pointerEvents = "none";
        } else if (scrollTop < lastScrollTop || scrollTop <= 25) {
          button.classList.remove("fade-out");
          button.style.pointerEvents = "auto";
        }
      } else {
        // Desktop: Original behavior - fade out when scrolling down
        if (scrollTop > lastScrollTop && scrollTop > 25) {
          button.classList.add("fade-out");
          button.style.pointerEvents = "none";
        } else if (scrollTop < lastScrollTop) {
          button.classList.remove("fade-out");
          button.style.pointerEvents = "auto";
        }
      }

      lastScrollTop = scrollTop;
    };

    // Attach scroll listener to the appropriate container
    scrollContainer.addEventListener("scroll", handleScroll);

    // Smooth scroll when clicking the button
    button.addEventListener("click", function () {
      const currentScrollContainer = getScrollContainer();
      currentScrollContainer.scrollBy({
        top: currentScrollContainer.clientHeight * 0.8,
        behavior: "smooth",
      });
    });

    // Handle window resize
    window.addEventListener("resize", function () {
      const newIsMobile = window.innerWidth < 1024;

      // Update button position
      if (newIsMobile) {
        button.classList.add("fixed");
        button.classList.remove("absolute");
      } else {
        button.classList.add("absolute");
        button.classList.remove("fixed");
      }

      // Remove old listener and add new one to correct container
      scrollContainer.removeEventListener("scroll", handleScroll);
      scrollContainer = getScrollContainer();
      scrollContainer.addEventListener("scroll", handleScroll);
    });
  });
}
