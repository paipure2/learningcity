/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ 91:
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Hooks: function() { return /* binding */ Hooks; }
/* harmony export */ });

const Hooks = {
  filters: {},
  /**
   * Adds a callback function to a specific programmatically triggered tag (hook)
   *
   * @param tag - the hook name
   * @param callback - the callback function variable name
   * @param priority - (optional) default=10
   * @param scope - (optional) function scope. When a function is executed within an object scope, the object variable should be passed.
   */
  addFilter: function(tag, callback, priority = 10, scope = null) {
    this.filters[tag] = this.filters[tag] || [];
    this.filters[tag].push({ priority, scope, callback });
  },
  /**
   * Removes a callback function from a hook
   *
   * @param tag - the hook name
   * @param callback - the callback function variable
   */
  removeFilter: function(tag, callback) {
    if (typeof this.filters[tag] != "undefined") {
      if (typeof callback == "undefined") {
        this.filters[tag] = [];
      } else {
        const _this = this;
        this.filters[tag].forEach(function(filter, i) {
          if (filter.callback === callback) {
            _this.filters[tag].splice(i, 1);
          }
        });
      }
    }
  },
  applyFilters: function(tag) {
    let filters = [], args = Array.prototype.slice.call(arguments), value = arguments[1];
    if (typeof this.filters[tag] !== "undefined" && this.filters[tag].length > 0) {
      this.filters[tag].forEach(function(hook) {
        filters[hook.priority] = filters[hook.priority] || [];
        filters[hook.priority].push({
          scope: hook.scope,
          callback: hook.callback
        });
      });
      args.splice(0, 2);
      filters.forEach(function(hooks) {
        hooks.forEach(function(obj) {
          value = obj.callback.apply(obj.scope, [value].concat(args));
        });
      });
    }
    return value;
  }
};


/***/ }),

/***/ 271:
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./base64.ts": 806,
	"./browser.ts": 665,
	"./device.ts": 451,
	"./hooks-filters.ts": 91,
	"./index.ts": 685,
	"./interval-until-execute.ts": 919,
	"./other.ts": 627
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = 271;

/***/ }),

/***/ 451:
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   detectIOS: function() { return /* binding */ detectIOS; },
/* harmony export */   deviceType: function() { return /* binding */ deviceType; },
/* harmony export */   isMobile: function() { return /* binding */ isMobile; },
/* harmony export */   isTouchDevice: function() { return /* binding */ isTouchDevice; }
/* harmony export */ });

const deviceType = () => {
  let w = window.innerWidth;
  if (w <= 640) {
    return "phone";
  } else if (w <= 1024) {
    return "tablet";
  } else {
    return "desktop";
  }
};
const detectIOS = () => {
  if (typeof window.navigator != "undefined" && typeof window.navigator.userAgent != "undefined")
    return window.navigator.userAgent.match(/(iPod|iPhone|iPad)/) != null;
  return false;
};
const isMobile = () => {
  try {
    document.createEvent("TouchEvent");
    return true;
  } catch (e) {
    return false;
  }
};
const isTouchDevice = () => {
  return "ontouchstart" in window;
};


/***/ }),

/***/ 627:
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isNumeric: function() { return /* binding */ isNumeric; },
/* harmony export */   nicePhrase: function() { return /* binding */ nicePhrase; }
/* harmony export */ });

const nicePhrase = function(s) {
  return encodeURIComponent(s).replace(/\%20/g, "+");
};
function isNumeric(input) {
  if (typeof input === "number" && !isNaN(input)) {
    return true;
  }
  if (typeof input === "string") {
    const trimmed = input.trim();
    if (trimmed === "") return false;
    const num = Number(trimmed);
    return !isNaN(num) && trimmed === String(num).trim();
  }
  return false;
}


/***/ }),

/***/ 665:
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   formData: function() { return /* binding */ formData; },
/* harmony export */   isSafari: function() { return /* binding */ isSafari; },
/* harmony export */   openInNewTab: function() { return /* binding */ openInNewTab; },
/* harmony export */   submitToUrl: function() { return /* binding */ submitToUrl; },
/* harmony export */   whichjQuery: function() { return /* binding */ whichjQuery; }
/* harmony export */ });
/* harmony import */ var domini__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(993);
/* harmony import */ var domini__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(domini__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _interval_until_execute__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(919);



const isSafari = () => {
  return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
};
const whichjQuery = (plugin) => {
  let jq = false;
  if (typeof window.$ != "undefined") {
    if (typeof plugin === "undefined") {
      jq = window.$;
    } else {
      if (typeof window.$.fn[plugin] != "undefined") {
        jq = window.$;
      }
    }
  }
  if (jq === false && typeof window.jQuery != "undefined") {
    jq = window.jQuery;
    if (typeof plugin === "undefined") {
      jq = window.jQuery;
    } else {
      if (typeof window.jQuery.fn[plugin] != "undefined") {
        jq = window.jQuery;
      }
    }
  }
  return jq;
};
const formData = function(form, d) {
  let els = form.find("input,textarea,select,button").get();
  if (arguments.length === 1) {
    const data = {};
    els.forEach(function(el) {
      if (el.name && !el.disabled && (el.checked || /select|textarea/i.test(el.nodeName) || /text/i.test(el.type) || domini__WEBPACK_IMPORTED_MODULE_0___default()(el).hasClass("hasDatepicker") || domini__WEBPACK_IMPORTED_MODULE_0___default()(el).hasClass("asp_slider_hidden"))) {
        if (data[el.name] === void 0) {
          data[el.name] = [];
        }
        if (domini__WEBPACK_IMPORTED_MODULE_0___default()(el).hasClass("hasDatepicker")) {
          data[el.name].push(domini__WEBPACK_IMPORTED_MODULE_0___default()(el).parent().find(".asp_datepicker_hidden").val());
        } else {
          data[el.name].push(domini__WEBPACK_IMPORTED_MODULE_0___default()(el).val());
        }
      }
    });
    return JSON.stringify(data);
  } else if (d !== void 0) {
    const data = typeof d != "object" ? JSON.parse(d) : d;
    els.forEach(function(el) {
      if (el.name) {
        if (data[el.name]) {
          let names = data[el.name], _this = domini__WEBPACK_IMPORTED_MODULE_0___default()(el);
          if (Object.prototype.toString.call(names) !== "[object Array]") {
            names = [names];
          }
          if (el.type === "checkbox" || el.type === "radio") {
            let val = _this.val(), found = false;
            for (let i = 0; i < names.length; i++) {
              if (names[i] === val) {
                found = true;
                break;
              }
            }
            _this.prop("checked", found);
          } else {
            _this.val(names[0]);
            if (domini__WEBPACK_IMPORTED_MODULE_0___default()(el).hasClass("asp_gochosen") || domini__WEBPACK_IMPORTED_MODULE_0___default()(el).hasClass("asp_goselect2")) {
              (0,_interval_until_execute__WEBPACK_IMPORTED_MODULE_1__.intervalUntilExecute)(function(_$) {
                _$(el).trigger("change.asp_select2");
              }, function() {
                return whichjQuery("asp_select2");
              }, 50, 3);
            } else if (domini__WEBPACK_IMPORTED_MODULE_0___default()(el).hasClass("hasDatepicker")) {
              (0,_interval_until_execute__WEBPACK_IMPORTED_MODULE_1__.intervalUntilExecute)(function(_$) {
                const node = _this.get(0);
                if (node === void 0) {
                  return;
                }
                let value = names[0], format = _$(node).datepicker("option", "dateFormat");
                _$(node).datepicker("option", "dateFormat", "yy-mm-dd");
                _$(node).datepicker("setDate", value);
                _$(node).datepicker("option", "dateFormat", format);
                _$(node).trigger("selectnochange");
              }, function() {
                return whichjQuery("datepicker");
              }, 50, 3);
            }
          }
        } else {
          if (el.type === "checkbox" || el.type === "radio") {
            domini__WEBPACK_IMPORTED_MODULE_0___default()(el).prop("checked", false);
          }
        }
      }
    });
    return form;
  }
};
const submitToUrl = function(action, method, input, target = "self") {
  let form;
  form = domini__WEBPACK_IMPORTED_MODULE_0___default()('<form style="display: none;" />');
  form.attr("action", action);
  form.attr("method", method);
  domini__WEBPACK_IMPORTED_MODULE_0___default()("body").append(form);
  if (typeof input !== "undefined" && input !== null) {
    Object.keys(input).forEach(function(name) {
      let value = input[name];
      let $input = domini__WEBPACK_IMPORTED_MODULE_0___default()('<input type="hidden" />');
      $input.attr("name", name);
      $input.attr("value", value);
      form.append($input);
    });
  }
  if (target == "new") {
    form.attr("target", "_blank");
  }
  form.get(0).submit();
};
const openInNewTab = function(url) {
  Object.assign(document.createElement("a"), { target: "_blank", href: url }).click();
};


/***/ }),

/***/ 685:
/***/ (function(__unused_webpack_module, __unused_webpack_exports, __webpack_require__) {

"use strict";

const utilities = {};
const context = __webpack_require__(271);
context.keys().forEach((key) => {
  if (key === "./index.ts") return;
  const module = context(key);
  Object.keys(module).forEach((exportName) => {
    utilities[exportName] = module[exportName];
  });
});
window.WPD.utils = utilities;


/***/ }),

/***/ 806:
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Base64: function() { return /* binding */ Base64; }
/* harmony export */ });

const Base64 = {
  // private property
  _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
  // public method for encoding
  encode: function(input) {
    return btoa(this._utf8_encode(input));
  },
  // public method for decoding
  decode: function(input) {
    return this._utf8_decode(
      atob(input.replace(/[^A-Za-z0-9\+\/\=]/g, ""))
    );
  },
  // private method for UTF-8 encoding
  _utf8_encode: function(string) {
    string = string.replace(/\r\n/g, "\n");
    let utftext = "";
    for (let n = 0; n < string.length; n++) {
      let c = string.charCodeAt(n);
      if (c < 128) {
        utftext += String.fromCharCode(c);
      } else if (c > 127 && c < 2048) {
        utftext += String.fromCharCode(c >> 6 | 192);
        utftext += String.fromCharCode(c & 63 | 128);
      } else {
        utftext += String.fromCharCode(c >> 12 | 224);
        utftext += String.fromCharCode(c >> 6 & 63 | 128);
        utftext += String.fromCharCode(c & 63 | 128);
      }
    }
    return utftext;
  },
  // private method for UTF-8 decoding
  _utf8_decode: function(utftext) {
    let string = "", i = 0, c = 0, c2, c3;
    while (i < utftext.length) {
      c = utftext.charCodeAt(i);
      if (c < 128) {
        string += String.fromCharCode(c);
        i++;
      } else if (c > 191 && c < 224) {
        c2 = utftext.charCodeAt(i + 1);
        string += String.fromCharCode((c & 31) << 6 | c2 & 63);
        i += 2;
      } else {
        c2 = utftext.charCodeAt(i + 1);
        c3 = utftext.charCodeAt(i + 2);
        string += String.fromCharCode((c & 15) << 12 | (c2 & 63) << 6 | c3 & 63);
        i += 3;
      }
    }
    return string;
  }
};


/***/ }),

/***/ 919:
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   intervalUntilExecute: function() { return /* binding */ intervalUntilExecute; }
/* harmony export */ });

function intervalUntilExecute(f, criteria, interval = 100, maxTries = 50) {
  let t, tries = 0, res = typeof criteria === "function" ? criteria() : criteria;
  if (res === false) {
    t = setInterval(function() {
      res = typeof criteria === "function" ? criteria() : criteria;
      tries++;
      if (tries > maxTries) {
        clearInterval(t);
        return false;
      }
      if (res !== false) {
        clearInterval(t);
        return f(res);
      }
    }, interval);
  } else {
    return f(res);
  }
}
;


/***/ }),

/***/ 993:
/***/ (function(module, exports) {

!function(e, t) {
  "object" == typeof exports && "object" == typeof module ? module.exports = t() : "function" == typeof define && define.amd ? define("DoMini", [], t) : "object" == typeof exports ? exports.DoMini = t() : e.DoMini = t();
}(window, () => (() => {
  "use strict";
  var e = { d: (t2, n2) => {
    for (var i2 in n2) e.o(n2, i2) && !e.o(t2, i2) && Object.defineProperty(t2, i2, { enumerable: true, get: n2[i2] });
  }, o: (e2, t2) => Object.prototype.hasOwnProperty.call(e2, t2) }, t = {};
  let n;
  e.d(t, { default: () => r }), void 0 === window.DoMini ? (n = function(e2, t2) {
    return void 0 !== arguments[2] ? this.constructor.call(this, e2, t2) : 1 !== arguments.length || "function" != typeof arguments[0] ? new n(e2, t2, true) : void ("complete" === document.readyState || "loaded" === document.readyState || "interactive" === document.readyState ? arguments[0].apply(this, [n]) : window.addEventListener("DOMContentLoaded", () => {
      arguments[0].apply(this, [n]);
    }));
  }, n.prototype = n.fn = { constructor: function(e2, t2) {
    if (this.length = 0, void 0 !== t2) {
      if (t2 instanceof n) return t2.find(e2);
      if (this.isValidNode(t2) || "string" == typeof t2) return n(t2).find(e2);
    } else if ("string" == typeof e2 && "" !== e2) this.push(...this._(e2));
    else {
      if (e2 instanceof n) return e2;
      this.isValidNode(e2) && this.push(e2);
    }
    return this;
  }, _: function(e2) {
    return "<" === e2.charAt(0) ? n._fn.createElementsFromHTML(e2) : [...document.querySelectorAll(e2)];
  }, isValidNode: (e2) => e2 instanceof Element || e2 instanceof Document || e2 instanceof Window, push: Array.prototype.push, pop: Array.prototype.pop, sort: Array.prototype.sort, splice: Array.prototype.splice }, n.prototype[Symbol.iterator] = Array.prototype[Symbol.iterator], n._fn = {}, n.version = "0.2.8") : n = window.DoMini;
  const i = n;
  i.fn.get = function(e2) {
    return void 0 === e2 ? Array.from(this) : this[e2];
  }, i.fn.extend = function() {
    for (let e2 = 1; e2 < arguments.length; e2++) for (let t2 in arguments[e2]) arguments[e2].hasOwnProperty(t2) && (arguments[0][t2] = arguments[e2][t2]);
    return arguments[0];
  }, i.fn.forEach = function(e2) {
    return this.get().forEach(function(t2, n2, i2) {
      e2.apply(t2, [t2, n2, i2]);
    }), this;
  }, i.fn.each = function(e2) {
    return this.get().forEach(function(t2, n2, i2) {
      e2.apply(t2, [n2, t2, i2]);
    }), this;
  }, i.fn.css = function(e2, t2) {
    for (const n2 of this) if (1 === arguments.length) {
      if ("object" != typeof e2) return window.getComputedStyle(n2)[e2];
      Object.keys(e2).forEach(function(t3) {
        n2.style[t3] = e2[t3];
      });
    } else n2.style[e2] = t2;
    return this;
  }, i.fn.hasClass = function(e2) {
    let t2 = this.get(0);
    return null != t2 && t2.classList.contains(e2);
  }, i.fn.addClass = function(e2) {
    let t2 = e2;
    return "string" == typeof e2 && (t2 = e2.split(" ")), t2 = t2.filter(function(e3) {
      return "" !== e3.trim();
    }), t2.length > 0 && this.forEach(function(e3) {
      e3.classList.add.apply(e3.classList, t2);
    }), this;
  }, i.fn.removeClass = function(e2) {
    if (void 0 !== e2) {
      let t2 = e2;
      "string" == typeof e2 && (t2 = e2.split(" ")), t2 = t2.filter(function(e3) {
        return "" !== e3.trim();
      }), t2.length > 0 && this.forEach(function(e3) {
        e3.classList.remove.apply(e3.classList, t2);
      });
    } else this.forEach(function(e3) {
      e3.classList.length > 0 && e3.classList.remove.apply(e3.classList, e3.classList);
    });
    return this;
  }, i.fn.isVisible = function() {
    let e2, t2 = this.get(0), n2 = true;
    for (; null !== t2; ) {
      if (e2 = window.getComputedStyle(t2), "none" === e2.display || "hidden" === e2.visibility || 0 === parseInt(e2.opacity)) {
        n2 = false;
        break;
      }
      t2 = t2.parentElement;
    }
    return n2;
  }, i.fn.val = function(e2) {
    let t2;
    if (1 === arguments.length) {
      for (const t3 of this) if ("select-multiple" === t3.type) {
        e2 = "string" == typeof e2 ? e2.split(",") : e2;
        for (let n2, i2 = 0, o2 = t3.options.length; i2 < o2; i2++) n2 = t3.options[i2], n2.selected = -1 !== e2.indexOf(n2.value);
      } else t3.value = e2;
      t2 = this;
    } else {
      let e3 = this.get(0);
      null != e3 && (t2 = "select-multiple" === e3.type ? Array.prototype.map.call(e3.selectedOptions, function(e4) {
        return e4.value;
      }) : e3.value);
    }
    return t2;
  }, i.fn.attr = function(e2, t2) {
    let n2;
    for (const i2 of this) if (2 === arguments.length) i2.setAttribute(e2, t2), n2 = this;
    else {
      if ("object" != typeof e2) {
        n2 = i2.getAttribute(e2);
        break;
      }
      Object.keys(e2).forEach(function(t3) {
        i2.setAttribute(t3, e2[t3]);
      });
    }
    return n2;
  }, i.fn.removeAttr = function(e2) {
    for (const t2 of this) t2.removeAttribute(e2);
    return this;
  }, i.fn.prop = function(e2, t2) {
    let n2;
    for (const i2 of this) {
      if (2 !== arguments.length) {
        n2 = void 0 !== i2[e2] ? i2[e2] : null;
        break;
      }
      i2[e2] = t2;
    }
    return 2 === arguments.length ? this : n2;
  }, i.fn.data = function(e2, t2) {
    const n2 = e2.replace(/-([a-z])/g, function(e3) {
      return e3[1].toUpperCase();
    });
    if (2 === arguments.length) {
      for (const e3 of this) null != e3 && (e3.dataset[n2] = t2);
      return this;
    }
    {
      let e3 = this.get(0);
      return null != e3 && void 0 !== e3.dataset[n2] ? e3.dataset[n2] : "";
    }
  }, i.fn.html = function(e2) {
    if (1 === arguments.length) {
      for (const t2 of this) t2.innerHTML = e2;
      return this;
    }
    {
      let e3 = this.get(0);
      return null == e3 ? "" : e3.innerHTML;
    }
  }, i.fn.text = function(e2) {
    if (1 === arguments.length) {
      for (const t2 of this) t2.textContent = e2;
      return this;
    }
    {
      let e3 = this.get(0);
      return null == e3 ? "" : e3.textContent;
    }
  }, i.fn.position = function() {
    let e2 = this.get(0);
    return null != e2 ? { top: e2.offsetTop, left: e2.offsetLeft } : { top: 0, left: 0 };
  }, i.fn.offset = function() {
    let e2 = this.get(0);
    return null != e2 ? i._fn.hasFixedParent(e2) ? e2.getBoundingClientRect() : i._fn.absolutePosition(e2) : { top: 0, left: 0 };
  }, i.fn.outerWidth = function(e2) {
    e2 = e2 || false;
    let t2 = this.get(0);
    return null != t2 ? e2 ? parseInt(t2.offsetWidth) + parseInt(this.css("marginLeft")) + parseInt(this.css("marginRight")) : parseInt(t2.offsetWidth) : 0;
  }, i.fn.outerHeight = function(e2) {
    e2 = e2 || false;
    let t2 = this.get(0);
    return null != t2 ? e2 ? parseInt(t2.offsetHeight) + parseInt(this.css("marginTop")) + parseInt(this.css("marginBottom")) : parseInt(t2.offsetHeight) : 0;
  }, i.fn.noPaddingHeight = function(e2) {
    return e2 = e2 || false, this.length > 0 ? e2 ? parseInt(this.css("height")) + parseInt(this.css("marginTop")) + parseInt(this.css("marginBottom")) : parseInt(this.css("height")) : 0;
  }, i.fn.noPaddingWidth = function(e2) {
    return e2 = e2 || false, this.length > 0 ? e2 ? parseInt(this.css("width")) + parseInt(this.css("marginLeft")) + parseInt(this.css("marginRight")) : parseInt(this.css("width")) : 0;
  }, i.fn.innerWidth = function() {
    let e2 = this.get(0);
    if (null != e2) {
      let t2 = window.getComputedStyle(e2);
      return this.outerWidth() - parseFloat(t2.borderLeftWidth) - parseFloat(t2.borderRightWidth);
    }
    return 0;
  }, i.fn.innerHeight = function() {
    let e2 = this.get(0);
    if (null != e2) {
      let t2 = window.getComputedStyle(e2);
      return this.outerHeight() - parseFloat(t2.borderTopWidth) - parseFloat(t2.borderBottomtWidth);
    }
    return 0;
  }, i.fn.width = function() {
    return this.outerWidth();
  }, i.fn.height = function() {
    return this.outerHeight();
  }, i.fn.on = function() {
    let e2 = arguments, t2 = function(e3, t3) {
      let n3;
      if ("mouseenter" === t3.type || "mouseleave" === t3.type || "mouseover" === t3.type) {
        let o2 = document.elementFromPoint(t3.clientX, t3.clientY);
        if (!o2.matches(e3[1])) for (; (o2 = o2.parentElement) && !o2.matches(e3[1]); ) ;
        null != o2 && (n3 = i(o2));
      } else n3 = i(t3.target).closest(e3[1]);
      if (null != n3 && n3.closest(this).length > 0) {
        let i2 = [];
        if (i2.push(t3), void 0 !== e3[4]) for (let t4 = 4; t4 < e3.length; t4++) i2.push(e3[t4]);
        e3[2].apply(n3.get(0), i2);
      }
    }, n2 = e2[0].split(" ");
    for (let o2 = 0; o2 < n2.length; o2++) {
      let r2 = n2[o2];
      if ("string" == typeof e2[1]) this.forEach(function(n3) {
        if (!i._fn.hasEventListener(n3, r2, e2[2])) {
          let i2 = t2.bind(n3, e2);
          n3.addEventListener(r2, i2, e2[3]), n3._domini_events = void 0 === n3._domini_events ? [] : n3._domini_events, n3._domini_events.push({ type: r2, selector: e2[1], func: i2, trigger: e2[2], args: e2[3] });
        }
      });
      else for (let t3 = 0; t3 < n2.length; t3++) {
        let o3 = n2[t3];
        this.forEach(function(t4) {
          i._fn.hasEventListener(t4, o3, e2[1]) || (t4.addEventListener(o3, e2[1], e2[2]), t4._domini_events = void 0 === t4._domini_events ? [] : t4._domini_events, t4._domini_events.push({ type: o3, func: e2[1], trigger: e2[1], args: e2[2] }));
        });
      }
    }
    return this;
  }, i.fn.off = function(e2, t2) {
    return this.forEach(function(n2) {
      if (void 0 !== n2._domini_events && n2._domini_events.length > 0) if (void 0 === e2) {
        let e3;
        for (; e3 = n2._domini_events.pop(); ) n2.removeEventListener(e3.type, e3.func, e3.args);
        n2._domini_events = [];
      } else e2.split(" ").forEach(function(e3) {
        let i2, o2 = [];
        for (; i2 = n2._domini_events.pop(); ) i2.type !== e3 || void 0 !== t2 && i2.trigger !== t2 ? o2.push(i2) : n2.removeEventListener(e3, i2.func, i2.args);
        n2._domini_events = o2;
      });
    }), this;
  }, i.fn.offForced = function() {
    let e2 = this;
    return this.forEach(function(t2, n2) {
      let i2 = t2.cloneNode(true);
      t2.parentNode.replaceChild(i2, t2), e2[n2] = i2;
    }), this;
  }, i.fn.trigger = function(e2, t2, n2, o2) {
    return n2 = n2 || false, o2 = o2 || false, this.forEach(function(r2) {
      let s = false;
      if (o2 && "undefined" != typeof jQuery && void 0 !== jQuery._data && void 0 !== jQuery._data(r2, "events") && void 0 !== jQuery._data(r2, "events")[e2] && (jQuery(r2).trigger(e2, t2), s = true), !s && n2) {
        let n3 = new Event(e2);
        n3.detail = t2, r2.dispatchEvent(n3);
      }
      if (void 0 !== r2._domini_events) r2._domini_events.forEach(function(n3) {
        if (n3.type === e2) {
          let i2 = new Event(e2);
          n3.trigger.apply(r2, [i2].concat(t2));
        }
      });
      else {
        let n3 = false, o3 = r2;
        for (; o3 = o3.parentElement, null != o3 && (void 0 !== o3._domini_events && o3._domini_events.forEach(function(s2) {
          if (void 0 !== s2.selector) {
            let l = i(o3).find(s2.selector);
            if (l.length > 0 && l.get().indexOf(r2) >= 0 && s2.type === e2) {
              let i2 = new Event(e2);
              s2.trigger.apply(r2, [i2].concat(t2)), n3 = true;
            }
          }
        }), !n3); ) ;
      }
    }), this;
  }, i.fn.clear = function() {
    for (const e2 of this) delete e2._domini_events;
    return this;
  }, i.fn.clone = function() {
    let e2 = [];
    for (const t2 of this) e2.push(t2.cloneNode(true));
    return i().add(e2);
  }, i.fn.detach = function(e2) {
    let t2 = this, n2 = [];
    void 0 !== e2 && (t2 = this.find(e2));
    for (const e3 of t2) null != e3.parentElement && n2.push(e3.parentElement.removeChild(e3));
    return i().add(n2);
  }, i.fn.remove = function(e2) {
    return this.detach(e2).off().clear();
  }, i.fn.prepend = function(e2) {
    if ((e2 = i._fn.elementArrayFromAny(e2)).length > 0) for (const t2 of this) for (const n2 of e2) t2.insertBefore(n2, t2.children[0]);
    return this;
  }, i.fn.append = function(e2) {
    if ((e2 = i._fn.elementArrayFromAny(e2)).length > 0) for (const t2 of this) for (const n2 of e2) t2.appendChild(n2);
    return this;
  }, i.fn.is = function(e2) {
    let t2 = false;
    for (const n2 of this) if (n2.matches(e2)) {
      t2 = true;
      break;
    }
    return t2;
  }, i.fn.parent = function(e2) {
    let t2 = [];
    for (const n2 of this) {
      let i2 = n2.parentElement;
      "string" == typeof e2 && (null == i2 || i2.matches(e2) || (i2 = null)), t2.push(i2);
    }
    return i().add(t2);
  }, i.fn.copy = function(e2, t2) {
    let n2, i2, o2;
    if ("object" != typeof e2 || null === e2) return n2 = e2, n2;
    for (i2 in n2 = new e2.constructor(), e2) e2.hasOwnProperty(i2) && (o2 = typeof e2[i2], t2 && "object" === o2 && null !== e2[i2] ? n2[i2] = this.copy(e2[i2]) : n2[i2] = e2[i2]);
    return n2;
  }, i.fn.first = function() {
    return i(this[0]);
  }, i.fn.last = function() {
    return i(this[this.length - 1]);
  }, i.fn.prev = function(e2) {
    let t2 = [];
    for (const n2 of this) {
      let i2;
      if ("string" == typeof e2) for (i2 = n2.previousElementSibling; null != i2; ) {
        if (i2.matches(e2)) {
          t2.push(i2);
          break;
        }
        i2 = i2.previousElementSibling;
      }
      else t2.push(n2.previousElementSibling);
    }
    return i(null).add(t2);
  }, i.fn.next = function(e2) {
    let t2 = [];
    for (const n2 of this) {
      let i2;
      if ("string" == typeof e2) for (i2 = n2.nextElementSibling; null != i2; ) {
        if (i2.matches(e2)) {
          t2.includes(i2) || t2.push(i2);
          break;
        }
        i2 = i2.nextElementSibling;
      }
      else t2.push(n2.nextElementSibling);
    }
    return i(null).add(t2);
  }, i.fn.closest = function(e2) {
    let t2 = [];
    for (let n2 of this) if ("string" == typeof e2 && "" !== e2) {
      for (; !n2.matches(e2) && (n2 = n2.parentElement); ) ;
      t2.includes(n2) || t2.push(n2);
    } else {
      if ((e2 = e2 instanceof i ? e2.get(0) : e2) instanceof Element) for (; n2 !== e2 && (n2 = n2.parentElement); ) ;
      else n2 = null;
      t2.includes(n2) || t2.push(n2);
    }
    return i().add(t2);
  }, i.fn.add = function(e2) {
    let t2 = i._fn.elementArrayFromAny(e2);
    for (const e3 of t2) Array.from(this).includes(e3) || this.push(e3);
    return this;
  }, i.fn.find = function(e2) {
    const t2 = new i();
    if ("string" == typeof e2) {
      let n2 = [];
      this.get().forEach(function(t3) {
        const i2 = t3.querySelectorAll?.(e2) ?? [];
        n2 = n2.concat(Array.from(i2));
      }), n2.length > 0 && t2.add(n2);
    }
    return t2;
  }, i._fn.bodyTransform = function() {
    let e2 = 0, t2 = 0;
    if ("undefined" != typeof WebKitCSSMatrix) {
      let n2 = window.getComputedStyle(document.body);
      if (void 0 !== n2.transform) {
        let i2 = new WebKitCSSMatrix(n2.transform);
        "undefined" !== i2.m41 && (e2 = i2.m41), "undefined" !== i2.m42 && (t2 = i2.m42);
      }
    }
    return { x: e2, y: t2 };
  }, i._fn.bodyTransformY = function() {
    return this.bodyTransform().y;
  }, i._fn.bodyTransformX = function() {
    return this.bodyTransform().x;
  }, i._fn.hasFixedParent = function(e2) {
    if (0 != i._fn.bodyTransformY()) return false;
    do {
      if ("fixed" == window.getComputedStyle(e2).position) return true;
    } while (e2 = e2.parentElement);
    return false;
  }, i._fn.hasEventListener = function(e2, t2, n2) {
    if (void 0 === e2._domini_events) return false;
    for (let i2 = 0; i2 < e2._domini_events.length; i2++) if (e2._domini_events[i2].trigger === n2 && e2._domini_events[i2].type === t2) return true;
    return false;
  }, i._fn.allDescendants = function(e2) {
    let t2 = [], n2 = this;
    return Array.isArray(e2) || (e2 = [e2]), e2.forEach(function(e3) {
      for (let i2 = 0; i2 < e3.childNodes.length; i2++) {
        let o2 = e3.childNodes[i2];
        t2.push(o2), t2 = t2.concat(n2.allDescendants(o2));
      }
    }), t2;
  }, i._fn.createElementsFromHTML = function(e2) {
    let t2 = document.createElement("template");
    return t2.innerHTML = e2.replace(/(\r\n|\n|\r)/gm, ""), [...t2.content.childNodes];
  }, i._fn.elementArrayFromAny = function(e2) {
    if ("string" == typeof e2) e2 = i(e2).get();
    else if (e2 instanceof i) e2 = e2.get();
    else if (e2 instanceof Element) e2 = [e2];
    else {
      if (!(e2 instanceof Array)) return [];
      e2 = e2.filter((e3) => e3 instanceof Element);
    }
    return e2;
  }, i._fn.ElementArrayFromAny = i._fn.elementArrayFromAny, i._fn.absolutePosition = function(e2) {
    if (!e2.getClientRects().length) return { top: 0, left: 0 };
    let t2 = e2.getBoundingClientRect(), n2 = e2.ownerDocument.defaultView;
    return { top: t2.top + n2.pageYOffset, left: t2.left + n2.pageXOffset };
  }, i._fn.plugin = function(e2, t2) {
    i.fn[e2] = function(n2) {
      return void 0 !== n2 && t2[n2] ? t2[n2].apply(this, Array.prototype.slice.call(arguments, 1)) : this.forEach(function(i2) {
        i2["domini_" + e2] = Object.create(t2).init(n2, i2);
      });
    };
  }, document.dispatchEvent(new Event("domini-dom-core-loaded"));
  const o = i;
  i.fn.animate = function(e2, t2, n2) {
    t2 = t2 || 200, n2 = n2 || "easeInOutQuad";
    for (const o2 of this) {
      let r2, s, l, f, a, c = 0, u = 60, h = {}, d = {};
      if (l = this.prop("_domini_animations"), l = null == l ? [] : l, false === e2) l.forEach(function(e3) {
        clearInterval(e3);
      });
      else {
        let p = function() {
          c++, c > r2 ? clearInterval(f) : (s = a(c / r2), Object.keys(d).forEach(function(e3) {
            e3.indexOf("scroll") > -1 ? o2[e3] = h[e3] + d[e3] * s : o2.style[e3] = h[e3] + d[e3] * s + "px";
          }));
        };
        a = i.fn.animate.easing[n2] ?? i.fn.animate.easing.easeInOutQuad, Object.keys(e2).forEach(function(t3) {
          t3.indexOf("scroll") > -1 ? (h[t3] = o2[t3], d[t3] = e2[t3] - h[t3]) : (h[t3] = parseInt(window.getComputedStyle(o2)[t3]), d[t3] = e2[t3] - h[t3]);
        }), r2 = t2 / 1e3 * u, f = setInterval(p, 1e3 / u), l.push(f), this.prop("_domini_animations", l);
      }
    }
    return this;
  }, i.fn.animate.easing = { linear: function(e2) {
    return e2;
  }, easeInOutQuad: function(e2) {
    return e2 < 0.5 ? 2 * e2 * e2 : 1 - Math.pow(-2 * e2 + 2, 2) / 2;
  }, easeOutQuad: function(e2) {
    return 1 - (1 - e2) * (1 - e2);
  } }, i.fn.unhighlight = function(e2) {
    let t2 = { className: "highlight", element: "span" };
    return i.fn.extend(t2, e2), this.find(t2.element + "." + t2.className).forEach(function() {
      let e3 = this.parentNode;
      e3.replaceChild(this.firstChild, this), e3.normalize();
    });
  }, i.fn.highlight = function(e2, t2) {
    this.defaults = { className: "highlight", element: "span", caseSensitive: false, wordsOnly: false, excludeParents: ".excludeFromHighlight" };
    const n2 = i, o2 = { ...this.defaults, ...t2 };
    if (e2.constructor === String && (e2 = [e2]), (e2 = e2.filter(function(e3) {
      return "" !== e3;
    })).forEach(function(e3, t3, n3) {
      n3[t3] = e3.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&").normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }), 0 === e2.length) return this;
    let r2 = o2.caseSensitive ? "" : "i", s = "(" + e2.join("|") + ")";
    o2.wordsOnly && (s = "(?:,|^|\\s)" + s + "(?:,|$|\\s)");
    let l = new RegExp(s, r2);
    function f(e3, t3, i2, o3, r3) {
      if (r3 = "" === r3 ? n2.fn.highlight.defaults : r3, 3 === e3.nodeType) {
        if (!n2(e3.parentNode).is(r3)) {
          let n3 = e3.data.normalize("NFD").replace(/[\u0300-\u036f]/g, "").match(t3);
          if (n3) {
            let t4, r4 = document.createElement(i2 || "span");
            r4.className = o3 || "highlight", t4 = /\.|,|\s/.test(n3[0].charAt(0)) ? n3.index + 1 : n3.index;
            let s2 = e3.splitText(t4);
            s2.splitText(n3[1].length);
            let l2 = s2.cloneNode(true);
            return r4.appendChild(l2), s2.parentNode.replaceChild(r4, s2), 1;
          }
        }
      } else if (1 === e3.nodeType && e3.childNodes && !/(script|style)/i.test(e3.tagName) && !n2(e3).closest(r3).length > 0 && (e3.tagName !== i2.toUpperCase() || e3.className !== o3)) for (let n3 = 0; n3 < e3.childNodes.length; n3++) n3 += f(e3.childNodes[n3], t3, i2, o3, r3);
      return 0;
    }
    return this.forEach(function(e3) {
      f(e3, l, o2.element, o2.className, o2.excludeParents);
    });
  }, i.fn.serialize = function() {
    let e2 = this.get(0);
    if (!e2 || "FORM" !== e2.nodeName) return "";
    let t2, n2, i2 = [];
    for (t2 = e2.elements.length - 1; t2 >= 0; t2 -= 1) if ("" !== e2.elements[t2].name) switch (e2.elements[t2].nodeName) {
      case "INPUT":
        switch (e2.elements[t2].type) {
          case "checkbox":
          case "radio":
            e2.elements[t2].checked && i2.push(e2.elements[t2].name + "=" + encodeURIComponent(e2.elements[t2].value));
            break;
          case "file":
            break;
          default:
            i2.push(e2.elements[t2].name + "=" + encodeURIComponent(e2.elements[t2].value));
        }
        break;
      case "TEXTAREA":
        i2.push(e2.elements[t2].name + "=" + encodeURIComponent(e2.elements[t2].value));
        break;
      case "SELECT":
        switch (e2.elements[t2].type) {
          case "select-one":
            i2.push(e2.elements[t2].name + "=" + encodeURIComponent(e2.elements[t2].value));
            break;
          case "select-multiple":
            for (n2 = e2.elements[t2].options.length - 1; n2 >= 0; n2 -= 1) e2.elements[t2].options[n2].selected && i2.push(e2.elements[t2].name + "=" + encodeURIComponent(e2.elements[t2].options[n2].value));
        }
        break;
      case "BUTTON":
        switch (e2.elements[t2].type) {
          case "reset":
          case "submit":
          case "button":
            i2.push(e2.elements[t2].name + "=" + encodeURIComponent(e2.elements[t2].value));
        }
    }
    return i2.join("&");
  }, i.fn.serializeObject = function(e2, t2) {
    let n2, o2 = [];
    for (n2 in e2) if (e2.hasOwnProperty(n2)) {
      let r2 = t2 ? t2 + "[" + n2 + "]" : n2, s = e2[n2];
      o2.push(null !== s && "object" == typeof s ? i.fn.serializeObject(s, r2) : encodeURIComponent(r2) + "=" + encodeURIComponent(s));
    }
    return o2.join("&");
  }, i.fn.inViewPort = function(e2, t2) {
    let n2, i2, o2 = this.get(0);
    if (null == o2) return false;
    e2 = void 0 === e2 ? 0 : e2, t2 = void 0 === t2 ? window : "string" == typeof t2 ? document.querySelector(t2) : t2;
    let r2 = o2.getBoundingClientRect(), s = r2.top, l = r2.bottom, f = r2.left, a = r2.right, c = false;
    if (null == t2 && (t2 = window), t2 === window) n2 = window.innerWidth || 0, i2 = window.innerHeight || 0;
    else {
      n2 = t2.clientWidth, i2 = t2.clientHeight;
      let e3 = t2.getBoundingClientRect();
      s -= e3.top, l -= e3.top, f -= e3.left, a -= e3.left;
    }
    return e2 = ~~Math.round(parseFloat(e2)), a <= 0 || f >= n2 || (c = e2 > 0 ? s >= e2 && l < i2 - e2 : (l > 0 && s <= i2 - e2) | (s <= 0 && l > e2)), c;
  }, i.fn.ajax = function(e2) {
    if ("cors" === (e2 = this.extend({ url: "", method: "GET", cors: "cors", data: {}, success: null, fail: null, accept: "text/html", contentType: "application/x-www-form-urlencoded; charset=UTF-8" }, e2)).cors) {
      let t2 = new XMLHttpRequest();
      return t2.onreadystatechange = function() {
        null != e2.success && 4 === this.readyState && this.status >= 200 && this.status < 400 && e2.success(this.responseText), null != e2.fail && 4 === this.readyState && this.status >= 400 && e2.fail(this);
      }, t2.open(e2.method.toUpperCase(), e2.url, true), t2.setRequestHeader("Content-type", e2.contentType), t2.setRequestHeader("Accept", e2.accept), t2.send(this.serializeObject(e2.data)), t2;
    }
    {
      let t2 = "ajax_cb_" + "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function(e3) {
        let t3 = 16 * Math.random() | 0;
        return ("x" === e3 ? t3 : 3 & t3 | 8).toString(16);
      }).replaceAll("-", "");
      i.fn[t2] = function() {
        e2.success.apply(this, arguments), delete i.fn[e2.data.fn];
      }, e2.data.callback = "DoMini.fn." + t2, e2.data.fn = t2;
      let n2 = document.createElement("script");
      n2.type = "text/javascript", n2.src = e2.url + "?" + this.serializeObject(e2.data), n2.onload = function() {
        this.remove();
      }, document.body.appendChild(n2);
    }
  };
  const r = o;
  return t.default;
})());


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
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
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
!function() {
"use strict";

// EXTERNAL MODULE: ./node_modules/domini/dist/domini.js
var domini = __webpack_require__(993);
var domini_default = /*#__PURE__*/__webpack_require__.n(domini);
;// ./src/client/global/WPD.ts



window.WPD = window.WPD || {};
window.WPD.global = {
  utils: {}
};
window.WPD.dom = (domini_default());
window.DoMini = (domini_default());
window.WPD.domini = window.WPD.dom;
window.WPD.DoMini = window.WPD.dom;

// EXTERNAL MODULE: ./src/client/global/utils/index.ts
var utils = __webpack_require__(685);
;// ./src/client/plugin/wrapper/Instances.ts


window._asl_instances_storage = window._asl_instances_storage || [];
const Instances = {
  instances: window._asl_instances_storage,
  get: function(id, instance) {
    this.clean();
    if (typeof id === "undefined" || id === 0) {
      return this.instances;
    } else {
      if (typeof instance === "undefined") {
        let ret = [];
        for (let i = 0; i < this.instances.length; i++) {
          if (this.instances[i].o.id === id) {
            ret.push(this.instances[i]);
          }
        }
        return ret.length > 0 ? ret : false;
      } else {
        for (let i = 0; i < this.instances.length; i++) {
          if (this.instances[i].o.id === id && this.instances[i].o.iid === instance) {
            return this.instances[i];
          }
        }
      }
    }
    return false;
  },
  set: function(obj) {
    if (!this.exist(obj.o.id, obj.o.iid)) {
      this.instances.push(obj);
      return true;
    } else {
      return false;
    }
  },
  exist: function(id, instance) {
    id = typeof id === "string" ? parseInt(id) : id;
    instance = typeof instance === "string" ? parseInt(instance) : instance;
    this.clean();
    for (let i = 0; i < this.instances.length; i++) {
      if (this.instances[i].o.id === id) {
        if (typeof instance === "undefined") {
          return true;
        } else if (this.instances[i].o.iid === instance) {
          return true;
        }
      }
    }
    return false;
  },
  clean: function() {
    let unset = [], _this = this;
    this.instances.forEach(function(v, k) {
      if (domini_default()(".asl_m_" + v.o.rid).length === 0) {
        unset.push(k);
      }
    });
    unset.forEach(function(k) {
      if (typeof _this.instances[k] !== "undefined") {
        _this.instances[k].destroy();
        _this.instances.splice(k, 1);
      }
    });
  },
  destroy: function(id, instance) {
    let i = this.get(id, instance);
    if (i !== false) {
      if (Array.isArray(i)) {
        i.forEach(function(s) {
          s.destroy();
        });
        this.instances = [];
      } else {
        let u = 0;
        this.instances.forEach(function(v, k) {
          if (v.o.id === id && v.o.iid === instance) {
            u = k;
          }
        });
        i.destroy();
        this.instances.splice(u, 1);
      }
    }
  }
};
/* harmony default export */ var wrapper_Instances = (Instances);

;// ./src/client/plugin/wrapper/api.ts


function api() {
  "use strict";
  const a4 = function(id, instance, func, args) {
    let s = wrapper_Instances.get(id, instance);
    if (s !== false && !Array.isArray(s)) {
      const f = s[func];
      if (typeof f === "function") {
        f.bind(s).apply(s, [args]);
      }
    }
  }, a3 = function(id, func, args) {
    let s;
    if (typeof func === "number" && isFinite(func)) {
      s = wrapper_Instances.get(id, func);
      if (s !== false && !Array.isArray(s)) {
        const f = s[args];
        if (typeof f === "function") {
          return f.bind(s).apply(args);
        }
      }
    } else if (typeof func === "string") {
      s = wrapper_Instances.get(id);
      return s !== false && Array.isArray(s) && s.forEach(function(i) {
        const f = i[func];
        if (typeof f === "function") {
          f.bind(s).apply(i, [args]);
        }
      });
    }
  }, a2 = function(id, func) {
    let s;
    if (func === "exists") {
      return wrapper_Instances.exist(id);
    }
    s = wrapper_Instances.get(id);
    return s !== false && Array.isArray(s) && s.forEach(function(i) {
      const f = i[func];
      if (typeof f === "function") {
        f.bind(i).apply(i);
      }
    });
  };
  if (arguments.length === 4) {
    return a4.apply(this, arguments);
  } else if (arguments.length === 3) {
    return a3.apply(this, arguments);
  } else if (arguments.length === 2) {
    return a2.apply(this, arguments);
  } else if (arguments.length === 0) {
    console.log("Usage: ASL.api(id, [optional]instance, function, [optional]args);");
    console.log("For more info: https://knowledgebase.ajaxsearchpro.com/other/javascript-api");
  }
}

;// ./src/client/utils/onSafeDocumentReady.ts

const onSafeDocumentReady = (callback) => {
  let wasExecuted = false;
  const isDocumentReady = () => {
    return document.readyState === "complete" || document.readyState === "interactive" || document.readyState === "loaded";
  };
  const removeListeners = () => {
    window.removeEventListener("DOMContentLoaded", onDOMContentLoaded);
    document.removeEventListener("readystatechange", onReadyStateChange);
  };
  const runCallback = () => {
    if (!wasExecuted) {
      wasExecuted = true;
      callback();
      removeListeners();
    }
  };
  const onDOMContentLoaded = () => {
    runCallback();
  };
  const onReadyStateChange = () => {
    if (isDocumentReady()) {
      runCallback();
    }
  };
  if (isDocumentReady()) {
    runCallback();
  } else {
    window.addEventListener("DOMContentLoaded", onDOMContentLoaded);
    document.addEventListener("readystatechange", onReadyStateChange);
  }
};
/* harmony default export */ var utils_onSafeDocumentReady = (onSafeDocumentReady);

;// ./src/client/plugin/wrapper/ASL.ts





window.ASL = { ...window.ASL, ...{
  instances: wrapper_Instances,
  instance_args: [],
  api: api,
  initialized: false,
  initializeAllSearches: function() {
    const instances = this.getInstances();
    instances.forEach(function(data, i) {
      domini_default().fn._(".asl_m_" + i).forEach(function(el) {
        if (typeof el.hasAsp != "undefined") {
          return true;
        }
        el.hasAsp = true;
        return domini_default()(el).ajaxsearchlite(data);
      });
    });
  },
  initializeSearchByID: function(id, instance = 0) {
    const data = this.getInstance(id);
    const selector = instance === 0 ? ".asl_m_" + id : ".asl_m_" + id + "_" + instance;
    domini_default().fn._(selector).forEach(function(el) {
      if (typeof el.hasAsp != "undefined") {
        return true;
      }
      el.hasAsp = true;
      return domini_default()(el).ajaxsearchlite(data);
    });
  },
  getInstances: function() {
    domini_default().fn._(".asl_init_data").forEach((el) => {
      const id = parseInt(el.dataset["aslId"] || "");
      if (typeof el.dataset["settings"] !== "undefined") {
        this.instance_args[id] = JSON.parse(el.dataset["settings"]);
      }
    });
    return this.instance_args;
  },
  getInstance: function(id) {
    if (typeof this.instance_args[id] !== "undefined") {
      return this.instance_args[id];
    }
    return this.getInstances()[id];
  },
  initialize: function(id) {
    if (typeof window.ASL.version == "undefined") {
      return false;
    }
    if (window.ASL.script_async_load || window.ASL.init_only_in_viewport) {
      const searches = document.querySelectorAll(".asl_w_container");
      if (searches.length) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const id2 = parseInt(entry.target.dataset.id ?? "0");
              const instance = parseInt(entry.target.dataset.instance ?? "0");
              this.initializeSearchByID(id2, instance);
              observer.unobserve(entry.target);
            }
          });
        });
        searches.forEach(function(el) {
          const search = el;
          if (typeof search._is_observed !== "undefined") {
            return;
          }
          search._is_observed = true;
          observer.observe(search);
        });
      }
    } else {
      if (typeof id === "undefined") {
        this.initializeAllSearches();
      } else {
        this.initializeSearchByID(id);
      }
    }
    this.initializeMutateDetector();
    this.initializeHighlight();
    this.initializeOtherEvents();
    this.initialized = true;
    return true;
  },
  initializeHighlight: function() {
    if (!window.ASL.highlight.enabled) {
      return;
    }
    for (const data of window.ASL.highlight.data) {
      let selector = data.selector !== "" && domini_default()(data.selector).length > 0 ? data.selector : "article", $highlighted, phrase;
      selector = domini_default()(selector).length > 0 ? selector : "body";
      const s = new URLSearchParams(location.search);
      phrase = s.get("s") ?? s.get("asl_highlight") ?? s.get("asl_s") ?? s.get("asl_ls") ?? "";
      domini_default()(selector).unhighlight({ className: "asl_single_highlighted" });
      if (phrase === null) {
        return;
      }
      phrase = phrase.trim();
      if (phrase === "") {
        return;
      }
      const words = phrase.trim().split(" ");
      domini_default()(selector).highlight([phrase.trim()], {
        element: "span",
        className: "asl_single_highlighted asl_single_highlighted_exact",
        wordsOnly: data.whole,
        excludeParents: ".asl_w, .asl-try"
      });
      if (words.length > 0) {
        domini_default()(selector).highlight(words, {
          element: "span",
          className: "asl_single_highlighted",
          wordsOnly: data.whole,
          excludeParents: ".asl_w, .asl-try, .asl_single_highlighted"
        });
      }
      $highlighted = domini_default()(".asl_single_highlighted_exact");
      if ($highlighted.length === 0) {
        $highlighted = domini_default()(".asl_single_highlighted");
      }
      if ($highlighted.length > 0 && data.scroll) {
        let stop = $highlighted.offset().top - 120;
        const $adminbar = domini_default()("#wpadminbar");
        if ($adminbar.length > 0) {
          stop -= $adminbar.height();
        }
        stop = stop + data.scroll_offset;
        stop = stop < 0 ? 0 : stop;
        domini_default()("html").animate({
          "scrollTop": stop
        }, 500);
      }
    }
  },
  initializeOtherEvents: function() {
    let ttt, ts;
    const $body = domini_default()("body");
    ts = "#menu-item-search, .fa-search, .fa, .fas";
    ts = ts + ", .fusion-flyout-menu-toggle, .fusion-main-menu-search-open";
    ts = ts + ", #search_button";
    ts = ts + ", .mini-search.popup-search";
    ts = ts + ", .icon-search";
    ts = ts + ", .menu-item-search-dropdown";
    ts = ts + ", .mobile-menu-button";
    ts = ts + ", .td-icon-search, .tdb-search-icon";
    ts = ts + ", .side_menu_button, .search_button";
    ts = ts + ", .raven-search-form-toggle";
    ts = ts + ", [data-elementor-open-lightbox], .elementor-button-link, .elementor-button";
    ts = ts + ", i[class*=-search], a[class*=-search]";
    $body.on("click touchend", ts, () => {
      clearTimeout(ttt);
      ttt = setTimeout(() => {
        this.initializeAllSearches();
      }, 300);
    });
    if (typeof window.jQuery != "undefined") {
      window.jQuery(document).on("elementor/popup/show", () => {
        setTimeout(() => {
          this.initializeAllSearches();
        }, 10);
      });
    }
  },
  initializeMutateDetector: function() {
    let t;
    if (typeof window.ASL.detect_ajax != "undefined" && window.ASL.detect_ajax) {
      const o = new MutationObserver(() => {
        clearTimeout(t);
        t = setTimeout(() => {
          this.initializeAllSearches();
        }, 500);
      });
      const body = document.querySelector("body");
      if (body == null) {
        return;
      }
      o.observe(body, { subtree: true, childList: true });
    }
  },
  loadScriptStack: function(stack) {
    let scriptTag;
    if (stack.length > 0) {
      const script = stack.shift();
      if (script === void 0) {
        return;
      }
      scriptTag = document.createElement("script");
      scriptTag.src = script["src"];
      scriptTag.onload = () => {
        if (stack.length > 0) {
          this.loadScriptStack(stack);
        } else {
          this.ready();
        }
      };
      document.body.appendChild(scriptTag);
    }
  },
  ready: function() {
    const $this = this;
    utils_onSafeDocumentReady(() => {
      $this.initialize();
    });
  },
  init: function() {
    if (window.ASL.script_async_load) {
      this.loadScriptStack(window.ASL.additional_scripts);
    } else {
      if (typeof window.WPD.AjaxSearchLite !== "undefined") {
        this.ready();
      }
    }
  }
} };

;// ./src/client/plugin/core/AslPlugin.ts

class AslPlugin_AslPlugin {
  call_num = 0;
  settingsInitialized = false;
  resultsInitialized = false;
  searching = false;
  post = void 0;
  postAuto = void 0;
  // Holding the last phrase that returned results
  lastSuccesfulSearch = "";
  // Store the last search information
  lastSearchData = {};
  ktype = "";
  keycode = 0;
  _usingLiveLoader = void 0;
  nodes = {};
  documentEventHandlers = [];
  resultsOpened = false;
  savedScrollTop = 0;
  savedContainerTop = 0;
  /**
   * on IOS touch (iPhone, iPad etc...) the 'click' event does not fire, when not bound to a clickable element
   * like a link, so instead, use touchend
   * Stupid solution, but it works...
   */
  clickTouchend = "click touchend";
  mouseupTouchend = "mouseup touchend";
  dragging = false;
  settingsChanged = false;
  isAutoP = false;
  resAnim = {
    "showClass": "asl_an_fadeInDrop",
    "showCSS": {
      "visibility": "visible",
      "display": "block",
      "opacity": 1,
      "animation-duration": "300ms"
    },
    "hideClass": "asl_an_fadeOutDrop",
    "hideCSS": {
      "visibility": "hidden",
      "opacity": 0,
      "display": "none"
    },
    "duration": 300
  };
  settAnim = {
    "showClass": "asl_an_fadeInDrop",
    "showCSS": {
      "visibility": "visible",
      "display": "block",
      "opacity": 1,
      "animation-duration": "300ms"
    },
    "hideClass": "asl_an_fadeOutDrop",
    "hideCSS": {
      "visibility": "hidden",
      "opacity": 0,
      "display": "none"
    },
    "duration": 300
  };
  timeouts = {
    searchWithCheck: void 0,
    search: void 0
  };
  /**
   * Search instance options passed from server
   */
  o = {
    id: 1,
    iid: 1,
    rid: "1_1",
    name: "Search name",
    homeurl: "",
    resultstype: "vertical",
    resultsposition: "hover",
    itemscount: 10,
    charcount: 0,
    highlight: true,
    blocking: false,
    detectVisibility: false,
    redirectOnClick: true,
    redirectOnEnter: true,
    highlightWholewords: true,
    singleHighlight: false,
    settingsVisible: 0,
    scrollToResults: {
      enabled: 1,
      offset: 0
    },
    resultareaclickable: 0,
    autocomplete: {
      enabled: 1,
      lang: "en",
      trigger_charcount: 0
    },
    mobile: {
      menu_selector: "#mobile-menu",
      force_res_hover: 0
    },
    trigger: {
      click: "ajax_search",
      click_location: "same",
      update_href: false,
      "return": "ajax_search",
      return_location: "same",
      facet: 1,
      type: 1,
      redirect_url: "/search",
      delay: 300
    },
    animations: {
      pc: {
        settings: {
          anim: "fadedrop",
          dur: 300
        },
        results: {
          anim: "fadedrop",
          dur: 300
        },
        items: "voidanim"
      },
      mob: {
        settings: {
          anim: "fadedrop",
          dur: 300
        },
        results: {
          anim: "fadedrop",
          dur: 300
        },
        items: "voidanim"
      }
    },
    autop: {
      state: "disabled",
      phrase: "",
      count: 10
    },
    resPage: {
      useAjax: 0,
      selector: ".search-results",
      trigger_type: 1,
      trigger_facet: 1,
      trigger_magnifier: 1,
      trigger_return: 1
    },
    resultsSnapTo: "left",
    results: {
      width: "auto",
      width_tablet: "auto",
      width_phone: "auto"
    },
    settingsimagepos: "left",
    closeOnDocClick: true,
    overridewpdefault: false,
    override_method: "get"
  };
}
window.WPD.global.AslPlugin = AslPlugin_AslPlugin;

;// ./src/client/bundle/optimized/prereq.ts






// EXTERNAL MODULE: ./src/client/global/utils/browser.ts
var browser = __webpack_require__(665);
;// ./src/client/plugin/core/actions/filters.ts



"use strict";
AslPlugin_AslPlugin.prototype.setFilterStateInput = function(timeout) {
  let $this = this;
  if (typeof timeout == "undefined") {
    timeout = 65;
  }
  let process = function() {
    if (JSON.stringify($this.originalFormData) != JSON.stringify((0,browser.formData)($this.n("searchsettings").find("form"))))
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
AslPlugin_AslPlugin.prototype.gaPageview = function(term) {
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
AslPlugin_AslPlugin.prototype.gaEvent = function(which, d) {
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
AslPlugin_AslPlugin.prototype.gaGetTrackingID = function() {
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

// EXTERNAL MODULE: ./src/client/global/utils/other.ts
var other = __webpack_require__(627);
// EXTERNAL MODULE: ./src/client/global/utils/interval-until-execute.ts
var interval_until_execute = __webpack_require__(919);
// EXTERNAL MODULE: ./src/client/global/utils/hooks-filters.ts
var hooks_filters = __webpack_require__(91);
;// ./src/client/plugin/core/actions/live.ts







const live_ASL = window.ASL;
AslPlugin_AslPlugin.prototype.getLiveLoadAltSelectors = function() {
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
AslPlugin_AslPlugin.prototype.usingLiveLoader = function() {
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
AslPlugin_AslPlugin.prototype.liveLoad = function(selector, url, updateLocation, forceAjax) {
  if (selector == "body" || selector == "html") {
    console.log("Ajax Search Pro: Do not use html or body as the live loader selector.");
    return false;
  }
  if (live_ASL.pageHTML == "") {
    if (!live_ASL._ajax_page_html) {
      live_ASL._ajax_page_html = true;
      domini_default().fn.ajax({
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
    data = hooks_filters.Hooks.applyFilters("asl/live_load/raw_data", data, $this);
    let parser = new DOMParser();
    let dataNode = parser.parseFromString(data, "text/html");
    let $dataNode = domini_default()(dataNode);
    if (data != "" && $dataNode.length > 0 && $dataNode.find(selector).length > 0) {
      data = data.replace(/&asl_force_reset_pagination=1/gmi, "");
      data = data.replace(/%26asl_force_reset_pagination%3D1/gmi, "");
      data = data.replace(/&#038;asl_force_reset_pagination=1/gmi, "");
      if ((0,browser.isSafari)()) {
        data = data.replace(/srcset/gmi, "nosrcset");
      }
      data = hooks_filters.Hooks.applyFilters("asl/live_load/html", data, $this.o.id, $this.o.iid);
      $dataNode = domini_default()(parser.parseFromString(data, "text/html"));
      let replacementNode = $dataNode.find(selector).get(0);
      replacementNode = hooks_filters.Hooks.applyFilters("asl/live_load/replacement_node", replacementNode, $this, $el.get(0), data);
      if (replacementNode != null) {
        const node = $el.get(0);
        if (node !== void 0) {
          $el.get(0)?.parentNode?.replaceChild(replacementNode, node);
        }
      }
      $el = domini_default()(selector).first();
      if (updateLocation) {
        document.title = dataNode.title;
        history.pushState({}, "", url);
      }
      domini_default()(selector).first().find(".woocommerce-ordering").on("change", "select.orderby", function() {
        domini_default()(this).closest("form").trigger("submit");
      });
      $this.addHighlightString(domini_default()(selector).find("a"));
      hooks_filters.Hooks.applyFilters("asl/live_load/finished", url, $this, selector, $el.get(0));
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
  if (domini_default()(selector).length < 1) {
    altSel.forEach(function(s) {
      if (domini_default()(s).length > 0) {
        selector = s;
        return false;
      }
    });
    if (domini_default()(selector).length < 1) {
      console.log("Ajax Search Lite: The live search selector does not exist on the page.");
      return false;
    }
  }
  selector = hooks_filters.Hooks.applyFilters("asl/live_load/selector", selector, this);
  let $el = domini_default()(selector).first(), $this = this;
  $this.searchAbort();
  $el.css("opacity", 0.4);
  hooks_filters.Hooks.applyFilters("asl/live_load/start", url, $this, selector, $el.get(0));
  if (!forceAjax && $this.n("searchsettings").find("input[name=filters_initial]").val() == 1 && $this.n("text").val() == "") {
    (0,interval_until_execute.intervalUntilExecute)(function() {
      process(live_ASL.pageHTML);
    }, function() {
      return live_ASL.pageHTML != "";
    });
  } else {
    $this.searching = true;
    $this.post = domini_default().fn.ajax({
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
AslPlugin_AslPlugin.prototype.getCurrentLiveURL = function() {
  let $this = this;
  let url = "asl_ls=" + (0,other.nicePhrase)($this.n("text").val()), start = "&", location2 = window.location.href;
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
AslPlugin_AslPlugin.prototype.showLoader = function() {
  this.n("proloading").css({
    display: "block"
  });
};
AslPlugin_AslPlugin.prototype.hideLoader = function() {
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
AslPlugin_AslPlugin.prototype.loadASLFonts = function() {
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
AslPlugin_AslPlugin.prototype.updateHref = function() {
  if (this.o.trigger.update_href && !this.usingLiveLoader()) {
    let url = this.getStateURL() + (this.resultsOpened ? "&asl_s=" : "&asl_ls=") + this.n("text").val();
    history.replaceState("", "", url.replace(location.origin, ""));
  }
};
AslPlugin_AslPlugin.prototype.fixClonedSelf = function() {
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
AslPlugin_AslPlugin.prototype.destroy = function() {
  let $this = this;
  Object.keys($this.nodes).forEach(function(k) {
    $this.nodes[k].off?.();
  });
  $this.n("searchsettings").remove?.();
  $this.n("resultsDiv").remove?.();
  $this.n("search").remove?.();
  $this.n("container").remove?.();
  $this.documentEventHandlers.forEach(function(h) {
    domini_default()(h.node).off(h.event, h.handler);
  });
};
/* harmony default export */ var actions_other = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/actions/redirect.ts






AslPlugin_AslPlugin.prototype.isRedirectToFirstResult = function() {
  return Boolean((this.n("resultsDiv").find(".asl_res_url").length > 0 || domini_default()(".asl_es_" + this.o.id + " a").length > 0 || this.o.resPage.useAjax && domini_default()(this.o.resPage.selector + "a").length > 0) && (this.o.redirectOnClick && this.ktype == "click" && this.o.trigger.click == "first_result" || this.o.redirectOnEnter && (this.ktype == "input" || this.ktype == "keyup") && this.keycode == 13 && this.o.trigger.return == "first_result"));
};
AslPlugin_AslPlugin.prototype.doRedirectToFirstResult = function() {
  let _loc, url = "";
  if (this.ktype == "click") {
    _loc = this.o.trigger.click_location;
  } else {
    _loc = this.o.trigger.return_location;
  }
  if (this.n("resultsDiv").find(".asl_res_url").length > 0) {
    url = domini_default()(this.n("resultsDiv").find(".asl_res_url").get(0)).attr("href");
  } else if (domini_default()(".asl_es_" + this.o.id + " a").length > 0) {
    url = domini_default()(domini_default()(".asl_es_" + this.o.id + " a").get(0)).attr("href");
  } else if (this.o.resPage.useAjax && domini_default()(this.o.resPage.selector + "a").length > 0) {
    url = domini_default()(domini_default()(this.o.resPage.selector + "a").get(0)).attr("href");
  }
  if (url !== "") {
    if (_loc == "same") {
      window.location.href = url;
    } else {
      (0,browser.openInNewTab)(url);
    }
    this.hideLoader();
    this.hideResults();
  }
  return false;
};
AslPlugin_AslPlugin.prototype.doRedirectToResults = function(ktype) {
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
      (0,browser.submitToUrl)(url, "post", {
        asl_active: 1,
        p_asl_data: this.n("searchsettings").find("form").serialize()
      }, _loc);
    } else {
      if (_loc == "same") {
        location.href = url;
      } else {
        (0,browser.openInNewTab)(url);
      }
    }
  } else {
    (0,browser.submitToUrl)(url, "post", {
      np_asl_data: this.n("searchsettings").find("form").serialize()
    }, _loc);
  }
  this.n("proloading").css("display", "none");
  this.hideLoader();
  this.hideResults();
  this.searchAbort();
};
AslPlugin_AslPlugin.prototype.getRedirectURL = function(ktype = "enter") {
  let url, source, final, base_url;
  if (ktype == "click") {
    source = this.o.trigger.click;
  } else {
    source = this.o.trigger.return;
  }
  if (source == "results_page" || source == "ajax_search") {
    url = "?s=" + (0,other.nicePhrase)(this.n("text").val());
  } else if (source == "woo_results_page") {
    url = "?post_type=product&s=" + (0,other.nicePhrase)(this.n("text").val());
  } else {
    base_url = this.o.trigger.redirect_url;
    url = base_url.replace(/{phrase}/g, (0,other.nicePhrase)(this.n("text").val()));
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
  final = hooks_filters.Hooks.applyFilters("asl/redirect/url", final, this.o.id, this.o.iid);
  return final;
};
/* harmony default export */ var redirect = ((/* unused pure expression or super */ null && (AslPlugin)));

// EXTERNAL MODULE: ./src/client/global/utils/device.ts
var device = __webpack_require__(451);
;// ./src/client/plugin/core/actions/results.ts




AslPlugin_AslPlugin.prototype.showResults = function() {
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
AslPlugin_AslPlugin.prototype.hideResults = function(blur = true) {
  let $this = this;
  if (!$this.resultsOpened) return false;
  $this.n("resultsDiv").removeClass($this.resAnim.showClass).addClass($this.resAnim.hideClass);
  setTimeout(function() {
    $this.n("resultsDiv").css($this.resAnim.hideCSS);
  }, $this.resAnim.duration);
  $this.n("proclose").css({
    display: "none"
  });
  if ((0,device.isMobile)() && blur) {
    document.activeElement?.blur();
  }
  $this.resultsOpened = false;
  $this.n("s").trigger("asl_results_hide", [$this.o.id, $this.o.iid], true, true);
};
AslPlugin_AslPlugin.prototype.showResultsBox = function() {
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
AslPlugin_AslPlugin.prototype.addHighlightString = function($items) {
  let $this = this, phrase = $this.n("text").val().replace(/["']/g, "");
  $items = typeof $items == "undefined" ? $this.n("items").find("a.asl_res_url") : $items;
  if ($this.o.singleHighlight && phrase != "" && $items.length > 0) {
    $items.forEach(function(el) {
      try {
        const url = new URL(domini_default()(el).attr("href"));
        url.searchParams.set("asl_highlight", phrase);
        url.searchParams.set("p_asid", String($this.o.id));
        domini_default()(el).attr("href", url.href);
      } catch (e) {
      }
    });
  }
};
AslPlugin_AslPlugin.prototype.scrollToResults = function() {
  let $this = this, tolerance = Math.floor(window.innerHeight * 0.1), stop;
  if (!$this.resultsOpened || !$this.o.scrollToResults.enabled || $this.n("resultsDiv").inViewPort(tolerance)) return;
  if ($this.o.resultsposition == "hover") {
    stop = $this.n("probox").offset().top - 20;
  } else {
    stop = $this.n("resultsDiv").offset().top - 20;
  }
  stop = stop + $this.o.scrollToResults.offset;
  let $adminbar = domini_default()("#wpadminbar");
  if ($adminbar.length > 0)
    stop -= $adminbar.height();
  stop = stop < 0 ? 0 : stop;
  window.scrollTo({ top: stop, behavior: "smooth" });
};
/* harmony default export */ var results = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/actions/search.ts






const search_ASL = window.ASL;
AslPlugin_AslPlugin.prototype.searchAbort = function() {
  let $this = this;
  if ($this.post != null) {
    $this.post.abort();
  }
};
AslPlugin_AslPlugin.prototype.searchWithCheck = function(timeout = 50) {
  let $this = this;
  if ($this.n("text").val().length < $this.o.charcount) return;
  $this.searchAbort();
  clearTimeout($this.timeouts.searchWithCheck);
  $this.timeouts.searchWithCheck = setTimeout(function() {
    $this.search();
  }, timeout);
};
AslPlugin_AslPlugin.prototype.search = function() {
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
  data = hooks_filters.Hooks.applyFilters("asl/search/data", data);
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
  if (domini_default()(".asl_es_" + $this.o.id).length > 0) {
    $this.liveLoad(".asl_es_" + $this.o.id, $this.getCurrentLiveURL(), false);
  } else if ($this.o.resPage.useAjax) {
    $this.liveLoad($this.o.resPage.selector, $this.getRedirectURL());
  } else {
    $this.post = domini_default().fn.ajax({
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
        response = hooks_filters.Hooks.applyFilters("asl/search/html", response);
        $this.n("resdrg").html("");
        $this.n("resdrg").html(response);
        $this.n("resdrg").find(".asl_keyword").on("click", function() {
          $this.n("text").val(domini_default()(this).html());
          $this.n("container").find("input.orig").val(domini_default()(this).html()).trigger("keydown");
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
                url = "?s=" + (0,other.nicePhrase)($this.n("text").val());
              } else if (source == "woo_results_page") {
                url = "?post_type=product&s=" + (0,other.nicePhrase)($this.n("text").val());
              } else {
                url = $this.o.trigger.redirect_url.replace("{phrase}", (0,other.nicePhrase)($this.n("text").val()));
              }
              if ($this.o.overridewpdefault) {
                if ($this.o.override_method == "post") {
                  (0,browser.submitToUrl)($this.o.homeurl + url, "post", {
                    asl_active: 1,
                    p_asl_data: $this.n("searchsettings").find("form").serialize()
                  });
                } else {
                  location.href = $this.o.homeurl + url + "&asl_active=1&p_asid=" + $this.o.id + "&p_asl_data=1&" + $this.n("searchsettings").find("form").serialize();
                }
              } else {
                (0,browser.submitToUrl)($this.o.homeurl + url, "post", {
                  np_asl_data: $this.n("searchsettings").find("form").serialize()
                });
              }
            });
          }
        }
        hooks_filters.Hooks.applyFilters("asl/search/end", $this, data);
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


AslPlugin_AslPlugin.prototype.searchFor = function(phrase) {
  if (typeof phrase != "undefined") {
    this.n("text").val(phrase);
  }
  this.n("textAutocomplete").val("");
  this.search();
};
AslPlugin_AslPlugin.prototype.toggleSettings = function(state) {
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
AslPlugin_AslPlugin.prototype.closeResults = function(clear) {
  if (typeof clear != "undefined" && clear) {
    this.n("text").val("");
    this.n("textAutocomplete").val("");
  }
  this.hideResults();
  this.n("proloading").css("display", "none");
  this.hideLoader();
  this.searchAbort();
};
AslPlugin_AslPlugin.prototype.getStateURL = function() {
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
AslPlugin_AslPlugin.prototype.filtersInitial = function() {
  return this.n("searchsettings").find("input[name=filters_initial]").val() == 1;
};
AslPlugin_AslPlugin.prototype.filtersChanged = function() {
  return this.n("searchsettings").find("input[name=filters_changed]").val() == 1;
};
/* harmony default export */ var etc_api = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/etc/position.ts





AslPlugin_AslPlugin.prototype.detectAndFixFixedPositioning = function() {
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
AslPlugin_AslPlugin.prototype.fixResultsPosition = function(ignoreVisibility = false) {
  let $this = this, $body = domini_default()("body"), bodyTop = 0, rpos = $this.n("resultsDiv").css("position");
  if (domini_default()._fn.bodyTransformY() != 0 || $body.css("position") != "static") {
    bodyTop = $body.offset().top;
  }
  if (domini_default()._fn.bodyTransformY() != 0 && rpos == "fixed") {
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
    if (domini_default()._fn.bodyTransformX() != 0 || $body.css("position") != "static") {
      bodyLeft = $body.offset().left;
    }
    if (typeof _rposition != "undefined") {
      let vwidth, adjust = 0;
      if ((0,device.deviceType)() === "phone") {
        vwidth = $this.o.results.width_phone;
      } else if ((0,device.deviceType)() == "tablet") {
        vwidth = $this.o.results.width_tablet;
      } else {
        vwidth = $this.o.results.width;
      }
      if (vwidth == "auto") {
        vwidth = $this.n("search").outerWidth() < 240 ? 240 : $this.n("search").outerWidth();
      }
      $this.n("resultsDiv").css("width", (0,other.isNumeric)(vwidth) ? vwidth + "px" : vwidth);
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
AslPlugin_AslPlugin.prototype.fixSettingsPosition = function(ignoreVisibility = false) {
  let $this = this, $body = domini_default()("body"), bodyTop = 0, settPos = $this.n("searchsettings").css("position");
  if (domini_default()._fn.bodyTransformY() != 0 || $body.css("position") != "static") {
    bodyTop = $body.offset().top;
  }
  if (domini_default()._fn.bodyTransformY() != 0 && settPos == "fixed") {
    settPos = "absolute";
    $this.n("searchsettings").css("position", "absolute");
  }
  if (settPos == "fixed") {
    bodyTop = 0;
  }
  if (ignoreVisibility || $this.n("prosettings").data("opened") !== "0") {
    let $n, sPosition, top, left, bodyLeft = 0;
    if (domini_default()._fn.bodyTransformX() != 0 || $body.css("position") != "static") {
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
AslPlugin_AslPlugin.prototype.fixSettingsWidth = function() {
};
AslPlugin_AslPlugin.prototype.hideOnInvisibleBox = function() {
  let $this = this;
  if ($this.o.detectVisibility && !$this.n("search").hasClass("hiddend") && ($this.n("search").is(":hidden") || !$this.n("search").is(":visible"))) {
    $this.hideSettings?.();
    $this.hideResults();
  }
};
/* harmony default export */ var position = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/events/button.ts


AslPlugin_AslPlugin.prototype.initMagnifierEvents = function() {
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




AslPlugin_AslPlugin.prototype.initInputEvents = function() {
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
AslPlugin_AslPlugin.prototype._initFocusInput = function() {
  let $this = this;
  $this.n("text").on("click", function(e) {
    e.stopPropagation();
    e.stopImmediatePropagation();
    domini_default()(this).trigger("focus", []);
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
    if (domini_default()(this).val() != "") {
      $this.n("proclose").css("display", "block");
    } else {
      $this.n("proclose").css({
        display: "none"
      });
    }
  });
};
AslPlugin_AslPlugin.prototype._initSearchInput = function() {
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
AslPlugin_AslPlugin.prototype._initEnterEvent = function() {
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
    let isInput = domini_default()(this).hasClass("orig");
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
AslPlugin_AslPlugin.prototype._initFormEvent = function() {
  let $this = this;
  domini_default()($this.n("text").closest("form").get(0)).on("submit", function(e, args) {
    e.preventDefault();
    if ((0,device.isMobile)()) {
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



AslPlugin_AslPlugin.prototype.initNavigationEvents = function() {
  let $this = this;
  let handler = function(e) {
    let keycode = e.keyCode || e.which;
    if (
      // @ts-ignore
      domini_default()(".item", $this.n("resultsDiv")).length > 0 && $this.n("resultsDiv").css("display") != "none" && $this.o.resultstype == "vertical"
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
  domini_default()(document).on("keydown", handler);
};

;// ./src/client/plugin/core/events/other.ts




AslPlugin_AslPlugin.prototype.initOtherEvents = function() {
  let $this = this, handler, handler2;
  if ((0,device.isMobile)() && (0,device.detectIOS)()) {
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
    if (domini_default()(".asl_es_" + $this.o.id).length > 0) {
      $this.showLoader();
      $this.liveLoad(".asl_es_" + $this.o.id, $this.getCurrentLiveURL(), false);
    } else if ($this.o.resPage.useAjax) {
      $this.showLoader();
      $this.liveLoad($this.o.resPage.selector, $this.getRedirectURL());
    }
    $this.n("text").get(0).focus();
  });
  if ((0,device.isMobile)()) {
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
    domini_default()(window).on("orientationchange", handler);
  } else {
    handler = function() {
      $this.resize();
    };
    $this.documentEventHandlers.push({
      "node": window,
      "event": "resize",
      "handler": handler
    });
    domini_default()(window).on("resize", handler, { passive: true });
  }
  handler2 = function() {
    $this.scrolling(false);
  };
  $this.documentEventHandlers.push({
    "node": window,
    "event": "scroll",
    "handler": handler2
  });
  domini_default()(window).on("scroll", handler2, { passive: true });
  if ((0,device.isMobile)() && $this.o.mobile.menu_selector != "") {
    domini_default()($this.o.mobile.menu_selector).on("touchend", function() {
      let _this = this;
      setTimeout(function() {
        let $input = domini_default()(_this).find("input.orig");
        $input = $input.length == 0 ? domini_default()(_this).next().find("input.orig") : $input;
        $input = $input.length == 0 ? domini_default()(_this).parent().find("input.orig") : $input;
        $input = $input.length == 0 ? $this.n("text") : $input;
        if ($this.n("search").inViewPort()) {
          $input.get(0).focus();
        }
      }, 300);
    });
  }
  if ((0,device.detectIOS)() && (0,device.isMobile)() && (0,device.isTouchDevice)()) {
    if (parseInt($this.n("text").css("font-size")) < 16) {
      $this.n("text").data("fontSize", $this.n("text").css("font-size")).css("font-size", "16px");
      $this.n("textAutocomplete").css("font-size", "16px");
      domini_default()("body").append("<style>#ajaxsearchlite" + $this.o.rid + " input.orig::-webkit-input-placeholder{font-size: 16px !important;}</style>");
    }
  }
};
AslPlugin_AslPlugin.prototype.orientationChange = function() {
  let $this = this;
  $this.detectAndFixFixedPositioning();
  $this.fixSettingsPosition();
  $this.fixResultsPosition();
};
AslPlugin_AslPlugin.prototype.resize = function() {
  let $this = this;
  $this.detectAndFixFixedPositioning();
  $this.fixSettingsPosition();
  $this.fixResultsPosition();
};
AslPlugin_AslPlugin.prototype.scrolling = function(ignoreVisibility) {
  let $this = this;
  $this.detectAndFixFixedPositioning();
  $this.hideOnInvisibleBox();
  $this.fixSettingsPosition(ignoreVisibility);
  $this.fixResultsPosition(ignoreVisibility);
};

;// ./src/client/plugin/core/events/results.ts



AslPlugin_AslPlugin.prototype.initResultsEvents = function() {
  let $this = this;
  $this.n("resultsDiv").css({
    opacity: "0"
  });
  let handler = function(e) {
    let keycode = e.keyCode || e.which, ktype = e.type;
    if (domini_default()(e.target).closest(".asl_w").length == 0) {
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
  domini_default()(document).on($this.clickTouchend, handler);
  $this.n("resultsDiv").on("click", ".results .item", function() {
    $this.gaEvent?.("result_click", {
      "result_title": domini_default()(this).find("a.asl_res_url").text(),
      "result_url": domini_default()(this).find("a.asl_res_url").attr("href")
    });
  });
};
/* harmony default export */ var events_results = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/events/touch.ts



AslPlugin_AslPlugin.prototype.monitorTouchMove = function() {
  let $this = this;
  $this.dragging = false;
  domini_default()("body").on("touchmove", function() {
    $this.dragging = true;
  }).on("touchstart", function() {
    $this.dragging = false;
  });
};
/* harmony default export */ var touch = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/init/init.ts



AslPlugin_AslPlugin.prototype.init = function(options, elem) {
  this.o = { ...this.o, ...options };
  this.nodes = {};
  this.nodes.search = domini_default()(elem);
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
AslPlugin_AslPlugin.prototype.n = function(k) {
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
        this.nodes[k] = domini_default()("#wpdreams_asl_settings_" + this.o.id);
        break;
      case "resultsAppend":
        this.nodes[k] = domini_default()("#wpdreams_asl_results_" + this.o.id);
        break;
      case "trythis":
        this.nodes[k] = domini_default()("#asp-try-" + this.o.rid);
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
AslPlugin_AslPlugin.prototype.initNodeVariables = function() {
  let $this = this;
  $this.o.id = parseInt($this.nodes.search.data("id"));
  $this.o.iid = parseInt($this.nodes.search.data("instance"));
  $this.o.rid = $this.o.id + "_" + $this.o.iid;
  $this.fixClonedSelf();
};
AslPlugin_AslPlugin.prototype.initEvents = function() {
  this.initSettingsSwitchEvents?.();
  this.initOtherEvents();
  this.initMagnifierEvents();
  this.initInputEvents();
};
/* harmony default export */ var init = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/init/results.ts




AslPlugin_AslPlugin.prototype.initResults = function() {
  if (!this.resultsInitialized) {
    this.initResultsBox();
    this.initResultsEvents();
    this.initNavigationEvents?.();
  }
};
AslPlugin_AslPlugin.prototype.initResultsBox = function() {
  let $this = this;
  $this.initResultsAnimations();
  if ((0,device.isMobile)() && $this.o.mobile.force_res_hover == 1) {
    $this.o.resultsposition = "hover";
    $this.nodes.resultsDiv = $this.n("resultsDiv").clone();
    domini_default()("body").append($this.nodes.resultsDiv);
    $this.nodes.resultsDiv.css({
      "position": "absolute"
    });
    $this.detectAndFixFixedPositioning();
  } else {
    if ($this.o.resultsposition == "hover" && $this.n("resultsAppend").length <= 0) {
      $this.nodes.resultsDiv = $this.n("resultsDiv").clone();
      domini_default()("body").append($this.n("resultsDiv"));
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
AslPlugin_AslPlugin.prototype.initResultsAnimations = function() {
  this.n("resultsDiv").css({
    "-webkit-animation-duration": this.resAnim.duration + "ms",
    "animation-duration": this.resAnim.duration + "ms"
  });
};
/* harmony default export */ var init_results = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/bundle/optimized/core.ts




















;// ./src/client/bundle/optimized/ga.ts



;// ./src/client/plugin/core/actions/autocomplete.ts



"use strict";
AslPlugin_AslPlugin.prototype.autocompleteGoogleOnly = function() {
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
    domini_default().fn.ajax({
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
          response = domini_default()("<textarea />").html(response).text();
          response = response.substr(val.length);
          $this.n("textAutocomplete").val(val + response);
        }
      }
    });
  }
};
AslPlugin_AslPlugin.prototype.fixAutocompleteScrollLeft = function() {
  const autoCompleteEl = this.n("textAutocomplete").get(0);
  if (autoCompleteEl === void 0) {
    console.warn("textAutocomplete missing");
    return;
  }
  autoCompleteEl.scrollLeft = this.n("text").get(0)?.scrollLeft ?? 0;
};
/* harmony default export */ var autocomplete = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/events/autocomplete.ts



AslPlugin_AslPlugin.prototype.initAutocompleteEvent = function() {
  let $this = this;
  if (!$this.o.autocomplete.enabled) {
    return;
  }
  $this.n("text").on("keyup", function(e) {
    $this.keycode = e.keyCode || e.which;
    $this.ktype = e.type;
    let thekey = 39;
    if (domini_default()("body").hasClass("rtl"))
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




;// ./src/client/plugin/core/actions/results_vertical.ts



AslPlugin_AslPlugin.prototype.showVerticalResults = function() {
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
          h += domini_default()(el).outerHeight(true);
          if (domini_default()(el).outerHeight(true) > highest)
            highest = domini_default()(el).outerHeight(true);
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
              final_h += domini_default()(el).outerHeight(true);
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



;// ./src/client/plugin/core/actions/settings.ts


AslPlugin_AslPlugin.prototype.showSettings = function() {
  let $this = this;
  $this.initSettings?.();
  $this.n("searchsettings").css($this.settAnim.showCSS);
  $this.n("searchsettings").removeClass($this.settAnim.hideClass).addClass($this.settAnim.showClass);
  $this.n("prosettings").data("opened", 1);
  $this.fixSettingsPosition(true);
};
AslPlugin_AslPlugin.prototype.hideSettings = function() {
  let $this = this;
  $this.initSettings?.();
  $this.n("searchsettings").removeClass($this.settAnim.showClass).addClass($this.settAnim.hideClass);
  setTimeout(function() {
    $this.n("searchsettings").css($this.settAnim.hideCSS);
  }, $this.settAnim.duration);
  $this.n("prosettings").data("opened", 0);
};
/* harmony default export */ var settings = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/events/facet.ts



AslPlugin_AslPlugin.prototype.initFacetEvents = function() {
  let $this = this;
  if (!$this.o.trigger.facet) {
    return;
  }
  $this.n("searchsettings").find("input[type=checkbox]").on("asl_chbx_change", function(e) {
    $this.ktype = e.type;
    $this.n("searchsettings").find("input[name=filters_changed]").val(1);
    $this.gaEvent?.("facet_change", {
      "option_label": domini_default()(this).closest("fieldset").find("legend").text(),
      "option_value": domini_default()(this).closest(".asl_option").find(".asl_option_label").text() + (domini_default()(this).prop("checked") ? "(checked)" : "(unchecked)")
    });
    $this.setFilterStateInput(65);
    $this.searchWithCheck(80);
  });
};

;// ./src/client/plugin/core/events/settings.ts




AslPlugin_AslPlugin.prototype.initSettingsSwitchEvents = function() {
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
AslPlugin_AslPlugin.prototype.initSettingsEvents = function() {
  let $this = this, t;
  let formDataHandler = function() {
    if (typeof $this.originalFormData === "undefined") {
      $this.originalFormData = (0,browser.formData)($this.n("searchsettings").find("form"));
    }
    $this.n("searchsettings").off("mousedown touchstart mouseover", formDataHandler);
  };
  $this.n("searchsettings").on("mousedown touchstart mouseover", formDataHandler);
  let handler = function(e) {
    if (domini_default()(e.target).closest(".asl_w").length == 0) {
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
  domini_default()(document).on($this.clickTouchend, handler);
  $this.n("searchsettings").on("click", function() {
    $this.settingsChanged = true;
  });
  $this.n("searchsettings").on($this.clickTouchend, function(e) {
    $this.updateHref();
    if (typeof e.target != "undefined" && !domini_default()(e.target).hasClass("noUi-handle")) {
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
    domini_default()(this).find('input[type="checkbox"]').prop("checked", !domini_default()(this).find('input[type="checkbox"]').prop("checked"));
    clearTimeout(t);
    let _this = this;
    t = setTimeout(function() {
      domini_default()(_this).find('input[type="checkbox"]').trigger("asl_chbx_change", []);
    }, 50);
  });
  $this.n("searchsettings").find("div.asl_option label").on("click", function(e) {
    e.preventDefault();
  });
  $this.n("searchsettings").find("fieldset.asl_checkboxes_filter_box").forEach(function() {
    let all_unchecked = true;
    domini_default()(this).find('.asl_option:not(.asl_option_selectall) input[type="checkbox"]').forEach(function() {
      if (domini_default()(this).prop("checked") == true) {
        all_unchecked = false;
        return false;
      }
    });
    if (all_unchecked) {
      domini_default()(this).find('.asl_option_selectall input[type="checkbox"]').prop("checked", false).removeAttr("data-origvalue");
    }
  });
  $this.n("searchsettings").find("fieldset").forEach(function() {
    domini_default()(this).find(".asl_option:not(.hiddend)").last().addClass("asl-o-last");
  });
  $this.n("searchsettings").find('.asl_option_cat input[type="checkbox"], .asl_option_cff input[type="checkbox"]').on(
    "asl_chbx_change",
    function() {
      let className = domini_default()(this).data("targetclass");
      if (typeof className == "string" && className != "")
        $this.n("searchsettings").find("input." + className).prop("checked", domini_default()(this).prop("checked"));
    }
  );
};
/* harmony default export */ var events_settings = ((/* unused pure expression or super */ null && (AslPlugin)));

;// ./src/client/plugin/core/init/settings.ts




AslPlugin_AslPlugin.prototype.initSettings = function() {
  if (!this.settingsInitialized) {
    this.loadASLFonts?.();
    this.initSettingsBox?.();
    this.initSettingsEvents?.();
    this.initFacetEvents?.();
  }
};
AslPlugin_AslPlugin.prototype.initSettingsBox = function() {
  let $this = this;
  let appendSettingsTo = function($el) {
    let old = $this.n("searchsettings").get(0);
    $this.nodes.searchsettings = $this.n("searchsettings").clone();
    $el.append($this.n("searchsettings"));
    domini_default()(old).find("*[id]").forEach(function(el) {
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
  appendSettingsTo(domini_default()("body"));
  $this.n("searchsettings").get(0).id = $this.n("searchsettings").get(0).id.replace("__original__", "");
  $this.detectAndFixFixedPositioning();
  $this.settingsInitialized = true;
};
AslPlugin_AslPlugin.prototype.initSettingsAnimations = function() {
  let $this = this;
  const animOptions = (0,device.isMobile)() ? $this.o.animations.mob : $this.o.animations.pc;
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






;// ./src/client/plugin/core/AjaxSearchLite.ts


const AjaxSearchLite_AjaxSearchLite = {
  plugin: new AslPlugin_AslPlugin(),
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

;// ./src/client/addons/woocommerce.ts



class WooCommerceAddToCartAddon {
  name = "WooCommerce Add To Cart";
  requests = [];
  $liveRegion = void 0;
  init() {
    hooks_filters.Hooks.addFilter("asl/search/end", this.finished.bind(this), 10, this);
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

;// ./src/client/plugin/wrapper/loader.ts


function loader_loader() {
  (0,interval_until_execute.intervalUntilExecute)(() => window.ASL.init(), function() {
    return typeof window.ASL.version != "undefined";
  });
}

;// ./src/client/bundle/optimized/load.ts






window.WPD.AjaxSearchLite = core_AjaxSearchLite;
domini_default()._fn.plugin("ajaxsearchlite", core_AjaxSearchLite.plugin);
loader_loader();

;// ./src/client/bundle/merged/asl.ts









}();
window.AjaxSearchLite = __webpack_exports__["default"];
/******/ })()
;