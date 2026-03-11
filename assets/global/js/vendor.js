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

"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;
var _ShuffleItem, _transitions, _count, _uniqueId, _Shuffle, _transitionManager, _queue, _rafId, _resizeObserver, _getElementOption, _validateStyles, _filter, _getFilteredSets, _doesPassFilter, _updateItemCount, _getItems, _mergeNewItems, _getFilteredItems, _getConcealedItems, _getColumnSize, _getGutterSize, _setColumns, _setContainerSize, _getContainerSize, _getStaggerAmount, _resetCols, _layout, _getNextPositions, _getItemPosition, _shrink, _handleResizeCallback, _whenTransitionDone, _getTransitionFunction, _processQueue, _startTransitions, _cancelMovement, _movementFinished;
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _callSuper(_this, derived, args) {
  function isNativeReflectConstruct() {
    if (typeof Reflect === "undefined" || !Reflect.construct) return false;
    if (Reflect.construct.sham) return false;
    if (typeof Proxy === "function") return true;
    try {
      return !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
    } catch (e) {
      return false;
    }
  }
  derived = _getPrototypeOf(derived);
  return _possibleConstructorReturn(_this, isNativeReflectConstruct() ? Reflect.construct(derived, args || [], _getPrototypeOf(_this).constructor) : derived.apply(_this, args));
}
function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }
function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }
function _get() { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get.bind(); } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(arguments.length < 3 ? target : receiver); } return desc.value; }; } return _get.apply(this, arguments); }
function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }
function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }
function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }
function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }
function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
function _classPrivateFieldSet(receiver, privateMap, value) { var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
function _classPrivateFieldGet(receiver, privateMap) { var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
function _classExtractFieldDescriptor(receiver, privateMap, action) { if (!privateMap.has(receiver)) { throw new TypeError("attempted to " + action + " private field on non-instance"); } return privateMap.get(receiver); }
function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
//#region src/tiny-emitter.ts
/**
* @fileoverview copy of `tiny-emitter` on npm, but converted to ESM
*/
var TinyEmitter = /*#__PURE__*/function () {
  function TinyEmitter() {
    _classCallCheck(this, TinyEmitter);
    _defineProperty(this, "handlers", void 0);
  }
  return _createClass(TinyEmitter, [{
    key: "on",
    value: function on(event, callback, ctx) {
      var _this$handlers;
      var handlers = (_this$handlers = this.handlers) !== null && _this$handlers !== void 0 ? _this$handlers : this.handlers = {};
      (handlers[event] || (handlers[event] = [])).push({
        fn: callback,
        ctx: ctx
      });
      return this;
    }
  }, {
    key: "once",
    value: function once(event, callback, ctx) {
      var self = this;
      function listener() {
        self.off(event, listener);
        callback.apply(ctx, arguments);
      }
      listener.onceCb = callback;
      return this.on(event, listener, ctx);
    }
  }, {
    key: "emit",
    value: function emit(event) {
      var _this$handlers2;
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }
      var data = args;
      var evtArr = _toConsumableArray(((_this$handlers2 = this.handlers) !== null && _this$handlers2 !== void 0 ? _this$handlers2 : this.handlers = {})[event] || []);
      var i = 0;
      var len = evtArr.length;
      for (; i < len; i += 1) evtArr[i].fn.apply(evtArr[i].ctx, data);
      return this;
    }
  }, {
    key: "off",
    value: function off(event, callback) {
      var _this$handlers3;
      var handlers = (_this$handlers3 = this.handlers) !== null && _this$handlers3 !== void 0 ? _this$handlers3 : this.handlers = {};
      var evts = handlers[event];
      var liveEvents = [];
      if (evts && callback) {
        for (var i = 0, len = evts.length; i < len; i += 1) if (evts[i].fn !== callback && evts[i].fn.onceCb !== callback) liveEvents.push(evts[i]);
      }
      if (liveEvents.length > 0) handlers[event] = liveEvents;else delete handlers[event];
      return this;
    }
  }]);
}();

//#endregion
//#region src/parallel.ts
/**
* Execute an array of functions in parallel.
* Calls the callback with an error if any function fails,
* otherwise calls it with null and an array of results.
*/
function parallel(fns, context, callback) {
  if (!callback) if (typeof context === "function") {
    callback = context;
    context = null;
  } else callback = noop;
  var pending = fns && fns.length;
  if (!pending) {
    callback(null, []);
    return;
  }
  var finished = false;
  var results = new Array(pending);
  for (var i = 0, len = fns.length; i < len; i++) fns[i].call(context, maybeDone(i));
  function maybeDone(i) {
    return function onDoneCheck(err, result) {
      if (finished) return;
      if (err) {
        callback(err, results);
        finished = true;
        return;
      }
      results[i] = result;
      if (! --pending) callback(null, results);
    };
  }
}
function noop() {}

//#endregion
//#region src/helpers.ts
/**
* Change a property or execute a function which will not have a transition
* @param elements DOM elements that won't be transitioned.
* @param callback A function which will be called while transition is set to 0ms.
*/
function skipTransitions(elements, callback) {
  var zero = "0ms";
  var data = elements.map(function (element) {
    var style = element.style;
    var duration = style.transitionDuration;
    var delay = style.transitionDelay;
    style.transitionDuration = zero;
    style.transitionDelay = zero;
    return {
      duration: duration,
      delay: delay
    };
  });
  callback();
  elements[0].offsetWidth;
  for (var i = 0; i < elements.length; i += 1) {
    elements[i].style.transitionDuration = data[i].duration;
    elements[i].style.transitionDelay = data[i].delay;
  }
}
/**
* Apply styles without a transition.
* Array of transition objects.
*/
function styleImmediately(objects) {
  if (objects.length > 0) skipTransitions(objects.map(function (obj) {
    return obj.item.element;
  }), function () {
    var _iterator = _createForOfIteratorHelper(objects),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var obj = _step.value;
        obj.item.applyCss(obj.styles);
        obj.callback();
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  });
}
function arrayUnique(items) {
  return _toConsumableArray(new Set(items));
}
var EXPECTED_WIDTH = 10;
var value = null;
function testComputedSize() {
  if (value !== null) return value;
  var element = document.body || document.documentElement;
  var div = document.createElement("div");
  div.style.cssText = "width:10px;padding:2px;box-sizing:border-box;";
  element.append(div);
  var _globalThis$getComput = globalThis.getComputedStyle(div, null),
    width = _globalThis$getComput.width;
  value = Math.round(getNumber(width)) === EXPECTED_WIDTH;
  div.remove();
  return value;
}
var DEFAULT_NUMBER = 0;
/**
* Always returns a numeric value, given a value. Logic from jQuery's `isNumeric`.
* @param value Possibly numeric value.
* @return `value` or zero if `value` isn't numeric.
*/
function getNumber(value) {
  return Number.parseFloat(String(value)) || DEFAULT_NUMBER;
}
/**
* Retrieve the computed style for an element, parsed as a float.
* @param element Element to get style for.
* @param style Style property.
* @param styles Optionally include clean styles to use instead of asking for
* them again.
* @return The parsed computed value or zero if that fails because IE will
* return 'auto' when the element doesn't have margins instead of the computed style.
*/
function getNumberStyle(element, style) {
  var styles = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : globalThis.getComputedStyle(element, null);
  var value = getNumber(styles[style]);
  if (!testComputedSize() && style === "width") value += getNumber(styles.paddingLeft) + getNumber(styles.paddingRight) + getNumber(styles.borderLeftWidth) + getNumber(styles.borderRightWidth);else if (!testComputedSize() && style === "height") value += getNumber(styles.paddingTop) + getNumber(styles.paddingBottom) + getNumber(styles.borderTopWidth) + getNumber(styles.borderBottomWidth);
  return value;
}
/**
* Returns the outer width of an element, optionally including its margins.
*
* There are a few different methods for getting the width of an element, none of
* which work perfectly for all Shuffle's use cases.
*
* 1. getBoundingClientRect() `left` and `right` properties.
*   - Accounts for transform scaled elements, making it useless for Shuffle
*   elements which have shrunk.
* 2. The `offsetWidth` property.
*   - This value stays the same regardless of the elements transform property,
*   however, it does not return subpixel values.
* 3. getComputedStyle()
*   - This works great Chrome, Firefox, Safari, but IE<=11 does not include
*   padding and border when box-sizing: border-box is set, requiring a feature
*   test and extra work to add the padding back for IE and other browsers which
*   follow the W3C spec here.
*
* @param element The element.
* @param includeMargins Whether to include margins.
* @return The width and height.
*/
function getSize(element) {
  var includeMargins = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  var styles = globalThis.getComputedStyle(element, null);
  var width = getNumberStyle(element, "width", styles);
  var height = getNumberStyle(element, "height", styles);
  if (includeMargins) {
    var marginLeft = getNumberStyle(element, "marginLeft", styles);
    var marginRight = getNumberStyle(element, "marginRight", styles);
    var marginTop = getNumberStyle(element, "marginTop", styles);
    var marginBottom = getNumberStyle(element, "marginBottom", styles);
    width += marginLeft + marginRight;
    height += marginTop + marginBottom;
  }
  return {
    width: width,
    height: height
  };
}

//#endregion
//#region src/point.ts
var Point = /*#__PURE__*/function () {
  /**
  * Represents a coordinate pair.
  * @param x X coordinate.
  * @param y Y coordinate.
  */
  function Point(x, y) {
    _classCallCheck(this, Point);
    _defineProperty(this, "x", void 0);
    _defineProperty(this, "y", void 0);
    this.x = getNumber(x);
    this.y = getNumber(y);
  }
  /**
  * Whether two points are equal.
  * @param pointA Point A.
  * @param pointB Point B.
  * @return Whether the points are equal.
  */
  return _createClass(Point, null, [{
    key: "equals",
    value: function equals(pointA, pointB) {
      return pointA.x === pointB.x && pointA.y === pointB.y;
    }
  }]);
}();

//#endregion
//#region src/rect.ts
var Rect = /*#__PURE__*/function () {
  /**
  * Class for representing rectangular regions.
  * https://github.com/google/closure-library/blob/master/closure/goog/math/rect.js
  * @param x Left.
  * @param y Top.
  * @param width Width.
  * @param height Height.
  * @param id Identifier.
  */
  function Rect(x, y, width, height, id) {
    _classCallCheck(this, Rect);
    _defineProperty(this, "id", void 0);
    _defineProperty(this, "left", void 0);
    _defineProperty(this, "top", void 0);
    _defineProperty(this, "width", void 0);
    _defineProperty(this, "height", void 0);
    this.id = id;
    this.left = x;
    this.top = y;
    this.width = width;
    this.height = height;
  }
  /**
  * Returns whether two rectangles intersect.
  * @param rectA A rectangle.
  * @param rectB A rectangle.
  * @return Whether a and b intersect.
  */
  return _createClass(Rect, null, [{
    key: "intersects",
    value: function intersects(rectA, rectB) {
      return rectA.left < rectB.left + rectB.width && rectB.left < rectA.left + rectA.width && rectA.top < rectB.top + rectB.height && rectB.top < rectA.top + rectA.height;
    }
  }]);
}();

//#endregion
//#region src/constants.ts
var Classes = {
  BASE: "shuffle",
  SHUFFLE_ITEM: "shuffle-item",
  VISIBLE: "shuffle-item--visible",
  HIDDEN: "shuffle-item--hidden"
};
var ALL_ITEMS = "all";
var FILTER_ATTRIBUTE_KEY = "groups";
var FilterMode = {
  ANY: "any",
  ALL: "all"
};
var EventType = {
  LAYOUT: "shuffle:layout",
  REMOVED: "shuffle:removed"
};
var DEFAULT_OPTIONS = {
  group: ALL_ITEMS,
  speed: 250,
  easing: "cubic-bezier(0.4, 0.0, 0.2, 1)",
  itemSelector: "*",
  sizer: null,
  gutterWidth: 0,
  columnWidth: 0,
  delimiter: null,
  buffer: 0,
  columnThreshold: .01,
  initialSort: null,
  staggerAmount: 15,
  staggerAmountMax: 150,
  useTransforms: true,
  filterMode: FilterMode.ANY,
  isCentered: false,
  isRTL: false,
  roundTransforms: true
};

//#endregion
//#region src/shuffle-item.ts
var id$1 = 0;
var ShuffleItem = (_ShuffleItem = /*#__PURE__*/function () {
  function ShuffleItem(element, isRTL) {
    _classCallCheck(this, ShuffleItem);
    _defineProperty(this, "id", void 0);
    _defineProperty(this, "element", void 0);
    _defineProperty(this, "isRTL", void 0);
    _defineProperty(this, "isVisible", void 0);
    _defineProperty(this, "isHidden", void 0);
    _defineProperty(this, "scale", 1);
    _defineProperty(this, "point", new Point());
    id$1 += 1;
    this.id = id$1;
    this.element = element;
    /**
    * Set correct direction of item
    */
    this.isRTL = Boolean(isRTL);
    /**
    * Used to separate items for layout and shrink.
    */
    this.isVisible = true;
    /**
    * Used to determine if a transition will happen. By the time the _layout
    * and _shrink methods get the ShuffleItem instances, the `isVisible` value
    * has already been changed by the separation methods, so this property is
    * needed to know if the item was visible/hidden before the shrink/layout.
    */
    this.isHidden = false;
  }
  return _createClass(ShuffleItem, [{
    key: "show",
    value: function show() {
      this.isVisible = true;
      this.element.classList.remove(Classes.HIDDEN);
      this.element.classList.add(Classes.VISIBLE);
      this.element.removeAttribute("aria-hidden");
    }
  }, {
    key: "hide",
    value: function hide() {
      this.isVisible = false;
      this.element.classList.remove(Classes.VISIBLE);
      this.element.classList.add(Classes.HIDDEN);
      this.element.setAttribute("aria-hidden", "true");
    }
  }, {
    key: "init",
    value: function init() {
      this.addClasses([Classes.SHUFFLE_ITEM, Classes.VISIBLE]);
      this.applyCss(ShuffleItem.Css.INITIAL);
      this.applyCss(this.isRTL ? ShuffleItem.Css.DIRECTION.rtl : ShuffleItem.Css.DIRECTION.ltr);
      this.scale = ShuffleItem.Scale.VISIBLE;
      this.point = new Point();
    }
  }, {
    key: "addClasses",
    value: function addClasses(classes) {
      var _iterator2 = _createForOfIteratorHelper(classes),
        _step2;
      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var className = _step2.value;
          this.element.classList.add(className);
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }
    }
  }, {
    key: "removeClasses",
    value: function removeClasses(classes) {
      var _iterator3 = _createForOfIteratorHelper(classes),
        _step3;
      try {
        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
          var className = _step3.value;
          this.element.classList.remove(className);
        }
      } catch (err) {
        _iterator3.e(err);
      } finally {
        _iterator3.f();
      }
    }
  }, {
    key: "applyCss",
    value: function applyCss(obj) {
      for (var _i = 0, _Object$entries = Object.entries(obj); _i < _Object$entries.length; _i++) {
        var _Object$entries$_i = _slicedToArray(_Object$entries[_i], 2),
          key = _Object$entries$_i[0],
          _value = _Object$entries$_i[1];
        this.element.style[key] = String(_value);
      }
    }
  }, {
    key: "dispose",
    value: function dispose() {
      this.removeClasses([Classes.HIDDEN, Classes.VISIBLE, Classes.SHUFFLE_ITEM]);
      this.element.removeAttribute("style");
      this.element = null;
    }
  }]);
}(), _defineProperty(_ShuffleItem, "Css", {
  INITIAL: {
    position: "absolute",
    top: 0,
    visibility: "visible",
    willChange: "transform"
  },
  DIRECTION: {
    ltr: {
      left: 0
    },
    rtl: {
      right: 0
    }
  },
  VISIBLE: {
    before: {
      opacity: 1,
      visibility: "visible"
    },
    after: {
      transitionDelay: ""
    }
  },
  HIDDEN: {
    before: {
      opacity: 0
    },
    after: {
      visibility: "hidden",
      transitionDelay: ""
    }
  }
}), _defineProperty(_ShuffleItem, "Scale", {
  VISIBLE: 1,
  HIDDEN: .001
}), _ShuffleItem);
/**
* Toggles the visible and hidden class names.
* @param Object with visible and hidden arrays.
*/
function toggleFilterClasses(_ref) {
  var visible = _ref.visible,
    hidden = _ref.hidden;
  var _iterator4 = _createForOfIteratorHelper(visible),
    _step4;
  try {
    for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
      var item = _step4.value;
      item.show();
    }
  } catch (err) {
    _iterator4.e(err);
  } finally {
    _iterator4.f();
  }
  var _iterator5 = _createForOfIteratorHelper(hidden),
    _step5;
  try {
    for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
      var _item = _step5.value;
      _item.hide();
    }
  } catch (err) {
    _iterator5.e(err);
  } finally {
    _iterator5.f();
  }
}
/**
* Set the initial css for each item
* @param items Set to initialize.
*/
function initItems(items) {
  var _iterator6 = _createForOfIteratorHelper(items),
    _step6;
  try {
    for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
      var item = _step6.value;
      item.init();
    }
  } catch (err) {
    _iterator6.e(err);
  } finally {
    _iterator6.f();
  }
}
/**
* Remove element reference and styles.
* @param items Set to dispose.
*/
function disposeItems(items) {
  var _iterator7 = _createForOfIteratorHelper(items),
    _step7;
  try {
    for (_iterator7.s(); !(_step7 = _iterator7.n()).done;) {
      var item = _step7.value;
      item.dispose();
    }
  } catch (err) {
    _iterator7.e(err);
  } finally {
    _iterator7.f();
  }
}
function applyHiddenState(item) {
  item.scale = ShuffleItem.Scale.HIDDEN;
  item.isHidden = true;
  item.applyCss(ShuffleItem.Css.HIDDEN.before);
  item.applyCss(ShuffleItem.Css.HIDDEN.after);
}

//#endregion
//#region src/sorter.ts
/**
* Fisher-Yates shuffle.
* http://stackoverflow.com/a/962890/373422
* https://bost.ocks.org/mike/shuffle/
* @param array Array to shuffle.
* @return Randomly sorted array.
*/
function randomize(array) {
  var count = array.length;
  while (count) {
    count -= 1;
    var index = Math.floor(Math.random() * (count + 1));
    var temp = array[index];
    array[index] = array[count];
    array[count] = temp;
  }
  return array;
}
var defaults = {
  reverse: false,
  by: null,
  compare: null,
  randomize: false,
  key: "element"
};
/**
* You can return `undefined` from the `by` function to revert to DOM order.
* @param arr Array to sort.
* @param options Sorting options.
* @return The sorted array.
*/
function sorter(arr, options) {
  if (!Array.isArray(arr)) return [];
  var opts = _objectSpread(_objectSpread({}, defaults), options);
  var original = _toConsumableArray(arr);
  var revert = false;
  if (arr.length === 0) return [];
  if (opts.randomize) return randomize(arr);
  if (typeof opts.by === "function") {
    var sortBy = opts.by;
    arr.sort(function (itemA, itemB) {
      if (revert) return 0;
      var itemAValue = itemA[opts.key];
      var itemBValue = itemB[opts.key];
      var valA = sortBy(itemAValue);
      var valB = sortBy(itemBValue);
      if (valA === void 0 && valB === void 0) {
        revert = true;
        return 0;
      }
      if (valA < valB || valA === "sortFirst" || valB === "sortLast") return -1;
      if (valA > valB || valA === "sortLast" || valB === "sortFirst") return 1;
      return 0;
    });
  } else if (typeof opts.compare === "function") arr.sort(opts.compare);
  if (revert) return original;
  if (opts.reverse) arr.reverse();
  return arr;
}

//#endregion
//#region src/transition-manager.ts
var TransitionManager = (_transitions = /*#__PURE__*/new WeakMap(), _count = /*#__PURE__*/new WeakMap(), _uniqueId = /*#__PURE__*/new WeakSet(), /*#__PURE__*/function () {
  function TransitionManager() {
    _classCallCheck(this, TransitionManager);
    _classPrivateMethodInitSpec(this, _uniqueId);
    _classPrivateFieldInitSpec(this, _transitions, {
      writable: true,
      value: /* @__PURE__ */new Map()
    });
    _classPrivateFieldInitSpec(this, _count, {
      writable: true,
      value: 0
    });
  }
  return _createClass(TransitionManager, [{
    key: "waitForTransition",
    value: function waitForTransition(element, callback) {
      var _this = this;
      var id = _classPrivateMethodGet(this, _uniqueId, _uniqueId2).call(this);
      var listener = function listener(event) {
        if (event.currentTarget === event.target) {
          _this.cancelTransition(id);
          callback(event);
        }
      };
      element.addEventListener("transitionend", listener);
      _classPrivateFieldGet(this, _transitions).set(id, {
        element: element,
        listener: listener
      });
      return id;
    }
  }, {
    key: "cancelTransition",
    value: function cancelTransition(id) {
      var entry = _classPrivateFieldGet(this, _transitions).get(id);
      if (entry) {
        entry.element.removeEventListener("transitionend", entry.listener);
        _classPrivateFieldGet(this, _transitions)["delete"](id);
        return true;
      }
      return false;
    }
  }, {
    key: "cancelAll",
    value: function cancelAll() {
      var _iterator8 = _createForOfIteratorHelper(_classPrivateFieldGet(this, _transitions).keys()),
        _step8;
      try {
        for (_iterator8.s(); !(_step8 = _iterator8.n()).done;) {
          var _id = _step8.value;
          this.cancelTransition(_id);
        }
      } catch (err) {
        _iterator8.e(err);
      } finally {
        _iterator8.f();
      }
    }
  }]);
}());
function _uniqueId2() {
  _classPrivateFieldSet(this, _count, _classPrivateFieldGet(this, _count) + 1);
  return "transitionend".concat(_classPrivateFieldGet(this, _count));
}
function createTransitionManager() {
  return new TransitionManager();
}

//#endregion
//#region src/layout.ts
/**
* Determine the number of columns an items spans.
* @param itemWidth Width of the item.
* @param columnWidth Width of the column (includes gutter).
* @param columns Total number of columns.
* @param threshold A buffer value for the size of the column to fit.
* @return The column span.
*/
function getColumnSpan(itemWidth, columnWidth, columns, threshold) {
  var columnSpan = itemWidth / columnWidth;
  if (Math.abs(Math.round(columnSpan) - columnSpan) < threshold) columnSpan = Math.round(columnSpan);
  return Math.min(Math.ceil(columnSpan), columns);
}
/**
* Retrieves the column set to use for placement.
* @param positions The position array.
* @param columnSpan The number of columns this current item spans.
* @param columns The total columns in the grid.
* @return An array of numbers representing the column set.
*/
function getAvailablePositions(positions, columnSpan, columns) {
  if (columnSpan === 1) return positions;
  var available = [];
  for (var i = 0; i <= columns - columnSpan; i += 1) available.push(Math.max.apply(Math, _toConsumableArray(positions.slice(i, i + columnSpan))));
  return available;
}
/**
* Find index of short column, the first from the left where this item will go.
*
* @param positions The array to search for the smallest number.
* @param buffer Optional buffer which is very useful when the height is a percentage of the width.
* @return Index of the short column.
*/
function getShortColumn(positions, buffer) {
  var minPosition = Math.min.apply(Math, _toConsumableArray(positions));
  for (var i = 0, len = positions.length; i < len; i += 1) if (positions[i] >= minPosition - buffer && positions[i] <= minPosition + buffer) return i;
  return 0;
}
/**
* Determine the location of the next item, based on its size.
* @param params Object with itemSize, positions, gridSize, total, threshold, and buffer.
* @return The point position for the item.
*/
function getItemPosition(_ref2) {
  var itemSize = _ref2.itemSize,
    positions = _ref2.positions,
    gridSize = _ref2.gridSize,
    total = _ref2.total,
    threshold = _ref2.threshold,
    buffer = _ref2.buffer;
  var span = getColumnSpan(itemSize.width, gridSize, total, threshold);
  var setY = getAvailablePositions(positions, span, total);
  var shortColumnIndex = getShortColumn(setY, buffer);
  var point = new Point(gridSize * shortColumnIndex, setY[shortColumnIndex]);
  var setHeight = setY[shortColumnIndex] + itemSize.height;
  for (var i = 0; i < span; i += 1) positions[shortColumnIndex + i] = setHeight;
  return point;
}
/**
* This method attempts to center items. This method could potentially be slow
* with a large number of items because it must place items, then check every
* previous item to ensure there is no overlap.
* @param itemRects Item data objects.
* @param containerWidth Width of the containing element.
* @return An array of centered points.
*/
function getCenteredPositions(itemRects, containerWidth) {
  var rowMap = {};
  var _iterator9 = _createForOfIteratorHelper(itemRects),
    _step9;
  try {
    for (_iterator9.s(); !(_step9 = _iterator9.n()).done;) {
      var itemRect = _step9.value;
      if (rowMap[itemRect.top] === void 0) rowMap[itemRect.top] = [itemRect];else rowMap[itemRect.top].push(itemRect);
    }
  } catch (err) {
    _iterator9.e(err);
  } finally {
    _iterator9.f();
  }
  var rects = [];
  var rows = [];
  var centeredRows = [];
  var _loop = function _loop() {
    var itemRects = _Object$values[_i2];
    rows.push(itemRects);
    var lastItem = itemRects.at(-1);
    var end = lastItem.left + lastItem.width;
    var offset = Math.round((containerWidth - end) / 2);
    var finalRects = itemRects;
    var canMove = false;
    if (offset > 0) {
      var newRects = [];
      canMove = itemRects.every(function (comparisonRect) {
        var newRect = new Rect(comparisonRect.left + offset, comparisonRect.top, comparisonRect.width, comparisonRect.height, comparisonRect.id);
        var noOverlap = !rects.some(function (rectangle) {
          return Rect.intersects(newRect, rectangle);
        });
        newRects.push(newRect);
        return noOverlap;
      });
      if (canMove) finalRects = newRects;
    }
    if (!canMove) {
      var intersectingRect;
      if (itemRects.some(function (itemRect) {
        return rects.some(function (comparisonRect) {
          var intersects = Rect.intersects(itemRect, comparisonRect);
          if (intersects) intersectingRect = comparisonRect;
          return intersects;
        });
      })) {
        var rowIndex = centeredRows.findIndex(function (items) {
          return items.includes(intersectingRect);
        });
        centeredRows.splice(rowIndex, 1, rows[rowIndex]);
      }
    }
    rects.push.apply(rects, _toConsumableArray(finalRects));
    centeredRows.push(finalRects);
  };
  for (var _i2 = 0, _Object$values = Object.values(rowMap); _i2 < _Object$values.length; _i2++) {
    _loop();
  }
  return centeredRows.flat().toSorted(function (rowA, rowB) {
    return rowA.id - rowB.id;
  }).map(function (itemRect) {
    return new Point(itemRect.left, itemRect.top);
  });
}

//#endregion
//#region src/shuffle.ts
var id = 0;
var Shuffle = exports["default"] = (_transitionManager = /*#__PURE__*/new WeakMap(), _queue = /*#__PURE__*/new WeakMap(), _rafId = /*#__PURE__*/new WeakMap(), _resizeObserver = /*#__PURE__*/new WeakMap(), _getElementOption = /*#__PURE__*/new WeakSet(), _validateStyles = /*#__PURE__*/new WeakSet(), _filter = /*#__PURE__*/new WeakSet(), _getFilteredSets = /*#__PURE__*/new WeakSet(), _doesPassFilter = /*#__PURE__*/new WeakSet(), _updateItemCount = /*#__PURE__*/new WeakSet(), _getItems = /*#__PURE__*/new WeakSet(), _mergeNewItems = /*#__PURE__*/new WeakSet(), _getFilteredItems = /*#__PURE__*/new WeakSet(), _getConcealedItems = /*#__PURE__*/new WeakSet(), _getColumnSize = /*#__PURE__*/new WeakSet(), _getGutterSize = /*#__PURE__*/new WeakSet(), _setColumns = /*#__PURE__*/new WeakSet(), _setContainerSize = /*#__PURE__*/new WeakSet(), _getContainerSize = /*#__PURE__*/new WeakSet(), _getStaggerAmount = /*#__PURE__*/new WeakSet(), _resetCols = /*#__PURE__*/new WeakSet(), _layout = /*#__PURE__*/new WeakSet(), _getNextPositions = /*#__PURE__*/new WeakSet(), _getItemPosition = /*#__PURE__*/new WeakSet(), _shrink = /*#__PURE__*/new WeakSet(), _handleResizeCallback = /*#__PURE__*/new WeakSet(), _whenTransitionDone = /*#__PURE__*/new WeakSet(), _getTransitionFunction = /*#__PURE__*/new WeakSet(), _processQueue = /*#__PURE__*/new WeakSet(), _startTransitions = /*#__PURE__*/new WeakSet(), _cancelMovement = /*#__PURE__*/new WeakSet(), _movementFinished = /*#__PURE__*/new WeakSet(), _Shuffle = /*#__PURE__*/function (_TinyEmitter) {
  /**
  * Categorize, sort, and filter a responsive grid of items.
  *
  * @param element An element which is the parent container for the grid items.
  * @param options Options object.
  * @constructor
  */
  function Shuffle(_element) {
    var _this2;
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    _classCallCheck(this, Shuffle);
    _this2 = _callSuper(this, Shuffle);
    _classPrivateMethodInitSpec(_this2, _movementFinished);
    _classPrivateMethodInitSpec(_this2, _cancelMovement);
    /**
    * Wait for each transition to finish, the emit the layout event.
    * Array of transition objects.
    */
    _classPrivateMethodInitSpec(_this2, _startTransitions);
    /**
    * Execute the styles gathered in the style queue. This applies styles to elements,
    * triggering transitions.
    */
    _classPrivateMethodInitSpec(_this2, _processQueue);
    /**
    * Return a function which will set CSS styles and call the `done` function
    * when (if) the transition finishes.
    * @param opts Transition object.
    */
    _classPrivateMethodInitSpec(_this2, _getTransitionFunction);
    /**
    * Listen for the transition end on an element and execute the itemCallback
    * when it finishes.
    * @param element Element to listen on.
    * @param itemCallback Callback for the item.
    * @param done Callback to notify `parallel` that this one is done.
    */
    _classPrivateMethodInitSpec(_this2, _whenTransitionDone);
    /**
    * Resize handler.
    * @param entries
    */
    _classPrivateMethodInitSpec(_this2, _handleResizeCallback);
    /**
    * Hides the elements that don't match our filter.
    * @param collection Collection to shrink.
    */
    _classPrivateMethodInitSpec(_this2, _shrink);
    /**
    * Determine the location of the next item, based on its size.
    * @param itemSize Object with width and height.
    */
    _classPrivateMethodInitSpec(_this2, _getItemPosition);
    /**
    * Return an array of Point instances representing the future positions of
    * each item.
    * @param items Array of sorted shuffle items.
    */
    _classPrivateMethodInitSpec(_this2, _getNextPositions);
    /**
    * Loops through each item that should be shown and calculates the x, y position.
    * @param items Array of items that will be shown/layed out in order in their array.
    */
    _classPrivateMethodInitSpec(_this2, _layout);
    /**
    * Zeros out the y columns array, which is used to determine item placement.
    */
    _classPrivateMethodInitSpec(_this2, _resetCols);
    /**
    * Get the clamped stagger amount.
    * @param index Index of the item to be staggered.
    */
    _classPrivateMethodInitSpec(_this2, _getStaggerAmount);
    /**
    * Based on the column heights, it returns the biggest one.
    */
    _classPrivateMethodInitSpec(_this2, _getContainerSize);
    /**
    * Adjust the height of the grid
    */
    _classPrivateMethodInitSpec(_this2, _setContainerSize);
    /**
    * Calculate the number of columns to be used. Gets css if using sizer element.
    * @param containerWidth Optionally specify a container width if it's already available.
    */
    _classPrivateMethodInitSpec(_this2, _setColumns);
    /**
    * Returns the gutter size, based on gutter width and sizer options.
    * @param containerWidth Size of the parent container.
    */
    _classPrivateMethodInitSpec(_this2, _getGutterSize);
    /**
    * Returns the column size, based on column width and sizer options.
    * @param containerWidth Size of the parent container.
    * @param gutterSize Size of the gutters.
    */
    _classPrivateMethodInitSpec(_this2, _getColumnSize);
    _classPrivateMethodInitSpec(_this2, _getConcealedItems);
    _classPrivateMethodInitSpec(_this2, _getFilteredItems);
    /**
    * Combine the current items array with a new one and sort it by DOM order.
    * @param items Items to track.
    */
    _classPrivateMethodInitSpec(_this2, _mergeNewItems);
    _classPrivateMethodInitSpec(_this2, _getItems);
    /**
    * Updates the visible item count.
    */
    _classPrivateMethodInitSpec(_this2, _updateItemCount);
    /**
    * Test an item to see if it passes a category.
    * @param category Category or function to filter by.
    * @param element An element to test.
    * @return Whether it passes the category/filter.
    */
    _classPrivateMethodInitSpec(_this2, _doesPassFilter);
    /**
    * Returns an object containing the visible and hidden elements.
    * @param category Category or function to filter by.
    * @param items A collection of items to filter.
    */
    _classPrivateMethodInitSpec(_this2, _getFilteredSets);
    /**
    * Filter the elements by a category.
    * @param category Category to filter by. If it's given, the last category will be used to filter the items.
    * @param collection Optionally filter a collection. Defaults to all the items.
    * @return Object with visible and hidden arrays.
    */
    _classPrivateMethodInitSpec(_this2, _filter);
    /**
    * Ensures the shuffle container has the css styles it needs applied to it.
    * @param styles Key value pairs for position and overflow.
    */
    _classPrivateMethodInitSpec(_this2, _validateStyles);
    /**
    * Retrieve an element from an option.
    * @param option The option to check.
    * @return The plain element or null.
    */
    _classPrivateMethodInitSpec(_this2, _getElementOption);
    _defineProperty(_this2, "element", void 0);
    _defineProperty(_this2, "sizer", void 0);
    _defineProperty(_this2, "options", void 0);
    _defineProperty(_this2, "lastSort", void 0);
    _defineProperty(_this2, "group", void 0);
    _defineProperty(_this2, "lastFilter", void 0);
    _defineProperty(_this2, "isEnabled", void 0);
    _defineProperty(_this2, "isDestroyed", void 0);
    _defineProperty(_this2, "isInitialized", void 0);
    _defineProperty(_this2, "isTransitioning", void 0);
    _defineProperty(_this2, "id", void 0);
    _defineProperty(_this2, "items", void 0);
    _defineProperty(_this2, "sortedItems", void 0);
    _defineProperty(_this2, "visibleItems", void 0);
    _defineProperty(_this2, "cols", void 0);
    _defineProperty(_this2, "colWidth", void 0);
    _defineProperty(_this2, "containerWidth", void 0);
    _defineProperty(_this2, "positions", void 0);
    _classPrivateFieldInitSpec(_this2, _transitionManager, {
      writable: true,
      value: void 0
    });
    _classPrivateFieldInitSpec(_this2, _queue, {
      writable: true,
      value: void 0
    });
    _classPrivateFieldInitSpec(_this2, _rafId, {
      writable: true,
      value: void 0
    });
    _classPrivateFieldInitSpec(_this2, _resizeObserver, {
      writable: true,
      value: null
    });
    _this2.options = _objectSpread(_objectSpread({}, Shuffle.options), options);
    _this2.lastSort = {};
    _this2.group = Shuffle.ALL_ITEMS;
    _this2.lastFilter = Shuffle.ALL_ITEMS;
    _this2.isEnabled = true;
    _this2.isDestroyed = false;
    _this2.isInitialized = false;
    _classPrivateFieldSet(_this2, _transitionManager, createTransitionManager());
    _this2.isTransitioning = false;
    _classPrivateFieldSet(_this2, _queue, []);
    var _el = _classPrivateMethodGet(_this2, _getElementOption, _getElementOption2).call(_this2, _element);
    if (!_el) throw new TypeError("Shuffle needs to be initialized with an element.");
    _this2.element = _el;
    _this2.id = "shuffle_".concat(id);
    id += 1;
    _this2.items = _classPrivateMethodGet(_this2, _getItems, _getItems2).call(_this2);
    _this2.sortedItems = _this2.items;
    _this2.sizer = _classPrivateMethodGet(_this2, _getElementOption, _getElementOption2).call(_this2, _this2.options.sizer);
    _this2.element.classList.add(Shuffle.Classes.BASE);
    initItems(_this2.items);
    if (document.readyState !== "complete") {
      var layout = _this2.layout.bind(_this2);
      window.addEventListener("load", function onLoad() {
        window.removeEventListener("load", onLoad);
        layout();
      });
    }
    var containerCss = globalThis.getComputedStyle(_this2.element, null);
    var _containerWidth = Shuffle.getSize(_this2.element).width;
    _classPrivateMethodGet(_this2, _validateStyles, _validateStyles2).call(_this2, containerCss);
    _classPrivateMethodGet(_this2, _setColumns, _setColumns2).call(_this2, _containerWidth);
    _this2.filter(_this2.options.group, _this2.options.initialSort);
    _classPrivateFieldSet(_this2, _rafId, null);
    _classPrivateFieldSet(_this2, _resizeObserver, new ResizeObserver(_classPrivateMethodGet(_this2, _handleResizeCallback, _handleResizeCallback2).bind(_this2)));
    _classPrivateFieldGet(_this2, _resizeObserver).observe(_this2.element);
    _this2.element.offsetWidth;
    _this2.setItemTransitions(_this2.items);
    _this2.element.style.transition = "height ".concat(_this2.options.speed, "ms ").concat(_this2.options.easing);
    _this2.isInitialized = true;
    return _this2;
  }
  _inherits(Shuffle, _TinyEmitter);
  return _createClass(Shuffle, [{
    key: "on",
    value: function on(event, callback, context) {
      return _get(_getPrototypeOf(Shuffle.prototype), "on", this).call(this, event, callback, context);
    }
  }, {
    key: "once",
    value: function once(event, callback, context) {
      return _get(_getPrototypeOf(Shuffle.prototype), "once", this).call(this, event, callback, context);
    }
  }, {
    key: "emit",
    value: function emit(event, data) {
      if (this.isDestroyed) return this;
      return _get(_getPrototypeOf(Shuffle.prototype), "emit", this).call(this, event, data);
    }
  }, {
    key: "off",
    value: function off(event, callback) {
      return _get(_getPrototypeOf(Shuffle.prototype), "off", this).call(this, event, callback);
    }
  }, {
    key: "setItemTransitions",
    value:
    /**
    * Sets css transform transition on a group of elements. This is not executed
    * at the same time as `item.init` so that transitions don't occur upon
    * initialization of a new Shuffle instance.
    * @param items Shuffle items to set transitions on.
    * @protected
    */
    function setItemTransitions(items) {
      var _this$options = this.options,
        speed = _this$options.speed,
        easing = _this$options.easing;
      var positionProps = this.options.useTransforms ? ["transform"] : ["top", "left"];
      var cssProps = Object.keys(ShuffleItem.Css.HIDDEN.before);
      var properties = [].concat(positionProps, cssProps).join(",");
      var _iterator0 = _createForOfIteratorHelper(items),
        _step0;
      try {
        for (_iterator0.s(); !(_step0 = _iterator0.n()).done;) {
          var item = _step0.value;
          item.element.style.transitionDuration = "".concat(speed, "ms");
          item.element.style.transitionTimingFunction = easing;
          item.element.style.transitionProperty = properties;
        }
      } catch (err) {
        _iterator0.e(err);
      } finally {
        _iterator0.f();
      }
    }
  }, {
    key: "getTransformedPositions",
    value:
    /**
    * Mutate positions before they're applied.
    * @param itemRects Item data objects.
    * @param containerWidth Width of the containing element.
    * @protected
    */
    function getTransformedPositions(itemRects, containerWidth) {
      return getCenteredPositions(itemRects, containerWidth);
    }
  }, {
    key: "getStylesForTransition",
    value:
    /**
    * Returns styles which will be applied to the an item for a transition.
    * @param item Item to get styles for. Should have updated scale and point properties.
    * @param styleObject Extra styles that will be used in the transition.
    * @return Transforms for transitions, left/top for animate.
    */
    function getStylesForTransition(item, styleObject) {
      var styles = _objectSpread({}, styleObject);
      if (this.options.useTransforms) styles.transform = "translate(".concat(this.options.isRTL ? "-" : "").concat(this.options.roundTransforms ? Math.round(item.point.x) : item.point.x, "px, ").concat(this.options.roundTransforms ? Math.round(item.point.y) : item.point.y, "px) scale(").concat(item.scale, ")");else {
        if (this.options.isRTL) styles.right = "".concat(item.point.x, "px");else styles.left = "".concat(item.point.x, "px");
        styles.top = "".concat(item.point.y, "px");
      }
      return styles;
    }
  }, {
    key: "filter",
    value:
    /**
    * The magic. This is what makes the plugin 'shuffle'
    * @param category Category to filter by. Can be a function, string, or array of strings.
    * @param sortOptions A sort object which can sort the visible set
    */
    function filter(category, sortOptions) {
      if (!this.isEnabled) return;
      if (!category || category.length === 0) category = Shuffle.ALL_ITEMS;
      _classPrivateMethodGet(this, _filter, _filter2).call(this, category);
      _classPrivateMethodGet(this, _shrink, _shrink2).call(this);
      _classPrivateMethodGet(this, _updateItemCount, _updateItemCount2).call(this);
      this.sort(sortOptions);
    }
    /**
    * Gets the visible elements, sorts them, and passes them to layout.
    * @param sortOptions The options object to pass to `sorter`.
    */
  }, {
    key: "sort",
    value: function sort() {
      var sortOptions = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.lastSort;
      if (!this.isEnabled) return;
      _classPrivateMethodGet(this, _resetCols, _resetCols2).call(this);
      var items = sorter(_classPrivateMethodGet(this, _getFilteredItems, _getFilteredItems2).call(this), sortOptions);
      this.sortedItems = items;
      _classPrivateMethodGet(this, _layout, _layout2).call(this, items);
      _classPrivateMethodGet(this, _processQueue, _processQueue2).call(this);
      _classPrivateMethodGet(this, _setContainerSize, _setContainerSize2).call(this);
      this.lastSort = sortOptions;
    }
    /**
    * Reposition everything.
    * @param options options object
    * @param options.recalculateSizes Whether to calculate column, gutter, and container widths again.
    * @param options.force By default, `update` does nothing if the instance is disabled. Setting this
    *    to true forces the update to happen regardless.
    */
  }, {
    key: "update",
    value: function update() {
      var _ref3 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        _ref3$recalculateSize = _ref3.recalculateSizes,
        recalculateSizes = _ref3$recalculateSize === void 0 ? true : _ref3$recalculateSize,
        _ref3$force = _ref3.force,
        force = _ref3$force === void 0 ? false : _ref3$force;
      if (this.isEnabled || force) {
        if (recalculateSizes) _classPrivateMethodGet(this, _setColumns, _setColumns2).call(this);
        this.sort();
      }
    }
    /**
    * Use this instead of `update()` if you don't need the columns and gutters updated
    * Maybe an image inside `shuffle` loaded (and now has a height), which means calculations
    * could be off.
    */
  }, {
    key: "layout",
    value: function layout() {
      this.update({
        recalculateSizes: true
      });
    }
    /**
    * New items have been appended to shuffle. Mix them in with the current
    * filter or sort status.
    * @param newItems Collection of new items.
    */
  }, {
    key: "add",
    value: function add(newItems) {
      var _this3 = this;
      var items = arrayUnique(newItems).map(function (el) {
        return new ShuffleItem(el, _this3.options.isRTL);
      });
      initItems(items);
      _classPrivateMethodGet(this, _resetCols, _resetCols2).call(this);
      var sortedItems = sorter(_classPrivateMethodGet(this, _mergeNewItems, _mergeNewItems2).call(this, items), this.lastSort);
      var allSortedItemsSet = _classPrivateMethodGet(this, _filter, _filter2).call(this, this.lastFilter, sortedItems);
      var itemPositions = _classPrivateMethodGet(this, _getNextPositions, _getNextPositions2).call(this, allSortedItemsSet.visible);
      for (var i = 0; i < allSortedItemsSet.visible.length; i += 1) {
        var item = allSortedItemsSet.visible[i];
        if (items.includes(item)) {
          item.point = itemPositions[i];
          applyHiddenState(item);
          item.applyCss(this.getStylesForTransition(item, {}));
        }
      }
      var _iterator1 = _createForOfIteratorHelper(allSortedItemsSet.hidden),
        _step1;
      try {
        for (_iterator1.s(); !(_step1 = _iterator1.n()).done;) {
          var _item2 = _step1.value;
          if (items.includes(_item2)) applyHiddenState(_item2);
        }
      } catch (err) {
        _iterator1.e(err);
      } finally {
        _iterator1.f();
      }
      this.element.offsetWidth;
      this.setItemTransitions(items);
      this.items = _classPrivateMethodGet(this, _mergeNewItems, _mergeNewItems2).call(this, items);
      this.filter(this.lastFilter);
    }
    /**
    * Disables shuffle from updating dimensions and layout on resize
    */
  }, {
    key: "disable",
    value: function disable() {
      this.isEnabled = false;
    }
    /**
    * Enables shuffle again
    * @param isUpdateLayout if undefined, shuffle will update columns and gutters
    */
  }, {
    key: "enable",
    value: function enable() {
      var isUpdateLayout = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
      this.isEnabled = true;
      if (isUpdateLayout) this.update();
    }
    /**
    * Remove 1 or more shuffle items.
    * @param elements An array containing one or more elements in shuffle
    */
  }, {
    key: "remove",
    value: function remove(elements) {
      var _this4 = this;
      if (elements.length === 0) return;
      var collection = arrayUnique(elements);
      var oldItems = collection.map(function (element) {
        return _this4.getItemByElement(element);
      }).filter(Boolean);
      var handleLayout = function handleLayout() {
        disposeItems(oldItems);
        var _iterator10 = _createForOfIteratorHelper(collection),
          _step10;
        try {
          for (_iterator10.s(); !(_step10 = _iterator10.n()).done;) {
            var element = _step10.value;
            element.remove();
          }
        } catch (err) {
          _iterator10.e(err);
        } finally {
          _iterator10.f();
        }
        _this4.emit(Shuffle.EventType.REMOVED, {
          type: Shuffle.EventType.REMOVED,
          shuffle: _this4,
          collection: collection
        });
      };
      toggleFilterClasses({
        visible: [],
        hidden: oldItems
      });
      _classPrivateMethodGet(this, _shrink, _shrink2).call(this, oldItems);
      this.sort();
      this.items = this.items.filter(function (item) {
        return !oldItems.includes(item);
      });
      _classPrivateMethodGet(this, _updateItemCount, _updateItemCount2).call(this);
      this.once(Shuffle.EventType.LAYOUT, handleLayout);
    }
    /**
    * Retrieve a shuffle item by its element.
    * @param element Element to look for.
    * @return A shuffle item or undefined if it's not found.
    */
  }, {
    key: "getItemByElement",
    value: function getItemByElement(element) {
      return this.items.find(function (item) {
        return item.element === element;
      });
    }
    /**
    * Dump the elements currently stored and reinitialize all child elements which
    * match the `itemSelector`.
    */
  }, {
    key: "resetItems",
    value: function resetItems() {
      var _this5 = this;
      disposeItems(this.items);
      this.isInitialized = false;
      this.items = _classPrivateMethodGet(this, _getItems, _getItems2).call(this);
      initItems(this.items);
      this.once(Shuffle.EventType.LAYOUT, function () {
        _this5.setItemTransitions(_this5.items);
        _this5.isInitialized = true;
      });
      this.filter(this.lastFilter);
    }
    /**
    * Destroys shuffle, removes events, styles, and classes
    */
  }, {
    key: "destroy",
    value: function destroy() {
      _classPrivateMethodGet(this, _cancelMovement, _cancelMovement2).call(this);
      if (_classPrivateFieldGet(this, _resizeObserver)) {
        _classPrivateFieldGet(this, _resizeObserver).unobserve(this.element);
        _classPrivateFieldSet(this, _resizeObserver, null);
      }
      this.element.classList.remove("shuffle");
      this.element.removeAttribute("style");
      disposeItems(this.items);
      this.items.length = 0;
      this.sortedItems.length = 0;
      this.sizer = null;
      this.element = null;
      this.isDestroyed = true;
      this.isEnabled = false;
    }
  }]);
}(TinyEmitter), _defineProperty(_Shuffle, "getSize", getSize), _defineProperty(_Shuffle, "ShuffleItem", ShuffleItem), _defineProperty(_Shuffle, "ALL_ITEMS", ALL_ITEMS), _defineProperty(_Shuffle, "FILTER_ATTRIBUTE_KEY", FILTER_ATTRIBUTE_KEY), _defineProperty(_Shuffle, "EventType", EventType), _defineProperty(_Shuffle, "Classes", Classes), _defineProperty(_Shuffle, "FilterMode", FilterMode), _defineProperty(_Shuffle, "options", DEFAULT_OPTIONS), _defineProperty(_Shuffle, "Point", Point), _defineProperty(_Shuffle, "Rect", Rect), _Shuffle);

//#endregion
function _getElementOption2(option) {
  if (typeof option === "string") return this.element ? this.element.querySelector(option) : document.querySelector(option);
  if (option && "nodeType" in option && option.nodeType && option.nodeType === 1) return option;
  if (option && "jquery" in option) return option[0];
  return null;
}
function _validateStyles2(styles) {
  if (styles.position === "static") this.element.style.position = "relative";
  if (styles.overflow !== "hidden") this.element.style.overflow = "hidden";
}
function _filter2() {
  var category = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.lastFilter;
  var collection = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.items;
  var set = _classPrivateMethodGet(this, _getFilteredSets, _getFilteredSets2).call(this, category, collection);
  toggleFilterClasses(set);
  this.lastFilter = category;
  if (typeof category === "string") this.group = category;
  return set;
}
function _getFilteredSets2(category, items) {
  var visible = [];
  var hidden = [];
  if (category === _Shuffle.ALL_ITEMS) visible = items;else {
    var _iterator11 = _createForOfIteratorHelper(items),
      _step11;
    try {
      for (_iterator11.s(); !(_step11 = _iterator11.n()).done;) {
        var item = _step11.value;
        if (_classPrivateMethodGet(this, _doesPassFilter, _doesPassFilter2).call(this, category, item.element)) visible.push(item);else hidden.push(item);
      }
    } catch (err) {
      _iterator11.e(err);
    } finally {
      _iterator11.f();
    }
  }
  return {
    visible: visible,
    hidden: hidden
  };
}
function _doesPassFilter2(category, element) {
  var _element$dataset$_Shu;
  if (typeof category === "function") return category.call(element, element, this);
  var attr = (_element$dataset$_Shu = element.dataset[_Shuffle.FILTER_ATTRIBUTE_KEY]) !== null && _element$dataset$_Shu !== void 0 ? _element$dataset$_Shu : "";
  var keys = this.options.delimiter ? attr.split(this.options.delimiter) : JSON.parse(attr);
  function testCategory(category) {
    return keys.includes(category);
  }
  if (Array.isArray(category)) {
    if (this.options.filterMode === FilterMode.ANY) return category.some(testCategory);
    return category.every(testCategory);
  }
  return keys.includes(category);
}
function _updateItemCount2() {
  this.visibleItems = _classPrivateMethodGet(this, _getFilteredItems, _getFilteredItems2).call(this).length;
}
function _getItems2() {
  var _this6 = this;
  return Array.from(this.element.children).filter(function (el) {
    return el.matches(_this6.options.itemSelector);
  }).map(function (el) {
    return new ShuffleItem(el, _this6.options.isRTL);
  });
}
function _mergeNewItems2(items) {
  var children = Array.from(this.element.children);
  return sorter([].concat(_toConsumableArray(this.items), _toConsumableArray(items)), {
    by: function by(element) {
      return children.indexOf(element);
    }
  });
}
function _getFilteredItems2() {
  return this.items.filter(function (item) {
    return item.isVisible;
  });
}
function _getConcealedItems2() {
  return this.items.filter(function (item) {
    return !item.isVisible;
  });
}
function _getColumnSize2(containerWidth, gutterSize) {
  var size;
  if (typeof this.options.columnWidth === "function") size = this.options.columnWidth(containerWidth);else if (this.sizer) size = _Shuffle.getSize(this.sizer).width;else if (this.options.columnWidth) size = this.options.columnWidth;else if (this.items.length > 0) size = _Shuffle.getSize(this.items[0].element, true).width;else size = containerWidth;
  if (size === 0) size = containerWidth;
  return size + gutterSize;
}
function _getGutterSize2(containerWidth) {
  if (typeof this.options.gutterWidth === "function") return this.options.gutterWidth(containerWidth);
  if (this.sizer) return getNumberStyle(this.sizer, "marginLeft");
  if (this.options.gutterWidth) return this.options.gutterWidth;
  return 0;
}
function _setColumns2() {
  var containerWidth = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : _Shuffle.getSize(this.element).width;
  var gutter = _classPrivateMethodGet(this, _getGutterSize, _getGutterSize2).call(this, containerWidth);
  var columnWidth = _classPrivateMethodGet(this, _getColumnSize, _getColumnSize2).call(this, containerWidth, gutter);
  var calculatedColumns = (containerWidth + gutter) / columnWidth;
  var threshold = this.options.columnThreshold;
  if (Math.abs(Math.round(calculatedColumns) - calculatedColumns) < threshold) calculatedColumns = Math.round(calculatedColumns);
  this.cols = Math.max(Math.floor(calculatedColumns || 0), 1);
  this.containerWidth = containerWidth;
  this.colWidth = columnWidth;
}
function _setContainerSize2() {
  this.element.style.height = "".concat(_classPrivateMethodGet(this, _getContainerSize, _getContainerSize2).call(this), "px");
}
function _getContainerSize2() {
  return Math.max.apply(Math, _toConsumableArray(this.positions));
}
function _getStaggerAmount2(index) {
  return Math.min(index * this.options.staggerAmount, this.options.staggerAmountMax);
}
function _resetCols2() {
  var i = this.cols;
  this.positions = [];
  while (i) {
    i -= 1;
    this.positions.push(0);
  }
}
function _layout2(items) {
  var _this7 = this;
  var itemPositions = _classPrivateMethodGet(this, _getNextPositions, _getNextPositions2).call(this, items);
  var count = 0;
  var _loop2 = function _loop2() {
    var item = items[i];
    var callback = function callback() {
      item.applyCss(ShuffleItem.Css.VISIBLE.after);
    };
    if (Point.equals(item.point, itemPositions[i]) && !item.isHidden) {
      item.applyCss(ShuffleItem.Css.VISIBLE.before);
      callback();
      return 1; // continue
    }
    item.point = itemPositions[i];
    item.scale = ShuffleItem.Scale.VISIBLE;
    item.isHidden = false;
    var styles = _this7.getStylesForTransition(item, ShuffleItem.Css.VISIBLE.before);
    styles.transitionDelay = "".concat(_classPrivateMethodGet(_this7, _getStaggerAmount, _getStaggerAmount2).call(_this7, count), "ms");
    _classPrivateFieldGet(_this7, _queue).push({
      item: item,
      styles: styles,
      callback: callback
    });
    count += 1;
  };
  for (var i = 0; i < items.length; i += 1) {
    if (_loop2()) continue;
  }
}
function _getNextPositions2(items) {
  var _this8 = this;
  if (this.options.isCentered) {
    var itemsData = items.map(function (item, i) {
      var itemSize = _Shuffle.getSize(item.element, true);
      var point = _classPrivateMethodGet(_this8, _getItemPosition, _getItemPosition2).call(_this8, itemSize);
      return new Rect(point.x, point.y, itemSize.width, itemSize.height, i);
    });
    return this.getTransformedPositions(itemsData, this.containerWidth);
  }
  return items.map(function (item) {
    return _classPrivateMethodGet(_this8, _getItemPosition, _getItemPosition2).call(_this8, _Shuffle.getSize(item.element, true));
  });
}
function _getItemPosition2(itemSize) {
  return getItemPosition({
    itemSize: itemSize,
    positions: this.positions,
    gridSize: this.colWidth,
    total: this.cols,
    threshold: this.options.columnThreshold,
    buffer: this.options.buffer
  });
}
function _shrink2() {
  var _this9 = this;
  var collection = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : _classPrivateMethodGet(this, _getConcealedItems, _getConcealedItems2).call(this);
  var count = 0;
  var _iterator12 = _createForOfIteratorHelper(collection),
    _step12;
  try {
    var _loop3 = function _loop3() {
      var item = _step12.value;
      var callback = function callback() {
        item.applyCss(ShuffleItem.Css.HIDDEN.after);
      };
      if (item.isHidden) {
        item.applyCss(ShuffleItem.Css.HIDDEN.before);
        callback();
        return 1; // continue
      }
      item.scale = ShuffleItem.Scale.HIDDEN;
      item.isHidden = true;
      var styles = _this9.getStylesForTransition(item, ShuffleItem.Css.HIDDEN.before);
      styles.transitionDelay = "".concat(_classPrivateMethodGet(_this9, _getStaggerAmount, _getStaggerAmount2).call(_this9, count), "ms");
      _classPrivateFieldGet(_this9, _queue).push({
        item: item,
        styles: styles,
        callback: callback
      });
      count += 1;
    };
    for (_iterator12.s(); !(_step12 = _iterator12.n()).done;) {
      if (_loop3()) continue;
    }
  } catch (err) {
    _iterator12.e(err);
  } finally {
    _iterator12.f();
  }
}
function _handleResizeCallback2(entries) {
  var _this0 = this;
  if (!this.isEnabled || this.isDestroyed) return;
  var _iterator13 = _createForOfIteratorHelper(entries),
    _step13;
  try {
    for (_iterator13.s(); !(_step13 = _iterator13.n()).done;) {
      var entry = _step13.value;
      if (Math.round(entry.contentRect.width) !== Math.round(this.containerWidth)) {
        cancelAnimationFrame(_classPrivateFieldGet(this, _rafId));
        _classPrivateFieldSet(this, _rafId, requestAnimationFrame(function () {
          _this0.update();
        }));
      }
    }
  } catch (err) {
    _iterator13.e(err);
  } finally {
    _iterator13.f();
  }
}
function _whenTransitionDone2(element, itemCallback, done) {
  _classPrivateFieldGet(this, _transitionManager).waitForTransition(element, function (evt) {
    itemCallback();
    done(null, evt);
  });
}
function _getTransitionFunction2(opts) {
  var _this1 = this;
  return function (done) {
    opts.item.applyCss(opts.styles);
    _classPrivateMethodGet(_this1, _whenTransitionDone, _whenTransitionDone2).call(_this1, opts.item.element, opts.callback, done);
  };
}
function _processQueue2() {
  if (this.isTransitioning) _classPrivateMethodGet(this, _cancelMovement, _cancelMovement2).call(this);
  var hasSpeed = typeof this.options.speed === "number" && this.options.speed > 0;
  var hasQueue = _classPrivateFieldGet(this, _queue).length > 0;
  if (hasQueue && hasSpeed && this.isInitialized) _classPrivateMethodGet(this, _startTransitions, _startTransitions2).call(this, _classPrivateFieldGet(this, _queue));else if (hasQueue) {
    styleImmediately(_classPrivateFieldGet(this, _queue));
    this.emit(_Shuffle.EventType.LAYOUT, {
      type: _Shuffle.EventType.LAYOUT,
      shuffle: this
    });
  } else this.emit(_Shuffle.EventType.LAYOUT, {
    type: _Shuffle.EventType.LAYOUT,
    shuffle: this
  });
  _classPrivateFieldGet(this, _queue).length = 0;
}
function _startTransitions2(transitions) {
  var _this10 = this;
  this.isTransitioning = true;
  parallel(transitions.map(function (obj) {
    return _classPrivateMethodGet(_this10, _getTransitionFunction, _getTransitionFunction2).call(_this10, obj);
  }), _classPrivateMethodGet(this, _movementFinished, _movementFinished2).bind(this));
}
function _cancelMovement2() {
  _classPrivateFieldGet(this, _transitionManager).cancelAll();
  this.isTransitioning = false;
}
function _movementFinished2() {
  this.isTransitioning = false;
  this.emit(_Shuffle.EventType.LAYOUT, {
    type: _Shuffle.EventType.LAYOUT,
    shuffle: this
  });
}


return module.exports["default"] || module.exports;
}));
