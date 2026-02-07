(function () {
  if (typeof acf === "undefined") return;

  const COURSE_FIELD_KEY = "field_69521c28cbe50";  // field key ของ course
  const PREVIEW_FIELD_KEY = "field_697203cc7e196"; // field key ของ course_provider_preview

  function setPreview(html) {
    const previewField = acf.getField(PREVIEW_FIELD_KEY);
    if (!previewField) return;

    const el = previewField.$el?.find(".acf-input");
    if (el && el.length) el.html(html || "-"); // ✅ เปลี่ยนเป็น html()
  }

  function extractCourseId(value) {
    if (!value) return 0;
    if (typeof value === "number") return value;
    if (typeof value === "string") return parseInt(value, 10) || 0;
    if (typeof value === "object" && value.ID) return parseInt(value.ID, 10) || 0;
    return 0;
  }

  async function fetchProvider(courseId) {
    if (!courseId) return setPreview("-");

    const form = new FormData();
    form.append("action", "scp_get_course_provider");
    form.append("nonce", SCP.nonce);
    form.append("course_id", courseId);

    try {
      const res = await fetch(SCP.ajaxurl, { method: "POST", body: form });
      const json = await res.json();

      // ✅ รับเป็น html
      if (json && json.success) setPreview(json.data.html);
      else setPreview("-");
    } catch (e) {
      setPreview("-");
    }
  }

  function init() {
    const courseField = acf.getField(COURSE_FIELD_KEY);
    if (!courseField) return;

    fetchProvider(extractCourseId(courseField.val()));

    courseField.on("change", function () {
      fetchProvider(extractCourseId(courseField.val()));
    });
  }

  acf.addAction("ready", init);
  acf.addAction("append", init);
})();
