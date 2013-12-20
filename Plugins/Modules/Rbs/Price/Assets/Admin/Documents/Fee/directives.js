(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsFilterNode', rbsFilterNode);

	function rbsFilterNode () {
		return {
			restrict : 'A',
			priority : 10,
			scope: true,
			link : function (scope, elm, attrs) {
				scope.name = null;
				scope.parameters = {};
				console.log('rbsFilterNode', scope)
			}
		}
	}
	app.directive('rbsFiltersGroup', rbsFiltersGroup);

	function rbsFiltersGroup () {
		return {
			restrict : 'A',
			template : '<div></div>',
			priority : 100,
			scope: true,
			link : function (scope, elm, attrs) {
				scope.filters = [];
				console.log('rbsFiltersGroup', scope)
			}
		}
	}

})(window.jQuery);