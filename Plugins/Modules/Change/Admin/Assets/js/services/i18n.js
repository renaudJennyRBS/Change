(function () {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * Localization filter.
	 */
	app.filter('i18n', function () {

		return function i18nFilterFn (string, params) {
			// Guess path and key from the full locale key.
			var	p = string.lastIndexOf('.'),
				path = string.substring(0, p),
				key = string.substr(p+1);

			// Search for the key in the global object (comes from "Change/Admin/i18n.js").
			if (__change.i18n[path] && __change.i18n[path][key]) {
				string = __change.i18n[path][key];
				// Replace parameters (if any).
				angular.forEach(params, function (value, key) {
					string = string.replace(new RegExp('\\{' + key + '\\}', 'g'), value);
				});
			}

			return string;
		};

	});


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