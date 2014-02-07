(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsBreadcrumb', ['RbsChange.Breadcrumb', '$location', function (Breadcrumb, $location) {
		return {
			restrict : 'E',
			scope : true,
			templateUrl : 'Rbs/Admin/js/directives/breadcrumb.twig',
			replace : true,
			'link' : function (scope, element, attrs) {
				scope.home =  scope.entries =  scope.current = null;

				scope.$on('Change:BreadcrumbUpdated', function() {
					scope.home = Breadcrumb.homeEntry();
					scope.entries = Breadcrumb.pathEntries();
					scope.current = Breadcrumb.currentEntry();
				})
			}
		}
	}]);
})();