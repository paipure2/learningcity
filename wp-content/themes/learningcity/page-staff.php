<?php
/* Template Name: Staff Portal */
get_header();

$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$rest_nonce = wp_create_nonce('wp_rest');
?>

<style>
  .staff-shell{
    --bg: #f7f7f2;
    --card: #ffffff;
    --ink: #0f172a;
    --muted: #64748b;
    --accent: #0f766e;
    --accent-2: #0b5f58;
    background: var(--bg);
    color: var(--ink);
  }
  .staff-card{
    background: var(--card);
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
  }
  .staff-btn{
    background: var(--accent);
    color: #fff;
    border-radius: 14px;
    padding: 10px 16px;
    font-weight: 700;
  }
  .staff-btn:hover{ background: var(--accent-2); }
  .staff-btn-ghost{
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 10px 16px;
    font-weight: 600;
  }
  .staff-pill{
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    font-size: 12px;
    color: var(--muted);
  }
  .staff-input, .staff-textarea{
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 12px;
    width: 100%;
    font-size: 14px;
    background: #fff;
  }
  .staff-textarea{ min-height: 120px; }
  .staff-list-item{
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #fff;
  }
  .staff-list-item.active{
    border-color: var(--accent);
    box-shadow: 0 6px 16px rgba(15, 118, 110, 0.12);
  }
  .staff-tabs{
    display: flex;
    gap: 8px;
    background: #eef2f7;
    padding: 6px;
    border-radius: 999px;
  }
  .staff-tab{
    flex: 1;
    text-align: center;
    padding: 8px 10px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 14px;
    color: #475569;
  }
  .staff-tab.active{
    background: var(--accent);
    color: #fff;
  }
  .staff-session-card{
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px;
    background: #fff;
  }
  .staff-session-card h4{
    font-weight: 700;
    font-size: 14px;
  }
  .staff-muted{ color: var(--muted); font-size: 12px; }
  .staff-badge{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: #ecfeff;
    color: #0e7490;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
  }
  .staff-grid{
    display: grid;
    gap: 18px;
    grid-template-columns: 320px 1fr;
  }
  @media (max-width: 1024px){
    .staff-grid{ grid-template-columns: 1fr; }
  }
</style>

<div class="staff-shell py-10">
  <div class="max-w-6xl mx-auto px-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <div class="text-sm text-slate-500">Staff Portal</div>
        <h1 class="text-2xl font-extrabold">แดชบอร์ดจัดการข้อมูล</h1>
      </div>
      <?php if ($is_logged_in): ?>
        <div class="flex items-center gap-3">
          <span class="staff-pill">สวัสดี <?php echo esc_html($current_user->display_name); ?></span>
          <a class="staff-btn-ghost" href="<?php echo esc_url(wp_logout_url('/staff/')); ?>">ออกจากระบบ</a>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!$is_logged_in): ?>
      <div class="staff-card p-6 max-w-xl">
        <div class="text-sm text-slate-600">กรอกอีเมลเพื่อรับรหัส OTP (หมดอายุใน 10 นาที)</div>
        <div class="flex gap-3 mt-4">
          <input id="staffEmail" class="staff-input" placeholder="you@org.com" />
          <button id="staffSendOtp" class="staff-btn">ส่ง OTP</button>
        </div>
        <div id="staffOtpWrap" class="mt-4 hidden">
          <div class="text-sm text-slate-600 mb-2">กรอกรหัส OTP ที่ได้รับ</div>
          <div class="flex gap-3">
            <input id="staffOtp" class="staff-input" placeholder="รหัส 6 หลัก" />
            <button id="staffVerifyOtp" class="staff-btn">ยืนยัน</button>
          </div>
          <div class="flex items-center gap-3 mt-2">
            <button id="staffResendOtp" class="staff-btn-ghost">ส่ง OTP ใหม่</button>
            <span id="staffOtpMsg" class="text-sm text-slate-600"></span>
          </div>
        </div>
        <div id="staffLoginMsg" class="text-sm text-slate-600 mt-3"></div>
      </div>
    <?php else: ?>
      <div class="staff-grid">
        <aside class="staff-card p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="font-semibold">สถานที่ของคุณ</div>
            <button id="staffReload" class="staff-btn-ghost text-sm">รีเฟรช</button>
          </div>
          <input id="staffLocationSearch" class="staff-input mb-3" placeholder="ค้นหาสถานที่..." />
          <div id="staffLocationList" class="space-y-2 max-h-[520px] overflow-auto"></div>
        </aside>

        <main class="space-y-4">
          <div class="staff-card p-5">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm text-slate-500">สถานที่ที่เลือก</div>
                <h2 id="staffSelectedTitle" class="text-xl font-extrabold">—</h2>
              </div>
              <span id="staffSessionsCount" class="staff-badge">0 Sessions</span>
            </div>
            <div class="staff-tabs mt-4">
              <button id="tabLocation" class="staff-tab active">ข้อมูลสถานที่</button>
              <button id="tabSessions" class="staff-tab">Sessions</button>
            </div>
          </div>

          <div id="panelLocation" class="staff-card p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-semibold">เบอร์โทร</label>
                <input id="staffPhone" class="staff-input mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold">ชื่อสถานที่</label>
                <input id="staffLocationTitle" class="staff-input mt-2" disabled />
              </div>
            </div>
            <div>
              <label class="text-sm font-semibold">เวลาเปิดปิด</label>
              <textarea id="staffHours" class="staff-textarea mt-2"></textarea>
            </div>
            <div class="flex items-center gap-3">
              <button id="staffSave" class="staff-btn">บันทึกข้อมูลสถานที่</button>
              <div id="staffSaveMsg" class="staff-muted"></div>
            </div>
          </div>

          <div id="panelSessions" class="staff-card p-5 space-y-4 hidden">
            <div class="flex items-center gap-3">
              <input id="staffSessionSearch" class="staff-input" placeholder="ค้นหา session..." />
              <button id="staffSaveAllSessions" class="staff-btn-ghost">บันทึกทั้งหมด</button>
              <div id="staffSessionSaveMsg" class="staff-muted"></div>
            </div>
            <div id="staffSessions" class="space-y-3"></div>
          </div>
        </main>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  const STAFF = {
    loggedIn: <?php echo $is_logged_in ? 'true' : 'false'; ?>,
    restNonce: "<?php echo esc_js($rest_nonce); ?>",
    ajaxUrl: "<?php echo esc_js(admin_url('admin-ajax.php')); ?>",
  };

  function qs(id){ return document.getElementById(id); }

  if (!STAFF.loggedIn) {
    let staffUid = null;
    const btn = qs("staffSendOtp");
    const otpWrap = qs("staffOtpWrap");
    const otpMsg = qs("staffOtpMsg");
    const loginMsg = qs("staffLoginMsg");

    async function requestOtp() {
      const email = qs("staffEmail")?.value?.trim();
      if (!email) { loginMsg.textContent = "กรุณากรอกอีเมล"; return; }
      loginMsg.textContent = "กำลังส่ง OTP...";
      const fd = new FormData();
      fd.append("action", "lc_staff_request_otp");
      fd.append("email", email);
      const res = await fetch(STAFF.ajaxUrl, { method: "POST", body: fd });
      const json = await res.json();
      if (json?.success) {
        staffUid = json?.data?.uid || null;
        otpWrap.classList.remove("hidden");
        loginMsg.textContent = "ส่ง OTP แล้ว กรุณาตรวจอีเมล";
        if (json?.data?.dev_otp) {
          loginMsg.textContent = `OTP สำหรับ dev: ${json.data.dev_otp}`;
        }
      } else {
        loginMsg.textContent = "ส่ง OTP ไม่สำเร็จ";
      }
    }

    btn?.addEventListener("click", requestOtp);
    qs("staffResendOtp")?.addEventListener("click", requestOtp);

    qs("staffVerifyOtp")?.addEventListener("click", async () => {
      const otp = qs("staffOtp")?.value?.trim();
      if (!otp || !staffUid) { otpMsg.textContent = "กรุณากรอก OTP"; return; }
      otpMsg.textContent = "กำลังยืนยัน...";
      const fd = new FormData();
      fd.append("action", "lc_staff_verify_otp");
      fd.append("uid", staffUid);
      fd.append("otp", otp);
      const res = await fetch(STAFF.ajaxUrl, { method: "POST", body: fd });
      const json = await res.json();
      if (json?.success) {
        otpMsg.textContent = "ยืนยันแล้ว กำลังเข้าสู่ระบบ...";
        window.location.reload();
      } else {
        otpMsg.textContent = "OTP ไม่ถูกต้องหรือหมดอายุ";
      }
    });
  } else {
    const list = qs("staffLocationList");
    const search = qs("staffLocationSearch");
    const phone = qs("staffPhone");
    const hours = qs("staffHours");
    const msg = qs("staffSaveMsg");
    const selectedTitle = qs("staffSelectedTitle");
    const locationTitle = qs("staffLocationTitle");
    const tabLocation = qs("tabLocation");
    const tabSessions = qs("tabSessions");
    const panelLocation = qs("panelLocation");
    const panelSessions = qs("panelSessions");
    const sessionWrap = qs("staffSessions");
    const sessionSearch = qs("staffSessionSearch");
    const sessionSaveAll = qs("staffSaveAllSessions");
    const sessionSaveMsg = qs("staffSessionSaveMsg");
    const sessionsCount = qs("staffSessionsCount");

    let locations = [];
    let activeLocation = null;
    let sessions = [];

    function setActiveTab(tab) {
      if (tab === "sessions") {
        tabSessions.classList.add("active");
        tabLocation.classList.remove("active");
        panelSessions.classList.remove("hidden");
        panelLocation.classList.add("hidden");
      } else {
        tabLocation.classList.add("active");
        tabSessions.classList.remove("active");
        panelLocation.classList.remove("hidden");
        panelSessions.classList.add("hidden");
      }
    }

    tabLocation?.addEventListener("click", () => setActiveTab("location"));
    tabSessions?.addEventListener("click", () => setActiveTab("sessions"));

    async function loadLocations() {
      list.innerHTML = "กำลังโหลด...";
      const res = await fetch("/learningcity/wp-json/lc/v1/staff/locations", {
        headers: { "X-WP-Nonce": STAFF.restNonce }
      });
      const json = await res.json();
      locations = json.items || [];
      renderLocationList(locations);
      if (locations.length) selectLocation(locations[0].id);
    }

    function renderLocationList(items) {
      list.innerHTML = "";
      if (!items.length) {
        list.innerHTML = `<div class="staff-muted">ไม่พบสถานที่</div>`;
        return;
      }
      items.forEach(loc => {
        const row = document.createElement("button");
        row.className = "staff-list-item";
        row.dataset.id = loc.id;
        row.innerHTML = `
          <div class="min-w-0">
            <div class="font-semibold truncate">${loc.title}</div>
            <div class="staff-muted truncate">${loc.phone || "ไม่มีเบอร์โทร"}</div>
          </div>
          <span class="staff-pill">Location</span>
        `;
        row.addEventListener("click", () => selectLocation(loc.id));
        list.appendChild(row);
      });
    }

    function highlightActive() {
      list.querySelectorAll(".staff-list-item").forEach(el => {
        el.classList.toggle("active", Number(el.dataset.id) === activeLocation?.id);
      });
    }

    async function selectLocation(id) {
      activeLocation = locations.find(l => l.id === Number(id)) || null;
      if (!activeLocation) return;
      selectedTitle.textContent = activeLocation.title;
      locationTitle.value = activeLocation.title;
      phone.value = activeLocation.phone || "";
      hours.value = activeLocation.hours || "";
      msg.textContent = "";
      highlightActive();
      await loadSessions(activeLocation.id);
    }

    qs("staffReload")?.addEventListener("click", loadLocations);

    search?.addEventListener("input", () => {
      const q = search.value.trim().toLowerCase();
      const filtered = locations.filter(l => (l.title || "").toLowerCase().includes(q));
      renderLocationList(filtered);
      highlightActive();
    });

    qs("staffSave")?.addEventListener("click", async () => {
      if (!activeLocation) return;
      msg.textContent = "กำลังบันทึก...";
      const res = await fetch(`/learningcity/wp-json/lc/v1/staff/location/${activeLocation.id}`, {
        method: "POST",
        headers: {
          "X-WP-Nonce": STAFF.restNonce,
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          phone: phone.value || "",
          hours: hours.value || ""
        })
      });
      const json = await res.json();
      msg.textContent = json?.ok ? "บันทึกแล้ว" : "บันทึกไม่สำเร็จ";
    });

    async function loadSessions(locationId) {
      sessionWrap.innerHTML = "กำลังโหลด...";
      const res = await fetch(`/learningcity/wp-json/lc/v1/staff/sessions?location_id=${locationId}`, {
        headers: { "X-WP-Nonce": STAFF.restNonce }
      });
      const json = await res.json();
      sessions = json.items || [];
      sessionsCount.textContent = `${sessions.length} Sessions`;
      renderSessions(sessions);
    }

    function renderSessions(items) {
      sessionWrap.innerHTML = "";
      if (!items.length) {
        sessionWrap.innerHTML = `<div class="staff-muted">ไม่พบ session</div>`;
        return;
      }
      items.forEach(it => {
        const card = document.createElement("div");
        card.className = "staff-session-card";
        card.dataset.id = it.id;
        card.innerHTML = `
          <div class="flex items-center justify-between gap-2">
            <h4>${it.title || "-"}</h4>
            <a href="${it.edit_url || "#"}" target="_blank" rel="noreferrer" class="staff-muted underline">เปิดในแอดมิน ↗</a>
          </div>
          <div class="mt-2">
            <label class="staff-muted">session_details</label>
            <textarea class="staff-textarea mt-2" rows="3">${it.session_details || ""}</textarea>
          </div>
          <div class="mt-2 flex items-center gap-2">
            <button class="btnSaveSession staff-btn">บันทึก</button>
            <span class="saveMsg staff-muted"></span>
          </div>
        `;
        const btn = card.querySelector(".btnSaveSession");
        const msg = card.querySelector(".saveMsg");
        const ta = card.querySelector("textarea");
        btn?.addEventListener("click", async () => {
          msg.textContent = "กำลังบันทึก...";
          const res = await fetch(`/learningcity/wp-json/lc/v1/staff/session/${it.id}`, {
            method: "POST",
            headers: {
              "X-WP-Nonce": STAFF.restNonce,
              "Content-Type": "application/json"
            },
            body: JSON.stringify({ session_details: ta.value || "" })
          });
          const json = await res.json();
          msg.textContent = json?.ok ? "บันทึกแล้ว" : "บันทึกไม่สำเร็จ";
        });
        sessionWrap.appendChild(card);
      });
    }

    sessionSearch?.addEventListener("input", () => {
      const q = sessionSearch.value.trim().toLowerCase();
      const filtered = sessions.filter(s => (s.title || "").toLowerCase().includes(q));
      renderSessions(filtered);
    });

    sessionSaveAll?.addEventListener("click", async () => {
      const cards = Array.from(sessionWrap.querySelectorAll(".staff-session-card"));
      if (!cards.length) return;
      sessionSaveMsg.textContent = "กำลังบันทึกทั้งหมด...";
      let ok = 0;
      for (const card of cards) {
        const id = card.dataset.id;
        const ta = card.querySelector("textarea");
        const res = await fetch(`/learningcity/wp-json/lc/v1/staff/session/${id}`, {
          method: "POST",
          headers: {
            "X-WP-Nonce": STAFF.restNonce,
            "Content-Type": "application/json"
          },
          body: JSON.stringify({ session_details: ta.value || "" })
        });
        const json = await res.json();
        if (json?.ok) ok++;
      }
      sessionSaveMsg.textContent = `บันทึกแล้ว ${ok} รายการ`;
    });

    loadLocations();
  }
</script>

<?php get_footer(); ?>
