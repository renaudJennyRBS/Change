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


	/**
	 * The following directive should be placed on an input field to validate that its value is a valid locale name.
	 */
	app.directive('localeId', ['RbsChange.Utils', function (Utils) {

		return {

			require : 'ngModel',

			link : function localeIdLinkFn (scope, elm, attrs, ctrl) {
				ctrl.$parsers.unshift(function (viewValue) {
					if (Utils.isValidLCID(viewValue)) {
						ctrl.$setValidity('locale', true);
					} else {
						ctrl.$setValidity('locale', false);
					}
					return viewValue;
				});

				ctrl.$formatters.push(function (value) {
					if (ctrl.$valid) {
						if (value.length === 2) {
							return angular.lowercase(value);
						} else if (value.length === 5) {
							return angular.lowercase(value.substring(0, 2)) + '_' + angular.uppercase(value.substring(3, 5));
						}
					}
					return value;
				});

				elm.bind('blur', function () {
					var viewValue = ctrl.$modelValue, i;
					for (i in ctrl.$formatters) {
						if (ctrl.$formatters.hasOwnProperty(i)) {
							viewValue = ctrl.$formatters[i](viewValue);
						}
					}
					ctrl.$viewValue = viewValue;
					ctrl.$render();
				});
			}
		};
	}]);

})();