(function ()
{
	"use strict";

	function Editor ()
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Price/Fee/editor.twig',
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl)
			{
				scope.onLoad = function(){

				};

				scope.onReady = function(){
					if (angular.isArray(scope.document.cartFilterData) || !angular.isObject(scope.document.cartFilterData)) {
						scope.document.cartFilterData = {};
					}
				};

				editorCtrl.init('Rbs_Price_Fee');
			}
		}
	}
	angular.module('RbsChange').directive('rbsDocumentEditorRbsPriceFee', Editor);
})();