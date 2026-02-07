function header() {
  const hamburgerMenu = document.querySelector("header .hamburger-menu");
  const closeMenu = document.querySelector("header .btn-close");
  const expandMenu = document.querySelector("header .expand-menu");
  const handleOpenMenu = () => {
    expandMenu.classList.add("is-active");
  };
  const handleCloseMenu = () => {
    expandMenu.classList.remove("is-active");
  };

  if (hamburgerMenu && closeMenu && expandMenu) {
    hamburgerMenu.addEventListener("click", handleOpenMenu);
    closeMenu.addEventListener("click", handleCloseMenu);
  }
}

function footer() {}

export function initInterface() {
  header();
  footer();
}
