;(function (g, f) {
  if (typeof define === "function" && define.amd) {
    define([], f);
  } else if (typeof module !== "undefined" && module.exports) {
    module.exports = f();
  } else {
    g.Shuffle = f();
  }
}(typeof globalThis !== "undefined" ? globalThis : typeof self !== "undefined" ? self : this, function () {
"use strict";
var exports = {}, module = { exports: exports };
