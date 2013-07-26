(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsModelSelector', ['RbsChange.Models', function (Models) {
		return {
			restrict : 'E',
			template : '<select ng-model="model" ng-options="m.label group by m.plugin for m in models | filter:filter | orderBy:[\'plugin\',\'label\']"></select>',
			replace  : true,

			scope : {
				model : '=',
				filter : '@'
			},

			link : function (scope, elm, attrs) {
				scope.models = Models.getAll();
				scope.filter = {};
				attrs.$observe('filter', function (value) {
					scope.filter = scope.$eval(attrs.filter);
				});
			}

		};
	}]);

})();