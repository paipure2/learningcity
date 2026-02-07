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
;// ./src/client/plugin/core/actions/settings.ts


external_global_namespaceObject.AslPlugin.prototype.showSettings = function() {
  let $this = this;
  $this.initSettings?.();
  $this.n("searchsettings").css($this.settAnim.showCSS);
  $this.n("searchsettings").removeClass($this.settAnim.hideClass).addClass($this.settAnim.showClass);
  $this.n("prosettings").data("opened", 1);
  $this.fixSettingsPosition(true);
};
external_global_namespaceObject.AslPlugin.prototype.hideSettings = function() {
  let $this = this;
  $this.initSettings?.();
  $this.n("searchsettings").removeClass($this.settAnim.showClass).addClass($this.settAnim.hideClass);
  setTimeout(function() {
    $this.n("searchsettings").css($this.settAnim.hideCSS);
  }, $this.settAnim.duration);
  $this.n("prosettings").data("opened", 0);
};
/* harmony default export */ var settings = ((/* unused pure expression or super */ null && (AslPlugin)));

;// external "DoMini"
var external_DoMini_namespaceObject = Object(window.WPD)["DoMini"];
var external_DoMini_default = /*#__PURE__*/__webpack_require__.n(external_DoMini_namespaceObject);
;// ./src/client/plugin/core/events/facet.ts



external_global_namespaceObject.AslPlugin.prototype.initFacetEvents = function() {
  let $this = this;
  if (!$this.o.trigger.facet) {
    return;
  }
  $this.n("searchsettings").find("input[type=checkbox]").on("asl_chbx_change", function(e) {
    $this.ktype = e.type;
    $this.n("searchsettings").find("input[name=filters_changed]").val(1);
    $this.gaEvent?.("facet_change", {
      "option_label": external_DoMini_default()(this).closest("fieldset").find("legend").text(),
      "option_value": external_DoMini_default()(this).closest(".asl_option").find(".asl_option_label").text() + (external_DoMini_default()(this).prop("checked") ? "(checked)" : "(unchecked)")
    });
    $this.setFilterStateInput(65);
    $this.searchWithCheck(80);
  });
};

;// external "utils"
var external_utils_namespaceObject = Object(window.WPD)["utils"];
;// ./src/client/plugin/core/events/settings.ts




external_global_namespaceObject.AslPlugin.prototype.initSettingsSwitchEvents = function() {
  let $this = this;
  $this.n("prosettings").on("click", function() {
    if ($this.n("prosettings").data("opened") === "0") {
      $this.showSettings();
    } else {
      $this.hideSettings();
    }
  });
  if ($this.o.settingsVisible == 1) {
    $this.showSettings();
  }
};
external_global_namespaceObject.AslPlugin.prototype.initSettingsEvents = function() {
  let $this = this, t;
  let formDataHandler = function() {
    if (typeof $this.originalFormData === "undefined") {
      $this.originalFormData = (0,external_utils_namespaceObject.formData)($this.n("searchsettings").find("form"));
    }
    $this.n("searchsettings").off("mousedown touchstart mouseover", formDataHandler);
  };
  $this.n("searchsettings").on("mousedown touchstart mouseover", formDataHandler);
  let handler = function(e) {
    if (external_DoMini_default()(e.target).closest(".asl_w").length == 0) {
      if (!$this.dragging) {
        $this.hideSettings?.();
      }
    }
  };
  $this.documentEventHandlers.push({
    "node": document,
    "event": $this.clickTouchend,
    "handler": handler
  });
  external_DoMini_default()(document).on($this.clickTouchend, handler);
  $this.n("searchsettings").on("click", function() {
    $this.settingsChanged = true;
  });
  $this.n("searchsettings").on($this.clickTouchend, function(e) {
    $this.updateHref();
    if (typeof e.target != "undefined" && !external_DoMini_default()(e.target).hasClass("noUi-handle")) {
      e.stopImmediatePropagation();
    } else {
      if (e.type == "click")
        e.stopImmediatePropagation();
    }
  });
  $this.n("searchsettings").find("div.asl_option").on($this.mouseupTouchend, function(e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    if ($this.dragging) {
      return false;
    }
    external_DoMini_default()(this).find('input[type="checkbox"]').prop("checked", !external_DoMini_default()(this).find('input[type="checkbox"]').prop("checked"));
    clearTimeout(t);
    let _this = this;
    t = setTimeout(function() {
      external_DoMini_default()(_this).find('input[type="checkbox"]').trigger("asl_chbx_change", []);
    }, 50);
  });
  $this.n("searchsettings").find("div.asl_option label").on("click", function(e) {
    e.preventDefault();
  });
  $this.n("searchsettings").find("fieldset.asl_checkboxes_filter_box").forEach(function() {
    let all_unchecked = true;
    external_DoMini_default()(this).find('.asl_option:not(.asl_option_selectall) input[type="checkbox"]').forEach(function() {
      if (external_DoMini_default()(this).prop("checked") == true) {
        all_unchecked = false;
        return false;
      }
    });
    if (all_unchecked) {
      external_DoMini_default()(this).find('.asl_option_selectall input[type="checkbox"]').prop("checked", false).removeAttr("data-origvalue");
    }
  });
  $this.n("searchsettings").find("fieldset").forEach(function() {
    external_DoMini_default()(this).find(".asl_option:not(.hiddend)").last().addClass("asl-o-last");
  });
  $this.n("searchsettings").find('.asl_option_cat input[type="checkbox"], .asl_option_cff input[type="checkbox"]').on(
    "asl_chbx_change",
    function() {
      let className = external_DoMini_default()(this).data("targetclass");
      if (typeof className == "string" && className != "")
        $this.n("searchsettings").find("input." + className).prop("checked", external_DoMini_default()(this).prop("checked"));
    }
  );
};
/* harmony default export */ var events_settings = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/init/settings.ts




external_global_namespaceObject.AslPlugin.prototype.initSettings = function() {
  if (!this.settingsInitialized) {
    this.loadASLFonts?.();
    this.initSettingsBox?.();
    this.initSettingsEvents?.();
    this.initFacetEvents?.();
  }
};
external_global_namespaceObject.AslPlugin.prototype.initSettingsBox = function() {
  let $this = this;
  let appendSettingsTo = function($el) {
    let old = $this.n("searchsettings").get(0);
    $this.nodes.searchsettings = $this.n("searchsettings").clone();
    $el.append($this.n("searchsettings"));
    external_DoMini_default()(old).find("*[id]").forEach(function(el) {
      if (el === void 0) {
        return;
      }
      if (el.id.indexOf("__original__") < 0) {
        el.id = "__original__" + el.id;
      }
    });
    $this.n("searchsettings").find("*[id]").forEach(function(el) {
      if (el === void 0) {
        return;
      }
      if (el.id.indexOf("__original__") > -1) {
        el.id = el.id.replace("__original__", "");
      }
    });
  };
  $this.initSettingsAnimations?.();
  appendSettingsTo(external_DoMini_default()("body"));
  $this.n("searchsettings").get(0).id = $this.n("searchsettings").get(0).id.replace("__original__", "");
  $this.detectAndFixFixedPositioning();
  $this.settingsInitialized = true;
};
external_global_namespaceObject.AslPlugin.prototype.initSettingsAnimations = function() {
  let $this = this;
  const animOptions = (0,external_utils_namespaceObject.isMobile)() ? $this.o.animations.mob : $this.o.animations.pc;
  $this.settAnim.duration = animOptions.settings.dur;
  $this.settAnim.showCSS["animation-duration"] = animOptions.settings.dur + "ms";
  if (animOptions.settings.anim === "fade") {
    $this.settAnim.showClass = "asl_an_fadeIn";
    $this.settAnim.hideClass = "asl_an_fadeOut";
  }
  if (animOptions.settings.anim === "fadedrop" && !$this.o.blocking) {
    $this.settAnim.showClass = "asl_an_fadeInDrop";
    $this.settAnim.hideClass = "asl_an_fadeOutDrop";
  } else if (animOptions.settings.anim === "fadedrop") {
    $this.settAnim.showClass = "asl_an_fadeIn";
    $this.settAnim.hideClass = "asl_an_fadeOut";
  }
  $this.n("searchsettings").css({
    "-webkit-animation-duration": $this.settAnim.duration + "ms",
    "animation-duration": $this.settAnim.duration + "ms"
  });
};
/* harmony default export */ var init_settings = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/bundle/optimized/settings.ts






Object(window.WPD).AjaxSearchLite = __webpack_exports__["default"];
/******/ })()
;