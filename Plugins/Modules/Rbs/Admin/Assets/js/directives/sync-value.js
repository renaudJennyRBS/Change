(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsSyncValue', ['$parse', '$compile', '$timeout', function ($parse, $compile, $timeout) {

		return {
			restrict : 'A',
			scope    : true,
			require  : 'ngModel',
			// Lower priority so that the <rbs-field-*/> Directives can compile before this one.
			priority : -1000,

			link : function linkFn (scope, elm, attrs, ngModel) {
				var	source = attrs.rbsSyncValue,
					originalValue,
					targetSetterFn = $parse(attrs.ngModel).assign,
					unwatchSourceFn,
					enabled = false;

				$compile('<button class="btn sync-value-button" type="button" ng-click="toggle()" ng-class="{\'btn-success\': isEnabled()}"><span ng-if="isEnabled()">=</span><span ng-if="!isEnabled()">&ne;</span></button>')(scope, function (clone) {
					clone.attr('title', attrs.syncTitle);
					elm.before(clone);
				});
				elm.css({'padding-left': '34px'});
				elm.parent().css({'position': 'relative'});

				scope.isEnabled = function () {
					return enabled;
				};


				function update () {
					if (enabled) {
						originalValue = angular.copy(ngModel.$viewValue);
						elm.attr('readonly', 'readonly');
						unwatchSourceFn = scope.$watch(source, function (value) {
							ngModel.$setViewValue(value);
							ngModel.$render();
						});
						ngModel.$setViewValue(scope.$eval(attrs.rbsSyncValue));
						ngModel.$render();
					} else {
						elm.removeAttr('readonly');
						if (unwatchSourceFn) {
							unwatchSourceFn();
						}
						targetSetterFn(scope, originalValue);
						elm.focus();
					}
				}

				scope.toggle = function () {
					enabled = ! enabled;
					update();
				};

				$timeout(function () {
					originalValue = angular.copy(ngModel.$viewValue);
					enabled = ! ngModel.$viewValue || angular.equals(ngModel.$viewValue, scope.$eval(attrs.rbsSyncValue));
					update();
				});

			}
		};
	}]);

})();