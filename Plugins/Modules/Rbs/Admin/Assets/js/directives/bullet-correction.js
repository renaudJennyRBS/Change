(function () {
	"use strict";

	/**
	 * @name rbsBulletCorrection
	 * @description Show an icon if there is a correction on the document.
	 * @example <code><rbs-bullet-correction ng-model="document" /></code>
	 */
	angular.module('RbsChange').directive('rbsBulletCorrection', [function ()
	{
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Admin/js/directives/bullet-correction.twig',
			require: '?ngModel',
			replace: true,
			scope : {
				document : '=ngModel'
			}
		};
	}]);
})();