(function () {
  const CFG = window.LCW_WAITLIST || {};
  if (!CFG.ajax_url || !CFG.nonce) return;

  const setMessage = (form, text, isError) => {
    const box = form.closest('.lcw-waitlist-wrap')?.querySelector('.lcw-msg');
    if (!box) return;
    box.classList.remove('hidden', 'text-rose-600', 'text-emerald-700');
    box.classList.add(isError ? 'text-rose-600' : 'text-emerald-700');
    box.textContent = text;
  };

  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.lcw-waitlist-form');
    if (!form) return;
    e.preventDefault();

    const courseId = Number(form.getAttribute('data-course-id') || 0);
    const emailInput = form.querySelector('input[name="email"]');
    const websiteInput = form.querySelector('input[name="website"]');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (!courseId || !emailInput) {
      setMessage(form, CFG.error || 'เกิดข้อผิดพลาด', true);
      return;
    }

    const email = (emailInput.value || '').trim();
    if (!email) {
      setMessage(form, 'กรุณากรอกอีเมล', true);
      return;
    }

    const fd = new FormData();
    fd.append('action', 'lcw_subscribe_waitlist');
    fd.append('nonce', CFG.nonce);
    fd.append('course_id', String(courseId));
    fd.append('email', email);
    fd.append('website', websiteInput ? websiteInput.value : '');

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.oldText = submitBtn.textContent;
      submitBtn.textContent = CFG.sending || 'กำลังบันทึก...';
    }

    try {
      const res = await fetch(CFG.ajax_url, {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
      });

      const json = await res.json();
      if (!res.ok || !json || !json.success) {
        const msg = json?.data?.message || CFG.error || 'ไม่สามารถบันทึกได้';
        setMessage(form, msg, true);
        return;
      }

      emailInput.value = '';
      setMessage(form, CFG.success || 'บันทึกแล้ว', false);
    } catch (err) {
      setMessage(form, CFG.error || 'ไม่สามารถบันทึกได้', true);
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = submitBtn.dataset.oldText || 'รับแจ้งเตือน';
      }
    }
  });
})();
