(function() {
	"use strict";

	function rbsOrderOrderEditorLineEditor($q, REST, $http) {
		return {
			restrict: 'E',
			templateUrl: 'Document/Rbs/Order/Order/lineEditor.twig',
			require: 'ngModel',
			scope: {
				'priceInfo': "="
			},

			link: function(scope, element, attrs, ngModel) {
				scope.line = {};

				ngModel.$render = function ngModelRenderFn() {
					scope.line = ngModel.$viewValue;
					var price = scope.line.items[0].price;
					if (!angular.isObject(price.taxCategories)) {
						price.taxCategories = {};
					}
				};
			}
		};
	}

	rbsOrderOrderEditorLineEditor.$inject = [ '$q', 'RbsChange.REST', '$http' ];
	angular.module('RbsChange').directive('rbsOrderLineEditor', rbsOrderOrderEditorLineEditor);
})();