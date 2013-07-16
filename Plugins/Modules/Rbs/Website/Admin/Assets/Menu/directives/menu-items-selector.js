(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsWebsiteMenuItemsSelector', [ function () {

		return {
			restrict : 'EC',
			templateUrl : 'Rbs/Website/Menu/directives/menu-items-selector.twig',
			require : 'ngModel',

			link: function (scope, elm, attrs, ngModel) {

				ngModel.$render = function () {
					scope.items = ngModel.$viewValue;
				};

			}
		};
	}]);
})();