(function ()
{
	"use strict";

	function Editor ($routeParams, REST) {
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Discount/Discount/editor.twig',
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
				};

				scope.onReady = function(){
					if (angular.isArray(scope.document.cartFilterData) || !angular.isObject(scope.document.cartFilterData)) {
						scope.document.cartFilterData = {};
					}
				};

				editorCtrl.init('Rbs_Discount_Discount');
			}
		}
	}

	Editor.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsDiscountDiscount', Editor);
})();