(function() {
	"use strict";

	function Editor($routeParams, REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.onLoad = function() {
					if (scope.document.isNew() && $routeParams.hasOwnProperty('orderProcessId') && !scope.document.orderProcess) {
						REST.resource('Rbs_Commerce_Process', $routeParams['orderProcessId']).then(function(process) {
							scope.document.orderProcess = process;
							scope.document.orderProcessId = process.id;
						})
					}

					if (!angular.isObject(scope.document.cartFilterData) || angular.isArray(scope.document.cartFilterData)) {
						scope.document.cartFilterData = {};
					}
				};
			}
		}
	}

	Editor.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsDiscountCouponNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsDiscountCouponEdit', Editor);
})();