// Modal Module
//==============================================================//

function skeletonFakeLoad(modal) {
  const skeleton = modal.querySelector(".skeleton");
  const content = modal.querySelector(".content-inner");
  if (!skeleton) return;

  skeleton.classList.add("is-loading");
  content.classList.remove("is-loaded");

  setTimeout(() => {
    skeleton.classList.remove("is-loading");
    content.classList.add("is-loaded");
  }, 800);
}

export function initModals() {
  const body = document.body;

  // ฟังก์ชันจัดการ scroll แบบ native
  const manageBodyScroll = function (lock = true) {
    if (lock) {
      // Lock scroll
      const scrollbarWidth =
        window.innerWidth - document.documentElement.clientWidth;
      body.style.overflow = "hidden";
      body.style.paddingRight = `${scrollbarWidth}px`;
    } else {
      // Unlock scroll
      body.style.overflow = "";
      body.style.paddingRight = "";
    }
  };

  // Function to initialize modal functionality
  const initializeModals = function () {
    const modals = document.querySelectorAll(".modal");
    const closeModalBtn = document.querySelectorAll(".close-modal");
    const overlayModals = document.querySelectorAll(".overlay-modal");

    // Function to close all modals
    const closeModalsFunction = () => {
      modals.forEach((modal) => {
        modal.classList.remove("modal-active");
      });
      body.removeAttribute("data-scroll");

      // Unlock scroll
      manageBodyScroll(false);
      removeHashFromUrl();
    };

    // Function to open a specific modal by content attribute
    const openModalByContent = (content) => {
      const modalWrap = document.querySelector(
        `[data-modal-content="${content}"]`
      );
      if (modalWrap) {
        modalWrap.classList.add("modal-active");
        if (document.querySelectorAll(".modal-active").length === 1) {
          body.setAttribute("data-scroll", "hidden");

          // Lock scroll
          manageBodyScroll(true);
        }
      }
    };

    // Check if there's a modal to open based on slug URL ID
    const openModalFromUrl = () => {
      const urlHash = window.location.hash;
      if (urlHash) {
        const content = urlHash.substring(1);
        openModalByContent(content);
      }
    };

    // Remove slug ID from URL
    const removeHashFromUrl = () => {
      history.pushState(
        "",
        document.title,
        window.location.pathname + window.location.search
      );
    };

    // Re-attach event listeners for modal buttons
    document.querySelectorAll("[data-modal-id]").forEach((button) => {
      // Course cards are handled by course-modal-ajax.js to avoid open/close race conditions.
      if (
        button.classList?.contains("card-course") &&
        button.hasAttribute("data-course-id") &&
        button.getAttribute("data-modal-id") === "modal-course"
      ) {
        return;
      }

      // Remove existing event listeners (if any)
      button.removeEventListener("click", button.modalClickHandler);

      // Create new click handler
      button.modalClickHandler = () => {
        const content = button.getAttribute("data-modal-id");
        const modalWrap = document.querySelector(
          `[data-modal-content="${content}"]`
        );

        if (!modalWrap) return;

        if (modalWrap.classList.contains("modal-active")) {
          closeModalsFunction();
        } else {
          window.history.replaceState(
            null,
            null,
            `${window.location.pathname}#${content}`
          );
          modalWrap.classList.add("modal-active");

          skeletonFakeLoad(modalWrap);

          if (document.querySelectorAll(".modal-active").length === 1) {
            body.setAttribute("data-scroll", "hidden");

            // Lock scroll
            manageBodyScroll(true);
          }
        }
      };

      // Add new event listener
      button.addEventListener("click", button.modalClickHandler);
    });

    // Event listeners for overlay clicks
    overlayModals.forEach((overlay) => {
      overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
          closeModalsFunction();
        }
      });
    });

    // Event listeners for close buttons
    closeModalBtn.forEach((button) => {
      button.addEventListener("click", closeModalsFunction);
    });

    // Escape key listener
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeModalsFunction();
      }
    });

    // Open modal based on slug URL ID
    openModalFromUrl();
  };

  // Initialize modals
  initializeModals();

  // Return public methods if needed
  return {
    reinitialize: initializeModals,
  };
}
//==============================================================//
