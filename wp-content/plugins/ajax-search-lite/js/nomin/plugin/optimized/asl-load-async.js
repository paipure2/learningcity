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
;// ./src/client/plugin/core/AjaxSearchLite.ts


const AjaxSearchLite_AjaxSearchLite = {
  plugin: new external_global_namespaceObject.AslPlugin(),
  addons: {
    addons: [],
    add(addon) {
      if (this.addons.indexOf(addon) === -1) {
        const k = this.addons.push(addon);
        this.addons[k - 1].init();
      }
    },
    remove(name) {
      this.addons = this.addons.filter((addon) => {
        if (addon.name === name) {
          if (typeof addon.destroy !== "undefined") {
            addon.destroy();
          }
          return false;
        }
        return true;
      });
    }
  }
};
/* harmony default export */ var core_AjaxSearchLite = (AjaxSearchLite_AjaxSearchLite);

;// external "utils"
var external_utils_namespaceObject = Object(window.WPD)["utils"];
;// ./src/client/addons/woocommerce.ts



class WooCommerceAddToCartAddon {
  name = "WooCommerce Add To Cart";
  requests = [];
  $liveRegion = void 0;
  init() {
    external_utils_namespaceObject.Hooks.addFilter("asl/search/end", this.finished.bind(this), 10, this);
  }
  finished($this) {
    if (typeof window.wc_add_to_cart_params === "undefined" || typeof jQuery === "undefined") {
      return;
    }
    this.addRequest = this.addRequest.bind(this);
    this.run = this.run.bind(this);
    this.$liveRegion = this.createLiveRegion();
    jQuery($this.n("resdrg").get(0)).find(".add-to-cart-button:not(.wc-interactive)").off().on("click", { addToCartHandler: this }, this.onAddToCart);
  }
  /**
   * Add add-to-cart event to the queue.
   */
  addRequest(request) {
    this.requests.push(request);
    if (this.requests.length === 1) {
      this.run();
    }
  }
  /**
   * Run add-to-cart events in sequence.
   */
  run() {
    const requestManager = this;
    const originalCallback = requestManager.requests[0].complete;
    requestManager.requests[0].complete = function() {
      if (typeof originalCallback === "function") {
        originalCallback();
      }
      requestManager.requests.shift();
      if (requestManager.requests.length > 0) {
        requestManager.run();
      }
    };
    jQuery.ajax(this.requests[0]);
  }
  /**
   * Handle the add to cart event.
   */
  onAddToCart(e) {
    if (typeof window.wc_add_to_cart_params === "undefined" || typeof jQuery === "undefined") {
      return;
    }
    const $thisbutton = jQuery(this);
    if ($thisbutton.is(".ajax-add-to-cart")) {
      if (!$thisbutton.attr("data-product_id")) {
        return true;
      }
      e.data.addToCartHandler.$liveRegion.text("").removeAttr("aria-relevant");
      e.preventDefault();
      $thisbutton.removeClass("added");
      $thisbutton.addClass("loading");
      if (false === jQuery(document.body).triggerHandler("should_send_ajax_request.adding_to_cart", [$thisbutton])) {
        jQuery(document.body).trigger("ajax_request_not_sent.adding_to_cart", [false, false, $thisbutton]);
        return true;
      }
      const data = {};
      jQuery.each($thisbutton.data(), function(key, value) {
        data[key] = value;
      });
      jQuery.each($thisbutton[0].dataset, function(key, value) {
        data[key] = value;
      });
      const $quantityButton = $thisbutton.closest(".add-to-cart-container").find(".add-to-cart-quantity");
      if ($quantityButton.length > 0) {
        data.quantity = $quantityButton.get(0).value;
      }
      jQuery(document.body).trigger("adding_to_cart", [$thisbutton, data]);
      e.data.addToCartHandler.addRequest({
        type: "POST",
        url: window.wc_add_to_cart_params.wc_ajax_url.toString().replace("%%endpoint%%", "add_to_cart"),
        data,
        success: function(response) {
          if (!response) {
            return;
          }
          if (response.error && response.product_url) {
            window.location = response.product_url;
            return;
          }
          if (typeof window.wc_add_to_cart_params === "undefined" || typeof jQuery === "undefined") {
            return;
          }
          if (window.wc_add_to_cart_params.cart_redirect_after_add === "yes") {
            window.location = window.wc_add_to_cart_params.cart_url;
            return;
          }
          jQuery(document.body).trigger("added_to_cart", [response.fragments, response.cart_hash, $thisbutton]);
        },
        dataType: "json"
      });
    }
  }
  /**
   * Add live region into the body element.
   */
  createLiveRegion() {
    const existingLiveRegion = jQuery(".widget_shopping_cart_live_region");
    if (existingLiveRegion.length) {
      return existingLiveRegion;
    }
    return jQuery('<div class="widget_shopping_cart_live_region screen-reader-text" role="status"></div>').appendTo("body");
  }
}
core_AjaxSearchLite.addons.add(new WooCommerceAddToCartAddon());
/* harmony default export */ var woocommerce = ((/* unused pure expression or super */ null && (AjaxSearchLite)));

;// ./src/client/addons/divi.ts


class DiviAddon {
  name = "Divi Addon";
  init() {
    if (window.DiviArea !== void 0) {
      window.DiviArea.addAction("click_overlay", () => window.ASL.api(0, "closeResults"));
    }
  }
}
core_AjaxSearchLite.addons.add(new DiviAddon());
/* harmony default export */ var divi = ((/* unused pure expression or super */ null && (AjaxSearchLite)));

;// ./src/client/bundle/optimized/load-async.ts





window.WPD.AjaxSearchLite = core_AjaxSearchLite;
external_DoMini_default()._fn.plugin("ajaxsearchlite", core_AjaxSearchLite.plugin);

Object(window.WPD).AjaxSearchLite = __webpack_exports__["default"];
/******/ })()
;