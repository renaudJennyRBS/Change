(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	app.directive('rbsPhysicalDimensionsInput', [
		rbsPhysicalDimensionsInputDirective
	]);

	function rbsPhysicalDimensionsInputDirective () {

		return {
			restrict : 'E',
			templateUrl : 'Rbs/Stock/js/physical-dimensions.twig',
			require: 'ng-model',
			replace: 'true',
			scope: {},
			// Create isolated scope

			link : function (scope, elm, attrs, ngModel) {
				scope.massUnit = 'm';
				scope.lengthUnit = '';
				scope.data = {
					mass:{value:null, unit:scope.massUnit},
					length:{value:null, unit:scope.lengthUnit},

				};
				scope.$watch('ngModel.$modelValue',function(newValue){
					console.log(ngModel);
				});


			}
		}
	};
})(window.jQuery);
