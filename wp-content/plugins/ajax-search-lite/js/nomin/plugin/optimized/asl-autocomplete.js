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

;// external "DoMini"
var external_DoMini_namespaceObject = Object(window.WPD)["DoMini"];
var external_DoMini_default = /*#__PURE__*/__webpack_require__.n(external_DoMini_namespaceObject);
;// external "global"
var external_global_namespaceObject = Object(window.WPD)["global"];
;// ./src/client/plugin/core/actions/autocomplete.ts



"use strict";
external_global_namespaceObject.AslPlugin.prototype.autocompleteGoogleOnly = function() {
  const $this = this, val = String($this.n("text").val());
  if ($this.n("text").val() == "") {
    $this.n("textAutocomplete").val("");
    return;
  }
  let autocompleteVal = String($this.n("textAutocomplete").val());
  if (autocompleteVal != "" && autocompleteVal.indexOf(val) == 0) {
    return;
  } else {
    $this.n("textAutocomplete").val("");
  }
  let lang = $this.o.autocomplete.lang;
  ["wpml_lang", "polylang_lang", "qtranslate_lang"].forEach(function(v) {
    if ($this.n("searchsettings").find('input[name="' + v + '"]').length > 0 && String($this.n("searchsettings").find('input[name="' + v + '"]').val()).length > 1) {
      lang = String($this.n("searchsettings").find('input[name="' + v + '"]').val());
    }
  });
  if (String($this.n("text").val()).length >= $this.o.autocomplete.trigger_charcount) {
    external_DoMini_default().fn.ajax({
      url: "https://clients1.google.com/complete/search",
      cors: "no-cors",
      data: {
        q: val,
        hl: lang,
        nolabels: "t",
        client: "hp",
        ds: ""
      },
      success: function(data) {
        if (data[1].length > 0) {
          let response = data[1][0][0].replace(/(<([^>]+)>)/ig, "");
          response = external_DoMini_default()("<textarea />").html(response).text();
          response = response.substr(val.length);
          $this.n("textAutocomplete").val(val + response);
        }
      }
    });
  }
};
external_global_namespaceObject.AslPlugin.prototype.fixAutocompleteScrollLeft = function() {
  const autoCompleteEl = this.n("textAutocomplete").get(0);
  if (autoCompleteEl === void 0) {
    console.warn("textAutocomplete missing");
    return;
  }
  autoCompleteEl.scrollLeft = this.n("text").get(0)?.scrollLeft ?? 0;
};
/* harmony default export */ var autocomplete = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/events/autocomplete.ts



external_global_namespaceObject.AslPlugin.prototype.initAutocompleteEvent = function() {
  let $this = this;
  if (!$this.o.autocomplete.enabled) {
    return;
  }
  $this.n("text").on("keyup", function(e) {
    $this.keycode = e.keyCode || e.which;
    $this.ktype = e.type;
    let thekey = 39;
    if (external_DoMini_default()("body").hasClass("rtl"))
      thekey = 37;
    if ($this.keycode === thekey && $this.n("textAutocomplete").val() !== "") {
      e.preventDefault();
      $this.n("text").val($this.n("textAutocomplete").val());
      if ($this.post != null) $this.post.abort();
      $this.search();
    } else {
      if ($this.postAuto != null) $this.postAuto.abort();
      $this.autocompleteGoogleOnly();
    }
  });
  $this.n("text").on("keyup mouseup input blur select", function() {
    $this.fixAutocompleteScrollLeft();
  });
};
/* harmony default export */ var events_autocomplete = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/bundle/optimized/autocomplete.ts




Object(window.WPD).AjaxSearchLite = __webpack_exports__["default"];
/******/ })()
;