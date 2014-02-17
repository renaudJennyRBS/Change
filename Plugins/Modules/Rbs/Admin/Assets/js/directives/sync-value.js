(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsSyncValue', ['$parse', '$compile', function ($parse, $compile)
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

				var	source = attrs.rbsSyncValue, originalValue,
					targetSetterFn = $parse(attrs.ngModel).assign,
					unwatchSourceFn;

				scope.enabled = false;

				ngModel.$formatters.unshift(function (value) {
					scope.enabled = (value == scope.$eval(attrs.rbsSyncValue));
					return value;
				});

				$compile('<button class="btn sync-value-button" type="button" ng-click="enabled = ! enabled" ng-class="{\'btn-success\': enabled}"><span ng-if="enabled">=</span><span ng-if="! enabled">&ne;</span></button>')(scope, function (clone) {
					clone.attr('title', attrs.syncTitle);
					elm.before(clone);
				});
				elm.css({'padding-left': '42px'});
				elm.parent().css({'position': 'relative'});


				scope.$watch('enabled', function (enabled)
				{
					if (angular.isDefined(enabled))
					{
						if (unwatchSourceFn) {
							unwatchSourceFn();
						}
						if (enabled) {
							originalValue = angular.copy(ngModel.$viewValue);
							elm.attr('readonly', 'readonly');
							unwatchSourceFn = scope.$watch(source, function (value) {
								setValue(value);
							});
							setValue(scope.$eval(attrs.rbsSyncValue));
						} else {
							elm.removeAttr('readonly');
							if (originalValue) {
								targetSetterFn(scope, originalValue);
								elm.focus();
							}
						}
					}
				}, true);

				function setValue(value)
				{
					ngModel.$setViewValue(value);
					ngModel.$render();
				}
			}
		};
	}]);

})();