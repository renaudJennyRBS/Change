(function ()
{
	"use strict";

	function Editor ($routeParams, REST) {
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Payment/AtosSipsConnector/editor.twig',
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl)
			{
				scope.onLoad = function(){
					if (!angular.isObject(scope.document.cartFilterData) || angular.isArray(scope.document.cartFilterData)) {
						scope.document.cartFilterData = {};
					}
				};

				editorCtrl.init('Rbs_Payment_AtosSipsConnector');
			}
		}
	}

	Editor.$inject = ['$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsPaymentAtosSipsConnector', Editor);
})();