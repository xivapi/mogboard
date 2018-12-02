var mogboard =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/ui/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/App.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/App.js":
/*!**************************!*\
  !*** ./assets/js/App.js ***!
  \**************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Polyfills__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Polyfills */ \"./assets/js/Polyfills.js\");\n/* harmony import */ var _Polyfills__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_Polyfills__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _Server__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Server */ \"./assets/js/Server.js\");\n/* harmony import */ var _HeaderCategories__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./HeaderCategories */ \"./assets/js/HeaderCategories.js\");\n/* harmony import */ var _HeaderCategoriesSelection__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./HeaderCategoriesSelection */ \"./assets/js/HeaderCategoriesSelection.js\");\n/* harmony import */ var _Search__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Search */ \"./assets/js/Search.js\");\n/* harmony import */ var _ProductPricing__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ProductPricing */ \"./assets/js/ProductPricing.js\");\n/* harmony import */ var _ProductHistory__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./ProductHistory */ \"./assets/js/ProductHistory.js\");\n\n\n_Server__WEBPACK_IMPORTED_MODULE_1__[\"default\"].init();\n\n_HeaderCategories__WEBPACK_IMPORTED_MODULE_2__[\"default\"].watch();\n\n_HeaderCategoriesSelection__WEBPACK_IMPORTED_MODULE_3__[\"default\"].watch();\n\n_Search__WEBPACK_IMPORTED_MODULE_4__[\"default\"].watch();\n\n\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  ProductPricing: _ProductPricing__WEBPACK_IMPORTED_MODULE_5__[\"default\"],\n  ProductHistory: _ProductHistory__WEBPACK_IMPORTED_MODULE_6__[\"default\"]\n});\n\n//# sourceURL=webpack://mogboard/./assets/js/App.js?");

/***/ }),

/***/ "./assets/js/HeaderCategories.js":
/*!***************************************!*\
  !*** ./assets/js/HeaderCategories.js ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\nvar HeaderCategories =\n/*#__PURE__*/\nfunction () {\n  function HeaderCategories() {\n    _classCallCheck(this, HeaderCategories);\n\n    this.uiCurrentOpenCatList = null;\n    this.uiButtons = $('.search .search-bar');\n    this.uiCatList = $('.search .categories');\n  }\n\n  _createClass(HeaderCategories, [{\n    key: \"watch\",\n    value: function watch() {\n      var _this = this;\n\n      this.uiButtons.on('click', 'button', function (event) {\n        var id = $(event.currentTarget).attr('id');\n\n        _this.toggleDropdownMenu(id);\n      });\n      $(document).mouseup(function (event) {\n        var buttons = _this.uiButtons.find('button');\n\n        var catlist = _this.uiCatList.find('.open'); // if the target of the click isn't the container nor a descendant of the container\n\n\n        if (!buttons.is(event.target) && buttons.has(event.target).length === 0 && !catlist.is(event.target) && catlist.has(event.target).length === 0) {\n          _this.uiButtons.find('.active').removeClass('active');\n\n          _this.uiCatList.find('.open').removeClass('open');\n\n          _this.uiCurrentOpenCatList = null;\n        }\n      });\n    }\n  }, {\n    key: \"toggleDropdownMenu\",\n    value: function toggleDropdownMenu(id) {\n      this.hideDropdownMenus(); // set states\n\n      if (this.uiCurrentOpenCatList !== id) {\n        this.uiButtons.find(\"#\".concat(id)).addClass('active');\n        this.uiCatList.find(\".\".concat(id)).addClass('open');\n        this.uiCurrentOpenCatList = id;\n      } else {\n        this.uiCurrentOpenCatList = null;\n      }\n    }\n  }, {\n    key: \"hideDropdownMenus\",\n    value: function hideDropdownMenus() {\n      // remove any previous states\n      this.uiButtons.find('.active').removeClass('active');\n      this.uiCatList.find('.open').removeClass('open');\n      this.uiCurrentOpenCatList = null;\n    }\n  }]);\n\n  return HeaderCategories;\n}();\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (new HeaderCategories());\n\n//# sourceURL=webpack://mogboard/./assets/js/HeaderCategories.js?");

/***/ }),

/***/ "./assets/js/HeaderCategoriesSelection.js":
/*!************************************************!*\
  !*** ./assets/js/HeaderCategoriesSelection.js ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _HeaderCategories__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./HeaderCategories */ \"./assets/js/HeaderCategories.js\");\n/* harmony import */ var _Http__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Http */ \"./assets/js/Http.js\");\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\n\n\n\nvar HeaderCategoriesSelection =\n/*#__PURE__*/\nfunction () {\n  function HeaderCategoriesSelection() {\n    _classCallCheck(this, HeaderCategoriesSelection);\n\n    this.uiView = $('.search-ui');\n    this.uiCatList = $('.search .categories');\n  }\n\n  _createClass(HeaderCategoriesSelection, [{\n    key: \"watch\",\n    value: function watch() {\n      var _this = this;\n\n      this.uiCatList.find('button').on('click', function (event) {\n        // hide any dropdowns\n        _HeaderCategories__WEBPACK_IMPORTED_MODULE_0__[\"default\"].hideDropdownMenus(); // load category\n\n        var catId = $(event.currentTarget).attr('id');\n        _Http__WEBPACK_IMPORTED_MODULE_1__[\"default\"].getItemCategoryList(catId, function (response) {\n          console.log(response);\n\n          _this.uiView.html(response);\n        });\n      });\n    }\n  }]);\n\n  return HeaderCategoriesSelection;\n}();\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (new HeaderCategoriesSelection());\n\n//# sourceURL=webpack://mogboard/./assets/js/HeaderCategoriesSelection.js?");

/***/ }),

/***/ "./assets/js/Http.js":
/*!***************************!*\
  !*** ./assets/js/Http.js ***!
  \***************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\nvar Http =\n/*#__PURE__*/\nfunction () {\n  function Http() {\n    _classCallCheck(this, Http);\n  }\n\n  _createClass(Http, [{\n    key: \"getItemCategoryList\",\n\n    /**\r\n     * Get the results from an item category list for a specific id\r\n     *\r\n     * @param id int\r\n     * @param callback function\r\n     */\n    value: function getItemCategoryList(id, callback) {\n      var url = app.url_item_category_list.replace('-id-', id);\n      fetch(url, {\n        mode: 'cors'\n      }).then(function (response) {\n        return response.text();\n      }).then(callback);\n    }\n    /**\r\n     * Get prices for an item\r\n     *\r\n     * @param server\r\n     * @param itemId\r\n     * @param callback\r\n     */\n\n  }, {\n    key: \"getItemPrices\",\n    value: function getItemPrices(server, itemId, callback) {\n      var url = app.url_product_price.replace('-server-', server).replace('-id-', itemId);\n      fetch(url, {\n        mode: 'cors'\n      }).then(function (response) {\n        return response.text();\n      }).then(callback);\n    }\n    /**\r\n     * Get price history of an item\r\n     *\r\n     * @param server\r\n     * @param itemId\r\n     * @param callback\r\n     */\n\n  }, {\n    key: \"getItemHistory\",\n    value: function getItemHistory(server, itemId, callback) {\n      var url = app.url_product_history.replace('-server-', server).replace('-id-', itemId);\n      fetch(url, {\n        mode: 'cors'\n      }).then(function (response) {\n        return response.text();\n      }).then(callback);\n    }\n  }]);\n\n  return Http;\n}();\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (new Http());\n\n//# sourceURL=webpack://mogboard/./assets/js/Http.js?");

/***/ }),

/***/ "./assets/js/Polyfills.js":
/*!********************************!*\
  !*** ./assets/js/Polyfills.js ***!
  \********************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("if (!Object.prototype.forEach) {\n  Object.defineProperty(Object.prototype, 'forEach', {\n    value: function value(callback, thisArg) {\n      if (this === null) {\n        throw new TypeError('Not an object');\n      }\n\n      thisArg = thisArg || window;\n\n      for (var key in this) {\n        if (this.hasOwnProperty(key)) {\n          callback.call(thisArg, this[key], key, this);\n        }\n      }\n    }\n  });\n}\n\n//# sourceURL=webpack://mogboard/./assets/js/Polyfills.js?");

/***/ }),

/***/ "./assets/js/ProductHistory.js":
/*!*************************************!*\
  !*** ./assets/js/ProductHistory.js ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Server__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Server */ \"./assets/js/Server.js\");\n/* harmony import */ var _Http__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Http */ \"./assets/js/Http.js\");\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\n\n\n\nvar ProductHistory =\n/*#__PURE__*/\nfunction () {\n  function ProductHistory() {\n    _classCallCheck(this, ProductHistory);\n\n    this.ui = null;\n  }\n\n  _createClass(ProductHistory, [{\n    key: \"setUi\",\n    value: function setUi(className) {\n      this.ui = $(className);\n      return this;\n    }\n  }, {\n    key: \"fetch\",\n    value: function fetch(itemId, callback) {\n      var _this = this;\n\n      this.ui.html('Loading item history ...');\n      var server = _Server__WEBPACK_IMPORTED_MODULE_0__[\"default\"].getServer();\n      _Http__WEBPACK_IMPORTED_MODULE_1__[\"default\"].getItemHistory(server, itemId, function (response) {\n        _this.ui.html(response);\n\n        callback();\n      });\n    }\n  }]);\n\n  return ProductHistory;\n}();\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (new ProductHistory());\n\n//# sourceURL=webpack://mogboard/./assets/js/ProductHistory.js?");

/***/ }),

/***/ "./assets/js/ProductPricing.js":
/*!*************************************!*\
  !*** ./assets/js/ProductPricing.js ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Server__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Server */ \"./assets/js/Server.js\");\n/* harmony import */ var _Http__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Http */ \"./assets/js/Http.js\");\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\n\n\n\nvar ProductPricing =\n/*#__PURE__*/\nfunction () {\n  function ProductPricing() {\n    _classCallCheck(this, ProductPricing);\n\n    this.ui = null;\n  }\n\n  _createClass(ProductPricing, [{\n    key: \"setUi\",\n    value: function setUi(className) {\n      this.ui = $(className);\n      return this;\n    }\n  }, {\n    key: \"fetch\",\n    value: function fetch(itemId, callback) {\n      var _this = this;\n\n      this.ui.html('Loading item prices ...');\n      var server = _Server__WEBPACK_IMPORTED_MODULE_0__[\"default\"].getServer();\n      _Http__WEBPACK_IMPORTED_MODULE_1__[\"default\"].getItemPrices(server, itemId, function (response) {\n        _this.ui.html(response);\n\n        callback();\n      });\n    }\n  }]);\n\n  return ProductPricing;\n}();\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (new ProductPricing());\n\n//# sourceURL=webpack://mogboard/./assets/js/ProductPricing.js?");

/***/ }),

/***/ "./assets/js/Search.js":
/*!*****************************!*\
  !*** ./assets/js/Search.js ***!
  \*****************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _XIVAPI__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./XIVAPI */ \"./assets/js/XIVAPI.js\");\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\n\n\nvar Search =\n/*#__PURE__*/\nfunction () {\n  function Search() {\n    _classCallCheck(this, Search);\n\n    this.input = $('.search-bar input');\n    this.ui = $('.search-ui');\n    this.timeout = null;\n    this.timeoutDelay = 200;\n    this.searchTerm = null;\n  }\n\n  _createClass(Search, [{\n    key: \"watch\",\n    value: function watch() {\n      var _this = this;\n\n      this.input.on('keyup', function (event) {\n        clearTimeout(_this.timeout);\n        var searchTerm = $(event.currentTarget).val().trim();\n\n        if (_this.searchTerm === searchTerm || searchTerm.length < 2) {\n          _this.searchTerm = searchTerm;\n          return;\n        }\n\n        _this.searchTerm = searchTerm; // perform search\n\n        _this.timeout = setTimeout(function () {\n          _XIVAPI__WEBPACK_IMPORTED_MODULE_0__[\"default\"].search(searchTerm, function (response) {\n            _this.render(response);\n          });\n        }, _this.timeoutDelay);\n      });\n    }\n  }, {\n    key: \"render\",\n    value: function render(response) {\n      var results = []; // prep results\n\n      response.Results.forEach(function (item, i) {\n        var url = app.url_product.replace('-id-', item.ID);\n        results.push(\"<a href=\\\"\".concat(url, \"\\\" class=\\\"rarity-\").concat(item.Rarity, \"\\\">\\n                    <span><img src=\\\"https://xivapi.com\").concat(item.Icon, \"\\\"></span>\\n                    <span>\").concat(item.LevelItem, \"</span>\\n                    \").concat(item.Name, \"\\n                    <span>\").concat(item.ItemSearchCategory.Name, \"</span>\\n                </a>\"));\n      }); // render results\n\n      this.ui.html(\"\\n            <div class=\\\"item-search-list\\\">\\n                <h2>Found \".concat(response.Pagination.Results, \" / \").concat(response.Pagination.ResultsTotal, \" for <strong>\").concat(this.searchTerm, \"</strong></h2>\\n                \").concat(results.join(''), \"\\n            </div>\\n        \"));\n    }\n  }]);\n\n  return Search;\n}();\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (new Search());\n\n//# sourceURL=webpack://mogboard/./assets/js/Search.js?");

/***/ }),

/***/ "./assets/js/Server.js":
/*!*****************************!*\
  !*** ./assets/js/Server.js ***!
  \*****************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\nvar Server =\n/*#__PURE__*/\nfunction () {\n  function Server() {\n    _classCallCheck(this, Server);\n  }\n\n  _createClass(Server, [{\n    key: \"init\",\n    value: function init() {\n      var server = localStorage.getItem('server'); // default server if non exist\n\n      server = server ? server : 'Phoenix';\n      localStorage.setItem('server', server);\n    }\n  }, {\n    key: \"getServer\",\n    value: function getServer() {\n      return localStorage.getItem('server');\n    }\n  }, {\n    key: \"setServer\",\n    value: function setServer(server) {\n      localStorage.setItem('server', server);\n      this.init();\n    }\n  }]);\n\n  return Server;\n}();\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (new Server());\n\n//# sourceURL=webpack://mogboard/./assets/js/Server.js?");

/***/ }),

/***/ "./assets/js/XIVAPI.js":
/*!*****************************!*\
  !*** ./assets/js/XIVAPI.js ***!
  \*****************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\nvar XIVAPI =\n/*#__PURE__*/\nfunction () {\n  function XIVAPI() {\n    _classCallCheck(this, XIVAPI);\n  }\n\n  _createClass(XIVAPI, [{\n    key: \"get\",\n    value: function get(endpoint, queries, callback) {\n      queries = queries ? queries : {};\n      queries.key = app.xivapi_key;\n      queries.tags = 'mogboardv2';\n      queries.language = localStorage.getItem('language');\n      var query = Object.keys(queries).map(function (k) {\n        return encodeURIComponent(k) + '=' + encodeURIComponent(queries[k]);\n      }).join('&');\n      endpoint = endpoint + '?' + query;\n      fetch(\"https://xivapi.com\".concat(endpoint), {\n        mode: 'cors'\n      }).then(function (response) {\n        return response.json();\n      }).then(callback);\n    }\n    /**\r\n     * Search for an item\r\n     */\n\n  }, {\n    key: \"search\",\n    value: function search(string, callback) {\n      var params = {\n        indexes: 'item',\n        filters: 'ItemSearchCategory.ID>=1',\n        columns: 'ID,Icon,Name,LevelItem,Rarity,ItemSearchCategory.Name,ItemSearchCategory.ID,ItemKind.Name',\n        string: string.trim(),\n        limit: 100\n      };\n      this.get(\"/search\", params, callback);\n    }\n    /**\r\n     * Return information about an item\r\n     */\n\n  }, {\n    key: \"getItem\",\n    value: function getItem(itemId, callback) {\n      this.get(\"/Item/\".concat(itemId), {}, callback);\n    }\n    /**\r\n     * Get a list of servers grouped by their data center\r\n     */\n\n  }, {\n    key: \"getServerList\",\n    value: function getServerList(callback) {\n      this.get('/servers/dc', {}, callback);\n    }\n  }]);\n\n  return XIVAPI;\n}();\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (new XIVAPI());\n\n//# sourceURL=webpack://mogboard/./assets/js/XIVAPI.js?");

/***/ })

/******/ })["default"];