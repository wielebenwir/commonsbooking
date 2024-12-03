"use strict";

function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
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
function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }
function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }
function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
(function (global, factory) {
  (typeof exports === "undefined" ? "undefined" : _typeof(exports)) === 'object' && typeof module !== 'undefined' ? module.exports = factory() : typeof define === 'function' && define.amd ? define(factory) : (global = typeof globalThis !== 'undefined' ? globalThis : global || self, global.Shuffle = factory());
})(void 0, function () {
  'use strict';

  var tinyEmitterExports = {};
  var tinyEmitter = {
    get exports() {
      return tinyEmitterExports;
    },
    set exports(v) {
      tinyEmitterExports = v;
    }
  };
  function E() {
    // Keep this empty so it's easier to inherit from
    // (via https://github.com/lipsmack from https://github.com/scottcorgan/tiny-emitter/issues/3)
  }
  E.prototype = {
    on: function on(name, callback, ctx) {
      var e = this.e || (this.e = {});
      (e[name] || (e[name] = [])).push({
        fn: callback,
        ctx: ctx
      });
      return this;
    },
    once: function once(name, callback, ctx) {
      var self = this;
      function listener() {
        self.off(name, listener);
        callback.apply(ctx, arguments);
      }
      listener._ = callback;
      return this.on(name, listener, ctx);
    },
    emit: function emit(name) {
      var data = [].slice.call(arguments, 1);
      var evtArr = ((this.e || (this.e = {}))[name] || []).slice();
      var i = 0;
      var len = evtArr.length;
      for (i; i < len; i++) {
        evtArr[i].fn.apply(evtArr[i].ctx, data);
      }
      return this;
    },
    off: function off(name, callback) {
      var e = this.e || (this.e = {});
      var evts = e[name];
      var liveEvents = [];
      if (evts && callback) {
        for (var i = 0, len = evts.length; i < len; i++) {
          if (evts[i].fn !== callback && evts[i].fn._ !== callback) liveEvents.push(evts[i]);
        }
      }

      // Remove event from queue to prevent memory leak
      // Suggested by https://github.com/lazd
      // Ref: https://github.com/scottcorgan/tiny-emitter/commit/c6ebfaa9bc973b33d110a84a307742b7cf94c953#commitcomment-5024910

      liveEvents.length ? e[name] = liveEvents : delete e[name];
      return this;
    }
  };
  tinyEmitter.exports = E;
  tinyEmitterExports.TinyEmitter = E;
  var arrayParallel = function parallel(fns, context, callback) {
    if (!callback) {
      if (typeof context === 'function') {
        callback = context;
        context = null;
      } else {
        callback = noop;
      }
    }
    var pending = fns && fns.length;
    if (!pending) return callback(null, []);
    var finished = false;
    var results = new Array(pending);
    fns.forEach(context ? function (fn, i) {
      fn.call(context, maybeDone(i));
    } : function (fn, i) {
      fn(maybeDone(i));
    });
    function maybeDone(i) {
      return function (err, result) {
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
  };
  function noop() {}

  /**
   * Always returns a numeric value, given a value. Logic from jQuery's `isNumeric`.
   * @param {*} value Possibly numeric value.
   * @return {number} `value` or zero if `value` isn't numeric.
   */
  function getNumber(value) {
    return parseFloat(value) || 0;
  }
  var Point = /*#__PURE__*/function () {
    /**
     * Represents a coordinate pair.
     * @param {number} [x=0] X.
     * @param {number} [y=0] Y.
     */
    function Point(x, y) {
      _classCallCheck(this, Point);
      this.x = getNumber(x);
      this.y = getNumber(y);
    }

    /**
     * Whether two points are equal.
     * @param {Point} a Point A.
     * @param {Point} b Point B.
     * @return {boolean}
     */
    return _createClass(Point, null, [{
      key: "equals",
      value: function equals(a, b) {
        return a.x === b.x && a.y === b.y;
      }
    }]);
  }();
  var Point$1 = Point;
  var Rect = /*#__PURE__*/function () {
    /**
     * Class for representing rectangular regions.
     * https://github.com/google/closure-library/blob/master/closure/goog/math/rect.js
     * @param {number} x Left.
     * @param {number} y Top.
     * @param {number} w Width.
     * @param {number} h Height.
     * @param {number} id Identifier
     * @constructor
     */
    function Rect(x, y, w, h, id) {
      _classCallCheck(this, Rect);
      this.id = id;

      /** @type {number} */
      this.left = x;

      /** @type {number} */
      this.top = y;

      /** @type {number} */
      this.width = w;

      /** @type {number} */
      this.height = h;
    }

    /**
     * Returns whether two rectangles intersect.
     * @param {Rect} a A Rectangle.
     * @param {Rect} b A Rectangle.
     * @return {boolean} Whether a and b intersect.
     */
    return _createClass(Rect, null, [{
      key: "intersects",
      value: function intersects(a, b) {
        return a.left < b.left + b.width && b.left < a.left + a.width && a.top < b.top + b.height && b.top < a.top + a.height;
      }
    }]);
  }();
  var Classes = {
    BASE: 'shuffle',
    SHUFFLE_ITEM: 'shuffle-item',
    VISIBLE: 'shuffle-item--visible',
    HIDDEN: 'shuffle-item--hidden'
  };
  var id$1 = 0;
  var ShuffleItem = /*#__PURE__*/function () {
    function ShuffleItem(element, isRTL) {
      _classCallCheck(this, ShuffleItem);
      id$1 += 1;
      this.id = id$1;
      this.element = element;

      /**
       * Set correct direction of item
       */
      this.isRTL = isRTL;

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
        this.element.removeAttribute('aria-hidden');
      }
    }, {
      key: "hide",
      value: function hide() {
        this.isVisible = false;
        this.element.classList.remove(Classes.VISIBLE);
        this.element.classList.add(Classes.HIDDEN);
        this.element.setAttribute('aria-hidden', true);
      }
    }, {
      key: "init",
      value: function init() {
        this.addClasses([Classes.SHUFFLE_ITEM, Classes.VISIBLE]);
        this.applyCss(ShuffleItem.Css.INITIAL);
        this.applyCss(this.isRTL ? ShuffleItem.Css.DIRECTION.rtl : ShuffleItem.Css.DIRECTION.ltr);
        this.scale = ShuffleItem.Scale.VISIBLE;
        this.point = new Point$1();
      }
    }, {
      key: "addClasses",
      value: function addClasses(classes) {
        var _this = this;
        classes.forEach(function (className) {
          _this.element.classList.add(className);
        });
      }
    }, {
      key: "removeClasses",
      value: function removeClasses(classes) {
        var _this2 = this;
        classes.forEach(function (className) {
          _this2.element.classList.remove(className);
        });
      }
    }, {
      key: "applyCss",
      value: function applyCss(obj) {
        var _this3 = this;
        Object.keys(obj).forEach(function (key) {
          _this3.element.style[key] = obj[key];
        });
      }
    }, {
      key: "dispose",
      value: function dispose() {
        this.removeClasses([Classes.HIDDEN, Classes.VISIBLE, Classes.SHUFFLE_ITEM]);
        this.element.removeAttribute('style');
        this.element = null;
      }
    }]);
  }();
  ShuffleItem.Css = {
    INITIAL: {
      position: 'absolute',
      top: 0,
      visibility: 'visible',
      willChange: 'transform'
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
        visibility: 'visible'
      },
      after: {
        transitionDelay: ''
      }
    },
    HIDDEN: {
      before: {
        opacity: 0
      },
      after: {
        visibility: 'hidden',
        transitionDelay: ''
      }
    }
  };
  ShuffleItem.Scale = {
    VISIBLE: 1,
    HIDDEN: 0.001
  };
  var ShuffleItem$1 = ShuffleItem;
  var value = null;
  var testComputedSize = function testComputedSize() {
    if (value !== null) {
      return value;
    }
    var element = document.body || document.documentElement;
    var e = document.createElement('div');
    e.style.cssText = 'width:10px;padding:2px;box-sizing:border-box;';
    element.appendChild(e);
    var _window$getComputedSt = window.getComputedStyle(e, null),
      width = _window$getComputedSt.width;
    // Fix for issue #314
    value = Math.round(getNumber(width)) === 10;
    element.removeChild(e);
    return value;
  };

  /**
   * Retrieve the computed style for an element, parsed as a float.
   * @param {Element} element Element to get style for.
   * @param {string} style Style property.
   * @param {CSSStyleDeclaration} [styles] Optionally include clean styles to
   *     use instead of asking for them again.
   * @return {number} The parsed computed value or zero if that fails because IE
   *     will return 'auto' when the element doesn't have margins instead of
   *     the computed style.
   */
  function getNumberStyle(element, style) {
    var styles = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : window.getComputedStyle(element, null);
    var value = getNumber(styles[style]);

    // Support IE<=11 and W3C spec.
    if (!testComputedSize() && style === 'width') {
      value += getNumber(styles.paddingLeft) + getNumber(styles.paddingRight) + getNumber(styles.borderLeftWidth) + getNumber(styles.borderRightWidth);
    } else if (!testComputedSize() && style === 'height') {
      value += getNumber(styles.paddingTop) + getNumber(styles.paddingBottom) + getNumber(styles.borderTopWidth) + getNumber(styles.borderBottomWidth);
    }
    return value;
  }

  /**
   * Fisher-Yates shuffle.
   * http://stackoverflow.com/a/962890/373422
   * https://bost.ocks.org/mike/shuffle/
   * @param {Array} array Array to shuffle.
   * @return {Array} Randomly sorted array.
   */
  function randomize(array) {
    var n = array.length;
    while (n) {
      n -= 1;
      var i = Math.floor(Math.random() * (n + 1));
      var temp = array[i];
      array[i] = array[n];
      array[n] = temp;
    }
    return array;
  }
  var defaults = {
    // Use array.reverse() to reverse the results
    reverse: false,
    // Sorting function
    by: null,
    // Custom sort function
    compare: null,
    // If true, this will skip the sorting and return a randomized order in the array
    randomize: false,
    // Determines which property of each item in the array is passed to the
    // sorting method.
    key: 'element'
  };

  /**
   * You can return `undefined` from the `by` function to revert to DOM order.
   * @param {Array<T>} arr Array to sort.
   * @param {SortOptions} options Sorting options.
   * @return {Array<T>}
   */
  function sorter(arr, options) {
    var opts = _objectSpread(_objectSpread({}, defaults), options);
    var original = Array.from(arr);
    var revert = false;
    if (!arr.length) {
      return [];
    }
    if (opts.randomize) {
      return randomize(arr);
    }

    // Sort the elements by the opts.by function.
    // If we don't have opts.by, default to DOM order
    if (typeof opts.by === 'function') {
      arr.sort(function (a, b) {
        // Exit early if we already know we want to revert
        if (revert) {
          return 0;
        }
        var valA = opts.by(a[opts.key]);
        var valB = opts.by(b[opts.key]);

        // If both values are undefined, use the DOM order
        if (valA === undefined && valB === undefined) {
          revert = true;
          return 0;
        }
        if (valA < valB || valA === 'sortFirst' || valB === 'sortLast') {
          return -1;
        }
        if (valA > valB || valA === 'sortLast' || valB === 'sortFirst') {
          return 1;
        }
        return 0;
      });
    } else if (typeof opts.compare === 'function') {
      arr.sort(opts.compare);
    }

    // Revert to the original array if necessary
    if (revert) {
      return original;
    }
    if (opts.reverse) {
      arr.reverse();
    }
    return arr;
  }
  var transitions = {};
  var eventName = 'transitionend';
  var count = 0;
  function uniqueId() {
    count += 1;
    return eventName + count;
  }
  function cancelTransitionEnd(id) {
    if (transitions[id]) {
      transitions[id].element.removeEventListener(eventName, transitions[id].listener);
      transitions[id] = null;
      return true;
    }
    return false;
  }
  function onTransitionEnd(element, callback) {
    var id = uniqueId();
    var listener = function listener(evt) {
      if (evt.currentTarget === evt.target) {
        cancelTransitionEnd(id);
        callback(evt);
      }
    };
    element.addEventListener(eventName, listener);
    transitions[id] = {
      element: element,
      listener: listener
    };
    return id;
  }
  function arrayMax(array) {
    return Math.max.apply(Math, _toConsumableArray(array));
  }
  function arrayMin(array) {
    return Math.min.apply(Math, _toConsumableArray(array));
  }

  /**
   * Determine the number of columns an items spans.
   * @param {number} itemWidth Width of the item.
   * @param {number} columnWidth Width of the column (includes gutter).
   * @param {number} columns Total number of columns
   * @param {number} threshold A buffer value for the size of the column to fit.
   * @return {number}
   */
  function getColumnSpan(itemWidth, columnWidth, columns, threshold) {
    var columnSpan = itemWidth / columnWidth;

    // If the difference between the rounded column span number and the
    // calculated column span number is really small, round the number to
    // make it fit.
    if (Math.abs(Math.round(columnSpan) - columnSpan) < threshold) {
      // e.g. columnSpan = 4.0089945390298745
      columnSpan = Math.round(columnSpan);
    }

    // Ensure the column span is not more than the amount of columns in the whole layout.
    return Math.min(Math.ceil(columnSpan), columns);
  }

  /**
   * Retrieves the column set to use for placement.
   * @param {number} columnSpan The number of columns this current item spans.
   * @param {number} columns The total columns in the grid.
   * @return {Array.<number>} An array of numbers representing the column set.
   */
  function getAvailablePositions(positions, columnSpan, columns) {
    // The item spans only one column.
    if (columnSpan === 1) {
      return positions;
    }

    // The item spans more than one column, figure out how many different
    // places it could fit horizontally.
    // The group count is the number of places within the positions this block
    // could fit, ignoring the current positions of items.
    // Imagine a 2 column brick as the second item in a 4 column grid with
    // 10px height each. Find the places it would fit:
    // [20, 10, 10, 0]
    //  |   |   |
    //  *   *   *
    //
    // Then take the places which fit and get the bigger of the two:
    // max([20, 10]), max([10, 10]), max([10, 0]) = [20, 10, 10]
    //
    // Next, find the first smallest number (the short column).
    // [20, 10, 10]
    //      |
    //      *
    //
    // And that's where it should be placed!
    //
    // Another example where the second column's item extends past the first:
    // [10, 20, 10, 0] => [20, 20, 10] => 10
    var available = [];

    // For how many possible positions for this item there are.
    for (var i = 0; i <= columns - columnSpan; i++) {
      // Find the bigger value for each place it could fit.
      available.push(arrayMax(positions.slice(i, i + columnSpan)));
    }
    return available;
  }

  /**
   * Find index of short column, the first from the left where this item will go.
   *
   * @param {Array.<number>} positions The array to search for the smallest number.
   * @param {number} buffer Optional buffer which is very useful when the height
   *     is a percentage of the width.
   * @return {number} Index of the short column.
   */
  function getShortColumn(positions, buffer) {
    var minPosition = arrayMin(positions);
    for (var i = 0, len = positions.length; i < len; i++) {
      if (positions[i] >= minPosition - buffer && positions[i] <= minPosition + buffer) {
        return i;
      }
    }
    return 0;
  }

  /**
   * Determine the location of the next item, based on its size.
   * @param {Object} itemSize Object with width and height.
   * @param {Array.<number>} positions Positions of the other current items.
   * @param {number} gridSize The column width or row height.
   * @param {number} total The total number of columns or rows.
   * @param {number} threshold Buffer value for the column to fit.
   * @param {number} buffer Vertical buffer for the height of items.
   * @return {Point}
   */
  function getItemPosition(_ref) {
    var itemSize = _ref.itemSize,
      positions = _ref.positions,
      gridSize = _ref.gridSize,
      total = _ref.total,
      threshold = _ref.threshold,
      buffer = _ref.buffer;
    var span = getColumnSpan(itemSize.width, gridSize, total, threshold);
    var setY = getAvailablePositions(positions, span, total);
    var shortColumnIndex = getShortColumn(setY, buffer);

    // Position the item
    var point = new Point$1(gridSize * shortColumnIndex, setY[shortColumnIndex]);

    // Update the columns array with the new values for each column.
    // e.g. before the update the columns could be [250, 0, 0, 0] for an item
    // which spans 2 columns. After it would be [250, itemHeight, itemHeight, 0].
    var setHeight = setY[shortColumnIndex] + itemSize.height;
    for (var i = 0; i < span; i++) {
      positions[shortColumnIndex + i] = setHeight;
    }
    return point;
  }

  /**
   * This method attempts to center items. This method could potentially be slow
   * with a large number of items because it must place items, then check every
   * previous item to ensure there is no overlap.
   * @param {Array.<Rect>} itemRects Item data objects.
   * @param {number} containerWidth Width of the containing element.
   * @return {Array.<Point>}
   */
  function getCenteredPositions(itemRects, containerWidth) {
    var rowMap = {};

    // Populate rows by their offset because items could jump between rows like:
    // a   c
    //  bbb
    itemRects.forEach(function (itemRect) {
      if (rowMap[itemRect.top]) {
        // Push the point to the last row array.
        rowMap[itemRect.top].push(itemRect);
      } else {
        // Start of a new row.
        rowMap[itemRect.top] = [itemRect];
      }
    });

    // For each row, find the end of the last item, then calculate
    // the remaining space by dividing it by 2. Then add that
    // offset to the x position of each point.
    var rects = [];
    var rows = [];
    var centeredRows = [];
    Object.keys(rowMap).forEach(function (key) {
      var itemRects = rowMap[key];
      rows.push(itemRects);
      var lastItem = itemRects[itemRects.length - 1];
      var end = lastItem.left + lastItem.width;
      var offset = Math.round((containerWidth - end) / 2);
      var finalRects = itemRects;
      var canMove = false;
      if (offset > 0) {
        var newRects = [];
        canMove = itemRects.every(function (r) {
          var newRect = new Rect(r.left + offset, r.top, r.width, r.height, r.id);

          // Check all current rects to make sure none overlap.
          var noOverlap = !rects.some(function (r) {
            return Rect.intersects(newRect, r);
          });
          newRects.push(newRect);
          return noOverlap;
        });

        // If none of the rectangles overlapped, the whole group can be centered.
        if (canMove) {
          finalRects = newRects;
        }
      }

      // If the items are not going to be offset, ensure that the original
      // placement for this row will not overlap previous rows (row-spanning
      // elements could be in the way).
      if (!canMove) {
        var intersectingRect;
        var hasOverlap = itemRects.some(function (itemRect) {
          return rects.some(function (r) {
            var intersects = Rect.intersects(itemRect, r);
            if (intersects) {
              intersectingRect = r;
            }
            return intersects;
          });
        });

        // If there is any overlap, replace the overlapping row with the original.
        if (hasOverlap) {
          var rowIndex = centeredRows.findIndex(function (items) {
            return items.includes(intersectingRect);
          });
          centeredRows.splice(rowIndex, 1, rows[rowIndex]);
        }
      }
      rects = rects.concat(finalRects);
      centeredRows.push(finalRects);
    });

    // Reduce array of arrays to a single array of points.
    // https://stackoverflow.com/a/10865042/373422
    // Then reset sort back to how the items were passed to this method.
    // Remove the wrapper object with index, map to a Point.
    return centeredRows.flat().sort(function (a, b) {
      return a.id - b.id;
    }).map(function (itemRect) {
      return new Point$1(itemRect.left, itemRect.top);
    });
  }

  /**
   * Hyphenates a javascript style string to a css one. For example:
   * MozBoxSizing -> -moz-box-sizing.
   * @param {string} str The string to hyphenate.
   * @return {string} The hyphenated string.
   */
  function hyphenate(str) {
    return str.replace(/([A-Z])/g, function (str, m1) {
      return "-".concat(m1.toLowerCase());
    });
  }
  function arrayUnique(x) {
    return Array.from(new Set(x));
  }

  // Used for unique instance variables
  var id = 0;
  var Shuffle = /*#__PURE__*/function (_tinyEmitterExports) {
    /**
     * Categorize, sort, and filter a responsive grid of items.
     *
     * @param {Element} element An element which is the parent container for the grid items.
     * @param {Object} [options=Shuffle.options] Options object.
     * @constructor
     */
    function Shuffle(element) {
      var _this4;
      _classCallCheck(this, Shuffle);
      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      _this4 = _callSuper(this, Shuffle);
      _this4.options = _objectSpread(_objectSpread({}, Shuffle.options), options);
      _this4.lastSort = {};
      _this4.group = Shuffle.ALL_ITEMS;
      _this4.lastFilter = Shuffle.ALL_ITEMS;
      _this4.isEnabled = true;
      _this4.isDestroyed = false;
      _this4.isInitialized = false;
      _this4._transitions = [];
      _this4.isTransitioning = false;
      _this4._queue = [];
      var el = _this4._getElementOption(element);
      if (!el) {
        throw new TypeError('Shuffle needs to be initialized with an element.');
      }
      _this4.element = el;
      _this4.id = "shuffle_".concat(id);
      id += 1;
      _this4._init();
      _this4.isInitialized = true;
      return _this4;
    }
    _inherits(Shuffle, _tinyEmitterExports);
    return _createClass(Shuffle, [{
      key: "_init",
      value: function _init() {
        this.items = this._getItems();
        this.sortedItems = this.items;
        this.options.sizer = this._getElementOption(this.options.sizer);

        // Add class and invalidate styles
        this.element.classList.add(Shuffle.Classes.BASE);

        // Set initial css for each item
        this._initItems(this.items);

        // If the page has not already emitted the `load` event, call layout on load.
        // This avoids layout issues caused by images and fonts loading after the
        // instance has been initialized.
        if (document.readyState !== 'complete') {
          var layout = this.layout.bind(this);
          window.addEventListener('load', function onLoad() {
            window.removeEventListener('load', onLoad);
            layout();
          });
        }

        // Get container css all in one request. Causes reflow
        var containerCss = window.getComputedStyle(this.element, null);
        var containerWidth = Shuffle.getSize(this.element).width;

        // Add styles to the container if it doesn't have them.
        this._validateStyles(containerCss);

        // We already got the container's width above, no need to cause another
        // reflow getting it again... Calculate the number of columns there will be
        this._setColumns(containerWidth);

        // Kick off!
        this.filter(this.options.group, this.options.initialSort);

        // Bind resize events
        this._rafId = null;
        // This is true for all supported browsers, but just to be safe, avoid throwing
        // an error if ResizeObserver is not present. You can manually add a window resize
        // event and call `update()` if ResizeObserver is missing, or use Shuffle v5.
        if ('ResizeObserver' in window) {
          this._resizeObserver = new ResizeObserver(this._handleResizeCallback.bind(this));
          this._resizeObserver.observe(this.element);
        }

        // The shuffle items haven't had transitions set on them yet so the user
        // doesn't see the first layout. Set them now that the first layout is done.
        // First, however, a synchronous layout must be caused for the previous
        // styles to be applied without transitions.
        this.element.offsetWidth; // eslint-disable-line no-unused-expressions
        this.setItemTransitions(this.items);
        this.element.style.transition = "height ".concat(this.options.speed, "ms ").concat(this.options.easing);
      }

      /**
       * Retrieve an element from an option.
       * @param {string|jQuery|Element} option The option to check.
       * @return {?Element} The plain element or null.
       * @private
       */
    }, {
      key: "_getElementOption",
      value: function _getElementOption(option) {
        // If column width is a string, treat is as a selector and search for the
        // sizer element within the outermost container
        if (typeof option === 'string') {
          return this.element.querySelector(option);
        }

        // Check for an element
        if (option && option.nodeType && option.nodeType === 1) {
          return option;
        }

        // Check for jQuery object
        if (option && option.jquery) {
          return option[0];
        }
        return null;
      }

      /**
       * Ensures the shuffle container has the css styles it needs applied to it.
       * @param {Object} styles Key value pairs for position and overflow.
       * @private
       */
    }, {
      key: "_validateStyles",
      value: function _validateStyles(styles) {
        // Position cannot be static.
        if (styles.position === 'static') {
          this.element.style.position = 'relative';
        }

        // Overflow has to be hidden.
        if (styles.overflow !== 'hidden') {
          this.element.style.overflow = 'hidden';
        }
      }

      /**
       * Filter the elements by a category.
       * @param {string|string[]|function(Element):boolean} [category] Category to
       *     filter by. If it's given, the last category will be used to filter the items.
       * @param {Array} [collection] Optionally filter a collection. Defaults to
       *     all the items.
       * @return {{visible: ShuffleItem[], hidden: ShuffleItem[]}}
       * @private
       */
    }, {
      key: "_filter",
      value: function _filter() {
        var category = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.lastFilter;
        var collection = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.items;
        var set = this._getFilteredSets(category, collection);

        // Individually add/remove hidden/visible classes
        this._toggleFilterClasses(set);

        // Save the last filter in case elements are appended.
        this.lastFilter = category;

        // This is saved mainly because providing a filter function (like searching)
        // will overwrite the `lastFilter` property every time its called.
        if (typeof category === 'string') {
          this.group = category;
        }
        return set;
      }

      /**
       * Returns an object containing the visible and hidden elements.
       * @param {string|string[]|function(Element):boolean} category Category or function to filter by.
       * @param {ShuffleItem[]} items A collection of items to filter.
       * @return {{visible: ShuffleItem[], hidden: ShuffleItem[]}}
       * @private
       */
    }, {
      key: "_getFilteredSets",
      value: function _getFilteredSets(category, items) {
        var _this5 = this;
        var visible = [];
        var hidden = [];

        // category === 'all', add visible class to everything
        if (category === Shuffle.ALL_ITEMS) {
          visible = items;

          // Loop through each item and use provided function to determine
          // whether to hide it or not.
        } else {
          items.forEach(function (item) {
            if (_this5._doesPassFilter(category, item.element)) {
              visible.push(item);
            } else {
              hidden.push(item);
            }
          });
        }
        return {
          visible: visible,
          hidden: hidden
        };
      }

      /**
       * Test an item to see if it passes a category.
       * @param {string|string[]|function():boolean} category Category or function to filter by.
       * @param {Element} element An element to test.
       * @return {boolean} Whether it passes the category/filter.
       * @private
       */
    }, {
      key: "_doesPassFilter",
      value: function _doesPassFilter(category, element) {
        if (typeof category === 'function') {
          return category.call(element, element, this);
        }

        // Check each element's data-groups attribute against the given category.
        var attr = element.dataset[Shuffle.FILTER_ATTRIBUTE_KEY];
        var keys = this.options.delimiter ? attr.split(this.options.delimiter) : JSON.parse(attr);
        function testCategory(category) {
          return keys.includes(category);
        }
        if (Array.isArray(category)) {
          if (this.options.filterMode === Shuffle.FilterMode.ANY) {
            return category.some(testCategory);
          }
          return category.every(testCategory);
        }
        return keys.includes(category);
      }

      /**
       * Toggles the visible and hidden class names.
       * @param {{visible, hidden}} Object with visible and hidden arrays.
       * @private
       */
    }, {
      key: "_toggleFilterClasses",
      value: function _toggleFilterClasses(_ref) {
        var visible = _ref.visible,
          hidden = _ref.hidden;
        visible.forEach(function (item) {
          item.show();
        });
        hidden.forEach(function (item) {
          item.hide();
        });
      }

      /**
       * Set the initial css for each item
       * @param {ShuffleItem[]} items Set to initialize.
       * @private
       */
    }, {
      key: "_initItems",
      value: function _initItems(items) {
        items.forEach(function (item) {
          item.init();
        });
      }

      /**
       * Remove element reference and styles.
       * @param {ShuffleItem[]} items Set to dispose.
       * @private
       */
    }, {
      key: "_disposeItems",
      value: function _disposeItems(items) {
        items.forEach(function (item) {
          item.dispose();
        });
      }

      /**
       * Updates the visible item count.
       * @private
       */
    }, {
      key: "_updateItemCount",
      value: function _updateItemCount() {
        this.visibleItems = this._getFilteredItems().length;
      }

      /**
       * Sets css transform transition on a group of elements. This is not executed
       * at the same time as `item.init` so that transitions don't occur upon
       * initialization of a new Shuffle instance.
       * @param {ShuffleItem[]} items Shuffle items to set transitions on.
       * @protected
       */
    }, {
      key: "setItemTransitions",
      value: function setItemTransitions(items) {
        var _this$options = this.options,
          speed = _this$options.speed,
          easing = _this$options.easing;
        var positionProps = this.options.useTransforms ? ['transform'] : ['top', 'left'];

        // Allow users to transition other properties if they exist in the `before`
        // css mapping of the shuffle item.
        var cssProps = Object.keys(ShuffleItem$1.Css.HIDDEN.before).map(function (k) {
          return hyphenate(k);
        });
        var properties = positionProps.concat(cssProps).join();
        items.forEach(function (item) {
          item.element.style.transitionDuration = "".concat(speed, "ms");
          item.element.style.transitionTimingFunction = easing;
          item.element.style.transitionProperty = properties;
        });
      }
    }, {
      key: "_getItems",
      value: function _getItems() {
        var _this6 = this;
        return Array.from(this.element.children).filter(function (el) {
          return el.matches(_this6.options.itemSelector);
        }).map(function (el) {
          return new ShuffleItem$1(el, _this6.options.isRTL);
        });
      }

      /**
       * Combine the current items array with a new one and sort it by DOM order.
       * @param {ShuffleItem[]} items Items to track.
       * @return {ShuffleItem[]}
       */
    }, {
      key: "_mergeNewItems",
      value: function _mergeNewItems(items) {
        var children = Array.from(this.element.children);
        return sorter(this.items.concat(items), {
          by: function by(element) {
            return children.indexOf(element);
          }
        });
      }
    }, {
      key: "_getFilteredItems",
      value: function _getFilteredItems() {
        return this.items.filter(function (item) {
          return item.isVisible;
        });
      }
    }, {
      key: "_getConcealedItems",
      value: function _getConcealedItems() {
        return this.items.filter(function (item) {
          return !item.isVisible;
        });
      }

      /**
       * Returns the column size, based on column width and sizer options.
       * @param {number} containerWidth Size of the parent container.
       * @param {number} gutterSize Size of the gutters.
       * @return {number}
       * @private
       */
    }, {
      key: "_getColumnSize",
      value: function _getColumnSize(containerWidth, gutterSize) {
        var size;

        // If the columnWidth property is a function, then the grid is fluid
        if (typeof this.options.columnWidth === 'function') {
          size = this.options.columnWidth(containerWidth);

          // columnWidth option isn't a function, are they using a sizing element?
        } else if (this.options.sizer) {
          size = Shuffle.getSize(this.options.sizer).width;

          // if not, how about the explicitly set option?
        } else if (this.options.columnWidth) {
          size = this.options.columnWidth;

          // or use the size of the first item
        } else if (this.items.length > 0) {
          size = Shuffle.getSize(this.items[0].element, true).width;

          // if there's no items, use size of container
        } else {
          size = containerWidth;
        }

        // Don't let them set a column width of zero.
        if (size === 0) {
          size = containerWidth;
        }
        return size + gutterSize;
      }

      /**
       * Returns the gutter size, based on gutter width and sizer options.
       * @param {number} containerWidth Size of the parent container.
       * @return {number}
       * @private
       */
    }, {
      key: "_getGutterSize",
      value: function _getGutterSize(containerWidth) {
        var size;
        if (typeof this.options.gutterWidth === 'function') {
          size = this.options.gutterWidth(containerWidth);
        } else if (this.options.sizer) {
          size = getNumberStyle(this.options.sizer, 'marginLeft');
        } else {
          size = this.options.gutterWidth;
        }
        return size;
      }

      /**
       * Calculate the number of columns to be used. Gets css if using sizer element.
       * @param {number} [containerWidth] Optionally specify a container width if
       *    it's already available.
       */
    }, {
      key: "_setColumns",
      value: function _setColumns() {
        var containerWidth = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : Shuffle.getSize(this.element).width;
        var gutter = this._getGutterSize(containerWidth);
        var columnWidth = this._getColumnSize(containerWidth, gutter);
        var calculatedColumns = (containerWidth + gutter) / columnWidth;

        // Widths given from getStyles are not precise enough...
        if (Math.abs(Math.round(calculatedColumns) - calculatedColumns) < this.options.columnThreshold) {
          // e.g. calculatedColumns = 11.998876
          calculatedColumns = Math.round(calculatedColumns);
        }
        this.cols = Math.max(Math.floor(calculatedColumns || 0), 1);
        this.containerWidth = containerWidth;
        this.colWidth = columnWidth;
      }

      /**
       * Adjust the height of the grid
       */
    }, {
      key: "_setContainerSize",
      value: function _setContainerSize() {
        this.element.style.height = "".concat(this._getContainerSize(), "px");
      }

      /**
       * Based on the column heights, it returns the biggest one.
       * @return {number}
       * @private
       */
    }, {
      key: "_getContainerSize",
      value: function _getContainerSize() {
        return arrayMax(this.positions);
      }

      /**
       * Get the clamped stagger amount.
       * @param {number} index Index of the item to be staggered.
       * @return {number}
       */
    }, {
      key: "_getStaggerAmount",
      value: function _getStaggerAmount(index) {
        return Math.min(index * this.options.staggerAmount, this.options.staggerAmountMax);
      }

      /**
       * Emit an event from this instance.
       * @param {string} name Event name.
       * @param {Object} [data={}] Optional object data.
       */
    }, {
      key: "_dispatch",
      value: function _dispatch(name) {
        var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
        if (this.isDestroyed) {
          return;
        }
        data.shuffle = this;
        this.emit(name, data);
      }

      /**
       * Zeros out the y columns array, which is used to determine item placement.
       * @private
       */
    }, {
      key: "_resetCols",
      value: function _resetCols() {
        var i = this.cols;
        this.positions = [];
        while (i) {
          i -= 1;
          this.positions.push(0);
        }
      }

      /**
       * Loops through each item that should be shown and calculates the x, y position.
       * @param {ShuffleItem[]} items Array of items that will be shown/layed
       *     out in order in their array.
       */
    }, {
      key: "_layout",
      value: function _layout(items) {
        var _this7 = this;
        var itemPositions = this._getNextPositions(items);
        var count = 0;
        items.forEach(function (item, i) {
          function callback() {
            item.applyCss(ShuffleItem$1.Css.VISIBLE.after);
          }

          // If the item will not change its position, do not add it to the render
          // queue. Transitions don't fire when setting a property to the same value.
          if (Point$1.equals(item.point, itemPositions[i]) && !item.isHidden) {
            item.applyCss(ShuffleItem$1.Css.VISIBLE.before);
            callback();
            return;
          }
          item.point = itemPositions[i];
          item.scale = ShuffleItem$1.Scale.VISIBLE;
          item.isHidden = false;

          // Clone the object so that the `before` object isn't modified when the
          // transition delay is added.
          var styles = _this7.getStylesForTransition(item, ShuffleItem$1.Css.VISIBLE.before);
          styles.transitionDelay = "".concat(_this7._getStaggerAmount(count), "ms");
          _this7._queue.push({
            item: item,
            styles: styles,
            callback: callback
          });
          count += 1;
        });
      }

      /**
       * Return an array of Point instances representing the future positions of
       * each item.
       * @param {ShuffleItem[]} items Array of sorted shuffle items.
       * @return {Point[]}
       * @private
       */
    }, {
      key: "_getNextPositions",
      value: function _getNextPositions(items) {
        var _this8 = this;
        // If position data is going to be changed, add the item's size to the
        // transformer to allow for calculations.
        if (this.options.isCentered) {
          var itemsData = items.map(function (item, i) {
            var itemSize = Shuffle.getSize(item.element, true);
            var point = _this8._getItemPosition(itemSize);
            return new Rect(point.x, point.y, itemSize.width, itemSize.height, i);
          });
          return this.getTransformedPositions(itemsData, this.containerWidth);
        }

        // If no transforms are going to happen, simply return an array of the
        // future points of each item.
        return items.map(function (item) {
          return _this8._getItemPosition(Shuffle.getSize(item.element, true));
        });
      }

      /**
       * Determine the location of the next item, based on its size.
       * @param {{width: number, height: number}} itemSize Object with width and height.
       * @return {Point}
       * @private
       */
    }, {
      key: "_getItemPosition",
      value: function _getItemPosition(itemSize) {
        return getItemPosition({
          itemSize: itemSize,
          positions: this.positions,
          gridSize: this.colWidth,
          total: this.cols,
          threshold: this.options.columnThreshold,
          buffer: this.options.buffer
        });
      }

      /**
       * Mutate positions before they're applied.
       * @param {Rect[]} itemRects Item data objects.
       * @param {number} containerWidth Width of the containing element.
       * @return {Point[]}
       * @protected
       */
    }, {
      key: "getTransformedPositions",
      value: function getTransformedPositions(itemRects, containerWidth) {
        return getCenteredPositions(itemRects, containerWidth);
      }

      /**
       * Hides the elements that don't match our filter.
       * @param {ShuffleItem[]} collection Collection to shrink.
       * @private
       */
    }, {
      key: "_shrink",
      value: function _shrink() {
        var _this9 = this;
        var collection = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this._getConcealedItems();
        var count = 0;
        collection.forEach(function (item) {
          function callback() {
            item.applyCss(ShuffleItem$1.Css.HIDDEN.after);
          }

          // Continuing would add a transitionend event listener to the element, but
          // that listener would not execute because the transform and opacity would
          // stay the same.
          // The callback is executed here because it is not guaranteed to be called
          // after the transitionend event because the transitionend could be
          // canceled if another animation starts.
          if (item.isHidden) {
            item.applyCss(ShuffleItem$1.Css.HIDDEN.before);
            callback();
            return;
          }
          item.scale = ShuffleItem$1.Scale.HIDDEN;
          item.isHidden = true;
          var styles = _this9.getStylesForTransition(item, ShuffleItem$1.Css.HIDDEN.before);
          styles.transitionDelay = "".concat(_this9._getStaggerAmount(count), "ms");
          _this9._queue.push({
            item: item,
            styles: styles,
            callback: callback
          });
          count += 1;
        });
      }

      /**
       * Resize handler.
       * @param {ResizeObserverEntry[]} entries
       */
    }, {
      key: "_handleResizeCallback",
      value: function _handleResizeCallback(entries) {
        // If shuffle is disabled, destroyed, don't do anything.
        // You can still manually force a shuffle update with shuffle.update({ force: true }).
        if (!this.isEnabled || this.isDestroyed) {
          return;
        }

        // The reason ESLint disables this is because for..of generates a lot of extra
        // code using Babel, but Shuffle no longer supports browsers that old, so
        // nothing to worry about.
        // eslint-disable-next-line no-restricted-syntax
        var _iterator = _createForOfIteratorHelper(entries),
          _step;
        try {
          for (_iterator.s(); !(_step = _iterator.n()).done;) {
            var entry = _step.value;
            if (Math.round(entry.contentRect.width) !== Math.round(this.containerWidth)) {
              // If there was already an animation waiting, cancel it.
              cancelAnimationFrame(this._rafId);
              // Offload updating the DOM until the browser is ready.
              this._rafId = requestAnimationFrame(this.update.bind(this));
            }
          }
        } catch (err) {
          _iterator.e(err);
        } finally {
          _iterator.f();
        }
      }

      /**
       * Returns styles which will be applied to the an item for a transition.
       * @param {ShuffleItem} item Item to get styles for. Should have updated
       *   scale and point properties.
       * @param {Object} styleObject Extra styles that will be used in the transition.
       * @return {!Object} Transforms for transitions, left/top for animate.
       * @protected
       */
    }, {
      key: "getStylesForTransition",
      value: function getStylesForTransition(item, styleObject) {
        // Clone the object to avoid mutating the original.
        var styles = _objectSpread({}, styleObject);
        if (this.options.useTransforms) {
          var sign = this.options.isRTL ? '-' : '';
          var x = this.options.roundTransforms ? Math.round(item.point.x) : item.point.x;
          var y = this.options.roundTransforms ? Math.round(item.point.y) : item.point.y;
          styles.transform = "translate(".concat(sign).concat(x, "px, ").concat(y, "px) scale(").concat(item.scale, ")");
        } else {
          if (this.options.isRTL) {
            styles.right = "".concat(item.point.x, "px");
          } else {
            styles.left = "".concat(item.point.x, "px");
          }
          styles.top = "".concat(item.point.y, "px");
        }
        return styles;
      }

      /**
       * Listen for the transition end on an element and execute the itemCallback
       * when it finishes.
       * @param {Element} element Element to listen on.
       * @param {function} itemCallback Callback for the item.
       * @param {function} done Callback to notify `parallel` that this one is done.
       */
    }, {
      key: "_whenTransitionDone",
      value: function _whenTransitionDone(element, itemCallback, done) {
        var id = onTransitionEnd(element, function (evt) {
          itemCallback();
          done(null, evt);
        });
        this._transitions.push(id);
      }

      /**
       * Return a function which will set CSS styles and call the `done` function
       * when (if) the transition finishes.
       * @param {Object} opts Transition object.
       * @return {function} A function to be called with a `done` function.
       */
    }, {
      key: "_getTransitionFunction",
      value: function _getTransitionFunction(opts) {
        var _this10 = this;
        return function (done) {
          opts.item.applyCss(opts.styles);
          _this10._whenTransitionDone(opts.item.element, opts.callback, done);
        };
      }

      /**
       * Execute the styles gathered in the style queue. This applies styles to elements,
       * triggering transitions.
       * @private
       */
    }, {
      key: "_processQueue",
      value: function _processQueue() {
        if (this.isTransitioning) {
          this._cancelMovement();
        }
        var hasSpeed = this.options.speed > 0;
        var hasQueue = this._queue.length > 0;
        if (hasQueue && hasSpeed && this.isInitialized) {
          this._startTransitions(this._queue);
        } else if (hasQueue) {
          this._styleImmediately(this._queue);
          this._dispatch(Shuffle.EventType.LAYOUT);

          // A call to layout happened, but none of the newly visible items will
          // change position or the transition duration is zero, which will not trigger
          // the transitionend event.
        } else {
          this._dispatch(Shuffle.EventType.LAYOUT);
        }

        // Remove everything in the style queue
        this._queue.length = 0;
      }

      /**
       * Wait for each transition to finish, the emit the layout event.
       * @param {Object[]} transitions Array of transition objects.
       */
    }, {
      key: "_startTransitions",
      value: function _startTransitions(transitions) {
        var _this11 = this;
        // Set flag that shuffle is currently in motion.
        this.isTransitioning = true;

        // Create an array of functions to be called.
        var callbacks = transitions.map(function (obj) {
          return _this11._getTransitionFunction(obj);
        });
        arrayParallel(callbacks, this._movementFinished.bind(this));
      }
    }, {
      key: "_cancelMovement",
      value: function _cancelMovement() {
        // Remove the transition end event for each listener.
        this._transitions.forEach(cancelTransitionEnd);

        // Reset the array.
        this._transitions.length = 0;

        // Show it's no longer active.
        this.isTransitioning = false;
      }

      /**
       * Apply styles without a transition.
       * @param {Object[]} objects Array of transition objects.
       * @private
       */
    }, {
      key: "_styleImmediately",
      value: function _styleImmediately(objects) {
        if (objects.length) {
          var elements = objects.map(function (obj) {
            return obj.item.element;
          });
          Shuffle._skipTransitions(elements, function () {
            objects.forEach(function (obj) {
              obj.item.applyCss(obj.styles);
              obj.callback();
            });
          });
        }
      }
    }, {
      key: "_movementFinished",
      value: function _movementFinished() {
        this._transitions.length = 0;
        this.isTransitioning = false;
        this._dispatch(Shuffle.EventType.LAYOUT);
      }

      /**
       * The magic. This is what makes the plugin 'shuffle'
       * @param {string|string[]|function(Element):boolean} [category] Category to filter by.
       *     Can be a function, string, or array of strings.
       * @param {SortOptions} [sortOptions] A sort object which can sort the visible set
       */
    }, {
      key: "filter",
      value: function filter(category, sortOptions) {
        if (!this.isEnabled) {
          return;
        }
        if (!category || category && category.length === 0) {
          category = Shuffle.ALL_ITEMS; // eslint-disable-line no-param-reassign
        }
        this._filter(category);

        // Shrink each hidden item
        this._shrink();

        // How many visible elements?
        this._updateItemCount();

        // Update transforms on visible elements so they will animate to their new positions.
        this.sort(sortOptions);
      }

      /**
       * Gets the visible elements, sorts them, and passes them to layout.
       * @param {SortOptions} [sortOptions] The options object to pass to `sorter`.
       */
    }, {
      key: "sort",
      value: function sort() {
        var sortOptions = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.lastSort;
        if (!this.isEnabled) {
          return;
        }
        this._resetCols();
        var items = sorter(this._getFilteredItems(), sortOptions);
        this.sortedItems = items;
        this._layout(items);

        // `_layout` always happens after `_shrink`, so it's safe to process the style
        // queue here with styles from the shrink method.
        this._processQueue();

        // Adjust the height of the container.
        this._setContainerSize();
        this.lastSort = sortOptions;
      }

      /**
       * Reposition everything.
       * @param {object} options options object
       * @param {boolean} [options.recalculateSizes=true] Whether to calculate column, gutter, and container widths again.
       * @param {boolean} [options.force=false] By default, `update` does nothing if the instance is disabled. Setting this
       *    to true forces the update to happen regardless.
       */
    }, {
      key: "update",
      value: function update() {
        var _ref2 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
          _ref2$recalculateSize = _ref2.recalculateSizes,
          recalculateSizes = _ref2$recalculateSize === void 0 ? true : _ref2$recalculateSize,
          _ref2$force = _ref2.force,
          force = _ref2$force === void 0 ? false : _ref2$force;
        if (this.isEnabled || force) {
          if (recalculateSizes) {
            this._setColumns();
          }

          // Layout items
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
       * @param {Element[]} newItems Collection of new items.
       */
    }, {
      key: "add",
      value: function add(newItems) {
        var _this12 = this;
        var items = arrayUnique(newItems).map(function (el) {
          return new ShuffleItem$1(el, _this12.options.isRTL);
        });

        // Add classes and set initial positions.
        this._initItems(items);

        // Determine which items will go with the current filter.
        this._resetCols();
        var allItems = this._mergeNewItems(items);
        var sortedItems = sorter(allItems, this.lastSort);
        var allSortedItemsSet = this._filter(this.lastFilter, sortedItems);
        var isNewItem = function isNewItem(item) {
          return items.includes(item);
        };
        var applyHiddenState = function applyHiddenState(item) {
          item.scale = ShuffleItem$1.Scale.HIDDEN;
          item.isHidden = true;
          item.applyCss(ShuffleItem$1.Css.HIDDEN.before);
          item.applyCss(ShuffleItem$1.Css.HIDDEN.after);
        };

        // Layout all items again so that new items get positions.
        // Synchronously apply positions.
        var itemPositions = this._getNextPositions(allSortedItemsSet.visible);
        allSortedItemsSet.visible.forEach(function (item, i) {
          if (isNewItem(item)) {
            item.point = itemPositions[i];
            applyHiddenState(item);
            item.applyCss(_this12.getStylesForTransition(item, {}));
          }
        });
        allSortedItemsSet.hidden.forEach(function (item) {
          if (isNewItem(item)) {
            applyHiddenState(item);
          }
        });

        // Cause layout so that the styles above are applied.
        this.element.offsetWidth; // eslint-disable-line no-unused-expressions

        // Add transition to each item.
        this.setItemTransitions(items);

        // Update the list of items.
        this.items = this._mergeNewItems(items);

        // Update layout/visibility of new and old items.
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
       * @param {boolean} [isUpdateLayout=true] if undefined, shuffle will update columns and gutters
       */
    }, {
      key: "enable",
      value: function enable() {
        var isUpdateLayout = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
        this.isEnabled = true;
        if (isUpdateLayout) {
          this.update();
        }
      }

      /**
       * Remove 1 or more shuffle items.
       * @param {Element[]} elements An array containing one or more
       *     elements in shuffle
       * @return {Shuffle} The shuffle instance.
       */
    }, {
      key: "remove",
      value: function remove(elements) {
        var _this13 = this;
        if (!elements.length) {
          return;
        }
        var collection = arrayUnique(elements);
        var oldItems = collection.map(function (element) {
          return _this13.getItemByElement(element);
        }).filter(function (item) {
          return !!item;
        });
        var handleLayout = function handleLayout() {
          _this13._disposeItems(oldItems);

          // Remove the collection in the callback
          collection.forEach(function (element) {
            element.parentNode.removeChild(element);
          });
          _this13._dispatch(Shuffle.EventType.REMOVED, {
            collection: collection
          });
        };

        // Hide collection first.
        this._toggleFilterClasses({
          visible: [],
          hidden: oldItems
        });
        this._shrink(oldItems);
        this.sort();

        // Update the list of items here because `remove` could be called again
        // with an item that is in the process of being removed.
        this.items = this.items.filter(function (item) {
          return !oldItems.includes(item);
        });
        this._updateItemCount();
        this.once(Shuffle.EventType.LAYOUT, handleLayout);
      }

      /**
       * Retrieve a shuffle item by its element.
       * @param {Element} element Element to look for.
       * @return {?ShuffleItem} A shuffle item or undefined if it's not found.
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
        var _this14 = this;
        // Remove refs to current items.
        this._disposeItems(this.items);
        this.isInitialized = false;

        // Find new items in the DOM.
        this.items = this._getItems();

        // Set initial styles on the new items.
        this._initItems(this.items);
        this.once(Shuffle.EventType.LAYOUT, function () {
          // Add transition to each item.
          _this14.setItemTransitions(_this14.items);
          _this14.isInitialized = true;
        });

        // Lay out all items.
        this.filter(this.lastFilter);
      }

      /**
       * Destroys shuffle, removes events, styles, and classes
       */
    }, {
      key: "destroy",
      value: function destroy() {
        this._cancelMovement();
        if (this._resizeObserver) {
          this._resizeObserver.unobserve(this.element);
          this._resizeObserver = null;
        }

        // Reset container styles
        this.element.classList.remove('shuffle');
        this.element.removeAttribute('style');

        // Reset individual item styles
        this._disposeItems(this.items);
        this.items.length = 0;
        this.sortedItems.length = 0;
        this._transitions.length = 0;

        // Null DOM references
        this.options.sizer = null;
        this.element = null;

        // Set a flag so if a debounced resize has been triggered,
        // it can first check if it is actually isDestroyed and not doing anything
        this.isDestroyed = true;
        this.isEnabled = false;
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
       * @param {Element} element The element.
       * @param {boolean} [includeMargins=false] Whether to include margins.
       * @return {{width: number, height: number}} The width and height.
       */
    }], [{
      key: "getSize",
      value: function getSize(element) {
        var includeMargins = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
        // Store the styles so that they can be used by others without asking for it again.
        var styles = window.getComputedStyle(element, null);
        var width = getNumberStyle(element, 'width', styles);
        var height = getNumberStyle(element, 'height', styles);
        if (includeMargins) {
          var marginLeft = getNumberStyle(element, 'marginLeft', styles);
          var marginRight = getNumberStyle(element, 'marginRight', styles);
          var marginTop = getNumberStyle(element, 'marginTop', styles);
          var marginBottom = getNumberStyle(element, 'marginBottom', styles);
          width += marginLeft + marginRight;
          height += marginTop + marginBottom;
        }
        return {
          width: width,
          height: height
        };
      }

      /**
       * Change a property or execute a function which will not have a transition
       * @param {Element[]} elements DOM elements that won't be transitioned.
       * @param {function} callback A function which will be called while transition
       *     is set to 0ms.
       * @private
       */
    }, {
      key: "_skipTransitions",
      value: function _skipTransitions(elements, callback) {
        var zero = '0ms';

        // Save current duration and delay.
        var data = elements.map(function (element) {
          var style = element.style;
          var duration = style.transitionDuration;
          var delay = style.transitionDelay;

          // Set the duration to zero so it happens immediately
          style.transitionDuration = zero;
          style.transitionDelay = zero;
          return {
            duration: duration,
            delay: delay
          };
        });
        callback();

        // Cause forced synchronous layout.
        elements[0].offsetWidth; // eslint-disable-line no-unused-expressions

        // Put the duration back
        elements.forEach(function (element, i) {
          element.style.transitionDuration = data[i].duration;
          element.style.transitionDelay = data[i].delay;
        });
      }
    }]);
  }(tinyEmitterExports);
  Shuffle.ShuffleItem = ShuffleItem$1;
  Shuffle.ALL_ITEMS = 'all';
  Shuffle.FILTER_ATTRIBUTE_KEY = 'groups';

  /** @enum {string} */
  Shuffle.EventType = {
    LAYOUT: 'shuffle:layout',
    REMOVED: 'shuffle:removed'
  };

  /** @enum {string} */
  Shuffle.Classes = Classes;

  /** @enum {string} */
  Shuffle.FilterMode = {
    ANY: 'any',
    ALL: 'all'
  };

  // Overridable options
  Shuffle.options = {
    // Initial filter group.
    group: Shuffle.ALL_ITEMS,
    // Transition/animation speed (milliseconds).
    speed: 250,
    // CSS easing function to use.
    easing: 'cubic-bezier(0.4, 0.0, 0.2, 1)',
    // e.g. '.picture-item'.
    itemSelector: '*',
    // Element or selector string. Use an element to determine the size of columns
    // and gutters.
    sizer: null,
    // A static number or function that tells the plugin how wide the gutters
    // between columns are (in pixels).
    gutterWidth: 0,
    // A static number or function that returns a number which tells the plugin
    // how wide the columns are (in pixels).
    columnWidth: 0,
    // If your group is not json, and is comma delimited, you could set delimiter
    // to ','.
    delimiter: null,
    // Useful for percentage based heights when they might not always be exactly
    // the same (in pixels).
    buffer: 0,
    // Reading the width of elements isn't precise enough and can cause columns to
    // jump between values.
    columnThreshold: 0.01,
    // Shuffle can be initialized with a sort object. It is the same object
    // given to the sort method.
    initialSort: null,
    // Transition delay offset for each item in milliseconds.
    staggerAmount: 15,
    // Maximum stagger delay in milliseconds.
    staggerAmountMax: 150,
    // Whether to use transforms or absolute positioning.
    useTransforms: true,
    // Affects using an array with filter. e.g. `filter(['one', 'two'])`. With "any",
    // the element passes the test if any of its groups are in the array. With "all",
    // the element only passes if all groups are in the array.
    // Note, this has no effect if you supply a custom filter function.
    filterMode: Shuffle.FilterMode.ANY,
    // Attempt to center grid items in each row.
    isCentered: false,
    // Attempt to align grid items to right.
    isRTL: false,
    // Whether to round pixel values used in translate(x, y). This usually avoids
    // blurriness.
    roundTransforms: true
  };
  Shuffle.Point = Point$1;
  Shuffle.Rect = Rect;

  // Expose for testing. Hack at your own risk.
  Shuffle.__sorter = sorter;
  Shuffle.__getColumnSpan = getColumnSpan;
  Shuffle.__getAvailablePositions = getAvailablePositions;
  Shuffle.__getShortColumn = getShortColumn;
  Shuffle.__getCenteredPositions = getCenteredPositions;
  return Shuffle;
});
//# sourceMappingURL=vendor.js.map
