(function () {

	var app = angular.module('RbsChange');

	app.provider('RbsChange.i18n', ['RbsChange.i18nStringsProvider', 'RbsChange.LocalesProvider', function RbsChangeI18NProvider(strings, locales) {
		this.$get = function I18NService () {

			return {
				'translate' : function (key) {
					if (strings.hasOwnProperty(key)) {
						return strings[key];
					} else {
						//$log.warn("RbsChange.i18n: Localized string for '" + key + "' not found.");
						return key;
					}
				},

				'getLocaleNameFromCode' : function (code) {
					if (!code) {
						return undefined;
					}
					return locales[code] || code;
				},

				'isValidLCID' : function (lcid) {
					return angular.isString(lcid) && (/^[a-z]{2}(_[a-zA-Z]{2})?$/).test(lcid);
				}
			};
		};
	}]);


	app.directive('i18n', ['RbsChange.i18n', function (I18N) {
		return {
			priority: 0,
			restrict: 'A',
			scope: false,
			compile: function compile (tElement, tAttrs, transclude) {
				if (tAttrs.i18n) {
					tElement.html(I18N.translate(tAttrs.i18n));
				}
				return {
					pre: function preLink (scope, iElement, iAttrs, controller) {},
					post: function postLink (scope, iElement, iAttrs, controller) {}
				};
			}
		};
	}]);


	app.directive('i18nAttr', ['RbsChange.i18n',
		function (I18N) {
			return {
				priority : 0,
				restrict : 'A',
				scope : false,
				compile : function compile (tElement, tAttrs, transclude) {
					var value;
					if (tAttrs.i18nAttr) {
						value = tAttrs.i18nAttr;
						var p = value.indexOf('=');
						tAttrs.$set(
								value.substring(0, p).trim(),
								I18N.translate(value.substring(p + 1).trim())
							);
					}
					return {
						pre : function preLink (scope, iElement, iAttrs, controller) {
						},
						post : function postLink (scope, iElement, iAttrs, controller) {
						}
					};
				}
			};
		}
	]);


	app.directive('i18nTitle', ['RbsChange.i18n', function (I18N) {
		return {
			priority: 0,
			restrict: 'A',
			scope: false,
			compile: function compile (tElement, tAttrs, transclude) {
				if (tAttrs.i18nTitle) {
					tAttrs.$set('title', I18N.translate(tAttrs.i18nTitle));
				}
				return {
					pre: function preLink (scope, iElement, iAttrs, controller) {},
					post: function postLink (scope, iElement, iAttrs, controller) {}
				};
			}
		};
	}]);


	app.directive('localeId', ['RbsChange.i18n', function (i18n) {

		return {

			require : 'ngModel',

			link : function (scope, elm, attrs, ctrl) {
				ctrl.$parsers.unshift(function (viewValue) {
					if (i18n.isValidLCID(viewValue)) {
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
					var viewValue = ctrl.$modelValue;
					for (var i in ctrl.$formatters) {
						viewValue = ctrl.$formatters[i](viewValue);
					}
					ctrl.$viewValue = viewValue;
					ctrl.$render();
				});

			}
		};
	}]);


})();