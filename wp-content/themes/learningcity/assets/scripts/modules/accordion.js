function accordionInContent() {
  let accHead = document.querySelectorAll(".accordion-header");
  let accPanel = document.querySelectorAll(".accordion-panel");
  let textExpand = document.querySelectorAll(".accordion-text-expand");
  let icon = document.querySelectorAll(".icon-arrow-accordion");

  if (!accHead.length) return;

  function openAccordion(index) {
    accHead[index].classList.add("is-active");
    accPanel[index].style.maxHeight = accPanel[index].scrollHeight + "px";
    textExpand[index].innerHTML = "ปิด";
    icon[index].classList.add("is-active");
  }

  function closeAccordion(index) {
    accHead[index].classList.remove("is-active");
    accPanel[index].style.maxHeight = null;
    textExpand[index].innerHTML = "รายละเอียด";
    icon[index].classList.remove("is-active");
  }

  // เปิดตัวแรกเป็นค่าเริ่มต้น
  openAccordion(0);

  accHead.forEach((head, i) => {
    head.addEventListener("click", () => {
      const isActive = head.classList.contains("is-active");

      // ปิดทุกอันก่อน
      accHead.forEach((_, idx) => closeAccordion(idx));

      // ถ้าอันที่คลิก "ยังไม่เปิด" → เปิด
      if (!isActive) {
        openAccordion(i);
      }
    });
  });
}

export function initAccordion() {
  accordionInContent();
}
