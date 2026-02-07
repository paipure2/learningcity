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
;// ./src/client/plugin/core/actions/results_vertical.ts



external_global_namespaceObject.AslPlugin.prototype.showVerticalResults = function() {
  let $this = this;
  $this.showResultsBox();
  if ($this.n("items").length > 0) {
    let count = $this.n("items").length < $this.o.itemscount ? $this.n("items").length : $this.o.itemscount;
    count = count <= 0 ? 9999 : count;
    let groups = $this.n("resultsDiv").find(".asl_group_header");
    if ($this.o.itemscount == 0 || $this.n("items").length <= $this.o.itemscount) {
      $this.n("results").css({
        height: "auto"
      });
    } else {
      if ($this.call_num < 1)
        $this.n("results").css({
          height: "30px"
        });
      if ($this.call_num < 1) {
        let i = 0, h = 0, final_h = 0, highest = 0;
        $this.n("items").forEach(function(el) {
          h += external_DoMini_default()(el).outerHeight(true);
          if (external_DoMini_default()(el).outerHeight(true) > highest)
            highest = external_DoMini_default()(el).outerHeight(true);
          i++;
        });
        final_h = highest * count;
        if (final_h > h)
          final_h = h;
        i = i < 1 ? 1 : i;
        h = h / i * count;
        if (groups.length > 0) {
          groups.forEach(function(el, index) {
            if (!index || !el || !el.parentNode) {
              return;
            }
            let position = Array.prototype.slice.call(el.parentNode.children).indexOf(el), group_position = position - index - Math.floor(position / 3);
            if (group_position < count) {
              final_h += external_DoMini_default()(el).outerHeight(true);
            }
          });
        }
        $this.n("results").css({
          height: final_h + "px"
        });
      }
    }
    $this.n("items").last().addClass("asl_last_item");
    $this.n("results").find(".asl_group_header").prev(".item").addClass("asl_last_item");
    if ($this.o.highlight) {
      $this.n("resultsDiv").find("div.item").highlight($this.n("text").val().split(" "), {
        element: "span",
        className: "highlighted",
        wordsOnly: $this.o.highlightWholewords
      });
    }
  }
  $this.resize();
  if ($this.n("items").length == 0) {
    $this.n("results").css({
      height: "auto"
    });
  }
  $this.n("results").css({
    "overflowY": "auto"
  });
  const firstRes = $this.n("results").get(0);
  if (firstRes) {
    firstRes.scrollTop = 0;
  }
  $this.fixResultsPosition(true);
  $this.searching = false;
};
/* harmony default export */ var results_vertical = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/bundle/optimized/results-vertical.ts



Object(window.WPD).AjaxSearchLite = __webpack_exports__["default"];
/******/ })()
;