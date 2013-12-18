(function () {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * Localization service.
	 */
	app.service('RbsChange.i18n', ['$filter', function i18nServiceFn ($filter) {

		this.trans = function (string, params) {
			var p, path, key, filters = null;

			p = string.indexOf('|');
			if (p !== -1) {
				filters = string.substr(p+1).trim().split('|');
				string = string.substring(0, p).trim();
			}

			p = string.lastIndexOf('.');
			path = string.substring(0, p);
			key = string.substr(p + 1);

			// Search for the key in the global object (comes from "Change/Admin/i18n.js").
			if (__change.i18n[path] && __change.i18n[path][key]) {
				string = __change.i18n[path][key];
				// Replace parameters (if any).
				angular.forEach(params, function (value, key) {
					string = string.replace(new RegExp('\\$' + key + '\\$', 'gi'), value);
				});
			}

			if (filters) {
				angular.forEach(filters, function (filterName) {
					string = $filter(filterName.trim())(string);
				});
			}

			return string;
		};
	}]);


	/**
	 * Localization filter.
	 */
	app.filter('i18n', ['RbsChange.i18n', function (i18n) {

		return function i18nFilterFn (string, params) {
			return i18n.trans(string, params);
		};

	}]);


	/**
	 * Uppercase first letter.
	 */
	app.filter('ucf', function () {

		return function ucfFilterFn (input) {
			if (input && input.length > 0) {
				return angular.uppercase(input.substr(0, 1)) + input.substr(1);
			}
			return input;
		};

	});


	/**
	 * Ellipsis letter.
	 */
	app.filter('etc', function () {

		return function etcFilterFn (input) {
			return input + '…';
		};

	});


	/**
	 * Label.
	 */
	app.filter('lbl', function () {

		return function lblFilterFn (input) {
			// FIXME Remove space before ':' according to current language.
			return input + ' :';
		};

	});

})();