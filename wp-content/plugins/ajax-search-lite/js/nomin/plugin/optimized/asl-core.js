/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};

;// external "global"
var external_global_namespaceObject = Object(window.WPD)["global"];
;// external "utils"
var external_utils_namespaceObject = Object(window.WPD)["utils"];
;// ./src/client/plugin/core/actions/filters.ts



"use strict";
external_global_namespaceObject.AslPlugin.prototype.setFilterStateInput = function(timeout) {
  let $this = this;
  if (typeof timeout == "undefined") {
    timeout = 65;
  }
  let process = function() {
    if (JSON.stringify($this.originalFormData) != JSON.stringify((0,external_utils_namespaceObject.formData)($this.n("searchsettings").find("form"))))
      $this.n("searchsettings").find("input[name=filters_initial]").val(0);
    else
      $this.n("searchsettings").find("input[name=filters_initial]").val(1);
  };
  if (timeout == 0) {
    process();
  } else {
    setTimeout(function() {
      process();
    }, timeout);
  }
};
/* harmony default export */ var filters = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/actions/ga_events.ts


"use strict";
const ASL = window.ASL;
external_global_namespaceObject.AslPlugin.prototype.gaPageview = function(term) {
  let $this = this;
  let tracking_id = $this.gaGetTrackingID();
  if (typeof ASL.analytics == "undefined" || ASL.analytics.method != "pageview")
    return false;
  if (ASL.analytics.string != "") {
    let _ga = typeof window.__gaTracker == "function" ? window.__gaTracker : typeof window.ga == "function" ? window.ga : false;
    let _gtag = typeof window.gtag == "function" ? window.gtag : false;
    let url = $this.o.homeurl.replace(window.location.origin, "");
    if (_gtag !== false) {
      if (tracking_id !== false) {
        tracking_id.forEach(function(id) {
          _gtag("config", id, { "page_path": url + ASL.analytics.string.replace("{asl_term}", term) });
        });
      }
    } else if (_ga !== false) {
      let params = {
        "page": url + ASL.analytics.string.replace("{asl_term}", term),
        "title": "Ajax Search"
      };
      if (tracking_id !== false) {
        tracking_id.forEach(function(id) {
          _ga("create", id, "auto");
          _ga("send", "pageview", params);
        });
      } else {
        _ga("send", "pageview", params);
      }
    }
  }
};
external_global_namespaceObject.AslPlugin.prototype.gaEvent = function(which, d) {
  let $this = this;
  let tracking_id = $this.gaGetTrackingID();
  if (typeof ASL.analytics == "undefined" || ASL.analytics.method != "event")
    return false;
  let _gtag = typeof window.gtag == "function" ? window.gtag : false;
  let _ga = typeof window.__gaTracker == "function" ? window.__gaTracker : typeof window.ga == "function" ? window.ga : false;
  if (_gtag === false && _ga === false && typeof window.dataLayer == "undefined")
    return false;
  if (typeof ASL.analytics.event[which] != "undefined" && ASL.analytics.event[which].active) {
    let def_data = {
      "search_id": $this.o.id,
      "search_name": $this.o.name,
      "phrase": $this.n("text").val(),
      "option_name": "",
      "option_value": "",
      "result_title": "",
      "result_url": "",
      "results_count": ""
    };
    let event = {
      "event_category": ASL.analytics.event[which].category,
      "event_label": ASL.analytics.event[which].label,
      "value": ASL.analytics.event[which].value,
      "send_to": ""
    };
    const data = { ...def_data, ...d };
    Object.keys(data).forEach(function(k) {
      let v = data[k];
      v = String(v).replace(/[\s\n\r]+/g, " ").trim();
      Object.keys(event).forEach(function(kk) {
        let regex = new RegExp("{" + k + "}", "gmi");
        event[kk] = event[kk].replace(regex, v);
      });
    });
    if (_ga !== false) {
      if (tracking_id !== false) {
        tracking_id.forEach(function(id) {
          _ga("create", id, "auto");
          _ga(
            "send",
            "event",
            event.event_category,
            ASL.analytics.event[which].action,
            event.event_label,
            event.value
          );
        });
      } else {
        _ga(
          "send",
          "event",
          event.event_category,
          ASL.analytics.event[which].action,
          event.event_label,
          event.value
        );
      }
    } else if (_gtag !== false) {
      if (tracking_id !== false) {
        tracking_id.forEach(function(id) {
          event.send_to = id;
          _gtag("event", ASL.analytics.event[which].action, event);
        });
      } else {
        _gtag("event", ASL.analytics.event[which].action, event);
      }
    } else if (window?.dataLayer?.push !== void 0) {
      window.dataLayer.push({
        "event": "gaEvent",
        "eventCategory": event.event_category,
        "eventAction": ASL.analytics.event[which].action,
        "eventLabel": event.event_label
      });
    }
  }
};
external_global_namespaceObject.AslPlugin.prototype.gaGetTrackingID = function() {
  let ret = false;
  if (typeof ASL.analytics == "undefined") {
    return ret;
  }
  if (typeof ASL.analytics.tracking_id != "undefined" && ASL.analytics.tracking_id != "") {
    return [ASL.analytics.tracking_id];
  } else {
    let _gtag = typeof window.gtag == "function" ? window.gtag : false;
    if (_gtag === false && typeof window.ga != "undefined" && typeof window.ga.getAll != "undefined") {
      let id = [];
      window.ga.getAll().forEach(function(tracker) {
        id.push(tracker.get("trackingId"));
      });
      return id.length > 0 ? id : false;
    }
  }
  return ret;
};
/* harmony default export */ var ga_events = ((/* unused pure expression or super */ null && (AslPlugin)));

;// external "DoMini"
var external_DoMini_namespaceObject = Object(window.WPD)["DoMini"];
var external_DoMini_default = /*#__PURE__*/__webpack_require__.n(external_DoMini_namespaceObject);
;// ./src/client/plugin/core/actions/live.ts







const live_ASL = window.ASL;
external_global_namespaceObject.AslPlugin.prototype.getLiveLoadAltSelectors = function() {
  return [
    ".search-content",
    "#content #posts-container",
    "#content",
    "#Content",
    "div[role=main]",
    "main[role=main]",
    "div.theme-content",
    "div.td-ss-main-content",
    "main#page-content",
    "main.l-content",
    "#primary",
    "#main-content",
    ".main-content",
    ".search section .bde-post-loop",
    // breakdance posts loop section search archive
    ".archive section .bde-post-loop",
    // breakdance posts loop section general archive
    ".search section .bde-post-list",
    // breakdance posts list section search archive
    ".archive section .bde-post-list",
    // breakdance posts list section general archive
    "main .wp-block-query",
    // block themes
    "main"
    // fallback
  ];
};
external_global_namespaceObject.AslPlugin.prototype.usingLiveLoader = function() {
  const $this = this;
  if ($this._usingLiveLoader !== void 0) return $this._usingLiveLoader;
  const o = $this.o;
  const idClass = "asp_es_" + o.id;
  const altSelectors = this.getLiveLoadAltSelectors().join(",");
  if (document.getElementsByClassName(idClass).length) {
    return $this._usingLiveLoader = true;
  }
  const options = ["resPage"];
  $this._usingLiveLoader = options.some((key) => {
    const opt = o[key];
    return opt.useAjax && (document.querySelector(opt.selector) || altSelectors && document.querySelector(altSelectors));
  });
  return $this._usingLiveLoader;
};
external_global_namespaceObject.AslPlugin.prototype.liveLoad = function(selector, url, updateLocation, forceAjax) {
  if (selector == "body" || selector == "html") {
    console.log("Ajax Search Pro: Do not use html or body as the live loader selector.");
    return false;
  }
  if (live_ASL.pageHTML == "") {
    if (!live_ASL._ajax_page_html) {
      live_ASL._ajax_page_html = true;
      external_DoMini_default().fn.ajax({
        url: location.href,
        method: "GET",
        success: function(data) {
          live_ASL.pageHTML = data;
        },
        // @ts-ignore
        dataType: "html"
      });
    }
  }
  function process(data) {
    data = external_utils_namespaceObject.Hooks.applyFilters("asl/live_load/raw_data", data, $this);
    let parser = new DOMParser();
    let dataNode = parser.parseFromString(data, "text/html");
    let $dataNode = external_DoMini_default()(dataNode);
    if (data != "" && $dataNode.length > 0 && $dataNode.find(selector).length > 0) {
      data = data.replace(/&asl_force_reset_pagination=1/gmi, "");
      data = data.replace(/%26asl_force_reset_pagination%3D1/gmi, "");
      data = data.replace(/&#038;asl_force_reset_pagination=1/gmi, "");
      if ((0,external_utils_namespaceObject.isSafari)()) {
        data = data.replace(/srcset/gmi, "nosrcset");
      }
      data = external_utils_namespaceObject.Hooks.applyFilters("asl/live_load/html", data, $this.o.id, $this.o.iid);
      $dataNode = external_DoMini_default()(parser.parseFromString(data, "text/html"));
      let replacementNode = $dataNode.find(selector).get(0);
      replacementNode = external_utils_namespaceObject.Hooks.applyFilters("asl/live_load/replacement_node", replacementNode, $this, $el.get(0), data);
      if (replacementNode != null) {
        const node = $el.get(0);
        if (node !== void 0) {
          $el.get(0)?.parentNode?.replaceChild(replacementNode, node);
        }
      }
      $el = external_DoMini_default()(selector).first();
      if (updateLocation) {
        document.title = dataNode.title;
        history.pushState({}, "", url);
      }
      external_DoMini_default()(selector).first().find(".woocommerce-ordering").on("change", "select.orderby", function() {
        external_DoMini_default()(this).closest("form").trigger("submit");
      });
      $this.addHighlightString(external_DoMini_default()(selector).find("a"));
      external_utils_namespaceObject.Hooks.applyFilters("asl/live_load/finished", url, $this, selector, $el.get(0));
      live_ASL.initialize();
      $this.lastSuccesfulSearch = $this.n("searchsettings").find("form").serialize() + $this.n("text").val().trim();
      $this.lastSearchData = data;
    }
    $this.n("s").trigger("asl_search_end", [$this.o.id, $this.o.iid, $this.n("text").val(), data], true, true);
    $this.gaEvent?.("search_end", { "results_count": "unknown" });
    $this.gaPageview?.($this.n("text").val());
    $this.hideLoader();
    $el.css("opacity", 1);
    $this.searching = false;
    if ($this.n("text").val() != "") {
      $this.n("proclose").css({
        display: "block"
      });
    }
  }
  updateLocation = typeof updateLocation == "undefined" ? this.o.trigger.update_href : updateLocation;
  forceAjax = typeof forceAjax == "undefined" ? false : forceAjax;
  let altSel = this.getLiveLoadAltSelectors();
  if (selector != "#main")
    altSel.unshift("#main");
  if (external_DoMini_default()(selector).length < 1) {
    altSel.forEach(function(s) {
      if (external_DoMini_default()(s).length > 0) {
        selector = s;
        return false;
      }
    });
    if (external_DoMini_default()(selector).length < 1) {
      console.log("Ajax Search Lite: The live search selector does not exist on the page.");
      return false;
    }
  }
  selector = external_utils_namespaceObject.Hooks.applyFilters("asl/live_load/selector", selector, this);
  let $el = external_DoMini_default()(selector).first(), $this = this;
  $this.searchAbort();
  $el.css("opacity", 0.4);
  external_utils_namespaceObject.Hooks.applyFilters("asl/live_load/start", url, $this, selector, $el.get(0));
  if (!forceAjax && $this.n("searchsettings").find("input[name=filters_initial]").val() == 1 && $this.n("text").val() == "") {
    (0,external_utils_namespaceObject.intervalUntilExecute)(function() {
      process(live_ASL.pageHTML);
    }, function() {
      return live_ASL.pageHTML != "";
    });
  } else {
    $this.searching = true;
    $this.post = external_DoMini_default().fn.ajax({
      url,
      method: "GET",
      success: function(data) {
        process(data);
      },
      // @ts-ignore
      dataType: "html",
      fail: function(jqXHR) {
        $el.css("opacity", 1);
        if (jqXHR.status === 0 && jqXHR.readyState === jqXHR.UNSENT) {
          return;
        }
        $el.html("This request has failed. Please check your connection.");
        $this.hideLoader();
        $this.searching = false;
        $this.n("proclose").css({
          display: "block"
        });
      }
    });
  }
};
external_global_namespaceObject.AslPlugin.prototype.getCurrentLiveURL = function() {
  let $this = this;
  let url = "asl_ls=" + (0,external_utils_namespaceObject.nicePhrase)($this.n("text").val()), start = "&", location2 = window.location.href;
  location2 = location2.indexOf("asl_ls=") > -1 ? location2.slice(0, location2.indexOf("asl_ls=")) : location2;
  location2 = location2.indexOf("asl_ls&") > -1 ? location2.slice(0, location2.indexOf("asl_ls&")) : location2;
  location2 = location2.indexOf("p_asid=") > -1 ? location2.slice(0, location2.indexOf("p_asid=")) : location2;
  location2 = location2.indexOf("asl_") > -1 ? location2.slice(0, location2.indexOf("asl_")) : location2;
  if (location2.indexOf("?") === -1) {
    start = "?";
  }
  let final = location2 + start + url + "&asl_active=1&asl_force_reset_pagination=1&p_asid=" + $this.o.id + "&p_asl_data=1&" + $this.n("searchsettings").find("form").serialize();
  final = final.replace("?&", "?");
  return final;
};
/* harmony default export */ var live = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/actions/loader.ts


"use strict";
external_global_namespaceObject.AslPlugin.prototype.showLoader = function() {
  this.n("proloading").css({
    display: "block"
  });
};
external_global_namespaceObject.AslPlugin.prototype.hideLoader = function() {
  let $this = this;
  $this.n("proloading").css({
    display: "none"
  });
  $this.n("results").css("display", "");
};
/* harmony default export */ var loader = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/actions/other.ts



"use strict";
const other_ASL = window.ASL;
external_global_namespaceObject.AslPlugin.prototype.loadASLFonts = function() {
  if (other_ASL.font_url !== false) {
    let font = new FontFace(
      "aslsicons2",
      "url(" + other_ASL.font_url + ")",
      { style: "normal", weight: "normal", display: "swap" }
    );
    font.load().then(function(loaded_face) {
      document.fonts.add(loaded_face);
    }).catch(function(er) {
    });
    other_ASL.font_url = false;
  }
};
external_global_namespaceObject.AslPlugin.prototype.updateHref = function() {
  if (this.o.trigger.update_href && !this.usingLiveLoader()) {
    let url = this.getStateURL() + (this.resultsOpened ? "&asl_s=" : "&asl_ls=") + this.n("text").val();
    history.replaceState("", "", url.replace(location.origin, ""));
  }
};
external_global_namespaceObject.AslPlugin.prototype.fixClonedSelf = function() {
  let $this = this, oldInstanceId = $this.o.iid, oldRID = $this.o.rid;
  while (!other_ASL.instances.set($this)) {
    ++$this.o.iid;
    if ($this.o.iid > 50) {
      break;
    }
  }
  if (oldInstanceId != $this.o.iid) {
    $this.o.rid = $this.o.id + "_" + $this.o.iid;
    const search = $this.n("search").get(0);
    if (search !== void 0) {
      search.id = "ajaxsearchlite" + $this.o.rid;
      $this.n("search").removeClass("asl_m_" + oldRID).addClass("asl_m_" + $this.o.rid).data("instance", $this.o.iid);
    }
    const searchsettings = $this.n("searchsettings").get(0);
    if (searchsettings !== void 0) {
      searchsettings.id = searchsettings.id.replace("settings" + oldRID, "settings" + $this.o.rid);
    }
    if ($this.n("searchsettings").hasClass("asl_s_" + oldRID)) {
      $this.n("searchsettings").removeClass("asl_s_" + oldRID).addClass("asl_s_" + $this.o.rid).data("instance", $this.o.iid);
    } else {
      $this.n("searchsettings").removeClass("asl_sb_" + oldRID).addClass("asl_sb_" + $this.o.rid).data("instance", $this.o.iid);
    }
    const resultsDiv = $this.n("resultsDiv").get(0);
    if (resultsDiv !== void 0) {
      resultsDiv.id = resultsDiv.id.replace("prores" + oldRID, "prores" + $this.o.rid);
      $this.n("resultsDiv").removeClass("asl_r_" + oldRID).addClass("asl_r_" + $this.o.rid).data("instance", $this.o.iid);
    }
    const asl_init_data = $this.n("container").find(".asl_init_data").get(0);
    if (asl_init_data !== void 0) {
      $this.n("container").find(".asl_init_data").data("instance", $this.o.iid);
      asl_init_data.id = asl_init_data.id.replace("asl_init_id_" + oldRID, "asl_init_id_" + $this.o.rid);
    }
    $this.n("prosettings").data("opened", 0);
  }
};
external_global_namespaceObject.AslPlugin.prototype.destroy = function() {
  let $this = this;
  Object.keys($this.nodes).forEach(function(k) {
    $this.nodes[k].off?.();
  });
  $this.n("searchsettings").remove?.();
  $this.n("resultsDiv").remove?.();
  $this.n("search").remove?.();
  $this.n("container").remove?.();
  $this.documentEventHandlers.forEach(function(h) {
    external_DoMini_default()(h.node).off(h.event, h.handler);
  });
};
/* harmony default export */ var other = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/actions/redirect.ts






external_global_namespaceObject.AslPlugin.prototype.isRedirectToFirstResult = function() {
  return Boolean((this.n("resultsDiv").find(".asl_res_url").length > 0 || external_DoMini_default()(".asl_es_" + this.o.id + " a").length > 0 || this.o.resPage.useAjax && external_DoMini_default()(this.o.resPage.selector + "a").length > 0) && (this.o.redirectOnClick && this.ktype == "click" && this.o.trigger.click == "first_result" || this.o.redirectOnEnter && (this.ktype == "input" || this.ktype == "keyup") && this.keycode == 13 && this.o.trigger.return == "first_result"));
};
external_global_namespaceObject.AslPlugin.prototype.doRedirectToFirstResult = function() {
  let _loc, url = "";
  if (this.ktype == "click") {
    _loc = this.o.trigger.click_location;
  } else {
    _loc = this.o.trigger.return_location;
  }
  if (this.n("resultsDiv").find(".asl_res_url").length > 0) {
    url = external_DoMini_default()(this.n("resultsDiv").find(".asl_res_url").get(0)).attr("href");
  } else if (external_DoMini_default()(".asl_es_" + this.o.id + " a").length > 0) {
    url = external_DoMini_default()(external_DoMini_default()(".asl_es_" + this.o.id + " a").get(0)).attr("href");
  } else if (this.o.resPage.useAjax && external_DoMini_default()(this.o.resPage.selector + "a").length > 0) {
    url = external_DoMini_default()(external_DoMini_default()(this.o.resPage.selector + "a").get(0)).attr("href");
  }
  if (url !== "") {
    if (_loc == "same") {
      window.location.href = url;
    } else {
      (0,external_utils_namespaceObject.openInNewTab)(url);
    }
    this.hideLoader();
    this.hideResults();
  }
  return false;
};
external_global_namespaceObject.AslPlugin.prototype.doRedirectToResults = function(ktype) {
  let _loc;
  if (ktype == "click") {
    _loc = this.o.trigger.click_location;
  } else {
    _loc = this.o.trigger.return_location;
  }
  let url = this.getRedirectURL(ktype);
  if (this.o.overridewpdefault) {
    if (this.o.resPage.useAjax) {
      this.hideResults();
      this.liveLoad(this.o.resPage.selector, url);
      this.showLoader();
      return false;
    }
    if (this.o.override_method == "post") {
      (0,external_utils_namespaceObject.submitToUrl)(url, "post", {
        asl_active: 1,
        p_asl_data: this.n("searchsettings").find("form").serialize()
      }, _loc);
    } else {
      if (_loc == "same") {
        location.href = url;
      } else {
        (0,external_utils_namespaceObject.openInNewTab)(url);
      }
    }
  } else {
    (0,external_utils_namespaceObject.submitToUrl)(url, "post", {
      np_asl_data: this.n("searchsettings").find("form").serialize()
    }, _loc);
  }
  this.n("proloading").css("display", "none");
  this.hideLoader();
  this.hideResults();
  this.searchAbort();
};
external_global_namespaceObject.AslPlugin.prototype.getRedirectURL = function(ktype = "enter") {
  let url, source, final, base_url;
  if (ktype == "click") {
    source = this.o.trigger.click;
  } else {
    source = this.o.trigger.return;
  }
  if (source == "results_page" || source == "ajax_search") {
    url = "?s=" + (0,external_utils_namespaceObject.nicePhrase)(this.n("text").val());
  } else if (source == "woo_results_page") {
    url = "?post_type=product&s=" + (0,external_utils_namespaceObject.nicePhrase)(this.n("text").val());
  } else {
    base_url = this.o.trigger.redirect_url;
    url = base_url.replace(/{phrase}/g, (0,external_utils_namespaceObject.nicePhrase)(this.n("text").val()));
  }
  if (this.o.homeurl.indexOf("?") > 1 && url.indexOf("?") === 0) {
    url = url.replace("?", "&");
  }
  if (this.o.overridewpdefault && this.o.override_method != "post") {
    let start = "&";
    if (this.o.homeurl.indexOf("?") === -1 && url.indexOf("?") === -1) {
      start = "?";
    }
    let addUrl = url + start + "asl_active=1&p_asl_data=1&" + this.n("searchsettings").find("form").serialize();
    final = this.o.homeurl + addUrl;
  } else {
    final = this.o.homeurl + url;
  }
  final = final.replace("https://", "https:///");
  final = final.replace("http://", "http:///");
  final = final.replace(/\/\//g, "/");
  final = external_utils_namespaceObject.Hooks.applyFilters("asl/redirect/url", final, this.o.id, this.o.iid);
  return final;
};
/* harmony default export */ var redirect = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/actions/results.ts




external_global_namespaceObject.AslPlugin.prototype.showResults = function() {
  let $this = this;
  $this.initResults();
  $this.showVerticalResults();
  $this.hideLoader();
  $this.n("proclose").css({
    display: "block"
  });
  if ($this.n("showmore") != null) {
    if ($this.n("items").length > 0) {
      $this.n("showmore").css({
        "display": "block"
      });
    } else {
      $this.n("showmore").css({
        "display": "none"
      });
    }
  }
  $this.resultsOpened = true;
};
external_global_namespaceObject.AslPlugin.prototype.hideResults = function(blur = true) {
  let $this = this;
  if (!$this.resultsOpened) return false;
  $this.n("resultsDiv").removeClass($this.resAnim.showClass).addClass($this.resAnim.hideClass);
  setTimeout(function() {
    $this.n("resultsDiv").css($this.resAnim.hideCSS);
  }, $this.resAnim.duration);
  $this.n("proclose").css({
    display: "none"
  });
  if ((0,external_utils_namespaceObject.isMobile)() && blur) {
    document.activeElement?.blur();
  }
  $this.resultsOpened = false;
  $this.n("s").trigger("asl_results_hide", [$this.o.id, $this.o.iid], true, true);
};
external_global_namespaceObject.AslPlugin.prototype.showResultsBox = function() {
  let $this = this;
  $this.n("s").trigger("asl_results_show", [$this.o.id, $this.o.iid], true, true);
  $this.n("resultsDiv").css({
    display: "block",
    height: "auto"
  });
  $this.n("resultsDiv").css($this.resAnim.showCSS);
  $this.n("resultsDiv").removeClass($this.resAnim.hideClass).addClass($this.resAnim.showClass);
  $this.fixResultsPosition(true);
};
external_global_namespaceObject.AslPlugin.prototype.addHighlightString = function($items) {
  let $this = this, phrase = $this.n("text").val().replace(/["']/g, "");
  $items = typeof $items == "undefined" ? $this.n("items").find("a.asl_res_url") : $items;
  if ($this.o.singleHighlight && phrase != "" && $items.length > 0) {
    $items.forEach(function(el) {
      try {
        const url = new URL(external_DoMini_default()(el).attr("href"));
        url.searchParams.set("asl_highlight", phrase);
        url.searchParams.set("p_asid", String($this.o.id));
        external_DoMini_default()(el).attr("href", url.href);
      } catch (e) {
      }
    });
  }
};
external_global_namespaceObject.AslPlugin.prototype.scrollToResults = function() {
  let $this = this, tolerance = Math.floor(window.innerHeight * 0.1), stop;
  if (!$this.resultsOpened || !$this.o.scrollToResults.enabled || $this.n("resultsDiv").inViewPort(tolerance)) return;
  if ($this.o.resultsposition == "hover") {
    stop = $this.n("probox").offset().top - 20;
  } else {
    stop = $this.n("resultsDiv").offset().top - 20;
  }
  stop = stop + $this.o.scrollToResults.offset;
  let $adminbar = external_DoMini_default()("#wpadminbar");
  if ($adminbar.length > 0)
    stop -= $adminbar.height();
  stop = stop < 0 ? 0 : stop;
  window.scrollTo({ top: stop, behavior: "smooth" });
};
/* harmony default export */ var results = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/actions/search.ts






const search_ASL = window.ASL;
external_global_namespaceObject.AslPlugin.prototype.searchAbort = function() {
  let $this = this;
  if ($this.post != null) {
    $this.post.abort();
  }
};
external_global_namespaceObject.AslPlugin.prototype.searchWithCheck = function(timeout = 50) {
  let $this = this;
  if ($this.n("text").val().length < $this.o.charcount) return;
  $this.searchAbort();
  clearTimeout($this.timeouts.searchWithCheck);
  $this.timeouts.searchWithCheck = setTimeout(function() {
    $this.search();
  }, timeout);
};
external_global_namespaceObject.AslPlugin.prototype.search = function() {
  let $this = this;
  if ($this.searching && 0) {}
  if ($this.n("text").val().length < $this.o.charcount) return;
  $this.searching = true;
  $this.n("proloading").css({
    display: "block"
  });
  $this.n("proclose").css({
    display: "none"
  });
  let data = {
    action: "ajaxsearchlite_search",
    aslp: $this.n("text").val(),
    asid: $this.o.id,
    options: $this.n("searchsettings").find("form").serialize()
  };
  data = external_utils_namespaceObject.Hooks.applyFilters("asl/search/data", data);
  if (JSON.stringify(data) === JSON.stringify($this.lastSearchData)) {
    if (!$this.resultsOpened)
      $this.showResults();
    $this.hideLoader();
    if ($this.isRedirectToFirstResult()) {
      $this.doRedirectToFirstResult();
      return false;
    }
    return false;
  }
  $this.gaEvent?.("search_start");
  if (external_DoMini_default()(".asl_es_" + $this.o.id).length > 0) {
    $this.liveLoad(".asl_es_" + $this.o.id, $this.getCurrentLiveURL(), false);
  } else if ($this.o.resPage.useAjax) {
    $this.liveLoad($this.o.resPage.selector, $this.getRedirectURL());
  } else {
    $this.post = external_DoMini_default().fn.ajax({
      "url": search_ASL.ajaxurl,
      "method": "POST",
      "data": data,
      "success": function(r) {
        let response = r.replace(/^\s*[\r\n]/gm, "");
        const cleanResponse = response.match(/___ASLSTART___(.*[\s\S]*)___ASLEND___/);
        if (cleanResponse === null) {
          $this.hideLoader();
          console.warn("The response inner data is missing!");
          return;
        }
        response = cleanResponse[1];
        response = external_utils_namespaceObject.Hooks.applyFilters("asl/search/html", response);
        $this.n("resdrg").html("");
        $this.n("resdrg").html(response);
        $this.n("resdrg").find(".asl_keyword").on("click", function() {
          $this.n("text").val(external_DoMini_default()(this).html());
          $this.n("container").find("input.orig").val(external_DoMini_default()(this).html()).trigger("keydown");
          $this.n("container").find("form").trigger("submit", ["ajax"]);
          $this.search();
        });
        $this.nodes.items = $this.n("resultsDiv").find(".item");
        $this.addHighlightString();
        $this.gaEvent?.("search_end", { "results_count": $this.n("items").length });
        $this.gaPageview?.($this.n("text").val());
        if ($this.isRedirectToFirstResult()) {
          $this.doRedirectToFirstResult();
          return false;
        }
        $this.hideLoader();
        $this.showResults();
        $this.scrollToResults();
        $this.lastSuccesfulSearch = $this.n("searchsettings").find("form").serialize() + $this.n("text").val().trim();
        $this.lastSearchData = data;
        $this.updateHref();
        if ($this.n("items").length == 0) {
          if ($this.n("showmore") != null) {
            $this.n("showmore").css("display", "none");
          }
        } else {
          if ($this.n("showmore") != null) {
            $this.n("showmore").css("display", "block");
            $this.n("showmore").find("span").off();
            $this.n("showmore").find("span").on("click", function() {
              let source = $this.o.trigger.click, url;
              if (source == "results_page") {
                url = "?s=" + (0,external_utils_namespaceObject.nicePhrase)($this.n("text").val());
              } else if (source == "woo_results_page") {
                url = "?post_type=product&s=" + (0,external_utils_namespaceObject.nicePhrase)($this.n("text").val());
              } else {
                url = $this.o.trigger.redirect_url.replace("{phrase}", (0,external_utils_namespaceObject.nicePhrase)($this.n("text").val()));
              }
              if ($this.o.overridewpdefault) {
                if ($this.o.override_method == "post") {
                  (0,external_utils_namespaceObject.submitToUrl)($this.o.homeurl + url, "post", {
                    asl_active: 1,
                    p_asl_data: $this.n("searchsettings").find("form").serialize()
                  });
                } else {
                  location.href = $this.o.homeurl + url + "&asl_active=1&p_asid=" + $this.o.id + "&p_asl_data=1&" + $this.n("searchsettings").find("form").serialize();
                }
              } else {
                (0,external_utils_namespaceObject.submitToUrl)($this.o.homeurl + url, "post", {
                  np_asl_data: $this.n("searchsettings").find("form").serialize()
                });
              }
            });
          }
        }
        external_utils_namespaceObject.Hooks.applyFilters("asl/search/end", $this, data);
      },
      "fail": function(jqXHR) {
        $this.n("resdrg").html("");
        $this.n("resdrg").html('<div class="asl_nores">The request failed. Please check your connection! Status: ' + jqXHR.status + "</div>");
        $this.nodes.items = $this.n("resultsDiv").find(".item");
        $this.hideLoader();
        $this.showResults();
        $this.scrollToResults();
      }
    });
  }
};
/* harmony default export */ var search = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/etc/api.ts


external_global_namespaceObject.AslPlugin.prototype.searchFor = function(phrase) {
  if (typeof phrase != "undefined") {
    this.n("text").val(phrase);
  }
  this.n("textAutocomplete").val("");
  this.search();
};
external_global_namespaceObject.AslPlugin.prototype.toggleSettings = function(state) {
  if (typeof state != "undefined") {
    if (state == "show") {
      this.showSettings();
    } else {
      this.hideSettings();
    }
  } else {
    if (parseInt(this.n("prosettings").data("opened")) === 1) {
      this.hideSettings();
    } else {
      this.showSettings();
    }
  }
};
external_global_namespaceObject.AslPlugin.prototype.closeResults = function(clear) {
  if (typeof clear != "undefined" && clear) {
    this.n("text").val("");
    this.n("textAutocomplete").val("");
  }
  this.hideResults();
  this.n("proloading").css("display", "none");
  this.hideLoader();
  this.searchAbort();
};
external_global_namespaceObject.AslPlugin.prototype.getStateURL = function() {
  let url, sep;
  const urlParts = location.href.split("p_asid");
  url = urlParts[0];
  url = url.replace("&asl_active=1", "");
  url = url.replace("?asl_active=1", "");
  url = url.slice(-1) == "?" ? url.slice(0, -1) : url;
  url = url.slice(-1) == "&" ? url.slice(0, -1) : url;
  sep = url.indexOf("?") > 1 ? "&" : "?";
  return url + sep + "p_asid=" + this.o.id + "&p_asl_data=1&" + this.n("searchsettings").find("form").serialize();
};
external_global_namespaceObject.AslPlugin.prototype.filtersInitial = function() {
  return this.n("searchsettings").find("input[name=filters_initial]").val() == 1;
};
external_global_namespaceObject.AslPlugin.prototype.filtersChanged = function() {
  return this.n("searchsettings").find("input[name=filters_changed]").val() == 1;
};
/* harmony default export */ var api = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/etc/position.ts





external_global_namespaceObject.AslPlugin.prototype.detectAndFixFixedPositioning = function() {
  let $this = this, fixedp = false, n = $this.n("search").get(0);
  while (n) {
    n = n.parentElement;
    if (n != null && window.getComputedStyle(n).position === "fixed") {
      fixedp = true;
      break;
    }
  }
  if (fixedp || $this.n("search").css("position") == "fixed") {
    if ($this.n("resultsDiv").css("position") == "absolute") {
      $this.n("resultsDiv").css({
        "position": "fixed",
        "z-index": 2147483647
      });
    }
    if (!$this.o.blocking) {
      $this.n("searchsettings").css({
        "position": "fixed",
        "z-index": 2147483647
      });
    }
  } else {
    if ($this.n("resultsDiv").css("position") == "fixed") {
      $this.n("resultsDiv").css("position", "absolute");
    }
    if (!$this.o.blocking) {
      $this.n("searchsettings").css("position", "absolute");
    }
  }
};
external_global_namespaceObject.AslPlugin.prototype.fixResultsPosition = function(ignoreVisibility = false) {
  let $this = this, $body = external_DoMini_default()("body"), bodyTop = 0, rpos = $this.n("resultsDiv").css("position");
  if (external_DoMini_default()._fn.bodyTransformY() != 0 || $body.css("position") != "static") {
    bodyTop = $body.offset().top;
  }
  if (external_DoMini_default()._fn.bodyTransformY() != 0 && rpos == "fixed") {
    rpos = "absolute";
    $this.n("resultsDiv").css("position", "absolute");
  }
  if (rpos == "fixed") {
    bodyTop = 0;
  }
  if (rpos != "fixed" && rpos != "absolute") {
    return;
  }
  if (ignoreVisibility || $this.n("resultsDiv").css("visibility") == "visible") {
    let _rposition = $this.n("search").offset(), bodyLeft = 0;
    if (external_DoMini_default()._fn.bodyTransformX() != 0 || $body.css("position") != "static") {
      bodyLeft = $body.offset().left;
    }
    if (typeof _rposition != "undefined") {
      let vwidth, adjust = 0;
      if ((0,external_utils_namespaceObject.deviceType)() === "phone") {
        vwidth = $this.o.results.width_phone;
      } else if ((0,external_utils_namespaceObject.deviceType)() == "tablet") {
        vwidth = $this.o.results.width_tablet;
      } else {
        vwidth = $this.o.results.width;
      }
      if (vwidth == "auto") {
        vwidth = $this.n("search").outerWidth() < 240 ? 240 : $this.n("search").outerWidth();
      }
      $this.n("resultsDiv").css("width", (0,external_utils_namespaceObject.isNumeric)(vwidth) ? vwidth + "px" : vwidth);
      if ($this.o.resultsSnapTo == "right") {
        adjust = $this.n("resultsDiv").outerWidth() - $this.n("search").outerWidth();
      } else if ($this.o.resultsSnapTo == "center") {
        adjust = Math.floor(($this.n("resultsDiv").outerWidth() - parseInt(String($this.n("search").outerWidth()))) / 2);
      }
      $this.n("resultsDiv").css({
        top: _rposition.top + $this.n("search").outerHeight(true) - bodyTop + "px",
        left: _rposition.left - adjust - bodyLeft + "px"
      });
    }
  }
};
external_global_namespaceObject.AslPlugin.prototype.fixSettingsPosition = function(ignoreVisibility = false) {
  let $this = this, $body = external_DoMini_default()("body"), bodyTop = 0, settPos = $this.n("searchsettings").css("position");
  if (external_DoMini_default()._fn.bodyTransformY() != 0 || $body.css("position") != "static") {
    bodyTop = $body.offset().top;
  }
  if (external_DoMini_default()._fn.bodyTransformY() != 0 && settPos == "fixed") {
    settPos = "absolute";
    $this.n("searchsettings").css("position", "absolute");
  }
  if (settPos == "fixed") {
    bodyTop = 0;
  }
  if (ignoreVisibility || $this.n("prosettings").data("opened") !== "0") {
    let $n, sPosition, top, left, bodyLeft = 0;
    if (external_DoMini_default()._fn.bodyTransformX() != 0 || $body.css("position") != "static") {
      bodyLeft = $body.offset().left;
    }
    $this.fixSettingsWidth();
    if ($this.n("prosettings").css("display") != "none") {
      $n = $this.n("prosettings");
    } else {
      $n = $this.n("promagnifier");
    }
    sPosition = $n.offset();
    top = sPosition.top + $n.height() - 2 - bodyTop + "px";
    left = $this.o.settingsimagepos == "left" ? sPosition.left : sPosition.left + $n.width() - $this.n("searchsettings").width();
    left = left - bodyLeft + "px";
    $this.n("searchsettings").css({
      display: "block",
      top,
      left
    });
  }
};
external_global_namespaceObject.AslPlugin.prototype.fixSettingsWidth = function() {
};
external_global_namespaceObject.AslPlugin.prototype.hideOnInvisibleBox = function() {
  let $this = this;
  if ($this.o.detectVisibility && !$this.n("search").hasClass("hiddend") && ($this.n("search").is(":hidden") || !$this.n("search").is(":visible"))) {
    $this.hideSettings?.();
    $this.hideResults();
  }
};
/* harmony default export */ var position = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/events/button.ts


external_global_namespaceObject.AslPlugin.prototype.initMagnifierEvents = function() {
  let $this = this, t;
  $this.n("promagnifier").on("click", function(e) {
    $this.keycode = e.keyCode || e.which;
    $this.ktype = e.type;
    $this.gaEvent?.("magnifier");
    if ($this.n("text").val().length >= $this.o.charcount && $this.o.redirectOnClick && $this.o.trigger.click !== "first_result") {
      $this.doRedirectToResults("click");
      clearTimeout(t);
      return false;
    }
    if (!($this.o.trigger.click == "ajax_search" || $this.o.trigger.click == "first_result")) {
      return false;
    }
    $this.searchAbort();
    clearTimeout($this.timeouts.search);
    $this.n("proloading").css("display", "none");
    $this.timeouts.search = setTimeout(function() {
      if ($this.n("searchsettings").find("form").serialize() + $this.n("text").val().trim() != $this.lastSuccesfulSearch || !$this.resultsOpened && !$this.usingLiveLoader()) {
        $this.search();
      } else {
        if ($this.isRedirectToFirstResult())
          $this.doRedirectToFirstResult();
        else
          $this.n("proclose").css("display", "block");
      }
    }, $this.o.trigger.delay);
  });
};
/* harmony default export */ var events_button = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/events/input.ts




external_global_namespaceObject.AslPlugin.prototype.initInputEvents = function() {
  let $this = this, initialized = false;
  let initTriggers = function() {
    $this.n("text").off("mousedown touchstart keydown", initTriggers);
    if (!initialized) {
      $this._initFocusInput();
      if ($this.o.trigger.type) {
        $this._initSearchInput();
      }
      $this._initEnterEvent();
      $this._initFormEvent();
      $this.initAutocompleteEvent?.();
      initialized = true;
    }
  };
  $this.n("text").on("mousedown touchstart keydown", initTriggers, { passive: true });
};
external_global_namespaceObject.AslPlugin.prototype._initFocusInput = function() {
  let $this = this;
  $this.n("text").on("click", function(e) {
    e.stopPropagation();
    e.stopImmediatePropagation();
    external_DoMini_default()(this).trigger("focus", []);
    $this.gaEvent?.("focus");
    if ($this.n("searchsettings").find("form").serialize() + $this.n("text").val().trim() == $this.lastSuccesfulSearch) {
      if (!$this.resultsOpened && !$this.usingLiveLoader()) {
        $this.showResults();
      }
      return false;
    }
  });
  $this.n("text").on("focus input", function(e) {
    if ($this.searching) {
      return;
    }
    if (external_DoMini_default()(this).val() != "") {
      $this.n("proclose").css("display", "block");
    } else {
      $this.n("proclose").css({
        display: "none"
      });
    }
  });
};
external_global_namespaceObject.AslPlugin.prototype._initSearchInput = function() {
  let $this = this;
  $this.n("text").on("input", function(e) {
    $this.keycode = e.keyCode || e.which;
    $this.ktype = e.type;
    $this.updateHref();
    if ($this.n("text").val().length < $this.o.charcount) {
      $this.n("proloading").css("display", "none");
      $this.hideResults(false);
      $this.searchAbort();
      clearTimeout($this.timeouts.search);
      return false;
    }
    $this.searchAbort();
    clearTimeout($this.timeouts.search);
    $this.n("proloading").css("display", "none");
    $this.timeouts.search = setTimeout(function() {
      if ($this.n("searchsettings").find("form").serialize() + $this.n("text").val().trim() != $this.lastSuccesfulSearch || !$this.resultsOpened && !$this.usingLiveLoader()) {
        $this.search();
      } else {
        if ($this.isRedirectToFirstResult())
          $this.doRedirectToFirstResult();
        else
          $this.n("proclose").css("display", "block");
      }
    }, $this.o.trigger.delay);
  });
};
external_global_namespaceObject.AslPlugin.prototype._initEnterEvent = function() {
  let $this = this, rt, enterRecentlyPressed = false;
  $this.n("text").on("keyup", function(e) {
    $this.keycode = e.keyCode || e.which;
    $this.ktype = e.type;
    if ($this.keycode == 13) {
      clearTimeout(rt);
      rt = setTimeout(function() {
        enterRecentlyPressed = false;
      }, 300);
      if (enterRecentlyPressed) {
        return false;
      } else {
        enterRecentlyPressed = true;
      }
    }
    let isInput = external_DoMini_default()(this).hasClass("orig");
    if ($this.n("text").val().length >= $this.o.charcount && isInput && $this.keycode == 13) {
      $this.gaEvent?.("return");
      if ($this.o.redirectOnEnter) {
        if ($this.o.trigger.return != "first_result") {
          $this.doRedirectToResults($this.ktype);
        } else {
          $this.search();
        }
      } else if ($this.o.trigger.return == "ajax_search") {
        if ($this.n("searchsettings").find("form").serialize() + $this.n("text").val().trim() != $this.lastSuccesfulSearch || !$this.resultsOpened && !$this.usingLiveLoader()) {
          $this.search();
        }
      }
      clearTimeout($this.timeouts.search);
    }
  });
};
external_global_namespaceObject.AslPlugin.prototype._initFormEvent = function() {
  let $this = this;
  external_DoMini_default()($this.n("text").closest("form").get(0)).on("submit", function(e, args) {
    e.preventDefault();
    if ((0,external_utils_namespaceObject.isMobile)()) {
      if ($this.o.redirectOnEnter) {
        let event = new Event("keyup");
        event.keyCode = event.which = 13;
        $this.n("text").get(0).dispatchEvent(event);
      } else {
        $this.search();
        document?.activeElement?.blur();
      }
    } else if (typeof args != "undefined" && args == "ajax") {
      $this.search();
    }
  });
};

;// ./src/client/plugin/core/events/navigation.ts



external_global_namespaceObject.AslPlugin.prototype.initNavigationEvents = function() {
  let $this = this;
  let handler = function(e) {
    let keycode = e.keyCode || e.which;
    if (
      // @ts-ignore
      external_DoMini_default()(".item", $this.n("resultsDiv")).length > 0 && $this.n("resultsDiv").css("display") != "none" && $this.o.resultstype == "vertical"
    ) {
      if (keycode == 40 || keycode == 38) {
        let $hovered = $this.n("resultsDiv").find(".item.hovered");
        $this.n("text").trigger("blur", []);
        if ($hovered.length == 0) {
          $this.n("resultsDiv").find(".item").first().addClass("hovered");
        } else {
          if (keycode == 40) {
            if ($hovered.next(".item").length == 0) {
              $this.n("resultsDiv").find(".item").removeClass("hovered").first().addClass("hovered");
            } else {
              $hovered.removeClass("hovered").next(".item").addClass("hovered");
            }
          }
          if (keycode == 38) {
            if ($hovered.prev(".item").length == 0) {
              $this.n("resultsDiv").find(".item").removeClass("hovered").last().addClass("hovered");
            } else {
              $hovered.removeClass("hovered").prev(".item").addClass("hovered");
            }
          }
        }
        e.stopPropagation();
        e.preventDefault();
        if (!$this.n("resultsDiv").find(".resdrg .item.hovered").inViewPort(50, $this.n("resultsDiv").get(0))) {
          let n = $this.n("resultsDiv").find(".resdrg .item.hovered").get(0);
          if (n != null && typeof n.scrollIntoView != "undefined") {
            n.scrollIntoView({ behavior: "smooth", block: "start", inline: "nearest" });
          }
        }
      }
      if (keycode == 13 && $this.n("resultsDiv").find(".item.hovered").length > 0) {
        e.stopPropagation();
        e.preventDefault();
        $this.n("resultsDiv").find(".item.hovered a.asl_res_url").get(0).click();
      }
    }
  };
  $this.documentEventHandlers.push({
    "node": document,
    "event": "keydown",
    "handler": handler
  });
  external_DoMini_default()(document).on("keydown", handler);
};

;// ./src/client/plugin/core/events/other.ts




external_global_namespaceObject.AslPlugin.prototype.initOtherEvents = function() {
  let $this = this, handler, handler2;
  if ((0,external_utils_namespaceObject.isMobile)() && (0,external_utils_namespaceObject.detectIOS)()) {
    $this.n("text").on("touchstart", function() {
      $this.savedScrollTop = window.scrollY;
      $this.savedContainerTop = $this.n("search").offset().top;
    });
  }
  $this.n("proclose").on($this.clickTouchend, function(e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    $this.n("text").val("");
    $this.n("textAutocomplete").val("");
    $this.hideResults();
    $this.n("text").trigger("focus", []);
    $this.n("proloading").css("display", "none");
    $this.hideLoader();
    $this.searchAbort();
    if (external_DoMini_default()(".asl_es_" + $this.o.id).length > 0) {
      $this.showLoader();
      $this.liveLoad(".asl_es_" + $this.o.id, $this.getCurrentLiveURL(), false);
    } else if ($this.o.resPage.useAjax) {
      $this.showLoader();
      $this.liveLoad($this.o.resPage.selector, $this.getRedirectURL());
    }
    $this.n("text").get(0).focus();
  });
  if ((0,external_utils_namespaceObject.isMobile)()) {
    handler = function() {
      $this.orientationChange();
      setTimeout(function() {
        $this.orientationChange();
      }, 600);
    };
    $this.documentEventHandlers.push({
      "node": window,
      "event": "orientationchange",
      "handler": handler
    });
    external_DoMini_default()(window).on("orientationchange", handler);
  } else {
    handler = function() {
      $this.resize();
    };
    $this.documentEventHandlers.push({
      "node": window,
      "event": "resize",
      "handler": handler
    });
    external_DoMini_default()(window).on("resize", handler, { passive: true });
  }
  handler2 = function() {
    $this.scrolling(false);
  };
  $this.documentEventHandlers.push({
    "node": window,
    "event": "scroll",
    "handler": handler2
  });
  external_DoMini_default()(window).on("scroll", handler2, { passive: true });
  if ((0,external_utils_namespaceObject.isMobile)() && $this.o.mobile.menu_selector != "") {
    external_DoMini_default()($this.o.mobile.menu_selector).on("touchend", function() {
      let _this = this;
      setTimeout(function() {
        let $input = external_DoMini_default()(_this).find("input.orig");
        $input = $input.length == 0 ? external_DoMini_default()(_this).next().find("input.orig") : $input;
        $input = $input.length == 0 ? external_DoMini_default()(_this).parent().find("input.orig") : $input;
        $input = $input.length == 0 ? $this.n("text") : $input;
        if ($this.n("search").inViewPort()) {
          $input.get(0).focus();
        }
      }, 300);
    });
  }
  if ((0,external_utils_namespaceObject.detectIOS)() && (0,external_utils_namespaceObject.isMobile)() && (0,external_utils_namespaceObject.isTouchDevice)()) {
    if (parseInt($this.n("text").css("font-size")) < 16) {
      $this.n("text").data("fontSize", $this.n("text").css("font-size")).css("font-size", "16px");
      $this.n("textAutocomplete").css("font-size", "16px");
      external_DoMini_default()("body").append("<style>#ajaxsearchlite" + $this.o.rid + " input.orig::-webkit-input-placeholder{font-size: 16px !important;}</style>");
    }
  }
};
external_global_namespaceObject.AslPlugin.prototype.orientationChange = function() {
  let $this = this;
  $this.detectAndFixFixedPositioning();
  $this.fixSettingsPosition();
  $this.fixResultsPosition();
};
external_global_namespaceObject.AslPlugin.prototype.resize = function() {
  let $this = this;
  $this.detectAndFixFixedPositioning();
  $this.fixSettingsPosition();
  $this.fixResultsPosition();
};
external_global_namespaceObject.AslPlugin.prototype.scrolling = function(ignoreVisibility) {
  let $this = this;
  $this.detectAndFixFixedPositioning();
  $this.hideOnInvisibleBox();
  $this.fixSettingsPosition(ignoreVisibility);
  $this.fixResultsPosition(ignoreVisibility);
};

;// ./src/client/plugin/core/events/results.ts



external_global_namespaceObject.AslPlugin.prototype.initResultsEvents = function() {
  let $this = this;
  $this.n("resultsDiv").css({
    opacity: "0"
  });
  let handler = function(e) {
    let keycode = e.keyCode || e.which, ktype = e.type;
    if (external_DoMini_default()(e.target).closest(".asl_w").length == 0) {
      $this.hideOnInvisibleBox();
      if (ktype != "click" || ktype != "touchend" || keycode != 3) {
        if (!$this.resultsOpened || !$this.o.closeOnDocClick) return;
        if (!$this.dragging) {
          $this.hideLoader();
          $this.searchAbort();
          $this.hideResults();
        }
      }
    }
  };
  $this.documentEventHandlers.push({
    "node": document,
    "event": $this.clickTouchend,
    "handler": handler
  });
  external_DoMini_default()(document).on($this.clickTouchend, handler);
  $this.n("resultsDiv").on("click", ".results .item", function() {
    $this.gaEvent?.("result_click", {
      "result_title": external_DoMini_default()(this).find("a.asl_res_url").text(),
      "result_url": external_DoMini_default()(this).find("a.asl_res_url").attr("href")
    });
  });
};
/* harmony default export */ var events_results = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/events/touch.ts



external_global_namespaceObject.AslPlugin.prototype.monitorTouchMove = function() {
  let $this = this;
  $this.dragging = false;
  external_DoMini_default()("body").on("touchmove", function() {
    $this.dragging = true;
  }).on("touchstart", function() {
    $this.dragging = false;
  });
};
/* harmony default export */ var touch = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/init/init.ts



external_global_namespaceObject.AslPlugin.prototype.init = function(options, elem) {
  this.o = { ...this.o, ...options };
  this.nodes = {};
  this.nodes.search = external_DoMini_default()(elem);
  this.initNodeVariables();
  this.o.redirectOnClick = this.o.trigger.click != "ajax_search" && this.o.trigger.click != "nothing";
  this.o.redirectOnEnter = this.o.trigger.return != "ajax_search" && this.o.trigger.return != "nothing";
  if (this.usingLiveLoader()) {
    this.o.trigger.type = this.o.resPage.trigger_type;
    this.o.trigger.facet = this.o.resPage.trigger_facet;
    if (this.o.resPage.trigger_magnifier) {
      this.o.redirectOnClick = false;
      this.o.trigger.click = "ajax_search";
    }
    if (this.o.resPage.trigger_return) {
      this.o.redirectOnEnter = false;
      this.o.trigger.return = "ajax_search";
    }
  }
  this.monitorTouchMove();
  this.initEvents();
  this.n("s").trigger("asl_init_search_bar", [this.o.id, this.o.iid], true, true);
  return this;
};
external_global_namespaceObject.AslPlugin.prototype.n = function(k) {
  if (typeof this.nodes[k] !== "undefined") {
    return this.nodes[k];
  } else {
    switch (k) {
      case "s":
        this.nodes[k] = this.nodes.search;
        break;
      case "container":
        this.nodes[k] = this.nodes.search.closest(".asl_w_container");
        break;
      case "searchsettings":
        this.nodes[k] = this.n("container").find(".asl_s");
        break;
      case "resultsDiv":
        this.nodes[k] = this.n("container").find(".asl_r");
        break;
      case "probox":
        this.nodes[k] = this.nodes.search.find(".probox");
        break;
      case "proinput":
        this.nodes[k] = this.nodes.search.find(".proinput");
        break;
      case "text":
        this.nodes[k] = this.nodes.search.find(".proinput input.orig");
        break;
      case "textAutocomplete":
        this.nodes[k] = this.nodes.search.find(".proinput input.autocomplete");
        break;
      case "proloading":
        this.nodes[k] = this.nodes.search.find(".proloading");
        break;
      case "proclose":
        this.nodes[k] = this.nodes.search.find(".proclose");
        break;
      case "promagnifier":
        this.nodes[k] = this.nodes.search.find(".promagnifier");
        break;
      case "prosettings":
        this.nodes[k] = this.nodes.search.find(".prosettings");
        break;
      case "settingsAppend":
        this.nodes[k] = external_DoMini_default()("#wpdreams_asl_settings_" + this.o.id);
        break;
      case "resultsAppend":
        this.nodes[k] = external_DoMini_default()("#wpdreams_asl_results_" + this.o.id);
        break;
      case "trythis":
        this.nodes[k] = external_DoMini_default()("#asp-try-" + this.o.rid);
        break;
      case "hiddenContainer":
        this.nodes[k] = this.n("container").find(".asl_hidden_data");
        break;
      case "aspItemOverlay":
        this.nodes[k] = this.n("hiddenContainer").find(".asl_item_overlay");
        break;
      case "showmore":
        this.nodes[k] = this.n("resultsDiv").find(".showmore");
        break;
      case "items":
        this.nodes[k] = this.n("resultsDiv").find(".item").length > 0 ? this.n("resultsDiv").find(".item") : this.n("resultsDiv").find(".photostack-flip");
        break;
      case "results":
        this.nodes[k] = this.n("resultsDiv").find(".results");
        break;
      case "resdrg":
        this.nodes[k] = this.n("resultsDiv").find(".resdrg");
        break;
    }
    return this.nodes[k];
  }
};
external_global_namespaceObject.AslPlugin.prototype.initNodeVariables = function() {
  let $this = this;
  $this.o.id = parseInt($this.nodes.search.data("id"));
  $this.o.iid = parseInt($this.nodes.search.data("instance"));
  $this.o.rid = $this.o.id + "_" + $this.o.iid;
  $this.fixClonedSelf();
};
external_global_namespaceObject.AslPlugin.prototype.initEvents = function() {
  this.initSettingsSwitchEvents?.();
  this.initOtherEvents();
  this.initMagnifierEvents();
  this.initInputEvents();
};
/* harmony default export */ var init = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/init/results.ts




external_global_namespaceObject.AslPlugin.prototype.initResults = function() {
  if (!this.resultsInitialized) {
    this.initResultsBox();
    this.initResultsEvents();
    this.initNavigationEvents?.();
  }
};
external_global_namespaceObject.AslPlugin.prototype.initResultsBox = function() {
  let $this = this;
  $this.initResultsAnimations();
  if ((0,external_utils_namespaceObject.isMobile)() && $this.o.mobile.force_res_hover == 1) {
    $this.o.resultsposition = "hover";
    $this.nodes.resultsDiv = $this.n("resultsDiv").clone();
    external_DoMini_default()("body").append($this.nodes.resultsDiv);
    $this.nodes.resultsDiv.css({
      "position": "absolute"
    });
    $this.detectAndFixFixedPositioning();
  } else {
    if ($this.o.resultsposition == "hover" && $this.n("resultsAppend").length <= 0) {
      $this.nodes.resultsDiv = $this.n("resultsDiv").clone();
      external_DoMini_default()("body").append($this.n("resultsDiv"));
    } else {
      $this.o.resultsposition = "block";
      $this.n("resultsDiv").css({
        "position": "static"
      });
      if ($this.n("resultsAppend").length > 0) {
        if ($this.n("resultsAppend").find(".asl_w").length > 0) {
          $this.nodes.resultsDiv = $this.n("resultsAppend").find(".asl_w");
        } else {
          $this.nodes.resultsDiv = $this.n("resultsDiv").clone();
          $this.nodes.resultsAppend.append($this.n("resultsDiv"));
        }
      }
    }
  }
  $this.nodes.showmore = $this.n("resultsDiv").find(".showmore");
  $this.nodes.items = $this.n("resultsDiv").find(".item").length > 0 ? $this.n("resultsDiv").find(".item") : $this.n("resultsDiv").find(".photostack-flip");
  $this.nodes.results = $this.n("resultsDiv").find(".results");
  $this.nodes.resdrg = $this.n("resultsDiv").find(".resdrg");
  $this.n("resultsDiv").get(0).id = $this.n("resultsDiv").get(0).id.replace("__original__", "");
  $this.detectAndFixFixedPositioning();
  $this.resultsInitialized = true;
};
external_global_namespaceObject.AslPlugin.prototype.initResultsAnimations = function() {
  this.n("resultsDiv").css({
    "-webkit-animation-duration": this.resAnim.duration + "ms",
    "animation-duration": this.resAnim.duration + "ms"
  });
};
/* harmony default export */ var init_results = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/bundle/optimized/core.ts




















Object(window.WPD).AjaxSearchLite = __webpack_exports__["default"];
/******/ })()
;