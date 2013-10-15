(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsSyncValue', ['$parse', '$compile', '$timeout', function ($parse, $compile, $timeout)
	{
		return {
			restrict : 'A',
			scope    : true,
			require  : '?ngModel',
			// Lower priority so that the <rbs-field-*/> Directives can compile before this one.
			priority : -1000,

			link : function linkFn (scope, elm, attrs, ngModel)
			{
				if (!ngModel) return;

				var	source = attrs.rbsSyncValue,
					originalValue,
					targetSetterFn = $parse(attrs.ngModel).assign,
					unwatchSourceFn,
					renderFn = ngModel.$render;

				scope.enabled = false;

				$compile('<button class="btn sync-value-button" type="button" ng-click="enabled = ! enabled" ng-class="{\'btn-success\': enabled}"><span ng-if="enabled">=</span><span ng-if="! enabled">&ne;</span></button>')(scope, function (clone) {
					clone.attr('title', attrs.syncTitle);
					elm.before(clone);
				});
				elm.css({'padding-left': '42px'});
				elm.parent().css({'position': 'relative'});


				scope.$watch('enabled', function (enabled, old)
				{
					if (angular.isDefined(enabled) && enabled !== old)
					{
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
				}, true);

				var initialValueSet = false;

				ngModel.$render = function ()
				{
					if (angular.isDefined(ngModel.$viewValue) && ! initialValueSet) {
						scope.enabled = ! ngModel.$viewValue || angular.equals(ngModel.$viewValue, scope.$eval(attrs.rbsSyncValue));
						initialValueSet = true;
					}
					renderFn();
				};

/*
				$timeout(function () {
					originalValue = angular.copy(ngModel.$viewValue);
					console.log("originalValue=", originalValue);
					enabled = ! ngModel.$viewValue || angular.equals(ngModel.$viewValue, scope.$eval(attrs.rbsSyncValue));
					update();
				});
*/
			}
		};
	}]);

})();