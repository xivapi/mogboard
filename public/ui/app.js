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
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _HeaderCategories__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./HeaderCategories */ \"./assets/js/HeaderCategories.js\");\n/* harmony import */ var _HeaderCategoriesSelection__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./HeaderCategoriesSelection */ \"./assets/js/HeaderCategoriesSelection.js\");\n\n_HeaderCategories__WEBPACK_IMPORTED_MODULE_0__[\"default\"].watch();\n\n_HeaderCategoriesSelection__WEBPACK_IMPORTED_MODULE_1__[\"default\"].watch();\n\n//# sourceURL=webpack://mogboard/./assets/js/App.js?");

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
eval("__webpack_require__.r(__webpack_exports__);\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }\n\nvar Http =\n/*#__PURE__*/\nfunction () {\n  function Http() {\n    _classCallCheck(this, Http);\n  }\n\n  _createClass(Http, [{\n    key: \"getItemCategoryList\",\n\n    /**\r\n     * Get the results from an item category list for a specific id\r\n     *\r\n     * @param id int\r\n     * @param callback function\r\n     */\n    value: function getItemCategoryList(id, callback) {\n      var url = app.url_item_category_list.replace('-id-', id);\n      fetch(url, {\n        mode: 'cors'\n      }).then(function (response) {\n        return response.text();\n      }).then(callback);\n    }\n  }]);\n\n  return Http;\n}();\n\n/* harmony default export */ __webpack_exports__[\"default\"] = (new Http());\n\n//# sourceURL=webpack://mogboard/./assets/js/Http.js?");

/***/ })

/******/ })["default"];