(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsModelSelector', ['RbsChange.Models', 'RbsChange.ArrayUtils', function (Models, ArrayUtils) {
		return {
			restrict : 'E',
			template : '<select ng-model="model" ng-options="m.label group by m.plugin for m in models | filter:filter:comparator | orderBy:[\'plugin\',\'label\']"></select>',
			replace  : true,

			scope : {
				model : '=',
				filter : '@'
			},

			link : function (scope, elm, attrs) {
				scope.models = Models.getAll();
				scope.filter = {};
				scope.comparator = function (expected, actual)
				{
					if (angular.isArray(actual))
					{
						return ArrayUtils.inArray(expected, actual) != -1;
					}
					else if (actual === null)
					{
						return true;
					}
					return expected == actual;
				};
				attrs.$observe('filter', function (value) {
					scope.filter = scope.$eval(attrs.filter);
				});
			}

		};
	}]);

})();