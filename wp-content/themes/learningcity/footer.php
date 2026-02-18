<?php wp_footer(); ?>
<?php $mourning_image = get_template_directory_uri() . '/assets/images/mourning-popup.jpg'; ?>
<style>
  #mourningModal {
    position: fixed;
    inset: 0;
    z-index: 99999999;
    display: none;
  }
  #mourningModal.is-open {
    display: block;
  }
  #mourningModal .mourning-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.76);
    backdrop-filter: blur(2px);
    animation: fadeInMourning 0.35s ease both;
  }
  #mourningModal .mourning-wrap {
    position: relative;
    min-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  #mourningModal .mourning-panel {
    width: min(92vw, 560px);
    background: #0f0f10;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.45);
    animation: panelInMourning 0.42s cubic-bezier(.2,.8,.2,1) both;
  }
  #mourningModal .mourning-image {
    position: relative;
    background: #000;
  }
  #mourningModal .mourning-image img {
    display: block;
    width: 100%;
    height: auto;
    animation: imageBreath 5s ease-in-out infinite;
  }
  #mourningModal .mourning-content {
    padding: 14px 16px 16px;
    color: #f4f4f4;
    text-align: center;
  }
  #mourningModal .mourning-title {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.35;
  }
  #mourningModal .mourning-subtitle {
    margin: 8px 0 0;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.82);
    line-height: 1.6;
  }
  #mourningModal .mourning-actions {
    margin-top: 14px;
    display: flex;
    justify-content: center;
  }
  #mourningModal .mourning-btn {
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    border-radius: 999px;
    padding: 8px 18px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.15s ease;
  }
  #mourningModal .mourning-btn:hover {
    background: rgba(255, 255, 255, 0.16);
  }
  #mourningModal .mourning-btn:active {
    transform: translateY(1px);
  }
  @keyframes fadeInMourning {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  @keyframes panelInMourning {
    from { opacity: 0; transform: translateY(14px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }
  @keyframes imageBreath {
    0% { transform: scale(1); }
    50% { transform: scale(1.015); }
    100% { transform: scale(1); }
  }
</style>

<div id="mourningModal" role="dialog" aria-modal="true" aria-label="ประกาศไว้อาลัย">
  <div class="mourning-overlay"></div>
  <div class="mourning-wrap">
    <div class="mourning-panel">
      <div class="mourning-image">
        <img src="<?php echo esc_url($mourning_image); ?>" alt="ประกาศไว้อาลัย">
      </div>
      <div class="mourning-content">
        <div class="mourning-actions">
          <button type="button" id="mourningAcknowledge" class="mourning-btn">เข้าสู่เว็บไซต์</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var modal = document.getElementById("mourningModal");
    var acknowledgeBtn = document.getElementById("mourningAcknowledge");
    var STORAGE_KEY = "mourning_modal_expire_at_v1";
    var ONE_DAY_MS = 24 * 60 * 60 * 1000;

    if (!modal || !acknowledgeBtn) return;

    function now() {
      return Date.now();
    }

    function shouldShow() {
      var expireAt = parseInt(localStorage.getItem(STORAGE_KEY) || "0", 10);
      return !expireAt || now() >= expireAt;
    }

    function openModal() {
      modal.classList.add("is-open");
      document.body.style.overflow = "hidden";
    }

    function closeModalAndCache() {
      localStorage.setItem(STORAGE_KEY, String(now() + ONE_DAY_MS));
      modal.classList.remove("is-open");
      document.body.style.overflow = "";
    }

    if (shouldShow()) {
      window.addEventListener("load", function () {
        setTimeout(openModal, 180);
      });
    }

    acknowledgeBtn.addEventListener("click", closeModalAndCache);
    modal.addEventListener("click", function (e) {
      if (e.target === modal || e.target.classList.contains("mourning-overlay")) {
        closeModalAndCache();
      }
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("is-open")) {
        closeModalAndCache();
      }
    });
  })();
</script>



</body>



</html>
