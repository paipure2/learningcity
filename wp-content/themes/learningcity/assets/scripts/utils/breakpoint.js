/**
 * Get current Tailwind breakpoint
 * @returns {string} Current breakpoint (xs, sm, md, lg, xl, 2xl)
 */
export function getBreakpoint() {
  const width = window.innerWidth;
  if (width < 640) return "xs";
  if (width < 768) return "sm";
  if (width < 1024) return "md";
  if (width < 1280) return "lg";
  if (width < 1536) return "xl";
  return "2xl";
}

/**
 * Watch for breakpoint changes and reload page when crossing breakpoints
 * Optionally refresh ScrollTrigger when staying in same breakpoint
 * @param {Object} options - Configuration options
 * @param {boolean} options.refreshScrollTrigger - Whether to refresh ScrollTrigger on resize (default: true)
 * @param {number} options.debounceDelay - Debounce delay in ms (default: 250)
 */
export function watchBreakpointChanges(options = {}) {
  const { refreshScrollTrigger = true, debounceDelay = 250 } = options;

  let currentBreakpoint = getBreakpoint();
  let resizeTimer;

  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      const newBreakpoint = getBreakpoint();

      if (currentBreakpoint !== newBreakpoint) {
        // Reload page when crossing breakpoint
        currentBreakpoint = newBreakpoint;
        location.reload();
        console.log(`Breakpoint changed to ${newBreakpoint}, reloading page.`);
      } else if (refreshScrollTrigger && window.ScrollTrigger) {
        // Just refresh ScrollTrigger for same breakpoint
        window.ScrollTrigger.refresh();
        console.log(
          `Breakpoint remains ${newBreakpoint}, refreshing ScrollTrigger.`
        );
      }
    }, debounceDelay);
  });
}
