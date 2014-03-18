(function ()
{
	"use strict";

	function Editor ($routeParams, REST) {
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Discount/Coupon/editor.twig',
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl)
			{
				scope.onLoad = function(){
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

				editorCtrl.init('Rbs_Discount_Coupon');
			}
		}
	}

	Editor.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsDiscountCoupon', Editor);
})();