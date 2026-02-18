(function () {
  var cfg = window.LCAnalyticsConfig || {};
  if (!cfg.ajaxUrl || !cfg.nonce) return;

  var lastSearchKey = '';
  var lastSearchAt = 0;
  var SEARCH_COOLDOWN_MS = 60000;
  var lastNotifyKey = '';
  var lastNotifyAt = 0;
  var NOTIFY_COOLDOWN_MS = 15000;

  function send(payload) {
    if (!payload || !payload.event_type) return;

    var fd = new FormData();
    fd.append('action', 'lc_analytics_track');
    fd.append('nonce', cfg.nonce);
    fd.append('event_type', String(payload.event_type || ''));
    fd.append('object_type', String(payload.object_type || ''));
    fd.append('object_id', String(payload.object_id || 0));
    fd.append('keyword', String(payload.keyword || ''));
    fd.append('context', String(payload.context || ''));

    if (navigator.sendBeacon) {
      try {
        navigator.sendBeacon(cfg.ajaxUrl, fd);
        return;
      } catch (e) {
        // fallback to fetch below
      }
    }

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      keepalive: true,
    }).catch(function () {});
  }

  function getPostIdFromPath(path) {
    var match = String(path || '').match(/\/(course|location)\/([0-9]+)\/?$/i);
    if (!match) return 0;
    return parseInt(match[2], 10) || 0;
  }

  function normalizeEmail(email) {
    return String(email || '').trim().toLowerCase();
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || ''));
  }

  function findEmailFromForm(form) {
    if (!form) return '';
    var input = form.querySelector('input[type="email"]');
    if (!input) return '';
    return normalizeEmail(input.value || '');
  }

  function findCourseIdFromForm(form) {
    if (!form) return 0;
    var names = ['course_id', 'post_id', 'courseId', 'postId', 'id'];
    for (var i = 0; i < names.length; i++) {
      var node = form.querySelector('input[name="' + names[i] + '"]');
      if (!node) continue;
      var val = parseInt(node.value || '0', 10) || 0;
      if (val > 0) return val;
    }
    return 0;
  }

  function shouldTrackNotifyFromSubmitter(submitter) {
    if (!submitter) return false;
    var text = (
      submitter.getAttribute('aria-label') ||
      submitter.textContent ||
      submitter.value ||
      ''
    ).toLowerCase();

    return text.indexOf('แจ้งเตือน') !== -1 || text.indexOf('notify') !== -1;
  }

  document.addEventListener(
    'click',
    function (e) {
      var popupCard = e.target.closest('[data-modal-id="modal-course"][data-course-id]');
      if (popupCard) {
        var courseId = parseInt(popupCard.getAttribute('data-course-id') || '0', 10) || 0;
        send({
          event_type: 'course_popup_click',
          object_type: 'course',
          object_id: courseId,
          context: 'modal',
        });
        return;
      }

      var searchTrigger = e.target.closest('[data-modal-id="modal-search"]');
      if (searchTrigger) {
        send({
          event_type: 'search_popup_open',
          context: 'modal_trigger',
        });
      }

      var link = e.target.closest('a[href]');
      if (!link) return;

      try {
        var url = new URL(link.getAttribute('href'), window.location.origin);
        var path = url.pathname || '';

        if (path.indexOf('/course/') !== -1) {
          send({
            event_type: 'course_click',
            object_type: 'course',
            object_id: getPostIdFromPath(path),
            context: 'link',
          });
          return;
        }

        if (path.indexOf('/location/') !== -1) {
          send({
            event_type: 'location_click',
            object_type: 'location',
            object_id: getPostIdFromPath(path),
            context: 'link',
          });
          return;
        }

        if (url.searchParams.get('place')) {
          send({
            event_type: 'location_click',
            object_type: 'location',
            object_id: parseInt(url.searchParams.get('place') || '0', 10) || 0,
            context: 'map_link',
          });
        }
      } catch (err) {
        // ignore
      }
    },
    true
  );

  window.LCAnalytics = window.LCAnalytics || {};
  window.LCAnalytics.trackCourseNotify = function (email, courseId, context) {
    var cleanEmail = normalizeEmail(email);
    if (!isValidEmail(cleanEmail)) return;

    var cid = parseInt(courseId || 0, 10) || 0;
    if (!cid) {
      cid = getPostIdFromPath(window.location.pathname || '');
    }

    var now = Date.now();
    var dedupeKey = cleanEmail + '|' + String(cid);
    if (dedupeKey === lastNotifyKey && now - lastNotifyAt < NOTIFY_COOLDOWN_MS) {
      return;
    }
    lastNotifyKey = dedupeKey;
    lastNotifyAt = now;

    send({
      event_type: 'course_notify_subscribe',
      object_type: 'course',
      object_id: cid,
      keyword: cleanEmail,
      context: String(context || 'notify_form'),
    });
  };

  window.LCAnalytics.trackSearch = function (keyword) {
    var q = String(keyword || '').trim();
    if (q.length < 2) return;

    var k = q.toLowerCase();
    var now = Date.now();
    if (k === lastSearchKey && now - lastSearchAt < SEARCH_COOLDOWN_MS) {
      return;
    }

    lastSearchKey = k;
    lastSearchAt = now;

    send({
      event_type: 'search_keyword',
      keyword: q,
      context: 'modal_search',
    });
  };

  document.addEventListener(
    'submit',
    function (e) {
      var form = e.target;
      if (!form || !form.matches || !form.matches('form')) return;

      var submitter = e.submitter || document.activeElement || null;
      if (!shouldTrackNotifyFromSubmitter(submitter)) return;

      var email = findEmailFromForm(form);
      if (!isValidEmail(email)) return;

      var courseId = findCourseIdFromForm(form) || getPostIdFromPath(window.location.pathname || '');
      window.LCAnalytics.trackCourseNotify(email, courseId, 'notify_submit');
    },
    true
  );
})();
