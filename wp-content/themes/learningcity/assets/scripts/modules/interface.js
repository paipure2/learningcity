function header() {
  const hamburgerMenu = document.querySelector("header .hamburger-menu");
  const closeMenu = document.querySelector("header .btn-close");
  const expandMenu = document.querySelector("header .expand-menu");
  const panels = Array.from(document.querySelectorAll("header [data-menu-panel]"));
  const menuTargetButtons = Array.from(document.querySelectorAll("header [data-menu-target]"));
  const menuBackButtons = Array.from(document.querySelectorAll("header [data-menu-back]"));

  const setActivePanel = (panelName = "root") => {
    if (!expandMenu || panels.length === 0) return;

    expandMenu.setAttribute("data-menu-current", panelName);

    panels.forEach((panel) => {
      const isActive = panel.getAttribute("data-menu-panel") === panelName;
      panel.classList.toggle("is-active", isActive);
    });
  };

  const handleOpenMenu = () => {
    expandMenu.classList.add("is-active");
    setActivePanel("root");
  };
  const handleCloseMenu = () => {
    expandMenu.classList.remove("is-active");
    setActivePanel("root");
  };

  if (hamburgerMenu && closeMenu && expandMenu) {
    hamburgerMenu.addEventListener("click", handleOpenMenu);
    closeMenu.addEventListener("click", handleCloseMenu);
  }

  if (menuTargetButtons.length > 0) {
    menuTargetButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const target = button.getAttribute("data-menu-target");
        if (!target) return;
        setActivePanel(target);
      });
    });
  }

  if (menuBackButtons.length > 0) {
    menuBackButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const target = button.getAttribute("data-menu-back") || "root";
        setActivePanel(target);
      });
    });
  }
}

function footer() {}

function asideExpand() {
  const buttons = Array.from(document.querySelectorAll("[data-aside-expand-button]"));

  if (buttons.length === 0) return;

  buttons.forEach((button) => {
    let isAnimating = false;

    button.addEventListener("click", () => {
      if (isAnimating) return;

      const key = button.getAttribute("data-aside-expand-button");
      if (!key) return;

      const content = document.querySelector(`[data-aside-expand-content="${key}"]`);
      if (!content) return;

      const isExpanded = !content.classList.contains("hidden");
      const nextExpanded = !isExpanded;
      const openLabel = button.getAttribute("data-label-open") || "ดูทั้งหมด";
      const closeLabel = button.getAttribute("data-label-close") || "ซ่อน";

      isAnimating = true;
      button.setAttribute("aria-expanded", String(nextExpanded));
      button.textContent = nextExpanded ? closeLabel : openLabel;

      if (nextExpanded) {
        content.classList.remove("hidden");
        content.classList.add("flex");
        content.style.height = "0px";
        content.style.opacity = "0";

        requestAnimationFrame(() => {
          content.style.height = `${content.scrollHeight}px`;
          content.style.opacity = "1";
        });

        const onExpandEnd = (event) => {
          if (event.propertyName !== "height") return;
          content.style.height = "auto";
          content.removeEventListener("transitionend", onExpandEnd);
          isAnimating = false;
        };

        content.addEventListener("transitionend", onExpandEnd);
        return;
      }

      content.style.height = `${content.scrollHeight}px`;
      content.style.opacity = "1";

      requestAnimationFrame(() => {
        content.style.height = "0px";
        content.style.opacity = "0";
      });

      const onCollapseEnd = (event) => {
        if (event.propertyName !== "height") return;
        content.classList.add("hidden");
        content.classList.remove("flex");
        content.style.height = "";
        content.style.opacity = "";
        content.removeEventListener("transitionend", onCollapseEnd);
        isAnimating = false;
      };

      content.addEventListener("transitionend", onCollapseEnd);
    });
  });
}

function desktopMegaMenuScrollHint() {
  const scrollColumns = Array.from(
    document.querySelectorAll("header .desktop-mega-menu__column--scroll, header .desktop-mega-menu__column--provider"),
  );

  if (scrollColumns.length === 0) return;

  scrollColumns.forEach((column) => {
    const list =
      column.querySelector(".desktop-mega-menu__list--scroll") ||
      column.querySelector(".desktop-mega-menu__list");
    const indicator = column.querySelector(".desktop-mega-menu__scroll-indicator");

    if (!(list instanceof HTMLElement) || !(indicator instanceof HTMLElement)) return;

    const syncIndicator = () => {
      const hasOverflow = list.scrollHeight > list.clientHeight + 4;
      const hasScrolled = list.scrollTop > 8;
      indicator.classList.toggle("is-hidden", !hasOverflow || hasScrolled);
    };

    list.addEventListener("scroll", syncIndicator, { passive: true });
    window.addEventListener("resize", syncIndicator);
    syncIndicator();
  });
}

export function initInterface() {
  header();
  asideExpand();
  desktopMegaMenuScrollHint();
  footer();
}
